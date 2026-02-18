/**
 * Edit Project Page - Visual Editor
 * Handles all editing functionality for projects
 */

import { apiRequest } from './utils.js';

// ===================================
// STATE
// ===================================
let hasUnsavedChanges = false;
let currentProjectId = window.PROJECT_DATA?.id;
let projectImages = [];

// ===================================
// DOM ELEMENTS
// ===================================
const btnSaveProject = document.getElementById('btnSaveProject');
const editStatus = document.getElementById('editStatus');

// Hero Image
const heroImageContainer = document.getElementById('heroImageContainer');
const heroImage = document.getElementById('heroImage');
const btnUploadHero = document.getElementById('btnUploadHero');
const btnRemoveHero = document.getElementById('btnRemoveHero');
const heroImageInput = document.getElementById('heroImageInput');

// Project Fields
const projectTitle = document.getElementById('projectTitle');
const projectDescription = document.getElementById('projectDescription');
const projectCategory = document.getElementById('projectCategory');
const projectSlug = document.getElementById('projectSlug');

// Gallery
const btnAddImages = document.getElementById('btnAddImages');
const galleryImagesInput = document.getElementById('galleryImagesInput');
const projectGallery = document.getElementById('projectGallery');

// ===================================
// INITIALIZATION
// ===================================
document.addEventListener('DOMContentLoaded', () => {
    console.log('🚀 Edit Project: Initializing...');
    initSortable();
    attachEventListeners();
    loadProjectImages();
    initMasonryLayout(); // Initialize masonry layout for gallery

    // Warn before leaving with unsaved changes
    window.addEventListener('beforeunload', (e) => {
        if (hasUnsavedChanges) {
            e.preventDefault();
            e.returnValue = '';
        }
    });
});

// ===================================
// MASONRY LAYOUT
// ===================================
function initMasonryLayout() {
    const grid = document.getElementById('projectGallery');
    if (!grid) {
        console.log('⚠️ Gallery grid not found');
        return;
    }

    const items = grid.querySelectorAll('.work-item');
    if (items.length === 0) {
        console.log('⚠️ No items to layout');
        return;
    }

    console.log(`📐 Initializing masonry layout for ${items.length} items...`);

    function layoutMasonry() {
        const columns = getColumnCount();
        const gap = getGapSize();
        const containerWidth = grid.offsetWidth;
        const itemWidth = (containerWidth - (gap * (columns - 1))) / columns;

        console.log(`Masonry: ${columns} columns, ${gap}px gap, ${itemWidth}px item width`);

        // Set grid to relative positioning
        grid.style.position = 'relative';

        // Initialize column heights
        const columnHeights = new Array(columns).fill(0);

        // Position each item
        items.forEach((item, index) => {
            const shortestColumnIndex = columnHeights.indexOf(Math.min(...columnHeights));
            const x = shortestColumnIndex * (itemWidth + gap);
            const y = columnHeights[shortestColumnIndex];

            item.style.width = itemWidth + 'px';
            item.style.left = x + 'px';
            item.style.top = y + 'px';

            console.log(`Item ${index}: positioned at (${x}px, ${y}px)`);

            columnHeights[shortestColumnIndex] += item.offsetHeight + gap;
        });

        const maxHeight = Math.max(...columnHeights);
        grid.style.height = (maxHeight - gap) + 'px';

        console.log(`✅ Masonry layout complete! Grid height: ${maxHeight - gap}px`);
    }

    function getColumnCount() {
        const width = window.innerWidth;
        const adminColumns = parseInt(grid.dataset.columns) || 4;

        if (width <= 600) return 2;
        if (width <= 900) return 3;
        if (width <= 1200) return Math.min(adminColumns, 4);
        if (width <= 1400) return Math.min(adminColumns, 5);

        return adminColumns;
    }

    function getGapSize() {
        return parseInt(grid.dataset.gap) || 10;
    }

    // Execute layout after images load
    let imagesLoaded = 0;
    const totalImages = items.length;

    items.forEach(item => {
        const img = item.querySelector('img');
        if (img) {
            if (img.complete) {
                imagesLoaded++;
                if (imagesLoaded === totalImages) {
                    layoutMasonry();
                }
            } else {
                img.addEventListener('load', () => {
                    imagesLoaded++;
                    if (imagesLoaded === totalImages) {
                        layoutMasonry();
                    }
                });
            }
        }
    });

    // Recalculate on resize
    window.addEventListener('resize', debounce(layoutMasonry, 200));

    // Make layoutMasonry available globally for recalculation
    window.recalculateMasonry = layoutMasonry;
}

// Debounce helper
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

// ===================================
// SORTABLE - DRAG-AND-DROP REORDERING
// ===================================
let sortableInstance = null;

function initSortable() {
    const gallery = document.getElementById('projectGallery');
    if (!gallery) {
        console.log('⚠️ Gallery not found');
        return;
    }

    if (typeof Sortable === 'undefined') {
        console.error('❌ SortableJS library not loaded!');
        return;
    }

    console.log('🔧 Initializing Sortable...');

    sortableInstance = new Sortable(gallery, {
        animation: 150,
        handle: '.btn-drag-handle',
        ghostClass: 'sortable-ghost',
        chosenClass: 'sortable-chosen',
        dragClass: 'sortable-drag',
        swapThreshold: 0.65,

        onStart: (evt) => {
            console.log('🎯 Drag started');
            // Temporarily disable masonry absolute positioning
            const items = gallery.querySelectorAll('.work-item');
            items.forEach(item => {
                item.style.position = 'relative';
                item.style.left = 'auto';
                item.style.top = 'auto';
            });
            gallery.style.display = 'grid';
            gallery.style.gridTemplateColumns = `repeat(${getColumnCount()}, 1fr)`;
            gallery.style.gap = '20px';
        },

        onEnd: async (evt) => {
            console.log(`📦 Drag ended: ${evt.oldIndex} → ${evt.newIndex}`);

            // Restore masonry positioning
            const items = gallery.querySelectorAll('.work-item');
            items.forEach(item => {
                item.style.position = 'absolute';
            });
            gallery.style.display = '';
            gallery.style.gridTemplateColumns = '';
            gallery.style.gap = '';

            // Recalculate masonry with new DOM order
            if (typeof window.recalculateMasonry === 'function') {
                setTimeout(() => window.recalculateMasonry(), 100);
            }

            // Save new order
            await saveImageOrder();
        }
    });

    console.log('✅ Sortable initialized successfully');
}

// Helper function to get column count (same logic as masonry)
function getColumnCount() {
    const width = window.innerWidth;
    const gallery = document.getElementById('projectGallery');
    const adminColumns = gallery ? parseInt(gallery.dataset.columns) || 4 : 4;

    if (width <= 600) return 2;
    if (width <= 900) return 3;
    if (width <= 1200) return Math.min(adminColumns, 4);
    if (width <= 1400) return Math.min(adminColumns, 5);

    return adminColumns;
}

// Initialize sortable after DOM is ready
if (projectGallery) {
    initSortable();
}


// ===================================
// EVENT LISTENERS
// ===================================
function attachEventListeners() {
    // Save button
    if (btnSaveProject) {
        btnSaveProject.addEventListener('click', saveProject);
    }

    // Hero image upload
    if (btnUploadHero) {
        btnUploadHero.addEventListener('click', () => heroImageInput?.click());
    }

    if (heroImageContainer) {
        heroImageContainer.addEventListener('click', (e) => {
            if (e.target === heroImageContainer || e.target.closest('.hero-placeholder')) {
                heroImageInput?.click();
            }
        });
    }

    if (heroImageInput) {
        heroImageInput.addEventListener('change', handleHeroImageUpload);
    }

    // Hero image remove
    if (btnRemoveHero) {
        btnRemoveHero.addEventListener('click', (e) => {
            e.stopPropagation();
            removeHeroImage();
        });
    }

    // Gallery images upload
    if (btnAddImages) {
        btnAddImages.addEventListener('click', () => galleryImagesInput?.click());
    }

    if (galleryImagesInput) {
        galleryImagesInput.addEventListener('change', handleGalleryImagesUpload);
    }

    // Mark as unsaved on field changes
    [projectTitle, projectDescription, projectCategory].forEach(field => {
        if (field) {
            field.addEventListener('input', markAsUnsaved);
        }
    });

    // Gallery image controls (delegated)
    if (projectGallery) {
        projectGallery.addEventListener('click', (e) => {
            const setHeroBtn = e.target.closest('.btn-set-hero');
            const deleteBtn = e.target.closest('.btn-delete-image');

            if (setHeroBtn) {
                const imageId = setHeroBtn.dataset.imageId;
                setImageAsHero(imageId);
            }

            if (deleteBtn) {
                const imageId = deleteBtn.dataset.imageId;
                deleteImage(imageId);
            }
        });
    }
}

// ===================================
// LOAD PROJECT IMAGES
// ===================================
async function loadProjectImages() {
    try {
        const response = await fetch(`gallery_api?action=get_project&id=${currentProjectId}`);
        const data = await response.json();

        if (data.success && data.images) {
            projectImages = data.images;
        }
    } catch (error) {
        console.error('Error loading project images:', error);
    }
}

// ===================================
// HERO IMAGE UPLOAD
// ===================================
async function handleHeroImageUpload(e) {
    const file = e.target.files[0];
    if (!file) return;

    const formData = new FormData();
    formData.append('action', 'upload_project_hero');
    formData.append('project_id', currentProjectId);
    formData.append('hero_image', file);

    try {
        setStatus('saving');
        btnUploadHero.disabled = true;
        btnUploadHero.textContent = 'Enviando...';

        const response = await fetch('gallery_api', {
            method: 'POST',
            body: formData
        });

        const data = await response.json();

        if (data.success) {
            // Update hero image preview
            if (heroImage) {
                heroImage.src = data.hero_image_path + '?v=' + new Date().getTime();
            } else {
                // Create image if placeholder
                const placeholder = heroImageContainer.querySelector('.hero-placeholder');
                if (placeholder) {
                    placeholder.remove();
                }

                const img = document.createElement('img');
                img.id = 'heroImage';
                img.src = data.hero_image_path;
                img.alt = projectTitle.value;
                heroImageContainer.insertBefore(img, heroImageContainer.firstChild);

                // Add remove button if not exists
                if (!btnRemoveHero) {
                    const removeBtn = document.createElement('button');
                    removeBtn.className = 'btn-hero-action btn-remove';
                    removeBtn.id = 'btnRemoveHero';
                    removeBtn.innerHTML = `
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polyline points="3 6 5 6 21 6"/>
                            <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/>
                        </svg>
                        Remover
                    `;
                    removeBtn.addEventListener('click', (e) => {
                        e.stopPropagation();
                        removeHeroImage();
                    });
                    heroImageContainer.querySelector('.hero-overlay').appendChild(removeBtn);
                }
            }

            setStatus('saved');
            setTimeout(() => setStatus('unsaved'), 2000);
        } else {
            alert('Erro ao fazer upload: ' + (data.error || 'Desconhecido'));
            setStatus('unsaved');
        }
    } catch (error) {
        console.error('Error uploading hero:', error);
        alert('Erro ao fazer upload da imagem');
        setStatus('unsaved');
    } finally {
        btnUploadHero.disabled = false;
        btnUploadHero.innerHTML = `
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
                <polyline points="17 8 12 3 7 8"/>
                <line x1="12" y1="3" x2="12" y2="15"/>
            </svg>
            Trocar Imagem Hero
        `;
        heroImageInput.value = '';
    }
}

// ===================================
// REMOVE HERO IMAGE
// ===================================
async function removeHeroImage() {
    if (!confirm('Remover a imagem hero?')) return;

    try {
        const data = await apiRequest('update_project', {
            id: currentProjectId,
            title: projectTitle.value,
            description: projectDescription.value,
            category_id: projectCategory.value || null,
            hero_image_path: null
        });

        if (data.success) {
            // Replace image with placeholder
            if (heroImage) {
                heroImage.remove();
            }

            const placeholder = document.createElement('div');
            placeholder.className = 'hero-placeholder';
            placeholder.innerHTML = `
                <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                    <rect x="3" y="3" width="18" height="18" rx="2" ry="2"/>
                    <circle cx="8.5" cy="8.5" r="1.5"/>
                    <polyline points="21 15 16 10 5 21"/>
                </svg>
                <p>Clique para adicionar imagem hero</p>
            `;
            heroImageContainer.insertBefore(placeholder, heroImageContainer.firstChild);

            // Remove remove button
            if (btnRemoveHero) {
                btnRemoveHero.remove();
            }

            setStatus('saved');
            setTimeout(() => setStatus('unsaved'), 2000);
        } else {
            alert('Erro ao remover: ' + (data.error || 'Desconhecido'));
        }
    } catch (error) {
        console.error('Error removing hero:', error);
        alert('Erro ao remover imagem');
    }
}

// ===================================
// GALLERY IMAGES UPLOAD
// ===================================
async function handleGalleryImagesUpload(e) {
    const files = Array.from(e.target.files);
    if (files.length === 0) return;

    const formData = new FormData();
    formData.append('action', 'upload_project_images');
    formData.append('project_id', currentProjectId);

    files.forEach((file, index) => {
        formData.append('images[]', file);
    });

    try {
        setStatus('saving');
        btnAddImages.disabled = true;
        btnAddImages.textContent = 'Enviando...';

        const response = await fetch('gallery_api', {
            method: 'POST',
            body: formData
        });

        const data = await response.json();

        if (data.success && data.uploaded) {
            // Reload page to show new images
            window.location.reload();
        } else {
            alert('Erro ao fazer upload: ' + (data.error || 'Desconhecido'));
            setStatus('unsaved');
        }
    } catch (error) {
        console.error('Error uploading images:', error);
        alert('Erro ao fazer upload das imagens');
        setStatus('unsaved');
    } finally {
        btnAddImages.disabled = false;
        btnAddImages.innerHTML = `
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <line x1="12" y1="5" x2="12" y2="19"/>
                <line x1="5" y1="12" x2="19" y2="12"/>
            </svg>
            Adicionar Imagens
        `;
        galleryImagesInput.value = '';
    }
}

// ===================================
// SET IMAGE AS HERO
// ===================================
async function setImageAsHero(imageId) {
    try {
        const data = await apiRequest('set_project_image_hero', {
            image_id: imageId,
            project_id: currentProjectId
        });

        if (data.success) {
            // Reload to update hero and star states
            window.location.reload();
        } else {
            alert('Erro: ' + (data.error || 'Desconhecido'));
        }
    } catch (error) {
        console.error('Error setting hero:', error);
        alert('Erro ao definir imagem como hero');
    }
}

// ===================================
// DELETE IMAGE
// ===================================
async function deleteImage(imageId) {
    if (!confirm('Remover esta imagem do projeto?')) return;

    try {
        const data = await apiRequest('delete_project_image', {
            image_id: imageId
        });

        if (data.success) {
            // Remove from DOM
            const imageElement = document.querySelector(`.work-item[data-id="${imageId}"]`);
            if (imageElement) {
                imageElement.remove();
            }
            // Reload to update gallery
            window.location.reload();
        } else {
            alert('Erro: ' + (data.error || 'Desconhecido'));
        }
    } catch (error) {
        console.error('Error deleting image:', error);
        alert('Erro ao remover imagem');
    }
}

// ===================================
// SAVE IMAGE ORDER
// ===================================
async function saveImageOrder() {
    const gallery = document.getElementById('projectGallery');
    if (!gallery) return;

    // Collect image IDs in current DOM order
    const imageIds = Array.from(gallery.querySelectorAll('.work-item'))
        .map(item => item.getAttribute('data-id'))
        .filter(id => id && id !== 'null'); // Remove nulls and empty strings

    if (imageIds.length === 0) {
        console.log('⚠️ No images to reorder');
        return;
    }

    console.log('💾 Saving image order:', imageIds);

    try {
        const data = await apiRequest('reorder_project_images', {
            images: imageIds
        });

        if (data.success) {
            console.log('✅ Image order saved successfully!');
        } else {
            console.error('❌ Error saving order:', data.error);
            alert('Erro ao salvar ordem das imagens: ' + (data.error || 'Desconhecido'));
        }
    } catch (error) {
        console.error('❌ Error in saveImageOrder:', error);
        alert('Erro ao salvar ordem das imagens');
    }
}

// ===================================
// SAVE PROJECT
// ===================================
async function saveProject() {
    try {
        setStatus('saving');
        btnSaveProject.disabled = true;
        btnSaveProject.innerHTML = 'Salvando...';

        // Get image order
        const imageElements = projectGallery.querySelectorAll('.work-item');
        const imageOrder = Array.from(imageElements).map(el => el.dataset.id);

        // Save project data
        const projectData = await apiRequest('update_project', {
            id: currentProjectId,
            title: projectTitle.value,
            description: projectDescription.value,
            category_id: projectCategory.value || null
        });

        if (!projectData.success) {
            throw new Error(projectData.error || 'Erro ao salvar projeto');
        }

        // Save image order if there are images
        if (imageOrder.length > 0) {
            const orderData = await apiRequest('reorder_project_images', {
                images: imageOrder
            }, true); // Use FormData

            if (!orderData.success) {
                console.error('Error saving image order:', orderData.error);
            }
        }

        setStatus('saved');
        hasUnsavedChanges = false;

        // Redirect back to admin after 1 second
        setTimeout(() => {
            window.location.href = 'admin.php#projects';
        }, 1000);

    } catch (error) {
        console.error('Error saving project:', error);
        alert('Erro ao salvar: ' + error.message);
        setStatus('unsaved');

        // Restore button
        btnSaveProject.disabled = false;
        btnSaveProject.innerHTML = `
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/>
                <polyline points="17 21 17 13 7 13 7 21"/>
                <polyline points="7 3 7 8 15 8"/>
            </svg>
            Salvar Alterações
        `;
    }
}

// ===================================
// STATUS MANAGEMENT
// ===================================
function markAsUnsaved() {
    hasUnsavedChanges = true;
    setStatus('unsaved');
}

function setStatus(status) {
    if (!editStatus) return;

    editStatus.className = 'edit-status ' + status;

    switch (status) {
        case 'unsaved':
            editStatus.textContent = 'Não salvo';
            break;
        case 'saving':
            editStatus.textContent = 'Salvando...';
            break;
        case 'saved':
            editStatus.textContent = '✓ Salvo';
            break;
    }
}
