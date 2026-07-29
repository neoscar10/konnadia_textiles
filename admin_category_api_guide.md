# Admin Category Management Mobile API Documentation & Integration Guide

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
| **Fetch Category Tree** | `GET /api/v1/admin/categories/tree` | `access categories` |
| **List Folder Categories** | `GET /api/v1/admin/categories` | `access categories` |
| **View Category Details** | `GET /api/v1/admin/categories/{id}` | `access categories` |
| **Create Category / Folder / Leaf** | `POST /api/v1/admin/categories` | `access categories` |
| **Update Category** | `PUT /api/v1/admin/categories/{id}` | `access categories` |
| **Toggle Active Status** | `PATCH /api/v1/admin/categories/{id}/toggle-status` | `access categories` |
| **Delete Category** | `DELETE /api/v1/admin/categories/{id}` | `access categories` |
| **Fetch Category Defaults** | `GET /api/v1/admin/categories/{id}/defaults` | `access categories` |
| **Save Category Defaults** | `POST /api/v1/admin/categories/{id}/defaults` | `access categories` |
| **Move Category Products** | `POST /api/v1/admin/categories/{id}/move-products` | `access categories` |
| **List Attached Products** | `GET /api/v1/admin/categories/{id}/products` | `access categories` |

---

## 2. API Endpoints Specification

### 2.1 Fetch Complete Recursive Category Tree
Retrieve the full nested category hierarchy for tree view navigation on mobile.

- **HTTP Method**: `GET`
- **Full URL**: `https://konnadia.empoweredtechinnovations.org/api/v1/admin/categories/tree`
- **Response `200 OK`**:
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "parent_id": null,
      "name": "Fabrics",
      "title": "Fabrics",
      "slug": "fabrics",
      "description": "All fabric categories",
      "is_active": true,
      "is_leaf": false,
      "sort_order": 1,
      "children": [
        {
          "id": 2,
          "parent_id": 1,
          "name": "Silk Fabrics",
          "title": "Silk Fabrics",
          "slug": "silk-fabrics",
          "description": "Silk range",
          "is_active": true,
          "is_leaf": true,
          "sort_order": 1,
          "children": []
        }
      ]
    }
  ]
}
```

---

### 2.2 List Folder Contents & Breadcrumbs
Retrieve direct sub-categories under a folder with breadcrumb path navigation. Pass `parent_id` parameter (`null` or omitted for root categories).

- **HTTP Method**: `GET`
- **Full URL**: `https://konnadia.empoweredtechinnovations.org/api/v1/admin/categories`
- **Query Parameters**:
  - `parent_id` *(optional int)*: Folder ID to list contents of (`null` for root level).
  - `search` *(optional string)*: Search by category name or description.

- **Response `200 OK`**:
```json
{
  "success": true,
  "data": {
    "current_category": {
      "id": 1,
      "name": "Fabrics",
      "slug": "fabrics",
      "is_leaf": false,
      "full_path": "Fabrics"
    },
    "breadcrumbs": [
      { "id": 1, "name": "Fabrics" }
    ],
    "categories": [
      {
        "id": 2,
        "parent_id": 1,
        "name": "Silk Fabrics",
        "title": "Silk Fabrics",
        "slug": "silk-fabrics",
        "description": "Silk range",
        "is_active": true,
        "is_leaf": true,
        "sort_order": 1,
        "full_path": "Fabrics › Silk Fabrics",
        "children_count": 0,
        "products_count": 15
      }
    ]
  }
}
```

---

### 2.3 Get Category Details
Retrieve complete information for a single category.

- **HTTP Method**: `GET`
- **Full URL**: `https://konnadia.empoweredtechinnovations.org/api/v1/admin/categories/{id}`
- **Response `200 OK`**:
```json
{
  "success": true,
  "data": {
    "id": 2,
    "parent_id": 1,
    "name": "Silk Fabrics",
    "title": "Silk Fabrics",
    "slug": "silk-fabrics",
    "description": "Pure silk fabric range",
    "is_active": true,
    "is_leaf": true,
    "sort_order": 1,
    "full_path": "Fabrics › Silk Fabrics",
    "children_count": 0,
    "products_count": 15,
    "default_product_config": {
      "base_price": "1200.00",
      "hsn_code": "5007",
      "gst_percentage": "12.00",
      "minimum_order_quantity": 10,
      "product_type": "retail"
    }
  }
}
```

---

### 2.4 Create Category / Folder / Leaf
Create a new category folder or leaf category.

- **HTTP Method**: `POST`
- **Full URL**: `https://konnadia.empoweredtechinnovations.org/api/v1/admin/categories`
- **Request Body (JSON)**:
```json
{
  "name": "Cotton Fabrics",
  "parent_id": 1,
  "description": "Pure cotton fabric range",
  "is_active": true,
  "is_leaf": true,
  "sort_order": 2
}
```

- **Validation Rules**:
  - Cannot create sub-categories inside a category marked as `is_leaf: true`.
  - Duplicate category names under the same parent are rejected.

- **Response `201 Created`**: Returns category resource object.

---

### 2.5 Update Category
Update an existing category's name, description, active status, or leaf flag.

- **HTTP Method**: `PUT` or `PATCH`
- **Full URL**: `https://konnadia.empoweredtechinnovations.org/api/v1/admin/categories/{id}`
- **Request Body (JSON)**:
```json
{
  "name": "Pure Silk Fabrics",
  "description": "Updated description",
  "is_active": true,
  "is_leaf": true
}
```

- **Validation Rules**:
  - Cannot change `is_leaf` from `true` to `false` if products are attached to the category. Move or delete products first.

- **Response `200 OK`**: Returns updated category resource object.

---

### 2.6 Toggle Active Status
Quick toggle to activate or deactivate a category.

- **HTTP Method**: `PATCH`
- **Full URL**: `https://konnadia.empoweredtechinnovations.org/api/v1/admin/categories/{id}/toggle-status`
- **Response `200 OK`**:
```json
{
  "success": true,
  "message": "Category deactivated successfully.",
  "data": {
    "id": 2,
    "is_active": false
  }
}
```

---

### 2.7 Delete Category (With Leaf Safety)
Delete a category folder or leaf category.

- **HTTP Method**: `DELETE`
- **Full URL**: `https://konnadia.empoweredtechinnovations.org/api/v1/admin/categories/{id}`
- **Query / Body Parameters (Optional for Leaf Safety)**:
  - `action` *(optional string)*: `move_products` or `delete_products`.
  - `target_category_id` *(optional int, required if action is move_products)*: Target leaf category ID to transfer products to before deletion.

#### Leaf Safety Logic:
If a leaf category has attached products and no action is specified, the API returns a `422` HTTP response prompting the user to select an action:
```json
{
  "success": false,
  "message": "Category contains 5 product(s). Specify action='move_products' with target_category_id or action='delete_products'.",
  "requires_leaf_safety": true,
  "product_count": 5
}
```

- **Response `200 OK` (when safe or action provided)**:
```json
{
  "success": true,
  "message": "Category deleted successfully."
}
```

---

### 2.8 Get Category Product Defaults
Retrieve default product parameters configured for a category.

- **HTTP Method**: `GET`
- **Full URL**: `https://konnadia.empoweredtechinnovations.org/api/v1/admin/categories/{id}/defaults`
- **Response `200 OK`**:
```json
{
  "success": true,
  "data": {
    "category_id": 2,
    "category_name": "Silk Fabrics",
    "defaults": {
      "base_price": "1200.00",
      "description": "Default silk description",
      "hsn_code": "5007",
      "gst_percentage": "12.00",
      "minimum_order_quantity": 10,
      "product_type": "retail",
      "units": {
        "level1_name": "Meter",
        "level1_code": "m",
        "level2_name": "Roll",
        "level2_code": "roll",
        "level2_conversion": 100.0
      },
      "pricingOverrides": {
        "1": 15.0
      }
    }
  }
}
```

---

### 2.9 Configure Category Product Defaults
Save default template parameters (base price, HSN, GST %, MOQ, unit conversions, customer level discount overrides) for a category.

- **HTTP Method**: `POST`
- **Full URL**: `https://konnadia.empoweredtechinnovations.org/api/v1/admin/categories/{id}/defaults`
- **Request Body (JSON)**:
```json
{
  "base_price": 1200.00,
  "description": "Default silk description",
  "hsn_code": "5007",
  "gst_percentage": 12.0,
  "minimum_order_quantity": 10,
  "product_type": "retail",
  "units": {
    "level1_name": "Meter",
    "level1_code": "m",
    "level2_name": "Roll",
    "level2_code": "roll",
    "level2_conversion": 100
  },
  "pricingOverrides": {
    "1": 15.0
  }
}
```

- **Response `200 OK`**: Returns updated defaults object.

---

### 2.10 Move Products to Another Leaf Category
Transfer all attached products from source leaf category to target leaf category.

- **HTTP Method**: `POST`
- **Full URL**: `https://konnadia.empoweredtechinnovations.org/api/v1/admin/categories/{id}/move-products`
- **Request Body (JSON)**:
```json
{
  "target_category_id": 3
}
```

- **Response `200 OK`**:
```json
{
  "success": true,
  "message": "Products moved successfully to target category."
}
```

---

### 2.11 Get Attached Products for Leaf Category
Retrieve paginated list of products attached to a leaf category with optional search and filters.

- **HTTP Method**: `GET`
- **Full URL**: `https://konnadia.empoweredtechinnovations.org/api/v1/admin/categories/{id}/products`
- **Query Parameters**:
  - `search` *(optional string)*: Search by title or SKU.
  - `status` *(optional string)*: `active` or `inactive`.
  - `stock_status` *(optional string)*: `instock` or `outofstock`.
  - `per_page` *(optional int, default 15)*: Number of items per page.
  - `page` *(optional int, default 1)*: Page number.

- **Response `200 OK`**: Returns paginated products list array.
