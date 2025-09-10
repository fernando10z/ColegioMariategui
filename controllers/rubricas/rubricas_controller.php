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
        if ($usuario_id === null) {
            session_start();
            $usuario_id = $_SESSION['usuario_id'] ?? null;
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
 * Función para preparar resumen de datos de rúbrica para auditoría
 */
function prepararResumenRubrica($rubrica) {
    if (!$rubrica || !is_array($rubrica)) {
        return null;
    }
    
    return [
        'id' => $rubrica['id'] ?? null,
        'nombre' => $rubrica['nombre'] ?? null,
        'competencia_nombre' => $rubrica['competencia_nombre'] ?? null,
        'curso_nombre' => $rubrica['curso_nombre'] ?? null,
        'tipo_evaluacion' => $rubrica['tipo_evaluacion'] ?? null,
        'estado' => $rubrica['estado'] ?? null,
        'total_criterios' => $rubrica['total_criterios'] ?? 0
    ];
}

/**
 * Función para obtener datos completos de una rúbrica
 */
function obtenerDatosRubrica($rubrica_id) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            SELECT r.*, 
                   COALESCE(c.nombre, 'Sin competencia') as competencia_nombre,
                   COALESCE(c.codigo, '') as competencia_codigo,
                   COALESCE(cur.nombre, 'Sin curso') as curso_nombre,
                   COALESCE(ac.nombre, 'Sin área') as area_curricular_nombre,
                   COALESCE(g.nombre, 'Sin grado') as grado_nombre,
                   (SELECT COUNT(*) FROM criterios_evaluacion ce WHERE ce.competencia_id = r.competencia_id) as total_criterios
            FROM rubricas r
            LEFT JOIN competencias c ON r.competencia_id = c.id
            LEFT JOIN cursos cur ON r.curso_id = cur.id
            LEFT JOIN areas_curriculares ac ON c.area_curricular_id = ac.id
            LEFT JOIN grados g ON cur.grado_id = g.id
            WHERE r.id = ?
        ");
        $stmt->execute([$rubrica_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        error_log("Error obteniendo datos de rúbrica: " . $e->getMessage());
        return null;
    }
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
        // Registrar intento de acceso con método incorrecto
        registrarAuditoria(
            'ACCESO_METODO_INCORRECTO',
            'rubricas',
            null,
            null,
            ['metodo_usado' => $_SERVER['REQUEST_METHOD']]
        );
        sendJsonResponse(false, 'Método no permitido', null, 405);
    }
    
    // Obtener acción
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        
        case 'get':
            // Obtener rúbrica por ID
            $id = intval($_POST['id'] ?? 0);
            
            if ($id <= 0) {
                registrarAuditoria(
                    'CONSULTA_RUBRICA_ID_INVALIDO',
                    'rubricas',
                    null,
                    null,
                    ['id_enviado' => $_POST['id'] ?? 'no enviado']
                );
                sendJsonResponse(false, 'ID inválido');
            }
            
            // Consulta con joins para obtener información completa
            $stmt = $pdo->prepare("
                SELECT r.*, 
                       COALESCE(c.nombre, 'Sin competencia') as competencia_nombre,
                       COALESCE(c.codigo, '') as competencia_codigo,
                       COALESCE(c.descripcion, '') as competencia_descripcion,
                       COALESCE(cur.nombre, 'Sin curso') as curso_nombre,
                       COALESCE(ac.nombre, 'Sin área') as area_curricular_nombre,
                       COALESCE(g.nombre, 'Sin grado') as grado_nombre,
                       COALESCE(CONCAT(pp.nombres, ' ', pp.apellido_paterno), 'Sistema') as creado_por_nombre,
                       (SELECT COUNT(*) FROM criterios_evaluacion ce WHERE ce.competencia_id = r.competencia_id) as total_criterios
                FROM rubricas r
                LEFT JOIN competencias c ON r.competencia_id = c.id
                LEFT JOIN cursos cur ON r.curso_id = cur.id
                LEFT JOIN areas_curriculares ac ON c.area_curricular_id = ac.id
                LEFT JOIN grados g ON cur.grado_id = g.id
                LEFT JOIN usuarios u ON r.creado_por = u.id
                LEFT JOIN perfiles_personas pp ON u.id = pp.usuario_id
                WHERE r.id = ?
            ");
            
            $stmt->execute([$id]);
            $rubrica = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$rubrica) {
                registrarAuditoria(
                    'CONSULTA_RUBRICA_NO_ENCONTRADA',
                    'rubricas',
                    $id,
                    null,
                    ['rubrica_id_buscada' => $id]
                );
                sendJsonResponse(false, 'Rúbrica no encontrada');
            }
            
            // Decodificar configuración de escalas si existe
            if (!empty($rubrica['configuracion_escalas'])) {
                try {
                    $rubrica['configuracion_escalas'] = json_decode($rubrica['configuracion_escalas'], true);
                } catch (Exception $e) {
                    $rubrica['configuracion_escalas'] = null;
                }
            }
            
            // Registrar auditoría de consulta exitosa
            registrarAuditoria(
                'CONSULTA_RUBRICA_INDIVIDUAL',
                'rubricas',
                $id,
                null,
                prepararResumenRubrica($rubrica)
            );
            
            sendJsonResponse(true, 'Rúbrica obtenida correctamente', $rubrica);
            break;
            
        case 'create':
            // Crear nueva rúbrica
            $requiredFields = ['nombre', 'competencia_id', 'curso_id', 'tipo_evaluacion'];
            $missingFields = [];
            
            foreach ($requiredFields as $field) {
                if (empty($_POST[$field])) {
                    $missingFields[] = $field;
                }
            }
            
            if (!empty($missingFields)) {
                registrarAuditoria(
                    'CREAR_RUBRICA_CAMPOS_FALTANTES',
                    'rubricas',
                    null,
                    null,
                    [
                        'campos_faltantes' => $missingFields,
                        'datos_enviados' => array_intersect_key($_POST, array_flip($requiredFields))
                    ]
                );
                sendJsonResponse(false, 'Campos requeridos faltantes: ' . implode(', ', $missingFields));
            }
            
            // Validar que competencia existe y obtener sus datos
            $stmt = $pdo->prepare("
                SELECT c.*, ac.nombre as area_nombre 
                FROM competencias c 
                LEFT JOIN areas_curriculares ac ON c.area_curricular_id = ac.id 
                WHERE c.id = ? AND c.estado = 1
            ");
            $stmt->execute([intval($_POST['competencia_id'])]);
            $competencia = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$competencia) {
                registrarAuditoria(
                    'CREAR_RUBRICA_COMPETENCIA_INVALIDA',
                    'rubricas',
                    null,
                    null,
                    ['competencia_id_enviada' => $_POST['competencia_id']]
                );
                sendJsonResponse(false, 'La competencia seleccionada no existe o está inactiva');
            }
            
            // Validar que curso existe y obtener sus datos
            $stmt = $pdo->prepare("
                SELECT cur.*, g.nombre as grado_nombre, ne.nombre as nivel_nombre
                FROM cursos cur
                LEFT JOIN grados g ON cur.grado_id = g.id
                LEFT JOIN niveles_educativos ne ON g.nivel_educativo_id = ne.id
                WHERE cur.id = ? AND cur.estado = 1
            ");
            $stmt->execute([intval($_POST['curso_id'])]);
            $curso = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$curso) {
                registrarAuditoria(
                    'CREAR_RUBRICA_CURSO_INVALIDO',
                    'rubricas',
                    null,
                    null,
                    ['curso_id_enviado' => $_POST['curso_id']]
                );
                sendJsonResponse(false, 'El curso seleccionado no existe o está inactivo');
            }
            
            // Validar tipo de evaluación
            $tiposValidos = ['diagnostica', 'formativa', 'sumativa'];
            if (!in_array($_POST['tipo_evaluacion'], $tiposValidos)) {
                registrarAuditoria(
                    'CREAR_RUBRICA_TIPO_EVALUACION_INVALIDO',
                    'rubricas',
                    null,
                    null,
                    [
                        'tipo_enviado' => $_POST['tipo_evaluacion'],
                        'tipos_validos' => $tiposValidos
                    ]
                );
                sendJsonResponse(false, 'Tipo de evaluación no válido');
            }
            
            // Preparar configuración de escalas
            $configuracionEscalas = null;
            if (!empty($_POST['configuracion_escalas'])) {
                $escalasArray = json_decode($_POST['configuracion_escalas'], true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    $configuracionEscalas = $_POST['configuracion_escalas'];
                }
            }
            
            // Iniciar transacción
            $pdo->beginTransaction();
            
            try {
                // Obtener usuario actual
                session_start();
                $creadoPor = $_SESSION['usuario_id'] ?? 1;
                
                // Preparar datos para auditoría
                $datosNuevos = [
                    'nombre' => trim($_POST['nombre']),
                    'descripcion' => trim($_POST['descripcion'] ?? ''),
                    'competencia_id' => intval($_POST['competencia_id']),
                    'competencia_nombre' => $competencia['nombre'],
                    'area_curricular' => $competencia['area_nombre'],
                    'curso_id' => intval($_POST['curso_id']),
                    'curso_nombre' => $curso['nombre'],
                    'grado' => $curso['grado_nombre'],
                    'nivel_educativo' => $curso['nivel_nombre'],
                    'tipo_evaluacion' => $_POST['tipo_evaluacion'],
                    'tiene_configuracion_escalas' => !empty($configuracionEscalas),
                    'estado' => intval($_POST['estado'] ?? 1),
                    'creado_por' => $creadoPor
                ];
                
                // Insertar rúbrica
                $stmt = $pdo->prepare("
                    INSERT INTO rubricas (
                        nombre, descripcion, competencia_id, curso_id, tipo_evaluacion,
                        configuracion_escalas, estado, creado_por, fecha_creacion
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())
                ");
                
                $result = $stmt->execute([
                    $datosNuevos['nombre'],
                    $datosNuevos['descripcion'],
                    $datosNuevos['competencia_id'],
                    $datosNuevos['curso_id'],
                    $datosNuevos['tipo_evaluacion'],
                    $configuracionEscalas,
                    $datosNuevos['estado'],
                    $datosNuevos['creado_por']
                ]);
                
                if (!$result) {
                    throw new Exception('Error al insertar la rúbrica en la base de datos');
                }
                
                $rubricaId = $pdo->lastInsertId();
                $datosNuevos['id'] = $rubricaId;
                
                $pdo->commit();
                
                // Registrar auditoría de creación exitosa
                registrarAuditoria(
                    'CREAR_RUBRICA_EXITOSA',
                    'rubricas',
                    $rubricaId,
                    null,
                    $datosNuevos
                );
                
                sendJsonResponse(true, 'Rúbrica creada correctamente', ['id' => $rubricaId]);
                
            } catch (Exception $e) {
                $pdo->rollBack();
                
                // Registrar auditoría de error en creación
                registrarAuditoria(
                    'ERROR_CREAR_RUBRICA',
                    'rubricas',
                    null,
                    null,
                    [
                        'datos_intentados' => $datosNuevos ?? null,
                        'error_message' => $e->getMessage()
                    ]
                );
                
                throw $e;
            }
            break;
            
        case 'update':
            // Actualizar rúbrica
            $id = intval($_POST['id'] ?? 0);
            if ($id <= 0) {
                registrarAuditoria(
                    'ACTUALIZAR_RUBRICA_ID_INVALIDO',
                    'rubricas',
                    null,
                    null,
                    ['id_enviado' => $_POST['id'] ?? 'no enviado']
                );
                sendJsonResponse(false, 'ID inválido');
            }
            
            // Obtener datos actuales para auditoría
            $datosAnteriores = obtenerDatosRubrica($id);
            if (!$datosAnteriores) {
                registrarAuditoria(
                    'ACTUALIZAR_RUBRICA_NO_ENCONTRADA',
                    'rubricas',
                    $id,
                    null,
                    ['rubrica_id_buscada' => $id]
                );
                sendJsonResponse(false, 'Rúbrica no encontrada');
            }
            
            // Validar campos requeridos
            $requiredFields = ['nombre', 'competencia_id', 'curso_id', 'tipo_evaluacion'];
            $missingFields = [];
            
            foreach ($requiredFields as $field) {
                if (empty($_POST[$field])) {
                    $missingFields[] = $field;
                }
            }
            
            if (!empty($missingFields)) {
                registrarAuditoria(
                    'ACTUALIZAR_RUBRICA_CAMPOS_FALTANTES',
                    'rubricas',
                    $id,
                    prepararResumenRubrica($datosAnteriores),
                    [
                        'campos_faltantes' => $missingFields,
                        'datos_enviados' => array_intersect_key($_POST, array_flip($requiredFields))
                    ]
                );
                sendJsonResponse(false, 'Campos requeridos faltantes: ' . implode(', ', $missingFields));
            }
            
            // Validaciones similares al create
            $stmt = $pdo->prepare("
                SELECT c.*, ac.nombre as area_nombre 
                FROM competencias c 
                LEFT JOIN areas_curriculares ac ON c.area_curricular_id = ac.id 
                WHERE c.id = ? AND c.estado = 1
            ");
            $stmt->execute([intval($_POST['competencia_id'])]);
            $competencia = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$competencia) {
                registrarAuditoria(
                    'ACTUALIZAR_RUBRICA_COMPETENCIA_INVALIDA',
                    'rubricas',
                    $id,
                    prepararResumenRubrica($datosAnteriores),
                    ['competencia_id_enviada' => $_POST['competencia_id']]
                );
                sendJsonResponse(false, 'La competencia seleccionada no existe o está inactiva');
            }
            
            $stmt = $pdo->prepare("
                SELECT cur.*, g.nombre as grado_nombre, ne.nombre as nivel_nombre
                FROM cursos cur
                LEFT JOIN grados g ON cur.grado_id = g.id
                LEFT JOIN niveles_educativos ne ON g.nivel_educativo_id = ne.id
                WHERE cur.id = ? AND cur.estado = 1
            ");
            $stmt->execute([intval($_POST['curso_id'])]);
            $curso = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$curso) {
                registrarAuditoria(
                    'ACTUALIZAR_RUBRICA_CURSO_INVALIDO',
                    'rubricas',
                    $id,
                    prepararResumenRubrica($datosAnteriores),
                    ['curso_id_enviado' => $_POST['curso_id']]
                );
                sendJsonResponse(false, 'El curso seleccionado no existe o está inactivo');
            }
            
            $tiposValidos = ['diagnostica', 'formativa', 'sumativa'];
            if (!in_array($_POST['tipo_evaluacion'], $tiposValidos)) {
                registrarAuditoria(
                    'ACTUALIZAR_RUBRICA_TIPO_EVALUACION_INVALIDO',
                    'rubricas',
                    $id,
                    prepararResumenRubrica($datosAnteriores),
                    [
                        'tipo_enviado' => $_POST['tipo_evaluacion'],
                        'tipos_validos' => $tiposValidos
                    ]
                );
                sendJsonResponse(false, 'Tipo de evaluación no válido');
            }
            
            // Preparar configuración de escalas
            $configuracionEscalas = null;
            if (!empty($_POST['configuracion_escalas'])) {
                $escalasArray = json_decode($_POST['configuracion_escalas'], true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    $configuracionEscalas = $_POST['configuracion_escalas'];
                }
            }
            
            // Preparar datos nuevos para auditoría
            $datosNuevos = [
                'nombre' => trim($_POST['nombre']),
                'descripcion' => trim($_POST['descripcion'] ?? ''),
                'competencia_id' => intval($_POST['competencia_id']),
                'competencia_nombre' => $competencia['nombre'],
                'area_curricular' => $competencia['area_nombre'],
                'curso_id' => intval($_POST['curso_id']),
                'curso_nombre' => $curso['nombre'],
                'grado' => $curso['grado_nombre'],
                'nivel_educativo' => $curso['nivel_nombre'],
                'tipo_evaluacion' => $_POST['tipo_evaluacion'],
                'tiene_configuracion_escalas' => !empty($configuracionEscalas),
                'estado' => intval($_POST['estado'] ?? 1),
                'fecha_actualizacion' => date('Y-m-d H:i:s')
            ];
            
            // Actualizar rúbrica
            $stmt = $pdo->prepare("
                UPDATE rubricas SET 
                    nombre = ?,
                    descripcion = ?,
                    competencia_id = ?,
                    curso_id = ?,
                    tipo_evaluacion = ?,
                    configuracion_escalas = ?,
                    estado = ?
                WHERE id = ?
            ");
            
            $result = $stmt->execute([
                $datosNuevos['nombre'],
                $datosNuevos['descripcion'],
                $datosNuevos['competencia_id'],
                $datosNuevos['curso_id'],
                $datosNuevos['tipo_evaluacion'],
                $configuracionEscalas,
                $datosNuevos['estado'],
                $id
            ]);
            
            if ($result) {
                // Registrar auditoría de actualización exitosa
                registrarAuditoria(
                    'ACTUALIZAR_RUBRICA_EXITOSA',
                    'rubricas',
                    $id,
                    prepararResumenRubrica($datosAnteriores),
                    $datosNuevos
                );
                
                sendJsonResponse(true, 'Rúbrica actualizada correctamente');
            } else {
                // Registrar auditoría de error en actualización
                registrarAuditoria(
                    'ERROR_ACTUALIZAR_RUBRICA',
                    'rubricas',
                    $id,
                    prepararResumenRubrica($datosAnteriores),
                    [
                        'datos_intentados' => $datosNuevos,
                        'error_info' => $stmt->errorInfo()
                    ]
                );
                
                sendJsonResponse(false, 'Error al actualizar la rúbrica');
            }
            break;
            
        case 'delete':
            // Desactivar rúbrica (soft delete)
            $id = intval($_POST['id'] ?? 0);
            if ($id <= 0) {
                registrarAuditoria(
                    'ELIMINAR_RUBRICA_ID_INVALIDO',
                    'rubricas',
                    null,
                    null,
                    ['id_enviado' => $_POST['id'] ?? 'no enviado']
                );
                sendJsonResponse(false, 'ID inválido');
            }
            
            // Obtener datos actuales para auditoría
            $datosAnteriores = obtenerDatosRubrica($id);
            if (!$datosAnteriores) {
                registrarAuditoria(
                    'ELIMINAR_RUBRICA_NO_ENCONTRADA',
                    'rubricas',
                    $id,
                    null,
                    ['rubrica_id_buscada' => $id]
                );
                sendJsonResponse(false, 'Rúbrica no encontrada');
            }
            
            // Verificar si tiene actividades asociadas
            $stmt = $pdo->prepare("
                SELECT COUNT(*) as total 
                FROM actividades_evaluacion 
                WHERE rubrica_id = ?
            ");
            $stmt->execute([$id]);
            $actividades = intval($stmt->fetch()['total']);
            
            // Verificar si tiene calificaciones asociadas
            $stmt = $pdo->prepare("
                SELECT COUNT(DISTINCT c.id) as total_calificaciones
                FROM calificaciones c
                INNER JOIN actividades_evaluacion ae ON c.actividad_evaluacion_id = ae.id
                WHERE ae.rubrica_id = ?
            ");
            $stmt->execute([$id]);
            $calificaciones = intval($stmt->fetch()['total_calificaciones']);
            
            // Desactivar la rúbrica
            $stmt = $pdo->prepare("UPDATE rubricas SET estado = 0 WHERE id = ?");
            $result = $stmt->execute([$id]);
            
            if ($result) {
                $motivoEliminacion = $actividades > 0 ? 'tiene_actividades_asociadas' : 'eliminacion_directa';
                $mensaje = $actividades > 0 
                    ? "Rúbrica desactivada correctamente. Tiene $actividades actividad(es) y $calificaciones calificación(es) asociadas"
                    : 'Rúbrica eliminada correctamente';
                
                // Registrar auditoría de eliminación exitosa
                registrarAuditoria(
                    'ELIMINAR_RUBRICA_EXITOSA',
                    'rubricas',
                    $id,
                    prepararResumenRubrica($datosAnteriores),
                    [
                        'motivo_eliminacion' => $motivoEliminacion,
                        'actividades_asociadas' => $actividades,
                        'calificaciones_asociadas' => $calificaciones,
                        'estado_anterior' => $datosAnteriores['estado'],
                        'estado_nuevo' => 0
                    ]
                );
                
                sendJsonResponse(true, $mensaje);
            } else {
                // Registrar auditoría de error en eliminación
                registrarAuditoria(
                    'ERROR_ELIMINAR_RUBRICA',
                    'rubricas',
                    $id,
                    prepararResumenRubrica($datosAnteriores),
                    [
                        'actividades_asociadas' => $actividades,
                        'calificaciones_asociadas' => $calificaciones,
                        'error_info' => $stmt->errorInfo()
                    ]
                );
                
                sendJsonResponse(false, 'Error al eliminar la rúbrica');
            }
            break;
            
        case 'get_statistics':
            // Obtener estadísticas de uso de una rúbrica
            $id = intval($_POST['id'] ?? 0);
            if ($id <= 0) {
                registrarAuditoria(
                    'ESTADISTICAS_RUBRICA_ID_INVALIDO',
                    'rubricas',
                    null,
                    null,
                    ['id_enviado' => $_POST['id'] ?? 'no enviado']
                );
                sendJsonResponse(false, 'ID inválido');
            }
            
            // Verificar que la rúbrica existe
            $rubricaInfo = obtenerDatosRubrica($id);
            if (!$rubricaInfo) {
                registrarAuditoria(
                    'ESTADISTICAS_RUBRICA_NO_ENCONTRADA',
                    'rubricas',
                    $id,
                    null,
                    ['rubrica_id_buscada' => $id]
                );
                sendJsonResponse(false, 'Rúbrica no encontrada');
            }
            
            $stats = [];
            
            // Total de actividades que usan esta rúbrica
            $stmt = $pdo->prepare("
                SELECT COUNT(*) as total 
                FROM actividades_evaluacion 
                WHERE rubrica_id = ?
            ");
            $stmt->execute([$id]);
            $stats['actividades'] = intval($stmt->fetch()['total']);
            
            // Total de estudiantes evaluados con esta rúbrica
            $stmt = $pdo->prepare("
                SELECT COUNT(DISTINCT c.estudiante_id) as total
                FROM calificaciones c
                INNER JOIN actividades_evaluacion ae ON c.actividad_evaluacion_id = ae.id
                WHERE ae.rubrica_id = ?
            ");
            $stmt->execute([$id]);
            $stats['estudiantes'] = intval($stmt->fetch()['total']);
            
            // Total de calificaciones registradas
            $stmt = $pdo->prepare("
                SELECT COUNT(*) as total
                FROM calificaciones c
                INNER JOIN actividades_evaluacion ae ON c.actividad_evaluacion_id = ae.id
                WHERE ae.rubrica_id = ?
            ");
            $stmt->execute([$id]);
            $stats['calificaciones'] = intval($stmt->fetch()['total']);
            
            // Promedio de calificaciones por escala
            $stmt = $pdo->prepare("
                SELECT calificacion_literal, COUNT(*) as total
                FROM calificaciones c
                INNER JOIN actividades_evaluacion ae ON c.actividad_evaluacion_id = ae.id
                WHERE ae.rubrica_id = ?
                GROUP BY calificacion_literal
                ORDER BY calificacion_literal
            ");
            $stmt->execute([$id]);
            $stats['distribucion_calificaciones'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Registrar auditoría de consulta de estadísticas
            registrarAuditoria(
                'CONSULTA_ESTADISTICAS_RUBRICA',
                'rubricas',
                $id,
                null,
                [
                    'rubrica_nombre' => $rubricaInfo['nombre'],
                    'estadisticas_obtenidas' => $stats
                ]
            );
            
            sendJsonResponse(true, 'Estadísticas obtenidas correctamente', $stats);
            break;
            
        case 'get_all':
            // Obtener todas las rúbricas
            $estado = $_POST['estado'] ?? 'all';
            
            $whereClause = '';
            $params = [];
            
            if ($estado === 'active') {
                $whereClause = 'WHERE r.estado = 1';
            } elseif ($estado === 'inactive') {
                $whereClause = 'WHERE r.estado = 0';
            }
            
            $stmt = $pdo->prepare("
                SELECT r.*, 
                       COALESCE(c.nombre, 'Sin competencia') as competencia_nombre,
                       COALESCE(c.codigo, '') as competencia_codigo,
                       COALESCE(cur.nombre, 'Sin curso') as curso_nombre,
                       COALESCE(ac.nombre, 'Sin área') as area_curricular_nombre,
                       COALESCE(g.nombre, 'Sin grado') as grado_nombre,
                       (SELECT COUNT(*) FROM criterios_evaluacion ce WHERE ce.competencia_id = r.competencia_id) as total_criterios,
                       (SELECT COUNT(*) FROM actividades_evaluacion ae WHERE ae.rubrica_id = r.id) as total_actividades
                FROM rubricas r
                LEFT JOIN competencias c ON r.competencia_id = c.id
                LEFT JOIN cursos cur ON r.curso_id = cur.id
                LEFT JOIN areas_curriculares ac ON c.area_curricular_id = ac.id
                LEFT JOIN grados g ON cur.grado_id = g.id
                $whereClause
                ORDER BY r.fecha_creacion DESC
            ");
            $stmt->execute($params);
            $rubricas = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Calcular estadísticas generales para auditoría
            $rubricasActivas = array_filter($rubricas, function($r) { return $r['estado'] == 1; });
            $rubricasInactivas = array_filter($rubricas, function($r) { return $r['estado'] == 0; });
            $totalActividades = array_sum(array_column($rubricas, 'total_actividades'));
            $totalCriterios = array_sum(array_column($rubricas, 'total_criterios'));
            
            // Registrar auditoría de consulta general
            registrarAuditoria(
                'CONSULTA_RUBRICAS_TODAS',
                'rubricas',
                null,
                null,
                [
                    'filtro_estado' => $estado,
                    'total_rubricas' => count($rubricas),
                    'rubricas_activas' => count($rubricasActivas),
                    'rubricas_inactivas' => count($rubricasInactivas),
                    'total_actividades_sistema' => $totalActividades,
                    'total_criterios_sistema' => $totalCriterios,
                    'competencias_involucradas' => array_unique(array_column($rubricas, 'competencia_nombre')),
                    'cursos_involucrados' => array_unique(array_column($rubricas, 'curso_nombre'))
                ]
            );
            
            sendJsonResponse(true, 'Rúbricas obtenidas correctamente', $rubricas);
            break;
            
        case 'duplicate':
            // Duplicar rúbrica
            $id = intval($_POST['id'] ?? 0);
            if ($id <= 0) {
                registrarAuditoria(
                    'DUPLICAR_RUBRICA_ID_INVALIDO',
                    'rubricas',
                    null,
                    null,
                    ['id_enviado' => $_POST['id'] ?? 'no enviado']
                );
                sendJsonResponse(false, 'ID inválido');
            }
            
            // Obtener datos de la rúbrica original
            $datosOriginales = obtenerDatosRubrica($id);
            if (!$datosOriginales) {
                registrarAuditoria(
                    'DUPLICAR_RUBRICA_NO_ENCONTRADA',
                    'rubricas',
                    $id,
                    null,
                    ['rubrica_id_buscada' => $id]
                );
                sendJsonResponse(false, 'Rúbrica no encontrada');
            }
            
            // Iniciar transacción
            $pdo->beginTransaction();
            
            try {
                // Obtener usuario actual
                session_start();
                $creadoPor = $_SESSION['usuario_id'] ?? 1;
                
                // Crear nombre para la copia
                $nombreNuevo = $datosOriginales['nombre'] . ' (Copia)';
                
                // Insertar nueva rúbrica duplicada
                $stmt = $pdo->prepare("
                    INSERT INTO rubricas (
                        nombre, descripcion, competencia_id, curso_id, tipo_evaluacion,
                        configuracion_escalas, estado, creado_por, fecha_creacion
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())
                ");
                
                $result = $stmt->execute([
                    $nombreNuevo,
                    $datosOriginales['descripcion'],
                    $datosOriginales['competencia_id'],
                    $datosOriginales['curso_id'],
                    $datosOriginales['tipo_evaluacion'],
                    $datosOriginales['configuracion_escalas'],
                    1, // Nueva rúbrica activa por defecto
                    $creadoPor
                ]);
                
                if (!$result) {
                    throw new Exception('Error al duplicar la rúbrica');
                }
                
                $nuevaRubricaId = $pdo->lastInsertId();
                $pdo->commit();
                
                // Registrar auditoría de duplicación exitosa
                registrarAuditoria(
                    'DUPLICAR_RUBRICA_EXITOSA',
                    'rubricas',
                    $nuevaRubricaId,
                    prepararResumenRubrica($datosOriginales),
                    [
                        'rubrica_original_id' => $id,
                        'rubrica_nueva_id' => $nuevaRubricaId,
                        'nombre_original' => $datosOriginales['nombre'],
                        'nombre_nuevo' => $nombreNuevo,
                        'creado_por' => $creadoPor
                    ]
                );
                
                sendJsonResponse(true, 'Rúbrica duplicada correctamente', ['id' => $nuevaRubricaId]);
                
            } catch (Exception $e) {
                $pdo->rollBack();
                
                // Registrar auditoría de error en duplicación
                registrarAuditoria(
                    'ERROR_DUPLICAR_RUBRICA',
                    'rubricas',
                    $id,
                    prepararResumenRubrica($datosOriginales),
                    [
                        'error_message' => $e->getMessage(),
                        'nombre_intentado' => $nombreNuevo ?? 'No definido'
                    ]
                );
                
                throw $e;
            }
            break;
            
        default:
            // Registrar intento de acción no válida
            registrarAuditoria(
                'ACCION_NO_VALIDA_RUBRICAS',
                'rubricas',
                null,
                null,
                [
                    'accion_intentada' => $action,
                    'post_data' => $_POST
                ]
            );
            
            sendJsonResponse(false, 'Acción no válida', null, 400);
            break;
    }
    
} catch (PDOException $e) {
    // Registrar error de base de datos en auditoría
    registrarAuditoria(
        'ERROR_BASE_DATOS_RUBRICAS',
        'rubricas',
        null,
        null,
        [
            'error_message' => $e->getMessage(),
            'error_code' => $e->getCode(),
            'accion_intentada' => $action ?? 'desconocida'
        ]
    );
    
    error_log("Error de BD en rubricas_controller: " . $e->getMessage());
    sendJsonResponse(false, 'Error de base de datos', null, 500);
    
} catch (Exception $e) {
    // Registrar error general en auditoría
    registrarAuditoria(
        'ERROR_GENERAL_RUBRICAS',
        'rubricas',
        null,
        null,
        [
            'error_message' => $e->getMessage(),
            'accion_intentada' => $action ?? 'desconocida'
        ]
    );
    
    error_log("Error en rubricas_controller: " . $e->getMessage());
    sendJsonResponse(false, $e->getMessage(), null, 500);
    
} catch (Throwable $e) {
    // Registrar error fatal en auditoría
    registrarAuditoria(
        'ERROR_FATAL_RUBRICAS',
        'rubricas',
        null,
        null,
        [
            'error_message' => $e->getMessage(),
            'accion_intentada' => $action ?? 'desconocida'
        ]
    );
    
    error_log("Error fatal en rubricas_controller: " . $e->getMessage());
    sendJsonResponse(false, 'Error interno del servidor', null, 500);
}

// ============================================================================
// FUNCIONES DE UTILIDAD ADICIONALES
// ============================================================================

/**
 * Función para generar reporte de uso de rúbricas
 */
function generarReporteRubricas() {
    global $pdo;
    
    try {
        $reporte = [];
        
        // Estadísticas generales
        $stmt = $pdo->prepare("
            SELECT 
                COUNT(*) as total_rubricas,
                SUM(CASE WHEN estado = 1 THEN 1 ELSE 0 END) as rubricas_activas,
                SUM(CASE WHEN estado = 0 THEN 1 ELSE 0 END) as rubricas_inactivas
            FROM rubricas
        ");
        $stmt->execute();
        $reporte['estadisticas_generales'] = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Rúbricas por tipo de evaluación
        $stmt = $pdo->prepare("
            SELECT tipo_evaluacion, COUNT(*) as total
            FROM rubricas 
            WHERE estado = 1
            GROUP BY tipo_evaluacion
            ORDER BY total DESC
        ");
        $stmt->execute();
        $reporte['por_tipo_evaluacion'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Rúbricas más utilizadas
        $stmt = $pdo->prepare("
            SELECT r.nombre, r.tipo_evaluacion, COUNT(ae.id) as total_actividades
            FROM rubricas r
            LEFT JOIN actividades_evaluacion ae ON r.id = ae.rubrica_id
            WHERE r.estado = 1
            GROUP BY r.id, r.nombre, r.tipo_evaluacion
            ORDER BY total_actividades DESC
            LIMIT 10
        ");
        $stmt->execute();
        $reporte['mas_utilizadas'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Registrar generación de reporte
        registrarAuditoria(
            'GENERACION_REPORTE_RUBRICAS',
            'rubricas',
            null,
            null,
            $reporte
        );
        
        return $reporte;
        
    } catch (Exception $e) {
        error_log("Error generando reporte de rúbricas: " . $e->getMessage());
        
        // Registrar error en auditoría
        registrarAuditoria(
            'ERROR_REPORTE_RUBRICAS',
            'rubricas',
            null,
            null,
            ['error_message' => $e->getMessage()]
        );
        
        return false;
    }
}

?>