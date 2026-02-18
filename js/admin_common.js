/**
 * Wide Studio - Admin Common JS
 * Lógica compartilhada entre as páginas do admin
 */

import { createPillIndicator } from './utils.js';

document.addEventListener('DOMContentLoaded', () => {
    // Inicializa o indicador de abas (pill)
    // Inicializa o indicador de abas (pill) com pequeno delay para garantir renderização
    setTimeout(() => {
        const tabsNav = document.getElementById('admin-tabs-nav');
        if (tabsNav) {
            const tabButtons = tabsNav.querySelectorAll('.tab-btn');
            const pillObj = createPillIndicator(tabsNav, tabButtons, 'tab-pill-indicator');

            // Remove o style inline de fallback dos botões assim que a pill assumir
            if (pillObj) {
                tabButtons.forEach(btn => btn.style.background = '');
            }
        }
    }, 50);

    const globalSaveBtn = document.getElementById('globalSaveBtn');
    if (globalSaveBtn) {
        globalSaveBtn.addEventListener('click', () => {
            window.dispatchEvent(new CustomEvent('savePageData'));
        });
    }
});

// Helper para mostrar feedback de salvamento
export function showSaveFeedback(success, message = '') {
    const btn = document.getElementById('globalSaveBtn');
    if (!btn) return;

    const originalText = btn.innerText;
    btn.disabled = true;

    if (success) {
        btn.innerText = '✅ Salvo!';
        btn.style.background = '#27ae60';
    } else {
        btn.innerText = '❌ Erro';
        btn.style.background = '#e74c3c';
        console.error(message);
    }

    setTimeout(() => {
        btn.innerText = originalText;
        btn.disabled = false;
        btn.style.background = '';
    }, 2000);
}
