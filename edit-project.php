<?php
session_start();
require 'config.php';

// Verifica login
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$pdo = getDbConnection();

// Pegar ID do projeto
$projectId = $_GET['id'] ?? 0;

if (empty($projectId)) {
    header("Location: admin.php");
    exit;
}

// Buscar dados do projeto
try {
    $stmt = $pdo->prepare("SELECT * FROM projects WHERE id = ?");
    $stmt->execute([$projectId]);
    $project = $stmt->fetch();
    
    if (!$project) {
        header("Location: admin.php");
        exit;
    }
    
    // Buscar categorias disponíveis
    $stmt = $pdo->query("SELECT * FROM project_categories ORDER BY display_order ASC, name ASC");
    $categories = $stmt->fetchAll();
    
    // Buscar imagens do projeto
    $stmt = $pdo->prepare("
        SELECT * FROM works 
        WHERE project_id = ? 
        ORDER BY display_order ASC, created_at DESC
    ");
    $stmt->execute([$project['id']]);
    $projectImages = $stmt->fetchAll();
    
    // Configurações da galeria
    $galleryColumns = getSetting('gallery_columns', '4');
    $galleryGap = getSetting('gallery_gap', '10');
    $galleryColorMode = getSetting('gallery_color_mode', 'grayscale');
    
} catch (PDOException $e) {
    header("Location: admin.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar: <?php echo htmlspecialchars($project['title']); ?> - Admin</title>
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">

    <!-- Styles -->
    <link rel="stylesheet" href="css/variables.css">
    <link rel="stylesheet" href="css/utilities.css">
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="css/project.css">
    <link rel="stylesheet" href="css/edit-project.css">
    <link rel="icon" href="logo/favicon2.png" type="image/png">
    <?php include 'includes/logo-font.php'; ?>
    <?php include 'includes/gallery-columns.php'; ?>
    
    <!-- Sortable.js for drag-and-drop -->
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
</head>

<body class="gallery-mode-<?php echo htmlspecialchars($galleryColorMode); ?> edit-mode">

    <!-- Fixed Header with Save Button -->
    <div class="edit-header">
        <div class="edit-header-content">
            <a href="admin.php#projects" class="btn-back">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M19 12H5M12 19l-7-7 7-7"/>
                </svg>
                Voltar para Projetos
            </a>
            
            <div class="edit-header-center">
                <span class="edit-status" id="editStatus">Não salvo</span>
            </div>
            
            <button class="btn-save-project" id="btnSaveProject">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/>
                    <polyline points="17 21 17 13 7 13 7 21"/>
                    <polyline points="7 3 7 8 15 8"/>
                </svg>
                Salvar Alterações
            </button>
        </div>
    </div>

    <!-- Header -->
    <?php include 'header.php'; ?>

    <!-- Hero Section (Editable) -->
    <section class="project-hero">
        <!-- Hero Image with Upload Overlay -->
        <div class="project-hero-image editable-hero" id="heroImageContainer">
            <?php if ($project['hero_image_path']): ?>
                <img src="<?php echo htmlspecialchars($project['hero_image_path']); ?>" 
                     alt="<?php echo htmlspecialchars($project['title']); ?>"
                     id="heroImage">
            <?php else: ?>
                <div class="hero-placeholder">
                    <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                        <rect x="3" y="3" width="18" height="18" rx="2" ry="2"/>
                        <circle cx="8.5" cy="8.5" r="1.5"/>
                        <polyline points="21 15 16 10 5 21"/>
                    </svg>
                    <p>Clique para adicionar imagem hero</p>
                </div>
            <?php endif; ?>
            
            <div class="hero-overlay">
                <button class="btn-hero-action" id="btnUploadHero">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
                        <polyline points="17 8 12 3 7 8"/>
                        <line x1="12" y1="3" x2="12" y2="15"/>
                    </svg>
                    Trocar Imagem Hero
                </button>
                <?php if ($project['hero_image_path']): ?>
                <button class="btn-hero-action btn-remove" id="btnRemoveHero">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="3 6 5 6 21 6"/>
                        <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/>
                    </svg>
                    Remover
                </button>
                <?php endif; ?>
            </div>
            
            <input type="file" id="heroImageInput" accept="image/*" style="display: none;">
        </div>
        
        <div class="project-hero-info">
            <!-- Editable Title -->
            <input type="text" 
                   class="project-title editable-title" 
                   id="projectTitle"
                   value="<?php echo htmlspecialchars($project['title']); ?>"
                   placeholder="Título do Projeto">
            
            <div class="project-content-grid">
                <!-- Editable Description -->
                <textarea class="project-description editable-description" 
                          id="projectDescription"
                          placeholder="Descrição do projeto..."
                          rows="4"><?php echo htmlspecialchars($project['description']); ?></textarea>
                
                <div class="project-meta editable-meta">
                    <div class="meta-item">
                        <label class="meta-label">Categoria:</label>
                        <select class="meta-value editable-input" id="projectCategory">
                            <option value="">Selecione uma categoria</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?php echo $cat['id']; ?>" 
                                        <?php echo ($project['category_id'] == $cat['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($cat['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="meta-item" style="display: none;">
                        <label class="meta-label">Slug:</label>
                        <input type="text" 
                               class="meta-value editable-input" 
                               id="projectSlug"
                               value="<?php echo htmlspecialchars($project['slug']); ?>"
                               placeholder="projeto-slug"
                               readonly>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <main>
        <!-- Project Gallery (Editable) -->
        <section class="works-section project-gallery-section">
            <div class="gallery-header">
                <h2>Imagens do Projeto</h2>
                <button class="btn-add-images" id="btnAddImages">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="12" y1="5" x2="12" y2="19"/>
                        <line x1="5" y1="12" x2="19" y2="12"/>
                    </svg>
                    Adicionar Imagens
                </button>
                <input type="file" id="galleryImagesInput" accept="image/*" multiple style="display: none;">
            </div>
            
            <div class="works-grid" id="projectGallery" data-columns="<?php echo $galleryColumns; ?>" data-gap="<?php echo $galleryGap; ?>">
                <?php foreach ($projectImages as $image): ?>
                    <article class="work-item editable-image" 
                             data-id="<?php echo htmlspecialchars($image['id'] ?? ''); ?>"
                             data-title="<?php echo htmlspecialchars($image['title'] ?? ''); ?>"
                             data-desc="<?php echo htmlspecialchars($image['description'] ?? ''); ?>">
                        <div class="img-container">
                            <img src="<?php echo htmlspecialchars($image['image_path']); ?>" 
                                 alt="<?php echo htmlspecialchars($image['title'] ?: $project['title']); ?>" 
                                 style="object-position: center <?php echo htmlspecialchars($image['alignment'] ?? 'center'); ?>;"
                                 loading="lazy">
                            
                            <!-- Edit Controls -->
                            <div class="image-controls">
                                <!-- Drag Handle -->
                                <button class="btn-drag-handle" title="Arrastar para reordenar">
                                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <line x1="9" y1="6" x2="9" y2="18"/>
                                        <line x1="15" y1="6" x2="15" y2="18"/>
                                    </svg>
                                </button>
                                
                                <button class="btn-set-hero" 
                                        data-image-id="<?php echo $image['id']; ?>"
                                        title="Definir como Hero">
                                    <svg width="20" height="20" viewBox="0 0 24 24" fill="<?php echo ($project['hero_image_path'] === $image['image_path']) ? 'currentColor' : 'none'; ?>" stroke="currentColor" stroke-width="2">
                                        <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/>
                                    </svg>
                                </button>
                                <button class="btn-delete-image" 
                                        data-image-id="<?php echo $image['id']; ?>"
                                        title="Remover Imagem">
                                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <line x1="18" y1="6" x2="6" y2="18"/>
                                        <line x1="6" y1="6" x2="18" y2="18"/>
                                    </svg>
                                </button>
                            </div>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
            
            <?php if (count($projectImages) === 0): ?>
            <div class="empty-gallery">
                <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                    <rect x="3" y="3" width="18" height="18" rx="2" ry="2"/>
                    <circle cx="8.5" cy="8.5" r="1.5"/>
                    <polyline points="21 15 16 10 5 21"/>
                </svg>
                <p>Nenhuma imagem adicionada ainda</p>
                <button class="btn-add-images-empty" onclick="document.getElementById('btnAddImages').click()">
                    Adicionar Primeira Imagem
                </button>
            </div>
            <?php endif; ?>
        </section>

        <!-- Footer -->
        <?php include 'footer.php'; ?>
    </main>

    <!-- Hidden data for JavaScript -->
    <script>
        window.PROJECT_DATA = {
            id: <?php echo json_encode($project['id']); ?>,
            title: <?php echo json_encode($project['title']); ?>,
            description: <?php echo json_encode($project['description']); ?>,
            slug: <?php echo json_encode($project['slug']); ?>,
            category_id: <?php echo json_encode($project['category_id']); ?>,
            hero_image_path: <?php echo json_encode($project['hero_image_path']); ?>
        };
    </script>

    <!-- Lenis Smooth Scroll Library -->
    <script src="https://unpkg.com/@studio-freight/lenis@1.0.42/dist/lenis.min.js"></script>
    
    <!-- SortableJS for drag-and-drop reordering -->
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
    
    <!-- Edit Project Script (ES6 Module) -->
    <script type="module" src="js/edit-project.js"></script>
</body>

</html>
