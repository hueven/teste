<?php
/**
 * Router - Central API request handler
 *
 * Routes all API requests to appropriate handler classes.
 * Usage: index.php routes all /api/* requests to this file
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/BaseHandler.php';
require_once __DIR__ . '/CollectionsHandler.php';
require_once __DIR__ . '/AuthenticationHandler.php';

class Router {

    /**
     * @var PDO
     */
    private $pdo;

    /**
     * @var array Registered handlers
     */
    private $handlers = [
        'auth' => 'AuthenticationHandler',
        'collections' => 'CollectionsHandler',
        // Add more handlers here as you create them
        // 'products' => 'ProductsHandler',
        // 'gallery' => 'GalleryHandler',
    ];

    /**
     * Constructor
     *
     * @param PDO $pdo
     */
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    /**
     * Route and execute request
     */
    public function handle() {
        try {
            // Get resource and action from request
            $resource = $this->getResource();
            $action = $_GET['action'] ?? $_POST['action'] ?? 'default';

            // Validate resource
            if (!$resource) {
                $this->error('No resource specified', 400);
            }

            // Get handler class
            if (!isset($this->handlers[$resource])) {
                $this->error("Resource not found: " . htmlspecialchars($resource), 404);
            }

            $handlerClass = $this->handlers[$resource];

            // Instantiate and execute handler
            $handler = new $handlerClass($this->pdo);
            $handler->handle();

        } catch (Exception $e) {
            $this->error("Internal server error", 500);
        }
    }

    /**
     * Get resource from request
     *
     * @return string|null
     */
    private function getResource() {
        // Extract resource from URL path
        // Examples:
        // /api/auth -> auth
        // /api/collections -> collections
        // /api/products/upload -> products

        $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        $path = str_replace('/api/', '', $path);
        $parts = explode('/', trim($path, '/'));

        return $parts[0] ?? null;
    }

    /**
     * Send error response
     *
     * @param string $message
     * @param int $statusCode
     */
    private function error($message, $statusCode = 400) {
        jsonResponse(false, ['error' => $message], $statusCode);
    }

    /**
     * Register custom handler
     *
     * @param string $resource
     * @param string $handlerClass
     */
    public function registerHandler($resource, $handlerClass) {
        $this->handlers[$resource] = $handlerClass;
    }

    /**
     * Get registered handlers
     *
     * @return array
     */
    public function getHandlers() {
        return array_keys($this->handlers);
    }
}

// ============================================================================
// ENTRY POINT
// ============================================================================

// Initialize router and handle request
$router = new Router($pdo);
$router->handle();
