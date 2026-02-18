/**
 * Wide Studio - Admin About JS
 * Gerencia o conteúdo da página Sobre
 */

import { apiRequest } from './utils.js';
import { showSaveFeedback } from './admin_common.js';

// Elementos
const aboutInput = document.getElementById('aboutText');

document.addEventListener('DOMContentLoaded', () => {
    window.addEventListener('savePageData', saveAboutData);
});

async function saveAboutData() {
    if (!aboutInput) return;

    try {
        const json = await apiRequest('save_about', { text: aboutInput.value });
        showSaveFeedback(json.success);
    } catch (e) {
        showSaveFeedback(false, e.message);
    }
}
