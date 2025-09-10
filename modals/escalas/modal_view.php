<!-- modals/escalas/modal_view.php -->
<div class="modal fade" id="viewEscalaModal" tabindex="-1" aria-labelledby="viewEscalaModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-xl">
    <div class="modal-content">
      <div class="modal-header bg-light">
        <h5 class="modal-title" id="viewEscalaModalLabel">
          <iconify-icon icon="mdi:eye" class="me-2"></iconify-icon>
          Detalles de la Escala de Calificación
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      
      <div class="modal-body">
        <!-- Información General -->
        <div class="row mb-4">
          <div class="col-12">
            <div class="card border-0 bg-light">
              <div class="card-header bg-transparent border-0">
                <h6 class="card-title mb-0">
                  <iconify-icon icon="mdi:information-outline" class="me-2"></iconify-icon>
                  Información General
                </h6>
              </div>
              <div class="card-body">
                <div class="row">
                  <div class="col-md-6">
                    <div class="mb-3">
                      <label class="form-label fw-semibold text-muted">ID de Escala</label>
                      <div class="form-control-plaintext fw-bold" id="viewEscalaId">-</div>
                    </div>
                  </div>
                  <div class="col-md-6">
                    <div class="mb-3">
                      <label class="form-label fw-semibold text-muted">Fecha de Creación</label>
                      <div class="form-control-plaintext" id="viewFechaCreacion">-</div>
                    </div>
                  </div>
                  <div class="col-md-6">
                    <div class="mb-3">
                      <label class="form-label fw-semibold text-muted">Año Académico</label>
                      <div class="form-control-plaintext" id="viewAnoAcademico">
                        <span class="badge bg-primary" id="viewAnoAcademicoBadge">-</span>
                      </div>
                    </div>
                  </div>
                  <div class="col-md-6">
                    <div class="mb-3">
                      <label class="form-label fw-semibold text-muted">Nivel Educativo</label>
                      <div class="form-control-plaintext" id="viewNivelEducativo">
                        <span class="badge" id="viewNivelEducativoBadge">-</span>
                      </div>
                    </div>
                  </div>
                  <div class="col-md-6">
                    <div class="mb-3">
                      <label class="form-label fw-semibold text-muted">Tipo de Escala</label>
                      <div class="form-control-plaintext" id="viewTipoEscala">
                        <span class="badge" id="viewTipoEscalaBadge">-</span>
                      </div>
                    </div>
                  </div>
                  <div class="col-md-6">
                    <div class="mb-3">
                      <label class="form-label fw-semibold text-muted">Estado de Configuración</label>
                      <div class="form-control-plaintext" id="viewEstadoConfiguracion">
                        <span class="badge" id="viewEstadoConfiguracionBadge">-</span>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Configuración de Escalas -->
        <div class="row mb-4">
          <div class="col-12">
            <div class="card border-0 bg-light">
              <div class="card-header bg-transparent border-0">
                <h6 class="card-title mb-0">
                  <iconify-icon icon="mdi:star-four-points" class="me-2"></iconify-icon>
                  Configuración de Escalas
                </h6>
              </div>
              <div class="card-body">
                <div id="viewConfiguracionContent">
                  <div class="text-center text-muted py-4">
                    <iconify-icon icon="mdi:loading" class="fs-2 mb-2"></iconify-icon>
                    <p>Cargando configuración...</p>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Estadísticas de Uso -->
        <div class="row mb-4">
          <div class="col-12">
            <div class="card border-0 bg-light">
              <div class="card-header bg-transparent border-0">
                <h6 class="card-title mb-0">
                  <iconify-icon icon="mdi:chart-bar" class="me-2"></iconify-icon>
                  Estadísticas de Uso
                </h6>
              </div>
              <div class="card-body">
                <div class="row" id="viewEstadisticasContent">
                  <div class="col-md-3">
                    <div class="text-center p-3 border rounded">
                      <iconify-icon icon="mdi:clipboard-check" class="fs-1 text-primary mb-2"></iconify-icon>
                      <h5 class="mb-1" id="viewTotalCalificaciones">-</h5>
                      <small class="text-muted">Calificaciones Registradas</small>
                    </div>
                  </div>
                  <div class="col-md-3">
                    <div class="text-center p-3 border rounded">
                      <iconify-icon icon="mdi:account-group" class="fs-1 text-success mb-2"></iconify-icon>
                      <h5 class="mb-1" id="viewTotalEstudiantes">-</h5>
                      <small class="text-muted">Estudiantes Evaluados</small>
                    </div>
                  </div>
                  <div class="col-md-3">
                    <div class="text-center p-3 border rounded">
                      <iconify-icon icon="mdi:school" class="fs-1 text-warning mb-2"></iconify-icon>
                      <h5 class="mb-1" id="viewTotalCursos">-</h5>
                      <small class="text-muted">Cursos que la Usan</small>
                    </div>
                  </div>
                  <div class="col-md-3">
                    <div class="text-center p-3 border rounded">
                      <iconify-icon icon="mdi:calendar-today" class="fs-1 text-info mb-2"></iconify-icon>
                      <h5 class="mb-1" id="viewUltimoUso">-</h5>
                      <small class="text-muted">Último Uso</small>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Distribución de Calificaciones -->
        <div class="row mb-4" id="viewDistribucionSection" style="display: none;">
          <div class="col-12">
            <div class="card border-0 bg-light">
              <div class="card-header bg-transparent border-0">
                <h6 class="card-title mb-0">
                  <iconify-icon icon="mdi:chart-pie" class="me-2"></iconify-icon>
                  Distribución de Calificaciones
                </h6>
              </div>
              <div class="card-body">
                <div class="row" id="viewDistribucionContent">
                  <!-- Se llenará dinámicamente -->
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Cursos que Utilizan esta Escala -->
        <div class="row mb-4" id="viewCursosSection" style="display: none;">
          <div class="col-12">
            <div class="card border-0 bg-light">
              <div class="card-header bg-transparent border-0">
                <h6 class="card-title mb-0">
                  <iconify-icon icon="mdi:book-open-variant" class="me-2"></iconify-icon>
                  Cursos que Utilizan esta Escala
                </h6>
              </div>
              <div class="card-body">
                <div id="viewCursosContent">
                  <!-- Se llenará dinámicamente -->
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Información Técnica -->
        <div class="row">
          <div class="col-12">
            <div class="card border-0 bg-light">
              <div class="card-header bg-transparent border-0">
                <h6 class="card-title mb-0">
                  <iconify-icon icon="mdi:code-json" class="me-2"></iconify-icon>
                  Información Técnica
                </h6>
              </div>
              <div class="card-body">
                <div class="row">
                  <div class="col-md-6">
                    <div class="mb-3">
                      <label class="form-label fw-semibold text-muted">Configuración JSON</label>
                      <div class="border rounded p-3 bg-white">
                        <pre id="viewConfiguracionJSON" class="mb-0" style="font-size: 0.875rem; max-height: 200px; overflow-y: auto;"></pre>
                      </div>
                    </div>
                  </div>
                  <div class="col-md-6">
                    <div class="mb-3">
                      <label class="form-label fw-semibold text-muted">Metadatos</label>
                      <div class="border rounded p-3 bg-white">
                        <div class="row text-sm">
                          <div class="col-12 mb-2">
                            <strong>Campos configurados:</strong> <span id="viewCamposConfigurados">-</span>
                          </div>
                          <div class="col-12 mb-2">
                            <strong>Validación:</strong> <span id="viewValidacion">-</span>
                          </div>
                          <div class="col-12 mb-2">
                            <strong>Compatibilidad:</strong> <span id="viewCompatibilidad">-</span>
                          </div>
                          <div class="col-12">
                            <strong>Tamaño JSON:</strong> <span id="viewTamanoJSON">-</span>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
      
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
          <iconify-icon icon="mdi:close"></iconify-icon>
          Cerrar
        </button>
        <button type="button" class="btn btn-outline-primary" id="viewEditBtn">
          <iconify-icon icon="mdi:pencil"></iconify-icon>
          Editar Escala
        </button>
        <button type="button" class="btn btn-outline-success" id="viewConfigureBtn">
          <iconify-icon icon="mdi:cog"></iconify-icon>
          Configurar Escalas
        </button>
        <button type="button" class="btn btn-primary" id="viewExportBtn">
          <iconify-icon icon="mdi:download"></iconify-icon>
          Exportar Configuración
        </button>
      </div>
    </div>
  </div>
</div>

<script>
$(document).ready(function() {
  // Variable para almacenar los datos de la escala
  let currentEscalaData = null;

  // Función para llenar el modal con datos de la escala
  window.fillViewModal = function(data) {
    currentEscalaData = data;
    
    // Información básica
    $('#viewEscalaId').text(data.id || '-');
    $('#viewFechaCreacion').text(formatViewDate(data.fecha_creacion));
    
    // Año académico
    $('#viewAnoAcademicoBadge').text(data.ano_academico_nombre || 'Sin especificar');
    if (data.ano_estado === 'activo') {
      $('#viewAnoAcademicoBadge').removeClass().addClass('badge bg-success');
    } else {
      $('#viewAnoAcademicoBadge').removeClass().addClass('badge bg-secondary');
    }
    
    // Nivel educativo
    const nivelTexto = formatNivelEducativo(data.nivel_educativo);
    $('#viewNivelEducativoBadge').text(nivelTexto);
    $('#viewNivelEducativoBadge').removeClass().addClass(`badge ${getNivelBadgeClass(data.nivel_educativo)}`);
    
    // Tipo de escala
    const tipoTexto = formatTipoEscala(data.tipo_escala);
    $('#viewTipoEscalaBadge').text(tipoTexto);
    $('#viewTipoEscalaBadge').removeClass().addClass(`badge ${getTipoBadgeClass(data.tipo_escala)}`);
    
    // Procesar configuración
    processConfiguracion(data.configuracion, data.tipo_escala);
    
    // Cargar estadísticas
    loadEstadisticas(data.id);
    
    // Configurar botones
    $('#viewEditBtn').off('click').on('click', function() {
      $('#viewEscalaModal').modal('hide');
      editEscala(data.id);
    });
    
    $('#viewConfigureBtn').off('click').on('click', function() {
      $('#viewEscalaModal').modal('hide');
      configureEscala(data.id);
    });
    
    $('#viewExportBtn').off('click').on('click', function() {
      exportarConfiguracion(data);
    });
  };

  function formatViewDate(dateString) {
    if (!dateString) return '-';
    try {
      const date = new Date(dateString);
      return date.toLocaleDateString('es-PE', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
      });
    } catch (e) {
      return '-';
    }
  }

  function formatNivelEducativo(nivel) {
    const niveles = {
      'inicial': 'Inicial (3-5 años)',
      'primaria': 'Primaria (1° - 6°)',
      'secundaria': 'Secundaria (1° - 5°)'
    };
    return niveles[nivel] || 'No especificado';
  }

  function formatTipoEscala(tipo) {
    const tipos = {
      'literal': 'Literal (A, B, C, D)',
      'numerico': 'Numérico (0-20)',
      'descriptivo': 'Descriptivo'
    };
    return tipos[tipo] || 'No especificado';
  }

  function getNivelBadgeClass(nivel) {
    const clases = {
      'inicial': 'bg-info',
      'primaria': 'bg-primary',
      'secundaria': 'bg-success'
    };
    return clases[nivel] || 'bg-secondary';
  }

  function getTipoBadgeClass(tipo) {
    const clases = {
      'literal': 'bg-primary',
      'numerico': 'bg-success',
      'descriptivo': 'bg-warning'
    };
    return clases[tipo] || 'bg-secondary';
  }

  function processConfiguracion(configuracion, tipo) {
    let config = null;
    let estadoTexto = 'Sin configurar';
    let estadoClase = 'bg-warning';
    
    // Procesar JSON
    if (configuracion) {
      try {
        config = typeof configuracion === 'string' ? JSON.parse(configuracion) : configuracion;
        if (config && Object.keys(config).length > 0) {
          estadoTexto = 'Configuración completa';
          estadoClase = 'bg-success';
        }
      } catch (e) {
        console.error('Error al parsear configuración:', e);
        estadoTexto = 'Configuración inválida';
        estadoClase = 'bg-danger';
      }
    }
    
    // Actualizar estado
    $('#viewEstadoConfiguracionBadge').text(estadoTexto).removeClass().addClass(`badge ${estadoClase}`);
    
    // Mostrar configuración visual
    mostrarConfiguracionVisual(config, tipo);
    
    // Mostrar JSON técnico
    mostrarConfiguracionTecnica(config);
  }

  function mostrarConfiguracionVisual(config, tipo) {
    const container = $('#viewConfiguracionContent');
    
    if (!config || Object.keys(config).length === 0) {
      container.html(`
        <div class="text-center text-muted py-4">
          <iconify-icon icon="mdi:alert-circle-outline" class="fs-2 mb-2"></iconify-icon>
          <p>No hay configuración establecida para esta escala</p>
          <button class="btn btn-outline-primary btn-sm" onclick="configureEscala(${currentEscalaData?.id})">
            <iconify-icon icon="mdi:cog"></iconify-icon>
            Configurar Ahora
          </button>
        </div>
      `);
      return;
    }

    let configHtml = '';
    
    switch (tipo) {
      case 'literal':
        configHtml = `
          <div class="row">
            <div class="col-md-6 mb-3">
              <div class="card border-success">
                <div class="card-header bg-success text-white">
                  <h6 class="mb-0">
                    <iconify-icon icon="mdi:star"></iconify-icon>
                    Escala A - ${config.A?.descripcion || 'Logro destacado'}
                  </h6>
                </div>
                <div class="card-body">
                  <div class="d-flex justify-content-between align-items-center">
                    <span class="badge bg-success fs-6">${config.A?.rango_min || 0} - ${config.A?.rango_max || 0}</span>
                    <small class="text-muted">Puntos</small>
                  </div>
                </div>
              </div>
            </div>
            <div class="col-md-6 mb-3">
              <div class="card border-primary">
                <div class="card-header bg-primary text-white">
                  <h6 class="mb-0">
                    <iconify-icon icon="mdi:thumb-up"></iconify-icon>
                    Escala B - ${config.B?.descripcion || 'Logro esperado'}
                  </h6>
                </div>
                <div class="card-body">
                  <div class="d-flex justify-content-between align-items-center">
                    <span class="badge bg-primary fs-6">${config.B?.rango_min || 0} - ${config.B?.rango_max || 0}</span>
                    <small class="text-muted">Puntos</small>
                  </div>
                </div>
              </div>
            </div>
            <div class="col-md-6 mb-3">
              <div class="card border-warning">
                <div class="card-header bg-warning text-dark">
                  <h6 class="mb-0">
                    <iconify-icon icon="mdi:clock"></iconify-icon>
                    Escala C - ${config.C?.descripcion || 'En proceso'}
                  </h6>
                </div>
                <div class="card-body">
                  <div class="d-flex justify-content-between align-items-center">
                    <span class="badge bg-warning fs-6">${config.C?.rango_min || 0} - ${config.C?.rango_max || 0}</span>
                    <small class="text-muted">Puntos</small>
                  </div>
                </div>
              </div>
            </div>
            <div class="col-md-6 mb-3">
              <div class="card border-danger">
                <div class="card-header bg-danger text-white">
                  <h6 class="mb-0">
                    <iconify-icon icon="mdi:alert"></iconify-icon>
                    Escala D - ${config.D?.descripcion || 'En inicio'}
                  </h6>
                </div>
                <div class="card-body">
                  <div class="d-flex justify-content-between align-items-center">
                    <span class="badge bg-danger fs-6">${config.D?.rango_min || 0} - ${config.D?.rango_max || 0}</span>
                    <small class="text-muted">Puntos</small>
                  </div>
                </div>
              </div>
            </div>
          </div>
        `;
        break;
        
      case 'numerico':
        configHtml = `
          <div class="row">
            <div class="col-md-4">
              <div class="text-center p-3 border rounded bg-white">
                <iconify-icon icon="mdi:numeric" class="fs-1 text-primary mb-2"></iconify-icon>
                <h5 class="mb-1">${config.min || 0} - ${config.max || 20}</h5>
                <small class="text-muted">Rango de Valores</small>
              </div>
            </div>
            <div class="col-md-4">
              <div class="text-center p-3 border rounded bg-white">
                <iconify-icon icon="mdi:decimal" class="fs-1 text-success mb-2"></iconify-icon>
                <h5 class="mb-1">${config.decimales || 0}</h5>
                <small class="text-muted">Decimales Permitidos</small>
              </div>
            </div>
            <div class="col-md-4">
              <div class="text-center p-3 border rounded bg-white">
                <iconify-icon icon="mdi:calculator" class="fs-1 text-warning mb-2"></iconify-icon>
                <h5 class="mb-1">${(config.max || 20) - (config.min || 0) + 1}</h5>
                <small class="text-muted">Valores Posibles</small>
              </div>
            </div>
          </div>
        `;
        break;
        
      case 'descriptivo':
        configHtml = `
          <div class="row">
            <div class="col-md-6 mb-3">
              <div class="card border-success">
                <div class="card-body text-center">
                  <iconify-icon icon="mdi:star" class="fs-1 text-success mb-2"></iconify-icon>
                  <h6 class="fw-bold">${config.superior || 'Competente'}</h6>
                  <small class="text-muted">Nivel Superior</small>
                </div>
              </div>
            </div>
            <div class="col-md-6 mb-3">
              <div class="card border-primary">
                <div class="card-body text-center">
                  <iconify-icon icon="mdi:thumb-up" class="fs-1 text-primary mb-2"></iconify-icon>
                  <h6 class="fw-bold">${config.satisfactorio || 'Satisfactorio'}</h6>
                  <small class="text-muted">Nivel Satisfactorio</small>
                </div>
              </div>
            </div>
            <div class="col-md-6 mb-3">
              <div class="card border-warning">
                <div class="card-body text-center">
                  <iconify-icon icon="mdi:clock" class="fs-1 text-warning mb-2"></iconify-icon>
                  <h6 class="fw-bold">${config.desarrollo || 'En desarrollo'}</h6>
                  <small class="text-muted">En Desarrollo</small>
                </div>
              </div>
            </div>
            <div class="col-md-6 mb-3">
              <div class="card border-danger">
                <div class="card-body text-center">
                  <iconify-icon icon="mdi:alert" class="fs-1 text-danger mb-2"></iconify-icon>
                  <h6 class="fw-bold">${config.inicial || 'Inicial'}</h6>
                  <small class="text-muted">Nivel Inicial</small>
                </div>
              </div>
            </div>
          </div>
        `;
        break;
    }
    
    container.html(configHtml);
  }

  function mostrarConfiguracionTecnica(config) {
    // JSON
    const jsonText = config ? JSON.stringify(config, null, 2) : 'No configurado';
    $('#viewConfiguracionJSON').text(jsonText);
    
    // Metadatos
    if (config) {
      $('#viewCamposConfigurados').text(Object.keys(config).length);
      $('#viewValidacion').html('<span class="text-success"><iconify-icon icon="mdi:check"></iconify-icon> Válida</span>');
      $('#viewCompatibilidad').html('<span class="text-success"><iconify-icon icon="mdi:check"></iconify-icon> Compatible</span>');
      $('#viewTamanoJSON').text(`${JSON.stringify(config).length} bytes`);
    } else {
      $('#viewCamposConfigurados').text('0');
      $('#viewValidacion').html('<span class="text-warning"><iconify-icon icon="mdi:alert"></iconify-icon> Pendiente</span>');
      $('#viewCompatibilidad').html('<span class="text-muted">N/A</span>');
      $('#viewTamanoJSON').text('0 bytes');
    }
  }

  function loadEstadisticas(escalaId) {
    // Simular carga de estadísticas (aquí iría la llamada AJAX real)
    setTimeout(() => {
      // Datos de ejemplo - reemplazar con datos reales
      $('#viewTotalCalificaciones').text('1,247');
      $('#viewTotalEstudiantes').text('156');
      $('#viewTotalCursos').text('12');
      $('#viewUltimoUso').text('Hoy');
      
      // Mostrar secciones adicionales si hay datos
      $('#viewDistribucionSection').show();
      $('#viewCursosSection').show();
      
      mostrarDistribucionCalificaciones();
      mostrarCursosQueUsan();
    }, 1000);
  }

  function mostrarDistribucionCalificaciones() {
    // Datos de ejemplo - reemplazar con datos reales
    const distribucion = {
      'A': { cantidad: 234, porcentaje: 18.8, color: 'bg-success' },
      'B': { cantidad: 567, porcentaje: 45.5, color: 'bg-primary' },
      'C': { cantidad: 346, porcentaje: 27.7, color: 'bg-warning' },
      'D': { cantidad: 100, porcentaje: 8.0, color: 'bg-danger' }
    };
    
    let distribucionHtml = '';
    Object.entries(distribucion).forEach(([escala, datos]) => {
      distribucionHtml += `
        <div class="col-md-3 mb-3">
          <div class="card border-0">
            <div class="card-body text-center">
              <div class="progress mb-2" style="height: 8px;">
                <div class="progress-bar ${datos.color}" style="width: ${datos.porcentaje}%"></div>
              </div>
              <h5 class="mb-1">${datos.cantidad}</h5>
              <small class="text-muted">Escala ${escala} (${datos.porcentaje}%)</small>
            </div>
          </div>
        </div>
      `;
    });
    
    $('#viewDistribucionContent').html(distribucionHtml);
  }

  function mostrarCursosQueUsan() {
    // Datos de ejemplo - reemplazar con datos reales
    const cursos = [
      { nombre: 'Comunicación 1°A', docente: 'María Rodríguez', estudiantes: 28 },
      { nombre: 'Matemática 1°A', docente: 'Carlos Mendoza', estudiantes: 28 },
      { nombre: 'Ciencia y Tecnología 1°A', docente: 'Ana Torres', estudiantes: 28 },
      { nombre: 'Comunicación 1°B', docente: 'María Rodríguez', estudiantes: 26 }
    ];
    
    let cursosHtml = '<div class="table-responsive"><table class="table table-sm"><thead><tr><th>Curso</th><th>Docente</th><th>Estudiantes</th></tr></thead><tbody>';
    
    cursos.forEach(curso => {
      cursosHtml += `
        <tr>
          <td><strong>${curso.nombre}</strong></td>
          <td>${curso.docente}</td>
          <td><span class="badge bg-primary">${curso.estudiantes}</span></td>
        </tr>
      `;
    });
    
    cursosHtml += '</tbody></table></div>';
    $('#viewCursosContent').html(cursosHtml);
  }

  function exportarConfiguracion(data) {
    const exportData = {
      escala: {
        id: data.id,
        ano_academico: data.ano_academico_nombre,
        nivel_educativo: data.nivel_educativo,
        tipo_escala: data.tipo_escala,
        fecha_creacion: data.fecha_creacion
      },
      configuracion: data.configuracion ? JSON.parse(data.configuracion) : null,
      metadata: {
        exportado_en: new Date().toISOString(),
        version: '1.0'
      }
    };
    
    const blob = new Blob([JSON.stringify(exportData, null, 2)], { type: 'application/json' });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = `escala_calificacion_${data.id}_${data.nivel_educativo}.json`;
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
    URL.revokeObjectURL(url);
    
    showNotification('success', 'Exportación exitosa', 'La configuración se ha descargado correctamente.');
  }

  // Limpiar modal al cerrar
  $('#viewEscalaModal').on('hidden.bs.modal', function() {
    currentEscalaData = null;
    $('#viewDistribucionSection').hide();
    $('#viewCursosSection').hide();
  });
});
</script>