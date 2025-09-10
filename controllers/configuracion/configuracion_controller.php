<?php
// Limpiar cualquier output previo
ob_start();
ob_clean();

// Evitar acceso directo
if (!defined('SECURE_ACCESS')) {
    define('SECURE_ACCESS', true);
}

// Configurar manejo de errores para evitar output HTML
error_reporting(0); // Desactivar errores en pantalla
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);

// Incluir la configuración de base de datos
// Ajustar la ruta según tu estructura de directorios
$config_path = __DIR__ . '/../../config/bd.php';
if (!file_exists($config_path)) {
    // Intentar ruta alternativa
    $config_path = __DIR__ . '/../config/bd.php';
    if (!file_exists($config_path)) {
        // Otra ruta alternativa
        $config_path = dirname(dirname(__DIR__)) . '/config/bd.php';
    }
}

if (file_exists($config_path)) {
    require_once $config_path;
} else {
    // Limpiar buffer antes de enviar JSON
    ob_clean();
    header('Content-Type: application/json; charset=utf-8');
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error: No se pudo encontrar el archivo de configuración de base de datos',
        'debug_info' => [
            'current_dir' => __DIR__,
            'tried_paths' => [
                __DIR__ . '/../../config/bd.php',
                __DIR__ . '/../config/bd.php',
                dirname(dirname(__DIR__)) . '/config/bd.php'
            ]
        ]
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
    // Limpiar cualquier output previo
    if (ob_get_length()) {
        ob_clean();
    }
    
    // Configurar cabeceras
    header('Content-Type: application/json; charset=utf-8');
    http_response_code($statusCode);
    
    // Enviar respuesta JSON
    echo json_encode([
        'success' => $success,
        'message' => $message,
        'data' => $data,
        'timestamp' => date('c')
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// Función para validar campos requeridos
function validateRequiredFields($data, $requiredFields) {
    $missingFields = [];
    foreach ($requiredFields as $field) {
        if (empty($data[$field])) {
            $missingFields[] = $field;
        }
    }
    return $missingFields;
}

// Función para validar email
function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

// Función para validar código modular (hasta 20 caracteres)
function validateCodigoModular($codigo) {
    return preg_match('/^\d{1,20}$/', $codigo) && strlen($codigo) >= 6;
}

// Función para validar color hexadecimal
function validateHexColor($color) {
    return preg_match('/^#[0-9A-Fa-f]{6}$/', $color);
}

// Función para subir logo
function uploadLogo($file) {
    // Determinar la ruta base del proyecto
    $projectRoot = dirname(dirname(__DIR__));
    $uploadDir = $projectRoot . '/uploads/logos/';
    
    // Crear directorio si no existe
    if (!file_exists($uploadDir)) {
        if (!mkdir($uploadDir, 0755, true)) {
            throw new Exception('No se pudo crear el directorio de uploads: ' . $uploadDir);
        }
    }
    
    // Validar archivo
    $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
    $maxSize = 5 * 1024 * 1024; // 5MB
    
    if (!in_array($file['type'], $allowedTypes)) {
        throw new Exception('Tipo de archivo no permitido. Solo se permiten JPG, PNG y GIF');
    }
    
    if ($file['size'] > $maxSize) {
        throw new Exception('El archivo es muy grande. Máximo 5MB');
    }
    
    // Generar nombre único
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $fileName = 'logo_' . uniqid() . '.' . $extension;
    $filePath = $uploadDir . $fileName;
    
    // Mover archivo
    if (!move_uploaded_file($file['tmp_name'], $filePath)) {
        throw new Exception('Error al subir el archivo');
    }
    
    // Retornar ruta relativa
    return 'uploads/logos/' . $fileName;
}

// Función para eliminar logo anterior
function deleteLogo($logoPath) {
    if (!empty($logoPath)) {
        $projectRoot = dirname(dirname(__DIR__));
        $fullPath = $projectRoot . '/' . $logoPath;
        if (file_exists($fullPath)) {
            unlink($fullPath);
        }
    }
}

try {
    // Verificar método de solicitud
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        sendJsonResponse(false, 'Método no permitido', null, 405);
    }
    
    // Obtener acción
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        
        case 'get':
            // Obtener configuración por ID
            $id = intval($_POST['id'] ?? 0);
            
            if ($id <= 0) {
                sendJsonResponse(false, 'ID inválido');
            }
            
            $stmt = $pdo->prepare("SELECT * FROM configuracion_sistema WHERE id = ?");
            $stmt->execute([$id]);
            $config = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$config) {
                sendJsonResponse(false, 'Configuración no encontrada');
            }
            
            // Decodificar campos JSON
            if (!empty($config['colores_institucionales'])) {
                $config['colores_institucionales'] = json_decode($config['colores_institucionales'], true);
            }
            if (!empty($config['configuracion_backup'])) {
                $config['configuracion_backup'] = json_decode($config['configuracion_backup'], true);
            }
            if (!empty($config['parametros_generales'])) {
                $config['parametros_generales'] = json_decode($config['parametros_generales'], true);
            }
            
            sendJsonResponse(true, 'Configuración obtenida correctamente', $config);
            break;
            
        case 'update':
            // Actualizar configuración
            $id = intval($_POST['id'] ?? 0);
            
            if ($id <= 0) {
                sendJsonResponse(false, 'ID inválido');
            }
            
            // Campos requeridos
            $requiredFields = [
                'nombre_institucion',
                'codigo_modular', 
                'ugel',
                'nivel_educativo',
                'direccion',
                'telefono',
                'email'
            ];
            
            // Validar campos requeridos
            $missingFields = validateRequiredFields($_POST, $requiredFields);
            if (!empty($missingFields)) {
                sendJsonResponse(false, 'Campos requeridos faltantes: ' . implode(', ', $missingFields));
            }
            
            // Validaciones específicas
            if (!validateCodigoModular($_POST['codigo_modular'])) {
                sendJsonResponse(false, 'El código modular debe tener entre 6 y 20 dígitos');
            }
            
            if (!validateEmail($_POST['email'])) {
                sendJsonResponse(false, 'El email no tiene un formato válido');
            }
            
            // Obtener configuración actual
            $stmt = $pdo->prepare("SELECT logo_url FROM configuracion_sistema WHERE id = ?");
            $stmt->execute([$id]);
            $currentConfig = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$currentConfig) {
                sendJsonResponse(false, 'Configuración no encontrada');
            }
            
            // Procesar logo
            $logoUrl = $currentConfig['logo_url'];
            if (isset($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
                try {
                    // Eliminar logo anterior
                    if (!empty($logoUrl)) {
                        deleteLogo($logoUrl);
                    }
                    
                    // Subir nuevo logo
                    $logoUrl = uploadLogo($_FILES['logo']);
                } catch (Exception $e) {
                    sendJsonResponse(false, 'Error al procesar logo: ' . $e->getMessage());
                }
            }
            
            // Preparar colores institucionales
            $coloresInstitucionales = [];
            if (!empty($_POST['color_primario']) && validateHexColor($_POST['color_primario'])) {
                $coloresInstitucionales['primario'] = $_POST['color_primario'];
            }
            if (!empty($_POST['color_secundario']) && validateHexColor($_POST['color_secundario'])) {
                $coloresInstitucionales['secundario'] = $_POST['color_secundario'];
            }
            if (!empty($_POST['color_acento']) && validateHexColor($_POST['color_acento'])) {
                $coloresInstitucionales['acento'] = $_POST['color_acento'];
            }
            
            // Preparar configuración de backup
            $configuracionBackup = [
                'habilitado' => isset($_POST['backup_habilitado']) ? true : false,
                'frecuencia' => $_POST['backup_frecuencia'] ?? 'semanal',
                'ultima_actualizacion' => date('Y-m-d H:i:s')
            ];
            
            // Preparar parámetros generales
            $parametrosGenerales = [
                'version_sistema' => '1.0.0',
                'mantenimiento' => false,
                'timezone' => 'America/Lima',
                'idioma_predeterminado' => 'es',
                'ultima_modificacion' => date('Y-m-d H:i:s'),
                'modificado_por' => $_SESSION['usuario_id'] ?? 'sistema'
            ];
            
            // Iniciar transacción
            $pdo->beginTransaction();
            
            try {
                // Actualizar configuración
                $stmt = $pdo->prepare("
                    UPDATE configuracion_sistema SET 
                        nombre_institucion = ?,
                        codigo_modular = ?,
                        ugel = ?,
                        nivel_educativo = ?,
                        direccion = ?,
                        telefono = ?,
                        email = ?,
                        logo_url = ?,
                        colores_institucionales = ?,
                        configuracion_backup = ?,
                        parametros_generales = ?,
                        fecha_actualizacion = CURRENT_TIMESTAMP
                    WHERE id = ?
                ");
                
                $result = $stmt->execute([
                    $_POST['nombre_institucion'],
                    $_POST['codigo_modular'],
                    $_POST['ugel'],
                    $_POST['nivel_educativo'],
                    $_POST['direccion'],
                    $_POST['telefono'],
                    $_POST['email'],
                    $logoUrl,
                    json_encode($coloresInstitucionales, JSON_UNESCAPED_UNICODE),
                    json_encode($configuracionBackup, JSON_UNESCAPED_UNICODE),
                    json_encode($parametrosGenerales, JSON_UNESCAPED_UNICODE),
                    $id
                ]);
                
                if (!$result) {
                    throw new Exception('Error al actualizar la configuración');
                }
                
                // Confirmar transacción
                $pdo->commit();
                
                // Obtener configuración actualizada
                $stmt = $pdo->prepare("SELECT * FROM configuracion_sistema WHERE id = ?");
                $stmt->execute([$id]);
                $updatedConfig = $stmt->fetch(PDO::FETCH_ASSOC);
                
                sendJsonResponse(true, 'Configuración actualizada correctamente', $updatedConfig);
                
            } catch (Exception $e) {
                // Revertir transacción
                $pdo->rollBack();
                
                // Si se subió un logo nuevo, eliminarlo
                if (isset($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK && $logoUrl !== $currentConfig['logo_url']) {
                    deleteLogo($logoUrl);
                }
                
                throw $e;
            }
            break;
            
        case 'delete_logo':
            // Eliminar solo el logo
            $id = intval($_POST['id'] ?? 0);
            
            if ($id <= 0) {
                sendJsonResponse(false, 'ID inválido');
            }
            
            // Obtener configuración actual
            $stmt = $pdo->prepare("SELECT logo_url FROM configuracion_sistema WHERE id = ?");
            $stmt->execute([$id]);
            $config = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$config) {
                sendJsonResponse(false, 'Configuración no encontrada');
            }
            
            // Eliminar archivo físico
            if (!empty($config['logo_url'])) {
                deleteLogo($config['logo_url']);
            }
            
            // Actualizar base de datos
            $stmt = $pdo->prepare("UPDATE configuracion_sistema SET logo_url = NULL WHERE id = ?");
            $result = $stmt->execute([$id]);
            
            if ($result) {
                sendJsonResponse(true, 'Logo eliminado correctamente');
            } else {
                sendJsonResponse(false, 'Error al eliminar el logo');
            }
            break;
            
        case 'get_all':
            // Obtener todas las configuraciones
            $stmt = $pdo->prepare("SELECT * FROM configuracion_sistema ORDER BY id ASC");
            $stmt->execute();
            $configuraciones = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Decodificar campos JSON para cada configuración
            foreach ($configuraciones as &$config) {
                if (!empty($config['colores_institucionales'])) {
                    $config['colores_institucionales'] = json_decode($config['colores_institucionales'], true);
                }
                if (!empty($config['configuracion_backup'])) {
                    $config['configuracion_backup'] = json_decode($config['configuracion_backup'], true);
                }
                if (!empty($config['parametros_generales'])) {
                    $config['parametros_generales'] = json_decode($config['parametros_generales'], true);
                }
            }
            
            sendJsonResponse(true, 'Configuraciones obtenidas correctamente', $configuraciones);
            break;
            
        case 'test_connection':
            // Probar conexión a base de datos
            try {
                $stmt = $pdo->query("SELECT 1");
                $resultado = $stmt->fetch();
                sendJsonResponse(true, 'Conexión a base de datos exitosa', [
                    'timestamp' => date('Y-m-d H:i:s'),
                    'server_info' => $pdo->getAttribute(PDO::ATTR_SERVER_INFO)
                ]);
            } catch (Exception $e) {
                sendJsonResponse(false, 'Error de conexión: ' . $e->getMessage());
            }
            break;
            
        default:
            sendJsonResponse(false, 'Acción no válida', null, 400);
            break;
    }
    
} catch (PDOException $e) {
    // Error de base de datos
    error_log("Error de BD en configuracion_controller: " . $e->getMessage());
    sendJsonResponse(false, 'Error de base de datos: ' . $e->getMessage(), null, 500);
    
} catch (Exception $e) {
    // Error general
    error_log("Error en configuracion_controller: " . $e->getMessage());
    sendJsonResponse(false, $e->getMessage(), null, 500);
    
} catch (Throwable $e) {
    // Error fatal
    error_log("Error fatal en configuracion_controller: " . $e->getMessage());
    sendJsonResponse(false, 'Error interno del servidor', null, 500);
}

// ============================================================================
// FUNCIONES DE UTILIDAD ADICIONALES
// ============================================================================

/**
 * Función para validar y sanitizar entrada
 */
function sanitizeInput($input, $type = 'string') {
    switch ($type) {
        case 'email':
            return filter_var(trim($input), FILTER_SANITIZE_EMAIL);
        case 'url':
            return filter_var(trim($input), FILTER_SANITIZE_URL);
        case 'int':
            return filter_var($input, FILTER_SANITIZE_NUMBER_INT);
        case 'string':
        default:
            return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
    }
}

/**
 * Función para generar backup de configuración
 */
function createConfigBackup($configId) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("SELECT * FROM configuracion_sistema WHERE id = ?");
        $stmt->execute([$configId]);
        $config = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($config) {
            $backupData = [
                'timestamp' => date('Y-m-d H:i:s'),
                'config_id' => $configId,
                'data' => $config
            ];
            
            $backupDir = '../../backups/configuracion/';
            if (!file_exists($backupDir)) {
                mkdir($backupDir, 0755, true);
            }
            
            $backupFile = $backupDir . 'config_backup_' . $configId . '_' . date('Ymd_His') . '.json';
            file_put_contents($backupFile, json_encode($backupData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            
            return $backupFile;
        }
        
        return false;
        
    } catch (Exception $e) {
        error_log("Error creando backup: " . $e->getMessage());
        return false;
    }
}

/**
 * Función para validar permisos de usuario (implementar según necesidades)
 */
function validateUserPermissions($action) {
    // Implementar validación de permisos según tu sistema de autenticación
    // Por ahora retorna true, pero deberías implementar la lógica real
    
    /*
    if (!isset($_SESSION['usuario_id'])) {
        return false;
    }
    
    $allowedActions = $_SESSION['permisos'] ?? [];
    return in_array($action, $allowedActions);
    */
    
    return true;
}

?>