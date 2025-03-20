<?php
require_once '../includes/db.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

// Verificar si es admin
if (!isLoggedIn()) {
    echo json_encode([
        'success' => false,
        'message' => 'No autorizado'
    ]);
    exit();
}

try {
    if (!isset($_GET['id'])) {
        throw new Exception('ID de reserva no proporcionado');
    }

    $reservationId = (int)$_GET['id'];
    $db = Database::getInstance();

    // Iniciar transacción
    $db->beginTransaction();

    try {
        // Obtener información de la reserva
        $stmt = $db->prepare("
            SELECT draw_id, tickets, status 
            FROM reservations 
            WHERE id = ?
        ");
        $stmt->bind_param("i", $reservationId);
        $stmt->execute();
        $reservation = $stmt->get_result()->fetch_assoc();

        if (!$reservation) {
            throw new Exception('Reserva no encontrada');
        }

        if ($reservation['status'] === 'paid') {
            throw new Exception('Esta reserva ya está marcada como pagada');
        }

        // Actualizar estado de la reserva
        $stmt = $db->prepare("
            UPDATE reservations 
            SET status = 'paid', 
                updated_at = CURRENT_TIMESTAMP 
            WHERE id = ?
        ");
        $stmt->bind_param("i", $reservationId);
        $stmt->execute();

        // Marcar tickets como reservados permanentemente
        $tickets = explode(',', $reservation['tickets']);
        foreach ($tickets as $ticket) {
            $stmt = $db->prepare("
                UPDATE tickets 
                SET reserved = 1,
                    blocked_until = NULL,
                    reservation_id = ?
                WHERE draw_id = ? 
                AND ticket_number = ?
            ");
            $stmt->bind_param("iis", $reservationId, $reservation['draw_id'], $ticket);
            $stmt->execute();
        }

        $db->commit();

        echo json_encode([
            'success' => true,
            'message' => 'Reserva marcada como pagada exitosamente'
        ]);

    } catch (Exception $e) {
        $db->rollback();
        throw $e;
    }

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}