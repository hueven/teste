/**
 * Wide Studio - Common JavaScript
 * Funcionalidades compartilhadas entre todas as páginas
 */

import { onScroll, createPillIndicator } from './utils.js';

// =================================
// LENIS SMOOTH SCROLL
// =================================

/**
 * Inicializa Lenis Smooth Scroll
 * Biblioteca premium para scroll suave com física e inércia
 * @returns {Lenis|null} Instância do Lenis ou null se não disponível
 */
export function initLenis() {
    // Previne múltiplas inicializações
    if (window.lenis) return window.lenis;

    // Verifica se Lenis está disponível no window
    if (typeof Lenis === 'undefined') {
        console.warn('Lenis não está disponível. Certifique-se de incluir o script CDN.');
        return null;
    }

    const lenis = new Lenis({
        duration: 1.2,        // Duração da suavização
        easing: (t) => Math.min(1, 1.001 - Math.pow(2, -10 * t)),
        orientation: 'vertical',
        gestureOrientation: 'vertical',
        smoothWheel: true,
        wheelMultiplier: 1,
        touchMultiplier: 2,
        infinite: false,
    });

    // Adiciona classe auxiliar no HTML (útil para CSS)
    document.documentElement.classList.add('lenis');

    // Integra com requestAnimationFrame para performance
    function raf(time) {
        lenis.raf(time);
        requestAnimationFrame(raf);
    }
    requestAnimationFrame(raf);

    // Exporta para uso global
    window.lenis = lenis;

    return lenis;
}

// =================================
// CUSTOM CURSOR
// =================================

/**
 * Inicializa o cursor customizado
 * Remove duplicações - verifica se já existe antes de criar
 */
export function initCustomCursor() {
    // Previne duplicação - se já existe, não cria novamente
    if (document.getElementById('custom-cursor')) {
        return;
    }

    const cursor = document.createElement('div');
    cursor.id = 'custom-cursor';
    document.body.appendChild(cursor);

    document.addEventListener('mousemove', (e) => {
        cursor.style.left = `${e.clientX}px`;
        cursor.style.top = `${e.clientY}px`;
    });

    // Configura hover states para elementos interativos
    setupInteractiveHover();
}

// =================================
// INTERACTIVE HOVER - DESABILITADO (cursor customizado removido)
// =================================

/**
 * Função desabilitada - era usada para cursor customizado
 */
export function setupInteractiveHover(additionalSelectors = '') {
    // Desabilitado - cursores padrão não precisam dessa função
    return;
}

// =================================
// MOBILE MENU
// =================================

/**
 * Inicializa o menu mobile
 * Funciona com qualquer estrutura de menu que use .menu-trigger e .menu-overlay
 */
export function initMobileMenu() {
    const menuTrigger = document.querySelector('.menu-trigger');
    const menuOverlay = document.querySelector('.menu-overlay');

    if (!menuTrigger || !menuOverlay) return;

    /**
     * Toggle do menu
     * @param {boolean} open - Se deve abrir ou fechar
     */
    const toggleMenu = (open) => {
        menuTrigger.classList.toggle('active', open);
        menuOverlay.classList.toggle('open', open);
        document.body.style.overflow = open ? 'hidden' : '';
    };

    // Abre/fecha ao clicar no trigger
    menuTrigger.addEventListener('click', () => {
        toggleMenu(!menuOverlay.classList.contains('open'));
    });

    // Fecha ao clicar nos links
    const menuLinks = document.querySelectorAll('.menu-link, .mobile-nav-link');
    menuLinks.forEach(link => {
        link.addEventListener('click', () => toggleMenu(false));
    });

    // Fecha ao clicar no overlay (fundo)
    menuOverlay.addEventListener('click', (e) => {
        if (e.target === menuOverlay) {
            toggleMenu(false);
        }
    });
}

// =================================
// HEADER SCROLL EFFECT
// =================================

/**
 * Inicializa efeito de scroll no header
 * Usa onScroll otimizado do utils.js
 */
export function initHeaderScroll() {
    const topBar = document.querySelector('.top-bar');
    if (!topBar) return;

    // Usa o helper otimizado de scroll
    onScroll(() => {
        topBar.classList.toggle('scrolled', window.scrollY > 50);
    });
}

// =================================
// ACCORDION
// =================================

/**
 * Inicializa accordion para serviços ou outros elementos
 * @param {string} selector - Seletor dos itens do accordion
 */
export function initAccordion(selector = '.service-item, .service-header') {
    const items = document.querySelectorAll(selector);
    if (!items.length) return;

    items.forEach(item => {
        item.addEventListener('click', () => {
            const parent = item.classList.contains('service-header')
                ? item.parentElement
                : item;

            // Fecha outros accordions
            const allItems = document.querySelectorAll(selector);
            allItems.forEach(other => {
                const otherParent = other.classList.contains('service-header')
                    ? other.parentElement
                    : other;

                if (otherParent !== parent) {
                    otherParent.classList.remove('active');
                }
            });

            // Toggle do item atual
            parent.classList.toggle('active');
        });
    });
}

// =================================
// REVEAL ANIMATIONS
// =================================

/**
 * Inicializa animações de reveal no scroll
 * @param {string} selector - Seletor dos elementos a revelar
 * @param {Object} options - Opções do IntersectionObserver
 */
export function initRevealAnimations(selector = '.reveal', options = {}) {
    const defaultOptions = {
        root: null,
        threshold: 0.1,
        rootMargin: "0px"
    };

    const observerOptions = { ...defaultOptions, ...options };

    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('active');
                observer.unobserve(entry.target);
            }
        });
    }, observerOptions);

    document.querySelectorAll(selector).forEach(el => {
        observer.observe(el);
    });

    return observer;
}

// =================================
// LIGHTBOX HELPERS
// =================================

/**
 * Configura handlers de fechamento para lightbox
 * @param {HTMLElement} lightbox - Elemento do lightbox
 * @param {Function} onCloseFn - Callback opcional ao fechar
 * @returns {Function} Função para fechar o lightbox
 */
export function setupLightboxClose(lightbox, onCloseFn = null) {
    if (!lightbox) return null;

    const closeBtn = lightbox.querySelector('.lightbox-close');
    const overlay = lightbox.querySelector('.lightbox-overlay');

    /**
     * Fecha o lightbox
     */
    const close = () => {
        lightbox.classList.remove('open', 'active');
        // Restaura scroll tanto em html quanto em body
        document.documentElement.style.overflow = '';
        document.body.style.overflow = '';
        document.body.style.paddingRight = '';
        if (onCloseFn) onCloseFn();
    };

    // Fechar com botão X
    if (closeBtn) {
        closeBtn.addEventListener('click', close);
    }

    // Fechar clicando no overlay
    if (overlay) {
        overlay.addEventListener('click', close);
    }

    // Fechar clicando fora do conteúdo
    lightbox.addEventListener('click', (e) => {
        if (e.target === lightbox) {
            close();
        }
    });

    // Fechar com tecla ESC
    const escHandler = (e) => {
        if (e.key === 'Escape' && lightbox.classList.contains('open')) {
            close();
        }
    };

    document.addEventListener('keydown', escHandler);

    // Retorna a função close e cleanup
    return {
        close,
        cleanup: () => {
            document.removeEventListener('keydown', escHandler);
        }
    };
}

// =================================
// FORM VALIDATION
// =================================

/**
 * Valida formulário simples
 * @param {HTMLFormElement} form - Formulário a validar
 * @returns {boolean} True se válido
 */
export function validateForm(form) {
    if (!form) return false;

    const inputs = form.querySelectorAll('input[required], textarea[required], select[required]');
    let isValid = true;

    inputs.forEach(input => {
        if (!input.value.trim()) {
            input.classList.add('error');
            isValid = false;
        } else {
            input.classList.remove('error');
        }
    });

    return isValid;
}

/**
 * Limpa validação de formulário
 * @param {HTMLFormElement} form - Formulário a limpar
 */
export function clearFormValidation(form) {
    if (!form) return;

    form.querySelectorAll('.error').forEach(input => {
        input.classList.remove('error');
    });
}

// =================================
// SMOOTH SCROLL TO ELEMENT
// =================================

/**
 * Faz scroll suave até um elemento
 * @param {string|HTMLElement} target - Seletor ou elemento alvo
 * @param {number} offset - Offset do topo (para headers fixos)
 */
export function scrollToElement(target, offset = 0) {
    const element = typeof target === 'string'
        ? document.querySelector(target)
        : target;

    if (!element) return;

    const elementPosition = element.getBoundingClientRect().top;
    const offsetPosition = elementPosition + window.pageYOffset - offset;

    window.scrollTo({
        top: offsetPosition,
        behavior: 'smooth'
    });
}

// =================================
// INIT ALL
// =================================

/**
 * Inicializa todos os componentes comuns
 * Use esta função para setup automático de página
 * @param {Object} options - Opções de quais componentes inicializar
 */
export function initAll(options = {}) {
    const defaults = {
        lenis: true,      // Smooth scroll premium
        cursor: true,
        menu: true,
        header: true,
        reveals: true,
        accordion: true
    };

    const config = { ...defaults, ...options };

    if (config.lenis) initLenis();
    if (config.cursor) initCustomCursor();
    if (config.menu) initMobileMenu();
    if (config.header) initHeaderScroll();
    if (config.reveals) initRevealAnimations();
    if (config.accordion) initAccordion();
}

// =================================
// EXPORTS GLOBAIS (para compatibilidade)
// =================================

// Exporta para window para uso em páginas sem modules
if (typeof window !== 'undefined') {
    window.WideStudio = {
        initLenis,
        initCustomCursor,
        setupInteractiveHover,
        initMobileMenu,
        initHeaderScroll,
        initAccordion,
        initRevealAnimations,
        setupLightboxClose,
        validateForm,
        clearFormValidation,
        scrollToElement,
        initAll,

        // Re-exporta utils importantes
        createPillIndicator,
        onScroll
    };
}
