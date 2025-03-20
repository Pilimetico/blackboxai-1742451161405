<?php
require_once '../includes/db.php';
require_once '../includes/functions.php';

$db = Database::getInstance();
$settings = getSettings();

// Validar ID del sorteo
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: index.php');
    exit();
}

$drawId = (int)$_GET['id'];

// Obtener información del sorteo
$stmt = $db->prepare("SELECT * FROM draws WHERE id = ?");
$stmt->bind_param("i", $drawId);
$stmt->execute();
$draw = $stmt->get_result()->fetch_assoc();

if (!$draw) {
    header('Location: index.php');
    exit();
}

// Procesar tickets preseleccionados si vienen de la máquina de la suerte
$preselectedTickets = [];
if (isset($_GET['tickets'])) {
    $preselectedTickets = explode(',', $_GET['tickets']);
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($draw['name']); ?> - Sistema de Rifas</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
        }
        .ticket {
            transition: all 0.3s ease;
        }
        .ticket:hover {
            transform: scale(1.05);
        }
        .ticket.selected {
            transform: scale(1.05);
            box-shadow: 0 0 0 2px #4F46E5;
        }
        .ticket.reserved {
            opacity: 0.5;
            cursor: not-allowed;
        }
        .reservation-modal {
            animation: fadeIn 0.3s ease;
        }
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
    </style>
</head>
<body class="bg-gray-100 min-h-screen">
    <!-- Header -->
    <header class="bg-gradient-to-r from-purple-600 to-blue-600 text-white shadow-lg">
        <div class="container mx-auto px-4 py-6">
            <div class="flex justify-between items-center">
                <a href="index.php" class="flex items-center space-x-2 text-white hover:text-gray-200 transition-colors duration-200">
                    <i class="fas fa-arrow-left"></i>
                    <span>Volver</span>
                </a>
                <h1 class="text-2xl font-bold"><?php echo htmlspecialchars($draw['name']); ?></h1>
                <div class="w-24"></div>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="container mx-auto px-4 py-8">
        <!-- Draw Details -->
        <div class="bg-white rounded-xl shadow-lg overflow-hidden mb-8">
            <div class="md:flex">
                <div class="md:w-1/3">
                    <img src="<?php echo htmlspecialchars($draw['image_path']); ?>" 
                         alt="<?php echo htmlspecialchars($draw['name']); ?>" 
                         class="w-full h-64 object-cover">
                </div>
                <div class="p-6 md:w-2/3">
                    <h2 class="text-3xl font-bold text-gray-800 mb-4"><?php echo htmlspecialchars($draw['name']); ?></h2>
                    <p class="text-gray-600 mb-6"><?php echo htmlspecialchars($draw['description']); ?></p>
                    
                    <!-- Progress Bar -->
                    <?php
                    $stmt = $db->prepare("SELECT COUNT(*) as total FROM tickets WHERE draw_id = ? AND reserved = 1");
                    $stmt->bind_param("i", $drawId);
                    $stmt->execute();
                    $reserved = $stmt->get_result()->fetch_assoc()['total'];
                    $percentage = ($reserved / $draw['total_tickets']) * 100;
                    ?>
                    <div class="w-full bg-gray-200 rounded-full h-2.5 mb-4">
                        <div class="bg-blue-600 h-2.5 rounded-full" style="width: <?php echo $percentage; ?>%"></div>
                    </div>
                    <p class="text-sm text-gray-500 mb-6">
                        <?php echo $reserved; ?> de <?php echo $draw['total_tickets']; ?> boletos vendidos
                    </p>
                </div>
            </div>
        </div>

        <!-- Ticket Search -->
        <div class="bg-white rounded-xl shadow-lg p-6 mb-8">
            <div class="flex space-x-4">
                <div class="flex-1">
                    <input type="text" id="ticketSearch" 
                           placeholder="Buscar número específico..." 
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <button onclick="searchTicket()" 
                        class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700 transition-colors duration-200">
                    Buscar
                </button>
            </div>
            <div id="searchResult" class="mt-4 hidden"></div>
        </div>

        <!-- Tickets Grid -->
        <div class="bg-white rounded-xl shadow-lg p-6">
            <h3 class="text-xl font-semibold mb-6">Selecciona tus números</h3>
            
            <!-- Range Filter -->
            <div class="flex space-x-4 mb-6">
                <?php
                $ranges = [];
                $rangeSize = min(100, ceil($draw['total_tickets'] / 5));
                for ($i = 1; $i <= $draw['total_tickets']; $i += $rangeSize) {
                    $end = min($i + $rangeSize - 1, $draw['total_tickets']);
                    $ranges[] = [$i, $end];
                }
                ?>
                <?php foreach ($ranges as $range): ?>
                <button onclick="filterRange(<?php echo $range[0]; ?>, <?php echo $range[1]; ?>)"
                        class="bg-gray-100 hover:bg-gray-200 text-gray-800 px-4 py-2 rounded transition-colors duration-200">
                    <?php echo str_pad($range[0], 3, '0', STR_PAD_LEFT); ?> - 
                    <?php echo str_pad($range[1], 3, '0', STR_PAD_LEFT); ?>
                </button>
                <?php endforeach; ?>
            </div>

            <!-- Tickets -->
            <div id="ticketsGrid" class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-4">
                <?php
                $stmt = $db->prepare("
                    SELECT ticket_number, reserved, blocked_until 
                    FROM tickets 
                    WHERE draw_id = ? 
                    ORDER BY CAST(ticket_number AS UNSIGNED)
                ");
                $stmt->bind_param("i", $drawId);
                $stmt->execute();
                $result = $stmt->get_result();

                while ($ticket = $result->fetch_assoc()):
                    $isReserved = $ticket['reserved'] == 1;
                    $isBlocked = $ticket['blocked_until'] !== null && strtotime($ticket['blocked_until']) > time();
                    $isPreselected = in_array($ticket['ticket_number'], $preselectedTickets);
                    $status = $isReserved ? 'reserved' : ($isBlocked ? 'blocked' : 'available');
                ?>
                <div class="ticket <?php echo $status; ?> <?php echo $isPreselected ? 'selected' : ''; ?>"
                     data-number="<?php echo $ticket['ticket_number']; ?>"
                     onclick="toggleTicket(this, '<?php echo $ticket['ticket_number']; ?>')"
                     data-range-start="<?php echo (int)$ticket['ticket_number']; ?>">
                    <div class="bg-gray-100 hover:bg-gray-200 rounded-lg p-4 text-center cursor-pointer">
                        <span class="text-xl font-bold <?php echo $isReserved || $isBlocked ? 'text-gray-400' : 'text-gray-800'; ?>">
                            <?php echo $ticket['ticket_number']; ?>
                        </span>
                    </div>
                </div>
                <?php endwhile; ?>
            </div>

            <!-- Selected Tickets Summary -->
            <div id="selectedTickets" class="mt-8 p-4 bg-gray-100 rounded-lg hidden">
                <h4 class="font-semibold mb-2">Boletos Seleccionados:</h4>
                <div id="selectedTicketsList" class="mb-4"></div>
                <div class="flex justify-between items-center">
                    <button onclick="clearSelection()" class="text-red-600 hover:text-red-700">
                        <i class="fas fa-trash"></i> Limpiar selección
                    </button>
                    <button onclick="openReservationModal()" 
                            class="bg-green-600 text-white px-6 py-2 rounded-lg hover:bg-green-700 transition-colors duration-200">
                        Apartar Boletos
                    </button>
                </div>
            </div>
        </div>
    </main>

    <!-- Reservation Modal -->
    <div id="reservationModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
        <div class="reservation-modal bg-white rounded-xl shadow-2xl p-8 max-w-md w-full mx-4">
            <h3 class="text-2xl font-bold text-gray-800 mb-6">Confirmar Reserva</h3>
            
            <?php if ($settings['block_tickets']): ?>
            <div class="bg-yellow-100 border-l-4 border-yellow-500 p-4 mb-6">
                <p class="text-yellow-700">
                    <i class="fas fa-clock mr-2"></i>
                    Los números seleccionados serán bloqueados por <?php echo $settings['block_minutes']; ?> minutos mientras se confirma el pago.
                </p>
            </div>
            <?php endif; ?>

            <form id="reservationForm" onsubmit="submitReservation(event)" class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Nombre</label>
                    <input type="text" name="firstname" required
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Apellido</label>
                    <input type="text" name="lastname" required
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Teléfono</label>
                    <input type="tel" name="phone" required
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div class="flex justify-end space-x-4 pt-4">
                    <button type="button" onclick="closeReservationModal()"
                            class="px-4 py-2 text-gray-600 hover:text-gray-800">
                        Cancelar
                    </button>
                    <button type="submit"
                            class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700 transition-colors duration-200">
                        Confirmar Reserva
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        let selectedTickets = <?php echo json_encode($preselectedTickets); ?>;

        function toggleTicket(element, number) {
            if (element.classList.contains('reserved') || element.classList.contains('blocked')) {
                return;
            }

            const index = selectedTickets.indexOf(number);
            if (index === -1) {
                selectedTickets.push(number);
                element.classList.add('selected');
            } else {
                selectedTickets.splice(index, 1);
                element.classList.remove('selected');
            }

            updateSelectedTicketsDisplay();
        }

        function updateSelectedTicketsDisplay() {
            const container = document.getElementById('selectedTickets');
            const list = document.getElementById('selectedTicketsList');
            
            if (selectedTickets.length > 0) {
                container.classList.remove('hidden');
                list.innerHTML = selectedTickets.map(ticket => 
                    `<span class="inline-block bg-white rounded px-3 py-1 m-1">${ticket}</span>`
                ).join('');
            } else {
                container.classList.add('hidden');
            }
        }

        function clearSelection() {
            selectedTickets = [];
            document.querySelectorAll('.ticket.selected').forEach(ticket => {
                ticket.classList.remove('selected');
            });
            updateSelectedTicketsDisplay();
        }

        function filterRange(start, end) {
            document.querySelectorAll('.ticket').forEach(ticket => {
                const number = parseInt(ticket.dataset.rangeStart);
                if (number >= start && number <= end) {
                    ticket.style.display = '';
                } else {
                    ticket.style.display = 'none';
                }
            });
        }

        function searchTicket() {
            const number = document.getElementById('ticketSearch').value.trim();
            if (!number) return;

            fetch(`/ajax/search_ticket.php?draw_id=<?php echo $drawId; ?>&number=${number}`)
                .then(response => response.json())
                .then(data => {
                    const resultDiv = document.getElementById('searchResult');
                    resultDiv.classList.remove('hidden');
                    
                    if (data.available) {
                        resultDiv.innerHTML = `
                            <div class="bg-green-100 border-l-4 border-green-500 p-4">
                                <div class="flex justify-between items-center">
                                    <p class="text-green-700">El número ${number} está disponible!</p>
                                    <button onclick="selectSearchedTicket('${number}')"
                                            class="bg-green-500 text-white px-4 py-1 rounded hover:bg-green-600">
                                        Seleccionar
                                    </button>
                                </div>
                            </div>`;
                    } else {
                        resultDiv.innerHTML = `
                            <div class="bg-red-100 border-l-4 border-red-500 p-4">
                                <p class="text-red-700">El número ${number} no está disponible.</p>
                            </div>`;
                    }
                });
        }

        function selectSearchedTicket(number) {
            const ticket = document.querySelector(`.ticket[data-number="${number}"]`);
            if (ticket && !ticket.classList.contains('selected')) {
                toggleTicket(ticket, number);
            }
            document.getElementById('searchResult').classList.add('hidden');
            document.getElementById('ticketSearch').value = '';
        }

        function openReservationModal() {
            if (selectedTickets.length === 0) return;
            document.getElementById('reservationModal').classList.remove('hidden');
            document.getElementById('reservationModal').classList.add('flex');
        }

        function closeReservationModal() {
            document.getElementById('reservationModal').classList.add('hidden');
            document.getElementById('reservationModal').classList.remove('flex');
        }

        function submitReservation(event) {
            event.preventDefault();
            const formData = new FormData(event.target);
            formData.append('draw_id', <?php echo $drawId; ?>);
            formData.append('tickets', selectedTickets.join(','));

            fetch('/ajax/reserve_tickets.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    window.location.href = data.whatsapp_url;
                    setTimeout(() => {
                        window.location.reload();
                    }, 1000);
                } else {
                    alert(data.message);
                }
            });
        }

        // Inicializar vista
        updateSelectedTicketsDisplay();
    </script>
</body>
</html>