<?php
include 'config/bd.php';

try {
    // Obtener años académicos con información adicional
    $stmt = $pdo->prepare("
        SELECT 
            aa.id,
            aa.anio,
            aa.nombre,
            aa.fecha_inicio,
            aa.fecha_fin,
            aa.tipo_periodo,
            aa.estado,
            aa.configuracion_evaluacion,
            aa.fecha_creacion,
            DATEDIFF(aa.fecha_fin, aa.fecha_inicio) + 1 as duracion_dias,
            (SELECT COUNT(*) FROM periodos_academicos pa WHERE pa.ano_academico_id = aa.id) as total_periodos,
            (SELECT COUNT(*) FROM secciones s WHERE s.ano_academico_id = aa.id) as total_secciones,
            (SELECT COUNT(DISTINCT ma.estudiante_id) 
             FROM matriculas ma 
             INNER JOIN secciones s ON ma.seccion_id = s.id 
             WHERE s.ano_academico_id = aa.id) as total_estudiantes,
            CASE 
                WHEN aa.estado = 'activo' THEN 'Activo'
                WHEN aa.estado = 'planificado' THEN 'Planificado' 
                WHEN aa.estado = 'finalizado' THEN 'Finalizado'
                ELSE 'Desconocido'
            END as estado_descripcion
        FROM anos_academicos aa
        ORDER BY aa.anio DESC, aa.id DESC
    ");
    $stmt->execute();
    $anos_academicos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Procesar configuraciones JSON
    foreach ($anos_academicos as &$ano) {
        if (!empty($ano['configuracion_evaluacion'])) {
            $config = json_decode($ano['configuracion_evaluacion'], true);
            $ano['configuracion_evaluacion_texto'] = json_encode($config, JSON_PRETTY_PRINT);
            $ano['escala_principal'] = $config['escala_principal'] ?? 'No definida';
        } else {
            $ano['configuracion_evaluacion_texto'] = 'Sin configuración';
            $ano['escala_principal'] = 'No definida';
        }
        
        // Formatear fechas
        $ano['fecha_inicio_formatted'] = date('d/m/Y', strtotime($ano['fecha_inicio']));
        $ano['fecha_fin_formatted'] = date('d/m/Y', strtotime($ano['fecha_fin']));
        $ano['fecha_creacion_formatted'] = date('d/m/Y H:i', strtotime($ano['fecha_creacion']));
        
        // Calcular duración en meses
        $inicio = new DateTime($ano['fecha_inicio']);
        $fin = new DateTime($ano['fecha_fin']);
        $intervalo = $inicio->diff($fin);
        $ano['duracion_meses'] = $intervalo->m + ($intervalo->y * 12);
    }
    unset($ano); // Limpiar referencia

    // ============================================================================
    // ESTADÍSTICAS DEL SISTEMA
    // ============================================================================

    // Estadísticas generales
    $estadisticas = [
        'total_anos' => count($anos_academicos),
        'anos_activos' => 0,
        'anos_planificados' => 0, 
        'anos_finalizados' => 0,
        'total_estudiantes_sistema' => 0,
        'total_secciones_sistema' => 0,
        'total_periodos_sistema' => 0
    ];

    // Procesar estadísticas
    foreach ($anos_academicos as $ano) {
        switch ($ano['estado']) {
            case 'activo':
                $estadisticas['anos_activos']++;
                break;
            case 'planificado':
                $estadisticas['anos_planificados']++;
                break;
            case 'finalizado':
                $estadisticas['anos_finalizados']++;
                break;
        }
        
        $estadisticas['total_estudiantes_sistema'] += intval($ano['total_estudiantes']);
        $estadisticas['total_secciones_sistema'] += intval($ano['total_secciones']);
        $estadisticas['total_periodos_sistema'] += intval($ano['total_periodos']);
    }

    // Tipos de período más utilizados
    $stmt = $pdo->prepare("
        SELECT tipo_periodo, COUNT(*) as total 
        FROM anos_academicos 
        WHERE tipo_periodo IS NOT NULL AND tipo_periodo != ''
        GROUP BY tipo_periodo
        ORDER BY total DESC
    ");
    $stmt->execute();
    $tipos_periodo = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $anos_academicos = [];
    $estadisticas = [
        'total_anos' => 0,
        'anos_activos' => 0,
        'anos_planificados' => 0,
        'anos_finalizados' => 0,
        'total_estudiantes_sistema' => 0,
        'total_secciones_sistema' => 0,
        'total_periodos_sistema' => 0
    ];
    $tipos_periodo = [];
    $error_message = "Error al consultar los años académicos: " . $e->getMessage();
} catch (Exception $e) {
    $anos_academicos = [];
    $estadisticas = [
        'total_anos' => 0,
        'anos_activos' => 0,
        'anos_planificados' => 0,
        'anos_finalizados' => 0,
        'total_estudiantes_sistema' => 0,
        'total_secciones_sistema' => 0,
        'total_periodos_sistema' => 0
    ];
    $tipos_periodo = [];
    $error_message = "Error general: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars($page_title) ?> | Sistema Académico</title>
    
    <!-- CSS Framework -->
    <link rel="shortcut icon" type="image/png" href="assets/images/logos/favicon.png" />
    <link rel="stylesheet" href="assets/css/styles.min.css" />
    
    <!-- DataTables CSS -->
    <link href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.bootstrap5.min.css" rel="stylesheet">
    
    <!-- SweetAlert2 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11.9.0/dist/sweetalert2.min.css" rel="stylesheet">
    
    <style>
        /* ============================================================================
           ESTILOS PERSONALIZADOS PARA AÑOS ACADÉMICOS
           ============================================================================ */
        :root {
            --primary-color: #2563eb;
            --secondary-color: #64748b;
            --success-color: #059669;
            --warning-color: #d97706;
            --danger-color: #dc2626;
            --info-color: #0891b2;
            --gray-100: #f1f5f9;
            --gray-200: #e2e8f0;
            --gray-300: #cbd5e1;
            --gray-400: #94a3b8;
            --gray-500: #64748b;
            --gray-600: #475569;
            --gray-700: #334155;
            --gray-800: #1e293b;
            --gray-900: #0f172a;
            --shadow-sm: 0 1px 2px 0 rgb(0 0 0 / 0.05);
            --shadow-md: 0 4px 6px -1px rgb(0 0 0 / 0.1), 0 2px 4px -2px rgb(0 0 0 / 0.1);
            --shadow-lg: 0 10px 15px -3px rgb(0 0 0 / 0.1), 0 4px 6px -4px rgb(0 0 0 / 0.1);
        }

        /* Tarjetas de estadísticas mejoradas */
        .stats-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 1rem;
            box-shadow: var(--shadow-lg);
            transition: transform 0.2s ease, box-shadow 0.2s ease;
            border: none;
        }

        .stats-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 20px 25px -5px rgb(0 0 0 / 0.1), 0 10px 10px -5px rgb(0 0 0 / 0.04);
        }

        .stats-card.primary {
            background: linear-gradient(135deg, var(--primary-color) 0%, #3b82f6 100%);
        }

        .stats-card.success {
            background: linear-gradient(135deg, var(--success-color) 0%, #10b981 100%);
        }

        .stats-card.warning {
            background: linear-gradient(135deg, var(--warning-color) 0%, #f59e0b 100%);
        }

        .stats-card.danger {
            background: linear-gradient(135deg, var(--danger-color) 0%, #ef4444 100%);
        }

        .stats-card .stats-number {
            font-size: 2.5rem;
            font-weight: 700;
            line-height: 1;
            margin-bottom: 0.5rem;
        }

        .stats-card .stats-label {
            font-size: 0.875rem;
            opacity: 0.9;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .stats-card iconify-icon {
            font-size: 3rem;
            opacity: 0.3;
            float: right;
            margin-top: -2rem;
        }

        /* Badges mejorados */
        .badge {
            font-size: 0.75rem;
            font-weight: 600;
            padding: 0.375rem 0.75rem;
            border-radius: 6px;
            text-transform: uppercase;
            letter-spacing: 0.025em;
        }

        .badge.estado-activo {
            background-color: #dcfdf4;
            color: #065f46;
            border: 1px solid #a7f3d0;
        }

        .badge.estado-planificado {
            background-color: #fef3c7;
            color: #92400e;
            border: 1px solid #fde68a;
        }

        .badge.estado-finalizado {
            background-color: #fee2e2;
            color: #991b1b;
            border: 1px solid #fecaca;
        }

        .badge.periodo-bimestral {
            background-color: #dbeafe;
            color: #1d4ed8;
            border: 1px solid #93c5fd;
        }

        .badge.periodo-trimestral {
            background-color: #f3e8ff;
            color: #7c3aed;
            border: 1px solid #c4b5fd;
        }

        .badge.periodo-semestral {
            background-color: #ecfdf5;
            color: #047857;
            border: 1px solid #86efac;
        }

        /* Botones de acción mejorados */
        .action-buttons {
            display: flex;
            gap: 0.25rem;
            justify-content: center;
        }

        .btn-action {
            padding: 0.375rem 0.5rem;
            border-radius: 6px;
            border: none;
            font-size: 0.875rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.15s ease;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 32px;
            min-height: 32px;
        }

        .btn-action iconify-icon {
            font-size: 1rem;
        }

        .btn-view {
            background-color: var(--info-color);
            color: white;
        }

        .btn-view:hover {
            background-color: #0e7490;
            transform: translateY(-1px);
            box-shadow: var(--shadow-md);
        }

        .btn-edit {
            background-color: var(--warning-color);
            color: white;
        }

        .btn-edit:hover {
            background-color: #b45309;
            transform: translateY(-1px);
            box-shadow: var(--shadow-md);
        }

        .btn-periods {
            background-color: var(--primary-color);
            color: white;
        }

        .btn-periods:hover {
            background-color: #1d4ed8;
            transform: translateY(-1px);
            box-shadow: var(--shadow-md);
        }

        .btn-delete {
            background-color: var(--danger-color);
            color: white;
        }

        .btn-delete:hover {
            background-color: #b91c1c;
            transform: translateY(-1px);
            box-shadow: var(--shadow-md);
        }

        /* Botones de creación */
        .btn-create, .btn-outline-create {
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            font-weight: 600;
            font-size: 0.875rem;
            transition: all 0.2s ease;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            text-decoration: none;
            cursor: pointer;
        }

        .btn-create {
            background: linear-gradient(135deg, var(--success-color) 0%, #10b981 100%);
            color: white;
            border: none;
        }

        .btn-create:hover {
            background: linear-gradient(135deg, #047857 0%, #059669 100%);
            transform: translateY(-2px);
            box-shadow: var(--shadow-lg);
            color: white;
        }

        .btn-outline-create {
            background: white;
            color: var(--success-color);
            border: 2px solid var(--success-color);
        }

        .btn-outline-create:hover {
            background: var(--success-color);
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
        #anosAcademicosTable {
            width: 100% !important;
            min-width: 1400px;
        }

        /* Ajustar anchos de columnas específicas */
        #anosAcademicosTable th:nth-child(1) { width: 60px; }  /* ID */
        #anosAcademicosTable th:nth-child(2) { width: 80px; }  /* Año */
        #anosAcademicosTable th:nth-child(3) { width: 200px; } /* Nombre */
        #anosAcademicosTable th:nth-child(4) { width: 100px; } /* Período */
        #anosAcademicosTable th:nth-child(5) { width: 180px; } /* Fechas */
        #anosAcademicosTable th:nth-child(6) { width: 90px; }  /* Estado */
        #anosAcademicosTable th:nth-child(7) { width: 120px; } /* Estudiantes */
        #anosAcademicosTable th:nth-child(8) { width: 100px; } /* Períodos */
        #anosAcademicosTable th:nth-child(9) { width: 100px; } /* Secciones */
        #anosAcademicosTable th:nth-child(10) { width: 100px; } /* Duración */
        #anosAcademicosTable th:nth-child(11) { width: 140px; } /* Acciones */

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
            padding-right: 2rem;
            background-color: white;
            color: var(--gray-700);
            font-size: 0.875rem;
            margin-left: 0.5rem;
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

        /* Filtros por columna */
        #anosAcademicosTable tfoot th {
            padding: 8px !important;
            border-top: 2px solid var(--gray-200);
            background-color: var(--gray-50);
        }

        #anosAcademicosTable tfoot input,
        #anosAcademicosTable tfoot select {
            width: 100% !important;
            padding: 6px 8px;
            border: 1px solid var(--gray-300);
            border-radius: 4px;
            font-size: 0.75rem;
            background-color: white;
        }

        #anosAcademicosTable tfoot input:focus,
        #anosAcademicosTable tfoot select:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 2px rgb(37 99 235 / 0.1);
        }

        /* Controles inferiores */
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

        /* Paginador limpio */
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
            box-shadow: 0 8px 20px rgb(0 0 0 / 0.25);
        }

        /* Tarjeta principal */
        .card {
            border: none;
            border-radius: 12px;
            box-shadow: var(--shadow-sm);
            background: white;
        }

        .card-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 12px 12px 0 0 !important;
            padding: 1.5rem;
            border: none;
        }

        .card-title {
            margin: 0;
            font-size: 1.25rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .card-title iconify-icon {
            font-size: 1.5rem;
        }

        /* Responsive para móviles */
        @media (max-width: 768px) {
            .stats-card .stats-number {
                font-size: 2rem;
            }

            .stats-card iconify-icon {
                font-size: 2rem;
                margin-top: -1rem;
            }

            .dataTables_wrapper .row:first-child,
            .dataTables_wrapper .row:last-child {
                flex-direction: column;
                gap: 1rem;
            }

            .btn-create-floating {
                bottom: 1rem;
                right: 1rem;
                width: 50px;
                height: 50px;
                font-size: 1.25rem;
            }
        }

        /* Animaciones suaves */
        * {
            transition: color 0.15s ease, background-color 0.15s ease, border-color 0.15s ease;
        }

        /* Mejoras de accesibilidad */
        .btn-action:focus,
        .btn-create:focus,
        .btn-outline-create:focus {
            outline: 2px solid var(--primary-color);
            outline-offset: 2px;
        }

        /* Indicadores de carga */
        .loading {
            opacity: 0.6;
            pointer-events: none;
            position: relative;
        }

        .loading::after {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 20px;
            height: 20px;
            border: 2px solid var(--gray-300);
            border-top: 2px solid var(--primary-color);
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% { transform: translate(-50%, -50%) rotate(0deg); }
            100% { transform: translate(-50%, -50%) rotate(360deg); }
        }
    </style>
</head>

<body>
    <!-- ============================================================================
         WRAPPER PRINCIPAL
         ============================================================================ -->
    <div class="page-wrapper" id="main-wrapper" data-layout="vertical" data-navbarbg="skin6" data-sidebartype="full"
         data-sidebar-position="fixed" data-header-position="fixed">
        
        <?php include 'layouts/sidebar.php'; ?>
        
        <!-- ============================================================================
             CONTENIDO PRINCIPAL
             ============================================================================ -->
        <div class="body-wrapper">
            <?php include 'layouts/header.php'; ?>
            
            <div class="container-fluid">
                
                <!-- Breadcrumb -->
                <div class="card bg-primary-subtle shadow-none position-relative overflow-hidden mb-4">
                    <div class="card-body px-4 py-3">
                        <div class="row align-items-center">
                            <div class="col-9">
                                <h4 class="fw-semibold mb-8"><?= htmlspecialchars($page_title) ?></h4>
                                <nav aria-label="breadcrumb">
                                    <ol class="breadcrumb">
                                        <li class="breadcrumb-item">
                                            <a class="text-muted text-decoration-none" href="admin/">Panel de Control</a>
                                        </li>
                                        <li class="breadcrumb-item">
                                            <a class="text-muted text-decoration-none" href="javascript:void(0)">Gestión Académica</a>
                                        </li>
                                        <li class="breadcrumb-item active" aria-current="page">Años Académicos</li>
                                    </ol>
                                </nav>
                            </div>
                            <div class="col-3 text-center">
                                <iconify-icon icon="<?= $module_icon ?>" class="fs-10 text-primary"></iconify-icon>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Estadísticas -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="stats-card primary">
                            <div class="stats-number"><?= $estadisticas['total_anos'] ?></div>
                            <div class="stats-label">Total de Años</div>
                            <iconify-icon icon="mdi:calendar-multiple"></iconify-icon>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stats-card success">
                            <div class="stats-number"><?= $estadisticas['anos_activos'] ?></div>
                            <div class="stats-label">Años Activos</div>
                            <iconify-icon icon="mdi:calendar-check"></iconify-icon>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stats-card warning">
                            <div class="stats-number"><?= number_format($estadisticas['total_estudiantes_sistema']) ?></div>
                            <div class="stats-label">Estudiantes Total</div>
                            <iconify-icon icon="mdi:account-multiple"></iconify-icon>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stats-card danger">
                            <div class="stats-number"><?= $estadisticas['total_periodos_sistema'] ?></div>
                            <div class="stats-label">Períodos Total</div>
                            <iconify-icon icon="mdi:calendar-range"></iconify-icon>
                        </div>
                    </div>
                </div>

                <!-- Errores -->
                <?php if (isset($error_message)): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <strong>Error:</strong> <?= htmlspecialchars($error_message) ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <!-- Tarjeta principal -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title">
                            <iconify-icon icon="mdi:school-outline"></iconify-icon>
                            Gestión de Años Académicos
                        </h5>
                        <div class="d-flex gap-2 ms-auto">
                            <button class="btn btn-success btn-sm" onclick="createAnoAcademico()">
                                <iconify-icon icon="mdi:plus"></iconify-icon>
                                Nuevo Año
                            </button>
                            <button class="btn btn-outline-primary btn-sm" id="btnGenerarReportePDF">
                                <iconify-icon icon="mdi:file-pdf-box"></iconify-icon>
                                Reporte PDF
                            </button>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <?php if (!empty($anos_academicos)): ?>
                                <table id="anosAcademicosTable" class="table table-striped table-hover">
                                    <thead class="table-dark">
                                        <tr>
                                            <th>ID</th>
                                            <th>Año</th>
                                            <th>Nombre del Año Académico</th>
                                            <th>Tipo de Período</th>
                                            <th>Fechas</th>
                                            <th>Estado</th>
                                            <th>Estudiantes</th>
                                            <th>Períodos</th>
                                            <th>Secciones</th>
                                            <th>Duración</th>
                                            <th>Acciones</th>
                                        </tr>
                                    </thead>
                                    <tfoot>
                                        <tr>
                                            <th><input type="text" placeholder="Filtrar ID" class="form-control form-control-sm"></th>
                                            <th><input type="text" placeholder="Filtrar año" class="form-control form-control-sm"></th>
                                            <th><input type="text" placeholder="Filtrar nombre" class="form-control form-control-sm"></th>
                                            <th>
                                                <select class="form-select form-select-sm">
                                                    <option value="">Todos los períodos</option>
                                                    <option value="bimestral">Bimestral</option>
                                                    <option value="trimestral">Trimestral</option>
                                                    <option value="semestral">Semestral</option>
                                                </select>
                                            </th>
                                            <th><input type="text" placeholder="Filtrar fechas" class="form-control form-control-sm"></th>
                                            <th>
                                                <select class="form-select form-select-sm">
                                                    <option value="">Todos los estados</option>
                                                    <option value="activo">Activo</option>
                                                    <option value="planificado">Planificado</option>
                                                    <option value="finalizado">Finalizado</option>
                                                </select>
                                            </th>
                                            <th><input type="text" placeholder="N° estudiantes" class="form-control form-control-sm"></th>
                                            <th><input type="text" placeholder="N° períodos" class="form-control form-control-sm"></th>
                                            <th><input type="text" placeholder="N° secciones" class="form-control form-control-sm"></th>
                                            <th><input type="text" placeholder="Duración" class="form-control form-control-sm"></th>
                                            <th></th>
                                        </tr>
                                    </tfoot>
                                    <tbody>
                                        <?php foreach ($anos_academicos as $ano): ?>
                                            <tr>
                                                <td class="text-center">
                                                    <span class="badge bg-secondary"><?= $ano['id'] ?></span>
                                                </td>
                                                <td class="text-center">
                                                    <strong class="text-primary"><?= $ano['anio'] ?></strong>
                                                </td>
                                                <td>
                                                    <div>
                                                        <strong><?= htmlspecialchars($ano['nombre']) ?></strong>
                                                        <small class="d-block text-muted">
                                                            Creado: <?= $ano['fecha_creacion_formatted'] ?>
                                                        </small>
                                                    </div>
                                                </td>
                                                <td class="text-center">
                                                    <span class="badge periodo-<?= $ano['tipo_periodo'] ?>">
                                                        <?= ucfirst($ano['tipo_periodo']) ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <small>
                                                        <strong>Inicio:</strong> <?= $ano['fecha_inicio_formatted'] ?><br>
                                                        <strong>Fin:</strong> <?= $ano['fecha_fin_formatted'] ?>
                                                    </small>
                                                </td>
                                                <td class="text-center">
                                                    <span class="badge estado-<?= $ano['estado'] ?>">
                                                        <?= $ano['estado_descripcion'] ?>
                                                    </span>
                                                </td>
                                                <td class="text-center">
                                                    <span class="badge bg-info"><?= number_format($ano['total_estudiantes']) ?></span>
                                                </td>
                                                <td class="text-center">
                                                    <span class="badge bg-primary"><?= $ano['total_periodos'] ?></span>
                                                </td>
                                                <td class="text-center">
                                                    <span class="badge bg-secondary"><?= $ano['total_secciones'] ?></span>
                                                </td>
                                                <td class="text-center">
                                                    <small>
                                                        <?= $ano['duracion_meses'] ?> meses<br>
                                                        <span class="text-muted"><?= $ano['duracion_dias'] ?> días</span>
                                                    </small>
                                                </td>
                                                <td>
                                                    <div class="action-buttons">
                                                        <button class="btn-action btn-view" onclick="viewAnoAcademico(<?= $ano['id'] ?>)" title="Ver detalles">
                                                            <iconify-icon icon="mdi:eye"></iconify-icon>
                                                        </button>
                                                        <button class="btn-action btn-edit" onclick="editAnoAcademico(<?= $ano['id'] ?>)" title="Editar">
                                                            <iconify-icon icon="mdi:pencil"></iconify-icon>
                                                        </button>
                                                        <button class="btn-action btn-periods" onclick="managePeriods(<?= $ano['id'] ?>)" title="Gestionar períodos">
                                                            <iconify-icon icon="mdi:calendar-range"></iconify-icon>
                                                        </button>
                                                        <button class="btn-action btn-delete" onclick="deleteAnoAcademico(<?= $ano['id'] ?>)" title="Eliminar">
                                                            <iconify-icon icon="mdi:delete"></iconify-icon>
                                                        </button>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            <?php else: ?>
                                <div class="empty-state">
                                    <iconify-icon icon="mdi:calendar-remove-outline"></iconify-icon>
                                    <h3>No hay años académicos configurados</h3>
                                    <p>Configure años académicos para establecer los períodos lectivos del sistema educativo</p>
                                    <button class="btn btn-outline-create mt-3" onclick="createAnoAcademico()">
                                        <iconify-icon icon="mdi:plus"></iconify-icon>
                                        Crear Primer Año Académico
                                    </button>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Botón flotante para crear nuevo año -->
    <?php if (!empty($anos_academicos)): ?>
        <button class="btn-create-floating" onclick="createAnoAcademico()" title="Crear nuevo año académico">
            <iconify-icon icon="mdi:plus"></iconify-icon>
        </button>
    <?php endif; ?>

    <!-- Incluir Modales -->
    <?php include 'modals/anos_academicos/modal_create.php'; ?>
    <?php include 'modals/anos_academicos/modal_edit.php'; ?>
    <?php include 'modals/anos_academicos/modal_view.php'; ?>
    <?php include 'modals/anos_academicos/modal_periods.php'; ?>

    <!-- ============================================================================
         SCRIPTS
         ============================================================================ -->
    
    <!-- Scripts base -->
    <script src="assets/libs/jquery/dist/jquery.min.js"></script>
    <script src="assets/libs/bootstrap/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/sidebarmenu.js"></script>
    <script src="assets/js/app.min.js"></script>
    <script src="assets/libs/simplebar/dist/simplebar.js"></script>

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
    <script src="assets/js/utils/notifications.js"></script>

    <script>
        $(document).ready(function () {
            // ============================================================================
            // INICIALIZACIÓN DE DATATABLE CON FILTROS POR COLUMNA
            // ============================================================================
            const table = $('#anosAcademicosTable').DataTable({
                "language": {
                    "url": "https://cdn.datatables.net/plug-ins/1.13.7/i18n/es-ES.json"
                },
                "responsive": false, // Desactivar responsive para usar scroll horizontal
                "scrollX": true, // Habilitar scroll horizontal
                "order": [[1, "desc"]], // Ordenar por año descendente
                "pageLength": 15, // Mostrar más registros por página
                "lengthMenu": [[10, 15, 25, 50, -1], [10, 15, 25, 50, "Todos"]],
                "dom": '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>rtip',
                "columnDefs": [
                    { "orderable": false, "targets": [10] }, // Acciones no ordenables
                    { "className": "text-center", "targets": [0, 1, 3, 5, 6, 7, 8, 9] } // Centrar columnas
                ],
                "autoWidth": false, // Desactivar auto-width
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
                                    column.search(val, false, false).draw();
                                }
                            });
                        }
                    });
                }
            });

            // ============================================================================
            // FUNCIONES PRINCIPALES
            // ============================================================================

            // Función para crear nuevo año académico
            window.createAnoAcademico = function () {
                // Resetear formulario y mostrar modal
                $('#createAnoAcademicoModal form')[0]?.reset();
                $('#createAnoAcademicoModal').modal('show');
            };

            // Función para ver detalles de año académico
            window.viewAnoAcademico = function (id) {
                // Obtener datos del año académico
                $.ajax({
                    url: 'controllers/anos_academicos/anos_academicos_controller.php',
                    type: 'POST',
                    data: { action: 'get', id: id },
                    dataType: 'json',
                    success: function (response) {
                        if (response.success) {
                            // Mostrar datos en modal de vista
                            fillViewModal(response.data);
                            $('#viewAnoAcademicoModal').modal('show');
                        } else {
                            showNotification('error', 'Error', response.message);
                        }
                    },
                    error: function () {
                        showNotification('error', 'Error', 'Error al obtener los datos del año académico');
                    }
                });
            };

            // Función para editar año académico
            window.editAnoAcademico = function (id) {
                // Obtener datos del año académico
                $.ajax({
                    url: 'controllers/anos_academicos/anos_academicos_controller.php',
                    type: 'POST',
                    data: { action: 'get', id: id },
                    dataType: 'json',
                    success: function (response) {
                        if (response.success) {
                            // Llenar formulario de edición
                            fillEditModal(response.data);
                            $('#editAnoAcademicoModal').modal('show');
                        } else {
                            showNotification('error', 'Error', response.message);
                        }
                    },
                    error: function () {
                        showNotification('error', 'Error', 'Error al obtener los datos del año académico');
                    }
                });
            };

            // Función para gestionar períodos
            window.managePeriods = function (id) {
                // Cargar modal de gestión de períodos
                $.ajax({
                    url: 'controllers/anos_academicos/periodos_controller.php',
                    type: 'POST',
                    data: { action: 'get_by_ano', ano_academico_id: id },
                    dataType: 'json',
                    success: function (response) {
                        if (response.success) {
                            fillPeriodsModal(id, response.data);
                            $('#managePeriodsModal').modal('show');
                        } else {
                            showNotification('error', 'Error', response.message);
                        }
                    },
                    error: function () {
                        showNotification('error', 'Error', 'Error al cargar los períodos académicos');
                    }
                });
            };

            // Función para eliminar año académico
            window.deleteAnoAcademico = function (id) {
                Swal.fire({
                    title: '¿Está seguro?',
                    html: 'El año académico será eliminado permanentemente.<br><strong>Esta acción no se puede deshacer.</strong>',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Sí, eliminar',
                    cancelButtonText: 'Cancelar',
                    confirmButtonColor: '#dc2626',
                    reverseButtons: true
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.ajax({
                            url: 'controllers/anos_academicos/anos_academicos_controller.php',
                            type: 'POST',
                            data: { action: 'delete', id: id },
                            dataType: 'json',
                            success: function (response) {
                                if (response.success) {
                                    showNotification('success', 'Año Eliminado', response.message || 'El año académico fue eliminado correctamente.');
                                    // Recargar la tabla
                                    $('#anosAcademicosTable').DataTable().ajax.reload(null, false);
                                    // O recargar la página si no usas ajax en DataTable
                                    setTimeout(function () { location.reload(); }, 800);
                                } else {
                                    showNotification('error', 'Error', response.message || 'No se pudo eliminar el año académico.');
                                }
                            },
                            error: function () {
                                showNotification('error', 'Error', 'Error al intentar eliminar el año académico.');
                            }
                        });
                    }
                });
            };

            // ============================================================================
            // GENERACIÓN DE REPORTES PDF
            // ============================================================================
            $('#btnGenerarReportePDF').on('click', function () {
                var table = $('#anosAcademicosTable').DataTable();
                var filtros = {};
                var hayFiltros = false;

                // Recopilar filtros activos del DataTable
                $('#anosAcademicosTable tfoot th').each(function (index) {
                    var input = $(this).find('input');
                    var select = $(this).find('select');
                    var columnName = $('#anosAcademicosTable thead th').eq(index).text().trim();

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
                    'Procesando años académicos...'
                );

                // Construir URL con parámetros de filtro
                var url = 'reports/anos_academicos_pdf_report.php';
                var params = new URLSearchParams(filtros);

                if (params.toString()) {
                    url += '?' + params.toString();
                }

                // Abrir ventana nueva para el PDF
                var ventanaPDF = window.open(url, '_blank');

                // Cerrar notificación después de un tiempo
                setTimeout(function () {
                    Swal.close();
                    if (ventanaPDF) {
                        showNotification('success', 'Reporte Generado', 'El reporte PDF se ha generado correctamente.');
                    } else {
                        showNotification('error', 'Error', 'No se pudo abrir la ventana del reporte. Verifique si está bloqueando ventanas emergentes.');
                    }
                }, 2000);
            });

            // ============================================================================
            // FUNCIONES AUXILIARES PARA MODALES
            // ============================================================================

            // Llenar modal de vista con datos
            window.fillViewModal = function (data) {
                // Implementar lógica para llenar modal de vista
                console.log('Datos para modal de vista:', data);
            };

            // Llenar modal de edición con datos
            window.fillEditModal = function (data) {
                // Implementar lógica para llenar modal de edición
                console.log('Datos para modal de edición:', data);
            };

            // Llenar modal de períodos con datos
            window.fillPeriodsModal = function (anoId, periods) {
                // Implementar lógica para llenar modal de períodos
                console.log('Datos para modal de períodos:', anoId, periods);
            };

            // ============================================================================
            // MANEJO DE ERRORES GLOBALES
            // ============================================================================
            $(document).ajaxError(function (event, jqXHR, ajaxSettings, thrownError) {
                if (jqXHR.status === 403) {
                    showNotification('error', 'Sin Permisos', 'No tiene permisos para realizar esta acción.');
                } else if (jqXHR.status === 500) {
                    showNotification('error', 'Error del Servidor', 'Error interno del servidor. Contacte al administrador.');
                } else if (jqXHR.status === 404) {
                    showNotification('error', 'No Encontrado', 'El recurso solicitado no fue encontrado.');
                }
            });

            // ============================================================================
            // CONFIRMACIÓN ANTES DE SALIR CON CAMBIOS SIN GUARDAR
            // ============================================================================
            var hasUnsavedChanges = false;

            $('form').on('change', 'input, select, textarea', function () {
                hasUnsavedChanges = true;
            });

            $('form').on('submit', function () {
                hasUnsavedChanges = false;
            });

            $(window).on('beforeunload', function () {
                if (hasUnsavedChanges) {
                    return 'Tiene cambios sin guardar. ¿Está seguro de que desea salir?';
                }
            });
        });
    </script>
</body>
</html>