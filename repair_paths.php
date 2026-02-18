<?php
/**
 * Script de Reparo: Atualiza caminhos de imagens no Banco de Dados
 * De: images/colecoes/ -> Para: images/collections/
 */
require_once 'config.php';
$pdo = getDbConnection();

header('Content-Type: text/plain; charset=utf-8');

echo "=== INICIANDO REPARO DE CAMINHOS ===\n\n";

try {
    // 1. Atualizar Tabela collection_images
    $stmt1 = $pdo->prepare("UPDATE collection_images SET image_path = REPLACE(image_path, 'images/colecoes/', 'images/collections/') WHERE image_path LIKE '%images/colecoes/%'");
    $stmt1->execute();
    $rows1 = $stmt1->rowCount();
    echo "✓ [collection_images]: $rows1 caminhos atualizados.\n";

    // 2. Atualizar Tabela collections (hero_image)
    $stmt2 = $pdo->prepare("UPDATE collections SET hero_image = REPLACE(hero_image, 'images/colecoes/', 'images/collections/') WHERE hero_image LIKE '%images/colecoes/%'");
    $stmt2->execute();
    $rows2 = $stmt2->rowCount();
    echo "✓ [collections]: $rows2 hero_images atualizadas.\n";

    echo "\n=== REPARO CONCLUÍDO COM SUCESSO ===\n";
    echo "Agora todas as referências no banco de dados apontam para /images/collections/.\n";

} catch (Exception $e) {
    echo "\n❌ ERRO FATAL: " . $e->getMessage() . "\n";
}
