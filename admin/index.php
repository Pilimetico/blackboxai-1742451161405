<?php
require_once '../includes/db.php';
require_once '../includes/functions.php';

// Verificar login
requireLogin();

$db = Database::getInstance();
$settings = getSettings();

// Obtener estadísticas
$stats = getBasicStats();

// Obtener últimas reservas
$latestReservations = $db->query("
    SELECT r.*, d.name as draw_name 
    FROM reservations r 
    JOIN draws d ON r.draw_id = d.id 
    ORDER BY r.created_at DESC 
    LIMIT 5
")->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Sistema de Rifas</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
        }
        .stat-card {
            transition: transform 0.3s ease;
        }
        .stat-card:hover {
            transform: translateY(-5px);
        }
        @media (max-width: 768px) {
            .mobile-sidebar {
                transform: translateX(-100%);
                transition: transform 0.3s ease-in-out;
            }
            .mobile-sidebar.active {
                transform: translateX(0);
            }
        }
    </style>
</head>
<body class="bg-gray-100">
    <!-- Mobile Header -->
    <div class="md:hidden bg-white shadow-sm fixed top-0 left-0 right-0 z-30">
        <div class="flex items-center justify-between p-4">
            <img src="<?php echo htmlspecialchars($settings['logo_path']); ?>" alt="Logo" class="h-8 w-auto">
            <button onclick="toggleSidebar()" class="text-gray-500 hover:text-gray-700">
                <i class="fas fa-bars text-xl"></i>
            </button>
        </div>
    </div>

    <div class="flex min-h-screen">
        <!-- Sidebar -->
        <aside id="sidebar" class="mobile-sidebar fixed md:relative w-64 bg-white shadow-lg h-full z-40 md:translate-x-0">
            <div class="p-6">
                <!-- Close button for mobile -->
                <div class="md:hidden flex justify-end mb-6">
                    <button onclick="toggleSidebar()" class="text-gray-500 hover:text-gray-700">
                        <i class="fas fa-times text-xl"></i>
                    </button>
                </div>

                <!-- Logo - Hidden on mobile -->
                <div class="hidden md:block mb-8">
                    <img src="<?php echo htmlspecialchars($settings['logo_path']); ?>" alt="Logo" class="h-12 w-auto">
                </div>

                <nav class="space-y-2">
                    <a href="index.php" class="flex items-center space-x-2 text-blue-600 bg-blue-50 px-4 py-3 rounded-xl">
                        <i class="fas fa-home"></i>
                        <span>Dashboard</span>
                    </a>
                    <a href="draws.php" class="flex items-center space-x-2 text-gray-600 hover:bg-gray-50 px-4 py-3 rounded-xl transition-colors duration-200">
                        <i class="fas fa-ticket-alt"></i>
                        <span>Sorteos</span>
                    </a>
                    <a href="reservations.php" class="flex items-center space-x-2 text-gray-600 hover:bg-gray-50 px-4 py-3 rounded-xl transition-colors duration-200">
                        <i class="fas fa-bookmark"></i>
                        <span>Reservas</span>
                    </a>
                    <a href="settings.php" class="flex items-center space-x-2 text-gray-600 hover:bg-gray-50 px-4 py-3 rounded-xl transition-colors duration-200">
                        <i class="fas fa-cog"></i>
                        <span>Configuración</span>
                    </a>
                    <a href="logout.php" class="flex items-center space-x-2 text-red-600 hover:bg-red-50 px-4 py-3 rounded-xl transition-colors duration-200">
                        <i class="fas fa-sign-out-alt"></i>
                        <span>Cerrar Sesión</span>
                    </a>
                </nav>
            </div>
        </aside>

        <!-- Main Content -->
        <main class="flex-1 md:ml-64 pt-16 md:pt-0">
            <!-- Header -->
            <header class="bg-white shadow-sm">
                <div class="px-6 py-4">
                    <h1 class="text-2xl font-bold text-gray-800">Dashboard</h1>
                </div>
            </header>

            <!-- Content -->
            <div class="p-6">
                <!-- Stats Grid -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                    <!-- Total Sorteos -->
                    <div class="stat-card bg-white rounded-xl shadow-sm p-6">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-600">Total Sorteos</p>
                                <p class="text-3xl font-bold text-gray-800"><?php echo $stats['total_draws']; ?></p>
                            </div>
                            <div class="bg-blue-100 p-3 rounded-full">
                                <i class="fas fa-ticket-alt text-blue-600 text-xl"></i>
                            </div>
                        </div>
                    </div>

                    <!-- Reservas Pendientes -->
                    <div class="stat-card bg-white rounded-xl shadow-sm p-6">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-600">Reservas Pendientes</p>
                                <p class="text-3xl font-bold text-gray-800"><?php echo $stats['pending_reservations']; ?></p>
                            </div>
                            <div class="bg-yellow-100 p-3 rounded-full">
                                <i class="fas fa-clock text-yellow-600 text-xl"></i>
                            </div>
                        </div>
                    </div>

                    <!-- Reservas Pagadas -->
                    <div class="stat-card bg-white rounded-xl shadow-sm p-6">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-600">Reservas Pagadas</p>
                                <p class="text-3xl font-bold text-gray-800"><?php echo $stats['paid_reservations']; ?></p>
                            </div>
                            <div class="bg-green-100 p-3 rounded-full">
                                <i class="fas fa-check text-green-600 text-xl"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Latest Reservations -->
                <div class="bg-white rounded-xl shadow-sm overflow-hidden">
                    <div class="p-6">
                        <h2 class="text-xl font-semibold text-gray-800 mb-4">Últimas Reservas</h2>
                        <div class="overflow-x-auto">
                            <div class="inline-block min-w-full align-middle">
                                <div class="overflow-hidden rounded-xl border">
                                    <table class="min-w-full divide-y divide-gray-200">
                                        <thead class="bg-gray-50">
                                            <tr>
                                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Cliente</th>
                                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Sorteo</th>
                                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tickets</th>
                                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Estado</th>
                                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Fecha</th>
                                                <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Acciones</th>
                                            </tr>
                                        </thead>
                                        <tbody class="bg-white divide-y divide-gray-200">
                                            <?php foreach ($latestReservations as $reservation): ?>
                                            <tr class="hover:bg-gray-50">
                                                <td class="px-6 py-4 whitespace-nowrap">
                                                    <div class="text-sm font-medium text-gray-900">
                                                        <?php echo htmlspecialchars($reservation['customer_firstname'] . ' ' . $reservation['customer_lastname']); ?>
                                                    </div>
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap">
                                                    <div class="text-sm text-gray-900">
                                                        <?php echo htmlspecialchars($reservation['draw_name']); ?>
                                                    </div>
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap">
                                                    <div class="text-sm text-gray-900">
                                                        <?php echo htmlspecialchars($reservation['tickets']); ?>
                                                    </div>
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap">
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
                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                    <?php echo date('d/m/Y H:i', strtotime($reservation['created_at'])); ?>
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                                    <?php if ($reservation['status'] === 'pending'): ?>
                                                    <button onclick="markAsPaid(<?php echo $reservation['id']; ?>)" 
                                                            class="text-green-600 hover:text-green-900 mr-3">
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
                            </div>
                        </div>
                        <div class="mt-4 text-right">
                            <a href="reservations.php" class="text-blue-600 hover:text-blue-700 font-medium">
                                Ver todas las reservas
                                <i class="fas fa-arrow-right ml-1"></i>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script>
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            sidebar.classList.toggle('active');
        }

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

        // Close sidebar when clicking outside on mobile
        document.addEventListener('click', function(event) {
            const sidebar = document.getElementById('sidebar');
            const sidebarButton = document.querySelector('button[onclick="toggleSidebar()"]');
            
            if (window.innerWidth < 768 && // Only on mobile
                sidebar.classList.contains('active') && // Sidebar is open
                !sidebar.contains(event.target) && // Click not in sidebar
                !sidebarButton.contains(event.target)) { // Click not on toggle button
                sidebar.classList.remove('active');
            }
        });
    </script>
</body>
</html>