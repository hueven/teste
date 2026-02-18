<?php
/**
 * Wide Studio - Change Password API
 * Alteração de senha do usuário
 */

session_start();
header('Content-Type: application/json');
require 'config.php';

// Verifica login
if (!isset($_SESSION['user_id'])) {
    jsonResponse(false, ['error' => 'Não autorizado']);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(false, ['error' => 'Método inválido']);
}

$pdo = getDbConnection();
$data = json_decode(file_get_contents('php://input'), true);

$current_password = $data['current_password'] ?? '';
$new_password = $data['new_password'] ?? '';
$user_id = $_SESSION['user_id'];

// Validação de complexidade
if (strlen($new_password) < 8) {
    jsonResponse(false, ['error' => 'A senha deve ter pelo menos 8 caracteres.']);
}

if (!preg_match('/[\W_]/', $new_password)) {
    jsonResponse(false, ['error' => 'A senha deve conter pelo menos um caractere especial (!, @, #, etc).']);
}

try {
    // Verifica senha atual
    $stmt = $pdo->prepare("SELECT password FROM admin_users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($current_password, $user['password'])) {
        jsonResponse(false, ['error' => 'Senha atual incorreta.']);
    }

    // Atualiza para nova senha
    $new_hash = password_hash($new_password, PASSWORD_DEFAULT);
    
    $update = $pdo->prepare("UPDATE admin_users SET password = ? WHERE id = ?");
    $update->execute([$new_hash, $user_id]);

    jsonResponse(true);

} catch (PDOException $e) {
    jsonResponse(false, ['error' => 'Erro interno ao salvar.']);
}
?>
