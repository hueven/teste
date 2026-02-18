/**
 * Wide Studio - Project Page Script
 * Reutiliza lógica da galeria pública para página de projeto
 */

import {
    initLenis,
    initMobileMenu,
    setupLightboxClose,
    initRevealAnimations
} from './js/common.js';

document.addEventListener('DOMContentLoaded', () => {

    // Inicializa Lenis Smooth Scroll
    initLenis();

    // Inicializa componentes comuns
    initMobileMenu();

    // Inicializa animações de reveal
    initRevealAnimations('.reveal');

    // Inicializa lightbox e masonry
    initLightbox();
    initMasonryLayout();
    initHeaderScroll();
});

// =================================
// HEADER SCROLL EFFECT
// =================================

function initHeaderScroll() {
    const topBar = document.querySelector('.top-bar');
    if (!topBar) return;

    // Header já visível desde o início
    topBar.classList.add('visible');

    window.addEventListener('scroll', () => {
        if (window.scrollY > 50) {
            topBar.classList.add('scrolled');
        } else {
            topBar.classList.remove('scrolled');
        }
    });
}

// =================================
// LIGHTBOX
// =================================

function initLightbox() {
    const lightboxModal = document.querySelector('.lightbox-modal');
    const workItems = document.querySelectorAll('.work-item');

    if (!lightboxModal || !workItems.length) return;

    // Setup close handlers
    const lightboxCloser = setupLightboxClose(lightboxModal);
    const closeLightbox = lightboxCloser ? lightboxCloser.close : null;

    // Click fora para fechar
    const lightboxOverlay = lightboxModal.querySelector('.lightbox-overlay');
    if (lightboxOverlay) {
        lightboxOverlay.addEventListener('click', () => {
            if (closeLightbox) closeLightbox();
        });
    }

    // Navigation system
    let currentIndex = 0;
    const navPrev = lightboxModal.querySelector('.lightbox-nav-prev');
    const navNext = lightboxModal.querySelector('.lightbox-nav-next');

    // Zoom functionality
    const lightboxImg = lightboxModal.querySelector('.lightbox-img');
    const imageContainer = lightboxModal.querySelector('.lightbox-image-container');

    let zoomLevel = 1;
    let isDragging = false;
    let startX = 0;
    let startY = 0;
    let translateX = 0;
    let translateY = 0;

    if (lightboxImg && imageContainer) {
        // Click no container fecha o lightbox
        imageContainer.addEventListener('click', (e) => {
            if (e.target === imageContainer) {
                if (closeLightbox) closeLightbox();
            }
        });

        // Zoom with mouse wheel
        imageContainer.addEventListener('wheel', (e) => {
            e.preventDefault();
            const delta = e.deltaY * -0.001;
            const newZoom = Math.min(Math.max(zoomLevel + delta, 1), 1.5);
            if (newZoom !== zoomLevel) {
                zoomLevel = newZoom;
                applyZoom();
            }
        }, { passive: false });

        // Click to toggle zoom
        let clickStartX = 0;
        let clickStartY = 0;
        let hasMoved = false;

        lightboxImg.addEventListener('mousedown', (e) => {
            clickStartX = e.clientX;
            clickStartY = e.clientY;
            hasMoved = false;
        });

        lightboxImg.addEventListener('mousemove', (e) => {
            if (Math.abs(e.clientX - clickStartX) > 5 || Math.abs(e.clientY - clickStartY) > 5) {
                hasMoved = true;
            }
        });

        lightboxImg.addEventListener('click', (e) => {
            e.stopPropagation();
            if (!hasMoved && zoomLevel <= 1) {
                zoomLevel = 1.5;
                applyZoom();
            } else if (!hasMoved && zoomLevel > 1) {
                zoomLevel = 1;
                applyZoom();
            }
        });

        // Drag to pan
        lightboxImg.addEventListener('mousedown', (e) => {
            if (zoomLevel <= 1) return;
            isDragging = true;
            startX = e.clientX - translateX;
            startY = e.clientY - translateY;
            lightboxImg.style.cursor = 'grabbing';
            e.preventDefault();
        });

        document.addEventListener('mousemove', (e) => {
            if (!isDragging) return;
            translateX = e.clientX - startX;
            translateY = e.clientY - startY;
            applyZoom(false);
        });

        document.addEventListener('mouseup', () => {
            if (isDragging) {
                isDragging = false;
                if (lightboxImg) {
                    lightboxImg.style.cursor = zoomLevel > 1 ? 'grab' : 'zoom-in';
                }
            }
        });

        function applyZoom(useTransition = false) {
            const isZoomed = zoomLevel > 1;

            if (useTransition) {
                lightboxImg.style.transition = 'transform 0.4s cubic-bezier(0.34, 1.56, 0.64, 1)';
            } else {
                lightboxImg.style.transition = 'none';
            }

            if (isZoomed) {
                lightboxImg.classList.add('zoomed');
                lightboxImg.style.transform = `scale(${zoomLevel}) translate(${translateX / zoomLevel}px, ${translateY / zoomLevel}px)`;
                lightboxImg.style.cursor = isDragging ? 'grabbing' : 'grab';
            } else {
                lightboxImg.classList.remove('zoomed');
                lightboxImg.style.transform = 'scale(1)';
                lightboxImg.style.cursor = 'zoom-in';
                translateX = 0;
                translateY = 0;
            }

            if (imageContainer) {
                imageContainer.classList.toggle('zoomed', isZoomed);
            }
        }

        window.resetLightboxZoom = function () {
            zoomLevel = 1;
            translateX = 0;
            translateY = 0;
            isDragging = false;
            applyZoom();
        };
    }

    // Update lightbox
    function updateLightbox(index) {
        const item = workItems[index];
        if (!item) return;

        const img = item.querySelector('img');
        if (!img) return;

        currentIndex = index;

        if (typeof window.resetLightboxZoom === 'function') {
            window.resetLightboxZoom();
        }

        lightboxModal.querySelector('.lightbox-img').src = img.src;
        updateNavigationButtons();
    }

    function updateNavigationButtons() {
        if (!navPrev || !navNext) return;
        navPrev.disabled = currentIndex === 0;
        navNext.disabled = currentIndex === workItems.length - 1;
    }

    // Navigation: Previous
    if (navPrev) {
        navPrev.addEventListener('click', (e) => {
            e.stopPropagation();
            if (currentIndex > 0) {
                updateLightbox(currentIndex - 1);
            }
        });
    }

    // Navigation: Next
    if (navNext) {
        navNext.addEventListener('click', (e) => {
            e.stopPropagation();
            if (currentIndex < workItems.length - 1) {
                updateLightbox(currentIndex + 1);
            }
        });
    }

    // Keyboard navigation
    document.addEventListener('keydown', (e) => {
        if (!lightboxModal.classList.contains('open')) return;

        if (e.key === 'ArrowLeft' && currentIndex > 0) {
            updateLightbox(currentIndex - 1);
        } else if (e.key === 'ArrowRight' && currentIndex < workItems.length - 1) {
            updateLightbox(currentIndex + 1);
        }
    });

    // Open lightbox on item click
    workItems.forEach((item, index) => {
        item.addEventListener('click', () => {
            updateLightbox(index);
            lightboxModal.classList.add('open');
            document.documentElement.style.overflow = 'hidden';
            document.body.style.overflow = 'hidden';
            document.body.style.paddingRight = '0';
        });
    });
}

// =================================
// MASONRY LAYOUT
// =================================

function initMasonryLayout() {
    const grid = document.querySelector('.works-grid');
    const items = document.querySelectorAll('.work-item');

    if (!grid || !items.length) return;

    function layoutMasonry() {
        const columns = getColumnCount();
        const gap = getGapSize();
        const gridWidth = grid.offsetWidth;
        const itemWidth = (gridWidth - (gap * (columns - 1))) / columns;
        const columnHeights = new Array(columns).fill(0);

        items.forEach(item => {
            if (item.style.display === 'none') return;

            const shortestColumnIndex = columnHeights.indexOf(Math.min(...columnHeights));
            const x = shortestColumnIndex * (itemWidth + gap);
            const y = columnHeights[shortestColumnIndex];

            item.style.width = itemWidth + 'px';
            item.style.left = x + 'px';
            item.style.top = y + 'px';

            columnHeights[shortestColumnIndex] += item.offsetHeight + gap;
        });

        const maxHeight = Math.max(...columnHeights);
        grid.style.height = (maxHeight - gap) + 'px';
    }

    function getColumnCount() {
        const width = window.innerWidth;
        const adminColumns = parseInt(grid.dataset.columns) || 4;

        if (width <= 600) return 2;
        if (width <= 900) return 3;
        if (width <= 1200) return Math.min(adminColumns, 4);
        if (width <= 1400) return Math.min(adminColumns, 5);

        return adminColumns;
    }

    function getGapSize() {
        return parseInt(grid.dataset.gap) || 10;
    }

    // Execute layout após imagens carregarem
    let imagesLoaded = 0;
    const totalImages = items.length;

    items.forEach(item => {
        const img = item.querySelector('img');
        if (img) {
            if (img.complete) {
                imagesLoaded++;
                if (imagesLoaded === totalImages) {
                    layoutMasonry();
                }
            } else {
                img.addEventListener('load', () => {
                    imagesLoaded++;
                    if (imagesLoaded === totalImages) {
                        layoutMasonry();
                    }
                });
            }
        }
    });

    // Recalcular em resize
    let resizeTimer;
    window.addEventListener('resize', () => {
        clearTimeout(resizeTimer);
        resizeTimer = setTimeout(() => {
            layoutMasonry();
        }, 250);
    });

    window.recalculateMasonry = layoutMasonry;
}
