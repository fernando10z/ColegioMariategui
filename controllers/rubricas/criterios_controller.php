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
 * Función para obtener datos de un criterio antes de modificarlo
 */
function obtenerDatosCriterio($criterio_id) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            SELECT ce.*, c.nombre as competencia_nombre, c.codigo as competencia_codigo
            FROM criterios_evaluacion ce
            LEFT JOIN competencias c ON ce.competencia_id = c.id
            WHERE ce.id = ?
        ");
        $stmt->execute([$criterio_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
        
    } catch (Exception $e) {
        error_log("Error obteniendo datos de criterio: " . $e->getMessage());
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

// Función para validar peso porcentual
function validatePorcentaje($peso) {
    $peso = floatval($peso);
    return $peso >= 0 && $peso <= 100;
}

try {
    // Verificar método de solicitud
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        sendJsonResponse(false, 'Método no permitido', null, 405);
    }
    
    // Obtener acción
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        
        case 'get_by_competencia':
            // Obtener criterios por competencia
            $competencia_id = intval($_POST['competencia_id'] ?? 0);
            
            if ($competencia_id <= 0) {
                sendJsonResponse(false, 'ID de competencia inválido');
            }
            
            // Obtener información de la competencia
            $stmt = $pdo->prepare("
                SELECT c.*, ac.nombre as area_nombre, ne.nombre as nivel_nombre
                FROM competencias c
                LEFT JOIN areas_curriculares ac ON c.area_curricular_id = ac.id
                LEFT JOIN niveles_educativos ne ON c.nivel_educativo_id = ne.id
                WHERE c.id = ?
            ");
            $stmt->execute([$competencia_id]);
            $competencia = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$competencia) {
                sendJsonResponse(false, 'Competencia no encontrada');
            }
            
            // Obtener criterios de evaluación
            $stmt = $pdo->prepare("
                SELECT * FROM criterios_evaluacion 
                WHERE competencia_id = ? 
                ORDER BY orden_visualizacion ASC, id ASC
            ");
            $stmt->execute([$competencia_id]);
            $criterios = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Registrar auditoría de consulta
            registrarAuditoria(
                'CONSULTA_CRITERIOS_COMPETENCIA',
                'criterios_evaluacion',
                null,
                null,
                [
                    'competencia_id' => $competencia_id,
                    'competencia_nombre' => $competencia['nombre'],
                    'total_criterios' => count($criterios)
                ]
            );
            
            // Calcular estadísticas
            $totalPeso = array_sum(array_column($criterios, 'peso_porcentaje'));
            $criteriosPrincipales = array_filter($criterios, function($c) { return $c['es_principal'] == 1; });
            
            sendJsonResponse(true, 'Criterios obtenidos correctamente', [
                'competencia' => $competencia,
                'criterios' => $criterios,
                'estadisticas' => [
                    'total_criterios' => count($criterios),
                    'criterios_principales' => count($criteriosPrincipales),
                    'peso_total' => $totalPeso,
                    'peso_faltante' => max(0, 100 - $totalPeso)
                ]
            ]);
            break;
            
        case 'create':
            // Crear nuevo criterio
            $requiredFields = ['competencia_id', 'descripcion'];
            $missingFields = validateRequiredFields($_POST, $requiredFields);
            
            if (!empty($missingFields)) {
                sendJsonResponse(false, 'Campos requeridos faltantes: ' . implode(', ', $missingFields));
            }
            
            $competencia_id = intval($_POST['competencia_id']);
            $codigo = trim($_POST['codigo'] ?? '');
            $descripcion = trim($_POST['descripcion']);
            $peso_porcentaje = floatval($_POST['peso_porcentaje'] ?? 0);
            $es_principal = isset($_POST['es_principal']) ? 1 : 0;
            
            // Validaciones
            if ($competencia_id <= 0) {
                sendJsonResponse(false, 'ID de competencia inválido');
            }
            
            if (!validatePorcentaje($peso_porcentaje)) {
                sendJsonResponse(false, 'El peso porcentual debe estar entre 0 y 100');
            }
            
            // Verificar que la competencia existe
            $stmt = $pdo->prepare("SELECT id, nombre FROM competencias WHERE id = ?");
            $stmt->execute([$competencia_id]);
            $competencia_info = $stmt->fetch();
            if (!$competencia_info) {
                sendJsonResponse(false, 'Competencia no encontrada');
            }
            
            // Verificar que el código no se repita en la misma competencia (si se proporciona)
            if (!empty($codigo)) {
                $stmt = $pdo->prepare("SELECT id FROM criterios_evaluacion WHERE competencia_id = ? AND codigo = ?");
                $stmt->execute([$competencia_id, $codigo]);
                if ($stmt->fetch()) {
                    sendJsonResponse(false, 'Ya existe un criterio con ese código en esta competencia');
                }
            }
            
            // Obtener el siguiente orden de visualización
            $stmt = $pdo->prepare("SELECT COALESCE(MAX(orden_visualizacion), 0) + 1 as siguiente_orden FROM criterios_evaluacion WHERE competencia_id = ?");
            $stmt->execute([$competencia_id]);
            $orden_visualizacion = $stmt->fetch()['siguiente_orden'];
            
            // Preparar datos para auditoría
            $datos_nuevos = [
                'competencia_id' => $competencia_id,
                'competencia_nombre' => $competencia_info['nombre'],
                'codigo' => $codigo ?: null,
                'descripcion' => $descripcion,
                'peso_porcentaje' => $peso_porcentaje,
                'es_principal' => $es_principal,
                'orden_visualizacion' => $orden_visualizacion
            ];
            
            // Insertar criterio
            $stmt = $pdo->prepare("
                INSERT INTO criterios_evaluacion 
                (competencia_id, codigo, descripcion, peso_porcentaje, es_principal, orden_visualizacion) 
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            
            $result = $stmt->execute([
                $competencia_id,
                $codigo ?: null,
                $descripcion,
                $peso_porcentaje,
                $es_principal,
                $orden_visualizacion
            ]);
            
            if ($result) {
                $criterio_id = $pdo->lastInsertId();
                
                // Registrar auditoría de creación
                registrarAuditoria(
                    'CREAR_CRITERIO',
                    'criterios_evaluacion',
                    $criterio_id,
                    null,
                    $datos_nuevos
                );
                
                // Obtener el criterio creado
                $stmt = $pdo->prepare("SELECT * FROM criterios_evaluacion WHERE id = ?");
                $stmt->execute([$criterio_id]);
                $criterio = $stmt->fetch(PDO::FETCH_ASSOC);
                
                sendJsonResponse(true, 'Criterio creado correctamente', $criterio);
            } else {
                sendJsonResponse(false, 'Error al crear el criterio');
            }
            break;
            
        case 'update':
            // Actualizar criterio
            $id = intval($_POST['id'] ?? 0);
            $requiredFields = ['descripcion'];
            $missingFields = validateRequiredFields($_POST, $requiredFields);
            
            if ($id <= 0) {
                sendJsonResponse(false, 'ID inválido');
            }
            
            if (!empty($missingFields)) {
                sendJsonResponse(false, 'Campos requeridos faltantes: ' . implode(', ', $missingFields));
            }
            
            // Obtener datos anteriores para auditoría
            $datos_anteriores = obtenerDatosCriterio($id);
            if (!$datos_anteriores) {
                sendJsonResponse(false, 'Criterio no encontrado');
            }
            
            $codigo = trim($_POST['codigo'] ?? '');
            $descripcion = trim($_POST['descripcion']);
            $peso_porcentaje = floatval($_POST['peso_porcentaje'] ?? 0);
            $es_principal = isset($_POST['es_principal']) ? 1 : 0;
            
            // Validaciones
            if (!validatePorcentaje($peso_porcentaje)) {
                sendJsonResponse(false, 'El peso porcentual debe estar entre 0 y 100');
            }
            
            // Verificar que el código no se repita en la misma competencia (si se proporciona)
            if (!empty($codigo)) {
                $stmt = $pdo->prepare("SELECT id FROM criterios_evaluacion WHERE competencia_id = ? AND codigo = ? AND id != ?");
                $stmt->execute([$datos_anteriores['competencia_id'], $codigo, $id]);
                if ($stmt->fetch()) {
                    sendJsonResponse(false, 'Ya existe un criterio con ese código en esta competencia');
                }
            }
            
            // Preparar datos nuevos para auditoría
            $datos_nuevos = [
                'id' => $id,
                'competencia_id' => $datos_anteriores['competencia_id'],
                'competencia_nombre' => $datos_anteriores['competencia_nombre'],
                'codigo' => $codigo ?: null,
                'descripcion' => $descripcion,
                'peso_porcentaje' => $peso_porcentaje,
                'es_principal' => $es_principal,
                'orden_visualizacion' => $datos_anteriores['orden_visualizacion']
            ];
            
            // Actualizar criterio
            $stmt = $pdo->prepare("
                UPDATE criterios_evaluacion SET 
                codigo = ?, descripcion = ?, peso_porcentaje = ?, es_principal = ?
                WHERE id = ?
            ");
            
            $result = $stmt->execute([
                $codigo ?: null,
                $descripcion,
                $peso_porcentaje,
                $es_principal,
                $id
            ]);
            
            if ($result) {
                // Registrar auditoría de actualización
                registrarAuditoria(
                    'ACTUALIZAR_CRITERIO',
                    'criterios_evaluacion',
                    $id,
                    $datos_anteriores,
                    $datos_nuevos
                );
                
                // Obtener el criterio actualizado
                $stmt = $pdo->prepare("SELECT * FROM criterios_evaluacion WHERE id = ?");
                $stmt->execute([$id]);
                $criterio = $stmt->fetch(PDO::FETCH_ASSOC);
                
                sendJsonResponse(true, 'Criterio actualizado correctamente', $criterio);
            } else {
                sendJsonResponse(false, 'Error al actualizar el criterio');
            }
            break;
            
        case 'delete':
            // Eliminar criterio
            $id = intval($_POST['id'] ?? 0);
            
            if ($id <= 0) {
                sendJsonResponse(false, 'ID inválido');
            }
            
            // Obtener datos anteriores para auditoría
            $datos_anteriores = obtenerDatosCriterio($id);
            if (!$datos_anteriores) {
                sendJsonResponse(false, 'Criterio no encontrado');
            }
            
            // Verificar si el criterio está siendo usado en calificaciones
            $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM calificaciones WHERE criterio_evaluacion_id = ?");
            $stmt->execute([$id]);
            $calificaciones = $stmt->fetch()['total'];
            
            if ($calificaciones > 0) {
                // Registrar intento de eliminación fallido
                registrarAuditoria(
                    'INTENTO_ELIMINAR_CRITERIO_FALLIDO',
                    'criterios_evaluacion',
                    $id,
                    $datos_anteriores,
                    ['motivo' => 'Criterio tiene calificaciones asociadas', 'calificaciones_count' => $calificaciones]
                );
                
                sendJsonResponse(false, 'No se puede eliminar el criterio porque tiene calificaciones asociadas');
            }
            
            // Eliminar criterio
            $stmt = $pdo->prepare("DELETE FROM criterios_evaluacion WHERE id = ?");
            $result = $stmt->execute([$id]);
            
            if ($result) {
                // Registrar auditoría de eliminación
                registrarAuditoria(
                    'ELIMINAR_CRITERIO',
                    'criterios_evaluacion',
                    $id,
                    $datos_anteriores,
                    ['motivo' => 'Eliminación exitosa']
                );
                
                sendJsonResponse(true, 'Criterio eliminado correctamente');
            } else {
                sendJsonResponse(false, 'Error al eliminar el criterio');
            }
            break;
            
        case 'reorder':
            // Reordenar criterios
            $criterios_orden = $_POST['criterios_orden'] ?? [];
            
            if (empty($criterios_orden) || !is_array($criterios_orden)) {
                sendJsonResponse(false, 'Datos de ordenamiento inválidos');
            }
            
            // Obtener estado anterior de todos los criterios afectados
            $criterios_ids = array_map('intval', $criterios_orden);
            $placeholders = str_repeat('?,', count($criterios_ids) - 1) . '?';
            
            $stmt = $pdo->prepare("
                SELECT id, orden_visualizacion, descripcion, competencia_id 
                FROM criterios_evaluacion 
                WHERE id IN ($placeholders)
            ");
            $stmt->execute($criterios_ids);
            $criterios_anteriores = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $pdo->beginTransaction();
            
            try {
                $stmt = $pdo->prepare("UPDATE criterios_evaluacion SET orden_visualizacion = ? WHERE id = ?");
                $criterios_nuevos = [];
                
                foreach ($criterios_orden as $orden => $criterio_id) {
                    $criterio_id = intval($criterio_id);
                    $nuevo_orden = intval($orden) + 1; // Los arrays empiezan en 0, los órdenes en 1
                    
                    $result = $stmt->execute([$nuevo_orden, $criterio_id]);
                    if (!$result) {
                        throw new Exception('Error al actualizar el orden del criterio: ' . $criterio_id);
                    }
                    
                    $criterios_nuevos[] = [
                        'id' => $criterio_id,
                        'nuevo_orden' => $nuevo_orden
                    ];
                }
                
                // Registrar auditoría de reordenamiento
                registrarAuditoria(
                    'REORDENAR_CRITERIOS',
                    'criterios_evaluacion',
                    null,
                    $criterios_anteriores,
                    [
                        'nuevo_orden' => $criterios_nuevos,
                        'total_criterios_afectados' => count($criterios_orden)
                    ]
                );
                
                $pdo->commit();
                sendJsonResponse(true, 'Orden de criterios actualizado correctamente');
                
            } catch (Exception $e) {
                $pdo->rollBack();
                
                // Registrar error en auditoría
                registrarAuditoria(
                    'ERROR_REORDENAR_CRITERIOS',
                    'criterios_evaluacion',
                    null,
                    $criterios_anteriores,
                    ['error' => $e->getMessage(), 'criterios_intentados' => $criterios_orden]
                );
                
                sendJsonResponse(false, 'Error al reordenar criterios: ' . $e->getMessage());
            }
            break;
            
        case 'get':
            // Obtener criterio específico
            $id = intval($_POST['id'] ?? 0);
            
            if ($id <= 0) {
                sendJsonResponse(false, 'ID inválido');
            }
            
            $stmt = $pdo->prepare("
                SELECT ce.*, c.nombre as competencia_nombre, c.codigo as competencia_codigo
                FROM criterios_evaluacion ce
                LEFT JOIN competencias c ON ce.competencia_id = c.id
                WHERE ce.id = ?
            ");
            $stmt->execute([$id]);
            $criterio = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$criterio) {
                sendJsonResponse(false, 'Criterio no encontrado');
            }
            
            // Registrar auditoría de consulta individual
            registrarAuditoria(
                'CONSULTA_CRITERIO_INDIVIDUAL',
                'criterios_evaluacion',
                $id,
                null,
                [
                    'criterio_descripcion' => $criterio['descripcion'],
                    'competencia_nombre' => $criterio['competencia_nombre']
                ]
            );
            
            sendJsonResponse(true, 'Criterio obtenido correctamente', $criterio);
            break;
            
        case 'validate_peso_total':
            // Validar que el peso total no exceda 100%
            $competencia_id = intval($_POST['competencia_id'] ?? 0);
            $criterio_id_exclude = intval($_POST['criterio_id_exclude'] ?? 0);
            $nuevo_peso = floatval($_POST['nuevo_peso'] ?? 0);
            
            if ($competencia_id <= 0) {
                sendJsonResponse(false, 'ID de competencia inválido');
            }
            
            // Calcular peso total actual excluyendo el criterio especificado
            $sql = "SELECT SUM(peso_porcentaje) as peso_total FROM criterios_evaluacion WHERE competencia_id = ?";
            $params = [$competencia_id];
            
            if ($criterio_id_exclude > 0) {
                $sql .= " AND id != ?";
                $params[] = $criterio_id_exclude;
            }
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $peso_actual = floatval($stmt->fetch()['peso_total'] ?? 0);
            
            $peso_total_con_nuevo = $peso_actual + $nuevo_peso;
            $es_valido = $peso_total_con_nuevo <= 100;
            
            // Registrar validación en auditoría
            registrarAuditoria(
                'VALIDACION_PESO_CRITERIOS',
                'criterios_evaluacion',
                $criterio_id_exclude ?: null,
                null,
                [
                    'competencia_id' => $competencia_id,
                    'peso_actual' => $peso_actual,
                    'nuevo_peso' => $nuevo_peso,
                    'peso_total_resultante' => $peso_total_con_nuevo,
                    'es_valido' => $es_valido
                ]
            );
            
            sendJsonResponse(true, 'Validación completada', [
                'es_valido' => $es_valido,
                'peso_actual' => $peso_actual,
                'nuevo_peso' => $nuevo_peso,
                'peso_total' => $peso_total_con_nuevo,
                'peso_disponible' => max(0, 100 - $peso_actual)
            ]);
            break;
            
        default:
            // Registrar intento de acción no válida
            registrarAuditoria(
                'ACCION_NO_VALIDA',
                'criterios_evaluacion',
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
        'criterios_evaluacion',
        null,
        null,
        [
            'error_message' => $e->getMessage(),
            'error_code' => $e->getCode(),
            'accion_intentada' => $action ?? 'desconocida'
        ]
    );
    
    error_log("Error de BD en criterios_controller: " . $e->getMessage());
    sendJsonResponse(false, 'Error de base de datos: ' . $e->getMessage(), null, 500);
    
} catch (Exception $e) {
    // Registrar error general en auditoría
    registrarAuditoria(
        'ERROR_GENERAL',
        'criterios_evaluacion',
        null,
        null,
        [
            'error_message' => $e->getMessage(),
            'accion_intentada' => $action ?? 'desconocida'
        ]
    );
    
    error_log("Error en criterios_controller: " . $e->getMessage());
    sendJsonResponse(false, $e->getMessage(), null, 500);
    
} catch (Throwable $e) {
    // Registrar error fatal en auditoría
    registrarAuditoria(
        'ERROR_FATAL',
        'criterios_evaluacion',
        null,
        null,
        [
            'error_message' => $e->getMessage(),
            'accion_intentada' => $action ?? 'desconocida'
        ]
    );
    
    error_log("Error fatal en criterios_controller: " . $e->getMessage());
    sendJsonResponse(false, 'Error interno del servidor', null, 500);
}

// ============================================================================
// FUNCIONES DE UTILIDAD ADICIONALES
// ============================================================================

/**
 * Función para validar permisos de criterios
 */
function validateCriterioPermissions($action, $criterio_data = null) {
    // Implementar validación de permisos según tu sistema
    return true;
}

/**
 * Función para crear backup de criterios antes de cambios importantes
 */
function backupCriterios($competencia_id) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("SELECT * FROM criterios_evaluacion WHERE competencia_id = ?");
        $stmt->execute([$competencia_id]);
        $criterios = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $backupData = [
            'timestamp' => date('Y-m-d H:i:s'),
            'competencia_id' => $competencia_id,
            'criterios' => $criterios
        ];
        
        $backupDir = '../../backups/criterios/';
        if (!file_exists($backupDir)) {
            mkdir($backupDir, 0755, true);
        }
        
        $backupFile = $backupDir . 'criterios_backup_' . $competencia_id . '_' . date('Ymd_His') . '.json';
        file_put_contents($backupFile, json_encode($backupData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        
        // Registrar creación de backup en auditoría
        registrarAuditoria(
            'BACKUP_CRITERIOS_CREADO',
            'criterios_evaluacion',
            null,
            null,
            [
                'competencia_id' => $competencia_id,
                'backup_file' => $backupFile,
                'total_criterios' => count($criterios)
            ]
        );
        
        return $backupFile;
        
    } catch (Exception $e) {
        error_log("Error creando backup de criterios: " . $e->getMessage());
        
        // Registrar error de backup en auditoría
        registrarAuditoria(
            'ERROR_BACKUP_CRITERIOS',
            'criterios_evaluacion',
            null,
            null,
            [
                'competencia_id' => $competencia_id,
                'error_message' => $e->getMessage()
            ]
        );
        
        return false;
    }
}

/**
 * Función para generar código automático de criterio
 */
function generateCriterioCodigo($competencia_id) {
    global $pdo;
    
    try {
        // Obtener código de competencia
        $stmt = $pdo->prepare("SELECT codigo FROM competencias WHERE id = ?");
        $stmt->execute([$competencia_id]);
        $competencia_codigo = $stmt->fetch()['codigo'] ?? 'COMP';
        
        // Contar criterios existentes
        $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM criterios_evaluacion WHERE competencia_id = ?");
        $stmt->execute([$competencia_id]);
        $total = intval($stmt->fetch()['total']) + 1;
        
        $codigo_generado = $competencia_codigo . '.' . $total;
        
        // Registrar generación de código en auditoría
        registrarAuditoria(
            'CODIGO_CRITERIO_GENERADO',
            'criterios_evaluacion',
            null,
            null,
            [
                'competencia_id' => $competencia_id,
                'competencia_codigo' => $competencia_codigo,
                'codigo_generado' => $codigo_generado,
                'numero_criterio' => $total
            ]
        );
        
        return $codigo_generado;
        
    } catch (Exception $e) {
        error_log("Error generando código de criterio: " . $e->getMessage());
        
        $codigo_fallback = 'CRI.' . time();
        
        // Registrar error en auditoría
        registrarAuditoria(
            'ERROR_GENERAR_CODIGO_CRITERIO',
            'criterios_evaluacion',
            null,
            null,
            [
                'competencia_id' => $competencia_id,
                'error_message' => $e->getMessage(),
                'codigo_fallback' => $codigo_fallback
            ]
        );
        
        return $codigo_fallback;
    }
}

?>