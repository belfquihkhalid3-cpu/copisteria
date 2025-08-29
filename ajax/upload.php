<?php
/**
 * Upload de fichiers AJAX - Copisteria Low Cost
 */

require_once '../config/config.php';
require_once '../includes/ajax-helpers.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Headers pour AJAX
header('Content-Type: application/json; charset=utf-8');

// Vérifier si l'utilisateur est connecté
if (!isLoggedIn()) {
    sendJSONResponse(['success' => false, 'error' => 'Usuario no autenticado'], 401);
}

// Vérifier la méthode HTTP
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendJSONResponse(['success' => false, 'error' => 'Método no autorizado'], 405);
}

// Vérifier le token CSRF
if (!isset($_POST['csrf_token']) || !verifyCSRFToken($_POST['csrf_token'])) {
    sendJSONResponse(['success' => false, 'error' => 'Token CSRF inválido'], 403);
}

// Vérifier qu'il y a des fichiers
if (!isset($_FILES['files']) || empty($_FILES['files']['name'][0])) {
    sendJSONResponse(['success' => false, 'error' => 'Ningún archivo recibido']);
}

$user_id = $_SESSION['user_id'];
$uploaded_files = [];
$errors = [];

try {
    // Créer les dossiers nécessaires
    ensureDirectoriesExist();
    
    // Traiter chaque fichier
    $files = $_FILES['files'];
    $file_count = count($files['name']);
    
    for ($i = 0; $i < $file_count; $i++) {
        if ($files['error'][$i] === UPLOAD_ERR_OK) {
            $result = processUploadedFile([
                'name' => $files['name'][$i],
                'type' => $files['type'][$i],
                'tmp_name' => $files['tmp_name'][$i],
                'size' => $files['size'][$i],
                'error' => $files['error'][$i]
            ], $user_id);
            
            if ($result['success']) {
                $uploaded_files[] = $result['file'];
            } else {
                $errors[] = $result['error'];
            }
        } else {
            $errors[] = "Error de upload para el archivo " . $files['name'][$i];
        }
    }
    
    // Logger l'upload
    logEvent('INFO', 'Upload de archivos', [
        'user_id' => $user_id,
        'files_count' => count($uploaded_files),
        'errors_count' => count($errors)
    ]);
    
    // Réponse
    if (!empty($uploaded_files)) {
        sendJSONResponse([
            'success' => true,
            'files' => $uploaded_files,
            'errors' => $errors,
            'message' => count($uploaded_files) . ' archivo(s) subido(s) con éxito'
        ]);
    } else {
        sendJSONResponse([
            'success' => false,
            'error' => 'Ningún archivo pudo ser subido',
            'errors' => $errors
        ]);
    }
    
} catch (Exception $e) {
    error_log("Error upload: " . $e->getMessage());
    sendJSONResponse(['success' => false, 'error' => 'Error del servidor durante la subida']);
}

/**
 * Traiter un fichier uploadé
 */
function processUploadedFile($file, $user_id) {
    // Validation de sécurité
    $validation_result = validateUploadedFile($file);
    if (!empty($validation_result)) {
        return ['success' => false, 'error' => implode(', ', $validation_result)];
    }
    
    // Générer nom de fichier sécurisé
    $secure_filename = generateSecureFileName($file['name'], $user_id);
    $upload_path = UPLOAD_PATH . 'documents/' . $secure_filename;
    
    // Déplacer le fichier
    if (!move_uploaded_file($file['tmp_name'], $upload_path)) {
        return ['success' => false, 'error' => 'Imposible guardar el archivo'];
    }
    
    // Analyser le fichier
    $file_info = analyzeFile($upload_path, $file);
    
    // Générer thumbnail si image
    $thumbnail = null;
    if (strpos($file['type'], 'image/') === 0) {
        $thumbnail = generateThumbnail($upload_path, $secure_filename);
    }
    
    return [
        'success' => true,
        'file' => [
            'id' => uniqid('file_' . $user_id . '_'),
            'original_name' => $file['name'],
            'secure_name' => $secure_filename,
            'size' => $file['size'],
            'type' => $file['type'],
            'pages' => $file_info['pages'],
            'thumbnail' => $thumbnail,
            'upload_time' => date('Y-m-d H:i:s'),
            'file_path' => 'assets/uploads/documents/' . $secure_filename
        ]
    ];
}

/**
 * Valider un fichier uploadé
 */
function validateUploadedFile($file) {
    $errors = [];
    
    // Vérifier la taille
    if ($file['size'] > MAX_FILE_SIZE) {
        $errors[] = "Archivo demasiado grande (max " . formatFileSize(MAX_FILE_SIZE) . ")";
    }
    
    // Vérifier l'extension
    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($extension, ALLOWED_EXTENSIONS)) {
        $errors[] = "Tipo de archivo no autorizado: ." . $extension;
    }
    
    // Vérifier le type MIME
    if (!in_array($file['type'], ALLOWED_MIME_TYPES)) {
        $errors[] = "Tipo MIME no autorizado: " . $file['type'];
    }
    
    // Vérification de sécurité supplémentaire avec finfo
    if (function_exists('finfo_open')) {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $detected_type = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        
        if (!in_array($detected_type, ALLOWED_MIME_TYPES)) {
            $errors[] = "Tipo de archivo detectado no autorizado: " . $detected_type;
        }
    }
    
    // Vérifier que le fichier n'est pas un script
    $dangerous_extensions = ['php', 'php3', 'php4', 'php5', 'phtml', 'pl', 'py', 'jsp', 'asp', 'sh', 'cgi'];
    if (in_array($extension, $dangerous_extensions)) {
        $errors[] = "Tipo de archivo peligroso detectado";
    }
    
    return $errors;
}

/**
 * Analyser un fichier pour obtenir des informations
 */
function analyzeFile($file_path, $original_file) {
    $info = [
        'pages' => 1,
        'dimensions' => null,
        'color_mode' => 'unknown'
    ];
    
    $extension = strtolower(pathinfo($original_file['name'], PATHINFO_EXTENSION));
    
    switch ($extension) {
        case 'pdf':
            $info['pages'] = getPDFPageCount($file_path);
            break;
            
        case 'jpg':
        case 'jpeg':
        case 'png':
            $image_info = getimagesize($file_path);
            if ($image_info) {
                $info['dimensions'] = [
                    'width' => $image_info[0],
                    'height' => $image_info[1]
                ];
            }
            break;
            
        case 'doc':
        case 'docx':
            // Estimation basée sur la taille
            $info['pages'] = max(1, round($original_file['size'] / (50 * 1024)));
            break;
            
        default:
            $info['pages'] = 1;
    }
    
    return $info;
}

/**
 * Compter les pages d'un PDF
 */
function getPDFPageCount($file_path) {
    try {
        // Méthode 1: Lecture directe du PDF
        $content = file_get_contents($file_path);
        if ($content !== false) {
            preg_match_all('/\/Page\W/', $content, $matches);
            $pages = count($matches[0]);
            if ($pages > 0) {
                return $pages;
            }
        }
        
        // Méthode 2: Chercher /Count
        if (preg_match('/\/Count\s+(\d+)/', $content, $matches)) {
            return intval($matches[1]);
        }
        
        // Par défaut
        return 1;
        
    } catch (Exception $e) {
        return 1;
    }
}

/**
 * Générer une miniature pour les images
 */
function generateThumbnail($original_path, $filename) {
    try {
        $thumbnail_dir = UPLOAD_PATH . 'thumbnails/';
        if (!is_dir($thumbnail_dir)) {
            mkdir($thumbnail_dir, 0755, true);
        }
        
        $thumbnail_name = 'thumb_' . $filename;
        $thumbnail_path = $thumbnail_dir . $thumbnail_name;
        
        // Créer la miniature
        $image_info = getimagesize($original_path);
        if (!$image_info) {
            return null;
        }
        
        $original_width = $image_info[0];
        $original_height = $image_info[1];
        $mime_type = $image_info['mime'];
        
        // Dimensions de la miniature
        $thumb_width = 200;
        $thumb_height = 200;
        
        // Calculer les nouvelles dimensions en gardant le ratio
        $ratio = min($thumb_width / $original_width, $thumb_height / $original_height);
        $new_width = intval($original_width * $ratio);
        $new_height = intval($original_height * $ratio);
        
        // Créer l'image source
        switch ($mime_type) {
            case 'image/jpeg':
                $source = imagecreatefromjpeg($original_path);
                break;
            case 'image/png':
                $source = imagecreatefrompng($original_path);
                break;
            default:
                return null;
        }
        
        if (!$source) {
            return null;
        }
        
        // Créer la miniature
        $thumbnail = imagecreatetruecolor($new_width, $new_height);
        
        // Préserver la transparence pour PNG
        if ($mime_type === 'image/png') {
            imagealphablending($thumbnail, false);
            imagesavealpha($thumbnail, true);
        }
        
        imagecopyresampled($thumbnail, $source, 0, 0, 0, 0, 
                          $new_width, $new_height, $original_width, $original_height);
        
        // Sauvegarder la miniature
        switch ($mime_type) {
            case 'image/jpeg':
                imagejpeg($thumbnail, $thumbnail_path, 85);
                break;
            case 'image/png':
                imagepng($thumbnail, $thumbnail_path);
                break;
        }
        
        imagedestroy($source);
        imagedestroy($thumbnail);
        
        return 'assets/uploads/thumbnails/' . $thumbnail_name;
        
    } catch (Exception $e) {
        error_log("Error generación miniatura: " . $e->getMessage());
        return null;
    }
}
?>