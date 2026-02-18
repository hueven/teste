<?php
/**
 * RateLimiter - Prevent brute force attacks
 *
 * Implements rate limiting to protect endpoints from brute force attacks.
 * Uses file-based or database-based storage for tracking attempts.
 */

require_once __DIR__ . '/../config.php';

class RateLimiter {

    /**
     * @var string Key for identifying the client
     */
    private $key;

    /**
     * @var int Maximum attempts allowed
     */
    private $maxAttempts;

    /**
     * @var int Time window in seconds
     */
    private $windowSeconds;

    /**
     * @var string Storage type: 'file' or 'database'
     */
    private $storageType;

    /**
     * @var PDO Database connection (for database storage)
     */
    private $pdo;

    /**
     * Constructor
     *
     * @param string $key Identifier for the rate limit (e.g., IP or user email)
     * @param int $maxAttempts Maximum attempts allowed
     * @param int $windowSeconds Time window in seconds
     * @param string $storageType 'file' or 'database'
     * @param PDO|null $pdo Database connection (required if using database storage)
     */
    public function __construct(
        $key,
        $maxAttempts = RATE_LIMIT_LOGIN_ATTEMPTS,
        $windowSeconds = RATE_LIMIT_LOGIN_WINDOW,
        $storageType = 'file',
        $pdo = null
    ) {
        $this->key = hash('sha256', $key);
        $this->maxAttempts = $maxAttempts;
        $this->windowSeconds = $windowSeconds;
        $this->storageType = $storageType;
        $this->pdo = $pdo;
    }

    /**
     * Record an attempt
     *
     * @return bool True if under limit, false if limit exceeded
     */
    public function attempt() {
        if (!RATE_LIMIT_ENABLED) {
            return true;
        }

        $attempts = $this->getAttempts();
        $attempts++;

        if ($this->storageType === 'database') {
            $this->storeAttemptDatabase($attempts);
        } else {
            $this->storeAttemptFile($attempts);
        }

        return $attempts <= $this->maxAttempts;
    }

    /**
     * Get current number of attempts
     *
     * @return int
     */
    public function getAttempts() {
        if ($this->storageType === 'database') {
            return $this->getAttemptsFromDatabase();
        } else {
            return $this->getAttemptsFromFile();
        }
    }

    /**
     * Check if rate limit is exceeded
     *
     * @return bool
     */
    public function isLimited() {
        return $this->getAttempts() > $this->maxAttempts;
    }

    /**
     * Reset attempts for this key
     */
    public function reset() {
        if ($this->storageType === 'database') {
            $this->resetDatabase();
        } else {
            $this->resetFile();
        }
    }

    /**
     * Get time remaining until reset (in seconds)
     *
     * @return int
     */
    public function getTimeRemaining() {
        if ($this->storageType === 'database') {
            return $this->getTimeRemainingDatabase();
        } else {
            return $this->getTimeRemainingFile();
        }
    }

    // ========================================================================
    // FILE-BASED STORAGE
    // ========================================================================

    /**
     * Get attempts from file storage
     *
     * @return int
     */
    private function getAttemptsFromFile() {
        $filePath = $this->getFilePath();

        if (!file_exists($filePath)) {
            return 0;
        }

        $data = json_decode(file_get_contents($filePath), true);

        if (!is_array($data) || !isset($data['attempts']) || !isset($data['timestamp'])) {
            return 0;
        }

        // Check if window has expired
        if (time() - $data['timestamp'] > $this->windowSeconds) {
            @unlink($filePath);
            return 0;
        }

        return $data['attempts'];
    }

    /**
     * Store attempts in file
     *
     * @param int $attempts
     */
    private function storeAttemptFile($attempts) {
        $filePath = $this->getFilePath();
        $dir = dirname($filePath);

        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $data = [
            'attempts' => $attempts,
            'timestamp' => time()
        ];

        file_put_contents($filePath, json_encode($data), LOCK_EX);
    }

    /**
     * Reset file storage
     */
    private function resetFile() {
        $filePath = $this->getFilePath();
        if (file_exists($filePath)) {
            @unlink($filePath);
        }
    }

    /**
     * Get time remaining from file storage
     *
     * @return int
     */
    private function getTimeRemainingFile() {
        $filePath = $this->getFilePath();

        if (!file_exists($filePath)) {
            return 0;
        }

        $data = json_decode(file_get_contents($filePath), true);

        if (!is_array($data) || !isset($data['timestamp'])) {
            return 0;
        }

        $elapsed = time() - $data['timestamp'];
        $remaining = $this->windowSeconds - $elapsed;

        return max(0, $remaining);
    }

    /**
     * Get file path for storage
     *
     * @return string
     */
    private function getFilePath() {
        $dir = __DIR__ . '/../storage/rate_limits';
        return $dir . '/' . substr($this->key, 0, 2) . '/' . $this->key . '.json';
    }

    // ========================================================================
    // DATABASE STORAGE
    // ========================================================================

    /**
     * Get attempts from database
     *
     * @return int
     */
    private function getAttemptsFromDatabase() {
        if (!$this->pdo) {
            return 0;
        }

        try {
            $stmt = $this->pdo->prepare(
                "SELECT attempts, timestamp FROM rate_limits
                 WHERE key = ? AND created_at > DATE_SUB(NOW(), INTERVAL ? SECOND)"
            );
            $stmt->execute([$this->key, $this->windowSeconds]);
            $result = $stmt->fetch();

            return $result ? $result['attempts'] : 0;
        } catch (PDOException $e) {
            // Fall back to file storage on database error
            return $this->getAttemptsFromFile();
        }
    }

    /**
     * Store attempts in database
     *
     * @param int $attempts
     */
    private function storeAttemptDatabase($attempts) {
        if (!$this->pdo) {
            $this->storeAttemptFile($attempts);
            return;
        }

        try {
            $stmt = $this->pdo->prepare(
                "INSERT INTO rate_limits (key, attempts, created_at)
                 VALUES (?, ?, NOW())
                 ON DUPLICATE KEY UPDATE attempts = ?, created_at = NOW()"
            );
            $stmt->execute([$this->key, $attempts, $attempts]);
        } catch (PDOException $e) {
            // Fall back to file storage
            $this->storeAttemptFile($attempts);
        }
    }

    /**
     * Reset database storage
     */
    private function resetDatabase() {
        if (!$this->pdo) {
            $this->resetFile();
            return;
        }

        try {
            $stmt = $this->pdo->prepare("DELETE FROM rate_limits WHERE key = ?");
            $stmt->execute([$this->key]);
        } catch (PDOException $e) {
            $this->resetFile();
        }
    }

    /**
     * Get time remaining from database
     *
     * @return int
     */
    private function getTimeRemainingDatabase() {
        if (!$this->pdo) {
            return 0;
        }

        try {
            $stmt = $this->pdo->prepare(
                "SELECT TIMESTAMPDIFF(SECOND, created_at, DATE_ADD(created_at, INTERVAL ? SECOND)) as remaining
                 FROM rate_limits
                 WHERE key = ? AND created_at > DATE_SUB(NOW(), INTERVAL ? SECOND)"
            );
            $stmt->execute([$this->windowSeconds, $this->key, $this->windowSeconds]);
            $result = $stmt->fetch();

            return $result ? max(0, $result['remaining']) : 0;
        } catch (PDOException $e) {
            return $this->getTimeRemainingFile();
        }
    }

    /**
     * Clean up old entries
     * Should be called periodically (via cron job)
     *
     * @param PDO $pdo
     */
    public static function cleanup($pdo = null) {
        if ($pdo) {
            try {
                $pdo->exec(
                    "DELETE FROM rate_limits
                     WHERE created_at < DATE_SUB(NOW(), INTERVAL 24 HOUR)"
                );
            } catch (PDOException $e) {
                // Silently fail
            }
        }

        // Clean up old file-based entries
        $dir = __DIR__ . '/../storage/rate_limits';
        if (is_dir($dir)) {
            $files = glob($dir . '/*/*.json');
            $maxAge = time() - 86400; // 24 hours

            foreach ($files as $file) {
                if (filemtime($file) < $maxAge) {
                    @unlink($file);
                }
            }
        }
    }
}
