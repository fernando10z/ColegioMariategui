<!-- modals/rubricas/modal_criterios.php -->
<div class="modal fade" id="criteriosModal" tabindex="-1" aria-labelledby="criteriosModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-xl">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="criteriosModalLabel">
          <iconify-icon icon="mdi:format-list-checks" class="me-2"></iconify-icon>
          Criterios de Evaluación
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      
      <div class="modal-body">
        <!-- Información de la Competencia -->
        <div class="row mb-4">
          <div class="col-12">
            <div class="card bg-primary bg-opacity-10 border-primary">
              <div class="card-body">
                <div class="row align-items-center">
                  <div class="col-md-8">
                    <h6 class="text-primary mb-1">
                      <iconify-icon icon="mdi:target" class="me-2"></iconify-icon>
                      <span id="criteriosCompetenciaNombre">Competencia</span>
                    </h6>
                    <p class="mb-0 text-muted small" id="criteriosCompetenciaDescripcion">
                      Descripción de la competencia
                    </p>
                  </div>
                  <div class="col-md-4 text-end">
                    <div class="d-flex justify-content-end gap-2">
                      <span class="badge bg-primary" id="criteriosCodigo">-</span>
                      <span class="badge bg-success" id="criteriosTotal">0 criterios</span>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Botón para agregar nuevo criterio -->
        <div class="row mb-3">
          <div class="col-12">
            <button type="button" class="btn btn-success" id="addCriterioBtn">
              <iconify-icon icon="mdi:plus"></iconify-icon>
              Agregar Nuevo Criterio
            </button>
          </div>
        </div>

        <!-- Lista de Criterios -->
        <div class="row">
          <div class="col-12">
            <div id="criteriosList" class="list-group">
              <!-- Los criterios se cargarán dinámicamente aquí -->
            </div>
            
            <!-- Estado vacío -->
            <div id="criteriosEmpty" class="text-center py-5 d-none">
              <iconify-icon icon="mdi:format-list-bulleted" class="fs-1 text-muted mb-3"></iconify-icon>
              <h5 class="text-muted">No hay criterios definidos</h5>
              <p class="text-muted">Agregue criterios de evaluación para esta competencia</p>
              <button type="button" class="btn btn-outline-success" onclick="$('#addCriterioBtn').click()">
                <iconify-icon icon="mdi:plus"></iconify-icon>
                Agregar Primer Criterio
              </button>
            </div>
          </div>
        </div>
      </div>
      
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
          <iconify-icon icon="mdi:close"></iconify-icon>
          Cerrar
        </button>
        <button type="button" class="btn btn-warning" id="reorderCriteriosBtn">
          <iconify-icon icon="mdi:sort"></iconify-icon>
          Reordenar Criterios
        </button>
        <button type="button" class="btn btn-primary" id="saveCriteriosOrderBtn" style="display: none;">
          <iconify-icon icon="mdi:content-save"></iconify-icon>
          Guardar Orden
        </button>
      </div>
    </div>
  </div>
</div>

<!-- Modal para Agregar/Editar Criterio -->
<div class="modal fade" id="criterioFormModal" tabindex="-1" aria-labelledby="criterioFormModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="criterioFormModalLabel">
          <iconify-icon icon="mdi:plus" class="me-2"></iconify-icon>
          Agregar Criterio
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      
      <form id="criterioForm">
        <input type="hidden" id="criterioCompetenciaId" name="competencia_id">
        <input type="hidden" id="criterioId" name="id">
        
        <div class="modal-body">
          <div class="row">
            <!-- Código del Criterio -->
            <div class="col-md-4 mb-3">
              <label for="criterioCodigo" class="form-label fw-semibold">
                <iconify-icon icon="mdi:barcode" class="me-1"></iconify-icon>
                Código *
              </label>
              <input type="text" class="form-control" id="criterioCodigo" name="codigo" required 
                     placeholder="Ej: COM1.1" maxlength="20">
              <div class="form-text">Código identificador único</div>
              <div class="invalid-feedback">
                El código es requerido
              </div>
            </div>

            <!-- Peso Porcentual -->
            <div class="col-md-4 mb-3">
              <label for="criterioPeso" class="form-label fw-semibold">
                <iconify-icon icon="mdi:percent" class="me-1"></iconify-icon>
                Peso (%) *
              </label>
              <input type="number" class="form-control" id="criterioPeso" name="peso_porcentaje" 
                     required min="0" max="100" step="0.01" placeholder="25.00">
              <div class="form-text">Porcentaje de importancia</div>
              <div class="invalid-feedback">
                El peso debe estar entre 0 y 100
              </div>
            </div>

            <!-- Es Principal -->
            <div class="col-md-4 mb-3">
              <label class="form-label fw-semibold">
                <iconify-icon icon="mdi:star" class="me-1"></iconify-icon>
                Tipo
              </label>
              <div class="form-check form-switch mt-2">
                <input class="form-check-input" type="checkbox" id="criterioEsPrincipal" name="es_principal">
                <label class="form-check-label" for="criterioEsPrincipal">
                  Criterio Principal
                </label>
              </div>
              <div class="form-text">Marcar si es un criterio clave</div>
            </div>

            <!-- Descripción -->
            <div class="col-12 mb-3">
              <label for="criterioDescripcion" class="form-label fw-semibold">
                <iconify-icon icon="mdi:text" class="me-1"></iconify-icon>
                Descripción *
              </label>
              <textarea class="form-control" id="criterioDescripcion" name="descripcion" rows="3" 
                        required placeholder="Descripción detallada del criterio de evaluación..."></textarea>
              <div class="form-text">Describa qué evalúa este criterio</div>
              <div class="invalid-feedback">
                La descripción es requerida
              </div>
            </div>

            <!-- Orden de Visualización -->
            <div class="col-12 mb-3">
              <label for="criterioOrden" class="form-label fw-semibold">
                <iconify-icon icon="mdi:sort-numeric" class="me-1"></iconify-icon>
                Orden de Visualización
              </label>
              <input type="number" class="form-control" id="criterioOrden" name="orden_visualizacion" 
                     min="1" placeholder="1">
              <div class="form-text">Orden en que aparecerá el criterio (se asigna automáticamente si se deja vacío)</div>
            </div>
          </div>
        </div>
        
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
            <iconify-icon icon="mdi:close"></iconify-icon>
            Cancelar
          </button>
          <button type="submit" class="btn btn-primary" id="saveCriterioBtn">
            <span id="criterioBtnContent">
              <iconify-icon icon="mdi:content-save"></iconify-icon>
              Guardar Criterio
            </span>
            <span id="criterioBtnLoading" class="d-none">
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
  let currentCompetenciaId = null;
  let criteriosData = [];
  let isReorderMode = false;

  // Función global para llenar el modal de criterios
  window.fillCriteriosModal = function(competenciaId, data) {
    currentCompetenciaId = competenciaId;
    criteriosData = data.criterios || [];
    
    // Llenar información de la competencia
    $('#criteriosCompetenciaNombre').text(data.competencia?.nombre || 'Competencia');
    $('#criteriosCompetenciaDescripcion').text(data.competencia?.descripcion || 'Sin descripción');
    $('#criteriosCodigo').text(data.competencia?.codigo || '-');
    $('#criteriosTotal').text(`${criteriosData.length} criterio${criteriosData.length !== 1 ? 's' : ''}`);
    
    // Configurar el input hidden del formulario
    $('#criterioCompetenciaId').val(competenciaId);
    
    // Cargar la lista de criterios
    loadCriteriosList();
  };

  // Función para cargar la lista de criterios
  function loadCriteriosList() {
    const container = $('#criteriosList');
    const emptyState = $('#criteriosEmpty');
    
    if (criteriosData.length === 0) {
      container.hide();
      emptyState.removeClass('d-none');
      return;
    }
    
    emptyState.addClass('d-none');
    container.show().empty();
    
    // Ordenar criterios por orden de visualización
    criteriosData.sort((a, b) => (a.orden_visualizacion || 999) - (b.orden_visualizacion || 999));
    
    criteriosData.forEach((criterio, index) => {
      const criterioHtml = createCriterioItem(criterio, index);
      container.append(criterioHtml);
    });
  }

  // Función para crear el HTML de un criterio
  function createCriterioItem(criterio, index) {
    const esPrincipal = parseInt(criterio.es_principal) === 1;
    const principalBadge = esPrincipal ? '<span class="badge bg-warning text-dark ms-2">Principal</span>' : '';
    
    return `
      <div class="list-group-item criterio-item" data-criterio-id="${criterio.id}" data-index="${index}">
        <div class="row align-items-center">
          <div class="col-md-1 text-center">
            <div class="reorder-handle d-none me-2" style="cursor: move;">
              <iconify-icon icon="mdi:drag-vertical" class="text-muted"></iconify-icon>
            </div>
            <span class="orden-numero badge bg-secondary">${criterio.orden_visualizacion || index + 1}</span>
          </div>
          <div class="col-md-2">
            <span class="fw-bold text-primary">${criterio.codigo || 'Sin código'}</span>
            ${principalBadge}
          </div>
          <div class="col-md-6">
            <div class="criterio-descripcion">
              ${criterio.descripcion || 'Sin descripción'}
            </div>
          </div>
          <div class="col-md-2 text-center">
            <span class="badge bg-info">${criterio.peso_porcentaje || 0}%</span>
          </div>
          <div class="col-md-1 text-end">
            <div class="btn-group btn-group-sm">
              <button type="button" class="btn btn-outline-primary edit-criterio-btn" 
                      data-criterio-id="${criterio.id}"
                      title="Editar criterio">
                <iconify-icon icon="mdi:pencil"></iconify-icon>
              </button>
              <button type="button" class="btn btn-outline-danger delete-criterio-btn" 
                      data-criterio-id="${criterio.id}"
                      title="Eliminar criterio">
                <iconify-icon icon="mdi:delete"></iconify-icon>
              </button>
            </div>
          </div>
        </div>
      </div>
    `;
  }

  // Botón para agregar nuevo criterio
  $('#addCriterioBtn').on('click', function() {
    // Limpiar formulario
    $('#criterioForm')[0].reset();
    $('#criterioId').val('');
    $('#criterioCompetenciaId').val(currentCompetenciaId);
    
    // Cambiar título del modal
    $('#criterioFormModalLabel').html('<iconify-icon icon="mdi:plus" class="me-2"></iconify-icon>Agregar Criterio');
    $('#criterioBtnContent').html('<iconify-icon icon="mdi:content-save"></iconify-icon>Guardar Criterio');
    
    // Asignar orden automático
    const nextOrder = criteriosData.length + 1;
    $('#criterioOrden').val(nextOrder);
    
    // Mostrar modal
    $('#criterioFormModal').modal('show');
  });

  // Editar criterio
  $(document).on('click', '.edit-criterio-btn', function() {
    const criterioId = $(this).data('criterio-id');
    const criterio = criteriosData.find(c => c.id == criterioId);
    
    if (criterio) {
      // Llenar formulario con datos del criterio
      $('#criterioId').val(criterio.id);
      $('#criterioCompetenciaId').val(currentCompetenciaId);
      $('#criterioCodigo').val(criterio.codigo || '');
      $('#criterioPeso').val(criterio.peso_porcentaje || '');
      $('#criterioEsPrincipal').prop('checked', parseInt(criterio.es_principal) === 1);
      $('#criterioDescripcion').val(criterio.descripcion || '');
      $('#criterioOrden').val(criterio.orden_visualizacion || '');
      
      // Cambiar título del modal
      $('#criterioFormModalLabel').html('<iconify-icon icon="mdi:pencil" class="me-2"></iconify-icon>Editar Criterio');
      $('#criterioBtnContent').html('<iconify-icon icon="mdi:content-save"></iconify-icon>Actualizar Criterio');
      
      // Mostrar modal
      $('#criterioFormModal').modal('show');
    }
  });

  // Eliminar criterio
  $(document).on('click', '.delete-criterio-btn', function() {
    const criterioId = $(this).data('criterio-id');
    const criterio = criteriosData.find(c => c.id == criterioId);
    
    if (criterio) {
      Swal.fire({
        title: '¿Está seguro?',
        text: `Se eliminará el criterio "${criterio.codigo || 'Sin código'}"`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar',
        confirmButtonColor: '#dc2626'
      }).then((result) => {
        if (result.isConfirmed) {
          deleteCriterio(criterioId);
        }
      });
    }
  });

  // Función para eliminar criterio
  function deleteCriterio(criterioId) {
    $.ajax({
      url: 'controllers/rubricas/criterios_controller.php',
      type: 'POST',
      data: { action: 'delete', id: criterioId },
      dataType: 'json',
      success: function(response) {
        if (response.success) {
          showNotification('success', 'Eliminado', 'Criterio eliminado correctamente');
          // Actualizar lista local
          criteriosData = criteriosData.filter(c => c.id != criterioId);
          loadCriteriosList();
          updateCriteriosTotal();
        } else {
          showNotification('error', 'Error', response.message || 'Error al eliminar el criterio');
        }
      },
      error: function() {
        showNotification('error', 'Error', 'Error de conexión al eliminar el criterio');
      }
    });
  }

  // Envío del formulario de criterio
  $('#criterioForm').on('submit', function(e) {
    e.preventDefault();
    
    if (!this.checkValidity()) {
      e.stopPropagation();
      this.classList.add('was-validated');
      return;
    }

    // Mostrar estado de carga
    const btn = $('#saveCriterioBtn');
    const btnContent = $('#criterioBtnContent');
    const btnLoading = $('#criterioBtnLoading');
    
    btn.prop('disabled', true);
    btnContent.addClass('d-none');
    btnLoading.removeClass('d-none');

    const formData = new FormData(this);
    const isEdit = $('#criterioId').val() !== '';
    formData.append('action', isEdit ? 'update' : 'create');

    $.ajax({
      url: 'controllers/rubricas/criterios_controller.php',
      type: 'POST',
      data: formData,
      processData: false,
      contentType: false,
      dataType: 'json',
      success: function(response) {
        if (response.success) {
          showNotification('success', 'Éxito', isEdit ? 'Criterio actualizado correctamente' : 'Criterio creado correctamente');
          $('#criterioFormModal').modal('hide');
          
          // Recargar criterios
          loadCriteriosFromServer();
        } else {
          showNotification('error', 'Error', response.message || 'Error al guardar el criterio');
        }
      },
      error: function() {
        showNotification('error', 'Error', 'Error de conexión al guardar el criterio');
      },
      complete: function() {
        btn.prop('disabled', false);
        btnContent.removeClass('d-none');
        btnLoading.addClass('d-none');
      }
    });
  });

  // Función para recargar criterios desde el servidor
  function loadCriteriosFromServer() {
    if (!currentCompetenciaId) return;
    
    $.ajax({
      url: 'controllers/rubricas/criterios_controller.php',
      type: 'POST',
      data: { action: 'get_by_competencia', competencia_id: currentCompetenciaId },
      dataType: 'json',
      success: function(response) {
        if (response.success) {
          criteriosData = response.data.criterios || [];
          loadCriteriosList();
          updateCriteriosTotal();
        }
      }
    });
  }

  // Función para actualizar el total de criterios
  function updateCriteriosTotal() {
    $('#criteriosTotal').text(`${criteriosData.length} criterio${criteriosData.length !== 1 ? 's' : ''}`);
  }

  // Modo reordenar
  $('#reorderCriteriosBtn').on('click', function() {
    isReorderMode = !isReorderMode;
    
    if (isReorderMode) {
      // Activar modo reordenar
      $(this).text('Cancelar Reordenar').removeClass('btn-warning').addClass('btn-secondary');
      $('#saveCriteriosOrderBtn').show();
      $('.reorder-handle').removeClass('d-none');
      $('.edit-criterio-btn, .delete-criterio-btn').addClass('d-none');
      
      // Hacer la lista sortable
      $('#criteriosList').sortable({
        handle: '.reorder-handle',
        update: function(event, ui) {
          // Actualizar números de orden visualmente
          $('#criteriosList .criterio-item').each(function(index) {
            $(this).find('.orden-numero').text(index + 1);
          });
        }
      });
    } else {
      // Desactivar modo reordenar
      $(this).text('Reordenar Criterios').removeClass('btn-secondary').addClass('btn-warning');
      $('#saveCriteriosOrderBtn').hide();
      $('.reorder-handle').addClass('d-none');
      $('.edit-criterio-btn, .delete-criterio-btn').removeClass('d-none');
      
      // Destruir sortable
      if ($('#criteriosList').hasClass('ui-sortable')) {
        $('#criteriosList').sortable('destroy');
      }
      
      // Recargar lista original
      loadCriteriosList();
    }
  });

  // Guardar nuevo orden
  $('#saveCriteriosOrderBtn').on('click', function() {
    const newOrder = [];
    $('#criteriosList .criterio-item').each(function(index) {
      const criterioId = $(this).data('criterio-id');
      newOrder.push({
        id: criterioId,
        orden_visualizacion: index + 1
      });
    });

    $.ajax({
      url: 'controllers/rubricas/criterios_controller.php',
      type: 'POST',
      data: { 
        action: 'update_order',
        criterios: JSON.stringify(newOrder)
      },
      dataType: 'json',
      success: function(response) {
        if (response.success) {
          showNotification('success', 'Éxito', 'Orden actualizado correctamente');
          
          // Actualizar datos locales
          newOrder.forEach(item => {
            const criterio = criteriosData.find(c => c.id == item.id);
            if (criterio) {
              criterio.orden_visualizacion = item.orden_visualizacion;
            }
          });
          
          // Salir del modo reordenar
          $('#reorderCriteriosBtn').click();
        } else {
          showNotification('error', 'Error', response.message || 'Error al actualizar el orden');
        }
      },
      error: function() {
        showNotification('error', 'Error', 'Error de conexión al actualizar el orden');
      }
    });
  });

  // Limpiar al cerrar modal principal
  $('#criteriosModal').on('hidden.bs.modal', function() {
    currentCompetenciaId = null;
    criteriosData = [];
    isReorderMode = false;
    
    // Resetear interfaz
    $('#reorderCriteriosBtn').text('Reordenar Criterios').removeClass('btn-secondary').addClass('btn-warning');
    $('#saveCriteriosOrderBtn').hide();
    $('.reorder-handle').addClass('d-none');
    $('.edit-criterio-btn, .delete-criterio-btn').removeClass('d-none');
    
    // Destruir sortable si existe
    if ($('#criteriosList').hasClass('ui-sortable')) {
      $('#criteriosList').sortable('destroy');
    }
    
    // Limpiar contenido
    $('#criteriosList').empty();
    $('#criteriosEmpty').addClass('d-none');
  });

  // Limpiar formulario al cerrar modal de criterio
  $('#criterioFormModal').on('hidden.bs.modal', function() {
    $('#criterioForm')[0].reset();
    $('#criterioForm').removeClass('was-validated');
  });

  // Validación en tiempo real del peso
  $('#criterioPeso').on('input', function() {
    const valor = parseFloat(this.value);
    if (valor >= 0 && valor <= 100) {
      this.classList.remove('is-invalid');
      this.classList.add('is-valid');
    } else if (this.value !== '') {
      this.classList.remove('is-valid');
      this.classList.add('is-invalid');
    } else {
      this.classList.remove('is-valid', 'is-invalid');
    }
  });
});
</script>

<style>
/* Estilos para el modal de criterios */
.criterio-item {
  border: 1px solid #dee2e6;
  margin-bottom: 0.5rem;
  border-radius: 0.375rem;
  transition: all 0.2s ease;
}

.criterio-item:hover {
  background-color: #f8f9fa;
  border-color: #6c757d;
}

.criterio-descripcion {
  font-size: 0.9rem;
  line-height: 1.4;
  color: #495057;
}

.reorder-handle {
  font-size: 1.2rem;
}

.orden-numero {
  font-weight: 600;
  min-width: 25px;
  display: inline-block;
}

/* Estilos para sortable */
.ui-sortable-helper {
  background-color: #fff !important;
  box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15) !important;
  border: 2px solid #007bff !important;
}

.ui-sortable-placeholder {
  background-color: #e3f2fd !important;
  border: 2px dashed #2196f3 !important;
  visibility: visible !important;
  height: 60px !important;
}

/* Responsive */
@media (max-width: 768px) {
  .criterio-item .row > div {
    margin-bottom: 0.5rem;
  }
  
  .criterio-item .col-md-1,
  .criterio-item .col-md-2 {
    text-align: center;
  }
  
  .btn-group-sm .btn {
    padding: 0.25rem 0.4rem;
    font-size: 0.875rem;
  }
}

/* Estados del formulario */
.was-validated .form-control:valid {
  border-color: #198754;
}

.was-validated .form-control:invalid {
  border-color: #dc3545;
}
</style>