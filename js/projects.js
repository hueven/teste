/**
 * Wide Studio - Projects Management Module (Simplified)
 * Gerencia projetos no admin panel - versão simplificada para lista
 */

import { apiRequest } from './utils.js';
import { SVG_ICONS } from './templates.js';

// Estado
let projectsData = [];
let projectsList = null;
let isAddingProject = false;

/**
 * Inicializa o módulo de projetos
 */
export function initProjects() {
    projectsList = document.getElementById('projects-list');

    // Inicializar Sortable para projetos
    if (projectsList && typeof Sortable !== 'undefined') {
        new Sortable(projectsList, {
            handle: '.drag-handle',
            animation: 150,
            ghostClass: 'sortable-ghost'
        });
    }
}

/**
 * Renderiza a lista de projetos (versão simplificada)
 * @param {Array} projects - Lista de projetos do banco
 */
export function renderProjects(projects) {
    if (!projectsList) return;

    projectsData = projects;
    projectsList.innerHTML = '';

    if (projects.length === 0) {
        projectsList.innerHTML = '<p style="opacity: 0.5; text-align: center; padding: 40px;">Nenhum projeto cadastrado. Crie seu primeiro projeto!</p>';
        return;
    }

    projects.forEach((project) => {
        projectsList.appendChild(createSimpleProjectCard(project));
    });
}

/**
 * Cria um card simples de projeto para a lista
 * @param {Object} project - Dados do projeto
 * @returns {HTMLElement} Elemento do card
 */
function createSimpleProjectCard(project) {
    const card = document.createElement('div');
    card.className = 'project-list-item';
    card.setAttribute('data-id', project.id);

    const heroThumbnail = project.hero_image_path
        ? `<img src="${project.hero_image_path}" alt="${project.title}" class="project-thumbnail">`
        : `<div class="project-thumbnail-placeholder">
               <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                   <rect x="3" y="3" width="18" height="18" rx="2" ry="2"/>
                   <circle cx="8.5" cy="8.5" r="1.5"/>
                   <polyline points="21 15 16 10 5 21"/>
               </svg>
           </div>`;

    card.innerHTML = `
        <div class="drag-handle">${SVG_ICONS.dragHandle}</div>
        ${heroThumbnail}
        <div class="project-info">
            <h3 class="project-name">${project.title || 'Sem Título'}</h3>
            ${project.category_name ? `<span class="project-category-pill">${project.category_name}</span>` : ''}
            <p class="project-meta-info">
                ${project.image_count || 0} ${project.image_count === 1 ? 'imagem' : 'imagens'}
            </p>
        </div>
        <div class="project-actions">
            <a href="edit-project.php?id=${project.id}" class="btn-edit-project">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
                    <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
                </svg>
                Editar
            </a>
            <button class="btn-delete-project" onclick="deleteProject(${project.id}, this)" title="Deletar Projeto">
                ${SVG_ICONS.delete}
            </button>
        </div>
    `;

    return card;
}

/**
 * Adiciona um novo projeto e redireciona para edição
 */
export async function addNewProject() {
    if (isAddingProject) {
        console.log('Já está adicionando um projeto...');
        return;
    }

    isAddingProject = true;

    try {
        const json = await apiRequest('add_project', {
            title: 'Novo Projeto',
            description: ''
        });

        if (json.success && json.id) {
            // Redirecionar diretamente para a página de edição
            window.location.href = `edit-project.php?id=${json.id}`;
        } else {
            alert(`Erro ao criar projeto: ${json.error}`);
            isAddingProject = false;
        }
    } catch (e) {
        console.error('Erro ao criar projeto:', e);
        alert('Erro na requisição');
        isAddingProject = false;
    }
}

/**
 * Deleta um projeto
 * @param {number} id - ID do projeto
 * @param {HTMLElement} btn - Botão que foi clicado
 */
export async function deleteProject(id, btn) {
    if (!confirm('Deletar este projeto? Todas as imagens associadas serão removidas!')) return;

    try {
        const json = await apiRequest('delete_project', { id });
        if (json.success) {
            btn.closest('.project-list-item').remove();
            projectsData = projectsData.filter(p => p.id != id);

            // Se não há mais projetos, mostrar mensagem
            if (projectsData.length === 0 && projectsList) {
                projectsList.innerHTML = '<p style="opacity: 0.5; text-align: center; padding: 40px;">Nenhum projeto cadastrado. Crie seu primeiro projeto!</p>';
            }
        } else {
            alert(`Erro ao deletar: ${json.error}`);
        }
    } catch (e) {
        console.error('Erro ao deletar projeto:', e);
        alert('Erro na requisição');
    }
}

/**
 * Salva a ordem dos projetos (chamado pelo admin.js)
 * @returns {Promise<boolean>} True se sucesso
 */
export async function saveAllProjects() {
    const projectIds = Array.from(document.querySelectorAll('.project-list-item'))
        .map(el => el.getAttribute('data-id'));

    if (projectIds.length === 0) {
        return true;
    }

    try {
        await apiRequest('reorder_projects', { projects: projectIds });
        return true;
    } catch (e) {
        console.error('Erro ao salvar ordem dos projetos:', e);
        throw e;
    }
}

// Expor funções globalmente
window.addNewProject = addNewProject;
window.deleteProject = deleteProject;
window.renderProjects = renderProjects;
