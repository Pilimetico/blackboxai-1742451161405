<?php
session_start();

// Función para sanitizar input
function sanitize($input) {
    if (is_array($input)) {
        foreach($input as $key => $value) {
            $input[$key] = sanitize($value);
        }
    } else {
        $input = trim($input);
        $input = stripslashes($input);
        $input = htmlspecialchars($input, ENT_QUOTES, 'UTF-8');
    }
    return $input;
}

// Función para validar si el usuario está logueado
function isLoggedIn() {
    return isset($_SESSION['admin_id']) && !empty($_SESSION['admin_id']);
}

// Función para redirigir si no está logueado
function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: /admin/login.php');
        exit();
    }
}

// Función para generar números aleatorios únicos
function generateRandomTickets($total, $min, $max, $excludeNumbers = []) {
    $numbers = [];
    while (count($numbers) < $total) {
        $number = str_pad(mt_rand($min, $max), 3, '0', STR_PAD_LEFT);
        if (!in_array($number, $excludeNumbers) && !in_array($number, $numbers)) {
            $numbers[] = $number;
        }
    }
    return $numbers;
}

// Función para subir imágenes
function uploadImage($file, $destination, $allowedTypes = ['image/jpeg', 'image/png', 'image/gif']) {
    try {
        // Validar si hay error en la subida
        if ($file['error'] !== UPLOAD_ERR_OK) {
            throw new Exception('Error en la subida del archivo');
        }

        // Validar tipo de archivo
        if (!in_array($file['type'], $allowedTypes)) {
            throw new Exception('Tipo de archivo no permitido');
        }

        // Validar tamaño (max 5MB)
        if ($file['size'] > 5 * 1024 * 1024) {
            throw new Exception('El archivo es demasiado grande');
        }

        // Generar nombre único
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = uniqid() . '.' . $extension;
        $filepath = $destination . '/' . $filename;

        // Mover archivo
        if (!move_uploaded_file($file['tmp_name'], $filepath)) {
            throw new Exception('Error al mover el archivo');
        }

        return $filepath;
    } catch (Exception $e) {
        throw new Exception('Error al subir imagen: ' . $e->getMessage());
    }
}

// Función para formatear número de WhatsApp
function formatWhatsAppNumber($code, $number) {
    $code = trim(str_replace('+', '', $code));
    $number = trim(str_replace(['-', ' ', '(', ')'], '', $number));
    return $code . $number;
}

// Función para generar enlace de WhatsApp
function generateWhatsAppLink($number, $message) {
    $encodedMessage = urlencode($message);
    return "https://api.whatsapp.com/send?phone={$number}&text={$encodedMessage}";
}

// Función para generar mensaje de reserva
function generateReservationMessage($customerName, $drawName, $tickets) {
    return "¡Nueva reserva!\n\n" .
           "Cliente: {$customerName}\n" .
           "Sorteo: {$drawName}\n" .
           "Números: {$tickets}";
}

// Función para verificar disponibilidad de ticket
function isTicketAvailable($drawId, $ticketNumber) {
    $db = Database::getInstance();
    $stmt = $db->prepare("
        SELECT reserved, blocked_until 
        FROM tickets 
        WHERE draw_id = ? AND ticket_number = ?
    ");
    $stmt->bind_param("is", $drawId, $ticketNumber);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        // Si está reservado, no está disponible
        if ($row['reserved']) {
            return false;
        }
        
        // Si está bloqueado temporalmente y el bloqueo no ha expirado
        if ($row['blocked_until'] !== null && strtotime($row['blocked_until']) > time()) {
            return false;
        }
        
        return true;
    }
    
    return false;
}

// Función para bloquear tickets temporalmente
function blockTicketsTemporarily($drawId, $tickets, $minutes) {
    $db = Database::getInstance();
    $blockedUntil = date('Y-m-d H:i:s', strtotime("+{$minutes} minutes"));
    
    $db->beginTransaction();
    try {
        foreach ($tickets as $ticket) {
            $stmt = $db->prepare("
                UPDATE tickets 
                SET blocked_until = ? 
                WHERE draw_id = ? AND ticket_number = ? AND reserved = 0
            ");
            $stmt->bind_param("sis", $blockedUntil, $drawId, $ticket);
            $stmt->execute();
        }
        $db->commit();
        return true;
    } catch (Exception $e) {
        $db->rollback();
        return false;
    }
}

// Función para reservar tickets
function reserveTickets($drawId, $tickets, $reservationId) {
    $db = Database::getInstance();
    
    $db->beginTransaction();
    try {
        foreach ($tickets as $ticket) {
            $stmt = $db->prepare("
                UPDATE tickets 
                SET reserved = 1, 
                    reservation_id = ?,
                    blocked_until = NULL
                WHERE draw_id = ? AND ticket_number = ? AND reserved = 0
            ");
            $stmt->bind_param("iis", $reservationId, $drawId, $ticket);
            $stmt->execute();
        }
        $db->commit();
        return true;
    } catch (Exception $e) {
        $db->rollback();
        return false;
    }
}

// Función para liberar tickets bloqueados expirados
function releaseExpiredTickets() {
    $db = Database::getInstance();
    $stmt = $db->prepare("
        UPDATE tickets 
        SET blocked_until = NULL 
        WHERE blocked_until IS NOT NULL 
        AND blocked_until < NOW() 
        AND reserved = 0
    ");
    return $stmt->execute();
}

// Función para obtener configuración del sistema
function getSettings() {
    $db = Database::getInstance();
    $result = $db->query("SELECT * FROM settings WHERE id = 1");
    return $result->fetch_assoc();
}

// Función para mostrar mensajes de error/éxito
function showMessage($message, $type = 'success') {
    return "<div class='alert alert-{$type} mb-4 p-4 rounded'>{$message}</div>";
}

// Función para validar formato de número
function validateTicketNumber($number) {
    return preg_match('/^\d{3,4}$/', $number);
}

// Función para obtener estadísticas básicas
function getBasicStats() {
    $db = Database::getInstance();
    $stats = [];
    
    // Total de sorteos
    $result = $db->query("SELECT COUNT(*) as total FROM draws");
    $stats['total_draws'] = $result->fetch_assoc()['total'];
    
    // Total de reservas pendientes
    $result = $db->query("SELECT COUNT(*) as total FROM reservations WHERE status = 'pending'");
    $stats['pending_reservations'] = $result->fetch_assoc()['total'];
    
    // Total de reservas pagadas
    $result = $db->query("SELECT COUNT(*) as total FROM reservations WHERE status = 'paid'");
    $stats['paid_reservations'] = $result->fetch_assoc()['total'];
    
    return $stats;
}

// Función para paginar resultados
function paginate($query, $page = 1, $perPage = 10) {
    $db = Database::getInstance();
    
    // Contar total de registros
    $countResult = $db->query("SELECT COUNT(*) as total FROM ($query) as t");
    $total = $countResult->fetch_assoc()['total'];
    
    // Calcular offset
    $offset = ($page - 1) * $perPage;
    
    // Agregar LIMIT a la consulta
    $query .= " LIMIT $offset, $perPage";
    
    // Ejecutar consulta paginada
    $result = $db->query($query);
    
    return [
        'data' => $result->fetch_all(MYSQLI_ASSOC),
        'total' => $total,
        'pages' => ceil($total / $perPage),
        'current_page' => $page
    ];
}