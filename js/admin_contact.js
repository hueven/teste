/**
 * Wide Studio - Admin Contact JS
 * Gerencia os textos de contato e informações de redes sociais
 */

import { apiRequest } from './utils.js';
import { showSaveFeedback } from './admin_common.js';

// Elementos
const contactTitleInput = document.getElementById('contactTitle');
const contactSubtitleInput = document.getElementById('contactSubtitle');
const contactEmailInput = document.getElementById('contactEmail');
const contactPhoneInput = document.getElementById('contactPhone');
const contactInstagramInput = document.getElementById('contactInstagram');
const footerTextInput = document.getElementById('footerText');

document.addEventListener('DOMContentLoaded', () => {
    loadContactData();
    window.addEventListener('savePageData', saveContactData);
});

async function loadContactData() {
    try {
        const json = await fetch('gallery_api').then(r => r.json());
        if (json.success) {
            if (contactTitleInput && json.contact_title !== undefined) contactTitleInput.value = json.contact_title || '';
            if (contactSubtitleInput && json.contact_subtitle !== undefined) contactSubtitleInput.value = json.contact_subtitle || '';
            if (contactEmailInput && json.contact_email !== undefined) contactEmailInput.value = json.contact_email || '';
            if (contactPhoneInput && json.contact_phone !== undefined) contactPhoneInput.value = json.contact_phone || '';
            if (contactInstagramInput && json.contact_instagram !== undefined) contactInstagramInput.value = json.contact_instagram || '';
            if (footerTextInput && json.footer_text !== undefined) footerTextInput.value = json.footer_text || '';
        }
    } catch (e) {
        console.error('Error loading contact data:', e);
    }
}

async function saveContactData() {
    const data = {
        contact_title: contactTitleInput.value,
        contact_subtitle: contactSubtitleInput.value,
        contact_email: contactEmailInput.value,
        contact_phone: contactPhoneInput.value,
        contact_instagram: contactInstagramInput.value,
        footer_text: footerTextInput.value
    };

    try {
        const json = await apiRequest('save_settings', data);
        showSaveFeedback(json.success);
    } catch (e) {
        showSaveFeedback(false, e.message);
    }
}
