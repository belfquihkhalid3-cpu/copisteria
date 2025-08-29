<?php
/**
 * Déconnexion sécurisée - Copisteria Low Cost
 */

require_once '../config/config.php';

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$session_token = $_SESSION['session_token'] ?? null;

try {
    // Logger la déconnexion
    logEvent('INFO', 'Déconnexion utilisateur', [
        'user_id' => $user_id,
        'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
    ]);
    
    // Invalider la session en base de données
    if ($session_token) {
        $database = new Database();
        $pdo = $database->getConnection();
        
        $stmt = $pdo->prepare("UPDATE user_sessions SET is_active = 0 WHERE session_token = ?");
        $stmt->execute([$session_token]);
    }
    
} catch (Exception $e) {
    error_log("Erreur lors de la déconnexion: " . $e->getMessage());
}

// Détruire la session PHP
session_unset();
session_destroy();

// Supprimer le cookie de session
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Redirection avec message
header('Location: login.php?msg=logout');
exit;
?>