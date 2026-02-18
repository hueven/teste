/**
 * AdminState - Centralized state management for admin panel
 *
 * Manages all admin application state in a single object
 * instead of scattered global variables.
 *
 * Usage:
 * - AdminState.collections.add(collection)
 * - AdminState.collections.update(id, data)
 * - AdminState.on('change', callback)
 */

class AdminState {
    constructor() {
        /**
         * Application state
         * @type {Object}
         */
        this.state = {
            user: {
                id: null,
                email: null,
                authenticated: false
            },
            collections: [],
            currentCollection: null,
            currentCollectionImages: [],
            products: [],
            currentProduct: null,
            isLoading: false,
            error: null,
            csrfToken: null,
            menu: []
        };

        /**
         * Event listeners
         * @type {Object}
         */
        this.listeners = {};

        // Auto-save state to localStorage
        this.enableLocalStorage();
    }

    // ========================================================================
    // GETTERS
    // ========================================================================

    /**
     * Get entire state
     */
    getState() {
        return { ...this.state };
    }

    /**
     * Get user info
     */
    getUser() {
        return { ...this.state.user };
    }

    /**
     * Get all collections
     */
    getCollections() {
        return [...this.state.collections];
    }

    /**
     * Get collection by ID
     */
    getCollection(id) {
        return this.state.collections.find(c => c.id === id);
    }

    /**
     * Get current collection
     */
    getCurrentCollection() {
        return this.state.currentCollection ? { ...this.state.currentCollection } : null;
    }

    /**
     * Get all products
     */
    getProducts() {
        return [...this.state.products];
    }

    /**
     * Get product by ID
     */
    getProduct(id) {
        return this.state.products.find(p => p.id === id);
    }

    /**
     * Get CSRF token
     */
    getCsrfToken() {
        return this.state.csrfToken;
    }

    /**
     * Is loading
     */
    isLoading() {
        return this.state.isLoading;
    }

    /**
     * Get error
     */
    getError() {
        return this.state.error;
    }

    // ========================================================================
    // SETTERS
    // ========================================================================

    /**
     * Set user info
     */
    setUser(user) {
        this.state.user = { ...this.state.user, ...user };
        this.emit('user:change', this.state.user);
    }

    /**
     * Set authenticated status
     */
    setAuthenticated(authenticated) {
        this.state.user.authenticated = authenticated;
        this.emit('auth:change', authenticated);
    }

    /**
     * Set loading state
     */
    setLoading(isLoading) {
        this.state.isLoading = isLoading;
        this.emit('loading:change', isLoading);
    }

    /**
     * Set error message
     */
    setError(error) {
        this.state.error = error;
        this.emit('error:change', error);
    }

    /**
     * Clear error
     */
    clearError() {
        this.setError(null);
    }

    /**
     * Set CSRF token
     */
    setCsrfToken(token) {
        this.state.csrfToken = token;
    }

    // ========================================================================
    // COLLECTION OPERATIONS
    // ========================================================================

    /**
     * Add collection
     */
    addCollection(collection) {
        this.state.collections.push(collection);
        this.emit('collections:add', collection);
        this.save();
    }

    /**
     * Update collection
     */
    updateCollection(id, updates) {
        const index = this.state.collections.findIndex(c => c.id === id);
        if (index !== -1) {
            this.state.collections[index] = {
                ...this.state.collections[index],
                ...updates
            };
            this.emit('collection:update', this.state.collections[index]);
            this.save();
        }
    }

    /**
     * Delete collection
     */
    deleteCollection(id) {
        const index = this.state.collections.findIndex(c => c.id === id);
        if (index !== -1) {
            const deleted = this.state.collections.splice(index, 1);
            this.emit('collection:delete', deleted[0]);
            this.save();
        }
    }

    /**
     * Set collections list
     */
    setCollections(collections) {
        this.state.collections = [...collections];
        this.emit('collections:update', this.state.collections);
        this.save();
    }

    /**
     * Set current collection
     */
    setCurrentCollection(collection) {
        this.state.currentCollection = collection ? { ...collection } : null;
        this.emit('collection:current', this.state.currentCollection);
    }

    /**
     * Reorder collections
     */
    reorderCollections(order) {
        const newCollections = [];
        order.forEach(id => {
            const collection = this.getCollection(id);
            if (collection) {
                newCollections.push(collection);
            }
        });
        this.state.collections = newCollections;
        this.emit('collections:reorder', this.state.collections);
        this.save();
    }

    // ========================================================================
    // PRODUCT OPERATIONS
    // ========================================================================

    /**
     * Add product
     */
    addProduct(product) {
        this.state.products.push(product);
        this.emit('product:add', product);
        this.save();
    }

    /**
     * Update product
     */
    updateProduct(id, updates) {
        const index = this.state.products.findIndex(p => p.id === id);
        if (index !== -1) {
            this.state.products[index] = {
                ...this.state.products[index],
                ...updates
            };
            this.emit('product:update', this.state.products[index]);
            this.save();
        }
    }

    /**
     * Delete product
     */
    deleteProduct(id) {
        const index = this.state.products.findIndex(p => p.id === id);
        if (index !== -1) {
            const deleted = this.state.products.splice(index, 1);
            this.emit('product:delete', deleted[0]);
            this.save();
        }
    }

    /**
     * Set products list
     */
    setProducts(products) {
        this.state.products = [...products];
        this.emit('products:update', this.state.products);
        this.save();
    }

    /**
     * Set current product
     */
    setCurrentProduct(product) {
        this.state.currentProduct = product ? { ...product } : null;
        this.emit('product:current', this.state.currentProduct);
    }

    // ========================================================================
    // EVENT SYSTEM
    // ========================================================================

    /**
     * Register event listener
     */
    on(event, callback) {
        if (!this.listeners[event]) {
            this.listeners[event] = [];
        }
        this.listeners[event].push(callback);

        // Return unsubscribe function
        return () => {
            this.off(event, callback);
        };
    }

    /**
     * Remove event listener
     */
    off(event, callback) {
        if (!this.listeners[event]) return;

        const index = this.listeners[event].indexOf(callback);
        if (index !== -1) {
            this.listeners[event].splice(index, 1);
        }
    }

    /**
     * Emit event
     */
    emit(event, data) {
        if (!this.listeners[event]) return;

        this.listeners[event].forEach(callback => {
            try {
                callback(data);
            } catch (error) {
                console.error(`Error in ${event} listener:`, error);
            }
        });

        // Also emit a generic 'change' event
        if (event !== 'change') {
            this.emit('change', { event, data });
        }
    }

    // ========================================================================
    // LOCAL STORAGE PERSISTENCE
    // ========================================================================

    /**
     * Enable localStorage auto-save
     */
    enableLocalStorage() {
        // Load saved state
        this.restore();

        // Save on any change
        this.on('change', () => {
            this.save();
        });
    }

    /**
     * Save state to localStorage
     */
    save() {
        try {
            const toSave = {
                collections: this.state.collections,
                products: this.state.products,
                menu: this.state.menu
            };
            localStorage.setItem('admin_state', JSON.stringify(toSave));
        } catch (error) {
            console.warn('Failed to save state to localStorage:', error);
        }
    }

    /**
     * Restore state from localStorage
     */
    restore() {
        try {
            const saved = localStorage.getItem('admin_state');
            if (saved) {
                const data = JSON.parse(saved);
                if (data.collections) {
                    this.state.collections = data.collections;
                }
                if (data.products) {
                    this.state.products = data.products;
                }
                if (data.menu) {
                    this.state.menu = data.menu;
                }
            }
        } catch (error) {
            console.warn('Failed to restore state from localStorage:', error);
        }
    }

    /**
     * Clear all state
     */
    reset() {
        this.state = {
            user: { id: null, email: null, authenticated: false },
            collections: [],
            currentCollection: null,
            currentCollectionImages: [],
            products: [],
            currentProduct: null,
            isLoading: false,
            error: null,
            csrfToken: null,
            menu: []
        };
        localStorage.removeItem('admin_state');
        this.emit('reset', this.state);
    }
}

// Export for use in other modules
export default AdminState;
