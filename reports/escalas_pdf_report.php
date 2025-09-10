<?php
// Verificar que hay parámetros de filtro del DataTable
$filtros = [];
$where_conditions = [];
$params = [];

// Procesar filtros del DataTable
if (!empty($_GET['filter_id'])) {
    $filtros['ID'] = $_GET['filter_id'];
    $where_conditions[] = "ec.id LIKE ?";
    $params[] = '%' . $_GET['filter_id'] . '%';
}

if (!empty($_GET['filter_año_académico'])) {
    $filtros['Año Académico'] = $_GET['filter_año_académico'];
    $where_conditions[] = "(aa.nombre LIKE ? OR aa.anio LIKE ?)";
    $params[] = '%' . $_GET['filter_año_académico'] . '%';
    $params[] = '%' . $_GET['filter_año_académico'] . '%';
}

if (!empty($_GET['filter_nivel_educativo'])) {
    $filtros['Nivel Educativo'] = $_GET['filter_nivel_educativo'];
    $where_conditions[] = "ec.nivel_educativo LIKE ?";
    $params[] = '%' . $_GET['filter_nivel_educativo'] . '%';
}

if (!empty($_GET['filter_tipo_escala'])) {
    $filtros['Tipo Escala'] = $_GET['filter_tipo_escala'];
    $where_conditions[] = "ec.tipo_escala LIKE ?";
    $params[] = '%' . $_GET['filter_tipo_escala'] . '%';
}

if (!empty($_GET['filter_escalas'])) {
    $filtros['Escalas Configuradas'] = $_GET['filter_escalas'];
    $where_conditions[] = "ec.configuracion LIKE ?";
    $params[] = '%' . $_GET['filter_escalas'] . '%';
}

if (!empty($_GET['filter_configuración'])) {
    $filtros['Configuración'] = $_GET['filter_configuración'];
    $where_conditions[] = "ec.configuracion LIKE ?";
    $params[] = '%' . $_GET['filter_configuración'] . '%';
}

if (!empty($_GET['filter_fecha'])) {
    $filtros['Fecha'] = $_GET['filter_fecha'];
    $where_conditions[] = "DATE(ec.fecha_creacion) = ?";
    $params[] = $_GET['filter_fecha'];
}

// Incluir configuración de BD
include '../config/bd.php';

// Configurar zona horaria
date_default_timezone_set('America/Lima');

// Obtener configuración del sistema
$configQuery = "SELECT nombre_institucion, email, telefono, logo_url FROM configuracion_sistema WHERE id = 1";
$configStmt = $pdo->prepare($configQuery);
$configStmt->execute();
$config = $configStmt->fetch(PDO::FETCH_ASSOC);

if (!$config) {
    $config = [
        'nombre_institucion' => 'I.E. José Carlos Mariátegui',
        'email' => 'info@jcmariategui.edu.pe',
        'telefono' => '01-234-5678'
    ];
}

// Construir consulta de escalas con filtros
$baseQuery = "SELECT ec.id, ec.ano_academico_id, ec.nivel_educativo, ec.tipo_escala, 
                     ec.configuracion, ec.fecha_creacion,
                     COALESCE(aa.nombre, 'Año no especificado') as ano_academico_nombre,
                     COALESCE(aa.anio, 0) as anio,
                     COALESCE(aa.estado, 'inactivo') as ano_estado
              FROM escalas_calificacion ec
              LEFT JOIN anos_academicos aa ON ec.ano_academico_id = aa.id";

if (!empty($where_conditions)) {
    $baseQuery .= " WHERE " . implode(" AND ", $where_conditions);
}

$baseQuery .= " ORDER BY aa.anio DESC, ec.nivel_educativo, ec.fecha_creacion DESC LIMIT 500";

$stmt = $pdo->prepare($baseQuery);
$stmt->execute($params);
$escalas = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Procesar configuración JSON para mostrar información legible
foreach ($escalas as &$escala) {
    if (!empty($escala['configuracion'])) {
        $config_json = json_decode($escala['configuracion'], true);
        if ($config_json && is_array($config_json)) {
            $escala['escalas_configuradas'] = count($config_json);
            $escala['config_detalle'] = $config_json;
            
            // Crear resumen de escalas
            $resumen_escalas = [];
            foreach ($config_json as $letra => $datos) {
                $resumen_escalas[] = $letra . ' (' . ($datos['rango_min'] ?? '0') . '-' . ($datos['rango_max'] ?? '0') . ')';
            }
            $escala['resumen_escalas'] = implode(', ', $resumen_escalas);
        } else {
            $escala['escalas_configuradas'] = 0;
            $escala['config_detalle'] = [];
            $escala['resumen_escalas'] = 'Sin configuración';
        }
    } else {
        $escala['escalas_configuradas'] = 0;
        $escala['config_detalle'] = [];
        $escala['resumen_escalas'] = 'Sin configuración';
    }
}

// Obtener estadísticas
$totalQuery = "SELECT COUNT(*) as total FROM escalas_calificacion ec";
if (!empty($where_conditions)) {
    $totalQuery .= " LEFT JOIN anos_academicos aa ON ec.ano_academico_id = aa.id WHERE " . implode(" AND ", $where_conditions);
    $totalStmt = $pdo->prepare($totalQuery);
    $totalStmt->execute($params);
} else {
    $totalStmt = $pdo->prepare($totalQuery);
    $totalStmt->execute();
}
$totalRegistros = $totalStmt->fetch()['total'];

// Obtener estadísticas por nivel educativo
$statsQuery = "SELECT nivel_educativo, COUNT(*) as total FROM escalas_calificacion ec";
if (!empty($where_conditions)) {
    $statsQuery .= " LEFT JOIN anos_academicos aa ON ec.ano_academico_id = aa.id WHERE " . implode(" AND ", $where_conditions);
    $statsQuery .= " GROUP BY nivel_educativo";
    $statsStmt = $pdo->prepare($statsQuery);
    $statsStmt->execute($params);
} else {
    $statsQuery .= " GROUP BY nivel_educativo";
    $statsStmt = $pdo->prepare($statsQuery);
    $statsStmt->execute();
}
$statsPorNivel = $statsStmt->fetchAll(PDO::FETCH_ASSOC);

// Si no hay datos, mostrar mensaje
if (empty($escalas)) {
    header('Content-Type: text/html; charset=utf-8');
    echo "<script>alert('No hay registros que coincidan con los filtros aplicados.'); window.close();</script>";
    exit;
}

// Generar HTML para PDF
$html = '<style>
    body {
        font-family: Arial, sans-serif;
        font-size: 11px;
        color: #333;
        margin: 0;
        padding: 0;
    }
    
    .header-table {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 20px;
    }
    
    .logo-section {
        width: 20%;
        text-align: center;
        border: 1px solid #666;
        padding: 10px;
        border-radius: 10px;
    }
    
    .logo-placeholder {
        width: 60px;
        height: 60px;
        background-color: #2563eb;
        border-radius: 50%;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 16px;
        font-weight: bold;
    }
    
    .institution-info {
        width: 50%;
        text-align: center;
        padding: 10px;
    }
    
    .institution-info h2 {
        margin: 0 0 5px 0;
        font-size: 16px;
        color: #333;
    }
    
    .report-info {
        width: 30%;
        text-align: center;
        border: 1px solid #666;
        padding: 10px;
        border-radius: 10px;
        background-color: #f8f9fa;
    }
    
    .section-title {
        background-color: #e9ecef;
        padding: 10px;
        margin: 20px 0 10px 0;
        font-weight: bold;
        text-align: center;
        border-radius: 5px;
        font-size: 14px;
    }
    
    .filters-info {
        background-color: #fff3cd;
        padding: 8px;
        margin: 10px 0;
        border-radius: 5px;
        font-size: 10px;
    }
    
    .stats-table {
        width: 100%;
        border-collapse: collapse;
        margin: 10px 0;
        background-color: #f8f9fa;
    }
    
    .stats-table th,
    .stats-table td {
        border: 0.5px solid #333;
        padding: 8px;
        font-size: 10px;
        text-align: center;
    }
    
    .stats-table th {
        background-color: #e9ecef;
        font-weight: bold;
    }
    
    .escalas-table {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 20px;
    }
    
    .escalas-table th,
    .escalas-table td {
        border: 0.5px solid #333;
        padding: 6px;
        font-size: 9px;
        text-align: left;
        vertical-align: top;
    }
    
    .escalas-table th {
        background-color: #f2f2f2;
        font-weight: bold;
        text-align: center;
    }
    
    .nivel-badge {
        padding: 2px 6px;
        border-radius: 10px;
        font-size: 8px;
        font-weight: bold;
        color: white;
        display: inline-block;
    }
    
    .badge-inicial { background-color: #ec4899; }
    .badge-primaria { background-color: #4f46e5; }
    .badge-secundaria { background-color: #7c3aed; }
    
    .tipo-badge {
        padding: 2px 6px;
        border-radius: 10px;
        font-size: 8px;
        font-weight: bold;
        color: white;
        display: inline-block;
    }
    
    .badge-literal { background-color: #2563eb; }
    .badge-numerico { background-color: #059669; }
    .badge-descriptivo { background-color: #d97706; }
    
    .estado-badge {
        padding: 1px 4px;
        border-radius: 8px;
        font-size: 7px;
        font-weight: bold;
        color: white;
        display: inline-block;
    }
    
    .badge-activo { background-color: #059669; }
    .badge-inactivo { background-color: #dc2626; }
    
    .configuracion-detail {
        font-family: "Courier New", monospace;
        background-color: #f8f9fa;
        padding: 3px 5px;
        border-radius: 3px;
        font-size: 8px;
        color: #333;
        border: 0.5px solid #ddd;
    }
    
    .contador-escalas {
        background-color: #059669;
        color: white;
        padding: 2px 6px;
        border-radius: 10px;
        font-size: 8px;
        font-weight: bold;
        display: inline-block;
        min-width: 15px;
        text-align: center;
    }
    
    .footer {
        margin-top: 20px;
        padding: 10px;
        font-size: 10px;
        border: 0.5px solid #333;
        border-radius: 5px;
        text-align: center;
        background-color: #f8f9fa;
    }
    
    .col-id { width: 6%; }
    .col-ano { width: 16%; }
    .col-nivel { width: 12%; }
    .col-tipo { width: 14%; }
    .col-escalas { width: 8%; }
    .col-config { width: 30%; }
    .col-fecha { width: 14%; }
</style>';

// Cabecera del reporte
$html .= '<table class="header-table">
    <tr>
        <td class="logo-section">
            <img src="https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcS6UHoiV1ib0jjh3_DpCMdzgLrkJdXnN4yr6A&s" alt="Logo" style="max-width:60px; max-height:60px; border-radius:50%; background:#fff; border:1px solid #ccc; display:block; margin:0 auto 5px auto;">
        </td>
        <td class="institution-info">
            <h2>' . htmlspecialchars($config['nombre_institucion']) . '</h2>
            <div>Sistema de Gestión Educativa</div>
            <div>' . htmlspecialchars($config['email']) . '</div>
            <div>Telf. ' . htmlspecialchars($config['telefono']) . '</div>
        </td>
        <td class="report-info">
            <h3>REPORTE DE ESCALAS DE CALIFICACIÓN</h3>
            <div>Fecha: ' . date('d/m/Y') . '</div>
            <div>Hora: ' . date('H:i:s') . '</div>
        </td>
    </tr>
</table>';

// Información de filtros aplicados
if (!empty($filtros)) {
    $html .= '<div class="filters-info">
        <strong>Filtros aplicados:</strong> ';
    foreach ($filtros as $campo => $valor) {
        $html .= $campo . ': ' . htmlspecialchars($valor) . ' | ';
    }
    $html = rtrim($html, ' | ') . '</div>';
}

$html .= '<div class="section-title">ESCALAS DE CALIFICACIÓN (' . count($escalas) . ' de ' . $totalRegistros . ' registros)</div>';

// Tabla de escalas
$html .= '<table class="escalas-table">
    <thead>
        <tr>
            <th class="col-id">ID</th>
            <th class="col-ano">Año Académico</th>
            <th class="col-nivel">Nivel</th>
            <th class="col-tipo">Tipo</th>
            <th class="col-escalas">Escalas</th>
            <th class="col-config">Configuración</th>
            <th class="col-fecha">Fecha</th>
        </tr>
    </thead>
    <tbody>';

foreach ($escalas as $escala) {
    // Determinar clases de badges
    $nivelClass = 'badge-secundaria';
    $nivelDisplay = 'Secundaria';
    switch ($escala['nivel_educativo']) {
        case 'inicial':
            $nivelClass = 'badge-inicial';
            $nivelDisplay = 'Inicial';
            break;
        case 'primaria':
            $nivelClass = 'badge-primaria';
            $nivelDisplay = 'Primaria';
            break;
    }
    
    $tipoClass = 'badge-literal';
    $tipoDisplay = 'Literal';
    switch ($escala['tipo_escala']) {
        case 'numerico':
            $tipoClass = 'badge-numerico';
            $tipoDisplay = 'Numérico';
            break;
        case 'descriptivo':
            $tipoClass = 'badge-descriptivo';
            $tipoDisplay = 'Descriptivo';
            break;
    }
    
    $estadoClass = $escala['ano_estado'] === 'activo' ? 'badge-activo' : 'badge-inactivo';
    
    $html .= '<tr>
        <td class="col-id">' . htmlspecialchars($escala['id']) . '</td>
        <td class="col-ano">
            <div style="font-weight: bold; font-size: 9px;">' . htmlspecialchars($escala['ano_academico_nombre']) . '</div>';
    
    if (!empty($escala['anio'])) {
        $html .= '<div style="font-size: 8px; color: #666;">Año ' . htmlspecialchars($escala['anio']) . '</div>
                  <span class="estado-badge ' . $estadoClass . '">' . ucfirst($escala['ano_estado']) . '</span>';
    }
    
    $html .= '</td>
        <td class="col-nivel">
            <span class="nivel-badge ' . $nivelClass . '">' . $nivelDisplay . '</span>
        </td>
        <td class="col-tipo">
            <span class="tipo-badge ' . $tipoClass . '">' . $tipoDisplay . '</span>
        </td>
        <td class="col-escalas text-center">
            <span class="contador-escalas">' . intval($escala['escalas_configuradas']) . '</span>
        </td>
        <td class="col-config">';
    
    if (!empty($escala['resumen_escalas']) && $escala['resumen_escalas'] !== 'Sin configuración') {
        $html .= '<div class="configuracion-detail">' . htmlspecialchars($escala['resumen_escalas']) . '</div>';
        
        // Mostrar detalles adicionales si hay configuración
        if (!empty($escala['config_detalle'])) {
            $html .= '<div style="margin-top: 3px; font-size: 7px; color: #666;">';
            foreach ($escala['config_detalle'] as $letra => $config) {
                $descripcion = $config['descripcion'] ?? 'Sin descripción';
                if (strlen($descripcion) > 25) {
                    $descripcion = substr($descripcion, 0, 22) . '...';
                }
                $html .= '<div>' . $letra . ': ' . htmlspecialchars($descripcion) . '</div>';
            }
            $html .= '</div>';
        }
    } else {
        $html .= '<span style="color: #666; font-style: italic;">Sin configuración</span>';
    }
    
    $html .= '</td>
        <td class="col-fecha">' . date('d/m/Y', strtotime($escala['fecha_creacion'])) . '</td>
    </tr>';
}

$html .= '</tbody></table>';

// Pie de página
$html .= '<div class="footer">
    <strong>Reporte generado el:</strong> ' . date('d/m/Y H:i:s') . '<br>
    <strong>Sistema:</strong> Gestión Educativa José Carlos Mariátegui<br>
    <strong>Total de registros mostrados:</strong> ' . count($escalas) . ' de ' . $totalRegistros . '
</div>';

// Generar PDF usando DomPDF
require_once '../pdf/vendor/autoload.php';

use Dompdf\Dompdf;
use Dompdf\Options;

$options = new Options();
$options->set('isHtml5ParserEnabled', true);
// Permitir imágenes remotas para que DomPDF pueda cargar la imagen del logo
$options->set('isRemoteEnabled', true);
$options->set('defaultFont', 'Arial');

$dompdf = new Dompdf($options);
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'landscape');
$dompdf->render();

// Enviar PDF al navegador
$filename = 'Escalas_Calificacion_' . date('Y-m-d_H-i-s') . '.pdf';
header("Content-Type: application/pdf");
header("Content-Disposition: inline; filename=\"$filename\"");
echo $dompdf->output();
exit;
?>