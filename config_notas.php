<?php
include 'config/bd.php';

// Consultar datos de escalas de calificación y estadísticas
try {
  // Consulta principal de escalas con JOINs para obtener información relacionada
  $query = "SELECT ec.id, ec.ano_academico_id, ec.nivel_educativo, ec.tipo_escala, 
                     ec.configuracion, ec.fecha_creacion,
                     COALESCE(aa.nombre, 'Año no especificado') as ano_academico_nombre,
                     COALESCE(aa.anio, 0) as anio,
                     COALESCE(aa.estado, 'inactivo') as ano_estado
              FROM escalas_calificacion ec
              LEFT JOIN anos_academicos aa ON ec.ano_academico_id = aa.id
              WHERE ec.estado = 1
              ORDER BY aa.anio DESC, ec.nivel_educativo, ec.fecha_creacion DESC";
  $stmt = $pdo->prepare($query);
  $stmt->execute();
  $escalas = $stmt->fetchAll(PDO::FETCH_ASSOC);

  // Procesar configuración JSON para mostrar información legible
  foreach ($escalas as &$escala) {
    if (!empty($escala['configuracion'])) {
      $config = json_decode($escala['configuracion'], true);
      if ($config && is_array($config)) {
        $escala['escalas_configuradas'] = count($config);
        $escala['config_detalle'] = $config;

        // Crear resumen de escalas
        $resumen_escalas = [];
        foreach ($config as $letra => $datos) {
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

  // Obtener estadísticas generales
  $estadisticas = [];

  // Total de escalas configuradas
  $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM escalas_calificacion");
  $stmt->execute();
  $estadisticas['total_escalas'] = intval($stmt->fetch()['total']);

  // Escalas por nivel educativo
  $stmt = $pdo->prepare("
        SELECT nivel_educativo, COUNT(*) as total 
        FROM escalas_calificacion 
        GROUP BY nivel_educativo
    ");
  $stmt->execute();
  $escalas_por_nivel = $stmt->fetchAll(PDO::FETCH_ASSOC);
  $estadisticas['escalas_inicial'] = 0;
  $estadisticas['escalas_primaria'] = 0;
  $estadisticas['escalas_secundaria'] = 0;

  foreach ($escalas_por_nivel as $nivel) {
    switch ($nivel['nivel_educativo']) {
      case 'inicial':
        $estadisticas['escalas_inicial'] = intval($nivel['total']);
        break;
      case 'primaria':
        $estadisticas['escalas_primaria'] = intval($nivel['total']);
        break;
      case 'secundaria':
        $estadisticas['escalas_secundaria'] = intval($nivel['total']);
        break;
    }
  }

  // Años académicos con escalas configuradas
  $stmt = $pdo->prepare("
        SELECT COUNT(DISTINCT ec.ano_academico_id) as total
        FROM escalas_calificacion ec
        INNER JOIN anos_academicos aa ON ec.ano_academico_id = aa.id
    ");
  $stmt->execute();
  $estadisticas['anos_con_escalas'] = intval($stmt->fetch()['total']);

  // Total de competencias en el sistema
  $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM competencias WHERE estado = 1");
  $stmt->execute();
  $estadisticas['total_competencias'] = intval($stmt->fetch()['total']);

  // Total de criterios de evaluación
  $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM criterios_evaluacion");
  $stmt->execute();
  $estadisticas['total_criterios'] = intval($stmt->fetch()['total']);

  // Promedio de criterios por competencia
  if ($estadisticas['total_competencias'] > 0) {
    $estadisticas['promedio_criterios'] = round($estadisticas['total_criterios'] / $estadisticas['total_competencias'], 1);
  } else {
    $estadisticas['promedio_criterios'] = 0;
  }

  // Obtener tipos de escala para estadísticas
  $stmt = $pdo->prepare("
        SELECT tipo_escala, COUNT(*) as total 
        FROM escalas_calificacion 
        WHERE tipo_escala IS NOT NULL AND tipo_escala != ''
        GROUP BY tipo_escala
    ");
  $stmt->execute();
  $tipos_escala = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
  $escalas = [];
  $estadisticas = [
    'total_escalas' => 0,
    'escalas_inicial' => 0,
    'escalas_primaria' => 0,
    'escalas_secundaria' => 0,
    'anos_con_escalas' => 0,
    'total_competencias' => 0,
    'total_criterios' => 0,
    'promedio_criterios' => 0
  ];
  $tipos_escala = [];
  $error_message = "Error al consultar las escalas de calificación: " . $e->getMessage();
} catch (Exception $e) {
  $escalas = [];
  $estadisticas = [
    'total_escalas' => 0,
    'escalas_inicial' => 0,
    'escalas_primaria' => 0,
    'escalas_secundaria' => 0,
    'anos_con_escalas' => 0,
    'total_competencias' => 0,
    'total_criterios' => 0,
    'promedio_criterios' => 0
  ];
  $tipos_escala = [];
  $error_message = "Error general: " . $e->getMessage();
}
?>
<!doctype html>
<html lang="en">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Colegio Mariategui - Configuración de Notas</title>
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
      --pink-color: #ec4899;
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

    .info-card-icon.pink {
      background: rgb(236 72 153 / 0.1);
      color: var(--pink-color);
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

    .badge-literal {
      background-color: rgb(37 99 235 / 0.1);
      color: var(--primary-color);
    }

    .badge-numerico {
      background-color: rgb(5 150 105 / 0.1);
      color: var(--success-color);
    }

    .badge-descriptivo {
      background-color: rgb(217 119 6 / 0.1);
      color: var(--warning-color);
    }

    .badge-inicial {
      background-color: rgb(236 72 153 / 0.1);
      color: var(--pink-color);
    }

    .badge-primaria {
      background-color: rgb(79 70 229 / 0.1);
      color: var(--indigo-color);
    }

    .badge-secundaria {
      background-color: rgb(124 58 237 / 0.1);
      color: var(--purple-color);
    }

    .badge-activo {
      background-color: rgb(34 197 94 / 0.1);
      color: var(--success-color);
    }

    .badge-inactivo {
      background-color: rgb(239 68 68 / 0.1);
      color: var(--danger-color);
    }

    /* Contadores y elementos destacados */
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

    .contador-escalas {
      background-color: var(--success-color);
    }

    .escala-resumen {
      font-family: 'Monaco', 'Menlo', 'Consolas', monospace;
      font-size: 0.75rem;
      background-color: var(--gray-100);
      padding: 0.25rem 0.5rem;
      border-radius: 4px;
      color: var(--gray-700);
      border: 1px solid var(--gray-200);
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

    .btn-outline-danger {
      color: var(--danger-color);
      border: 1.5px solid var(--danger-color);
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

    .btn-outline-danger:hover {
      background-color: var(--danger-color);
      border-color: var(--danger-color);
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
    #escalasTable {
      width: 100% !important;
      min-width: 1200px;
    }

    /* Ajustar anchos de columnas específicas */
    #escalasTable th:nth-child(1) {
      width: 60px;
    }

    /* ID */
    #escalasTable th:nth-child(2) {
      width: 150px;
    }

    /* Año Académico */
    #escalasTable th:nth-child(3) {
      width: 120px;
    }

    /* Nivel Educativo */
    #escalasTable th:nth-child(4) {
      width: 120px;
    }

    /* Tipo Escala */
    #escalasTable th:nth-child(5) {
      width: 100px;
    }

    /* Escalas Config */
    #escalasTable th:nth-child(6) {
      width: 300px;
    }

    /* Configuración */
    #escalasTable th:nth-child(7) {
      width: 120px;
    }

    /* Fecha */
    #escalasTable th:nth-child(8) {
      width: 160px;
    }

    /* Acciones */

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
    *,
    *::before,
    *::after {
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
                <iconify-icon icon="mdi:star-four-points"></iconify-icon>
                Configuración de Notas
              </h5>

              
            </div>

            <!-- Tarjetas de información -->
            <div class="info-cards-container">
              <div class="row g-3">
                <div class="col-xl-2 col-lg-4 col-md-6">
                  <div class="info-card">
                    <div class="info-card-icon primary">
                      <iconify-icon icon="mdi:scale-balance"></iconify-icon>
                    </div>
                    <div class="info-card-title">Total Escalas</div>
                    <div class="info-card-value">
                      <?php echo intval($estadisticas['total_escalas'] ?? 0); ?>
                    </div>
                    <div class="info-card-description">Escalas configuradas</div>
                  </div>
                </div>

                <div class="col-xl-2 col-lg-4 col-md-6">
                  <div class="info-card">
                    <div class="info-card-icon pink">
                      <iconify-icon icon="mdi:baby-face"></iconify-icon>
                    </div>
                    <div class="info-card-title">Inicial</div>
                    <div class="info-card-value">
                      <?php echo intval($estadisticas['escalas_inicial'] ?? 0); ?>
                    </div>
                    <div class="info-card-description">Escalas nivel inicial</div>
                  </div>
                </div>

                <div class="col-xl-2 col-lg-4 col-md-6">
                  <div class="info-card">
                    <div class="info-card-icon indigo">
                      <iconify-icon icon="mdi:school"></iconify-icon>
                    </div>
                    <div class="info-card-title">Primaria</div>
                    <div class="info-card-value">
                      <?php echo intval($estadisticas['escalas_primaria'] ?? 0); ?>
                    </div>
                    <div class="info-card-description">Escalas nivel primaria</div>
                  </div>
                </div>

                <div class="col-xl-2 col-lg-4 col-md-6">
                  <div class="info-card">
                    <div class="info-card-icon purple">
                      <iconify-icon icon="mdi:account-group"></iconify-icon>
                    </div>
                    <div class="info-card-title">Secundaria</div>
                    <div class="info-card-value">
                      <?php echo intval($estadisticas['escalas_secundaria'] ?? 0); ?>
                    </div>
                    <div class="info-card-description">Escalas nivel secundaria</div>
                  </div>
                </div>

                <div class="col-xl-2 col-lg-4 col-md-6">
                  <div class="info-card">
                    <div class="info-card-icon success">
                      <iconify-icon icon="mdi:calendar-check"></iconify-icon>
                    </div>
                    <div class="info-card-title">Años Configurados</div>
                    <div class="info-card-value">
                      <?php echo intval($estadisticas['anos_con_escalas'] ?? 0); ?>
                    </div>
                    <div class="info-card-description">Años académicos</div>
                  </div>
                </div>

                <div class="col-xl-2 col-lg-4 col-md-6">
                  <div class="info-card">
                    <div class="info-card-icon warning">
                      <iconify-icon icon="mdi:format-list-checks"></iconify-icon>
                    </div>
                    <div class="info-card-title">Competencias</div>
                    <div class="info-card-value">
                      <?php echo intval($estadisticas['total_competencias'] ?? 0); ?>
                    </div>
                    <div class="info-card-description">
                      <?php echo floatval($estadisticas['promedio_criterios'] ?? 0); ?> criterios/comp. promedio
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Contenido del card -->
            <div class="card-body p-0">
              <?php if (!empty($escalas)): ?>
                <!-- Tabla de Escalas de Calificación -->
                <div class="table-container">
                  <div class="d-flex justify-content-end p-3 border-top">
                    <button type="button" class="btn btn-outline-danger" id="btnGenerarReportePDF"
                      title="Generar reporte PDF con filtros aplicados">
                      <iconify-icon icon="mdi:file-pdf-box" class="me-2"></iconify-icon>
                      Generar Reporte PDF
                    </button>
                  </div>

                  <table id="escalasTable" class="table table-hover w-100">
                    <thead>
                      <tr>
                        <th>ID</th>
                        <th>Año Académico</th>
                        <th>Nivel Educativo</th>
                        <th>Tipo Escala</th>
                        <th>Escalas</th>
                        <th>Configuración</th>
                        <th>Fecha</th>
                        <th>Acciones</th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php foreach ($escalas as $escala): ?>
                        <tr>
                          <td>
                            <span class="fw-semibold text-dark"><?php echo htmlspecialchars($escala['id']); ?></span>
                          </td>
                          <td>
                            <div class="fw-semibold text-dark">
                              <?php echo htmlspecialchars($escala['ano_academico_nombre'] ?? 'Sin año'); ?>
                            </div>
                            <?php if (!empty($escala['anio'])): ?>
                              <small class="text-muted">Año <?php echo htmlspecialchars($escala['anio']); ?></small>
                              <?php
                              $ano_estado = $escala['ano_estado'] ?? 'inactivo';
                              $estado_class = $ano_estado === 'activo' ? 'badge-activo' : 'badge-inactivo';
                              ?>
                              <br><span class="badge badge-estado <?php echo $estado_class; ?> mt-1"
                                style="font-size: 0.6rem;">
                                <?php echo ucfirst($ano_estado); ?>
                              </span>
                            <?php endif; ?>
                          </td>
                          <td>
                            <?php
                            $nivel = $escala['nivel_educativo'] ?? '';
                            $nivel_class = '';
                            $nivel_display = '';

                            switch ($nivel) {
                              case 'inicial':
                                $nivel_class = 'badge-inicial';
                                $nivel_display = 'Inicial';
                                break;
                              case 'primaria':
                                $nivel_class = 'badge-primaria';
                                $nivel_display = 'Primaria';
                                break;
                              case 'secundaria':
                                $nivel_class = 'badge-secundaria';
                                $nivel_display = 'Secundaria';
                                break;
                              default:
                                $nivel_class = 'badge-estado';
                                $nivel_display = 'No definido';
                            }
                            ?>
                            <span class="badge <?php echo $nivel_class; ?>">
                              <?php echo $nivel_display; ?>
                            </span>
                          </td>
                          <td>
                            <?php
                            $tipo = $escala['tipo_escala'] ?? '';
                            $tipo_class = '';
                            $tipo_display = '';

                            switch ($tipo) {
                              case 'literal':
                                $tipo_class = 'badge-literal';
                                $tipo_display = 'Literal (A,B,C,D)';
                                break;
                              case 'numerico':
                                $tipo_class = 'badge-numerico';
                                $tipo_display = 'Numérico (0-20)';
                                break;
                              case 'descriptivo':
                                $tipo_class = 'badge-descriptivo';
                                $tipo_display = 'Descriptivo';
                                break;
                              default:
                                $tipo_class = 'badge-estado';
                                $tipo_display = 'No definido';
                            }
                            ?>
                            <span class="badge <?php echo $tipo_class; ?>">
                              <?php echo $tipo_display; ?>
                            </span>
                          </td>
                          <td>
                            <span class="contador-badge contador-escalas">
                              <?php echo intval($escala['escalas_configuradas'] ?? 0); ?>
                            </span>
                          </td>
                          <td>
                            <?php if (!empty($escala['resumen_escalas']) && $escala['resumen_escalas'] !== 'Sin configuración'): ?>
                              <div class="escala-resumen">
                                <?php echo htmlspecialchars($escala['resumen_escalas']); ?>
                              </div>
                            <?php else: ?>
                              <span class="text-muted small">Sin configuración</span>
                            <?php endif; ?>
                          </td>
                          <td>
                            <span class="text-muted small">
                              <?php
                              if (!empty($escala['fecha_creacion'])) {
                                try {
                                  $fecha = new DateTime($escala['fecha_creacion']);
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
                                onclick="viewEscala(<?php echo intval($escala['id']); ?>)" title="Ver detalles">
                                <iconify-icon icon="mdi:eye"></iconify-icon>
                              </button>
                              <button class="btn btn-outline-edit btn-sm"
                                onclick="editEscala(<?php echo intval($escala['id']); ?>)" title="Editar escala">
                                <iconify-icon icon="mdi:pencil"></iconify-icon>
                              </button>
                              <button class="btn btn-outline-create btn-sm"
                                onclick="configureEscala(<?php echo intval($escala['id']); ?>)" title="Configurar escalas">
                                <iconify-icon icon="mdi:cog"></iconify-icon>
                              </button>
                              <button class="btn btn-outline-danger btn-sm"
                                onclick="deleteEscala(<?php echo intval($escala['id']); ?>)" title="Eliminar escala">
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
                        <th><input type="text" placeholder="Filtrar Año" /></th>
                        <th>
                          <select>
                            <option value="">Todos los niveles</option>
                            <option value="Inicial">Inicial</option>
                            <option value="Primaria">Primaria</option>
                            <option value="Secundaria">Secundaria</option>
                          </select>
                        </th>
                        <th>
                          <select>
                            <option value="">Todos los tipos</option>
                            <option value="Literal (A,B,C,D)">Literal</option>
                            <option value="Numérico (0-20)">Numérico</option>
                            <option value="Descriptivo">Descriptivo</option>
                          </select>
                        </th>
                        <th><input type="text" placeholder="Filtrar Escalas" /></th>
                        <th><input type="text" placeholder="Filtrar Config" /></th>
                        <th><input type="text" placeholder="Filtrar Fecha" /></th>
                        <th>
                          <div class="no-filter">Sin filtro</div>
                        </th>
                      </tr>
                    </tfoot>
                  </table>
                </div>
              <?php else: ?>
                <!-- Estado vacío -->
                <div class="empty-state">
                  <iconify-icon icon="mdi:star-four-points-outline"></iconify-icon>
                  <h3>No hay escalas de calificación configuradas</h3>
                  <p>Configure escalas de calificación para establecer los criterios de evaluación del sistema</p>
                  <button class="btn btn-outline-create mt-3" onclick="createEscala()">
                    <iconify-icon icon="mdi:plus"></iconify-icon>
                    Crear Primera Escala
                  </button>
                </div>
              <?php endif; ?>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Botón flotante para crear nueva escala -->
  <?php if (!empty($escalas)): ?>
    <button class="btn-create-floating" onclick="createEscala()" title="Crear nueva escala de calificación">
      <iconify-icon icon="mdi:plus"></iconify-icon>
    </button>
  <?php endif; ?>

  <!-- Incluir Modales -->
  <?php include 'modals/escalas/modal_create.php'; ?>
  <?php include 'modals/escalas/modal_edit.php'; ?>
  <?php include 'modals/escalas/modal_view.php'; ?>
  <?php include 'modals/escalas/modal_configure.php'; ?>

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

  <!-- Notifications utility -->
  <script src="./assets/js/utils/notifications.js"></script>

  <script>
    $(document).ready(function () {
      // Inicializar DataTable con filtros por columna
      const table = $('#escalasTable').DataTable({
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
          { "orderable": false, "targets": [7] }, // Acciones no ordenables
          { "className": "text-center", "targets": [0, 4, 7] } // Centrar algunas columnas
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
            function (settings, data, dataIndex) {
              // Solo aplicar a nuestra tabla
              if (settings.nTable.id !== 'escalasTable') {
                return true;
              }

              var table = $('#escalasTable').DataTable();
              var filtersActive = false;
              var showRow = true;

              // Verificar cada columna con filtro
              $('#escalasTable tfoot th').each(function (colIdx) {
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

          // Función para crear nueva escala
          window.createEscala = function () {
            $('#createEscalaModal').modal('show');
          };

          // Función para ver escala
          window.viewEscala = function (id) {
            // Obtener datos de la escala
            $.ajax({
              url: 'controllers/escalas/escalas_controller.php',
              type: 'POST',
              data: { action: 'get', id: id },
              dataType: 'json',
              success: function (response) {
                if (response.success) {
                  // Mostrar datos en modal de vista
                  fillViewModal(response.data);
                  $('#viewEscalaModal').modal('show');
                } else {
                  showNotification('error', 'Error', response.message);
                }
              },
              error: function () {
                showNotification('error', 'Error', 'Error al obtener los datos de la escala');
              }
            });
          };

          // Función para editar escala
          window.editEscala = function (id) {
            // Obtener datos de la escala
            $.ajax({
              url: 'controllers/escalas/escalas_controller.php',
              type: 'POST',
              data: { action: 'get', id: id },
              dataType: 'json',
              success: function (response) {
                if (response.success) {
                  // Llenar formulario de edición
                  fillEditModal(response.data);
                  $('#editEscalaModal').modal('show');
                } else {
                  showNotification('error', 'Error', response.message);
                }
              },
              error: function () {
                showNotification('error', 'Error', 'Error al obtener los datos de la escala');
              }
            });
          };

          // Función para configurar escala
          window.configureEscala = function (id) {
            // Cargar modal de configuración de escala
            $.ajax({
              url: 'controllers/escalas/escalas_controller.php',
              type: 'POST',
              data: { action: 'get_configuracion', id: id },
              dataType: 'json',
              success: function (response) {
                if (response.success) {
                  fillConfigureModal(id, response.data);
                  $('#configureEscalaModal').modal('show');
                } else {
                  showNotification('error', 'Error', response.message);
                }
              },
              error: function () {
                showNotification('error', 'Error', 'Error al cargar la configuración');
              }
            });
          };

          window.deleteEscala = function (id) {
            Swal.fire({
              title: '¿Está seguro?',
              text: 'La escala de calificación será eliminada permanentemente.',
              icon: 'warning',
              showCancelButton: true,
              confirmButtonText: 'Sí, eliminar',
              cancelButtonText: 'Cancelar',
              confirmButtonColor: '#dc2626',
              reverseButtons: true
            }).then((result) => {
              if (result.isConfirmed) {
                $.ajax({
                  url: 'controllers/escalas/escalas_controller.php',
                  type: 'POST',
                  data: { action: 'delete', id: id },
                  dataType: 'json',
                  success: function (response) {
                    if (response.success) {
                      showNotification('success', 'Escala eliminada', response.message || 'La escala fue eliminada correctamente.');
                      // Recargar la tabla
                      $('#escalasTable').DataTable().ajax.reload(null, false);
                      // O recargar la página si no usas ajax en DataTable
                      setTimeout(function () { location.reload(); }, 800);
                    } else {
                      showNotification('error', 'Error', response.message || 'No se pudo eliminar la escala.');
                    }
                  },
                  error: function () {
                    showNotification('error', 'Error', 'Error al intentar eliminar la escala.');
                  }
                });
              }
            });
          };

          $('#btnGenerarReportePDF').on('click', function () {
            var table = $('#escalasTable').DataTable();
            var filtros = {};
            var hayFiltros = false;

            // Recopilar filtros activos del DataTable
            $('#escalasTable tfoot th').each(function (index) {
              var input = $(this).find('input');
              var select = $(this).find('select');
              var columnName = $('#escalasTable thead th').eq(index).text().trim();

              if (input.length > 0 && input.val().trim() !== '') {
                filtros['filter_' + columnName.toLowerCase().replace(/\s+/g, '_')] = input.val().trim();
                hayFiltros = true;
              }

              if (select.length > 0 && select.val().trim() !== '') {
                filtros['filter_' + columnName.toLowerCase().replace(/\s+/g, '_')] = select.val().trim();
                hayFiltros = true;
              }
            });

            // Mostrar notificación de generación
            let loadingAlert = showLoadingNotification(
              'Generando Reporte PDF',
              'Procesando escalas de calificación...'
            );

            // Construir URL con parámetros de filtro
            var url = 'reports/escalas_pdf_report.php';
            var params = new URLSearchParams(filtros);

            if (params.toString()) {
              url += '?' + params.toString();
            }

            // Abrir PDF en nueva ventana
            setTimeout(function () {
              var pdfWindow = window.open(url, '_blank');

              // Verificar si la ventana se abrió correctamente
              if (pdfWindow) {
                hideLoadingNotification();

                if (hayFiltros) {
                  showSuccessNotification(
                    'Reporte PDF Generado',
                    'El reporte se generó con los filtros aplicados en la tabla.'
                  );
                } else {
                  showSuccessNotification(
                    'Reporte PDF Generado',
                    'El reporte se generó con todas las escalas de calificación.'
                  );
                }

                // Detectar si la ventana se cerró (posible bloqueo de popups)
                var checkClosed = setInterval(function () {
                  if (pdfWindow.closed) {
                    clearInterval(checkClosed);
                  }
                }, 1000);

              } else {
                hideLoadingNotification();
                showErrorNotification(
                  'Popup Bloqueado',
                  'El navegador bloqueó la ventana del reporte. Por favor, permite popups para este sitio y vuelve a intentar.'
                );
              }
            }, 500);
          });

          // Estilo para el botón cuando hay filtros activos
          function updateReportButtonState() {
            var hasActiveFilters = false;
            $('#escalasTable tfoot input, #escalasTable tfoot select').each(function () {
              if ($(this).val().trim() !== '') {
                hasActiveFilters = true;
                return false;
              }
            });

            var $btnReporte = $('#btnGenerarReportePDF');
            if (hasActiveFilters) {
              $btnReporte.removeClass('btn-outline-danger').addClass('btn-danger text-white');
              $btnReporte.find('iconify-icon').attr('icon', 'mdi:file-pdf-box');
              $btnReporte.attr('title', 'Generar reporte PDF con filtros aplicados');
            } else {
              $btnReporte.removeClass('btn-danger text-white').addClass('btn-outline-danger');
              $btnReporte.find('iconify-icon').attr('icon', 'mdi:file-pdf-box-outline');
              $btnReporte.attr('title', 'Generar reporte PDF de todas las escalas');
            }
          }

          // Monitorear cambios en filtros para actualizar el botón
          $('#escalasTable tfoot input, #escalasTable tfoot select').on('keyup change', function () {
            updateReportButtonState();
          });

          // Estado inicial del botón
          updateReportButtonState();

          // Mejorar la experiencia de usuario con los filtros
          $('#escalasTable tfoot input').on('focus', function () {
            $(this).css('border-color', '#2563eb');
          }).on('blur', function () {
            $(this).css('border-color', '#cbd5e1');
          });

          $('#escalasTable tfoot select').on('focus', function () {
            $(this).css('border-color', '#2563eb');
          }).on('blur', function () {
            $(this).css('border-color', '#cbd5e1');
          });

          // Añadir indicador visual cuando hay filtros activos
          $('#escalasTable tfoot input, #escalasTable tfoot select').on('keyup change', function () {
            var hasFilters = false;
            $('#escalasTable tfoot input, #escalasTable tfoot select').each(function () {
              if ($(this).val() !== '') {
                hasFilters = true;
                return false;
              }
            });

            if (hasFilters) {
              $('#escalasTable tfoot').addClass('filters-active');
            } else {
              $('#escalasTable tfoot').removeClass('filters-active');
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