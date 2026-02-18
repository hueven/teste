<?php
/**
 * BaseHandler - Base class for all API handlers
 *
 * Provides common functionality for all API handlers including:
 * - Authentication and session management
 * - Input validation and sanitization
 * - Error handling and response formatting
 * - CSRF protection
 */

require_once __DIR__ . '/../config.php';

abstract class BaseHandler {
    /**
     * @var PDO
     */
    protected $pdo;

    /**
     * @var bool Whether authentication is required
     */
    protected $requiresAuth = true;

    /**
     * @var array Request parameters
     */
    protected $params = [];

    /**
     * Constructor
     *
     * @param PDO $pdo
     */
    public function __construct($pdo) {
        $this->pdo = $pdo;
        $this->initialize();
    }

    /**
     * Initialize handler
     * Checks authentication and loads parameters
     */
    protected function initialize() {
        initSession();

        // Check authentication if required
        if ($this->requiresAuth) {
            $this->requireAuthentication();
        }

        // Load request parameters
        $this->loadParams();
    }

    /**
     * Require authentication
     */
    protected function requireAuthentication() {
        if (!isAuthenticated()) {
            $this->error('Unauthorized', 401);
        }
    }

    /**
     * Load parameters from request
     */
    protected function loadParams() {
        $this->params = array_merge($_GET, $_POST);
    }

    /**
     * Get parameter value
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    protected function getParam($key, $default = null) {
        return $this->params[$key] ?? $default;
    }

    /**
     * Get required parameter
     *
     * @param string $key
     * @param string $errorMessage
     * @return mixed
     */
    protected function getRequiredParam($key, $errorMessage = null) {
        if (!isset($this->params[$key]) || $this->params[$key] === '') {
            $errorMessage = $errorMessage ?? "Parameter '{$key}' is required";
            $this->error($errorMessage, 400);
        }

        return $this->params[$key];
    }

    /**
     * Get integer parameter
     *
     * @param string $key
     * @param int|null $default
     * @return int|null
     */
    protected function getIntParam($key, $default = null) {
        $value = $this->getParam($key, $default);
        return $value !== null ? (int)$value : $default;
    }

    /**
     * Get string parameter
     *
     * @param string $key
     * @param string|null $default
     * @return string|null
     */
    protected function getStringParam($key, $default = null) {
        $value = $this->getParam($key, $default);
        return $value !== null ? (string)$value : $default;
    }

    /**
     * Validate CSRF token
     */
    protected function validateCsrfToken() {
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            if (!verifyCsrfToken()) {
                $this->error('Invalid CSRF token', 403);
            }
        }
    }

    /**
     * Execute query safely
     *
     * @param string $query
     * @param array $params
     * @return PDOStatement|null
     */
    protected function query($query, $params = []) {
        try {
            $stmt = $this->pdo->prepare($query);
            $stmt->execute($params);
            return $stmt;
        } catch (PDOException $e) {
            if (APP_DEBUG) {
                $this->error("Database error: " . $e->getMessage(), 500);
            } else {
                $this->error("Database error occurred", 500);
            }
        }
    }

    /**
     * Fetch all rows
     *
     * @param string $query
     * @param array $params
     * @return array
     */
    protected function fetchAll($query, $params = []) {
        $stmt = $this->query($query, $params);
        return $stmt ? $stmt->fetchAll() : [];
    }

    /**
     * Fetch one row
     *
     * @param string $query
     * @param array $params
     * @return array|null
     */
    protected function fetchOne($query, $params = []) {
        $stmt = $this->query($query, $params);
        return $stmt ? $stmt->fetch() : null;
    }

    /**
     * Get last insert ID
     *
     * @return int
     */
    protected function lastInsertId() {
        return (int)$this->pdo->lastInsertId();
    }

    /**
     * Send success response
     *
     * @param mixed $data
     * @param int $statusCode
     */
    protected function success($data = [], $statusCode = 200) {
        jsonResponse(true, $data, $statusCode);
    }

    /**
     * Send error response
     *
     * @param string $message
     * @param int $statusCode
     */
    protected function error($message = 'An error occurred', $statusCode = 400) {
        jsonResponse(false, ['error' => $message], $statusCode);
    }

    /**
     * Get current user ID
     *
     * @return int
     */
    protected function getUserId() {
        return getCurrentUserId() ?? 0;
    }

    /**
     * Sanitize input string
     *
     * @param string $input
     * @return string
     */
    protected function sanitize($input) {
        return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
    }

    /**
     * Handle the request
     * Subclasses should implement this method
     */
    abstract public function handle();
}
