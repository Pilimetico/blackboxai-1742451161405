<?php
require_once '../includes/db.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

try {
    // Validar parámetros
    if (!isset($_GET['draw_id']) || !isset($_GET['number'])) {
        throw new Exception('Parámetros incompletos');
    }

    $drawId = (int)$_GET['draw_id'];
    $number = trim($_GET['number']);

    // Validar formato del número
    if (!validateTicketNumber($number)) {
        throw new Exception('Formato de número inválido');
    }

    // Formatear número a 3 dígitos
    $number = str_pad($number, 3, '0', STR_PAD_LEFT);

    $db = Database::getInstance();

    // Verificar si el sorteo existe
    $stmt = $db->prepare("SELECT id FROM draws WHERE id = ?");
    $stmt->bind_param("i", $drawId);
    $stmt->execute();
    if (!$stmt->get_result()->fetch_assoc()) {
        throw new Exception('Sorteo no encontrado');
    }

    // Verificar disponibilidad del ticket
    $stmt = $db->prepare("
        SELECT reserved, blocked_until 
        FROM tickets 
        WHERE draw_id = ? AND ticket_number = ?
    ");
    $stmt->bind_param("is", $drawId, $number);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($ticket = $result->fetch_assoc()) {
        $isReserved = $ticket['reserved'] == 1;
        $isBlocked = $ticket['blocked_until'] !== null && strtotime($ticket['blocked_until']) > time();

        if ($isReserved) {
            echo json_encode([
                'success' => true,
                'available' => false,
                'message' => 'Este número ya está reservado'
            ]);
        } elseif ($isBlocked) {
            echo json_encode([
                'success' => true,
                'available' => false,
                'message' => 'Este número está temporalmente bloqueado'
            ]);
        } else {
            echo json_encode([
                'success' => true,
                'available' => true,
                'message' => 'Número disponible'
            ]);
        }
    } else {
        echo json_encode([
            'success' => true,
            'available' => false,
            'message' => 'Número no encontrado en este sorteo'
        ]);
    }

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}