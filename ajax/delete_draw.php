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
        throw new Exception('ID del sorteo no proporcionado');
    }

    $drawId = (int)$_GET['id'];
    $db = Database::getInstance();

    // Iniciar transacción
    $db->beginTransaction();

    try {
        // Verificar si hay reservas pagadas
        $stmt = $db->prepare("
            SELECT COUNT(*) as total 
            FROM reservations 
            WHERE draw_id = ? AND status = 'paid'
        ");
        $stmt->bind_param("i", $drawId);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();

        if ($result['total'] > 0) {
            throw new Exception('No se puede eliminar un sorteo con reservas pagadas');
        }

        // Obtener información del sorteo para eliminar la imagen
        $stmt = $db->prepare("SELECT image_path FROM draws WHERE id = ?");
        $stmt->bind_param("i", $drawId);
        $stmt->execute();
        $draw = $stmt->get_result()->fetch_assoc();

        if (!$draw) {
            throw new Exception('Sorteo no encontrado');
        }

        // Eliminar tickets
        $stmt = $db->prepare("DELETE FROM tickets WHERE draw_id = ?");
        $stmt->bind_param("i", $drawId);
        $stmt->execute();

        // Eliminar reservas pendientes
        $stmt = $db->prepare("DELETE FROM reservations WHERE draw_id = ? AND status = 'pending'");
        $stmt->bind_param("i", $drawId);
        $stmt->execute();

        // Eliminar sorteo
        $stmt = $db->prepare("DELETE FROM draws WHERE id = ?");
        $stmt->bind_param("i", $drawId);
        $stmt->execute();

        // Eliminar imagen si existe
        if ($draw['image_path'] && file_exists($draw['image_path'])) {
            unlink($draw['image_path']);
        }

        $db->commit();

        echo json_encode([
            'success' => true,
            'message' => 'Sorteo eliminado exitosamente'
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