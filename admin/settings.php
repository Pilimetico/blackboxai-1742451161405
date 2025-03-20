<?php
require_once '../includes/db.php';
require_once '../includes/functions.php';

// Verificar login
requireLogin();

$db = Database::getInstance();
$settings = getSettings();
$success = $error = '';

// Procesar actualización de configuración
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $whatsappCode = sanitize($_POST['whatsapp_code']);
        $whatsappNumber = sanitize($_POST['whatsapp_number']);
        $blockTickets = isset($_POST['block_tickets']) ? 1 : 0;
        $blockMinutes = isset($_POST['block_minutes']) ? (int)$_POST['block_minutes'] : 30;

        // Validar número de WhatsApp
        if (empty($whatsappCode) || empty($whatsappNumber)) {
            throw new Exception('El código de país y número de WhatsApp son requeridos');
        }

        // Procesar logo si se subió uno nuevo
        $logoPath = $settings['logo_path'];
        if (isset($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
            $logoPath = uploadImage($_FILES['logo'], '../uploads/logos');
        }

        // Actualizar configuración
        $stmt = $db->prepare("
            UPDATE settings 
            SET whatsapp_code = ?,
                whatsapp_number = ?,
                block_tickets = ?,
                block_minutes = ?,
                logo_path = ?,
                updated_at = CURRENT_TIMESTAMP 
            WHERE id = 1
        ");
        $stmt->bind_param("sssis", $whatsappCode, $whatsappNumber, $blockTickets, $blockMinutes, $logoPath);
        
        if ($stmt->execute()) {
            $success = 'Configuración actualizada exitosamente';
            $settings = getSettings(); // Recargar configuración
        } else {
            throw new Exception('Error al actualizar la configuración');
        }

    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Configuración - Sistema de Rifas</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
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
                    <a href="draws.php" class="flex items-center space-x-2 text-gray-600 hover:bg-gray-50 px-4 py-2 rounded-lg transition-colors duration-200">
                        <i class="fas fa-ticket-alt"></i>
                        <span>Sorteos</span>
                    </a>
                    <a href="reservations.php" class="flex items-center space-x-2 text-gray-600 hover:bg-gray-50 px-4 py-2 rounded-lg transition-colors duration-200">
                        <i class="fas fa-bookmark"></i>
                        <span>Reservas</span>
                    </a>
                    <a href="settings.php" class="flex items-center space-x-2 text-blue-600 bg-blue-50 px-4 py-2 rounded-lg">
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
                <div class="px-6 py-4">
                    <h1 class="text-2xl font-bold text-gray-800">Configuración del Sistema</h1>
                </div>
            </header>

            <!-- Content -->
            <div class="p-6">
                <?php if ($success): ?>
                <div class="bg-green-100 border-l-4 border-green-500 p-4 mb-6">
                    <p class="text-green-700"><?php echo $success; ?></p>
                </div>
                <?php endif; ?>

                <?php if ($error): ?>
                <div class="bg-red-100 border-l-4 border-red-500 p-4 mb-6">
                    <p class="text-red-700"><?php echo $error; ?></p>
                </div>
                <?php endif; ?>

                <!-- Settings Form -->
                <div class="bg-white rounded-xl shadow-lg p-6">
                    <form method="POST" enctype="multipart/form-data" class="space-y-6">
                        <!-- Logo Section -->
                        <div>
                            <h2 class="text-lg font-semibold text-gray-800 mb-4">Logo del Sistema</h2>
                            <div class="flex items-center space-x-6">
                                <img src="<?php echo htmlspecialchars($settings['logo_path']); ?>" 
                                     alt="Logo actual" 
                                     class="h-20 w-auto">
                                <div class="flex-1">
                                    <input type="file" name="logo" accept="image/*"
                                           class="w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                                    <p class="mt-1 text-sm text-gray-500">
                                        Formatos permitidos: JPEG, PNG, GIF. Tamaño máximo: 5MB
                                    </p>
                                </div>
                            </div>
                        </div>

                        <hr class="my-6">

                        <!-- WhatsApp Section -->
                        <div>
                            <h2 class="text-lg font-semibold text-gray-800 mb-4">Configuración de WhatsApp</h2>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">
                                        Código de País
                                    </label>
                                    <input type="text" name="whatsapp_code" 
                                           value="<?php echo htmlspecialchars($settings['whatsapp_code']); ?>"
                                           placeholder="+57"
                                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">
                                        Número de WhatsApp
                                    </label>
                                    <input type="text" name="whatsapp_number" 
                                           value="<?php echo htmlspecialchars($settings['whatsapp_number']); ?>"
                                           placeholder="3001234567"
                                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                                </div>
                            </div>
                        </div>

                        <hr class="my-6">

                        <!-- Ticket Blocking Section -->
                        <div>
                            <h2 class="text-lg font-semibold text-gray-800 mb-4">Bloqueo Temporal de Tickets</h2>
                            <div class="space-y-4">
                                <div class="flex items-center">
                                    <input type="checkbox" name="block_tickets" id="block_tickets"
                                           <?php echo $settings['block_tickets'] ? 'checked' : ''; ?>
                                           class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                                    <label for="block_tickets" class="ml-2 block text-sm text-gray-900">
                                        Bloquear temporalmente los números mientras se confirma el pago
                                    </label>
                                </div>
                                <div id="blockMinutesField" class="<?php echo $settings['block_tickets'] ? '' : 'hidden'; ?>">
                                    <label class="block text-sm font-medium text-gray-700 mb-1">
                                        Tiempo de bloqueo (minutos)
                                    </label>
                                    <input type="number" name="block_minutes" 
                                           value="<?php echo $settings['block_minutes']; ?>"
                                           min="1" max="1440"
                                           class="w-full md:w-1/3 px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                                </div>
                            </div>
                        </div>

                        <!-- Submit Button -->
                        <div class="pt-6">
                            <button type="submit" 
                                    class="w-full md:w-auto bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700 transition-colors duration-200">
                                Guardar Cambios
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </main>
    </div>

    <script>
        // Toggle block minutes field visibility
        document.getElementById('block_tickets').addEventListener('change', function() {
            document.getElementById('blockMinutesField').classList.toggle('hidden', !this.checked);
        });
    </script>
</body>
</html>