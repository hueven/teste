/**
 * AdminAPI - API client for admin panel
 *
 * Provides methods for communicating with backend API
 * Integrates with AdminState for state management
 */

class AdminAPI {
    constructor(state, baseUrl = '/api') {
        this.state = state;
        this.baseUrl = baseUrl;
    }

    // ========================================================================
    // AUTHENTICATION
    // ========================================================================

    /**
     * Login
     */
    async login(email, password) {
        this.state.setLoading(true);
        this.state.clearError();

        try {
            const response = await this.post('/auth', {
                action: 'login',
                email,
                password,
                csrf_token: this.state.getCsrfToken()
            });

            if (response.success) {
                this.state.setUser({
                    id: response.data.user_id,
                    email: response.data.email,
                    authenticated: true
                });
                this.state.setAuthenticated(true);
                return true;
            } else {
                this.state.setError(response.data.error || 'Login failed');
                return false;
            }
        } catch (error) {
            this.state.setError(error.message);
            return false;
        } finally {
            this.state.setLoading(false);
        }
    }

    /**
     * Logout
     */
    async logout() {
        this.state.setLoading(true);

        try {
            await this.post('/auth', { action: 'logout' });
            this.state.reset();
            this.state.setAuthenticated(false);
            return true;
        } catch (error) {
            console.error('Logout error:', error);
            return false;
        } finally {
            this.state.setLoading(false);
        }
    }

    /**
     * Check authentication status
     */
    async checkAuth() {
        try {
            const response = await this.post('/auth', { action: 'check' });
            if (response.data.authenticated) {
                this.state.setAuthenticated(true);
                return true;
            } else {
                this.state.setAuthenticated(false);
                return false;
            }
        } catch (error) {
            this.state.setAuthenticated(false);
            return false;
        }
    }

    // ========================================================================
    // COLLECTIONS
    // ========================================================================

    /**
     * Get all collections
     */
    async fetchCollections(page = 1, limit = 20) {
        this.state.setLoading(true);
        this.state.clearError();

        try {
            const response = await this.get('/collections', {
                action: 'list',
                page,
                limit
            });

            if (response.success) {
                this.state.setCollections(response.data.collections);
                return response.data;
            } else {
                this.state.setError(response.data.error || 'Failed to fetch collections');
                return null;
            }
        } catch (error) {
            this.state.setError(error.message);
            return null;
        } finally {
            this.state.setLoading(false);
        }
    }

    /**
     * Create collection
     */
    async createCollection(name, description = '', imageUrl = '') {
        this.state.setLoading(true);
        this.state.clearError();

        try {
            const response = await this.post('/collections', {
                action: 'create',
                name,
                description,
                image_url: imageUrl,
                csrf_token: this.state.getCsrfToken()
            });

            if (response.success) {
                // Refresh collections list
                await this.fetchCollections();
                return response.data;
            } else {
                this.state.setError(response.data.error || 'Failed to create collection');
                return null;
            }
        } catch (error) {
            this.state.setError(error.message);
            return null;
        } finally {
            this.state.setLoading(false);
        }
    }

    /**
     * Update collection
     */
    async updateCollection(id, updates) {
        this.state.setLoading(true);
        this.state.clearError();

        try {
            const response = await this.post('/collections', {
                action: 'update',
                id,
                csrf_token: this.state.getCsrfToken(),
                ...updates
            });

            if (response.success) {
                this.state.updateCollection(id, updates);
                return true;
            } else {
                this.state.setError(response.data.error || 'Failed to update collection');
                return false;
            }
        } catch (error) {
            this.state.setError(error.message);
            return false;
        } finally {
            this.state.setLoading(false);
        }
    }

    /**
     * Delete collection
     */
    async deleteCollection(id) {
        this.state.setLoading(true);
        this.state.clearError();

        try {
            const response = await this.post('/collections', {
                action: 'delete',
                id,
                csrf_token: this.state.getCsrfToken()
            });

            if (response.success) {
                this.state.deleteCollection(id);
                return true;
            } else {
                this.state.setError(response.data.error || 'Failed to delete collection');
                return false;
            }
        } catch (error) {
            this.state.setError(error.message);
            return false;
        } finally {
            this.state.setLoading(false);
        }
    }

    /**
     * Reorder collections
     */
    async reorderCollections(order) {
        this.state.setLoading(true);
        this.state.clearError();

        try {
            const response = await this.post('/collections', {
                action: 'reorder',
                order,
                csrf_token: this.state.getCsrfToken()
            });

            if (response.success) {
                this.state.reorderCollections(order);
                return true;
            } else {
                this.state.setError(response.data.error || 'Failed to reorder collections');
                return false;
            }
        } catch (error) {
            this.state.setError(error.message);
            return false;
        } finally {
            this.state.setLoading(false);
        }
    }

    // ========================================================================
    // HTTP METHODS
    // ========================================================================

    /**
     * GET request
     */
    async get(endpoint, params = {}) {
        const url = new URL(this.baseUrl + endpoint, window.location.origin);
        Object.keys(params).forEach(key => {
            url.searchParams.append(key, params[key]);
        });

        const response = await fetch(url, {
            method: 'GET',
            credentials: 'include',
            headers: {
                'Accept': 'application/json'
            }
        });

        return this.handleResponse(response);
    }

    /**
     * POST request
     */
    async post(endpoint, data = {}) {
        const response = await fetch(this.baseUrl + endpoint, {
            method: 'POST',
            credentials: 'include',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
                'Accept': 'application/json'
            },
            body: new URLSearchParams(data)
        });

        return this.handleResponse(response);
    }

    /**
     * POST with FormData (for file uploads)
     */
    async postFormData(endpoint, formData) {
        const response = await fetch(this.baseUrl + endpoint, {
            method: 'POST',
            credentials: 'include',
            body: formData
        });

        return this.handleResponse(response);
    }

    /**
     * Handle response
     */
    async handleResponse(response) {
        const contentType = response.headers.get('content-type');

        if (!contentType || !contentType.includes('application/json')) {
            throw new Error('Invalid response format');
        }

        const data = await response.json();

        if (!response.ok) {
            throw new Error(data.data?.error || 'Request failed');
        }

        return data;
    }
}

// Export for use in other modules
export default AdminAPI;
