 <?php
// Récupérer les informations de l'utilisateur connecté
$user_name = isset($_SESSION['user_name']) ? $_SESSION['user_name'] : 'Usuario';
$cart_total = isset($_SESSION['cart_total']) ? $_SESSION['cart_total'] : 0.00;
$cart_items = isset($_SESSION['cart_items']) ? $_SESSION['cart_items'] : 0;
?>

<div class="top-header">
    <div class="header-left">
        <div class="logo">
            <i class="fas fa-file-alt"></i> 
            <a href="index.php" class="text-decoration-none">Copisteria Low Cost</a>
        </div>
        
        <!-- File Tabs (will be populated by JavaScript) -->
        <div class="file-tabs" id="fileTabs">
            <!-- Dynamic tabs will appear here -->
        </div>
    </div>
    
    <div class="header-actions">
        <!-- Print Button -->
        <button class="btn btn-outline-primary btn-sm me-2" onclick="printDocument()" title="Imprimir">
            <i class="fas fa-print"></i> Imprimir
        </button>
        
        <!-- Cart Button -->
        <button class="btn btn-primary btn-sm me-2" onclick="viewCart()" title="Carrito">
            <i class="fas fa-shopping-cart"></i> 
            <span id="cartTotal"><?php echo number_format($cart_total, 2); ?> €</span>
            <?php if ($cart_items > 0): ?>
                <span class="badge bg-danger ms-1"><?php echo $cart_items; ?></span>
            <?php endif; ?>
        </button>
        
        <!-- User Dropdown -->
        <div class="dropdown">
            <button class="btn btn-outline-secondary btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown">
                <i class="fas fa-user"></i> <?php echo htmlspecialchars($user_name); ?>
            </button>
            <ul class="dropdown-menu dropdown-menu-end">
                <li>
                    <a class="dropdown-item" href="user/dashboard.php">
                        <i class="fas fa-tachometer-alt"></i> Dashboard
                    </a>
                </li>
                <li>
                    <a class="dropdown-item" href="user/orders.php">
                        <i class="fas fa-box"></i> Mis Pedidos
                    </a>
                </li>
                <li>
                    <a class="dropdown-item" href="user/configurations.php">
                        <i class="fas fa-cog"></i> Configuraciones
                    </a>
                </li>
                <li>
                    <a class="dropdown-item" href="user/profile.php">
                        <i class="fas fa-user-edit"></i> Mi Perfil
                    </a>
                </li>
                <li><hr class="dropdown-divider"></li>
                <li>
                    <a class="dropdown-item text-danger" href="auth/logout.php">
                        <i class="fas fa-sign-out-alt"></i> Cerrar Sesión
                    </a>
                </li>
            </ul>
        </div>
        
        <!-- Settings Button -->
        <button class="btn btn-outline-secondary btn-sm ms-2" onclick="openSettings()" title="Configuración">
            <i class="fas fa-cog"></i>
        </button>
        
        <!-- Menu Button (Mobile) -->
        <button class="btn btn-outline-secondary btn-sm d-md-none ms-2" onclick="toggleSidebar()" title="Menú">
            <i class="fas fa-bars"></i>
        </button>
    </div>
</div>

<!-- Breadcrumb Navigation -->
<nav aria-label="breadcrumb" class="bg-light py-2 px-3 border-bottom">
    <ol class="breadcrumb mb-0">
        <li class="breadcrumb-item">
            <a href="index.php" class="text-decoration-none">
                <i class="fas fa-home"></i> Inicio
            </a>
        </li>
        <li class="breadcrumb-item">
            <a href="print.php" class="text-decoration-none">Imprimir</a>
        </li>
        <li class="breadcrumb-item active" aria-current="page" id="currentStep">
            Configuración
        </li>
    </ol>
</nav>

<script>
// Header JavaScript functions
function printDocument() {
    if (uploadedFiles.length === 0) {
        showAlert('Por favor, sube al menos un archivo para imprimir.', 'warning');
        return;
    }
    
    // Implementar lógica de impresión
    console.log('Imprimiendo documentos...');
    showAlert('Enviando documentos a impresión...', 'info');
}

function viewCart() {
    // Mostrar modal del carrito o redirigir a página del carrito
    window.location.href = 'user/cart.php';
}

function openSettings() {
    // Abrir modal de configuraciones globales
    $('#settingsModal').modal('show');
}

function toggleSidebar() {
    const sidebar = document.querySelector('.config-sidebar');
    sidebar.classList.toggle('d-none');
    sidebar.classList.toggle('d-md-block');
}

function updateCartTotal(total, items = 0) {
    document.getElementById('cartTotal').textContent = total.toFixed(2) + ' €';
    
    const badge = document.querySelector('.badge.bg-danger');
    if (items > 0) {
        if (!badge) {
            const newBadge = document.createElement('span');
            newBadge.className = 'badge bg-danger ms-1';
            newBadge.textContent = items;
            document.getElementById('cartTotal').parentNode.appendChild(newBadge);
        } else {
            badge.textContent = items;
        }
    } else if (badge) {
        badge.remove();
    }
}

function updateBreadcrumb(step) {
    const currentStep = document.getElementById('currentStep');
    const steps = {
        'config': 'Configuración',
        'upload': 'Subir Archivos',
        'review': 'Revisar Pedido',
        'payment': 'Pago',
        'confirmation': 'Confirmación'
    };
    
    currentStep.textContent = steps[step] || 'Configuración';
}

function addFileTab(fileName, fileId) {
    const fileTabs = document.getElementById('fileTabs');
    const tab = document.createElement('div');
    tab.className = 'file-tab';
    tab.dataset.fileId = fileId;
    tab.innerHTML = `
        <span class="file-name">${fileName}</span>
        <button class="btn-close-tab" onclick="removeFileTab('${fileId}')">×</button>
    `;
    fileTabs.appendChild(tab);
}

function removeFileTab(fileId) {
    const tab = document.querySelector(`[data-file-id="${fileId}"]`);
    if (tab) {
        tab.remove();
    }
    
    // También remover el archivo de la lista
    removeUploadedFile(fileId);
}

function showAlert(message, type = 'info') {
    // Crear toast notification
    const toastContainer = document.getElementById('toastContainer') || createToastContainer();
    
    const toast = document.createElement('div');
    toast.className = `toast align-items-center text-bg-${type} border-0`;
    toast.setAttribute('role', 'alert');
    toast.innerHTML = `
        <div class="d-flex">
            <div class="toast-body">
                ${message}
            </div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
        </div>
    `;
    
    toastContainer.appendChild(toast);
    const bsToast = new bootstrap.Toast(toast);
    bsToast.show();
    
    // Remover el toast después de que se oculte
    toast.addEventListener('hidden.bs.toast', () => {
        toast.remove();
    });
}

function createToastContainer() {
    const container = document.createElement('div');
    container.id = 'toastContainer';
    container.className = 'toast-container position-fixed top-0 end-0 p-3';
    container.style.zIndex = '9999';
    document.body.appendChild(container);
    return container;
}
</script>

<style>
.top-header {
    background: white;
    border-bottom: 1px solid #dee2e6;
    padding: 0.75rem 1rem;
    display: flex;
    justify-content: space-between;
    align-items: center;
    position: sticky;
    top: 0;
    z-index: 1000;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.header-left {
    display: flex;
    align-items: center;
    gap: 1rem;
}

.logo {
    font-weight: 600;
    color: #007bff;
    font-size: 1.1rem;
}

.logo a {
    color: inherit;
}

.file-tabs {
    display: flex;
    gap: 0.5rem;
    max-width: 400px;
    overflow-x: auto;
}

.file-tab {
    background: #f8f9fa;
    border: 1px solid #dee2e6;
    border-radius: 6px;
    padding: 0.25rem 0.5rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    white-space: nowrap;
    font-size: 0.8rem;
}

.file-name {
    max-width: 100px;
    overflow: hidden;
    text-overflow: ellipsis;
}

.btn-close-tab {
    background: none;
    border: none;
    color: #6c757d;
    cursor: pointer;
    padding: 0;
    width: 16px;
    height: 16px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
    font-size: 12px;
}

.btn-close-tab:hover {
    background: #dc3545;
    color: white;
}

.header-actions {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

@media (max-width: 768px) {
    .file-tabs {
        display: none;
    }
    
    .header-actions .btn-sm {
        padding: 0.25rem 0.5rem;
        font-size: 0.8rem;
    }
}
</style>