<?php
// Detecta a página atual
$current_page = basename($_SERVER['PHP_SELF']);
$page_name = str_replace('.php', '', $current_page);

// Fetch Menu do Banco
$menuItems = [];
if (isset($pdo)) {
    try {
        $stmtM = $pdo->query("SELECT * FROM menu_items ORDER BY display_order ASC");
        if ($stmtM) $menuItems = $stmtM->fetchAll(PDO::FETCH_ASSOC);
    } catch(Exception $e) {}
}

// Fallback se tabela não existir ou estiver vazia
if (empty($menuItems)) {
    $menuItems = [
        ['title' => 'Projetos', 'link_url' => '/'],
        ['title' => 'Serviços', 'link_url' => 'servicos'],
        ['title' => 'Contato', 'link_url' => 'contato']
    ];
}

// Buscar configurações da logo
$logoText = 'Gustavo Starke';
$logoFontSize = '20';
$logoFontWeight = '600';
$logoLetterSpacing = '0.02';

if (isset($pdo)) {
    try {
        $logoSettings = getSettings(['logo_text', 'logo_font_size', 'logo_font_weight', 'logo_letter_spacing']);
        if (!empty($logoSettings['logo_text'])) $logoText = $logoSettings['logo_text'];
        if (!empty($logoSettings['logo_font_size'])) $logoFontSize = $logoSettings['logo_font_size'];
        if (!empty($logoSettings['logo_font_weight'])) $logoFontWeight = $logoSettings['logo_font_weight'];
        if (!empty($logoSettings['logo_letter_spacing'])) $logoLetterSpacing = $logoSettings['logo_letter_spacing'];
    } catch(Exception $e) {}
}

// Verifica se é home (para animação)
$is_home_page = ($current_page == 'index.php');
?>
<!-- Header Unificado -->
<header class="top-bar <?php echo (!$is_home_page) ? 'visible' : ''; ?>">
    <div class="logo-container">
        <a href="/" class="logo-link">
            <span class="logo-text" style="font-size: <?php echo htmlspecialchars($logoFontSize); ?>px; font-weight: <?php echo htmlspecialchars($logoFontWeight); ?>; letter-spacing: <?php echo htmlspecialchars($logoLetterSpacing); ?>em;">
                <?php echo htmlspecialchars($logoText); ?>
            </span>
        </a>
    </div>

    <!-- Desktop Nav -->
    <nav class="desktop-nav">
        <?php foreach($menuItems as $item): 
            $isActive = ($page_name == str_replace('.php', '', $item['link_url']));
            
            // Se for 'index' ou 'index.php', usar '/' ao invés
            $href = $item['link_url'];
            if ($href == 'index' || $href == 'index.php') {
                $href = '/';
            }
        ?>
            <a href="<?php echo htmlspecialchars($href); ?>" class="nav-btn <?php echo $isActive ? 'active' : ''; ?>">
                <?php echo htmlspecialchars($item['title']); ?>
            </a>
        <?php endforeach; ?>
    </nav>

    <div class="menu-trigger hamburger-menu">
        <span class="line"></span><span class="line"></span>
    </div>
</header>

<!-- Overlay Menu Mobile -->
<div class="menu-overlay">
    <nav class="menu-nav">
        <?php foreach($menuItems as $item): 
            $isActive = ($page_name == str_replace('.php', '', $item['link_url']));
            
            // Se for 'index' ou 'index.php', usar '/' ao invés
            $href = $item['link_url'];
            if ($href == 'index' || $href == 'index.php') {
                $href = '/';
            }
        ?>
            <a href="<?php echo htmlspecialchars($href); ?>" class="menu-link <?php echo $isActive ? 'active' : ''; ?>">
                <?php echo htmlspecialchars($item['title']); ?>
            </a>
        <?php endforeach; ?>
    </nav>
</div>
