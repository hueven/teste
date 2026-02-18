<?php
/**
 * admin.php - MODERNIZED VERSION
 *
 * Refactored admin panel using:
 * - New config.php with environment variables
 * - CSRF token protection
 * - Secure session handling
 * - Cleaner HTML with CSRF token
 */

require_once __DIR__ . '/config.php';

// Require authentication
requireAuth();

// Generate CSRF token for forms
$csrfToken = generateCsrfToken();
$user = [
    'id' => getCurrentUserId(),
    'email' => $_SESSION['user_email'] ?? 'Unknown'
];
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Wide Studio</title>
    <link rel="stylesheet" href="/css/admin.css">
    <link rel="stylesheet" href="/css/collections.css">
</head>
<body class="admin-panel">
    <!-- Navigation -->
    <nav class="admin-navbar">
        <div class="navbar-brand">
            <h1>Wide Studio Admin</h1>
        </div>
        <div class="navbar-menu">
            <ul>
                <li><a href="#/collections">Collections</a></li>
                <li><a href="#/products">Products</a></li>
                <li><a href="#/settings">Settings</a></li>
                <li><a href="#/menu">Menu</a></li>
            </ul>
        </div>
        <div class="navbar-user">
            <span><?php echo htmlspecialchars($user['email']); ?></span>
            <button id="btnLogout" class="btn btn-logout">Logout</button>
        </div>
    </nav>

    <!-- Main Content -->
    <main class="admin-container">
        <!-- Loading Indicator -->
        <div id="loadingIndicator" class="loading-indicator hidden">
            <div class="spinner"></div>
            <p>Loading...</p>
        </div>

        <!-- Error Alert -->
        <div id="errorAlert" class="alert alert-error hidden">
            <span id="errorMessage"></span>
            <button class="alert-close">×</button>
        </div>

        <!-- Success Alert -->
        <div id="successAlert" class="alert alert-success hidden">
            <span id="successMessage"></span>
            <button class="alert-close">×</button>
        </div>

        <!-- Collections Section -->
        <section id="collectionsSection" class="section">
            <div class="section-header">
                <h2>Collections</h2>
                <button id="btnNewCollection" class="btn btn-primary">+ New Collection</button>
            </div>

            <!-- Collections List -->
            <div id="collectionsList" class="collections-list">
                <p class="placeholder">Loading collections...</p>
            </div>

            <!-- Create/Edit Collection Modal -->
            <div id="collectionModal" class="modal hidden">
                <div class="modal-content">
                    <div class="modal-header">
                        <h3 id="collectionModalTitle">Create Collection</h3>
                        <button class="modal-close">×</button>
                    </div>
                    <form id="collectionForm" class="modal-form">
                        <!-- CSRF Token (IMPORTANT!) -->
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                        <input type="hidden" name="action" value="create">
                        <input type="hidden" name="id" value="">

                        <div class="form-group">
                            <label for="collectionName">Collection Name *</label>
                            <input type="text" id="collectionName" name="name" required>
                            <small class="error-text" id="nameError"></small>
                        </div>

                        <div class="form-group">
                            <label for="collectionDesc">Description</label>
                            <textarea id="collectionDesc" name="description" rows="4"></textarea>
                            <small class="error-text" id="descError"></small>
                        </div>

                        <div class="form-group">
                            <label for="collectionImage">Image URL</label>
                            <input type="url" id="collectionImage" name="image_url">
                            <small class="error-text" id="imageError"></small>
                        </div>

                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary modal-close">Cancel</button>
                            <button type="submit" class="btn btn-primary">Save Collection</button>
                        </div>
                    </form>
                </div>
            </div>
        </section>
    </main>

    <!-- SCRIPTS -->
    <!-- Import modern ES6 modules for state management and API -->
    <script type="module">
        import AdminState from '/js/AdminState.js';
        import AdminAPI from '/js/AdminAPI.js';

        // Initialize state management and API
        const state = new AdminState();
        const api = new AdminAPI(state, '/api');

        // Set CSRF token from hidden input
        state.setCsrfToken(document.querySelector('input[name="csrf_token"]').value);

        // ====================================================================
        // UI ELEMENTS
        // ====================================================================

        const ui = {
            loading: document.getElementById('loadingIndicator'),
            errorAlert: document.getElementById('errorAlert'),
            errorMessage: document.getElementById('errorMessage'),
            successAlert: document.getElementById('successAlert'),
            successMessage: document.getElementById('successMessage'),
            collectionsList: document.getElementById('collectionsList'),
            collectionModal: document.getElementById('collectionModal'),
            collectionForm: document.getElementById('collectionForm'),
            btnNewCollection: document.getElementById('btnNewCollection'),
            btnLogout: document.getElementById('btnLogout'),
        };

        // ====================================================================
        // STATE LISTENERS
        // ====================================================================

        // Listen for loading changes
        state.on('loading:change', (isLoading) => {
            ui.loading.classList.toggle('hidden', !isLoading);
        });

        // Listen for errors
        state.on('error:change', (error) => {
            if (error) {
                ui.errorMessage.textContent = error;
                ui.errorAlert.classList.remove('hidden');
                setTimeout(() => {
                    ui.errorAlert.classList.add('hidden');
                }, 5000);
            }
        });

        // Listen for collections updates
        state.on('collections:update', () => {
            renderCollections();
        });

        // Listen for collection add
        state.on('collections:add', () => {
            renderCollections();
            showSuccess('Collection created successfully!');
            closeModal();
        });

        // Listen for collection update
        state.on('collection:update', () => {
            renderCollections();
            showSuccess('Collection updated successfully!');
            closeModal();
        });

        // Listen for collection delete
        state.on('collection:delete', () => {
            renderCollections();
            showSuccess('Collection deleted successfully!');
        });

        // ====================================================================
        // EVENT LISTENERS
        // ====================================================================

        // New collection button
        ui.btnNewCollection.addEventListener('click', () => {
            ui.collectionForm.reset();
            ui.collectionForm.querySelector('input[name="action"]').value = 'create';
            ui.collectionForm.querySelector('input[name="id"]').value = '';
            document.getElementById('collectionModalTitle').textContent = 'Create Collection';
            openModal();
        });

        // Form submission
        ui.collectionForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const action = ui.collectionForm.querySelector('input[name="action"]').value;
            const id = ui.collectionForm.querySelector('input[name="id"]').value;
            const name = document.getElementById('collectionName').value;
            const description = document.getElementById('collectionDesc').value;
            const imageUrl = document.getElementById('collectionImage').value;

            if (action === 'create') {
                await api.createCollection(name, description, imageUrl);
            } else if (action === 'update') {
                await api.updateCollection(parseInt(id), { name, description, image_url: imageUrl });
            }
        });

        // Logout button
        ui.btnLogout.addEventListener('click', async () => {
            if (confirm('Are you sure you want to logout?')) {
                await api.logout();
                window.location.href = '/login.php';
            }
        });

        // Close modal buttons
        document.querySelectorAll('.modal-close').forEach(btn => {
            btn.addEventListener('click', closeModal);
        });

        // Alert close buttons
        document.querySelectorAll('.alert-close').forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.target.closest('.alert').classList.add('hidden');
            });
        });

        // ====================================================================
        // RENDERING FUNCTIONS
        // ====================================================================

        function renderCollections() {
            const collections = state.getCollections();

            if (collections.length === 0) {
                ui.collectionsList.innerHTML = '<p class="placeholder">No collections yet. Create one to get started!</p>';
                return;
            }

            ui.collectionsList.innerHTML = collections.map(collection => `
                <div class="collection-card" data-id="${collection.id}">
                    <div class="collection-image">
                        ${collection.image_url ? `<img src="${htmlEscape(collection.image_url)}" alt="">` : '<div class="placeholder-image">No Image</div>'}
                    </div>
                    <div class="collection-info">
                        <h3>${htmlEscape(collection.name)}</h3>
                        <p>${htmlEscape(collection.description || 'No description')}</p>
                        <small>Created: ${new Date(collection.created_at).toLocaleDateString()}</small>
                    </div>
                    <div class="collection-actions">
                        <button class="btn btn-sm btn-primary" data-action="edit" data-id="${collection.id}">Edit</button>
                        <button class="btn btn-sm btn-secondary" data-action="view" data-id="${collection.id}">View Images</button>
                        <button class="btn btn-sm btn-danger" data-action="delete" data-id="${collection.id}">Delete</button>
                    </div>
                </div>
            `).join('');

            // Add event listeners to action buttons
            ui.collectionsList.querySelectorAll('[data-action]').forEach(btn => {
                btn.addEventListener('click', handleCollectionAction);
            });
        }

        async function handleCollectionAction(e) {
            const action = e.target.dataset.action;
            const id = parseInt(e.target.dataset.id);
            const collection = state.getCollection(id);

            if (!collection) return;

            if (action === 'edit') {
                document.getElementById('collectionName').value = collection.name;
                document.getElementById('collectionDesc').value = collection.description || '';
                document.getElementById('collectionImage').value = collection.image_url || '';
                ui.collectionForm.querySelector('input[name="action"]').value = 'update';
                ui.collectionForm.querySelector('input[name="id"]').value = id;
                document.getElementById('collectionModalTitle').textContent = 'Edit Collection';
                openModal();
            } else if (action === 'delete') {
                if (confirm(`Delete collection "${collection.name}"? This cannot be undone.`)) {
                    await api.deleteCollection(id);
                }
            }
        }

        // ====================================================================
        // UTILITY FUNCTIONS
        // ====================================================================

        function openModal() {
            ui.collectionModal.classList.remove('hidden');
            document.body.style.overflow = 'hidden';
        }

        function closeModal() {
            ui.collectionModal.classList.add('hidden');
            document.body.style.overflow = '';
        }

        function showSuccess(message) {
            ui.successMessage.textContent = message;
            ui.successAlert.classList.remove('hidden');
            setTimeout(() => {
                ui.successAlert.classList.add('hidden');
            }, 4000);
        }

        function htmlEscape(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        // ====================================================================
        // INITIALIZATION
        // ====================================================================

        async function init() {
            // Check authentication
            const isAuth = await api.checkAuth();
            if (!isAuth) {
                window.location.href = '/login.php';
                return;
            }

            // Fetch collections
            await api.fetchCollections();
        }

        // Start
        init();
    </script>

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #f5f5f5;
            color: #333;
        }

        /* Navbar */
        .admin-navbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: #fff;
            border-bottom: 1px solid #ddd;
            padding: 1rem 2rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .navbar-menu ul {
            display: flex;
            list-style: none;
            gap: 2rem;
        }

        .navbar-menu a {
            text-decoration: none;
            color: #666;
            transition: color 0.2s;
        }

        .navbar-menu a:hover {
            color: #000;
        }

        /* Container */
        .admin-container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 1rem;
        }

        /* Section */
        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }

        /* Buttons */
        .btn {
            padding: 0.5rem 1rem;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 1rem;
            transition: all 0.2s;
        }

        .btn-primary {
            background: #007bff;
            color: white;
        }

        .btn-primary:hover {
            background: #0056b3;
        }

        .btn-danger {
            background: #dc3545;
            color: white;
        }

        .btn-danger:hover {
            background: #c82333;
        }

        .btn-logout {
            background: #6c757d;
            color: white;
        }

        /* Collections */
        .collections-list {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 1.5rem;
        }

        .collection-card {
            background: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            transition: transform 0.2s;
        }

        .collection-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }

        .collection-image {
            width: 100%;
            height: 200px;
            background: #f0f0f0;
            overflow: hidden;
        }

        .collection-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .collection-info {
            padding: 1rem;
        }

        .collection-actions {
            padding: 1rem;
            display: flex;
            gap: 0.5rem;
            border-top: 1px solid #eee;
        }

        /* Modal */
        .modal {
            display: flex;
            align-items: center;
            justify-content: center;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.5);
            z-index: 1000;
        }

        .modal.hidden {
            display: none;
        }

        .modal-content {
            background: white;
            border-radius: 8px;
            width: 90%;
            max-width: 500px;
            box-shadow: 0 4px 16px rgba(0,0,0,0.2);
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1.5rem;
            border-bottom: 1px solid #eee;
        }

        .modal-form {
            padding: 1.5rem;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
        }

        .form-group input,
        .form-group textarea {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-family: inherit;
        }

        /* Alerts */
        .alert {
            padding: 1rem;
            border-radius: 4px;
            margin-bottom: 1rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .alert.hidden {
            display: none;
        }

        /* Loading */
        .loading-indicator {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            text-align: center;
            z-index: 2000;
        }

        .spinner {
            width: 40px;
            height: 40px;
            border: 4px solid #f3f3f3;
            border-top: 4px solid #007bff;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        .placeholder {
            text-align: center;
            padding: 2rem;
            color: #999;
        }
    </style>
</body>
</html>
