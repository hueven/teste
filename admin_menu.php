<?php
require_once 'admin_layout_top.php';
$pageScript = 'admin_menu.js';
require_once 'admin_header.php';
?>

<section class="intro-section">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
        <h1 class="section-title" style="margin:0;">Gerenciar Menu</h1>
        <button id="addMenuItemBtn" class="btn-add-service">
            <span>+</span> Adicionar Link
        </button>
    </div>
    
    <!-- HEADER DA TABELA -->
    <div class="menu-header menu-grid-layout" style="margin-top: 30px;">
        <div style="width: 30px;"></div>
        <div style="font-weight: 600;">Título do Botão</div>
        <div style="font-weight: 600;">Link de Destino (URL)</div>
        <div style="width: 40px;"></div>
    </div>

    <div id="menu-list" style="display: flex; flex-direction: column; margin-top: 10px;">
        <div class="loading-placeholder">Carregando menu...</div>
    </div>
</section>

<?php require_once 'admin_layout_bottom.php'; ?>
