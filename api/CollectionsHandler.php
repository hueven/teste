<?php
/**
 * CollectionsHandler - CRUD operations for collections
 *
 * Handles all operations related to collections management:
 * - List collections
 * - Create collection
 * - Update collection
 * - Delete collection
 * - Get collection details
 */

require_once __DIR__ . '/BaseHandler.php';
require_once __DIR__ . '/Validator.php';

class CollectionsHandler extends BaseHandler {

    /**
     * Handle collection operations
     */
    public function handle() {
        $action = $this->getParam('action', 'list');

        switch ($action) {
            case 'list':
                $this->listCollections();
                break;
            case 'get':
                $this->getCollection();
                break;
            case 'create':
                $this->validateCsrfToken();
                $this->createCollection();
                break;
            case 'update':
                $this->validateCsrfToken();
                $this->updateCollection();
                break;
            case 'delete':
                $this->validateCsrfToken();
                $this->deleteCollection();
                break;
            case 'reorder':
                $this->validateCsrfToken();
                $this->reorderCollections();
                break;
            default:
                $this->error("Unknown action: " . htmlspecialchars($action), 400);
        }
    }

    /**
     * List all collections
     */
    private function listCollections() {
        $page = $this->getIntParam('page', 1);
        $limit = $this->getIntParam('limit', 20);
        $search = $this->getStringParam('search', '');

        // Validate pagination
        if (!Validator::isValidInt($page, 1)) {
            $page = 1;
        }
        if (!Validator::isValidInt($limit, 1, 100)) {
            $limit = 20;
        }

        $offset = ($page - 1) * $limit;

        // Build query
        $where = '';
        $params = [];

        if ($search) {
            $where = 'WHERE name LIKE ?';
            $params[] = '%' . $search . '%';
        }

        // Get total count
        $countStmt = $this->query("SELECT COUNT(*) as count FROM collections $where", $params);
        $total = $countStmt->fetch()['count'] ?? 0;

        // Get collections
        $query = "SELECT id, name, slug, description, image_url, position, created_at
                  FROM collections
                  $where
                  ORDER BY position ASC, created_at DESC
                  LIMIT ? OFFSET ?";

        $params[] = $limit;
        $params[] = $offset;

        $collections = $this->fetchAll($query, $params);

        $this->success([
            'collections' => $collections,
            'pagination' => [
                'page' => $page,
                'limit' => $limit,
                'total' => $total,
                'pages' => ceil($total / $limit)
            ]
        ]);
    }

    /**
     * Get single collection
     */
    private function getCollection() {
        $id = $this->getRequiredParam('id', 'Collection ID is required');

        if (!Validator::isValidInt($id)) {
            $this->error('Invalid collection ID', 400);
        }

        $collection = $this->fetchOne(
            'SELECT * FROM collections WHERE id = ?',
            [$id]
        );

        if (!$collection) {
            $this->error('Collection not found', 404);
        }

        $this->success($collection);
    }

    /**
     * Create new collection
     */
    private function createCollection() {
        $name = $this->getRequiredParam('name', 'Collection name is required');
        $description = $this->getStringParam('description', '');
        $imageUrl = $this->getStringParam('image_url', '');

        // Validate
        if (!Validator::isValidStringLength($name, 1, 255)) {
            $this->error('Collection name must be between 1 and 255 characters', 400);
        }

        if ($description && !Validator::isValidStringLength($description, 1, 1000)) {
            $this->error('Description must be between 1 and 1000 characters', 400);
        }

        if ($imageUrl && !Validator::isValidUrl($imageUrl)) {
            $this->error('Invalid image URL', 400);
        }

        // Sanitize
        $name = $this->sanitize($name);
        $description = $this->sanitize($description);
        $slug = sanitizeCollectionName($name);

        // Check if slug already exists
        $existing = $this->fetchOne(
            'SELECT id FROM collections WHERE slug = ?',
            [$slug]
        );

        if ($existing) {
            $this->error('Collection name already exists', 409);
        }

        // Get next position
        $lastPosition = $this->fetchOne(
            'SELECT MAX(position) as max_pos FROM collections'
        );
        $position = ($lastPosition['max_pos'] ?? 0) + 1;

        // Create collection
        try {
            $stmt = $this->query(
                'INSERT INTO collections (name, slug, description, image_url, position, user_id, created_at)
                 VALUES (?, ?, ?, ?, ?, ?, NOW())',
                [$name, $slug, $description, $imageUrl, $position, $this->getUserId()]
            );

            $collectionId = $this->lastInsertId();

            $this->success([
                'id' => $collectionId,
                'message' => 'Collection created successfully'
            ], 201);
        } catch (Exception $e) {
            $this->error('Failed to create collection', 500);
        }
    }

    /**
     * Update collection
     */
    private function updateCollection() {
        $id = $this->getRequiredParam('id', 'Collection ID is required');
        $name = $this->getStringParam('name');
        $description = $this->getStringParam('description');
        $imageUrl = $this->getStringParam('image_url');

        if (!Validator::isValidInt($id)) {
            $this->error('Invalid collection ID', 400);
        }

        // Verify collection exists and belongs to user
        $collection = $this->fetchOne(
            'SELECT id, user_id FROM collections WHERE id = ?',
            [$id]
        );

        if (!$collection) {
            $this->error('Collection not found', 404);
        }

        if ($collection['user_id'] != $this->getUserId()) {
            $this->error('Unauthorized', 403);
        }

        // Validate update fields
        if ($name !== null) {
            if (!Validator::isValidStringLength($name, 1, 255)) {
                $this->error('Collection name must be between 1 and 255 characters', 400);
            }
            $name = $this->sanitize($name);
        }

        if ($description !== null) {
            if (!Validator::isValidStringLength($description, 0, 1000)) {
                $this->error('Description must be at most 1000 characters', 400);
            }
            $description = $this->sanitize($description);
        }

        if ($imageUrl !== null && !Validator::isValidUrl($imageUrl)) {
            $this->error('Invalid image URL', 400);
        }

        // Build update query
        $updates = [];
        $params = [];

        if ($name !== null) {
            $updates[] = 'name = ?';
            $params[] = $name;
        }
        if ($description !== null) {
            $updates[] = 'description = ?';
            $params[] = $description;
        }
        if ($imageUrl !== null) {
            $updates[] = 'image_url = ?';
            $params[] = $imageUrl;
        }

        if (empty($updates)) {
            $this->error('No fields to update', 400);
        }

        $updates[] = 'updated_at = NOW()';
        $params[] = $id;

        $query = 'UPDATE collections SET ' . implode(', ', $updates) . ' WHERE id = ?';
        $this->query($query, $params);

        $this->success(['message' => 'Collection updated successfully']);
    }

    /**
     * Delete collection
     */
    private function deleteCollection() {
        $id = $this->getRequiredParam('id', 'Collection ID is required');

        if (!Validator::isValidInt($id)) {
            $this->error('Invalid collection ID', 400);
        }

        // Verify collection exists and belongs to user
        $collection = $this->fetchOne(
            'SELECT id, user_id FROM collections WHERE id = ?',
            [$id]
        );

        if (!$collection) {
            $this->error('Collection not found', 404);
        }

        if ($collection['user_id'] != $this->getUserId()) {
            $this->error('Unauthorized', 403);
        }

        // Delete related items first
        $this->query('DELETE FROM collection_items WHERE collection_id = ?', [$id]);

        // Delete collection
        $this->query('DELETE FROM collections WHERE id = ?', [$id]);

        $this->success(['message' => 'Collection deleted successfully']);
    }

    /**
     * Reorder collections
     */
    private function reorderCollections() {
        $order = $_POST['order'] ?? null;

        if (!$order || !is_array($order)) {
            $this->error('Order array is required', 400);
        }

        try {
            // Validate all IDs
            foreach ($order as $index => $id) {
                if (!Validator::isValidInt($id)) {
                    $this->error('Invalid collection ID at position ' . ($index + 1), 400);
                }
            }

            // Update positions
            foreach ($order as $position => $id) {
                $this->query(
                    'UPDATE collections SET position = ? WHERE id = ? AND user_id = ?',
                    [$position + 1, $id, $this->getUserId()]
                );
            }

            $this->success(['message' => 'Collections reordered successfully']);
        } catch (Exception $e) {
            $this->error('Failed to reorder collections', 500);
        }
    }
}
