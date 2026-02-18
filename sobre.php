<?php
require_once 'config.php';

$defaultText = "Carolina Starke, natural de Blumenau - SC, é formada em Design Industrial pela PUC-PR e pelo Politécnico de Torino, na Itália, onde iniciou sua carreira como designer autônoma.\n\nSua abordagem é marcada pela simplicidade estética e pela atenção aos detalhes, buscando sempre um equilíbrio entre forma e função. Com um olho curioso e inquieto, suas inspirações surgem de experiências vividas e dos lugares que marcaram sua trajetória.\n\nPremiada em concursos como Brasil Design Award e Museu da Casa Brasileira, Carolina acredita que o verdadeiro design é aquele que se conecta de maneira genuína com as pessoas, sem excessos, mas carregado de significado e propósito.";

$aboutText = getSetting('about_text', $defaultText);
// Converter quebras de linha em <p>
$paragraphs = explode("\n\n", $aboutText);
$formattedText = "";
foreach($paragraphs as $p) {
    if(trim($p)) {
        $formattedText .= "<p>" . nl2br(htmlspecialchars($p)) . "</p>";
    }
}

$aboutImage = 'images/sobre/CarolinaStarke.png';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars(getSiteTitle(' - Sobre')); ?></title>
    
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

    <style>
    .about-container {
        max-width: 1400px;
        margin: 120px auto 80px;
        padding: 0 40px;
        min-height: calc(100vh - 300px);
        display: flex;
        align-items: center;
    }

    .about-content {
        display: grid;
        grid-template-columns: 0.75fr 1.2fr;
        gap: 100px;
        align-items: flex-start;
    }

    .about-title {
        font-family: 'Inter', sans-serif;
        font-size: 0.85rem;
        font-weight: 400;
        letter-spacing: 0.2em;
        text-transform: uppercase;
        margin-bottom: 40px;
        color: var(--text-color);
        opacity: 0.6;
    }

    .about-bio {
        font-size: 1.05rem;
        line-height: 1.8;
        color: var(--text-color);
        text-align: justify;
        max-width: 600px;
    }

    .about-bio p {
        margin-bottom: 25px;
    }

    .about-image-wrapper {
        position: relative;
        max-width: 400px;
        margin-right: auto;
        margin-left: 0;
    }

    .about-profile-img {
        width: 100%;
        height: auto;
        display: block;
        filter: grayscale(20%);
        transition: filter 0.5s ease;
    }

    .about-profile-img:hover {
        filter: grayscale(0%);
    }

    /* Responsividade */
    @media (max-width: 1024px) {
        .about-content {
            gap: 60px;
        }
        .about-bio {
            font-size: 1.05rem;
        }
    }

    @media (max-width: 768px) {
        .about-container {
            margin-top: 80px;
        }
        .about-content {
            grid-template-columns: 1fr;
            gap: 50px;
        }
        .about-image-column {
            order: -1;
        }
        .about-image-wrapper {
            margin: 0 auto;
            max-width: 400px;
        }
        .about-title {
            text-align: center;
        }
    }
    </style>
</head>
<body>

    <!-- Header -->
    <?php include 'header.php'; ?>

    <main class="about-container">
        <div class="about-content">
            <div class="about-image-column">
                <div class="about-image-wrapper reveal">
                    <img src="<?php echo $aboutImage; ?>" alt="Carolina Starke" class="about-profile-img">
                </div>
            </div>
            <div class="about-text-column">
                <h1 class="about-title reveal">Sobre</h1>
                <div class="about-bio reveal">
                    <?php echo $formattedText; ?>
                </div>
            </div>
        </div>
    </main>

    <!-- Footer -->
    <?php include 'footer.php'; ?>

    <!-- Scripts -->
    <script src="https://unpkg.com/@studio-freight/lenis@1.0.42/dist/lenis.min.js"></script>
    <script type="module">
        import { initAll } from './js/common.js';
        
        // Em scripts do tipo module, o código já executa após o parse do DOM.
        // Chamamos initAll que cuida do Lenis, Menu e Reveal Animations.
        initAll();
        
        // Caso queira forçar a ativação imediata (opcional)
        window.addEventListener('load', () => {
            const reveals = document.querySelectorAll('.reveal');
            reveals.forEach((el, i) => {
                setTimeout(() => el.classList.add('active'), i * 100);
            });
        });
    </script>
</body>
</html>
