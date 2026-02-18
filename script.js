/**
 * Wide Studio - Public Gallery Script (REFATORADO)
 * Galeria pública com filtros, lightbox e animações
 */

import {
    initLenis,
    initMobileMenu,
    setupLightboxClose,
    initRevealAnimations
} from './js/common.js';
import { getCookie, createPillIndicator, onScroll } from './js/utils.js';

document.addEventListener('DOMContentLoaded', () => {

    // Inicializa Lenis Smooth Scroll
    initLenis();

    // Inicializa componentes comuns
    initMobileMenu();

    // Lógica específica da galeria pública
    initFirstVisitAnimation();
    initParallaxEffect();
    initGalleryFilters();
    // initLightbox(); // Desabilitado: agora projetos linkam para página dedicada
    initMasonryLayout();
});

// =================================
// FIRST VISIT ANIMATION
// =================================

function initFirstVisitAnimation() {
    // Detecta se página foi recarregada
    const navEntry = performance.getEntriesByType('navigation')[0];
    const isReload = navEntry && navEntry.type === 'reload';

    if (isReload) {
        sessionStorage.removeItem('hasVisited');
    }

    const isFirstVisit = !sessionStorage.getItem('hasVisited');
    if (isFirstVisit) {
        sessionStorage.setItem('hasVisited', 'true');
    }

    const heroText = document.querySelector('.hero-text');
    const delayedRevealElements = document.querySelectorAll('.reveal');
    const topBar = document.querySelector('.top-bar');

    if (!heroText) {
        // Fallback se não houver hero text
        initRevealAnimations('.reveal');
        if (topBar) topBar.classList.add('visible');
        return;
    }

    // Salva HTML original para preservar pills
    const originalHTML = heroText.innerHTML.trim();
    heroText.innerHTML = '';

    // Função para extrair palavras e pills do HTML
    function parseTextWithPills(html) {
        const temp = document.createElement('div');
        temp.innerHTML = html;
        const items = [];

        // Processa cada nó de texto e elemento
        const processNode = (node) => {
            if (node.nodeType === Node.TEXT_NODE) {
                // Texto normal - divide em palavras
                const words = node.textContent.trim().split(/\s+/).filter(w => w);
                words.forEach(word => items.push({ type: 'word', content: word }));
            } else if (node.nodeType === Node.ELEMENT_NODE) {
                if (node.classList.contains('hero-pill')) {
                    // É um pill - mantém como elemento
                    items.push({ type: 'pill', element: node.cloneNode(true) });
                } else {
                    // Outros elementos - processa filhos
                    Array.from(node.childNodes).forEach(processNode);
                }
            }
        };

        Array.from(temp.childNodes).forEach(processNode);
        return items;
    }

    const items = parseTextWithPills(originalHTML);

    if (isFirstVisit) {
        // === PRIMEIRA VISITA: Animação completa ===
        const staggerTime = 70;

        items.forEach((item, index) => {
            const wrapper = document.createElement('span');
            wrapper.classList.add('hero-word');
            wrapper.style.transitionDelay = `${index * staggerTime}ms`;

            if (item.type === 'pill') {
                wrapper.appendChild(item.element);
            } else {
                wrapper.textContent = item.content;
            }

            heroText.appendChild(wrapper);
            // Adiciona espaço entre palavras
            if (index < items.length - 1) {
                heroText.appendChild(document.createTextNode(' '));
            }
        });

        // Inicia texto com delay
        setTimeout(() => {
            heroText.querySelectorAll('.hero-word').forEach(span => {
                span.classList.add('visible');
            });
        }, 100);

        // Libera galeria e header após animação do texto
        const totalAnimationTime = (items.length * staggerTime) + 1500;

        setTimeout(() => {
            initRevealAnimations('.reveal, .filter-container');
            if (topBar) topBar.classList.add('visible');
        }, totalAnimationTime);

    } else {
        // === NAVEGAÇÃO INTERNA: Fade suave apenas ===

        // Header imediato
        if (topBar) topBar.classList.add('visible');

        // Texto aparece imediato (sem word-by-word)
        items.forEach((item, index) => {
            const wrapper = document.createElement('span');
            wrapper.classList.add('hero-word', 'visible');
            wrapper.style.transitionDelay = '0ms';

            if (item.type === 'pill') {
                wrapper.appendChild(item.element);
            } else {
                wrapper.textContent = item.content;
            }

            heroText.appendChild(wrapper);
            // Adiciona espaço entre palavras
            if (index < items.length - 1) {
                heroText.appendChild(document.createTextNode(' '));
            }
        });

        // Conteúdo com fade rápido
        delayedRevealElements.forEach(el => {
            el.style.transition = 'opacity 0.4s ease, transform 0.4s ease';
        });

        // Pequeno delay antes de revelar
        setTimeout(() => {
            initRevealAnimations('.reveal, .filter-container');
        }, 50);
    }
}

// =================================
// PARALLAX EFFECT
// =================================

function initParallaxEffect() {
    const heroTextElement = document.querySelector('.hero-text');
    const heroSection = document.querySelector('.hero-section');
    const topBar = document.querySelector('.top-bar');

    const parallaxConfig = {
        heroScrollSpeed: 0.5, // ← Hero rola a metade da velocidade (parallax)
        heroFadeEnd: 700,
        blurMax: 6
    };

    onScroll(() => {
        const scrolled = window.scrollY;

        // Header scroll effect
        if (topBar) {
            topBar.classList.toggle('scrolled', scrolled > 50);
        }

        // Hero Text Parallax - Scroll Lento (efeito de profundidade)
        if (heroTextElement) {
            // Aplica translateY para simular scroll mais lento
            const parallaxOffset = scrolled * parallaxConfig.heroScrollSpeed;

            // Fade out progressivo
            const progress = Math.min(scrolled / parallaxConfig.heroFadeEnd, 1);

            heroTextElement.style.transform = `translateY(${parallaxOffset}px)`;
            heroTextElement.style.opacity = 1 - progress;
            heroTextElement.style.filter = `blur(${progress * parallaxConfig.blurMax}px)`;
        }
    });
}

// =================================
// GALLERY FILTERS
// =================================

function initGalleryFilters() {
    const filterButtons = document.querySelectorAll('.filter-btn');
    const galleryItems = document.querySelectorAll('.work-item');
    const galleryGrid = document.querySelector('.works-grid');
    const filtersContainer = document.querySelector('.gallery-filters');

    if (!filtersContainer || !filterButtons.length) return;

    const { movePillTo } = createPillIndicator(
        filtersContainer,
        filterButtons,
        'filter-pill-indicator'
    );

    filterButtons.forEach(btn => {
        btn.addEventListener('click', () => {
            // Update active button
            filterButtons.forEach(b => b.classList.remove('active'));
            btn.classList.add('active');

            // Move pill indicator
            if (movePillTo) movePillTo(btn);

            // Apply filter
            applyFilter(btn.dataset.filter, galleryItems, galleryGrid);
        });
    });
}

function applyFilter(filterValue, items, grid) {
    if (!grid) return;

    // Fade out entire gallery
    grid.style.transition = 'opacity 0.25s ease';
    grid.style.opacity = '0';

    // After fade out, apply filter and fade in
    setTimeout(() => {
        items.forEach(item => {
            const itemType = item.dataset.type || '';
            const shouldShow = filterValue === 'all' || itemType === filterValue;

            if (shouldShow) {
                item.style.display = 'block';
            } else {
                item.style.display = 'none';
            }
        });

        // Recalcular Masonry layout após filtrar
        if (window.recalculateMasonry) {
            window.recalculateMasonry();
        }

        // Fade in
        grid.style.opacity = '1';
    }, 250);
}

// =================================
// LIGHTBOX
// =================================

function initLightbox() {
    const lightboxModal = document.querySelector('.lightbox-modal');
    const workItems = document.querySelectorAll('.work-item');

    if (!lightboxModal || !workItems.length) return;

    // Setup close handlers e captura a função close
    const lightboxCloser = setupLightboxClose(lightboxModal);
    const closeLightbox = lightboxCloser ? lightboxCloser.close : null;

    // Adicionar evento de click fora para fechar
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

    // Advanced Zoom functionality with scroll and drag
    const lightboxImg = lightboxModal.querySelector('.lightbox-img');
    const imageContainer = lightboxModal.querySelector('.lightbox-image-container');

    let zoomLevel = 1;
    let isDragging = false;
    let startX = 0;
    let startY = 0;
    let translateX = 0;
    let translateY = 0;

    if (lightboxImg && imageContainer) {
        // Click no container (fora da imagem) fecha o lightbox
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

        // Click to toggle zoom (1x or 1.5x)
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

            // Só faz toggle se não arrastou
            if (!hasMoved && zoomLevel <= 1) {
                zoomLevel = 1.5;
                applyZoom();
            } else if (!hasMoved && zoomLevel > 1) {
                zoomLevel = 1;
                applyZoom();
            }
        });

        // Drag to pan (only when zoomed)
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

            // Calculate new position
            let newTranslateX = e.clientX - startX;
            let newTranslateY = e.clientY - startY;

            // Calculate bounds to prevent dragging too far
            if (lightboxImg && imageContainer && zoomLevel > 1) {
                const imgRect = lightboxImg.getBoundingClientRect();
                const containerRect = imageContainer.getBoundingClientRect();

                // Calculate the scaled dimensions
                const scaledWidth = imgRect.width;
                const scaledHeight = imgRect.height;

                // Permite que até 70% da imagem saia da view
                const minVisiblePercent = 0.3;

                const maxTranslateX = ((scaledWidth * (1 - minVisiblePercent)) + (containerRect.width * minVisiblePercent)) / 2 / zoomLevel;
                const maxTranslateY = ((scaledHeight * (1 - minVisiblePercent)) + (containerRect.height * minVisiblePercent)) / 2 / zoomLevel;

                // Durante o drag, permite ultrapassar limite com resistência (efeito mola)
                const resistance = 0.3; // Resistência ao ultrapassar limite

                if (Math.abs(newTranslateX) > maxTranslateX) {
                    const excess = Math.abs(newTranslateX) - maxTranslateX;
                    newTranslateX = (newTranslateX > 0 ? 1 : -1) * (maxTranslateX + excess * resistance);
                }

                if (Math.abs(newTranslateY) > maxTranslateY) {
                    const excess = Math.abs(newTranslateY) - maxTranslateY;
                    newTranslateY = (newTranslateY > 0 ? 1 : -1) * (maxTranslateY + excess * resistance);
                }
            }

            translateX = newTranslateX;
            translateY = newTranslateY;
            applyZoom(false); // Sem transição durante drag
        });

        document.addEventListener('mouseup', () => {
            if (isDragging) {
                isDragging = false;

                // Efeito elástico: volta para dentro dos limites se ultrapassou
                if (lightboxImg && imageContainer && zoomLevel > 1) {
                    const imgRect = lightboxImg.getBoundingClientRect();
                    const containerRect = imageContainer.getBoundingClientRect();

                    const scaledWidth = imgRect.width;
                    const scaledHeight = imgRect.height;

                    const minVisiblePercent = 0.3;

                    const maxTranslateX = ((scaledWidth * (1 - minVisiblePercent)) + (containerRect.width * minVisiblePercent)) / 2 / zoomLevel;
                    const maxTranslateY = ((scaledHeight * (1 - minVisiblePercent)) + (containerRect.height * minVisiblePercent)) / 2 / zoomLevel;

                    // Verifica se ultrapassou os limites
                    const wasOutOfBounds = Math.abs(translateX) > maxTranslateX || Math.abs(translateY) > maxTranslateY;

                    // Aplica limites
                    translateX = Math.max(-maxTranslateX, Math.min(maxTranslateX, translateX));
                    translateY = Math.max(-maxTranslateY, Math.min(maxTranslateY, translateY));

                    // Volta com animação elástica se estava fora dos limites
                    if (wasOutOfBounds) {
                        applyZoom(true); // Com transição elástica
                    }
                }

                if (lightboxImg) {
                    lightboxImg.style.cursor = zoomLevel > 1 ? 'grab' : 'zoom-in';
                }
            }
        });

        // Apply zoom transformation
        function applyZoom(useTransition = false) {
            const isZoomed = zoomLevel > 1;

            // Apply transition for elastic effect
            if (useTransition) {
                lightboxImg.style.transition = 'transform 0.4s cubic-bezier(0.34, 1.56, 0.64, 1)';
            } else {
                lightboxImg.style.transition = 'none';
            }

            // Apply transform
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

            // Update container and hide scrollbar when zoomed
            if (imageContainer) {
                imageContainer.classList.toggle('zoomed', isZoomed);
                imageContainer.style.overflow = isZoomed ? 'hidden' : 'hidden';
            }
        }

        // Reset zoom function
        window.resetLightboxZoom = function () {
            zoomLevel = 1;
            translateX = 0;
            translateY = 0;
            isDragging = false;
            applyZoom();
        };
    }

    // Function to update lightbox with current item
    function updateLightbox(index) {
        const item = workItems[index];
        if (!item) return;

        const img = item.querySelector('img');
        if (!img) return;

        currentIndex = index;

        // Reset zoom state
        if (typeof window.resetLightboxZoom === 'function') {
            window.resetLightboxZoom();
        }

        // Populate only image
        lightboxModal.querySelector('.lightbox-img').src = img.src;

        // Update navigation buttons
        updateNavigationButtons();
    }

    // Function to update navigation button states
    function updateNavigationButtons() {
        if (!navPrev || !navNext) return;

        // Disable prev if at first item
        navPrev.disabled = currentIndex === 0;

        // Disable next if at last item
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
        // Only if lightbox is open
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

            // Show lightbox
            lightboxModal.classList.add('open');

            // Bloqueia scroll tanto em html quanto em body
            document.documentElement.style.overflow = 'hidden';
            document.body.style.overflow = 'hidden';
            document.body.style.paddingRight = '0';
        });
    });

    // =================================
    // SWIPE TO CLOSE (Mobile Only)
    // =================================

    let touchStartY = 0;
    let touchCurrentY = 0;
    let isSwiping = false;
    const lightboxContent = lightboxModal.querySelector('.lightbox-content');

    // Detectar início do toque
    lightboxModal.addEventListener('touchstart', (e) => {
        // Só ativa se não estiver com zoom
        if (zoomLevel > 1) return;

        touchStartY = e.touches[0].clientY;
        isSwiping = true;

        // Remove transição para movimento fluido
        if (lightboxContent) {
            lightboxContent.style.transition = 'none';
        }
        if (lightboxImg) {
            lightboxImg.style.transition = 'none';
        }
    }, { passive: true });

    // Detectar movimento do dedo
    lightboxModal.addEventListener('touchmove', (e) => {
        if (!isSwiping || zoomLevel > 1) return;

        touchCurrentY = e.touches[0].clientY;
        const deltaY = touchCurrentY - touchStartY;

        // Só permite arrastar para baixo
        if (deltaY > 0) {
            // Aplica transformação com resistência
            const resistance = 0.6; // Menos resistência = mais fácil de arrastar
            const translateY = deltaY * resistance;
            const opacity = Math.max(0.3, 1 - (deltaY / 800));

            // Scale down effect - diminui conforme arrasta (iPhone style)
            const scale = Math.max(0.85, 1 - (deltaY / 1200));

            // Aplica no background
            if (lightboxModal) {
                lightboxModal.style.opacity = opacity;
            }

            // Aplica transformação na imagem para efeito visual
            if (lightboxImg) {
                lightboxImg.style.transform = `translateY(${translateY}px) scale(${scale})`;
            }
        }
    }, { passive: true });

    // Detectar fim do toque
    lightboxModal.addEventListener('touchend', (e) => {
        if (!isSwiping || zoomLevel > 1) return;

        const deltaY = touchCurrentY - touchStartY;
        const threshold = 150; // Distância mínima para fechar

        // Adiciona transição para animação suave
        if (lightboxImg) {
            lightboxImg.style.transition = 'transform 0.4s cubic-bezier(0.16, 1, 0.3, 1)';
        }
        if (lightboxModal) {
            lightboxModal.style.transition = 'opacity 0.4s ease';
        }

        if (deltaY > threshold) {
            // Fecha o lightbox com animação
            if (lightboxImg) {
                lightboxImg.style.transform = 'translateY(100vh) scale(0.7)';
            }
            if (lightboxModal) {
                lightboxModal.style.opacity = '0';
            }

            setTimeout(() => {
                // Usa a função close capturada
                if (closeLightbox) {
                    closeLightbox();
                }

                // Reset completo
                if (lightboxImg) {
                    lightboxImg.style.transform = '';
                    lightboxImg.style.transition = '';
                }
                if (lightboxModal) {
                    lightboxModal.style.opacity = '';
                    lightboxModal.style.transition = '';
                }
            }, 300);
        } else {
            // Volta para posição original (snap back)
            if (lightboxImg) {
                lightboxImg.style.transform = '';
            }
            if (lightboxModal) {
                lightboxModal.style.opacity = '';
            }
        }

        isSwiping = false;
        touchStartY = 0;
        touchCurrentY = 0;
    }, { passive: true });
}

// =================================
// MASONRY LAYOUT - Algoritmo "Coluna Mais Curta"
// =================================

function initMasonryLayout() {
    const grid = document.querySelector('.works-grid');
    const items = document.querySelectorAll('.work-item');

    if (!grid || !items.length) return;

    // Função de layout
    function layoutMasonry() {
        const columns = getColumnCount();
        const gap = getGapSize();
        const gridWidth = grid.offsetWidth;
        const itemWidth = (gridWidth - (gap * (columns - 1))) / columns;
        const columnHeights = new Array(columns).fill(0);

        items.forEach(item => {
            if (item.style.display === 'none') return;

            // GARANTIR QUE ITEM TENHA WIDTH ANTES DE CALCULAR HEIGHT
            item.style.width = itemWidth + 'px';
            item.style.position = 'absolute';

            const shortestColumnIndex = columnHeights.indexOf(Math.min(...columnHeights));
            const x = shortestColumnIndex * (itemWidth + gap);
            const y = columnHeights[shortestColumnIndex];

            item.style.left = x + 'px';
            item.style.top = y + 'px';

            columnHeights[shortestColumnIndex] += item.offsetHeight + gap;
        });

        const maxHeight = Math.max(...columnHeights);
        grid.style.height = maxHeight + 'px';
        grid.style.opacity = '1';

        // Garante que o height seja aplicado imediatamente se for grid-fixed-ratio
        if (grid.classList.contains('grid-fixed-ratio')) {
            grid.style.minHeight = maxHeight + 'px';
        }
    }

    // Função para obter número de colunas
    function getColumnCount() {
        const width = window.innerWidth;
        const adminColumns = parseInt(grid.dataset.columns) || 4;
        if (width <= 600) return 2;
        if (width <= 900) return 3;
        if (width <= 1200) return Math.min(adminColumns, 4);
        return Math.min(adminColumns, 5);
    }

    // Função para obter gap
    function getGapSize() {
        return parseInt(grid.dataset.gap) || 10;
    }

    // Expor para global
    window.recalculateMasonry = layoutMasonry;

    // Executar layout inicial
    layoutMasonry();

    // Re-executar quando imagens carregarem (inclusive cacheadas)
    items.forEach(item => {
        const img = item.querySelector('img');
        if (img) {
            if (img.complete) {
                layoutMasonry();
            } else {
                img.addEventListener('load', layoutMasonry);
                img.addEventListener('error', layoutMasonry); // Mesmo se falhar, recalcula
            }
        }
    });

    // Forced recalculate after small delay (para fonts carregarem)
    setTimeout(layoutMasonry, 100);
    setTimeout(layoutMasonry, 500);
    setTimeout(layoutMasonry, 2000); // Segurança final

    // Resize handler com debounce
    let resizeTimer;
    window.addEventListener('resize', () => {
        clearTimeout(resizeTimer);
        resizeTimer = setTimeout(layoutMasonry, 100);
    });

    // Expor função de recálculo globalmente para os filtros
    window.recalculateMasonry = layoutMasonry;
}
