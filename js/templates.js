/**
 * Wide Studio - HTML Templates
 * Templates HTML reutilizáveis para componentes
 */

// =================================
// SVG ICONS (Centralizados)
// =================================

export const SVG_ICONS = {
    delete: `
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <polyline points="3 6 5 6 21 6"></polyline>
            <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
        </svg>
    `,

    dragHandle: `
        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <circle cx="9" cy="12" r="1"></circle>
            <circle cx="9" cy="5" r="1"></circle>
            <circle cx="9" cy="19" r="1"></circle>
            <circle cx="15" cy="12" r="1"></circle>
            <circle cx="15" cy="5" r="1"></circle>
            <circle cx="15" cy="19" r="1"></circle>
        </svg>
    `,

    upload: `
        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
            <polyline points="17 8 12 3 7 8"></polyline>
            <line x1="12" y1="3" x2="12" y2="15"></line>
        </svg>
    `,

    close: '×',

    heart: `
        <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor" stroke="none">
            <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"></path>
        </svg>
    `
};

// =================================
// ADMIN TEMPLATES
// =================================

/**
 * Cria um card de serviço para o admin
 * @param {Object} item - Dados do serviço
 * @param {number} index - Índice do serviço
 * @returns {HTMLElement} Elemento do card
 */
export function createServiceCard(item, index) {
    const card = document.createElement('div');
    card.className = 'service-card';
    card.setAttribute('data-id', item.id);

    card.innerHTML = `
        <div class="drag-handle service-drag">${SVG_ICONS.dragHandle}</div>
        <div class="service-actions">
            <button class="btn-icon-delete" onclick="deleteService(${item.id}, this)" title="Excluir Serviço">
                ${SVG_ICONS.delete}
            </button>
        </div>
        <div class="service-meta">Serviço ${String(index + 1).padStart(2, '0')}</div>
        <div class="service-field">
            <label class="input-label">Título do Serviço</label>
            <input type="text" class="nice-input service-title" value="${item.title || ''}" placeholder="Ex: Fotografia">
        </div>
        <div class="service-field">
            <label class="input-label">Descrição</label>
            <textarea class="nice-input service-desc" rows="4" placeholder="Descreva os detalhes...">${item.description || ''}</textarea>
        </div>
        <div class="service-field">
            <label class="input-label">Imagem do Serviço</label>
            ${item.image_path ? `
                <div class="service-image-preview">
                    ${(() => {
                const ext = item.image_path.split('.').pop().toLowerCase();
                const isVideo = ['mp4', 'webm', 'mov'].includes(ext);
                return isVideo
                    ? `<video class="service-preview-img" autoplay loop muted playsinline preload="auto">
                                   <source src="${item.image_path}?v=${Date.now()}" type="video/${ext === 'mov' ? 'mp4' : ext}">
                               </video>`
                    : `<img src="${item.image_path}?v=${Date.now()}" alt="Preview" class="service-preview-img">`;
            })()}
                    <div class="service-image-actions">
                        <button type="button" class="btn-secondary-small" onclick="replaceServiceImage(${item.id})" title="Trocar Imagem">
                            🔄 Trocar
                        </button>
                        <button type="button" class="btn-danger-small" onclick="deleteServiceImage(${item.id}, this)" title="Remover Imagem">
                            🗑️ Remover
                        </button>
                    </div>
                </div>
            ` : `
                <button type="button" class="btn-upload-service" onclick="uploadServiceImage(${item.id})">
                    <span style="font-size: 1.2rem;">📁</span> Enviar Imagem/Vídeo
                </button>
                <p style="font-size: 0.8rem; color: #888; margin-top: 8px;">Clique para selecionar uma imagem ou vídeo do seu computador</p>
            `}
        </div>
    `;

    return card;
}

/**
 * Cria um card de projeto para o admin
 * @param {Object} project - Dados do projeto
 * @param {number} index - Índice do projeto
 * @returns {HTMLElement} Elemento do card
 */
export function createProjectCard(project, index) {
    const card = document.createElement('div');
    card.className = 'service-card project-card';
    card.setAttribute('data-id', project.id);

    card.innerHTML = `
        <div class="drag-handle service-drag">${SVG_ICONS.dragHandle}</div>
        <div class="service-actions">
            <button class="btn-icon-delete" onclick="deleteProject(${project.id}, this)" title="Excluir Projeto">
                ${SVG_ICONS.delete}
            </button>
        </div>
        <div class="service-meta">Projeto ${String(index + 1).padStart(2, '0')}</div>
        
        <!-- Project Title -->
        <div class="service-field">
            <label class="input-label">Título do Projeto</label>
            <input type="text" class="nice-input project-title" value="${project.title || ''}" placeholder="Ex: Residência Moderna">
        </div>
        
        <!-- Project Slug -->
        <div class="service-field">
            <label class="input-label">URL (slug)</label>
            <input type="text" class="nice-input project-slug" value="${project.slug || ''}" placeholder="residencia-moderna">
            <p style="font-size: 0.75rem; color: #888; margin-top: 4px;">
                URL do projeto: /${project.slug || '...'}
            </p>
        </div>
        
        <!-- Project Description -->
        <div class="service-field">
            <label class="input-label">Descrição</label>
            <textarea class="nice-input project-desc" rows="3" placeholder="Descreva o projeto...">${project.description || ''}</textarea>
        </div>
        
        <!-- Hero Image -->
        <div class="service-field">
            <label class="input-label">Imagem Principal (Hero)</label>
            ${project.hero_image_path ? `
                <div class="service-image-preview hero-dropzone" 
                     data-project-id="${project.id}"
                     ondragover="handleHeroDragOver(event)" 
                     ondragleave="handleHeroDragLeave(event)" 
                     ondrop="handleHeroDrop(event)">
                     
                    <img src="${project.hero_image_path}?v=${Date.now()}" alt="Hero Image" class="service-preview-img">
                    <div class="service-image-actions">
                        <button type="button" class="btn-secondary-small" onclick="replaceProjectHero(${project.id})" title="Trocar Imagem Hero">
                            Trocar
                        </button>
                        <button type="button" class="btn-danger-small" onclick="deleteProjectHero(${project.id}, this)" title="Remover Imagem">
                            Remover
                        </button>
                        <div class="drag-overlay">Solte para trocar</div>
                    </div>
                </div>
            ` : `
                <div class="service-image-upload hero-dropzone" 
                     data-project-id="${project.id}"
                     ondragover="handleHeroDragOver(event)" 
                     ondragleave="handleHeroDragLeave(event)" 
                     ondrop="handleHeroDrop(event)"
                     onclick="uploadProjectHero(${project.id}, this)">
                     
                    <span class="upload-icon">📷</span>
                    <span class="upload-text">Clique ou arraste uma imagem aqui</span>
                </div>
                <p class="input-hint">Capa do projeto na home e no topo da página interna.</p>
            `}
        </div>
        
        <!-- Manage Project Images Button -->
        <div class="service-field" style="border-top: 1px solid rgba(0,0,0,0.1); padding-top: 20px; margin-top: 10px;">
            <button type="button" class="btn-secondary-small" onclick="manageProjectImages(${project.id})" style="width: 100%; padding: 12px; display: flex; align-items: center; justify-content: center; gap: 8px;">
                <span>📸 Gerenciar Fotos</span>
                <span style="background: rgba(0,0,0,0.15); padding: 3px 10px; border-radius: 12px; font-size: 0.85em; font-weight: 600;">${project.image_count || 0}</span>
            </button>
        </div>
    `;

    return card;
}


/**
 * Cria um item de menu para o admin
 * @param {Object} item - Dados do menu
 * @returns {HTMLElement} Elemento do menu
 */
export function createMenuItem(item) {
    const div = document.createElement('div');
    div.className = 'menu-item-row menu-grid-layout';
    div.setAttribute('data-id', item.id);

    div.innerHTML = `
        <div class="drag-handle">${SVG_ICONS.dragHandle}</div>
        <div>
            <input type="text" class="nice-input menu-title" value="${item.title || ''}" placeholder="Nome">
        </div>
        <div>
            <input type="text" class="nice-input menu-link" value="${item.link_url || ''}" placeholder="URL">
        </div>
        <button class="btn-icon-delete" onclick="deleteMenuItem(${item.id}, this)" title="Remover Link">
            ${SVG_ICONS.delete}
        </button>
    `;

    return div;
}

/**
 * Cria um item de tipo de imagem para o admin
 * @param {Object} item - Dados do tipo
 * @returns {HTMLElement} Elemento do tipo
 */
export function createImageTypeItem(item) {
    const div = document.createElement('div');
    div.className = 'image-type-item';
    div.setAttribute('data-id', item.id);

    div.innerHTML = `
        <div class="drag-handle">${SVG_ICONS.dragHandle}</div>
        <input type="text" class="nice-input type-name" value="${item.name || ''}" placeholder="Nome do tipo">
        <button class="btn-icon-delete" onclick="deleteImageType(${item.id}, this)" title="Remover Tipo">
            ${SVG_ICONS.delete}
        </button>
    `;

    return div;
}

/**
 * Gera opções de select para tipos de imagem
 * @param {Array} types - Array de tipos disponíveis
 * @param {number|null} selectedId - ID do tipo selecionado
 * @returns {string} HTML das opções
 */
export function getTypeOptions(types, selectedId) {
    let options = '<option value="">Sem tipo</option>';
    types.forEach(type => {
        const selected = (selectedId && selectedId == type.id) ? 'selected' : '';
        options += `<option value="${type.id}" ${selected}>${type.name}</option>`;
    });
    return options;
}

/**
 * Cria um item de galeria para o admin
 * @param {Object} item - Dados da imagem
 * @param {Array} imageTypes - Tipos disponíveis
 * @returns {HTMLElement} Elemento da galeria
 */
export function createGalleryItem(item, imageTypes = []) {
    const el = document.createElement('div');
    el.className = 'gallery-item';
    el.setAttribute('data-id', item.id);
    const align = item.alignment || 'center';

    el.innerHTML = `
        <div class="drag-handle">${SVG_ICONS.dragHandle}</div>
        <div class="img-wrapper">
            <img src="${item.image_path}?v=${new Date().getTime()}" 
                 class="item-thumb" 
                 style="object-position: center ${align};"
                 onerror="this.src='../logo/logo.png'">
            <button class="btn-replace" onclick="triggerReplace(${item.id})" title="Trocar Imagem">
                Trocar
            </button>
        </div>
        <div class="item-details">
            <div class="input-group">
                <label>Título</label>
                <input type="text" class="admin-input title-input" value="${item.title || ''}" placeholder="Sem Título">
            </div>
            <div class="input-group">
                <label>Descrição</label>
                <textarea class="admin-input desc-input" rows="3" placeholder="Descrição do projeto">${item.description || ''}</textarea>
            </div>
            <div class="input-group input-group-small">
                <label>Tipo</label>
                <select class="admin-input type-input">
                    ${getTypeOptions(imageTypes, item.type_id)}
                </select>
            </div>
            <div class="input-group input-group-small">
                <label>Alinhamento</label>
                <select class="admin-input align-input">
                    <option value="top" ${align === 'top' ? 'selected' : ''}>Topo</option>
                    <option value="center" ${align === 'center' ? 'selected' : ''}>Centro</option>
                    <option value="bottom" ${align === 'bottom' ? 'selected' : ''}>Baixo</option>
                </select>
            </div>
        </div>
        <button class="btn-delete" title="Remover" onclick="deleteItem(${item.id}, this)">${SVG_ICONS.close}</button>
    `;

    return el;
}

// =================================
// PUBLIC GALLERY TEMPLATES
// =================================

/**
 * Cria um elemento de trabalho para galeria pública
 * @param {Object} item - Dados do trabalho
 * @returns {HTMLElement} Elemento do trabalho
 */
export function createWorkItem(item) {
    const workItem = document.createElement('div');
    workItem.className = 'work-item';
    workItem.setAttribute('data-id', item.id);
    workItem.setAttribute('data-title', item.title || 'Untitled');
    workItem.setAttribute('data-desc', item.description || '');
    workItem.setAttribute('data-likes', item.likes || '0');
    if (item.type) {
        workItem.setAttribute('data-type', item.type);
    }

    const img = document.createElement('img');
    img.src = item.image_path;
    img.alt = item.title || 'Wide Studio Work';
    img.loading = 'lazy';

    workItem.appendChild(img);

    return workItem;
}

/**
 * Cria um botão de filtro
 * @param {string} value - Valor do filtro
 * @param {string} label - Label do botão
 * @param {boolean} active - Se está ativo
 * @returns {HTMLElement} Botão de filtro
 */
export function createFilterButton(value, label, active = false) {
    const btn = document.createElement('button');
    btn.className = `filter-btn${active ? ' active' : ''}`;
    btn.setAttribute('data-filter', value);
    btn.textContent = label;

    return btn;
}
