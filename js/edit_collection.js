/**
 * Edit Collection Page Script
 * Manages collection info, products, and gallery
 */

console.log('✅ edit_collection.js loaded - V2 (Collections API)');
alert('JS Atualizado (V2)! Usando nova API de Coleções.');

let products = [];
let images = [];
let heroImage = null;

// ============================================
// INITIALIZATION
// ============================================

document.addEventListener('DOMContentLoaded', () => {
    console.log('🚀 DOMContentLoaded - Starting initialization');
    loadCollectionData();
    initializeUploadZone();
    initializeProductsSortable();
});

// ============================================
// DATA LOADING
// ============================================

// SUBSTITUIÇÃO GLOBAL DE ENDPOINT
// Todas as chamadas agora vão para collections_api.php

async function loadCollectionData() {
    console.log('📡 Loading collection data for ID:', COLLECTION_ID);

    try {
        const url = `collections_api.php?action=get_collection_detail&id=${COLLECTION_ID}`;
        console.log('🔗 Fetching URL:', url);

        const response = await fetch(url);
        // ... resta do código igual ...
        console.log('📥 Response status:', response.status, response.statusText);

        const text = await response.text();
        console.log('📄 Raw response:', text.substring(0, 500));

        let data;
        try {
            data = JSON.parse(text);
        } catch (e) {
            console.error('❌ JSON parse error:', e);
            console.error('Raw text:', text);
            alert('Erro ao processar resposta da API');
            return;
        }

        console.log('📦 Parsed data:', data);

        if (data.success) {
            products = data.products || [];
            images = data.images || [];
            heroImage = data.collection.hero_image || null;

            console.log('✅ Data assigned:');
            console.log('  - Products:', products.length);
            console.log('  - Images:', images.length, images);
            console.log('  - Hero:', heroImage);

            renderProducts();
            renderGallery();
        } else {
            console.error('❌ API returned success=false:', data.error);
            alert('Erro: ' + (data.error || 'Erro desconhecido'));
        }
    } catch (error) {
        console.error('💥 Fatal error:', error);
        alert('Erro ao carregar coleção: ' + error.message);
    }
}

// ============================================
// PRODUCTS MANAGEMENT
// ============================================

function renderProducts() {
    const container = document.getElementById('products-list');

    if (products.length === 0) {
        container.innerHTML = '<p style="color: #999; text-align: center; padding: 40px;">Nenhum produto cadastrado</p>';
        return;
    }

    container.innerHTML = products.map(product => `
        <div class="product-item" data-id="${product.id}">
            <span class="product-drag-handle">⋮⋮</span>
            <div class="product-field">
                <strong>Nome</strong>
                ${product.title || '<em style="color: #999;">Sem nome</em>'}
            </div>
            <div class="product-field">
                <strong>Tipo</strong>
                ${product.type || '-'}
            </div>
            <div class="product-field">
                <strong>Dimensões</strong>
                ${product.dimensions || '-'}
            </div>
            <div class="product-field">
                <strong>Materiais</strong>
                ${product.materials || '-'}
            </div>
            <div class="product-actions">
                <button onclick="editProduct(${product.id})" class="btn-icon" title="Editar">✏️</button>
                <button onclick="deleteProduct(${product.id})" class="btn-icon" title="Remover">🗑️</button>
            </div>
        </div>
    `).join('');
}

function initializeProductsSortable() {
    const container = document.getElementById('products-list');
    new Sortable(container, {
        handle: '.product-drag-handle',
        animation: 150,
        onEnd: async (evt) => {
            // Atualizar ordem local
            const movedItem = products.splice(evt.oldIndex, 1)[0];
            products.splice(evt.newIndex, 0, movedItem);

            // Enviar nova ordem para o servidor
            await reorderProducts();
        }
    });
}

async function reorderProducts() {
    const order = products.map((p, index) => ({
        id: p.id,
        display_order: index
    }));

    try {
        await fetch('collections_api.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                action: 'reorder_products',
                products: order
            })
        });
    } catch (error) {
        console.error('Erro ao reordenar:', error);
    }
}

function openProductModal(productId = null) {
    const modal = document.getElementById('product-modal');
    const title = document.getElementById('modal-title');

    if (productId) {
        const product = products.find(p => p.id === productId);
        if (product) {
            title.textContent = 'Editar Produto';
            document.getElementById('product-id').value = product.id;
            document.getElementById('product-title').value = product.title || '';
            document.getElementById('product-type').value = product.type || '';
            document.getElementById('product-dimensions').value = product.dimensions || '';
            document.getElementById('product-materials').value = product.materials || '';
        }
    } else {
        title.textContent = 'Adicionar Produto';
        document.getElementById('product-id').value = '';
        document.getElementById('product-title').value = '';
        document.getElementById('product-type').value = '';
        document.getElementById('product-dimensions').value = '';
        document.getElementById('product-materials').value = '';
    }

    modal.classList.add('active');
}

function closeProductModal() {
    document.getElementById('product-modal').classList.remove('active');
}

function editProduct(productId) {
    openProductModal(productId);
}

async function saveProduct() {
    const productId = document.getElementById('product-id').value;
    const title = document.getElementById('product-title').value.trim();
    const type = document.getElementById('product-type').value.trim();
    const dimensions = document.getElementById('product-dimensions').value.trim();
    const materials = document.getElementById('product-materials').value.trim();

    if (!type) {
        alert('O campo "Tipo" é obrigatório');
        return;
    }

    const action = productId ? 'update_product' : 'add_product';
    const payload = {
        action,
        collection_id: COLLECTION_ID,
        title,
        type,
        dimensions,
        materials
    };

    if (productId) {
        payload.id = productId;
    }

    try {
        const response = await fetch('collections_api.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        });

        const text = await response.text();
        let data;
        try {
            data = JSON.parse(text);
        } catch (e) {
            console.error('SERVER RAW:', text);
            alert('Erro no servidor ao salvar produto.');
            return;
        }

        if (data.success) {
            closeProductModal();
            await loadCollectionData(); // Recarregar
        } else {
            alert('Erro ao salvar produto: ' + (data.error || 'Desconhecido'));
        }
    } catch (error) {
        console.error('Erro:', error);
        alert('Erro de conexão ao salvar produto');
    }
}

async function deleteProduct(productId) {
    if (!confirm('Remover este produto?')) return;

    try {
        const response = await fetch('collections_api.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                action: 'delete_product',
                id: productId
            })
        });

        const text = await response.text();
        let data;
        try {
            data = JSON.parse(text);
        } catch (e) {
            console.error('SERVER RAW:', text);
            alert('Erro no servidor ao remover produto.');
            return;
        }

        if (data.success) {
            await loadCollectionData();
        } else {
            alert('Erro ao remover produto: ' + (data.error || 'Desconhecido'));
        }
    } catch (error) {
        console.error('Erro:', error);
        alert('Erro de conexão ao remover produto');
    }
}

// ============================================
// GALLERY MANAGEMENT
// ============================================

function renderGallery() {
    const container = document.getElementById('gallery-grid');
    const counter = document.getElementById('image-count');

    counter.textContent = images.length;

    if (images.length === 0) {
        container.innerHTML = '<p style="grid-column: 1/-1; text-align: center; color: #999; padding: 40px;">Nenhuma imagem</p>';
        return;
    }

    container.innerHTML = images.map(img => {
        const isHero = heroImage && heroImage.includes(img.name);
        return `
            <div class="gallery-item" data-image="${img.name}">
                <img src="${img.path}" alt="${img.name}">
                ${isHero ? '<span class="hero-badge">HERO</span>' : ''}
                <div class="gallery-item-overlay">
                    ${!isHero ? `<button onclick="setHeroImage('${img.name}')" class="btn-secondary btn-sm">Definir como Hero</button>` : ''}
                    <button onclick="deleteImage('${img.name}')" class="btn-secondary btn-sm">Remover</button>
                </div>
            </div>
        `;
    }).join('');
}

function initializeUploadZone() {
    const zone = document.getElementById('upload-zone');
    const input = document.getElementById('file-input');

    zone.addEventListener('click', () => input.click());

    // Removido listener via JS para usar inline no HTML e garantir execução
    /*
    input.addEventListener('change', (e) => {
        handleFiles(e.target.files);
    });
    */

    zone.addEventListener('dragover', (e) => {
        e.preventDefault();
        zone.classList.add('dragover');
    });

    zone.addEventListener('dragleave', () => {
        zone.classList.remove('dragover');
    });

    zone.addEventListener('drop', (e) => {
        e.preventDefault();
        zone.classList.remove('dragover');
        handleFiles(e.dataTransfer.files);
    });
}

async function handleFiles(files) {
    alert('handleFiles chamado! Arquivos: ' + files.length);
    console.log('🔥 handleFiles called with', files.length, 'files');
    if (files.length === 0) {
        console.warn('⚠️ No files received in handleFiles');
        return;
    }

    document.body.style.cursor = 'wait';
    let hasSuccess = false;

    for (let i = 0; i < files.length; i++) {
        const file = files[i];
        alert(`Processando arquivo ${i + 1}: ` + file.name);

        if (!file.type.startsWith('image/')) {
            alert('Arquivo ignorado (não é imagem): ' + file.name);
            continue;
        }

        const formData = new FormData();
        formData.append('action', 'upload_collection_image');
        formData.append('collection_id', COLLECTION_ID);
        formData.append('image', file);

        try {
            alert('Enviando para o servidor...');
            const res = await fetch('collections_api.php', {
                method: 'POST',
                body: formData
            });

            alert('Resposta do servidor recebida. Status: ' + res.status);

            const text = await res.text();
            alert('Conteúdo da resposta (primeiros 100 chars): ' + text.substring(0, 100));
            let json;

            try {
                json = JSON.parse(text);
            } catch (e) {
                console.error('SERVER RAW:', text);
                alert('Erro: Resposta inválida do servidor. Verifique o console.');
                continue;
            }

            if (json.success) {
                hasSuccess = true;
                console.log('✅ Upload Success:', file.name);
            } else {
                console.error('❌ Upload Error:', json.error);
                alert('Erro: ' + json.error);
            }
        } catch (error) {
            console.error('Connection Error:', error);
            alert('Erro de conexão.');
        }
    }

    document.body.style.cursor = 'default';

    if (hasSuccess) {
        loadCollectionData();
    }
}

async function setHeroImage(imageName) {
    try {
        const response = await fetch('collections_api.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                action: 'set_hero_image',
                collection_id: COLLECTION_ID,
                image_name: imageName,
                folder_name: FOLDER_NAME
            })
        });

        const data = await response.json();

        if (data.success) {
            heroImage = data.hero_image;
            renderGallery();
        } else {
            alert('Erro ao definir imagem hero');
        }
    } catch (error) {
        console.error('Erro:', error);
        alert('Erro ao definir imagem hero');
    }
}

async function deleteImage(imageName) {
    if (!confirm('Remover esta imagem?')) return;

    try {
        const response = await fetch('collections_api.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                action: 'delete_collection_image',
                collection_id: COLLECTION_ID,
                image_name: imageName,
                folder_name: FOLDER_NAME
            })
        });

        const data = await response.json();

        if (data.success) {
            await loadCollectionData();
        } else {
            alert('Erro ao remover imagem');
        }
    } catch (error) {
        console.error('Erro:', error);
        alert('Erro ao remover imagem');
    }
}

// ============================================
// SAVE ALL
// ============================================

async function saveAllChanges() {
    const title = document.getElementById('collection-title').value.trim();
    const description = document.getElementById('collection-description').value.trim();
    const year = document.getElementById('collection-year').value.trim();
    const producedBy = document.getElementById('collection-produced-by').value.trim();

    if (!title) {
        alert('O título é obrigatório');
        return;
    }

    try {
        const response = await fetch('collections_api.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                action: 'update_collection_info',
                id: COLLECTION_ID,
                title,
                description,
                year,
                produced_by: producedBy
            })
        });

        const data = await response.json();

        if (data.success) {
            alert('Alterações salvas com sucesso!');
        } else {
            alert('Erro ao salvar: ' + (data.error || 'Desconhecido'));
        }
    } catch (error) {
        console.error('Erro:', error);
        alert('Erro ao salvar alterações');
    }
}
