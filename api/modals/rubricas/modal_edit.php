<!-- modals/rubricas/modal_edit.php -->
<div class="modal fade" id="editRubricaModal" tabindex="-1" aria-labelledby="editRubricaModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="editRubricaModalLabel">
          <iconify-icon icon="mdi:clipboard-edit" class="me-2"></iconify-icon>
          Editar Rúbrica
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      
      <form id="editRubricaForm">
        <input type="hidden" id="editRubricaId" name="id">
        
        <div class="modal-body">
          <div class="row">
            <!-- Información Básica -->
            <div class="col-12 mb-4">
              <h6 class="text-muted text-uppercase fw-bold mb-3">
                <iconify-icon icon="mdi:information-outline" class="me-1"></iconify-icon>
                Información Básica
              </h6>
            </div>

            <!-- Nombre de la Rúbrica -->
            <div class="col-12 mb-3">
              <label for="editNombre" class="form-label fw-semibold">
                <iconify-icon icon="mdi:clipboard-text" class="me-1"></iconify-icon>
                Nombre de la Rúbrica *
              </label>
              <input type="text" class="form-control" id="editNombre" name="nombre" required 
                     placeholder="Ej: Rúbrica de Comunicación Oral - 1° Secundaria">
              <div class="form-text">Ingrese un nombre descriptivo para la rúbrica</div>
              <div class="invalid-feedback">
                El nombre de la rúbrica es requerido
              </div>
            </div>

            <!-- Descripción -->
            <div class="col-12 mb-3">
              <label for="editDescripcion" class="form-label fw-semibold">
                <iconify-icon icon="mdi:text" class="me-1"></iconify-icon>
                Descripción
              </label>
              <textarea class="form-control" id="editDescripcion" name="descripcion" rows="3" 
                        placeholder="Descripción detallada de la rúbrica y su propósito..."></textarea>
              <div class="form-text">Opcional: Proporcione una descripción del propósito de esta rúbrica</div>
            </div>

            <!-- Configuración de Evaluación -->
            <div class="col-12 mb-4 mt-3">
              <h6 class="text-muted text-uppercase fw-bold mb-3">
                <iconify-icon icon="mdi:cog-outline" class="me-1"></iconify-icon>
                Configuración de Evaluación
              </h6>
            </div>

            <!-- Competencia -->
            <div class="col-md-6 mb-3">
              <label for="editCompetencia" class="form-label fw-semibold">
                <iconify-icon icon="mdi:target" class="me-1"></iconify-icon>
                Competencia *
              </label>
              <select class="form-select" id="editCompetencia" name="competencia_id" required>
                <option value="">Seleccionar competencia...</option>
              </select>
              <div class="form-text">Competencia que evaluará esta rúbrica</div>
              <div class="invalid-feedback">
                La competencia es requerida
              </div>
            </div>

            <!-- Curso -->
            <div class="col-md-6 mb-3">
              <label for="editCurso" class="form-label fw-semibold">
                <iconify-icon icon="mdi:book-open-page-variant" class="me-1"></iconify-icon>
                Curso *
              </label>
              <select class="form-select" id="editCurso" name="curso_id" required>
                <option value="">Seleccionar curso...</option>
              </select>
              <div class="form-text">Curso al que pertenece esta rúbrica</div>
              <div class="invalid-feedback">
                El curso es requerido
              </div>
            </div>

            <!-- Tipo de Evaluación -->
            <div class="col-md-6 mb-3">
              <label for="editTipoEvaluacion" class="form-label fw-semibold">
                <iconify-icon icon="mdi:clipboard-check" class="me-1"></iconify-icon>
                Tipo de Evaluación *
              </label>
              <select class="form-select" id="editTipoEvaluacion" name="tipo_evaluacion" required>
                <option value="">Seleccionar tipo...</option>
                <option value="diagnostica">Diagnóstica</option>
                <option value="formativa">Formativa</option>
                <option value="sumativa">Sumativa</option>
              </select>
              <div class="form-text">Propósito de la evaluación</div>
              <div class="invalid-feedback">
                El tipo de evaluación es requerido
              </div>
            </div>

            <!-- Estado -->
            <div class="col-md-6 mb-3">
              <label for="editEstado" class="form-label fw-semibold">
                <iconify-icon icon="mdi:toggle-switch" class="me-1"></iconify-icon>
                Estado
              </label>
              <select class="form-select" id="editEstado" name="estado">
                <option value="1">Activa</option>
                <option value="0">Inactiva</option>
              </select>
              <div class="form-text">Estado de la rúbrica</div>
            </div>

            <!-- Información de Creación (Solo lectura) -->
            <div class="col-12 mb-4 mt-3">
              <h6 class="text-muted text-uppercase fw-bold mb-3">
                <iconify-icon icon="mdi:information-variant" class="me-1"></iconify-icon>
                Información de Registro
              </h6>
            </div>

            <div class="col-md-6 mb-3">
              <label class="form-label fw-semibold">
                <iconify-icon icon="mdi:calendar" class="me-1"></iconify-icon>
                Fecha de Creación
              </label>
              <input type="text" class="form-control-plaintext" id="editFechaCreacion" readonly 
                     placeholder="Fecha de creación">
            </div>

            <div class="col-md-6 mb-3">
              <label class="form-label fw-semibold">
                <iconify-icon icon="mdi:account" class="me-1"></iconify-icon>
                Creado Por
              </label>
              <input type="text" class="form-control-plaintext" id="editCreadoPor" readonly 
                     placeholder="Usuario creador">
            </div>

            <!-- Configuración de Escalas (Opcional) -->
            <div class="col-12 mb-3">
              <label class="form-label fw-semibold">
                <iconify-icon icon="mdi:star-settings" class="me-1"></iconify-icon>
                Configuración de Escalas (Opcional)
              </label>
              <div class="border rounded p-3 bg-light">
                <div class="form-check form-switch mb-3">
                  <input class="form-check-input" type="checkbox" id="editUseCustomScales" name="use_custom_scales">
                  <label class="form-check-label" for="editUseCustomScales">
                    Usar configuración personalizada de escalas
                  </label>
                </div>
                
                <div id="editCustomScalesConfig" class="d-none">
                  <div class="row">
                    <div class="col-md-3 mb-2">
                      <label class="form-label small">Escala A (Excelente)</label>
                      <input type="text" class="form-control form-control-sm" name="escala_a_desc" 
                             placeholder="Logro destacado" value="Logro destacado">
                    </div>
                    <div class="col-md-3 mb-2">
                      <label class="form-label small">Escala B (Bueno)</label>
                      <input type="text" class="form-control form-control-sm" name="escala_b_desc" 
                             placeholder="Logro esperado" value="Logro esperado">
                    </div>
                    <div class="col-md-3 mb-2">
                      <label class="form-label small">Escala C (Regular)</label>
                      <input type="text" class="form-control form-control-sm" name="escala_c_desc" 
                             placeholder="En proceso" value="En proceso">
                    </div>
                    <div class="col-md-3 mb-2">
                      <label class="form-label small">Escala D (Deficiente)</label>
                      <input type="text" class="form-control form-control-sm" name="escala_d_desc" 
                             placeholder="En inicio" value="En inicio">
                    </div>
                  </div>
                </div>

                <!-- Configuración actual (mostrar si existe) -->
                <div id="editCurrentScalesConfig" class="d-none mt-3">
                  <div class="alert alert-info small">
                    <iconify-icon icon="mdi:information" class="me-1"></iconify-icon>
                    <strong>Configuración actual:</strong>
                    <div id="editCurrentScalesContent"></div>
                  </div>
                </div>
              </div>
              <div class="form-text">
                <iconify-icon icon="mdi:information" class="me-1"></iconify-icon>
                Si no se especifica, se usará la configuración de escalas por defecto del sistema
              </div>
            </div>
          </div>
        </div>
        
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
            <iconify-icon icon="mdi:close"></iconify-icon>
            Cancelar
          </button>
          <button type="button" class="btn btn-warning me-2" id="editViewCriteriosBtn">
            <iconify-icon icon="mdi:format-list-checks"></iconify-icon>
            Ver Criterios
          </button>
          <button type="submit" class="btn btn-primary" id="editRubricaBtn">
            <span id="editBtnContent">
              <iconify-icon icon="mdi:content-save"></iconify-icon>
              Actualizar Rúbrica
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
  // Variable para almacenar los datos originales
  let originalRubricaData = null;

  // Cargar datos para selects al abrir el modal
  $('#editRubricaModal').on('show.bs.modal', function() {
    loadCompetenciasForEdit();
    loadCursosForEdit();
  });

  // Toggle de configuración personalizada de escalas
  $('#editUseCustomScales').on('change', function() {
    const customConfig = document.getElementById('editCustomScalesConfig');
    if (this.checked) {
      customConfig.classList.remove('d-none');
    } else {
      customConfig.classList.add('d-none');
    }
  });

  // Botón para ver criterios de la competencia actual
  $('#editViewCriteriosBtn').on('click', function() {
    const competenciaId = $('#editCompetencia').val();
    if (competenciaId) {
      $('#editRubricaModal').modal('hide');
      setTimeout(() => {
        configureCriterios(competenciaId);
      }, 300);
    } else {
      showNotification('warning', 'Advertencia', 'Primero seleccione una competencia');
    }
  });

  // Función global para llenar el modal de edición
  window.fillEditModal = function(data) {
    originalRubricaData = data;
    
    // Llenar campos básicos
    $('#editRubricaId').val(data.id);
    $('#editNombre').val(data.nombre || '');
    $('#editDescripcion').val(data.descripcion || '');
    $('#editTipoEvaluacion').val(data.tipo_evaluacion || '');
    $('#editEstado').val(data.estado || '1');

    // Información de creación
    if (data.fecha_creacion) {
      try {
        const fecha = new Date(data.fecha_creacion);
        $('#editFechaCreacion').val(fecha.toLocaleDateString('es-ES'));
      } catch (e) {
        $('#editFechaCreacion').val(data.fecha_creacion);
      }
    }
    
    // Obtener información del creador si está disponible
    $('#editCreadoPor').val(data.creado_por_nombre || 'Usuario del sistema');

    // Configuración de escalas
    if (data.configuracion_escalas) {
      try {
        const config = typeof data.configuracion_escalas === 'string' 
                      ? JSON.parse(data.configuracion_escalas) 
                      : data.configuracion_escalas;
        
        if (config && Object.keys(config).length > 0) {
          $('#editUseCustomScales').prop('checked', true);
          $('#editCustomScalesConfig').removeClass('d-none');
          
          // Llenar campos de escalas
          if (config.A) $('input[name="escala_a_desc"]').val(config.A.descripcion || 'Logro destacado');
          if (config.B) $('input[name="escala_b_desc"]').val(config.B.descripcion || 'Logro esperado');
          if (config.C) $('input[name="escala_c_desc"]').val(config.C.descripcion || 'En proceso');
          if (config.D) $('input[name="escala_d_desc"]').val(config.D.descripcion || 'En inicio');

          // Mostrar configuración actual
          $('#editCurrentScalesConfig').removeClass('d-none');
          let configText = '';
          Object.keys(config).forEach(key => {
            configText += `<span class="badge bg-secondary me-1">${key}: ${config[key].descripcion || 'Sin descripción'}</span>`;
          });
          $('#editCurrentScalesContent').html(configText);
        }
      } catch (e) {
        console.error('Error al parsear configuración de escalas:', e);
      }
    }

    // Esperar a que se carguen las opciones antes de seleccionar
    setTimeout(() => {
      if (data.competencia_id) {
        $('#editCompetencia').val(data.competencia_id);
      }
      if (data.curso_id) {
        $('#editCurso').val(data.curso_id);
      }
    }, 500);
  };

  // Validación en tiempo real del nombre
  $('#editNombre').on('input', function() {
    const nombre = this.value.trim();
    if (nombre.length >= 3) {
      this.classList.remove('is-invalid');
      this.classList.add('is-valid');
    } else if (nombre.length > 0) {
      this.classList.remove('is-valid');
      this.classList.add('is-invalid');
    } else {
      this.classList.remove('is-valid', 'is-invalid');
    }
  });

  // Función para cargar competencias
  function loadCompetenciasForEdit() {
    $.ajax({
      url: 'controllers/rubricas/competencias_controller.php',
      type: 'POST',
      data: { action: 'get_all_active' },
      dataType: 'json',
      success: function(response) {
        if (response.success) {
          const select = $('#editCompetencia');
          select.empty().append('<option value="">Seleccionar competencia...</option>');
          
          response.data.forEach(function(competencia) {
            const option = `<option value="${competencia.id}" 
                              data-area="${competencia.area_curricular_nombre || ''}"
                              data-codigo="${competencia.codigo || ''}">
                              ${competencia.nombre} ${competencia.codigo ? '(' + competencia.codigo + ')' : ''}
                            </option>`;
            select.append(option);
          });

          // Seleccionar la competencia actual si existe
          if (originalRubricaData && originalRubricaData.competencia_id) {
            select.val(originalRubricaData.competencia_id);
          }
        }
      }
    });
  }

  // Función para cargar cursos
  function loadCursosForEdit() {
    $.ajax({
      url: 'controllers/rubricas/cursos_controller.php',
      type: 'POST',
      data: { action: 'get_all_active' },
      dataType: 'json',
      success: function(response) {
        if (response.success) {
          const select = $('#editCurso');
          select.empty().append('<option value="">Seleccionar curso...</option>');
          
          response.data.forEach(function(curso) {
            const option = `<option value="${curso.id}" 
                              data-grado="${curso.grado_nombre || ''}"
                              data-area="${curso.area_curricular_nombre || ''}">
                              ${curso.nombre}
                            </option>`;
            select.append(option);
          });

          // Seleccionar el curso actual si existe
          if (originalRubricaData && originalRubricaData.curso_id) {
            select.val(originalRubricaData.curso_id);
          }
        }
      }
    });
  }

  // Manejar envío del formulario de edición
  $('#editRubricaForm').on('submit', function(e) {
    e.preventDefault();
    
    // Validar formulario
    if (!this.checkValidity()) {
      e.stopPropagation();
      this.classList.add('was-validated');
      return;
    }

    // Mostrar estado de carga
    const btn = $('#editRubricaBtn');
    const btnContent = $('#editBtnContent');
    const btnLoading = $('#editBtnLoading');
    
    btn.prop('disabled', true);
    btnContent.addClass('d-none');
    btnLoading.removeClass('d-none');

    // Preparar datos del formulario
    const formData = new FormData(this);
    formData.append('action', 'update');

    // Agregar configuración de escalas si está personalizada
    if ($('#editUseCustomScales').is(':checked')) {
      const escalasConfig = {
        A: { descripcion: $('input[name="escala_a_desc"]').val() || 'Logro destacado' },
        B: { descripcion: $('input[name="escala_b_desc"]').val() || 'Logro esperado' },
        C: { descripcion: $('input[name="escala_c_desc"]').val() || 'En proceso' },
        D: { descripcion: $('input[name="escala_d_desc"]').val() || 'En inicio' }
      };
      formData.append('configuracion_escalas', JSON.stringify(escalasConfig));
    }

    // Enviar petición
    $.ajax({
      url: 'controllers/rubricas/rubricas_controller.php',
      type: 'POST',
      data: formData,
      processData: false,
      contentType: false,
      dataType: 'json',
      success: function(response) {
        if (response.success) {
          showNotification('success', 'Éxito', 'Rúbrica actualizada correctamente');
          $('#editRubricaModal').modal('hide');
          // Recargar la página para mostrar los cambios
          setTimeout(function() { location.reload(); }, 800);
        } else {
          showNotification('error', 'Error', response.message || 'Error al actualizar la rúbrica');
        }
      },
      error: function(xhr, status, error) {
        console.error('Error:', error);
        showNotification('error', 'Error', 'Error de conexión al actualizar la rúbrica');
      },
      complete: function() {
        // Restaurar estado del botón
        btn.prop('disabled', false);
        btnContent.removeClass('d-none');
        btnLoading.addClass('d-none');
      }
    });
  });

  // Limpiar formulario al cerrar modal
  $('#editRubricaModal').on('hidden.bs.modal', function() {
    $('#editRubricaForm')[0].reset();
    $('#editRubricaForm').removeClass('was-validated');
    $('#editNombre').removeClass('is-valid', 'is-invalid');
    $('#editUseCustomScales').prop('checked', false);
    $('#editCustomScalesConfig').addClass('d-none');
    $('#editCurrentScalesConfig').addClass('d-none');
    originalRubricaData = null;
  });
});
</script>