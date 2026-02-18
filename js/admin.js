/**
 * Wide Studio - Admin Panel JavaScript (REFATORADO)
 * Gerencia galeria, serviços, menu e configurações
 */

import { apiRequest, createPillIndicator } from './utils.js';
import { initCustomCursor, setupInteractiveHover } from './common.js';
import {
    SVG_ICONS,
    createServiceCard,
    createMenuItem,
    createImageTypeItem,
    createGalleryItem,
    getTypeOptions
} from './templates.js';
import {
    initProjects,
    renderProjects,
    saveAllProjects
} from './projects.js';
import {
    initCategories,
    renderCategories
} from './categories.js';

// Inicializa cursor customizado
initCustomCursor();
setupInteractiveHover();

// Inicializa módulo de projetos
// initProjects();

// Inicializa módulo de categorias
// initCategories();

// =================================
// ELEMENTOS DO DOM
// =================================
// const galleryList = document.getElementById('galleryList'); // REMOVED - Gallery tab removed
// const loading = document.getElementById('loading'); // REMOVED - Gallery tab removed
const btnSave = document.querySelector('.btn-save');
// const fileInput = document.getElementById('fileInput'); // REMOVED - Gallery tab removed
// const dropzone = document.getElementById('dropzone'); // REMOVED - Gallery tab removed
const heroInput = document.getElementById('heroText');
const menuList = document.getElementById('menu-list');
const replaceInput = document.getElementById('replaceInput');
// const imageTypesList = document.getElementById('image-types-list'); // REMOVED - Image Types feature removed
const contactTitleInput = document.getElementById('contactTitle');
const contactSubtitleInput = document.getElementById('contactSubtitle');
const siteTitleInput = document.getElementById('siteTitle');
const servicesTitleInput = document.getElementById('servicesTitle');
// Contact info inputs
const contactEmailInput = document.getElementById('contactEmail');
const contactPhoneInput = document.getElementById('contactPhone');
const contactInstagramInput = document.getElementById('contactInstagram');
const footerTextInput = document.getElementById('footerText');
// Logo inputs
const logoTextInput = document.getElementById('logoText');
const logoFontFamilyInput = document.getElementById('logoFontFamily');
const logoFontSizeInputs = document.querySelectorAll('input[name="logoFontSize"]');
const logoFontWeightInputs = document.querySelectorAll('input[name="logoFontWeight"]');
const logoLetterSpacingInputs = document.querySelectorAll('input[name="logoLetterSpacing"]');
const colorModeToggle = document.getElementById('color-mode-toggle');
const iosToggleWrapper = document.querySelector('.ios-toggle-wrapper');


// Inputs de tipografia do hero text
const heroFontSizeInputs = document.querySelectorAll('input[name="heroFontSize"]');
const heroFontWeightInputs = document.querySelectorAll('input[name="heroFontWeight"]');

// Inputs de colunas da galeria
const galleryColumnsInputs = document.querySelectorAll('input[name="galleryColumns"]');

// Inputs de gap da galeria
const galleryGapInputs = document.querySelectorAll('input[name="galleryGap"]');

// Inputs de border radius da galeria
const galleryBorderRadiusInputs = document.querySelectorAll('input[name="galleryBorderRadius"]');

// Logo preview - declarar cedo para evitar erros de "antes da inicialização"
const logoPreview = document.getElementById('logoPreview');

// Estado
let replaceTargetId = null;
// let imageTypesData = []; // REMOVED - Image Types feature removed
let currentColorMode = 'grayscale'; // Modo de cores atual


// File input para imagens de serviços
const serviceImageInput = document.createElement('input');
serviceImageInput.type = 'file';
serviceImageInput.accept = 'image/*,video/mp4,video/webm,video/quicktime'; // Imagens E vídeos
serviceImageInput.style.display = 'none';
serviceImageInput.id = 'serviceImageInput';
document.body.appendChild(serviceImageInput);

let currentServiceImageId = null;
let isReplacingServiceImage = false;

// File input para imagens de coleções
const collectionImageInput = document.getElementById('collectionImageInput');
let currentCollectionImageId = null;

// Event listener para upload de imagem de coleção
if (collectionImageInput) {
    collectionImageInput.addEventListener('change', async function (e) {
        if (!e.target.files || !e.target.files[0]) return;
        if (!currentCollectionImageId) return;

        const file = e.target.files[0];
        const formData = new FormData();
        formData.append('action', 'upload_collection_image');
        formData.append('collection_id', currentCollectionImageId);
        formData.append('image', file);
        formData.append('set_primary', '1'); // Definir como imagem principal

        try {
            // Usar gallery_api sem extensão para evitar redirect 301 do .htaccess
            const response = await fetch('gallery_api', {
                method: 'POST',
                body: formData
            });

            const json = await response.json();

            if (json.success) {
                // Atualizar a thumbnail da coleção
                const card = document.querySelector(`.collection-card[data-id="${currentCollectionImageId}"]`);
                if (card) {
                    const img = card.querySelector('.collection-thumbnail img');
                    if (img && json.image_path) {
                        img.src = json.image_path + '?t=' + Date.now(); // Cache bust
                    }
                }
                // Recarregar coleções para atualizar contadores
                loadCollections();
            } else {
                alert('Erro ao fazer upload: ' + (json.error || 'desconhecido'));
            }
        } catch (error) {
            console.error('Erro no upload:', error);
            alert('Erro ao fazer upload da imagem');
        }

        // Limpar input
        e.target.value = '';
        currentCollectionImageId = null;
    });
}

// =================================
// GENERIC CRUD HELPERS
// =================================

/**
 * Cria e submete uma action genérica
 * @param {string} action - Nome da action
 * @param {Object} extraData - Dados adicionais
 * @returns {Promise<boolean>} True se sucesso
 */
async function createAndSubmitAction(action, extraData = {}) {
    try {
        const json = await apiRequest(action, extraData);
        if (json.success) {
            loadGallery();
            return true;
        } else {
            alert(`Erro: ${json.error || 'Desconhecido'}`);
            return false;
        }
    } catch (e) {
        console.error('Erro na requisição:', e);
        alert('Erro na requisição');
        return false;
    }
}

/**
 * Deleta um item genérico
 * @param {string} action - Nome da action de delete
 * @param {number} id - ID do item
 * @param {string} confirmMsg - Mensagem de confirmação
 * @param {Function} onSuccess - Callback de sucesso
 */
async function deleteGenericItem(action, id, confirmMsg, onSuccess) {
    if (!confirm(confirmMsg)) return;

    try {
        const json = await apiRequest(action, { id });
        if (json.success && onSuccess) {
            onSuccess();
        } else {
            alert(`Erro ao deletar: ${json.error || 'desconhecido'}`);
        }
    } catch (e) {
        console.error('Erro ao deletar:', e);
        alert('Erro na requisição');
    }
}

// =================================
// COLOR MODE TOGGLE (Preto e Branco / Cores)
// =================================

/**
 * Atualiza a UI do toggle de modo de cores (iOS Switch)
 * @param {string} mode - 'grayscale' ou 'color'
 */
function updateColorModeUI(mode) {
    if (colorModeToggle && iosToggleWrapper) {
        // Checkbox: checked = color, unchecked = grayscale
        colorModeToggle.checked = (mode === 'color');

        // Atualiza classes do wrapper para estilizar labels
        iosToggleWrapper.classList.toggle('mode-grayscale', mode === 'grayscale');
        iosToggleWrapper.classList.toggle('mode-color', mode === 'color');
    }
}

/**
 * Define o modo de cores e salva no servidor
 * @param {string} mode - 'grayscale' ou 'color'
 */
async function setColorMode(mode) {
    currentColorMode = mode;
    updateColorModeUI(mode);

    try {
        await apiRequest('save_gallery_settings', { color_mode: mode });
    } catch (e) {
        console.error('Erro ao salvar modo de cores:', e);
    }
}

// Event listener para o toggle iOS
if (colorModeToggle) {
    colorModeToggle.addEventListener('change', (e) => {
        const mode = e.target.checked ? 'color' : 'grayscale';
        setColorMode(mode);
    });
}



// =================================
// TABS NAVIGATION (USANDO createPillIndicator)
// =================================

const tabsNav = document.querySelector('.tabs-nav');
const tabButtons = document.querySelectorAll('.tab-btn');

if (tabsNav && tabButtons.length > 0) {
    const { movePillTo } = createPillIndicator(
        tabsNav,
        tabButtons,
        'tab-pill-indicator'
    );

    // Exporta para uso no openTab
    window.moveTabPillTo = movePillTo;
}

/**
 * Abre uma aba específica
 * @param {string} tabId - ID da aba a ser aberta
 * @param {HTMLElement} btn - Botão que foi clicado
 */
window.openTab = function (tabId, btn) {
    // Esconde todas as abas
    document.querySelectorAll('.tab-pane').forEach(el => el.classList.remove('active'));
    // Remove active de todos os botões
    document.querySelectorAll('.tab-btn').forEach(el => el.classList.remove('active'));

    // Ativa a aba selecionada
    document.getElementById(tabId).classList.add('active');
    btn.classList.add('active');

    // Move pill indicator
    if (window.moveTabPillTo) {
        window.moveTabPillTo(btn);
    }
};

// =================================
// CARREGAR DADOS
// =================================

/**
 * Carrega todos os dados do admin (galeria, hero, serviços, menu, tipos)
 */
async function loadGallery() {
    try {
        const res = await fetch('gallery_api');
        const json = await res.json();
        console.log('Dados carregados da API:', json);

        if (json.success) {
            // Reportar erros parciais do banco de dados se houver
            if (json.collections_error) console.error('Erro no banco (coleções):', json.collections_error);
            if (json.works_error) console.error('Erro no banco (galeria):', json.works_error);

            if (json.hero_text) heroInput.value = json.hero_text;
            if (json.menu) renderMenu(json.menu);
            if (json.collections) renderCollections(json.collections);

            // Carregar textos do contato
            if (contactTitleInput && 'contact_title' in json) contactTitleInput.value = json.contact_title;
            if (contactSubtitleInput && 'contact_subtitle' in json) contactSubtitleInput.value = json.contact_subtitle;
            // Carregar título do site
            if (siteTitleInput && 'site_title' in json) siteTitleInput.value = json.site_title;

            // Carregar modo de cores da galeria
            if ('gallery_color_mode' in json) {
                currentColorMode = json.gallery_color_mode || 'grayscale';
                updateColorModeUI(currentColorMode);
            }

            // Carregar tipografia do hero text
            if (json.hero_font_size || json.hero_font_weight) {
                loadHeroTypography(json.hero_font_size || 'medium', json.hero_font_weight || 'light');
            }

            // Carregar número de colunas da galeria
            if (json.gallery_columns) {
                loadGalleryColumns(json.gallery_columns);
            }
            // Carregar gap da galeria
            if (json.gallery_gap) {
                loadGalleryGap(json.gallery_gap);
            }
            // Carregar border radius da galeria
            if (json.gallery_border_radius) {
                loadGalleryBorderRadius(json.gallery_border_radius);
            }
            // Carregar informações de contato
            if (contactEmailInput && 'contact_email' in json) contactEmailInput.value = json.contact_email;
            if (contactPhoneInput && 'contact_phone' in json) contactPhoneInput.value = json.contact_phone;
            if (contactInstagramInput && 'contact_instagram' in json) contactInstagramInput.value = json.contact_instagram;
            // Carregar texto do footer
            if (footerTextInput && 'footer_text' in json) footerTextInput.value = json.footer_text;

            // Carregar logo settings
            if (logoTextInput && 'logo_text' in json) {
                logoTextInput.value = json.logo_text;
                if (logoPreview) logoPreview.textContent = json.logo_text;
            }
            if (json.logo_font_size) {
                const radio = document.querySelector(`input[name="logoFontSize"][value="${json.logo_font_size}"]`);
                if (radio) radio.checked = true;
                if (logoPreview) logoPreview.style.fontSize = json.logo_font_size + 'px';
            }
            if (json.logo_font_weight) {
                const radio = document.querySelector(`input[name="logoFontWeight"][value="${json.logo_font_weight}"]`);
                if (radio) radio.checked = true;
                if (logoPreview) logoPreview.style.fontWeight = json.logo_font_weight;
            }
            if (json.logo_letter_spacing) {
                const radio = document.querySelector(`input[name="logoLetterSpacing"][value="${json.logo_letter_spacing}"]`);
                if (radio) radio.checked = true;
                if (logoPreview) logoPreview.style.letterSpacing = json.logo_letter_spacing + 'em';
            }
            if (json.logo_font_family) {
                if (logoFontFamilyInput) logoFontFamilyInput.value = json.logo_font_family;
                if (typeof loadGoogleFont === 'function') loadGoogleFont(json.logo_font_family);
                if (logoPreview) logoPreview.style.fontFamily = `"${json.logo_font_family}", sans-serif`;
            }
        } else {
            console.error('Erro na API:', json.error);
            alert('Erro ao carregar dados: ' + (json.error || 'Desconhecido'));
        }
    } catch (e) {
        console.error('Erro crítico ao carregar dados:', e);
        alert('Erro crítico ao carregar dados: ' + e.message);
    }
}

// =================================
// RENDERIZAÇÃO - SERVIÇOS (USANDO TEMPLATES)
// =================================



// =================================
// RENDERIZAÇÃO - MENU (USANDO TEMPLATES)
// =================================

/**
 * Renderiza a lista de itens do menu
 * @param {Array} items - Lista de itens do menu
 */
function renderMenu(items) {
    menuList.innerHTML = '';
    items.forEach((item) => {
        menuList.appendChild(createMenuItem(item));
    });
}

/**
 * Adiciona um novo item ao menu
 */
async function addMenuItem() {
    await createAndSubmitAction('add_menu_item');
}

/**
 * Deleta um item do menu
 * @param {number} id - ID do item
 * @param {HTMLElement} btn - Botão que foi clicado
 */
async function deleteMenuItem(id, btn) {
    await deleteGenericItem(
        'delete_menu_item',
        id,
        'Remover este link do menu?',
        () => btn.closest('div[data-id]').remove()
    );
}

// =================================
// RENDERIZAÇÃO - TIPOS DE IMAGEM (REMOVIDA)
// Feature descontinuada
// =================================
/*
function renderImageTypes(items) {
    imageTypesList.innerHTML = '';

    if (items.length === 0) {
        imageTypesList.innerHTML = '<p style="opacity: 0.5; font-size: 0.9rem;">Nenhum tipo cadastrado. Adicione tipos para filtrar a galeria.</p>';
        return;
    }

    items.forEach((item) => {
        imageTypesList.appendChild(createImageTypeItem(item));
    });

    new Sortable(imageTypesList, {
        handle: '.drag-handle',
        animation: 150,
        ghostClass: 'sortable-ghost'
    });
}

async function addImageType() {
    await createAndSubmitAction('add_image_type');
}

async function deleteImageType(id, btn) {
    await deleteGenericItem(
        'delete_image_type',
        id,
        'Remover este tipo? As imagens associadas ficarão sem tipo.',
        () => {
            btn.closest('.image-type-item').remove();
            imageTypesData = imageTypesData.filter(t => t.id != id);
            loadGallery();
        }
    );
}
*/

// =================================
// RENDERIZAÇÃO - GALERIA (REMOVIDA)
// Feature removida com a aba Galeria
// =================================
/*
function renderItems(items) {
    galleryList.innerHTML = '';
    items.forEach(item => {
        const el = createGalleryItem(item, imageTypesData);
        galleryList.appendChild(el);
    });

    document.querySelectorAll('.align-input').forEach(select => {
        select.addEventListener('change', (e) => {
            const img = e.target.closest('.gallery-item').querySelector('img');
            img.style.objectPosition = `center ${e.target.value}`;
        });
    });
}
*/

// =================================
// REPLACE IMAGE LOGIC
// =================================

/**
 * Abre o seletor de arquivo para trocar imagem
 * @param {number} id - ID do item
 */
function triggerReplace(id) {
    replaceTargetId = id;
    replaceInput.click();
}

replaceInput.addEventListener('change', async (e) => {
    if (!e.target.files.length) return;

    const file = e.target.files[0];
    const formData = new FormData();
    formData.append('action', 'replace');
    formData.append('id', replaceTargetId);
    formData.append('image', file);

    document.body.style.cursor = 'wait';

    try {
        const json = await apiRequest('replace', formData, true);
        if (json.success) {
            const itemEl = document.querySelector(`.gallery-item[data-id="${replaceTargetId}"]`);
            const img = itemEl.querySelector('img');
            img.src = json.new_path + '?v=' + new Date().getTime();
        } else {
            alert('Erro ao trocar: ' + json.error);
        }
    } catch (error) {
        console.error('Erro ao trocar imagem:', error);
        alert('Erro na conexão.');
    } finally {
        document.body.style.cursor = 'default';
        replaceInput.value = '';
    }
});

// =================================
// SORTABLE (Drag & Drop)
// =================================

// REMOVED - galleryList não existe mais
/*
new Sortable(galleryList, {
    handle: '.drag-handle',
    animation: 150,
    ghostClass: 'sortable-ghost'
});
*/



new Sortable(menuList, {
    handle: '.drag-handle',
    animation: 150,
    ghostClass: 'sortable-ghost'
});

// =================================
// UPLOAD DE IMAGENS - REMOVIDO
// Feature removida: upload estava na aba Galeria que foi removida
// =================================
/*
dropzone.addEventListener('click', () => fileInput.click());

dropzone.addEventListener('dragover', (e) => {
    e.preventDefault();
    dropzone.classList.add('dragover');
});

dropzone.addEventListener('dragleave', (e) => {
    e.preventDefault();
    dropzone.classList.remove('dragover');
});

dropzone.addEventListener('drop', (e) => {
    e.preventDefault();
    dropzone.classList.remove('dragover');
    handleFiles(e.dataTransfer.files);
});

fileInput.addEventListener('change', (e) => handleFiles(e.target.files));
*/

/**
 * Processa arquivos para upload
 * @param {FileList} files - Lista de arquivos
 */
async function handleFiles(files) {
    if (!files.length) return;

    const formData = new FormData();
    formData.append('action', 'upload');

    for (let i = 0; i < files.length; i++) {
        formData.append('images[]', files[i]);
    }

    const dropText = document.querySelector('.drop-text');
    const originalText = dropText.textContent;
    dropText.textContent = 'Enviando...';

    try {
        const json = await apiRequest('upload', formData, true);
        if (json.success) {
            loadGallery();
        } else {
            alert('Erro Upload: ' + json.error);
        }
    } catch (e) {
        console.error('Erro no upload:', e);
        alert('Erro na conexão.');
    } finally {
        dropText.textContent = originalText;
    }
}

// =================================
// SALVAR ALTERAÇÕES
// =================================

btnSave.addEventListener('click', async () => {
    const originalText = btnSave.textContent;
    btnSave.textContent = 'Salvando...';
    btnSave.disabled = true;

    try {
        // 1. Salvar Hero Text
        await apiRequest('save_hero', { text: heroInput.value });

        // 2. Salvar Textos do Contato
        await apiRequest('save_contact', {
            title: contactTitleInput?.value || '',
            subtitle: contactSubtitleInput?.value || ''
        });

        // 3. Salvar Título do Site
        await apiRequest('save_site_title', {
            title: siteTitleInput?.value || 'Wide Studio'
        });



        // 3.6. Salvar Informações de Contato
        await apiRequest('save_contact_info', {
            email: contactEmailInput?.value || '',
            phone: contactPhoneInput?.value || '',
            instagram: contactInstagramInput?.value || ''
        });

        // 3.65. Salvar Texto do Footer
        await apiRequest('save_footer_text', {
            text: footerTextInput?.value || ''
        });

        // 3.66. Salvar Logo Settings
        const selectedLogoSize = document.querySelector('input[name="logoFontSize"]:checked')?.value || '20';
        const selectedLogoWeight = document.querySelector('input[name="logoFontWeight"]:checked')?.value || '600';
        const selectedLogoSpacing = document.querySelector('input[name="logoLetterSpacing"]:checked')?.value || '0.02';
        const selectedFontFamily = logoFontFamilyInput?.value || 'Inter';

        await apiRequest('save_logo_settings', {
            text: logoTextInput?.value || 'Gustavo Starke',
            font_size: selectedLogoSize,
            font_weight: selectedLogoWeight,
            letter_spacing: selectedLogoSpacing,
            font_family: selectedFontFamily
        });

        // 3.7. Salvar Tipografia do Hero Text
        const selectedSize = document.querySelector('input[name="heroFontSize"]:checked')?.value || 'medium';
        const selectedWeight = document.querySelector('input[name="heroFontWeight"]:checked')?.value || 'light';
        await apiRequest('save_hero_typography', {
            font_size: selectedSize,
            font_weight: selectedWeight
        });

        // 3.7. Salvar Número de Colunas da Galeria
        const selectedColumns = document.querySelector('input[name="galleryColumns"]:checked')?.value || '4';
        await apiRequest('save_gallery_columns', {
            columns: selectedColumns
        });

        // 3.8. Salvar Gap da Galeria
        const selectedGap = document.querySelector('input[name="galleryGap"]:checked')?.value || '10';
        await apiRequest('save_gallery_gap', {
            gap: selectedGap
        });

        // 3.9. Salvar Border Radius da Galeria
        const selectedBorderRadius = document.querySelector('input[name="galleryBorderRadius"]:checked')?.value || '8';
        await apiRequest('save_gallery_border_radius', {
            borderRadius: selectedBorderRadius
        });

        // 4. Salvar Serviços (REMOVIDO)
        // const servicesToSave = [];
        // document.querySelectorAll('.service-card').forEach((el, index) => {
        //     const titleEl = el.querySelector('.service-title');
        //     const descEl = el.querySelector('.service-desc');
        //
        //     if (titleEl && descEl) {
        //         servicesToSave.push({
        //             id: el.getAttribute('data-id'),
        //             title: titleEl.value,
        //             description: descEl.value,
        //             // image_path é gerenciado separadamente via upload/replace/delete
        //             display_order: index + 1
        //         });
        //     }
        // });
        // if (servicesToSave.length > 0) {
        //     await apiRequest('save_services', { items: servicesToSave });
        // }

        // 4. Salvar Menu
        const menuToSave = [];
        document.querySelectorAll('#menu-list > div').forEach((el, index) => {
            menuToSave.push({
                id: el.getAttribute('data-id'),
                title: el.querySelector('.menu-title').value,
                link_url: el.querySelector('.menu-link').value,
                display_order: index + 1
            });
        });
        if (menuToSave.length > 0) {
            await apiRequest('save_menu', { items: menuToSave });
        }

        // 5. Salvar Tipos de Imagem - REMOVIDO (feature descontinuada)
        /*
        const typesToSave = [];
        document.querySelectorAll('.image-type-item').forEach((el, index) => {
            typesToSave.push({
                id: el.getAttribute('data-id'),
                name: el.querySelector('.type-name').value,
                display_order: index + 1
            });
        });
        if (typesToSave.length > 0) {
            await apiRequest('save_image_types', { items: typesToSave });
        }
        */

        // 6. Salvar Galeria - REMOVIDO (feature descontinuada)
        /*
        const itemsToSave = [];
        document.querySelectorAll('.gallery-item').forEach((row, index) => {
            itemsToSave.push({
                id: row.getAttribute('data-id'),
                title: row.querySelector('.title-input').value,
                description: row.querySelector('.desc-input').value,
                type_id: row.querySelector('.type-input').value || null,
                alignment: row.querySelector('.align-input').value,
                display_order: index + 1
            });
        });
        */

        // 7. Salvar Projetos (REMOVIDO)
        // await saveAllProjects();

        // 8. Salvar Galeria - REMOVIDO (feature descontinuada)
        /*
        const json = await apiRequest('save_all', { items: itemsToSave });

        if (json.success) {
            btnSave.textContent = 'Salvo!';
            setTimeout(() => {
                btnSave.textContent = originalText;
                btnSave.disabled = false;
            }, 2000);
        } else {
            throw new Error(json.error);
        }
        */

        // Sucesso
        btnSave.textContent = 'Salvo!';
        setTimeout(() => {
            btnSave.textContent = originalText;
            btnSave.disabled = false;
        }, 2000);
    } catch (e) {
        console.error('Erro ao salvar:', e);
        alert('Erro ao salvar: ' + e.message);
        btnSave.textContent = originalText;
        btnSave.disabled = false;
    }
});

// =================================
// DELETAR ITEM DA GALERIA
// =================================

/**
 * Deleta um item da galeria
 * @param {number} id - ID do item
 * @param {HTMLElement} btn - Botão que foi clicado
 */
async function deleteItem(id, btn) {
    if (!confirm('Tem certeza? Isso apaga a imagem permanentemente.')) return;

    const row = btn.closest('.gallery-item');
    row.style.opacity = '0.5';

    try {
        const formData = new FormData();
        formData.append('action', 'delete');
        formData.append('id', id);

        const json = await apiRequest('delete', formData, true);
        if (json.success) {
            row.remove();
        } else {
            alert('Erro: ' + json.error);
            row.style.opacity = '1';
        }
    } catch (e) {
        console.error('Erro ao deletar:', e);
        alert('Erro ao deletar.');
        row.style.opacity = '1';
    }
}

// =================================
// SERVICE IMAGE UPLOAD/REPLACE/DELETE
// =================================

/**
 * Abre file picker para upload de imagem de serviço
 * @param {number} serviceId - ID do serviço
 */
function uploadServiceImage(serviceId) {
    currentServiceImageId = serviceId;
    isReplacingServiceImage = false;
    serviceImageInput.click();
}

/**
 * Abre file picker para substituir imagem de serviço
 * @param {number} serviceId - ID do serviço
 */
function replaceServiceImage(serviceId) {
    currentServiceImageId = serviceId;
    isReplacingServiceImage = true;
    serviceImageInput.click();
}

/**
 * Handler para quando arquivo é selecionado
 */
serviceImageInput.addEventListener('change', async (e) => {
    if (!e.target.files.length) return;

    const file = e.target.files[0];
    const formData = new FormData();
    formData.append('action', isReplacingServiceImage ? 'replace_service_image' : 'upload_service_image');
    formData.append('service_id', currentServiceImageId);
    formData.append('image', file);

    const originalCursor = document.body.style.cursor;
    document.body.style.cursor = 'wait';

    try {
        const json = await apiRequest(
            isReplacingServiceImage ? 'replace_service_image' : 'upload_service_image',
            formData,
            true
        );

        if (json.success) {
            loadGallery(); // Recarrega tudo para atualizar o preview
        } else {
            alert('Erro ao enviar imagem: ' + (json.error || 'Desconhecido'));
        }
    } catch (error) {
        console.error('Erro ao enviar imagem:', error);
        alert('Erro na conexão.');
    } finally {
        document.body.style.cursor = originalCursor;
        serviceImageInput.value = ''; // Limpa input
    }
});

/**
 * Deleta a imagem de um serviço
 * @param {number} serviceId - ID do serviço
 * @param {HTMLElement} btn - Botão que foi clicado
 */
async function deleteServiceImage(serviceId, btn) {
    if (!confirm('Remover a imagem deste serviço?')) return;

    try {
        const json = await apiRequest('delete_service_image', { service_id: serviceId });
        if (json.success) {
            loadGallery(); // Recarrega para atualizar UI
        } else {
            alert('Erro ao remover imagem: ' + (json.error || 'Desconhecido'));
        }
    } catch (error) {
        console.error('Erro ao remover imagem:', error);
        alert('Erro na conexão.');
    }
}

// =================================
// HERO TYPOGRAPHY
// =================================

/**
 * Carrega as configurações de tipografia do hero text
 * @param {string} fontSize - Tamanho da fonte (xs, sm, medium, lg, xl)
 * @param {string} fontWeight - Espessura da fonte (thin, light, regular, medium, bold)
 */
function loadHeroTypography(fontSize, fontWeight) {
    // Marca o radio button correto para o tamanho
    heroFontSizeInputs.forEach(input => {
        input.checked = (input.value === fontSize);
    });

    // Marca o radio button correto para a espessura
    heroFontWeightInputs.forEach(input => {
        input.checked = (input.value === fontWeight);
    });
}

/**
 * Carrega o número de colunas da galeria
 * @param {string} columns - Número de colunas (3, 4, 5 ou 6)
 */
function loadGalleryColumns(columns) {
    galleryColumnsInputs.forEach(input => {
        input.checked = (input.value === columns);
    });
}

/**
 * Carrega o gap (espaçamento) da galeria
 * @param {string} gap - Valor do gap (2, 5, 10, 12 ou 15)
 */
function loadGalleryGap(gap) {
    galleryGapInputs.forEach(input => {
        input.checked = (input.value === gap);
    });
}

/**
 * Carrega o border radius da galeria
 * @param {string} borderRadius - Valor do border radius (0, 8, 16 ou 24)
 */
function loadGalleryBorderRadius(borderRadius) {
    galleryBorderRadiusInputs.forEach(input => {
        input.checked = (input.value === borderRadius);
    });
}

// =================================
// INICIALIZAÇÃO
// =================================

loadGallery();

// Expor funções no escopo global para onclick inline
window.addService = addService;
window.deleteService = deleteService;
window.addMenuItem = addMenuItem;
window.deleteMenuItem = deleteMenuItem;
// window.addImageType = addImageType; // REMOVED - Image Types feature removed
// window.deleteImageType = deleteImageType; // REMOVED - Image Types feature removed
window.triggerReplace = triggerReplace;
window.deleteItem = deleteItem;
window.uploadServiceImage = uploadServiceImage;
window.replaceServiceImage = replaceServiceImage;
window.deleteServiceImage = deleteServiceImage;

// =====================================
// LOGO PREVIEW UPDATE
// =====================================
// logoPreview já declarado no topo do arquivo

// Atualizar preview do texto da logo
if (logoTextInput && logoPreview) {
    logoTextInput.addEventListener('input', (e) => {
        const text = e.target.value || 'Gustavo Starke';
        logoPreview.textContent = text;
    });
}

// Atualizar preview do tamanho da fonte
if (logoFontSizeInputs && logoPreview) {
    logoFontSizeInputs.forEach(input => {
        input.addEventListener('change', (e) => {
            logoPreview.style.fontSize = e.target.value + 'px';
        });
    });
}

// Atualizar preview do peso da fonte
if (logoFontWeightInputs && logoPreview) {
    logoFontWeightInputs.forEach(input => {
        input.addEventListener('change', (e) => {
            logoPreview.style.fontWeight = e.target.value;
        });
    });
}

// Atualizar preview do espaçamento de letras
if (logoLetterSpacingInputs && logoPreview) {
    logoLetterSpacingInputs.forEach(input => {
        input.addEventListener('change', (e) => {
            logoPreview.style.letterSpacing = e.target.value + 'em';
        });
    });
}
// Helper para carregar Google Fonts dinamicamente
function loadGoogleFont(fontName) {
    if (!fontName || fontName === 'Inter') return; // Inter já é padrão

    // Evitar duplicidade
    const id = `font-loader-${fontName.replace(/\s+/g, '-').toLowerCase()}`;
    if (document.getElementById(id)) return;

    const link = document.createElement('link');
    link.id = id;
    link.rel = 'stylesheet';
    link.href = `https://fonts.googleapis.com/css2?family=${fontName.replace(/\s+/g, '+')}:wght@300;400;500;600;700&display=swap`;
    document.head.appendChild(link);
}

// Listener para mudança de fonte
if (typeof logoFontFamilyInput !== 'undefined' && logoFontFamilyInput && logoPreview) {
    logoFontFamilyInput.addEventListener('change', (e) => {
        const fontName = e.target.value;
        loadGoogleFont(fontName);
        logoPreview.style.fontFamily = `"${fontName}", sans-serif`;
    });
}

// =================================
// COLLECTIONS - CRUD
// =================================

let collectionsData = [];

/**
 * Renderiza a lista de coleções
 * @param {Array} collections - Lista de coleções
 */
function renderCollections(collections) {
    console.log('Renderizando coleções:', collections);
    collectionsData = collections;
    const collectionsList = document.getElementById('collections-list');
    if (!collectionsList) return;

    collectionsList.innerHTML = '';

    if (collections.length === 0) {
        collectionsList.innerHTML = '<p style="opacity: 0.5; font-size: 0.9rem;">Nenhuma coleção cadastrada. Adicione uma coleção para começar.</p>';
        return;
    }

    collections.forEach((collection) => {
        const card = createCollectionCard(collection);
        collectionsList.appendChild(card);
    });

    // Make sortable
    if (typeof Sortable !== 'undefined') {
        new Sortable(collectionsList, {
            handle: '.drag-handle',
            animation: 150,
            ghostClass: 'sortable-ghost',
            onEnd: saveCollectionsOrder
        });
    }
}

/**
 * Cria um card de coleção
 * @param {Object} collection - Dados da coleção
 * @returns {HTMLElement} Card da coleção
 */
function createCollectionCard(collection) {
    const card = document.createElement('div');
    card.className = 'collection-card';
    card.setAttribute('data-id', collection.id);

    // Usar primary_image_path se existir, senão placeholder
    const imagePath = collection.primary_image_path || 'https://placehold.co/400x300/EEE/31343C?text=Sem+Imagem';

    card.innerHTML = `
        <div class="drag-handle" title="Arrastar para reordenar">
            ⋮⋮
        </div>
        
        <div class="collection-thumbnail" onclick="uploadCollectionImage(${collection.id})">
            <img src="${imagePath}" alt="${collection.title}">
            <div class="thumbnail-overlay">
                <span>Alterar Imagem</span>
            </div>
        </div>
        
        <div class="collection-info">
            <input type="text" class="collection-title admin-input" value="${collection.title || ''}" placeholder="Nome da Coleção">
            <textarea class="collection-desc admin-input" rows="2" placeholder="Descrição">${collection.description || ''}</textarea>
            <div class="collection-meta">
                <input type="text" class="collection-producer admin-input" value="${collection.produced_by || ''}" placeholder="Produzido por" style="flex: 1;">
                <input type="text" class="collection-year admin-input" value="${collection.year || ''}" placeholder="Ano" style="width: 80px;">
            </div>
            <div class="collection-stats">
                <span>${collection.image_count || 0} imagem(ns) • ${collection.product_count || 0} produto(s)</span>
            </div>
        </div>
        
        <div class="collection-actions">
            <button class="btn-edit-collection" onclick="editCollection(${collection.id})" title="Editar Coleção">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                    <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                </svg>
            </button>
            <button class="btn-delete-collection" onclick="deleteCollection(${collection.id}, this)" title="Remover Coleção">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="3 6 5 6 21 6"></polyline>
                    <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                </svg>
            </button>
        </div>
    `;

    return card;
}

/**
 * Adiciona uma nova coleção
 */
async function addCollection() {
    try {
        const json = await apiRequest('add_collection');
        if (json.success) {
            loadGallery(); // Recarrega tudo
        } else {
            alert('Erro ao adicionar coleção: ' + (json.error || 'desconhecido'));
        }
    } catch (e) {
        console.error('Erro ao adicionar coleção:', e);
        alert('Erro na requisição');
    }
}

/**
 * Edita uma coleção (placeholder para funcionalidade futura)
 * @param {number} id - ID da coleção
 */
function editCollection(id) {
    // Redirecionar para a página de edição dedicada
    window.location.href = `edit_collection.php?id=${id}`;
}

/**
 * Deleta uma coleção
 * @param {number} id - ID da coleção
 * @param {HTMLElement} btn - Botão que foi clicado
 */
async function deleteCollection(id, btn) {
    if (!confirm('Remover esta coleção? Os produtos associados ficarão sem coleção.')) return;

    try {
        const json = await apiRequest('delete_collection', { id });
        if (json.success) {
            btn.closest('.collection-card').remove();
        } else {
            alert('Erro ao deletar: ' + (json.error || 'desconhecido'));
        }
    } catch (e) {
        console.error('Erro ao deletar coleção:', e);
        alert('Erro na requisição');
    }
}

/**
 * Abre o seletor de arquivo para upload de imagem da coleção
 * @param {number} collectionId - ID da coleção
 */
function uploadCollectionImage(collectionId) {
    console.log('uploadCollectionImage chamado com ID:', collectionId);
    console.log('collectionImageInput:', collectionImageInput);
    currentCollectionImageId = collectionId;
    if (collectionImageInput) {
        collectionImageInput.click();
    } else {
        console.error('collectionImageInput não encontrado!');
    }
}

// Event listener para upload de imagem de coleção
collectionImageInput.addEventListener('change', async (e) => {
    if (!e.target.files.length) return;

    const file = e.target.files[0];
    const formData = new FormData();
    formData.append('action', 'upload_collection_image');
    formData.append('collection_id', currentCollectionImageId);
    formData.append('image', file);

    document.body.style.cursor = 'wait';

    try {
        const res = await fetch('gallery_api.php', {
            method: 'POST',
            body: formData
        });
        const json = await res.json();

        if (json.success) {
            // Atualizar thumbnail
            const card = document.querySelector(`.collection-card[data-id="${currentCollectionImageId}"]`);
            if (card) {
                const img = card.querySelector('.collection-thumbnail img');
                img.src = json.image_path + '?v=' + new Date().getTime();
            }
        } else {
            alert('Erro ao fazer upload: ' + (json.error || 'desconhecido'));
        }
    } catch (error) {
        console.error('Erro ao fazer upload:', error);
        alert('Erro na conexão.');
    } finally {
        document.body.style.cursor = 'default';
        collectionImageInput.value = '';
    }
});

/**
 * Salva a ordem das coleções após drag and drop
 */
async function saveCollectionsOrder() {
    const items = [];
    document.querySelectorAll('.collection-card').forEach((el, index) => {
        items.push({
            id: el.getAttribute('data-id'),
            display_order: index + 1
        });
    });

    try {
        await apiRequest('save_collections_order', { items });
    } catch (e) {
        console.error('Erro ao salvar ordem:', e);
    }
}

/**
 * Salva todas as coleções (chamado pelo botão Salvar)
 */
async function saveCollections() {
    const collectionsToSave = [];
    document.querySelectorAll('.collection-card').forEach((el, index) => {
        const titleEl = el.querySelector('.collection-title');
        const descEl = el.querySelector('.collection-desc');
        const producerEl = el.querySelector('.collection-producer');
        const yearEl = el.querySelector('.collection-year');

        if (titleEl) {
            collectionsToSave.push({
                id: el.getAttribute('data-id'),
                title: titleEl.value,
                description: descEl?.value || '',
                produced_by: producerEl?.value || '',
                year: yearEl?.value || '',
                display_order: index + 1
            });
        }
    });

    // Salvar cada coleção individualmente
    for (const collection of collectionsToSave) {
        await apiRequest('save_collection', collection);
    }
}

// Adicionar saveCollections ao botão Salvar (modificar o listener existente)
if (btnSave) {
    btnSave.addEventListener('click', async () => {
        await saveCollections();
    });
}

// Exportar funções globais
window.addCollection = addCollection;
window.editCollection = editCollection;
window.deleteCollection = deleteCollection;
window.uploadCollectionImage = uploadCollectionImage;
window.loadCollections = loadGallery; // Aponta para loadGallery para compatibilidade

