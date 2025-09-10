/**
 * Utilidades de Notificación
 * Sistema de notificaciones usando SweetAlert2
 */

// Función principal de notificación
window.showNotification = function(type, title, message, options = {}) {
    // Verificar que SweetAlert2 esté disponible
    if (typeof Swal === 'undefined') {
        console.error('SweetAlert2 no está cargado');
        alert(`${title}: ${message}`);
        return;
    }

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
        case 'info':
            icon = 'info';
            confirmButtonColor = '#3b82f6';
            break;
    }

    const defaultOptions = {
        icon: icon,
        title: title,
        text: message,
        confirmButtonColor: confirmButtonColor,
        confirmButtonText: 'Entendido',
        allowOutsideClick: true,
        allowEscapeKey: true,
        timer: null,
        timerProgressBar: false
    };

    // Combinar opciones por defecto con opciones personalizadas
    const finalOptions = { ...defaultOptions, ...options };

    return Swal.fire(finalOptions);
};

// Notificación de éxito con auto-cierre
window.showSuccessNotification = function(title, message, autoClose = 3000) {
    return showNotification('success', title, message, {
        timer: autoClose,
        timerProgressBar: true,
        showConfirmButton: false
    });
};

// Notificación de error persistente
window.showErrorNotification = function(title, message) {
    return showNotification('error', title, message, {
        allowOutsideClick: false,
        allowEscapeKey: false
    });
};

// Confirmación de acción destructiva
window.showConfirmDialog = function(title, message, confirmText = 'Sí, confirmar', cancelText = 'Cancelar') {
    if (typeof Swal === 'undefined') {
        return Promise.resolve(confirm(`${title}\n\n${message}`));
    }

    return Swal.fire({
        title: title,
        text: message,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#ef4444',
        cancelButtonColor: '#6b7280',
        confirmButtonText: confirmText,
        cancelButtonText: cancelText,
        reverseButtons: true,
        allowOutsideClick: false
    }).then((result) => {
        return result.isConfirmed;
    });
};

// Confirmación de eliminación
window.showDeleteConfirm = function(itemName = 'este elemento') {
    return showConfirmDialog(
        '¿Está seguro?', 
        `El ${itemName} será eliminado permanentemente. Esta acción no se puede deshacer.`,
        'Sí, eliminar',
        'Cancelar'
    );
};

// Notificación de carga
window.showLoadingNotification = function(title = 'Procesando...', message = 'Por favor espere') {
    if (typeof Swal === 'undefined') {
        console.log(`${title}: ${message}`);
        return;
    }

    return Swal.fire({
        title: title,
        text: message,
        allowOutsideClick: false,
        allowEscapeKey: false,
        showConfirmButton: false,
        willOpen: () => {
            Swal.showLoading();
        }
    });
};

// Cerrar notificación de carga
window.hideLoadingNotification = function() {
    if (typeof Swal !== 'undefined') {
        Swal.close();
    }
};

// Toast notification (esquina de la pantalla)
window.showToast = function(type, message, position = 'top-end') {
    if (typeof Swal === 'undefined') {
        console.log(`Toast ${type}: ${message}`);
        return;
    }

    let icon = 'info';
    switch(type) {
        case 'success':
            icon = 'success';
            break;
        case 'error':
            icon = 'error';
            break;
        case 'warning':
            icon = 'warning';
            break;
    }

    const Toast = Swal.mixin({
        toast: true,
        position: position,
        showConfirmButton: false,
        timer: 3000,
        timerProgressBar: true,
        didOpen: (toast) => {
            toast.addEventListener('mouseenter', Swal.stopTimer);
            toast.addEventListener('mouseleave', Swal.resumeTimer);
        }
    });

    return Toast.fire({
        icon: icon,
        title: message
    });
};

// Notificación con HTML personalizado
window.showHtmlNotification = function(title, htmlContent, type = 'info') {
    if (typeof Swal === 'undefined') {
        alert(`${title}\n\n${htmlContent.replace(/<[^>]*>/g, '')}`);
        return;
    }

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

    return Swal.fire({
        title: title,
        html: htmlContent,
        icon: icon,
        confirmButtonColor: confirmButtonColor,
        confirmButtonText: 'Entendido'
    });
};

// Verificar y mostrar errores de validación de formulario
window.showFormValidationErrors = function(errors) {
    if (!errors || errors.length === 0) return;

    let errorHtml = '<ul style="text-align: left; margin: 0; padding-left: 20px;">';
    errors.forEach(error => {
        errorHtml += `<li>${error}</li>`;
    });
    errorHtml += '</ul>';

    showHtmlNotification('Errores de Validación', errorHtml, 'error');
};

// Manejar errores AJAX de forma consistente
window.handleAjaxError = function(xhr, status, error) {
    console.error('Error AJAX:', { xhr, status, error });
    
    let message = 'Error de conexión';
    let title = 'Error';
    
    if (xhr.status === 0) {
        message = 'No se pudo conectar al servidor. Verifique su conexión a internet.';
    } else if (xhr.status === 403) {
        title = 'Acceso Denegado';
        message = 'No tiene permisos para realizar esta acción.';
    } else if (xhr.status === 404) {
        title = 'No Encontrado';
        message = 'El recurso solicitado no fue encontrado.';
    } else if (xhr.status === 500) {
        title = 'Error del Servidor';
        message = 'Error interno del servidor. Contacte al administrador.';
    } else if (xhr.responseJSON && xhr.responseJSON.message) {
        message = xhr.responseJSON.message;
    } else {
        message = `Error ${xhr.status}: ${error}`;
    }
    
    showErrorNotification(title, message);
};

// Función de utilidad para mostrar progreso
window.showProgressNotification = function(title, initialMessage = 'Iniciando...') {
    if (typeof Swal === 'undefined') {
        console.log(`${title}: ${initialMessage}`);
        return {
            update: (message) => console.log(message),
            close: () => console.log('Completado')
        };
    }

    Swal.fire({
        title: title,
        text: initialMessage,
        allowOutsideClick: false,
        allowEscapeKey: false,
        showConfirmButton: false,
        willOpen: () => {
            Swal.showLoading();
        }
    });

    return {
        update: (message) => {
            Swal.update({
                text: message
            });
        },
        close: () => {
            Swal.close();
        }
    };
};

// Ejecutar cuando el DOM esté listo
$(document).ready(function() {
    // Configurar manejo global de errores AJAX
    $(document).ajaxError(function(event, xhr, settings, error) {
        // Solo manejar errores que no hayan sido manejados específicamente
        if (!settings.suppressGlobalErrorHandler) {
            handleAjaxError(xhr, settings, error);
        }
    });

    // Mostrar notificación de conexión perdida si es necesario
    window.addEventListener('offline', function() {
        showToast('error', 'Conexión perdida. Algunos elementos pueden no funcionar correctamente.');
    });

    window.addEventListener('online', function() {
        showToast('success', 'Conexión restablecida.');
    });
});

// Función de compatibility para navegadores antiguos
if (!window.Promise) {
    window.showNotification = function(type, title, message) {
        alert(`${title}: ${message}`);
    };
    window.showConfirmDialog = function(title, message) {
        return confirm(`${title}\n\n${message}`);
    };
}