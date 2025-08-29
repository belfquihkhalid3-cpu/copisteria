<?php
/**
 * Page d'inscription - Copisteria Low Cost
 * Version autonome avec styles intégrés
 */

require_once '../config/config.php';
require_once '../classes/Database.php';

// Démarrer la session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Rediriger si déjà connecté
if (isset($_SESSION['user_id'])) {
    header('Location: ../user/dashboard.php');
    exit;
}

$errors = [];
$success = false;
$formData = [];

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Vérification CSRF
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== ($_SESSION['csrf_token'] ?? '')) {
        $errors[] = 'Token de seguridad inválido';
    } else {
        // Récupérer et nettoyer les données
        $formData = [
            'first_name' => trim($_POST['first_name'] ?? ''),
            'last_name' => trim($_POST['last_name'] ?? ''),
            'email' => trim(strtolower($_POST['email'] ?? '')),
            'phone' => trim($_POST['phone'] ?? ''),
            'address' => trim($_POST['address'] ?? ''),
            'password' => $_POST['password'] ?? '',
            'password_confirm' => $_POST['password_confirm'] ?? '',
            'terms' => isset($_POST['terms']),
            'notifications' => isset($_POST['notifications'])
        ];

        // Validations
        if (empty($formData['first_name'])) {
            $errors[] = 'El nombre es obligatorio';
        } elseif (strlen($formData['first_name']) < 2) {
            $errors[] = 'El nombre debe tener al menos 2 caracteres';
        }

        if (empty($formData['last_name'])) {
            $errors[] = 'Los apellidos son obligatorios';
        } elseif (strlen($formData['last_name']) < 2) {
            $errors[] = 'Los apellidos deben tener al menos 2 caracteres';
        }

        if (empty($formData['email'])) {
            $errors[] = 'El email es obligatorio';
        } elseif (!filter_var($formData['email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'El formato del email no es válido';
        }

        if (!empty($formData['phone']) && !preg_match('/^[\d\s\+\-\(\)]{9,20}$/', $formData['phone'])) {
            $errors[] = 'El formato del teléfono no es válido';
        }

        if (empty($formData['password'])) {
            $errors[] = 'La contraseña es obligatoria';
        } elseif (strlen($formData['password']) < 8) {
            $errors[] = 'La contraseña debe tener al menos 8 caracteres';
        } elseif (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).+$/', $formData['password'])) {
            $errors[] = 'La contraseña debe contener al menos: 1 minúscula, 1 mayúscula y 1 número';
        }

        if ($formData['password'] !== $formData['password_confirm']) {
            $errors[] = 'Las contraseñas no coinciden';
        }

        if (!$formData['terms']) {
            $errors[] = 'Debe aceptar los términos y condiciones';
        }

        // Si no hay erreurs, procesar registro
        if (empty($errors)) {
            try {
                $db = new Database();
                $conn = $db->getConnection();

                // Verificar si el email ya existe
                $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
                $stmt->execute([$formData['email']]);
                
                if ($stmt->fetch()) {
                    $errors[] = 'Ya existe una cuenta con este email';
                } else {
                    // Crear el usuario
                    $hashedPassword = password_hash($formData['password'], PASSWORD_BCRYPT, ['cost' => 12]);
                    $verificationToken = bin2hex(random_bytes(32));

                    $stmt = $conn->prepare("
                        INSERT INTO users (
                            email, password, first_name, last_name, phone, address,
                            verification_token, notifications_enabled, created_at
                        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())
                    ");
                    
                    $stmt->execute([
                        $formData['email'],
                        $hashedPassword,
                        $formData['first_name'],
                        $formData['last_name'],
                        $formData['phone'] ?: null,
                        $formData['address'] ?: null,
                        $verificationToken,
                        $formData['notifications'] ? 1 : 0
                    ]);

                    $userId = $conn->lastInsertId();

                    // Créer notification de bienvenida
                    $stmt = $conn->prepare("
                        INSERT INTO notifications (
                            user_id, notification_type, title, message, created_at
                        ) VALUES (?, 'WELCOME', ?, ?, NOW())
                    ");
                    
                    $welcomeMessage = "¡Bienvenido/a a Copisteria Low Cost, {$formData['first_name']}! Tu cuenta ha sido creada exitosamente.";
                    $stmt->execute([
                        $userId,
                        'Bienvenido/a a Copisteria Low Cost',
                        $welcomeMessage
                    ]);

                    $success = true;
                    $formData = []; // Nettoyer le formulaire
                }
            } catch (Exception $e) {
                error_log("Error de registro: " . $e->getMessage());
                $errors[] = 'Error interno del servidor. Inténtalo más tarde.';
            }
        }
    }
}

// Générer token CSRF
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro - Copisteria Low Cost</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.2/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .register-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .register-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            overflow: hidden;
            max-width: 1000px;
            width: 100%;
        }
        
        .register-left {
            background: linear-gradient(45deg, #667eea, #764ba2);
            color: white;
            padding: 60px 40px;
            text-align: center;
            position: relative;
            overflow: hidden;
        }
        
        .register-right {
            padding: 40px;
        }
        
        .logo {
            font-size: 2.5rem;
            font-weight: bold;
            margin-bottom: 1rem;
            position: relative;
            z-index: 2;
        }
        
        .logo-subtitle {
            font-size: 1.1rem;
            opacity: 0.9;
            margin-bottom: 2rem;
            position: relative;
            z-index: 2;
        }
        
        .form-control {
            border: 2px solid #e9ecef;
            border-radius: 10px;
            padding: 12px 15px;
            transition: all 0.3s ease;
        }
        
        .form-control:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }
        
        .btn-register {
            background: linear-gradient(45deg, #667eea, #764ba2);
            border: none;
            border-radius: 10px;
            padding: 12px 30px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            transition: all 0.3s ease;
        }
        
        .btn-register:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(102, 126, 234, 0.3);
        }
        
        .alert {
            border-radius: 10px;
            border: none;
            margin-bottom: 1.5rem;
        }
        
        .features-list {
            list-style: none;
            padding: 0;
            margin: 2rem 0;
            position: relative;
            z-index: 2;
        }
        
        .features-list li {
            margin: 1rem 0;
            display: flex;
            align-items: center;
        }
        
        .features-list i {
            margin-right: 1rem;
            font-size: 1.2rem;
        }
    </style>
</head>
<body>
    <div class="register-container">
        <div class="register-card">
            <div class="row g-0">
                <!-- Left Side - Branding -->
                <div class="col-lg-5 register-left">
                    <div class="logo">
                        <i class="fas fa-print"></i>
                        Copisteria
                    </div>
                    <div class="logo-subtitle">Únete a nuestro servicio de impresión</div>
                    
                    <ul class="features-list">
                        <li><i class="fas fa-check-circle"></i> Subida y impresión rápidas</li>
                        <li><i class="fas fa-palette"></i> Impresión color y B/N</li>
                        <li><i class="fas fa-cog"></i> Configuración personalizada</li>
                        <li><i class="fas fa-book"></i> Encuadernación y acabados</li>
                        <li><i class="fas fa-clock"></i> Seguimiento en tiempo real</li>
                        <li><i class="fas fa-euro-sign"></i> Precios competitivos</li>
                    </ul>
                </div>
                
                <!-- Right Side - Register Form -->
                <div class="col-lg-7 register-right">
                    <div class="text-center mb-4">
                        <h2 class="display-6 fw-bold text-primary mb-2">Crear Cuenta</h2>
                        <p class="text-muted">Únete a Copisteria Low Cost y disfruta de nuestros servicios</p>
                    </div>

                    <?php if ($success): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <i class="fas fa-check-circle me-2"></i>
                            <strong>¡Cuenta creada exitosamente!</strong> 
                            <br>Ya puedes iniciar sesión con tu cuenta.
                            <br><a href="login.php" class="alert-link">Iniciar sesión ahora</a>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            <strong>Error en el registro:</strong>
                            <ul class="mb-0 mt-2">
                                <?php foreach ($errors as $error): ?>
                                    <li><?php echo htmlspecialchars($error); ?></li>
                                <?php endforeach; ?>
                            </ul>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <?php if (!$success): ?>
                    <form method="POST" id="registerForm" novalidate>
                        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                        
                        <!-- Información personal -->
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="first_name" class="form-label">Nombre <span class="text-danger">*</span></label>
                                <input type="text" 
                                       class="form-control" 
                                       id="first_name" 
                                       name="first_name" 
                                       value="<?php echo htmlspecialchars($formData['first_name'] ?? ''); ?>"
                                       required>
                            </div>
                            <div class="col-md-6">
                                <label for="last_name" class="form-label">Apellidos <span class="text-danger">*</span></label>
                                <input type="text" 
                                       class="form-control" 
                                       id="last_name" 
                                       name="last_name" 
                                       value="<?php echo htmlspecialchars($formData['last_name'] ?? ''); ?>"
                                       required>
                            </div>
                        </div>

                        <!-- Email -->
                        <div class="mb-3">
                            <label for="email" class="form-label">Email <span class="text-danger">*</span></label>
                            <input type="email" 
                                   class="form-control" 
                                   id="email" 
                                   name="email" 
                                   value="<?php echo htmlspecialchars($formData['email'] ?? ''); ?>"
                                   required>
                            <div class="form-text">Usaremos este email para notificaciones importantes</div>
                        </div>

                        <!-- Teléfono -->
                        <div class="mb-3">
                            <label for="phone" class="form-label">Teléfono</label>
                            <input type="tel" 
                                   class="form-control" 
                                   id="phone" 
                                   name="phone" 
                                   value="<?php echo htmlspecialchars($formData['phone'] ?? ''); ?>"
                                   placeholder="+34 666 777 888">
                            <div class="form-text">Opcional - Para notificaciones por SMS</div>
                        </div>

                        <!-- Dirección -->
                        <div class="mb-3">
                            <label for="address" class="form-label">Dirección</label>
                            <textarea class="form-control" 
                                      id="address" 
                                      name="address" 
                                      rows="2" 
                                      placeholder="Calle, número, código postal, ciudad"><?php echo htmlspecialchars($formData['address'] ?? ''); ?></textarea>
                            <div class="form-text">Opcional - Para entregas a domicilio</div>
                        </div>

                        <!-- Contraseñas -->
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="password" class="form-label">Contraseña <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <input type="password" 
                                           class="form-control" 
                                           id="password" 
                                           name="password" 
                                           required>
                                    <button class="btn btn-outline-secondary" type="button" id="togglePassword">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                                <div class="form-text">Mínimo 8 caracteres, incluye mayúscula, minúscula y número</div>
                            </div>
                            <div class="col-md-6">
                                <label for="password_confirm" class="form-label">Confirmar Contraseña <span class="text-danger">*</span></label>
                                <input type="password" 
                                       class="form-control" 
                                       id="password_confirm" 
                                       name="password_confirm" 
                                       required>
                            </div>
                        </div>

                        <!-- Opciones -->
                        <div class="mb-3">
                            <div class="form-check">
                                <input class="form-check-input" 
                                       type="checkbox" 
                                       id="notifications" 
                                       name="notifications" 
                                       <?php echo ($formData['notifications'] ?? true) ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="notifications">
                                    Recibir notificaciones por email sobre mis pedidos
                                </label>
                            </div>
                        </div>

                        <!-- Términos y condiciones -->
                        <div class="mb-4">
                            <div class="form-check">
                                <input class="form-check-input" 
                                       type="checkbox" 
                                       id="terms" 
                                       name="terms" 
                                       required>
                                <label class="form-check-label" for="terms">
                                    Acepto los términos y condiciones y la política de privacidad <span class="text-danger">*</span>
                                </label>
                            </div>
                        </div>

                        <!-- Botón de registro -->
                        <button type="submit" class="btn btn-register btn-primary w-100 mb-3" id="registerBtn">
                            <i class="fas fa-user-plus me-2"></i>
                            Crear Cuenta
                        </button>
                    </form>
                    <?php endif; ?>

                    <!-- Link de login -->
                    <div class="text-center">
                        <p class="mb-0">¿Ya tienes cuenta? <a href="login.php" class="text-decoration-none">Iniciar sesión</a></p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.2/js/bootstrap.bundle.min.js"></script>
    
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Toggle password visibility
        const togglePassword = document.getElementById('togglePassword');
        const passwordField = document.getElementById('password');
        
        if (togglePassword) {
            togglePassword.addEventListener('click', function() {
                const type = passwordField.getAttribute('type') === 'password' ? 'text' : 'password';
                passwordField.setAttribute('type', type);
                
                const icon = this.querySelector('i');
                icon.classList.toggle('fa-eye');
                icon.classList.toggle('fa-eye-slash');
            });
        }
        
        // Validación en tiempo real
        const form = document.getElementById('registerForm');
        const submitBtn = document.getElementById('registerBtn');
        
        if (form) {
            // Validar contraseñas coinciden
            const password = document.getElementById('password');
            const passwordConfirm = document.getElementById('password_confirm');
            
            function checkPasswordsMatch() {
                if (password.value && passwordConfirm.value) {
                    if (password.value !== passwordConfirm.value) {
                        passwordConfirm.setCustomValidity('Las contraseñas no coinciden');
                        passwordConfirm.classList.add('is-invalid');
                    } else {
                        passwordConfirm.setCustomValidity('');
                        passwordConfirm.classList.remove('is-invalid');
                    }
                }
            }
            
            password.addEventListener('input', checkPasswordsMatch);
            passwordConfirm.addEventListener('input', checkPasswordsMatch);
            
            // Validar fortaleza de contraseña
            password.addEventListener('input', function() {
                const value = this.value;
                const isStrong = value.length >= 8 && 
                               /[a-z]/.test(value) && 
                               /[A-Z]/.test(value) && 
                               /\d/.test(value);
                
                if (value && !isStrong) {
                    this.classList.add('is-invalid');
                } else {
                    this.classList.remove('is-invalid');
                }
            });
            
            // Prevenir envío duplicado
            form.addEventListener('submit', function() {
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Creando cuenta...';
                
                setTimeout(function() {
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = '<i class="fas fa-user-plus me-2"></i>Crear Cuenta';
                }, 3000);
            });
        }
    });
    </script>
</body>
</html>