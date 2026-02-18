# API Examples - Wide Studio Gallery CMS

## 📡 Overview

This document shows how to use the new refactored API endpoints with real examples.

---

## 🔐 Authentication

### Login

**Endpoint:** `POST /api/auth`

**Parameters:**
```
action=login
email=admin@example.com
password=your_password
csrf_token=<token_from_session>
```

**Example with cURL:**

```bash
# First, get CSRF token by visiting login page
curl -c cookies.txt -b cookies.txt https://example.com/login.php

# Then login with token
curl -b cookies.txt -X POST https://example.com/api/auth \
  -d "action=login&email=admin@example.com&password=password123&csrf_token=TOKEN_HERE"
```

**Example with JavaScript:**

```javascript
import AdminAPI from '/js/AdminAPI.js';
import AdminState from '/js/AdminState.js';

const state = new AdminState();
const api = new AdminAPI(state);

const success = await api.login('admin@example.com', 'password123');
if (success) {
    console.log('Logged in!');
    window.location.href = '/admin.php';
}
```

**Success Response:**
```json
{
    "success": true,
    "data": {
        "message": "Logged in successfully",
        "user_id": 1,
        "email": "admin@example.com"
    }
}
```

**Error Response:**
```json
{
    "success": false,
    "data": {
        "error": "Invalid email or password"
    }
}
```

---

### Logout

**Endpoint:** `POST /api/auth`

**Parameters:**
```
action=logout
```

**Example:**

```javascript
await api.logout();
// Clears session and redirects
```

---

### Check Authentication Status

**Endpoint:** `POST /api/auth`

**Parameters:**
```
action=check
```

**Example:**

```bash
curl -b cookies.txt https://example.com/api/auth?action=check
```

**Response:**
```json
{
    "success": true,
    "data": {
        "authenticated": true,
        "user_id": 1,
        "email": "admin@example.com"
    }
}
```

---

## 📚 Collections

### List Collections

**Endpoint:** `GET /api/collections` or `POST /api/collections`

**Parameters:**
```
action=list
page=1
limit=20
search=portfolio
```

**Example with cURL:**

```bash
curl "https://example.com/api/collections?action=list&page=1&limit=10"
```

**Example with JavaScript:**

```javascript
await api.fetchCollections(page = 1, limit = 20);

const collections = state.getCollections();
console.log(collections);
```

**Response:**
```json
{
    "success": true,
    "data": {
        "collections": [
            {
                "id": 1,
                "name": "Summer Portfolio",
                "slug": "summer-portfolio",
                "description": "2024 summer projects",
                "image_url": "https://example.com/images/summer.jpg",
                "position": 1,
                "created_at": "2024-02-18 10:30:00"
            }
        ],
        "pagination": {
            "page": 1,
            "limit": 20,
            "total": 5,
            "pages": 1
        }
    }
}
```

---

### Get Single Collection

**Endpoint:** `GET /api/collections` or `POST /api/collections`

**Parameters:**
```
action=get
id=1
```

**Example:**

```bash
curl "https://example.com/api/collections?action=get&id=1"
```

**Response:**
```json
{
    "success": true,
    "data": {
        "id": 1,
        "name": "Summer Portfolio",
        "slug": "summer-portfolio",
        "description": "2024 summer projects",
        "image_url": "https://example.com/images/summer.jpg",
        "position": 1,
        "user_id": 1,
        "created_at": "2024-02-18 10:30:00",
        "updated_at": "2024-02-18 10:30:00"
    }
}
```

---

### Create Collection

**Endpoint:** `POST /api/collections`

**Parameters:**
```
action=create
name=My Collection
description=Collection description
image_url=https://example.com/image.jpg
csrf_token=<token>
```

**Example with cURL:**

```bash
curl -X POST https://example.com/api/collections \
  -d "action=create&name=New+Collection&description=Description&csrf_token=TOKEN"
```

**Example with JavaScript:**

```javascript
const success = await api.createCollection(
    'My Collection',
    'This is my collection',
    'https://example.com/image.jpg'
);
```

**Success Response:**
```json
{
    "success": true,
    "data": {
        "id": 2,
        "message": "Collection created successfully"
    }
}
```

**Error Response (missing field):**
```json
{
    "success": false,
    "data": {
        "error": "Collection name is required"
    }
}
```

**Error Response (duplicate name):**
```json
{
    "success": false,
    "data": {
        "error": "Collection name already exists"
    }
}
```

---

### Update Collection

**Endpoint:** `POST /api/collections`

**Parameters:**
```
action=update
id=1
name=Updated Name
description=Updated description
image_url=https://example.com/new-image.jpg
csrf_token=<token>
```

**Example:**

```javascript
const success = await api.updateCollection(1, {
    name: 'Updated Collection',
    description: 'New description',
    image_url: 'https://example.com/new.jpg'
});
```

**Response:**
```json
{
    "success": true,
    "data": {
        "message": "Collection updated successfully"
    }
}
```

---

### Delete Collection

**Endpoint:** `POST /api/collections`

**Parameters:**
```
action=delete
id=1
csrf_token=<token>
```

**Example:**

```javascript
const success = await api.deleteCollection(1);
```

**Response:**
```json
{
    "success": true,
    "data": {
        "message": "Collection deleted successfully"
    }
}
```

---

### Reorder Collections

**Endpoint:** `POST /api/collections`

**Parameters:**
```
action=reorder
order[]=3
order[]=1
order[]=2
csrf_token=<token>
```

**Example:**

```javascript
const newOrder = [3, 1, 2];  // Collection IDs in new order
const success = await api.reorderCollections(newOrder);
```

**Response:**
```json
{
    "success": true,
    "data": {
        "message": "Collections reordered successfully"
    }
}
```

---

## 🛡️ Error Handling

### Common Error Codes

| Status | Error | Cause |
|--------|-------|-------|
| 400 | Missing required field | `name` parameter not provided |
| 401 | Unauthorized | Not logged in |
| 403 | Unauthorized | CSRF token invalid |
| 404 | Not found | Collection doesn't exist |
| 409 | Conflict | Collection name already exists |
| 429 | Too many requests | Rate limiting triggered |
| 500 | Server error | Database error |

### Error Response Format

```json
{
    "success": false,
    "data": {
        "error": "Error message here"
    }
}
```

---

## 📝 State Management Examples

### Listen for Collection Changes

```javascript
const state = new AdminState();

// Listen for any change
state.on('change', ({ event, data }) => {
    console.log('Something changed:', event, data);
});

// Listen for specific events
state.on('collections:update', (collections) => {
    console.log('Collections list updated:', collections);
});

state.on('collections:add', (collection) => {
    console.log('New collection added:', collection);
});

state.on('collection:delete', (deletedCollection) => {
    console.log('Collection deleted:', deletedCollection);
});

state.on('loading:change', (isLoading) => {
    console.log('Loading state changed:', isLoading);
});

state.on('error:change', (error) => {
    if (error) {
        console.error('Error occurred:', error);
    }
});
```

### Working with State

```javascript
const state = new AdminState();

// Get current state
console.log(state.getState());

// Get collections
const collections = state.getCollections();

// Get specific collection
const collection = state.getCollection(1);

// Update collections
state.setCollections([...]);

// Add collection
state.addCollection({ id: 2, name: 'New' });

// Update collection
state.updateCollection(1, { name: 'Updated' });

// Delete collection
state.deleteCollection(1);

// Get error
if (state.getError()) {
    console.error(state.getError());
}

// Clear error
state.clearError();

// Check if loading
if (state.isLoading()) {
    console.log('API call in progress...');
}

// Get user info
const user = state.getUser();

// Set user
state.setUser({ id: 1, email: 'admin@example.com', authenticated: true });
```

---

## 🧪 Testing with Postman

### 1. Import Collection

Create a Postman collection with these requests:

**Login:**
- Method: POST
- URL: `{{base_url}}/api/auth`
- Body (form-data):
  - action: login
  - email: admin@example.com
  - password: password123
  - csrf_token: (get from Set-Cookie header)

**List Collections:**
- Method: GET
- URL: `{{base_url}}/api/collections?action=list&page=1&limit=20`

**Create Collection:**
- Method: POST
- URL: `{{base_url}}/api/collections`
- Body (form-data):
  - action: create
  - name: Test Collection
  - description: Test description
  - csrf_token: (from login response)

---

## 🔄 Complete Workflow Example

```javascript
import AdminAPI from '/js/AdminAPI.js';
import AdminState from '/js/AdminState.js';

// Initialize
const state = new AdminState();
const api = new AdminAPI(state);

// Main workflow
async function main() {
    try {
        // 1. Check if authenticated
        const isAuth = await api.checkAuth();
        if (!isAuth) {
            // Redirect to login
            window.location.href = '/login.php';
            return;
        }

        // 2. Fetch collections
        console.log('Fetching collections...');
        await api.fetchCollections();

        // 3. Create new collection
        console.log('Creating collection...');
        await api.createCollection(
            'My Portfolio',
            'My best work from 2024'
        );

        // 4. Listen for updates
        state.on('collections:update', () => {
            const collections = state.getCollections();
            console.log('Updated collections:', collections);
            renderCollections(collections);
        });

        // 5. Handle errors
        state.on('error:change', (error) => {
            if (error) {
                showErrorNotification(error);
            }
        });

    } catch (error) {
        console.error('Workflow error:', error);
    }
}

function renderCollections(collections) {
    // Render your collections here
    collections.forEach(col => {
        console.log(`- ${col.name}: ${col.description}`);
    });
}

function showErrorNotification(error) {
    const div = document.createElement('div');
    div.className = 'notification error';
    div.textContent = error;
    document.body.appendChild(div);
    setTimeout(() => div.remove(), 5000);
}

// Start
main();
```

---

## 📦 Using with FormData (File Uploads)

```javascript
const formData = new FormData();
formData.append('action', 'upload_collection_image');
formData.append('collection_id', 1);
formData.append('title', 'My Photo');
formData.append('image', fileInput.files[0]);
formData.append('csrf_token', state.getCsrfToken());

const response = await api.postFormData('/gallery', formData);
```

---

## 🔗 API Base URL

All API calls use: `{{APP_URL}}/api/`

Configure in `.env`:
```env
APP_URL=https://example.com
```

Or in JavaScript:
```javascript
const api = new AdminAPI(state, '/api');  // Custom base URL
```

---

## 📊 Rate Limiting Response

When rate limited, you'll get:

```json
{
    "success": false,
    "data": {
        "error": "Too many login attempts. Please try again in 847 seconds."
    }
}
```

HTTP Status: `429 Too Many Requests`

---

## ✅ Best Practices

1. **Always include CSRF token** for POST requests
2. **Handle loading state** - show spinners/disable buttons
3. **Listen for errors** - display user-friendly messages
4. **Validate on client** - before sending to server
5. **Cache responses** - use localStorage via AdminState
6. **Batch requests** - when possible
7. **Clear old state** - when user logs out

---

## 🆘 Debugging

Enable debug mode in `.env`:
```env
APP_DEBUG=true
```

This will:
- Show detailed error messages
- Log to `logs/app.log`
- Display exception details

Check logs:
```bash
tail -f logs/app.log
```

---

**For more details, see [IMPROVEMENTS.md](./IMPROVEMENTS.md) and [MIGRATION_GUIDE.md](./MIGRATION_GUIDE.md)**
