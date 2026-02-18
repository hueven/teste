<?php
require 'config.php';
$pdo = getDbConnection();

// Pegar slug da URL
$slug = $_GET['slug'] ?? '';

if (empty($slug)) {
    header("Location: index.php");
    exit;
}

// Buscar dados do projeto
try {
    $stmt = $pdo->prepare("
        SELECT p.*, pc.name as category_name 
        FROM projects p
        LEFT JOIN project_categories pc ON p.category_id = pc.id
        WHERE p.slug = ?
    ");
    $stmt->execute([$slug]);
    $project = $stmt->fetch();
    
    if (!$project) {
        header("Location: index.php");
        exit;
    }
    
    // Buscar imagens do projeto
    $stmt = $pdo->prepare("
        SELECT * FROM works 
        WHERE project_id = ? 
        ORDER BY display_order ASC, created_at DESC
    ");
    $stmt->execute([$project['id']]);
    $projectImages = $stmt->fetchAll();
    
    // Buscar projeto anterior e próximo
    $stmt = $pdo->prepare("
        SELECT id, slug, title 
        FROM projects 
        WHERE display_order < ? 
        ORDER BY display_order DESC 
        LIMIT 1
    ");
    $stmt->execute([$project['display_order']]);
    $prevProject = $stmt->fetch();
    
    $stmt = $pdo->prepare("
        SELECT id, slug, title 
        FROM projects 
        WHERE display_order > ? 
        ORDER BY display_order ASC 
        LIMIT 1
    ");
    $stmt->execute([$project['display_order']]);
    $nextProject = $stmt->fetch();
    
    // Configurações da galeria
    $galleryColumns = getSetting('gallery_columns', '4');
    $galleryGap = getSetting('gallery_gap', '10');
    $galleryColorMode = getSetting('gallery_color_mode', 'grayscale');
    
    
} catch (PDOException $e) {
    header("Location: index.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($project['title']) . ' - ' . htmlspecialchars(getSiteTitle()); ?></title>
    
    <!-- SEO Meta Tags -->
    <meta name="description" content="<?php echo htmlspecialchars($project['description']); ?>">
    <meta property="og:title" content="<?php echo htmlspecialchars($project['title']); ?>">
    <meta property="og:description" content="<?php echo htmlspecialchars($project['description']); ?>">
    <?php if ($project['hero_image_path']): ?>
    <meta property="og:image" content="<?php echo htmlspecialchars($project['hero_image_path']); ?>">
    <?php endif; ?>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">

    <!-- Styles -->
    <link rel="stylesheet" href="css/variables.css">
    <link rel="stylesheet" href="css/utilities.css">
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="css/detail-page.css">
    <link rel="icon" href="logo/favicon2.png" type="image/png">
    <?php include 'includes/logo-font.php'; ?>
    <?php include 'includes/gallery-columns.php'; ?>
</head>

<body class="gallery-mode-<?php echo htmlspecialchars($galleryColorMode); ?>">

    <!-- Header -->
    <?php include 'header.php'; ?>

    <!-- Hero Section -->
    <section class="project-hero">
        <?php if ($project['hero_image_path']): ?>
            <div class="project-hero-image">
                <img src="<?php echo htmlspecialchars($project['hero_image_path']); ?>" 
                     alt="<?php echo htmlspecialchars($project['title']); ?>">
            </div>
        <?php endif; ?>
        
        <div class="project-hero-info">
            <div class="project-header-column">
                <h1 class="project-title"><?php echo htmlspecialchars($project['title']); ?></h1>
                
                <div class="project-meta">
                    <?php if ($project['category_name']): ?>
                        <span class="meta-item">
                            <span class="meta-value hero-pill"><?php echo htmlspecialchars($project['category_name']); ?></span>
                        </span>
                    <?php endif; ?>
                </div>
            </div>

            <div class="project-description-column">
                <?php if ($project['description']): ?>
                    <p class="project-description"><?php echo nl2br(htmlspecialchars($project['description'])); ?></p>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <main>
        <!-- Project Gallery -->
        <?php if (count($projectImages) > 0): ?>
        <section class="works-section project-gallery-section">
            <div class="works-grid" data-columns="<?php echo $galleryColumns; ?>" data-gap="<?php echo $galleryGap; ?>">
                <?php foreach ($projectImages as $image): ?>
                    <article class="work-item reveal" 
                             data-id="<?php echo htmlspecialchars($image['id'] ?? ''); ?>"
                             data-title="<?php echo htmlspecialchars($image['title'] ?? ''); ?>"
                             data-desc="<?php echo htmlspecialchars($image['description'] ?? ''); ?>">
                        <div class="img-container">
                            <img src="<?php echo htmlspecialchars($image['image_path']); ?>" 
                                 alt="<?php echo htmlspecialchars($image['title'] ?: $project['title']); ?>" 
                                 style="object-position: center <?php echo htmlspecialchars($image['alignment'] ?? 'center'); ?>;"
                                 loading="lazy">
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        </section>
        <?php endif; ?>
        
        <!-- Project Navigation -->
        <?php if ($prevProject || $nextProject): ?>
        <section class="project-navigation">
            <div class="nav-container">
                <?php if ($prevProject): ?>
                    <a href="<?php echo htmlspecialchars($prevProject['slug']); ?>" class="nav-link nav-prev">
                        <span class="nav-arrow">←</span>
                        <span class="nav-label">
                            <span class="nav-text">Projeto Anterior</span>
                            <span class="nav-title"><?php echo htmlspecialchars($prevProject['title']); ?></span>
                        </span>
                    </a>
                <?php endif; ?>
                
                <a href="index.php" class="nav-link nav-all">
                    <span class="nav-text">Todos os Projetos</span>
                </a>
                
                <?php if ($nextProject): ?>
                    <a href="<?php echo htmlspecialchars($nextProject['slug']); ?>" class="nav-link nav-next">
                        <span class="nav-label">
                            <span class="nav-text">Próximo Projeto</span>
                            <span class="nav-title"><?php echo htmlspecialchars($nextProject['title']); ?></span>
                        </span>
                        <span class="nav-arrow">→</span>
                    </a>
                <?php endif; ?>
            </div>
        </section>
        <?php endif; ?>

        <!-- Footer -->
        <?php include 'footer.php'; ?>
    </main>

    <!-- Lightbox Modal -->
    <div class="lightbox-modal">
        <div class="lightbox-overlay"></div>

        <div class="lightbox-content">
            <!-- Close Button -->
            <button class="lightbox-close">
                <span class="line"></span>
                <span class="line"></span>
            </button>

            <!-- Image Container -->
            <div class="lightbox-image-container">
                <img src="" alt="Gallery Image" class="lightbox-img">
                
                <!-- Navigation Arrows -->
                <button class="lightbox-nav lightbox-nav-prev" aria-label="Foto anterior">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="15 18 9 12 15 6"></polyline>
                    </svg>
                </button>
                
                <button class="lightbox-nav lightbox-nav-next" aria-label="Próxima foto">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="9 18 15 12 9 6"></polyline>
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <!-- Lenis Smooth Scroll Library -->
    <script src="https://unpkg.com/@studio-freight/lenis@1.0.42/dist/lenis.min.js"></script>
    
    <!-- Project Script (ES6 Module) -->
    <script type="module" src="project.js"></script>
</body>

</html>
