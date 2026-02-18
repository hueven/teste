<?php
/**
 * login.php - MODERNIZED VERSION
 *
 * Secure login using:
 * - New config.php with environment variables
 * - CSRF token protection
 * - Safe error handling (no credential hints)
 * - Secure session handling
 * - Rate limiting on backend
 */

require_once __DIR__ . '/config.php';

// Initialize session
initSession();

// If already logged in, redirect to admin
if (isAuthenticated()) {
    header('Location: /admin-migrated.php');
    exit;
}

// Generate CSRF token for form
$csrfToken = generateCsrfToken();
$error = null;

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verify CSRF token
    if (!verifyCsrfToken($_POST['csrf_token'] ?? null)) {
        $error = 'Invalid request. Please try again.';
    } else {
        $email = $_POST['email'] ?? '';
        $password = $_POST['password'] ?? '';

        // Client-side validation (server-side validation happens in API)
        if (empty($email) || empty($password)) {
            $error = 'Please enter email and password.';
        } else {
            // Attempt login via API
            try {
                // For security, we still use the API approach internally
                // or you can use direct DB query here
                $user = $pdo->prepare(
                    "SELECT id, email, password_hash FROM users WHERE email = ?"
                )->execute([$email])->fetch();

                if ($user && password_verify($password, $user['password_hash'])) {
                    // Regenerate session for security
                    regenerateSessionId(true);

                    // Store in session
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['user_email'] = $user['email'];
                    $_SESSION['login_time'] = time();

                    // Redirect to admin
                    header('Location: /admin-migrated.php');
                    exit;
                } else {
                    // Generic error (don't reveal if user exists)
                    $error = 'Invalid email or password.';
                    safeLog("Failed login attempt for: " . htmlspecialchars($email), 'INFO');
                }
            } catch (PDOException $e) {
                if (APP_DEBUG) {
                    safeLog("Login error: " . $e->getMessage(), 'ERROR');
                }
                $error = 'An error occurred. Please try again.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Wide Studio Admin</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            color: #333;
        }

        .login-container {
            background: white;
            border-radius: 8px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.2);
            width: 100%;
            max-width: 400px;
            padding: 2rem;
        }

        .login-header {
            text-align: center;
            margin-bottom: 2rem;
        }

        .login-header h1 {
            font-size: 2rem;
            margin-bottom: 0.5rem;
        }

        .login-header p {
            color: #666;
            font-size: 0.9rem;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: #333;
        }

        input[type="email"],
        input[type="password"] {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 1rem;
            font-family: inherit;
            transition: border-color 0.2s;
        }

        input[type="email"]:focus,
        input[type="password"]:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .form-remember {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 1.5rem;
            font-size: 0.9rem;
        }

        .form-remember input[type="checkbox"] {
            cursor: pointer;
        }

        .btn-login {
            width: 100%;
            padding: 0.75rem;
            background: #667eea;
            color: white;
            border: none;
            border-radius: 4px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.2s;
        }

        .btn-login:hover {
            background: #5568d3;
        }

        .btn-login:active {
            transform: scale(0.98);
        }

        .alert {
            padding: 0.75rem;
            margin-bottom: 1.5rem;
            border-radius: 4px;
            font-size: 0.9rem;
        }

        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .alert-info {
            background: #d1ecf1;
            color: #0c5460;
            border: 1px solid #bee5eb;
        }

        .login-footer {
            text-align: center;
            margin-top: 1.5rem;
            font-size: 0.9rem;
            color: #666;
        }

        .login-footer a {
            color: #667eea;
            text-decoration: none;
            font-weight: 600;
        }

        .login-footer a:hover {
            text-decoration: underline;
        }

        /* Loading state */
        .btn-login:disabled {
            opacity: 0.7;
            cursor: not-allowed;
        }

        .csrf-token-error {
            background: #fff3cd;
            color: #856404;
            padding: 1rem;
            border-radius: 4px;
            margin-bottom: 1.5rem;
            border: 1px solid #ffeeba;
        }

        @media (max-width: 480px) {
            .login-container {
                padding: 1.5rem;
            }

            .login-header h1 {
                font-size: 1.5rem;
            }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <h1>Wide Studio</h1>
            <p>Admin Login</p>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-error">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <?php if (isset($_GET['session_expired'])): ?>
            <div class="alert alert-info">
                Your session has expired. Please log in again.
            </div>
        <?php endif; ?>

        <form method="POST" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>">
            <!-- CSRF Token (IMPORTANT!) -->
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">

            <div class="form-group">
                <label for="email">Email Address</label>
                <input type="email"
                       id="email"
                       name="email"
                       required
                       autocomplete="email"
                       value="<?php echo isset($email) ? htmlspecialchars($email) : ''; ?>"
                       placeholder="your@email.com">
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <input type="password"
                       id="password"
                       name="password"
                       required
                       autocomplete="current-password"
                       placeholder="••••••••">
            </div>

            <div class="form-remember">
                <input type="checkbox" id="remember" name="remember" value="1">
                <label for="remember" style="margin: 0;">Remember me</label>
            </div>

            <button type="submit" class="btn-login" id="submitBtn">Sign In</button>
        </form>

        <div class="login-footer">
            <p>Don't have an account? <a href="/signup.php">Sign up</a></p>
            <p><a href="/forgot-password.php">Forgot password?</a></p>
        </div>
    </div>

    <script>
        // Handle form submission
        const form = document.querySelector('form');
        const submitBtn = document.getElementById('submitBtn');

        form.addEventListener('submit', function(e) {
            // Disable submit button to prevent double submission
            submitBtn.disabled = true;
            submitBtn.textContent = 'Signing in...';

            // Re-enable after 2 seconds in case of error
            setTimeout(() => {
                if (!form.submitted) {
                    submitBtn.disabled = false;
                    submitBtn.textContent = 'Sign In';
                }
            }, 2000);
        });

        // Mark form as submitted on successful submission
        if (form.method === 'POST') {
            form.submitted = false;
        }

        // Focus on email field
        document.getElementById('email').focus();

        // Show password toggle (optional)
        const passwordField = document.getElementById('password');
        const togglePasswordBtn = document.createElement('button');
        togglePasswordBtn.type = 'button';
        togglePasswordBtn.textContent = 'Show';
        togglePasswordBtn.style.position = 'absolute';
        togglePasswordBtn.style.right = '10px';
        togglePasswordBtn.style.top = '50%';
        togglePasswordBtn.style.transform = 'translateY(-50%)';

        // Uncomment to add password toggle:
        // passwordField.parentElement.style.position = 'relative';
        // passwordField.parentElement.appendChild(togglePasswordBtn);
    </script>
</body>
</html>
