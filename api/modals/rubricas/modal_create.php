<!-- modals/rubricas/modal_create.php -->
<div class="modal fade" id="createRubricaModal" tabindex="-1" aria-labelledby="createRubricaModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="createRubricaModalLabel">
          <iconify-icon icon="mdi:clipboard-plus" class="me-2"></iconify-icon>
          Crear Nueva Rúbrica
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      
      <form id="createRubricaForm">
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
              <label for="createNombre" class="form-label fw-semibold">
                <iconify-icon icon="mdi:clipboard-text" class="me-1"></iconify-icon>
                Nombre de la Rúbrica *
              </label>
              <input type="text" class="form-control" id="createNombre" name="nombre" required 
                     placeholder="Ej: Rúbrica de Comunicación Oral - 1° Secundaria">
              <div class="form-text">Ingrese un nombre descriptivo para la rúbrica</div>
              <div class="invalid-feedback">
                El nombre de la rúbrica es requerido
              </div>
            </div>

            <!-- Descripción -->
            <div class="col-12 mb-3">
              <label for="createDescripcion" class="form-label fw-semibold">
                <iconify-icon icon="mdi:text" class="me-1"></iconify-icon>
                Descripción
              </label>
              <textarea class="form-control" id="createDescripcion" name="descripcion" rows="3" 
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
              <label for="createCompetencia" class="form-label fw-semibold">
                <iconify-icon icon="mdi:target" class="me-1"></iconify-icon>
                Competencia *
              </label>
              <select class="form-select" id="createCompetencia" name="competencia_id" required>
                <option value="">Cargando competencias...</option>
              </select>
              <div class="form-text">Competencia que evaluará esta rúbrica</div>
              <div class="invalid-feedback">
                La competencia es requerida
              </div>
            </div>

            <!-- Curso -->
            <div class="col-md-6 mb-3">
              <label for="createCurso" class="form-label fw-semibold">
                <iconify-icon icon="mdi:book-open-page-variant" class="me-1"></iconify-icon>
                Curso *
              </label>
              <select class="form-select" id="createCurso" name="curso_id" required>
                <option value="">Cargando cursos...</option>
              </select>
              <div class="form-text">Curso al que pertenece esta rúbrica</div>
              <div class="invalid-feedback">
                El curso es requerido
              </div>
            </div>

            <!-- Tipo de Evaluación -->
            <div class="col-md-6 mb-3">
              <label for="createTipoEvaluacion" class="form-label fw-semibold">
                <iconify-icon icon="mdi:clipboard-check" class="me-1"></iconify-icon>
                Tipo de Evaluación *
              </label>
              <select class="form-select" id="createTipoEvaluacion" name="tipo_evaluacion" required>
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
              <label for="createEstado" class="form-label fw-semibold">
                <iconify-icon icon="mdi:toggle-switch" class="me-1"></iconify-icon>
                Estado Inicial
              </label>
              <select class="form-select" id="createEstado" name="estado">
                <option value="1">Activa</option>
                <option value="0">Inactiva</option>
              </select>
              <div class="form-text">Estado inicial de la rúbrica</div>
            </div>

            <!-- Configuración de Escalas (Opcional) -->
            <div class="col-12 mb-3">
              <label class="form-label fw-semibold">
                <iconify-icon icon="mdi:star-settings" class="me-1"></iconify-icon>
                Configuración de Escalas (Opcional)
              </label>
              <div class="border rounded p-3 bg-light">
                <div class="form-check form-switch mb-3">
                  <input class="form-check-input" type="checkbox" id="createUseCustomScales" name="use_custom_scales">
                  <label class="form-check-label" for="createUseCustomScales">
                    Usar configuración personalizada de escalas
                  </label>
                </div>
                
                <div id="createCustomScalesConfig" class="d-none">
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
          <button type="submit" class="btn btn-primary" id="createRubricaBtn">
            <span id="createBtnContent">
              <iconify-icon icon="mdi:content-save"></iconify-icon>
              Crear Rúbrica
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
    // Inicializar función de notificación si no existe
    if (typeof window.showNotification === 'undefined') {
      window.showNotification = function(type, title, message) {
        if (typeof Swal !== 'undefined') {
          let icon = 'info';
          let confirmButtonColor = '#3085d6';
          
          switch(type) {
            case 'success':
              icon = 'success';
              confirmButtonColor = '#10b981';
              break;
            case 'error':
              icon = 'error';
              confirmButtonColor = '#ef4444';
              break;
            case 'warning':
              icon = 'warning';
              confirmButtonColor = '#f59e0b';
              break;
          }

          Swal.fire({
            icon: icon,
            title: title,
            text: message,
            confirmButtonColor: confirmButtonColor,
            confirmButtonText: 'Entendido'
          });
        } else {
          alert(title + ': ' + message);
        }
      };
    }

    // Cargar datos para selects al abrir el modal
    $('#createRubricaModal').on('show.bs.modal', function() {
      console.log('Cargando datos para el modal...');
      loadCompetenciasForCreate();
      loadCursosForCreate();
    });

    // Toggle de configuración personalizada de escalas
    $('#createUseCustomScales').on('change', function() {
      const customConfig = document.getElementById('createCustomScalesConfig');
      if (this.checked) {
        customConfig.classList.remove('d-none');
      } else {
        customConfig.classList.add('d-none');
      }
    });

    // Validación en tiempo real del nombre
    $('#createNombre').on('input', function() {
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
    function loadCompetenciasForCreate() {
      const select = $('#createCompetencia');
      select.html('<option value="">Cargando competencias...</option>');
      
      $.ajax({
        url: 'controllers/rubricas/competencias_controller.php',
        type: 'POST',
        data: { action: 'get_all_active' },
        dataType: 'json',
        timeout: 10000, // 10 segundos de timeout
        success: function(response) {
          console.log('Respuesta competencias:', response);
          
          if (response.success && response.data) {
            select.empty().append('<option value="">Seleccionar competencia...</option>');
            
            response.data.forEach(function(competencia) {
              const areaInfo = competencia.area_curricular_nombre ? ` - ${competencia.area_curricular_nombre}` : '';
              const codigoInfo = competencia.codigo ? ` (${competencia.codigo})` : '';
              
              const option = `<option value="${competencia.id}" 
                                data-area="${competencia.area_curricular_nombre || ''}"
                                data-codigo="${competencia.codigo || ''}">
                                ${competencia.nombre}${codigoInfo}${areaInfo}
                              </option>`;
              select.append(option);
            });
            
            console.log(`Cargadas ${response.data.length} competencias`);
          } else {
            select.html('<option value="">Error: No se pudieron cargar las competencias</option>');
            showNotification('warning', 'Advertencia', response.message || 'No se pudieron cargar las competencias');
          }
        },
        error: function(xhr, status, error) {
          console.error('Error cargando competencias:', { xhr, status, error, responseText: xhr.responseText });
          select.html('<option value="">Error al cargar competencias</option>');
          
          let errorMessage = 'Error de conexión al cargar competencias';
          if (xhr.responseJSON && xhr.responseJSON.message) {
            errorMessage = xhr.responseJSON.message;
          } else if (xhr.status === 404) {
            errorMessage = 'El controlador de competencias no fue encontrado. Verifique que el archivo existe.';
          } else if (xhr.status === 500) {
            errorMessage = 'Error interno del servidor al cargar competencias';
          }
          
          showNotification('error', 'Error', errorMessage);
        }
      });
    }

    // Función para cargar cursos
    function loadCursosForCreate() {
      const select = $('#createCurso');
      select.html('<option value="">Cargando cursos...</option>');
      
      $.ajax({
        url: 'controllers/rubricas/cursos_controller.php',
        type: 'POST',
        data: { action: 'get_all_active' },
        dataType: 'json',
        timeout: 10000, // 10 segundos de timeout
        success: function(response) {
          console.log('Respuesta cursos:', response);
          
          if (response.success && response.data) {
            select.empty().append('<option value="">Seleccionar curso...</option>');
            
            response.data.forEach(function(curso) {
              const gradoInfo = curso.grado_nombre ? ` - ${curso.grado_nombre}` : '';
              const areaInfo = curso.area_curricular_nombre ? ` (${curso.area_curricular_nombre})` : '';
              
              const option = `<option value="${curso.id}" 
                                data-grado="${curso.grado_nombre || ''}"
                                data-area="${curso.area_curricular_nombre || ''}">
                                ${curso.nombre}${gradoInfo}${areaInfo}
                              </option>`;
              select.append(option);
            });
            
            console.log(`Cargados ${response.data.length} cursos`);
          } else {
            select.html('<option value="">Error: No se pudieron cargar los cursos</option>');
            showNotification('warning', 'Advertencia', response.message || 'No se pudieron cargar los cursos');
          }
        },
        error: function(xhr, status, error) {
          console.error('Error cargando cursos:', { xhr, status, error, responseText: xhr.responseText });
          select.html('<option value="">Error al cargar cursos</option>');
          
          let errorMessage = 'Error de conexión al cargar cursos';
          if (xhr.responseJSON && xhr.responseJSON.message) {
            errorMessage = xhr.responseJSON.message;
          } else if (xhr.status === 404) {
            errorMessage = 'El controlador de cursos no fue encontrado. Verifique que el archivo existe.';
          } else if (xhr.status === 500) {
            errorMessage = 'Error interno del servidor al cargar cursos';
          }
          
          showNotification('error', 'Error', errorMessage);
        }
      });
    }

    // Manejar envío del formulario
    $('#createRubricaForm').on('submit', function(e) {
      e.preventDefault();
      
      // Validar formulario
      if (!this.checkValidity()) {
        e.stopPropagation();
        this.classList.add('was-validated');
        showNotification('warning', 'Formulario Incompleto', 'Por favor complete todos los campos requeridos');
        return;
      }

      // Mostrar estado de carga
      const btn = $('#createRubricaBtn');
      const btnContent = $('#createBtnContent');
      const btnLoading = $('#createBtnLoading');
      
      btn.prop('disabled', true);
      btnContent.addClass('d-none');
      btnLoading.removeClass('d-none');

      // Preparar datos del formulario
      const formData = new FormData(this);
      formData.append('action', 'create');

      // Agregar configuración de escalas si está personalizada
      if ($('#createUseCustomScales').is(':checked')) {
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
        timeout: 15000, // 15 segundos de timeout
        success: function(response) {
          console.log('Respuesta crear rúbrica:', response);
          
          if (response.success) {
            showNotification('success', 'Éxito', response.message || 'Rúbrica creada correctamente');
            $('#createRubricaModal').modal('hide');
            // Recargar la página para mostrar la nueva rúbrica
            setTimeout(function() { 
              location.reload(); 
            }, 1000);
          } else {
            showNotification('error', 'Error', response.message || 'Error al crear la rúbrica');
          }
        },
        error: function(xhr, status, error) {
          console.error('Error crear rúbrica:', { xhr, status, error, responseText: xhr.responseText });
          
          let errorMessage = 'Error de conexión al crear la rúbrica';
          if (xhr.responseJSON && xhr.responseJSON.message) {
            errorMessage = xhr.responseJSON.message;
          } else if (xhr.status === 500) {
            errorMessage = 'Error interno del servidor al crear la rúbrica';
          } else if (status === 'timeout') {
            errorMessage = 'La operación tardó demasiado tiempo. Intente nuevamente.';
          }
          
          showNotification('error', 'Error', errorMessage);
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
    $('#createRubricaModal').on('hidden.bs.modal', function() {
      $('#createRubricaForm')[0].reset();
      $('#createRubricaForm').removeClass('was-validated');
      $('#createNombre').removeClass('is-valid', 'is-invalid');
      $('#createUseCustomScales').prop('checked', false);
      $('#createCustomScalesConfig').addClass('d-none');
      
      // Restaurar opciones por defecto en selects
      $('#createCompetencia').html('<option value="">Seleccionar competencia...</option>');
      $('#createCurso').html('<option value="">Seleccionar curso...</option>');
    });
  });
</script>