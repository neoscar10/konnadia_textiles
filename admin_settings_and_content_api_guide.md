# Admin Settings & Content Management Mobile API Integration Guide
*(Home Content CMS, Staff & Admin Users, System Settings, and Contact Messages & Support)*

**Base URL**: `https://konnadia.empoweredtechinnovations.org/`  
**API Prefix**: `/api/v1/admin`  
**Authentication**: Bearer Token (`Authorization: Bearer <token>`) issued via `POST /api/v1/admin/auth/login`.  
**Required Headers**:
```http
Authorization: Bearer <admin_jwt_token>
Accept: application/json
Content-Type: application/json
```

---

# TABLE OF CONTENTS
1. [Section 1: Home Content Management (`/admin/home-content`)](#section-1-home-content-management-adminhome-content)
2. [Section 2: Staff & Admin Users Management (`/admin/admins`)](#section-2-staff--admin-users-management-adminadmins)
3. [Section 3: System Settings & Profile (`/admin/settings`)](#section-3-system-settings--profile-adminsettings)
4. [Section 4: Contact Messages & Support (`/admin/contact-messages`)](#section-4-contact-messages--support-admincontact-messages)

---

# SECTION 1: HOME CONTENT MANAGEMENT (`/admin/home-content`)

## 1.1 Permission & Access Control
- **Required Permission**: `access home-content`

## 1.2 Endpoints Specification

### 1.2.1 Get Home Content Statistics
Retrieve summary metric cards for home content sections.

- **HTTP Method**: `GET`
- **Full URL**: `https://konnadia.empoweredtechinnovations.org/api/v1/admin/home-content/stats`
- **Response `200 OK`**:
```json
{
  "success": true,
  "data": {
    "total_sections": 8,
    "active_sections": 6,
    "inactive_sections": 2,
    "scheduled_sections": 1
  }
}
```

---

### 1.2.2 List Home Content Sections (Paginated with Filters)
Retrieve paginated list of home content sections with position ordering.

- **HTTP Method**: `GET`
- **Full URL**: `https://konnadia.empoweredtechinnovations.org/api/v1/admin/home-content`
- **Query Parameters**:
  - `search` *(optional string)*: Search by section title.
  - `status` *(optional string)*: `active` or `inactive`.
  - `type` *(optional string)*: `banner`, `banner_slider`, `image_slider`, `category_slider`, `product_slider`, `image_text_card`.
  - `per_page` *(optional int, default 10)*: Items per page.
  - `page` *(optional int, default 1)*: Page number.

- **Response `200 OK`**:
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "type": "banner",
      "title": "Festive Collection 2026",
      "subtitle": "Exclusive Indian Sarees",
      "position": 1,
      "is_active": true,
      "items_count": 1,
      "starts_at": null,
      "ends_at": null,
      "created_at": "30-Jul-2026 19:52"
    }
  ],
  "pagination": {
    "total": 1,
    "count": 1,
    "per_page": 10,
    "current_page": 1,
    "total_pages": 1
  }
}
```

---

### 1.2.3 Fetch Options & Pickers Reference Data
Get categories, product pickers list, and section types to build the mobile creation wizard.

- **HTTP Method**: `GET`
- **Full URL**: `https://konnadia.empoweredtechinnovations.org/api/v1/admin/home-content/options`
- **Query Parameters**:
  - `product_search` *(optional string)*: Filter product picker.
  - `category_id` *(optional int)*: Filter product picker by category.

- **Response `200 OK`**:
```json
{
  "success": true,
  "data": {
    "section_types": [
      { "key": "banner", "label": "Single Hero Banner" },
      { "key": "banner_slider", "label": "Hero Banner Slider" },
      { "key": "image_slider", "label": "Image Carousel" },
      { "key": "category_slider", "label": "Category Grid / Slider" },
      { "key": "product_slider", "label": "Product Grid / Slider" },
      { "key": "image_text_card", "label": "Rich Image & Text Card" }
    ],
    "categories": [
      { "id": 1, "title": "Sarees", "slug": "sarees" }
    ],
    "products": [
      {
        "id": 10,
        "title": "Designer Silk Saree",
        "sku": "KT-SAREE-01",
        "base_price": 2500.0,
        "stock_quantity": 40,
        "primary_image_url": "https://konnadia.empoweredtechinnovations.org/storage/products/saree.jpg"
      }
    ]
  }
}
```

---

### 1.2.4 Get Section Details
- **HTTP Method**: `GET`
- **Full URL**: `https://konnadia.empoweredtechinnovations.org/api/v1/admin/home-content/{id}`

---

### 1.2.5 Create Home Content Section
- **HTTP Method**: `POST`
- **Full URL**: `https://konnadia.empoweredtechinnovations.org/api/v1/admin/home-content`
- **Request Body (JSON)**:
```json
{
  "type": "banner",
  "title": "Festive Collection 2026",
  "subtitle": "Exclusive Indian Sarees",
  "is_active": true,
  "display_style": "full_width",
  "items_per_view": 1,
  "display_limit": 10,
  "items": [
    {
      "item_type": "banner",
      "cta_label": "Shop Now",
      "image_path": "banners/hero.jpg",
      "link_type": "category",
      "link_category_id": 1
    }
  ]
}
```

---

### 1.2.6 Update Section
- **HTTP Method**: `PUT` or `PATCH`
- **Full URL**: `https://konnadia.empoweredtechinnovations.org/api/v1/admin/home-content/{id}`

---

### 1.2.7 Reorder Home Content Sections
Reorder positions of home content sections.

- **HTTP Method**: `POST`
- **Full URL**: `https://konnadia.empoweredtechinnovations.org/api/v1/admin/home-content/reorder`
- **Request Body (JSON)**:
```json
{
  "ordered_ids": [3, 1, 2, 4]
}
```

---

### 1.2.8 Toggle Active Status
- **HTTP Method**: `PATCH`
- **Full URL**: `https://konnadia.empoweredtechinnovations.org/api/v1/admin/home-content/{id}/toggle-status`

---

### 1.2.9 Delete Section
- **HTTP Method**: `DELETE`
- **Full URL**: `https://konnadia.empoweredtechinnovations.org/api/v1/admin/home-content/{id}`

---

# SECTION 2: STAFF & ADMIN USERS MANAGEMENT (`/admin/admins`)

## 2.1 Permission & Access Control
- **Required Permission**: `access admins`

## 2.2 Endpoints Specification

### 2.2.1 Fetch Permissions Checklist
Retrieve the list of all available system permissions for assigning to admin accounts.

- **HTTP Method**: `GET`
- **Full URL**: `https://konnadia.empoweredtechinnovations.org/api/v1/admin/admins/permissions-list`
- **Response `200 OK`**:
```json
{
  "success": true,
  "data": [
    { "name": "access dashboard", "label": "Dashboard" },
    { "name": "access customers", "label": "Customers" },
    { "name": "access customer-levels", "label": "Customer Levels" },
    { "name": "access products", "label": "Products" },
    { "name": "access categories", "label": "Categories" },
    { "name": "access inventory", "label": "Inventory" },
    { "name": "access retail-shops", "label": "Retail Shops" },
    { "name": "access product-transfers", "label": "Product Transfers" },
    { "name": "access orders", "label": "Orders" },
    { "name": "access home-content", "label": "Home Content" },
    { "name": "access settings", "label": "Settings" }
  ]
}
```

---

### 2.2.2 List Admin Users (Paginated with Filters)
Retrieve paginated list of staff admin accounts (excluding Super Admins).

- **HTTP Method**: `GET`
- **Full URL**: `https://konnadia.empoweredtechinnovations.org/api/v1/admin/admins`
- **Query Parameters**:
  - `search` *(optional string)*: Search by name, email, or mobile number.
  - `status` *(optional string)*: `1` (active) or `0` (restricted).

- **Response `200 OK`**:
```json
{
  "success": true,
  "data": [
    {
      "id": 5,
      "name": "Manager Staff",
      "email": "manager_staff@konnadia.com",
      "mobile_number": "+91 9998887776",
      "is_active": true,
      "permissions": ["access products", "access orders"],
      "created_at": "30-Jul-2026 19:52"
    }
  ]
}
```

---

### 2.2.3 Get Single Admin Details
- **HTTP Method**: `GET`
- **Full URL**: `https://konnadia.empoweredtechinnovations.org/api/v1/admin/admins/{id}`

---

### 2.2.4 Create Admin Account
Create a new admin staff user with permission assignments.

- **HTTP Method**: `POST`
- **Full URL**: `https://konnadia.empoweredtechinnovations.org/api/v1/admin/admins`
- **Request Body (JSON)**:
```json
{
  "name": "Manager Staff",
  "email": "manager_staff@konnadia.com",
  "mobile_number": "+91 9998887776",
  "password": "secretpassword",
  "password_confirmation": "secretpassword",
  "is_active": true,
  "permissions": ["access products", "access orders", "access inventory"]
}
```

- **Response `201 Created`**: Returns created admin user resource object.

---

### 2.2.5 Update Admin Account
- **HTTP Method**: `PUT` or `PATCH`
- **Full URL**: `https://konnadia.empoweredtechinnovations.org/api/v1/admin/admins/{id}`

---

### 2.2.6 Restrict / Enable Admin Account (Toggle Status)
- **HTTP Method**: `PATCH`
- **Full URL**: `https://konnadia.empoweredtechinnovations.org/api/v1/admin/admins/{id}/toggle-status`

---

### 2.2.7 Delete Admin Account
- **HTTP Method**: `DELETE`
- **Full URL**: `https://konnadia.empoweredtechinnovations.org/api/v1/admin/admins/{id}`

---

# SECTION 3: SYSTEM SETTINGS & PROFILE (`/admin/settings`)

## 3.1 Permission & Access Control
- **Required Permission**: Authenticated Admin

## 3.2 Endpoints Specification

### 3.2.1 Get Current Admin Profile & Settings
Get current logged in administrator profile details, roles, and granted permissions.

- **HTTP Method**: `GET`
- **Full URL**: `https://konnadia.empoweredtechinnovations.org/api/v1/admin/settings`
- **Response `200 OK`**:
```json
{
  "success": true,
  "data": {
    "id": 1,
    "name": "Super Admin",
    "email": "super_admin@konnadia.com",
    "mobile_number": "+91 9876543210",
    "roles": ["super_admin"],
    "permissions": ["access dashboard", "access orders", "access inventory"],
    "is_super_admin": true,
    "created_at": "01-Jan-2026 00:00"
  }
}
```

---

### 3.2.2 Change Password
Update authenticated administrator password.

- **HTTP Method**: `POST`
- **Full URL**: `https://konnadia.empoweredtechinnovations.org/api/v1/admin/settings/change-password`
- **Request Body (JSON)**:
```json
{
  "current_password": "oldpassword123",
  "new_password": "newpassword123",
  "new_password_confirmation": "newpassword123"
}
```

- **Response `200 OK`**:
```json
{
  "success": true,
  "message": "Password changed successfully."
}
```

---

# SECTION 4: CONTACT MESSAGES & SUPPORT (`/admin/contact-messages`)

## 4.1 Permission & Access Control
- **Required Permission**: `access contact-messages`

## 4.2 Endpoints Specification

### 4.2.1 Get Contact Messages Statistics
Retrieve summary counters of unread vs read customer support inquiries.

- **HTTP Method**: `GET`
- **Full URL**: `https://konnadia.empoweredtechinnovations.org/api/v1/admin/contact-messages/stats`
- **Response `200 OK`**:
```json
{
  "success": true,
  "data": {
    "total_messages": 120,
    "unread_messages": 8,
    "read_messages": 112
  }
}
```

---

### 4.2.2 List Contact Messages (Paginated with Filters)
Retrieve paginated list of customer contact messages.

- **HTTP Method**: `GET`
- **Full URL**: `https://konnadia.empoweredtechinnovations.org/api/v1/admin/contact-messages`
- **Query Parameters**:
  - `search` *(optional string)*: Search by sender name, email, subject, or message text.
  - `status` *(optional string)*: `read` or `unread`.
  - `per_page` *(optional int, default 10)*: Items per page.
  - `page` *(optional int, default 1)*: Page number.

- **Response `200 OK`**:
```json
{
  "success": true,
  "data": [
    {
      "id": 15,
      "name": "Aarav Mehta",
      "email": "aarav@gmail.com",
      "phone": "+91 9112233445",
      "subject": "Wholesale Inquiry",
      "message": "Interested in bulk orders of silk sarees.",
      "is_read": false,
      "created_at": "30-Jul-2026 19:52"
    }
  ],
  "pagination": {
    "total": 1,
    "count": 1,
    "per_page": 10,
    "current_page": 1,
    "total_pages": 1
  }
}
```

---

### 4.2.3 View Message Detail (Auto Mark Read)
Fetching message detail automatically sets `is_read = true`.

- **HTTP Method**: `GET`
- **Full URL**: `https://konnadia.empoweredtechinnovations.org/api/v1/admin/contact-messages/{id}`

---

### 4.2.4 Mark Message as Unread
- **HTTP Method**: `PATCH`
- **Full URL**: `https://konnadia.empoweredtechinnovations.org/api/v1/admin/contact-messages/{id}/mark-unread`

---

### 4.2.5 Delete Contact Message
- **HTTP Method**: `DELETE`
- **Full URL**: `https://konnadia.empoweredtechinnovations.org/api/v1/admin/contact-messages/{id}`
