<?php
require 'config.php';
$pdo = getDbConnection();

// Buscar textos de contato e informações de contato
$contactTitle = "Vamos criar algo incrível juntos?";
$contactSubtitle = "Entre em contato e vamos conversar sobre seu próximo projeto.";
$contactEmail = "contato@exemplo.com";
$contactPhone = "(00) 0 0000-0000";
$contactInstagram = "@seuperfil";

try {
    // Buscar configurações (otimizado - 1 query)
    $settings = getSettings(['contact_title', 'contact_subtitle', 'contact_email', 'contact_phone', 'contact_instagram']);
    
    if (!empty($settings['contact_title'])) $contactTitle = $settings['contact_title'];
    if (!empty($settings['contact_subtitle'])) $contactSubtitle = $settings['contact_subtitle'];
    if (!empty($settings['contact_email'])) $contactEmail = $settings['contact_email'];
    if (!empty($settings['contact_phone'])) $contactPhone = $settings['contact_phone'];
    if (!empty($settings['contact_instagram'])) $contactInstagram = $settings['contact_instagram'];
    
} catch (Exception $e) {
    // Keep defaults from above
}

// Formatar Instagram (garantir @ no início)
$instagramDisplay = $contactInstagram;
if (!empty($instagramDisplay) && $instagramDisplay[0] !== '@') {
    $instagramDisplay = '@' . $instagramDisplay;
}

// Extrair username do Instagram (sem @)
$instagramUsername = ltrim($contactInstagram, '@');

// Formatar telefone para WhatsApp (apenas números)
$whatsappNumber = preg_replace('/[^0-9]/', '', $contactPhone);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars(getSiteTitle(' - Contato')); ?></title>
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
    
    <!-- Styles -->
    <link rel="stylesheet" href="css/variables.css">
    <link rel="stylesheet" href="css/utilities.css">
    <link rel="stylesheet" href="style.css">
    <link rel="icon" href="logo/favicon2.png" type="image/png">
    <?php include 'includes/logo-font.php'; ?>
</head>
<body>

    <!-- Header -->
    <?php include 'header.php'; ?>

    <main>
        <section class="contact-section">
            <!-- Hero Text -->
            <div class="contact-hero">
                <h1 class="contact-title reveal">
                    <?php echo nl2br(htmlspecialchars($contactTitle)); ?>
                </h1>
                <p class="contact-subtitle reveal">
                    <?php echo htmlspecialchars($contactSubtitle); ?>
                </p>
            </div>

            <!-- Contact Cards -->
            <div class="contact-grid">
                <!-- Email Card -->
                <div class="contact-card reveal">
                    <div class="contact-card-icon">
                        <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path>
                            <polyline points="22,6 12,13 2,6"></polyline>
                        </svg>
                    </div>
                    <span class="contact-card-label">Email</span>
                    <button type="button" class="contact-card-value" id="copy-email-contact" data-email="<?php echo htmlspecialchars($contactEmail); ?>">
                        <?php echo htmlspecialchars($contactEmail); ?>
                    </button>
                    <span class="contact-card-hint">Clique para copiar</span>
                </div>

                <!-- WhatsApp Card -->
                <a href="https://wa.me/<?php echo $whatsappNumber; ?>" target="_blank" class="contact-card reveal">
                    <div class="contact-card-icon">
                        <svg width="28" height="28" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/>
                        </svg>
                    </div>
                    <span class="contact-card-label">WhatsApp</span>
                    <span class="contact-card-value"><?php echo htmlspecialchars($contactPhone); ?></span>
                    <span class="contact-card-hint">Clique para conversar</span>
                </a>

                <!-- Instagram Card -->
                <a href="https://instagram.com/<?php echo htmlspecialchars($instagramUsername); ?>" target="_blank" class="contact-card reveal">
                    <div class="contact-card-icon">
                        <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                            <rect x="2" y="2" width="20" height="20" rx="5" ry="5"></rect>
                            <path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z"></path>
                            <line x1="17.5" y1="6.5" x2="17.51" y2="6.5"></line>
                        </svg>
                    </div>
                    <span class="contact-card-label">Instagram</span>
                    <span class="contact-card-value"><?php echo htmlspecialchars($instagramDisplay); ?></span>
                    <span class="contact-card-hint">Siga nosso trabalho</span>
                </a>
            </div>

            <!-- Location Info -->
            <div class="contact-location reveal" style="margin-top: 40px; padding-top: 0; border-top: none; text-align: center;">
                <p class="contact-location-text" style="display: inline-flex; align-items: center; gap: 8px; font-size: 0.9rem; opacity: 0.7;">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path>
                        <circle cx="12" cy="10" r="3"></circle>
                    </svg>
                    Blumenau, Santa Catarina
                </p>
            </div>
        </section>

        <!-- Footer -->
        <?php include 'footer.php'; ?>
    </main>

    <!-- Lenis Smooth Scroll Library -->
    <script src="https://unpkg.com/@studio-freight/lenis@1.0.42/dist/lenis.min.js"></script>
    
    <!-- Scripts -->
    <script type="module">
        import { initAll } from './js/common.js';
        
        document.addEventListener('DOMContentLoaded', () => {
            // Header visible
            document.querySelector('.top-bar').classList.add('visible');
            
            // Init common functionality
            initAll();

            // Reveal animations
            const revealElements = document.querySelectorAll('.reveal');
            revealElements.forEach((el, index) => {
                setTimeout(() => {
                    el.classList.add('active');
                }, 100 + (index * 100));
            });

            // Copy Email functionality
            const emailCard = document.querySelector('.contact-card:has(#copy-email-contact)');
            const copyBtn = document.getElementById('copy-email-contact');
            
            if (emailCard && copyBtn) {
                const originalText = copyBtn.textContent.trim();
                const hintEl = emailCard.querySelector('.contact-card-hint');
                const email = copyBtn.dataset.email;
                let isProcessing = false; // Flag para evitar cliques duplicados
                
                // Função para copiar email
                const copyEmail = async (e) => {
                    e.preventDefault();
                    e.stopPropagation(); // Evita event bubbling
                    
                    // Evita múltiplos cliques enquanto processa
                    if (isProcessing) return;
                    isProcessing = true;
                    
                    try {
                        await navigator.clipboard.writeText(email);
                        
                        // Feedback visual no botão
                        copyBtn.textContent = '✓ Copiado';
                        copyBtn.classList.add('copied');
                        if (hintEl) hintEl.style.opacity = '0';
                        
                        setTimeout(() => {
                            copyBtn.textContent = originalText;
                            copyBtn.classList.remove('copied');
                            if (hintEl) hintEl.style.opacity = '';
                            isProcessing = false; // Libera para novos cliques
                        }, 2000);
                    } catch (err) {
                        // Fallback para navegadores antigos
                        const textarea = document.createElement('textarea');
                        textarea.value = email;
                        document.body.appendChild(textarea);
                        textarea.select();
                        document.execCommand('copy');
                        document.body.removeChild(textarea);
                        
                        // Feedback visual no botão
                        copyBtn.textContent = '✓ Copiado';
                        copyBtn.classList.add('copied');
                        if (hintEl) hintEl.style.opacity = '0';
                        
                        setTimeout(() => {
                            copyBtn.textContent = originalText;
                            copyBtn.classList.remove('copied');
                            if (hintEl) hintEl.style.opacity = '';
                            isProcessing = false; // Libera para novos cliques
                        }, 2000);
                    }
                };
                
                // Adiciona evento de click ao card inteiro
                emailCard.addEventListener('click', copyEmail);
            }
        });
    </script>
</body>
</html>
