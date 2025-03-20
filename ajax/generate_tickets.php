<?php
require_once '../includes/db.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

try {
    // Validar parámetros
    if (!isset($_GET['draw_id']) || !isset($_GET['quantity'])) {
        throw new Exception('Parámetros incompletos');
    }

    $drawId = (int)$_GET['draw_id'];
    $quantity = (int)$_GET['quantity'];

    // Validar cantidad
    if ($quantity < 1 || $quantity > 50) {
        throw new Exception('Cantidad inválida');
    }

    $db = Database::getInstance();

    // Obtener información del sorteo
    $stmt = $db->prepare("SELECT total_tickets FROM draws WHERE id = ?");
    $stmt->bind_param("i", $drawId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if (!$draw = $result->fetch_assoc()) {
        throw new Exception('Sorteo no encontrado');
    }

    // Obtener números ya reservados o bloqueados
    $stmt = $db->prepare("
        SELECT ticket_number 
        FROM tickets 
        WHERE draw_id = ? 
        AND (reserved = 1 OR (blocked_until IS NOT NULL AND blocked_until > NOW()))
    ");
    $stmt->bind_param("i", $drawId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $excludeNumbers = [];
    while ($row = $result->fetch_assoc()) {
        $excludeNumbers[] = $row['ticket_number'];
    }

    // Verificar si hay suficientes números disponibles
    if (count($excludeNumbers) + $quantity > $draw['total_tickets']) {
        throw new Exception('No hay suficientes números disponibles');
    }

    // Generar números aleatorios únicos
    $tickets = generateRandomTickets(
        $quantity, 
        1, 
        $draw['total_tickets'], 
        $excludeNumbers
    );

    // Formatear números a 3 dígitos
    $tickets = array_map(function($number) {
        return str_pad($number, 3, '0', STR_PAD_LEFT);
    }, $tickets);

    echo json_encode([
        'success' => true,
        'tickets' => $tickets
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}