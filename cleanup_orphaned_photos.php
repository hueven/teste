<?php
/**
 * Script de Limpeza de Fotos Órfãs
 * 
 * Este script deleta fotos da tabela 'works' que não estão associadas a nenhum projeto
 * (project_id = NULL) e remove os arquivos físicos correspondentes.
 * 
 * ATENÇÃO: Execute este script apenas UMA VEZ para limpar fotos órfãs antigas.
 * Após a execução, delete este arquivo por segurança.
 */

session_start();
require 'config.php';

// Proteção: apenas admin logado pode executar
if (!isset($_SESSION['user_id'])) {
    die('❌ Erro: Você precisa estar logado como admin para executar este script.');
}

$pdo = getDbConnection();

echo "<!DOCTYPE html>
<html>
<head>
    <meta charset='UTF-8'>
    <title>Limpeza de Fotos Órfãs</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            max-width: 800px;
            margin: 50px auto;
            padding: 20px;
            background: #f5f5f5;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1 {
            color: #333;
            margin-bottom: 20px;
        }
        .warning {
            background: #fff3cd;
            border-left: 4px solid #ffc107;
            padding: 15px;
            margin: 20px 0;
        }
        .success {
            background: #d4edda;
            border-left: 4px solid #28a745;
            padding: 15px;
            margin: 20px 0;
        }
        .error {
            background: #f8d7da;
            border-left: 4px solid #dc3545;
            padding: 15px;
            margin: 20px 0;
        }
        .photo-list {
            max-height: 400px;
            overflow-y: auto;
            background: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            margin: 15px 0;
        }
        .photo-item {
            padding: 8px;
            border-bottom: 1px solid #dee2e6;
            font-family: monospace;
            font-size: 12px;
        }
        button {
            background: #dc3545;
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            margin-top: 20px;
        }
        button:hover {
            background: #c82333;
        }
        button:disabled {
            background: #6c757d;
            cursor: not-allowed;
        }
        .btn-cancel {
            background: #6c757d;
            margin-left: 10px;
        }
        .btn-cancel:hover {
            background: #5a6268;
        }
    </style>
</head>
<body>
    <div class='container'>";

try {
    // Buscar fotos órfãs (sem project_id)
    $stmt = $pdo->query("SELECT id, image_path, title, created_at FROM works WHERE project_id IS NULL ORDER BY created_at DESC");
    $orphanedPhotos = $stmt->fetchAll();
    
    $count = count($orphanedPhotos);
    
    if ($count === 0) {
        echo "<h1>✨ Tudo Limpo!</h1>";
        echo "<div class='success'>";
        echo "<strong>Não foram encontradas fotos órfãs no banco de dados.</strong><br>";
        echo "Seu banco está limpo! Você pode deletar este script agora.";
        echo "</div>";
        echo "<a href='admin.php'><button class='btn-cancel'>Voltar ao Admin</button></a>";
    } else {
        // Se não foi confirmado ainda, mostrar preview
        if (!isset($_POST['confirm'])) {
            echo "<h1>⚠️ {$count} Foto" . ($count > 1 ? 's' : '') . " Órfã" . ($count > 1 ? 's' : '') . " Encontrada" . ($count > 1 ? 's' : '') . "</h1>";
            
            echo "<div class='warning'>";
            echo "<strong>ATENÇÃO:</strong> As seguintes fotos não estão associadas a nenhum projeto e serão deletadas permanentemente:";
            echo "</div>";
            
            echo "<div class='photo-list'>";
            foreach ($orphanedPhotos as $photo) {
                $exists = file_exists($photo['image_path']) ? '✓' : '✗';
                $title = $photo['title'] ?: '(sem título)';
                echo "<div class='photo-item'>";
                echo "{$exists} ID: {$photo['id']} | {$photo['image_path']} | {$title}";
                echo "</div>";
            }
            echo "</div>";
            
            echo "<form method='POST'>";
            echo "<input type='hidden' name='confirm' value='1'>";
            echo "<button type='submit'>🗑️ Deletar {$count} Foto" . ($count > 1 ? 's' : '') . " Órfã" . ($count > 1 ? 's' : '') . "</button>";
            echo "<a href='admin.php'><button type='button' class='btn-cancel'>Cancelar</button></a>";
            echo "</form>";
            
        } else {
            // Executar limpeza
            echo "<h1>🧹 Executando Limpeza...</h1>";
            
            $deletedFiles = 0;
            $deletedRecords = 0;
            $errors = [];
            
            foreach ($orphanedPhotos as $photo) {
                try {
                    // Deletar arquivo físico
                    if (file_exists($photo['image_path'])) {
                        if (unlink($photo['image_path'])) {
                            $deletedFiles++;
                        } else {
                            $errors[] = "Erro ao deletar arquivo: {$photo['image_path']}";
                        }
                    }
                    
                    // Deletar do banco
                    $stmt = $pdo->prepare("DELETE FROM works WHERE id = ?");
                    $stmt->execute([$photo['id']]);
                    $deletedRecords++;
                    
                } catch (Exception $e) {
                    $errors[] = "Erro no ID {$photo['id']}: " . $e->getMessage();
                }
            }
            
            echo "<div class='success'>";
            echo "<strong>✅ Limpeza Concluída!</strong><br><br>";
            echo "📁 Arquivos deletados: {$deletedFiles}<br>";
            echo "🗄️ Registros removidos do banco: {$deletedRecords}";
            echo "</div>";
            
            if (count($errors) > 0) {
                echo "<div class='error'>";
                echo "<strong>⚠️ Alguns erros ocorreram:</strong><br>";
                foreach ($errors as $error) {
                    echo "• {$error}<br>";
                }
                echo "</div>";
            }
            
            echo "<div class='warning'>";
            echo "<strong>⚠️ IMPORTANTE:</strong> Por segurança, delete este arquivo agora:<br>";
            echo "<code>cleanup_orphaned_photos.php</code>";
            echo "</div>";
            
            echo "<a href='admin.php'><button class='btn-cancel'>Voltar ao Admin</button></a>";
        }
    }
    
} catch (Exception $e) {
    echo "<div class='error'>";
    echo "<strong>❌ Erro:</strong> " . htmlspecialchars($e->getMessage());
    echo "</div>";
}

echo "    </div>
</body>
</html>";
?>
