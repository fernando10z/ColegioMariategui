<?php
include 'config/bd.php';

// Consultar datos de la configuración del sistema directamente
try {
    $query = "SELECT * FROM configuracion_sistema ORDER BY id ASC";
    $stmt = $pdo->prepare($query);
    $stmt->execute();
    $configuraciones = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Obtener estadísticas adicionales
    $estadisticas = [];
    if (!empty($configuraciones)) {
        $config = $configuraciones[0]; // Tomar el primer registro para las estadísticas
        $estadisticas = [
            'colores_institucionales' => !empty($config['colores_institucionales']) ? json_decode($config['colores_institucionales'], true) : null,
            'configuracion_backup' => !empty($config['configuracion_backup']) ? json_decode($config['configuracion_backup'], true) : null,
            'parametros_generales' => !empty($config['parametros_generales']) ? json_decode($config['parametros_generales'], true) : null,
            'fecha_creacion' => $config['fecha_creacion'] ?? null
        ];
    }
} catch (PDOException $e) {
    $configuraciones = [];
    $estadisticas = [];
    $error_message = "Error al consultar las configuraciones: " . $e->getMessage();
}
?>
<!doctype html>
<html lang="en">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Colegio Mariategui - Configuración del Sistema</title>
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

    .info-card-icon.secondary {
      background: rgb(100 116 139 / 0.1);
      color: var(--secondary-color);
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

    /* Logo preview mejorado */
    .logo-preview {
      width: 48px;
      height: 48px;
      object-fit: cover;
      border-radius: 6px;
      border: 2px solid var(--gray-200);
      background-color: var(--gray-50);
    }

    .logo-placeholder {
      width: 48px;
      height: 48px;
      display: flex;
      align-items: center;
      justify-content: center;
      background-color: var(--gray-100);
      border: 2px dashed var(--gray-300);
      border-radius: 6px;
      color: var(--gray-400);
      font-size: 0.75rem;
      text-align: center;
    }

    /* Badges para estados */
    .badge-nivel {
      padding: 0.375rem 0.75rem;
      border-radius: 9999px;
      font-size: 0.75rem;
      font-weight: 500;
      text-transform: uppercase;
      letter-spacing: 0.025em;
      background-color: rgb(59 130 246 / 0.1);
      color: #1d4ed8;
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

    .btn-outline-edit:focus {
      box-shadow: 0 0 0 3px rgb(100 116 139 / 0.2);
      outline: none;
    }

    /* DataTables personalización */
    .dataTables_wrapper {
      padding: 1rem;
      width: 100% !important;
    }

    /* Hacer la tabla más ancha */
    #configuracionTable {
      width: 100% !important;
      min-width: 1200px;
    }

    /* Ajustar anchos de columnas específicas */
    #configuracionTable th:nth-child(1) { width: 60px; }  /* ID */
    #configuracionTable th:nth-child(2) { width: 80px; }  /* Logo */
    #configuracionTable th:nth-child(3) { width: 200px; } /* Institución */
    #configuracionTable th:nth-child(4) { width: 120px; } /* Código Modular */
    #configuracionTable th:nth-child(5) { width: 100px; } /* UGEL */
    #configuracionTable th:nth-child(6) { width: 130px; } /* Nivel Educativo */
    #configuracionTable th:nth-child(7) { width: 250px; } /* Dirección */
    #configuracionTable th:nth-child(8) { width: 120px; } /* Teléfono */
    #configuracionTable th:nth-child(9) { width: 200px; } /* Email */
    #configuracionTable th:nth-child(10) { width: 120px; } /* Acciones */

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
      max-width: 250px;
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
    }

    /* Columna de dirección expandible */
    .direccion-cell {
      max-width: 250px;
      word-wrap: break-word;
      white-space: normal !important;
      line-height: 1.4;
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

      .info-cards-container {
        padding: 1rem;
      }

      .info-card {
        padding: 1rem;
        margin-bottom: 1rem;
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
                <iconify-icon icon="mdi:cog"></iconify-icon>
                Configuración del Sistema
              </h5>
            </div>

            <!-- Tarjetas de información -->
            <div class="info-cards-container">
              <div class="row g-3">
                <div class="col-xl-3 col-lg-6 col-md-6">
                  <div class="info-card">
                    <div class="info-card-icon primary">
                      <iconify-icon icon="mdi:palette"></iconify-icon>
                    </div>
                    <div class="info-card-title">Colores Institucionales</div>
                    <div class="info-card-value">
                      <?php 
                        if (!empty($estadisticas['colores_institucionales'])) {
                          echo is_array($estadisticas['colores_institucionales']) ? count($estadisticas['colores_institucionales']) . ' colores' : 'Configurado';
                        } else {
                          echo 'No configurado';
                        }
                      ?>
                    </div>
                    <div class="info-card-description">Esquema de colores del sistema</div>
                  </div>
                </div>

                <div class="col-xl-3 col-lg-6 col-md-6">
                  <div class="info-card">
                    <div class="info-card-icon success">
                      <iconify-icon icon="mdi:backup-restore"></iconify-icon>
                    </div>
                    <div class="info-card-title">Configuración Backup</div>
                    <div class="info-card-value">
                      <?php 
                        if (!empty($estadisticas['configuracion_backup'])) {
                          $backup = $estadisticas['configuracion_backup'];
                          echo is_array($backup) && isset($backup['enabled']) && $backup['enabled'] ? 'Activo' : 'Configurado';
                        } else {
                          echo 'Sin configurar';
                        }
                      ?>
                    </div>
                    <div class="info-card-description">Sistema de respaldo automático</div>
                  </div>
                </div>

                <div class="col-xl-3 col-lg-6 col-md-6">
                  <div class="info-card">
                    <div class="info-card-icon warning">
                      <iconify-icon icon="mdi:cog-outline"></iconify-icon>
                    </div>
                    <div class="info-card-title">Parámetros Generales</div>
                    <div class="info-card-value">
                      <?php 
                        if (!empty($estadisticas['parametros_generales'])) {
                          echo is_array($estadisticas['parametros_generales']) ? count($estadisticas['parametros_generales']) . ' parámetros' : 'Configurado';
                        } else {
                          echo 'Por configurar';
                        }
                      ?>
                    </div>
                    <div class="info-card-description">Configuraciones del sistema</div>
                  </div>
                </div>

                <div class="col-xl-3 col-lg-6 col-md-6">
                  <div class="info-card">
                    <div class="info-card-icon secondary">
                      <iconify-icon icon="mdi:calendar-clock"></iconify-icon>
                    </div>
                    <div class="info-card-title">Fecha de Creación</div>
                    <div class="info-card-value">
                      <?php 
                        if (!empty($estadisticas['fecha_creacion'])) {
                          $fecha = new DateTime($estadisticas['fecha_creacion']);
                          echo $fecha->format('d/m/Y');
                        } else {
                          echo '--/--/--';
                        }
                      ?>
                    </div>
                    <div class="info-card-description">Registro inicial del sistema</div>
                  </div>
                </div>
              </div>
            </div>
            
            <!-- Contenido del card -->
            <div class="card-body p-0">
              <?php if (!empty($configuraciones)): ?>
                <!-- Tabla de Configuraciones -->
                <div class="table-container">
                  <table id="configuracionTable" class="table table-hover w-100">
                    <thead>
                      <tr>
                        <th>ID</th>
                        <th>Logo</th>
                        <th>Institución</th>
                        <th>Código Modular</th>
                        <th>UGEL</th>
                        <th>Nivel Educativo</th>
                        <th>Dirección</th>
                        <th>Teléfono</th>
                        <th>Email</th>
                        <th>Acciones</th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php foreach ($configuraciones as $config): ?>
                        <tr>
                          <td>
                            <span class="fw-semibold text-dark"><?php echo htmlspecialchars($config['id']); ?></span>
                          </td>
                          <td>
                            <?php if (!empty($config['logo_url'])): ?>
                              <img src="<?php echo htmlspecialchars($config['logo_url']); ?>" 
                                   alt="Logo de <?php echo htmlspecialchars($config['nombre_institucion']); ?>" 
                                   class="logo-preview">
                            <?php else: ?>
                              <div class="logo-placeholder">
                                <iconify-icon icon="mdi:image-off"></iconify-icon>
                              </div>
                            <?php endif; ?>
                          </td>
                          <td>
                            <div class="fw-semibold text-dark"><?php echo htmlspecialchars($config['nombre_institucion']); ?></div>
                          </td>
                          <td>
                            <span class="font-monospace"><?php echo htmlspecialchars($config['codigo_modular']); ?></span>
                          </td>
                          <td>
                            <span class="text-dark"><?php echo htmlspecialchars($config['ugel']); ?></span>
                          </td>
                          <td>
                            <span class="badge badge-nivel"><?php echo htmlspecialchars($config['nivel_educativo']); ?></span>
                          </td>
                          <td>
                            <div class="direccion-cell" title="<?php echo htmlspecialchars($config['direccion']); ?>">
                              <?php echo htmlspecialchars($config['direccion']); ?>
                            </div>
                          </td>
                          <td>
                            <span class="font-monospace"><?php echo htmlspecialchars($config['telefono']); ?></span>
                          </td>
                          <td>
                            <span class="text-primary"><?php echo htmlspecialchars($config['email']); ?></span>
                          </td>
                          <td>
                            <div class="d-flex gap-2">
                              <button class="btn btn-outline-edit btn-sm" 
                                      onclick="editConfiguracion(<?php echo $config['id']; ?>)"
                                      title="Editar configuración">
                                <iconify-icon icon="mdi:pencil"></iconify-icon>
                                Editar
                              </button>
                            </div>
                          </td>
                        </tr>
                      <?php endforeach; ?>
                    </tbody>
                  </table>
                </div>
              <?php else: ?>
                <!-- Estado vacío -->
                <div class="empty-state">
                  <iconify-icon icon="mdi:cog-off"></iconify-icon>
                  <h3>No hay configuraciones registradas</h3>
                  <p>Comience agregando la configuración del sistema</p>
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

  <!-- Incluir Modal de Edición -->
  <?php include 'modals/configuracion/modal_edit.php'; ?>

  <script>
    $(document).ready(function() {
      // Inicializar DataTable
      const table = $('#configuracionTable').DataTable({
        "language": {
          "url": "https://cdn.datatables.net/plug-ins/1.13.7/i18n/es-ES.json"
        },
        "responsive": false, // Desactivar responsive para usar scroll horizontal
        "scrollX": true, // Habilitar scroll horizontal
        "order": [[0, "asc"]],
        "pageLength": 15, // Mostrar más registros por página
        "lengthMenu": [[10, 15, 25, 50, -1], [10, 15, 25, 50, "Todos"]],
        "dom": '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>rtip',
        "columnDefs": [
          { "orderable": false, "targets": [1, 9] }, // Logo y Acciones no ordenables
          { "className": "text-center", "targets": [0, 1, 9] }, // Centrar algunas columnas
          { "className": "text-center", "targets": [5] } // Centrar nivel educativo
        ],
        "autoWidth": false // Desactivar auto-width para usar nuestros anchos personalizados
      });

      // Función para mostrar notificaciones
      window.showNotification = function(type, title, message) {
        const Toast = Swal.mixin({
          toast: true,
          position: 'top-end',
          showConfirmButton: false,
          timer: 4000,
          timerProgressBar: true,
          didOpen: (toast) => {
            toast.addEventListener('mouseenter', Swal.stopTimer)
            toast.addEventListener('mouseleave', Swal.resumeTimer)
          }
        });

        Toast.fire({
          icon: type,
          title: title,
          text: message
        });
      };

      // Función para editar configuración
      window.editConfiguracion = function(id) {
        // Obtener datos de la configuración
        $.ajax({
          url: 'controllers/configuracion/configuracion_controller.php',
          type: 'POST',
          data: { action: 'get', id: id },
          dataType: 'json',
          success: function(response) {
            if (response.success) {
              const config = response.data;
              
              // Llenar el formulario del modal
              $('#editConfiguracionId').val(config.id);
              $('#editNombreInstitucion').val(config.nombre_institucion);
              $('#editCodigoModular').val(config.codigo_modular);
              $('#editUgel').val(config.ugel);
              $('#editNivelEducativo').val(config.nivel_educativo);
              $('#editDireccion').val(config.direccion);
              $('#editTelefono').val(config.telefono);
              $('#editEmail').val(config.email);
              
              // Mostrar logo actual si existe
              if (config.logo_url) {
                $('#currentLogo').html(`<img src="${config.logo_url}" alt="Logo actual" class="img-fluid" style="max-height: 100px; border-radius: 8px; border: 1px solid #e2e8f0;">`);
              } else {
                $('#currentLogo').html('<div class="text-muted text-center p-3" style="border: 2px dashed #cbd5e1; border-radius: 8px;"><iconify-icon icon="mdi:image-off" class="fs-4 mb-2 d-block"></iconify-icon>Sin logo actual</div>');
              }
              
              // Mostrar el modal
              $('#editConfiguracionModal').modal('show');
            } else {
              showNotification('error', 'Error', response.message);
            }
          },
          error: function() {
            showNotification('error', 'Error', 'Error al obtener los datos de la configuración');
          }
        });
      };

      // Manejar envío del formulario de edición
      $('#editConfiguracionForm').on('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        formData.append('action', 'update');
        
        // Mostrar loading
        Swal.fire({
          title: 'Actualizando configuración...',
          text: 'Por favor espere un momento',
          allowOutsideClick: false,
          allowEscapeKey: false,
          showConfirmButton: false,
          didOpen: () => {
            Swal.showLoading();
          }
        });
        
        $.ajax({
          url: 'controllers/configuracion/configuracion_controller.php',
          type: 'POST',
          data: formData,
          processData: false,
          contentType: false,
          dataType: 'json',
          success: function(response) {
            Swal.close();
            
            if (response.success) {
              $('#editConfiguracionModal').modal('hide');
              showNotification('success', '¡Éxito!', 'Configuración actualizada correctamente');
              
              // Recargar la página después de un breve delay
              setTimeout(function() {
                location.reload();
              }, 1000);
            } else {
              showNotification('error', 'Error', response.message);
            }
          },
          error: function(xhr, status, error) {
            Swal.close();
            showNotification('error', 'Error', 'Error al actualizar la configuración. Por favor intente nuevamente.');
            console.error('Error details:', xhr.responseText);
          }
        });
      });

      // Mostrar mensaje de error si existe
      <?php if (isset($error_message)): ?>
        showNotification('error', 'Error de Base de Datos', '<?php echo addslashes($error_message); ?>');
      <?php endif; ?>
    });
  </script>
</body>

</html>