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
// VERIFICAR Y CREAR CAMPO ESTADO SI NO EXISTE
// ============================================================================

try {
    // Verificar si existe el campo estado en escalas_calificacion
    $stmt = $pdo->prepare("SHOW COLUMNS FROM escalas_calificacion LIKE 'estado'");
    $stmt->execute();
    $column_exists = $stmt->fetch();
    
    if (!$column_exists) {
        // Agregar campo estado si no existe
        $pdo->exec("ALTER TABLE escalas_calificacion ADD COLUMN estado TINYINT(1) DEFAULT 1 AFTER configuracion");
        
        // Registrar en logs que se agregó el campo
        error_log("Campo 'estado' agregado a tabla escalas_calificacion");
    }
} catch (Exception $e) {
    error_log("Error verificando/creando campo estado: " . $e->getMessage());
}

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

// ============================================================================
// FUNCIONES AUXILIARES
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

/**
 * Validar configuración de escala según tipo
 */
function validarConfiguracionEscala($tipo, $configuracion) {
    $errores = [];
    
    switch ($tipo) {
        case 'literal':
            $escalas_requeridas = ['A', 'B', 'C', 'D'];
            foreach ($escalas_requeridas as $escala) {
                if (!isset($configuracion[$escala])) {
                    $errores[] = "Falta configuración para escala {$escala}";
                    continue;
                }
                
                $config = $configuracion[$escala];
                
                if (!isset($config['rango_min']) || !isset($config['rango_max'])) {
                    $errores[] = "Faltan rangos para escala {$escala}";
                }
                
                if ($config['rango_min'] > $config['rango_max']) {
                    $errores[] = "Rango inválido en escala {$escala}: mínimo mayor que máximo";
                }
                
                if ($config['rango_min'] < 0 || $config['rango_max'] > 20) {
                    $errores[] = "Rangos fuera del límite 0-20 en escala {$escala}";
                }
            }
            
            // Verificar superposiciones
            if (empty($errores)) {
                $rangos = [];
                foreach ($escalas_requeridas as $escala) {
                    $rangos[$escala] = [
                        'min' => $configuracion[$escala]['rango_min'],
                        'max' => $configuracion[$escala]['rango_max']
                    ];
                }
                
                foreach ($rangos as $escala1 => $rango1) {
                    foreach ($rangos as $escala2 => $rango2) {
                        if ($escala1 !== $escala2) {
                            if (!(
                                $rango1['max'] < $rango2['min'] || 
                                $rango1['min'] > $rango2['max']
                            )) {
                                $errores[] = "Superposición entre escalas {$escala1} y {$escala2}";
                            }
                        }
                    }
                }
            }
            break;
            
        case 'numerico':
            if (!isset($configuracion['min']) || !isset($configuracion['max'])) {
                $errores[] = "Faltan valores mínimo y máximo";
            } else {
                if ($configuracion['min'] >= $configuracion['max']) {
                    $errores[] = "El valor mínimo debe ser menor que el máximo";
                }
                
                if ($configuracion['min'] < 0) {
                    $errores[] = "El valor mínimo no puede ser negativo";
                }
            }
            
            if (isset($configuracion['decimales']) && !in_array($configuracion['decimales'], [0, 1, 2])) {
                $errores[] = "Decimales debe ser 0, 1 o 2";
            }
            break;
            
        case 'descriptivo':
            $campos_requeridos = ['superior', 'satisfactorio', 'desarrollo', 'inicial'];
            foreach ($campos_requeridos as $campo) {
                if (!isset($configuracion[$campo]) || empty(trim($configuracion[$campo]))) {
                    $errores[] = "Falta descripción para nivel {$campo}";
                }
            }
            break;
            
        default:
            $errores[] = "Tipo de escala no válido";
    }
    
    return $errores;
}

/**
 * Preparar configuración desde formulario
 */
function prepararConfiguracionDesdeFormulario($tipo, $formData) {
    $configuracion = [];
    
    switch ($tipo) {
        case 'literal':
            $escalas = ['A', 'B', 'C', 'D'];
            foreach ($escalas as $escala) {
                $letra = strtolower($escala);
                $configuracion[$escala] = [
                    'rango_min' => intval($formData["{$letra}_rango_min"] ?? 0),
                    'rango_max' => intval($formData["{$letra}_rango_max"] ?? 0),
                    'descripcion' => trim($formData["{$letra}_descripcion"] ?? ''),
                    'color' => trim($formData["{$letra}_color"] ?? '#000000')
                ];
            }
            break;
            
        case 'numerico':
            $configuracion = [
                'min' => intval($formData['num_min'] ?? 0),
                'max' => intval($formData['num_max'] ?? 20),
                'decimales' => intval($formData['num_decimales'] ?? 2),
                'nota_aprobatoria' => floatval($formData['nota_aprobatoria'] ?? 11),
                'redondeo' => trim($formData['redondeo'] ?? 'normal')
            ];
            break;
            
        case 'descriptivo':
            $configuracion = [
                'superior' => trim($formData['desc_superior'] ?? ''),
                'satisfactorio' => trim($formData['desc_satisfactorio'] ?? ''),
                'desarrollo' => trim($formData['desc_desarrollo'] ?? ''),
                'inicial' => trim($formData['desc_inicial'] ?? '')
            ];
            break;
    }
    
    return $configuracion;
}

/**
 * Obtener configuración por defecto según tipo
 */
function obtenerConfiguracionPorDefecto($tipo) {
    switch ($tipo) {
        case 'literal':
            return [
                'A' => ['rango_min' => 18, 'rango_max' => 20, 'descripcion' => 'Logro destacado', 'color' => '#10b981'],
                'B' => ['rango_min' => 14, 'rango_max' => 17, 'descripcion' => 'Logro esperado', 'color' => '#3b82f6'],
                'C' => ['rango_min' => 11, 'rango_max' => 13, 'descripcion' => 'En proceso', 'color' => '#f59e0b'],
                'D' => ['rango_min' => 0, 'rango_max' => 10, 'descripcion' => 'En inicio', 'color' => '#ef4444']
            ];
            
        case 'numerico':
            return [
                'min' => 0,
                'max' => 20,
                'decimales' => 2,
                'nota_aprobatoria' => 11,
                'redondeo' => 'normal'
            ];
            
        case 'descriptivo':
            return [
                'superior' => 'Competente',
                'satisfactorio' => 'Satisfactorio',
                'desarrollo' => 'En desarrollo',
                'inicial' => 'Inicial'
            ];
            
        default:
            return [];
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
            // Obtener todas las escalas de calificación ACTIVAS
            $stmt = $pdo->prepare("
                SELECT ec.*, 
                       aa.nombre as ano_academico_nombre,
                       aa.anio,
                       aa.estado as ano_estado
                FROM escalas_calificacion ec
                LEFT JOIN anos_academicos aa ON ec.ano_academico_id = aa.id
                WHERE ec.estado = 1
                ORDER BY aa.anio DESC, ec.nivel_educativo, ec.fecha_creacion DESC
            ");
            $stmt->execute();
            $escalas = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Procesar configuración JSON para cada escala
            foreach ($escalas as &$escala) {
                if (!empty($escala['configuracion'])) {
                    try {
                        $config = json_decode($escala['configuracion'], true);
                        $escala['escalas_configuradas'] = $config ? count($config) : 0;
                        $escala['config_valida'] = true;
                    } catch (Exception $e) {
                        $escala['escalas_configuradas'] = 0;
                        $escala['config_valida'] = false;
                    }
                } else {
                    $escala['escalas_configuradas'] = 0;
                    $escala['config_valida'] = false;
                }
            }
            
            // Registrar auditoría
            registrarAuditoria(
                'CONSULTA_ESCALAS_TODAS',
                'escalas_calificacion',
                null,
                null,
                ['total_escalas_encontradas' => count($escalas)]
            );
            
            sendJsonResponse(true, 'Escalas obtenidas correctamente', $escalas);
            break;
            
        case 'get':
            // Obtener escala por ID (solo activas)
            $id = intval($_POST['id'] ?? 0);
            
            if ($id <= 0) {
                sendJsonResponse(false, 'ID inválido');
            }
            
            $stmt = $pdo->prepare("
                SELECT ec.*, 
                       aa.nombre as ano_academico_nombre,
                       aa.anio,
                       aa.estado as ano_estado
                FROM escalas_calificacion ec
                LEFT JOIN anos_academicos aa ON ec.ano_academico_id = aa.id
                WHERE ec.id = ? AND ec.estado = 1
            ");
            $stmt->execute([$id]);
            $escala = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$escala) {
                registrarAuditoria(
                    'CONSULTA_ESCALA_NO_ENCONTRADA',
                    'escalas_calificacion',
                    $id,
                    null,
                    ['escala_id_buscada' => $id]
                );
                
                sendJsonResponse(false, 'Escala no encontrada');
            }
            
            // Procesar configuración
            if (!empty($escala['configuracion'])) {
                try {
                    $config = json_decode($escala['configuracion'], true);
                    $escala['config_detalle'] = $config;
                    $escala['config_valida'] = true;
                } catch (Exception $e) {
                    $escala['config_detalle'] = [];
                    $escala['config_valida'] = false;
                }
            } else {
                $escala['config_detalle'] = [];
                $escala['config_valida'] = false;
            }
            
            // Registrar auditoría
            registrarAuditoria(
                'CONSULTA_ESCALA_INDIVIDUAL',
                'escalas_calificacion',
                $id,
                null,
                [
                    'ano_academico' => $escala['ano_academico_nombre'],
                    'nivel_educativo' => $escala['nivel_educativo'],
                    'tipo_escala' => $escala['tipo_escala']
                ]
            );
            
            sendJsonResponse(true, 'Escala obtenida correctamente', $escala);
            break;
            
        case 'create':
            // Crear nueva escala de calificación
            $ano_academico_id = intval($_POST['ano_academico_id'] ?? 0);
            $nivel_educativo = trim($_POST['nivel_educativo'] ?? '');
            $tipo_escala = trim($_POST['tipo_escala'] ?? '');
            $configuracion_automatica = isset($_POST['configuracion_automatica']) && $_POST['configuracion_automatica'] === 'on';
            
            // Validaciones básicas
            if ($ano_academico_id <= 0) {
                sendJsonResponse(false, 'Año académico requerido');
            }
            
            if (!in_array($nivel_educativo, ['inicial', 'primaria', 'secundaria'])) {
                sendJsonResponse(false, 'Nivel educativo no válido');
            }
            
            if (!in_array($tipo_escala, ['literal', 'numerico', 'descriptivo'])) {
                sendJsonResponse(false, 'Tipo de escala no válido');
            }
            
            // Verificar duplicados (solo entre escalas activas)
            $stmt = $pdo->prepare("
                SELECT id FROM escalas_calificacion 
                WHERE ano_academico_id = ? AND nivel_educativo = ? AND estado = 1
            ");
            $stmt->execute([$ano_academico_id, $nivel_educativo]);
            
            if ($stmt->fetch()) {
                sendJsonResponse(false, 'Ya existe una escala activa para este año académico y nivel educativo');
            }
            
            // Preparar configuración
            $configuracion = null;
            if ($configuracion_automatica) {
                $configuracion = json_encode(obtenerConfiguracionPorDefecto($tipo_escala), JSON_UNESCAPED_UNICODE);
            } else {
                // Configuración personalizada desde el formulario
                $config_personalizada = prepararConfiguracionDesdeFormulario($tipo_escala, $_POST);
                $errores = validarConfiguracionEscala($tipo_escala, $config_personalizada);
                
                if (!empty($errores)) {
                    sendJsonResponse(false, 'Errores en la configuración: ' . implode(', ', $errores));
                }
                
                $configuracion = json_encode($config_personalizada, JSON_UNESCAPED_UNICODE);
            }
            
            // Insertar escala (estado = 1 por defecto)
            $stmt = $pdo->prepare("
                INSERT INTO escalas_calificacion 
                (ano_academico_id, nivel_educativo, tipo_escala, configuracion, estado, fecha_creacion)
                VALUES (?, ?, ?, ?, 1, NOW())
            ");
            
            $result = $stmt->execute([
                $ano_academico_id,
                $nivel_educativo,
                $tipo_escala,
                $configuracion
            ]);
            
            if ($result) {
                $nuevo_id = $pdo->lastInsertId();
                
                // Registrar auditoría
                registrarAuditoria(
                    'CREAR_ESCALA_CALIFICACION',
                    'escalas_calificacion',
                    $nuevo_id,
                    null,
                    [
                        'ano_academico_id' => $ano_academico_id,
                        'nivel_educativo' => $nivel_educativo,
                        'tipo_escala' => $tipo_escala,
                        'configuracion_automatica' => $configuracion_automatica,
                        'configuracion_aplicada' => $configuracion_automatica ? 'predeterminada' : 'personalizada'
                    ]
                );
                
                sendJsonResponse(true, 'Escala de calificación creada correctamente', ['id' => $nuevo_id]);
            } else {
                sendJsonResponse(false, 'Error al crear la escala de calificación');
            }
            break;
            
        case 'update':
            // Actualizar escala existente
            $id = intval($_POST['id'] ?? 0);
            $ano_academico_id = intval($_POST['ano_academico_id'] ?? 0);
            $nivel_educativo = trim($_POST['nivel_educativo'] ?? '');
            $tipo_escala = trim($_POST['tipo_escala'] ?? '');
            
            if ($id <= 0) {
                sendJsonResponse(false, 'ID inválido');
            }
            
            // Obtener datos actuales (solo si está activa)
            $stmt = $pdo->prepare("SELECT * FROM escalas_calificacion WHERE id = ? AND estado = 1");
            $stmt->execute([$id]);
            $escala_actual = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$escala_actual) {
                sendJsonResponse(false, 'Escala no encontrada o inactiva');
            }
            
            // Validaciones
            if ($ano_academico_id <= 0) {
                sendJsonResponse(false, 'Año académico requerido');
            }
            
            if (!in_array($nivel_educativo, ['inicial', 'primaria', 'secundaria'])) {
                sendJsonResponse(false, 'Nivel educativo no válido');
            }
            
            if (!in_array($tipo_escala, ['literal', 'numerico', 'descriptivo'])) {
                sendJsonResponse(false, 'Tipo de escala no válido');
            }
            
            // Verificar duplicados (excluyendo el actual, solo activas)
            $stmt = $pdo->prepare("
                SELECT id FROM escalas_calificacion 
                WHERE ano_academico_id = ? AND nivel_educativo = ? AND id != ? AND estado = 1
            ");
            $stmt->execute([$ano_academico_id, $nivel_educativo, $id]);
            
            if ($stmt->fetch()) {
                sendJsonResponse(false, 'Ya existe otra escala activa para este año académico y nivel educativo');
            }
            
            // Si cambió el tipo de escala, resetear configuración
            $configuracion = $escala_actual['configuracion'];
            if ($tipo_escala !== $escala_actual['tipo_escala']) {
                $configuracion = json_encode(obtenerConfiguracionPorDefecto($tipo_escala), JSON_UNESCAPED_UNICODE);
            }
            
            // Actualizar escala
            $stmt = $pdo->prepare("
                UPDATE escalas_calificacion 
                SET ano_academico_id = ?, nivel_educativo = ?, tipo_escala = ?, configuracion = ?
                WHERE id = ? AND estado = 1
            ");
            
            $result = $stmt->execute([
                $ano_academico_id,
                $nivel_educativo,
                $tipo_escala,
                $configuracion,
                $id
            ]);
            
            if ($result) {
                // Registrar auditoría
                registrarAuditoria(
                    'ACTUALIZAR_ESCALA_CALIFICACION',
                    'escalas_calificacion',
                    $id,
                    $escala_actual,
                    [
                        'ano_academico_id' => $ano_academico_id,
                        'nivel_educativo' => $nivel_educativo,
                        'tipo_escala' => $tipo_escala,
                        'tipo_cambio' => $tipo_escala !== $escala_actual['tipo_escala'] ? 'con_reset_configuracion' : 'solo_metadatos'
                    ]
                );
                
                sendJsonResponse(true, 'Escala actualizada correctamente');
            } else {
                sendJsonResponse(false, 'Error al actualizar la escala');
            }
            break;
            
        case 'configure':
            // Configurar escalas de una escala existente
            $escala_id = intval($_POST['escala_id'] ?? 0);
            $tipo_escala = trim($_POST['tipo_escala'] ?? '');
            
            if ($escala_id <= 0) {
                sendJsonResponse(false, 'ID de escala inválido');
            }
            
            // Verificar que la escala existe y está activa
            $stmt = $pdo->prepare("SELECT * FROM escalas_calificacion WHERE id = ? AND estado = 1");
            $stmt->execute([$escala_id]);
            $escala_actual = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$escala_actual) {
                sendJsonResponse(false, 'Escala no encontrada o inactiva');
            }
            
            // Preparar configuración desde formulario
            $nueva_configuracion = prepararConfiguracionDesdeFormulario($tipo_escala, $_POST);
            
            // Validar configuración
            $errores = validarConfiguracionEscala($tipo_escala, $nueva_configuracion);
            if (!empty($errores)) {
                sendJsonResponse(false, 'Errores en la configuración: ' . implode(', ', $errores));
            }
            
            // Actualizar configuración
            $configuracion_json = json_encode($nueva_configuracion, JSON_UNESCAPED_UNICODE);
            
            $stmt = $pdo->prepare("
                UPDATE escalas_calificacion 
                SET configuracion = ? 
                WHERE id = ? AND estado = 1
            ");
            
            $result = $stmt->execute([$configuracion_json, $escala_id]);
            
            if ($result) {
                // Registrar auditoría
                registrarAuditoria(
                    'CONFIGURAR_ESCALAS',
                    'escalas_calificacion',
                    $escala_id,
                    ['configuracion_anterior' => $escala_actual['configuracion']],
                    [
                        'configuracion_nueva' => $nueva_configuracion,
                        'tipo_escala' => $tipo_escala,
                        'total_escalas_configuradas' => count($nueva_configuracion)
                    ]
                );
                
                sendJsonResponse(true, 'Configuración de escalas guardada correctamente');
            } else {
                sendJsonResponse(false, 'Error al guardar la configuración');
            }
            break;
            
        case 'delete':
            // SOFT DELETE - Cambiar estado a 0 en lugar de eliminar físicamente
            $id = intval($_POST['id'] ?? 0);
            
            if ($id <= 0) {
                sendJsonResponse(false, 'ID inválido');
            }
            
            // Verificar que la escala existe y está activa
            $stmt = $pdo->prepare("SELECT * FROM escalas_calificacion WHERE id = ? AND estado = 1");
            $stmt->execute([$id]);
            $escala_actual = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$escala_actual) {
                sendJsonResponse(false, 'Escala no encontrada o ya está inactiva');
            }
            
            // SOFT DELETE - Cambiar estado a 0
            $stmt = $pdo->prepare("UPDATE escalas_calificacion SET estado = 0 WHERE id = ?");
            $result = $stmt->execute([$id]);
            
            if ($result) {
                // Registrar auditoría
                registrarAuditoria(
                    'DESACTIVAR_ESCALA_CALIFICACION',
                    'escalas_calificacion',
                    $id,
                    $escala_actual,
                    [
                        'motivo' => 'desactivacion_manual',
                        'estado_anterior' => 1,
                        'estado_nuevo' => 0,
                        'calificaciones_verificadas' => 0
                    ]
                );
                
                sendJsonResponse(true, 'Escala desactivada correctamente');
            } else {
                sendJsonResponse(false, 'Error al desactivar la escala');
            }
            break;
            
        case 'restore':
            // Restaurar escala desactivada
            $id = intval($_POST['id'] ?? 0);
            
            if ($id <= 0) {
                sendJsonResponse(false, 'ID inválido');
            }
            
            // Verificar que la escala existe y está inactiva
            $stmt = $pdo->prepare("SELECT * FROM escalas_calificacion WHERE id = ? AND estado = 0");
            $stmt->execute([$id]);
            $escala_actual = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$escala_actual) {
                sendJsonResponse(false, 'Escala no encontrada o ya está activa');
            }
            
            // Verificar que no haya conflictos con escalas activas
            $stmt = $pdo->prepare("
                SELECT id FROM escalas_calificacion 
                WHERE ano_academico_id = ? AND nivel_educativo = ? AND estado = 1 AND id != ?
            ");
            $stmt->execute([$escala_actual['ano_academico_id'], $escala_actual['nivel_educativo'], $id]);
            
            if ($stmt->fetch()) {
                sendJsonResponse(false, 'No se puede restaurar: ya existe una escala activa para este año académico y nivel educativo');
            }
            
            // Restaurar - Cambiar estado a 1
            $stmt = $pdo->prepare("UPDATE escalas_calificacion SET estado = 1 WHERE id = ?");
            $result = $stmt->execute([$id]);
            
            if ($result) {
                // Registrar auditoría
                registrarAuditoria(
                    'RESTAURAR_ESCALA_CALIFICACION',
                    'escalas_calificacion',
                    $id,
                    $escala_actual,
                    [
                        'motivo' => 'restauracion_manual',
                        'estado_anterior' => 0,
                        'estado_nuevo' => 1
                    ]
                );
                
                sendJsonResponse(true, 'Escala restaurada correctamente');
            } else {
                sendJsonResponse(false, 'Error al restaurar la escala');
            }
            break;
            
        case 'check_duplicate':
            // Verificar si existe una escala duplicada (solo activas)
            $ano_academico_id = intval($_POST['ano_academico_id'] ?? 0);
            $nivel_educativo = trim($_POST['nivel_educativo'] ?? '');
            $exclude_id = intval($_POST['exclude_id'] ?? 0);
            
            if ($ano_academico_id <= 0 || empty($nivel_educativo)) {
                sendJsonResponse(false, 'Parámetros inválidos');
            }
            
            $sql = "SELECT id FROM escalas_calificacion WHERE ano_academico_id = ? AND nivel_educativo = ? AND estado = 1";
            $params = [$ano_academico_id, $nivel_educativo];
            
            if ($exclude_id > 0) {
                $sql .= " AND id != ?";
                $params[] = $exclude_id;
            }
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $existe = $stmt->fetch();
            
            sendJsonResponse(true, 'Verificación completada', ['exists' => $existe !== false]);
            break;
            
        case 'get_configuracion':
            // Obtener configuración de una escala específica (solo activas)
            $id = intval($_POST['id'] ?? 0);
            
            if ($id <= 0) {
                sendJsonResponse(false, 'ID inválido');
            }
            
            $stmt = $pdo->prepare("SELECT configuracion, tipo_escala FROM escalas_calificacion WHERE id = ? AND estado = 1");
            $stmt->execute([$id]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$result) {
                sendJsonResponse(false, 'Escala no encontrada o inactiva');
            }
            
            $configuracion_data = [
                'tipo_escala' => $result['tipo_escala'],
                'configuracion' => null
            ];
            
            if (!empty($result['configuracion'])) {
                try {
                    $configuracion_data['configuracion'] = json_decode($result['configuracion'], true);
                } catch (Exception $e) {
                    $configuracion_data['configuracion'] = null;
                }
            }
            
            sendJsonResponse(true, 'Configuración obtenida correctamente', $configuracion_data);
            break;
            
        case 'get_estadisticas':
            // Obtener estadísticas de uso de una escala (solo activas)
            $id = intval($_POST['id'] ?? 0);
            
            if ($id <= 0) {
                sendJsonResponse(false, 'ID inválido');
            }
            
            // Obtener información de la escala
            $stmt = $pdo->prepare("SELECT * FROM escalas_calificacion WHERE id = ? AND estado = 1");
            $stmt->execute([$id]);
            $escala = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$escala) {
                sendJsonResponse(false, 'Escala no encontrada o inactiva');
            }
            
            $estadisticas = [];
            
            // Total de calificaciones usando esta escala
            $stmt = $pdo->prepare("
                SELECT COUNT(*) as total 
                FROM calificaciones c
                INNER JOIN actividades_evaluacion ae ON c.actividad_evaluacion_id = ae.id
                INNER JOIN asignaciones_docentes ad ON ae.asignacion_docente_id = ad.id
                WHERE ad.ano_academico_id = ?
            ");
            $stmt->execute([$escala['ano_academico_id']]);
            $estadisticas['total_calificaciones'] = intval($stmt->fetch()['total']);
            
            // Distribución por escala (solo para literal)
            if ($escala['tipo_escala'] === 'literal') {
                $stmt = $pdo->prepare("
                    SELECT calificacion_literal, COUNT(*) as cantidad
                    FROM calificaciones c
                    INNER JOIN actividades_evaluacion ae ON c.actividad_evaluacion_id = ae.id
                    INNER JOIN asignaciones_docentes ad ON ae.asignacion_docente_id = ad.id
                    WHERE ad.ano_academico_id = ? AND c.calificacion_literal IS NOT NULL
                    GROUP BY c.calificacion_literal
                ");
                $stmt->execute([$escala['ano_academico_id']]);
                $estadisticas['distribucion'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
            }
            
            sendJsonResponse(true, 'Estadísticas obtenidas correctamente', $estadisticas);
            break;
            
        case 'get_all_including_inactive':
            // Obtener todas las escalas incluyendo inactivas (para administración)
            $stmt = $pdo->prepare("
                SELECT ec.*, 
                       aa.nombre as ano_academico_nombre,
                       aa.anio,
                       aa.estado as ano_estado
                FROM escalas_calificacion ec
                LEFT JOIN anos_academicos aa ON ec.ano_academico_id = aa.id
                ORDER BY ec.estado DESC, aa.anio DESC, ec.nivel_educativo, ec.fecha_creacion DESC
            ");
            $stmt->execute();
            $escalas = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Procesar configuración JSON para cada escala
            foreach ($escalas as &$escala) {
                if (!empty($escala['configuracion'])) {
                    try {
                        $config = json_decode($escala['configuracion'], true);
                        $escala['escalas_configuradas'] = $config ? count($config) : 0;
                        $escala['config_valida'] = true;
                    } catch (Exception $e) {
                        $escala['escalas_configuradas'] = 0;
                        $escala['config_valida'] = false;
                    }
                } else {
                    $escala['escalas_configuradas'] = 0;
                    $escala['config_valida'] = false;
                }
            }
            
            // Registrar auditoría
            registrarAuditoria(
                'CONSULTA_ESCALAS_TODAS_INCLUYENDO_INACTIVAS',
                'escalas_calificacion',
                null,
                null,
                [
                    'total_escalas_encontradas' => count($escalas),
                    'escalas_activas' => count(array_filter($escalas, function($e) { return $e['estado'] == 1; })),
                    'escalas_inactivas' => count(array_filter($escalas, function($e) { return $e['estado'] == 0; }))
                ]
            );
            
            sendJsonResponse(true, 'Escalas obtenidas correctamente (incluyendo inactivas)', $escalas);
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
    sendJsonResponse(false, 'Error de base de datos', null, 500);
    
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
?>