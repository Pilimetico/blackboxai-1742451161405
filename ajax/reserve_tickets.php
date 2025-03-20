<?php
require_once '../includes/db.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

try {
    // Validar parámetros
    if (!isset($_POST['draw_id']) || !isset($_POST['tickets']) || 
        !isset($_POST['firstname']) || !isset($_POST['lastname']) || !isset($_POST['phone'])) {
        throw new Exception('Parámetros incompletos');
    }

    $drawId = (int)$_POST['draw_id'];
    $tickets = array_map('trim', explode(',', $_POST['tickets']));
    $firstname = sanitize($_POST['firstname']);
    $lastname = sanitize($_POST['lastname']);
    $phone = sanitize($_POST['phone']);

    if (empty($tickets)) {
        throw new Exception('Debe seleccionar al menos un número');
    }

    $db = Database::getInstance();
    $settings = getSettings();

    // Verificar si el sorteo existe
    $stmt = $db->prepare("SELECT name FROM draws WHERE id = ?");
    $stmt->bind_param("i", $drawId);
    $stmt->execute();
    $draw = $stmt->get_result()->fetch_assoc();

    if (!$draw) {
        throw new Exception('Sorteo no encontrado');
    }

    // Iniciar transacción
    $db->beginTransaction();

    try {
        // Verificar disponibilidad de todos los tickets
        $ticketsStr = implode("','", array_map([$db, 'escape'], $tickets));
        $result = $db->query("
            SELECT ticket_number, reserved, blocked_until 
            FROM tickets 
            WHERE draw_id = {$drawId} 
            AND ticket_number IN ('{$ticketsStr}')
        ");

        $unavailableTickets = [];
        while ($ticket = $result->fetch_assoc()) {
            if ($ticket['reserved'] == 1 || 
                ($ticket['blocked_until'] !== null && strtotime($ticket['blocked_until']) > time())) {
                $unavailableTickets[] = $ticket['ticket_number'];
            }
        }

        if (!empty($unavailableTickets)) {
            throw new Exception('Algunos números ya no están disponibles: ' . implode(', ', $unavailableTickets));
        }

        // Crear la reserva
        $stmt = $db->prepare("
            INSERT INTO reservations 
            (draw_id, customer_firstname, customer_lastname, phone, tickets) 
            VALUES (?, ?, ?, ?, ?)
        ");
        $ticketsJson = implode(',', $tickets);
        $stmt->bind_param("issss", $drawId, $firstname, $lastname, $phone, $ticketsJson);
        $stmt->execute();
        $reservationId = $db->getLastId();

        // Bloquear o reservar tickets según configuración
        if ($settings['block_tickets']) {
            // Bloquear temporalmente
            blockTicketsTemporarily($drawId, $tickets, $settings['block_minutes']);
        } else {
            // Reservar directamente
            reserveTickets($drawId, $tickets, $reservationId);
        }

        // Generar mensaje para WhatsApp
        $message = generateReservationMessage(
            $firstname . ' ' . $lastname,
            $draw['name'],
            $ticketsJson
        );

        // Generar enlace de WhatsApp
        $whatsappNumber = formatWhatsAppNumber($settings['whatsapp_code'], $settings['whatsapp_number']);
        $whatsappUrl = generateWhatsAppLink($whatsappNumber, $message);

        $db->commit();

        echo json_encode([
            'success' => true,
            'message' => 'Reserva realizada con éxito',
            'whatsapp_url' => $whatsappUrl
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