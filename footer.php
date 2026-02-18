<?php
// Footer - Buscar informações de contato se ainda não foram carregadas
if (!isset($contactEmail) || !isset($contactPhone) || !isset($contactInstagram) || !isset($footerText)) {
    try {
        $footerSettings = getSettings(['contact_email', 'contact_phone', 'contact_instagram', 'footer_text']);
        $contactEmail = $footerSettings['contact_email'] ?? 'contato@exemplo.com';
        $contactPhone = $footerSettings['contact_phone'] ?? '(00) 0 0000-0000';
        $contactInstagram = $footerSettings['contact_instagram'] ?? '@seuperfil';
        $footerText = $footerSettings['footer_text'] ?? '© Gustavo Starke Fotografia e Vídeo de Arquitetura';
        
        // Formatar para uso
        $instagramDisplay = $contactInstagram;
        if (!empty($instagramDisplay) && $instagramDisplay[0] !== '@') {
            $instagramDisplay = '@' . $instagramDisplay;
        }
        $instagramUsername = ltrim($contactInstagram, '@');
        $whatsappNumber = preg_replace('/[^0-9]/', '', $contactPhone);
    } catch (Exception $e) {
        $contactEmail = 'contato@exemplo.com';
        $contactPhone = '(00) 0 0000-0000';
        $instagramDisplay = '@seuperfil';
        $instagramUsername = 'seuperfil';
        $whatsappNumber = '';
        $footerText = '© Gustavo Starke Fotografia e Vídeo de Arquitetura';
    }
}
?>
<!-- Footer Component -->
<footer class="site-footer">
    <div class="footer-container">
        <p class="footer-text"><?php echo htmlspecialchars($footerText); ?></p>
        
        <div class="footer-contact-links">
            <button type="button" class="footer-link" id="copy-email-btn" data-email="<?php echo htmlspecialchars($contactEmail); ?>">
                <?php echo htmlspecialchars($contactEmail); ?>
            </button>
            <a href="https://wa.me/<?php echo $whatsappNumber; ?>" target="_blank" class="footer-link">
                Conversar no WhatsApp
            </a>
            <a href="https://instagram.com/<?php echo htmlspecialchars($instagramUsername); ?>" target="_blank" class="footer-link footer-link-instagram">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <rect x="2" y="2" width="20" height="20" rx="5" ry="5"></rect>
                    <path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z"></path>
                    <line x1="17.5" y1="6.5" x2="17.51" y2="6.5"></line>
                </svg>
                <?php echo htmlspecialchars($instagramDisplay); ?>
            </a>
        </div>
    </div>
</footer>

<script>
// Copy Email Functionality
document.addEventListener('DOMContentLoaded', () => {
    const copyBtn = document.getElementById('copy-email-btn');
    if (copyBtn) {
        const originalText = copyBtn.textContent.trim();
        
        copyBtn.addEventListener('click', async () => {
            const email = copyBtn.dataset.email;
            
            try {
                await navigator.clipboard.writeText(email);
                copyBtn.textContent = 'Email copiado ✓';
                copyBtn.classList.add('copied');
                
                setTimeout(() => {
                    copyBtn.textContent = originalText;
                    copyBtn.classList.remove('copied');
                }, 2000);
            } catch (err) {
                // Fallback for older browsers
                const textarea = document.createElement('textarea');
                textarea.value = email;
                document.body.appendChild(textarea);
                textarea.select();
                document.execCommand('copy');
                document.body.removeChild(textarea);
                
                copyBtn.textContent = 'Email copiado ✓';
                copyBtn.classList.add('copied');
                
                setTimeout(() => {
                    copyBtn.textContent = originalText;
                    copyBtn.classList.remove('copied');
                }, 2000);
            }
        });
    }
});
</script>
