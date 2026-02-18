/**
 * Wide Studio - Utility Functions
 * Funções utilitárias compartilhadas entre páginas
 */

// =================================
// API HELPERS
// =================================

/**
 * Faz uma requisição para a API
 * @param {string} action - Ação a ser executada
 * @param {Object} data - Dados adicionais
 * @param {boolean} useFormData - Se deve usar FormData ao invés de JSON
 * @returns {Promise<Object>} Resposta da API
 */
export async function apiRequest(action, data = {}, useFormData = false) {
    let body;
    let headers = {};

    if (useFormData) {
        // Se data já é FormData, usa direto; senão, cria novo FormData
        if (data instanceof FormData) {
            body = data;
        } else {
            body = new FormData();
            body.append('action', action);
            Object.keys(data).forEach(key => {
                body.append(key, data[key]);
            });
        }
    } else {
        // JSON request
        body = JSON.stringify({ action, ...data });
        headers['Content-Type'] = 'application/json';
    }

    // Usa 'gallery_api' sem .php porque o servidor tem URL rewriting
    const res = await fetch('gallery_api', {
        method: 'POST',
        headers,
        body
    });

    return res.json();
}

// =================================
// COOKIE HELPERS
// =================================

/**
 * Obtém o valor de um cookie
 * @param {string} name - Nome do cookie
 * @returns {string|null} Valor do cookie ou null
 */
export function getCookie(name) {
    const value = `; ${document.cookie}`;
    const parts = value.split(`; ${name}=`);
    return parts.length === 2 ? parts.pop().split(';').shift() : null;
}

/**
 * Define um cookie
 * @param {string} name - Nome do cookie
 * @param {string} value - Valor do cookie
 * @param {number} days - Dias até expirar
 */
export function setCookie(name, value, days = 365) {
    const date = new Date();
    date.setTime(date.getTime() + (days * 24 * 60 * 60 * 1000));
    const expires = `expires=${date.toUTCString()}`;
    document.cookie = `${name}=${value};${expires};path=/`;
}

// =================================
// PILL INDICATOR FACTORY
// =================================

/**
 * Cria um indicador pill animado para navegação
 * @param {HTMLElement} container - Container dos botões
 * @param {NodeList|Array} buttons - Botões para navegar
 * @param {string} className - Classe CSS do pill
 * @returns {Object|null} Objeto com { pill, movePillTo }
 */
export function createPillIndicator(container, buttons, className = 'pill-indicator') {
    if (!container || !buttons.length) return null;

    const pill = document.createElement('div');
    pill.className = className;
    container.appendChild(pill);

    /**
     * Move o pill para o botão alvo
     * @param {HTMLElement} targetBtn - Botão alvo
     */
    const movePillTo = (targetBtn) => {
        const containerRect = container.getBoundingClientRect();
        const btnRect = targetBtn.getBoundingClientRect();

        pill.style.left = `${btnRect.left - containerRect.left}px`;
        pill.style.top = `${btnRect.top - containerRect.top}px`;
        pill.style.width = `${btnRect.width}px`;
        pill.style.height = `${btnRect.height}px`;
    };

    // Initialize position on active button
    const activeBtn = container.querySelector('.active');
    if (activeBtn) {
        // Set initial position without transition
        pill.style.transition = 'none';
        movePillTo(activeBtn);
        // Re-enable transition after next frame
        requestAnimationFrame(() => {
            requestAnimationFrame(() => {
                pill.style.transition = '';
            });
        });
    }

    // Auto-update on window resize
    const resizeHandler = () => {
        const currentActive = container.querySelector('.active');
        if (currentActive) {
            pill.style.transition = 'none';
            movePillTo(currentActive);
            requestAnimationFrame(() => {
                requestAnimationFrame(() => {
                    pill.style.transition = '';
                });
            });
        }
    };

    window.addEventListener('resize', resizeHandler);

    return {
        pill,
        movePillTo,
        destroy: () => {
            window.removeEventListener('resize', resizeHandler);
            pill.remove();
        }
    };
}

// =================================
// SCROLL HANDLER (OPTIMIZED)
// =================================

/**
 * Adiciona um handler otimizado para scroll usando RAF
 * @param {Function} callback - Função a ser chamada no scroll
 * @returns {Function} Função para remover o listener
 */
export function onScroll(callback) {
    let ticking = false;

    const scrollHandler = () => {
        if (!ticking) {
            requestAnimationFrame(() => {
                callback();
                ticking = false;
            });
            ticking = true;
        }
    };

    window.addEventListener('scroll', scrollHandler, { passive: true });

    // Return cleanup function
    return () => {
        window.removeEventListener('scroll', scrollHandler);
    };
}

// =================================
// DOM HELPERS
// =================================

/**
 * Aguarda o DOM estar pronto
 * @param {Function} callback - Função a executar quando DOM estiver pronto
 */
export function onDOMReady(callback) {
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', callback);
    } else {
        callback();
    }
}

/**
 * Debounce para funções
 * @param {Function} func - Função a ser debounced
 * @param {number} wait - Tempo de espera em ms
 * @returns {Function} Função debounced
 */
export function debounce(func, wait = 300) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

/**
 * Throttle para funções
 * @param {Function} func - Função a ser throttled
 * @param {number} limit - Limite de tempo em ms
 * @returns {Function} Função throttled
 */
export function throttle(func, limit = 100) {
    let inThrottle;
    return function (...args) {
        if (!inThrottle) {
            func.apply(this, args);
            inThrottle = true;
            setTimeout(() => inThrottle = false, limit);
        }
    };
}

// =================================
// VALIDATION HELPERS
// =================================

/**
 * Valida se um elemento existe e está visível
 * @param {HTMLElement} element - Elemento a validar
 * @returns {boolean} True se elemento existe e está visível
 */
export function isElementVisible(element) {
    if (!element) return false;
    return element.offsetWidth > 0 && element.offsetHeight > 0;
}

/**
 * Escapa HTML para prevenir XSS
 * @param {string} text - Texto a escapar
 * @returns {string} Texto escapado
 */
export function escapeHTML(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}
