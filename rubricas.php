<?php
include 'config/bd.php';

// Consultar datos de las rúbricas y estadísticas
try {
    // Consulta principal de rúbricas con JOINs para obtener información relacionada
    $query = "SELECT r.id, r.nombre, r.descripcion, r.competencia_id, r.curso_id, 
                     r.tipo_evaluacion, r.estado, r.fecha_creacion,
                     COALESCE(c.nombre, 'Sin competencia') as competencia_nombre,
                     COALESCE(c.codigo, '') as competencia_codigo,
                     COALESCE(cur.nombre, 'Sin curso') as curso_nombre,
                     (SELECT COUNT(*) FROM criterios_evaluacion ce WHERE ce.competencia_id = r.competencia_id) as total_criterios
              FROM rubricas r
              LEFT JOIN competencias c ON r.competencia_id = c.id
              LEFT JOIN cursos cur ON r.curso_id = cur.id
              WHERE r.estado = 1
              ORDER BY r.fecha_creacion DESC";
    $stmt = $pdo->prepare($query);
    $stmt->execute();
    $rubricas = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Obtener estadísticas generales
    $estadisticas = [];
    
    // Total de rúbricas
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM rubricas");
    $stmt->execute();
    $estadisticas['total_rubricas'] = intval($stmt->fetch()['total']);
    
    // Rúbricas activas (estado = 1)
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM rubricas WHERE estado = 1");
    $stmt->execute();
    $estadisticas['rubricas_activas'] = intval($stmt->fetch()['total']);
    
    // Total de criterios de evaluación
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM criterios_evaluacion");
    $stmt->execute();
    $estadisticas['total_criterios'] = intval($stmt->fetch()['total']);
    
    // Total de escalas de calificación configuradas
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM escalas_calificacion");
    $stmt->execute();
    $estadisticas['total_escalas'] = intval($stmt->fetch()['total']);
    
    // Promedio de criterios por competencia activa
    $stmt = $pdo->prepare("
        SELECT COUNT(DISTINCT r.competencia_id) as competencias_activas 
        FROM rubricas r 
        WHERE r.estado = 1 AND r.competencia_id IS NOT NULL
    ");
    $stmt->execute();
    $competencias_activas = intval($stmt->fetch()['competencias_activas']);
    
    if ($competencias_activas > 0) {
        $estadisticas['promedio_criterios'] = round($estadisticas['total_criterios'] / $competencias_activas, 1);
    } else {
        $estadisticas['promedio_criterios'] = 0;
    }
    
    // Obtener tipos de evaluación para estadísticas
    $stmt = $pdo->prepare("
        SELECT tipo_evaluacion, COUNT(*) as total 
        FROM rubricas 
        WHERE tipo_evaluacion IS NOT NULL AND tipo_evaluacion != ''
        GROUP BY tipo_evaluacion
    ");
    $stmt->execute();
    $tipos_evaluacion = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    $rubricas = [];
    $estadisticas = [
        'total_rubricas' => 0,
        'rubricas_activas' => 0,
        'total_criterios' => 0,
        'total_escalas' => 0,
        'promedio_criterios' => 0
    ];
    $tipos_evaluacion = [];
    $error_message = "Error al consultar las rúbricas: " . $e->getMessage();
} catch (Exception $e) {
    $rubricas = [];
    $estadisticas = [
        'total_rubricas' => 0,
        'rubricas_activas' => 0,
        'total_criterios' => 0,
        'total_escalas' => 0,
        'promedio_criterios' => 0
    ];
    $tipos_evaluacion = [];
    $error_message = "Error general: " . $e->getMessage();
}
?>
<!doctype html>
<html lang="en">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Colegio Mariategui - Configuración de Rúbricas</title>
  <link rel="shortcut icon" type="image/png" href="./assets/images/logos/logomariategui.png" />
  <link rel="stylesheet" href="./assets/css/styles.min.css" />

  <!-- DataTables CSS -->
  <link href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css" rel="stylesheet">
  <link href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.bootstrap5.min.css" rel="stylesheet">
  
  <!-- SweetAlert2 -->
  <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11.9.0/dist/sweetalert2.min.css" rel="stylesheet">
  
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

    .info-card-icon.indigo {
      background: rgb(79 70 229 / 0.1);
      color: var(--indigo-color);
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

    .table tfoot select {
      appearance: none;
      -webkit-appearance: none;
      -moz-appearance: none;
      background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3e%3cpath stroke='%236b7280' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='m6 8 4 4 4-4'/%3e%3c/svg%3e");
      background-position: right 0.5rem center;
      background-repeat: no-repeat;
      background-size: 1rem 1rem;
      padding-right: 2rem;
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

    /* Badges para estados */
    .badge-estado {
      padding: 0.375rem 0.75rem;
      border-radius: 9999px;
      font-size: 0.75rem;
      font-weight: 500;
      text-transform: uppercase;
      letter-spacing: 0.025em;
    }

    .badge-activa {
      background-color: rgb(34 197 94 / 0.1);
      color: var(--success-color);
    }

    .badge-primary {
      background-color: rgb(37 99 235 / 0.1);
      color: var(--primary-color);
    }
    
    .badge-success {
      background-color: rgb(37 99 235 / 0.1);
      color: var(--success-color);
    }

    .badge-secondary {
      background-color: rgb(100 116 139 / 0.1);
      color: var(--secondary-color);
    }

    .badge-warning {
      background-color: rgb(217 119 6 / 0.1);
      color: var(--warning-color);
    }

    .badge-inactiva {
      background-color: rgb(239 68 68 / 0.1);
      color: var(--danger-color);
    }

    .badge-competencia {
      background-color: rgb(124 58 237 / 0.1);
      color: var(--purple-color);
      font-weight: 500;
    }

    /* Contadores de criterios y escalas */
    .contador-badge {
      background-color: var(--primary-color);
      color: white;
      padding: 0.25rem 0.5rem;
      border-radius: 12px;
      font-size: 0.75rem;
      font-weight: 600;
      min-width: 20px;
      text-align: center;
      display: inline-block;
    }

    .contador-criterios {
      background-color: var(--warning-color);
    }

    .contador-escalas {
      background-color: var(--success-color);
    }

    /* Botones outline mejorados */
    .btn-outline-edit {
      color: var(--secondary-color);
      border: 1.5px solid var(--secondary-color);
      background: transparent;
      border-radius: 6px;
      padding: 0.5rem 1rem;
      font-size: 0.875rem;
      font-weight: 500;
      transition: all 0.2s ease;
      display: inline-flex;
      align-items: center;
      gap: 0.5rem;
    }
    
    .btn-outline-edit:hover {
      background-color: var(--secondary-color);
      border-color: var(--secondary-color);
      color: white;
      transform: translateY(-1px);
      box-shadow: var(--shadow-md);
    }

    .btn-outline-create {
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
    }
    
    .btn-outline-create:hover {
      background-color: var(--success-color);
      border-color: var(--success-color);
      color: white;
      transform: translateY(-1px);
      box-shadow: var(--shadow-md);
    }

    .btn-outline-view {
      color: var(--primary-color);
      border: 1.5px solid var(--primary-color);
      background: transparent;
      border-radius: 6px;
      padding: 0.5rem 1rem;
      font-size: 0.875rem;
      font-weight: 500;
      transition: all 0.2s ease;
      display: inline-flex;
      align-items: center;
      gap: 0.5rem;
    }
    
    .btn-outline-view:hover {
      background-color: var(--primary-color);
      border-color: var(--primary-color);
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
    #rubricasTable {
      width: 100% !important;
      min-width: 1250px;
    }

    /* Ajustar anchos de columnas específicas */
    #rubricasTable th:nth-child(1) { width: 60px; }   /* ID */
    #rubricasTable th:nth-child(2) { width: 250px; }  /* Nombre */
    #rubricasTable th:nth-child(3) { width: 180px; }  /* Competencia */
    #rubricasTable th:nth-child(4) { width: 150px; }  /* Curso */
    #rubricasTable th:nth-child(5) { width: 130px; }  /* Tipo Evaluación */
    #rubricasTable th:nth-child(6) { width: 100px; }  /* Criterios */
    #rubricasTable th:nth-child(7) { width: 100px; }  /* Estado */
    #rubricasTable th:nth-child(8) { width: 120px; }  /* Fecha */
    #rubricasTable th:nth-child(9) { width: 160px; }  /* Acciones */

    /* Controles superiores en la misma línea */
    .dataTables_wrapper .row:first-child {
      display: flex !important;
      align-items: center;
      justify-content: space-between;
      margin-bottom: 1rem;
    }

    .dataTables_wrapper .dataTables_length {
      margin-bottom: 0;
      flex: 0 0 auto;
    }

    .dataTables_wrapper .dataTables_filter {
      margin-bottom: 0;
      flex: 0 0 auto;
    }

    .dataTables_wrapper .dataTables_length select {
      border: 1px solid var(--gray-300);
      border-radius: 6px;
      padding: 0.5rem 0.75rem;
      padding-right: 0.75rem;
      background-color: white;
      color: var(--gray-700);
      font-size: 0.875rem;
      margin-left: 0.5rem;
      appearance: none;
      -webkit-appearance: none;
      -moz-appearance: none;
      background-image: none;
    }

    .dataTables_wrapper .dataTables_filter input {
      border: 1px solid var(--gray-300);
      border-radius: 6px;
      padding: 0.5rem 0.75rem;
      margin-left: 0.5rem;
      background-color: white;
      color: var(--gray-700);
      font-size: 0.875rem;
      transition: border-color 0.2s ease;
    }

    .dataTables_wrapper .dataTables_filter input:focus {
      outline: none;
      border-color: var(--primary-color);
      box-shadow: 0 0 0 3px rgb(37 99 235 / 0.1);
    }

    /* Controles inferiores en la misma línea */
    .dataTables_wrapper .row:last-child {
      display: flex !important;
      align-items: center;
      justify-content: space-between;
      margin-top: 1rem;
    }

    .dataTables_wrapper .dataTables_info {
      color: var(--gray-600);
      font-size: 0.875rem;
      margin-bottom: 0;
      flex: 0 0 auto;
    }

    .dataTables_wrapper .dataTables_paginate {
      margin-bottom: 0;
      flex: 0 0 auto;
    }

    /* Paginador más limpio sin recuadros */
    .dataTables_wrapper .dataTables_paginate .paginate_button {
      padding: 0.5rem 0.75rem;
      margin: 0 0.125rem;
      background: transparent;
      color: var(--gray-600);
      border: none;
      border-radius: 4px;
      font-size: 0.875rem;
      font-weight: 500;
      transition: all 0.15s ease;
      text-decoration: none;
    }

    .dataTables_wrapper .dataTables_paginate .paginate_button:hover {
      background: var(--gray-100);
      color: var(--gray-800);
    }

    .dataTables_wrapper .dataTables_paginate .paginate_button.current {
      background: var(--primary-color);
      color: white;
      font-weight: 600;
    }

    .dataTables_wrapper .dataTables_paginate .paginate_button.disabled {
      color: var(--gray-300);
      background: transparent;
    }

    .dataTables_wrapper .dataTables_paginate .paginate_button.disabled:hover {
      background: transparent;
      color: var(--gray-300);
    }

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

    /* Texto truncado mejorado */
    .text-truncate-custom {
      max-width: 200px;
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
    }

    /* Columna de descripción expandible */
    .descripcion-cell {
      max-width: 250px;
      word-wrap: break-word;
      white-space: normal !important;
      line-height: 1.4;
    }

    /* Botón flotante para crear */
    .btn-create-floating {
      position: fixed;
      bottom: 2rem;
      right: 2rem;
      z-index: 1000;
      background: var(--success-color);
      color: white;
      border: none;
      border-radius: 50%;
      width: 60px;
      height: 60px;
      font-size: 1.5rem;
      box-shadow: 0 4px 12px rgb(0 0 0 / 0.15);
      transition: all 0.3s ease;
      display: flex;
      align-items: center;
      justify-content: center;
    }

    .btn-create-floating:hover {
      background: #047857;
      transform: scale(1.1);
      box-shadow: 0 6px 20px rgb(0 0 0 / 0.25);
    }

    /* Responsive mejorado */
    @media (max-width: 768px) {
      .card-header-modern {
        padding: 1rem 1.5rem;
      }

      .card-header-modern .page-title {
        font-size: 1.25rem;
      }

      .dataTables_wrapper {
        padding: 1rem;
      }

      .table thead th,
      .table tbody td {
        padding: 0.75rem 0.5rem;
        font-size: 0.875rem;
      }

      .table tfoot th {
        padding: 0.375rem 0.25rem;
      }

      .table tfoot input,
      .table tfoot select {
        font-size: 0.7rem;
        padding: 0.25rem 0.375rem;
      }

      .info-cards-container {
        padding: 1rem;
      }

      .info-card {
        padding: 1rem;
        margin-bottom: 1rem;
      }

      .btn-create-floating {
        bottom: 1rem;
        right: 1rem;
        width: 50px;
        height: 50px;
        font-size: 1.25rem;
      }
    }

    /* Remover animaciones innecesarias */
    *, *::before, *::after {
      animation-delay: 0s !important;
      animation-duration: 0s !important;
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
                <iconify-icon icon="mdi:clipboard-list"></iconify-icon>
                Configuración de Rúbricas
              </h5>
            </div>

            <!-- Tarjetas de información -->
            <div class="info-cards-container">
              <div class="row g-3">
                <div class="col-xl-3 col-lg-6 col-md-6">
                  <div class="info-card">
                    <div class="info-card-icon primary">
                      <iconify-icon icon="mdi:clipboard-check-multiple"></iconify-icon>
                    </div>
                    <div class="info-card-title">Total de Rúbricas</div>
                    <div class="info-card-value">
                      <?php echo intval($estadisticas['total_rubricas'] ?? 0); ?>
                    </div>
                    <div class="info-card-description">Plantillas de evaluación disponibles</div>
                  </div>
                </div>

                <div class="col-xl-3 col-lg-6 col-md-6">
                  <div class="info-card">
                    <div class="info-card-icon success">
                      <iconify-icon icon="mdi:checkbox-marked-circle"></iconify-icon>
                    </div>
                    <div class="info-card-title">Rúbricas Activas</div>
                    <div class="info-card-value">
                      <?php echo intval($estadisticas['rubricas_activas'] ?? 0); ?>
                    </div>
                    <div class="info-card-description">En uso actualmente</div>
                  </div>
                </div>

                <div class="col-xl-3 col-lg-6 col-md-6">
                  <div class="info-card">
                    <div class="info-card-icon warning">
                      <iconify-icon icon="mdi:format-list-checks"></iconify-icon>
                    </div>
                    <div class="info-card-title">Criterios de Evaluación</div>
                    <div class="info-card-value">
                      <?php echo intval($estadisticas['total_criterios'] ?? 0); ?>
                    </div>
                    <div class="info-card-description">
                      Promedio: <?php echo floatval($estadisticas['promedio_criterios'] ?? 0); ?> por competencia
                    </div>
                  </div>
                </div>

                <div class="col-xl-3 col-lg-6 col-md-6">
                  <div class="info-card">
                    <div class="info-card-icon purple">
                      <iconify-icon icon="mdi:star-four-points"></iconify-icon>
                    </div>
                    <div class="info-card-title">Escalas de Calificación</div>
                    <div class="info-card-value">
                      <?php echo intval($estadisticas['total_escalas'] ?? 0); ?>
                    </div>
                    <div class="info-card-description">Configuraciones por año académico</div>
                  </div>
                </div>
              </div>
            </div>
            
            <!-- Contenido del card -->
            <div class="card-body p-0">
              <?php if (!empty($rubricas)): ?>
                <!-- Tabla de Rúbricas -->
                <div class="table-container">
                  <table id="rubricasTable" class="table table-hover w-100">
                    <thead>
                      <tr>
                        <th>ID</th>
                        <th>Nombre</th>
                        <th>Competencia</th>
                        <th>Curso</th>
                        <th>Tipo Evaluación</th>
                        <th>Criterios</th>
                        <th>Estado</th>
                        <th>Fecha</th>
                        <th>Acciones</th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php foreach ($rubricas as $rubrica): ?>
                        <tr>
                          <td>
                            <span class="fw-semibold text-dark"><?php echo htmlspecialchars($rubrica['id']); ?></span>
                          </td>
                          <td>
                            <div class="fw-semibold text-dark"><?php echo htmlspecialchars($rubrica['nombre'] ?? 'Sin nombre'); ?></div>
                            <?php if (!empty($rubrica['descripcion'])): ?>
                              <small class="text-muted"><?php echo htmlspecialchars(substr($rubrica['descripcion'], 0, 50)) . '...'; ?></small>
                            <?php endif; ?>
                          </td>
                          <td>
                            <?php if (!empty($rubrica['competencia_nombre'])): ?>
                              <span class="badge badge-competencia">
                                <?php echo htmlspecialchars($rubrica['competencia_nombre']); ?>
                              </span>
                              <?php if (!empty($rubrica['competencia_codigo'])): ?>
                                <br><small class="text-muted"><?php echo htmlspecialchars($rubrica['competencia_codigo']); ?></small>
                              <?php endif; ?>
                            <?php else: ?>
                              <span class="text-muted">Sin competencia</span>
                            <?php endif; ?>
                          </td>
                          <td>
                            <?php if (!empty($rubrica['curso_nombre'])): ?>
                              <span class="text-dark">
                                <?php echo htmlspecialchars($rubrica['curso_nombre']); ?>
                              </span>
                            <?php else: ?>
                              <span class="text-muted">Sin curso</span>
                            <?php endif; ?>
                          </td>
                          <td>
                            <?php
                            $tipo = $rubrica['tipo_evaluacion'] ?? '';
                            $tipo_class = '';
                            $tipo_display = '';
                            
                            switch ($tipo) {
                              case 'diagnostica':
                                $tipo_class = 'badge-warning';
                                $tipo_display = 'Diagnóstica';
                                break;
                              case 'formativa':
                                $tipo_class = 'badge-primary';
                                $tipo_display = 'Formativa';
                                break;
                              case 'sumativa':
                                $tipo_class = 'badge-success';
                                $tipo_display = 'Sumativa';
                                break;
                              default:
                                $tipo_class = 'badge-secondary';
                                $tipo_display = 'No definido';
                            }
                            ?>
                            <span class="badge <?php echo $tipo_class; ?>">
                              <?php echo $tipo_display; ?>
                            </span>
                          </td>
                          <td>
                            <span class="contador-badge contador-criterios">
                              <?php echo intval($rubrica['total_criterios'] ?? 0); ?>
                            </span>
                          </td>
                          <td>
                            <?php
                            $estado = intval($rubrica['estado'] ?? 0);
                            $badge_class = $estado == 1 ? 'badge-activa' : 'badge-inactiva';
                            $estado_texto = $estado == 1 ? 'Activa' : 'Inactiva';
                            ?>
                            <span class="badge badge-estado <?php echo $badge_class; ?>">
                              <?php echo $estado_texto; ?>
                            </span>
                          </td>
                          <td>
                            <span class="text-muted small">
                              <?php 
                                if (!empty($rubrica['fecha_creacion'])) {
                                  try {
                                    $fecha = new DateTime($rubrica['fecha_creacion']);
                                    echo $fecha->format('d/m/Y');
                                  } catch (Exception $e) {
                                    echo '--/--/--';
                                  }
                                } else {
                                  echo '--/--/--';
                                }
                              ?>
                            </span>
                          </td>
                          <td>
                            <div class="d-flex gap-1">
                              <button class="btn btn-outline-view btn-sm" 
                                      onclick="viewRubrica(<?php echo intval($rubrica['id']); ?>)"
                                      title="Ver detalles">
                                <iconify-icon icon="mdi:eye"></iconify-icon>
                              </button>
                              <button class="btn btn-outline-edit btn-sm" 
                                      onclick="editRubrica(<?php echo intval($rubrica['id']); ?>)"
                                      title="Editar rúbrica">
                                <iconify-icon icon="mdi:pencil"></iconify-icon>
                              </button>
                              <button class="btn btn-outline-create btn-sm" 
                                      onclick="configureCriterios(<?php echo intval($rubrica['competencia_id'] ?? 0); ?>)"
                                      title="Ver criterios">
                                <iconify-icon icon="mdi:format-list-checks"></iconify-icon>
                              </button>
                              <button class="btn btn-outline-create btn-sm"
                                      onclick="deleteRubrica(<?php echo intval($rubrica['id']); ?>)"
                                      title="Eliminar rúbrica">
                                <iconify-icon icon="mdi:delete"></iconify-icon>
                              </button>
                            </div>
                          </td>
                        </tr>
                      <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                      <tr>
                        <th><input type="text" placeholder="Filtrar ID" /></th>
                        <th><input type="text" placeholder="Filtrar Nombre" /></th>
                        <th><input type="text" placeholder="Filtrar Competencia" /></th>
                        <th><input type="text" placeholder="Filtrar Curso" /></th>
                        <th>
                          <select>
                            <option value="">Todos los tipos</option>
                            <option value="Diagnóstica">Diagnóstica</option>
                            <option value="Formativa">Formativa</option>
                            <option value="Sumativa">Sumativa</option>
                            <option value="No definido">No definido</option>
                          </select>
                        </th>
                        <th><input type="text" placeholder="Filtrar Criterios" /></th>
                        <th>
                          <select>
                            <option value="">Todos los estados</option>
                            <option value="Activa">Activa</option>
                            <option value="Inactiva">Inactiva</option>
                          </select>
                        </th>
                        <th><input type="text" placeholder="Filtrar Fecha" /></th>
                        <th><div class="no-filter">Sin filtro</div></th>
                      </tr>
                    </tfoot>
                  </table>
                </div>
              <?php else: ?>
                <!-- Estado vacío -->
                <div class="empty-state">
                  <iconify-icon icon="mdi:clipboard-list-outline"></iconify-icon>
                  <h3>No hay rúbricas configuradas</h3>
                  <p>Configure rúbricas de evaluación asociadas a competencias y cursos específicos</p>
                  <button class="btn btn-outline-create mt-3" onclick="createRubrica()">
                    <iconify-icon icon="mdi:plus"></iconify-icon>
                    Crear Primera Rúbrica
                  </button>
                </div>
              <?php endif; ?>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Botón flotante para crear nueva rúbrica -->
  <?php if (!empty($rubricas)): ?>
  <button class="btn-create-floating" onclick="createRubrica()" title="Crear nueva rúbrica">
    <iconify-icon icon="mdi:plus"></iconify-icon>
  </button>
  <?php endif; ?>

    <!-- Incluir Modales -->
  <?php include 'modals/rubricas/modal_create.php'; ?>
  <?php include 'modals/rubricas/modal_edit.php'; ?>
  <?php include 'modals/rubricas/modal_view.php'; ?>
  <?php include 'modals/rubricas/modal_criterios.php'; ?>

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

  <!-- Custom JS -->
  <script src="assets/js/utils/notifications.js"></script>

  <script>
    $(document).ready(function() {
  // Inicializar DataTable con filtros por columna
  const table = $('#rubricasTable').DataTable({
    "language": {
      "url": "https://cdn.datatables.net/plug-ins/1.13.7/i18n/es-ES.json"
    },
    "responsive": false, // Desactivar responsive para usar scroll horizontal
    "scrollX": true, // Habilitar scroll horizontal
    "order": [[0, "desc"]],
    "pageLength": 15, // Mostrar más registros por página
    "lengthMenu": [[10, 15, 25, 50, -1], [10, 15, 25, 50, "Todos"]],
    "dom": '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>rtip',
    "columnDefs": [
      { "orderable": false, "targets": [8] }, // Acciones no ordenables
      { "className": "text-center", "targets": [0, 5, 6, 8] } // Centrar algunas columnas
    ],
    "autoWidth": false, // Desactivar auto-width para usar nuestros anchos personalizados
    "initComplete": function () {
      var api = this.api();

      // Configurar filtros para cada columna
      api.columns().every(function (colIdx) {
        var column = this;
        var footerCell = $(column.footer());
        var input = footerCell.find('input');
        var select = footerCell.find('select');

        // Filtro para inputs de texto
        if (input.length > 0) {
          input.off('keyup change clear').on('keyup change clear', function () {
            var val = $(this).val();
            
            if (val) {
              // Buscar en el contenido de texto sin HTML
              column.search(val, false, false).draw();
            } else {
              column.search('', false, false).draw();
            }
          });
        }

        // Filtro para selects
        if (select.length > 0) {
          select.off('change').on('change', function () {
            var val = $(this).val();
            
            if (val === '') {
              column.search('', false, false).draw();
            } else {
              // Buscar el valor exacto en el contenido visible
              column.search(val, false, false).draw();
            }
          });
        }
      });

      // Función personalizada para filtros más precisos
      $.fn.dataTable.ext.search.push(
        function(settings, data, dataIndex) {
          // Solo aplicar a nuestra tabla
          if (settings.nTable.id !== 'rubricasTable') {
            return true;
          }

          var table = $('#rubricasTable').DataTable();
          var filtersActive = false;
          var showRow = true;

          // Verificar cada columna con filtro
          $('#rubricasTable tfoot th').each(function(colIdx) {
            var input = $(this).find('input');
            var select = $(this).find('select');
            var filterValue = '';
            var cellData = '';

            if (input.length > 0) {
              filterValue = input.val().toLowerCase().trim();
              if (filterValue !== '') {
                filtersActive = true;
                // Obtener el texto de la celda sin HTML
                cellData = $(table.cell(dataIndex, colIdx).node()).text().toLowerCase().trim();
                
                if (cellData.indexOf(filterValue) === -1) {
                  showRow = false;
                }
              }
            }

            if (select.length > 0) {
              filterValue = select.val().trim();
              if (filterValue !== '') {
                filtersActive = true;
                // Obtener el texto de la celda sin HTML
                cellData = $(table.cell(dataIndex, colIdx).node()).text().trim();
                
                if (cellData !== filterValue) {
                  showRow = false;
                }
              }
            }
          });

          return showRow;
        }
      );
      
      // Función para crear nueva rúbrica
      window.createRubrica = function() {
        $('#createRubricaModal').modal('show');
      };

      // Función para ver rúbrica
      window.viewRubrica = function(id) {
        // Obtener datos de la rúbrica
        $.ajax({
          url: 'controllers/rubricas/rubricas_controller.php',
          type: 'POST',
          data: { action: 'get', id: id },
          dataType: 'json',
          success: function(response) {
            if (response.success) {
              // Mostrar datos en modal de vista
              fillViewModal(response.data);
              $('#viewRubricaModal').modal('show');
            } else {
              showNotification('error', 'Error', response.message);
            }
          },
          error: function() {
            showNotification('error', 'Error', 'Error al obtener los datos de la rúbrica');
          }
        });
      };

      // Función para editar rúbrica
      window.editRubrica = function(id) {
        // Obtener datos de la rúbrica
        $.ajax({
          url: 'controllers/rubricas/rubricas_controller.php',
          type: 'POST',
          data: { action: 'get', id: id },
          dataType: 'json',
          success: function(response) {
            if (response.success) {
              // Llenar formulario de edición
              fillEditModal(response.data);
              $('#editRubricaModal').modal('show');
            } else {
              showNotification('error', 'Error', response.message);
            }
          },
          error: function() {
            showNotification('error', 'Error', 'Error al obtener los datos de la rúbrica');
          }
        });
      };

      // Función para configurar criterios
      window.configureCriterios = function(competencia_id) {
        // Cargar modal de configuración de criterios por competencia
        $.ajax({
          url: 'controllers/rubricas/criterios_controller.php',
          type: 'POST',
          data: { action: 'get_by_competencia', competencia_id: competencia_id },
          dataType: 'json',
          success: function(response) {
            if (response.success) {
              fillCriteriosModal(competencia_id, response.data);
              $('#criteriosModal').modal('show');
            } else {
              showNotification('error', 'Error', response.message);
            }
          },
          error: function() {
            showNotification('error', 'Error', 'Error al cargar los criterios');
          }
        });
      };

    window.deleteRubrica = function(id) {
        Swal.fire({
            title: '¿Está seguro?',
            text: 'Estas seguro que quieres eliminar esta rúbrica. Esta acción no se puede deshacer.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Sí, Eliminar',
            cancelButtonText: 'Cancelar',
            confirmButtonColor: '#dc2626',
            reverseButtons: true
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: 'controllers/rubricas/rubricas_controller.php',
                    type: 'POST',
                    data: { action: 'delete', id: id },
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            showNotification('success', 'Rúbrica Eliminada', response.message || 'La rúbrica fue Eliminada correctamente.');
                            // Recargar la página si no usas ajax en DataTable
                            setTimeout(function() { location.reload(); }, 800);
                        } else {
                            showNotification('error', 'Error', response.message || 'No se pudo desactivar la rúbrica.');
                        }
                    },
                    error: function() {
                        showNotification('error', 'Error', 'Error al intentar desactivar la rúbrica.');
                    }
                });
            }
        });
    };

      // Mejorar la experiencia de usuario con los filtros
      $('#rubricasTable tfoot input').on('focus', function() {
        $(this).css('border-color', '#2563eb');
      }).on('blur', function() {
        $(this).css('border-color', '#cbd5e1');
      });

      $('#rubricasTable tfoot select').on('focus', function() {
        $(this).css('border-color', '#2563eb');
      }).on('blur', function() {
        $(this).css('border-color', '#cbd5e1');
      });

      // Añadir indicador visual cuando hay filtros activos
      $('#rubricasTable tfoot input, #rubricasTable tfoot select').on('keyup change', function() {
        var hasFilters = false;
        $('#rubricasTable tfoot input, #rubricasTable tfoot select').each(function() {
          if ($(this).val() !== '') {
            hasFilters = true;
            return false;
          }
        });

        if (hasFilters) {
          $('#rubricasTable tfoot').addClass('filters-active');
        } else {
          $('#rubricasTable tfoot').removeClass('filters-active');
        }
      });

      // Mostrar mensaje de error si existe
      <?php if (isset($error_message)): ?>
        showNotification('error', 'Error de Base de Datos', '<?php echo addslashes($error_message); ?>');
      <?php endif; ?>
    }
  });
});
  </script>
</body>
</html>