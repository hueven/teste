<?php
require 'config.php';
$pdo = getDbConnection();

// Busca Serviços
try {
    $stmt = $pdo->query("SELECT * FROM services ORDER BY display_order ASC");
    $services = $stmt ? $stmt->fetchAll() : [];
    
} catch (Exception $e) {
    $services = [];
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars(getSiteTitle(' - Serviços')); ?></title>
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
    
    <!-- Styles -->
    <link rel="stylesheet" href="css/variables.css">
    <link rel="stylesheet" href="css/utilities.css">
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="css/services.css">
    <link rel="icon" href="logo/favicon2.png" type="image/png">
    <?php include 'includes/logo-font.php'; ?>
</head>
<body>

    <!-- Header -->
    <?php include 'header.php'; ?>

    <main>
        <section class="services-section">
            <?php
            // Buscar título editável do banco de dados
            $stmt = $pdo->prepare("SELECT setting_value FROM settings WHERE setting_key = 'services_title'");
            $stmt->execute();
            $servicesTitle = $stmt->fetchColumn() ?: 'O que fazemos';
            ?>
            <div class="services-header reveal">
                <h1 class="services-title"><?php echo htmlspecialchars($servicesTitle); ?></h1>
            </div>
            
            <div class="services-grid">
                <?php if (!empty($services)): ?>
                    <?php foreach ($services as $s): ?>
                        <div class="service-card reveal">
                            <div class="service-content">
                                <div class="service-title-pill">
                                    <h2 class="service-title"><?php echo htmlspecialchars($s['title']); ?></h2>
                                </div>
                                <p class="service-description"><?php echo nl2br(htmlspecialchars($s['description'])); ?></p>
                            </div>
                            <div class="service-image">
                                <?php 
                                $mediaPath = !empty($s['image_path']) ? $s['image_path'] : '';
                                $extension = $mediaPath ? strtolower(pathinfo($mediaPath, PATHINFO_EXTENSION)) : '';
                                $videoFormats = ['mp4', 'webm', 'mov', 'avi'];
                                $isVideo = in_array($extension, $videoFormats);
                                
                                if ($isVideo && $mediaPath): ?>
                                    <video class="service-media" autoplay loop muted playsinline preload="metadata">
                                        <source src="<?php echo htmlspecialchars($mediaPath); ?>" type="video/<?php echo $extension === 'mov' ? 'mp4' : $extension; ?>">
                                        Seu navegador não suporta vídeo.
                                    </video>
                                <?php elseif ($mediaPath): ?>
                                    <img class="service-media" src="<?php echo htmlspecialchars($mediaPath); ?>" alt="<?php echo htmlspecialchars($s['title']); ?>">
                                <?php else: ?>
                                    <img class="service-media" src="https://images.unsplash.com/photo-1618005182384-a83a8bd57fbe?w=800&q=80" alt="Placeholder">
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p class="empty-message">Conteúdo de serviços ainda não disponível.</p>
                <?php endif; ?>
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
        import { onScroll } from './js/utils.js';
        
        document.addEventListener('DOMContentLoaded', () => {
            // Header visible
            document.querySelector('.top-bar').classList.add('visible');
            
            // Init all common functionality (without accordion since we use cards now)
            initAll({ accordion: false });
            
            // Scroll Focus Effect
            initScrollFocus();
            
            // Services Title Parallax Effect (same as hero text)
            initServicesParallax();
        });
        
        /**
         * Services Title Parallax - Mesmo efeito do hero text
         */
        function initServicesParallax() {
            const servicesTitle = document.querySelector('.services-title');
            if (!servicesTitle) return;
            
            const parallaxConfig = {
                scrollSpeed: 0.5,  // Scroll a metade da velocidade
                fadeEnd: 500,      // Distância até fade completo
                blurMax: 6         // Blur máximo
            };
            
            onScroll(() => {
                const scrolled = window.scrollY;
                
                // Parallax offset
                const parallaxOffset = scrolled * parallaxConfig.scrollSpeed;
                
                // Fade out progressivo
                const progress = Math.min(scrolled / parallaxConfig.fadeEnd, 1);
                
                servicesTitle.style.transform = `translateY(${parallaxOffset}px)`;
                servicesTitle.style.opacity = 1 - progress;
                servicesTitle.style.filter = `blur(${progress * parallaxConfig.blurMax}px)`;
            });
        }
        
        
        /**
         * Scroll Focus Effect - Destaca card mais próximo do centro
         */
        function initScrollFocus() {
            const cards = document.querySelectorAll('.service-card');
            if (!cards.length) return;
            
            function updateFocusedCard() {
                const viewportCenter = window.innerHeight / 2;
                let closestCard = null;
                let minDistance = Infinity;
                
                cards.forEach((card, index) => {
                    const rect = card.getBoundingClientRect();
                    const cardCenter = rect.top + (rect.height / 2);
                    const distance = Math.abs(cardCenter - viewportCenter);
                    
                    if (distance < minDistance) {
                        minDistance = distance;
                        closestCard = card;
                    }
                    
                    // ✨ PARALLAX HORIZONTAL ALTERNADO
                    const image = card.querySelector('.service-image');
                    if (image) {
                        // Calcula posição relativa da imagem no viewport
                        const imageTop = rect.top;
                        const imageBottom = rect.bottom;
                        const viewportHeight = window.innerHeight;
                        
                        // Só aplica parallax se a imagem estiver visível
                        if (imageBottom > 0 && imageTop < viewportHeight) {
                            // Normaliza posição de 0 a 1 (0 = topo da tela, 1 = fundo da tela)
                            const scrollProgress = (viewportHeight - imageTop) / (viewportHeight + rect.height);
                            
                            // Movimento máximo de 40px
                            const maxMove = 40;
                            const movement = (scrollProgress - 0.5) * maxMove;
                            
                            // Alterna direção: ímpares → direita, pares → esquerda
                            const direction = index % 2 === 0 ? 1 : -1;
                            const translateX = movement * direction;
                            
                            image.style.transform = `translateX(${translateX}px)`;
                        }
                    }
                });
                
                // Aplica classes de focus
                cards.forEach(card => {
                    if (card === closestCard) {
                        card.classList.add('focused');
                        card.classList.remove('unfocused');
                    } else {
                        card.classList.add('unfocused');
                        card.classList.remove('focused');
                    }
                });
            }
            
            // Executa na inicialização
            updateFocusedCard();
            
            // Executa durante scroll (otimizado com onScroll)
            onScroll(updateFocusedCard);
        }
    </script>
</body>
</html>
