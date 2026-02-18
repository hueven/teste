<?php
/**
 * index.php - MODERNIZED VERSION
 *
 * Home page using:
 * - New config.php with environment variables
 * - Safe error handling
 * - Secure session handling
 * - CSS-in-JS for inline styling (optional)
 */

require_once __DIR__ . '/config.php';

// Initialize session (but don't require auth for public page)
initSession();

// Get user info if authenticated
$isAuthenticated = isAuthenticated();
$user = $isAuthenticated ? [
    'id' => getCurrentUserId(),
    'email' => $_SESSION['user_email'] ?? null
] : null;

// Fetch settings and collections from database
try {
    // Get site settings
    $settings = $pdo->query(
        "SELECT * FROM settings LIMIT 1"
    )->fetch();

    // Get featured collections (public)
    $collections = $pdo->query(
        "SELECT id, name, slug, description, image_url
         FROM collections
         ORDER BY position ASC
         LIMIT 6"
    )->fetchAll();

} catch (PDOException $e) {
    // Handle error safely - don't expose DB error
    if (APP_DEBUG) {
        safeLog("Error fetching homepage data: " . $e->getMessage(), 'ERROR');
    }
    $settings = [];
    $collections = [];
}

// CSRF token for contact form (if exists)
$csrfToken = generateCsrfToken();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="<?php echo htmlspecialchars($settings['site_description'] ?? 'Photography Portfolio'); ?>">
    <title><?php echo htmlspecialchars($settings['site_title'] ?? 'Wide Studio'); ?></title>

    <link rel="stylesheet" href="/css/style.css">
    <link rel="stylesheet" href="/css/gallery.css">
    <link rel="stylesheet" href="/css/responsive.css">

    <!-- Preload critical images -->
    <?php if (!empty($collections)): ?>
        <?php foreach (array_slice($collections, 0, 3) as $collection): ?>
            <?php if ($collection['image_url']): ?>
                <link rel="preload" as="image" href="<?php echo htmlspecialchars($collection['image_url']); ?>">
            <?php endif; ?>
        <?php endforeach; ?>
    <?php endif; ?>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar">
        <div class="navbar-container">
            <div class="navbar-brand">
                <?php if ($settings['logo_url']): ?>
                    <img src="<?php echo htmlspecialchars($settings['logo_url']); ?>" alt="Logo" class="logo">
                <?php endif; ?>
                <h1><?php echo htmlspecialchars($settings['site_title'] ?? 'Wide Studio'); ?></h1>
            </div>

            <ul class="navbar-menu">
                <li><a href="#portfolio">Portfolio</a></li>
                <li><a href="#about">About</a></li>
                <li><a href="#contact">Contact</a></li>
                <?php if ($isAuthenticated): ?>
                    <li><a href="/admin-migrated.php" class="btn-admin">Admin</a></li>
                    <li><a href="/logout.php" class="btn-logout">Logout</a></li>
                <?php else: ?>
                    <li><a href="/login.php" class="btn-login">Login</a></li>
                <?php endif; ?>
            </ul>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero">
        <div class="hero-content">
            <h2>Welcome to <?php echo htmlspecialchars($settings['site_title'] ?? 'Wide Studio'); ?></h2>
            <p><?php echo htmlspecialchars($settings['site_description'] ?? 'Professional Photography'); ?></p>
            <a href="#portfolio" class="btn btn-primary">View Portfolio</a>
        </div>
    </section>

    <!-- Portfolio Section -->
    <section id="portfolio" class="portfolio-section">
        <div class="container">
            <h2>Portfolio</h2>
            <div class="portfolio-grid">
                <?php if (empty($collections)): ?>
                    <div class="placeholder">
                        <p>No collections available yet.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($collections as $collection): ?>
                        <article class="portfolio-item" data-collection-id="<?php echo $collection['id']; ?>">
                            <div class="portfolio-image">
                                <?php if ($collection['image_url']): ?>
                                    <img src="<?php echo htmlspecialchars($collection['image_url']); ?>"
                                         alt="<?php echo htmlspecialchars($collection['name']); ?>"
                                         loading="lazy">
                                <?php else: ?>
                                    <div class="placeholder-image">
                                        <p><?php echo htmlspecialchars($collection['name']); ?></p>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="portfolio-info">
                                <h3><?php echo htmlspecialchars($collection['name']); ?></h3>
                                <p><?php echo htmlspecialchars(substr($collection['description'] ?? '', 0, 100)); ?></p>
                                <a href="/project.php?id=<?php echo $collection['id']; ?>" class="link-more">
                                    View Collection →
                                </a>
                            </div>
                        </article>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <!-- About Section -->
    <section id="about" class="about-section">
        <div class="container">
            <h2>About</h2>
            <div class="about-content">
                <p><?php echo nl2br(htmlspecialchars($settings['about'] ?? 'Add your about text in admin settings.')); ?></p>
            </div>
        </div>
    </section>

    <!-- Contact Section -->
    <section id="contact" class="contact-section">
        <div class="container">
            <h2>Contact</h2>
            <form id="contactForm" class="contact-form" action="/api/contact" method="POST">
                <!-- CSRF Token (IMPORTANT!) -->
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                <input type="hidden" name="action" value="send">

                <div class="form-group">
                    <label for="name">Name *</label>
                    <input type="text" id="name" name="name" required>
                </div>

                <div class="form-group">
                    <label for="email">Email *</label>
                    <input type="email" id="email" name="email" required>
                </div>

                <div class="form-group">
                    <label for="message">Message *</label>
                    <textarea id="message" name="message" rows="5" required></textarea>
                </div>

                <button type="submit" class="btn btn-primary">Send Message</button>
            </form>

            <div class="contact-info">
                <?php if ($settings['contact_email']): ?>
                    <p>Email: <a href="mailto:<?php echo htmlspecialchars($settings['contact_email']); ?>">
                        <?php echo htmlspecialchars($settings['contact_email']); ?>
                    </a></p>
                <?php endif; ?>

                <div class="social-links">
                    <?php if ($settings['social_instagram']): ?>
                        <a href="<?php echo htmlspecialchars($settings['social_instagram']); ?>" target="_blank">Instagram</a>
                    <?php endif; ?>
                    <?php if ($settings['social_twitter']): ?>
                        <a href="<?php echo htmlspecialchars($settings['social_twitter']); ?>" target="_blank">Twitter</a>
                    <?php endif; ?>
                    <?php if ($settings['social_facebook']): ?>
                        <a href="<?php echo htmlspecialchars($settings['social_facebook']); ?>" target="_blank">Facebook</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <p>&copy; 2024 <?php echo htmlspecialchars($settings['site_title'] ?? 'Wide Studio'); ?>. All rights reserved.</p>
        </div>
    </footer>

    <!-- Scripts -->
    <script type="module">
        import AdminAPI from '/js/AdminAPI.js';
        import AdminState from '/js/AdminState.js';

        // Initialize for contact form
        const state = new AdminState();
        const api = new AdminAPI(state, '/api');

        // Set CSRF token
        state.setCsrfToken(document.querySelector('input[name="csrf_token"]').value);

        // Contact form handling
        const contactForm = document.getElementById('contactForm');
        if (contactForm) {
            contactForm.addEventListener('submit', async (e) => {
                e.preventDefault();

                state.setLoading(true);
                state.clearError();

                const formData = new FormData(contactForm);

                try {
                    const response = await fetch('/api/contact', {
                        method: 'POST',
                        credentials: 'include',
                        body: formData
                    });

                    const data = await response.json();

                    if (data.success) {
                        alert('Message sent successfully!');
                        contactForm.reset();
                        state.setCsrfToken(document.querySelector('input[name="csrf_token"]').value);
                    } else {
                        state.setError(data.data.error || 'Failed to send message');
                    }
                } catch (error) {
                    state.setError('Error sending message');
                    console.error('Contact form error:', error);
                } finally {
                    state.setLoading(false);
                }
            });
        }

        // Portfolio item click handler
        document.querySelectorAll('.portfolio-item').forEach(item => {
            item.addEventListener('click', () => {
                const collectionId = item.dataset.collectionId;
                window.location.href = `/project.php?id=${collectionId}`;
            });
        });

        // Smooth scrolling for anchor links
        document.querySelectorAll('a[href^="#"]').forEach(link => {
            link.addEventListener('click', (e) => {
                e.preventDefault();
                const target = document.querySelector(link.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({ behavior: 'smooth' });
                }
            });
        });
    </script>

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            line-height: 1.6;
            color: #333;
        }

        /* Navigation */
        .navbar {
            background: white;
            border-bottom: 1px solid #eee;
            position: sticky;
            top: 0;
            z-index: 100;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }

        .navbar-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 1rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .navbar-brand {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .logo {
            height: 40px;
        }

        .navbar-menu {
            display: flex;
            list-style: none;
            gap: 2rem;
            align-items: center;
        }

        .navbar-menu a {
            text-decoration: none;
            color: #333;
            transition: color 0.2s;
        }

        .navbar-menu a:hover {
            color: #007bff;
        }

        .btn-admin, .btn-login, .btn-logout {
            padding: 0.5rem 1rem;
            border-radius: 4px;
            border: 1px solid #ddd;
        }

        .btn-admin, .btn-login {
            background: #007bff;
            color: white;
            border: none;
        }

        .btn-logout {
            background: #dc3545;
            color: white;
            border: none;
        }

        /* Container */
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 2rem;
        }

        /* Hero */
        .hero {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 6rem 2rem;
            text-align: center;
        }

        .hero-content h2 {
            font-size: 3rem;
            margin-bottom: 1rem;
        }

        .hero-content p {
            font-size: 1.5rem;
            margin-bottom: 2rem;
            opacity: 0.9;
        }

        /* Button */
        .btn {
            display: inline-block;
            padding: 0.75rem 1.5rem;
            border-radius: 4px;
            text-decoration: none;
            border: none;
            cursor: pointer;
            transition: all 0.2s;
            font-size: 1rem;
        }

        .btn-primary {
            background: #007bff;
            color: white;
        }

        .btn-primary:hover {
            background: #0056b3;
            transform: translateY(-2px);
        }

        /* Sections */
        section {
            padding: 4rem 0;
        }

        section h2 {
            font-size: 2.5rem;
            margin-bottom: 3rem;
            text-align: center;
        }

        /* Portfolio */
        .portfolio-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 2rem;
        }

        .portfolio-item {
            background: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            cursor: pointer;
            transition: transform 0.2s;
        }

        .portfolio-item:hover {
            transform: translateY(-4px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }

        .portfolio-image {
            width: 100%;
            height: 250px;
            background: #f0f0f0;
            overflow: hidden;
        }

        .portfolio-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .placeholder-image {
            width: 100%;
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #e9ecef;
            color: #999;
        }

        .portfolio-info {
            padding: 1.5rem;
        }

        .link-more {
            color: #007bff;
            text-decoration: none;
            font-weight: 600;
            transition: color 0.2s;
        }

        .link-more:hover {
            color: #0056b3;
        }

        /* About */
        .about-content {
            max-width: 600px;
            margin: 0 auto;
            text-align: center;
            line-height: 1.8;
        }

        /* Contact */
        .contact-form {
            max-width: 500px;
            margin: 2rem auto;
            background: #f9f9f9;
            padding: 2rem;
            border-radius: 8px;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
        }

        .form-group input,
        .form-group textarea {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-family: inherit;
            font-size: 1rem;
        }

        .contact-info {
            text-align: center;
            margin-top: 2rem;
        }

        .social-links {
            display: flex;
            justify-content: center;
            gap: 1rem;
            margin-top: 1rem;
        }

        .social-links a {
            color: #007bff;
            text-decoration: none;
            transition: color 0.2s;
        }

        .social-links a:hover {
            color: #0056b3;
        }

        /* Footer */
        .footer {
            background: #333;
            color: white;
            text-align: center;
            padding: 2rem;
            margin-top: 4rem;
        }

        .placeholder {
            text-align: center;
            padding: 3rem;
            color: #999;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .navbar-container {
                flex-direction: column;
                gap: 1rem;
            }

            .navbar-menu {
                flex-wrap: wrap;
                justify-content: center;
                gap: 1rem;
            }

            .hero-content h2 {
                font-size: 2rem;
            }

            .portfolio-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</body>
</html>
