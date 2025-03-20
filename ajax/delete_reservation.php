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
            SELECT draw_id, tickets 
            FROM reservations 
            WHERE id = ?
        ");
        $stmt->bind_param("i", $reservationId);
        $stmt->execute();
        $reservation = $stmt->get_result()->fetch_assoc();

        if (!$reservation) {
            throw new Exception('Reserva no encontrada');
        }

        // Liberar los tickets asociados
        $tickets = explode(',', $reservation['tickets']);
        foreach ($tickets as $ticket) {
            $stmt = $db->prepare("
                UPDATE tickets 
                SET reserved = 0,
                    blocked_until = NULL,
                    reservation_id = NULL
                WHERE draw_id = ? 
                AND ticket_number = ?
            ");
            $stmt->bind_param("is", $reservation['draw_id'], $ticket);
            $stmt->execute();
        }

        // Eliminar la reserva
        $stmt = $db->prepare("DELETE FROM reservations WHERE id = ?");
        $stmt->bind_param("i", $reservationId);
        $stmt->execute();

        $db->commit();

        echo json_encode([
            'success' => true,
            'message' => 'Reserva eliminada exitosamente'
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