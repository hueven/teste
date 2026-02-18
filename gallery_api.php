<?php
/**
 * Wide Studio - Gallery API
 * Gerencia operações da galeria (CRUD de works)
 */

// ===========================================
// LÓGICA DE UPLOAD NO INÍCIO PARA EVITAR ERROS
// ===========================================

// Iniciar buffer de saída para evitar que warnings quebrem o JSON
ob_start();

// DEBUG: Log POST and FILES
file_put_contents(__DIR__ . '/debug_api_input.txt', 
    date('Y-m-d H:i:s') . "\n" .
    "POST: " . print_r($_POST, true) . "\n" .
    "FILES: " . print_r($_FILES, true) . "\n" .
    "Input Raw: " . file_get_contents('php://input') . "\n" .
    "-----------------------------------\n", 
    FILE_APPEND
);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'upload_collection_image') {
    // Configurar tratamento de erro para JSON
    ini_set('display_errors', 0);
    error_reporting(E_ALL);
    require_once 'config.php';
    $pdo = getDbConnection();
    
    try {
        $collectionId = $_POST['collection_id'] ?? 0;
        $setPrimary = $_POST['set_primary'] ?? '1';
        
        if (!isset($_FILES['image'])) {
            throw new Exception('Nenhuma imagem enviada');
        }
        
        // Função auxiliar local para sanitização (já existe no arquivo mas precisamos dela aqui em cima)
        
        // Buscar título da coleção
        $stmt = $pdo->prepare("SELECT title FROM collections WHERE id = ?");
        $stmt->execute([$collectionId]);
        $collection = $stmt->fetch();
        
        if (!$collection) {
            throw new Exception('Coleção não encontrada');
        }
        
        // Sanitizar nome
        $sanitizedName = sanitizeCollectionName($collection['title']);
        $collectionDir = __DIR__ . '/images/collections/' . $sanitizedName . '/';
        
        // Criar pasta se não existir
        if (!is_dir($collectionDir)) {
            if (!mkdir($collectionDir, 0755, true)) {
                throw new Exception('Falha ao criar pasta da coleção');
            }
        }
        
        $file = $_FILES['image'];
        $originalExt = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $newName = time() . '_' . uniqid() . '.' . $originalExt;
        
        // Caminhos
        $physicalPath = $collectionDir . $newName;
        $webPath = 'images/collections/' . $sanitizedName . '/' . $newName;
        
        if (move_uploaded_file($file['tmp_name'], $physicalPath)) {
            // Remover flag is_primary de outras imagens se for primária
            if ($setPrimary == '1') {
                $stmt = $pdo->prepare("UPDATE collection_images SET is_primary = 0 WHERE collection_id = ?");
                $stmt->execute([$collectionId]);
            }
            
            // Obter próximo display_order
            $stmt = $pdo->prepare("SELECT MAX(display_order) FROM collection_images WHERE collection_id = ?");
            $stmt->execute([$collectionId]);
            $maxOrder = $stmt->fetchColumn();
            $nextOrder = $maxOrder ? $maxOrder + 1 : 1;
            
            // Inserir nova imagem
            $stmt = $pdo->prepare("INSERT INTO collection_images (collection_id, image_path, is_primary, display_order) VALUES (?, ?, ?, ?)");
            $stmt->execute([$collectionId, $webPath, $setPrimary, $nextOrder]);
            
            // Resposta JSON manual e EXIT para não processar o resto do arquivo
            ob_clean(); // Limpa qualquer lixo anterior
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'image_path' => $webPath,
                'image_id' => $pdo->lastInsertId()
            ]);
            exit;
        } else {
            throw new Exception('Erro ao salvar arquivo');
        }
        
    } catch (Exception $e) {
        ob_clean(); // Limpa output anterior
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        exit;
    }
}

/**
 * Sanitiza o nome da coleção para uso como nome de pasta
 * Remove acentos, caracteres especiais, converte espaços em underscores
 * @param string $name Nome da coleção
 * @return string Nome sanitizado
 */
function sanitizeCollectionName($name) {
    // Converter para minúsculas
    $sanitized = mb_strtolower($name, 'UTF-8');
    
    // Remover acentos
    $sanitized = preg_replace('/[áàãâä]/u', 'a', $sanitized);
    $sanitized = preg_replace('/[éèêë]/u', 'e', $sanitized);
    $sanitized = preg_replace('/[íìîï]/u', 'i', $sanitized);
    $sanitized = preg_replace('/[óòõôö]/u', 'o', $sanitized);
    $sanitized = preg_replace('/[úùûü]/u', 'u', $sanitized);
    $sanitized = preg_replace('/[ç]/u', 'c', $sanitized);
    
    // Substituir caracteres não alfanuméricos por hífens
    $sanitized = preg_replace('/[^a-z0-9]/', '-', $sanitized);
    
    // Remover hífens duplicados
    $sanitized = preg_replace('/-+/', '-', $sanitized);
    
    // Remover hífens do início e fim
    $sanitized = trim($sanitized, '-');
    
    return $sanitized;
}

/**
 * Conta o número de imagens na pasta de uma coleção
 * @param string $collectionTitle Título da coleção
 * @return int Número de imagens encontradas
 */
function countCollectionImages($collectionTitle) {
    if (empty($collectionTitle)) return 0;
    
    $folderName = sanitizeCollectionName($collectionTitle);
    $dirPath = __DIR__ . '/images/collections/' . $folderName;
    
    if (!is_dir($dirPath)) return 0;
    
    $count = 0;
    $files = scandir($dirPath);
    
    foreach ($files as $file) {
        if ($file === '.' || $file === '..') continue;
        
        $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
        if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'gif'])) {
            $count++;
        }
    }
    
    return $count;
}

session_start();
require 'config.php';

// Verifica login
if (!isset($_SESSION['user_id'])) {
    jsonResponse(false, ['error' => 'Não autorizado']);
}

$pdo = getDbConnection();

/**
 * Obtém a ação da requisição
 */
function getAction() {
    $action = $_POST['action'] ?? $_GET['action'] ?? '';
    if (empty($action)) {
        $input = json_decode(file_get_contents('php://input'), true);
        $action = $input['action'] ?? '';
    }
    return $action;
}

$action = getAction();
$input = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST' || $_SERVER['REQUEST_METHOD'] === 'PUT') {
    $rawInput = file_get_contents('php://input');
    $input = json_decode($rawInput, true) ?? [];
}

try {
    // =================================
    // GET - Listar todos os dados
    // =================================
    if ($_SERVER['REQUEST_METHOD'] === 'GET' && empty($action)) {
        // ONLY return general data if NO specific action is requested
        // This allows specific GET endpoints like get_collection_detail to work
        
        // Galeria
        $works = [];
        try {
            $stmt = $pdo->query("SELECT * FROM works ORDER BY display_order ASC, created_at DESC");
            if ($stmt) $works = $stmt->fetchAll();
        } catch (Exception $e) {
            $worksError = $e->getMessage();
        }

        // Serviços
        $servicesData = [];
        try {
            $stmt3 = $pdo->query("SELECT * FROM services ORDER BY display_order ASC");
            if ($stmt3) $servicesData = $stmt3->fetchAll();
        } catch (Exception $e) {}

        // Menu
        $menuData = [];
        try {
            $stmtM = $pdo->query("SELECT * FROM menu_items ORDER BY display_order ASC");
            if ($stmtM) $menuData = $stmtM->fetchAll();
        } catch (Exception $e) {}

        // Tipos de Imagem
        $imageTypes = [];
        try {
            $stmtT = $pdo->query("SELECT * FROM image_types ORDER BY display_order ASC");
            if ($stmtT) $imageTypes = $stmtT->fetchAll();
        } catch (Exception $e) {}

        // Projetos
        $projectsData = [];
        try {
            $stmtP = $pdo->query("
                SELECT p.*,
                       pc.name as category_name,
                       (SELECT COUNT(*) FROM works WHERE project_id = p.id) as image_count
                FROM projects p 
                LEFT JOIN project_categories pc ON p.category_id = pc.id
                ORDER BY p.display_order ASC, p.created_at DESC
            ");
            if ($stmtP) $projectsData = $stmtP->fetchAll();
        } catch (Exception $e) {}

        // Categorias de Projetos
        $categoriesData = [];
        try {
            $stmtC = $pdo->query("
                SELECT c.*,
                       (SELECT COUNT(*) FROM projects WHERE category_id = c.id) as project_count
                FROM project_categories c 
                ORDER BY c.display_order ASC, c.name ASC
            ");
            if ($stmtC) $categoriesData = $stmtC->fetchAll();
        } catch (Exception $e) {}

        // Coleções (com imagem principal)
        $collectionsData = [];
        try {
            $stmtCol = $pdo->query("
                SELECT c.*,
                       (SELECT COUNT(*) FROM products WHERE collection_id = c.id) as product_count,
                       ci.image_path as primary_image_path
                FROM collections c 
                LEFT JOIN collection_images ci ON c.id = ci.collection_id AND ci.is_primary = 1
                ORDER BY c.display_order ASC, c.created_at DESC
            ");
            if ($stmtCol) {
                $collectionsData = $stmtCol->fetchAll();
                // Adicionar contagem de imagens da pasta
                foreach ($collectionsData as &$collection) {
                    $collection['image_count'] = countCollectionImages($collection['title']);
                }
            }
        } catch (Exception $e) {
            $collectionsError = $e->getMessage();
        }


        $settingsKeys = [
            'hero_text', 'contact_title', 'contact_subtitle',
            'gallery_color_mode', 'site_title', 'services_title',
            'hero_font_size', 'hero_font_weight', 'gallery_columns', 'gallery_gap',
            'gallery_border_radius',
            'contact_email', 'contact_phone', 'contact_instagram',
            'footer_text',
            'about_text',
            'logo_text', 'logo_font_size', 'logo_font_weight', 'logo_letter_spacing', 'logo_font_family'
        ];
        $settings = getSettings($settingsKeys);

        // Usa valores com defaults
        $hero = $settings['hero_text'] ?? '';
        $contactTitle = $settings['contact_title'] ?? '';
        $contactSubtitle = $settings['contact_subtitle'] ?? '';
        $galleryColorMode = $settings['gallery_color_mode'] ?? 'grayscale';
        $siteTitle = $settings['site_title'] ?? 'Wide Studio - Art Direction';
        $servicesTitle = $settings['services_title'] ?? 'O que fazemos';

        $heroFontSize = $settings['hero_font_size'] ?? 'medium';
        $heroFontWeight = $settings['hero_font_weight'] ?? 'light';
        $galleryColumns = $settings['gallery_columns'] ?? '4';
        $galleryGap = $settings['gallery_gap'] ?? '10';
        $galleryBorderRadius = $settings['gallery_border_radius'] ?? '8';
        $contactEmail = $settings['contact_email'] ?? 'contato@exemplo.com';
        $contactPhone = $settings['contact_phone'] ?? '(00) 0 0000-0000';
        $contactInstagram = $settings['contact_instagram'] ?? '@seuperfil';
        $footerText = $settings['footer_text'] ?? '© Gustavo Starke Fotografia e Vídeo de Arquitetura';
        $logoText = $settings['logo_text'] ?? 'Gustavo Starke';
        $logoFontSize = $settings['logo_font_size'] ?? '20';
        $logoFontWeight = $settings['logo_font_weight'] ?? '600';
        $logoFontWeight = $settings['logo_font_weight'] ?? '600';
        $logoLetterSpacing = $settings['logo_letter_spacing'] ?? '0.02';
        $logoFontFamily = $settings['logo_font_family'] ?? 'Inter';

        jsonResponse(true, [
            'data' => $works,
            'hero_text' => $hero,
            'services' => $servicesData,
            'menu' => $menuData,
            'image_types' => $imageTypes,
            'projects' => $projectsData,
            'categories' => $categoriesData,
            'collections' => $collectionsData,
            'contact_title' => $contactTitle,
            'contact_subtitle' => $contactSubtitle,
            'gallery_color_mode' => $galleryColorMode,
            'site_title' => $siteTitle,
            'services_title' => $servicesTitle,

            'hero_font_size' => $heroFontSize,
            'hero_font_weight' => $heroFontWeight,
            'gallery_columns' => $galleryColumns,
            'gallery_gap' => $galleryGap,
            'gallery_border_radius' => $galleryBorderRadius,
            'contact_email' => $contactEmail,
            'contact_phone' => $contactPhone,
            'contact_instagram' => $contactInstagram,
            'footer_text' => $footerText,
            'logo_text' => $logoText,
            'logo_font_size' => $logoFontSize,
            'logo_font_weight' => $logoFontWeight,
            'logo_letter_spacing' => $logoLetterSpacing,
            'collections_error' => $collectionsError ?? null,
            'works_error' => $worksError ?? null
        ]);
    }

    // =================================
    // UPLOAD - Enviar imagens
    // =================================
    if ($action === 'upload') {
        if (!isset($_FILES['images'])) {
            throw new Exception('Nenhum arquivo enviado');
        }

        $uploaded = [];
        $files = $_FILES['images'];
        
        // Garante que pasta existe
        if (!is_dir(UPLOAD_DIR)) {
            mkdir(UPLOAD_DIR, 0755, true);
        }

        for ($i = 0; $i < count($files['name']); $i++) {
            $tmp = $files['tmp_name'][$i];
            $name = $files['name'][$i];
            
            if (!isAllowedExtension($name)) {
                continue; // Pula arquivos não permitidos
            }
            
            $new_name = generateUniqueFilename($name);
            $db_path = UPLOAD_DIR . $new_name;

            if (move_uploaded_file($tmp, UPLOAD_DIR . $new_name)) {
                $stmt = $pdo->prepare("INSERT INTO works (image_path, title, display_order) VALUES (?, '', 999)");
                $stmt->execute([$db_path]);
                
                $uploaded[] = [
                    'id' => $pdo->lastInsertId(),
                    'image_path' => $db_path
                ];
            }
        }
        
        jsonResponse(true, ['uploaded' => $uploaded]);
    }

    // =================================
    // SAVE_ALL - Salvar galeria
    // =================================
    if ($action === 'save_all') {
        $input = json_decode(file_get_contents('php://input'), true);
        $items = $input['items'] ?? [];

        $pdo->beginTransaction();

        foreach ($items as $index => $item) {
            $align = $item['alignment'] ?? 'center';
            $typeId = !empty($item['type_id']) ? $item['type_id'] : null;
            $stmt = $pdo->prepare("UPDATE works SET title = ?, description = ?, year = ?, display_order = ?, alignment = ?, type_id = ? WHERE id = ?");
            $stmt->execute([
                $item['title'],
                $item['description'],
                $item['year'],
                $index,
                $align,
                $typeId,
                $item['id']
            ]);
        }

        $pdo->commit();
        jsonResponse(true);
    }

    // =================================
    // DELETE - Remover item
    // =================================
    if ($action === 'delete') {
        $id = $_POST['id'] ?? 0;
        
        $stmt = $pdo->prepare("SELECT image_path FROM works WHERE id = ?");
        $stmt->execute([$id]);
        $row = $stmt->fetch();

        if ($row) {
            if (file_exists($row['image_path'])) {
                unlink($row['image_path']);
            }
            
            $del = $pdo->prepare("DELETE FROM works WHERE id = ?");
            $del->execute([$id]);
            
            jsonResponse(true);
        } else {
            throw new Exception('Item não encontrado');
        }
    }

    // =================================
    // REPLACE - Trocar imagem
    // =================================
    if ($action === 'replace') {
        $id = $_POST['id'] ?? 0;
        
        if (!isset($_FILES['image'])) {
            throw new Exception('Nenhuma imagem enviada');
        }

        $stmt = $pdo->prepare("SELECT image_path FROM works WHERE id = ?");
        $stmt->execute([$id]);
        $row = $stmt->fetch();

        if (!$row) throw new Exception('Item não encontrado');

        $file = $_FILES['image'];
        
        if (!isAllowedExtension($file['name'])) {
            throw new Exception('Tipo de arquivo não permitido');
        }
        
        $new_name = generateUniqueFilename($file['name']);
        $db_path = UPLOAD_DIR . $new_name;

        if (move_uploaded_file($file['tmp_name'], UPLOAD_DIR . $new_name)) {
            // Deleta imagem antiga
            if (file_exists($row['image_path'])) {
                unlink($row['image_path']);
            }

            $update = $pdo->prepare("UPDATE works SET image_path = ? WHERE id = ?");
            $update->execute([$db_path, $id]);

            jsonResponse(true, ['new_path' => $db_path]);
        } else {
            throw new Exception('Erro ao salvar arquivo');
        }
    }

    // =================================
    // LIKE - Curtir item
    // =================================
    if ($action === 'like') {
        $id = $_POST['id'] ?? 0;
        $cookie_name = 'liked_' . $id;

        if (isset($_COOKIE[$cookie_name])) {
            jsonResponse(false, ['error' => 'Você já curtiu hoje.']);
        }

        $stmt = $pdo->prepare("UPDATE works SET likes = likes + 1 WHERE id = ?");
        $stmt->execute([$id]);

        $stmt = $pdo->prepare("SELECT likes FROM works WHERE id = ?");
        $stmt->execute([$id]);
        $new_likes = $stmt->fetchColumn();

        setcookie($cookie_name, 'true', time() + 86400, "/");

        jsonResponse(true, ['likes' => $new_likes]);
    }

    // =================================
    // SAVE_HERO - Salvar texto principal e tipografia (UNIFICADO)
    // =================================
    if ($action === 'save_hero') {
        $input = json_decode(file_get_contents('php://input'), true);
        
        $text = $input['hero_text'] ?? '';
        $fontSize = $input['hero_font_size'] ?? 'medium';
        $fontWeight = $input['hero_font_weight'] ?? 'light';

        $pdo->beginTransaction();
        try {
            // Salvar texto
            $stmt = $pdo->prepare("INSERT INTO settings (setting_key, setting_value) VALUES ('hero_text', ?) ON DUPLICATE KEY UPDATE setting_value = ?");
            $stmt->execute([$text, $text]);

            // Salvar tamanho
            $stmt1 = $pdo->prepare("INSERT INTO settings (setting_key, setting_value) VALUES ('hero_font_size', ?) ON DUPLICATE KEY UPDATE setting_value = ?");
            $stmt1->execute([$fontSize, $fontSize]);

            // Salvar peso/espessura
            $stmt2 = $pdo->prepare("INSERT INTO settings (setting_key, setting_value) VALUES ('hero_font_weight', ?) ON DUPLICATE KEY UPDATE setting_value = ?");
            $stmt2->execute([$fontWeight, $fontWeight]);

            $pdo->commit();
            jsonResponse(true);
        } catch (Exception $e) {
            $pdo->rollBack();
            jsonResponse(false, ['error' => $e->getMessage()]);
        }
    }

    // =================================
    // SAVE_CONTACT - Salvar textos de contato
    // =================================
    if ($action === 'save_contact') {
        $input = json_decode(file_get_contents('php://input'), true);
        $title = $input['title'] ?? '';
        $subtitle = $input['subtitle'] ?? '';

        $stmt1 = $pdo->prepare("INSERT INTO settings (setting_key, setting_value) VALUES ('contact_title', ?) ON DUPLICATE KEY UPDATE setting_value = ?");
        $stmt1->execute([$title, $title]);

        $stmt2 = $pdo->prepare("INSERT INTO settings (setting_key, setting_value) VALUES ('contact_subtitle', ?) ON DUPLICATE KEY UPDATE setting_value = ?");
        $stmt2->execute([$subtitle, $subtitle]);
        
        jsonResponse(true);
    }

    // =================================
    // SAVE_GALLERY_SETTINGS - Salvar configurações da galeria
    // =================================
    if ($action === 'save_gallery_settings') {
        $input = json_decode(file_get_contents('php://input'), true);
        $colorMode = $input['color_mode'] ?? 'grayscale';
        
        // Validar valor
        if (!in_array($colorMode, ['grayscale', 'color'])) {
            $colorMode = 'grayscale';
        }

        $stmt = $pdo->prepare("INSERT INTO settings (setting_key, setting_value) VALUES ('gallery_color_mode', ?) ON DUPLICATE KEY UPDATE setting_value = ?");
        $stmt->execute([$colorMode, $colorMode]);
        
        jsonResponse(true);
    }

    // =================================
    // SAVE_GALLERY_COLUMNS - Salvar número de colunas da galeria
    // =================================
    if ($action === 'save_gallery_columns') {
        $input = json_decode(file_get_contents('php://input'), true);
        $columns = $input['columns'] ?? '4';
        
        // Validar valor (mínimo 3, máximo 6)
        $columns = intval($columns);
        if ($columns < 3 || $columns > 6) {
            $columns = 4;
        }
        $columns = strval($columns);

        $stmt = $pdo->prepare("INSERT INTO settings (setting_key, setting_value) VALUES ('gallery_columns', ?) ON DUPLICATE KEY UPDATE setting_value = ?");
        $stmt->execute([$columns, $columns]);
        
        jsonResponse(true);
    }

    // =================================
    // SAVE_GALLERY_GAP - Salvar espaçamento da galeria
    // =================================
    if ($action === 'save_gallery_gap') {
        $input = json_decode(file_get_contents('php://input'), true);
        $gap = $input['gap'] ?? '10';
        
        // Validar valores permitidos (2, 5, 10, 12, 15)
        $validGaps = ['2', '5', '10', '12', '15'];
        if (!in_array($gap, $validGaps)) {
            $gap = '10';
        }

        $stmt = $pdo->prepare("INSERT INTO settings (setting_key, setting_value) VALUES ('gallery_gap', ?) ON DUPLICATE KEY UPDATE setting_value = ?");
        $stmt->execute([$gap, $gap]);
        
        jsonResponse(true);
    }

    // =================================
    // SAVE_GALLERY_BORDER_RADIUS - Salvar border radius da galeria
    // =================================
    if ($action === 'save_gallery_border_radius') {
        $input = json_decode(file_get_contents('php://input'), true);
        $borderRadius = $input['borderRadius'] ?? '8';
        
        // Validar valores permitidos (0, 8, 16, 24)
        $validRadii = ['0', '8', '16', '24'];
        if (!in_array($borderRadius, $validRadii)) {
            $borderRadius = '8';
        }

        $stmt = $pdo->prepare("INSERT INTO settings (setting_key, setting_value) VALUES ('gallery_border_radius', ?) ON DUPLICATE KEY UPDATE setting_value = ?");
        $stmt->execute([$borderRadius, $borderRadius]);
        
        jsonResponse(true);
    }
    
    // =================================
    // SAVE_ABOUT - Salvar texto sobre
    // =================================
    if ($action === 'save_about') {
        $input = json_decode(file_get_contents('php://input'), true);
        $text = $input['text'] ?? '';

        $stmt = $pdo->prepare("INSERT INTO settings (setting_key, setting_value) VALUES ('about_text', ?) ON DUPLICATE KEY UPDATE setting_value = ?");
        $stmt->execute([$text, $text]);
        
        jsonResponse(true);
    }

    // =================================
    // SAVE_SITE_TITLE - Salvar título do site
    // =================================
    if ($action === 'save_site_title') {
        $input = json_decode(file_get_contents('php://input'), true);
        $title = $input['title'] ?? 'Wide Studio - Art Direction';

        $stmt = $pdo->prepare("INSERT INTO settings (setting_key, setting_value) VALUES ('site_title', ?) ON DUPLICATE KEY UPDATE setting_value = ?");
        $stmt->execute([$title, $title]);
        
        jsonResponse(true);
    }

    // =================================
    // SAVE_SERVICES_TITLE - Salvar título da página de serviços
    // =================================
    if ($action === 'save_services_title') {
        $input = json_decode(file_get_contents('php://input'), true);
        $title = $input['title'] ?? 'O que fazemos';

        $stmt = $pdo->prepare("INSERT INTO settings (setting_key, setting_value) VALUES ('services_title', ?) ON DUPLICATE KEY UPDATE setting_value = ?");
        $stmt->execute([$title, $title]);
        
        jsonResponse(true);
    }

    // =================================
    // SAVE_CONTACT_INFO - Salvar informações de contato
    // =================================
    if ($action === 'save_contact_info') {
        $input = json_decode(file_get_contents('php://input'), true);
        $email = $input['email'] ?? '';
        $phone = $input['phone'] ?? '';
        $instagram = $input['instagram'] ?? '';

        // Salvar email
        $stmt1 = $pdo->prepare("INSERT INTO settings (setting_key, setting_value) VALUES ('contact_email', ?) ON DUPLICATE KEY UPDATE setting_value = ?");
        $stmt1->execute([$email, $email]);

        // Salvar telefone
        $stmt2 = $pdo->prepare("INSERT INTO settings (setting_key, setting_value) VALUES ('contact_phone', ?) ON DUPLICATE KEY UPDATE setting_value = ?");
        $stmt2->execute([$phone, $phone]);

        // Salvar instagram
        $stmt3 = $pdo->prepare("INSERT INTO settings (setting_key, setting_value) VALUES ('contact_instagram', ?) ON DUPLICATE KEY UPDATE setting_value = ?");
        $stmt3->execute([$instagram, $instagram]);
        
        jsonResponse(true);
    }

    // =================================
    // SAVE_FOOTER_TEXT - Salvar texto do footer
    // =================================
    if ($action === 'save_footer_text') {
        $input = json_decode(file_get_contents('php://input'), true);
        $text = $input['text'] ?? '';

        $stmt = $pdo->prepare("INSERT INTO settings (setting_key, setting_value) VALUES ('footer_text', ?) ON DUPLICATE KEY UPDATE setting_value = ?");
        $stmt->execute([$text, $text]);
        
        jsonResponse(true);
    }

    // =================================
    // SAVE_LOGO_SETTINGS - Salvar configurações da logo
    // =================================
    if ($action === 'save_logo_settings') {
        $input = json_decode(file_get_contents('php://input'), true);
        $text = $input['text'] ?? 'Gustavo Starke';
        $fontSize = $input['font_size'] ?? '20';
        $fontWeight = $input['font_weight'] ?? '600';
        $letterSpacing = $input['letter_spacing'] ?? '0.02';

        $stmt1 = $pdo->prepare("INSERT INTO settings (setting_key, setting_value) VALUES ('logo_text', ?) ON DUPLICATE KEY UPDATE setting_value = ?");
        $stmt1->execute([$text, $text]);

        $stmt2 = $pdo->prepare("INSERT INTO settings (setting_key, setting_value) VALUES ('logo_font_size', ?) ON DUPLICATE KEY UPDATE setting_value = ?");
        $stmt2->execute([$fontSize, $fontSize]);

        $stmt3 = $pdo->prepare("INSERT INTO settings (setting_key, setting_value) VALUES ('logo_font_weight', ?) ON DUPLICATE KEY UPDATE setting_value = ?");
        $stmt3->execute([$fontWeight, $fontWeight]);

        $stmt4 = $pdo->prepare("INSERT INTO settings (setting_key, setting_value) VALUES ('logo_letter_spacing', ?) ON DUPLICATE KEY UPDATE setting_value = ?");
        $stmt4->execute([$letterSpacing, $letterSpacing]);

        if (isset($input['font_family'])) {
            $fontFamily = $input['font_family'];
            $stmt5 = $pdo->prepare("INSERT INTO settings (setting_key, setting_value) VALUES ('logo_font_family', ?) ON DUPLICATE KEY UPDATE setting_value = ?");
            $stmt5->execute([$fontFamily, $fontFamily]);
        }
        
        jsonResponse(true);
    }
    // =================================
    // SERVICES - CRUD
    // =================================
    if ($action === 'save_services') {
        $input = json_decode(file_get_contents('php://input'), true);
        $items = $input['items'] ?? [];
        
        $pdo->beginTransaction();
        try {
            foreach ($items as $item) {
                $stmt = $pdo->prepare("UPDATE services SET title = ?, description = ?, display_order = ? WHERE id = ?");
                $order = $item['display_order'] ?? 0;
                // image_path é gerenciado separadamente via upload/replace/delete endpoints
                $stmt->execute([$item['title'], $item['description'], $order, $item['id']]);
            }
            $pdo->commit();
            jsonResponse(true);
        } catch (Exception $e) {
            $pdo->rollBack();
            jsonResponse(false, ['error' => $e->getMessage()]);
        }
    }

    if ($action === 'add_service') {
        $stmt = $pdo->query("SELECT MAX(display_order) FROM services");
        $max = $stmt->fetchColumn();
        $next = $max ? $max + 1 : 1;

        $stmt = $pdo->prepare("INSERT INTO services (title, description, display_order) VALUES ('Novo Serviço', 'Descrição do novo serviço.', ?)");
        $stmt->execute([$next]);
        jsonResponse(true, ['id' => $pdo->lastInsertId()]);
    }

    if ($action === 'delete_service') {
        $input = json_decode(file_get_contents('php://input'), true);
        $id = $input['id'] ?? 0;
        $stmt = $pdo->prepare("DELETE FROM services WHERE id = ?");
        $stmt->execute([$id]);
        jsonResponse(true);
    }

    // =================================
    // SERVICE IMAGE UPLOAD
    // =================================
    if ($action === 'upload_service_image') {
        $serviceId = $_POST['service_id'] ?? 0;
        
        if (!isset($_FILES['image'])) {
            throw new Exception('Nenhuma imagem/vídeo enviado');
        }

        // Criar pasta se não existir
        $serviceDir = 'imagens/services/';
        if (!is_dir($serviceDir)) {
            mkdir($serviceDir, 0755, true);
        }

        $file = $_FILES['image'];
        
        if (!isAllowedExtension($file['name'])) {
            throw new Exception('Tipo de arquivo não permitido');
        }
        
        // Manter extensão original (importante para vídeos)
        $originalExt = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $newName = 'service_' . $serviceId . '_' . time() . '.' . $originalExt;
        $imagePath = $serviceDir . $newName;

        if (move_uploaded_file($file['tmp_name'], $imagePath)) {
            $stmt = $pdo->prepare("UPDATE services SET image_path = ? WHERE id = ?");
            $stmt->execute([$imagePath, $serviceId]);
            jsonResponse(true, ['image_path' => $imagePath]);
        } else {
            throw new Exception('Erro ao salvar arquivo');
        }
    }

    // =================================
    // SERVICE IMAGE REPLACE
    // =================================
    if ($action === 'replace_service_image') {
        $serviceId = $_POST['service_id'] ?? 0;
        
        if (!isset($_FILES['image'])) {
            throw new Exception('Nenhuma imagem/vídeo enviado');
        }

        // Buscar imagem antiga
        $stmt = $pdo->prepare("SELECT image_path FROM services WHERE id = ?");
        $stmt->execute([$serviceId]);
        $oldImagePath = $stmt->fetchColumn();

        // Deletar imagem antiga se existir
        if ($oldImagePath && file_exists($oldImagePath)) {
            unlink($oldImagePath);
        }

        // Criar pasta se não existir
        $serviceDir = 'imagens/services/';
        if (!is_dir($serviceDir)) {
            mkdir($serviceDir, 0755, true);
        }

        $file = $_FILES['image'];
        
        if (!isAllowedExtension($file['name'])) {
            throw new Exception('Tipo de arquivo não permitido');
        }
        
        // Manter extensão original (importante para vídeos)
        $originalExt = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $newName = 'service_' . $serviceId . '_' . time() . '.' . $originalExt;
        $imagePath = $serviceDir . $newName;

        if (move_uploaded_file($file['tmp_name'], $imagePath)) {
            $stmt = $pdo->prepare("UPDATE services SET image_path = ? WHERE id = ?");
            $stmt->execute([$imagePath, $serviceId]);
            jsonResponse(true, ['image_path' => $imagePath]);
        } else {
            throw new Exception('Erro ao salvar arquivo');
        }
    }

    // =================================
    // SERVICE IMAGE DELETE
    // =================================
    if ($action === 'delete_service_image') {
        $input = json_decode(file_get_contents('php://input'), true);
        $serviceId = $input['service_id'] ?? 0;
        
        // Buscar caminho da imagem
        $stmt = $pdo->prepare("SELECT image_path FROM services WHERE id = ?");
        $stmt->execute([$serviceId]);
        $imagePath = $stmt->fetchColumn();
        
        // Deletar arquivo
        if ($imagePath && file_exists($imagePath)) {
            unlink($imagePath);
        }
        
        // Limpar banco
        $stmt = $pdo->prepare("UPDATE services SET image_path = NULL WHERE id = ?");
        $stmt->execute([$serviceId]);
        
        jsonResponse(true);
    }

    // =================================
    // MENU - CRUD
    // =================================
    if ($action === 'add_menu_item') {
        $stmt = $pdo->query("SELECT MAX(display_order) FROM menu_items");
        $max = $stmt->fetchColumn();
        $next = $max ? $max + 1 : 1;

        $stmt = $pdo->prepare("INSERT INTO menu_items (title, link_url, display_order) VALUES ('Novo Link', '#', ?)");
        $stmt->execute([$next]);
        jsonResponse(true, ['id' => $pdo->lastInsertId()]);
    }

    if ($action === 'delete_menu_item') {
        $input = json_decode(file_get_contents('php://input'), true);
        $id = $input['id'] ?? 0;
        $stmt = $pdo->prepare("DELETE FROM menu_items WHERE id = ?");
        $stmt->execute([$id]);
        jsonResponse(true);
    }

    if ($action === 'save_menu') {
        $input = json_decode(file_get_contents('php://input'), true);
        $items = $input['items'] ?? [];
        
        $pdo->beginTransaction();
        try {
            foreach ($items as $item) {
                $stmt = $pdo->prepare("UPDATE menu_items SET title = ?, link_url = ?, display_order = ? WHERE id = ?");
                $order = $item['display_order'] ?? 0;
                $stmt->execute([$item['title'], $item['link_url'], $order, $item['id']]);
            }
            $pdo->commit();
            jsonResponse(true);
        } catch (Exception $e) {
            $pdo->rollBack();
            jsonResponse(false, ['error' => $e->getMessage()]);
        }
    }

    // =================================
    // IMAGE TYPES - CRUD
    // =================================
    if ($action === 'add_image_type') {
        $stmt = $pdo->query("SELECT MAX(display_order) FROM image_types");
        $max = $stmt->fetchColumn();
        $next = $max ? $max + 1 : 1;

        $stmt = $pdo->prepare("INSERT INTO image_types (name, display_order) VALUES ('Novo Tipo', ?)");
        $stmt->execute([$next]);
        jsonResponse(true, ['id' => $pdo->lastInsertId()]);
    }

    if ($action === 'delete_image_type') {
        $input = json_decode(file_get_contents('php://input'), true);
        $id = $input['id'] ?? 0;
        
        // Remove o tipo das imagens associadas
        $stmt = $pdo->prepare("UPDATE works SET type_id = NULL WHERE type_id = ?");
        $stmt->execute([$id]);
        
        // Deleta o tipo
        $stmt = $pdo->prepare("DELETE FROM image_types WHERE id = ?");
        $stmt->execute([$id]);
        jsonResponse(true);
    }

    if ($action === 'save_image_types') {
        $input = json_decode(file_get_contents('php://input'), true);
        $items = $input['items'] ?? [];
        
        $pdo->beginTransaction();
        try {
            foreach ($items as $item) {
                $stmt = $pdo->prepare("UPDATE image_types SET name = ?, display_order = ? WHERE id = ?");
                $order = $item['display_order'] ?? 0;
                $stmt->execute([$item['name'], $order, $item['id']]);
            }
            $pdo->commit();
            jsonResponse(true);
        } catch (Exception $e) {
            $pdo->rollBack();
            jsonResponse(false, ['error' => $e->getMessage()]);
        }
    }


    // =================================
    // PROJECTS - CRUD
    // =================================
    
    // GET ALL PROJECTS
    if ($action === 'get_projects') {
        $stmt = $pdo->query("
            SELECT p.*,
                   pc.name as category_name,
                   (SELECT COUNT(*) FROM works WHERE project_id = p.id) as image_count
            FROM projects p 
            LEFT JOIN project_categories pc ON p.category_id = pc.id
            ORDER BY p.display_order ASC, p.created_at DESC
        ");
        $projects = $stmt->fetchAll();
        jsonResponse(true, ['projects' => $projects]);
    }

    // GET SINGLE PROJECT BY ID
    if ($action === 'get_project') {
        $input = json_decode(file_get_contents('php://input'), true);
        $id = $input['id'] ?? 0;
        
        $stmt = $pdo->prepare("SELECT * FROM projects WHERE id = ?");
        $stmt->execute([$id]);
        $project = $stmt->fetch();
        
        if (!$project) {
            throw new Exception('Projeto não encontrado');
        }
        
        // Buscar imagens do projeto
        $stmt = $pdo->prepare("SELECT * FROM works WHERE project_id = ? ORDER BY display_order ASC");
        $stmt->execute([$id]);
        $images = $stmt->fetchAll();
        
        jsonResponse(true, ['project' => $project, 'images' => $images]);
    }

    // ADD PROJECT
    if ($action === 'add_project') {
        $input = json_decode(file_get_contents('php://input'), true);
        
        $title = $input['title'] ?? 'Novo Projeto';
        $description = $input['description'] ?? '';
        $category_id = $input['category_id'] ?? null;
        
        // Gerar slug único a partir do título
        $slug = generateSlug($title);
        
        // Verificar se slug já existe
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM projects WHERE slug = ?");
        $stmt->execute([$slug]);
        if ($stmt->fetchColumn() > 0) {
            // Adiciona timestamp para tornar único
            $slug .= '-' . time();
        }
        
        // Obter próximo display_order
        $stmt = $pdo->query("SELECT MAX(display_order) FROM projects");
        $maxOrder = $stmt->fetchColumn();
        $nextOrder = $maxOrder ? $maxOrder + 1 : 1;
        
        $stmt = $pdo->prepare("
            INSERT INTO projects (title, description, slug, category_id, display_order) 
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->execute([$title, $description, $slug, $category_id, $nextOrder]);
        
        jsonResponse(true, ['id' => $pdo->lastInsertId(), 'slug' => $slug]);
    }


    // ADD NEW COLLECTION
    if ($action === 'add_collection') {
        // Suportar tanto JSON quanto POST convencional
        $title = $input['title'] ?? $_POST['title'] ?? 'Nova Coleção';
        $description = $input['description'] ?? $_POST['description'] ?? '';
        $produced_by = $input['produced_by'] ?? $_POST['produced_by'] ?? '';
        $year = $input['year'] ?? $_POST['year'] ?? '';
        
        // Obter próximo display_order
        $stmt = $pdo->query("SELECT COALESCE(MAX(display_order), 0) + 1 as next_order FROM collections");
        $nextOrder = $stmt->fetchColumn();
        
        // Inserir coleção (pasta será criada ao salvar)
        $stmt = $pdo->prepare("
            INSERT INTO collections (title, description, produced_by, year, display_order) 
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->execute([$title, $description, $produced_by, $year, $nextOrder]);
        $collectionId = $pdo->lastInsertId();
        
        jsonResponse(true, ['id' => $collectionId]);
    }

    // UPDATE PROJECT
    if ($action === 'update_project') {
        $input = json_decode(file_get_contents('php://input'), true);
        
        $id = $input['id'] ?? 0;
        $title = $input['title'] ?? '';
        $description = $input['description'] ?? '';
        $category_id = $input['category_id'] ?? null;
        
        // Gerar slug automaticamente do título
        $slug = generateSlug($title);
        
        // Verificar se slug não está duplicado
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM projects WHERE slug = ? AND id != ?");
        $stmt->execute([$slug, $id]);
        if ($stmt->fetchColumn() > 0) {
            // Adiciona timestamp para tornar único
            $slug .= '-' . time();
        }
        
        // Verificar se hero_image_path foi enviado (inclusive null)
        if (array_key_exists('hero_image_path', $input)) {
            $heroImagePath = $input['hero_image_path'];
            
            // Se for null, deletar a imagem existente do disco
            if ($heroImagePath === null) {
                $stmt = $pdo->prepare("SELECT hero_image_path FROM projects WHERE id = ?");
                $stmt->execute([$id]);
                $oldHeroImage = $stmt->fetchColumn();
                
                if ($oldHeroImage && file_exists($oldHeroImage)) {
                    unlink($oldHeroImage);
                }
            }
            
            $stmt = $pdo->prepare("
                UPDATE projects 
                SET title = ?, description = ?, slug = ?, category_id = ?, hero_image_path = ?
                WHERE id = ?
            ");
            $stmt->execute([$title, $description, $slug, $category_id, $heroImagePath, $id]);
        } else {
            // Update sem mexer no hero_image_path
            $stmt = $pdo->prepare("
                UPDATE projects 
                SET title = ?, description = ?, slug = ?, category_id = ?
                WHERE id = ?
            ");
            $stmt->execute([$title, $description, $slug, $category_id, $id]);
        }
        
        jsonResponse(true);
    }

    // DELETE PROJECT
    if ($action === 'delete_project') {
        $input = json_decode(file_get_contents('php://input'), true);
        $id = $input['id'] ?? 0;
        
        // Buscar imagens do projeto para deletar arquivos
        $stmt = $pdo->prepare("SELECT image_path FROM works WHERE project_id = ?");
        $stmt->execute([$id]);
        $images = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        // Buscar hero image
        $stmt = $pdo->prepare("SELECT hero_image_path FROM projects WHERE id = ?");
        $stmt->execute([$id]);
        $heroImage = $stmt->fetchColumn();
        
        // Deletar arquivos de imagens
        foreach ($images as $imagePath) {
            if (file_exists($imagePath)) {
                unlink($imagePath);
            }
        }
        
        // Deletar hero image
        if ($heroImage && file_exists($heroImage)) {
            unlink($heroImage);
        }
        
        // Deletar projeto (CASCADE vai deletar as referências em works)
        $stmt = $pdo->prepare("DELETE FROM projects WHERE id = ?");
        $stmt->execute([$id]);
        
        jsonResponse(true);
    }

    // REORDER PROJECTS
    if ($action === 'reorder_projects') {
        $input = json_decode(file_get_contents('php://input'), true);
        $projects = $input['projects'] ?? [];
        
        $pdo->beginTransaction();
        
        foreach ($projects as $index => $projectId) {
            $stmt = $pdo->prepare("UPDATE projects SET display_order = ? WHERE id = ?");
            $stmt->execute([$index, $projectId]);
        }
        
        $pdo->commit();
        jsonResponse(true);
    }

    // =================================
    // PROJECT CATEGORIES - CRUD
    // =================================
    
    // GET ALL CATEGORIES
    if ($action === 'get_categories') {
        $stmt = $pdo->query("
            SELECT c.*,
                   (SELECT COUNT(*) FROM projects WHERE category_id = c.id) as project_count
            FROM project_categories c 
            ORDER BY c.display_order ASC, c.name ASC
        ");
        $categories = $stmt->fetchAll();
        jsonResponse(true, ['categories' => $categories]);
    }

    // ADD CATEGORY
    if ($action === 'add_category') {
        $input = json_decode(file_get_contents('php://input'), true);
        
        $name = $input['name'] ?? 'Nova Categoria';
        
        // Gerar slug único a partir do nome
        $slug = generateSlug($name);
        
        // Verificar se slug já existe
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM project_categories WHERE slug = ?");
        $stmt->execute([$slug]);
        if ($stmt->fetchColumn() > 0) {
            // Adiciona timestamp para tornar único
            $slug .= '-' . time();
        }
        
        // Obter próximo display_order
        $stmt = $pdo->query("SELECT MAX(display_order) FROM project_categories");
        $maxOrder = $stmt->fetchColumn();
        $nextOrder = $maxOrder ? $maxOrder + 1 : 1;
        
        $stmt = $pdo->prepare("
            INSERT INTO project_categories (name, slug, display_order) 
            VALUES (?, ?, ?)
        ");
        $stmt->execute([$name, $slug, $nextOrder]);
        
        jsonResponse(true, ['id' => $pdo->lastInsertId(), 'slug' => $slug]);
    }

    // UPDATE CATEGORY
    if ($action === 'update_category') {
        $input = json_decode(file_get_contents('php://input'), true);
        
        $id = $input['id'] ?? 0;
        $name = $input['name'] ?? '';
        
        // Gerar slug automaticamente do nome
        $slug = generateSlug($name);
        
        // Verificar se slug não está duplicado
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM project_categories WHERE slug = ? AND id != ?");
        $stmt->execute([$slug, $id]);
        if ($stmt->fetchColumn() > 0) {
            // Adiciona timestamp para tornar único
            $slug .= '-' . time();
        }
        
        $stmt = $pdo->prepare("
            UPDATE project_categories 
            SET name = ?, slug = ?
            WHERE id = ?
        ");
        $stmt->execute([$name, $slug, $id]);
        
        jsonResponse(true);
    }

    // DELETE CATEGORY
    if ($action === 'delete_category') {
        $input = json_decode(file_get_contents('php://input'), true);
        $id = $input['id'] ?? 0;
        
        // Verificar se categoria está em uso
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM projects WHERE category_id = ?");
        $stmt->execute([$id]);
        $projectCount = $stmt->fetchColumn();
        
        if ($projectCount > 0) {
            throw new Exception("Esta categoria não pode ser removida pois está sendo usada por $projectCount projeto(s)");
        }
        
        // Deletar categoria
        $stmt = $pdo->prepare("DELETE FROM project_categories WHERE id = ?");
        $stmt->execute([$id]);
        
        jsonResponse(true);
    }

    // REORDER CATEGORIES
    if ($action === 'reorder_categories') {
        $input = json_decode(file_get_contents('php://input'), true);
        $categories = $input['categories'] ?? [];
        
        $pdo->beginTransaction();
        
        foreach ($categories as $index => $categoryId) {
            $stmt = $pdo->prepare("UPDATE project_categories SET display_order = ? WHERE id = ?");
            $stmt->execute([$index, $categoryId]);
        }
        
        $pdo->commit();
        jsonResponse(true);
    }

    // UPLOAD PROJECT HERO IMAGE
    if ($action === 'upload_project_hero') {
        $projectId = $_POST['project_id'] ?? 0;
        
        if (!isset($_FILES['image'])) {
            throw new Exception('Nenhuma imagem enviada');
        }
        
        // Buscar projeto
        $stmt = $pdo->prepare("SELECT * FROM projects WHERE id = ?");
        $stmt->execute([$projectId]);
        $project = $stmt->fetch();
        
        if (!$project) {
            throw new Exception('Projeto não encontrado');
        }
        
        // Criar pasta se não existir
        $heroDir = 'imagens/projects/hero/';
        if (!is_dir($heroDir)) {
            mkdir($heroDir, 0755, true);
        }
        
        $file = $_FILES['image'];
        
        if (!isAllowedExtension($file['name'])) {
            throw new Exception('Tipo de arquivo não permitido');
        }
        
        // Deletar hero image anterior se existir
        if ($project['hero_image_path'] && file_exists($project['hero_image_path'])) {
            unlink($project['hero_image_path']);
        }
        
        // Salvar nova imagem
        $newName = 'hero_' . $projectId . '_' . time() . '.' . strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $imagePath = $heroDir . $newName;
        
        if (move_uploaded_file($file['tmp_name'], $imagePath)) {
            // Atualizar hero_image_path do projeto
            $stmt = $pdo->prepare("UPDATE projects SET hero_image_path = ? WHERE id = ?");
            $stmt->execute([$imagePath, $projectId]);
            
            // Também inserir na tabela works para não perder a imagem
            // Remover is_hero de outras imagens
            $stmt = $pdo->prepare("UPDATE works SET is_hero = 0 WHERE project_id = ?");
            $stmt->execute([$projectId]);
            
            // Verificar se já existe
            $stmt = $pdo->prepare("SELECT id FROM works WHERE image_path = ? AND project_id = ?");
            $stmt->execute([$imagePath, $projectId]);
            
            if (!$stmt->fetch()) {
                // Inserir nova como hero
                $stmt = $pdo->prepare("INSERT INTO works (image_path, title, project_id, display_order, is_hero) VALUES (?, '', ?, 0, 1)");
                $stmt->execute([$imagePath, $projectId]);
            } else {
                // Marcar existente como hero
                $stmt = $pdo->prepare("UPDATE works SET is_hero = 1 WHERE image_path = ? AND project_id = ?");
                $stmt->execute([$imagePath, $projectId]);
            }
            
            jsonResponse(true, ['image_path' => $imagePath]);
        } else {
            throw new Exception('Erro ao salvar arquivo');
        }
    }

    // ASSOCIATE IMAGES WITH PROJECT
    if ($action === 'add_images_to_project') {
        $input = json_decode(file_get_contents('php://input'), true);
        $projectId = $input['project_id'] ?? 0;
        $imageIds = $input['image_ids'] ?? [];
        
        if (empty($imageIds)) {
            throw new Exception('Nenhuma imagem foi selecionada');
        }
        
        $pdo->beginTransaction();
        
        foreach ($imageIds as $imageId) {
            $stmt = $pdo->prepare("UPDATE works SET project_id = ? WHERE id = ?");
            $stmt->execute([$projectId, $imageId]);
        }
        
        $pdo->commit();
        jsonResponse(true);
    }

    // REMOVE IMAGE FROM PROJECT
    if ($action === 'remove_image_from_project') {
        $input = json_decode(file_get_contents('php://input'), true);
        $imageId = $input['image_id'] ?? 0;
        
        // Buscar informações da imagem antes de deletar
        $stmt = $pdo->prepare("SELECT image_path, is_hero, project_id FROM works WHERE id = ?");
        $stmt->execute([$imageId]);
        $image = $stmt->fetch();
        
        if (!$image) {
            throw new Exception('Imagem não encontrada');
        }
        
        // Se for hero image, limpar referência no projeto
        if ($image['is_hero'] && $image['project_id']) {
            $stmt = $pdo->prepare("UPDATE projects SET hero_image_path = NULL WHERE id = ?");
            $stmt->execute([$image['project_id']]);
        }
        
        // Deletar arquivo físico
        if (file_exists($image['image_path'])) {
            unlink($image['image_path']);
        }
        
        // Deletar registro do banco de dados
        $stmt = $pdo->prepare("DELETE FROM works WHERE id = ?");
        $stmt->execute([$imageId]);
        
        jsonResponse(true);
    }

    // UPLOAD IMAGES TO PROJECT (direct upload)
    if ($action === 'upload_project_images') {
        $projectId = $_POST['project_id'] ?? 0;
        
        if (!isset($_FILES['images'])) {
            throw new Exception('Nenhuma imagem enviada');
        }
        
        // Verificar se projeto existe
        $stmt = $pdo->prepare("SELECT id FROM projects WHERE id = ?");
        $stmt->execute([$projectId]);
        if (!$stmt->fetch()) {
            throw new Exception('Projeto não encontrado');
        }
        
        $uploaded = [];
        $files = $_FILES['images'];
        
        // Garante que pasta existe
        if (!is_dir(UPLOAD_DIR)) {
            mkdir(UPLOAD_DIR, 0755, true);
        }
        
        for ($i = 0; $i < count($files['name']); $i++) {
            $tmp = $files['tmp_name'][$i];
            $name = $files['name'][$i];
            
            if (!isAllowedExtension($name)) {
                continue;
            }
            
            $new_name = generateUniqueFilename($name);
            $db_path = UPLOAD_DIR . $new_name;
            
            if (move_uploaded_file($tmp, UPLOAD_DIR . $new_name)) {
                // Obter próximo display_order para este projeto
                $stmt = $pdo->prepare("SELECT MAX(display_order) FROM works WHERE project_id = ?");
                $stmt->execute([$projectId]);
                $maxOrder = $stmt->fetchColumn();
                $nextOrder = $maxOrder !== null ? $maxOrder + 1 : 0;
                
                $stmt = $pdo->prepare("
                    INSERT INTO works (image_path, title, display_order, project_id) 
                    VALUES (?, '', ?, ?)
                ");
                $stmt->execute([$db_path, $nextOrder, $projectId]);
                
                $uploaded[] = [
                    'id' => $pdo->lastInsertId(),
                    'image_path' => $db_path
                ];
            }
        }
        
        jsonResponse(true, ['uploaded' => $uploaded]);
    }

// ============================================
// ENDPOINT: Upload de múltiplas imagens do projeto
// ============================================
if ($action === 'upload_project_images') {
    try {
        $project_id = $_POST['project_id'] ?? null;
        
        if (!$project_id) {
            throw new Exception('ID do projeto não informado');
        }
        
        // Verificar se projeto existe
        $stmt = $pdo->prepare("SELECT id FROM projects WHERE id = ?");
        $stmt->execute([$project_id]);
        if (!$stmt->fetch()) {
            throw new Exception('Projeto não encontrado');
        }
        
        if (!isset($_FILES['images']) || empty($_FILES['images']['name'][0])) {
            throw new Exception('Nenhuma imagem selecionada');
        }
        
        $uploaded = [];
        $files = $_FILES['images'];
        
        // Processar cada arquivo
        for ($i = 0; $i < count($files['name']); $i++) {
            if ($files['error'][$i] !== UPLOAD_ERR_OK) {
                continue;
            }
            
            $filename = $files['name'][$i];
            $tmp = $files['tmp_name'][$i];
            
            if (!isAllowedExtension($filename)) {
                continue;
            }
            
            $new_name = generateUniqueFilename($filename);
            $db_path = 'imagens/' . $new_name;
            
            if (move_uploaded_file($tmp, UPLOAD_DIR . $new_name)) {
                // Inserir na tabela works vinculado ao projeto
                $stmt = $pdo->prepare("
                    INSERT INTO works (image_path, title, project_id, display_order) 
                    VALUES (?, '', ?, 999)
                ");
                $stmt->execute([$db_path, $project_id]);
                $uploaded[] = $db_path;
            }
        }
        
        jsonResponse(true, [
            'message' => count($uploaded) . ' imagem(ns) enviada(s)',
            'uploaded' => $uploaded
        ]);
        
    } catch (Exception $e) {
        jsonResponse(false, ['error' => $e->getMessage()]);
    }
}

// ============================================
// ENDPOINT: Upload de hero image do projeto
// ============================================
if ($action === 'upload_project_hero') {
    try {
        $project_id = $_POST['project_id'] ?? null;
        
        if (!$project_id) {
            throw new Exception('ID do projeto não informado');
        }
        
        // Verificar se projeto existe e pegar hero atual
        $stmt = $pdo->prepare("SELECT id, hero_image_path FROM projects WHERE id = ?");
        $stmt->execute([$project_id]);
        $project = $stmt->fetch();
        
        if (!$project) {
            throw new Exception('Projeto não encontrado');
        }
        
        if (!isset($_FILES['hero_image']) || $_FILES['hero_image']['error'] !== UPLOAD_ERR_OK) {
            throw new Exception('Nenhuma imagem selecionada');
        }
        
        $file = $_FILES['hero_image'];
        
        if (!isAllowedExtension($file['name'])) {
            throw new Exception('Tipo de arquivo não permitido');
        }
        
        // Deletar hero anterior se existir
        if ($project['hero_image_path'] && file_exists($project['hero_image_path'])) {
            unlink($project['hero_image_path']);
        }
        
        // Upload nova hero
        $new_name = generateUniqueFilename($file['name']);
        $db_path = UPLOAD_DIR . $new_name;
        
        if (!move_uploaded_file($file['tmp_name'], UPLOAD_DIR . $new_name)) {
            throw new Exception('Erro ao fazer upload da imagem');
        }
        
        // Atualizar projeto
        $stmt = $pdo->prepare("UPDATE projects SET hero_image_path = ? WHERE id = ?");
        $stmt->execute([$db_path, $project_id]);
        
        jsonResponse(true, [
            'message' => 'Hero image atualizada',
            'hero_image_path' => $db_path
        ]);
        
    } catch (Exception $e) {
        jsonResponse(false, ['error' => $e->getMessage()]);
    }
}

// ============================================
// ENDPOINT: Definir imagem como hero do projeto  
// ============================================
if ($action === 'set_project_image_hero') {
    try {
        // Ler dados do body se veio como JSON
        $input = file_get_contents('php://input');
        $data = json_decode($input, true);
        
        $image_id = $data['image_id'] ?? $_POST['image_id'] ?? null;
        $project_id = $data['project_id'] ?? $_POST['project_id'] ?? null;
        
        if (!$image_id || !$project_id) {
            throw new Exception('IDs não informados');
        }
        
        // Verificar se a imagem pertence ao projeto
        $stmt = $pdo->prepare("SELECT id, image_path FROM works WHERE id = ? AND project_id = ?");
        $stmt->execute([$image_id, $project_id]);
        $image = $stmt->fetch();
        
        if (!$image) {
            throw new Exception('Imagem não encontrada no projeto');
        }
        
        // Remover hero de todas as outras imagens do projeto
        $stmt = $pdo->prepare("UPDATE works SET is_hero = 0 WHERE project_id = ?");
        $stmt->execute([$project_id]);
        
        // Marcar esta imagem como hero
        $stmt = $pdo->prepare("UPDATE works SET is_hero = 1 WHERE id = ?");
        $stmt->execute([$image_id]);
        
        // Atualizar hero_image_path do projeto
        $stmt = $pdo->prepare("UPDATE projects SET hero_image_path = ? WHERE id = ?");
        $stmt->execute([$image['image_path'], $project_id]);
        
        jsonResponse(true, ['message' => 'Imagem definida como hero']);
        
    } catch (Exception $e) {
        jsonResponse(false, ['error' => $e->getMessage()]);
    }
}

// ============================================
// ENDPOINT: Reordenar imagens do projeto
// ============================================
if ($action === 'reorder_project_images') {
    try {
        $images = $_POST['images'] ?? [];
        
        if (empty($images)) {
            throw new Exception('Lista de imagens vazia');
        }
        
        // Atualizar display_order de cada imagem
        foreach ($images as $index => $image_id) {
            $stmt = $pdo->prepare("UPDATE works SET display_order = ? WHERE id = ?");
            $stmt->execute([$index, $image_id]);
        }
        
        jsonResponse(true, ['message' => 'Ordem atualizada']);
        
    } catch (Exception $e) {
        jsonResponse(false, ['error' => $e->getMessage()]);
    }
}

} catch (Exception $e) {
    jsonResponse(false, ['error' => $e->getMessage()]);
}

// =================================
// COLLECTIONS - CRUD
// =================================

if ($_SERVER['REQUEST_METHOD'] === 'POST' || $_SERVER['REQUEST_METHOD'] === 'GET') {
    try {
        // GET ALL COLLECTIONS
        if ($action === 'get_collections') {
            $stmt = $pdo->query("
                SELECT c.*,
                       (SELECT COUNT(*) FROM products WHERE collection_id = c.id) as product_count,
                       ci.image_path as primary_image_path
                FROM collections c 
                LEFT JOIN collection_images ci ON c.id = ci.collection_id AND ci.is_primary = 1
                ORDER BY c.display_order ASC, c.created_at DESC
            ");
            $collections = $stmt->fetchAll();
            
            // Adicionar contagem de imagens da pasta
            foreach ($collections as &$collection) {
                $collection['image_count'] = countCollectionImages($collection['title']);
            }
            
            jsonResponse(true, ['collections' => $collections]);
        }

        // SAVE COLLECTION
        if ($action === 'save_collection') {
            $input = json_decode(file_get_contents('php://input'), true);
            $id = $input['id'] ?? 0;
            $title = $input['title'] ?? '';
            $description = $input['description'] ?? '';
            $producedBy = $input['produced_by'] ?? '';
            $year = $input['year'] ?? '';
            $displayOrder = $input['display_order'] ?? 0;
            
            // Criar pasta da coleção se não existir
            $sanitizedName = sanitizeCollectionName($title);
            $collectionFolder = __DIR__ . '/images/colecoes/' . $sanitizedName;
            
            if (!file_exists($collectionFolder)) {
                @mkdir($collectionFolder, 0755, true);
            }
            
            $stmt = $pdo->prepare("UPDATE collections SET title = ?, description = ?, produced_by = ?, year = ?, display_order = ? WHERE id = ?");
            $stmt->execute([$title, $description, $producedBy, $year, $displayOrder, $id]);
            
            jsonResponse(true);
        }

        // DELETE COLLECTION
        if ($action === 'delete_collection') {
            $input = json_decode(file_get_contents('php://input'), true);
            $id = $input['id'] ?? 0;
            
            // Delete all collection images from filesystem
            $stmt = $pdo->prepare("SELECT image_path FROM collection_images WHERE collection_id = ?");
            $stmt->execute([$id]);
            $images = $stmt->fetchAll();
            
            foreach ($images as $img) {
                if ($img['image_path'] && file_exists($img['image_path'])) {
                    unlink($img['image_path']);
                }
            }
            
            // Delete collection (cascade will delete collection_images and set products.collection_id to NULL)
            $stmt = $pdo->prepare("DELETE FROM collections WHERE id = ?");
            $stmt->execute([$id]);
            jsonResponse(true);
        }

        // UPLOAD COLLECTION IMAGE
        if ($action === 'upload_collection_image') {
        $collectionId = $_POST['collection_id'] ?? 0;
        $setPrimary = $_POST['set_primary'] ?? '1'; // Por padrão, define como primária
        
        if (!isset($_FILES['image'])) {
            throw new Exception('Nenhuma imagem enviada');
        }
        
        // Buscar título da coleção para criar pasta
        $stmt = $pdo->prepare("SELECT title FROM collections WHERE id = ?");
        $stmt->execute([$collectionId]);
        $collection = $stmt->fetch();
        
        if (!$collection) {
            throw new Exception('Coleção não encontrada');
        }
        
        // Criar pasta da coleção se não existir
        $sanitizedName = sanitizeCollectionName($collection['title']);
        $collectionDir = __DIR__ . '/images/colecoes/' . $sanitizedName . '/';
        
        if (!is_dir($collectionDir)) {
            if (!mkdir($collectionDir, 0755, true)) {
                $error = error_get_last();
                error_log("Falha ao criar pasta: $collectionDir - " . ($error['message'] ?? 'desconhecido'));
                throw new Exception('Falha ao criar pasta da coleção: ' . ($error['message'] ?? 'desconhecido'));
            }
        }

        $file = $_FILES['image'];
        
        if (!isAllowedExtension($file['name'])) {
            throw new Exception('Tipo de arquivo não permitido');
        }
        
        $originalExt = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $newName = time() . '_' . uniqid() . '.' . $originalExt;
        
        // Caminho físico no servidor
        $physicalPath = $collectionDir . $newName;
        // Caminho web acessível pelo navegador
        $webPath = '/images/colecoes/' . $sanitizedName . '/' . $newName;

        if (move_uploaded_file($file['tmp_name'], $physicalPath)) {
            // Se definir como primária, remover flag is_primary de outras imagens
            if ($setPrimary == '1') {
                $stmt = $pdo->prepare("UPDATE collection_images SET is_primary = 0 WHERE collection_id = ?");
                $stmt->execute([$collectionId]);
            }
            
            // Obter próximo display_order
            $stmt = $pdo->prepare("SELECT MAX(display_order) FROM collection_images WHERE collection_id = ?");
            $stmt->execute([$collectionId]);
            $maxOrder = $stmt->fetchColumn();
            $nextOrder = $maxOrder ? $maxOrder + 1 : 1;
            
            // Inserir nova imagem (usando caminho web)
            $stmt = $pdo->prepare("INSERT INTO collection_images (collection_id, image_path, is_primary, display_order) VALUES (?, ?, ?, ?)");
            $stmt->execute([$collectionId, $webPath, $setPrimary, $nextOrder]);
            
            jsonResponse(true, [
                'image_path' => $webPath, 
                'image_id' => $pdo->lastInsertId()
            ]);
        } else {
            $error = error_get_last();
            $errorMsg = 'Erro ao salvar arquivo';
            if ($error) {
                $errorMsg .= ': ' . $error['message'];
            }
            error_log("move_uploaded_file falhou: {$file['tmp_name']} -> $physicalPath - " . ($error['message'] ?? 'desconhecido'));
            throw new Exception($errorMsg);
        }
    }
    
    // =================================
    // COLLECTION EDITOR ENDPOINTS
    // =================================
    
    // GET COLLECTION DETAIL (for editor page)
    if ($action === 'get_collection_detail') {
        $id = $_GET['id'] ?? 0;
        
        // Get collection
        $stmt = $pdo->prepare("SELECT * FROM collections WHERE id = ?");
        $stmt->execute([$id]);
        $collection = $stmt->fetch();
        
        if (!$collection) {
            jsonResponse(false, ['error' => 'Coleção não encontrada']);
        }
        
        // Get products
        $stmt = $pdo->prepare("
            SELECT * FROM products 
            WHERE collection_id = ? 
            ORDER BY display_order ASC, id ASC
        ");
        $stmt->execute([$id]);
        $products = $stmt->fetchAll();
        
        // Get images from folder
        $folderName = sanitizeCollectionName($collection['title']);
        $dirPath = __DIR__ . '/images/colecoes/' . $folderName;
        $webPath = 'images/colecoes/' . $folderName . '/';
        $images = [];
        
        if (is_dir($dirPath)) {
            $files = scandir($dirPath);
            foreach ($files as $file) {
                if ($file === '.' || $file === '..') continue;
                
                $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
                if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'gif'])) {
                    $images[] = [
                        'name' => $file,
                        'path' => $webPath . $file
                    ];
                }
            }
        }
        
        jsonResponse(true, [
            'collection' => $collection,
            'products' => $products,
            'images' => $images
        ]);
    }
    
    // UPDATE COLLECTION INFO
    if ($action === 'update_collection_info') {
        $input = json_decode(file_get_contents('php://input'), true);
        $id = $input['id'] ?? 0;
        $title = $input['title'] ?? '';
        $description = $input['description'] ?? '';
        $year = $input['year'] ?? '';
        $producedBy = $input['produced_by'] ?? '';
        
        if (!$title) {
            jsonResponse(false, ['error' => 'Título é obrigatório']);
        }
        
        $stmt = $pdo->prepare("
            UPDATE collections 
            SET title = ?, description = ?, year = ?, produced_by = ?
            WHERE id = ?
        ");
        $stmt->execute([$title, $description, $year, $producedBy, $id]);
        
        jsonResponse(true);
    }
    
    // ADD PRODUCT
    if ($action === 'add_product') {
        $input = json_decode(file_get_contents('php://input'), true);
        $collectionId = $input['collection_id'] ?? 0;
        $title = $input['title'] ?? null;
        $type = $input['type'] ?? '';
        $dimensions = $input['dimensions'] ?? null;
        $materials = $input['materials'] ?? null;
        
        if (!$type) {
            jsonResponse(false, ['error' => 'Tipo é obrigatório']);
        }
        
        // Get next display_order
        $stmt = $pdo->prepare("SELECT MAX(display_order) FROM products WHERE collection_id = ?");
        $stmt->execute([$collectionId]);
        $maxOrder = $stmt->fetchColumn();
        $nextOrder = $maxOrder ? $maxOrder + 1 : 0;
        
        $stmt = $pdo->prepare("
            INSERT INTO products (collection_id, title, type, dimensions, materials, display_order)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([$collectionId, $title, $type, $dimensions, $materials, $nextOrder]);
        
        jsonResponse(true, ['id' => $pdo->lastInsertId()]);
    }
    
    // UPDATE PRODUCT
    if ($action === 'update_product') {
        $input = json_decode(file_get_contents('php://input'), true);
        $id = $input['id'] ?? 0;
        $title = $input['title'] ?? null;
        $type = $input['type'] ?? '';
        $dimensions = $input['dimensions'] ?? null;
        $materials = $input['materials'] ?? null;
        
        if (!$type) {
            jsonResponse(false, ['error' => 'Tipo é obrigatório']);
        }
        
        $stmt = $pdo->prepare("
            UPDATE products 
            SET title = ?, type = ?, dimensions = ?, materials = ?
            WHERE id = ?
        ");
        $stmt->execute([$title, $type, $dimensions, $materials, $id]);
        
        jsonResponse(true);
    }
    
    // DELETE PRODUCT
    if ($action === 'delete_product') {
        $input = json_decode(file_get_contents('php://input'), true);
        $id = $input['id'] ?? 0;
        
        $stmt = $pdo->prepare("DELETE FROM products WHERE id = ?");
        $stmt->execute([$id]);
        
        jsonResponse(true);
    }
    
    // REORDER PRODUCTS
    if ($action === 'reorder_products') {
        $input = json_decode(file_get_contents('php://input'), true);
        $products = $input['products'] ?? [];
        
        $pdo->beginTransaction();
        try {
            foreach ($products as $product) {
                $stmt = $pdo->prepare("UPDATE products SET display_order = ? WHERE id = ?");
                $stmt->execute([$product['display_order'], $product['id']]);
            }
            $pdo->commit();
            jsonResponse(true);
        } catch (Exception $e) {
            $pdo->rollBack();
            jsonResponse(false, ['error' => $e->getMessage()]);
        }
    }
    
    // DELETE COLLECTION IMAGE
    if ($action === 'delete_collection_image') {
        $input = json_decode(file_get_contents('php://input'), true);
        $collectionId = $input['collection_id'] ?? 0;
        $imageName = $input['image_name'] ?? '';
        $folderName = $input['folder_name'] ?? '';
        
        if (!$imageName || !$folderName) {
            jsonResponse(false, ['error' => 'Parâmetros inválidos']);
        }
        
        $filePath = __DIR__ . '/images/colecoes/' . $folderName . '/' . $imageName;
        
        if (file_exists($filePath)) {
            if (unlink($filePath)) {
                // If this was the hero image, clear it from DB
                $stmt = $pdo->prepare("SELECT hero_image FROM collections WHERE id = ?");
                $stmt->execute([$collectionId]);
                $collection = $stmt->fetch();
                
                if ($collection && $collection['hero_image'] && strpos($collection['hero_image'], $imageName) !== false) {
                    $stmt = $pdo->prepare("UPDATE collections SET hero_image = NULL WHERE id = ?");
                    $stmt->execute([$collectionId]);
                }
                
                jsonResponse(true);
            } else {
                jsonResponse(false, ['error' => 'Falha ao remover arquivo']);
            }
        } else {
            jsonResponse(false, ['error' => 'Arquivo não encontrado']);
        }
    }
    
    // SET HERO IMAGE
    if ($action === 'set_hero_image') {
        $input = json_decode(file_get_contents('php://input'), true);
        $collectionId = $input['collection_id'] ?? 0;
        $imageName = $input['image_name'] ?? '';
        $folderName = $input['folder_name'] ?? '';
        
        if (!$imageName || !$folderName) {
            jsonResponse(false, ['error' => 'Parâmetros inválidos']);
        }
        
        $heroImagePath = 'images/colecoes/' . $folderName . '/' . $imageName;
        
        $stmt = $pdo->prepare("UPDATE collections SET hero_image = ? WHERE id = ?");
        $stmt->execute([$heroImagePath, $collectionId]);
        
        jsonResponse(true, ['hero_image' => $heroImagePath]);
    }

    // SAVE COLLECTIONS ORDER
    if ($action === 'save_collections_order') {
        $input = json_decode(file_get_contents('php://input'), true);
        $items = $input['items'] ?? [];
        
        $pdo->beginTransaction();
        try {
            foreach ($items as $item) {
                $stmt = $pdo->prepare("UPDATE collections SET display_order = ? WHERE id = ?");
                $order = $item['display_order'] ?? 0;
                $stmt->execute([$order, $item['id']]);
            }
            $pdo->commit();
            jsonResponse(true);
        } catch (Exception $e) {
            $pdo->rollBack();
            jsonResponse(false, ['error' => $e->getMessage()]);
        }
    }

    // SAVE SETTINGS (Unified)
    if ($action === 'save_settings') {
        $input = json_decode(file_get_contents('php://input'), true);
        unset($input['action']);
        
        $pdo->beginTransaction();
        try {
            foreach ($input as $key => $value) {
                if ($value === null) continue;
                $stmt = $pdo->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = ?");
                $stmt->execute([$key, $value, $value]);
            }
            $pdo->commit();
            jsonResponse(true);
        } catch (Exception $e) {
            $pdo->rollBack();
            jsonResponse(false, ['error' => $e->getMessage()]);
        }
    }

    } catch (Exception $e) {
        jsonResponse(false, ['error' => $e->getMessage()]);
    }
}

// Helper function para gerar slug
function generateSlug($text) {
    // Converte para minúsculas
    $text = strtolower($text);
    
    // Remove acentos
    $text = iconv('UTF-8', 'ASCII//TRANSLIT', $text);
    
    // Remove caracteres especiais
    $text = preg_replace('/[^a-z0-9\s-]/', '', $text);
    
    // Substitui espaços e múltiplos hífens por um único hífen
    $text = preg_replace('/[\s-]+/', '-', $text);
    
    // Remove hífens do início e fim
    $text = trim($text, '-');
    
    return $text;
}
?>

