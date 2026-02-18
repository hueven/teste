<?php
// collection_upload_handler.php
// Handler DEDICADO para upload de imagens de coleção
// Isola a lógica para evitar conflitos com routers ou configurações globais

// Configurações básicas
// Buffer de saída para garantir que nada seja impresso antes do JSON
ob_start();

header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 0); // Erros não devem aparecer no output
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/upload_errors.log');

// Log de entrada
error_log("=== UPLOAD HANDLER INITIATED ===");
error_log("Method: " . $_SERVER['REQUEST_METHOD']);

function sendJson($data, $code = 200) {
    // Limpar qualquer output anterior (warnings, notices, espaços em branco)
    ob_clean();
    http_response_code($code);
    echo json_encode($data);
    exit;
}

// Handler global de erros para garantir JSON
set_exception_handler(function($e) {
    error_log("Uncaught Exception: " . $e->getMessage());
    sendJson(['success' => false, 'error' => $e->getMessage()], 500);
});

set_error_handler(function($errno, $errstr, $errfile, $errline) {
    error_log("PHP Error ($errno): $errstr in $errfile:$errline");
    // Não interromper execução para warnings, mas logar
    return false; 
});

// Verificar método
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendJson(['success' => false, 'error' => 'Método inválido. Use POST.'], 405);
}

// Carregar dependências
try {
    require_once 'config.php';
} catch (Throwable $e) {
    error_log("Erro ao carregar config.php: " . $e->getMessage());
    sendJson(['success' => false, 'error' => 'Erro interno de configuração'], 500);
}

try {
    // Verificar conexão DB
    $pdo = getDbConnection();

    // Validar inputs
    $collectionId = $_POST['collection_id'] ?? 0;
    
    if (!$collectionId) {
        throw new Exception('ID da coleção não fornecido.');
    }

    // Validar inputs
    $input = json_decode(file_get_contents('php://input'), true);
    
    // Suporte para Multipart (Files) OU JSON (Base64)
    if (isset($_FILES['image'])) {
        // Upload tradicional
        $file = $_FILES['image'];
        if ($file['error'] !== UPLOAD_ERR_OK) {
             throw new Exception("Erro no envio do arquivo. Código PHP: " . $file['error']);
        }
        $tmpName = $file['tmp_name'];
        $originalName = $file['name'];
        
    } elseif (isset($input['image_base64'])) {
        // Upload Base64
        $base64 = $input['image_base64'];
        if (preg_match('/^data:image\/(\w+);base64,/', $base64, $type)) {
            $base64 = substr($base64, strpos($base64, ',') + 1);
            $type = strtolower($type[1]); // jpg, png, gif
            
            if (!in_array($type, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
                throw new Exception('Tipo de arquivo não permitido (Base64).');
            }
            
            $base64 = base64_decode($base64);
            if ($base64 === false) {
                throw new Exception('Decodificação Base64 falhou');
            }
            
            // Criar arquivo temporário
            $tmpName = tempnam(sys_get_temp_dir(), 'img');
            file_put_contents($tmpName, $base64);
            $originalName = 'upload.' . $type;
            
        } else {
            throw new Exception('Formato Base64 inválido.');
        }
    } else {
        throw new Exception('Nenhuma imagem enviada (Files ou Base64).');
    }

    $collectionId = $_POST['collection_id'] ?? ($input['collection_id'] ?? 0);
    $setPrimary = $_POST['set_primary'] ?? ($input['set_primary'] ?? '0');
    
    if (!$collectionId) {
        throw new Exception('ID da coleção não fornecido.');
    }
    
    // Buscar informações da coleção
    $stmt = $pdo->prepare("SELECT title FROM collections WHERE id = ?");
    $stmt->execute([$collectionId]);
    $collection = $stmt->fetch();

    if (!$collection) {
        throw new Exception('Coleção não encontrada.');
    }

    // Preparar diretórios
    // Função local para garantir consistência
    function sanitizeNameForPath($name) {
        $name = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $name);
        $name = preg_replace('/[^a-zA-Z0-9\s\-_]/', '', $name);
        $name = preg_replace('/[\s\-]+/', '_', $name);
        return strtolower(trim($name, '_'));
    }

    $sanitizedName = sanitizeNameForPath($collection['title']);
    // Caminho absoluto físico
    $uploadDir = __DIR__ . '/images/collections/' . $sanitizedName . '/';
    // Caminho relativo web
    $webPathDir = 'images/collections/' . $sanitizedName . '/';

    // Log para debug
    error_log("Target Dir: $uploadDir");

    // Verificar/Criar diretório
    if (!is_dir($uploadDir)) {
        if (!mkdir($uploadDir, 0755, true)) {
            $error = error_get_last();
            throw new Exception("Falha ao criar diretório: " . ($error['message'] ?? 'Erro desconhecido'));
        }
    }

    // Gerar nome único para o arquivo
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $allowedExts = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
    
    if (!in_array($ext, $allowedExts)) {
        throw new Exception('Tipo de arquivo não permitido: ' . $ext);
    }

    $newFilename = time() . '_' . uniqid() . '.' . $ext;
    $destination = $uploadDir . $newFilename;
    $webPath = $webPathDir . $newFilename;

    // Mover arquivo
    $uploadSuccess = false;
    
    if (isset($_FILES['image'])) {
        $uploadSuccess = move_uploaded_file($tmpName, $destination);
    } else {
        // Para Base64 usamos rename ou copy
        $uploadSuccess = rename($tmpName, $destination);
    }

    if ($uploadSuccess) {
        // Sucesso no upload físico
        
        // Inserir no banco de dados (opcional, dependendo da lógica do sistema, mas recomendado para consistência)
        // O sistema atual lista arquivos da pasta, mas o endpoint anterior inseria no banco. Vamos manter a consistência.
        $setPrimary = $_POST['set_primary'] ?? '0';
        
        // Verificar ordem
        $stmt = $pdo->prepare("SELECT MAX(display_order) FROM collection_images WHERE collection_id = ?");
        $stmt->execute([$collectionId]);
        $maxOrder = $stmt->fetchColumn();
        $nextOrder = $maxOrder ? $maxOrder + 1 : 1;

        $stmt = $pdo->prepare("INSERT INTO collection_images (collection_id, image_path, is_primary, display_order) VALUES (?, ?, ?, ?)");
        $stmt->execute([$collectionId, $webPath, $setPrimary, $nextOrder]);
        $imageId = $pdo->lastInsertId();

        sendJson([
            'success' => true,
            'image_path' => $webPath,
            'image_id' => $imageId,
            'message' => 'Upload realizado com sucesso'
        ]);

    } else {
        throw new Exception('Falha ao mover arquivo enviado para o destino final.');
    }

} catch (Exception $e) {
    error_log("Upload Error: " . $e->getMessage());
    sendJson([
        'success' => false,
        'error' => $e->getMessage()
    ], 500);
}
