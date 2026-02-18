<?php
require_once 'config.php';

header('Content-Type: text/plain');

echo "--- DIAGNÓSTICO DE COLEÇÕES ---\n\n";

// 1. Verificar Conexão DB
try {
    $pdo = getDbConnection();
    echo "✅ Conexão DB: OK\n";
} catch (Exception $e) {
    echo "❌ Erro Conexão DB: " . $e->getMessage() . "\n";
}

// 2. Verificar Tabelas
$tables = ['collections', 'collection_images', 'products'];
foreach ($tables as $table) {
    try {
        $stmt = $pdo->query("SELECT 1 FROM `$table` LIMIT 1");
        echo "✅ Tabela `$table`: EXISTE\n";
        
        // Mostrar colunas de collection_images
        if ($table === 'collection_images') {
             $cols = $pdo->query("DESCRIBE `$table`")->fetchAll();
             echo "   Colunas: " . implode(', ', array_map(function($c){ return $c['Field']; }, $cols)) . "\n";
        }
    } catch (Exception $e) {
        echo "❌ Tabela `$table`: NÃO ENCONTRADA ou Erro (" . $e->getMessage() . ")\n";
    }
}

// 3. Verificar Pastas e Permissões
$paths = [
    'images/',
    'images/collections/'
];

foreach ($paths as $path) {
    $fullPath = __DIR__ . '/' . $path;
    if (is_dir($fullPath)) {
        echo "✅ Pasta `$path`: EXISTE\n";
        echo "   Permissões: " . substr(sprintf('%o', fileperms($fullPath)), -4) . "\n";
        echo "   Gravável: " . (is_writable($fullPath) ? 'SIM' : 'NÃO') . "\n";
    } else {
        echo "❌ Pasta `$path`: NÃO EXISTE\n";
    }
}

// 4. PHP Limits
echo "\n--- LIMITES PHP ---\n";
echo "upload_max_filesize: " . ini_get('upload_max_filesize') . "\n";
echo "post_max_size: " . ini_get('post_max_size') . "\n";
echo "memory_limit: " . ini_get('memory_limit') . "\n";

?>
