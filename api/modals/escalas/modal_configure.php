<!-- modals/escalas/modal_configure.php -->
<div class="modal fade" id="configureEscalaModal" tabindex="-1" aria-labelledby="configureEscalaModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-xl">
    <div class="modal-content">
      <div class="modal-header bg-gradient" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
        <h5 class="modal-title" id="configureEscalaModalLabel">
          <iconify-icon icon="mdi:cog" class="me-2"></iconify-icon>
          Configurar Escalas de Calificación
        </h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      
      <form id="configureEscalaForm">
        <input type="hidden" id="configureEscalaId" name="escala_id">
        <input type="hidden" id="configureTipoEscala" name="tipo_escala">
        
        <div class="modal-body">
          <!-- Información de contexto -->
          <div class="row mb-4">
            <div class="col-12">
              <div class="alert alert-info border-0">
                <div class="d-flex align-items-center">
                  <iconify-icon icon="mdi:information" class="fs-3 me-3"></iconify-icon>
                  <div>
                    <h6 class="alert-heading mb-1">Configurando Escala: <span id="configureContexto">-</span></h6>
                    <small class="mb-0" id="configureDescripcion">Defina los rangos y características para cada escala de calificación</small>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Configuración según tipo -->
          <div id="configureLiteralSection" style="display: none;">
            <!-- Configuración para escala literal (A, B, C, D) -->
            <div class="row mb-4">
              <div class="col-12">
                <h6 class="text-muted text-uppercase fw-bold mb-3">
                  <iconify-icon icon="mdi:star-four-points" class="me-1"></iconify-icon>
                  Configuración de Escalas Literales
                </h6>
              </div>
            </div>

            <!-- Escala A -->
            <div class="row mb-4">
              <div class="col-12">
                <div class="card border-success">
                  <div class="card-header bg-success text-white">
                    <h6 class="mb-0">
                      <iconify-icon icon="mdi:star" class="me-2"></iconify-icon>
                      Escala A - Logro Destacado
                    </h6>
                  </div>
                  <div class="card-body">
                    <div class="row">
                      <div class="col-md-4">
                        <label class="form-label fw-semibold">Rango Numérico *</label>
                        <div class="input-group">
                          <input type="number" class="form-control" id="configureARangoMin" name="a_rango_min" 
                                 value="18" min="0" max="20" required>
                          <span class="input-group-text">-</span>
                          <input type="number" class="form-control" id="configureARangoMax" name="a_rango_max" 
                                 value="20" min="0" max="20" required>
                        </div>
                        <div class="form-text">Rango de puntos para esta escala</div>
                      </div>
                      <div class="col-md-4">
                        <label class="form-label fw-semibold">Descripción</label>
                        <input type="text" class="form-control" id="configureADescripcion" name="a_descripcion" 
                               value="Logro destacado" placeholder="Descripción de la escala">
                        <div class="form-text">Texto descriptivo</div>
                      </div>
                      <div class="col-md-4">
                        <label class="form-label fw-semibold">Color de Identificación</label>
                        <div class="d-flex gap-2">
                          <input type="color" class="form-control form-control-color" id="configureAColor" 
                                 name="a_color" value="#10b981" title="Color para escala A">
                          <input type="text" class="form-control" id="configureAColorText" 
                                 value="#10b981" maxlength="7" pattern="^#[0-9A-Fa-f]{6}$">
                        </div>
                        <div class="form-text">Color para reportes y gráficos</div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Escala B -->
            <div class="row mb-4">
              <div class="col-12">
                <div class="card border-primary">
                  <div class="card-header bg-primary text-white">
                    <h6 class="mb-0">
                      <iconify-icon icon="mdi:thumb-up" class="me-2"></iconify-icon>
                      Escala B - Logro Esperado
                    </h6>
                  </div>
                  <div class="card-body">
                    <div class="row">
                      <div class="col-md-4">
                        <label class="form-label fw-semibold">Rango Numérico *</label>
                        <div class="input-group">
                          <input type="number" class="form-control" id="configureBRangoMin" name="b_rango_min" 
                                 value="14" min="0" max="20" required>
                          <span class="input-group-text">-</span>
                          <input type="number" class="form-control" id="configureBRangoMax" name="b_rango_max" 
                                 value="17" min="0" max="20" required>
                        </div>
                        <div class="form-text">Rango de puntos para esta escala</div>
                      </div>
                      <div class="col-md-4">
                        <label class="form-label fw-semibold">Descripción</label>
                        <input type="text" class="form-control" id="configureBDescripcion" name="b_descripcion" 
                               value="Logro esperado" placeholder="Descripción de la escala">
                        <div class="form-text">Texto descriptivo</div>
                      </div>
                      <div class="col-md-4">
                        <label class="form-label fw-semibold">Color de Identificación</label>
                        <div class="d-flex gap-2">
                          <input type="color" class="form-control form-control-color" id="configureBColor" 
                                 name="b_color" value="#3b82f6" title="Color para escala B">
                          <input type="text" class="form-control" id="configureBColorText" 
                                 value="#3b82f6" maxlength="7" pattern="^#[0-9A-Fa-f]{6}$">
                        </div>
                        <div class="form-text">Color para reportes y gráficos</div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Escala C -->
            <div class="row mb-4">
              <div class="col-12">
                <div class="card border-warning">
                  <div class="card-header bg-warning text-dark">
                    <h6 class="mb-0">
                      <iconify-icon icon="mdi:clock" class="me-2"></iconify-icon>
                      Escala C - En Proceso
                    </h6>
                  </div>
                  <div class="card-body">
                    <div class="row">
                      <div class="col-md-4">
                        <label class="form-label fw-semibold">Rango Numérico *</label>
                        <div class="input-group">
                          <input type="number" class="form-control" id="configureCRangoMin" name="c_rango_min" 
                                 value="11" min="0" max="20" required>
                          <span class="input-group-text">-</span>
                          <input type="number" class="form-control" id="configureCRangoMax" name="c_rango_max" 
                                 value="13" min="0" max="20" required>
                        </div>
                        <div class="form-text">Rango de puntos para esta escala</div>
                      </div>
                      <div class="col-md-4">
                        <label class="form-label fw-semibold">Descripción</label>
                        <input type="text" class="form-control" id="configureCDescripcion" name="c_descripcion" 
                               value="En proceso" placeholder="Descripción de la escala">
                        <div class="form-text">Texto descriptivo</div>
                      </div>
                      <div class="col-md-4">
                        <label class="form-label fw-semibold">Color de Identificación</label>
                        <div class="d-flex gap-2">
                          <input type="color" class="form-control form-control-color" id="configureCColor" 
                                 name="c_color" value="#f59e0b" title="Color para escala C">
                          <input type="text" class="form-control" id="configureCColorText" 
                                 value="#f59e0b" maxlength="7" pattern="^#[0-9A-Fa-f]{6}$">
                        </div>
                        <div class="form-text">Color para reportes y gráficos</div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Escala D -->
            <div class="row mb-4">
              <div class="col-12">
                <div class="card border-danger">
                  <div class="card-header bg-danger text-white">
                    <h6 class="mb-0">
                      <iconify-icon icon="mdi:alert" class="me-2"></iconify-icon>
                      Escala D - En Inicio
                    </h6>
                  </div>
                  <div class="card-body">
                    <div class="row">
                      <div class="col-md-4">
                        <label class="form-label fw-semibold">Rango Numérico *</label>
                        <div class="input-group">
                          <input type="number" class="form-control" id="configureDRangoMin" name="d_rango_min" 
                                 value="0" min="0" max="20" required>
                          <span class="input-group-text">-</span>
                          <input type="number" class="form-control" id="configureDRangoMax" name="d_rango_max" 
                                 value="10" min="0" max="20" required>
                        </div>
                        <div class="form-text">Rango de puntos para esta escala</div>
                      </div>
                      <div class="col-md-4">
                        <label class="form-label fw-semibold">Descripción</label>
                        <input type="text" class="form-control" id="configureDDescripcion" name="d_descripcion" 
                               value="En inicio" placeholder="Descripción de la escala">
                        <div class="form-text">Texto descriptivo</div>
                      </div>
                      <div class="col-md-4">
                        <label class="form-label fw-semibold">Color de Identificación</label>
                        <div class="d-flex gap-2">
                          <input type="color" class="form-control form-control-color" id="configureDColor" 
                                 name="d_color" value="#ef4444" title="Color para escala D">
                          <input type="text" class="form-control" id="configureDColorText" 
                                 value="#ef4444" maxlength="7" pattern="^#[0-9A-Fa-f]{6}$">
                        </div>
                        <div class="form-text">Color para reportes y gráficos</div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Configuración para escala numérica -->
          <div id="configureNumericoSection" style="display: none;">
            <div class="row mb-4">
              <div class="col-12">
                <h6 class="text-muted text-uppercase fw-bold mb-3">
                  <iconify-icon icon="mdi:numeric" class="me-1"></iconify-icon>
                  Configuración de Escala Numérica
                </h6>
              </div>
            </div>

            <div class="row">
              <div class="col-md-4 mb-3">
                <label class="form-label fw-semibold">Valor Mínimo *</label>
                <input type="number" class="form-control" id="configureNumMin" name="num_min" value="0" min="0" required>
                <div class="form-text">Calificación mínima posible</div>
              </div>
              <div class="col-md-4 mb-3">
                <label class="form-label fw-semibold">Valor Máximo *</label>
                <input type="number" class="form-control" id="configureNumMax" name="num_max" value="20" min="1" required>
                <div class="form-text">Calificación máxima posible</div>
              </div>
              <div class="col-md-4 mb-3">
                <label class="form-label fw-semibold">Decimales Permitidos</label>
                <select class="form-select" id="configureNumDecimales" name="num_decimales">
                  <option value="0">Sin decimales (enteros)</option>
                  <option value="1">1 decimal (ej: 15.5)</option>
                  <option value="2" selected>2 decimales (ej: 15.75)</option>
                </select>
                <div class="form-text">Precisión decimal permitida</div>
              </div>
            </div>

            <div class="row">
              <div class="col-md-6 mb-3">
                <label class="form-label fw-semibold">Nota Aprobatoria</label>
                <input type="number" class="form-control" id="configureNotaAprobatoria" name="nota_aprobatoria" 
                       value="11" min="0" max="20" step="0.01">
                <div class="form-text">Calificación mínima para aprobar</div>
              </div>
              <div class="col-md-6 mb-3">
                <label class="form-label fw-semibold">Redondeo</label>
                <select class="form-select" id="configureRedondeo" name="redondeo">
                  <option value="normal">Redondeo normal (0.5 hacia arriba)</option>
                  <option value="ceil">Siempre hacia arriba</option>
                  <option value="floor">Siempre hacia abajo</option>
                  <option value="truncate">Truncar (sin redondeo)</option>
                </select>
                <div class="form-text">Método de redondeo para cálculos</div>
              </div>
            </div>
          </div>

          <!-- Configuración para escala descriptiva -->
          <div id="configureDescriptivoSection" style="display: none;">
            <div class="row mb-4">
              <div class="col-12">
                <h6 class="text-muted text-uppercase fw-bold mb-3">
                  <iconify-icon icon="mdi:text" class="me-1"></iconify-icon>
                  Configuración de Escala Descriptiva
                </h6>
              </div>
            </div>

            <div class="row">
              <div class="col-md-6 mb-3">
                <label class="form-label fw-semibold">Nivel Superior</label>
                <input type="text" class="form-control" id="configureDescSuperior" name="desc_superior" 
                       value="Competente" placeholder="Ej: Competente">
                <div class="form-text">Descripción del nivel más alto</div>
              </div>
              <div class="col-md-6 mb-3">
                <label class="form-label fw-semibold">Nivel Satisfactorio</label>
                <input type="text" class="form-control" id="configureDescSatisfactorio" name="desc_satisfactorio" 
                       value="Satisfactorio" placeholder="Ej: Satisfactorio">
                <div class="form-text">Descripción del nivel intermedio alto</div>
              </div>
              <div class="col-md-6 mb-3">
                <label class="form-label fw-semibold">Nivel En Desarrollo</label>
                <input type="text" class="form-control" id="configureDescDesarrollo" name="desc_desarrollo" 
                       value="En desarrollo" placeholder="Ej: En desarrollo">
                <div class="form-text">Descripción del nivel intermedio</div>
              </div>
              <div class="col-md-6 mb-3">
                <label class="form-label fw-semibold">Nivel Inicial</label>
                <input type="text" class="form-control" id="configureDescInicial" name="desc_inicial" 
                       value="Inicial" placeholder="Ej: Inicial">
                <div class="form-text">Descripción del nivel más bajo</div>
              </div>
            </div>
          </div>

          <!-- Validaciones y avisos -->
          <div class="row mt-4">
            <div class="col-12">
              <div class="alert alert-warning d-none" id="configureWarningMessage">
                <iconify-icon icon="mdi:alert" class="me-2"></iconify-icon>
                <span id="configureWarningText"></span>
              </div>
              
              <div class="alert alert-success d-none" id="configureSuccessMessage">
                <iconify-icon icon="mdi:check-circle" class="me-2"></iconify-icon>
                <span id="configureSuccessText"></span>
              </div>
            </div>
          </div>

          <!-- Vista previa -->
          <div class="row mt-4">
            <div class="col-12">
              <div class="card border-0 bg-light">
                <div class="card-header bg-transparent border-0">
                  <h6 class="card-title mb-0">
                    <iconify-icon icon="mdi:eye" class="me-2"></iconify-icon>
                    Vista Previa de Configuración
                  </h6>
                </div>
                <div class="card-body">
                  <div id="configurePreviewContent">
                    <div class="text-center text-muted py-3">
                      <iconify-icon icon="mdi:eye-off" class="fs-2 mb-2"></iconify-icon>
                      <p>Configure las escalas para ver la vista previa</p>
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
          <button type="button" class="btn btn-outline-primary" id="configureResetBtn">
            <iconify-icon icon="mdi:refresh"></iconify-icon>
            Restablecer Valores
          </button>
          <button type="button" class="btn btn-outline-success" id="configurePreviewBtn">
            <iconify-icon icon="mdi:eye"></iconify-icon>
            Vista Previa
          </button>
          <button type="submit" class="btn btn-primary" id="configureGuardarBtn">
            <span id="configureBtnContent">
              <iconify-icon icon="mdi:content-save"></iconify-icon>
              Guardar Configuración
            </span>
            <span id="configureBtnLoading" class="d-none">
              <div class="spinner-border spinner-border-sm me-2" role="status"></div>
              Guardando...
            </span>
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
$(document).ready(function() {
  // Variables globales
  let currentConfigureData = null;

  // Función para llenar el modal con datos de configuración
  window.fillConfigureModal = function(escalaId, data) {
    currentConfigureData = data;
    
    // Obtener información de la escala
    $.ajax({
      url: 'controllers/escalas/escalas_controller.php',
      type: 'POST',
      data: { action: 'get', id: escalaId },
      dataType: 'json',
      success: function(response) {
        if (response.success) {
          const escalaData = response.data;
          
          // Configurar información de contexto
          $('#configureEscalaId').val(escalaId);
          $('#configureTipoEscala').val(escalaData.tipo_escala);
          $('#configureContexto').text(`${escalaData.nivel_educativo.toUpperCase()} - ${escalaData.ano_academico_nombre}`);
          $('#configureDescripcion').text(`Configurando escala ${escalaData.tipo_escala} para ${escalaData.nivel_educativo}`);
          
          // Mostrar sección correspondiente
          showConfigureSection(escalaData.tipo_escala);
          
          // Llenar datos existentes si los hay
          if (data && data.configuracion) {
            fillExistingConfiguration(escalaData.tipo_escala, data.configuracion);
          } else {
            setDefaultConfiguration(escalaData.tipo_escala);
          }
          
          // Actualizar vista previa inicial
          updateConfigurePreview();
        }
      },
      error: function() {
        showNotification('error', 'Error', 'No se pudo cargar la información de la escala');
      }
    });
  };

  function showConfigureSection(tipo) {
    // Ocultar todas las secciones
    $('#configureLiteralSection').hide();
    $('#configureNumericoSection').hide();
    $('#configureDescriptivoSection').hide();
    
    // Mostrar la sección correspondiente
    switch (tipo) {
      case 'literal':
        $('#configureLiteralSection').show();
        break;
      case 'numerico':
        $('#configureNumericoSection').show();
        break;
      case 'descriptivo':
        $('#configureDescriptivoSection').show();
        break;
    }
  }

  function fillExistingConfiguration(tipo, configuracion) {
    try {
      const config = typeof configuracion === 'string' ? JSON.parse(configuracion) : configuracion;
      
      switch (tipo) {
        case 'literal':
          if (config.A) {
            $('#configureARangoMin').val(config.A.rango_min || 18);
            $('#configureARangoMax').val(config.A.rango_max || 20);
            $('#configureADescripcion').val(config.A.descripcion || 'Logro destacado');
            $('#configureAColor').val(config.A.color || '#10b981');
            $('#configureAColorText').val(config.A.color || '#10b981');
          }
          if (config.B) {
            $('#configureBRangoMin').val(config.B.rango_min || 14);
            $('#configureBRangoMax').val(config.B.rango_max || 17);
            $('#configureBDescripcion').val(config.B.descripcion || 'Logro esperado');
            $('#configureBColor').val(config.B.color || '#3b82f6');
            $('#configureBColorText').val(config.B.color || '#3b82f6');
          }
          if (config.C) {
            $('#configureCRangoMin').val(config.C.rango_min || 11);
            $('#configureCRangoMax').val(config.C.rango_max || 13);
            $('#configureCDescripcion').val(config.C.descripcion || 'En proceso');
            $('#configureCColor').val(config.C.color || '#f59e0b');
            $('#configureCColorText').val(config.C.color || '#f59e0b');
          }
          if (config.D) {
            $('#configureDRangoMin').val(config.D.rango_min || 0);
            $('#configureDRangoMax').val(config.D.rango_max || 10);
            $('#configureDDescripcion').val(config.D.descripcion || 'En inicio');
            $('#configureDColor').val(config.D.color || '#ef4444');
            $('#configureDColorText').val(config.D.color || '#ef4444');
          }
          break;
          
        case 'numerico':
          $('#configureNumMin').val(config.min || 0);
          $('#configureNumMax').val(config.max || 20);
          $('#configureNumDecimales').val(config.decimales || 2);
          $('#configureNotaAprobatoria').val(config.nota_aprobatoria || 11);
          $('#configureRedondeo').val(config.redondeo || 'normal');
          break;
          
        case 'descriptivo':
          $('#configureDescSuperior').val(config.superior || 'Competente');
          $('#configureDescSatisfactorio').val(config.satisfactorio || 'Satisfactorio');
          $('#configureDescDesarrollo').val(config.desarrollo || 'En desarrollo');
          $('#configureDescInicial').val(config.inicial || 'Inicial');
          break;
      }
    } catch (e) {
      console.error('Error al procesar configuración existente:', e);
      setDefaultConfiguration(tipo);
    }
  }

  function setDefaultConfiguration(tipo) {
    // Los valores por defecto ya están en el HTML
    // Esto es por si se necesita resetear programáticamente
  }

  // Sincronizar selectores de color con inputs de texto
  $('[id$="Color"]').on('change', function() {
    const colorValue = $(this).val().toUpperCase();
    const textInput = $('#' + this.id + 'Text');
    textInput.val(colorValue);
    updateConfigurePreview();
  });

  $('[id$="ColorText"]').on('input', function() {
    const hexValue = $(this).val();
    if (/^#[0-9A-Fa-f]{6}$/.test(hexValue)) {
      const colorPicker = $('#' + this.id.replace('Text', ''));
      colorPicker.val(hexValue);
      $(this).removeClass('is-invalid').addClass('is-valid');
    } else if (hexValue.length > 0) {
      $(this).removeClass('is-valid').addClass('is-invalid');
    } else {
      $(this).removeClass('is-valid is-invalid');
    }
    updateConfigurePreview();
  });

  // Validación en tiempo real para rangos
  $('[id$="RangoMin"], [id$="RangoMax"]').on('input', function() {
    validateRanges();
    updateConfigurePreview();
  });

  function validateRanges() {
    const tipo = $('#configureTipoEscala').val();
    
    if (tipo === 'literal') {
      // Validar que los rangos no se superpongan
      const ranges = [
        { min: parseInt($('#configureARangoMin').val()) || 0, max: parseInt($('#configureARangoMax').val()) || 0, name: 'A' },
        { min: parseInt($('#configureBRangoMin').val()) || 0, max: parseInt($('#configureBRangoMax').val()) || 0, name: 'B' },
        { min: parseInt($('#configureCRangoMin').val()) || 0, max: parseInt($('#configureCRangoMax').val()) || 0, name: 'C' },
        { min: parseInt($('#configureDRangoMin').val()) || 0, max: parseInt($('#configureDRangoMax').val()) || 0, name: 'D' }
      ];
      
      let hasErrors = false;
      let warningMessage = '';
      
      // Verificar que min <= max en cada rango
      ranges.forEach(range => {
        if (range.min > range.max) {
          hasErrors = true;
          warningMessage = `El rango mínimo de la escala ${range.name} no puede ser mayor que el máximo.`;
        }
      });
      
      // Verificar superposiciones
      if (!hasErrors) {
        for (let i = 0; i < ranges.length; i++) {
          for (let j = i + 1; j < ranges.length; j++) {
            if (rangesOverlap(ranges[i], ranges[j])) {
              hasErrors = true;
              warningMessage = `Los rangos de las escalas ${ranges[i].name} y ${ranges[j].name} se superponen.`;
              break;
            }
          }
          if (hasErrors) break;
        }
      }
      
      // Verificar cobertura completa (0-20)
      if (!hasErrors) {
        const sortedRanges = ranges.sort((a, b) => a.min - b.min);
        let expectedMin = 0;
        
        for (const range of sortedRanges) {
          if (range.min > expectedMin) {
            hasErrors = true;
            warningMessage = `Hay un vacío en la cobertura entre ${expectedMin} y ${range.min}.`;
            break;
          }
          expectedMin = range.max + 1;
        }
        
        if (!hasErrors && expectedMin <= 20) {
          // Todo está bien, mostrar mensaje de éxito
          showConfigureSuccess('Los rangos están correctamente configurados y cubren todo el espectro de calificación.');
        }
      }
      
      if (hasErrors) {
        showConfigureWarning(warningMessage);
      } else {
        hideConfigureMessages();
      }
    }
  }

  function rangesOverlap(range1, range2) {
    return Math.max(range1.min, range2.min) <= Math.min(range1.max, range2.max);
  }

  function showConfigureWarning(message) {
    $('#configureWarningText').text(message);
    $('#configureWarningMessage').removeClass('d-none');
    $('#configureSuccessMessage').addClass('d-none');
  }

  function showConfigureSuccess(message) {
    $('#configureSuccessText').text(message);
    $('#configureSuccessMessage').removeClass('d-none');
    $('#configureWarningMessage').addClass('d-none');
  }

  function hideConfigureMessages() {
    $('#configureWarningMessage').addClass('d-none');
    $('#configureSuccessMessage').addClass('d-none');
  }

  // Actualizar vista previa
  function updateConfigurePreview() {
    const tipo = $('#configureTipoEscala').val();
    const previewContainer = $('#configurePreviewContent');
    
    let previewHtml = '';
    
    switch (tipo) {
      case 'literal':
        const aMin = $('#configureARangoMin').val() || 18;
        const aMax = $('#configureARangoMax').val() || 20;
        const aDesc = $('#configureADescripcion').val() || 'Logro destacado';
        const aColor = $('#configureAColor').val() || '#10b981';
        
        const bMin = $('#configureBRangoMin').val() || 14;
        const bMax = $('#configureBRangoMax').val() || 17;
        const bDesc = $('#configureBDescripcion').val() || 'Logro esperado';
        const bColor = $('#configureBColor').val() || '#3b82f6';
        
        const cMin = $('#configureCRangoMin').val() || 11;
        const cMax = $('#configureCRangoMax').val() || 13;
        const cDesc = $('#configureCDescripcion').val() || 'En proceso';
        const cColor = $('#configureCColor').val() || '#f59e0b';
        
        const dMin = $('#configureDRangoMin').val() || 0;
        const dMax = $('#configureDRangoMax').val() || 10;
        const dDesc = $('#configureDDescripcion').val() || 'En inicio';
        const dColor = $('#configureDColor').val() || '#ef4444';
        
        previewHtml = `
          <div class="d-flex gap-2 flex-wrap justify-content-center">
            <span class="badge fs-6 px-3 py-2" style="background-color: ${aColor}; color: white;">
              A: ${aDesc} (${aMin}-${aMax})
            </span>
            <span class="badge fs-6 px-3 py-2" style="background-color: ${bColor}; color: white;">
              B: ${bDesc} (${bMin}-${bMax})
            </span>
            <span class="badge fs-6 px-3 py-2" style="background-color: ${cColor}; color: white;">
              C: ${cDesc} (${cMin}-${cMax})
            </span>
            <span class="badge fs-6 px-3 py-2" style="background-color: ${dColor}; color: white;">
              D: ${dDesc} (${dMin}-${dMax})
            </span>
          </div>
        `;
        break;
        
      case 'numerico':
        const numMin = $('#configureNumMin').val() || 0;
        const numMax = $('#configureNumMax').val() || 20;
        const decimales = $('#configureNumDecimales').val() || 2;
        const aprobatoria = $('#configureNotaAprobatoria').val() || 11;
        
        previewHtml = `
          <div class="text-center">
            <div class="d-flex gap-3 justify-content-center align-items-center flex-wrap">
              <div class="badge bg-primary fs-6 px-3 py-2">
                Rango: ${numMin} - ${numMax}
              </div>
              <div class="badge bg-success fs-6 px-3 py-2">
                Decimales: ${decimales}
              </div>
              <div class="badge bg-warning fs-6 px-3 py-2">
                Nota aprobatoria: ${aprobatoria}
              </div>
            </div>
          </div>
        `;
        break;
        
      case 'descriptivo':
        const superior = $('#configureDescSuperior').val() || 'Competente';
        const satisfactorio = $('#configureDescSatisfactorio').val() || 'Satisfactorio';
        const desarrollo = $('#configureDescDesarrollo').val() || 'En desarrollo';
        const inicial = $('#configureDescInicial').val() || 'Inicial';
        
        previewHtml = `
          <div class="d-flex gap-2 flex-wrap justify-content-center">
            <span class="badge bg-success fs-6 px-3 py-2">${superior}</span>
            <span class="badge bg-primary fs-6 px-3 py-2">${satisfactorio}</span>
            <span class="badge bg-warning fs-6 px-3 py-2">${desarrollo}</span>
            <span class="badge bg-danger fs-6 px-3 py-2">${inicial}</span>
          </div>
        `;
        break;
    }
    
    previewContainer.html(previewHtml);
  }

  // Eventos de los botones
  $('#configurePreviewBtn').on('click', function() {
    updateConfigurePreview();
    showConfigureSuccess('Vista previa actualizada correctamente.');
  });

  $('#configureResetBtn').on('click', function() {
    Swal.fire({
      title: '¿Restablecer valores?',
      text: 'Se restaurarán los valores predeterminados para este tipo de escala.',
      icon: 'question',
      showCancelButton: true,
      confirmButtonText: 'Sí, restablecer',
      cancelButtonText: 'Cancelar'
    }).then((result) => {
      if (result.isConfirmed) {
        const tipo = $('#configureTipoEscala').val();
        setDefaultConfiguration(tipo);
        updateConfigurePreview();
        showConfigureSuccess('Valores restablecidos a los predeterminados.');
      }
    });
  });

  // Actualizar vista previa en tiempo real
  $('#configureEscalaForm input, #configureEscalaForm select').on('input change', function() {
    setTimeout(updateConfigurePreview, 100);
  });

  // Manejar envío del formulario
  $('#configureEscalaForm').on('submit', function(e) {
    e.preventDefault();
    
    if (!this.checkValidity()) {
      e.stopPropagation();
      $(this).addClass('was-validated');
      return;
    }

    const formData = new FormData(this);
    formData.append('action', 'configure');
    
    // Deshabilitar botón y mostrar loading
    const btn = $('#configureGuardarBtn');
    const btnContent = $('#configureBtnContent');
    const btnLoading = $('#configureBtnLoading');
    
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
          showNotification('success', 'Configuración guardada', response.message || 'Las escalas fueron configuradas correctamente.');
          $('#configureEscalaModal').modal('hide');
          setTimeout(function() {
            location.reload();
          }, 1000);
        } else {
          showNotification('error', 'Error', response.message || 'Error al guardar la configuración.');
        }
      },
      error: function() {
        showNotification('error', 'Error', 'Error de conexión al guardar la configuración.');
      },
      complete: function() {
        // Rehabilitar botón
        btn.prop('disabled', false);
        btnContent.removeClass('d-none');
        btnLoading.addClass('d-none');
      }
    });
  });

  // Limpiar modal al cerrar
  $('#configureEscalaModal').on('hidden.bs.modal', function() {
    $('#configureEscalaForm')[0].reset();
    $('#configureEscalaForm').removeClass('was-validated');
    hideConfigureMessages();
    currentConfigureData = null;
    
    // Ocultar todas las secciones
    $('#configureLiteralSection').hide();
    $('#configureNumericoSection').hide();
    $('#configureDescriptivoSection').hide();
  });
});
</script>