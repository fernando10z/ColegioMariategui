<?php
// Limpiar cualquier output previo
ob_start();
ob_clean();

// Evitar acceso directo
if (!defined('SECURE_ACCESS')) {
    define('SECURE_ACCESS', true);
}

// Configurar manejo de errores para evitar output HTML
error_reporting(0);
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);

// Incluir la configuración de base de datos
$config_path = __DIR__ . '/../../config/bd.php';
if (!file_exists($config_path)) {
    $config_path = __DIR__ . '/../config/bd.php';
    if (!file_exists($config_path)) {
        $config_path = dirname(dirname(__DIR__)) . '/config/bd.php';
    }
}

if (file_exists($config_path)) {
    require_once $config_path;
} else {
    ob_clean();
    header('Content-Type: application/json; charset=utf-8');
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error: No se pudo encontrar el archivo de configuración de base de datos'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// Limpiar buffer y configurar cabeceras para JSON
ob_clean();
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-cache, must-revalidate');
header('Pragma: no-cache');

// Función para enviar respuesta JSON
function sendJsonResponse($success, $message, $data = null, $statusCode = 200) {
    if (ob_get_length()) {
        ob_clean();
    }
    
    header('Content-Type: application/json; charset=utf-8');
    http_response_code($statusCode);
    
    echo json_encode([
        'success' => $success,
        'message' => $message,
        'data' => $data,
        'timestamp' => date('c')
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    // Verificar método de solicitud
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        sendJsonResponse(false, 'Método no permitido', null, 405);
    }
    
    // Obtener acción
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        
        case 'get_all_active':
            // Obtener todas las competencias activas
            $stmt = $pdo->prepare("
                SELECT c.*, 
                       ac.nombre as area_curricular_nombre,
                       ac.color_identificacion as area_color,
                       ne.nombre as nivel_educativo_nombre
                FROM competencias c
                LEFT JOIN areas_curriculares ac ON c.area_curricular_id = ac.id
                LEFT JOIN niveles_educativos ne ON c.nivel_educativo_id = ne.id
                WHERE c.estado = 1 AND ac.estado = 1 AND ne.estado = 1
                ORDER BY ac.nombre, c.nombre
            ");
            $stmt->execute();
            $competencias = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            sendJsonResponse(true, 'Competencias obtenidas correctamente', $competencias);
            break;
            
        case 'get_by_area':
            // Obtener competencias por área curricular
            $area_id = intval($_POST['area_id'] ?? 0);
            
            if ($area_id <= 0) {
                sendJsonResponse(false, 'ID de área inválido');
            }
            
            $stmt = $pdo->prepare("
                SELECT c.*, 
                       ac.nombre as area_curricular_nombre,
                       ne.nombre as nivel_educativo_nombre
                FROM competencias c
                LEFT JOIN areas_curriculares ac ON c.area_curricular_id = ac.id
                LEFT JOIN niveles_educativos ne ON c.nivel_educativo_id = ne.id
                WHERE c.area_curricular_id = ? AND c.estado = 1
                ORDER BY c.nombre
            ");
            $stmt->execute([$area_id]);
            $competencias = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            sendJsonResponse(true, 'Competencias obtenidas correctamente', $competencias);
            break;
            
        case 'get_by_nivel':
            // Obtener competencias por nivel educativo
            $nivel_id = intval($_POST['nivel_id'] ?? 0);
            
            if ($nivel_id <= 0) {
                sendJsonResponse(false, 'ID de nivel inválido');
            }
            
            $stmt = $pdo->prepare("
                SELECT c.*, 
                       ac.nombre as area_curricular_nombre,
                       ne.nombre as nivel_educativo_nombre
                FROM competencias c
                LEFT JOIN areas_curriculares ac ON c.area_curricular_id = ac.id
                LEFT JOIN niveles_educativos ne ON c.nivel_educativo_id = ne.id
                WHERE c.nivel_educativo_id = ? AND c.estado = 1
                ORDER BY ac.nombre, c.nombre
            ");
            $stmt->execute([$nivel_id]);
            $competencias = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            sendJsonResponse(true, 'Competencias obtenidas correctamente', $competencias);
            break;
            
        case 'get':
            // Obtener competencia por ID
            $id = intval($_POST['id'] ?? 0);
            
            if ($id <= 0) {
                sendJsonResponse(false, 'ID inválido');
            }
            
            $stmt = $pdo->prepare("
                SELECT c.*, 
                       ac.nombre as area_curricular_nombre,
                       ac.descripcion as area_descripcion,
                       ne.nombre as nivel_educativo_nombre,
                       ne.descripcion as nivel_descripcion
                FROM competencias c
                LEFT JOIN areas_curriculares ac ON c.area_curricular_id = ac.id
                LEFT JOIN niveles_educativos ne ON c.nivel_educativo_id = ne.id
                WHERE c.id = ?
            ");
            $stmt->execute([$id]);
            $competencia = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$competencia) {
                sendJsonResponse(false, 'Competencia no encontrada');
            }
            
            // Obtener criterios asociados
            $stmt = $pdo->prepare("
                SELECT * FROM criterios_evaluacion 
                WHERE competencia_id = ? 
                ORDER BY orden_visualizacion, id
            ");
            $stmt->execute([$id]);
            $criterios = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $competencia['criterios'] = $criterios;
            
            sendJsonResponse(true, 'Competencia obtenida correctamente', $competencia);
            break;
            
        case 'get_all':
            // Obtener todas las competencias (activas e inactivas)
            $stmt = $pdo->prepare("
                SELECT c.*, 
                       ac.nombre as area_curricular_nombre,
                       ne.nombre as nivel_educativo_nombre,
                       (SELECT COUNT(*) FROM criterios_evaluacion ce WHERE ce.competencia_id = c.id) as total_criterios
                FROM competencias c
                LEFT JOIN areas_curriculares ac ON c.area_curricular_id = ac.id
                LEFT JOIN niveles_educativos ne ON c.nivel_educativo_id = ne.id
                ORDER BY c.estado DESC, ac.nombre, c.nombre
            ");
            $stmt->execute();
            $competencias = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            sendJsonResponse(true, 'Competencias obtenidas correctamente', $competencias);
            break;
            
        case 'get_with_rubricas':
            // Obtener competencias con el número de rúbricas asociadas
            $stmt = $pdo->prepare("
                SELECT c.*, 
                       ac.nombre as area_curricular_nombre,
                       ne.nombre as nivel_educativo_nombre,
                       (SELECT COUNT(*) FROM criterios_evaluacion ce WHERE ce.competencia_id = c.id) as total_criterios,
                       (SELECT COUNT(*) FROM rubricas r WHERE r.competencia_id = c.id) as total_rubricas
                FROM competencias c
                LEFT JOIN areas_curriculares ac ON c.area_curricular_id = ac.id
                LEFT JOIN niveles_educativos ne ON c.nivel_educativo_id = ne.id
                WHERE c.estado = 1
                ORDER BY ac.nombre, c.nombre
            ");
            $stmt->execute();
            $competencias = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            sendJsonResponse(true, 'Competencias con rúbricas obtenidas correctamente', $competencias);
            break;
            
        case 'search':
            // Buscar competencias por término
            $termino = trim($_POST['termino'] ?? '');
            
            if (empty($termino)) {
                sendJsonResponse(false, 'Término de búsqueda requerido');
            }
            
            $stmt = $pdo->prepare("
                SELECT c.*, 
                       ac.nombre as area_curricular_nombre,
                       ne.nombre as nivel_educativo_nombre
                FROM competencias c
                LEFT JOIN areas_curriculares ac ON c.area_curricular_id = ac.id
                LEFT JOIN niveles_educativos ne ON c.nivel_educativo_id = ne.id
                WHERE c.estado = 1 AND (
                    c.nombre LIKE ? OR 
                    c.codigo LIKE ? OR 
                    c.descripcion LIKE ? OR
                    ac.nombre LIKE ?
                )
                ORDER BY ac.nombre, c.nombre
                LIMIT 50
            ");
            
            $searchTerm = "%$termino%";
            $stmt->execute([$searchTerm, $searchTerm, $searchTerm, $searchTerm]);
            $competencias = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            sendJsonResponse(true, 'Búsqueda completada', $competencias);
            break;
            
        default:
            sendJsonResponse(false, 'Acción no válida', null, 400);
            break;
    }
    
} catch (PDOException $e) {
    error_log("Error de BD en competencias_controller: " . $e->getMessage());
    sendJsonResponse(false, 'Error de base de datos', null, 500);
    
} catch (Exception $e) {
    error_log("Error en competencias_controller: " . $e->getMessage());
    sendJsonResponse(false, $e->getMessage(), null, 500);
    
} catch (Throwable $e) {
    error_log("Error fatal en competencias_controller: " . $e->getMessage());
    sendJsonResponse(false, 'Error interno del servidor', null, 500);
}

?>