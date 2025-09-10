<?php
// Verificar que hay parámetros de filtro del DataTable
$filtros = [];
$where_conditions = [];
$params = [];

// Procesar filtros del DataTable
if (!empty($_GET['filter_usuario'])) {
    $filtros['Usuario'] = $_GET['filter_usuario'];
    $where_conditions[] = "(CONCAT(pp.nombres, ' ', pp.apellido_paterno, ' ', COALESCE(pp.apellido_materno, '')) LIKE ? OR u.email LIKE ?)";
    $params[] = '%' . $_GET['filter_usuario'] . '%';
    $params[] = '%' . $_GET['filter_usuario'] . '%';
}

if (!empty($_GET['filter_accion'])) {
    $filtros['Acción'] = $_GET['filter_accion'];
    $where_conditions[] = "la.accion LIKE ?";
    $params[] = '%' . $_GET['filter_accion'] . '%';
}

if (!empty($_GET['filter_tabla'])) {
    $filtros['Tabla'] = $_GET['filter_tabla'];
    $where_conditions[] = "la.tabla_afectada LIKE ?";
    $params[] = '%' . $_GET['filter_tabla'] . '%';
}

if (!empty($_GET['filter_ip'])) {
    $filtros['IP'] = $_GET['filter_ip'];
    $where_conditions[] = "la.ip_address LIKE ?";
    $params[] = '%' . $_GET['filter_ip'] . '%';
}

if (!empty($_GET['filter_fecha'])) {
    $filtros['Fecha'] = $_GET['filter_fecha'];
    $where_conditions[] = "DATE(la.fecha_accion) = ?";
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

// Construir consulta de logs con filtros
$baseQuery = "SELECT la.id, la.usuario_id, la.accion, la.tabla_afectada, la.registro_id, 
                     la.ip_address, la.user_agent, la.fecha_accion,
                     COALESCE(CONCAT(pp.nombres, ' ', pp.apellido_paterno, ' ', COALESCE(pp.apellido_materno, '')), 'Sistema') as usuario_nombre,
                     COALESCE(u.email, 'sistema@colegio.edu.pe') as usuario_email,
                     COALESCE(r.nombre, 'Sin rol') as usuario_rol
              FROM logs_auditoria la
              LEFT JOIN usuarios u ON la.usuario_id = u.id
              LEFT JOIN perfiles_personas pp ON u.id = pp.usuario_id
              LEFT JOIN roles r ON u.rol_id = r.id";

if (!empty($where_conditions)) {
    $baseQuery .= " WHERE " . implode(" AND ", $where_conditions);
}

$baseQuery .= " ORDER BY la.fecha_accion DESC LIMIT 500";

$stmt = $pdo->prepare($baseQuery);
$stmt->execute($params);
$logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Obtener estadísticas
$totalQuery = "SELECT COUNT(*) as total FROM logs_auditoria la";
if (!empty($where_conditions)) {
    $totalQuery .= " LEFT JOIN usuarios u ON la.usuario_id = u.id LEFT JOIN perfiles_personas pp ON u.id = pp.usuario_id WHERE " . implode(" AND ", $where_conditions);
    $totalStmt = $pdo->prepare($totalQuery);
    $totalStmt->execute($params);
} else {
    $totalStmt = $pdo->prepare($totalQuery);
    $totalStmt->execute();
}
$totalRegistros = $totalStmt->fetch()['total'];

// Si no hay datos, mostrar mensaje
if (empty($logs)) {
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
    
    .logs-table {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 20px;
    }
    
    .logs-table th,
    .logs-table td {
        border: 0.5px solid #333;
        padding: 6px;
        font-size: 9px;
        text-align: left;
        vertical-align: top;
    }
    
    .logs-table th {
        background-color: #f2f2f2;
        font-weight: bold;
        text-align: center;
    }
    
    .accion-badge {
        padding: 2px 6px;
        border-radius: 10px;
        font-size: 8px;
        font-weight: bold;
        color: white;
    }
    
    .badge-consulta { background-color: #2563eb; }
    .badge-creacion { background-color: #059669; }
    .badge-edicion { background-color: #d97706; }
    .badge-eliminacion { background-color: #dc2626; }
    .badge-sistema { background-color: #7c3aed; }
    .badge-error { background-color: #ef4444; }
    
    .ip-address {
        font-family: "Courier New", monospace;
        background-color: #f8f9fa;
        padding: 1px 3px;
        border-radius: 3px;
        font-size: 8px;
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
    
    .col-id { width: 5%; }
    .col-usuario { width: 18%; }
    .col-accion { width: 15%; }
    .col-tabla { width: 12%; }
    .col-ip { width: 12%; }
    .col-fecha { width: 15%; }
    .col-user-agent { width: 23%; }
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
            <h3>REPORTE DE LOGS DE AUDITORÍA</h3>
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

$html .= '<div class="section-title">REGISTRO DE EVENTOS DE AUDITORÍA (' . count($logs) . ' de ' . $totalRegistros . ' registros)</div>';

// Tabla de logs
$html .= '<table class="logs-table">
    <thead>
        <tr>
            <th class="col-id">ID</th>
            <th class="col-usuario">Usuario</th>
            <th class="col-accion">Acción</th>
            <th class="col-tabla">Tabla</th>
            <th class="col-ip">IP Address</th>
            <th class="col-fecha">Fecha</th>
            <th class="col-user-agent">User Agent</th>
        </tr>
    </thead>
    <tbody>';

foreach ($logs as $log) {
    // Determinar clase de acción
    $accion = strtolower($log['accion']);
    $accionClass = 'badge-sistema';
    if (strpos($accion, 'consulta') !== false) $accionClass = 'badge-consulta';
    elseif (strpos($accion, 'crear') !== false) $accionClass = 'badge-creacion';
    elseif (strpos($accion, 'editar') !== false) $accionClass = 'badge-edicion';
    elseif (strpos($accion, 'eliminar') !== false) $accionClass = 'badge-eliminacion';
    elseif (strpos($accion, 'error') !== false) $accionClass = 'badge-error';

    // Truncar User Agent
    $userAgent = $log['user_agent'];
    if (strlen($userAgent) > 60) {
        $userAgent = substr($userAgent, 0, 57) . '...';
    }

    $html .= '<tr>
        <td class="col-id">' . htmlspecialchars($log['id']) . '</td>
        <td class="col-usuario">
            <div style="font-weight: bold; font-size: 9px;">' . htmlspecialchars($log['usuario_nombre']) . '</div>
            <div style="font-size: 8px; color: #666;">' . htmlspecialchars($log['usuario_email']) . '</div>
            <div style="font-size: 8px; color: #888;">' . htmlspecialchars($log['usuario_rol']) . '</div>
        </td>
        <td class="col-accion">
            <span class="accion-badge ' . $accionClass . '">' . htmlspecialchars($log['accion']) . '</span>
        </td>
        <td class="col-tabla">' . htmlspecialchars($log['tabla_afectada'] ?: 'N/A') . '</td>
        <td class="col-ip">
            <span class="ip-address">' . htmlspecialchars($log['ip_address'] ?: 'N/A') . '</span>
        </td>
        <td class="col-fecha">' . date('d/m/Y H:i:s', strtotime($log['fecha_accion'])) . '</td>
        <td class="col-user-agent" style="font-size: 8px;">' . htmlspecialchars($userAgent ?: 'N/A') . '</td>
    </tr>';
}

$html .= '</tbody></table>';

// Pie de página
$html .= '<div class="footer">
    <strong>Reporte generado el:</strong> ' . date('d/m/Y H:i:s') . '<br>
    <strong>Sistema:</strong> Gestión Educativa José Carlos Mariátegui
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
$filename = 'Logs_Auditoria_' . date('Y-m-d_H-i-s') . '.pdf';
header("Content-Type: application/pdf");
header("Content-Disposition: inline; filename=\"$filename\"");
echo $dompdf->output();
exit;
?>