<?php
// Récupérer les configurations sauvegardées de l'utilisateur
$savedConfigs = [];
if (isset($_SESSION['user_id'])) {
    // TODO: Récupérer depuis la base de données
    // $savedConfigs = getUserConfigurations($_SESSION['user_id']);
}
?>

<div class="config-sidebar" id="configSidebar">
    <!-- Configurations Sauvegardées -->
    <?php if (!empty($savedConfigs)): ?>
    <div class="config-section">
        <div class="config-header">
            <div>
                <div class="config-title">Configuraciones Guardadas</div>
                <div class="config-subtitle">Cargar configuración anterior</div>
            </div>
        </div>
        <div class="config-content">
            <select class="form-select form-select-sm" onchange="loadSavedConfig(this.value)">
                <option value="">Seleccionar configuración...</option>
                <?php foreach ($savedConfigs as $config): ?>
                    <option value="<?php echo $config['id']; ?>">
                        <?php echo htmlspecialchars($config['name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>
    <?php endif; ?>

    <!-- Configuración Principal -->
    <div class="config-section">
        <div class="config-header" onclick="toggleSection(this)">
            <div>
                <div class="config-title">Configuración</div>
                <div class="config-subtitle">Selecciona como lo imprimimos</div>
            </div>
            <i class="fas fa-chevron-down chevron-icon"></i>
        </div>
        
        <!-- Copias -->
        <div class="config-content">
            <label class="form-label fw-semibold">
                Copias
                <i class="fas fa-info-circle text-primary ms-1" title="Número de copias por documento"></i>
            </label>
            <div class="copies-counter">
                <button type="button" class="counter-btn" onclick="changeCount(-1)">-</button>
                <span class="counter-display" id="copiesCount">1</span>
                <button type="button" class="counter-btn" onclick="changeCount(1)">+</button>
            </div>
        </div>
    </div>

    <!-- Color de la impresión -->
    <div class="config-section">
        <div class="config-header">
            <div class="config-title">
                Color de la impresión
                <i class="fas fa-info-circle text-primary ms-1" 
                   title="Selecciona entre impresión en blanco y negro o a color"></i>
            </div>
        </div>
        <div class="config-content">
            <div class="option-group">
                <button class="option-btn selected" data-option="color" data-value="bw">
                    <div class="option-text">B/N</div>
                    <div class="option-subtext">Escala de grises</div>
                </button>
                <button class="option-btn" data-option="color" data-value="color">
                    <div class="option-text">Color</div>
                    <div class="option-subtext">Formato CMYK</div>
                </button>
            </div>
        </div>
    </div>

    <!-- Tamaño del papel -->
    <div class="config-section">
        <div class="config-header">
            <div class="config-title">
                Tamaño del papel
                <i class="fas fa-info-circle text-primary ms-1" 
                   title="Formato del papel para la impresión"></i>
            </div>
        </div>
        <div class="config-content">
            <div class="option-group three-columns">
                <button class="option-btn" data-option="size" data-value="a3">
                    <div class="option-text">A3</div>
                    <div class="option-subtext">420 × 297 mm</div>
                </button>
                <button class="option-btn selected" data-option="size" data-value="a4">
                    <div class="option-text">A4</div>
                    <div class="option-subtext">297 × 210 mm</div>
                </button>
                <button class="option-btn" data-option="size" data-value="a5">
                    <div class="option-text">A5</div>
                    <div class="option-subtext">210 × 148 mm</div>
                </button>
            </div>
        </div>
    </div>

    <!-- Grosor del papel -->
    <div class="config-section">
        <div class="config-header">
            <div class="config-title">
                Grosor del papel
                <i class="fas fa-info-circle text-primary ms-1" 
                   title="Peso del papel en gramos por metro cuadrado"></i>
            </div>
        </div>
        <div class="config-content">
            <div class="option-group three-columns">
                <button class="option-btn selected" data-option="weight" data-value="80g">
                    <div class="option-text">80 gr</div>
                    <div class="option-subtext"></div>
                </button>
                <button class="option-btn" data-option="weight" data-value="160g">
                    <div class="option-text">160 gr</div>
                    <div class="option-subtext">Grosor alto</div>
                </button>
                <button class="option-btn" data-option="weight" data-value="280g">
                    <div class="option-text">280 gr</div>
                    <div class="option-subtext">Tipo cartulina</div>
                </button>
            </div>
        </div>
    </div>

    <!-- Forma de impresión -->
    <div class="config-section">
        <div class="config-header">
            <div class="config-title">
                Forma de impresión
                <i class="fas fa-info-circle text-primary ms-1" 
                   title="Imprimir en una cara o ambas caras del papel"></i>
            </div>
        </div>
        <div class="config-content">
            <div class="option-group">
                <button class="option-btn" data-option="sides" data-value="single">
                    <div class="option-text">Una cara</div>
                    <div class="option-subtext">por una sola del papel</div>
                </button>
                <button class="option-btn selected" data-option="sides" data-value="double">
                    <div class="option-text">Doble cara</div>
                    <div class="option-subtext">por ambos lados del papel</div>
                </button>
            </div>
        </div>
    </div>

    <!-- Orientación -->
    <div class="config-section">
        <div class="config-header">
            <div class="config-title">
                Orientación
                <i class="fas fa-info-circle text-primary ms-1" 
                   title="Orientación del papel"></i>
            </div>
        </div>
        <div class="config-content">
            <div class="option-group">
                <button class="option-btn selected" data-option="orientation" data-value="vertical">
                    <div class="option-text">Vertical</div>
                </button>
                <button class="option-btn" data-option="orientation" data-value="horizontal">
                    <div class="option-text">Horizontal</div>
                </button>
            </div>
        </div>
    </div>

    <!-- Pasar página -->
    <div class="config-section">
        <div class="config-header">
            <div class="config-title">Pasar página</div>
        </div>
        <div class="config-content">
            <div class="layout-options">
                <div class="layout-option selected" data-option="flip" data-value="long">
                    <div class="layout-icon">📄</div>
                    <div class="option-text">Lado largo</div>
                </div>
                <div class="layout-option" data-option="flip" data-value="short">
                    <div class="layout-icon">📄</div>
                    <div class="option-text">Lado corto</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Impresiones por cara -->
    <div class="config-section">
        <div class="config-header">
            <div class="config-title">
                Impresiones por cara
                <i class="fas fa-info-circle text-primary ms-1" 
                   title="Número de páginas por hoja impresa"></i>
            </div>
        </div>
        <div class="config-content">
            <div class="finishing-grid">
                <div class="finishing-option selected" data-option="pages_per_sheet" data-value="1">
                    <div class="finishing-icon">📄</div>
                    <div class="finishing-title">Normal</div>
                </div>
                <div class="finishing-option" data-option="pages_per_sheet" data-value="2v">
                    <div class="finishing-icon">📑</div>
                    <div class="finishing-title">2 páginas</div>
                    <div class="finishing-subtitle">Organización vertical</div>
                </div>
                <div class="finishing-option" data-option="pages_per_sheet" data-value="2s">
                    <div class="finishing-icon">📑</div>
                    <div class="finishing-title">2 diapositivas</div>
                </div>
                <div class="finishing-option" data-option="pages_per_sheet" data-value="4">
                    <div class="finishing-icon">📑</div>
                    <div class="finishing-title">4 diapositivas</div>
                    <div class="finishing-subtitle">por cara impresa</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Acabado -->
    <div class="config-section">
        <div class="config-header" onclick="toggleSection(this)">
            <div>
                <div class="config-title">Acabado</div>
                <div class="config-subtitle">Selecciona el tipo de acabado</div>
            </div>
            <i class="fas fa-chevron-down chevron-icon"></i>
        </div>
        <div class="config-content">
            <div class="finishing-grid">
                <div class="finishing-option" data-option="finishing" data-value="individual">
                    <div class="finishing-icon">📄</div>
                    <div class="finishing-title">Individual</div>
                    <div class="finishing-subtitle">Cada documento</div>
                </div>
                <div class="finishing-option" data-option="finishing" data-value="grouped">
                    <div class="finishing-icon">📚</div>
                    <div class="finishing-title">Agrupado</div>
                    <div class="finishing-subtitle">Todos en uno</div>
                </div>
                <div class="finishing-option" data-option="finishing" data-value="none">
                    <div class="finishing-icon">📋</div>
                    <div class="finishing-title">Sin acabado</div>
                    <div class="finishing-subtitle">Solo impresión</div>
                </div>
                <div class="finishing-option green-selected" data-option="finishing" data-value="bound">
                    <div class="finishing-icon">📖</div>
                    <div class="finishing-title">Encuadernado</div>
                    <div class="finishing-subtitle">En espiral</div>
                </div>
                <div class="finishing-option" data-option="finishing" data-value="stapled">
                    <div class="finishing-icon">📎</div>
                    <div class="finishing-title">Grapado</div>
                    <div class="finishing-subtitle">En esquina</div>
                </div>
                <div class="finishing-option" data-option="finishing" data-value="laminated">
                    <div class="finishing-icon">🔒</div>
                    <div class="finishing-title">Plastificado</div>
                    <div class="finishing-subtitle">Cubierta asoma</div>
                </div>
                <div class="finishing-option" data-option="finishing" data-value="perforated">
                    <div class="finishing-icon">⚬</div>
                    <div class="finishing-title">Perforado</div>
                    <div class="finishing-subtitle">3 agujeros</div>
                </div>
                <div class="finishing-option" data-option="finishing" data-value="perforated4">
                    <div class="finishing-icon">⚬⚬</div>
                    <div class="finishing-title">Perforado</div>
                    <div class="finishing-subtitle">4 agujeros</div>
                </div>
            </div>

            <!-- Sub-options for binding (shown when Encuadernado is selected) -->
            <div class="sub-options" id="bindingSubOptions">
                <h6 class="mt-3 mb-2">Color de la espiral</h6>
                <div class="sub-option-item" onclick="openColorModal('spiral')">
                    <div class="sub-option-left">
                        <div class="color-preview color-black" id="spiralColorPreview"></div>
                        <div>
                            <div>Espiral color negra</div>
                            <span class="badge bg-dark" id="spiralColorBadge">Negra</span>
                        </div>
                    </div>
                    <i class="fas fa-chevron-right"></i>
                </div>

                <h6 class="mt-3 mb-2">Color de las tapas</h6>
                <div class="sub-option-item" onclick="openColorModal('cover-front')">
                    <div class="sub-option-left">
                        <div class="color-preview color-transparent" id="coverFrontColorPreview"></div>
                        <div>
                            <div>Tapa delantera</div>
                            <span class="badge bg-secondary d-none" id="coverFrontColorBadge"></span>
                        </div>
                    </div>
                    <i class="fas fa-chevron-right"></i>
                </div>
                <div class="sub-option-item" onclick="openColorModal('cover-back')">
                    <div class="sub-option-left">
                        <div class="color-preview color-black" id="coverBackColorPreview"></div>
                        <div>
                            <div>Tapa trasera</div>
                            <span class="badge bg-dark" id="coverBackColorBadge">Negra</span>
                        </div>
                    </div>
                    <i class="fas fa-chevron-right"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Comentario -->
    <div class="config-section">
        <div class="config-header" onclick="toggleSection(this)">
            <div>
                <div class="config-title">Comentario</div>
                <div class="config-subtitle">Comentario de la impresión</div>
            </div>
            <i class="fas fa-chevron-down chevron-icon"></i>
        </div>
        <div class="config-content">
            <div class="comments-section">
                <textarea 
                    class="form-control" 
                    id="commentsTextarea"
                    placeholder="Comentario de impresión..."
                    rows="4"
                    maxlength="400"
                    oninput="updateCharCounter(this)"></textarea>
                <div class="char-counter text-end mt-1">
                    <small class="text-muted">
                        <span id="charCount">0</span> / 400
                    </small>
                </div>
            </div>
        </div>
    </div>

    <!-- Acciones -->
    <div class="config-section">
        <div class="d-grid gap-2">
            <button class="btn btn-outline-primary btn-sm" onclick="saveCurrentConfiguration()">
                <i class="fas fa-save"></i> Guardar Configuración
            </button>
            <button class="btn btn-outline-secondary btn-sm" onclick="resetConfiguration()">
                <i class="fas fa-undo"></i> Resetear
            </button>
            <button class="btn btn-outline-info btn-sm" onclick="previewConfiguration()">
                <i class="fas fa-eye"></i> Vista Previa
            </button>
        </div>
    </div>

    <!-- Precio Estimado -->
    <div class="config-section bg-light">
        <div class="text-center">
            <h6 class="mb-2">
                <i class="fas fa-calculator text-primary"></i> 
                Precio Estimado
            </h6>
            <div class="price-breakdown">
                <div class="d-flex justify-content-between small">
                    <span>Impresión:</span>
                    <span id="printingCost">0,00 €</span>
                </div>
                <div class="d-flex justify-content-between small">
                    <span>Acabado:</span>
                    <span id="finishingCost">0,00 €</span>
                </div>
                <div class="d-flex justify-content-between small">
                    <span>Copias (×<span id="copiesMultiplier">1</span>):</span>
                    <span id="copiesCost">0,00 €</span>
                </div>
                <hr class="my-2">
                <div class="d-flex justify-content-between fw-bold">
                    <span>Total:</span>
                    <span class="text-primary" id="totalEstimatedCost">0,00 €</span>
                </div>
            </div>
            <small class="text-muted d-block mt-2">
                *Precio final puede variar según archivos
            </small>
        </div>
    </div>
</div>

<script>
// Sidebar JavaScript functions
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

// Toggle section collapse
function toggleSection(header) {
    const section = header.parentElement;
    section.classList.toggle('collapsed');
    
    const chevron = header.querySelector('.chevron-icon');
    chevron.classList.toggle('fa-chevron-down');
    chevron.classList.toggle('fa-chevron-right');
}

// Counter functions
function changeCount(delta) {
    const countElement = document.getElementById('copiesCount');
    let count = parseInt(countElement.textContent);
    count = Math.max(1, Math.min(999, count + delta));
    countElement.textContent = count;
    currentConfig.copies = count;
    
    // Update multiplier display
    document.getElementById('copiesMultiplier').textContent = count;
    
    updatePricing();
}

// Character counter for comments
function updateCharCounter(textarea) {
    const charCount = document.getElementById('charCount');
    const remaining = textarea.value.length;
    charCount.textContent = remaining;
    
    currentConfig.comments = textarea.value;
    
    // Change color if approaching limit
    const counter = charCount.parentElement;
    if (remaining > 350) {
        counter.classList.add('text-warning');
        counter.classList.remove('text-muted');
    } else if (remaining === 400) {
        counter.classList.add('text-danger');
        counter.classList.remove('text-warning', 'text-muted');
    } else {
        counter.classList.add('text-muted');
        counter.classList.remove('text-warning', 'text-danger');
    }
}

// Option selection handlers
document.addEventListener('click', function(e) {
    // Handle regular option buttons
    if (e.target.closest('.option-btn')) {
        const button = e.target.closest('.option-btn');
        const option = button.dataset.option;
        const value = button.dataset.value;
        
        // Remove selection from siblings
        const siblings = button.parentElement.querySelectorAll('.option-btn');
        siblings.forEach(btn => btn.classList.remove('selected'));
        
        // Select current button
        button.classList.add('selected');
        
        // Update config
        currentConfig[option] = value;
        updatePricing();
    }
    
    // Handle layout options
    if (e.target.closest('.layout-option')) {
        const button = e.target.closest('.layout-option');
        const option = button.dataset.option;
        const value = button.dataset.value;
        
        // Remove selection from siblings
        const siblings = button.parentElement.querySelectorAll('.layout-option');
        siblings.forEach(btn => btn.classList.remove('selected'));
        
        // Select current button
        button.classList.add('selected');
        
        // Update config
        currentConfig[option] = value;
        updatePricing();
    }
    
    // Handle finishing options
    if (e.target.closest('.finishing-option')) {
        const button = e.target.closest('.finishing-option');
        const option = button.dataset.option;
        const value = button.dataset.value;
        
        // Remove selection from siblings
        const siblings = button.parentElement.querySelectorAll('.finishing-option');
        siblings.forEach(btn => btn.classList.remove('selected', 'green-selected'));
        
        // Select current button
        if (value === 'bound') {
            button.classList.add('green-selected');
            document.getElementById('bindingSubOptions').style.display = 'block';
        } else {
            button.classList.add('selected');
            document.getElementById('bindingSubOptions').style.display = 'none';
        }
        
        // Update config
        currentConfig[option] = value;
        updatePricing();
    }
});

// Color modal functions
function openColorModal(target) {
    const modal = new bootstrap.Modal(document.getElementById('colorModal'));
    const title = document.getElementById('colorModalTitle');
    const subtitle = document.getElementById('colorModalSubtitle');
    
    // Store current target
    document.getElementById('colorModal').dataset.target = target;
    
    // Update modal content based on target
    switch(target) {
        case 'spiral':
            title.textContent = 'Color de la espiral';
            subtitle.textContent = 'Selecciona el color para la espiral';
            break;
        case 'cover-front':
            title.textContent = 'Color de la tapa delantera';
            subtitle.textContent = 'Selecciona el color para la tapa delantera';
            break;
        case 'cover-back':
            title.textContent = 'Color de la tapa trasera';
            subtitle.textContent = 'Selecciona el color para la tapa trasera';
            break;
    }
    
    // Populate color grid
    populateColorGrid(target);
    
    modal.show();
}

function populateColorGrid(target) {
    const colorGrid = document.getElementById('colorGrid');
    const colors = [
        {id: 'black', name: 'Negra', class: 'color-black'},
        {id: 'transparent', name: 'Transparente', class: 'color-transparent'},
        {id: 'yellow', name: 'Amarilla', class: 'color-yellow'},
        {id: 'turquoise', name: 'Turquesa', class: 'color-turquoise'},
        {id: 'pink', name: 'Rosa', class: 'color-pink'},
        {id: 'green', name: 'Verde menta', class: 'color-green'},
        {id: 'gold', name: 'Dorado', class: 'color-gold'},
        {id: 'blue', name: 'Azul pastel', class: 'color-blue'},
        {id: 'purple', name: 'Lila', class: 'color-purple'}
    ];
    
    const currentColor = getCurrentColor(target);
    
    colorGrid.innerHTML = colors.map(color => `
        <div class="color-choice ${color.id === currentColor ? 'selected' : ''}" 
             data-color="${color.id}" 
             onclick="selectColor('${color.id}')">
            <div class="color-choice-preview ${color.class}"></div>
            <div class="color-choice-name">${color.name}</div>
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

function selectColor(colorId) {
    const modal = document.getElementById('colorModal');
    const target = modal.dataset.target;
    
    // Update configuration
    switch(target) {
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
    
    // Close modal
    const bsModal = bootstrap.Modal.getInstance(modal);
    bsModal.hide();
    
    updatePricing();
}

function updateColorDisplay(target, colorId) {
    const colorNames = {
        'black': 'Negra',
        'transparent': 'Transparente',
        'yellow': 'Amarilla',
        'turquoise': 'Turquesa',
        'pink': 'Rosa',
        'green': 'Verde menta',
        'gold': 'Dorado',
        'blue': 'Azul pastel',
        'purple': 'Lila'
    };
    
    const previewId = target + 'ColorPreview';
    const badgeId = target.replace('-', '') + 'ColorBadge';
    
    const preview = document.getElementById(previewId);
    const badge = document.getElementById(badgeId);
    
    if (preview) {
        preview.className = `color-preview color-${colorId}`;
    }
    
    if (badge) {
        if (colorId === 'transparent') {
            badge.classList.add('d-none');
        } else {
            badge.classList.remove('d-none');
            badge.textContent = colorNames[colorId];
            badge.className = `badge ${colorId === 'black' ? 'bg-dark' : 'bg-primary'}`;
        }
    }
}

// Configuration management
function saveCurrentConfiguration() {
    const name = prompt('Nombre para esta configuración:');
    if (!name) return;
    
    // TODO: Save to database via AJAX
    const configData = {
        name: name,
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
            showAlert('Error al guardar la configuración', 'danger');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('Error al guardar la configuración', 'danger');
    });
}

function resetConfiguration() {
    if (!confirm('¿Estás seguro de que quieres resetear la configuración?')) return;
    
    // Reset to default values
    currentConfig = {
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
    
    // Update UI
    updateUIFromConfig();
    showAlert('Configuración restablecida', 'info');
}

function previewConfiguration() {
    // TODO: Show configuration preview modal
    console.log('Vista previa de configuración:', currentConfig);
    showAlert('Vista previa - En desarrollo', 'info');
}

function loadSavedConfig(configId) {
    if (!configId) return;
    
    // TODO: Load configuration from database
    fetch(`ajax/get-config.php?id=${configId}`)
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            currentConfig = data.config;
            updateUIFromConfig();
            showAlert(`Configuración "${data.name}" cargada`, 'success');
        } else {
            showAlert('Error al cargar la configuración', 'danger');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('Error al cargar la configuración', 'danger');
    });
}

function updateUIFromConfig() {
    // Update copies counter
    document.getElementById('copiesCount').textContent = currentConfig.copies;
    document.getElementById('copiesMultiplier').textContent = currentConfig.copies;
    
    // Update comments
    document.getElementById('commentsTextarea').value = currentConfig.comments;
    updateCharCounter(document.getElementById('commentsTextarea'));
    
    // Update all option buttons
    document.querySelectorAll('[data-option]').forEach(button => {
        const option = button.dataset.option;
        const value = button.dataset.value;
        
        if (currentConfig[option] === value) {
            button.classList.add('selected');
        } else {
            button.classList.remove('selected');
        }
    });
    
    // Update color displays
    updateColorDisplay('spiral', currentConfig.spiralColor);
    updateColorDisplay('cover-front', currentConfig.coverFrontColor);
    updateColorDisplay('cover-back', currentConfig.coverBackColor);
    
    // Update pricing
    updatePricing();
}

// Pricing calculation
function updatePricing() {
    // Basic pricing logic (will be enhanced with real data)
    let printingCost = 0;
    let finishingCost = 0;
    
    // Calculate printing cost per page
    const basePrice = currentConfig.size === 'a4' ? 
        (currentConfig.color === 'bw' ? 0.05 : 0.15) : 
        (currentConfig.color === 'bw' ? 0.10 : 0.25);
    
    // Paper weight adjustment
    const weightMultiplier = currentConfig.weight === '160g' ? 1.2 : 
                           currentConfig.weight === '280g' ? 1.5 : 1.0;
    
    // Estimate pages (will be updated when files are uploaded)
    const estimatedPages = uploadedFiles ? uploadedFiles.length * 5 : 10;
    
    printingCost = basePrice * weightMultiplier * estimatedPages;
    
    // Finishing costs
    switch(currentConfig.finishing) {
        case 'bound':
            finishingCost = 2.50;
            break;
        case 'stapled':
            finishingCost = 0.50;
            break;
        case 'laminated':
            finishingCost = 1.00;
            break;
        case 'perforated':
        case 'perforated4':
            finishingCost = 0.25;
            break;
    }
    
    const copiesCost = (printingCost + finishingCost) * currentConfig.copies;
    const total = copiesCost;
    
    // Update display
    document.getElementById('printingCost').textContent = printingCost.toFixed(2) + ' €';
    document.getElementById('finishingCost').textContent = finishingCost.toFixed(2) + ' €';
    document.getElementById('copiesCost').textContent = copiesCost.toFixed(2) + ' €';
    document.getElementById('totalEstimatedCost').textContent = total.toFixed(2) + ' €';
    
    // Update header cart total
    if (typeof updateCartTotal === 'function') {
        updateCartTotal(total);
    }
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    updatePricing();
    
    // Initialize binding sub-options visibility
    const bindingButton = document.querySelector('[data-value="bound"]');
    if (bindingButton && bindingButton.classList.contains('green-selected')) {
        document.getElementById('bindingSubOptions').style.display = 'block';
    }
});
</script>