<!-- modals/escalas/modal_edit.php -->
<div class="modal fade" id="editEscalaModal" tabindex="-1" aria-labelledby="editEscalaModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="editEscalaModalLabel">
          <iconify-icon icon="mdi:pencil" class="me-2"></iconify-icon>
          Editar Escala de Calificación
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      
      <form id="editEscalaForm">
        <input type="hidden" id="editEscalaId" name="id">
        
        <div class="modal-body">
          <div class="row">
            
            <!-- Información Básica -->
            <div class="col-12 mb-4">
              <h6 class="text-muted text-uppercase fw-bold mb-3">
                <iconify-icon icon="mdi:information-outline" class="me-1"></iconify-icon>
                Información Básica
              </h6>
            </div>

            <!-- Año Académico -->
            <div class="col-md-6 mb-3">
              <label for="editAnoAcademico" class="form-label fw-semibold">
                <iconify-icon icon="mdi:calendar" class="me-1"></iconify-icon>
                Año Académico *
              </label>
              <select class="form-select" id="editAnoAcademico" name="ano_academico_id" required>
                <option value="">Seleccionar año académico...</option>
                <!-- Se llenará dinámicamente -->
              </select>
              <div class="invalid-feedback">
                El año académico es requerido
              </div>
            </div>

            <!-- Nivel Educativo -->
            <div class="col-md-6 mb-3">
              <label for="editNivelEducativo" class="form-label fw-semibold">
                <iconify-icon icon="mdi:school" class="me-1"></iconify-icon>
                Nivel Educativo *
              </label>
              <select class="form-select" id="editNivelEducativo" name="nivel_educativo" required>
                <option value="">Seleccionar nivel...</option>
                <option value="inicial">Inicial (3-5 años)</option>
                <option value="primaria">Primaria (1° - 6°)</option>
                <option value="secundaria">Secundaria (1° - 5°)</option>
              </select>
              <div class="invalid-feedback">
                El nivel educativo es requerido
              </div>
            </div>

            <!-- Tipo de Escala -->
            <div class="col-12 mb-3">
              <label for="editTipoEscala" class="form-label fw-semibold">
                <iconify-icon icon="mdi:format-list-numbered" class="me-1"></iconify-icon>
                Tipo de Escala *
              </label>
              <select class="form-select" id="editTipoEscala" name="tipo_escala" required>
                <option value="">Seleccionar tipo de escala...</option>
                <option value="literal">Literal (A, B, C, D) - Recomendado para Inicial y Primaria</option>
                <option value="numerico">Numérico (0-20) - Para evaluaciones específicas</option>
                <option value="descriptivo">Descriptivo - Para competencias específicas</option>
              </select>
              <div class="form-text">
                <strong>Nota:</strong> Cambiar el tipo de escala reiniciará la configuración actual.
              </div>
              <div class="invalid-feedback">
                El tipo de escala es requerido
              </div>
            </div>

            <!-- Vista previa del tipo seleccionado -->
            <div class="col-12 mb-4">
              <div id="editPreviewEscala" class="d-none">
                <div class="alert alert-info">
                  <h6 class="alert-heading">
                    <iconify-icon icon="mdi:eye" class="me-1"></iconify-icon>
                    Vista Previa de la Escala
                  </h6>
                  <div id="editPreviewContent"></div>
                </div>
              </div>
            </div>

            <!-- Configuración Actual -->
            <div class="col-12 mb-4">
              <h6 class="text-muted text-uppercase fw-bold mb-3">
                <iconify-icon icon="mdi:cog" class="me-1"></iconify-icon>
                Configuración Actual
              </h6>
            </div>

            <!-- Estado de la configuración -->
            <div class="col-12 mb-3">
              <div class="alert alert-light border" id="editConfiguracionEstado">
                <div class="d-flex justify-content-between align-items-center">
                  <div>
                    <h6 class="alert-heading mb-1">Estado de Configuración</h6>
                    <small class="text-muted" id="editConfiguracionTexto">Cargando información...</small>
                  </div>
                  <button type="button" class="btn btn-outline-primary btn-sm" id="editConfigurarBtn">
                    <iconify-icon icon="mdi:cog"></iconify-icon>
                    Configurar Escalas
                  </button>
                </div>
              </div>
            </div>

            <!-- Configuración Manual (Editable) -->
            <div class="col-12" id="editConfiguracionManual">
              <div class="border rounded p-3 bg-light">
                <div class="d-flex justify-content-between align-items-center mb-3">
                  <h6 class="fw-bold mb-0">Configuración de Escalas</h6>
                  <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" id="editModoEdicion">
                    <label class="form-check-label" for="editModoEdicion">
                      Modo edición
                    </label>
                  </div>
                </div>
                <div class="row" id="editEscalasConfiguracion">
                  <!-- Se llenará dinámicamente según el tipo y configuración actual -->
                </div>
              </div>
            </div>

            <!-- Validaciones -->
            <div class="col-12 mt-3">
              <div class="alert alert-warning d-none" id="editWarningMessage">
                <iconify-icon icon="mdi:alert" class="me-2"></iconify-icon>
                <span id="editWarningText"></span>
              </div>
              
              <div class="alert alert-info d-none" id="editInfoMessage">
                <iconify-icon icon="mdi:information" class="me-2"></iconify-icon>
                <span id="editInfoText"></span>
              </div>
            </div>

            <!-- Información adicional -->
            <div class="col-12 mt-3">
              <div class="card border-0 bg-light">
                <div class="card-body p-3">
                  <h6 class="card-title">
                    <iconify-icon icon="mdi:information-outline" class="me-1"></iconify-icon>
                    Información Adicional
                  </h6>
                  <div class="row text-sm">
                    <div class="col-md-6">
                      <strong>Fecha de creación:</strong> <span id="editFechaCreacion">-</span>
                    </div>
                    <div class="col-md-6">
                      <strong>Configuraciones realizadas:</strong> <span id="editConfiguracionesCount">-</span>
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
            Cancelar
          </button>
          <button type="button" class="btn btn-outline-primary" id="editResetConfigBtn">
            <iconify-icon icon="mdi:refresh"></iconify-icon>
            Resetear Configuración
          </button>
          <button type="submit" class="btn btn-primary" id="editEscalaBtn">
            <span id="editBtnContent">
              <iconify-icon icon="mdi:content-save"></iconify-icon>
              Actualizar Escala
            </span>
            <span id="editBtnLoading" class="d-none">
              <div class="spinner-border spinner-border-sm me-2" role="status"></div>
              Actualizando...
            </span>
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
$(document).ready(function() {
  // Variable para almacenar datos originales
  let originalEscalaData = null;

  // Cargar años académicos al abrir el modal
  $('#editEscalaModal').on('show.bs.modal', function() {
    loadEditAnosAcademicos();
  });

  // Función para llenar el modal con datos de la escala
  window.fillEditModal = function(data) {
    originalEscalaData = data;
    
    // Llenar campos básicos
    $('#editEscalaId').val(data.id);
    $('#editAnoAcademico').val(data.ano_academico_id);
    $('#editNivelEducativo').val(data.nivel_educativo);
    $('#editTipoEscala').val(data.tipo_escala);
    
    // Información adicional
    $('#editFechaCreacion').text(formatDate(data.fecha_creacion));
    
    // Procesar configuración
    if (data.configuracion) {
      try {
        const config = typeof data.configuracion === 'string' ? 
                      JSON.parse(data.configuracion) : data.configuracion;
        $('#editConfiguracionesCount').text(Object.keys(config).length);
        updateEditConfiguracion(data.tipo_escala, config);
      } catch (e) {
        $('#editConfiguracionesCount').text('0');
        updateEditConfiguracion(data.tipo_escala, null);
      }
    } else {
      $('#editConfiguracionesCount').text('0');
      updateEditConfiguracion(data.tipo_escala, null);
    }
    
    // Actualizar vista previa
    updateEditPreviewEscala(data.tipo_escala);
    
    // Deshabilitar edición inicialmente
    $('#editModoEdicion').prop('checked', false);
    toggleEditMode(false);
  };

  // Cargar años académicos
  function loadEditAnosAcademicos() {
    $.ajax({
      url: 'controllers/escalas/anos_controller.php',
      type: 'POST',
      data: { action: 'get_all' },
      dataType: 'json',
      success: function(response) {
        if (response.success && response.data) {
          const select = $('#editAnoAcademico');
          select.html('<option value="">Seleccionar año académico...</option>');
          
          response.data.forEach(function(ano) {
            select.append(`<option value="${ano.id}">${ano.nombre} (${ano.anio})</option>`);
          });
        }
      }
    });
  }

  // Manejar cambio de tipo de escala
  $('#editTipoEscala').on('change', function() {
    const tipo = $(this).val();
    
    if (originalEscalaData && tipo !== originalEscalaData.tipo_escala) {
      showEditInfo('Al cambiar el tipo de escala se reiniciará la configuración actual.');
      updateEditConfiguracion(tipo, null);
    }
    
    updateEditPreviewEscala(tipo);
  });

  // Toggle modo edición
  $('#editModoEdicion').on('change', function() {
    toggleEditMode($(this).is(':checked'));
  });

  function toggleEditMode(enabled) {
    const configInputs = $('#editEscalasConfiguracion input, #editEscalasConfiguracion select');
    configInputs.prop('disabled', !enabled);
    
    if (enabled) {
      showEditInfo('Modo edición habilitado. Puede modificar los valores de configuración.');
    } else {
      hideEditMessages();
    }
  }

  function updateEditPreviewEscala(tipo) {
    const preview = $('#editPreviewEscala');
    const content = $('#editPreviewContent');
    
    if (!tipo) {
      preview.addClass('d-none');
      return;
    }

    let previewHtml = '';
    
    switch (tipo) {
      case 'literal':
        previewHtml = `
          <div class="d-flex gap-2 flex-wrap">
            <span class="badge bg-success">A - Logro destacado (18-20)</span>
            <span class="badge bg-primary">B - Logro esperado (14-17)</span>
            <span class="badge bg-warning">C - En proceso (11-13)</span>
            <span class="badge bg-danger">D - En inicio (0-10)</span>
          </div>
        `;
        break;
      case 'numerico':
        previewHtml = `
          <div class="d-flex gap-2 flex-wrap">
            <span class="badge bg-success">20-18 Excelente</span>
            <span class="badge bg-primary">17-14 Bueno</span>
            <span class="badge bg-warning">13-11 Regular</span>
            <span class="badge bg-danger">10-0 Deficiente</span>
          </div>
        `;
        break;
      case 'descriptivo':
        previewHtml = `
          <div class="d-flex gap-2 flex-wrap">
            <span class="badge bg-success">Competente</span>
            <span class="badge bg-primary">Satisfactorio</span>
            <span class="badge bg-warning">En desarrollo</span>
            <span class="badge bg-danger">Inicial</span>
          </div>
        `;
        break;
    }
    
    content.html(previewHtml);
    preview.removeClass('d-none');
  }

  function updateEditConfiguracion(tipo, config) {
    const container = $('#editEscalasConfiguracion');
    
    if (!tipo) {
      container.html('<p class="text-muted">Seleccione un tipo de escala para ver la configuración.</p>');
      return;
    }

    let configHtml = '';
    
    switch (tipo) {
      case 'literal':
        const aConfig = config?.A || { rango_min: 18, rango_max: 20, descripcion: 'Logro destacado', color: '#10b981' };
        const bConfig = config?.B || { rango_min: 14, rango_max: 17, descripcion: 'Logro esperado', color: '#3b82f6' };
        const cConfig = config?.C || { rango_min: 11, rango_max: 13, descripcion: 'En proceso', color: '#f59e0b' };
        const dConfig = config?.D || { rango_min: 0, rango_max: 10, descripcion: 'En inicio', color: '#ef4444' };
        
        configHtml = `
          <div class="col-md-6 mb-3">
            <label class="form-label fw-semibold text-success">
              <iconify-icon icon="mdi:star"></iconify-icon>
              Escala A - ${aConfig.descripcion}
            </label>
            <div class="input-group">
              <input type="number" class="form-control" name="config_a_min" value="${aConfig.rango_min}" min="0" max="20" disabled>
              <span class="input-group-text">-</span>
              <input type="number" class="form-control" name="config_a_max" value="${aConfig.rango_max}" min="0" max="20" disabled>
            </div>
          </div>
          <div class="col-md-6 mb-3">
            <label class="form-label fw-semibold text-primary">
              <iconify-icon icon="mdi:thumb-up"></iconify-icon>
              Escala B - ${bConfig.descripcion}
            </label>
            <div class="input-group">
              <input type="number" class="form-control" name="config_b_min" value="${bConfig.rango_min}" min="0" max="20" disabled>
              <span class="input-group-text">-</span>
              <input type="number" class="form-control" name="config_b_max" value="${bConfig.rango_max}" min="0" max="20" disabled>
            </div>
          </div>
          <div class="col-md-6 mb-3">
            <label class="form-label fw-semibold text-warning">
              <iconify-icon icon="mdi:clock"></iconify-icon>
              Escala C - ${cConfig.descripcion}
            </label>
            <div class="input-group">
              <input type="number" class="form-control" name="config_c_min" value="${cConfig.rango_min}" min="0" max="20" disabled>
              <span class="input-group-text">-</span>
              <input type="number" class="form-control" name="config_c_max" value="${cConfig.rango_max}" min="0" max="20" disabled>
            </div>
          </div>
          <div class="col-md-6 mb-3">
            <label class="form-label fw-semibold text-danger">
              <iconify-icon icon="mdi:alert"></iconify-icon>
              Escala D - ${dConfig.descripcion}
            </label>
            <div class="input-group">
              <input type="number" class="form-control" name="config_d_min" value="${dConfig.rango_min}" min="0" max="20" disabled>
              <span class="input-group-text">-</span>
              <input type="number" class="form-control" name="config_d_max" value="${dConfig.rango_max}" min="0" max="20" disabled>
            </div>
          </div>
        `;
        break;
        
      case 'numerico':
        const numConfig = config || { min: 0, max: 20, decimales: 2 };
        configHtml = `
          <div class="col-md-4 mb-3">
            <label class="form-label fw-semibold">Valor Mínimo</label>
            <input type="number" class="form-control" name="config_min" value="${numConfig.min || 0}" min="0" disabled>
          </div>
          <div class="col-md-4 mb-3">
            <label class="form-label fw-semibold">Valor Máximo</label>
            <input type="number" class="form-control" name="config_max" value="${numConfig.max || 20}" min="1" disabled>
          </div>
          <div class="col-md-4 mb-3">
            <label class="form-label fw-semibold">Decimales</label>
            <select class="form-select" name="config_decimales" disabled>
              <option value="0" ${numConfig.decimales === 0 ? 'selected' : ''}>Sin decimales</option>
              <option value="1" ${numConfig.decimales === 1 ? 'selected' : ''}>1 decimal</option>
              <option value="2" ${numConfig.decimales === 2 ? 'selected' : ''}>2 decimales</option>
            </select>
          </div>
        `;
        break;
        
      case 'descriptivo':
        const descConfig = config || { 
          superior: 'Competente', 
          satisfactorio: 'Satisfactorio', 
          desarrollo: 'En desarrollo', 
          inicial: 'Inicial' 
        };
        configHtml = `
          <div class="col-md-6 mb-3">
            <label class="form-label fw-semibold">Nivel Superior</label>
            <input type="text" class="form-control" name="config_superior" value="${descConfig.superior || 'Competente'}" disabled>
          </div>
          <div class="col-md-6 mb-3">
            <label class="form-label fw-semibold">Nivel Satisfactorio</label>
            <input type="text" class="form-control" name="config_satisfactorio" value="${descConfig.satisfactorio || 'Satisfactorio'}" disabled>
          </div>
          <div class="col-md-6 mb-3">
            <label class="form-label fw-semibold">Nivel En Desarrollo</label>
            <input type="text" class="form-control" name="config_desarrollo" value="${descConfig.desarrollo || 'En desarrollo'}" disabled>
          </div>
          <div class="col-md-6 mb-3">
            <label class="form-label fw-semibold">Nivel Inicial</label>
            <input type="text" class="form-control" name="config_inicial" value="${descConfig.inicial || 'Inicial'}" disabled>
          </div>
        `;
        break;
    }
    
    container.html(configHtml);
    
    // Actualizar estado de configuración
    updateConfigurationStatus(config);
  }

  function updateConfigurationStatus(config) {
    const texto = $('#editConfiguracionTexto');
    
    if (config && Object.keys(config).length > 0) {
      texto.html('<span class="text-success"><iconify-icon icon="mdi:check-circle"></iconify-icon> Configuración completa</span>');
    } else {
      texto.html('<span class="text-warning"><iconify-icon icon="mdi:alert-circle"></iconify-icon> Sin configuración</span>');
    }
  }

  // Botón para configurar escalas (abre modal de configuración)
  $('#editConfigurarBtn').on('click', function() {
    const escalaId = $('#editEscalaId').val();
    if (escalaId) {
      configureEscala(escalaId);
    }
  });

  // Resetear configuración
  $('#editResetConfigBtn').on('click', function() {
    Swal.fire({
      title: '¿Resetear configuración?',
      text: 'Se aplicará la configuración por defecto para este tipo de escala.',
      icon: 'question',
      showCancelButton: true,
      confirmButtonText: 'Sí, resetear',
      cancelButtonText: 'Cancelar'
    }).then((result) => {
      if (result.isConfirmed) {
        const tipo = $('#editTipoEscala').val();
        updateEditConfiguracion(tipo, null);
        showEditInfo('Configuración reseteada a valores por defecto.');
      }
    });
  });

  // Verificar duplicados
  $('#editAnoAcademico, #editNivelEducativo').on('change', function() {
    checkEditDuplicateEscala();
  });

  function checkEditDuplicateEscala() {
    const anoId = $('#editAnoAcademico').val();
    const nivel = $('#editNivelEducativo').val();
    const currentId = $('#editEscalaId').val();
    
    if (!anoId || !nivel || !currentId) {
      hideEditMessages();
      return;
    }

    $.ajax({
      url: 'controllers/escalas/escalas_controller.php',
      type: 'POST',
      data: { 
        action: 'check_duplicate',
        ano_academico_id: anoId,
        nivel_educativo: nivel,
        exclude_id: currentId
      },
      dataType: 'json',
      success: function(response) {
        if (response.exists) {
          showEditWarning('Ya existe otra escala para este año académico y nivel educativo.');
        } else {
          hideEditMessages();
        }
      }
    });
  }

  function showEditWarning(message) {
    $('#editWarningText').text(message);
    $('#editWarningMessage').removeClass('d-none');
    $('#editInfoMessage').addClass('d-none');
  }

  function showEditInfo(message) {
    $('#editInfoText').text(message);
    $('#editInfoMessage').removeClass('d-none');
    $('#editWarningMessage').addClass('d-none');
  }

  function hideEditMessages() {
    $('#editWarningMessage').addClass('d-none');
    $('#editInfoMessage').addClass('d-none');
  }

  function formatDate(dateString) {
    if (!dateString) return '-';
    try {
      const date = new Date(dateString);
      return date.toLocaleDateString('es-PE');
    } catch (e) {
      return '-';
    }
  }

  // Manejar envío del formulario
  $('#editEscalaForm').on('submit', function(e) {
    e.preventDefault();
    
    if (!this.checkValidity()) {
      e.stopPropagation();
      $(this).addClass('was-validated');
      return;
    }

    const formData = new FormData(this);
    formData.append('action', 'update');
    
    // Deshabilitar botón y mostrar loading
    const btn = $('#editEscalaBtn');
    const btnContent = $('#editBtnContent');
    const btnLoading = $('#editBtnLoading');
    
    btn.prop('disabled', true);
    btnContent.addClass('d-none');
    btnLoading.removeClass('d-none');

    $.ajax({
      url: 'controllers/escalas/escalas_controller.php',
      type: 'POST',
      data: formData,
      processData: false,
      contentType: false,
      dataType: 'json',
      success: function(response) {
        if (response.success) {
          showNotification('success', 'Escala actualizada', response.message || 'La escala fue actualizada correctamente.');
          $('#editEscalaModal').modal('hide');
          setTimeout(function() {
            location.reload();
          }, 1000);
        } else {
          showNotification('error', 'Error', response.message || 'Error al actualizar la escala.');
        }
      },
      error: function() {
        showNotification('error', 'Error', 'Error de conexión al actualizar la escala.');
      },
      complete: function() {
        // Rehabilitar botón
        btn.prop('disabled', false);
        btnContent.removeClass('d-none');
        btnLoading.addClass('d-none');
      }
    });
  });

  // Limpiar formulario al cerrar modal
  $('#editEscalaModal').on('hidden.bs.modal', function() {
    $('#editEscalaForm')[0].reset();
    $('#editEscalaForm').removeClass('was-validated');
    $('#editPreviewEscala').addClass('d-none');
    $('#editModoEdicion').prop('checked', false);
    hideEditMessages();
    originalEscalaData = null;
  });
});
</script>