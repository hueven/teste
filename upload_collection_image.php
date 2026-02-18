<?php
/**
 * Endpoint dedicado APENAS para upload de imagens de coleção
 */

session_start();
require_once 'config.php';

// Headers para CORS e JSON
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Content-Type: application/json');

// Verificar login
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Não autorizado']);
    exit;
}

// Debug: Se for OPTIONS, retorna ok
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// APENAS POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'success' => false, 
        'error' => 'Método não permitido. Recebido: ' . $_SERVER['REQUEST_METHOD']
    ]);
    exit;
}

try {
    $pdo = getDbConnection();
    
    $collectionId = $_POST['collection_id'] ?? 0;
    $setPrimary = $_POST['set_primary'] ?? '1';
    
    if (!isset($_FILES['image'])) {
        throw new Exception('Nenhuma imagem enviada');
    }
    
    // Buscar título da coleção
    $stmt = $pdo->prepare("SELECT title FROM collections WHERE id = ?");
    $stmt->execute([$collectionId]);
    $collection = $stmt->fetch();
    
    if (!$collection) {
        throw new Exception('Coleção não encontrada');
    }
    
    // Sanitizar nome
    $sanitizedName = sanitizeCollectionName($collection['title']);
    $collectionDir = __DIR__ . '/images/collections/' . $sanitizedName . '/';
    
    // Criar pasta se não existir
    if (!is_dir($collectionDir)) {
        if (!mkdir($collectionDir, 0755, true)) {
            throw new Exception('Falha ao criar pasta da coleção');
        }
    }
    
    $file = $_FILES['image'];
    
    if (!isAllowedExtension($file['name'])) {
        throw new Exception('Tipo de arquivo não permitido');
    }
    
    $originalExt = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $newName = time() . '_' . uniqid() . '.' . $originalExt;
    
    // Caminhos
    $physicalPath = $collectionDir . $newName;
    $webPath = '/images/collections/' . $sanitizedName . '/' . $newName;
    
    if (move_uploaded_file($file['tmp_name'], $physicalPath)) {
        // Remover flag is_primary de outras imagens se for primária
        if ($setPrimary == '1') {
            $stmt = $pdo->prepare("UPDATE collection_images SET is_primary = 0 WHERE collection_id = ?");
            $stmt->execute([$collectionId]);
        }
        
        // Obter próximo display_order
        $stmt = $pdo->prepare("SELECT MAX(display_order) FROM collection_images WHERE collection_id = ?");
        $stmt->execute([$collectionId]);
        $maxOrder = $stmt->fetchColumn();
        $nextOrder = $maxOrder ? $maxOrder + 1 : 1;
        
        // Inserir nova imagem
        $stmt = $pdo->prepare("INSERT INTO collection_images (collection_id, image_path, is_primary, display_order) VALUES (?, ?, ?, ?)");
        $stmt->execute([$collectionId, $webPath, $setPrimary, $nextOrder]);
        
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'image_path' => $webPath,
            'image_id' => $pdo->lastInsertId()
        ]);
    } else {
        throw new Exception('Erro ao salvar arquivo');
    }
    
} catch (Exception $e) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
