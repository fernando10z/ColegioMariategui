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

// ============================================================================
// FUNCIONES DE AUDITORÍA
// ============================================================================

/**
 * Función para registrar acciones en logs_auditoria
 */
function registrarAuditoria($accion, $tabla_afectada, $registro_id = null, $datos_anteriores = null, $datos_nuevos = null, $usuario_id = null) {
    global $pdo;
    
    try {
        // Obtener información del contexto
        $ip_address = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
        
        // Si hay proxies, obtener la IP real
        if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip_address = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0];
        } elseif (!empty($_SERVER['HTTP_X_REAL_IP'])) {
            $ip_address = $_SERVER['HTTP_X_REAL_IP'];
        }
        
        // Obtener usuario_id de sesión si no se proporciona
        if ($usuario_id === null && isset($_SESSION['usuario_id'])) {
            $usuario_id = intval($_SESSION['usuario_id']);
        }
        
        // Preparar datos para JSON
        $datos_anteriores_json = $datos_anteriores ? json_encode($datos_anteriores, JSON_UNESCAPED_UNICODE) : null;
        $datos_nuevos_json = $datos_nuevos ? json_encode($datos_nuevos, JSON_UNESCAPED_UNICODE) : null;
        
        // Insertar en logs_auditoria
        $stmt = $pdo->prepare("
            INSERT INTO logs_auditoria 
            (usuario_id, accion, tabla_afectada, registro_id, datos_anteriores, datos_nuevos, ip_address, user_agent, fecha_accion)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())
        ");
        
        $result = $stmt->execute([
            $usuario_id,
            $accion,
            $tabla_afectada,
            $registro_id,
            $datos_anteriores_json,
            $datos_nuevos_json,
            $ip_address,
            $user_agent
        ]);
        
        if (!$result) {
            error_log("Error al registrar auditoría: " . implode(', ', $stmt->errorInfo()));
        }
        
        return $result;
        
    } catch (Exception $e) {
        error_log("Error en registrarAuditoria: " . $e->getMessage());
        return false;
    }
}

/**
 * Función para obtener datos de una escala antes de modificarla
 */
function obtenerDatosEscala($escala_id) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            SELECT ec.*, aa.nombre as ano_academico_nombre, aa.anio
            FROM escalas_calificacion ec
            LEFT JOIN anos_academicos aa ON ec.ano_academico_id = aa.id
            WHERE ec.id = ?
        ");
        $stmt->execute([$escala_id]);
        $escala = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($escala && !empty($escala['configuracion'])) {
            $escala['configuracion_decoded'] = json_decode($escala['configuracion'], true);
        }
        
        return $escala;
        
    } catch (Exception $e) {
        error_log("Error obteniendo datos de escala: " . $e->getMessage());
        return null;
    }
}

// ============================================================================
// FUNCIONES ORIGINALES
// ============================================================================

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

// Función para validar configuración de escala
function validateEscalaConfiguracion($configuracion, $tipo_escala) {
    try {
        $config = json_decode($configuracion, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return ['valid' => false, 'error' => 'JSON inválido'];
        }
        
        switch ($tipo_escala) {
            case 'literal':
                $requiredKeys = ['A', 'B', 'C', 'D'];
                foreach ($requiredKeys as $key) {
                    if (!isset($config[$key])) {
                        return ['valid' => false, 'error' => "Falta configuración para nivel '$key'"];
                    }
                    if (!isset($config[$key]['descripcion']) || !isset($config[$key]['rango_min']) || !isset($config[$key]['rango_max'])) {
                        return ['valid' => false, 'error' => "Configuración incompleta para nivel '$key'"];
                    }
                }
                break;
                
            case 'numerico':
                if (!isset($config['rango_min']) || !isset($config['rango_max']) || !isset($config['decimales'])) {
                    return ['valid' => false, 'error' => 'Configuración numérica incompleta'];
                }
                break;
                
            case 'descriptivo':
                if (!isset($config['niveles']) || !is_array($config['niveles'])) {
                    return ['valid' => false, 'error' => 'Configuración descriptiva incompleta'];
                }
                break;
        }
        
        return ['valid' => true, 'config' => $config];
        
    } catch (Exception $e) {
        return ['valid' => false, 'error' => 'Error validando configuración: ' . $e->getMessage()];
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
        
        case 'get_all':
            // Obtener todas las escalas de calificación
            $stmt = $pdo->prepare("
                SELECT ec.*, 
                       aa.nombre as ano_academico_nombre,
                       aa.anio,
                       aa.estado as ano_estado
                FROM escalas_calificacion ec
                LEFT JOIN anos_academicos aa ON ec.ano_academico_id = aa.id
                ORDER BY aa.anio DESC, ec.nivel_educativo, ec.tipo_escala
            ");
            $stmt->execute();
            $escalas = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Decodificar configuraciones JSON
            foreach ($escalas as &$escala) {
                if (!empty($escala['configuracion'])) {
                    $escala['configuracion_decoded'] = json_decode($escala['configuracion'], true);
                }
            }
            
            // Registrar auditoría de consulta general
            registrarAuditoria(
                'CONSULTA_ESCALAS_TODAS',
                'escalas_calificacion',
                null,
                null,
                [
                    'total_escalas' => count($escalas),
                    'tipos_escala' => array_unique(array_column($escalas, 'tipo_escala')),
                    'niveles_educativos' => array_unique(array_column($escalas, 'nivel_educativo')),
                    'anos_academicos' => array_unique(array_column($escalas, 'anio'))
                ]
            );
            
            sendJsonResponse(true, 'Escalas obtenidas correctamente', $escalas);
            break;
            
        case 'get':
            // Obtener escala específica
            $id = intval($_POST['id'] ?? 0);
            
            if ($id <= 0) {
                sendJsonResponse(false, 'ID inválido');
            }
            
            $escala = obtenerDatosEscala($id);
            
            if (!$escala) {
                // Registrar intento de consulta de escala inexistente
                registrarAuditoria(
                    'CONSULTA_ESCALA_NO_ENCONTRADA',
                    'escalas_calificacion',
                    $id,
                    null,
                    ['escala_id_buscada' => $id]
                );
                
                sendJsonResponse(false, 'Escala no encontrada');
            }
            
            // Registrar auditoría de consulta individual
            registrarAuditoria(
                'CONSULTA_ESCALA_INDIVIDUAL',
                'escalas_calificacion',
                $id,
                null,
                [
                    'ano_academico' => $escala['ano_academico_nombre'],
                    'nivel_educativo' => $escala['nivel_educativo'],
                    'tipo_escala' => $escala['tipo_escala'],
                    'tiene_configuracion' => !empty($escala['configuracion'])
                ]
            );
            
            sendJsonResponse(true, 'Escala obtenida correctamente', $escala);
            break;
            
        case 'get_by_ano':
            // Obtener escalas por año académico
            $ano_academico_id = intval($_POST['ano_academico_id'] ?? 0);
            
            if ($ano_academico_id <= 0) {
                sendJsonResponse(false, 'ID de año académico inválido');
            }
            
            $stmt = $pdo->prepare("
                SELECT ec.*, 
                       aa.nombre as ano_academico_nombre,
                       aa.anio
                FROM escalas_calificacion ec
                LEFT JOIN anos_academicos aa ON ec.ano_academico_id = aa.id
                WHERE ec.ano_academico_id = ?
                ORDER BY ec.nivel_educativo, ec.tipo_escala
            ");
            $stmt->execute([$ano_academico_id]);
            $escalas = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Decodificar configuraciones
            foreach ($escalas as &$escala) {
                if (!empty($escala['configuracion'])) {
                    $escala['configuracion_decoded'] = json_decode($escala['configuracion'], true);
                }
            }
            
            // Obtener información del año académico para auditoría
            $stmt = $pdo->prepare("SELECT nombre, anio FROM anos_academicos WHERE id = ?");
            $stmt->execute([$ano_academico_id]);
            $ano_info = $stmt->fetch();
            
            // Registrar auditoría de consulta por año
            registrarAuditoria(
                'CONSULTA_ESCALAS_POR_ANO',
                'escalas_calificacion',
                null,
                null,
                [
                    'ano_academico_id' => $ano_academico_id,
                    'ano_academico_nombre' => $ano_info['nombre'] ?? 'Año desconocido',
                    'anio' => $ano_info['anio'] ?? null,
                    'total_escalas_encontradas' => count($escalas),
                    'niveles_configurados' => array_unique(array_column($escalas, 'nivel_educativo')),
                    'tipos_escala_usados' => array_unique(array_column($escalas, 'tipo_escala'))
                ]
            );
            
            sendJsonResponse(true, 'Escalas obtenidas correctamente', $escalas);
            break;
            
        case 'get_by_nivel':
            // Obtener escalas por nivel educativo
            $nivel_educativo = trim($_POST['nivel_educativo'] ?? '');
            
            if (empty($nivel_educativo)) {
                sendJsonResponse(false, 'Nivel educativo requerido');
            }
            
            $stmt = $pdo->prepare("
                SELECT ec.*, 
                       aa.nombre as ano_academico_nombre,
                       aa.anio,
                       aa.estado as ano_estado
                FROM escalas_calificacion ec
                LEFT JOIN anos_academicos aa ON ec.ano_academico_id = aa.id
                WHERE ec.nivel_educativo = ?
                ORDER BY aa.anio DESC, ec.tipo_escala
            ");
            $stmt->execute([$nivel_educativo]);
            $escalas = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Decodificar configuraciones
            foreach ($escalas as &$escala) {
                if (!empty($escala['configuracion'])) {
                    $escala['configuracion_decoded'] = json_decode($escala['configuracion'], true);
                }
            }
            
            // Registrar auditoría de consulta por nivel
            registrarAuditoria(
                'CONSULTA_ESCALAS_POR_NIVEL',
                'escalas_calificacion',
                null,
                null,
                [
                    'nivel_educativo' => $nivel_educativo,
                    'total_escalas_encontradas' => count($escalas),
                    'anos_academicos_involucrados' => array_unique(array_column($escalas, 'anio')),
                    'tipos_escala_disponibles' => array_unique(array_column($escalas, 'tipo_escala'))
                ]
            );
            
            sendJsonResponse(true, 'Escalas obtenidas correctamente', $escalas);
            break;
            
        case 'create':
            // Crear nueva escala de calificación
            $requiredFields = ['ano_academico_id', 'nivel_educativo', 'tipo_escala', 'configuracion'];
            $missingFields = validateRequiredFields($_POST, $requiredFields);
            
            if (!empty($missingFields)) {
                sendJsonResponse(false, 'Campos requeridos faltantes: ' . implode(', ', $missingFields));
            }
            
            $ano_academico_id = intval($_POST['ano_academico_id']);
            $nivel_educativo = trim($_POST['nivel_educativo']);
            $tipo_escala = trim($_POST['tipo_escala']);
            $configuracion = trim($_POST['configuracion']);
            
            // Validaciones
            if ($ano_academico_id <= 0) {
                sendJsonResponse(false, 'ID de año académico inválido');
            }
            
            $nivelesValidos = ['inicial', 'primaria', 'secundaria'];
            if (!in_array($nivel_educativo, $nivelesValidos)) {
                sendJsonResponse(false, 'Nivel educativo inválido');
            }
            
            $tiposValidos = ['literal', 'numerico', 'descriptivo'];
            if (!in_array($tipo_escala, $tiposValidos)) {
                sendJsonResponse(false, 'Tipo de escala inválido');
            }
            
            // Validar configuración JSON
            $validacionConfig = validateEscalaConfiguracion($configuracion, $tipo_escala);
            if (!$validacionConfig['valid']) {
                sendJsonResponse(false, 'Configuración inválida: ' . $validacionConfig['error']);
            }
            
            // Verificar que no exista ya una escala para el mismo año, nivel y tipo
            $stmt = $pdo->prepare("
                SELECT id FROM escalas_calificacion 
                WHERE ano_academico_id = ? AND nivel_educativo = ? AND tipo_escala = ?
            ");
            $stmt->execute([$ano_academico_id, $nivel_educativo, $tipo_escala]);
            if ($stmt->fetch()) {
                sendJsonResponse(false, 'Ya existe una escala de este tipo para el año y nivel especificados');
            }
            
            // Obtener información del año académico para auditoría
            $stmt = $pdo->prepare("SELECT nombre, anio FROM anos_academicos WHERE id = ?");
            $stmt->execute([$ano_academico_id]);
            $ano_info = $stmt->fetch();
            
            if (!$ano_info) {
                sendJsonResponse(false, 'Año académico no encontrado');
            }
            
            // Preparar datos para auditoría
            $datos_nuevos = [
                'ano_academico_id' => $ano_academico_id,
                'ano_academico_nombre' => $ano_info['nombre'],
                'anio' => $ano_info['anio'],
                'nivel_educativo' => $nivel_educativo,
                'tipo_escala' => $tipo_escala,
                'configuracion_preview' => array_slice($validacionConfig['config'], 0, 3, true), // Solo primeros 3 elementos para auditoría
                'total_niveles_configurados' => count($validacionConfig['config'])
            ];
            
            // Insertar escala
            $stmt = $pdo->prepare("
                INSERT INTO escalas_calificacion 
                (ano_academico_id, nivel_educativo, tipo_escala, configuracion, fecha_creacion) 
                VALUES (?, ?, ?, ?, NOW())
            ");
            
            $result = $stmt->execute([
                $ano_academico_id,
                $nivel_educativo,
                $tipo_escala,
                $configuracion
            ]);
            
            if ($result) {
                $escala_id = $pdo->lastInsertId();
                
                // Registrar auditoría de creación
                registrarAuditoria(
                    'CREAR_ESCALA_CALIFICACION',
                    'escalas_calificacion',
                    $escala_id,
                    null,
                    $datos_nuevos
                );
                
                // Obtener la escala creada
                $escala_creada = obtenerDatosEscala($escala_id);
                
                sendJsonResponse(true, 'Escala creada correctamente', $escala_creada);
            } else {
                sendJsonResponse(false, 'Error al crear la escala');
            }
            break;
            
        case 'update':
            // Actualizar escala de calificación
            $id = intval($_POST['id'] ?? 0);
            $requiredFields = ['nivel_educativo', 'tipo_escala', 'configuracion'];
            $missingFields = validateRequiredFields($_POST, $requiredFields);
            
            if ($id <= 0) {
                sendJsonResponse(false, 'ID inválido');
            }
            
            if (!empty($missingFields)) {
                sendJsonResponse(false, 'Campos requeridos faltantes: ' . implode(', ', $missingFields));
            }
            
            // Obtener datos anteriores para auditoría
            $datos_anteriores = obtenerDatosEscala($id);
            if (!$datos_anteriores) {
                sendJsonResponse(false, 'Escala no encontrada');
            }
            
            $nivel_educativo = trim($_POST['nivel_educativo']);
            $tipo_escala = trim($_POST['tipo_escala']);
            $configuracion = trim($_POST['configuracion']);
            
            // Validaciones
            $nivelesValidos = ['inicial', 'primaria', 'secundaria'];
            if (!in_array($nivel_educativo, $nivelesValidos)) {
                sendJsonResponse(false, 'Nivel educativo inválido');
            }
            
            $tiposValidos = ['literal', 'numerico', 'descriptivo'];
            if (!in_array($tipo_escala, $tiposValidos)) {
                sendJsonResponse(false, 'Tipo de escala inválido');
            }
            
            // Validar configuración JSON
            $validacionConfig = validateEscalaConfiguracion($configuracion, $tipo_escala);
            if (!$validacionConfig['valid']) {
                sendJsonResponse(false, 'Configuración inválida: ' . $validacionConfig['error']);
            }
            
            // Verificar unicidad (excluyendo el registro actual)
            $stmt = $pdo->prepare("
                SELECT id FROM escalas_calificacion 
                WHERE ano_academico_id = ? AND nivel_educativo = ? AND tipo_escala = ? AND id != ?
            ");
            $stmt->execute([$datos_anteriores['ano_academico_id'], $nivel_educativo, $tipo_escala, $id]);
            if ($stmt->fetch()) {
                sendJsonResponse(false, 'Ya existe otra escala de este tipo para el año y nivel especificados');
            }
            
            // Preparar datos nuevos para auditoría
            $datos_nuevos = [
                'id' => $id,
                'ano_academico_id' => $datos_anteriores['ano_academico_id'],
                'ano_academico_nombre' => $datos_anteriores['ano_academico_nombre'],
                'nivel_educativo' => $nivel_educativo,
                'tipo_escala' => $tipo_escala,
                'configuracion_preview' => array_slice($validacionConfig['config'], 0, 3, true),
                'total_niveles_configurados' => count($validacionConfig['config'])
            ];
            
            // Actualizar escala
            $stmt = $pdo->prepare("
                UPDATE escalas_calificacion SET 
                nivel_educativo = ?, tipo_escala = ?, configuracion = ?
                WHERE id = ?
            ");
            
            $result = $stmt->execute([
                $nivel_educativo,
                $tipo_escala,
                $configuracion,
                $id
            ]);
            
            if ($result) {
                // Registrar auditoría de actualización
                registrarAuditoria(
                    'ACTUALIZAR_ESCALA_CALIFICACION',
                    'escalas_calificacion',
                    $id,
                    $datos_anteriores,
                    $datos_nuevos
                );
                
                // Obtener la escala actualizada
                $escala_actualizada = obtenerDatosEscala($id);
                
                sendJsonResponse(true, 'Escala actualizada correctamente', $escala_actualizada);
            } else {
                sendJsonResponse(false, 'Error al actualizar la escala');
            }
            break;
            
        case 'delete':
            // Eliminar escala de calificación
            $id = intval($_POST['id'] ?? 0);
            
            if ($id <= 0) {
                sendJsonResponse(false, 'ID inválido');
            }
            
            // Obtener datos anteriores para auditoría
            $datos_anteriores = obtenerDatosEscala($id);
            if (!$datos_anteriores) {
                sendJsonResponse(false, 'Escala no encontrada');
            }
            
            // Verificar si la escala está siendo usada en calificaciones
            $stmt = $pdo->prepare("
                SELECT COUNT(*) as total 
                FROM calificaciones c
                INNER JOIN actividades_evaluacion ae ON c.actividad_evaluacion_id = ae.id
                INNER JOIN periodos_academicos pa ON ae.periodo_academico_id = pa.id
                WHERE pa.ano_academico_id = ?
            ");
            $stmt->execute([$datos_anteriores['ano_academico_id']]);
            $calificaciones = $stmt->fetch()['total'];
            
            if ($calificaciones > 0) {
                // Registrar intento de eliminación fallido
                registrarAuditoria(
                    'INTENTO_ELIMINAR_ESCALA_FALLIDO',
                    'escalas_calificacion',
                    $id,
                    $datos_anteriores,
                    [
                        'motivo' => 'Escala tiene calificaciones asociadas',
                        'calificaciones_count' => $calificaciones
                    ]
                );
                
                sendJsonResponse(false, 'No se puede eliminar la escala porque existen calificaciones que la utilizan');
            }
            
            // Eliminar escala
            $stmt = $pdo->prepare("DELETE FROM escalas_calificacion WHERE id = ?");
            $result = $stmt->execute([$id]);
            
            if ($result) {
                // Registrar auditoría de eliminación
                registrarAuditoria(
                    'ELIMINAR_ESCALA_CALIFICACION',
                    'escalas_calificacion',
                    $id,
                    $datos_anteriores,
                    ['motivo' => 'Eliminación exitosa']
                );
                
                sendJsonResponse(true, 'Escala eliminada correctamente');
            } else {
                sendJsonResponse(false, 'Error al eliminar la escala');
            }
            break;
            
        case 'validate_configuracion':
            // Validar configuración JSON
            $tipo_escala = trim($_POST['tipo_escala'] ?? '');
            $configuracion = trim($_POST['configuracion'] ?? '');
            
            if (empty($tipo_escala) || empty($configuracion)) {
                sendJsonResponse(false, 'Tipo de escala y configuración requeridos');
            }
            
            $validacion = validateEscalaConfiguracion($configuracion, $tipo_escala);
            
            // Registrar auditoría de validación
            registrarAuditoria(
                'VALIDACION_CONFIGURACION_ESCALA',
                'escalas_calificacion',
                null,
                null,
                [
                    'tipo_escala' => $tipo_escala,
                    'configuracion_valida' => $validacion['valid'],
                    'error_validacion' => $validacion['error'] ?? null,
                    'elementos_configurados' => $validacion['valid'] ? count($validacion['config']) : 0
                ]
            );
            
            if ($validacion['valid']) {
                sendJsonResponse(true, 'Configuración válida', $validacion['config']);
            } else {
                sendJsonResponse(false, $validacion['error']);
            }
            break;
            
        case 'get_tipos_disponibles':
            // Obtener tipos de escala disponibles
            $tipos = [
                'literal' => [
                    'nombre' => 'Literal',
                    'descripcion' => 'Escalas con letras (A, B, C, D)',
                    'ejemplo' => 'A = Logro destacado, B = Logro esperado, etc.'
                ],
                'numerico' => [
                    'nombre' => 'Numérico',
                    'descripcion' => 'Escalas con números (0-20, 0-100, etc.)',
                    'ejemplo' => 'Rango de 0 a 20 puntos'
                ],
                'descriptivo' => [
                    'nombre' => 'Descriptivo',
                    'descripcion' => 'Escalas con descripciones textuales',
                    'ejemplo' => 'Excelente, Bueno, Regular, Deficiente'
                ]
            ];
            
            // Registrar auditoría de consulta de tipos
            registrarAuditoria(
                'CONSULTA_TIPOS_ESCALA',
                'escalas_calificacion',
                null,
                null,
                [
                    'tipos_disponibles' => array_keys($tipos),
                    'total_tipos' => count($tipos)
                ]
            );
            
            sendJsonResponse(true, 'Tipos de escala obtenidos correctamente', $tipos);
            break;
            
        case 'get_estadisticas':
            // Obtener estadísticas de escalas
            $stmt = $pdo->prepare("
                SELECT 
                    COUNT(*) as total_escalas,
                    COUNT(DISTINCT ano_academico_id) as anos_configurados,
                    COUNT(DISTINCT nivel_educativo) as niveles_configurados,
                    nivel_educativo,
                    tipo_escala,
                    COUNT(*) as total_por_tipo
                FROM escalas_calificacion
                GROUP BY nivel_educativo, tipo_escala
                ORDER BY nivel_educativo, tipo_escala
            ");
            $stmt->execute();
            $estadisticas_detalle = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Estadísticas generales
            $stmt = $pdo->prepare("
                SELECT 
                    COUNT(*) as total_escalas,
                    COUNT(DISTINCT ano_academico_id) as anos_configurados,
                    COUNT(DISTINCT nivel_educativo) as niveles_configurados,
                    COUNT(DISTINCT tipo_escala) as tipos_usados
                FROM escalas_calificacion
            ");
            $stmt->execute();
            $estadisticas_generales = $stmt->fetch(PDO::FETCH_ASSOC);
            
            $estadisticas = [
                'generales' => $estadisticas_generales,
                'detalle' => $estadisticas_detalle
            ];
            
            // Registrar auditoría de consulta de estadísticas
            registrarAuditoria(
                'CONSULTA_ESTADISTICAS_ESCALAS',
                'escalas_calificacion',
                null,
                null,
                $estadisticas_generales
            );
            
            sendJsonResponse(true, 'Estadísticas obtenidas correctamente', $estadisticas);
            break;
            
        default:
            // Registrar intento de acción no válida
            registrarAuditoria(
                'ACCION_NO_VALIDA',
                'escalas_calificacion',
                null,
                null,
                ['accion_intentada' => $action, 'post_data' => $_POST]
            );
            
            sendJsonResponse(false, 'Acción no válida', null, 400);
            break;
    }
    
} catch (PDOException $e) {
    // Registrar error de base de datos en auditoría
    registrarAuditoria(
        'ERROR_BASE_DATOS',
        'escalas_calificacion',
        null,
        null,
        [
            'error_message' => $e->getMessage(),
            'error_code' => $e->getCode(),
            'accion_intentada' => $action ?? 'desconocida'
        ]
    );
    
    error_log("Error de BD en escalas_controller: " . $e->getMessage());
    sendJsonResponse(false, 'Error de base de datos: ' . $e->getMessage(), null, 500);
    
} catch (Exception $e) {
    // Registrar error general en auditoría
    registrarAuditoria(
        'ERROR_GENERAL',
        'escalas_calificacion',
        null,
        null,
        [
            'error_message' => $e->getMessage(),
            'accion_intentada' => $action ?? 'desconocida'
        ]
    );
    
    error_log("Error en escalas_controller: " . $e->getMessage());
    sendJsonResponse(false, $e->getMessage(), null, 500);
    
} catch (Throwable $e) {
    // Registrar error fatal en auditoría
    registrarAuditoria(
        'ERROR_FATAL',
        'escalas_calificacion',
        null,
        null,
        [
            'error_message' => $e->getMessage(),
            'accion_intentada' => $action ?? 'desconocida'
        ]
    );
    
    error_log("Error fatal en escalas_controller: " . $e->getMessage());
    sendJsonResponse(false, 'Error interno del servidor', null, 500);
}

// ============================================================================
// FUNCIONES DE UTILIDAD ADICIONALES
// ============================================================================

/**
 * Función para validar permisos de escalas
 */
function validateEscalaPermissions($action, $escala_data = null) {
    // Implementar validación de permisos según tu sistema
    return true;
}

/**
 * Función para generar configuración predeterminada de escala
 */
function generarConfiguracionPredeterminada($tipo_escala, $nivel_educativo) {
    switch ($tipo_escala) {
        case 'literal':
            return json_encode([
                'A' => [
                    'descripcion' => 'Logro destacado',
                    'rango_min' => 18,
                    'rango_max' => 20,
                    'color' => '#10b981'
                ],
                'B' => [
                    'descripcion' => 'Logro esperado',
                    'rango_min' => 14,
                    'rango_max' => 17,
                    'color' => '#3b82f6'
                ],
                'C' => [
                    'descripcion' => 'En proceso',
                    'rango_min' => 11,
                    'rango_max' => 13,
                    'color' => '#f59e0b'
                ],
                'D' => [
                    'descripcion' => 'En inicio',
                    'rango_min' => 0,
                    'rango_max' => 10,
                    'color' => '#ef4444'
                ]
            ], JSON_UNESCAPED_UNICODE);
            
        case 'numerico':
            return json_encode([
                'rango_min' => 0,
                'rango_max' => 20,
                'decimales' => 1,
                'color_aprobado' => '#10b981',
                'color_desaprobado' => '#ef4444',
                'nota_minima_aprobacion' => 11
            ], JSON_UNESCAPED_UNICODE);
            
        case 'descriptivo':
            return json_encode([
                'niveles' => [
                    [
                        'nombre' => 'Excelente',
                        'descripcion' => 'Supera ampliamente las expectativas',
                        'color' => '#10b981'
                    ],
                    [
                        'nombre' => 'Bueno',
                        'descripcion' => 'Cumple las expectativas',
                        'color' => '#3b82f6'
                    ],
                    [
                        'nombre' => 'Regular',
                        'descripcion' => 'Cumple parcialmente las expectativas',
                        'color' => '#f59e0b'
                    ],
                    [
                        'nombre' => 'Deficiente',
                        'descripcion' => 'No cumple las expectativas',
                        'color' => '#ef4444'
                    ]
                ]
            ], JSON_UNESCAPED_UNICODE);
            
        default:
            return '{}';
    }
}

/**
 * Función para exportar configuraciones de escalas
 */
function exportarConfiguracionesEscalas($ano_academico_id = null) {
    global $pdo;
    
    try {
        $sql = "
            SELECT ec.*, aa.nombre as ano_academico_nombre, aa.anio
            FROM escalas_calificacion ec
            LEFT JOIN anos_academicos aa ON ec.ano_academico_id = aa.id
        ";
        $params = [];
        
        if ($ano_academico_id) {
            $sql .= " WHERE ec.ano_academico_id = ?";
            $params[] = $ano_academico_id;
        }
        
        $sql .= " ORDER BY aa.anio DESC, ec.nivel_educativo, ec.tipo_escala";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $escalas = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Decodificar configuraciones
        foreach ($escalas as &$escala) {
            if (!empty($escala['configuracion'])) {
                $escala['configuracion_decoded'] = json_decode($escala['configuracion'], true);
            }
        }
        
        $exportData = [
            'timestamp' => date('Y-m-d H:i:s'),
            'ano_academico_id' => $ano_academico_id,
            'total_escalas' => count($escalas),
            'escalas' => $escalas
        ];
        
        $exportDir = '../../exports/escalas/';
        if (!file_exists($exportDir)) {
            mkdir($exportDir, 0755, true);
        }
        
        $filename = 'escalas_export_' . ($ano_academico_id ? $ano_academico_id . '_' : 'todas_') . date('Ymd_His') . '.json';
        $exportFile = $exportDir . $filename;
        file_put_contents($exportFile, json_encode($exportData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        
        // Registrar exportación en auditoría
        registrarAuditoria(
            'EXPORTACION_ESCALAS',
            'escalas_calificacion',
            null,
            null,
            [
                'archivo_generado' => $exportFile,
                'total_escalas_exportadas' => count($escalas),
                'ano_academico_id' => $ano_academico_id
            ]
        );
        
        return $exportFile;
        
    } catch (Exception $e) {
        error_log("Error exportando escalas: " . $e->getMessage());
        
        // Registrar error en auditoría
        registrarAuditoria(
            'ERROR_EXPORTACION_ESCALAS',
            'escalas_calificacion',
            null,
            null,
            [
                'error_message' => $e->getMessage(),
                'ano_academico_id' => $ano_academico_id
            ]
        );
        
        return false;
    }
}

?>