/**
 * Wide Studio - Admin Hero JS
 * Gerencia o texto principal e tipografia da home
 */

import { apiRequest } from './utils.js';
import { showSaveFeedback } from './admin_common.js';

// Elementos
const heroInput = document.getElementById('heroText');

document.addEventListener('DOMContentLoaded', () => {
    // Escuta o evento global de salvamento definido em admin_common.js
    window.addEventListener('savePageData', saveHeroData);
});

/**
 * Salva os dados do Hero (Texto e Tipografia)
 */
async function saveHeroData() {
    if (!heroInput) return;

    const data = {
        hero_text: heroInput.value,
        hero_font_size: document.querySelector('input[name="heroFontSize"]:checked')?.value || 'medium',
        hero_font_weight: document.querySelector('input[name="heroFontWeight"]:checked')?.value || 'light'
    };

    try {
        // Usa a ação unificada 'save_hero' na gallery_api
        const json = await apiRequest('save_hero', data);
        showSaveFeedback(json.success);
    } catch (e) {
        console.error('Save error:', e);
        showSaveFeedback(false, e.message);
    }
}
