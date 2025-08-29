<?php
/**
 * Calcul de prix AJAX - Copisteria Low Cost
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

try {
    $database = new Database();
    $pdo = $database->getConnection();
    
    // Extraire la configuration
    $config = $input['config'] ?? [];
    $files = $input['files'] ?? [];
    
    // Valeurs par défaut
    $paper_size = $config['size'] ?? 'A4';
    $paper_weight = $config['weight'] ?? '80g';
    $color_mode = $config['color'] ?? 'BW';
    $print_quality = $config['quality'] ?? 'NORMAL';
    $binding = $config['finishing'] ?? 'NONE';
    $copies = intval($config['copies'] ?? 1);
    
    // Normaliser les valeurs
    $paper_size = strtoupper($paper_size);
    $color_mode = strtoupper($color_mode);
    $print_quality = strtoupper($print_quality);
    $binding = strtoupper($binding);
    
    // Mappage des valeurs
    if ($binding === 'BOUND') $binding = 'SPIRAL';
    if ($binding === 'STAPLED') $binding = 'STAPLE';
    
    // Calculer le nombre total de pages
    $total_pages = 0;
    foreach ($files as $file) {
        $pages = intval($file['pages'] ?? 1);
        $total_pages += $pages;
    }
    
    if ($total_pages === 0) {
        $total_pages = 10; // Estimation par défaut
    }
    
    // Obtenir le prix unitaire depuis la base
    $stmt = $pdo->prepare("
        SELECT price_per_page 
        FROM pricing_rules 
        WHERE paper_size = ? 
          AND paper_weight = ? 
          AND color_mode = ? 
          AND print_quality = ?
          AND is_active = 1 
          AND (valid_until IS NULL OR valid_until >= CURDATE())
        ORDER BY valid_from DESC 
        LIMIT 1
    ");
    
    $stmt->execute([$paper_size, $paper_weight, $color_mode, $print_quality]);
    $pricing = $stmt->fetch();
    
    $unit_price = 0.05; // Prix par défaut
    if ($pricing) {
        $unit_price = floatval($pricing['price_per_page']);
    } else {
        // Prix par défaut selon la configuration
        $unit_price = getDefaultPrice($paper_size, $color_mode, $paper_weight);
    }
    
    // Calcul du coût d'impression
    $printing_cost = $unit_price * $total_pages;
    
    // Obtenir le coût de finition
    $finishing_cost = 0;
    if ($binding !== 'NONE') {
        $stmt = $pdo->prepare("
            SELECT cost, cost_type 
            FROM finishing_costs 
            WHERE service_type = 'BINDING' 
              AND service_name = ? 
              AND is_active = 1 
            LIMIT 1
        ");
        $stmt->execute([$binding]);
        $finishing = $stmt->fetch();
        
        if ($finishing) {
            $finishing_cost = floatval($finishing['cost']);
            
            // Si c'est par page, multiplier par le nombre de pages
            if ($finishing['cost_type'] === 'PER_PAGE') {
                $finishing_cost *= $total_pages;
            }
        } else {
            // Coûts par défaut
            $finishing_cost = getDefaultFinishingCost($binding);
        }
    }
    
    // Calculs finaux
    $subtotal = $printing_cost + $finishing_cost;
    $total = $subtotal * $copies;
    
    // Calcul de la TVA (optionnel)
    $tax_rate = 0.21; // 21% TVA
    $tax_amount = $total * $tax_rate;
    $total_with_tax = $total + $tax_amount;
    
    // Réponse détaillée
    $response = [
        'success' => true,
        'pricing' => [
            'unit_price' => $unit_price,
            'total_pages' => $total_pages,
            'copies' => $copies,
            'printing_cost' => $printing_cost,
            'finishing_cost' => $finishing_cost,
            'subtotal' => $subtotal,
            'total_before_tax' => $total,
            'tax_rate' => $tax_rate,
            'tax_amount' => $tax_amount,
            'total_with_tax' => $total_with_tax,
            'currency' => 'EUR'
        ],
        'breakdown' => [
            'paper_cost' => [
                'description' => "Impresión {$paper_size} {$color_mode} {$paper_weight}",
                'unit_price' => $unit_price,
                'quantity' => $total_pages,
                'total' => $printing_cost
            ],
            'finishing' => [
                'description' => getFinishingDescription($binding),
                'cost' => $finishing_cost,
                'total' => $finishing_cost
            ],
            'copies' => [
                'description' => "Copias (×{$copies})",
                'subtotal' => $subtotal,
                'total' => $total
            ]
        ],
        'formatted' => [
            'unit_price' => formatPrice($unit_price),
            'printing_cost' => formatPrice($printing_cost),
            'finishing_cost' => formatPrice($finishing_cost),
            'subtotal' => formatPrice($subtotal),
            'total' => formatPrice($total),
            'total_with_tax' => formatPrice($total_with_tax)
        ]
    ];
    
    // Logger le calcul
    logEvent('DEBUG', 'Cálculo de precio', [
        'user_id' => $_SESSION['user_id'],
        'config' => $config,
        'total_pages' => $total_pages,
        'total_price' => $total
    ]);
    
    sendJSONResponse($response);
    
} catch (Exception $e) {
    error_log("Error cálculo precio: " . $e->getMessage());
    sendJSONResponse([
        'success' => false,
        'error' => 'Error al calcular el precio'
    ]);
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

/**
 * Obtenir la description de la finition
 */
function getFinishingDescription($binding) {
    $descriptions = [
        'NONE' => 'Sin acabado',
        'SPIRAL' => 'Encuadernación espiral',
        'STAPLE' => 'Grapado',
        'THERMAL' => 'Encuadernación térmica',
        'HARDCOVER' => 'Tapa dura',
        'PERFECT' => 'Encuadernación perfecta'
    ];
    
    return $descriptions[$binding] ?? 'Acabado personalizado';
}

/**
 * Formater un prix
 */
function formatPrice($amount, $currency = 'EUR') {
    return number_format($amount, 2, ',', ' ') . ' €';
}
?>