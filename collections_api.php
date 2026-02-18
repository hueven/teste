<?php
/**
 * Wide Studio - Collections API (V2 - Limpo)
 */
ini_set('display_errors', 1);
error_reporting(E_ALL);
header('Content-Type: application/json');

require_once 'config.php';

// 1. Verificação de Sessão
$rawInput = file_get_contents('php://input');
file_put_contents(__DIR__ . '/images/api_debug.log', date('[Y-m-d H:i:s] ') . "HIT: " . $_SERVER['REQUEST_URI'] . " | Method: " . $_SERVER['REQUEST_METHOD'] . "\n", FILE_APPEND);

session_start();
if (!isset($_SESSION['user_id'])) {
    error_log("Collections API: Unauthorized access attempt");
    jsonResponse(false, ['error' => 'Não autorizado. Por favor, faça login novamente.']);
}

// 2. Conexão DB
try {
    $pdo = getDbConnection();
} catch (Exception $e) {
    error_log("Collections API: DB Error - " . $e->getMessage());
    jsonResponse(false, ['error' => 'Erro de conexão com o banco de dados.']);
}

// 3. Captura Ação
$action = $_POST['action'] ?? $_GET['action'] ?? '';
$input = [];

if (empty($action)) {
    $input = json_decode($rawInput, true) ?: [];
    $action = $input['action'] ?? '';
} else {
    $input = $_POST;
}

error_log("Collections API: Hit - Action: $action | User ID: " . ($_SESSION['user_id'] ?? 'none'));

// 4. Helper: Sanitização
function sanitizeCollectionName($name) {
    if (!$name) return 'default';
    $sanitized = mb_strtolower($name, 'UTF-8');
    $sanitized = preg_replace('/[áàãâä]/u', 'a', $sanitized);
    $sanitized = preg_replace('/[éèêë]/u', 'e', $sanitized);
    $sanitized = preg_replace('/[íìîï]/u', 'i', $sanitized);
    $sanitized = preg_replace('/[óòõôö]/u', 'o', $sanitized);
    $sanitized = preg_replace('/[úùûü]/u', 'u', $sanitized);
    $sanitized = preg_replace('/[ç]/u', 'c', $sanitized);
    $sanitized = preg_replace('/[^a-z0-9]/', '-', $sanitized);
    $sanitized = preg_replace('/-+/', '-', $sanitized);
    return trim($sanitized, '-');
}

// 5. Roteamento Principal
try {
    
    // GET COLLECTION DETAIL
    if ($action === 'get_collection_detail') {
        $id = $_GET['id'] ?? 0;
        
        // Coleção
        $stmt = $pdo->prepare("SELECT * FROM collections WHERE id = ?");
        $stmt->execute([$id]);
        $collection = $stmt->fetch();
        
        if (!$collection) {
            jsonResponse(false, ['error' => 'Coleção não encontrada']);
        }
        
        // Produtos
        $stmt = $pdo->prepare("SELECT * FROM products WHERE collection_id = ? ORDER BY display_order ASC, id ASC");
        $stmt->execute([$id]);
        $products = $stmt->fetchAll();
        
        // Imagens do diretório
        $folderName = sanitizeCollectionName($collection['title']);
        $dirPath = __DIR__ . '/images/collections/' . $folderName;
        $webPath = 'images/collections/' . $folderName . '/';
        $images = [];
        
        if (is_dir($dirPath)) {
            $files = scandir($dirPath);
            foreach ($files as $file) {
                if ($file === '.' || $file === '..') continue;
                $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
                if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'gif'])) {
                    $images[] = [
                        'name' => $file,
                        'path' => $webPath . $file
                    ];
                }
            }
        }
        
        jsonResponse(true, [
            'collection' => $collection,
            'products' => $products,
            'images' => $images
        ]);
    }

    // =================================
    // UPLOAD COLLECTION IMAGE
    // =================================
    if ($action === 'upload_collection_image') {
        $collectionId = $_POST['collection_id'] ?? 0;
        
        if (!isset($_FILES['image'])) {
            throw new Exception('Nenhuma imagem enviada');
        }
        
        // Verifica coleção
        $stmt = $pdo->prepare("SELECT title FROM collections WHERE id = ?");
        $stmt->execute([$collectionId]);
        $collection = $stmt->fetch();
        
        if (!$collection) {
            throw new Exception('Coleção não encontrada');
        }
        
        // Cria diretório
        $sanitizedName = sanitizeCollectionName($collection['title']);
        $collectionDir = __DIR__ . '/images/collections/' . $sanitizedName . '/';
        
        if (!is_dir($collectionDir)) {
            if (!mkdir($collectionDir, 0755, true)) {
                throw new Exception('Falha ao criar pasta');
            }
        }
        
        // Processa arquivo
        $file = $_FILES['image'];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
             throw new Exception('Formato inválido');
        }
        
        $newName = time() . '_' . uniqid() . '.' . $ext;
        $physicalPath = $collectionDir . $newName;
        $webPath = 'images/collections/' . $sanitizedName . '/' . $newName;
        
        error_log("Collections API: Upload attempt for ID $collectionId");
        error_log("Collections API: Target Dir: $collectionDir (exists: " . (is_dir($collectionDir) ? 'YES' : 'NO') . ")");
        error_log("Collections API: Physical Path: $physicalPath");
        
        if (move_uploaded_file($file['tmp_name'], $physicalPath)) {
            error_log("Collections API: move_uploaded_file SUCCESS");
            // Inserir no banco de dados para consistência
            $stmt = $pdo->prepare("INSERT INTO collection_images (collection_id, image_path, created_at) VALUES (?, ?, CURRENT_TIMESTAMP)");
            $stmt->execute([$collectionId, $webPath]);
            
            jsonResponse(true, [
                'image_path' => $webPath,
                'physical_path' => $physicalPath,
                'message' => 'Upload realizado com sucesso'
            ]);
        } else {
            error_log("Collections API: move_uploaded_file FAILED. Error code: " . $file['error']);
            throw new Exception('Erro ao mover arquivo para: ' . $physicalPath);
        }
    }

    // =================================
    // DELETE IMAGE
    // =================================
    if ($action === 'delete_collection_image') {
        $input = json_decode(file_get_contents('php://input'), true);
        $collectionId = $input['collection_id'] ?? 0;
        $imageName = $input['image_name'] ?? '';
        
        $stmt = $pdo->prepare("SELECT title FROM collections WHERE id = ?");
        $stmt->execute([$collectionId]);
        $collection = $stmt->fetch();
        if (!$collection) throw new Exception('Coleção não encontrada');
        
        $sanitizedName = sanitizeCollectionName($collection['title']);
        $filePath = __DIR__ . '/images/collections/' . $sanitizedName . '/' . $imageName;
        
        if (file_exists($filePath)) {
            unlink($filePath);
            
            // Remover do banco de dados
            $webPath = 'images/collections/' . $sanitizedName . '/' . $imageName;
            $pdo->prepare("DELETE FROM collection_images WHERE collection_id = ? AND image_path = ?")->execute([$collectionId, $webPath]);
            
            // Limpar hero se for ela
             $stmt = $pdo->prepare("SELECT hero_image FROM collections WHERE id = ?");
             $stmt->execute([$collectionId]);
             $colCheck = $stmt->fetch();
             if ($colCheck && $colCheck['hero_image'] && strpos($colCheck['hero_image'], $imageName) !== false) {
                 $pdo->prepare("UPDATE collections SET hero_image = NULL WHERE id = ?")->execute([$collectionId]);
             }
             
            jsonResponse(true);
        } else {
            error_log("Collections API: File not found for deletion - $filePath");
            throw new Exception('Arquivo físico não encontrado no servidor.');
        }
    }

    // =================================
    // SET HERO IMAGE
    // =================================
    if ($action === 'set_hero_image') {
        $input = json_decode(file_get_contents('php://input'), true);
        $collectionId = $input['collection_id'] ?? 0;
        $imageName = $input['image_name'] ?? '';
        
        $stmt = $pdo->prepare("SELECT title FROM collections WHERE id = ?");
        $stmt->execute([$collectionId]);
        $collection = $stmt->fetch();
        if (!$collection) throw new Exception('Coleção não encontrada');
        
        $sanitizedName = sanitizeCollectionName($collection['title']);
        $webPath = 'images/collections/' . $sanitizedName . '/' . $imageName;
        
        $stmt = $pdo->prepare("UPDATE collections SET hero_image = ? WHERE id = ?");
        $stmt->execute([$webPath, $collectionId]);
        
        jsonResponse(true, ['hero_image' => $webPath]);
    }

    // =================================
    // UPDATE INFO
    // =================================
    if ($action === 'update_collection_info') {
        $input = json_decode(file_get_contents('php://input'), true);
        $id = $input['id'] ?? 0;
        $title = $input['title'] ?? '';
        
        $stmt = $pdo->prepare("UPDATE collections SET title = ?, description = ?, year = ?, produced_by = ? WHERE id = ?");
        $stmt->execute([
            $title, 
            $input['description']??'', 
            $input['year']??'', 
            $input['produced_by']??'', 
            $id
        ]);
        jsonResponse(true);
    }
    
    // =================================
    // PRODUCTS CRUD
    // =================================
    
    // ADD
    if ($action === 'add_product') {
        $input = json_decode(file_get_contents('php://input'), true);
        $stmt = $pdo->prepare("INSERT INTO products (collection_id, title, type, dimensions, materials, display_order) VALUES (?, ?, ?, ?, ?, 0)");
        $stmt->execute([
            $input['collection_id'],
            $input['title'],
            $input['type'],
            $input['dimensions'],
            $input['materials']
        ]);
        jsonResponse(true);
    }
    
    // UPDATE
    if ($action === 'update_product') {
        $input = json_decode(file_get_contents('php://input'), true);
        $stmt = $pdo->prepare("UPDATE products SET title=?, type=?, dimensions=?, materials=? WHERE id=?");
        $stmt->execute([
            $input['title'],
            $input['type'],
            $input['dimensions'],
            $input['materials'],
            $input['id']
        ]);
        jsonResponse(true);
    }
    
    // DELETE
    if ($action === 'delete_product') {
        $input = json_decode(file_get_contents('php://input'), true);
        $pdo->prepare("DELETE FROM products WHERE id=?")->execute([$input['id']]);
        jsonResponse(true);
    }
    
    // REORDER
    if ($action === 'reorder_products') {
        $input = json_decode(file_get_contents('php://input'), true);
        foreach ($input['products'] as $p) {
            $pdo->prepare("UPDATE products SET display_order=? WHERE id=?")->execute([$p['display_order'], $p['id']]);
        }
        jsonResponse(true);
    }

    if (!$action || $action === 'test') {
        jsonResponse(true, ['message' => 'API de Coleções V2 ativa e respondendo!']);
    } else {
        // Ação desconhecida
        jsonResponse(false, ['error' => 'Ação não implementada: ' . $action]);
    }

} catch (Throwable $e) {
    jsonResponse(false, ['error' => 'Erro Fatal: ' . $e->getMessage()]);
}
