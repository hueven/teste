/**
 * Wide Studio - Admin Menu JS
 * Gerencia os links de navegação do header
 */

import { apiRequest } from './utils.js';
import { createMenuItem } from './templates.js';
import { showSaveFeedback } from './admin_common.js';

const menuList = document.getElementById('menu-list');
const addMenuItemBtn = document.getElementById('addMenuItemBtn');

document.addEventListener('DOMContentLoaded', () => {
    loadMenu();
    window.addEventListener('savePageData', saveMenu);

    if (addMenuItemBtn) {
        addMenuItemBtn.addEventListener('click', addMenuItem);
    }
});

async function loadMenu() {
    if (!menuList) return;
    try {
        const json = await fetch('gallery_api').then(r => r.json());
        if (json.success && json.menu) {
            renderMenu(json.menu);
        }
    } catch (e) {
        console.error('Error loading menu:', e);
    }
}

function renderMenu(menu) {
    menuList.innerHTML = '';

    if (menu.length === 0) {
        menuList.innerHTML = '<p style="opacity: 0.5; padding: 20px;">Nenhum item no menu.</p>';
        return;
    }

    menu.forEach(item => {
        const row = createMenuItem(item);
        menuList.appendChild(row);
    });

    // Sortable
    if (typeof Sortable !== 'undefined') {
        new Sortable(menuList, {
            handle: '.drag-handle',
            animation: 150,
            ghostClass: 'sortable-ghost'
        });
    }
}

async function addMenuItem() {
    try {
        const json = await apiRequest('add_menu_item');
        if (json.success) {
            loadMenu();
        }
    } catch (e) {
        console.error('Error adding menu item:', e);
    }
}

async function saveMenu() {
    const menuItems = [];
    document.querySelectorAll('.menu-item-row').forEach((row, index) => {
        const title = row.querySelector('.menu-title').value;
        const link = row.querySelector('.menu-link').value;
        if (title) {
            menuItems.push({
                id: row.getAttribute('data-id'),
                title: title,
                link_url: link,
                display_order: index
            });
        }
    });

    try {
        const json = await apiRequest('save_menu', { items: menuItems });
        showSaveFeedback(json.success);
    } catch (e) {
        showSaveFeedback(false, e.message);
    }
}

// Global para templates.js se necessário (botões de deletar geralmente chamam via onclick se não refatorados)
window.deleteMenuItem = async function (id, btn) {
    if (!confirm('Remover este item do menu?')) return;
    try {
        const json = await apiRequest('delete_menu_item', { id });
        if (json.success) {
            btn.closest('.menu-item-row').remove();
        }
    } catch (e) {
        console.error('Error deleting menu item:', e);
    }
};
