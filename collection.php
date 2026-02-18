<?php
require 'config.php';
$pdo = getDbConnection();

// Pegar ID da URL
$id = filter_input(INPUT_GET, 'id', FILTER_SANITIZE_NUMBER_INT);

// Se não tiver ID, redireciona
if (!$id) {
    header("Location: index.php");
    exit;
}

// Buscar dados da coleção
try {
    $stmt = $pdo->prepare("SELECT * FROM collections WHERE id = ?");
    $stmt->execute([$id]);
    $collection = $stmt->fetch();
    
    // Se não encontrar, redireciona
    if (!$collection) {
        header("Location: index.php");
        exit;
    }

    // Configurações da galeria
    $galleryColumns = getSetting('gallery_columns', '4');
    $galleryGap = getSetting('gallery_gap', '10');
    $galleryColorMode = getSetting('gallery_color_mode', 'grayscale');

    // Sanitizar título para encontrar pasta
    // Mesma lógica do upload: remove acentos, minúsculo, hifens
    $folderName = mb_strtolower($collection['title'], 'UTF-8');
    $folderName = preg_replace('/[áàãâä]/u', 'a', $folderName);
    $folderName = preg_replace('/[éèêë]/u', 'e', $folderName);
    $folderName = preg_replace('/[íìîï]/u', 'i', $folderName);
    $folderName = preg_replace('/[óòõôö]/u', 'o', $folderName);
    $folderName = preg_replace('/[úùûü]/u', 'u', $folderName);
    $folderName = preg_replace('/[ç]/u', 'c', $folderName);
    $folderName = preg_replace('/[^a-z0-9]/', '-', $folderName);
    $folderName = preg_replace('/-+/', '-', $folderName);
    $folderName = trim($folderName, '-');

    // Caminho da pasta
    $dirPath = __DIR__ . '/images/collections/' . $folderName;
    $webPath = 'images/collections/' . $folderName . '/';
    $images = [];

    // Listar imagens se a pasta existir
    if (is_dir($dirPath)) {
        // Pega todos os arquivos
        $files = scandir($dirPath);
        foreach ($files as $file) {
            // Ignora . e ..
            if ($file === '.' || $file === '..') continue;
            
            // Verifica extensão
            $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
            if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'gif'])) {
                // Adiciona ao array
                $images[] = [
                    'path' => $webPath . $file,
                    'name' => $file
                ];
            }
        }
    }
    
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
    <title><?php echo htmlspecialchars($collection['title']) . ' - ' . htmlspecialchars(getSiteTitle()); ?></title>
    
    <!-- SEO Meta Tags -->
    <meta name="description" content="<?php echo htmlspecialchars($collection['description']); ?>">
    <meta property="og:title" content="<?php echo htmlspecialchars($collection['title']); ?>">
    <meta property="og:description" content="<?php echo htmlspecialchars($collection['description']); ?>">
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">

    <!-- Styles -->
    <link rel="stylesheet" href="css/variables.css">
    <link rel="stylesheet" href="css/utilities.css">
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="css/detail-page.css"> <!-- Estilo compartilhado para páginas de detalhe -->
    <link rel="icon" href="logo/favicon2.png" type="image/png">
    <?php include 'includes/logo-font.php'; ?>
    <?php include 'includes/gallery-columns.php'; ?>
    
</head>

<body class="gallery-mode-<?php echo htmlspecialchars($galleryColorMode); ?>">

    <!-- Header -->
    <?php include 'header.php'; ?>

    <!-- Hero Section -->
    <section class="project-hero">
        <?php 
        // Se tiver imagem hero cadastrada no banco, usa ela
        // Se não, tenta pegar a primeira da pasta
        $heroImage = '';
        if (!empty($collection['hero_image'])) {
            $heroImage = $collection['hero_image'];
        } elseif (!empty($images)) {
            $heroImage = $images[0]['path'];
        }
        ?>
        
        <?php if ($heroImage): ?>
            <div class="project-hero-image">
                <img src="<?php echo htmlspecialchars($heroImage); ?>" 
                     alt="<?php echo htmlspecialchars($collection['title']); ?>">
            </div>
        <?php endif; ?>
        
        <div class="project-hero-info">
            <div class="project-header-column">
                <h1 class="project-title"><?php echo htmlspecialchars($collection['title']); ?></h1>
                
                <div class="collection-meta-list">
                    <?php if (!empty($collection['year'])): ?>
                        <div class="collection-meta-item">
                            <span class="meta-label">Ano</span>
                            <span class="meta-value"><?php echo htmlspecialchars($collection['year']); ?></span>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($collection['produced_by'])): ?>
                        <div class="collection-meta-item">
                            <span class="meta-label">Produção</span>
                            <span class="meta-value"><?php echo htmlspecialchars($collection['produced_by']); ?></span>
                        </div>
                    <?php endif; ?>
                </div>

                <a href="#" class="pill-button-link">
                    Baixar modelos 3D
                </a>
            </div>
            
            <div class="project-description-column">
                <?php if ($collection['description']): ?>
                    <p class="project-description"><?php echo nl2br(htmlspecialchars($collection['description'])); ?></p>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <main>
        <!-- Collection Gallery -->
        <?php if (count($images) > 0): ?>
        <section class="works-section project-gallery-section">
            <div class="works-grid" data-columns="<?php echo $galleryColumns; ?>" data-gap="<?php echo $galleryGap; ?>">
                <?php foreach ($images as $index => $image): ?>
                    <article class="work-item reveal" 
                             data-id="<?php echo $index; ?>"
                             data-title="<?php echo htmlspecialchars($collection['title']); ?>"
                             data-desc="">
                        <div class="img-container">
                            <img src="<?php echo htmlspecialchars($image['path']); ?>" 
                                 alt="<?php echo htmlspecialchars($image['name']); ?>" 
                                 loading="lazy">
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        </section>
        <?php endif; ?>
        
        <!-- Navigation -->
        <section class="project-navigation">
            <div class="nav-container">
                <a href="index.php" class="nav-link nav-all">
                    <span class="nav-text">Voltar para Home</span>
                </a>
            </div>
        </section>

        <!-- Footer -->
        <?php include 'footer.php'; ?>
    </main>

    <!-- Lightbox Modal -->
    <div class="lightbox-modal">
        <div class="lightbox-overlay"></div>

        <div class="lightbox-content">
            <button class="lightbox-close">
                <span class="line"></span>
                <span class="line"></span>
            </button>

            <div class="lightbox-image-container">
                <img src="" alt="Gallery Image" class="lightbox-img">
                
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

    <!-- Scripts -->
    <script src="https://unpkg.com/@studio-freight/lenis@1.0.42/dist/lenis.min.js"></script>
    <script type="module" src="project.js"></script> <!-- Reutilizando JS do projeto -->
</body>

</html>
