<?php
session_start();
header('Content-Type: application/json');

function createResponse($success, $message) {
    return json_encode([
        'success' => $success,
        'message' => $message
    ]);
}

try {
    // Validar todos los campos requeridos
    $requiredFields = [
        'installation_mode', 'db_host', 'db_name', 'db_user', 'db_pass',
        'admin_user', 'admin_pass', 'whatsapp_code', 'whatsapp_number'
    ];

    foreach ($requiredFields as $field) {
        if (!isset($_POST[$field]) || empty($_POST[$field])) {
            throw new Exception("El campo {$field} es requerido");
        }
    }

    // Validar archivo de logo
    if (!isset($_FILES['logo']) || $_FILES['logo']['error'] !== UPLOAD_ERR_OK) {
        throw new Exception("Error al subir el logo");
    }

    // Validar tipo de imagen
    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
    if (!in_array($_FILES['logo']['type'], $allowedTypes)) {
        throw new Exception("El archivo debe ser una imagen (JPEG, PNG o GIF)");
    }

    // Crear directorios necesarios
    $directories = [
        '../uploads',
        '../uploads/logos',
        '../uploads/draw_images',
        '../config'
    ];

    foreach ($directories as $dir) {
        if (!file_exists($dir)) {
            if (!mkdir($dir, 0755, true)) {
                throw new Exception("No se pudo crear el directorio: {$dir}");
            }
        }
    }

    // Mover logo
    $logoPath = '../uploads/logos/' . time() . '_' . $_FILES['logo']['name'];
    if (!move_uploaded_file($_FILES['logo']['tmp_name'], $logoPath)) {
        throw new Exception("Error al mover el archivo de logo");
    }

    // Intentar conexión a la base de datos
    $conn = new mysqli($_POST['db_host'], $_POST['db_user'], $_POST['db_pass']);
    if ($conn->connect_error) {
        throw new Exception("Error de conexión: " . $conn->connect_error);
    }

    // Crear base de datos si no existe
    $sql = "CREATE DATABASE IF NOT EXISTS " . $_POST['db_name'];
    if (!$conn->query($sql)) {
        throw new Exception("Error al crear la base de datos: " . $conn->error);
    }

    // Seleccionar la base de datos
    $conn->select_db($_POST['db_name']);

    // Crear tablas necesarias
    $tables = [
        "CREATE TABLE IF NOT EXISTS admin (
            id INT AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(50) NOT NULL UNIQUE,
            password VARCHAR(255) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )",
        "CREATE TABLE IF NOT EXISTS settings (
            id INT AUTO_INCREMENT PRIMARY KEY,
            whatsapp_code VARCHAR(10) NOT NULL,
            whatsapp_number VARCHAR(50) NOT NULL,
            block_tickets TINYINT(1) DEFAULT 0,
            block_minutes INT DEFAULT 30,
            logo_path VARCHAR(255) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )",
        "CREATE TABLE IF NOT EXISTS draws (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(255) NOT NULL,
            description TEXT,
            image_path VARCHAR(255) NOT NULL,
            total_tickets INT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )",
        "CREATE TABLE IF NOT EXISTS tickets (
            id INT AUTO_INCREMENT PRIMARY KEY,
            draw_id INT NOT NULL,
            ticket_number VARCHAR(10) NOT NULL,
            reserved TINYINT(1) DEFAULT 0,
            blocked_until DATETIME NULL,
            reservation_id INT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (draw_id) REFERENCES draws(id) ON DELETE CASCADE
        )",
        "CREATE TABLE IF NOT EXISTS reservations (
            id INT AUTO_INCREMENT PRIMARY KEY,
            draw_id INT NOT NULL,
            customer_firstname VARCHAR(100) NOT NULL,
            customer_lastname VARCHAR(100) NOT NULL,
            phone VARCHAR(50) NOT NULL,
            tickets TEXT NOT NULL,
            status ENUM('pending', 'paid') DEFAULT 'pending',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (draw_id) REFERENCES draws(id) ON DELETE CASCADE
        )"
    ];

    foreach ($tables as $sql) {
        if (!$conn->query($sql)) {
            throw new Exception("Error al crear tabla: " . $conn->error);
        }
    }

    // Insertar admin
    $adminPass = password_hash($_POST['admin_pass'], PASSWORD_DEFAULT);
    $stmt = $conn->prepare("INSERT INTO admin (username, password) VALUES (?, ?)");
    $stmt->bind_param("ss", $_POST['admin_user'], $adminPass);
    if (!$stmt->execute()) {
        throw new Exception("Error al crear usuario admin: " . $stmt->error);
    }

    // Insertar configuración inicial
    $stmt = $conn->prepare("INSERT INTO settings (whatsapp_code, whatsapp_number, logo_path) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $_POST['whatsapp_code'], $_POST['whatsapp_number'], $logoPath);
    if (!$stmt->execute()) {
        throw new Exception("Error al guardar configuración: " . $stmt->error);
    }

    // Crear archivo de configuración
    $configContent = "<?php
    define('DB_HOST', '" . $_POST['db_host'] . "');
    define('DB_USER', '" . $_POST['db_user'] . "');
    define('DB_PASS', '" . $_POST['db_pass'] . "');
    define('DB_NAME', '" . $_POST['db_name'] . "');
    ";

    if (!file_put_contents('../config/config.php', $configContent)) {
        throw new Exception("Error al crear archivo de configuración");
    }

    // Crear archivo .htaccess para seguridad
    $htaccessContent = "Options -Indexes\n";
    if (!file_put_contents('../.htaccess', $htaccessContent)) {
        throw new Exception("Error al crear archivo .htaccess");
    }

    echo createResponse(true, "Instalación completada exitosamente");

} catch (Exception $e) {
    echo createResponse(false, $e->getMessage());
}