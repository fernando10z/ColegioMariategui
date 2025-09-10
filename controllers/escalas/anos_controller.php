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
 * Función para obtener información resumida de años académicos para auditoría
 */
function prepararResumenAnos($anos) {
    return [
        'total_anos' => count($anos),
        'anos_activos' => count(array_filter($anos, function($a) { return $a['estado'] === 'activo'; })),
        'anos_planificados' => count(array_filter($anos, function($a) { return $a['estado'] === 'planificado'; })),
        'anos_finalizados' => count(array_filter($anos, function($a) { return $a['estado'] === 'finalizado'; })),
        'tipos_periodo' => array_unique(array_column($anos, 'tipo_periodo')),
        'anos_numericos' => array_unique(array_column($anos, 'anio'))
    ];
}

// ============================================================================
// FUNCIONES PRINCIPALES
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
            // Obtener todos los años académicos activos
            $stmt = $pdo->prepare("
                SELECT aa.*, 
                       (SELECT COUNT(*) FROM periodos_academicos pa WHERE pa.ano_academico_id = aa.id) as total_periodos,
                       (SELECT COUNT(*) FROM matriculas m WHERE m.ano_academico_id = aa.id) as total_matriculas,
                       (SELECT COUNT(*) FROM asignaciones_docentes ad WHERE ad.ano_academico_id = aa.id AND ad.estado = 'activo') as total_asignaciones
                FROM anos_academicos aa
                WHERE aa.estado IN ('activo', 'planificado')
                ORDER BY aa.anio DESC, aa.fecha_inicio DESC
            ");
            $stmt->execute();
            $anos = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Registrar auditoría de consulta
            registrarAuditoria(
                'CONSULTA_ANOS_ACTIVOS',
                'anos_academicos',
                null,
                null,
                prepararResumenAnos($anos)
            );
            
            sendJsonResponse(true, 'Años académicos activos obtenidos correctamente', $anos);
            break;
            
        case 'get_all':
            // Obtener todos los años académicos
            $stmt = $pdo->prepare("
                SELECT aa.*, 
                       (SELECT COUNT(*) FROM periodos_academicos pa WHERE pa.ano_academico_id = aa.id) as total_periodos,
                       (SELECT COUNT(*) FROM matriculas m WHERE m.ano_academico_id = aa.id) as total_matriculas,
                       (SELECT COUNT(*) FROM asignaciones_docentes ad WHERE ad.ano_academico_id = aa.id) as total_asignaciones,
                       (SELECT COUNT(*) FROM escalas_calificacion ec WHERE ec.ano_academico_id = aa.id) as total_escalas
                FROM anos_academicos aa
                ORDER BY aa.anio DESC, aa.fecha_inicio DESC
            ");
            $stmt->execute();
            $anos = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Calcular estadísticas adicionales
            $total_estudiantes = 0;
            $total_escalas = 0;
            foreach ($anos as $ano) {
                $total_estudiantes += intval($ano['total_matriculas']);
                $total_escalas += intval($ano['total_escalas']);
            }
            
            // Registrar auditoría de consulta completa
            registrarAuditoria(
                'CONSULTA_ANOS_COMPLETA',
                'anos_academicos',
                null,
                null,
                array_merge(prepararResumenAnos($anos), [
                    'total_estudiantes_historico' => $total_estudiantes,
                    'total_escalas_configuradas' => $total_escalas
                ])
            );
            
            sendJsonResponse(true, 'Años académicos obtenidos correctamente', $anos);
            break;
            
        case 'get':
            // Obtener año académico por ID
            $id = intval($_POST['id'] ?? 0);
            
            if ($id <= 0) {
                sendJsonResponse(false, 'ID inválido');
            }
            
            $stmt = $pdo->prepare("
                SELECT aa.*, 
                       (SELECT COUNT(*) FROM periodos_academicos pa WHERE pa.ano_academico_id = aa.id) as total_periodos,
                       (SELECT COUNT(*) FROM matriculas m WHERE m.ano_academico_id = aa.id) as total_matriculas,
                       (SELECT COUNT(*) FROM asignaciones_docentes ad WHERE ad.ano_academico_id = aa.id) as total_asignaciones,
                       (SELECT COUNT(*) FROM escalas_calificacion ec WHERE ec.ano_academico_id = aa.id) as total_escalas
                FROM anos_academicos aa
                WHERE aa.id = ?
            ");
            $stmt->execute([$id]);
            $ano = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$ano) {
                // Registrar intento de consulta de año inexistente
                registrarAuditoria(
                    'CONSULTA_ANO_NO_ENCONTRADO',
                    'anos_academicos',
                    $id,
                    null,
                    ['ano_id_buscado' => $id]
                );
                
                sendJsonResponse(false, 'Año académico no encontrado');
            }
            
            // Obtener períodos académicos del año
            $stmt = $pdo->prepare("
                SELECT * FROM periodos_academicos 
                WHERE ano_academico_id = ? 
                ORDER BY numero_periodo
            ");
            $stmt->execute([$id]);
            $periodos = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $ano['periodos'] = $periodos;
            
            // Registrar auditoría de consulta individual
            registrarAuditoria(
                'CONSULTA_ANO_INDIVIDUAL',
                'anos_academicos',
                $id,
                null,
                [
                    'ano_nombre' => $ano['nombre'],
                    'anio_numerico' => $ano['anio'],
                    'estado' => $ano['estado'],
                    'tipo_periodo' => $ano['tipo_periodo'],
                    'total_periodos' => $ano['total_periodos'],
                    'total_matriculas' => $ano['total_matriculas'],
                    'total_asignaciones' => $ano['total_asignaciones']
                ]
            );
            
            sendJsonResponse(true, 'Año académico obtenido correctamente', $ano);
            break;
            
        case 'create':
            // Crear nuevo año académico
            $anio = intval($_POST['anio'] ?? 0);
            $nombre = trim($_POST['nombre'] ?? '');
            $fecha_inicio = trim($_POST['fecha_inicio'] ?? '');
            $fecha_fin = trim($_POST['fecha_fin'] ?? '');
            $tipo_periodo = trim($_POST['tipo_periodo'] ?? '');
            $configuracion_evaluacion = $_POST['configuracion_evaluacion'] ?? null;
            
            // Validaciones
            $errores = [];
            
            if ($anio < 2020 || $anio > 2030) {
                $errores[] = 'El año debe estar entre 2020 y 2030';
            }
            
            if (empty($nombre)) {
                $errores[] = 'El nombre es requerido';
            }
            
            if (empty($fecha_inicio) || empty($fecha_fin)) {
                $errores[] = 'Las fechas de inicio y fin son requeridas';
            }
            
            if (!in_array($tipo_periodo, ['bimestral', 'trimestral', 'semestral'])) {
                $errores[] = 'Tipo de período inválido';
            }
            
            // Validar fechas
            if (!empty($fecha_inicio) && !empty($fecha_fin)) {
                $inicio = DateTime::createFromFormat('Y-m-d', $fecha_inicio);
                $fin = DateTime::createFromFormat('Y-m-d', $fecha_fin);
                
                if (!$inicio || !$fin) {
                    $errores[] = 'Formato de fecha inválido';
                } elseif ($inicio >= $fin) {
                    $errores[] = 'La fecha de inicio debe ser anterior a la fecha de fin';
                }
            }
            
            // Verificar duplicados
            $stmt = $pdo->prepare("SELECT id FROM anos_academicos WHERE anio = ?");
            $stmt->execute([$anio]);
            if ($stmt->fetch()) {
                $errores[] = 'Ya existe un año académico para el año ' . $anio;
            }
            
            if (!empty($errores)) {
                sendJsonResponse(false, 'Errores de validación: ' . implode(', ', $errores));
            }
            
            // Preparar configuración de evaluación
            if ($configuracion_evaluacion && is_string($configuracion_evaluacion)) {
                $configuracion_evaluacion = json_decode($configuracion_evaluacion, true);
            }
            
            if (!$configuracion_evaluacion) {
                // Configuración por defecto
                $configuracion_evaluacion = [
                    'escala_principal' => 'literal',
                    'pesos' => [],
                    'configuracion_periodos' => []
                ];
                
                // Configurar pesos según tipo de período
                switch ($tipo_periodo) {
                    case 'bimestral':
                        $configuracion_evaluacion['pesos'] = [
                            'bimestre1' => 25,
                            'bimestre2' => 25,
                            'bimestre3' => 25,
                            'bimestre4' => 25
                        ];
                        break;
                    case 'trimestral':
                        $configuracion_evaluacion['pesos'] = [
                            'trimestre1' => 33.33,
                            'trimestre2' => 33.33,
                            'trimestre3' => 33.34
                        ];
                        break;
                    case 'semestral':
                        $configuracion_evaluacion['pesos'] = [
                            'semestre1' => 50,
                            'semestre2' => 50
                        ];
                        break;
                }
            }
            
            $configuracion_json = json_encode($configuracion_evaluacion, JSON_UNESCAPED_UNICODE);
            
            // Insertar año académico
            $stmt = $pdo->prepare("
                INSERT INTO anos_academicos 
                (anio, nombre, fecha_inicio, fecha_fin, tipo_periodo, estado, configuracion_evaluacion, fecha_creacion)
                VALUES (?, ?, ?, ?, ?, 'planificado', ?, NOW())
            ");
            
            if ($stmt->execute([$anio, $nombre, $fecha_inicio, $fecha_fin, $tipo_periodo, $configuracion_json])) {
                $nuevo_id = $pdo->lastInsertId();
                
                // Crear períodos académicos automáticamente
                $periodos_creados = crearPeriodosAcademicos($nuevo_id, $tipo_periodo, $fecha_inicio, $fecha_fin);
                
                // Registrar auditoría de creación
                registrarAuditoria(
                    'CREAR_ANO_ACADEMICO',
                    'anos_academicos',
                    $nuevo_id,
                    null,
                    [
                        'anio' => $anio,
                        'nombre' => $nombre,
                        'fecha_inicio' => $fecha_inicio,
                        'fecha_fin' => $fecha_fin,
                        'tipo_periodo' => $tipo_periodo,
                        'periodos_creados' => count($periodos_creados),
                        'configuracion_evaluacion' => $configuracion_evaluacion
                    ]
                );
                
                sendJsonResponse(true, 'Año académico creado correctamente', [
                    'id' => $nuevo_id,
                    'periodos_creados' => count($periodos_creados)
                ]);
            } else {
                sendJsonResponse(false, 'Error al crear el año académico');
            }
            break;
            
        case 'update':
            // Actualizar año académico
            $id = intval($_POST['id'] ?? 0);
            $nombre = trim($_POST['nombre'] ?? '');
            $fecha_inicio = trim($_POST['fecha_inicio'] ?? '');
            $fecha_fin = trim($_POST['fecha_fin'] ?? '');
            $tipo_periodo = trim($_POST['tipo_periodo'] ?? '');
            $estado = trim($_POST['estado'] ?? '');
            $configuracion_evaluacion = $_POST['configuracion_evaluacion'] ?? null;
            
            if ($id <= 0) {
                sendJsonResponse(false, 'ID inválido');
            }
            
            // Obtener datos anteriores
            $stmt = $pdo->prepare("SELECT * FROM anos_academicos WHERE id = ?");
            $stmt->execute([$id]);
            $datos_anteriores = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$datos_anteriores) {
                sendJsonResponse(false, 'Año académico no encontrado');
            }
            
            // Validaciones básicas
            $errores = [];
            
            if (empty($nombre)) {
                $errores[] = 'El nombre es requerido';
            }
            
            if (!in_array($estado, ['planificado', 'activo', 'finalizado'])) {
                $errores[] = 'Estado inválido';
            }
            
            if (!empty($errores)) {
                sendJsonResponse(false, 'Errores de validación: ' . implode(', ', $errores));
            }
            
            // Preparar configuración
            if ($configuracion_evaluacion && is_string($configuracion_evaluacion)) {
                $configuracion_evaluacion = json_decode($configuracion_evaluacion, true);
            }
            if (!$configuracion_evaluacion) {
                $configuracion_evaluacion = json_decode($datos_anteriores['configuracion_evaluacion'], true) ?? [];
            }
            
            $configuracion_json = json_encode($configuracion_evaluacion, JSON_UNESCAPED_UNICODE);
            
            // Actualizar
            $stmt = $pdo->prepare("
                UPDATE anos_academicos 
                SET nombre = ?, fecha_inicio = ?, fecha_fin = ?, tipo_periodo = ?, 
                    estado = ?, configuracion_evaluacion = ?
                WHERE id = ?
            ");
            
            if ($stmt->execute([$nombre, $fecha_inicio, $fecha_fin, $tipo_periodo, $estado, $configuracion_json, $id])) {
                // Registrar auditoría de actualización
                registrarAuditoria(
                    'ACTUALIZAR_ANO_ACADEMICO',
                    'anos_academicos',
                    $id,
                    $datos_anteriores,
                    [
                        'nombre' => $nombre,
                        'fecha_inicio' => $fecha_inicio,
                        'fecha_fin' => $fecha_fin,
                        'tipo_periodo' => $tipo_periodo,
                        'estado' => $estado,
                        'configuracion_evaluacion' => $configuracion_evaluacion
                    ]
                );
                
                sendJsonResponse(true, 'Año académico actualizado correctamente');
            } else {
                sendJsonResponse(false, 'Error al actualizar el año académico');
            }
            break;
            
        case 'delete':
            // Desactivar año académico (no eliminar físicamente)
            $id = intval($_POST['id'] ?? 0);
            
            if ($id <= 0) {
                sendJsonResponse(false, 'ID inválido');
            }
            
            // Obtener datos antes de desactivar
            $stmt = $pdo->prepare("SELECT * FROM anos_academicos WHERE id = ?");
            $stmt->execute([$id]);
            $datos_anteriores = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$datos_anteriores) {
                sendJsonResponse(false, 'Año académico no encontrado');
            }
            
            // Verificar si se puede desactivar (no debe estar activo si hay matriculas activas)
            if ($datos_anteriores['estado'] === 'activo') {
                $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM matriculas WHERE ano_academico_id = ? AND estado = 'matriculado'");
                $stmt->execute([$id]);
                $matriculas_activas = $stmt->fetch()['total'];
                
                if ($matriculas_activas > 0) {
                    sendJsonResponse(false, 'No se puede desactivar un año académico con matrículas activas');
                }
            }
            
            // Desactivar (cambiar estado a finalizado)
            $stmt = $pdo->prepare("UPDATE anos_academicos SET estado = 'finalizado' WHERE id = ?");
            
            if ($stmt->execute([$id])) {
                // Registrar auditoría de desactivación
                registrarAuditoria(
                    'DESACTIVAR_ANO_ACADEMICO',
                    'anos_academicos',
                    $id,
                    $datos_anteriores,
                    ['estado_nuevo' => 'finalizado', 'motivo' => 'Desactivación manual']
                );
                
                sendJsonResponse(true, 'Año académico desactivado correctamente');
            } else {
                sendJsonResponse(false, 'Error al desactivar el año académico');
            }
            break;
            
        case 'activate':
            // Activar año académico
            $id = intval($_POST['id'] ?? 0);
            
            if ($id <= 0) {
                sendJsonResponse(false, 'ID inválido');
            }
            
            // Desactivar cualquier otro año que esté activo
            $stmt = $pdo->prepare("UPDATE anos_academicos SET estado = 'finalizado' WHERE estado = 'activo'");
            $stmt->execute();
            
            // Activar el año seleccionado
            $stmt = $pdo->prepare("UPDATE anos_academicos SET estado = 'activo' WHERE id = ?");
            
            if ($stmt->execute([$id])) {
                // Registrar auditoría de activación
                registrarAuditoria(
                    'ACTIVAR_ANO_ACADEMICO',
                    'anos_academicos',
                    $id,
                    null,
                    ['estado_nuevo' => 'activo', 'otros_anos_desactivados' => true]
                );
                
                sendJsonResponse(true, 'Año académico activado correctamente');
            } else {
                sendJsonResponse(false, 'Error al activar el año académico');
            }
            break;
            
        case 'get_current':
            // Obtener año académico activo actual
            $stmt = $pdo->prepare("
                SELECT aa.*, 
                       (SELECT COUNT(*) FROM periodos_academicos pa WHERE pa.ano_academico_id = aa.id) as total_periodos,
                       (SELECT COUNT(*) FROM matriculas m WHERE m.ano_academico_id = aa.id) as total_matriculas
                FROM anos_academicos aa
                WHERE aa.estado = 'activo'
                LIMIT 1
            ");
            $stmt->execute();
            $ano_actual = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Registrar auditoría de consulta actual
            registrarAuditoria(
                'CONSULTA_ANO_ACTUAL',
                'anos_academicos',
                $ano_actual['id'] ?? null,
                null,
                $ano_actual ? [
                    'ano_actual' => $ano_actual['nombre'],
                    'anio' => $ano_actual['anio'],
                    'estado' => $ano_actual['estado']
                ] : ['resultado' => 'sin_ano_activo']
            );
            
            if ($ano_actual) {
                sendJsonResponse(true, 'Año académico actual obtenido', $ano_actual);
            } else {
                sendJsonResponse(false, 'No hay ningún año académico activo');
            }
            break;
            
        default:
            // Registrar intento de acción no válida
            registrarAuditoria(
                'ACCION_NO_VALIDA',
                'anos_academicos',
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
        'anos_academicos',
        null,
        null,
        [
            'error_message' => $e->getMessage(),
            'error_code' => $e->getCode(),
            'accion_intentada' => $action ?? 'desconocida'
        ]
    );
    
    error_log("Error de BD en anos_controller: " . $e->getMessage());
    sendJsonResponse(false, 'Error de base de datos', null, 500);
    
} catch (Exception $e) {
    // Registrar error general en auditoría
    registrarAuditoria(
        'ERROR_GENERAL',
        'anos_academicos',
        null,
        null,
        [
            'error_message' => $e->getMessage(),
            'accion_intentada' => $action ?? 'desconocida'
        ]
    );
    
    error_log("Error en anos_controller: " . $e->getMessage());
    sendJsonResponse(false, $e->getMessage(), null, 500);
    
} catch (Throwable $e) {
    // Registrar error fatal en auditoría
    registrarAuditoria(
        'ERROR_FATAL',
        'anos_academicos',
        null,
        null,
        [
            'error_message' => $e->getMessage(),
            'accion_intentada' => $action ?? 'desconocida'
        ]
    );
    
    error_log("Error fatal en anos_controller: " . $e->getMessage());
    sendJsonResponse(false, 'Error interno del servidor', null, 500);
}

// ============================================================================
// FUNCIONES DE UTILIDAD
// ============================================================================

/**
 * Función para crear períodos académicos automáticamente
 */
function crearPeriodosAcademicos($ano_academico_id, $tipo_periodo, $fecha_inicio, $fecha_fin) {
    global $pdo;
    
    try {
        $inicio = new DateTime($fecha_inicio);
        $fin = new DateTime($fecha_fin);
        $periodos = [];
        
        switch ($tipo_periodo) {
            case 'bimestral':
                $total_dias = $inicio->diff($fin)->days;
                $dias_por_periodo = intval($total_dias / 4);
                
                for ($i = 1; $i <= 4; $i++) {
                    $periodo_inicio = clone $inicio;
                    $periodo_inicio->add(new DateInterval('P' . (($i - 1) * $dias_por_periodo) . 'D'));
                    
                    $periodo_fin = clone $periodo_inicio;
                    $periodo_fin->add(new DateInterval('P' . ($dias_por_periodo - 1) . 'D'));
                    
                    if ($i == 4) {
                        $periodo_fin = $fin; // Asegurar que el último período termine en la fecha correcta
                    }
                    
                    $nombre = 'Bimestre ' . conversorNumeroRomano($i);
                    $periodos[] = crearPeriodo($ano_academico_id, $i, $nombre, $periodo_inicio, $periodo_fin);
                }
                break;
                
            case 'trimestral':
                $total_dias = $inicio->diff($fin)->days;
                $dias_por_periodo = intval($total_dias / 3);
                
                for ($i = 1; $i <= 3; $i++) {
                    $periodo_inicio = clone $inicio;
                    $periodo_inicio->add(new DateInterval('P' . (($i - 1) * $dias_por_periodo) . 'D'));
                    
                    $periodo_fin = clone $periodo_inicio;
                    $periodo_fin->add(new DateInterval('P' . ($dias_por_periodo - 1) . 'D'));
                    
                    if ($i == 3) {
                        $periodo_fin = $fin;
                    }
                    
                    $nombre = 'Trimestre ' . conversorNumeroRomano($i);
                    $periodos[] = crearPeriodo($ano_academico_id, $i, $nombre, $periodo_inicio, $periodo_fin);
                }
                break;
                
            case 'semestral':
                $total_dias = $inicio->diff($fin)->days;
                $dias_por_periodo = intval($total_dias / 2);
                
                for ($i = 1; $i <= 2; $i++) {
                    $periodo_inicio = clone $inicio;
                    $periodo_inicio->add(new DateInterval('P' . (($i - 1) * $dias_por_periodo) . 'D'));
                    
                    $periodo_fin = clone $periodo_inicio;
                    $periodo_fin->add(new DateInterval('P' . ($dias_por_periodo - 1) . 'D'));
                    
                    if ($i == 2) {
                        $periodo_fin = $fin;
                    }
                    
                    $nombre = 'Semestre ' . conversorNumeroRomano($i);
                    $periodos[] = crearPeriodo($ano_academico_id, $i, $nombre, $periodo_inicio, $periodo_fin);
                }
                break;
        }
        
        return $periodos;
        
    } catch (Exception $e) {
        error_log("Error creando períodos académicos: " . $e->getMessage());
        return [];
    }
}

/**
 * Función auxiliar para crear un período individual
 */
function crearPeriodo($ano_academico_id, $numero, $nombre, $fecha_inicio, $fecha_fin) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            INSERT INTO periodos_academicos 
            (ano_academico_id, numero_periodo, nombre, fecha_inicio, fecha_fin, estado)
            VALUES (?, ?, ?, ?, ?, 'planificado')
        ");
        
        $stmt->execute([
            $ano_academico_id,
            $numero,
            $nombre,
            $fecha_inicio->format('Y-m-d'),
            $fecha_fin->format('Y-m-d')
        ]);
        
        return [
            'id' => $pdo->lastInsertId(),
            'numero_periodo' => $numero,
            'nombre' => $nombre,
            'fecha_inicio' => $fecha_inicio->format('Y-m-d'),
            'fecha_fin' => $fecha_fin->format('Y-m-d')
        ];
        
    } catch (Exception $e) {
        error_log("Error creando período individual: " . $e->getMessage());
        return null;
    }
}

/**
 * Convertir número a romano
 */
function conversorNumeroRomano($numero) {
    $numeros = [1 => 'I', 2 => 'II', 3 => 'III', 4 => 'IV', 5 => 'V'];
    return $numeros[$numero] ?? $numero;
}

/**
 * Función para validar permisos de años académicos
 */
function validateAnoPermissions($action, $ano_data = null) {
    // Implementar validación de permisos según tu sistema
    return true;
}

/**
 * Función para generar estadísticas de años académicos
 */
function generarEstadisticasAnos() {
    global $pdo;
    
    try {
        $estadisticas = [];
        
        // Total de años académicos
        $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM anos_academicos");
        $stmt->execute();
        $estadisticas['total_anos'] = $stmt->fetch()['total'];
        
        // Años por estado
        $stmt = $pdo->prepare("
            SELECT estado, COUNT(*) as total
            FROM anos_academicos
            GROUP BY estado
        ");
        $stmt->execute();
        $estadisticas['anos_por_estado'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Años por tipo de período
        $stmt = $pdo->prepare("
            SELECT tipo_periodo, COUNT(*) as total
            FROM anos_academicos
            GROUP BY tipo_periodo
        ");
        $stmt->execute();
        $estadisticas['anos_por_tipo_periodo'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Registrar generación de estadísticas
        registrarAuditoria(
            'GENERACION_ESTADISTICAS_ANOS',
            'anos_academicos',
            null,
            null,
            $estadisticas
        );
        
        return $estadisticas;
        
    } catch (Exception $e) {
        error_log("Error generando estadísticas de años: " . $e->getMessage());
        
        // Registrar error en auditoría
        registrarAuditoria(
            'ERROR_ESTADISTICAS_ANOS',
            'anos_academicos',
            null,
            null,
            ['error_message' => $e->getMessage()]
        );
        
        return false;
    }
}

?>