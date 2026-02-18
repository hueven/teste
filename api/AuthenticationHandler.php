<?php
/**
 * AuthenticationHandler - User authentication operations
 *
 * Handles:
 * - User login with rate limiting
 * - User logout
 * - Session management
 * - Password verification
 */

require_once __DIR__ . '/BaseHandler.php';
require_once __DIR__ . '/Validator.php';
require_once __DIR__ . '/RateLimiter.php';

class AuthenticationHandler extends BaseHandler {

    /**
     * Authentication does not require prior session
     */
    protected $requiresAuth = false;

    /**
     * Handle authentication operations
     */
    public function handle() {
        $action = $this->getParam('action', 'login');

        switch ($action) {
            case 'login':
                $this->login();
                break;
            case 'logout':
                $this->logout();
                break;
            case 'check':
                $this->checkSession();
                break;
            default:
                $this->error("Unknown action: " . htmlspecialchars($action), 400);
        }
    }

    /**
     * Authenticate user (login)
     */
    private function login() {
        // Check CSRF token
        $this->validateCsrfToken();

        // Get credentials
        $email = $this->getRequiredParam('email', 'Email is required');
        $password = $this->getRequiredParam('password', 'Password is required');

        // Validate email format
        if (!Validator::isValidEmail($email)) {
            $this->error('Invalid email format', 400);
        }

        // Get client IP for rate limiting
        $clientIp = $this->getClientIp();
        $rateLimiter = new RateLimiter("login:$clientIp", RATE_LIMIT_LOGIN_ATTEMPTS, RATE_LIMIT_LOGIN_WINDOW, 'file', $this->pdo);

        // Check if rate limit exceeded
        if ($rateLimiter->isLimited()) {
            $remaining = $rateLimiter->getTimeRemaining();
            $this->error(
                "Too many login attempts. Please try again in {$remaining} seconds.",
                429
            );
        }

        // Find user
        $user = $this->fetchOne(
            'SELECT id, email, password_hash FROM users WHERE email = ?',
            [$email]
        );

        // Record attempt
        if (!$rateLimiter->attempt()) {
            $this->error('Too many login attempts. Please try again later.', 429);
        }

        // Verify password
        if (!$user || !password_verify($password, $user['password_hash'])) {
            // Provide generic error to prevent user enumeration
            $this->error('Invalid email or password', 401);
        }

        // Rate limit reset on successful login
        $rateLimiter->reset();

        // Regenerate session
        regenerateSessionId(true);

        // Store user info in session
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['login_time'] = time();
        $_SESSION['ip_address'] = $clientIp;

        $this->success([
            'message' => 'Logged in successfully',
            'user_id' => $user['id'],
            'email' => $user['email']
        ]);
    }

    /**
     * Logout user
     */
    private function logout() {
        initSession();

        // Destroy session
        $_SESSION = [];
        session_destroy();

        // Delete session cookie
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params['path'],
                $params['domain'],
                $params['secure'],
                $params['httponly']
            );
        }

        $this->success(['message' => 'Logged out successfully']);
    }

    /**
     * Check if session is valid
     */
    private function checkSession() {
        if (isAuthenticated()) {
            $this->success([
                'authenticated' => true,
                'user_id' => getCurrentUserId(),
                'email' => $_SESSION['user_email'] ?? null
            ]);
        } else {
            $this->success([
                'authenticated' => false,
                'user_id' => null
            ]);
        }
    }

    /**
     * Get client IP address
     * Handles proxies and load balancers
     *
     * @return string
     */
    private function getClientIp() {
        // Check for shared internet
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        }
        // Check for IP passed from proxy
        elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            // Can contain multiple IPs, get the first one
            $ips = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
            $ip = trim($ips[0]);
        }
        // Check regular remote address
        else {
            $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        }

        // Validate IP
        if (!filter_var($ip, FILTER_VALIDATE_IP)) {
            $ip = '0.0.0.0';
        }

        return $ip;
    }
}
