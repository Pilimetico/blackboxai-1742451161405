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
        /* Scrollbar styles */
        .scrollbar-thin::-webkit-scrollbar {
            width: 6px;
        }
        .scrollbar-thin::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 3px;
        }
        .scrollbar-thin::-webkit-scrollbar-thumb {
            background: #888;
            border-radius: 3px;
        }
        .scrollbar-thin::-webkit-scrollbar-thumb:hover {
            background: #555;
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
            <div class="flex flex-col md:flex-row gap-4">
                <div class="flex-1">
                    <input type="text" id="ticketSearch" 
                           placeholder="Buscar número específico..." 
                           class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <button onclick="searchTicket()" 
                        class="w-full md:w-auto bg-blue-600 text-white px-6 py-3 rounded-xl hover:bg-blue-700 transition-colors duration-200 flex items-center justify-center">
                    <i class="fas fa-search mr-2"></i>
                    Buscar
                </button>
            </div>
            <div id="searchResult" class="mt-4 hidden"></div>
        </div>

        <!-- Tickets Grid -->
        <div class="bg-white rounded-xl shadow-lg p-6">
            <h3 class="text-xl font-semibold mb-6">Selecciona tus números</h3>
            
            <!-- Range Filter -->
            <div class="flex flex-wrap gap-4 mb-6">
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
                        class="bg-gray-100 hover:bg-gray-200 text-gray-800 px-4 py-2 rounded-xl transition-colors duration-200 text-sm">
                    <?php echo str_pad($range[0], 3, '0', STR_PAD_LEFT); ?> - 
                    <?php echo str_pad($range[1], 3, '0', STR_PAD_LEFT); ?>
                </button>
                <?php endforeach; ?>
            </div>

            <!-- Tickets Container with Scroll -->
            <div id="ticketsGrid" class="h-[500px] overflow-y-auto p-4 grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-4 rounded-xl bg-gray-50 scrollbar-thin">
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
                    <div class="bg-white hover:bg-gray-200 rounded-xl shadow-sm p-4 text-center cursor-pointer transition-all duration-200 hover:scale-105">
                        <span class="text-xl font-bold <?php echo $isReserved || $isBlocked ? 'text-gray-400' : 'text-gray-800'; ?>">
                            <?php echo $ticket['ticket_number']; ?>
                        </span>
                    </div>
                </div>
                <?php endwhile; ?>
            </div>

            <!-- Selected Tickets Summary -->
            <div id="selectedTickets" class="mt-8 p-4 bg-gray-100 rounded-xl hidden">
                <h4 class="font-semibold mb-2">Boletos Seleccionados:</h4>
                <div id="selectedTicketsList" class="mb-4"></div>
                <div class="flex flex-col md:flex-row justify-between items-center gap-4">
                    <button onclick="clearSelection()" class="text-red-600 hover:text-red-700">
                        <i class="fas fa-trash"></i> Limpiar selección
                    </button>
                    <button onclick="openReservationModal()" 
                            class="w-full md:w-auto bg-gradient-to-r from-green-500 to-green-600 text-white px-6 py-3 rounded-xl hover:from-green-600 hover:to-green-700 transition-all duration-200 transform hover:scale-105 shadow-md">
                        <i class="fas fa-check mr-2"></i>
                        Apartar Boletos
                    </button>
                </div>
            </div>
        </div>
    </main>

    <!-- Lucky Machine Modal -->
    <div id="luckyMachineModal" class="fixed inset-0 bg-black bg-opacity-75 hidden items-center justify-center z-50">
        <div class="lucky-machine-modal bg-white rounded-xl shadow-2xl p-8 max-w-md w-full mx-4 transform transition-all">
            <div class="text-center">
                <h3 class="text-2xl font-bold text-gray-800 mb-4">Máquina de la Suerte</h3>
                <div class="slot-machine-animation mb-6">
                    <img src="https://media1.giphy.com/media/v1.Y2lkPTc5MGI3NjExMGNiYjM4YTYyZDY4YmYzYjJiNzY1ZGY1ZWM5ZDM3ZmQxNjc2ZjE4YiZlcD12MV9pbnRlcm5hbF9naWZzX2dpZklkJmN0PWc/XGXN3Qc1xg9dYnQbPx/giphy.gif" 
                         alt="Slot Machine" 
                         class="w-48 h-48 mx-auto slot-machine rounded-lg shadow-lg">
                </div>
                <div class="grid grid-cols-2 md:grid-cols-3 gap-4 mb-6">
                    <button onclick="generateTickets(1)" 
                            class="bg-gradient-to-r from-blue-500 to-blue-600 hover:from-blue-600 hover:to-blue-700 text-white font-semibold py-3 rounded-xl transition-all duration-200 transform hover:scale-105 shadow-md">
                        1 Ticket
                    </button>
                    <button onclick="generateTickets(5)" 
                            class="bg-gradient-to-r from-purple-500 to-purple-600 hover:from-purple-600 hover:to-purple-700 text-white font-semibold py-3 rounded-xl transition-all duration-200 transform hover:scale-105 shadow-md">
                        5 Tickets
                    </button>
                    <button onclick="generateTickets(10)" 
                            class="bg-gradient-to-r from-green-500 to-green-600 hover:from-green-600 hover:to-green-700 text-white font-semibold py-3 rounded-xl transition-all duration-200 transform hover:scale-105 shadow-md">
                        10 Tickets
                    </button>
                    <button onclick="generateTickets(20)" 
                            class="bg-gradient-to-r from-yellow-500 to-yellow-600 hover:from-yellow-600 hover:to-yellow-700 text-white font-semibold py-3 rounded-xl transition-all duration-200 transform hover:scale-105 shadow-md">
                        20 Tickets
                    </button>
                    <button onclick="generateTickets(50)" 
                            class="bg-gradient-to-r from-red-500 to-red-600 hover:from-red-600 hover:to-red-700 text-white font-semibold py-3 rounded-xl transition-all duration-200 transform hover:scale-105 shadow-md">
                        50 Tickets
                    </button>
                </div>
                <div id="generatedTickets" class="hidden">
                    <h4 class="text-lg font-semibold mb-2">Tickets Generados:</h4>
                    <div id="ticketsList" class="bg-gray-100 p-4 rounded-xl mb-4 max-h-40 overflow-y-auto scrollbar-thin"></div>
                    <div class="flex flex-col md:flex-row gap-4">
                        <button onclick="saveTickets()" 
                                class="flex-1 bg-gradient-to-r from-green-500 to-green-600 hover:from-green-600 hover:to-green-700 text-white py-3 px-4 rounded-xl transition-all duration-200 transform hover:scale-105 shadow-md">
                            Guardar Boletos
                        </button>
                        <button onclick="spinAgain()" 
                                class="flex-1 bg-gradient-to-r from-blue-500 to-blue-600 hover:from-blue-600 hover:to-blue-700 text-white py-3 px-4 rounded-xl transition-all duration-200 transform hover:scale-105 shadow-md">
                            Volver a Girar
                        </button>
                    </div>
                </div>
            </div>
            <button onclick="closeLuckyMachine()" class="absolute top-4 right-4 text-gray-400 hover:text-gray-600">
                <i class="fas fa-times text-xl"></i>
            </button>
        </div>
    </div>

    <script>
        let currentDrawId = null;
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
                    `<span class="inline-block bg-white rounded-xl px-3 py-1 m-1 shadow-sm">${ticket}</span>`
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
                            <div class="bg-green-100 border-l-4 border-green-500 p-4 rounded-xl">
                                <div class="flex justify-between items-center">
                                    <p class="text-green-700">El número ${number} está disponible!</p>
                                    <button onclick="selectSearchedTicket('${number}')"
                                            class="bg-green-500 text-white px-4 py-2 rounded-xl hover:bg-green-600 transition-all duration-200">
                                        Seleccionar
                                    </button>
                                </div>
                            </div>`;
                    } else {
                        resultDiv.innerHTML = `
                            <div class="bg-red-100 border-l-4 border-red-500 p-4 rounded-xl">
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

        function openLuckyMachine(drawId) {
            currentDrawId = drawId;
            document.getElementById('luckyMachineModal').classList.remove('hidden');
            document.getElementById('luckyMachineModal').classList.add('flex');
            document.getElementById('generatedTickets').classList.add('hidden');
        }

        function closeLuckyMachine() {
            document.getElementById('luckyMachineModal').classList.add('hidden');
            document.getElementById('luckyMachineModal').classList.remove('flex');
            currentDrawId = null;
            selectedTickets = [];
        }

        function generateTickets(quantity) {
            fetch(`/ajax/generate_tickets.php?draw_id=${currentDrawId}&quantity=${quantity}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        selectedTickets = data.tickets;
                        const ticketsList = document.getElementById('ticketsList');
                        ticketsList.innerHTML = selectedTickets.map(ticket => 
                            `<span class="inline-block bg-white rounded-xl px-3 py-1 m-1 shadow-sm">${ticket}</span>`
                        ).join('');
                        document.getElementById('generatedTickets').classList.remove('hidden');
                    } else {
                        alert(data.message);
                    }
                });
        }

        function saveTickets() {
            if (selectedTickets.length > 0) {
                window.location.href = `details.php?id=${currentDrawId}&tickets=${selectedTickets.join(',')}`;
            }
        }

        function spinAgain() {
            selectedTickets = [];
            document.getElementById('generatedTickets').classList.add('hidden');
        }

        // Inicializar vista
        updateSelectedTicketsDisplay();
    </script>
</body>
</html>