<?php
/**
 * Page de connexion - Copisteria Low Cost
 */

require_once '../config/config.php';
require_once '../classes/Database.php';

// Démarrer la session sécurisée
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Vérification du timeout de session
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > SESSION_TIMEOUT)) {
    session_unset();
    session_destroy();
    header('Location: login.php?msg=session_expired');
    exit;
}

// Régénérer l'ID de session périodiquement
if (!isset($_SESSION['last_regeneration'])) {
    $_SESSION['last_regeneration'] = time();
} elseif (time() - $_SESSION['last_regeneration'] > 300) {
    session_regenerate_id(true);
    $_SESSION['last_regeneration'] = time();
}

$_SESSION['last_activity'] = time();

// Si déjà connecté, rediriger
if (isset($_SESSION['user_id'])) {
    $redirect = isset($_SESSION['is_admin']) && $_SESSION['is_admin'] ? '../admin/index.php' : '../user/dashboard.php';
    header('Location: ' . $redirect);
    exit;
}

$error_message = '';
$success_message = '';

// Traitement du formulaire de connexion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    // Vérification CSRF
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $error_message = 'Token de sécurité invalide. Veuillez réessayer.';
    } else {
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        
        // Validation basique
        if (empty($email) || empty($password)) {
            $error_message = 'Veuillez remplir tous les champs.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error_message = 'Adresse email invalide.';
        } else {
            try {
                // Connexion à la base de données
                $database = new Database();
                $pdo = $database->getConnection();
                
                // Rechercher l'utilisateur
                $stmt = $pdo->prepare("
                    SELECT id, email, password, first_name, last_name, is_active, is_admin, is_verified, login_attempts, locked_until
                    FROM users 
                    WHERE email = ?
                ");
                $stmt->execute([$email]);
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($user) {
                    // Vérifier si le compte est verrouillé
                    if ($user['locked_until'] && $user['locked_until'] > date('Y-m-d H:i:s')) {
                        $error_message = 'Compte temporairement verrouillé. Réessayez plus tard.';
                    } elseif (!$user['is_active']) {
                        $error_message = 'Compte désactivé. Contactez l\'administrateur.';
                    } elseif (password_verify($password, $user['password'])) {
                        // Connexion réussie
                        
                        // Réinitialiser les tentatives de connexion
                        $stmt = $pdo->prepare("
                            UPDATE users 
                            SET login_attempts = 0, locked_until = NULL, last_login_at = NOW() 
                            WHERE id = ?
                        ");
                        $stmt->execute([$user['id']]);
                        
                        // Régénérer l'ID de session pour sécurité
                        session_regenerate_id(true);
                        
                        // Créer la session
                        $_SESSION['user_id'] = $user['id'];
                        $_SESSION['user_email'] = $user['email'];
                        $_SESSION['user_name'] = $user['first_name'] . ' ' . $user['last_name'];
                        $_SESSION['is_admin'] = (bool)$user['is_admin'];
                        $_SESSION['login_time'] = time();
                        $_SESSION['last_activity'] = time();
                        
                        // Générer nouveau token CSRF
                        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
                        
                        // Générer token de session
                        $session_token = bin2hex(random_bytes(32));
                        $_SESSION['session_token'] = $session_token;
                        
                        // Enregistrer la session en base
                        $stmt = $pdo->prepare("
                            INSERT INTO user_sessions (user_id, session_token, ip_address, user_agent, expires_at) 
                            VALUES (?, ?, ?, ?, DATE_ADD(NOW(), INTERVAL 2 HOUR))
                        ");
                        $stmt->execute([
                            $user['id'],
                            $session_token,
                            $_SERVER['REMOTE_ADDR'] ?? '',
                            $_SERVER['HTTP_USER_AGENT'] ?? ''
                        ]);
                        
                        // Redirection après connexion
                        if ($user['is_admin']) {
                            $redirect = '../admin/index.php';
                        } else {
                            $redirect = $_SESSION['redirect_after_login'] ?? '../user/dashboard.php';
                        }
                        unset($_SESSION['redirect_after_login']);
                        
                        header('Location: ' . $redirect);
                        exit;
                        
                    } else {
                        // Mot de passe incorrect - Incrémenter les tentatives
                        $new_attempts = $user['login_attempts'] + 1;
                        $locked_until = null;
                        
                        // Verrouiller après 5 tentatives
                        if ($new_attempts >= 5) {
                            $locked_until = date('Y-m-d H:i:s', strtotime('+15 minutes'));
                            $error_message = 'Trop de tentatives. Compte verrouillé pour 15 minutes.';
                        } else {
                            $error_message = 'Email ou mot de passe incorrect. (' . (5 - $new_attempts) . ' tentatives restantes)';
                        }
                        
                        $stmt = $pdo->prepare("
                            UPDATE users 
                            SET login_attempts = ?, locked_until = ? 
                            WHERE id = ?
                        ");
                        $stmt->execute([$new_attempts, $locked_until, $user['id']]);
                    }
                } else {
                    $error_message = 'Email ou mot de passe incorrect.';
                }
                
            } catch (Exception $e) {
                error_log("Erreur de connexion: " . $e->getMessage());
                $error_message = 'Erreur de connexion. Veuillez réessayer.';
            }
        }
    }
}

// Messages de statut depuis l'URL
if (isset($_GET['msg'])) {
    switch ($_GET['msg']) {
        case 'logout':
            $success_message = 'Vous avez été déconnecté avec succès.';
            break;
        case 'registered':
            $success_message = 'Inscription réussie ! Vous pouvez maintenant vous connecter.';
            break;
        case 'session_expired':
            $error_message = 'Votre session a expiré. Veuillez vous reconnecter.';
            break;
    }
}

// Générer token CSRF si pas présent
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$pageTitle = 'Connexion - Copisteria Low Cost';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle); ?></title>
    
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
        
        .login-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .login-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            overflow: hidden;
            max-width: 900px;
            width: 100%;
        }
        
        .login-left {
            background: linear-gradient(45deg, #667eea, #764ba2);
            color: white;
            padding: 60px 40px;
            text-align: center;
            position: relative;
            overflow: hidden;
        }
        
        .login-left::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="grain" width="100" height="100" patternUnits="userSpaceOnUse"><circle cx="25" cy="25" r="1" fill="white" opacity="0.1"/><circle cx="75" cy="75" r="1" fill="white" opacity="0.1"/></pattern></defs><rect width="100" height="100" fill="url(%23grain)"/></svg>');
            animation: float 20s infinite linear;
        }
        
        @keyframes float {
            0% { transform: translateX(-100px) translateY(-100px) rotate(0deg); }
            100% { transform: translateX(-100px) translateY(-100px) rotate(360deg); }
        }
        
        .login-right {
            padding: 60px 40px;
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
        
        .form-floating {
            margin-bottom: 1.5rem;
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
        
        .btn-login {
            background: linear-gradient(45deg, #667eea, #764ba2);
            border: none;
            border-radius: 10px;
            padding: 12px 30px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            transition: all 0.3s ease;
        }
        
        .btn-login:hover {
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
        
        .divider {
            text-align: center;
            margin: 2rem 0;
            position: relative;
        }
        
        .divider::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 0;
            right: 0;
            height: 1px;
            background: #e9ecef;
        }
        
        .divider span {
            background: white;
            padding: 0 1rem;
            color: #6c757d;
            font-size: 0.9rem;
        }
        
        .quick-login {
            text-align: center;
            margin-top: 1.5rem;
        }
        
        .demo-accounts {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 1rem;
            margin-top: 1rem;
            font-size: 0.85rem;
        }
        
        @media (max-width: 768px) {
            .login-left {
                padding: 30px 20px;
            }
            .login-right {
                padding: 30px 20px;
            }
            .logo {
                font-size: 2rem;
            }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-card">
            <div class="row g-0">
                <!-- Left Side - Branding -->
                <div class="col-md-6 login-left">
                    <div class="logo">
                        <i class="fas fa-print"></i>
                        Copisteria
                    </div>
                    <div class="logo-subtitle">Servicio de impresión profesional</div>
                    
                    <ul class="features-list">
                        <li><i class="fas fa-check-circle"></i> Subida e impresión rápidas</li>
                        <li><i class="fas fa-palette"></i> Impresión color y B/N</li>
                        <li><i class="fas fa-cog"></i> Configuración personalizada</li>
                        <li><i class="fas fa-book"></i> Encuadernación y acabados</li>
                        <li><i class="fas fa-clock"></i> Seguimiento en tiempo real</li>
                        <li><i class="fas fa-euro-sign"></i> Precios competitivos</li>
                    </ul>
                </div>
                
                <!-- Right Side - Login Form -->
                <div class="col-md-6 login-right">
                    <h2 class="mb-4 text-center">Iniciar Sesión</h2>
                    
                    <?php if ($error_message): ?>
                        <div class="alert alert-danger" role="alert">
                            <i class="fas fa-exclamation-circle me-2"></i>
                            <?php echo htmlspecialchars($error_message); ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($success_message): ?>
                        <div class="alert alert-success" role="alert">
                            <i class="fas fa-check-circle me-2"></i>
                            <?php echo htmlspecialchars($success_message); ?>
                        </div>
                    <?php endif; ?>
                    
                    <form method="POST" action="">
                        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                        
                        <div class="form-floating">
                            <input type="email" class="form-control" id="email" name="email" 
                                   placeholder="usuario@ejemplo.com" required 
                                   value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                            <label for="email"><i class="fas fa-envelope me-2"></i>Email</label>
                        </div>
                        
                        <div class="form-floating">
                            <input type="password" class="form-control" id="password" name="password" 
                                   placeholder="Contraseña" required>
                            <label for="password"><i class="fas fa-lock me-2"></i>Contraseña</label>
                        </div>
                        
                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" id="remember" name="remember">
                            <label class="form-check-label" for="remember">
                                Recordarme
                            </label>
                        </div>
                        
                        <button type="submit" name="login" class="btn btn-login btn-primary w-100">
                            <i class="fas fa-sign-in-alt me-2"></i>
                            Iniciar Sesión
                        </button>
                    </form>
                    
                    <div class="divider">
                        <span>o</span>
                    </div>
                    
                    <div class="text-center">
                        <p class="mb-2">¿No tienes cuenta?</p>
                        <a href="register.php" class="btn btn-outline-primary">
                            <i class="fas fa-user-plus me-2"></i>
                            Crear cuenta
                        </a>
                    </div>
                    
                    <div class="text-center mt-3">
                        <a href="forgot-password.php" class="text-muted small">
                            <i class="fas fa-question-circle me-1"></i>
                            ¿Olvidaste tu contraseña?
                        </a>
                    </div>
                    
                    <!-- Comptes de démonstration -->
                    <div class="demo-accounts">
                        <strong><i class="fas fa-info-circle me-2"></i>Cuentas de prueba:</strong><br>
                        <code>admin@copisteria.com</code> / <code>admin123</code><br>
                        <code>test@copisteria.com</code> / <code>test123</code>
                    </div>
                    
                    <div class="quick-login">
                        <button type="button" class="btn btn-sm btn-outline-success me-2" onclick="quickLogin('admin')">
                            <i class="fas fa-user-shield me-1"></i>Admin
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-info" onclick="quickLogin('test')">
                            <i class="fas fa-user me-1"></i>Usuario
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Bootstrap JS -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.2/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Fonction de connexion rapide pour les tests
        function quickLogin(type) {
            if (type === 'admin') {
                document.getElementById('email').value = 'admin@copisteria.com';
                document.getElementById('password').value = 'admin123';
            } else if (type === 'test') {
                document.getElementById('email').value = 'test@copisteria.com';
                document.getElementById('password').value = 'test123';
            }
        }
        
        // Animation des alertes
        document.addEventListener('DOMContentLoaded', function() {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                alert.style.opacity = '0';
                alert.style.transform = 'translateY(-20px)';
                setTimeout(() => {
                    alert.style.transition = 'all 0.5s ease';
                    alert.style.opacity = '1';
                    alert.style.transform = 'translateY(0)';
                }, 100);
            });
        });
        
        // Amélioration UX - Focus automatique
        window.addEventListener('load', function() {
            const emailInput = document.getElementById('email');
            if (emailInput.value === '') {
                emailInput.focus();
            } else {
                document.getElementById('password').focus();
            }
        });
    </script>
</body>
</html>