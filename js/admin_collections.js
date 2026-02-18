/**
 * Wide Studio - Admin Collections JS
 * Gerencia a lista de coleções e seus produtos
 */

import { apiRequest } from './utils.js';
import { showSaveFeedback } from './admin_common.js';

// Estado
let collectionsData = [];
let currentCollectionImageId = null;

// Elementos
const collectionsList = document.getElementById('collections-list');
const collectionImageInput = document.getElementById('collectionImageInput');
const addCollectionBtn = document.getElementById('addCollectionBtn');

document.addEventListener('DOMContentLoaded', () => {
    loadCollections();

    // Listener para o botão global de salvar
    window.addEventListener('savePageData', saveCollections);

    // Botão Adicionar Coleção
    if (addCollectionBtn) {
        addCollectionBtn.addEventListener('click', addCollection);
    }

    // Upload de Imagem
    if (collectionImageInput) {
        collectionImageInput.addEventListener('change', handleImageUpload);
    }
});

// --- CARREGAMENTO ---

async function loadCollections() {
    if (!collectionsList) return;

    try {
        const json = await fetch('gallery_api').then(r => r.json());
        if (json.success && json.collections) {
            renderCollections(json.collections);
        } else {
            collectionsList.innerHTML = '<p class="error-msg">Erro ao carregar dados.</p>';
        }
    } catch (e) {
        console.error('Erro loading collections:', e);
        collectionsList.innerHTML = '<p class="error-msg">Erro crítico de conexão.</p>';
    }
}

function renderCollections(collections) {
    collectionsData = collections;
    collectionsList.innerHTML = '';

    if (collections.length === 0) {
        collectionsList.innerHTML = '<p style="opacity: 0.5; font-size: 0.9rem;">Nenhuma coleção cadastrada.</p>';
        return;
    }

    collections.forEach((collection) => {
        const card = createCollectionCard(collection);
        collectionsList.appendChild(card);
    });

    // Inicializa Sortable
    if (typeof Sortable !== 'undefined') {
        new Sortable(collectionsList, {
            handle: '.drag-handle',
            animation: 150,
            ghostClass: 'sortable-ghost',
            onEnd: saveCollectionsOrder
        });
    }
}

function createCollectionCard(collection) {
    const card = document.createElement('div');
    card.className = 'collection-card';
    card.setAttribute('data-id', collection.id);

    const imagePath = collection.primary_image_path || 'https://placehold.co/400x300/EEE/31343C?text=Sem+Imagem';

    card.innerHTML = `
        <div class="drag-handle" title="Arrastar para reordenar">⋮⋮</div>
        
        <div class="collection-thumbnail" data-id="${collection.id}">
            <img src="${imagePath}" alt="${collection.title}">
            <div class="thumbnail-overlay"><span>Alterar Imagem</span></div>
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
            <button class="btn-edit-collection" data-id="${collection.id}" title="Editar Coleção">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                    <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                </svg>
            </button>
            <button class="btn-delete-collection" data-id="${collection.id}" title="Remover Coleção">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="3 6 5 6 21 6"></polyline>
                    <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                </svg>
            </button>
        </div>
    `;

    // Event Listeners para o Card
    card.querySelector('.collection-thumbnail').addEventListener('click', () => {
        currentCollectionImageId = collection.id;
        collectionImageInput.click();
    });

    card.querySelector('.btn-edit-collection').addEventListener('click', () => {
        window.location.href = `edit_collection.php?id=${collection.id}`;
    });

    card.querySelector('.btn-delete-collection').addEventListener('click', (e) => {
        deleteCollection(collection.id, e.currentTarget);
    });

    return card;
}

// --- AÇÕES ---

async function addCollection() {
    try {
        const json = await apiRequest('add_collection');
        if (json.success) {
            loadCollections();
        } else {
            alert('Erro: ' + (json.error || 'Erro desconhecido'));
        }
    } catch (e) {
        console.error('Erro add collection:', e);
    }
}

async function deleteCollection(id, btn) {
    if (!confirm('Remover esta coleção?')) return;
    try {
        const json = await apiRequest('delete_collection', { id });
        if (json.success) {
            btn.closest('.collection-card').remove();
        }
    } catch (e) {
        console.error('Erro delete collection:', e);
    }
}

async function handleImageUpload(e) {
    if (!e.target.files.length || !currentCollectionImageId) return;

    const formData = new FormData();
    formData.append('action', 'upload_collection_image');
    formData.append('collection_id', currentCollectionImageId);
    formData.append('image', e.target.files[0]);

    document.body.style.cursor = 'wait';
    try {
        const res = await fetch('gallery_api', { method: 'POST', body: formData });
        const json = await res.json();
        if (json.success) {
            const card = document.querySelector(`.collection-card[data-id="${currentCollectionImageId}"] img`);
            if (card) card.src = json.image_path + '?v=' + Date.now();
        } else {
            alert('Upload falhou: ' + json.error);
        }
    } catch (error) {
        console.error('Erro upload:', error);
    } finally {
        document.body.style.cursor = 'default';
        collectionImageInput.value = '';
    }
}

async function saveCollectionsOrder() {
    const items = [];
    document.querySelectorAll('.collection-card').forEach((el, index) => {
        items.push({ id: el.getAttribute('data-id'), display_order: index + 1 });
    });
    try {
        await apiRequest('save_collections_order', { items });
    } catch (e) {
        console.error('Erro reorder:', e);
    }
}

async function saveCollections() {
    const collectionsToSave = [];
    document.querySelectorAll('.collection-card').forEach((el, index) => {
        const title = el.querySelector('.collection-title').value;
        if (title) {
            collectionsToSave.push({
                id: el.getAttribute('data-id'),
                title: title,
                description: el.querySelector('.collection-desc').value,
                produced_by: el.querySelector('.collection-producer').value,
                year: el.querySelector('.collection-year').value,
                display_order: index + 1
            });
        }
    });

    try {
        for (const col of collectionsToSave) {
            await apiRequest('save_collection', col);
        }
        showSaveFeedback(true);
    } catch (e) {
        showSaveFeedback(false, e.message);
    }
}

// Global para compatibilidade se necessário
window.loadCollections = loadCollections;
