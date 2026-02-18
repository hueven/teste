<?php
/**
 * Wide Studio - Login API
 * Autenticação de usuários
 */

// Inicia output buffering para capturar qualquer saída indesejada
ob_start();

// Configura error handling para não exibir erros no output
ini_set('display_errors', 0);
error_reporting(E_ALL);

// CRÍTICO: Verifica método ANTES de qualquer outra coisa
// Isso previne que session_start() ou outros headers causem redirect POST->GET
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    ob_clean(); // Limpa qualquer output
    error_log("Login API: Método recebido: " . $_SERVER['REQUEST_METHOD']);
    
    // Se for GET (acesso direto no navegador), redireciona para a página de login
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        header('Location: login.php');
        exit;
    }
    
    // Retorna erro JSON para outros métodos
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Método inválido. Método recebido: ' . $_SERVER['REQUEST_METHOD']]);
    exit;
}

try {
    session_start();
    require 'config.php';

    error_log("Login API: POST request received successfully");

    $pdo = getDbConnection();

// Aceita tanto JSON (fetch) quanto POST tradicional (form)
$rawInput = file_get_contents('php://input');
$jsonData = json_decode($rawInput, true);

error_log("Login API: Raw input length: " . strlen($rawInput));
error_log("Login API: JSON decoded: " . ($jsonData ? 'yes' : 'no'));

// Se veio JSON, usa JSON. Senão, usa $_POST
if ($jsonData && isset($jsonData['username'])) {
    $username = trim($jsonData['username'] ?? '');
    $password = trim($jsonData['password'] ?? '');
    error_log("Login API: Using JSON data for user: " . $username);
} else {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');
    error_log("Login API: Using POST data for user: " . $username);
}

if (empty($username) || empty($password)) {
    error_log("Login API: Empty credentials");
    jsonResponse(false, ['error' => 'Preencha todos os campos']);
}

// Busca usuário no banco
$stmt = $pdo->prepare("SELECT * FROM admin_users WHERE username = ?");
$stmt->execute([$username]);
$user = $stmt->fetch();

if ($user && password_verify($password, $user['password'])) {
    error_log("Login API: Authentication successful for user: " . $username);
    
    // Previne Session Fixation
    session_regenerate_id(true);
    
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['username'] = $user['username'];
    
    ob_clean(); // Limpa qualquer output antes de enviar JSON
    jsonResponse(true);
} else {
    error_log("Login API: Authentication failed for user: " . $username);
    ob_clean(); // Limpa qualquer output antes de enviar JSON
    jsonResponse(false, ['error' => 'Credenciais inválidas']);
}

} catch (Exception $e) {
    // Captura qualquer erro e retorna como JSON
    error_log("Login API: Exception caught: " . $e->getMessage());
    ob_clean(); // Limpa qualquer output
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Erro interno: ' . $e->getMessage()]);
    exit;
}
?>
