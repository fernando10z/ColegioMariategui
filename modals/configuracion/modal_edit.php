<!-- modals/configuracion/modal_edit.php -->
<div class="modal fade" id="editConfiguracionModal" tabindex="-1" aria-labelledby="editConfiguracionModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-xl">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="editConfiguracionModalLabel">
          <iconify-icon icon="mdi:cog-outline" class="me-2"></iconify-icon>
          Editar Configuración del Sistema
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      
      <form id="editConfiguracionForm" enctype="multipart/form-data">
        <input type="hidden" id="editConfiguracionId" name="id">
        
        <div class="modal-body">
          <div class="row">
            <!-- Logo actual y upload -->
            <div class="col-12 mb-4">
              <label class="form-label fw-semibold">
                <iconify-icon icon="mdi:image" class="me-1"></iconify-icon>
                Logo Institucional
              </label>
              
              <!-- Logo actual -->
              <div class="mb-3">
                <label class="form-label text-muted small">Logo Actual:</label>
                <div id="currentLogo" class="border rounded p-3 text-center bg-light">
                  <!-- Se llenará dinámicamente -->
                </div>
              </div>
              
              <!-- Nuevo logo -->
              <div class="border-2 border-dashed border-light rounded-3 p-4 text-center" id="editLogoUploadArea">
                <input type="file" class="form-control d-none" id="editLogoInput" name="logo" accept="image/*">
                <div id="editLogoPreview" class="d-none">
                  <img id="editLogoPreviewImg" src="" alt="Preview" class="img-fluid mb-3" style="max-height: 150px; border-radius: 8px;">
                  <br>
                  <button type="button" class="btn btn-outline-danger btn-sm" id="editRemoveLogo">
                    <iconify-icon icon="mdi:delete"></iconify-icon>
                    Remover Nuevo Logo
                  </button>
                </div>
                <div id="editLogoUploadContent">
                  <iconify-icon icon="mdi:cloud-upload" class="fs-1 text-muted mb-3 d-block"></iconify-icon>
                  <p class="text-muted mb-2">Arrastra y suelta una nueva imagen aquí o</p>
                  <button type="button" class="btn btn-outline-primary" onclick="document.getElementById('editLogoInput').click()">
                    <iconify-icon icon="mdi:folder-open"></iconify-icon>
                    Seleccionar Nuevo Archivo
                  </button>
                  <small class="d-block text-muted mt-2">Formatos: JPG, PNG, GIF (Max: 5MB)</small>
                </div>
              </div>
            </div>

            <div class="col-12 mb-4">
              <h6 class="text-muted text-uppercase fw-bold mb-3">
                <iconify-icon icon="mdi:information-outline" class="me-1"></iconify-icon>
                Información Institucional
              </h6>
            </div>

            <!-- Nombre de la Institución -->
            <div class="col-md-6 mb-3">
              <label for="editNombreInstitucion" class="form-label fw-semibold">
                <iconify-icon icon="mdi:school" class="me-1"></iconify-icon>
                Nombre de la Institución *
              </label>
              <input type="text" class="form-control" id="editNombreInstitucion" name="nombre_institucion" required>
              <div class="invalid-feedback">
                El nombre de la institución es requerido
              </div>
            </div>

            <!-- Código Modular -->
            <div class="col-md-6 mb-3">
              <label for="editCodigoModular" class="form-label fw-semibold">
                <iconify-icon icon="mdi:barcode" class="me-1"></iconify-icon>
                Código Modular *
              </label>
              <input type="text" class="form-control" id="editCodigoModular" name="codigo_modular" required 
                     pattern="[0-9]{6,20}" maxlength="20" placeholder="1234567">
              <div class="form-text">Ingrese entre 6 y 20 dígitos</div>
              <div class="invalid-feedback">
                El código modular debe tener entre 6 y 20 dígitos numéricos
              </div>
            </div>

            <!-- UGEL -->
            <div class="col-md-6 mb-3">
              <label for="editUgel" class="form-label fw-semibold">
                <iconify-icon icon="mdi:office-building-outline" class="me-1"></iconify-icon>
                UGEL *
              </label>
              <input type="text" class="form-control" id="editUgel" name="ugel" required placeholder="UGEL 06 - Ate">
              <div class="invalid-feedback">
                La UGEL es requerida
              </div>
            </div>

            <!-- Nivel Educativo -->
            <div class="col-md-6 mb-3">
              <label for="editNivelEducativo" class="form-label fw-semibold">
                <iconify-icon icon="mdi:account-group" class="me-1"></iconify-icon>
                Nivel Educativo *
              </label>
              <select class="form-select" id="editNivelEducativo" name="nivel_educativo" required>
                <option value="">Seleccionar nivel...</option>
                <option value="inicial">Inicial</option>
                <option value="primaria">Primaria</option>
                <option value="secundaria">Secundaria</option>
                <option value="superior">Superior</option>
              </select>
              <div class="invalid-feedback">
                El nivel educativo es requerido
              </div>
            </div>

            <div class="col-12 mb-4 mt-4">
              <h6 class="text-muted text-uppercase fw-bold mb-3">
                <iconify-icon icon="mdi:contact-outline" class="me-1"></iconify-icon>
                Información de Contacto
              </h6>
            </div>

            <!-- Dirección -->
            <div class="col-12 mb-3">
              <label for="editDireccion" class="form-label fw-semibold">
                <iconify-icon icon="mdi:map-marker" class="me-1"></iconify-icon>
                Dirección *
              </label>
              <textarea class="form-control" id="editDireccion" name="direccion" rows="2" required 
                        placeholder="Av. Los Héroes 1250, Lima, Perú"></textarea>
              <div class="invalid-feedback">
                La dirección es requerida
              </div>
            </div>

            <!-- Teléfono -->
            <div class="col-md-6 mb-3">
              <label for="editTelefono" class="form-label fw-semibold">
                <iconify-icon icon="mdi:phone" class="me-1"></iconify-icon>
                Teléfono *
              </label>
              <input type="tel" class="form-control" id="editTelefono" name="telefono" required 
                     placeholder="01-234-5678" pattern="[0-9\-\+\(\)\s]+">
              <div class="form-text">Ejemplo: 01-234-5678 o +51 987 654 321</div>
              <div class="invalid-feedback">
                El teléfono es requerido
              </div>
            </div>

            <!-- Email -->
            <div class="col-md-6 mb-3">
              <label for="editEmail" class="form-label fw-semibold">
                <iconify-icon icon="mdi:email" class="me-1"></iconify-icon>
                Correo Electrónico *
              </label>
              <input type="email" class="form-control" id="editEmail" name="email" required 
                     placeholder="info@colegio.edu.pe">
              <div class="invalid-feedback">
                El correo electrónico es requerido y debe ser válido
              </div>
            </div>

            <!-- Sección de Configuraciones Avanzadas -->
            <div class="col-12 mb-4 mt-4">
              <h6 class="text-muted text-uppercase fw-bold mb-3">
                <iconify-icon icon="mdi:cog-outline" class="me-1"></iconify-icon>
                Configuraciones Avanzadas
              </h6>
            </div>

            <!-- Colores Institucionales -->
            <div class="col-md-4 mb-3">
              <label for="editColorPrimario" class="form-label fw-semibold">
                <iconify-icon icon="mdi:palette" class="me-1"></iconify-icon>
                Color Primario
              </label>
              <div class="d-flex gap-2">
                <input type="color" class="form-control form-control-color" id="editColorPrimario" 
                       name="color_primario" value="#2563eb" title="Seleccionar color primario">
                <input type="text" class="form-control" id="editColorPrimarioText" 
                       placeholder="#2563eb" maxlength="7" pattern="^#[0-9A-Fa-f]{6}$">
              </div>
              <div class="form-text">Color principal de la institución</div>
            </div>

            <!-- Color Secundario -->
            <div class="col-md-4 mb-3">
              <label for="editColorSecundario" class="form-label fw-semibold">
                <iconify-icon icon="mdi:palette-outline" class="me-1"></iconify-icon>
                Color Secundario
              </label>
              <div class="d-flex gap-2">
                <input type="color" class="form-control form-control-color" id="editColorSecundario" 
                       name="color_secundario" value="#64748b" title="Seleccionar color secundario">
                <input type="text" class="form-control" id="editColorSecundarioText" 
                       placeholder="#64748b" maxlength="7" pattern="^#[0-9A-Fa-f]{6}$">
              </div>
              <div class="form-text">Color complementario</div>
            </div>

            <!-- Color de Acento -->
            <div class="col-md-4 mb-3">
              <label for="editColorAccento" class="form-label fw-semibold">
                <iconify-icon icon="mdi:invert-colors" class="me-1"></iconify-icon>
                Color de Acento
              </label>
              <div class="d-flex gap-2">
                <input type="color" class="form-control form-control-color" id="editColorAccento" 
                       name="color_acento" value="#059669" title="Seleccionar color de acento">
                <input type="text" class="form-control" id="editColorAccentoText" 
                       placeholder="#059669" maxlength="7" pattern="^#[0-9A-Fa-f]{6}$">
              </div>
              <div class="form-text">Color para destacar elementos</div>
            </div>

            <!-- Configuración de Backup -->
            <div class="col-md-6 mb-3">
              <label class="form-label fw-semibold">
                <iconify-icon icon="mdi:backup-restore" class="me-1"></iconify-icon>
                Sistema de Respaldo
              </label>
              <div class="form-check form-switch">
                <input class="form-check-input" type="checkbox" id="editBackupHabilitado" name="backup_habilitado">
                <label class="form-check-label" for="editBackupHabilitado">
                  Habilitar respaldo automático
                </label>
              </div>
              <div class="form-text">Activar copias de seguridad programadas</div>
            </div>

            <!-- Frecuencia de Backup -->
            <div class="col-md-6 mb-3">
              <label for="editBackupFrecuencia" class="form-label fw-semibold">
                <iconify-icon icon="mdi:clock-outline" class="me-1"></iconify-icon>
                Frecuencia de Respaldo
              </label>
              <select class="form-select" id="editBackupFrecuencia" name="backup_frecuencia">
                <option value="diario">Diario</option>
                <option value="semanal">Semanal</option>
                <option value="mensual">Mensual</option>
                <option value="personalizado">Personalizado</option>
              </select>
              <div class="form-text">Periodicidad de las copias de seguridad</div>
            </div>
          </div>
        </div>
        
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
            <iconify-icon icon="mdi:close"></iconify-icon>
            Cancelar
          </button>
          <button type="submit" class="btn btn-primary" id="editConfiguracionBtn">
            <span id="editBtnContent">
              <iconify-icon icon="mdi:content-save"></iconify-icon>
              Actualizar Configuración
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
  // Configurar upload de logo para edición
  const editLogoInput = document.getElementById('editLogoInput');
  const editLogoUploadArea = document.getElementById('editLogoUploadArea');
  const editLogoPreview = document.getElementById('editLogoPreview');
  const editLogoPreviewImg = document.getElementById('editLogoPreviewImg');
  const editLogoUploadContent = document.getElementById('editLogoUploadContent');
  const editRemoveLogo = document.getElementById('editRemoveLogo');

  // Drag and drop functionality para edición
  editLogoUploadArea.addEventListener('dragover', (e) => {
    e.preventDefault();
    editLogoUploadArea.classList.add('border-primary', 'bg-light');
  });

  editLogoUploadArea.addEventListener('dragleave', () => {
    editLogoUploadArea.classList.remove('border-primary', 'bg-light');
  });

  editLogoUploadArea.addEventListener('drop', (e) => {
    e.preventDefault();
    editLogoUploadArea.classList.remove('border-primary', 'bg-light');
    
    const files = e.dataTransfer.files;
    if (files.length > 0) {
      handleEditFileSelection(files[0]);
    }
  });

  // File input change para edición
  editLogoInput.addEventListener('change', (e) => {
    if (e.target.files.length > 0) {
      handleEditFileSelection(e.target.files[0]);
    }
  });

  // Remove logo para edición
  editRemoveLogo.addEventListener('click', () => {
    editLogoInput.value = '';
    editLogoPreview.classList.add('d-none');
    editLogoUploadContent.classList.remove('d-none');
  });

  function handleEditFileSelection(file) {
    // Validar tipo de archivo
    if (!file.type.startsWith('image/')) {
      showNotification('warning', 'Archivo no válido', 'Por favor seleccione una imagen');
      return;
    }

    // Validar tamaño (5MB)
    if (file.size > 5 * 1024 * 1024) {
      showNotification('warning', 'Archivo muy grande', 'El archivo debe ser menor a 5MB');
      return;
    }

    // Mostrar preview
    const reader = new FileReader();
    reader.onload = (e) => {
      editLogoPreviewImg.src = e.target.result;
      editLogoUploadContent.classList.add('d-none');
      editLogoPreview.classList.remove('d-none');
    };
    reader.readAsDataURL(file);

    // Asignar archivo al input
    const dataTransfer = new DataTransfer();
    dataTransfer.items.add(file);
    editLogoInput.files = dataTransfer.files;
  }

  // Validación de código modular en tiempo real
  $('#editCodigoModular').on('input', function() {
    const codigo = this.value.replace(/\D/g, ''); // Solo números
    this.value = codigo;
    
    if (codigo.length >= 6 && codigo.length <= 20) {
      this.classList.remove('is-invalid');
      this.classList.add('is-valid');
    } else if (codigo.length > 0) {
      this.classList.remove('is-valid');
      this.classList.add('is-invalid');
    } else {
      this.classList.remove('is-valid', 'is-invalid');
    }
  });

  // Sincronizar selectores de color con inputs de texto
  ['Primario', 'Secundario', 'Accento'].forEach(color => {
    const colorPicker = document.getElementById(`editColor${color}`);
    const colorText = document.getElementById(`editColor${color}Text`);
    
    colorPicker.addEventListener('change', (e) => {
      colorText.value = e.target.value.toUpperCase();
    });
    
    colorText.addEventListener('input', (e) => {
      const hexValue = e.target.value;
      if (/^#[0-9A-Fa-f]{6}$/.test(hexValue)) {
        colorPicker.value = hexValue;
        e.target.classList.remove('is-invalid');
        e.target.classList.add('is-valid');
      } else if (hexValue.length > 0) {
        e.target.classList.remove('is-valid');
        e.target.classList.add('is-invalid');
      } else {
        e.target.classList.remove('is-valid', 'is-invalid');
      }
    });
  });

  // Validación de email en tiempo real
  $('#editEmail').on('input', function() {
    const email = this.value;
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    
    if (emailRegex.test(email)) {
      this.classList.remove('is-invalid');
      this.classList.add('is-valid');
    } else if (email.length > 0) {
      this.classList.remove('is-valid');
      this.classList.add('is-invalid');
    } else {
      this.classList.remove('is-valid', 'is-invalid');
    }
  });

  // Habilitar/deshabilitar frecuencia de backup
  $('#editBackupHabilitado').on('change', function() {
    const frecuenciaSelect = document.getElementById('editBackupFrecuencia');
    if (this.checked) {
      frecuenciaSelect.disabled = false;
      frecuenciaSelect.parentElement.style.opacity = '1';
    } else {
      frecuenciaSelect.disabled = true;
      frecuenciaSelect.parentElement.style.opacity = '0.5';
    }
  });

  // Limpiar formulario al cerrar modal de edición
  $('#editConfiguracionModal').on('hidden.bs.modal', function() {
    $('#editConfiguracionForm')[0].reset();
    $('#editConfiguracionForm').removeClass('was-validated');
    editLogoInput.value = '';
    editLogoPreview.classList.add('d-none');
    editLogoUploadContent.classList.remove('d-none');
    $('#editCodigoModular, #editEmail').removeClass('is-valid', 'is-invalid');
    $('.form-control[id*="ColorText"]').removeClass('is-valid', 'is-invalid');
    $('#currentLogo').html('');
    
    // Resetear estado de backup
    document.getElementById('editBackupFrecuencia').disabled = true;
    document.getElementById('editBackupFrecuencia').parentElement.style.opacity = '0.5';
  });

  // Inicializar estado de backup al abrir modal
  $('#editConfiguracionModal').on('shown.bs.modal', function() {
    const backupHabilitado = document.getElementById('editBackupHabilitado').checked;
    const frecuenciaSelect = document.getElementById('editBackupFrecuencia');
    
    if (backupHabilitado) {
      frecuenciaSelect.disabled = false;
      frecuenciaSelect.parentElement.style.opacity = '1';
    } else {
      frecuenciaSelect.disabled = true;
      frecuenciaSelect.parentElement.style.opacity = '0.5';
    }
  });
});
</script>