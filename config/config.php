<?php
/**
 * Configuration principale - Copisteria Low Cost
 * Fichier de configuration global de l'application
 */

// Configuration des sessions UNIQUEMENT si aucune session n'est active
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_only_cookies', 1);
    ini_set('session.cookie_secure', 0); // Mettre à 1 en HTTPS
    ini_set('session.cookie_samesite', 'Lax');
    ini_set('session.gc_maxlifetime', 7200); // 2 heures
}

// Configuration des erreurs
error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED);
ini_set('display_errors', 1);
ini_set('log_errors', 1);

// Fuseau horaire
date_default_timezone_set('Europe/Madrid');

// Constantes de base
define('BASE_URL', 'http://localhost/copisteria');
define('UPLOAD_PATH', __DIR__ . '/../assets/uploads/');
define('TEMP_PATH', __DIR__ . '/../assets/uploads/temp/');
define('PROCESSED_PATH', __DIR__ . '/../assets/uploads/processed/');

// Constantes de sécurité
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOGIN_LOCKOUT_TIME', 900); // 15 minutes
define('SESSION_TIMEOUT', 7200); // 2 heures
define('CSRF_TOKEN_EXPIRE', 3600); // 1 heure

// Constantes de fichiers
define('MAX_FILE_SIZE', 52428800); // 50MB
define('ALLOWED_FILE_TYPES', ['pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png']);
define('MAX_FILES_PER_ORDER', 20);

// Configuration de débogage
define('DEBUG_MODE', true);
define('LOG_PATH', __DIR__ . '/../logs/');

// Créer les dossiers nécessaires s'ils n'existent pas
$directories = [
    UPLOAD_PATH,
    TEMP_PATH, 
    PROCESSED_PATH,
    UPLOAD_PATH . 'documents/',
    LOG_PATH
];

foreach ($directories as $dir) {
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
}

// Headers de sécurité
if (!headers_sent()) {
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: DENY');
    header('X-XSS-Protection: 1; mode=block');
    header('Referrer-Policy: strict-origin-when-cross-origin');
}

// Auto-loader simple pour les classes
spl_autoload_register(function ($className) {
    $classFile = __DIR__ . '/../classes/' . $className . '.php';
    if (file_exists($classFile)) {
        require_once $classFile;
    }
});

// Fonction globale pour inclure les fichiers communs
function includeCommonFiles() {
    static $included = false;
    
    if (!$included) {
        if (file_exists(__DIR__ . '/../includes/functions.php')) {
            require_once __DIR__ . '/../includes/functions.php';
        }
        $included = true;
    }
}

// Inclure automatiquement les fonctions communes
includeCommonFiles();

// Initialiser le token CSRF si une session est active
if (session_status() === PHP_SESSION_ACTIVE && !isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
?>