<?php
include 'config/bd.php';

// Consultar datos de los logs de auditoría y estadísticas
try {
    // Consulta principal de logs de auditoría con JOINs para obtener información relacionada
    $query = "SELECT la.id, la.usuario_id, la.accion, la.tabla_afectada, la.registro_id, 
                     la.datos_anteriores, la.datos_nuevos, la.ip_address, la.user_agent, la.fecha_accion,
                     COALESCE(CONCAT(pp.nombres, ' ', pp.apellido_paterno, ' ', COALESCE(pp.apellido_materno, '')), 'Sistema') as usuario_nombre,
                     COALESCE(u.email, 'sistema@colegio.edu.pe') as usuario_email,
                     COALESCE(r.nombre, 'Sin rol') as usuario_rol
              FROM logs_auditoria la
              LEFT JOIN usuarios u ON la.usuario_id = u.id
              LEFT JOIN perfiles_personas pp ON u.id = pp.usuario_id
              LEFT JOIN roles r ON u.rol_id = r.id
              ORDER BY la.fecha_accion DESC
              LIMIT 1000";
    $stmt = $pdo->prepare($query);
    $stmt->execute();
    $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Obtener estadísticas generales
    $estadisticas = [];
    
    // Total de logs de auditoría
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM logs_auditoria");
    $stmt->execute();
    $estadisticas['total_logs'] = intval($stmt->fetch()['total']);
    
    // Logs de hoy
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM logs_auditoria WHERE DATE(fecha_accion) = CURDATE()");
    $stmt->execute();
    $estadisticas['logs_hoy'] = intval($stmt->fetch()['total']);
    
    // Logs de la semana
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM logs_auditoria WHERE fecha_accion >= DATE_SUB(NOW(), INTERVAL 7 DAY)");
    $stmt->execute();
    $estadisticas['logs_semana'] = intval($stmt->fetch()['total']);
    
    // Usuarios únicos registrados
    $stmt = $pdo->prepare("SELECT COUNT(DISTINCT usuario_id) as total FROM logs_auditoria WHERE usuario_id IS NOT NULL");
    $stmt->execute();
    $estadisticas['usuarios_unicos'] = intval($stmt->fetch()['total']);
    
    // Tablas más auditadas
    $stmt = $pdo->prepare("
        SELECT tabla_afectada, COUNT(*) as total 
        FROM logs_auditoria 
        WHERE tabla_afectada IS NOT NULL AND tabla_afectada != ''
        GROUP BY tabla_afectada 
        ORDER BY total DESC 
        LIMIT 5
    ");
    $stmt->execute();
    $tablas_mas_auditadas = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Acciones más frecuentes
    $stmt = $pdo->prepare("
        SELECT accion, COUNT(*) as total 
        FROM logs_auditoria 
        GROUP BY accion 
        ORDER BY total DESC 
        LIMIT 10
    ");
    $stmt->execute();
    $acciones_frecuentes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    $logs = [];
    $estadisticas = [
        'total_logs' => 0,
        'logs_hoy' => 0,
        'logs_semana' => 0,
        'usuarios_unicos' => 0
    ];
    $tablas_mas_auditadas = [];
    $acciones_frecuentes = [];
    $error_message = "Error al consultar los logs de auditoría: " . $e->getMessage();
} catch (Exception $e) {
    $logs = [];
    $estadisticas = [
        'total_logs' => 0,
        'logs_hoy' => 0,
        'logs_semana' => 0,
        'usuarios_unicos' => 0
    ];
    $tablas_mas_auditadas = [];
    $acciones_frecuentes = [];
    $error_message = "Error general: " . $e->getMessage();
}
?>
<!doctype html>
<html lang="en">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Colegio Mariategui - Logs de Auditoría</title>
  <link rel="shortcut icon" type="image/png" href="./assets/images/logos/logomariategui.png" />
  <link rel="stylesheet" href="./assets/css/styles.min.css" />

  <!-- DataTables CSS -->
  <link href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css" rel="stylesheet">
  <link href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.bootstrap5.min.css" rel="stylesheet">
  
  <!-- SweetAlert2 -->
  <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11.9.0/dist/sweetalert2.min.css" rel="stylesheet">
  
  <!-- Chart.js para gráficos -->
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  
  <style>
    /* Variables CSS para consistencia de colores */
    :root {
      --primary-color: #2563eb;
      --secondary-color: #64748b;
      --success-color: #059669;
      --warning-color: #d97706;
      --danger-color: #dc2626;
      --purple-color: #7c3aed;
      --indigo-color: #4f46e5;
      --gray-50: #f8fafc;
      --gray-100: #f1f5f9;
      --gray-200: #e2e8f0;
      --gray-300: #cbd5e1;
      --gray-400: #94a3b8;
      --gray-500: #64748b;
      --gray-600: #475569;
      --gray-700: #334155;
      --gray-800: #1e293b;
      --gray-900: #0f172a;
      --border-radius: 8px;
      --shadow-sm: 0 1px 2px 0 rgb(0 0 0 / 0.05);
      --shadow-md: 0 4px 6px -1px rgb(0 0 0 / 0.1), 0 2px 4px -2px rgb(0 0 0 / 0.1);
    }
    
    /* Reset de espacios del header */
    .app-header {
      margin-top: 0 !important;
      padding-top: 0 !important;
      top: 0 !important;
    }
    
    .body-wrapper {
      padding-top: 0 !important;
    }
    
    .app-header .navbar {
      padding-top: 0.5rem !important;
      padding-bottom: 0.5rem !important;
    }

    aside {
      margin-top: 0 !important;
      padding-top: 0 !important;
      top: 0 !important;
    }

    /* Contenedor principal responsivo */
    .main-container {
      background-color: var(--gray-50);
      min-height: 100vh;
      padding: 1rem 0.5rem;
    }

    /* Card principal responsivo */
    .card-modern {
      background: white;
      border: 1px solid var(--gray-200);
      border-radius: var(--border-radius);
      box-shadow: var(--shadow-sm);
      overflow: hidden;
      transition: all 0.3s ease;
    }

    /* Estilos responsivos para el card */
    @media (min-width: 1200px) {
      .card-modern {
        margin-top: -50px !important;
        max-width: 98vw !important;
        width: 130% !important;
        left: -185px !important;
        margin-left: auto !important;
        margin-right: auto !important;
      }
    }

    @media (max-width: 1199px) and (min-width: 992px) {
      .card-modern {
        margin-top: -30px !important;
        max-width: 95vw !important;
        width: 120% !important;
        left: -100px !important;
        margin-left: auto !important;
        margin-right: auto !important;
      }
    }

    @media (max-width: 991px) and (min-width: 768px) {
      .card-modern {
        margin-top: -20px !important;
        max-width: 92vw !important;
        width: 110% !important;
        left: -50px !important;
        margin-left: auto !important;
        margin-right: auto !important;
      }
    }

    @media (max-width: 767px) {
      .card-modern {
        margin-top: -10px !important;
        max-width: 100vw !important;
        width: 100% !important;
        left: 0 !important;
        margin-left: 0 !important;
        margin-right: 0 !important;
      }
    }

    .card-header-modern {
      color: white;
      padding: 1rem 1.5rem;
      border-bottom: none;
    }

    .card-header-modern .page-title {
      font-size: 1.5rem;
      font-weight: 600;
      margin: 0;
      display: flex;
      align-items: center;
      gap: 0.75rem;
    }

    .card-header-modern iconify-icon {
      font-size: 1.75rem;
      opacity: 0.9;
    }

    /* Tarjetas de información */
    .info-cards-container {
      padding: 1.5rem;
      background: var(--gray-50);
      border-bottom: 1px solid var(--gray-200);
    }

    .info-card {
      background: white;
      border: 1px solid var(--gray-200);
      border-radius: var(--border-radius);
      padding: 1.25rem;
      box-shadow: var(--shadow-sm);
      transition: all 0.2s ease;
      height: 100%;
    }

    .info-card:hover {
      transform: translateY(-2px);
      box-shadow: var(--shadow-md);
    }

    .info-card-icon {
      width: 48px;
      height: 48px;
      border-radius: 12px;
      display: flex;
      align-items: center;
      justify-content: center;
      margin-bottom: 1rem;
      font-size: 1.5rem;
    }

    .info-card-icon.primary {
      background: rgb(37 99 235 / 0.1);
      color: var(--primary-color);
    }

    .info-card-icon.success {
      background: rgb(5 150 105 / 0.1);
      color: var(--success-color);
    }

    .info-card-icon.warning {
      background: rgb(217 119 6 / 0.1);
      color: var(--warning-color);
    }

    .info-card-icon.purple {
      background: rgb(124 58 237 / 0.1);
      color: var(--purple-color);
    }

    .info-card-title {
      font-size: 0.875rem;
      font-weight: 500;
      color: var(--gray-600);
      margin-bottom: 0.5rem;
      text-transform: uppercase;
      letter-spacing: 0.025em;
    }

    .info-card-value {
      font-size: 1.25rem;
      font-weight: 600;
      color: var(--gray-900);
      margin-bottom: 0.25rem;
    }

    .info-card-description {
      font-size: 0.75rem;
      color: var(--gray-500);
      line-height: 1.4;
    }

    /* Sección de gráficos */
    .charts-container {
      padding: 1.5rem;
      background: white;
      border-bottom: 1px solid var(--gray-200);
    }

    .chart-card {
      background: white;
      border: 1px solid var(--gray-200);
      border-radius: var(--border-radius);
      padding: 1.5rem;
      box-shadow: var(--shadow-sm);
      height: 100%;
    }

    .chart-title {
      font-size: 1rem;
      font-weight: 600;
      color: var(--gray-800);
      margin-bottom: 1rem;
      display: flex;
      align-items: center;
      gap: 0.5rem;
    }

    .chart-container {
      position: relative;
      height: 300px;
      width: 100%;
    }

    /* Tabla mejorada */
    .table-container {
      background: white;
      border-radius: var(--border-radius);
      overflow-x: auto;
      overflow-y: visible;
      width: 100%;
    }
    
    .table {
      margin-bottom: 0;
      width: 100% !important;
    }

    .table thead th {
      background-color: var(--gray-50);
      border-bottom: 2px solid var(--gray-200);
      font-weight: 600;
      font-size: 0.875rem;
      color: var(--gray-700);
      padding: 0.75rem 0.5rem;
      text-transform: uppercase;
      letter-spacing: 0.025em;
      white-space: nowrap;
      vertical-align: middle;
    }

    .table tbody td {
      padding: 0.75rem 0.5rem;
      vertical-align: middle;
      border-bottom: 1px solid var(--gray-100);
      color: var(--gray-600);
      white-space: nowrap;
    }

    .table tbody tr {
      transition: background-color 0.15s ease;
    }

    .table tbody tr:hover {
      background-color: var(--gray-50);
    }

    .table tbody tr:last-child td {
      border-bottom: none;
    }

    /* Estilos para el footer de filtros */
    .table tfoot th {
      background-color: var(--gray-100);
      border-top: 2px solid var(--gray-200);
      padding: 0.5rem 0.25rem;
      vertical-align: middle;
    }

    .table tfoot input,
    .table tfoot select {
      width: 100%;
      padding: 0.375rem 0.5rem;
      border: 1px solid var(--gray-300);
      border-radius: 4px;
      font-size: 0.75rem;
      background-color: white;
      color: var(--gray-700);
      transition: border-color 0.2s ease;
    }

    .table tfoot input:focus,
    .table tfoot select:focus {
      outline: none;
      border-color: var(--primary-color);
      box-shadow: 0 0 0 2px rgb(37 99 235 / 0.1);
    }

    /* Mensaje para columnas sin filtro */
    .no-filter {
      text-align: center;
      color: var(--gray-400);
      font-size: 0.7rem;
      font-style: italic;
      padding: 0.5rem;
      background-color: var(--gray-50);
      border-radius: 3px;
      border: 1px dashed var(--gray-300);
    }

    /* Badges para acciones */
    .badge-accion {
      padding: 0.375rem 0.75rem;
      border-radius: 9999px;
      font-size: 0.75rem;
      font-weight: 500;
      text-transform: uppercase;
      letter-spacing: 0.025em;
    }

    .badge-consulta {
      background-color: rgb(37 99 235 / 0.1);
      color: var(--primary-color);
    }

    .badge-creacion {
      background-color: rgb(5 150 105 / 0.1);
      color: var(--success-color);
    }

    .badge-edicion {
      background-color: rgb(217 119 6 / 0.1);
      color: var(--warning-color);
    }

    .badge-eliminacion {
      background-color: rgb(239 68 68 / 0.1);
      color: var(--danger-color);
    }

    .badge-sistema {
      background-color: rgb(124 58 237 / 0.1);
      color: var(--purple-color);
    }

    .badge-error {
      background-color: rgb(239 68 68 / 0.2);
      color: var(--danger-color);
      font-weight: 600;
    }

    /* Badges para tablas */
    .badge-tabla {
      background-color: rgb(100 116 139 / 0.1);
      color: var(--secondary-color);
      padding: 0.25rem 0.5rem;
      border-radius: 12px;
      font-size: 0.75rem;
      font-weight: 500;
    }

    /* IP Address styling */
    .ip-address {
      font-family: 'Courier New', monospace;
      background-color: var(--gray-100);
      padding: 0.25rem 0.5rem;
      border-radius: 4px;
      font-size: 0.75rem;
      color: var(--gray-700);
    }

    /* User agent truncado */
    .user-agent {
      max-width: 200px;
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
      font-size: 0.75rem;
      color: var(--gray-500);
      cursor: help;
    }

    /* Botones de exportación */
    .export-buttons {
      padding: 1rem 1.5rem;
      background: var(--gray-50);
      border-bottom: 1px solid var(--gray-200);
      display: flex;
      gap: 1rem;
      flex-wrap: wrap;
      align-items: center;
    }

    .btn-outline-export {
      color: var(--success-color);
      border: 1.5px solid var(--success-color);
      background: transparent;
      border-radius: 6px;
      padding: 0.5rem 1rem;
      font-size: 0.875rem;
      font-weight: 500;
      transition: all 0.2s ease;
      display: inline-flex;
      align-items: center;
      gap: 0.5rem;
      text-decoration: none;
    }
    
    .btn-outline-export:hover {
      background-color: var(--success-color);
      border-color: var(--success-color);
      color: white;
      transform: translateY(-1px);
      box-shadow: var(--shadow-md);
    }

    /* DataTables personalización */
    .dataTables_wrapper {
      padding: 1rem;
      width: 100% !important;
    }

    /* Hacer la tabla más ancha */
    #logsTable {
      width: 100% !important;
      min-width: 1200px;
    }

    /* Ajustar anchos de columnas específicas */
    #logsTable th:nth-child(1) { width: 60px; }   /* ID */
    #logsTable th:nth-child(2) { width: 180px; }  /* Usuario */
    #logsTable th:nth-child(3) { width: 200px; }  /* Acción */
    #logsTable th:nth-child(4) { width: 120px; }  /* Tabla */
    #logsTable th:nth-child(5) { width: 80px; }   /* Registro ID */
    #logsTable th:nth-child(6) { width: 120px; }  /* IP */
    #logsTable th:nth-child(7) { width: 250px; }  /* User Agent */
    #logsTable th:nth-child(8) { width: 140px; }  /* Fecha */

    /* Estado vacío */
    .empty-state {
      text-align: center;
      padding: 3rem 2rem;
      color: var(--gray-500);
    }

    .empty-state iconify-icon {
      font-size: 4rem;
      color: var(--gray-300);
      margin-bottom: 1rem;
    }

    .empty-state h3 {
      font-size: 1.25rem;
      font-weight: 600;
      color: var(--gray-700);
      margin-bottom: 0.5rem;
    }

    .empty-state p {
      font-size: 0.875rem;
      color: var(--gray-500);
      margin: 0;
    }
  </style>
</head>

<body>
  <!--  Body Wrapper -->
  <div class="page-wrapper" id="main-wrapper" data-layout="vertical" data-navbarbg="skin6" data-sidebartype="full"
    data-sidebar-position="fixed" data-header-position="fixed">
    <!--  Main wrapper -->
    <div class="body-wrapper">
      <?php include 'layouts/sidebar.php'; ?>
      <?php include 'layouts/header.php'; ?>
      
      <div class="body-wrapper-inner">
        <div class="container-fluid main-container">
          <div class="card card-modern">
            
            <!-- Header del card -->
            <div class="card-header-modern">
              <h5 class="page-title">
                <iconify-icon icon="mdi:shield-search"></iconify-icon>
                Logs de Auditoría del Sistema
              </h5>
            </div>

            <!-- Tarjetas de información -->
            <div class="info-cards-container">
              <div class="row g-3">
                <div class="col-xl-3 col-lg-6 col-md-6">
                  <div class="info-card">
                    <div class="info-card-icon primary">
                      <iconify-icon icon="mdi:file-document-multiple"></iconify-icon>
                    </div>
                    <div class="info-card-title">Total de Registros</div>
                    <div class="info-card-value">
                      <?php echo number_format(intval($estadisticas['total_logs'] ?? 0)); ?>
                    </div>
                    <div class="info-card-description">Eventos auditados en el sistema</div>
                  </div>
                </div>

                <div class="col-xl-3 col-lg-6 col-md-6">
                  <div class="info-card">
                    <div class="info-card-icon success">
                      <iconify-icon icon="mdi:calendar-today"></iconify-icon>
                    </div>
                    <div class="info-card-title">Registros de Hoy</div>
                    <div class="info-card-value">
                      <?php echo number_format(intval($estadisticas['logs_hoy'] ?? 0)); ?>
                    </div>
                    <div class="info-card-description">Actividad del día actual</div>
                  </div>
                </div>

                <div class="col-xl-3 col-lg-6 col-md-6">
                  <div class="info-card">
                    <div class="info-card-icon warning">
                      <iconify-icon icon="mdi:calendar-week"></iconify-icon>
                    </div>
                    <div class="info-card-title">Esta Semana</div>
                    <div class="info-card-value">
                      <?php echo number_format(intval($estadisticas['logs_semana'] ?? 0)); ?>
                    </div>
                    <div class="info-card-description">Actividad de los últimos 7 días</div>
                  </div>
                </div>

                <div class="col-xl-3 col-lg-6 col-md-6">
                  <div class="info-card">
                    <div class="info-card-icon purple">
                      <iconify-icon icon="mdi:account-multiple"></iconify-icon>
                    </div>
                    <div class="info-card-title">Usuarios Únicos</div>
                    <div class="info-card-value">
                      <?php echo number_format(intval($estadisticas['usuarios_unicos'] ?? 0)); ?>
                    </div>
                    <div class="info-card-description">Usuarios con actividad registrada</div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Sección de gráficos -->
            <div class="charts-container">
              <div class="row g-4">
                <div class="col-lg-6">
                  <div class="chart-card">
                    <div class="chart-title">
                      <iconify-icon icon="mdi:chart-donut"></iconify-icon>
                      Acciones Más Frecuentes
                    </div>
                    <div class="chart-container">
                      <canvas id="accionesChart"></canvas>
                    </div>
                  </div>
                </div>
                <div class="col-lg-6">
                  <div class="chart-card">
                    <div class="chart-title">
                      <iconify-icon icon="mdi:chart-bar"></iconify-icon>
                      Tablas Más Auditadas
                    </div>
                    <div class="chart-container">
                      <canvas id="tablasChart"></canvas>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Botón de exportación -->
            <div class="export-buttons">
              <span class="fw-semibold text-dark">Exportar datos:</span>
              <a href="#" class="btn btn-outline-danger btn-sm" onclick="generatePDF()">
                <iconify-icon icon="mdi:file-pdf-box"></iconify-icon>
                Generar Reporte PDF
              </a>
            </div>
            
            <!-- Contenido del card -->
            <div class="card-body p-0">
              <?php if (!empty($logs)): ?>
                <!-- Tabla de Logs -->
                <div class="table-container">
                  <table id="logsTable" class="table table-hover w-100">
                    <thead>
                      <tr>
                        <th>ID</th>
                        <th>Usuario</th>
                        <th>Acción</th>
                        <th>Tabla</th>
                        <th>Registro</th>
                        <th>IP Address</th>
                        <th>User Agent</th>
                        <th>Fecha</th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php foreach ($logs as $log): ?>
                        <tr>
                          <td>
                            <span class="fw-semibold text-dark"><?php echo htmlspecialchars($log['id']); ?></span>
                          </td>
                          <td>
                            <?php if (!empty($log['usuario_nombre']) && $log['usuario_nombre'] !== 'Sistema'): ?>
                              <div class="fw-semibold text-dark"><?php echo htmlspecialchars($log['usuario_nombre']); ?></div>
                              <small class="text-muted"><?php echo htmlspecialchars($log['usuario_email']); ?></small>
                              <br><span class="badge badge-tabla"><?php echo htmlspecialchars($log['usuario_rol']); ?></span>
                            <?php else: ?>
                              <div class="text-muted">
                                <iconify-icon icon="mdi:robot"></iconify-icon>
                                Sistema
                              </div>
                            <?php endif; ?>
                          </td>
                          <td>
                            <?php
                            $accion = $log['accion'] ?? '';
                            $accion_class = '';
                            
                            if (strpos(strtolower($accion), 'consulta') !== false || strpos(strtolower($accion), 'get') !== false) {
                              $accion_class = 'badge-consulta';
                            } elseif (strpos(strtolower($accion), 'crear') !== false || strpos(strtolower($accion), 'create') !== false) {
                              $accion_class = 'badge-creacion';
                            } elseif (strpos(strtolower($accion), 'editar') !== false || strpos(strtolower($accion), 'update') !== false) {
                              $accion_class = 'badge-edicion';
                            } elseif (strpos(strtolower($accion), 'eliminar') !== false || strpos(strtolower($accion), 'delete') !== false) {
                              $accion_class = 'badge-eliminacion';
                            } elseif (strpos(strtolower($accion), 'error') !== false || strpos(strtolower($accion), 'fail') !== false) {
                              $accion_class = 'badge-error';
                            } else {
                              $accion_class = 'badge-sistema';
                            }
                            ?>
                            <span class="badge badge-accion <?php echo $accion_class; ?>">
                              <?php echo htmlspecialchars($accion); ?>
                            </span>
                          </td>
                          <td>
                            <?php if (!empty($log['tabla_afectada'])): ?>
                              <span class="badge badge-tabla">
                                <?php echo htmlspecialchars($log['tabla_afectada']); ?>
                              </span>
                            <?php else: ?>
                              <span class="text-muted">N/A</span>
                            <?php endif; ?>
                          </td>
                          <td>
                            <?php if (!empty($log['registro_id'])): ?>
                              <span class="text-dark fw-semibold"><?php echo htmlspecialchars($log['registro_id']); ?></span>
                            <?php else: ?>
                              <span class="text-muted">—</span>
                            <?php endif; ?>
                          </td>
                          <td>
                            <?php if (!empty($log['ip_address']) && $log['ip_address'] !== 'unknown'): ?>
                              <span class="ip-address"><?php echo htmlspecialchars($log['ip_address']); ?></span>
                            <?php else: ?>
                              <span class="text-muted">No disponible</span>
                            <?php endif; ?>
                          </td>
                          <td>
                            <?php if (!empty($log['user_agent']) && $log['user_agent'] !== 'unknown'): ?>
                              <span class="user-agent" title="<?php echo htmlspecialchars($log['user_agent']); ?>">
                                <?php echo htmlspecialchars($log['user_agent']); ?>
                              </span>
                            <?php else: ?>
                              <span class="text-muted">No disponible</span>
                            <?php endif; ?>
                          </td>
                          <td>
                            <span class="text-muted small">
                              <?php 
                                if (!empty($log['fecha_accion'])) {
                                  try {
                                    $fecha = new DateTime($log['fecha_accion']);
                                    echo $fecha->format('d/m/Y H:i:s');
                                  } catch (Exception $e) {
                                    echo '--/--/-- --:--:--';
                                  }
                                } else {
                                  echo '--/--/-- --:--:--';
                                }
                              ?>
                            </span>
                          </td>
                        </tr>
                      <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                      <tr>
                        <th><input type="text" placeholder="Filtrar ID" id="filter_id" /></th>
                        <th><input type="text" placeholder="Filtrar Usuario" id="filter_usuario" /></th>
                        <th><input type="text" placeholder="Filtrar Acción" id="filter_accion" /></th>
                        <th><input type="text" placeholder="Filtrar Tabla" id="filter_tabla" /></th>
                        <th><input type="text" placeholder="Filtrar Registro" id="filter_registro" /></th>
                        <th><input type="text" placeholder="Filtrar IP" id="filter_ip" /></th>
                        <th><input type="text" placeholder="Filtrar User Agent" id="filter_user_agent" /></th>
                        <th><input type="text" placeholder="Filtrar Fecha" id="filter_fecha" /></th>
                      </tr>
                    </tfoot>
                  </table>
                </div>
              <?php else: ?>
                <!-- Estado vacío -->
                <div class="empty-state">
                  <iconify-icon icon="mdi:shield-search-outline"></iconify-icon>
                  <h3>No hay logs de auditoría</h3>
                  <p>No se encontraron registros de auditoría en el sistema</p>
                </div>
              <?php endif; ?>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Scripts -->
  <script src="./assets/libs/jquery/dist/jquery.min.js"></script>
  <script src="./assets/libs/bootstrap/dist/js/bootstrap.bundle.min.js"></script>
  <script src="./assets/js/sidebarmenu.js"></script>
  <script src="./assets/js/app.min.js"></script>
  <script src="./assets/libs/simplebar/dist/simplebar.js"></script>
  
  <!-- DataTables JS -->
  <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
  <script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
  <script src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js"></script>
  <script src="https://cdn.datatables.net/responsive/2.5.0/js/responsive.bootstrap5.min.js"></script>
  
  <!-- SweetAlert2 -->
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.9.0/dist/sweetalert2.all.min.js"></script>
  
  <!-- Iconify -->
  <script src="https://cdn.jsdelivr.net/npm/iconify-icon@1.0.8/dist/iconify-icon.min.js"></script>

  <!-- Incluir notifications.js -->
  <script src="assets/js/utils/notifications.js"></script>

  <script>
    $(document).ready(function() {
      // Inicializar DataTable con filtros por columna
      const table = $('#logsTable').DataTable({
        "language": {
          "url": "https://cdn.datatables.net/plug-ins/1.13.7/i18n/es-ES.json"
        },
        "responsive": false,
        "scrollX": true,
        "order": [[0, "desc"]],
        "pageLength": 25,
        "lengthMenu": [[10, 25, 50, 100, -1], [10, 25, 50, 100, "Todos"]],
        "dom": '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>rtip',
        "columnDefs": [
          { "className": "text-center", "targets": [0, 4] }
        ],
        "autoWidth": false,
        "initComplete": function () {
          var api = this.api();

          // Configurar filtros para cada columna
          api.columns().every(function (colIdx) {
            var column = this;
            var footerCell = $(column.footer());
            var input = footerCell.find('input');

            // Filtro para inputs de texto
            if (input.length > 0) {
              input.off('keyup change clear').on('keyup change clear', function () {
                var val = $(this).val();
                
                if (val) {
                  column.search(val, false, false).draw();
                } else {
                  column.search('', false, false).draw();
                }
              });
            }
          });

          // Mostrar mensaje de error si existe
          <?php if (isset($error_message)): ?>
            showNotification('error', 'Error de Base de Datos', '<?php echo addslashes($error_message); ?>');
          <?php endif; ?>
        }
      });

      // Inicializar gráficos
      initializeCharts();
    });

    // Función para generar PDF con filtros actuales
    function generatePDF() {
      // Obtener filtros activos del DataTable
      const filtros = {};
      
      $('#logsTable tfoot input').each(function() {
        const value = $(this).val().trim();
        const id = $(this).attr('id');
        if (value !== '') {
          filtros[id] = value;
        }
      });

      // Construir URL con parámetros
      let url = 'reports/generar_pdf_logs.php?';
      const params = new URLSearchParams(filtros);
      url += params.toString();

      // Abrir PDF en nueva pestaña
      window.open(url, '_blank');
    }

    // Función para inicializar gráficos
    function initializeCharts() {
      // Datos de acciones más frecuentes
      const accionesData = <?php echo json_encode($acciones_frecuentes); ?>;
      const tablasData = <?php echo json_encode($tablas_mas_auditadas); ?>;

      // Gráfico de acciones
      if (accionesData.length > 0) {
        const ctxAcciones = document.getElementById('accionesChart').getContext('2d');
        new Chart(ctxAcciones, {
          type: 'doughnut',
          data: {
            labels: accionesData.map(item => item.accion),
            datasets: [{
              data: accionesData.map(item => item.total),
              backgroundColor: [
                '#3b82f6',
                '#10b981',
                '#f59e0b',
                '#ef4444',
                '#8b5cf6',
                '#06b6d4',
                '#84cc16',
                '#f97316',
                '#ec4899',
                '#6366f1'
              ],
              borderWidth: 2,
              borderColor: '#ffffff'
            }]
          },
          options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
              legend: {
                position: 'bottom',
                labels: {
                  padding: 20,
                  usePointStyle: true
                }
              }
            }
          }
        });
      }

      // Gráfico de tablas
      if (tablasData.length > 0) {
        const ctxTablas = document.getElementById('tablasChart').getContext('2d');
        new Chart(ctxTablas, {
          type: 'bar',
          data: {
            labels: tablasData.map(item => item.tabla_afectada),
            datasets: [{
              label: 'Eventos de Auditoría',
              data: tablasData.map(item => item.total),
              backgroundColor: '#3b82f6',
              borderColor: '#1e40af',
              borderWidth: 1,
              borderRadius: 4,
              borderSkipped: false
            }]
          },
          options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
              legend: {
                display: false
              }
            },
            scales: {
              y: {
                beginAtZero: true,
                ticks: {
                  stepSize: 1
                }
              }
            }
          }
        });
      }
    }

    // Mejorar la experiencia de usuario con los filtros
    $('#logsTable tfoot input').on('focus', function() {
      $(this).css('border-color', '#2563eb');
    }).on('blur', function() {
      $(this).css('border-color', '#cbd5e1');
    });
  </script>
</body>
</html>