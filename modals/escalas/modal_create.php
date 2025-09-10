<!-- modals/escalas/modal_create.php -->
<div class="modal fade" id="createEscalaModal" tabindex="-1" aria-labelledby="createEscalaModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="createEscalaModalLabel">
          <iconify-icon icon="mdi:star-four-points" class="me-2"></iconify-icon>
          Crear Nueva Escala de Calificación
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      
      <form id="createEscalaForm">
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
              <label for="createAnoAcademico" class="form-label fw-semibold">
                <iconify-icon icon="mdi:calendar" class="me-1"></iconify-icon>
                Año Académico *
              </label>
              <select class="form-select" id="createAnoAcademico" name="ano_academico_id" required>
                <option value="">Seleccionar año académico...</option>
                <!-- Se llenará dinámicamente -->
              </select>
              <div class="invalid-feedback">
                El año académico es requerido
              </div>
            </div>

            <!-- Nivel Educativo -->
            <div class="col-md-6 mb-3">
              <label for="createNivelEducativo" class="form-label fw-semibold">
                <iconify-icon icon="mdi:school" class="me-1"></iconify-icon>
                Nivel Educativo *
              </label>
              <select class="form-select" id="createNivelEducativo" name="nivel_educativo" required>
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
              <label for="createTipoEscala" class="form-label fw-semibold">
                <iconify-icon icon="mdi:format-list-numbered" class="me-1"></iconify-icon>
                Tipo de Escala *
              </label>
              <select class="form-select" id="createTipoEscala" name="tipo_escala" required>
                <option value="">Seleccionar tipo de escala...</option>
                <option value="literal">Literal (A, B, C, D) - Recomendado para Inicial y Primaria</option>
                <option value="numerico">Numérico (0-20) - Para evaluaciones específicas</option>
                <option value="descriptivo">Descriptivo - Para competencias específicas</option>
              </select>
              <div class="form-text">
                <strong>Literal:</strong> Sistema estándar peruano A=Logro destacado, B=Logro esperado, C=En proceso, D=En inicio<br>
                <strong>Numérico:</strong> Para casos específicos que requieren puntuación exacta<br>
                <strong>Descriptivo:</strong> Para evaluaciones cualitativas detalladas
              </div>
              <div class="invalid-feedback">
                El tipo de escala es requerido
              </div>
            </div>

            <!-- Vista previa del tipo seleccionado -->
            <div class="col-12 mb-4">
              <div id="createPreviewEscala" class="d-none">
                <div class="alert alert-info">
                  <h6 class="alert-heading">
                    <iconify-icon icon="mdi:eye" class="me-1"></iconify-icon>
                    Vista Previa de la Escala
                  </h6>
                  <div id="createPreviewContent"></div>
                </div>
              </div>
            </div>

            <!-- Configuración Automática -->
            <div class="col-12 mb-4">
              <h6 class="text-muted text-uppercase fw-bold mb-3">
                <iconify-icon icon="mdi:cog" class="me-1"></iconify-icon>
                Configuración Inicial
              </h6>
            </div>

            <!-- Configuración Automática Checkbox -->
            <div class="col-12 mb-3">
              <div class="form-check form-switch">
                <input class="form-check-input" type="checkbox" id="createConfiguracionAutomatica" name="configuracion_automatica" checked>
                <label class="form-check-label fw-semibold" for="createConfiguracionAutomatica">
                  Aplicar configuración automática recomendada
                </label>
              </div>
              <div class="form-text">
                Si está marcado, se aplicará la configuración estándar del MINEDU para el tipo de escala seleccionado.
                Podrá personalizarla después de crear la escala.
              </div>
            </div>

            <!-- Configuración Personalizada (oculta por defecto) -->
            <div class="col-12" id="createConfiguracionPersonalizada" style="display: none;">
              <div class="border rounded p-3 bg-light">
                <h6 class="fw-bold mb-3">Configuración Personalizada</h6>
                <div class="row" id="createEscalasPersonalizadas">
                  <!-- Se llenará dinámicamente según el tipo -->
                </div>
              </div>
            </div>

            <!-- Validaciones Adicionales -->
            <div class="col-12 mt-3">
              <div class="alert alert-warning d-none" id="createWarningMessage">
                <iconify-icon icon="mdi:alert" class="me-2"></iconify-icon>
                <span id="createWarningText"></span>
              </div>
            </div>

          </div>
        </div>
        
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
            <iconify-icon icon="mdi:close"></iconify-icon>
            Cancelar
          </button>
          <button type="submit" class="btn btn-primary" id="createEscalaBtn">
            <span id="createBtnContent">
              <iconify-icon icon="mdi:plus"></iconify-icon>
              Crear Escala
            </span>
            <span id="createBtnLoading" class="d-none">
              <div class="spinner-border spinner-border-sm me-2" role="status"></div>
              Creando...
            </span>
          </button>
        </div>
      </form>
    </div>
  </div>
</div>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
    $(document).ready(function() {
    // Cargar años académicos al abrir el modal
    $('#createEscalaModal').on('show.bs.modal', function() {
        loadAnosAcademicos();
    });

    // Cargar años académicos
    function loadAnosAcademicos() {
        $.ajax({
        url: 'controllers/escalas/anos_controller.php',
        type: 'POST',
        data: { action: 'get_all_active' },
        dataType: 'json',
        success: function(response) {
            if (response.success && response.data) {
            const select = $('#createAnoAcademico');
            select.html('<option value="">Seleccionar año académico...</option>');
            
            response.data.forEach(function(ano) {
                const selected = ano.estado === 'activo' ? 'selected' : '';
                select.append(`<option value="${ano.id}" ${selected}>${ano.nombre} (${ano.anio})</option>`);
            });
            }
        },
        error: function() {
            showNotification('error', 'Error', 'Error al cargar los años académicos');
        }
        });
    }

    // Manejar cambio de tipo de escala
    $('#createTipoEscala').on('change', function() {
        const tipo = $(this).val();
        updatePreviewEscala(tipo);
        updateConfiguracionPersonalizada(tipo);
    });

    // Manejar toggle de configuración automática
    $('#createConfiguracionAutomatica').on('change', function() {
        const personalizada = $('#createConfiguracionPersonalizada');
        if ($(this).is(':checked')) {
        personalizada.hide();
        } else {
        personalizada.show();
        }
    });

    // Verificar duplicados al cambiar año o nivel
    $('#createAnoAcademico, #createNivelEducativo').on('change', function() {
        checkDuplicateEscala();
    });

    function updatePreviewEscala(tipo) {
        const preview = $('#createPreviewEscala');
        const content = $('#createPreviewContent');
        
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
            <small class="text-muted mt-2 d-block">Configuración estándar del MINEDU para educación básica</small>
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
            <small class="text-muted mt-2 d-block">Sistema numérico tradicional (0-20)</small>
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
            <small class="text-muted mt-2 d-block">Descriptores cualitativos de competencias</small>
            `;
            break;
        }
        
        content.html(previewHtml);
        preview.removeClass('d-none');
    }

    function updateConfiguracionPersonalizada(tipo) {
        const container = $('#createEscalasPersonalizadas');
        
        if (!tipo) {
        container.html('');
        return;
        }

        let configHtml = '';
        
        switch (tipo) {
        case 'literal':
            configHtml = `
            <div class="col-md-6 mb-3">
                <label class="form-label fw-semibold">Escala A - Logro Destacado</label>
                <div class="input-group">
                <input type="number" class="form-control" placeholder="Min" name="config_a_min" value="18" min="0" max="20">
                <span class="input-group-text">-</span>
                <input type="number" class="form-control" placeholder="Max" name="config_a_max" value="20" min="0" max="20">
                </div>
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label fw-semibold">Escala B - Logro Esperado</label>
                <div class="input-group">
                <input type="number" class="form-control" placeholder="Min" name="config_b_min" value="14" min="0" max="20">
                <span class="input-group-text">-</span>
                <input type="number" class="form-control" placeholder="Max" name="config_b_max" value="17" min="0" max="20">
                </div>
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label fw-semibold">Escala C - En Proceso</label>
                <div class="input-group">
                <input type="number" class="form-control" placeholder="Min" name="config_c_min" value="11" min="0" max="20">
                <span class="input-group-text">-</span>
                <input type="number" class="form-control" placeholder="Max" name="config_c_max" value="13" min="0" max="20">
                </div>
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label fw-semibold">Escala D - En Inicio</label>
                <div class="input-group">
                <input type="number" class="form-control" placeholder="Min" name="config_d_min" value="0" min="0" max="20">
                <span class="input-group-text">-</span>
                <input type="number" class="form-control" placeholder="Max" name="config_d_max" value="10" min="0" max="20">
                </div>
            </div>
            `;
            break;
        case 'numerico':
            configHtml = `
            <div class="col-12 mb-3">
                <label class="form-label fw-semibold">Configuración Numérica</label>
                <div class="row">
                <div class="col-md-6">
                    <label class="form-label">Valor Mínimo</label>
                    <input type="number" class="form-control" name="config_min" value="0" min="0">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Valor Máximo</label>
                    <input type="number" class="form-control" name="config_max" value="20" min="1">
                </div>
                </div>
            </div>
            <div class="col-12 mb-3">
                <label class="form-label fw-semibold">Decimales Permitidos</label>
                <select class="form-select" name="config_decimales">
                <option value="0">Sin decimales (enteros)</option>
                <option value="1">1 decimal (ej: 15.5)</option>
                <option value="2" selected>2 decimales (ej: 15.75)</option>
                </select>
            </div>
            `;
            break;
        case 'descriptivo':
            configHtml = `
            <div class="col-md-6 mb-3">
                <label class="form-label fw-semibold">Nivel Superior</label>
                <input type="text" class="form-control" name="config_superior" value="Competente" placeholder="Ej: Competente">
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label fw-semibold">Nivel Satisfactorio</label>
                <input type="text" class="form-control" name="config_satisfactorio" value="Satisfactorio" placeholder="Ej: Satisfactorio">
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label fw-semibold">Nivel En Desarrollo</label>
                <input type="text" class="form-control" name="config_desarrollo" value="En desarrollo" placeholder="Ej: En desarrollo">
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label fw-semibold">Nivel Inicial</label>
                <input type="text" class="form-control" name="config_inicial" value="Inicial" placeholder="Ej: Inicial">
            </div>
            `;
            break;
        }
        
        container.html(configHtml);
    }

    function checkDuplicateEscala() {
        const anoId = $('#createAnoAcademico').val();
        const nivel = $('#createNivelEducativo').val();
        
        if (!anoId || !nivel) {
        hideWarning();
        return;
        }

        $.ajax({
        url: 'controllers/escalas/escalas_controller.php',
        type: 'POST',
        data: { 
            action: 'check_duplicate',
            ano_academico_id: anoId,
            nivel_educativo: nivel
        },
        dataType: 'json',
        success: function(response) {
            if (response.exists) {
            showWarning('Ya existe una escala de calificación para este año académico y nivel educativo. ¿Desea continuar de todos modos?');
            } else {
            hideWarning();
            }
        }
        });
    }

    function showWarning(message) {
        $('#createWarningText').text(message);
        $('#createWarningMessage').removeClass('d-none');
    }

    function hideWarning() {
        $('#createWarningMessage').addClass('d-none');
    }

    // Manejar envío del formulario
    $('#createEscalaForm').on('submit', function(e) {
        e.preventDefault();
        
        if (!this.checkValidity()) {
        e.stopPropagation();
        $(this).addClass('was-validated');
        return;
        }

        const formData = new FormData(this);
        formData.append('action', 'create');
        
        // Deshabilitar botón y mostrar loading
        const btn = $('#createEscalaBtn');
        const btnContent = $('#createBtnContent');
        const btnLoading = $('#createBtnLoading');
        
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
            showNotification('success', 'Escala creada', response.message || 'La escala de calificación fue creada correctamente.');
            $('#createEscalaModal').modal('hide');
            setTimeout(function() {
                location.reload();
            }, 1000);
            } else {
            showNotification('error', 'Error', response.message || 'Error al crear la escala de calificación.');
            }
        },
        error: function() {
            showNotification('error', 'Error', 'Error de conexión al crear la escala.');
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
    $('#createEscalaModal').on('hidden.bs.modal', function() {
        $('#createEscalaForm')[0].reset();
        $('#createEscalaForm').removeClass('was-validated');
        $('#createPreviewEscala').addClass('d-none');
        $('#createConfiguracionPersonalizada').hide();
        $('#createConfiguracionAutomatica').prop('checked', true);
        hideWarning();
    });
    });
</script>