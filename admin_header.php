<?php
$displayLogoText = getSetting('logo_text', 'WIDE STUDIO');
?>
<header class="admin-header">
    <div class="brand">
        <a href="admin_collections.php" class="admin-logo-link" style="color: inherit; text-decoration: none; display: flex; align-items: center; gap: 10px;">
            <span class="logo-svg" style="font-family:'Inter', sans-serif; font-weight:400; font-size:0.85rem; letter-spacing:0.15em; text-transform:uppercase; line-height: 1;">
                <?php echo htmlspecialchars($displayLogoText); ?>
            </span>
        </a>
        
        <nav class="admin-nav">
            <a href="index.php" target="_blank" class="admin-nav-link">Ver Site</a>
            <a href="change_password.php" class="admin-nav-link">Alterar Senha</a>
            <a href="logout.php" class="admin-nav-link" style="border-color: rgba(255,0,0,0.1); color: #c00;">Sair</a>
        </nav>
    </div>

    <button class="btn-save" id="globalSaveBtn">Salvar Alterações</button>
</header>

<main class="admin-container">
    <div class="tabs-nav" id="admin-tabs-nav">
        <?php 
        $currentFile = basename($_SERVER['PHP_SELF']);
        $navItems = [
            'admin_collections.php' => 'Coleções',
            'admin_hero.php' => 'Texto Principal',
            'admin_contact.php' => 'Contato',
            'admin_about.php' => 'Sobre',
            'admin_menu.php' => 'Menu',
            'admin_settings.php' => 'Geral'
        ];
        foreach ($navItems as $file => $label): 
            $active = ($currentFile == $file) ? 'active' : '';
            // Fallback: Deixa o botão ativo com fundo caso o JS demore
            $activeStyle = $active ? 'style="background: var(--text-color); color: var(--bg-color);"' : '';
        ?>
            <a href="<?php echo $file; ?>" class="tab-btn <?php echo $active; ?>" <?php echo $activeStyle; ?>><?php echo $label; ?></a>
        <?php endforeach; ?>
    </div>
