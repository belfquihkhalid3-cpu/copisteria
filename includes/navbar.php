<?php
// Navigation principale pour toutes les pages
$currentPage = basename($_SERVER['PHP_SELF']);
$currentPath = $_SERVER['REQUEST_URI'];

// Déterminer la section active
$activeSection = '';
if (strpos($currentPath, '/user/') !== false) {
    $activeSection = 'user';
} elseif (strpos($currentPath, '/admin/') !== false) {
    $activeSection = 'admin';
} elseif (strpos($currentPath, '/auth/') !== false) {
    $activeSection = 'auth';
} elseif ($currentPage === 'print.php') {
    $activeSection = 'print';
} else {
    $activeSection = 'home';
}
?>

<nav class="navbar navbar-expand-lg navbar-dark bg-primary sticky-top">
    <div class="container-fluid">
        <!-- Logo/Marque -->
        <a class="navbar-brand fw-bold" href="<?php echo BASE_URL; ?>">
            <i class="fas fa-print me-2"></i>Copisteria Low Cost
        </a>

        <!-- Bouton mobile -->
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="navbarNav">
            <!-- Menu principal -->
            <ul class="navbar-nav me-auto">
                <li class="nav-item">
                    <a class="nav-link <?php echo $activeSection === 'home' ? 'active' : ''; ?>" 
                       href="<?php echo BASE_URL; ?>">
                        <i class="fas fa-home me-1"></i>Inicio
                    </a>
                </li>
                
                <li class="nav-item">
                    <a class="nav-link <?php echo $activeSection === 'print' ? 'active' : ''; ?>" 
                       href="<?php echo BASE_URL; ?>/print.php">
                        <i class="fas fa-print me-1"></i>Imprimir
                    </a>
                </li>

                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                        <i class="fas fa-info-circle me-1"></i>Servicios
                    </a>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>/print.php">
                            <i class="fas fa-print me-2"></i>Impresión Digital</a></li>
                        <li><a class="dropdown-item" href="#">
                            <i class="fas fa-book me-2"></i>Encuadernación</a></li>
                        <li><a class="dropdown-item" href="#">
                            <i class="fas fa-shield-alt me-2"></i>Plastificado</a></li>
                        <li><a class="dropdown-item" href="#">
                            <i class="fas fa-cut me-2"></i>Acabados Especiales</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="#">
                            <i class="fas fa-palette me-2"></i>Diseño Gráfico</a></li>
                    </ul>
                </li>

                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                        <i class="fas fa-question-circle me-1"></i>Ayuda
                    </a>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="#">
                            <i class="fas fa-question me-2"></i>Preguntas Frecuentes</a></li>
                        <li><a class="dropdown-item" href="#">
                            <i class="fas fa-file-alt me-2"></i>Formatos Compatibles</a></li>
                        <li><a class="dropdown-item" href="#">
                            <i class="fas fa-calculator me-2"></i>Calculadora de Precios</a></li>
                        <li><a class="dropdown-item" href="#">
                            <i class="fas fa-truck me-2"></i>Entregas</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="#">
                            <i class="fas fa-envelope me-2"></i>Contactar Soporte</a></li>
                    </ul>
                </li>
            </ul>

            <!-- Menu usuario -->
            <ul class="navbar-nav">
                <?php if (isset($_SESSION['user_id'])): ?>
                    <!-- Usuario logueado -->
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle <?php echo $activeSection === 'user' ? 'active' : ''; ?>" 
                           href="#" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-user-circle me-1"></i>
                            <?php echo htmlspecialchars($_SESSION['user_name'] ?? 'Usuario'); ?>
                            <?php if (isset($_SESSION['unread_notifications']) && $_SESSION['unread_notifications'] > 0): ?>
                                <span class="badge bg-danger ms-1"><?php echo $_SESSION['unread_notifications']; ?></span>
                            <?php endif; ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li class="dropdown-header">
                                <small class="text-muted"><?php echo htmlspecialchars($_SESSION['user_email'] ?? ''); ?></small>
                            </li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>/user/dashboard.php">
                                <i class="fas fa-tachometer-alt me-2"></i>Mi Panel</a></li>
                            <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>/user/orders.php">
                                <i class="fas fa-shopping-cart me-2"></i>Mis Pedidos
                                <?php if (isset($_SESSION['active_orders']) && $_SESSION['active_orders'] > 0): ?>
                                    <span class="badge bg-warning ms-1"><?php echo $_SESSION['active_orders']; ?></span>
                                <?php endif; ?>
                            </a></li>
                            <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>/user/configurations.php">
                                <i class="fas fa-cog me-2"></i>Mis Configuraciones</a></li>
                            <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>/user/profile.php">
                                <i class="fas fa-user me-2"></i>Mi Perfil</a></li>
                            
                            <?php if (isset($_SESSION['is_admin']) && $_SESSION['is_admin']): ?>
                                <li><hr class="dropdown-divider"></li>
                                <li class="dropdown-header">Administración</li>
                                <li><a class="dropdown-item <?php echo $activeSection === 'admin' ? 'active' : ''; ?>" 
                                       href="<?php echo BASE_URL; ?>/admin/index.php">
                                    <i class="fas fa-crown me-2 text-warning"></i>Panel Admin</a></li>
                                <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>/admin/orders.php">
                                    <i class="fas fa-list me-2"></i>Gestionar Pedidos</a></li>
                                <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>/admin/users.php">
                                    <i class="fas fa-users me-2"></i>Gestionar Usuarios</a></li>
                                <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>/admin/settings.php">
                                    <i class="fas fa-cogs me-2"></i>Configuración</a></li>
                            <?php endif; ?>
                            
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item text-danger" href="<?php echo BASE_URL; ?>/auth/logout.php">
                                <i class="fas fa-sign-out-alt me-2"></i>Cerrar Sesión</a></li>
                        </ul>
                    </li>
                <?php else: ?>
                    <!-- Usuario no logueado -->
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo BASE_URL; ?>/auth/login.php">
                            <i class="fas fa-sign-in-alt me-1"></i>Iniciar Sesión
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo BASE_URL; ?>/auth/register.php">
                            <i class="fas fa-user-plus me-1"></i>Registro
                        </a>
                    </li>
                <?php endif; ?>

                <!-- Carrito/Estado actual -->
                <?php if ($activeSection === 'print'): ?>
                    <li class="nav-item">
                        <button class="nav-link btn btn-link text-light" id="cartSummary" data-bs-toggle="modal" data-bs-target="#cartModal">
                            <i class="fas fa-shopping-cart me-1"></i>
                            <span id="cartCount">0</span> archivo(s)
                            <span id="cartTotal">0.00€</span>
                        </button>
                    </li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>

<!-- Breadcrumb para navegación interna -->
<?php if ($activeSection !== 'home' && $activeSection !== 'print'): ?>
<nav aria-label="breadcrumb" class="bg-light border-bottom">
    <div class="container-fluid">
        <ol class="breadcrumb mb-0 py-2">
            <li class="breadcrumb-item">
                <a href="<?php echo BASE_URL; ?>" class="text-decoration-none">
                    <i class="fas fa-home me-1"></i>Inicio
                </a>
            </li>
            <?php
            $breadcrumbs = [];
            switch ($activeSection) {
                case 'user':
                    $breadcrumbs[] = ['url' => '/user/dashboard.php', 'title' => 'Mi Cuenta'];
                    if ($currentPage === 'orders.php') {
                        $breadcrumbs[] = ['url' => '', 'title' => 'Mis Pedidos'];
                    } elseif ($currentPage === 'profile.php') {
                        $breadcrumbs[] = ['url' => '', 'title' => 'Mi Perfil'];
                    } elseif ($currentPage === 'configurations.php') {
                        $breadcrumbs[] = ['url' => '', 'title' => 'Configuraciones'];
                    } elseif ($currentPage === 'order-detail.php') {
                        $breadcrumbs[] = ['url' => '/user/orders.php', 'title' => 'Mis Pedidos'];
                        $breadcrumbs[] = ['url' => '', 'title' => 'Detalle del Pedido'];
                    }
                    break;
                case 'admin':
                    $breadcrumbs[] = ['url' => '/admin/index.php', 'title' => 'Administración'];
                    if ($currentPage === 'orders.php') {
                        $breadcrumbs[] = ['url' => '', 'title' => 'Gestión de Pedidos'];
                    } elseif ($currentPage === 'users.php') {
                        $breadcrumbs[] = ['url' => '', 'title' => 'Gestión de Usuarios'];
                    } elseif ($currentPage === 'settings.php') {
                        $breadcrumbs[] = ['url' => '', 'title' => 'Configuración del Sistema'];
                    }
                    break;
                case 'auth':
                    if ($currentPage === 'login.php') {
                        $breadcrumbs[] = ['url' => '', 'title' => 'Iniciar Sesión'];
                    } elseif ($currentPage === 'register.php') {
                        $breadcrumbs[] = ['url' => '', 'title' => 'Crear Cuenta'];
                    } elseif ($currentPage === 'forgot-password.php') {
                        $breadcrumbs[] = ['url' => '', 'title' => 'Recuperar Contraseña'];
                    }
                    break;
            }

            foreach ($breadcrumbs as $index => $breadcrumb): ?>
                <li class="breadcrumb-item <?php echo empty($breadcrumb['url']) ? 'active' : ''; ?>">
                    <?php if (!empty($breadcrumb['url'])): ?>
                        <a href="<?php echo BASE_URL . $breadcrumb['url']; ?>" class="text-decoration-none">
                            <?php echo htmlspecialchars($breadcrumb['title']); ?>
                        </a>
                    <?php else: ?>
                        <?php echo htmlspecialchars($breadcrumb['title']); ?>
                    <?php endif; ?>
                </li>
            <?php endforeach; ?>
        </ol>
    </div>
</nav>
<?php endif; ?>

<!-- Modal del carrito (solo en página de impresión) -->
<?php if ($activeSection === 'print'): ?>
<div class="modal fade" id="cartModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-shopping-cart me-2"></i>Resumen del Pedido
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="cartContent">
                    <div class="text-center py-4">
                        <i class="fas fa-shopping-cart fa-3x text-muted mb-3"></i>
                        <p class="text-muted">No hay archivos en el carrito</p>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Seguir Editando</button>
                <button type="button" class="btn btn-primary" id="proceedToOrder" disabled>
                    <i class="fas fa-arrow-right me-2"></i>Proceder al Pedido
                </button>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<script>
// Actualizar contador de notificaciones
<?php if (isset($_SESSION['user_id'])): ?>
function updateNotificationCount() {
    fetch('<?php echo BASE_URL; ?>/ajax/get-notification-count.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: JSON.stringify({
            csrf_token: '<?php echo $_SESSION['csrf_token'] ?? ''; ?>'
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const badge = document.querySelector('.navbar-nav .badge');
            if (data.count > 0) {
                if (badge) {
                    badge.textContent = data.count;
                } else {
                    // Crear badge si no existe
                    const userLink = document.querySelector('.navbar-nav .dropdown-toggle');
                    if (userLink) {
                        const newBadge = document.createElement('span');
                        newBadge.className = 'badge bg-danger ms-1';
                        newBadge.textContent = data.count;
                        userLink.appendChild(newBadge);
                    }
                }
            } else if (badge) {
                badge.remove();
            }
        }
    })
    .catch(error => console.error('Error updating notifications:', error));
}

// Actualizar cada 30 segundos
setInterval(updateNotificationCount, 30000);
<?php endif; ?>

// Mejorar la experiencia de navegación
document.addEventListener('DOMContentLoaded', function() {
    // Cerrar dropdowns al hacer clic fuera
    document.addEventListener('click', function(e) {
        if (!e.target.closest('.dropdown')) {
            const openDropdowns = document.querySelectorAll('.dropdown-menu.show');
            openDropdowns.forEach(dropdown => {
                const bsDropdown = bootstrap.Dropdown.getInstance(dropdown.previousElementSibling);
                if (bsDropdown) bsDropdown.hide();
            });
        }
    });
    
    // Resaltar página actual en navegación
    const currentUrl = window.location.pathname;
    const navLinks = document.querySelectorAll('.navbar-nav .nav-link');
    navLinks.forEach(link => {
        if (link.getAttribute('href') === currentUrl) {
            link.classList.add('active');
        }
    });
});
</script>