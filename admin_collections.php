<?php
require_once 'admin_layout_top.php';
$pageScript = 'admin_collections.js';
require_once 'admin_header.php';
?>

<section class="intro-section">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
        <h1 class="section-title" style="margin:0;">Gerenciar Coleções</h1>
        <button id="addCollectionBtn" class="btn-add-service">
            <span>+</span> Adicionar Coleção
        </button>
    </div>
    <p style="font-size: 0.9rem; color: #666; margin-top: 10px; margin-bottom: 30px;">
        Organize seus produtos em coleções temáticas. Arraste para reordenar.
    </p>
    
    <!-- Collections List -->
    <div id="collections-list" class="collections-grid">
        <div class="loading-placeholder">Carregando coleções...</div>
    </div>
</section>

<!-- Hidden file input for collection image upload -->
<input type="file" id="collectionImageInput" accept="image/*" style="display: none;">

<?php require_once 'admin_layout_bottom.php'; ?>
