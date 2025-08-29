<?php
/**
 * Traitement des commandes AJAX - Copisteria Low Cost
 */

require_once '../config/config.php';
require_once '../includes/ajax-helpers.php';
require_once '../classes/Database.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Headers pour AJAX
header('Content-Type: application/json; charset=utf-8');

// Vérifier si l'utilisateur est connecté
if (!isLoggedIn()) {
    sendJSONResponse(['success' => false, 'error' => 'No autenticado'], 401);
}

// Vérifier la méthode HTTP
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendJSONResponse(['success' => false, 'error' => 'Método no autorizado'], 405);
}

// Récupérer les données JSON
$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    sendJSONResponse(['success' => false, 'error' => 'Datos inválidos']);
}

$user_id = $_SESSION['user_id'];

try {
    $database = new Database();
    $pdo = $database->getConnection();
    
    
    // Traiter chaque fichier
    $total_price = 0;
    $total_pages = 0;
    
    foreach ($files as $file_data) {
        $item_result = createOrderItem($pdo, $order_id, $file_data, $config);
        if ($item_result['success']) {
            $total_price += $item_result['item_total'];
            $total_pages += $item_result['pages'];
        } else {
            throw new Exception('Error procesando archivo: ' . $item_result['error']);
        }
    }
    
    // Mettre à jour les totaux de la commande (sera fait automatiquement par le trigger)
    // Mais on peut aussi le faire explicitement pour être sûr
    $stmt = $pdo->prepare("
        UPDATE orders 
        SET total_price = ?, total_pages = ?, total_files = ?
        WHERE id = ?
    ");
    $stmt->execute([$total_price, $total_pages, count($files), $order_id]);
    
    // Récupérer les détails complets de la commande
    $stmt = $pdo->prepare("
        SELECT o.*, u.first_name, u.last_name, u.email
        FROM orders o 
        JOIN users u ON o.user_id = u.id 
        WHERE o.id = ?
    ");
    $stmt->execute([$order_id]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Valider la transaction
    $pdo->commit();
    
    // Créer une notification
    createOrderNotification($pdo, $order_id, $user_id, 'ORDER_RECEIVED');
    
    // Logger la création de commande
    logEvent('INFO', 'Nueva orden creada', [
        'user_id' => $user_id,
        'order_id' => $order_id,
        'order_number' => $order['order_number'],
        'total_price' => $total_price,
        'files_count' => count($files)
    ]);
    
    // Préparer la réponse
    $response = [
        'success' => true,
        'order' => [
            'id' => $order_id,
            'order_number' => $order['order_number'],
            'status' => $order['status'],
            'total_price' => $total_price,
            'total_pages' => $total_pages,
            'total_files' => count($files),
            'pickup_code' => $order['pickup_code'],
            'estimated_completion' => $order['estimated_completion'],
            'created_at' => $order['created_at']
        ],
        'message' => '¡Pedido creado con éxito!',
        'redirect_url' => '../user/order-detail.php?id=' . $order_id
    ];
    
    sendJSONResponse($response);
    
} catch (Exception $e) {
    // Annuler la transaction en cas d'erreur
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    error_log("Error procesando pedido: " . $e->getMessage());
    sendJSONResponse([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}

/**
 * Créer un élément de commande
 */
function createOrderItem($pdo, $order_id, $file_data, $config) {
    try {
        // Extraire les données du fichier
        $file_name = $file_data['secure_name'] ?? '';
        $original_name = $file_data['original_name'] ?? '';
        $file_path = $file_data['file_path'] ?? '';
        $file_size = intval($file_data['size'] ?? 0);
        $mime_type = $file_data['type'] ?? '';
        $pages = intval($file_data['pages'] ?? 1);
        
        // Configuration d'impression
        $paper_size = strtoupper($config['size'] ?? 'A4');
        $paper_weight = $config['weight'] ?? '80g';
        $color_mode = strtoupper($config['color'] ?? 'BW');
        $print_quality = strtoupper($config['quality'] ?? 'NORMAL');
        $orientation = strtoupper($config['orientation'] ?? 'PORTRAIT');
        $sides = strtoupper($config['sides'] ?? 'SINGLE');
        $binding = strtoupper($config['finishing'] ?? 'NONE');
        $copies = intval($config['copies'] ?? 1);
        
        // Couleurs pour la reliure spirale
        $spiral_color = $config['spiralColor'] ?? 'black';
        $cover_front_color = $config['coverFrontColor'] ?? 'transparent';
        $cover_back_color = $config['coverBackColor'] ?? 'transparent';
        
        // Mappage des valeurs
        if ($binding === 'BOUND') $binding = 'SPIRAL';
        if ($binding === 'STAPLED') $binding = 'STAPLE';
        
        // Calculer les prix via procédure stockée
        try {
            $stmt = $pdo->prepare("CALL sp_calculate_item_price(?, ?, ?, ?, ?, ?, ?, ?, @total_price)");
            $stmt->execute([
                $paper_size, $paper_weight, $color_mode, $print_quality,
                $pages, $copies, $binding, 'NONE'
            ]);
            
            // Récupérer le prix calculé
            $result = $pdo->query("SELECT @total_price as total_price")->fetch();
            $item_total = floatval($result['total_price'] ?? 0);
        } catch (Exception $e) {
            $item_total = 0;
        }
        
        // Si la procédure stockée n'a pas fonctionné, calcul manuel
        if ($item_total == 0) {
            $unit_price = getDefaultPrice($paper_size, $color_mode, $paper_weight);
            $binding_cost = getDefaultFinishingCost($binding);
            $item_total = ($unit_price * $pages + $binding_cost) * $copies;
        } else {
            $unit_price = $item_total / ($pages * $copies);
            $binding_cost = $binding !== 'NONE' ? getDefaultFinishingCost($binding) : 0;
        }
        
        // Générer hash du fichier pour déduplication
        $file_hash = '';
        if ($file_path && file_exists('../' . $file_path)) {
            $file_hash = md5_file('../' . $file_path);
        }
        
        // Insérer l'élément de commande
        $stmt = $pdo->prepare("
            INSERT INTO order_items (
                order_id, file_name, file_original_name, file_path, file_size, file_hash,
                mime_type, page_count, paper_size, paper_weight, color_mode, print_quality,
                orientation, sides, binding, spiral_color, cover_front_color, cover_back_color,
                copies, unit_price, binding_cost, item_total, processing_status
            ) VALUES (
                ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'PENDING'
            )
        ");
        
        $stmt->execute([
            $order_id, $file_name, $original_name, $file_path, $file_size, $file_hash,
            $mime_type, $pages, $paper_size, $paper_weight, $color_mode, $print_quality,
            $orientation, $sides, $binding, $spiral_color, $cover_front_color, $cover_back_color,
            $copies, $unit_price, $binding_cost, $item_total
        ]);
        
        return [
            'success' => true,
            'item_total' => $item_total,
            'pages' => $pages * $copies
        ];
        
    } catch (Exception $e) {
        return [
            'success' => false,
            'error' => $e->getMessage()
        ];
    }
}

/**
 * Créer une notification de commande
 */
function createOrderNotification($pdo, $order_id, $user_id, $type) {
    try {
        // Récupérer le numéro de commande
        $stmt = $pdo->prepare("SELECT order_number FROM orders WHERE id = ?");
        $stmt->execute([$order_id]);
        $order = $stmt->fetch();
        
        if (!$order) return;
        
        $order_number = $order['order_number'];
        
        // Définir le contenu selon le type
        switch ($type) {
            case 'ORDER_RECEIVED':
                $title = 'Pedido recibido';
                $message = "Su pedido {$order_number} ha sido recibido y está siendo procesado.";
                $action_text = 'Ver pedido';
                break;
            default:
                return;
        }
        
        // Créer la notification
        $stmt = $pdo->prepare("
            INSERT INTO notifications (
                user_id, order_id, notification_type, title, message, 
                action_url, action_text, created_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
        ");
        
        $stmt->execute([
            $user_id, $order_id, $type, $title, $message,
            "/user/order-detail.php?id={$order_id}", $action_text
        ]);
        
    } catch (Exception $e) {
        error_log("Error creando notificación: " . $e->getMessage());
    }
}

/**
 * Obtenir le prix par défaut selon la configuration
 */
function getDefaultPrice($paper_size, $color_mode, $paper_weight) {
    $base_prices = [
        'A4' => ['BW' => 0.05, 'COLOR' => 0.15],
        'A3' => ['BW' => 0.10, 'COLOR' => 0.25],
        'A5' => ['BW' => 0.03, 'COLOR' => 0.12],
        'LETTER' => ['BW' => 0.05, 'COLOR' => 0.15],
        'LEGAL' => ['BW' => 0.06, 'COLOR' => 0.18]
    ];
    
    $base_price = $base_prices[$paper_size][$color_mode] ?? 0.05;
    
    // Ajustement pour l'épaisseur
    switch ($paper_weight) {
        case '90g':
            $base_price *= 1.2;
            break;
        case '160g':
            $base_price *= 1.5;
            break;
        case '200g':
        case '250g':
            $base_price *= 2.0;
            break;
        case '280g':
            $base_price *= 2.5;
            break;
    }
    
    return $base_price;
}

/**
 * Obtenir le coût de finition par défaut
 */
function getDefaultFinishingCost($binding) {
    $costs = [
        'SPIRAL' => 2.50,
        'STAPLE' => 0.50,
        'THERMAL' => 3.50,
        'HARDCOVER' => 8.00,
        'PERFECT' => 5.00
    ];
    
    return $costs[$binding] ?? 0.00;
}
?>