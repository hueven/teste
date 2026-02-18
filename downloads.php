<?php
require 'config.php';
$pdo = getDbConnection();

// Buscar todos os produtos agrupados por coleção e sem duplicatas
try {
    // Usamos GROUP BY para evitar repetições se a migração foi executada várias vezes
    // E JOIN para pegar o nome da coleção
    $stmt = $pdo->query("
        SELECT p.*, c.title as collection_title 
        FROM products p
        LEFT JOIN collections c ON p.collection_id = c.id
        GROUP BY p.collection_id, p.title, p.type, p.dimensions, p.materials
        ORDER BY c.title ASC, p.display_order ASC
    ");
    $allProducts = $stmt->fetchAll();
    
    // Agrupar por coleção no PHP
    $productsByCollection = [];
    foreach ($allProducts as $product) {
        $collectionName = $product['collection_title'] ?: 'Avulsos';
        $productsByCollection[$collectionName][] = $product;
    }
} catch (PDOException $e) {
    $productsByCollection = [];
}
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Downloads - <?php echo htmlspecialchars(getSiteTitle()); ?></title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">

    <!-- Styles -->
    <link rel="stylesheet" href="css/variables.css">
    <link rel="stylesheet" href="css/utilities.css">
    <link rel="stylesheet" href="style.css">
    <link rel="icon" href="logo/favicon2.png" type="image/png">
    
    <style>
        .downloads-container {
            max-width: 900px;
            margin: 0 auto;
            padding: 40px 0 100px;
        }

        .download-list {
            margin-top: 60px;
        }

        .collection-group {
            margin-bottom: 60px;
        }

        .collection-group-title {
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.15em;
            color: var(--text-color);
            opacity: 0.4;
            font-weight: 600;
            padding-bottom: 15px;
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
            margin-bottom: 10px;
            display: block;
        }

        .download-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 25px 0;
            border-bottom: 1px solid rgba(0, 0, 0, 0.06);
            transition: all 0.3s ease;
        }

        .download-info {
            display: flex;
            flex-direction: column;
            gap: 4px;
        }

        .product-name {
            font-family: var(--font-main);
            font-size: 1.15rem;
            font-weight: 500;
            color: var(--text-color);
            letter-spacing: -0.01em;
        }

        .product-meta-desc {
            font-size: 0.8rem;
            opacity: 0.55;
            font-weight: 400;
            display: flex;
            gap: 15px;
        }

        .btn-download {
            font-family: var(--font-main);
            font-size: 0.72rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            padding: 10px 24px;
            border: 1px solid rgba(0, 0, 0, 0.1);
            border-radius: 50px;
            background: transparent;
            color: var(--text-color);
            cursor: pointer;
            transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-download:hover {
            background: var(--text-color);
            color: var(--bg-color);
            border-color: var(--text-color);
        }

        .btn-download svg {
            width: 13px;
            height: 13px;
        }

        @media (max-width: 768px) {
            .download-item {
                flex-direction: column;
                align-items: flex-start;
                gap: 15px;
            }
            
            .btn-download {
                width: 100%;
                justify-content: center;
            }
        }
    </style>
</head>

<body class="gallery-mode-color">

    <!-- Header -->
    <?php include 'header.php'; ?>

    <main class="works-section">
        <div class="downloads-container">
            <h1 class="section-title reveal">Downloads</h1>
            
            <div class="download-list">
                <?php if (!empty($productsByCollection)): ?>
                    <?php foreach ($productsByCollection as $collectionName => $items): ?>
                        <div class="collection-group reveal">
                            <span class="collection-group-title"><?php echo htmlspecialchars($collectionName); ?></span>
                            
                            <?php foreach ($items as $product): 
                                $displayName = $product['title'] ?: $product['type'];
                                $metaDesc = [];
                                if ($product['title'] && $product['type']) $metaDesc[] = $product['type'];
                                if ($product['dimensions']) $metaDesc[] = $product['dimensions'];
                                if ($product['materials']) $metaDesc[] = $product['materials'];
                            ?>
                                <div class="download-item">
                                    <div class="download-info">
                                        <span class="product-name"><?php echo htmlspecialchars($displayName); ?></span>
                                        <?php if (!empty($metaDesc)): ?>
                                            <span class="product-meta-desc"><?php echo htmlspecialchars(implode(' • ', $metaDesc)); ?></span>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <a href="#" class="btn-download" onclick="return false;">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                            <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v4"></path>
                                            <polyline points="7 10 12 15 17 10"></polyline>
                                            <line x1="12" y1="15" x2="12" y2="3"></line>
                                        </svg>
                                        Download 3D
                                    </a>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="empty-message">Nenhum produto disponível para download.</div>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <!-- Footer -->
    <?php include 'footer.php'; ?>

    <!-- Scripts -->
    <script src="https://unpkg.com/@studio-freight/lenis@1.0.42/dist/lenis.min.js"></script>
    <script type="module" src="script.js"></script>
</body>

</html>
