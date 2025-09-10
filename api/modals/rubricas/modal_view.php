<!-- modals/rubricas/modal_view.php -->
<div class="modal fade" id="viewRubricaModal" tabindex="-1" aria-labelledby="viewRubricaModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="viewRubricaModalLabel">
          <iconify-icon icon="mdi:clipboard-text-search" class="me-2"></iconify-icon>
          Detalles de la Rúbrica
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      
      <div class="modal-body">
        <div class="row">
          <!-- Información Básica -->
          <div class="col-12 mb-4">
            <div class="d-flex align-items-center justify-content-between">
              <h6 class="text-muted text-uppercase fw-bold mb-0">
                <iconify-icon icon="mdi:information-outline" class="me-1"></iconify-icon>
                Información Básica
              </h6>
              <span id="viewEstadoBadge" class="badge"></span>
            </div>
            <hr class="mt-2 mb-3">
          </div>

          <!-- Nombre y Descripción -->
          <div class="col-12 mb-3">
            <label class="form-label fw-semibold text-muted small">
              <iconify-icon icon="mdi:clipboard-text" class="me-1"></iconify-icon>
              NOMBRE DE LA RÚBRICA
            </label>
            <div class="card bg-light border-0">
              <div class="card-body py-2">
                <h6 class="mb-0 text-dark" id="viewNombre">-</h6>
              </div>
            </div>
          </div>

          <div class="col-12 mb-4" id="viewDescripcionContainer">
            <label class="form-label fw-semibold text-muted small">
              <iconify-icon icon="mdi:text" class="me-1"></iconify-icon>
              DESCRIPCIÓN
            </label>
            <div class="card bg-light border-0">
              <div class="card-body py-2">
                <p class="mb-0 text-dark" id="viewDescripcion">-</p>
              </div>
            </div>
          </div>

          <!-- Configuración de Evaluación -->
          <div class="col-12 mb-4">
            <h6 class="text-muted text-uppercase fw-bold mb-3">
              <iconify-icon icon="mdi:cog-outline" class="me-1"></iconify-icon>
              Configuración de Evaluación
            </h6>
          </div>

          <!-- Competencia y Curso -->
          <div class="col-md-6 mb-3">
            <label class="form-label fw-semibold text-muted small">
              <iconify-icon icon="mdi:target" class="me-1"></iconify-icon>
              COMPETENCIA ASOCIADA
            </label>
            <div class="card bg-light border-0">
              <div class="card-body py-2">
                <div id="viewCompetencia" class="d-flex align-items-center">
                  <span class="text-dark">-</span>
                </div>
              </div>
            </div>
          </div>

          <div class="col-md-6 mb-3">
            <label class="form-label fw-semibold text-muted small">
              <iconify-icon icon="mdi:book-open-page-variant" class="me-1"></iconify-icon>
              CURSO
            </label>
            <div class="card bg-light border-0">
              <div class="card-body py-2">
                <div id="viewCurso" class="d-flex align-items-center">
                  <span class="text-dark">-</span>
                </div>
              </div>
            </div>
          </div>

          <!-- Tipo de Evaluación y Criterios -->
          <div class="col-md-6 mb-3">
            <label class="form-label fw-semibold text-muted small">
              <iconify-icon icon="mdi:clipboard-check" class="me-1"></iconify-icon>
              TIPO DE EVALUACIÓN
            </label>
            <div class="card bg-light border-0">
              <div class="card-body py-2">
                <span id="viewTipoEvaluacion" class="badge">-</span>
              </div>
            </div>
          </div>

          <div class="col-md-6 mb-3">
            <label class="form-label fw-semibold text-muted small">
              <iconify-icon icon="mdi:format-list-checks" class="me-1"></iconify-icon>
              CRITERIOS DE EVALUACIÓN
            </label>
            <div class="card bg-light border-0">
              <div class="card-body py-2 d-flex align-items-center justify-content-between">
                <span id="viewCriteriosCount" class="text-dark">-</span>
                <button type="button" class="btn btn-outline-primary btn-sm" id="viewVerCriteriosBtn">
                  <iconify-icon icon="mdi:eye"></iconify-icon>
                  Ver Criterios
                </button>
              </div>
            </div>
          </div>

          <!-- Configuración de Escalas -->
          <div class="col-12 mb-4" id="viewEscalasContainer">
            <label class="form-label fw-semibold text-muted small">
              <iconify-icon icon="mdi:star-settings" class="me-1"></iconify-icon>
              CONFIGURACIÓN DE ESCALAS
            </label>
            <div class="card bg-light border-0">
              <div class="card-body">
                <div id="viewEscalasContent">
                  <div class="text-muted small">
                    <iconify-icon icon="mdi:information" class="me-1"></iconify-icon>
                    Se utiliza la configuración de escalas por defecto del sistema
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Información de Registro -->
          <div class="col-12 mb-4">
            <h6 class="text-muted text-uppercase fw-bold mb-3">
              <iconify-icon icon="mdi:information-variant" class="me-1"></iconify-icon>
              Información de Registro
            </h6>
          </div>

          <div class="col-md-6 mb-3">
            <label class="form-label fw-semibold text-muted small">
              <iconify-icon icon="mdi:calendar" class="me-1"></iconify-icon>
              FECHA DE CREACIÓN
            </label>
            <div class="card bg-light border-0">
              <div class="card-body py-2">
                <span class="text-dark" id="viewFechaCreacion">-</span>
              </div>
            </div>
          </div>

          <div class="col-md-6 mb-3">
            <label class="form-label fw-semibold text-muted small">
              <iconify-icon icon="mdi:account" class="me-1"></iconify-icon>
              CREADO POR
            </label>
            <div class="card bg-light border-0">
              <div class="card-body py-2">
                <span class="text-dark" id="viewCreadoPor">-</span>
              </div>
            </div>
          </div>

          <!-- Estadísticas de Uso -->
          <div class="col-12 mb-4" id="viewEstadisticasContainer">
            <h6 class="text-muted text-uppercase fw-bold mb-3">
              <iconify-icon icon="mdi:chart-line" class="me-1"></iconify-icon>
              Estadísticas de Uso
            </h6>
            
            <div class="row g-3">
              <div class="col-md-4">
                <div class="card border-primary bg-primary bg-opacity-10">
                  <div class="card-body text-center py-3">
                    <iconify-icon icon="mdi:clipboard-list" class="fs-1 text-primary mb-2"></iconify-icon>
                    <h5 class="mb-1" id="viewActividades">0</h5>
                    <small class="text-muted">Actividades Asociadas</small>
                  </div>
                </div>
              </div>
              
              <div class="col-md-4">
                <div class="card border-success bg-success bg-opacity-10">
                  <div class="card-body text-center py-3">
                    <iconify-icon icon="mdi:account-group" class="fs-1 text-success mb-2"></iconify-icon>
                    <h5 class="mb-1" id="viewEstudiantes">0</h5>
                    <small class="text-muted">Estudiantes Evaluados</small>
                  </div>
                </div>
              </div>
              
              <div class="col-md-4">
                <div class="card border-warning bg-warning bg-opacity-10">
                  <div class="card-body text-center py-3">
                    <iconify-icon icon="mdi:star" class="fs-1 text-warning mb-2"></iconify-icon>
                    <h5 class="mb-1" id="viewCalificaciones">0</h5>
                    <small class="text-muted">Calificaciones Registradas</small>
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
        <button type="button" class="btn btn-warning" id="viewEditarBtn">
          <iconify-icon icon="mdi:pencil"></iconify-icon>
          Editar Rúbrica
        </button>
        <button type="button" class="btn btn-primary" id="viewCriteriosBtn">
          <iconify-icon icon="mdi:format-list-checks"></iconify-icon>
          Gestionar Criterios
        </button>
      </div>
    </div>
  </div>
</div>

<script>
// FUNCIÓN GLOBAL para cargar estadísticas - DEBE estar fuera del document ready
window.loadRubricaStatistics = function(rubricaId) {
  console.log('Cargando estadísticas para rúbrica:', rubricaId);
  
  // Resetear estadísticas mientras se cargan
  $('#viewActividades').text('...');
  $('#viewEstudiantes').text('...');
  $('#viewCalificaciones').text('...');
  
  $.ajax({
    url: 'controllers/rubricas/rubricas_controller.php',
    type: 'POST',
    data: { action: 'get_statistics', id: rubricaId },
    dataType: 'json',
    timeout: 10000, // 10 segundos timeout
    success: function(response) {
      console.log('Respuesta estadísticas:', response);
      if (response.success && response.data) {
        const stats = response.data;
        $('#viewActividades').text(stats.actividades || 0);
        $('#viewEstudiantes').text(stats.estudiantes || 0);
        $('#viewCalificaciones').text(stats.calificaciones || 0);
      } else {
        console.warn('No se pudieron cargar las estadísticas:', response.message);
        $('#viewActividades').text('0');
        $('#viewEstudiantes').text('0');
        $('#viewCalificaciones').text('0');
      }
    },
    error: function(xhr, status, error) {
      console.error('Error cargando estadísticas:', {xhr, status, error});
      // Mostrar valores por defecto en caso de error
      $('#viewActividades').text('0');
      $('#viewEstudiantes').text('0');
      $('#viewCalificaciones').text('0');
    }
  });
};

// Función global para llenar el modal de visualización - DEBE estar fuera del document ready
window.fillViewModal = function(data) {
  console.log('Datos recibidos en fillViewModal:', data);
  
  try {
    // Almacenar datos globalmente
    window.currentRubricaData = data;
    
    // Llenar información básica
    $('#viewNombre').text(data.nombre || 'Sin nombre');
    
    // Descripción (ocultar sección si está vacía)
    if (data.descripcion && data.descripcion.trim() && data.descripcion !== 'asd') {
      $('#viewDescripcion').text(data.descripcion);
      $('#viewDescripcionContainer').show();
    } else {
      $('#viewDescripcionContainer').hide();
    }

    // Estado
    const estado = parseInt(data.estado) === 1 ? 'Activa' : 'Inactiva';
    const estadoClass = parseInt(data.estado) === 1 ? 'bg-success text-white' : 'bg-danger text-white';
    $('#viewEstadoBadge').text(estado).attr('class', `badge ${estadoClass}`);

    // Competencia
    if (data.competencia_nombre && data.competencia_nombre !== 'Sin competencia') {
      let competenciaHtml = `<span class="fw-semibold">${data.competencia_nombre}</span>`;
      if (data.competencia_codigo) {
        competenciaHtml += `<br><small class="text-muted">${data.competencia_codigo}</small>`;
      }
      if (data.competencia_descripcion) {
        competenciaHtml += `<br><small class="text-info">${data.competencia_descripcion}</small>`;
      }
      $('#viewCompetencia').html(competenciaHtml);
    } else {
      $('#viewCompetencia').html('<span class="text-muted">Sin competencia asignada</span>');
    }

    // Curso
    if (data.curso_nombre && data.curso_nombre !== 'Sin curso') {
      let cursoHtml = `<span class="fw-semibold">${data.curso_nombre}</span>`;
      if (data.grado_nombre) {
        cursoHtml += `<br><small class="text-muted">${data.grado_nombre}</small>`;
      }
      if (data.area_curricular_nombre) {
        cursoHtml += `<br><small class="text-info">${data.area_curricular_nombre}</small>`;
      }
      $('#viewCurso').html(cursoHtml);
    } else {
      $('#viewCurso').html('<span class="text-muted">Sin curso asignado</span>');
    }

    // Tipo de evaluación
    let tipoClass = 'bg-secondary text-white';
    let tipoText = 'No definido';
    
    switch (data.tipo_evaluacion) {
      case 'diagnostica':
        tipoClass = 'bg-warning text-dark';
        tipoText = 'Diagnóstica';
        break;
      case 'formativa':
        tipoClass = 'bg-primary text-white';
        tipoText = 'Formativa';
        break;
      case 'sumativa':
        tipoClass = 'bg-success text-white';
        tipoText = 'Sumativa';
        break;
    }
    
    $('#viewTipoEvaluacion').text(tipoText).attr('class', `badge ${tipoClass}`);

    // Criterios de evaluación
    const criteriosCount = parseInt(data.total_criterios) || 0;
    $('#viewCriteriosCount').text(`${criteriosCount} criterio${criteriosCount !== 1 ? 's' : ''} de evaluación`);

    // Configuración de escalas
    if (data.configuracion_escalas) {
      try {
        const config = typeof data.configuracion_escalas === 'string' 
                      ? JSON.parse(data.configuracion_escalas) 
                      : data.configuracion_escalas;
        
        if (config && Object.keys(config).length > 0) {
          let escalasHtml = '<div class="row g-2">';
          Object.keys(config).sort().forEach(key => {
            const escala = config[key];
            const descripcion = escala.descripcion || 'Sin descripción';
            escalasHtml += `
              <div class="col-md-3">
                <div class="text-center p-2 border rounded">
                  <div class="fw-bold text-primary">${key}</div>
                  <small class="text-muted">${descripcion}</small>
                </div>
              </div>
            `;
          });
          escalasHtml += '</div>';
          $('#viewEscalasContent').html(escalasHtml);
        }
      } catch (e) {
        console.error('Error al parsear configuración de escalas:', e);
      }
    }

    // Información de creación
    if (data.fecha_creacion) {
      try {
        // Manejar formato de fecha MySQL
        const fechaStr = data.fecha_creacion.replace(/-/g, '/');
        const fecha = new Date(fechaStr);
        
        if (!isNaN(fecha.getTime())) {
          $('#viewFechaCreacion').text(fecha.toLocaleDateString('es-ES', {
            year: 'numeric',
            month: 'long',
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
          }));
        } else {
          $('#viewFechaCreacion').text(data.fecha_creacion);
        }
      } catch (e) {
        $('#viewFechaCreacion').text(data.fecha_creacion);
      }
    } else {
      $('#viewFechaCreacion').text('No disponible');
    }
    
    $('#viewCreadoPor').text(data.creado_por_nombre || 'Sistema');

    // Cargar estadísticas de uso - Ahora la función está disponible globalmente
    if (typeof window.loadRubricaStatistics === 'function') {
      window.loadRubricaStatistics(data.id);
    } else {
      console.warn('Función loadRubricaStatistics no disponible');
      // Valores por defecto si no se puede cargar
      $('#viewActividades').text('0');
      $('#viewEstudiantes').text('0');
      $('#viewCalificaciones').text('0');
    }
    
    console.log('Modal llenado correctamente');
    
  } catch (error) {
    console.error('Error al llenar modal de vista:', error);
    if (typeof showNotification === 'function') {
      showNotification('error', 'Error', 'Error al cargar la información de la rúbrica');
    }
  }
};

$(document).ready(function() {

  // Botón para ver criterios desde el contenido
  $('#viewVerCriteriosBtn').on('click', function() {
    const currentData = window.currentRubricaData;
    if (currentData && currentData.competencia_id) {
      // Cerrar modal correctamente sin aria-hidden issues
      const modal = bootstrap.Modal.getInstance(document.getElementById('viewRubricaModal'));
      if (modal) {
        modal.hide();
      } else {
        $('#viewRubricaModal').modal('hide');
      }
      
      setTimeout(() => {
        if (typeof configureCriterios === 'function') {
          configureCriterios(currentData.competencia_id);
        } else {
          console.warn('Función configureCriterios no disponible');
          if (typeof showNotification === 'function') {
            showNotification('warning', 'Advertencia', 'Función para gestionar criterios no disponible');
          }
        }
      }, 300);
    } else {
      if (typeof showNotification === 'function') {
        showNotification('warning', 'Advertencia', 'Esta rúbrica no tiene una competencia asignada');
      }
    }
  });

  // Botón para editar desde el footer
  $('#viewEditarBtn').on('click', function() {
    const currentData = window.currentRubricaData;
    if (currentData) {
      // Cerrar modal correctamente sin aria-hidden issues
      const modal = bootstrap.Modal.getInstance(document.getElementById('viewRubricaModal'));
      if (modal) {
        modal.hide();
      } else {
        $('#viewRubricaModal').modal('hide');
      }
      
      setTimeout(() => {
        if (typeof editRubrica === 'function') {
          editRubrica(currentData.id);
        } else {
          console.warn('Función editRubrica no disponible');
          if (typeof showNotification === 'function') {
            showNotification('warning', 'Advertencia', 'Función para editar rúbrica no disponible');
          }
        }
      }, 300);
    }
  });

  // Botón para gestionar criterios desde el footer
  $('#viewCriteriosBtn').on('click', function() {
    const currentData = window.currentRubricaData;
    if (currentData && currentData.competencia_id) {
      // Cerrar modal correctamente sin aria-hidden issues
      const modal = bootstrap.Modal.getInstance(document.getElementById('viewRubricaModal'));
      if (modal) {
        modal.hide();
      } else {
        $('#viewRubricaModal').modal('hide');
      }
      
      setTimeout(() => {
        if (typeof configureCriterios === 'function') {
          configureCriterios(currentData.competencia_id);
        } else {
          console.warn('Función configureCriterios no disponible');
          if (typeof showNotification === 'function') {
            showNotification('warning', 'Advertencia', 'Función para gestionar criterios no disponible');
          }
        }
      }, 300);
    } else {
      if (typeof showNotification === 'function') {
        showNotification('warning', 'Advertencia', 'Esta rúbrica no tiene una competencia asignada');
      }
    }
  });

  // Manejo mejorado del evento de cerrar modal para evitar problemas de aria-hidden
  $('#viewRubricaModal').on('hide.bs.modal', function(e) {
    // Remover aria-hidden antes de cerrar para evitar conflictos
    $(this).removeAttr('aria-hidden');
  });

  // Limpiar datos al cerrar modal
  $('#viewRubricaModal').on('hidden.bs.modal', function() {
    console.log('Limpiando modal al cerrar');
    
    // Limpiar datos globales
    window.currentRubricaData = null;
    
    // Resetear contenido
    $('#viewNombre').text('-');
    $('#viewDescripcion').text('-');
    $('#viewEstadoBadge').text('').attr('class', 'badge');
    $('#viewCompetencia').html('<span class="text-dark">-</span>');
    $('#viewCurso').html('<span class="text-dark">-</span>');
    $('#viewTipoEvaluacion').text('-').attr('class', 'badge');
    $('#viewCriteriosCount').text('-');
    $('#viewFechaCreacion').text('-');
    $('#viewCreadoPor').text('-');
    $('#viewActividades').text('0');
    $('#viewEstudiantes').text('0');
    $('#viewCalificaciones').text('0');
    
    // Resetear configuración de escalas
    $('#viewEscalasContent').html(`
      <div class="text-muted small">
        <iconify-icon icon="mdi:information" class="me-1"></iconify-icon>
        Se utiliza la configuración de escalas por defecto del sistema
      </div>
    `);
    
    // Mostrar sección de descripción por defecto
    $('#viewDescripcionContainer').show();
    
    // Restaurar aria-hidden para futuras aperturas
    $(this).attr('aria-hidden', 'true');
  });

  // Manejo del evento shown para asegurar que el modal esté completamente cargado
  $('#viewRubricaModal').on('shown.bs.modal', function() {
    // Remover aria-hidden cuando el modal esté completamente mostrado
    $(this).removeAttr('aria-hidden');
    console.log('Modal de vista mostrado correctamente');
  });
});
</script>

<style>
/* Estilos específicos para el modal de visualización */
#viewRubricaModal .card.bg-light {
  background-color: #f8f9fa !important;
}

#viewRubricaModal .form-label.small {
  font-size: 0.75rem;
  font-weight: 600;
  margin-bottom: 0.5rem;
}

#viewRubricaModal .card-body {
  padding: 0.75rem 1rem;
}

#viewRubricaModal hr {
  opacity: 0.3;
}

/* Estilos para las tarjetas de estadísticas */
#viewRubricaModal .card.border-primary {
  border-width: 2px !important;
}

#viewRubricaModal .card.border-success {
  border-width: 2px !important;
}

#viewRubricaModal .card.border-warning {
  border-width: 2px !important;
}

/* Animación sutil para las estadísticas */
#viewRubricaModal .card h5 {
  transition: transform 0.2s ease;
}

#viewRubricaModal .card:hover h5 {
  transform: scale(1.1);
}

/* Espaciado mejorado */
#viewRubricaModal .modal-body {
  max-height: 80vh;
  overflow-y: auto;
}

/* Scrollbar personalizado */
#viewRubricaModal .modal-body::-webkit-scrollbar {
  width: 6px;
}

#viewRubricaModal .modal-body::-webkit-scrollbar-track {
  background: #f1f1f1;
  border-radius: 10px;
}

#viewRubricaModal .modal-body::-webkit-scrollbar-thumb {
  background: #c1c1c1;
  border-radius: 10px;
}

#viewRubricaModal .modal-body::-webkit-scrollbar-thumb:hover {
  background: #a8a8a8;
}

/* Mejorar badges */
#viewRubricaModal .badge {
  font-size: 0.75rem;
  padding: 0.375rem 0.75rem;
}

/* Mejorar información adicional */
#viewRubricaModal .text-info {
  font-size: 0.8rem;
  font-style: italic;
}

/* Loading state para estadísticas */
#viewRubricaModal .card h5:contains('...') {
  opacity: 0.6;
  animation: pulse 1.5s infinite;
}

@keyframes pulse {
  0% { opacity: 0.6; }
  50% { opacity: 1; }
  100% { opacity: 0.6; }
}

/* Arreglar problema de foco en modal */
#viewRubricaModal.modal.fade:not(.show) {
  display: none !important;
}

#viewRubricaModal .btn-close:focus {
  outline: 2px solid #0d6efd;
  outline-offset: 2px;
}
</style>