/**
 * Copisteria Low Cost - JavaScript Principal
 * Gestion de l'interface utilisateur et des interactions
 */

// Variables globales
let currentConfig = {
    copies: 1,
    color: 'bw',
    size: 'a4',
    weight: '80g',
    sides: 'double',
    orientation: 'vertical',
    flip: 'long',
    pages_per_sheet: '1',
    finishing: 'bound',
    spiralColor: 'black',
    coverFrontColor: 'transparent',
    coverBackColor: 'black',
    comments: ''
};

let uploadedFiles = [];
let currentColorTarget = null;
let isProcessingOrder = false;

// Configuration des couleurs disponibles
const availableColors = {
    'black': { name: 'Negra', class: 'color-black' },
    'transparent': { name: 'Transparente', class: 'color-transparent' },
    'yellow': { name: 'Amarilla', class: 'color-yellow' },
    'turquoise': { name: 'Turquesa', class: 'color-turquoise' },
    'pink': { name: 'Rosa', class: 'color-pink' },
    'green': { name: 'Verde menta', class: 'color-green' },
    'gold': { name: 'Dorado', class: 'color-gold' },
    'blue': { name: 'Azul pastel', class: 'color-blue' },
    'purple': { name: 'Lila', class: 'color-purple' }
};

// Initialisation au chargement de la page
document.addEventListener('DOMContentLoaded', function() {
    console.log('🚀 Copisteria - Inicializando aplicación...');
    
    initializeApp();
    setupEventListeners();
    updatePricing();
    
    console.log('✅ Aplicación inicializada correctamente');
});

/**
 * Initialisation de l'application
 */
function initializeApp() {
    // Initialiser les tooltips Bootstrap
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });

    // Initialiser les popovers Bootstrap
    const popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
    popoverTriggerList.map(function (popoverTriggerEl) {
        return new bootstrap.Popover(popoverTriggerEl);
    });

    // Configurer les sous-options de reliure
    const bindingButton = document.querySelector('[data-value="bound"]');
    if (bindingButton && bindingButton.classList.contains('green-selected')) {
        const subOptions = document.getElementById('bindingSubOptions');
        if (subOptions) {
            subOptions.style.display = 'block';
        }
    }

    // Charger la configuration sauvegardée si disponible
    loadUserPreferences();
    
    // Initialiser le drag & drop
    setupDragAndDrop();
}

/**
 * Configuration des event listeners
 */
function setupEventListeners() {
    // Gestion des options de configuration
    document.addEventListener('click', handleConfigurationClicks);
    
    // Gestion du compteur de copies
    setupCopiesCounter();
    
    // Gestion des commentaires
    const commentsTextarea = document.getElementById('commentsTextarea');
    if (commentsTextarea) {
        commentsTextarea.addEventListener('input', handleCommentsChange);
    }
    
    // Gestion de l'upload de fichiers
    const fileInput = document.getElementById('fileInput');
    if (fileInput) {
        fileInput.addEventListener('change', handleFileSelection);
    }
    
    // Gestion des modales
    setupModalListeners();
    
    // Gestion du redimensionnement de la fenêtre
    window.addEventListener('resize', debounce(handleWindowResize, 300));
    
    // Gestion de la fermeture de l'onglet
    window.addEventListener('beforeunload', handleBeforeUnload);
}

/**
 * Gestion des clics sur les options de configuration
 */
function handleConfigurationClicks(event) {
    const target = event.target.closest('.option-btn, .finishing-option, .layout-option');
    
    if (!target) return;
    
    const option = target.dataset.option;
    const value = target.dataset.value;
    
    if (!option || !value) return;
    
    // Empêcher les clics multiples rapides
    if (target.classList.contains('processing')) return;
    target.classList.add('processing');
    setTimeout(() => target.classList.remove('processing'), 300);
    
    // Gérer la sélection
    handleOptionSelection(target, option, value);
}

/**
 * Gestion de la sélection d'options
 */
function handleOptionSelection(target, option, value) {
    // Retirer la sélection des éléments frères
    const siblings = target.parentElement.querySelectorAll('.option-btn, .finishing-option, .layout-option');
    siblings.forEach(btn => btn.classList.remove('selected', 'green-selected'));
    
    // Sélectionner l'élément actuel
    if (option === 'finishing' && value === 'bound') {
        target.classList.add('green-selected');
        showBindingSubOptions(true);
    } else {
        target.classList.add('selected');
        if (option === 'finishing') {
            showBindingSubOptions(false);
        }
    }
    
    // Mettre à jour la configuration
    currentConfig[option] = value;
    
    // Animations visuelles
    animateOptionSelection(target);
    
    // Mettre à jour le prix
    updatePricing();
    
    // Sauvegarder les préférences
    saveUserPreferences();
    
    console.log(`📝 Configuración actualizada: ${option} = ${value}`);
}

/**
 * Afficher/masquer les sous-options de reliure
 */
function showBindingSubOptions(show) {
    const subOptions = document.getElementById('bindingSubOptions');
    if (subOptions) {
        if (show) {
            subOptions.style.display = 'block';
            subOptions.classList.add('fade-in');
        } else {
            subOptions.style.display = 'none';
            subOptions.classList.remove('fade-in');
        }
    }
}

/**
 * Animation de sélection d'option
 */
function animateOptionSelection(element) {
    element.style.transform = 'scale(0.95)';
    element.style.transition = 'transform 0.1s ease';
    
    setTimeout(() => {
        element.style.transform = 'scale(1)';
        setTimeout(() => {
            element.style.transform = '';
            element.style.transition = '';
        }, 200);
    }, 100);
}

/**
 * Configuration du compteur de copies
 */
function setupCopiesCounter() {
    const decreaseBtn = document.querySelector('.counter-btn[onclick*="-1"]');
    const increaseBtn = document.querySelector('.counter-btn[onclick*="1"]');
    
    if (decreaseBtn) {
        decreaseBtn.addEventListener('click', () => changeCount(-1));
        decreaseBtn.removeAttribute('onclick');
    }
    
    if (increaseBtn) {
        increaseBtn.addEventListener('click', () => changeCount(1));
        increaseBtn.removeAttribute('onclick');
    }
}

/**
 * Modification du nombre de copies
 */
function changeCount(delta) {
    const countElement = document.getElementById('copiesCount');
    const multiplierElement = document.getElementById('copiesMultiplier');
    
    if (!countElement) return;
    
    let count = parseInt(countElement.textContent) || 1;
    count = Math.max(1, Math.min(999, count + delta));
    
    countElement.textContent = count;
    if (multiplierElement) {
        multiplierElement.textContent = count;
    }
    
    currentConfig.copies = count;
    
    // Animation du compteur
    animateCounterChange(countElement, delta > 0);
    
    updatePricing();
    saveUserPreferences();
    
    console.log(`🔢 Copias actualizadas: ${count}`);
}

/**
 * Animation du changement de compteur
 */
function animateCounterChange(element, isIncrease) {
    element.style.color = isIncrease ? '#28a745' : '#dc3545';
    element.style.transform = 'scale(1.2)';
    
    setTimeout(() => {
        element.style.color = '';
        element.style.transform = 'scale(1)';
    }, 300);
}

/**
 * Gestion des changements de commentaires
 */
function handleCommentsChange(event) {
    const textarea = event.target;
    const charCount = document.getElementById('charCount');
    
    if (charCount) {
        const length = textarea.value.length;
        charCount.textContent = length;
        
        // Changer la couleur selon la longueur
        const counter = charCount.parentElement;
        counter.classList.remove('text-muted', 'text-warning', 'text-danger');
        
        if (length > 350) {
            counter.classList.add('text-warning');
        } else if (length >= 400) {
            counter.classList.add('text-danger');
        } else {
            counter.classList.add('text-muted');
        }
    }
    
    currentConfig.comments = textarea.value;
    saveUserPreferences();
}

/**
 * Gestion de la sélection de fichiers
 */
function handleFileSelection(event) {
    const files = Array.from(event.target.files);
    
    if (files.length === 0) return;
    
    console.log(`📁 ${files.length} archivo(s) seleccionado(s)`);
    
    // Valider les fichiers
    const validFiles = files.filter(validateFile);
    
    if (validFiles.length === 0) {
        showAlert('Ningún archivo válido seleccionado', 'warning');
        return;
    }
    
    // Traiter les fichiers valides
    processSelectedFiles(validFiles);
}

/**
 * Validation des fichiers
 */
function validateFile(file) {
    const maxSize = 50 * 1024 * 1024; // 50MB
    const allowedTypes = ['pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png'];
    const fileExtension = file.name.split('.').pop().toLowerCase();
    
    // Vérifier la taille
    if (file.size > maxSize) {
        showAlert(`Archivo "${file.name}" demasiado grande. Máximo 50MB.`, 'warning');
        return false;
    }
    
    // Vérifier le type
    if (!allowedTypes.includes(fileExtension)) {
        showAlert(`Tipo de archivo no permitido: ${fileExtension}`, 'warning');
        return false;
    }
    
    // Vérifier le nombre maximum de fichiers
    if (uploadedFiles.length >= 20) {
        showAlert('Máximo 20 archivos por pedido', 'warning');
        return false;
    }
    
    return true;
}

/**
 * Traitement des fichiers sélectionnés
 */
function processSelectedFiles(files) {
    showLoadingModal('Procesando archivos...');
    
    let processedCount = 0;
    const totalFiles = files.length;
    
    files.forEach((file, index) => {
        setTimeout(() => {
            processFile(file)
                .then(fileData => {
                    uploadedFiles.push(fileData);
                    addFileToDisplay(fileData);
                    
                    processedCount++;
                    updateProcessingProgress(processedCount, totalFiles);
                    
                    if (processedCount === totalFiles) {
                        hideLoadingModal();
                        showUploadedFilesArea();
                        updatePricing();
                        showAlert(`${totalFiles} archivo(s) subido(s) correctamente`, 'success');
                    }
                })
                .catch(error => {
                    console.error('Error procesando archivo:', error);
                    showAlert(`Error procesando "${file.name}"`, 'danger');
                    
                    processedCount++;
                    if (processedCount === totalFiles) {
                        hideLoadingModal();
                    }
                });
        }, index * 100); // Retraso escalonado para mejor UX
    });
}

/**
 * Traitement d'un fichier individuel
 */
function processFile(file) {
    return new Promise((resolve, reject) => {
        const fileData = {
            id: generateFileId(),
            name: file.name,
            size: file.size,
            type: file.type,
            lastModified: file.lastModified,
            file: file,
            pages: estimatePages(file),
            status: 'ready',
            preview: null
        };
        
        // Générer une prévisualisation si possible
        if (file.type.startsWith('image/')) {
            generateImagePreview(file)
                .then(preview => {
                    fileData.preview = preview;
                    resolve(fileData);
                })
                .catch(() => resolve(fileData)); // Continuer sans prévisualisation
        } else {
            resolve(fileData);
        }
    });
}

/**
 * Génération d'un ID unique pour le fichier
 */
function generateFileId() {
    return 'file_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);
}

/**
 * Estimation du nombre de pages
 */
function estimatePages(file) {
    const extension = file.name.split('.').pop().toLowerCase();
    
    // Estimation basique (à améliorer avec une vraie analyse)
    switch (extension) {
        case 'pdf': return Math.ceil(file.size / (100 * 1024)); // ~100KB par page
        case 'doc':
        case 'docx': return Math.ceil(file.size / (50 * 1024)); // ~50KB par page
        case 'jpg':
        case 'jpeg':
        case 'png': return 1; // Une image = une page
        default: return 1;
    }
}

/**
 * Génération de prévisualisation d'image
 */
function generateImagePreview(file) {
    return new Promise((resolve, reject) => {
        const reader = new FileReader();
        
        reader.onload = function(e) {
            resolve(e.target.result);
        };
        
        reader.onerror = function() {
            reject(new Error('Erreur lecture fichier'));
        };
        
        reader.readAsDataURL(file);
    });
}

/**
 * Ajout d'un fichier à l'affichage
 */
function addFileToDisplay(fileData) {
    const container = document.getElementById('filesContainer');
    if (!container) return;
    
    const fileElement = createFileElement(fileData);
    container.appendChild(fileElement);
    
    // Animation d'apparition
    setTimeout(() => {
        fileElement.classList.add('fade-in');
    }, 50);
    
    // Ajouter un onglet dans l'en-tête
    addFileTab(fileData.name, fileData.id);
}

/**
 * Création de l'élément de fichier
 */
function createFileElement(fileData) {
    const fileElement = document.createElement('div');
    fileElement.className = 'file-item';
    fileElement.dataset.fileId = fileData.id;
    
    const previewIcon = getFileIcon(fileData.type);
    const formattedSize = formatFileSize(fileData.size);
    
    fileElement.innerHTML = `
        <div class="file-preview ${fileData.preview ? 'has-preview' : ''}">
            ${fileData.preview ? 
                `<img src="${fileData.preview}" alt="Vista previa" style="width: 100%; height: 100%; object-fit: cover; border-radius: 4px;">` :
                `<i class="${previewIcon}"></i>`
            }
        </div>
        <div class="file-info">
            <div class="file-name" title="${fileData.name}">${truncateFileName(fileData.name)}</div>
            <div class="file-details">
                <span><i class="fas fa-weight"></i> ${formattedSize}</span>
                <span><i class="fas fa-file-alt"></i> ${fileData.pages} página(s)</span>
                <span class="file-status status-${fileData.status}">
                    <i class="fas fa-check-circle"></i> Listo
                </span>
            </div>
        </div>
        <div class="file-actions">
            <button class="btn btn-sm btn-outline-primary" onclick="previewFile('${fileData.id}')" title="Vista previa">
                <i class="fas fa-eye"></i>
            </button>
            <button class="btn btn-sm btn-outline-secondary" onclick="configureFile('${fileData.id}')" title="Configurar">
                <i class="fas fa-cog"></i>
            </button>
            <button class="btn btn-sm btn-outline-danger" onclick="removeFile('${fileData.id}')" title="Eliminar">
                <i class="fas fa-trash"></i>
            </button>
        </div>
    `;
    
    return fileElement;
}

/**
 * Obtenir l'icône appropriée pour le type de fichier
 */
function getFileIcon(mimeType) {
    if (mimeType.includes('pdf')) return 'fas fa-file-pdf text-danger';
    if (mimeType.includes('word')) return 'fas fa-file-word text-primary';
    if (mimeType.includes('image')) return 'fas fa-file-image text-success';
    return 'fas fa-file text-secondary';
}

/**
 * Formatage de la taille de fichier
 */
function formatFileSize(bytes) {
    if (bytes === 0) return '0 B';
    
    const k = 1024;
    const sizes = ['B', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    
    return parseFloat((bytes / Math.pow(k, i)).toFixed(1)) + ' ' + sizes[i];
}

/**
 * Tronquer le nom de fichier
 */
function truncateFileName(fileName, maxLength = 30) {
    if (fileName.length <= maxLength) return fileName;
    
    const extension = fileName.split('.').pop();
    const name = fileName.substring(0, fileName.lastIndexOf('.'));
    const truncatedName = name.substring(0, maxLength - extension.length - 4);
    
    return truncatedName + '...' + extension;
}

/**
 * Affichage de la zone des fichiers uploadés
 */
function showUploadedFilesArea() {
    const uploadArea = document.getElementById('uploadArea');
    const filesList = document.getElementById('filesList');
    
    if (uploadArea) uploadArea.style.display = 'none';
    if (filesList) filesList.style.display = 'block';
    
    // Mettre à jour le breadcrumb
    if (typeof updateBreadcrumb === 'function') {
        updateBreadcrumb('upload');
    }
}

/**
 * Ajouter plus de fichiers
 */
function addMoreFiles() {
    const fileInput = document.getElementById('fileInput');
    if (fileInput) {
        fileInput.click();
    }
}

/**
 * Prévisualisation d'un fichier
 */
function previewFile(fileId) {
    const fileData = uploadedFiles.find(f => f.id === fileId);
    if (!fileData) return;
    
    console.log('👁️ Vista previa del archivo:', fileData.name);
    
    // TODO: Implémenter la prévisualisation
    showAlert('Vista previa - En desarrollo', 'info');
}

/**
 * Configuration d'un fichier
 */
function configureFile(fileId) {
    const fileData = uploadedFiles.find(f => f.id === fileId);
    if (!fileData) return;
    
    console.log('⚙️ Configurando archivo:', fileData.name);
    
    // TODO: Ouvrir modal de configuration spécifique au fichier
    showAlert('Configuración específica - En desarrollo', 'info');
}

/**
 * Suppression d'un fichier
 */
function removeFile(fileId) {
    if (!confirm('¿Estás seguro de que quieres eliminar este archivo?')) return;
    
    // Retirer de la liste
    uploadedFiles = uploadedFiles.filter(f => f.id !== fileId);
    
    // Retirer de l'affichage
    const fileElement = document.querySelector(`[data-file-id="${fileId}"]`);
    if (fileElement) {
        fileElement.style.opacity = '0';
        fileElement.style.transform = 'translateX(-100px)';
        setTimeout(() => fileElement.remove(), 300);
    }
    
    // Retirer l'onglet
    removeFileTab(fileId);
    
    // Vérifier s'il faut revenir à la zone d'upload
    if (uploadedFiles.length === 0) {
        const uploadArea = document.getElementById('uploadArea');
        const filesList = document.getElementById('filesList');
        
        if (uploadArea) uploadArea.style.display = 'flex';
        if (filesList) filesList.style.display = 'none';
        
        if (typeof updateBreadcrumb === 'function') {
            updateBreadcrumb('config');
        }
    }
    
    updatePricing();
    showAlert('Archivo eliminado', 'info');
}

/**
 * Configuration du drag & drop
 */
function setupDragAndDrop() {
    const uploadArea = document.getElementById('uploadArea');
    if (!uploadArea) return;
    
    ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
        uploadArea.addEventListener(eventName, preventDefaults, false);
        document.body.addEventListener(eventName, preventDefaults, false);
    });
    
    ['dragenter', 'dragover'].forEach(eventName => {
        uploadArea.addEventListener(eventName, highlight, false);
    });
    
    ['dragleave', 'drop'].forEach(eventName => {
        uploadArea.addEventListener(eventName, unhighlight, false);
    });
    
    uploadArea.addEventListener('drop', handleDrop, false);
}

function preventDefaults(e) {
    e.preventDefault();
    e.stopPropagation();
}

function highlight(e) {
    const uploadArea = document.getElementById('uploadArea');
    if (uploadArea) {
        uploadArea.classList.add('drag-over');
        uploadArea.style.borderColor = 'var(--primary-blue)';
        uploadArea.style.backgroundColor = 'rgba(0, 123, 255, 0.05)';
    }
}

function unhighlight(e) {
    const uploadArea = document.getElementById('uploadArea');
    if (uploadArea) {
        uploadArea.classList.remove('drag-over');
        uploadArea.style.borderColor = '';
        uploadArea.style.backgroundColor = '';
    }
}

function handleDrop(e) {
    const dt = e.dataTransfer;
    const files = Array.from(dt.files);
    
    if (files.length > 0) {
        console.log(`🎯 ${files.length} archivo(s) arrastrado(s)`);
        
        const validFiles = files.filter(validateFile);
        if (validFiles.length > 0) {
            processSelectedFiles(validFiles);
        }
    }
}

/**
 * Traitement de la commande
 */
function processOrder() {
    if (isProcessingOrder) return;
    if (uploadedFiles.length === 0) {
        showAlert('Por favor, sube al menos un archivo', 'warning');
        return;
    }
    
    if (!confirm('¿Confirmar el pedido de impresión?')) return;
    
    isProcessingOrder = true;
    showLoadingModal('Procesando pedido...');
    
    const orderData = {
        config: currentConfig,
        files: uploadedFiles.map(f => ({
            id: f.id,
            name: f.name,
            size: f.size,
            pages: f.pages,
            type: f.type
        })),
        timestamp: new Date().toISOString()
    };
    
    // Envoyer au serveur
    fetch('ajax/process-order.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify(orderData)
    })
    .then(response => response.json())
    .then(data => {
        hideLoadingModal();
        isProcessingOrder = false;
        
        if (data.success) {
            showAlert('¡Pedido procesado correctamente!', 'success');
            
            // Rediriger vers la page de confirmation
            setTimeout(() => {
                window.location.href = `user/order-detail.php?id=${data.orderId}`;
            }, 2000);
        } else {
            showAlert(data.message || 'Error procesando el pedido', 'danger');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        hideLoadingModal();
        isProcessingOrder = false;
        showAlert('Error de conexión', 'danger');
    });
}

/**
 * Sauvegarde de la configuration
 */
function saveConfiguration() {
    const name = prompt('Nombre para esta configuración:');
    if (!name || name.trim() === '') return;
    
    const configData = {
        name: name.trim(),
        config: currentConfig
    };
    
    fetch('ajax/save-config.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify(configData)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showAlert('Configuración guardada correctamente', 'success');
        } else {
            showAlert(data.message || 'Error guardando la configuración', 'danger');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('Error de conexión', 'danger');
    });
}

/**
 * Gestion des modales
 */
function setupModalListeners() {
    const colorModal = document.getElementById('colorModal');
    if (colorModal) {
        colorModal.addEventListener('show.bs.modal', handleColorModalShow);
        colorModal.addEventListener('hidden.bs.modal', handleColorModalHide);
    }
}

function handleColorModalShow(event) {
    const target = currentColorTarget;
    if (!target) return;
    
    populateColorGrid(target);
}

function handleColorModalHide(event) {
    currentColorTarget = null;
}

function populateColorGrid(target) {
    const colorGrid = document.getElementById('colorGrid');
    if (!colorGrid) return;
    
    const currentColor = getCurrentColor(target);
    
    colorGrid.innerHTML = Object.entries(availableColors)
        .map(([colorId, colorData]) => `
            <div class="color-choice ${colorId === currentColor ? 'selected' : ''}" 
                 data-color="${colorId}" 
                 onclick="selectColor('${colorId}')">
                <div class="color-choice-preview ${colorData.class}"></div>
                <div class="color-choice-name">${colorData.name}</div>
            </div>
        `).join('');
}

function getCurrentColor(target) {
    switch(target) {
        case 'spiral': return currentConfig.spiralColor;
        case 'cover-front': return currentConfig.coverFrontColor;
        case 'cover-back': return currentConfig.coverBackColor;
        default: return 'black';
    }
}

function openColorModal(target) {
    currentColorTarget = target;
    
    const modal = document.getElementById('colorModal');
    const title = document.getElementById('colorModalTitle');
    const subtitle = document.getElementById('colorModalSubtitle');
    
    // Mettre à jour le contenu de la modale
    const titles = {
        'spiral': { title: 'Color de la espiral', subtitle: 'Selecciona el color para la espiral' },
        'cover-front': { title: 'Color de la tapa delantera', subtitle: 'Selecciona el color para la tapa delantera' },
        'cover-back': { title: 'Color de la tapa trasera', subtitle: 'Selecciona el color para la tapa trasera' }
    };
    
    if (titles[target]) {
        title.textContent = titles[target].title;
        subtitle.textContent = titles[target].subtitle;
    }
    
    const bsModal = new bootstrap.Modal(modal);
    bsModal.show();
}

function selectColor(colorId) {
    if (!currentColorTarget) return;
    
    // Mettre à jour la configuration
    switch(currentColorTarget) {
        case 'spiral':
            currentConfig.spiralColor = colorId;
            updateColorDisplay('spiral', colorId);
            break;
        case 'cover-front':
            currentConfig.coverFrontColor = colorId;
            updateColorDisplay('cover-front', colorId);
            break;
        case 'cover-back':
            currentConfig.coverBackColor = colorId;
            updateColorDisplay('cover-back', colorId);
            break;
    }
    
    // Fermer la modale
    const modal = bootstrap.Modal.getInstance(document.getElementById('colorModal'));
    if (modal) modal.hide();
    
    updatePricing();
    saveUserPreferences();
}

function updateColorDisplay(target, colorId) {
    const colorData = availableColors[colorId];
    if (!colorData) return;
    
    const previewId = target === 'spiral' ? 'spiralColorPreview' : 
                     target === 'cover-front' ? 'coverFrontColorPreview' : 
                     'coverBackColorPreview';
    const badgeId = target === 'spiral' ? 'spiralColorBadge' : 
                   target === 'cover-front' ? 'coverFrontColorBadge' : 
                   'coverBackColorBadge';
    
    const preview = document.getElementById(previewId);
    const badge = document.getElementById(badgeId);
    
    if (preview) {
        preview.className = `color-preview ${colorData.class}`;
    }
    
    if (badge) {
        if (colorId === 'transparent') {
            badge.classList.add('d-none');
        } else {
            badge.classList.remove('d-none');
            badge.textContent = colorData.name;
            badge.className = `badge ${colorId === 'black' ? 'bg-dark' : 'bg-primary'}`;
        }
    }
}

/**
 * Utilitaires
 */
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

function handleWindowResize() {
    // Ajuster l'interface pour les écrans plus petits
    const sidebar = document.querySelector('.config-sidebar');
    if (window.innerWidth < 768 && sidebar) {
        sidebar.classList.add('mobile-sidebar');
    } else if (sidebar) {
        sidebar.classList.remove('mobile-sidebar');
    }
}

function handleBeforeUnload(event) {
    if (uploadedFiles.length > 0 && !isProcessingOrder) {
        event.preventDefault();
        event.returnValue = '¿Estás seguro de que quieres salir? Se perderán los archivos subidos.';
        return event.returnValue;
    }
}

/**
 * Préférences utilisateur
 */
function saveUserPreferences() {
    localStorage.setItem('copisteria_config', JSON.stringify(currentConfig));
}

function loadUserPreferences() {
    try {
        const saved = localStorage.getItem('copisteria_config');
        if (saved) {
            currentConfig = { ...currentConfig, ...JSON.parse(saved) };
            updateUIFromConfig();
        }
    } catch (error) {
        console.warn('Erreur chargement préférences:', error);
    }
}

function updateUIFromConfig() {
    // Mettre à jour le compteur de copies
    const countElement = document.getElementById('copiesCount');
    if (countElement) {
        countElement.textContent = currentConfig.copies;
    }
    
    // Mettre à jour les commentaires
    const commentsTextarea = document.getElementById('commentsTextarea');
    if (commentsTextarea) {
        commentsTextarea.value = currentConfig.comments;
        updateCharCounter(commentsTextarea);
    }
    
    // Mettre à jour toutes les options sélectionnées
    Object.entries(currentConfig).forEach(([option, value]) => {
        const button = document.querySelector(`[data-option="${option}"][data-value="${value}"]`);
        if (button) {
            const siblings = button.parentElement.querySelectorAll('[data-option="' + option + '"]');
            siblings.forEach(btn => btn.classList.remove('selected', 'green-selected'));
            
            if (option === 'finishing' && value === 'bound') {
                button.classList.add('green-selected');
            } else {
                button.classList.add('selected');
            }
        }
    });
    
    // Mettre à jour les affichages de couleurs
    updateColorDisplay('spiral', currentConfig.spiralColor);
    updateColorDisplay('cover-front', currentConfig.coverFrontColor);
    updateColorDisplay('cover-back', currentConfig.coverBackColor);
}

// Export des fonctions globales pour les événements inline
window.changeCount = changeCount;
window.openColorModal = openColorModal;
window.selectColor = selectColor;
window.processOrder = processOrder;
window.saveConfiguration = saveConfiguration;
window.addMoreFiles = addMoreFiles;
window.previewFile = previewFile;
window.configureFile = configureFile;
window.removeFile = removeFile;