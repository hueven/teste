/**
 * Categories Management Module
 * Gerencia categorias de projetos no admin panel
 */

import { apiRequest } from './utils.js';
import { SVG_ICONS } from './templates.js';

// Estado
let categoriesData = [];
let categoriesList = null;

/**
 * Inicializa o módulo de categorias
 */
export function initCategories() {
    categoriesList = document.getElementById('categories-list');

    // Inicializar Sortable para categorias
    if (categoriesList && typeof Sortable !== 'undefined') {
        new Sortable(categoriesList, {
            handle: '.drag-handle',
            animation: 150,
            ghostClass: 'sortable-ghost',
            onEnd: () => {
                // Auto-save order when dragging ends
                saveCategories();
            }
        });
    }
}

/**
 * Renderiza a lista de categorias
 * @param {Array} categories - Lista de categorias do banco
 */
export function renderCategories(categories) {
    if (!categoriesList) return;

    categoriesData = categories;
    categoriesList.innerHTML = '';

    if (categories.length === 0) {
        categoriesList.innerHTML = '<p style="opacity: 0.5; text-align: center; padding: 40px;">Nenhuma categoria cadastrada. Crie sua primeira categoria!</p>';
        return;
    }

    categories.forEach((category) => {
        categoriesList.appendChild(createCategoryCard(category));
    });
}

/**
 * Cria um card de categoria para a lista
 * @param {Object} category - Dados da categoria
 * @returns {HTMLElement} Elemento do card
 */
function createCategoryCard(category) {
    const card = document.createElement('div');
    card.className = 'category-list-item';
    card.setAttribute('data-id', category.id);

    const projectCount = category.project_count || 0;
    const projectText = projectCount === 1 ? 'projeto' : 'projetos';

    card.innerHTML = `
        <div class="drag-handle">${SVG_ICONS.dragHandle}</div>
        <div class="category-info">
            <input type="text" 
                   class="category-name-input" 
                   value="${category.name || ''}"
                   placeholder="Nome da categoria"
                   data-id="${category.id}">
            <p class="category-meta-info">${projectCount} ${projectText}</p>
        </div>
        <div class="category-actions">
            <button class="btn-delete-category" 
                    onclick="deleteCategory(${category.id}, this)" 
                    title="Deletar Categoria"
                    ${projectCount > 0 ? 'disabled' : ''}>
                ${SVG_ICONS.delete}
            </button>
        </div>
    `;

    // Add event listener for name changes
    const nameInput = card.querySelector('.category-name-input');
    nameInput.addEventListener('blur', () => updateCategoryName(category.id, nameInput.value));
    nameInput.addEventListener('keypress', (e) => {
        if (e.key === 'Enter') {
            e.preventDefault();
            nameInput.blur();
        }
    });

    return card;
}

/**
 * Adiciona uma nova categoria
 */
export async function addCategory() {
    try {
        const json = await apiRequest('add_category', {
            name: 'Nova Categoria'
        });

        if (json.success && json.id) {
            // Reload categories
            const response = await apiRequest('get_categories', {});
            if (response.success && response.categories) {
                renderCategories(response.categories);
            }
        } else {
            alert(`Erro ao criar categoria: ${json.error}`);
        }
    } catch (e) {
        console.error('Erro ao criar categoria:', e);
        alert('Erro na requisição');
    }
}

/**
 * Atualiza o nome de uma categoria
 * @param {number} id - ID da categoria
 * @param {string} newName - Novo nome
 */
async function updateCategoryName(id, newName) {
    if (!newName || newName.trim() === '') {
        alert('O nome da categoria não pode estar vazio');
        // Reload to restore original name
        const response = await apiRequest('get_categories', {});
        if (response.success && response.categories) {
            renderCategories(response.categories);
        }
        return;
    }

    try {
        const json = await apiRequest('update_category', {
            id: id,
            name: newName.trim()
        });

        if (!json.success) {
            alert(`Erro ao atualizar: ${json.error}`);
            // Reload to restore original name
            const response = await apiRequest('get_categories', {});
            if (response.success && response.categories) {
                renderCategories(response.categories);
            }
        }
    } catch (e) {
        console.error('Erro ao atualizar categoria:', e);
        alert('Erro na requisição');
    }
}

/**
 * Deleta uma categoria
 * @param {number} id - ID da categoria
 * @param {HTMLElement} btn - Botão que foi clicado
 */
export async function deleteCategory(id, btn) {
    if (!confirm('Deletar esta categoria?')) return;

    try {
        const json = await apiRequest('delete_category', { id });
        if (json.success) {
            btn.closest('.category-list-item').remove();
            categoriesData = categoriesData.filter(c => c.id != id);

            // Se não há mais categorias, mostrar mensagem
            if (categoriesData.length === 0 && categoriesList) {
                categoriesList.innerHTML = '<p style="opacity: 0.5; text-align: center; padding: 40px;">Nenhuma categoria cadastrada. Crie sua primeira categoria!</p>';
            }
        } else {
            alert(`Erro ao deletar: ${json.error}`);
        }
    } catch (e) {
        console.error('Erro ao deletar categoria:', e);
        alert('Erro na requisição');
    }
}

/**
 * Salva a ordem das categorias
 * @returns {Promise<boolean>} True se sucesso
 */
export async function saveCategories() {
    const categoryIds = Array.from(document.querySelectorAll('.category-list-item'))
        .map(el => el.getAttribute('data-id'));

    if (categoryIds.length === 0) {
        return true;
    }

    try {
        await apiRequest('reorder_categories', { categories: categoryIds });
        return true;
    } catch (e) {
        console.error('Erro ao salvar ordem das categorias:', e);
        return false;
    }
}

// Expor funções globalmente
window.addCategory = addCategory;
window.deleteCategory = deleteCategory;
window.renderCategories = renderCategories;
