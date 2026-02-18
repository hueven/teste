<?php
require 'db.php';

// CONFIGURAÇÃO
$username_alvo = 'admin'; // O usuário que você quer logar no site
$nova_senha = '123'; // A senha que você quer usar

// Gera o hash correto
$hash = password_hash($nova_senha, PASSWORD_DEFAULT);

try {
    // Atualiza no banco
    $stmt = $pdo->prepare("UPDATE admin_users SET password = ? WHERE username = ?");
    $stmt->execute([$hash, $username_alvo]);
    
    if ($stmt->rowCount() > 0) {
        echo "<h1>Sucesso!</h1>";
        echo "Senha do usuário <b>$username_alvo</b> atualizada para <b>$nova_senha</b>.<br>";
        echo "Hash gerado: $hash";
    } else {
        echo "<h1>Aviso</h1>";
        echo "Nenhum usuário encontrado com o nome <b>$username_alvo</b>.<br>";
        echo "Verifique se na tabela 'admin_users' o nome é realmente 'admin'.";
        
        // Debug: Listar usuários existentes
        echo "<hr><h3>Usuários encontrados na tabela:</h3>";
        $stmt = $pdo->query("SELECT id, username FROM admin_users");
        while ($row = $stmt->fetch()) {
            echo "ID: " . $row['id'] . " - User: " . $row['username'] . "<br>";
        }
    }

} catch (PDOException $e) {
    echo "Erro SQL: " . $e->getMessage();
}
?>
