/**
 * Gallery Page JavaScript (REFATORADO)
 * Página inicial com galeria e serviços
 */

import {
    initCustomCursor,
    initDarkMode,
    initAccordion
} from './outro/js/common.js';

// Gallery data
const galleryData = [
    { src: 'imagens/01.webp' },
    { src: 'imagens/02.webp' },
    { src: 'imagens/03.webp' },
    { src: 'imagens/04.webp' },
];

// Initialize gallery
function initGallery() {
    const galleryGrid = document.getElementById('galleryGrid');

    if (!galleryGrid) return;

    // Create gallery items
    galleryData.forEach((item, index) => {
        const galleryItem = document.createElement('div');
        galleryItem.className = 'gallery-item';
        galleryItem.setAttribute('data-index', index);

        galleryItem.innerHTML = `
            <img src="${item.src}" alt="Wide Studio Gallery" loading="lazy">
        `;

        // Add click event for lightbox
        galleryItem.addEventListener('click', () => openLightbox(item.src));

        galleryGrid.appendChild(galleryItem);
    });
}

// Lightbox functionality
function setupLightbox() {
    // Create lightbox element
    const lightbox = document.createElement('div');
    lightbox.className = 'lightbox';
    lightbox.id = 'lightbox';

    lightbox.innerHTML = `
        <button class="lightbox-close" id="lightboxClose">&times;</button>
        <div class="lightbox-content" id="lightboxContent">
            <img src="" alt="Gallery Image">
        </div>
    `;

    document.body.appendChild(lightbox);

    // Close lightbox handlers
    const closeBtn = document.getElementById('lightboxClose');
    closeBtn.addEventListener('click', closeLightbox);

    lightbox.addEventListener('click', (e) => {
        if (e.target === lightbox) {
            closeLightbox();
        }
    });

    // ESC key to close
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && lightbox.classList.contains('active')) {
            closeLightbox();
        }
    });
}

function openLightbox(imageSrc) {
    const lightbox = document.getElementById('lightbox');
    const lightboxImg = lightbox.querySelector('img');

    lightboxImg.src = imageSrc;
    lightbox.classList.add('active');
    document.body.style.overflow = 'hidden';
}

function closeLightbox() {
    const lightbox = document.getElementById('lightbox');
    lightbox.classList.remove('active');
    document.body.style.overflow = '';
}

// Mobile Menu functionality
function setupMobileMenu() {
    const mobileMenuBtn = document.getElementById('mobileMenuBtn');
    const closeMenuBtn = document.getElementById('closeMenuBtn');
    const navOverlay = document.getElementById('navLinks');
    const mobileNavLinks = document.querySelectorAll('.mobile-nav-link');

    if (!mobileMenuBtn || !navOverlay) return;

    // Open menu
    mobileMenuBtn.addEventListener('click', () => {
        navOverlay.classList.add('active');
    });

    // Close menu
    if (closeMenuBtn) {
        closeMenuBtn.addEventListener('click', () => {
            navOverlay.classList.remove('active');
        });
    }

    // Close menu when clicking on a link
    mobileNavLinks.forEach(link => {
        link.addEventListener('click', () => {
            navOverlay.classList.remove('active');
        });
    });

    // Close menu when clicking outside
    navOverlay.addEventListener('click', (e) => {
        if (e.target === navOverlay) {
            navOverlay.classList.remove('active');
        }
    });
}

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    // Render layout
    if (typeof renderLayout === 'function') {
        renderLayout(); // From layout.js
    }

    // Initialize components
    initGallery();
    setupLightbox();
    initCustomCursor(); // Usa a função refatorada do common.js
    initDarkMode(); // Usa a função refatorada do common.js
    initAccordion('.service-header'); // Usa a função refatorada do common.js
    setupMobileMenu();
});
