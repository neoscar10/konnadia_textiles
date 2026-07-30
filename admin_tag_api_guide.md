# Admin Tag Management Mobile API Documentation & Integration Guide

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

## 1. Permission & Access Control Matrix

| Feature / Action | API Endpoint | Required Permission |
| :--- | :--- | :--- |
| **List Tags** | `GET /api/v1/admin/tags` | `access tags` |
| **Category Options & Tree** | `GET /api/v1/admin/tags/options` | `access tags` |
| **View Tag Details** | `GET /api/v1/admin/tags/{id}` | `access tags` |
| **Create Tag** | `POST /api/v1/admin/tags` | `access tags` |
| **Update Tag** | `PUT /api/v1/admin/tags/{id}` | `access tags` |
| **Delete Tag** | `DELETE /api/v1/admin/tags/{id}` | `access tags` |

---

## 2. API Endpoints Specification

### 2.1 Fetch Category Options & Tree for Tag Assignment
Retrieve the full category tree and leaf categories list to display the category selection picker when creating or editing a tag.

- **HTTP Method**: `GET`
- **Full URL**: `https://konnadia.empoweredtechinnovations.org/api/v1/admin/tags/options`
- **Response `200 OK`**:
```json
{
  "success": true,
  "data": {
    "category_tree": [
      {
        "id": 1,
        "name": "Apparel",
        "title": "Apparel",
        "slug": "apparel",
        "children": [
          {
            "id": 2,
            "name": "Formal Shirts",
            "title": "Formal Shirts",
            "slug": "formal-shirts",
            "children": []
          }
        ]
      }
    ],
    "leaf_categories": [
      {
        "id": 2,
        "name": "Formal Shirts",
        "title": "Formal Shirts",
        "full_path": "Apparel › Formal Shirts"
      }
    ]
  }
}
```

---

### 2.2 List Tags (Paginated with Search)
Retrieve paginated tags listing with optional search by tag name or slug. Each item includes assigned categories and total count of attached products.

- **HTTP Method**: `GET`
- **Full URL**: `https://konnadia.empoweredtechinnovations.org/api/v1/admin/tags`
- **Query Parameters**:
  - `search` *(optional string)*: Search term matching tag name or slug.
  - `per_page` *(optional int, default 10)*: Items per page.
  - `page` *(optional int, default 1)*: Page number.

- **Response `200 OK`**:
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "name": "Pure Linen",
      "slug": "pure-linen",
      "categories": [
        {
          "id": 2,
          "name": "Formal Shirts",
          "title": "Formal Shirts",
          "slug": "formal-shirts",
          "is_leaf": true,
          "full_path": "Apparel › Formal Shirts"
        }
      ],
      "products_count": 8,
      "created_at": "30-Jul-2026 00:18",
      "updated_at": "30-Jul-2026 00:18"
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

### 2.3 Get Tag Details
Retrieve detailed information for a single tag.

- **HTTP Method**: `GET`
- **Full URL**: `https://konnadia.empoweredtechinnovations.org/api/v1/admin/tags/{id}`
- **Response `200 OK`**:
```json
{
  "success": true,
  "data": {
    "id": 1,
    "name": "Pure Linen",
    "slug": "pure-linen",
    "categories": [
      {
        "id": 2,
        "name": "Formal Shirts",
        "title": "Formal Shirts",
        "slug": "formal-shirts",
        "is_leaf": true,
        "full_path": "Apparel › Formal Shirts"
      }
    ],
    "products_count": 8,
    "created_at": "30-Jul-2026 00:18",
    "updated_at": "30-Jul-2026 00:18"
  }
}
```

---

### 2.4 Create Tag
Create a new tag and associate it with one or multiple categories.

- **HTTP Method**: `POST`
- **Full URL**: `https://konnadia.empoweredtechinnovations.org/api/v1/admin/tags`
- **Request Body (JSON)**:
```json
{
  "name": "Organic Cotton",
  "category_ids": [1],
  "include_descendants": true
}
```

- **Parameters**:
  - `name` *(required string, max 100)*: Unique tag name.
  - `category_ids` *(required array of ints, min 1)*: List of category IDs to assign.
  - `include_descendants` *(optional boolean)*: If `true`, automatically expands parent category selection to include all subcategory IDs under those parents.

- **Response `201 Created`**: Returns created tag object.

---

### 2.5 Update Tag
Update an existing tag's name or assigned categories.

- **HTTP Method**: `PUT` or `PATCH`
- **Full URL**: `https://konnadia.empoweredtechinnovations.org/api/v1/admin/tags/{id}`
- **Request Body (JSON)**:
```json
{
  "name": "Premium Pure Linen",
  "category_ids": [2],
  "include_descendants": false
}
```

- **Response `200 OK`**: Returns updated tag object.

---

### 2.6 Delete Tag
Delete a tag. Automatically detaches the tag from all products (`product_tag`) and categories (`category_tag`) prior to deletion.

- **HTTP Method**: `DELETE`
- **Full URL**: `https://konnadia.empoweredtechinnovations.org/api/v1/admin/tags/{id}`
- **Response `200 OK`**:
```json
{
  "success": true,
  "message": "Tag deleted successfully."
}
```
