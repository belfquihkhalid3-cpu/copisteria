</main>

    <!-- Footer -->
    <footer class="bg-dark text-light py-4 mt-5">
        <div class="container-fluid">
            <div class="row">
                <div class="col-lg-4 mb-3">
                    <h5 class="text-primary mb-3">Copisteria Low Cost</h5>
                    <p class="text-muted">Tu servicio de impresión profesional online. Calidad garantizada a los mejores precios del mercado.</p>
                    <div class="d-flex gap-2">
                        <a href="#" class="text-light"><i class="fab fa-facebook"></i></a>
                        <a href="#" class="text-light"><i class="fab fa-twitter"></i></a>
                        <a href="#" class="text-light"><i class="fab fa-instagram"></i></a>
                        <a href="#" class="text-light"><i class="fab fa-linkedin"></i></a>
                    </div>
                </div>
                <div class="col-lg-2 col-md-6 mb-3">
                    <h6 class="mb-3">Servicios</h6>
                    <ul class="list-unstyled">
                        <li><a href="/print.php" class="text-muted text-decoration-none">Impresión</a></li>
                        <li><a href="#" class="text-muted text-decoration-none">Encuadernación</a></li>
                        <li><a href="#" class="text-muted text-decoration-none">Plastificado</a></li>
                        <li><a href="#" class="text-muted text-decoration-none">Diseño</a></li>
                    </ul>
                </div>
                <div class="col-lg-2 col-md-6 mb-3">
                    <h6 class="mb-3">Empresa</h6>
                    <ul class="list-unstyled">
                        <li><a href="#" class="text-muted text-decoration-none">Sobre Nosotros</a></li>
                        <li><a href="#" class="text-muted text-decoration-none">Contacto</a></li>
                        <li><a href="#" class="text-muted text-decoration-none">Ubicación</a></li>
                        <li><a href="#" class="text-muted text-decoration-none">Trabajar con Nosotros</a></li>
                    </ul>
                </div>
                <div class="col-lg-2 col-md-6 mb-3">
                    <h6 class="mb-3">Soporte</h6>
                    <ul class="list-unstyled">
                        <li><a href="#" class="text-muted text-decoration-none">Centro de Ayuda</a></li>
                        <li><a href="#" class="text-muted text-decoration-none">Preguntas Frecuentes</a></li>
                        <li><a href="#" class="text-muted text-decoration-none">Términos de Uso</a></li>
                        <li><a href="#" class="text-muted text-decoration-none">Privacidad</a></li>
                    </ul>
                </div>
                <div class="col-lg-2 col-md-6 mb-3">
                    <h6 class="mb-3">Contacto</h6>
                    <ul class="list-unstyled text-muted">
                        <li><i class="fas fa-map-marker-alt me-2"></i>Calle Principal, 123</li>
                        <li><i class="fas fa-phone me-2"></i>+34 900 123 456</li>
                        <li><i class="fas fa-envelope me-2"></i>info@copisteria.com</li>
                        <li><i class="fas fa-clock me-2"></i>Lun-Vie: 8:00-20:00</li>
                    </ul>
                </div>
            </div>
            <hr class="my-4 opacity-25">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <p class="mb-0 text-muted">&copy; <?php echo date('Y'); ?> Copisteria Low Cost. Todos los derechos reservados.</p>
                </div>
                <div class="col-md-6 text-md-end">
                    <span class="text-muted small">
                        Versión <?php 
                        try {
                            $db = new Database();
                            $conn = $db->getConnection();
                            $stmt = $conn->prepare("SELECT setting_value FROM system_settings WHERE setting_key = 'site_version' LIMIT 1");
                            $stmt->execute();
                            echo $stmt->fetchColumn() ?: '1.0.0';
                        } catch (Exception $e) {
                            echo '1.0.0';
                        }
                        ?>
                    </span>
                </div>
            </div>
        </div>
    </footer>

    <!-- Scripts globales -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Script principal personalizado -->
    <script>
    // Configuración global
    window.copisteriaConfig = {
        baseUrl: '<?php echo BASE_URL; ?>',
        currentUser: <?php echo isset($_SESSION['user_id']) ? json_encode([
            'id' => $_SESSION['user_id'],
            'name' => $_SESSION['user_name'] ?? 'Usuario',
            'email' => $_SESSION['user_email'] ?? '',
            'isAdmin' => $_SESSION['is_admin'] ?? false
        ]) : 'null'; ?>,
        csrfToken: '<?php echo $_SESSION['csrf_token'] ?? ''; ?>'
    };

    // Funciones globales
    function showToast(message, type = 'info') {
        const toastContainer = document.getElementById('toastContainer') || createToastContainer();
        const toast = createToastElement(message, type);
        toastContainer.appendChild(toast);
        
        const bsToast = new bootstrap.Toast(toast);
        bsToast.show();
        
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

    function createToastElement(message, type) {
        const icons = {
            success: 'fas fa-check-circle',
            error: 'fas fa-exclamation-triangle',
            warning: 'fas fa-exclamation-circle',
            info: 'fas fa-info-circle'
        };
        
        const colors = {
            success: 'text-success',
            error: 'text-danger',
            warning: 'text-warning',
            info: 'text-primary'
        };

        const toast = document.createElement('div');
        toast.className = 'toast';
        toast.setAttribute('role', 'alert');
        toast.innerHTML = `
            <div class="toast-header">
                <i class="${icons[type] || icons.info} ${colors[type] || colors.info} me-2"></i>
                <strong class="me-auto">Copisteria</strong>
                <button type="button" class="btn-close" data-bs-dismiss="toast"></button>
            </div>
            <div class="toast-body">
                ${message}
            </div>
        `;
        return toast;
    }

    // Confirmación para enlaces destructivos
    document.addEventListener('click', function(e) {
        if (e.target.matches('[data-confirm]')) {
            if (!confirm(e.target.dataset.confirm)) {
                e.preventDefault();
                return false;
            }
        }
    });

    // Auto-logout por inactividad (30 minutos)
    <?php if (isset($_SESSION['user_id'])): ?>
    let inactivityTimer;
    
    function resetInactivityTimer() {
        clearTimeout(inactivityTimer);
        inactivityTimer = setTimeout(() => {
            if (confirm('Tu sesión expirará por inactividad. ¿Deseas continuar?')) {
                resetInactivityTimer();
            } else {
                window.location.href = '/auth/logout.php';
            }
        }, 1800000); // 30 minutos
    }
    
    ['mousedown', 'mousemove', 'keypress', 'scroll', 'touchstart'].forEach(event => {
        document.addEventListener(event, resetInactivityTimer, true);
    });
    
    resetInactivityTimer();
    <?php endif; ?>

    // Validación de formularios
    document.addEventListener('DOMContentLoaded', function() {
        // Bootstrap form validation
        const forms = document.querySelectorAll('.needs-validation');
        Array.from(forms).forEach(form => {
            form.addEventListener('submit', event => {
                if (!form.checkValidity()) {
                    event.preventDefault();
                    event.stopPropagation();
                }
                form.classList.add('was-validated');
            }, false);
        });

        // Tooltips
        const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });

        // Popovers
        const popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
        popoverTriggerList.map(function (popoverTriggerEl) {
            return new bootstrap.Popover(popoverTriggerEl);
        });

        // Auto-hide alerts
        const alerts = document.querySelectorAll('.alert[data-auto-dismiss]');
        alerts.forEach(alert => {
            const delay = parseInt(alert.dataset.autoDismiss) || 5000;
            setTimeout(() => {
                const bsAlert = new bootstrap.Alert(alert);
                bsAlert.close();
            }, delay);
        });
    });

    // Función para formatear números
    function formatNumber(number, decimals = 2) {
        return new Intl.NumberFormat('es-ES', {
            minimumFractionDigits: decimals,
            maximumFractionDigits: decimals
        }).format(number);
    }

    // Función para formatear precios
    function formatPrice(price) {
        return formatNumber(price, 2) + '€';
    }

    // Función para formatear fechas
    function formatDate(date, options = {}) {
        const defaultOptions = {
            year: 'numeric',
            month: 'short',
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        };
        return new Intl.DateTimeFormat('es-ES', {...defaultOptions, ...options}).format(new Date(date));
    }

    // Función AJAX genérica
    function makeRequest(url, options = {}) {
        const defaultOptions = {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            credentials: 'same-origin'
        };

        if (options.body && typeof options.body === 'object') {
            options.body.csrf_token = window.copisteriaConfig.csrfToken;
            options.body = JSON.stringify(options.body);
        }

        return fetch(url, {...defaultOptions, ...options})
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.json();
            })
            .catch(error => {
                console.error('Request failed:', error);
                showToast('Error de conexión. Intenta de nuevo.', 'error');
                throw error;
            });
    }
    </script>

    <!-- Script principal de la aplicación -->
    <?php if (file_exists(__DIR__ . '/../assets/js/main.js')): ?>
        <script src="<?php echo BASE_URL; ?>/assets/js/main.js"></script>
    <?php endif; ?>

    <!-- Scripts específicos de página -->
    <?php if (isset($pageScripts) && is_array($pageScripts)): ?>
        <?php foreach ($pageScripts as $script): ?>
            <script src="<?php echo BASE_URL; ?>/assets/js/<?php echo $script; ?>"></script>
        <?php endforeach; ?>
    <?php endif; ?>

    <!-- Scripts inline de página -->
    <?php if (isset($inlineScript)): ?>
        <script><?php echo $inlineScript; ?></script>
    <?php endif; ?>

</body>
</html>