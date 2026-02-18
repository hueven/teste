<?php
require 'config.php';
$pdo = getDbConnection();

// Busca dados
try {
    // Texto Hero
    $stmt2 = $pdo->prepare("SELECT setting_value FROM settings WHERE setting_key = 'hero_text'");
    $stmt2->execute();
    $heroText = $stmt2->fetchColumn();
    
    if (!$heroText) {
        $heroText = "Designer & art director creating scalable design solutions in branding, packaging and webdesign, striving for clarity, simplicity & logic.";
    }
    
    // Função para converter <texto> em pills
    function convertPillSyntax($text) {
        // Parse pills ANTES de escapar HTML para evitar problemas
        // Procura por <texto> no texto original
        $parts = preg_split('/(<[^>]+>)/', $text, -1, PREG_SPLIT_DELIM_CAPTURE);
        
        $result = '';
        foreach ($parts as $part) {
            // Verifica se é um pill (começa com < e termina com >)
            if (preg_match('/^<([^>]+)>$/', $part, $matches)) {
                // É um pill - escapa o conteúdo e envolve em span
                $result .= '<span class="hero-pill">' . htmlspecialchars($matches[1]) . '</span>';
            } else {
                // Não é pill - escapa normalmente
                $result .= htmlspecialchars($part);
            }
        }
        
        return $result;
    }
    
    // Aplica conversão de pills no hero text
    $heroTextProcessed = convertPillSyntax($heroText);

    // Coleções (com imagem hero/principal)
    $stmt = $pdo->query("
        SELECT c.*,
               ci.image_path as hero_image,
               (SELECT COUNT(*) FROM collection_images WHERE collection_id = c.id) as image_count
        FROM collections c 
        LEFT JOIN collection_images ci ON c.id = ci.collection_id AND ci.is_primary = 1
        ORDER BY c.display_order ASC, c.created_at DESC
    ");
    $collections = $stmt->fetchAll();

    // Tipos de Imagem (Filtros) - Mantém para compatibilidade futura
    $imageTypes = [];
    try {
        $stmtTypes = $pdo->query("SELECT * FROM image_types ORDER BY display_order ASC");
        if ($stmtTypes) $imageTypes = $stmtTypes->fetchAll();
    } catch (Exception $e) {}

    // Configuração de modo de cores da galeria
    $galleryColorMode = getSetting('gallery_color_mode', 'grayscale');


    
    // Configurações da galeria (colunas e gap)
    $galleryColumns = getSetting('gallery_columns', '4');
    $galleryGap = getSetting('gallery_gap', '10');

    // Extrair categorias únicas das coleções para filtros (se necessário no futuro)
    $categories = [];
    // Removido - coleções não têm categorias por enquanto
    sort($categories); // Ordenar alfabeticamente

} catch (PDOException $e) {
    $projects = [];
    $imageTypes = [];
    $galleryColorMode = 'grayscale';
    $heroText = "Erro ao carregar conteúdo.";
}
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars(getSiteTitle()); ?></title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">

    <!-- Styles -->
    <link rel="stylesheet" href="css/variables.css">
    <link rel="stylesheet" href="css/utilities.css">
    <link rel="stylesheet" href="style.css">
    <link rel="icon" href="logo/favicon2.png" type="image/png">
    

    <?php include 'includes/hero-typography.php'; ?>
    <?php include 'includes/logo-font.php'; ?>
    <?php include 'includes/gallery-columns.php'; ?>
</head>

<body class="page-home gallery-mode-<?php echo htmlspecialchars($galleryColorMode); ?>">

    <!-- Header -->
    <?php include 'header.php'; ?>

    <!-- Hero Section (sticky - fica FORA do main para funcionar) -->
    <section class="hero-section">
        <h1 class="hero-text">
            <?php echo nl2br($heroTextProcessed); ?>
        </h1>
    </section>

    <main>
        <!-- Featured Works -->
        <section class="works-section">
            <h2 class="section-title reveal">Coleções</h2>
            
            <!-- Category Filters -->
            <?php if (count($categories) > 0): ?>
            <div class="filter-container reveal">
                <button class="filter-btn active" data-filter="all">Todos</button>
                <?php foreach ($categories as $category): ?>
                    <button class="filter-btn" data-filter="<?php echo htmlspecialchars(strtolower($category)); ?>">
                        <?php echo htmlspecialchars($category); ?>
                    </button>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <div class="works-grid grid-fixed-ratio" data-columns="<?php echo $galleryColumns; ?>" data-gap="<?php echo $galleryGap; ?>">
                <?php if (!empty($collections)): ?>
                    <?php foreach ($collections as $collection): ?>
                        <article class="work-item project-preview reveal" data-category="<?php echo htmlspecialchars($collection['title'] ?? ''); ?>">
                            <a href="collection.php?id=<?php echo $collection['id']; ?>" class="project-link">
                                <div class="img-container">
                                    <?php if (!empty($collection['hero_image'])): ?>
                                        <img 
                                            src="<?php echo htmlspecialchars($collection['hero_image']); ?>" 
                                            alt="<?php echo htmlspecialchars($collection['title']); ?>"
                                            loading="lazy"
                                        >
                                    <?php else: ?>
                                        <div class="no-image-placeholder">
                                            <span>📁</span>
                                            <span>Sem imagem</span>
                                        </div>
                                    <?php endif; ?>
                                    <div class="project-overlay">
                                        <h3 class="project-overlay-title"><?php echo htmlspecialchars($collection['title']); ?></h3>
                                    </div>
                                </div>
                            </a>
                        </article>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="empty-message">Nenhuma coleção cadastrada ainda.</div>
                <?php endif; ?>
            </div>
        </section>

        <!-- Footer -->
        <?php include 'footer.php'; ?>
    </main>

    <!-- Lenis Smooth Scroll Library -->
    <script src="https://unpkg.com/@studio-freight/lenis@1.0.42/dist/lenis.min.js"></script>
    
    <!-- Category Filter Script -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const filterButtons = document.querySelectorAll('.filter-btn');
            const projectItems = document.querySelectorAll('.work-item');

            if (filterButtons.length > 0 && projectItems.length > 0) {
                filterButtons.forEach(button => {
                    button.addEventListener('click', function() {
                        const filter = this.getAttribute('data-filter');
                        
                        // Update active button
                        filterButtons.forEach(btn => btn.classList.remove('active'));
                        this.classList.add('active');
                        
                        // PASSO 1: Fade out todos os itens primeiro
                        projectItems.forEach(item => {
                            item.style.opacity = '0';
                        });
                        
                        // PASSO 2: Após fade out, mudar display e recalcular layout
                        setTimeout(() => {
                            projectItems.forEach(item => {
                                const category = item.getAttribute('data-category');
                                
                                if (filter === 'all' || category === filter) {
                                    item.style.display = '';
                                } else {
                                    item.style.display = 'none';
                                }
                            });
                            
                            // PASSO 3: Forçar recálculo do masonry ANTES do fade in
                            if (typeof window.recalculateMasonry === 'function') {
                                // Force browser reflow
                                void projectItems[0].offsetHeight;
                                
                                window.recalculateMasonry();
                                
                                // PASSO 4: Fade in apenas os itens visíveis
                                setTimeout(() => {
                                    projectItems.forEach(item => {
                                        if (item.style.display !== 'none') {
                                            item.style.opacity = '1';
                                        }
                                    });
                                }, 10);
                            }
                        }, 300);
                    });
                });
            }
        });
    </script>
    
    <!-- Gallery Script (ES6 Module) -->
    <script type="module" src="script.js"></script>
</body>

</html>
