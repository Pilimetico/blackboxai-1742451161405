<?php
require_once '../includes/db.php';
require_once '../includes/functions.php';

// Verificar login
requireLogin();

$db = Database::getInstance();

// Parámetros de filtro
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$perPage = 10;
$status = isset($_GET['status']) ? $_GET['status'] : '';
$drawId = isset($_GET['draw_id']) ? (int)$_GET['draw_id'] : '';
$search = isset($_GET['search']) ? sanitize($_GET['search']) : '';

// Construir consulta base
$query = "
    SELECT r.*, d.name as draw_name 
    FROM reservations r 
    JOIN draws d ON r.draw_id = d.id 
    WHERE 1=1
";

// Aplicar filtros
if ($status) {
    $query .= " AND r.status = '" . $db->escape($status) . "'";
}
if ($drawId) {
    $query .= " AND r.draw_id = " . $drawId;
}
if ($search) {
    $query .= " AND (
        r.customer_firstname LIKE '%" . $db->escape($search) . "%' OR 
        r.customer_lastname LIKE '%" . $db->escape($search) . "%' OR 
        r.tickets LIKE '%" . $db->escape($search) . "%'
    )";
}

// Obtener resultados paginados
$results = paginate($query . " ORDER BY r.created_at DESC", $page, $perPage);

// Obtener lista de sorteos para el filtro
$draws = $db->query("SELECT id, name FROM draws ORDER BY name")->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Reservas - Sistema de Rifas</title>
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
                    <a href="reservations.php" class="flex items-center space-x-2 text-blue-600 bg-blue-50 px-4 py-2 rounded-lg">
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
                <div class="px-6 py-4">
                    <h1 class="text-2xl font-bold text-gray-800">Gestión de Reservas</h1>
                </div>
            </header>

            <!-- Content -->
            <div class="p-6">
                <!-- Filters -->
                <div class="bg-white rounded-xl shadow-lg p-6 mb-6">
                    <form method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Sorteo</label>
                            <select name="draw_id" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <option value="">Todos</option>
                                <?php foreach ($draws as $draw): ?>
                                <option value="<?php echo $draw['id']; ?>" <?php echo $drawId == $draw['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($draw['name']); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Estado</label>
                            <select name="status" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <option value="">Todos</option>
                                <option value="pending" <?php echo $status === 'pending' ? 'selected' : ''; ?>>Pendiente</option>
                                <option value="paid" <?php echo $status === 'paid' ? 'selected' : ''; ?>>Pagado</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Buscar</label>
                            <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>"
                                   placeholder="Nombre, apellido o número..."
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>

                        <div class="flex items-end">
                            <button type="submit" class="w-full bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition-colors duration-200">
                                <i class="fas fa-search mr-2"></i>
                                Filtrar
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Reservations Table -->
                <div class="bg-white rounded-xl shadow-lg overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead>
                                <tr class="bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    <th class="px-6 py-3">Cliente</th>
                                    <th class="px-6 py-3">Sorteo</th>
                                    <th class="px-6 py-3">Tickets</th>
                                    <th class="px-6 py-3">Estado</th>
                                    <th class="px-6 py-3">Fecha</th>
                                    <th class="px-6 py-3">Acciones</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php foreach ($results['data'] as $reservation): ?>
                                <tr>
                                    <td class="px-6 py-4">
                                        <div class="text-sm font-medium text-gray-900">
                                            <?php echo htmlspecialchars($reservation['customer_firstname'] . ' ' . $reservation['customer_lastname']); ?>
                                        </div>
                                        <div class="text-sm text-gray-500">
                                            <?php echo htmlspecialchars($reservation['phone']); ?>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="text-sm text-gray-900">
                                            <?php echo htmlspecialchars($reservation['draw_name']); ?>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="text-sm text-gray-900">
                                            <?php echo htmlspecialchars($reservation['tickets']); ?>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <?php if ($reservation['status'] === 'pending'): ?>
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                            Pendiente
                                        </span>
                                        <?php else: ?>
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                            Pagado
                                        </span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="text-sm text-gray-900">
                                            <?php echo date('d/m/Y H:i', strtotime($reservation['created_at'])); ?>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 text-right text-sm font-medium space-x-2">
                                        <?php if ($reservation['status'] === 'pending'): ?>
                                        <button onclick="markAsPaid(<?php echo $reservation['id']; ?>)" 
                                                class="text-green-600 hover:text-green-900">
                                            <i class="fas fa-check"></i>
                                        </button>
                                        <?php endif; ?>
                                        <button onclick="deleteReservation(<?php echo $reservation['id']; ?>)" 
                                                class="text-red-600 hover:text-red-900">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <?php if ($results['pages'] > 1): ?>
                    <div class="bg-white px-4 py-3 border-t border-gray-200 sm:px-6">
                        <div class="flex items-center justify-between">
                            <div class="flex-1 flex justify-between sm:hidden">
                                <?php if ($page > 1): ?>
                                <a href="?page=<?php echo $page - 1; ?>&status=<?php echo $status; ?>&draw_id=<?php echo $drawId; ?>&search=<?php echo urlencode($search); ?>" 
                                   class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                                    Anterior
                                </a>
                                <?php endif; ?>
                                <?php if ($page < $results['pages']): ?>
                                <a href="?page=<?php echo $page + 1; ?>&status=<?php echo $status; ?>&draw_id=<?php echo $drawId; ?>&search=<?php echo urlencode($search); ?>" 
                                   class="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                                    Siguiente
                                </a>
                                <?php endif; ?>
                            </div>
                            <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
                                <div>
                                    <p class="text-sm text-gray-700">
                                        Mostrando 
                                        <span class="font-medium"><?php echo ($page - 1) * $perPage + 1; ?></span>
                                        a 
                                        <span class="font-medium"><?php echo min($page * $perPage, $results['total']); ?></span>
                                        de 
                                        <span class="font-medium"><?php echo $results['total']; ?></span>
                                        resultados
                                    </p>
                                </div>
                                <div>
                                    <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px" aria-label="Pagination">
                                        <?php for ($i = 1; $i <= $results['pages']; $i++): ?>
                                        <a href="?page=<?php echo $i; ?>&status=<?php echo $status; ?>&draw_id=<?php echo $drawId; ?>&search=<?php echo urlencode($search); ?>" 
                                           class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700 hover:bg-gray-50 <?php echo $i === $page ? 'bg-blue-50 text-blue-600' : ''; ?>">
                                            <?php echo $i; ?>
                                        </a>
                                        <?php endfor; ?>
                                    </nav>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>

    <script>
        function markAsPaid(id) {
            if (confirm('¿Marcar esta reserva como pagada?')) {
                fetch(`/ajax/mark_reservation_paid.php?id=${id}`)
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

        function deleteReservation(id) {
            if (confirm('¿Estás seguro de que deseas eliminar esta reserva?')) {
                fetch(`/ajax/delete_reservation.php?id=${id}`)
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
    </script>
</body>
</html>