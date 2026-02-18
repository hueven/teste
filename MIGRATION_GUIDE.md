# Migration Guide - Wide Studio Gallery CMS

## 📋 Overview

This guide helps you migrate your existing Wide Studio Gallery CMS to the new refactored architecture with improved security and maintainability.

---

## 🚀 Step-by-Step Migration

### Phase 1: Setup Environment (15 minutes)

#### 1.1 Create `.env` file

```bash
# Copy template
cp .env.example .env

# Edit with your credentials
nano .env  # or vim, or any text editor
```

**Your `.env` should look like:**
```env
DB_HOST=localhost
DB_USER=your_database_user
DB_PASS=your_database_password
DB_NAME=your_database_name
DB_CHARSET=utf8mb4

APP_ENV=production
APP_DEBUG=false
APP_URL=https://yourdomain.com

SESSION_SECURE=true
SESSION_HTTP_ONLY=true
SESSION_SAME_SITE=Lax

RATE_LIMIT_ENABLED=true
RATE_LIMIT_LOGIN_ATTEMPTS=5
RATE_LIMIT_LOGIN_WINDOW=900
```

#### 1.2 Verify `.env` is in `.gitignore`

```bash
grep ".env" .gitignore
```

Should output: `.env`

#### 1.3 Test connection

Create `test-config.php`:

```php
<?php
require_once 'config.php';

try {
    $result = $pdo->query("SELECT 1");
    echo "✅ Database connection successful!";
} catch (PDOException $e) {
    echo "❌ Database error: " . $e->getMessage();
}
?>
```

Run it: `php test-config.php`

---

### Phase 2: Database Setup (10 minutes)

#### 2.1 Create tables

```bash
# Import schema
mysql -u your_user -p your_database < database-schema.sql

# Or manually in your database tool:
# Copy and execute SQL from database-schema.sql
```

#### 2.2 Verify tables exist

```bash
mysql -u your_user -p your_database -e "SHOW TABLES;"
```

Should see:
- users
- collections
- collection_items
- products
- menu_items
- settings
- rate_limits
- activity_logs (optional)

#### 2.3 Create admin user

```sql
INSERT INTO users (email, password_hash, name) VALUES (
    'admin@example.com',
    '$2y$10$your_hashed_password',
    'Administrator'
);
```

**To hash a password, use PHP:**
```php
<?php
echo password_hash('your_password_here', PASSWORD_DEFAULT);
?>
```

---

### Phase 3: Migrate Core Files (30 minutes)

#### 3.1 Replace `config.php`

**Current state:** Your old `config.php` with hardcoded credentials

**Action:**
1. Backup old file: `cp config.php config.php.backup`
2. Replace with new: Copy the new `config.php` from this repository
3. Test: `php test-config.php`

#### 3.2 Migrate `login.php`

**Option A: Quick replacement (RECOMMENDED)**
```bash
mv login.php login.php.backup
cp login-migrated.php login.php
```

**Option B: Manual migration**

Old `login.php`:
- Had direct database queries
- No CSRF protection
- No rate limiting

New features in `login-migrated.php`:
- ✅ CSRF token in form
- ✅ Session regeneration after login
- ✅ Safe error messages
- ✅ Remember me option

#### 3.3 Migrate `index.php` (homepage)

**Option A: Quick replacement**
```bash
mv index.php index.php.backup
cp index-migrated.php index.php
```

**Option B: Manual updates**

Add CSRF token to your contact form:
```php
<input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
```

#### 3.4 Migrate `admin.php`

**Option A: Quick replacement (RECOMMENDED)**
```bash
mv admin.php admin.php.backup
cp admin-migrated.php admin.php
```

**Option B: Manual integration**

Add to your admin panel:
```html
<!-- At the end of admin.php, before closing body tag -->
<script type="module">
    import AdminState from '/js/AdminState.js';
    import AdminAPI from '/js/AdminAPI.js';

    const state = new AdminState();
    const api = new AdminAPI(state, '/api');

    // Now use state and api for all operations
</script>
```

---

### Phase 4: Implement API Handlers (45 minutes)

#### 4.1 Create API routing entry point

Create `api.php` at root:

```php
<?php
/**
 * API Entry Point
 * Routes all /api/* requests to appropriate handlers
 */

// Remove this line if api.php is called via /api/something URL
// parse_url handles both /api and /api.php

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/api/Router.php';

$router = new Router($pdo);
$router->handle();
?>
```

#### 4.2 Setup web server routing

**For Apache (.htaccess):**
```apache
<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteBase /

    # Route /api/* to api.php
    RewriteRule ^api/(.*)$ api.php?path=$1 [QSA,L]
</IfModule>
```

**For Nginx:**
```nginx
location /api/ {
    try_files $uri $uri/ /api.php?$query_string;
}
```

#### 4.3 Update your forms to use API

Old way (direct PHP processing):
```php
<?php
if ($_POST['action'] === 'create_collection') {
    // Process in same file
}
?>
```

New way (API call):
```javascript
// In JavaScript
const api = new AdminAPI(state);
await api.createCollection(name, description);
```

#### 4.4 Migrate `gallery_api.php` (optional)

If you have `gallery_api.php` with many actions:

**Old structure:**
```php
<?php
if ($_GET['action'] === 'upload') { /* 80 lines */ }
if ($_GET['action'] === 'delete') { /* 50 lines */ }
if ($_GET['action'] === 'reorder') { /* 40 lines */ }
// ... 60+ more actions
?>
```

**New approach:**

1. Create `api/GalleryHandler.php`
2. Move upload logic to `GalleryHandler::upload()`
3. Move delete logic to `GalleryHandler::delete()`
4. Register in `api/Router.php`

```php
// In Router.php
$router->registerHandler('gallery', 'GalleryHandler');
```

---

### Phase 5: Integrate JavaScript Modules (20 minutes)

#### 5.1 Ensure `js/AdminState.js` is in place

Check: `ls -la js/AdminState.js`

This manages all your admin state.

#### 5.2 Ensure `js/AdminAPI.js` is in place

Check: `ls -la js/AdminAPI.js`

This handles all API communication.

#### 5.3 Update other JS files to use new system

**Old way:**
```javascript
let collections = [];
let loading = false;

function fetchCollections() {
    loading = true;
    fetch('/gallery_api.php?action=list')
        .then(/* process */)
        .catch(/* handle error */);
}
```

**New way:**
```javascript
import AdminAPI from '/js/AdminAPI.js';
import AdminState from '/js/AdminState.js';

const state = new AdminState();
const api = new AdminAPI(state);

// State manages everything
state.on('collections:update', () => {
    console.log('Collections updated:', state.getCollections());
});

// API call handles loading/errors automatically
await api.fetchCollections();
```

---

### Phase 6: Testing (30 minutes)

#### 6.1 Test Authentication

```bash
# 1. Try login at /login.php
# Should work with your admin credentials

# 2. Try accessing /admin.php without login
# Should redirect to /login.php

# 3. Test logout
# Should clear session and redirect
```

#### 6.2 Test CSRF Protection

```bash
# Try submitting a form without CSRF token
# Should get error: "Invalid request"

# Try with old CSRF token
# Should get error: "Invalid request"
```

#### 6.3 Test Rate Limiting

```bash
# Try login 6 times with wrong password
# After 5 attempts, should see:
# "Too many login attempts. Please try again later."
```

#### 6.4 Test Collections CRUD

```bash
# 1. Create collection
# Should appear immediately and be saved to DB

# 2. Edit collection
# Changes should reflect immediately

# 3. Delete collection
# Should be removed from DB

# 4. Check database
# Verify all changes persisted
```

#### 6.5 Test Error Handling

```bash
# Try invalid email format
# Should see: "Invalid email format"

# Try SQL injection (in any input)
# Should be sanitized and fail gracefully
```

---

### Phase 7: Performance Verification (15 minutes)

#### 7.1 Check database indexes

```bash
mysql -u user -p database -e "SHOW INDEXES FROM collections;"
```

#### 7.2 Test query performance

```bash
# Enable query logging and check slow queries
mysql -u user -p database -e "SHOW VARIABLES LIKE 'slow_query_log';"
```

#### 7.3 Check file sizes

```bash
# Old approach: One big file
ls -lh gallery_api.php  # ~2076 KB

# New approach: Modular files
ls -lh api/*.php  # Each ~100-200 KB
```

---

## 🔄 Rollback Plan

If something goes wrong:

```bash
# Restore backups
mv config.php.backup config.php
mv login.php.backup login.php
mv index.php.backup index.php
mv admin.php.backup admin.php

# Clear sessions
rm -rf /path/to/sessions/*

# Reload application
```

---

## 📊 Migration Checklist

### Pre-Migration
- [ ] Backup entire application
- [ ] Backup database
- [ ] Test on development server first
- [ ] Read this entire guide

### Core Files
- [ ] Updated `.env` file with credentials
- [ ] `.gitignore` includes `.env`
- [ ] New `config.php` in place
- [ ] `login.php` migrated
- [ ] `index.php` migrated
- [ ] `admin.php` migrated

### Database
- [ ] Tables created via `database-schema.sql`
- [ ] Admin user created
- [ ] Rate limit table exists
- [ ] All indexes in place

### API
- [ ] `api/Router.php` created
- [ ] `api/BaseHandler.php` created
- [ ] `api/Validator.php` created
- [ ] `api/RateLimiter.php` created
- [ ] `api/CollectionsHandler.php` created
- [ ] `api/AuthenticationHandler.php` created
- [ ] API routing configured (`.htaccess` or nginx)

### JavaScript
- [ ] `js/AdminState.js` in place
- [ ] `js/AdminAPI.js` in place
- [ ] Forms updated with CSRF tokens
- [ ] Event listeners integrated

### Testing
- [ ] Login works
- [ ] Authentication required pages protected
- [ ] CSRF protection verified
- [ ] Rate limiting tested
- [ ] Create/Edit/Delete operations work
- [ ] No SQL errors in admin panel
- [ ] `.env` not in git
- [ ] All features working

### Post-Migration
- [ ] Monitor logs for errors
- [ ] Test on production
- [ ] Update documentation
- [ ] Train team on new system
- [ ] Archive old backup files

---

## 🆘 Troubleshooting

### "Database connection failed"
- [ ] Check `.env` file has correct credentials
- [ ] Verify database user has correct permissions
- [ ] Test: `mysql -u user -p -h host database`

### "Invalid CSRF token"
- [ ] Clear browser cookies and cache
- [ ] Generate new token: `curl -b "" http://yoursite/login.php`
- [ ] Check session is working

### "Too many login attempts"
- [ ] Rate limiter triggered - wait 15 minutes
- [ ] Or clear: `rm -rf storage/rate_limits/`

### "Module not found"
- [ ] Check file paths in imports
- [ ] Ensure `api/` directory exists
- [ ] Run: `ls -la api/`

### Collections not loading
- [ ] Check database connection
- [ ] Verify `collections` table exists
- [ ] Check browser console for errors
- [ ] Run: `php -r "require 'config.php'; var_dump($pdo->query('SELECT COUNT(*) FROM collections')->fetch());"`

---

## 📚 Additional Resources

- [IMPROVEMENTS.md](./IMPROVEMENTS.md) - Detailed improvements explanation
- [database-schema.sql](./database-schema.sql) - Database schema
- [config.php](./config.php) - Configuration and utilities
- [api/BaseHandler.php](./api/BaseHandler.php) - API base class

---

## ✅ You're Done!

After completing all steps:

1. ✅ Your application is more secure
2. ✅ Code is better organized
3. ✅ Easier to maintain and extend
4. ✅ Better error handling
5. ✅ Protected against common attacks

Enjoy your improved codebase! 🎉
