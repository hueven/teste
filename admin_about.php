<?php
require_once 'admin_layout_top.php';
$pageScript = 'admin_about.js';

$defaultText = "Carolina Starke, natural de Blumenau - SC, é formada em Design Industrial pela PUC-PR e pelo Politécnico de Torino, na Itália, onde iniciou sua carreira como designer autônoma.\n\nSua abordagem é marcada pela simplicidade estética e pela atenção aos detalhes, buscando sempre um equilíbrio entre forma e função. Com um olhar curioso e inquieto, suas inspirações surgem de experiências vividas e dos lugares que marcaram sua trajetória.\n\nPremiada em concursos como Brasil Design Award e Museu da Casa Brasileira, Carolina acredita que o verdadeiro design é aquele que se conecta de maneira genuína com as pessoas, sem excessos, mas carregado de significado e propósito.";

$aboutText = getSetting('about_text', $defaultText);

require_once 'admin_header.php';
?>

<section class="intro-section">
    <h1 class="section-title">Página Sobre</h1>
    <div class="input-group" style="margin-top: 15px;">
        <label class="input-label" style="display: block; margin-bottom: 10px; font-weight: 600;">Biografia da Designer</label>
        <textarea id="aboutText" class="admin-input" rows="15" 
            placeholder="Digite o texto da página sobre..."><?php echo htmlspecialchars($aboutText); ?></textarea>
    </div>
</section>

<?php require_once 'admin_layout_bottom.php'; ?>
