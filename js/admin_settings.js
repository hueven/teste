/**
 * Wide Studio - Admin Settings JS
 * Gerencia configurações globais, logo e galeria
 */

import { apiRequest } from './utils.js';
import { showSaveFeedback } from './admin_common.js';

// Elementos
const siteTitleInput = document.getElementById('siteTitle');
const logoTextInput = document.getElementById('logoText');
const logoPreview = document.getElementById('logoPreview');
const logoFontFamilyInput = document.getElementById('logoFontFamily');
const colorModeToggle = document.getElementById('color-mode-toggle');

document.addEventListener('DOMContentLoaded', () => {
    loadSettings();
    window.addEventListener('savePageData', saveSettings);

    // Listeners em tempo real para o Preview da Logo
    if (logoTextInput) {
        logoTextInput.addEventListener('input', updateLogoPreview);
    }
    if (logoFontFamilyInput) {
        logoFontFamilyInput.addEventListener('change', (e) => {
            const font = e.target.value;
            loadGoogleFont(font);
            updateLogoPreview();
        });
    }

    // Listeners para outros campos da logo (Size, Weight, Spacing) para dar preview
    document.querySelectorAll('input[name^="logoFont"]').forEach(input => {
        input.addEventListener('change', updateLogoPreview);
    });
});

async function loadSettings() {
    try {
        const json = await fetch('gallery_api').then(r => r.json());
        if (json.success) {
            // Site Title
            if (siteTitleInput && json.site_title !== undefined) siteTitleInput.value = json.site_title || '';

            // Logo
            if (logoTextInput && json.logo_text !== undefined) logoTextInput.value = json.logo_text || '';
            if (logoFontFamilyInput && json.logo_font_family !== undefined) {
                logoFontFamilyInput.value = json.logo_font_family || 'Inter';
                loadGoogleFont(json.logo_font_family);
            }

            // Toggles / Radios
            if (json.logo_font_size !== undefined) setRadioValue('logoFontSize', json.logo_font_size);
            if (json.logo_font_weight !== undefined) setRadioValue('logoFontWeight', json.logo_font_weight);
            if (json.logo_letter_spacing !== undefined) setRadioValue('logoLetterSpacing', json.logo_letter_spacing);
            if (json.gallery_columns !== undefined) setRadioValue('galleryColumns', json.gallery_columns);

            if (colorModeToggle && json.color_mode !== undefined) {
                colorModeToggle.checked = (json.color_mode === 'color');
            }

            updateLogoPreview();
        }
    } catch (e) {
        console.error('Error loading settings:', e);
    }
}

function updateLogoPreview() {
    if (!logoPreview) return;

    const text = logoTextInput.value || 'Wide Studio';
    const font = logoFontFamilyInput.value;
    const size = document.querySelector('input[name="logoFontSize"]:checked')?.value || '20';
    const weight = document.querySelector('input[name="logoFontWeight"]:checked')?.value || '600';
    const spacing = document.querySelector('input[name="logoLetterSpacing"]:checked')?.value || '0.02';

    logoPreview.innerText = text;
    logoPreview.style.fontFamily = `"${font}", sans-serif`;
    logoPreview.style.fontSize = size + 'px';
    logoPreview.style.fontWeight = weight;
    logoPreview.style.letterSpacing = spacing + 'em';
}

async function saveSettings() {
    const data = {
        // action será adicionado pelo apiRequest se passarmos apenas os dados
        site_title: siteTitleInput.value,
        logo_text: logoTextInput.value,
        logo_font_family: logoFontFamilyInput.value,
        logo_font_size: document.querySelector('input[name="logoFontSize"]:checked')?.value,
        logo_font_weight: document.querySelector('input[name="logoFontWeight"]:checked')?.value,
        logo_letter_spacing: document.querySelector('input[name="logoLetterSpacing"]:checked')?.value,
        gallery_color_mode: colorModeToggle.checked ? 'color' : 'grayscale',
        gallery_columns: document.querySelector('input[name="galleryColumns"]:checked')?.value
    };

    try {
        // Aproveitar o endpoint genérico de save_settings
        const json = await apiRequest('save_settings', data);
        showSaveFeedback(json.success);
    } catch (e) {
        showSaveFeedback(false, e.message);
    }
}

// Helpers
function setRadioValue(name, value) {
    if (!value) return;
    const input = document.querySelector(`input[name="${name}"][value="${value}"]`);
    if (input) input.checked = true;
}

function loadGoogleFont(fontName) {
    if (!fontName || fontName === 'Inter') return;
    const id = `font-${fontName.replace(/\s+/g, '-').toLowerCase()}`;
    if (document.getElementById(id)) return;

    const link = document.createElement('link');
    link.id = id; link.rel = 'stylesheet';
    link.href = `https://fonts.googleapis.com/css2?family=${fontName.replace(/\s+/g, '+')}:wght@300;400;500;600;700&display=swap`;
    document.head.appendChild(link);
}
