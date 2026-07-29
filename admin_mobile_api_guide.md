# Admin Dashboard Mobile API Integration Guide
**Kanodia Textiles — Enterprise Manufacturing & ERP Platform**  
**Version:** 1.0  
**Base URL:** `https://your-domain.com/api/v1`

---

## 1. Overview & Authentication Standard

All Admin Dashboard APIs use **JWT (JSON Web Token) Bearer Token Authentication**.

### Common Headers
```http
Content-Type: application/json
Accept: application/json
Authorization: Bearer <YOUR_JWT_TOKEN>
```

---

## 2. Authentication Endpoints (`/api/v1/admin/auth`)

### 2.1 Admin Login
Authenticates an administrator or factory supervisor and issues a JWT token along with their permissions matrix.

- **HTTP Method:** `POST`
- **Endpoint:** `/api/v1/admin/auth/login`
- **Auth Required:** No

#### Request Body
```json
{
  "login": "admin@kanodia.com",
  "password": "yourpassword"
}
```
> **Note:** `login` accepts either an **Email Address** or **Mobile Number**.

#### Success Response (`200 OK`)
```json
{
  "success": true,
  "message": "Admin authentication successful.",
  "data": {
    "token": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...",
    "token_type": "bearer",
    "expires_in": 3600,
    "admin": {
      "id": 1,
      "name": "Super Admin",
      "email": "admin@kanodia.com",
      "mobile_number": "9876543210",
      "is_active": true,
      "roles": ["super_admin"],
      "permissions": ["access dashboard", "access products", "access inventory"]
    },
    "roles": ["super_admin"],
    "permissions": ["access dashboard", "access products", "access inventory"],
    "access_matrix": {
      "is_super_admin": true,
      "can_access_dashboard": true,
      "can_access_customers": true,
      "can_access_products": true,
      "can_access_categories": true,
      "can_access_inventory": true,
      "can_access_retail_shops": true,
      "can_access_product_transfers": true,
      "can_access_orders": true,
      "can_access_settings": true,
      "can_manage_admins": true,
      "can_manage_labor": true
    }
  }
}
```

#### Error Responses
- `401 Unauthorized`: Invalid credentials (`"Invalid login credentials."`)
- `403 Forbidden`: Account deactivated or user lacks admin privileges (`"Access denied. Account does not have admin access permissions."`)

---

### 2.2 Get Authenticated Profile (`/me`)
Retrieves the logged-in admin's profile, roles, and real-time permission access matrix.

- **HTTP Method:** `GET`
- **Endpoint:** `/api/v1/admin/auth/me`
- **Auth Required:** Yes (`Bearer Token`)

#### Success Response (`200 OK`)
```json
{
  "success": true,
  "message": "Admin profile retrieved successfully.",
  "data": {
    "admin": {
      "id": 2,
      "name": "Production Manager",
      "email": "manager@kanodia.com",
      "mobile_number": "9876543211",
      "is_active": true,
      "roles": ["admin"],
      "permissions": ["access products", "access inventory"]
    },
    "roles": ["admin"],
    "permissions": ["access products", "access inventory"],
    "access_matrix": {
      "is_super_admin": false,
      "can_access_dashboard": false,
      "can_access_customers": false,
      "can_access_products": true,
      "can_access_categories": false,
      "can_access_inventory": true,
      "can_access_retail_shops": false,
      "can_access_product_transfers": false,
      "can_access_orders": false,
      "can_access_settings": false,
      "can_manage_admins": false,
      "can_manage_labor": false
    }
  }
}
```

---

### 2.3 Token Refresh & Logout

#### Refresh Token
- **HTTP Method:** `POST`
- **Endpoint:** `/api/v1/admin/auth/refresh`
- **Auth Required:** Yes

```json
{
  "success": true,
  "message": "Token refreshed successfully.",
  "data": {
    "token": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...",
    "token_type": "bearer",
    "expires_in": 3600
  }
}
```

#### Logout
- **HTTP Method:** `POST`
- **Endpoint:** `/api/v1/admin/auth/logout`
- **Auth Required:** Yes

```json
{
  "success": true,
  "message": "Admin logout successful.",
  "data": []
}
```

---

## 3. Admins Management Endpoints (`/api/v1/admin/admins`)

> **Access Control Note:** All Admin Management endpoints require `super_admin` role.

---

### 3.1 List All Admin Accounts
Fetch paginated admin accounts with optional search and active status filters.

- **HTTP Method:** `GET`
- **Endpoint:** `/api/v1/admin/admins`
- **Query Parameters:**
  - `search` *(optional)*: Search string matching name, email, or mobile number
  - `status` *(optional)*: `true` / `false` / `1` / `0` (Filter active/inactive accounts)
  - `per_page` *(optional, default 15)*: Number of items per page

#### Sample Request
`GET /api/v1/admin/admins?search=manager&status=true&per_page=10`

#### Success Response (`200 OK`)
```json
{
  "success": true,
  "message": "Admins retrieved successfully.",
  "data": {
    "admins": [
      {
        "id": 2,
        "name": "Production Manager",
        "email": "manager@kanodia.com",
        "mobile_number": "9876543211",
        "is_active": true,
        "roles": ["admin"],
        "permissions": ["access products", "access inventory"],
        "created_at": "2026-07-29T14:15:00+00:00",
        "updated_at": "2026-07-29T14:15:00+00:00"
      }
    ],
    "pagination": {
      "total": 1,
      "per_page": 10,
      "current_page": 1,
      "last_page": 1
    }
  }
}
```

---

### 3.2 Get Available Page Permissions List
Retrieves a map of all assignable page/feature permissions to populate permission checkboxes in the mobile app.

- **HTTP Method:** `GET`
- **Endpoint:** `/api/v1/admin/admins/permissions`

#### Success Response (`200 OK`)
```json
{
  "success": true,
  "message": "Available permissions retrieved successfully.",
  "data": {
    "access dashboard": "Dashboard",
    "access customers": "Customers",
    "access customer-levels": "Customer Levels",
    "access products": "Products",
    "access design-catalog": "Design Catalog",
    "access categories": "Categories",
    "access tags": "Tags",
    "access inventory": "Inventory",
    "access retail-shops": "Retail Shops",
    "access product-transfers": "Product Transfers",
    "access orders": "Orders",
    "manage manufactured orders": "Manufactured Orders Scope",
    "manage retail orders": "Retail Orders Scope",
    "access home-content": "Home Content",
    "access settings": "Settings"
  }
}
```

---

### 3.3 Create New Admin Account
Creates a new staff/admin user and syncs Spatie RBAC permissions.

- **HTTP Method:** `POST`
- **Endpoint:** `/api/v1/admin/admins`

#### Request Body
```json
{
  "name": "Factory Auditor",
  "email": "auditor@kanodia.com",
  "mobile_number": "9876543299",
  "password": "password123",
  "password_confirmation": "password123",
  "is_active": true,
  "permissions": [
    "access products",
    "access inventory",
    "access orders",
    "manage manufactured orders"
  ]
}
```

#### Order Permission Business Rule:
> If `"access orders"` is included in `permissions`, you **MUST** also select at least one order scope: `"manage manufactured orders"` or `"manage retail orders"`.

#### Success Response (`201 Created`)
```json
{
  "success": true,
  "message": "Admin account created successfully.",
  "data": {
    "id": 5,
    "name": "Factory Auditor",
    "email": "auditor@kanodia.com",
    "mobile_number": "9876543299",
    "is_active": true,
    "roles": ["admin"],
    "permissions": [
      "access products",
      "access inventory",
      "access orders",
      "manage manufactured orders"
    ],
    "created_at": "2026-07-29T14:20:00+00:00",
    "updated_at": "2026-07-29T14:20:00+00:00"
  }
}
```

---

### 3.4 Update Admin Account
Modifies an existing admin account's profile details and permissions.

- **HTTP Method:** `PUT` or `PATCH`
- **Endpoint:** `/api/v1/admin/admins/{id}`

#### Request Body
```json
{
  "name": "Factory Auditor (Senior)",
  "mobile_number": "9876543288",
  "is_active": true,
  "permissions": [
    "access products",
    "access inventory",
    "access orders",
    "manage manufactured orders",
    "manage retail orders"
  ]
}
```

> **Safety Rule:** Super Admin accounts (`role: super_admin`) cannot be edited. Returns `403 Forbidden`.

---

### 3.5 Toggle Admin Active Status
Quickly enables or restricts an admin user's access.

- **HTTP Method:** `PATCH`
- **Endpoint:** `/api/v1/admin/admins/{id}/toggle-status`

#### Success Response (`200 OK`)
```json
{
  "success": true,
  "message": "Admin account restricted successfully.",
  "data": {
    "id": 5,
    "name": "Factory Auditor (Senior)",
    "email": "auditor@kanodia.com",
    "is_active": false,
    "roles": ["admin"]
  }
}
```

---

### 3.6 Delete Admin Account
Deletes an admin user account.

- **HTTP Method:** `DELETE`
- **Endpoint:** `/api/v1/admin/admins/{id}`

#### Success Response (`200 OK`)
```json
{
  "success": true,
  "message": "Admin account deleted successfully.",
  "data": []
}
```

> **Safety Rule:** Super Admin accounts cannot be deleted. Returns `403 Forbidden`.

---

## 4. Standard Error Response Schema

All error responses return consistent JSON structures:

### Validation Error (`422 Unprocessable Entity`)
```json
{
  "success": false,
  "message": "Validation failed.",
  "errors": {
    "email": [
      "The email has already been taken."
    ],
    "password": [
      "The password confirmation does not match."
    ]
  }
}
```

### Unauthorized / Token Error (`401 Unauthorized`)
```json
{
  "success": false,
  "message": "Authentication token has expired.",
  "errors": {}
}
```

### Permission Error (`403 Forbidden`)
```json
{
  "success": false,
  "message": "Access denied. You do not have permission to access 'access products'.",
  "errors": {}
}
```
