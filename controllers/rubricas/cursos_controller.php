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
 * Función para obtener información resumida de cursos para auditoría
 */
function prepararResumenCursos($cursos) {
    return [
        'total_cursos' => count($cursos),
        'areas_curriculares' => array_unique(array_column($cursos, 'area_curricular_nombre')),
        'grados' => array_unique(array_column($cursos, 'grado_nombre')),
        'niveles_educativos' => array_unique(array_column($cursos, 'nivel_educativo_nombre'))
    ];
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

try {
    // Verificar método de solicitud
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        sendJsonResponse(false, 'Método no permitido', null, 405);
    }
    
    // Obtener acción
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        
        case 'get_all_active':
            // Obtener todos los cursos activos
            $stmt = $pdo->prepare("
                SELECT c.*, 
                       ac.nombre as area_curricular_nombre,
                       ac.color_identificacion as area_color,
                       g.nombre as grado_nombre,
                       g.numero_grado,
                       ne.nombre as nivel_educativo_nombre
                FROM cursos c
                LEFT JOIN areas_curriculares ac ON c.area_curricular_id = ac.id
                LEFT JOIN grados g ON c.grado_id = g.id
                LEFT JOIN niveles_educativos ne ON g.nivel_educativo_id = ne.id
                WHERE c.estado = 1 AND ac.estado = 1 AND g.estado = 1 AND ne.estado = 1
                ORDER BY ne.orden_visualizacion, g.numero_grado, ac.nombre, c.nombre
            ");
            $stmt->execute();
            $cursos = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Registrar auditoría de consulta
            registrarAuditoria(
                'CONSULTA_CURSOS_ACTIVOS',
                'cursos',
                null,
                null,
                prepararResumenCursos($cursos)
            );
            
            sendJsonResponse(true, 'Cursos obtenidos correctamente', $cursos);
            break;
            
        case 'get_by_area':
            // Obtener cursos por área curricular
            $area_id = intval($_POST['area_id'] ?? 0);
            
            if ($area_id <= 0) {
                sendJsonResponse(false, 'ID de área inválido');
            }
            
            $stmt = $pdo->prepare("
                SELECT c.*, 
                       ac.nombre as area_curricular_nombre,
                       g.nombre as grado_nombre,
                       g.numero_grado,
                       ne.nombre as nivel_educativo_nombre
                FROM cursos c
                LEFT JOIN areas_curriculares ac ON c.area_curricular_id = ac.id
                LEFT JOIN grados g ON c.grado_id = g.id
                LEFT JOIN niveles_educativos ne ON g.nivel_educativo_id = ne.id
                WHERE c.area_curricular_id = ? AND c.estado = 1
                ORDER BY g.numero_grado, c.nombre
            ");
            $stmt->execute([$area_id]);
            $cursos = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Obtener nombre del área para auditoría
            $stmt = $pdo->prepare("SELECT nombre FROM areas_curriculares WHERE id = ?");
            $stmt->execute([$area_id]);
            $area_nombre = $stmt->fetch()['nombre'] ?? 'Área desconocida';
            
            // Registrar auditoría de consulta por área
            registrarAuditoria(
                'CONSULTA_CURSOS_POR_AREA',
                'cursos',
                null,
                null,
                [
                    'area_id' => $area_id,
                    'area_nombre' => $area_nombre,
                    'total_cursos_encontrados' => count($cursos),
                    'grados_involucrados' => array_unique(array_column($cursos, 'grado_nombre'))
                ]
            );
            
            sendJsonResponse(true, 'Cursos obtenidos correctamente', $cursos);
            break;
            
        case 'get_by_grado':
            // Obtener cursos por grado
            $grado_id = intval($_POST['grado_id'] ?? 0);
            
            if ($grado_id <= 0) {
                sendJsonResponse(false, 'ID de grado inválido');
            }
            
            $stmt = $pdo->prepare("
                SELECT c.*, 
                       ac.nombre as area_curricular_nombre,
                       ac.color_identificacion as area_color,
                       g.nombre as grado_nombre,
                       ne.nombre as nivel_educativo_nombre
                FROM cursos c
                LEFT JOIN areas_curriculares ac ON c.area_curricular_id = ac.id
                LEFT JOIN grados g ON c.grado_id = g.id
                LEFT JOIN niveles_educativos ne ON g.nivel_educativo_id = ne.id
                WHERE c.grado_id = ? AND c.estado = 1
                ORDER BY ac.nombre, c.nombre
            ");
            $stmt->execute([$grado_id]);
            $cursos = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Obtener información del grado para auditoría
            $stmt = $pdo->prepare("
                SELECT g.nombre as grado_nombre, g.numero_grado, ne.nombre as nivel_nombre
                FROM grados g 
                LEFT JOIN niveles_educativos ne ON g.nivel_educativo_id = ne.id 
                WHERE g.id = ?
            ");
            $stmt->execute([$grado_id]);
            $grado_info = $stmt->fetch();
            
            // Registrar auditoría de consulta por grado
            registrarAuditoria(
                'CONSULTA_CURSOS_POR_GRADO',
                'cursos',
                null,
                null,
                [
                    'grado_id' => $grado_id,
                    'grado_nombre' => $grado_info['grado_nombre'] ?? 'Grado desconocido',
                    'numero_grado' => $grado_info['numero_grado'] ?? null,
                    'nivel_educativo' => $grado_info['nivel_nombre'] ?? null,
                    'total_cursos_encontrados' => count($cursos),
                    'areas_involucradas' => array_unique(array_column($cursos, 'area_curricular_nombre'))
                ]
            );
            
            sendJsonResponse(true, 'Cursos obtenidos correctamente', $cursos);
            break;
            
        case 'get':
            // Obtener curso por ID
            $id = intval($_POST['id'] ?? 0);
            
            if ($id <= 0) {
                sendJsonResponse(false, 'ID inválido');
            }
            
            $stmt = $pdo->prepare("
                SELECT c.*, 
                       ac.nombre as area_curricular_nombre,
                       ac.descripcion as area_descripcion,
                       ac.color_identificacion as area_color,
                       g.nombre as grado_nombre,
                       g.descripcion as grado_descripcion,
                       g.numero_grado,
                       ne.nombre as nivel_educativo_nombre
                FROM cursos c
                LEFT JOIN areas_curriculares ac ON c.area_curricular_id = ac.id
                LEFT JOIN grados g ON c.grado_id = g.id
                LEFT JOIN niveles_educativos ne ON g.nivel_educativo_id = ne.id
                WHERE c.id = ?
            ");
            $stmt->execute([$id]);
            $curso = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$curso) {
                // Registrar intento de consulta de curso inexistente
                registrarAuditoria(
                    'CONSULTA_CURSO_NO_ENCONTRADO',
                    'cursos',
                    $id,
                    null,
                    ['curso_id_buscado' => $id]
                );
                
                sendJsonResponse(false, 'Curso no encontrado');
            }
            
            // Obtener competencias asociadas al área del curso
            $stmt = $pdo->prepare("
                SELECT id, codigo, nombre, descripcion 
                FROM competencias 
                WHERE area_curricular_id = ? AND estado = 1
                ORDER BY nombre
            ");
            $stmt->execute([$curso['area_curricular_id']]);
            $competencias = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $curso['competencias_disponibles'] = $competencias;
            
            // Registrar auditoría de consulta individual
            registrarAuditoria(
                'CONSULTA_CURSO_INDIVIDUAL',
                'cursos',
                $id,
                null,
                [
                    'curso_nombre' => $curso['nombre'],
                    'area_curricular' => $curso['area_curricular_nombre'],
                    'grado' => $curso['grado_nombre'],
                    'nivel_educativo' => $curso['nivel_educativo_nombre'],
                    'horas_semanales' => $curso['horas_semanales'],
                    'competencias_disponibles' => count($competencias)
                ]
            );
            
            sendJsonResponse(true, 'Curso obtenido correctamente', $curso);
            break;
            
        case 'get_all':
            // Obtener todos los cursos (activos e inactivos)
            $stmt = $pdo->prepare("
                SELECT c.*, 
                       ac.nombre as area_curricular_nombre,
                       g.nombre as grado_nombre,
                       g.numero_grado,
                       ne.nombre as nivel_educativo_nombre,
                       (SELECT COUNT(*) FROM rubricas r WHERE r.curso_id = c.id) as total_rubricas,
                       (SELECT COUNT(*) FROM asignaciones_docentes ad WHERE ad.curso_id = c.id AND ad.estado = 'activo') as total_asignaciones
                FROM cursos c
                LEFT JOIN areas_curriculares ac ON c.area_curricular_id = ac.id
                LEFT JOIN grados g ON c.grado_id = g.id
                LEFT JOIN niveles_educativos ne ON g.nivel_educativo_id = ne.id
                ORDER BY c.estado DESC, ne.orden_visualizacion, g.numero_grado, ac.nombre, c.nombre
            ");
            $stmt->execute();
            $cursos = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Calcular estadísticas para auditoría
            $cursos_activos = array_filter($cursos, function($c) { return $c['estado'] == 1; });
            $cursos_inactivos = array_filter($cursos, function($c) { return $c['estado'] == 0; });
            $total_rubricas = array_sum(array_column($cursos, 'total_rubricas'));
            $total_asignaciones = array_sum(array_column($cursos, 'total_asignaciones'));
            
            // Registrar auditoría de consulta completa
            registrarAuditoria(
                'CONSULTA_CURSOS_COMPLETA',
                'cursos',
                null,
                null,
                [
                    'total_cursos' => count($cursos),
                    'cursos_activos' => count($cursos_activos),
                    'cursos_inactivos' => count($cursos_inactivos),
                    'total_rubricas_sistema' => $total_rubricas,
                    'total_asignaciones_activas' => $total_asignaciones,
                    'areas_curriculares' => array_unique(array_column($cursos, 'area_curricular_nombre')),
                    'niveles_educativos' => array_unique(array_column($cursos, 'nivel_educativo_nombre'))
                ]
            );
            
            sendJsonResponse(true, 'Cursos obtenidos correctamente', $cursos);
            break;
            
        case 'get_by_nivel':
            // Obtener cursos por nivel educativo
            $nivel_id = intval($_POST['nivel_id'] ?? 0);
            
            if ($nivel_id <= 0) {
                sendJsonResponse(false, 'ID de nivel inválido');
            }
            
            $stmt = $pdo->prepare("
                SELECT c.*, 
                       ac.nombre as area_curricular_nombre,
                       ac.color_identificacion as area_color,
                       g.nombre as grado_nombre,
                       g.numero_grado
                FROM cursos c
                LEFT JOIN areas_curriculares ac ON c.area_curricular_id = ac.id
                LEFT JOIN grados g ON c.grado_id = g.id
                WHERE g.nivel_educativo_id = ? AND c.estado = 1
                ORDER BY g.numero_grado, ac.nombre, c.nombre
            ");
            $stmt->execute([$nivel_id]);
            $cursos = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Obtener nombre del nivel para auditoría
            $stmt = $pdo->prepare("SELECT nombre FROM niveles_educativos WHERE id = ?");
            $stmt->execute([$nivel_id]);
            $nivel_nombre = $stmt->fetch()['nombre'] ?? 'Nivel desconocido';
            
            // Registrar auditoría de consulta por nivel
            registrarAuditoria(
                'CONSULTA_CURSOS_POR_NIVEL',
                'cursos',
                null,
                null,
                [
                    'nivel_id' => $nivel_id,
                    'nivel_nombre' => $nivel_nombre,
                    'total_cursos_encontrados' => count($cursos),
                    'grados_involucrados' => array_unique(array_column($cursos, 'grado_nombre')),
                    'areas_involucradas' => array_unique(array_column($cursos, 'area_curricular_nombre'))
                ]
            );
            
            sendJsonResponse(true, 'Cursos obtenidos correctamente', $cursos);
            break;
            
        case 'get_with_docentes':
            // Obtener cursos con información de docentes asignados
            $ano_academico_id = intval($_POST['ano_academico_id'] ?? 1);
            
            $stmt = $pdo->prepare("
                SELECT c.*, 
                       ac.nombre as area_curricular_nombre,
                       g.nombre as grado_nombre,
                       g.numero_grado,
                       ne.nombre as nivel_educativo_nombre,
                       COUNT(ad.id) as total_docentes_asignados
                FROM cursos c
                LEFT JOIN areas_curriculares ac ON c.area_curricular_id = ac.id
                LEFT JOIN grados g ON c.grado_id = g.id
                LEFT JOIN niveles_educativos ne ON g.nivel_educativo_id = ne.id
                LEFT JOIN asignaciones_docentes ad ON c.id = ad.curso_id AND ad.ano_academico_id = ? AND ad.estado = 'activo'
                WHERE c.estado = 1
                GROUP BY c.id
                ORDER BY ne.orden_visualizacion, g.numero_grado, ac.nombre, c.nombre
            ");
            $stmt->execute([$ano_academico_id]);
            $cursos = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Calcular estadísticas de asignaciones
            $cursos_con_docentes = array_filter($cursos, function($c) { return $c['total_docentes_asignados'] > 0; });
            $cursos_sin_docentes = array_filter($cursos, function($c) { return $c['total_docentes_asignados'] == 0; });
            $total_asignaciones = array_sum(array_column($cursos, 'total_docentes_asignados'));
            
            // Registrar auditoría de consulta con docentes
            registrarAuditoria(
                'CONSULTA_CURSOS_CON_DOCENTES',
                'cursos',
                null,
                null,
                [
                    'ano_academico_id' => $ano_academico_id,
                    'total_cursos' => count($cursos),
                    'cursos_con_docentes' => count($cursos_con_docentes),
                    'cursos_sin_docentes' => count($cursos_sin_docentes),
                    'total_asignaciones_docentes' => $total_asignaciones,
                    'promedio_docentes_por_curso' => count($cursos) > 0 ? round($total_asignaciones / count($cursos), 2) : 0
                ]
            );
            
            sendJsonResponse(true, 'Cursos con docentes obtenidos correctamente', $cursos);
            break;
            
        case 'search':
            // Buscar cursos por término
            $termino = trim($_POST['termino'] ?? '');
            
            if (empty($termino)) {
                sendJsonResponse(false, 'Término de búsqueda requerido');
            }
            
            $stmt = $pdo->prepare("
                SELECT c.*, 
                       ac.nombre as area_curricular_nombre,
                       g.nombre as grado_nombre,
                       g.numero_grado,
                       ne.nombre as nivel_educativo_nombre
                FROM cursos c
                LEFT JOIN areas_curriculares ac ON c.area_curricular_id = ac.id
                LEFT JOIN grados g ON c.grado_id = g.id
                LEFT JOIN niveles_educativos ne ON g.nivel_educativo_id = ne.id
                WHERE c.estado = 1 AND (
                    c.nombre LIKE ? OR 
                    c.descripcion LIKE ? OR
                    ac.nombre LIKE ? OR
                    g.nombre LIKE ?
                )
                ORDER BY ne.orden_visualizacion, g.numero_grado, ac.nombre, c.nombre
                LIMIT 50
            ");
            
            $searchTerm = "%$termino%";
            $stmt->execute([$searchTerm, $searchTerm, $searchTerm, $searchTerm]);
            $cursos = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Registrar auditoría de búsqueda
            registrarAuditoria(
                'BUSQUEDA_CURSOS',
                'cursos',
                null,
                null,
                [
                    'termino_busqueda' => $termino,
                    'resultados_encontrados' => count($cursos),
                    'limite_aplicado' => 50,
                    'campos_buscados' => ['nombre_curso', 'descripcion_curso', 'area_curricular', 'grado']
                ]
            );
            
            sendJsonResponse(true, 'Búsqueda completada', $cursos);
            break;
            
        case 'get_areas':
            // Obtener áreas curriculares disponibles
            $stmt = $pdo->prepare("
                SELECT DISTINCT ac.id, ac.nombre, ac.descripcion, ac.color_identificacion
                FROM areas_curriculares ac
                INNER JOIN cursos c ON ac.id = c.area_curricular_id
                WHERE ac.estado = 1 AND c.estado = 1
                ORDER BY ac.nombre
            ");
            $stmt->execute();
            $areas = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Registrar auditoría de consulta de áreas
            registrarAuditoria(
                'CONSULTA_AREAS_CURRICULARES',
                'areas_curriculares',
                null,
                null,
                [
                    'total_areas_activas' => count($areas),
                    'areas_nombres' => array_column($areas, 'nombre')
                ]
            );
            
            sendJsonResponse(true, 'Áreas curriculares obtenidas correctamente', $areas);
            break;
            
        case 'get_grados':
            // Obtener grados disponibles
            $stmt = $pdo->prepare("
                SELECT DISTINCT g.id, g.nombre, g.numero_grado, ne.nombre as nivel_educativo_nombre
                FROM grados g
                INNER JOIN cursos c ON g.id = c.grado_id
                INNER JOIN niveles_educativos ne ON g.nivel_educativo_id = ne.id
                WHERE g.estado = 1 AND c.estado = 1 AND ne.estado = 1
                ORDER BY ne.orden_visualizacion, g.numero_grado
            ");
            $stmt->execute();
            $grados = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Registrar auditoría de consulta de grados
            registrarAuditoria(
                'CONSULTA_GRADOS_DISPONIBLES',
                'grados',
                null,
                null,
                [
                    'total_grados_activos' => count($grados),
                    'grados_nombres' => array_column($grados, 'nombre'),
                    'niveles_educativos' => array_unique(array_column($grados, 'nivel_educativo_nombre'))
                ]
            );
            
            sendJsonResponse(true, 'Grados obtenidos correctamente', $grados);
            break;
            
        default:
            // Registrar intento de acción no válida
            registrarAuditoria(
                'ACCION_NO_VALIDA',
                'cursos',
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
        'cursos',
        null,
        null,
        [
            'error_message' => $e->getMessage(),
            'error_code' => $e->getCode(),
            'accion_intentada' => $action ?? 'desconocida'
        ]
    );
    
    error_log("Error de BD en cursos_controller: " . $e->getMessage());
    sendJsonResponse(false, 'Error de base de datos', null, 500);
    
} catch (Exception $e) {
    // Registrar error general en auditoría
    registrarAuditoria(
        'ERROR_GENERAL',
        'cursos',
        null,
        null,
        [
            'error_message' => $e->getMessage(),
            'accion_intentada' => $action ?? 'desconocida'
        ]
    );
    
    error_log("Error en cursos_controller: " . $e->getMessage());
    sendJsonResponse(false, $e->getMessage(), null, 500);
    
} catch (Throwable $e) {
    // Registrar error fatal en auditoría
    registrarAuditoria(
        'ERROR_FATAL',
        'cursos',
        null,
        null,
        [
            'error_message' => $e->getMessage(),
            'accion_intentada' => $action ?? 'desconocida'
        ]
    );
    
    error_log("Error fatal en cursos_controller: " . $e->getMessage());
    sendJsonResponse(false, 'Error interno del servidor', null, 500);
}

// ============================================================================
// FUNCIONES DE UTILIDAD ADICIONALES
// ============================================================================

/**
 * Función para validar permisos de cursos
 */
function validateCursoPermissions($action, $curso_data = null) {
    // Implementar validación de permisos según tu sistema
    return true;
}

/**
 * Función para generar estadísticas de uso de cursos
 */
function generarEstadisticasCursos() {
    global $pdo;
    
    try {
        $estadisticas = [];
        
        // Total de cursos activos
        $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM cursos WHERE estado = 1");
        $stmt->execute();
        $estadisticas['cursos_activos'] = $stmt->fetch()['total'];
        
        // Cursos por área curricular
        $stmt = $pdo->prepare("
            SELECT ac.nombre, COUNT(c.id) as total_cursos
            FROM areas_curriculares ac
            LEFT JOIN cursos c ON ac.id = c.area_curricular_id AND c.estado = 1
            WHERE ac.estado = 1
            GROUP BY ac.id, ac.nombre
            ORDER BY total_cursos DESC
        ");
        $stmt->execute();
        $estadisticas['cursos_por_area'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Cursos por nivel educativo
        $stmt = $pdo->prepare("
            SELECT ne.nombre, COUNT(c.id) as total_cursos
            FROM niveles_educativos ne
            LEFT JOIN grados g ON ne.id = g.nivel_educativo_id
            LEFT JOIN cursos c ON g.id = c.grado_id AND c.estado = 1
            WHERE ne.estado = 1
            GROUP BY ne.id, ne.nombre
            ORDER BY ne.orden_visualizacion
        ");
        $stmt->execute();
        $estadisticas['cursos_por_nivel'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Registrar generación de estadísticas
        registrarAuditoria(
            'GENERACION_ESTADISTICAS_CURSOS',
            'cursos',
            null,
            null,
            $estadisticas
        );
        
        return $estadisticas;
        
    } catch (Exception $e) {
        error_log("Error generando estadísticas de cursos: " . $e->getMessage());
        
        // Registrar error en auditoría
        registrarAuditoria(
            'ERROR_ESTADISTICAS_CURSOS',
            'cursos',
            null,
            null,
            ['error_message' => $e->getMessage()]
        );
        
        return false;
    }
}

/**
 * Función para exportar datos de cursos (para respaldo)
 */
function exportarDatosCursos($formato = 'json') {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            SELECT c.*, 
                   ac.nombre as area_curricular_nombre,
                   g.nombre as grado_nombre,
                   ne.nombre as nivel_educativo_nombre
            FROM cursos c
            LEFT JOIN areas_curriculares ac ON c.area_curricular_id = ac.id
            LEFT JOIN grados g ON c.grado_id = g.id
            LEFT JOIN niveles_educativos ne ON g.nivel_educativo_id = ne.id
            ORDER BY ne.orden_visualizacion, g.numero_grado, ac.nombre, c.nombre
        ");
        $stmt->execute();
        $cursos = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $exportData = [
            'timestamp' => date('Y-m-d H:i:s'),
            'total_cursos' => count($cursos),
            'formato' => $formato,
            'cursos' => $cursos
        ];
        
        $exportDir = '../../exports/cursos/';
        if (!file_exists($exportDir)) {
            mkdir($exportDir, 0755, true);
        }
        
        $exportFile = $exportDir . 'cursos_export_' . date('Ymd_His') . '.json';
        file_put_contents($exportFile, json_encode($exportData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        
        // Registrar exportación en auditoría
        registrarAuditoria(
            'EXPORTACION_CURSOS',
            'cursos',
            null,
            null,
            [
                'archivo_generado' => $exportFile,
                'total_cursos_exportados' => count($cursos),
                'formato' => $formato
            ]
        );
        
        return $exportFile;
        
    } catch (Exception $e) {
        error_log("Error exportando datos de cursos: " . $e->getMessage());
        
        // Registrar error en auditoría
        registrarAuditoria(
            'ERROR_EXPORTACION_CURSOS',
            'cursos',
            null,
            null,
            [
                'error_message' => $e->getMessage(),
                'formato_solicitado' => $formato
            ]
        );
        
        return false;
    }
}

?>