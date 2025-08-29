<?php
/**
 * Fonctions utilitaires globales - Copisteria Low Cost
 * Collection de fonctions réutilisables dans toute l'application
 */

/**
 * Sécurité et validation
 */
function sanitizeInput($input) {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

function validatePhone($phone) {
    return preg_match('/^[\d\s\+\-\(\)]{9,20}$/', $phone);
}

function generateCSRFToken() {
    return bin2hex(random_bytes(32));
}

function verifyCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Formatage et affichage
 */
function formatPrice($price, $currency = '€') {
    return number_format((float)$price, 2, ',', '.') . $currency;
}

function formatDate($date, $format = 'd/m/Y H:i') {
    return date($format, strtotime($date));
}

function formatFileSize($bytes, $precision = 2) {
    $units = array('B', 'KB', 'MB', 'GB', 'TB');
    
    for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
        $bytes /= 1024;
    }
    
    return round($bytes, $precision) . ' ' . $units[$i];
}

function timeAgo($datetime, $full = false) {
    $now = new DateTime;
    $ago = new DateTime($datetime);
    $diff = $now->diff($ago);

    $diff->w = floor($diff->d / 7);
    $diff->d -= $diff->w * 7;

    $string = array(
        'y' => 'año',
        'm' => 'mes',
        'w' => 'semana',
        'd' => 'día',
        'h' => 'hora',
        'i' => 'minuto',
        's' => 'segundo',
    );

    foreach ($string as $k => &$v) {
        if ($diff->$k) {
            $v = $diff->$k . ' ' . $v . ($diff->$k > 1 ? 's' : '');
        } else {
            unset($string[$k]);
        }
    }

    if (!$full) $string = array_slice($string, 0, 1);
    return $string ? 'hace ' . implode(', ', $string) : 'ahora mismo';
}

/**
 * Gestion des statuts et traductions
 */
function getOrderStatusText($status) {
    $statuses = [
        'DRAFT' => 'Borrador',
        'PENDING' => 'Pendiente',
        'PAYMENT_PENDING' => 'Pago Pendiente',
        'PAID' => 'Pagado',
        'PROCESSING' => 'Procesando',
        'PRINTING' => 'Imprimiendo',
        'READY' => 'Listo',
        'COMPLETED' => 'Completado',
        'CANCELLED' => 'Cancelado',
        'REFUNDED' => 'Reembolsado'
    ];
    
    return $statuses[$status] ?? $status;
}

function getOrderStatusClass($status) {
    $classes = [
        'DRAFT' => 'secondary',
        'PENDING' => 'warning',
        'PAYMENT_PENDING' => 'warning',
        'PAID' => 'info',
        'PROCESSING' => 'primary',
        'PRINTING' => 'primary',
        'READY' => 'success',
        'COMPLETED' => 'success',
        'CANCELLED' => 'danger',
        'REFUNDED' => 'secondary'
    ];
    
    return $classes[$status] ?? 'secondary';
}

function getPaymentStatusText($status) {
    $statuses = [
        'PENDING' => 'Pendiente',
        'PAID' => 'Pagado',
        'PARTIAL' => 'Parcial',
        'FAILED' => 'Fallido',
        'REFUNDED' => 'Reembolsado'
    ];
    
    return $statuses[$status] ?? $status;
}

function getPriorityText($priority) {
    $priorities = [
        'LOW' => 'Baja',
        'NORMAL' => 'Normal',
        'HIGH' => 'Alta',
        'URGENT' => 'Urgente'
    ];
    
    return $priorities[$priority] ?? $priority;
}

/**
 * Notifications et messages
 */
function addFlashMessage($type, $message) {
    if (!isset($_SESSION['flash_messages'])) {
        $_SESSION['flash_messages'] = [];
    }
    
    $_SESSION['flash_messages'][] = [
        'type' => $type,
        'message' => $message,
        'timestamp' => time()
    ];
}

function getFlashMessages($clear = true) {
    $messages = $_SESSION['flash_messages'] ?? [];
    
    if ($clear) {
        unset($_SESSION['flash_messages']);
    }
    
    return $messages;
}

function displayFlashMessages() {
    $messages = getFlashMessages();
    
    foreach ($messages as $message) {
        $alertClass = [
            'success' => 'success',
            'error' => 'danger',
            'warning' => 'warning',
            'info' => 'info'
        ][$message['type']] ?? 'info';
        
        echo '<div class="alert alert-' . $alertClass . ' alert-dismissible fade show" role="alert">';
        echo htmlspecialchars($message['message']);
        echo '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>';
        echo '</div>';
    }
}

/**
 * Calculs et prix
 */
function calculatePrintPrice($paperSize, $paperWeight, $colorMode, $quality, $pages, $copies = 1) {
    global $db;
    
    try {
        $conn = $db->getConnection();
        $stmt = $conn->prepare("
            SELECT price_per_page, volume_discount_threshold, volume_discount_percentage
            FROM pricing_rules 
            WHERE paper_size = ? AND paper_weight = ? AND color_mode = ? 
              AND print_quality = ? AND is_active = 1
              AND (valid_until IS NULL OR valid_until >= CURDATE())
            ORDER BY valid_from DESC LIMIT 1
        ");
        
        $stmt->execute([$paperSize, $paperWeight, $colorMode, $quality]);
        $rule = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$rule) {
            return 0.0;
        }
        
        $basePrice = $rule['price_per_page'] * $pages * $copies;
        
        // Aplicar descuento por volumen
        if ($rule['volume_discount_threshold'] && $pages >= $rule['volume_discount_threshold']) {
            $discount = $basePrice * ($rule['volume_discount_percentage'] / 100);
            $basePrice -= $discount;
        }
        
        return round($basePrice, 4);
        
    } catch (Exception $e) {
        error_log("Error calculating price: " . $e->getMessage());
        return 0.0;
    }
}

function calculateFinishingCost($serviceType, $serviceName, $pages = 1, $paperSize = 'A4') {
    global $db;
    
    try {
        $conn = $db->getConnection();
        $stmt = $conn->prepare("
            SELECT cost, cost_type, min_cost, max_cost
            FROM finishing_costs 
            WHERE service_type = ? AND service_name = ? AND is_active = 1
              AND (min_pages IS NULL OR min_pages <= ?)
              AND (max_pages IS NULL OR max_pages >= ?)
              AND FIND_IN_SET(?, applies_to_paper_sizes)
            ORDER BY display_order LIMIT 1
        ");
        
        $stmt->execute([$serviceType, $serviceName, $pages, $pages, $paperSize]);
        $cost = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$cost) {
            return 0.0;
        }
        
        $totalCost = 0.0;
        
        switch ($cost['cost_type']) {
            case 'FIXED':
                $totalCost = $cost['cost'];
                break;
            case 'PER_PAGE':
                $totalCost = $cost['cost'] * $pages;
                break;
            case 'PER_SHEET':
                $totalCost = $cost['cost'] * ceil($pages / 2); // Asumiendo páginas dobles
                break;
            case 'PERCENTAGE':
                // Necesitaría el precio base para calcular porcentaje
                $totalCost = 0.0;
                break;
            default:
                $totalCost = $cost['cost'];
        }
        
        // Aplicar límites mínimo y máximo
        if ($cost['min_cost'] && $totalCost < $cost['min_cost']) {
            $totalCost = $cost['min_cost'];
        }
        if ($cost['max_cost'] && $totalCost > $cost['max_cost']) {
            $totalCost = $cost['max_cost'];
        }
        
        return round($totalCost, 2);
        
    } catch (Exception $e) {
        error_log("Error calculating finishing cost: " . $e->getMessage());
        return 0.0;
    }
}

/**
 * Gestion des fichiers
 */
function isValidFileType($filename, $allowedTypes = ['pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png']) {
    $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    return in_array($extension, $allowedTypes);
}

function sanitizeFileName($filename) {
    // Supprimer caractères dangereux
    $filename = preg_replace('/[^a-zA-Z0-9._-]/', '_', $filename);
    // Éviter noms de fichiers réservés
    $reserved = ['CON', 'PRN', 'AUX', 'NUL', 'COM1', 'COM2', 'COM3', 'COM4', 'COM5', 'COM6', 'COM7', 'COM8', 'COM9', 'LPT1', 'LPT2', 'LPT3', 'LPT4', 'LPT5', 'LPT6', 'LPT7', 'LPT8', 'LPT9'];
    $name = pathinfo($filename, PATHINFO_FILENAME);
    if (in_array(strtoupper($name), $reserved)) {
        $filename = '_' . $filename;
    }
    return $filename;
}

function generateUniqueFilename($originalName, $directory) {
    $extension = pathinfo($originalName, PATHINFO_EXTENSION);
    $basename = pathinfo($originalName, PATHINFO_FILENAME);
    $basename = sanitizeFileName($basename);
    
    $counter = 0;
    do {
        if ($counter === 0) {
            $filename = $basename . '.' . $extension;
        } else {
            $filename = $basename . '_' . $counter . '.' . $extension;
        }
        $counter++;
    } while (file_exists($directory . '/' . $filename));
    
    return $filename;
}

/**
 * Base de données et requêtes
 */
function getSystemSetting($key, $default = null) {
    global $db;
    
    try {
        $conn = $db->getConnection();
        $stmt = $conn->prepare("SELECT setting_value FROM system_settings WHERE setting_key = ? LIMIT 1");
        $stmt->execute([$key]);
        $value = $stmt->fetchColumn();
        
        return $value !== false ? $value : $default;
        
    } catch (Exception $e) {
        error_log("Error getting system setting: " . $e->getMessage());
        return $default;
    }
}

function updateSystemSetting($key, $value, $userId = null) {
    global $db;
    
    try {
        $conn = $db->getConnection();
        $stmt = $conn->prepare("
            INSERT INTO system_settings (setting_key, setting_value, updated_by, updated_at) 
            VALUES (?, ?, ?, NOW())
            ON DUPLICATE KEY UPDATE 
            setting_value = VALUES(setting_value),
            updated_by = VALUES(updated_by),
            updated_at = NOW()
        ");
        
        return $stmt->execute([$key, $value, $userId]);
        
    } catch (Exception $e) {
        error_log("Error updating system setting: " . $e->getMessage());
        return false;
    }
}

function logAdminAction($adminId, $action, $entityType, $entityId = null, $oldValue = null, $newValue = null, $description = null) {
    global $db;
    
    try {
        $conn = $db->getConnection();
        $stmt = $conn->prepare("
            INSERT INTO admin_logs 
            (admin_id, action, entity_type, entity_id, old_value, new_value, description, ip_address, user_agent, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
        ");
        
        return $stmt->execute([
            $adminId,
            $action,
            $entityType,
            $entityId,
            $oldValue ? json_encode($oldValue) : null,
            $newValue ? json_encode($newValue) : null,
            $description,
            $_SERVER['REMOTE_ADDR'] ?? null,
            $_SERVER['HTTP_USER_AGENT'] ?? null
        ]);
        
    } catch (Exception $e) {
        error_log("Error logging admin action: " . $e->getMessage());
        return false;
    }
}

function getUserStatistics($userId) {
    global $db;
    
    try {
        $conn = $db->getConnection();
        $stmt = $conn->prepare("CALL sp_user_statistics(?)");
        $stmt->execute([$userId]);
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
        
    } catch (Exception $e) {
        error_log("Error getting user statistics: " . $e->getMessage());
        return false;
    }
}

/**
 * Envoi d'emails et notifications
 */
function sendNotification($userId, $type, $title, $message, $actionUrl = null, $actionText = null, $orderId = null) {
    global $db;
    
    try {
        $conn = $db->getConnection();
        $stmt = $conn->prepare("
            INSERT INTO notifications 
            (user_id, order_id, notification_type, title, message, action_url, action_text, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
        ");
        
        return $stmt->execute([
            $userId,
            $orderId,
            $type,
            $title,
            $message,
            $actionUrl,
            $actionText
        ]);
        
    } catch (Exception $e) {
        error_log("Error sending notification: " . $e->getMessage());
        return false;
    }
}

function getUnreadNotificationCount($userId) {
    global $db;
    
    try {
        $conn = $db->getConnection();
        $stmt = $conn->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0");
        $stmt->execute([$userId]);
        
        return (int)$stmt->fetchColumn();
        
    } catch (Exception $e) {
        error_log("Error getting notification count: " . $e->getMessage());
        return 0;
    }
}

/**
 * Génération de contenu
 */
function generateOrderNumber($prefix = 'COP') {
    $year = date('Y');
    $timestamp = time();
    $random = str_pad(mt_rand(1, 999999), 6, '0', STR_PAD_LEFT);
    
    return $prefix . '-' . $year . '-' . $random;
}

function generatePickupCode($length = 6) {
    $characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    $code = '';
    
    for ($i = 0; $i < $length; $i++) {
        $code .= $characters[mt_rand(0, strlen($characters) - 1)];
    }
    
    return $code;
}

function generateVerificationToken() {
    return bin2hex(random_bytes(32));
}

/**
 * Pagination
 */
function paginate($totalItems, $itemsPerPage, $currentPage, $baseUrl, $queryParams = []) {
    $totalPages = ceil($totalItems / $itemsPerPage);
    $currentPage = max(1, min($currentPage, $totalPages));
    
    $pagination = [
        'current_page' => $currentPage,
        'total_pages' => $totalPages,
        'total_items' => $totalItems,
        'items_per_page' => $itemsPerPage,
        'has_prev' => $currentPage > 1,
        'has_next' => $currentPage < $totalPages,
        'prev_page' => $currentPage - 1,
        'next_page' => $currentPage + 1,
        'offset' => ($currentPage - 1) * $itemsPerPage
    ];
    
    // Générer les liens
    $queryString = !empty($queryParams) ? '&' . http_build_query($queryParams) : '';
    
    $pagination['links'] = [];
    
    // Lien précédent
    if ($pagination['has_prev']) {
        $pagination['links']['prev'] = $baseUrl . '?page=' . $pagination['prev_page'] . $queryString;
    }
    
    // Liens des pages
    $start = max(1, $currentPage - 2);
    $end = min($totalPages, $currentPage + 2);
    
    for ($i = $start; $i <= $end; $i++) {
        $pagination['links']['pages'][$i] = [
            'url' => $baseUrl . '?page=' . $i . $queryString,
            'is_current' => $i === $currentPage
        ];
    }
    
    // Lien suivant
    if ($pagination['has_next']) {
        $pagination['links']['next'] = $baseUrl . '?page=' . $pagination['next_page'] . $queryString;
    }
    
    return $pagination;
}

/**
 * Validation de sécurité avancée
 */
function checkRateLimit($identifier, $maxAttempts = 5, $timeWindow = 300) {
    $key = 'rate_limit_' . md5($identifier);
    
    if (!isset($_SESSION[$key])) {
        $_SESSION[$key] = ['count' => 0, 'first_attempt' => time()];
    }
    
    $data = $_SESSION[$key];
    
    // Si la fenêtre de temps est dépassée, réinitialiser
    if (time() - $data['first_attempt'] > $timeWindow) {
        $_SESSION[$key] = ['count' => 1, 'first_attempt' => time()];
        return true;
    }
    
    // Vérifier si la limite est atteinte
    if ($data['count'] >= $maxAttempts) {
        return false;
    }
    
    // Incrémenter le compteur
    $_SESSION[$key]['count']++;
    
    return true;
}

function isValidIPAddress($ip) {
    return filter_var($ip, FILTER_VALIDATE_IP) !== false;
}

function getClientIP() {
    $ipKeys = ['HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'HTTP_CLIENT_IP', 'REMOTE_ADDR'];
    
    foreach ($ipKeys as $key) {
        if (!empty($_SERVER[$key])) {
            $ips = explode(',', $_SERVER[$key]);
            $ip = trim($ips[0]);
            
            if (isValidIPAddress($ip)) {
                return $ip;
            }
        }
    }
    
    return '0.0.0.0';
}

/**
 * Helpers pour templates
 */
function renderStatusBadge($status, $type = 'order') {
    if ($type === 'order') {
        $class = getOrderStatusClass($status);
        $text = getOrderStatusText($status);
    } elseif ($type === 'payment') {
        $class = getPaymentStatusClass($status);
        $text = getPaymentStatusText($status);
    } else {
        $class = 'secondary';
        $text = $status;
    }
    
    return '<span class="badge bg-' . $class . '">' . htmlspecialchars($text) . '</span>';
}

function getPaymentStatusClass($status) {
    $classes = [
        'PENDING' => 'warning',
        'PAID' => 'success',
        'PARTIAL' => 'info',
        'FAILED' => 'danger',
        'REFUNDED' => 'secondary'
    ];
    
    return $classes[$status] ?? 'secondary';
}

function renderPriorityIcon($priority) {
    $icons = [
        'URGENT' => '<i class="fas fa-exclamation-triangle text-danger" title="Urgente"></i>',
        'HIGH' => '<i class="fas fa-exclamation text-warning" title="Prioridad Alta"></i>',
        'NORMAL' => '',
        'LOW' => '<i class="fas fa-minus text-muted" title="Prioridad Baja"></i>'
    ];
    
    return $icons[$priority] ?? '';
}

/**
 * Debugging et logs
 */
function debugLog($message, $data = null, $level = 'INFO') {
    if (defined('DEBUG_MODE') && DEBUG_MODE) {
        $timestamp = date('Y-m-d H:i:s');
        $logMessage = "[$timestamp] [$level] $message";
        
        if ($data !== null) {
            $logMessage .= "\nData: " . print_r($data, true);
        }
        
        $logMessage .= "\n" . str_repeat('-', 80) . "\n";
        
        error_log($logMessage, 3, __DIR__ . '/../logs/debug.log');
    }
}

/**
 * Cache simple
 */
function cacheSet($key, $value, $ttl = 3600) {
    $cacheFile = __DIR__ . '/../cache/' . md5($key) . '.cache';
    $data = [
        'expires' => time() + $ttl,
        'value' => $value
    ];
    
    return file_put_contents($cacheFile, serialize($data)) !== false;
}

function cacheGet($key) {
    $cacheFile = __DIR__ . '/../cache/' . md5($key) . '.cache';
    
    if (!file_exists($cacheFile)) {
        return null;
    }
    
    $data = unserialize(file_get_contents($cacheFile));
    
    if (time() > $data['expires']) {
        unlink($cacheFile);
        return null;
    }
    
    return $data['value'];
}

function cacheDelete($key) {
    $cacheFile = __DIR__ . '/../cache/' . md5($key) . '.cache';
    
    if (file_exists($cacheFile)) {
        return unlink($cacheFile);
    }
    
    return true;
}

/**
 * Utilitaires de développement
 */
function dd($data) {
    echo '<pre>';
    var_dump($data);
    echo '</pre>';
    die();
}

function dump($data) {
    echo '<pre>';
    print_r($data);
    echo '</pre>';
}
?>