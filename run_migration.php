<?php
/**
 * Script para executar a migração de projetos e coleções
 * VERSÃO EMBUTIDA (Não depende de arquivos externos)
 */

require_once 'config.php';

header('Content-Type: text/plain; charset=utf-8');

// Desabilitar buffer
if (function_exists('apache_setenv')) {
    @apache_setenv('no-gzip', 1);
}
@ini_set('zlib.output_compression', 0);
@ini_set('implicit_flush', 1);
for ($i = 0; $i < ob_get_level(); $i++) { ob_end_flush(); }
ob_implicit_flush(1);

try {
    $pdo = getDbConnection();
    
    echo "=== MIGRATION SYSTEM (EMBEDDED) ===\n";
    echo "Data: " . date('Y-m-d H:i:s') . "\n\n";
    
    // DEFINIÇÃO DOS SQLs
    $migrations = [];

    // 1. PROJECTS MIGRATION (Simplificado)
    $migrations['Projects Setup'] = "
        CREATE TABLE IF NOT EXISTS `projects` (
          `id` int(11) NOT NULL AUTO_INCREMENT,
          `title` varchar(255) NOT NULL,
          `category_id` int(11) DEFAULT NULL,
          `description` text,
          `date` date DEFAULT NULL,
          `location` varchar(255) DEFAULT NULL,
          `client` varchar(255) DEFAULT NULL,
          `area` varchar(50) DEFAULT NULL,
          `architect` varchar(255) DEFAULT NULL,
          `display_order` int(11) DEFAULT 0,
          `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
          `hero_image` varchar(255) DEFAULT NULL,
          PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        
        -- Atualizar woks se necessario
        -- (Ignoramos para focar nas colecoes)
    ";

    // 2. COLLECTIONS & PRODUCTS
    $migrations['Collections & Products'] = "
        CREATE TABLE IF NOT EXISTS `collections` (
            `id` INT(11) NOT NULL AUTO_INCREMENT,
            `title` VARCHAR(255) NOT NULL,
            `description` TEXT,
            `produced_by` VARCHAR(255),
            `year` VARCHAR(10),
            `display_order` INT(11) DEFAULT 0,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

        CREATE TABLE IF NOT EXISTS `products` (
            `id` INT(11) NOT NULL AUTO_INCREMENT,
            `collection_id` INT(11),
            `title` VARCHAR(255) DEFAULT NULL,
            `type` VARCHAR(100),
            `dimensions` VARCHAR(255), 
            `materials` VARCHAR(255),
            `display_order` INT(11) DEFAULT 0,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            KEY `idx_collection_id` (`collection_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ";

    // 3. COLLECTION IMAGES
    $migrations['Collection Images'] = "
        CREATE TABLE IF NOT EXISTS `collection_images` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `collection_id` INT NOT NULL,
            `image_path` VARCHAR(255) NOT NULL,
            `is_primary` TINYINT(1) DEFAULT 0,
            `display_order` INT DEFAULT 0,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX `idx_collection_id` (`collection_id`),
            INDEX `idx_is_primary` (`is_primary`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ";

    // EXECUTAR MIGRAÇÕES
    foreach ($migrations as $name => $sql) {
        echo ">>> Executando: $name\n";
        
        // Limpar comentários
        $sql = preg_replace('/--.*$/m', '', $sql);
        
        // Dividir statements
        $statements = array_filter(
            array_map('trim', explode(';', $sql)),
            function($stmt) { return !empty($stmt); }
        );
        
        foreach ($statements as $stmt) {
            try {
                $pdo->exec($stmt);
            } catch (PDOException $e) {
                // Ignorar erros de existência
                if (stripos($e->getMessage(), 'already exists') === false) {
                    echo "Info: " . $e->getMessage() . "\n";
                }
            }
        }
        echo "✓ Concluído\n\n";
    }
    
    echo "=== VERIFICAÇÃO FINAL ===\n\n";
    
    $tables = ['projects', 'collections', 'products', 'collection_images'];
    foreach ($tables as $table) {
        $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
        if ($stmt->rowCount() > 0) {
            echo "✓ Tabela '$table' OK\n";
            // Check columns for collection_images to be sure
            if ($table === 'collection_images') {
                 $cols = $pdo->query("SHOW COLUMNS FROM collection_images")->fetchAll(PDO::FETCH_COLUMN);
                 echo "  Colunas: " . implode(', ', $cols) . "\n";
            }
        } else {
            echo "✗ Tabela '$table' NÃO encontrada\n";
        }
    }
    
    echo "\n=== FINALIZADO ===\n";
    
} catch (Exception $e) {
    echo "\n=== ERRO FATAL ===\n";
    echo "Erro: " . $e->getMessage() . "\n";
}
