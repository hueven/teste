<?php
require_once 'admin_layout_top.php';
$pageScript = 'admin_contact.js';

// Carregar dados via PHP para evitar campos vazios no load
$settings = getSettings(['contact_title', 'contact_subtitle', 'contact_email', 'contact_phone', 'contact_instagram', 'footer_text']);

$title = $settings['contact_title'] ?? '';
$subtitle = $settings['contact_subtitle'] ?? '';
$email = $settings['contact_email'] ?? '';
$phone = $settings['contact_phone'] ?? '';
$instagram = $settings['contact_instagram'] ?? '';
$footer = $settings['footer_text'] ?? '';

require_once 'admin_header.php';
?>

<section class="intro-section">
    <div class="flex-between" style="margin-bottom: 30px;">
        <h1 class="section-title" style="margin:0;">Textos da Página de Contato</h1>
    </div>
    
    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 40px;">
        <div class="input-group">
            <label class="input-label">Título Principal</label>
            <textarea id="contactTitle" class="admin-input" rows="3"
                placeholder="Ex: Vamos criar algo incrível juntos?"><?php echo htmlspecialchars($title); ?></textarea>
            <p class="input-hint" style="font-size: 0.85rem; color: #888; margin-top: 8px;">Título principal da página de contato</p>
        </div>

        <div class="input-group">
            <label class="input-label">Subtítulo</label>
            <textarea id="contactSubtitle" class="admin-input" rows="3"
                placeholder="Ex: Entre em contato e vamos conversar sobre seu próximo projeto."><?php echo htmlspecialchars($subtitle); ?></textarea>
            <p class="input-hint" style="font-size: 0.85rem; color: #888; margin-top: 8px;">Texto de apoio abaixo do título</p>
        </div>
    </div>
</section>

<section class="intro-section" style="margin-top: 60px;">
    <h2 class="section-title">Informações de Contato & Redes Sociais</h2>
    
    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 40px; margin-top: 30px;">
        <div class="input-group">
            <label class="input-label">E-mail de Contato</label>
            <input type="email" id="contactEmail" class="admin-input" 
                value="<?php echo htmlspecialchars($email); ?>"
                placeholder="Ex: seu@email.com">
            <p class="input-hint" style="font-size: 0.85rem; color: #888; margin-top: 8px;">Exibido no formulário e rodapé</p>
        </div>

        <div class="input-group">
            <label class="input-label">Telefone/WhatsApp</label>
            <input type="text" id="contactPhone" class="admin-input" 
                value="<?php echo htmlspecialchars($phone); ?>"
                placeholder="Ex: (47) 9 9999-9999">
            <p class="input-hint" style="font-size: 0.85rem; color: #888; margin-top: 8px;">Usado para o link direto do WhatsApp</p>
        </div>

        <div class="input-group">
            <label class="input-label">Instagram</label>
            <input type="text" id="contactInstagram" class="admin-input" 
                value="<?php echo htmlspecialchars($instagram); ?>"
                placeholder="Ex: seuperfil">
            <p class="input-hint" style="font-size: 0.85rem; color: #888; margin-top: 8px;">Nome de usuário para link do Rodapé (ex: @perfil)</p>
        </div>

        <div class="input-group">
            <label class="input-label">Texto do Footer (Destaque)</label>
            <input type="text" id="footerText" class="admin-input" 
                value="<?php echo htmlspecialchars($footer); ?>"
                placeholder="Ex: © Gustavo Starke Fotografia">
            <p class="input-hint" style="font-size: 0.85rem; color: #888; margin-top: 8px;">Texto de direitos autorais no rodapé</p>
        </div>
    </div>
</section>

<?php require_once 'admin_layout_bottom.php'; ?>
