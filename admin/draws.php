<?php
require_once '../includes/db.php';
require_once '../includes/functions.php';

// Verificar login
requireLogin();

$db = Database::getInstance();

// Procesar creación/edición de sorteo
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $id = isset($_POST['id']) ? (int)$_POST['id'] : null;
        $name = sanitize($_POST['name']);
        $description = sanitize($_POST['description']);
        $totalTickets = (int)$_POST['total_tickets'];

        if ($totalTickets < 1) {
            throw new Exception('El número de tickets debe ser mayor a 0');
        }

        // Procesar imagen
        $imagePath = '';
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $imagePath = uploadImage($_FILES['image'], '../uploads/draw_images');
        } elseif (!$id) {
            throw new Exception('La imagen es requerida para nuevos sorteos');
        }

        $db->beginTransaction();

        if ($id) {
            // Actualizar sorteo existente
            $stmt = $db->prepare("
                UPDATE draws 
                SET name = ?, 
                    description = ?" . 
                    ($imagePath ? ", image_path = ?" : "") . "
                WHERE id = ?
            ");

            if ($imagePath) {
                $stmt->bind_param("sssi", $name, $description, $imagePath, $id);
            } else {
                $stmt->bind_param("ssi", $name, $description, $id);
            }
            $stmt->execute();

        } else {
            // Crear nuevo sorteo
            $stmt = $db->prepare("
                INSERT INTO draws (name, description, image_path, total_tickets) 
                VALUES (?, ?, ?, ?)
            ");
            $stmt->bind_param("sssi", $name, $description, $imagePath, $totalTickets);
            $stmt->execute();
            
            $id = $db->getLastId();

            // Generar tickets
            for ($i = 1; $i <= $totalTickets; $i++) {
                $ticketNumber = str_pad($i, 3, '0', STR_PAD_LEFT);
                $stmt = $db->prepare("
                    INSERT INTO tickets (draw_id, ticket_number) 
                    VALUES (?, ?)
                ");
                $stmt->bind_param("is", $id, $ticketNumber);
                $stmt->execute();
            }
        }

        $db->commit();
        $success = true;
        $message = $id ? 'Sorteo actualizado exitosamente' : 'Sorteo creado exitosamente';

    } catch (Exception $e) {
        $db->rollback();
        $error = $e->getMessage();
    }
}

// Obtener lista de sorteos
$draws = $db->query("SELECT * FROM draws ORDER BY created_at DESC")->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Sorteos - Sistema de Rifas</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
        }
        .draw-card {
            transition: transform 0.3s ease;
        }
        .draw-card:hover {
            transform: translateY(-5px);
        }
    </style>
</head>
<body class="bg-gray-100">
    <div class="flex h-screen">
        <!-- Sidebar -->
        <aside class="w-64 bg-white shadow-lg">
            <div class="p-6">
                <nav class="space-y-2">
                    <a href="index.php" class="flex items-center space-x-2 text-gray-600 hover:bg-gray-50 px-4 py-2 rounded-lg transition-colors duration-200">
                        <i class="fas fa-home"></i>
                        <span>Dashboard</span>
                    </a>
                    <a href="draws.php" class="flex items-center space-x-2 text-blue-600 bg-blue-50 px-4 py-2 rounded-lg">
                        <i class="fas fa-ticket-alt"></i>
                        <span>Sorteos</span>
                    </a>
                    <a href="reservations.php" class="flex items-center space-x-2 text-gray-600 hover:bg-gray-50 px-4 py-2 rounded-lg transition-colors duration-200">
                        <i class="fas fa-bookmark"></i>
                        <span>Reservas</span>
                    </a>
                    <a href="settings.php" class="flex items-center space-x-2 text-gray-600 hover:bg-gray-50 px-4 py-2 rounded-lg transition-colors duration-200">
                        <i class="fas fa-cog"></i>
                        <span>Configuración</span>
                    </a>
                    <a href="logout.php" class="flex items-center space-x-2 text-red-600 hover:bg-red-50 px-4 py-2 rounded-lg transition-colors duration-200">
                        <i class="fas fa-sign-out-alt"></i>
                        <span>Cerrar Sesión</span>
                    </a>
                </nav>
            </div>
        </aside>

        <!-- Main Content -->
        <main class="flex-1 overflow-y-auto">
            <!-- Header -->
            <header class="bg-white shadow">
                <div class="px-6 py-4 flex justify-between items-center">
                    <h1 class="text-2xl font-bold text-gray-800">Gestión de Sorteos</h1>
                    <button onclick="openDrawModal()" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition-colors duration-200">
                        <i class="fas fa-plus mr-2"></i>
                        Nuevo Sorteo
                    </button>
                </div>
            </header>

            <!-- Content -->
            <div class="p-6">
                <?php if (isset($success) && $success): ?>
                <div class="bg-green-100 border-l-4 border-green-500 p-4 mb-6">
                    <p class="text-green-700"><?php echo $message; ?></p>
                </div>
                <?php endif; ?>

                <?php if (isset($error)): ?>
                <div class="bg-red-100 border-l-4 border-red-500 p-4 mb-6">
                    <p class="text-red-700"><?php echo $error; ?></p>
                </div>
                <?php endif; ?>

                <!-- Draws Grid -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <?php foreach ($draws as $draw): ?>
                    <div class="draw-card bg-white rounded-xl shadow-lg overflow-hidden">
                        <img src="<?php echo htmlspecialchars($draw['image_path']); ?>" 
                             alt="<?php echo htmlspecialchars($draw['name']); ?>" 
                             class="w-full h-48 object-cover">
                        <div class="p-6">
                            <h3 class="text-xl font-bold text-gray-800 mb-2">
                                <?php echo htmlspecialchars($draw['name']); ?>
                            </h3>
                            <p class="text-gray-600 mb-4">
                                <?php echo htmlspecialchars($draw['description']); ?>
                            </p>
                            <div class="flex justify-between items-center">
                                <span class="text-sm text-gray-500">
                                    <?php echo $draw['total_tickets']; ?> tickets
                                </span>
                                <div class="space-x-2">
                                    <button onclick="editDraw(<?php echo htmlspecialchars(json_encode($draw)); ?>)" 
                                            class="text-blue-600 hover:text-blue-700">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button onclick="deleteDraw(<?php echo $draw['id']; ?>)" 
                                            class="text-red-600 hover:text-red-700">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </main>
    </div>

    <!-- Draw Modal -->
    <div id="drawModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
        <div class="bg-white rounded-xl shadow-2xl p-8 max-w-2xl w-full mx-4">
            <h2 id="modalTitle" class="text-2xl font-bold text-gray-800 mb-6">Nuevo Sorteo</h2>
            
            <form id="drawForm" method="POST" enctype="multipart/form-data" class="space-y-6">
                <input type="hidden" id="drawId" name="id">
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Nombre</label>
                    <input type="text" id="drawName" name="name" required
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Descripción</label>
                    <textarea id="drawDescription" name="description" required
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                            rows="3"></textarea>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Imagen</label>
                    <input type="file" id="drawImage" name="image" accept="image/*"
                           class="w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                </div>

                <div id="totalTicketsField">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Número de Tickets</label>
                    <input type="number" id="drawTotalTickets" name="total_tickets" min="1"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>

                <div class="flex justify-end space-x-4 pt-4">
                    <button type="button" onclick="closeDrawModal()"
                            class="px-4 py-2 text-gray-600 hover:text-gray-800">
                        Cancelar
                    </button>
                    <button type="submit"
                            class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700 transition-colors duration-200">
                        Guardar
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openDrawModal() {
            document.getElementById('modalTitle').textContent = 'Nuevo Sorteo';
            document.getElementById('drawForm').reset();
            document.getElementById('drawId').value = '';
            document.getElementById('totalTicketsField').style.display = 'block';
            document.getElementById('drawModal').classList.remove('hidden');
            document.getElementById('drawModal').classList.add('flex');
        }

        function closeDrawModal() {
            document.getElementById('drawModal').classList.add('hidden');
            document.getElementById('drawModal').classList.remove('flex');
        }

        function editDraw(draw) {
            document.getElementById('modalTitle').textContent = 'Editar Sorteo';
            document.getElementById('drawId').value = draw.id;
            document.getElementById('drawName').value = draw.name;
            document.getElementById('drawDescription').value = draw.description;
            document.getElementById('totalTicketsField').style.display = 'none';
            document.getElementById('drawModal').classList.remove('hidden');
            document.getElementById('drawModal').classList.add('flex');
        }

        function deleteDraw(id) {
            if (confirm('¿Estás seguro de que deseas eliminar este sorteo?')) {
                fetch(`/ajax/delete_draw.php?id=${id}`)
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            window.location.reload();
                        } else {
                            alert(data.message);
                        }
                    });
            }
        }

        // Cerrar modal con Escape
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeDrawModal();
            }
        });
    </script>
</body>
</html>