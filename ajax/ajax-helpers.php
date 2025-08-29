<?php
/**
 * Fonctions utilitaires pour les requêtes AJAX
 */

// Constantes pour les uploads
define('ALLOWED_EXTENSIONS', ['pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png']);
define('ALLOWED_MIME_TYPES', [
    'application/pdf',
    'application/msword',
    'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    'image/jpeg',
    'image/jpg', 
    'image/png'
]);

/**
 * Vérifier si l'utilisateur est connecté
 */
function isLoggedIn() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * Envoyer une réponse JSON
 */
function sendJSONResponse($data, $httpCode = 200) {
    http_response_code($httpCode);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

/**
 * Logger un événement
 */
function logEvent($level, $message, $data = null) {
    $timestamp = date('Y-m-d H:i:s');
    $logMessage = "[$timestamp] [$level] $message";
    
    if ($data !== null) {
        $logMessage .= " - Data: " . json_encode($data);
    }
    
    $logMessage .= "\n";
    
    // Créer le dossier de logs s'il n'existe pas
    $logDir = __DIR__ . '/../logs/';
    if (!is_dir($logDir)) {
        mkdir($logDir, 0755, true);
    }
    
    error_log($logMessage, 3, $logDir . 'app.log');
}

/**
 * Vérifier le token CSRF
 */
function verifyCSRFToken($token) {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * S'assurer que les dossiers nécessaires existent
 */
function ensureDirectoriesExist() {
    $directories = [
        UPLOAD_PATH . 'documents/',
        UPLOAD_PATH . 'thumbnails/',
        TEMP_PATH,
        PROCESSED_PATH
    ];
    
    foreach ($directories as $dir) {
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
    }
}

/**
 * Générer un nom de fichier sécurisé
 */
function generateSecureFileName($originalName, $userId) {
    $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
    $baseName = pathinfo($originalName, PATHINFO_FILENAME);
    
    // Nettoyer le nom de base
    $baseName = preg_replace('/[^a-zA-Z0-9_-]/', '_', $baseName);
    $baseName = substr($baseName, 0, 50); // Limiter la longueur
    
    // Générer un nom unique
    $timestamp = time();
    $random = bin2hex(random_bytes(4));
    
    return "{$userId}_{$timestamp}_{$random}_{$baseName}.{$extension}";
}

/**
 * Formater la taille d'un fichier
 */
function formatFileSize($bytes, $precision = 2) {
    $units = array('B', 'KB', 'MB', 'GB');
    
    for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
        $bytes /= 1024;
    }
    
    return round($bytes, $precision) . ' ' . $units[$i];
}
?>