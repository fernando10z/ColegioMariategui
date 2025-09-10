<?php
// Evitar cualquier output antes de las cabeceras JSON
if (ob_get_level()) {
    ob_end_clean();
}

// Configuración de base de datos
$host = 'innovatesc.com.pe';
$dbname = 'innovat2_portafolio_estudiantil';
$username = 'innovat2_portafolio_estudiantil';
$password = '8bm^J9q(_az^8r_p';

try {
    // Crear conexión PDO
    $pdo = new PDO(
        "mysql:host=$host;dbname=$dbname;charset=utf8mb4",
        $username,
        $password,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
        ]
    );
    
    // NO enviar ningún output aquí para evitar problemas con JSON
    // Si necesitas debug, usa error_log() en lugar de echo/print
    
} catch (PDOException $e) {
    // Log del error sin output
    error_log("Error de conexión BD: " . $e->getMessage());
    
    // Solo si NO es una petición AJAX, mostrar error
    if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || 
        strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest') {
        die("Error de conexión a la base de datos");
    }
    
    // Para peticiones AJAX, retornar JSON de error
    if (!headers_sent()) {
        header('Content-Type: application/json; charset=utf-8');
        http_response_code(500);
    }
    echo json_encode([
        'success' => false,
        'message' => 'Error de conexión a la base de datos'
    ]);
    exit;
}
?>