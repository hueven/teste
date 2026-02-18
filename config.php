<?php
/**
 * Wide Studio - Database Configuration
 * 
 * Este arquivo contém as configurações de conexão com o banco de dados.
 * IMPORTANTE: Em produção, use variáveis de ambiente ou um arquivo .env
 * que NÃO seja versionado no Git.
 */

// Configurações do Banco de Dados
define('DB_HOST', 'localhost');
define('DB_NAME', 'u465019645_dbcarol');
define('DB_USER', 'u465019645_cstarke');
define('DB_PASS', '3Df6&g3@');
define('DB_CHARSET', 'utf8mb4');

// Configurações da Aplicação
define('UPLOAD_DIR', 'imagens/');
define('MAX_UPLOAD_SIZE', 10 * 1024 * 1024); // 10MB
define('ALLOWED_EXTENSIONS', ['jpg', 'jpeg', 'png', 'gif', 'webp', 'mp4', 'webm', 'mov']);

// Configurações de Sessão
define('SESSION_LIFETIME', 86400); // 24 horas

/**
 * Obtém uma conexão PDO com o banco de dados
 * @return PDO
 */
function getDbConnection() {
    static $pdo = null;
    
    if ($pdo === null) {
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];
        
        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            // Em produção, não expor detalhes do erro
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'Erro ao conectar no banco de dados.']);
            exit;
        }
    }
    
    return $pdo;
}

/**
 * Retorna resposta JSON padronizada
 * @param bool $success
 * @param array $data
 */
function jsonResponse($success, $data = []) {
    header('Content-Type: application/json');
    echo json_encode(array_merge(['success' => $success], $data));
    exit;
}

/**
 * Valida se a extensão do arquivo é permitida
 * @param string $filename
 * @return bool
 */
function isAllowedExtension($filename) {
    $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    return in_array($ext, ALLOWED_EXTENSIONS);
}

/**
 * Gera nome único para arquivo
 * @param string $originalName
 * @return string
 */
function generateUniqueFilename($originalName) {
    $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
    $name = pathinfo($originalName, PATHINFO_FILENAME);
    
    // Remove caracteres especiais e espaços, mantém apenas alfanuméricos, hífen e underscore
    $name = preg_replace('/[^a-zA-Z0-9._-]/', '_', $name);
    
    // Hash curto (6 últimos caracteres do uniqid)
    $hash = substr(uniqid(), -6);
    
    return strtolower($name) . '_' . $hash . '.' . $ext;
}

/**
 * Obtém o título do site com sufixo opcional
 * @param string|null $suffix - Sufixo a adicionar (ex: " - Admin")
 * @return string
 */
function getSiteTitle($suffix = null) {
    $pdo = getDbConnection();
    $defaultTitle = 'Wide Studio - Art Direction';
    
    try {
        $stmt = $pdo->prepare("SELECT setting_value FROM settings WHERE setting_key = 'site_title'");
        $stmt->execute();
        $title = $stmt->fetchColumn();
        
        if (!$title) {
            $title = $defaultTitle;
        }
        
        return $suffix ? $title . $suffix : $title;
    } catch (Exception $e) {
        return $defaultTitle . ($suffix ?? '');
    }
}

/**
 * Obtém múltiplas configurações de uma vez (otimizado)
 * @param array $keys Lista de chaves de settings
 * @return array Array associativo [key => value]
 */
function getSettings($keys) {
    $pdo = getDbConnection();
    
    try {
        $placeholders = str_repeat('?,', count($keys) - 1) . '?';
        $stmt = $pdo->prepare("
            SELECT setting_key, setting_value 
            FROM settings 
            WHERE setting_key IN ($placeholders)
        ");
        $stmt->execute($keys);
        return $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    } catch (Exception $e) {
        return [];
    }
}

/**
 * Obtém uma configuração individual
 * @param string $key Chave da setting
 * @param mixed $default Valor padrão
 * @return mixed
 */
function getSetting($key, $default = null) {
    $settings = getSettings([$key]);
    return $settings[$key] ?? $default;
}

// Mantém compatibilidade com código existente
$pdo = getDbConnection();
