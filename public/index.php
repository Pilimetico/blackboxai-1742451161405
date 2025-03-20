<?php
require_once '../includes/db.php';
require_once '../includes/functions.php';

$db = Database::getInstance();
$settings = getSettings();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistema de Rifas</title>
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
        .lucky-machine-modal {
            animation: fadeIn 0.3s ease;
        }
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        .slot-machine {
            animation: shake 0.5s ease infinite;
        }
        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-5px); }
            75% { transform: translateX(5px); }
        }
    </style>
</head>
<body class="bg-gray-100 min-h-screen">
    <!-- Header -->
    <header class="bg-gradient-to-r from-purple-600 to-blue-600 text-white shadow-lg">
        <div class="container mx-auto px-4 py-6">
            <div class="flex justify-between items-center">
                <div class="flex items-center">
                    <img src="<?php echo htmlspecialchars($settings['logo_path']); ?>" alt="Logo" class="h-12 w-auto mr-4">
                    <h1 class="text-3xl font-bold">Sistema de Rifas</h1>
                </div>
                <a href="/admin" class="flex items-center space-x-2 bg-white bg-opacity-20 hover:bg-opacity-30 rounded-full px-4 py-2 transition-all duration-300">
                    <i class="fas fa-user"></i>
                    <span>Admin</span>
                </a>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="container mx-auto px-4 py-8">
        <!-- Draws Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
            <?php
            $result = $db->query("SELECT * FROM draws ORDER BY created_at DESC");
            while ($draw = $result->fetch_assoc()):
            ?>
            <div class="draw-card bg-white rounded-xl shadow-lg overflow-hidden">
                <img src="<?php echo htmlspecialchars($draw['image_path']); ?>" alt="<?php echo htmlspecialchars($draw['name']); ?>" class="w-full h-48 object-cover">
                <div class="p-6">
                    <h2 class="text-2xl font-bold text-gray-800 mb-2"><?php echo htmlspecialchars($draw['name']); ?></h2>
                    <p class="text-gray-600 mb-4"><?php echo htmlspecialchars($draw['description']); ?></p>
                    
                    <!-- Progress Bar -->
                    <?php
                    $stmt = $db->prepare("SELECT COUNT(*) as total FROM tickets WHERE draw_id = ? AND reserved = 1");
                    $stmt->bind_param("i", $draw['id']);
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

                    <div class="flex space-x-4">
                        <button onclick="openLuckyMachine(<?php echo $draw['id']; ?>)" 
                                class="flex-1 bg-gradient-to-r from-purple-500 to-indigo-500 text-white py-2 px-4 rounded-lg hover:from-purple-600 hover:to-indigo-600 transition-all duration-300 flex items-center justify-center space-x-2">
                            <i class="fas fa-dice"></i>
                            <span>Máquina de la Suerte</span>
                        </button>
                        <a href="details.php?id=<?php echo $draw['id']; ?>" 
                           class="flex-1 bg-gradient-to-r from-blue-500 to-teal-500 text-white py-2 px-4 rounded-lg hover:from-blue-600 hover:to-teal-600 transition-all duration-300 flex items-center justify-center space-x-2">
                            <i class="fas fa-ticket-alt"></i>
                            <span>Ver Boletos</span>
                        </a>
                    </div>
                </div>
            </div>
            <?php endwhile; ?>
        </div>
    </main>

    <!-- Lucky Machine Modal -->
    <div id="luckyMachineModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
        <div class="lucky-machine-modal bg-white rounded-xl shadow-2xl p-8 max-w-md w-full mx-4">
            <div class="text-center">
                <h3 class="text-2xl font-bold text-gray-800 mb-4">Máquina de la Suerte</h3>
                <div class="slot-machine-animation mb-6">
                    <img src="/assets/images/slot-machine.gif" alt="Slot Machine" class="w-32 h-32 mx-auto slot-machine">
                </div>
                <div class="grid grid-cols-3 gap-4 mb-6">
                    <button onclick="generateTickets(1)" class="bg-blue-100 hover:bg-blue-200 text-blue-800 font-semibold py-2 rounded transition-colors duration-200">1 Ticket</button>
                    <button onclick="generateTickets(5)" class="bg-purple-100 hover:bg-purple-200 text-purple-800 font-semibold py-2 rounded transition-colors duration-200">5 Tickets</button>
                    <button onclick="generateTickets(10)" class="bg-green-100 hover:bg-green-200 text-green-800 font-semibold py-2 rounded transition-colors duration-200">10 Tickets</button>
                    <button onclick="generateTickets(20)" class="bg-yellow-100 hover:bg-yellow-200 text-yellow-800 font-semibold py-2 rounded transition-colors duration-200">20 Tickets</button>
                    <button onclick="generateTickets(50)" class="bg-red-100 hover:bg-red-200 text-red-800 font-semibold py-2 rounded transition-colors duration-200">50 Tickets</button>
                </div>
                <div id="generatedTickets" class="hidden">
                    <h4 class="text-lg font-semibold mb-2">Tickets Generados:</h4>
                    <div id="ticketsList" class="bg-gray-100 p-4 rounded mb-4 max-h-40 overflow-y-auto"></div>
                    <div class="flex space-x-4">
                        <button onclick="saveTickets()" class="flex-1 bg-green-500 hover:bg-green-600 text-white py-2 px-4 rounded transition-colors duration-200">
                            Guardar Boletos
                        </button>
                        <button onclick="spinAgain()" class="flex-1 bg-blue-500 hover:bg-blue-600 text-white py-2 px-4 rounded transition-colors duration-200">
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
        let selectedTickets = [];

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
                            `<span class="inline-block bg-white rounded px-3 py-1 m-1">${ticket}</span>`
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
    </script>
</body>
</html>