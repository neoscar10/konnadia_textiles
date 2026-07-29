# Admin Product Management Mobile API Documentation & Integration Guide

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
| **List Products** | `GET /api/v1/admin/products` | `access products` |
| **Form Options / Metadata** | `GET /api/v1/admin/products/options` | `access products` |
| **View Product Details** | `GET /api/v1/admin/products/{id}` | `access products` |
| **Create Product** | `POST /api/v1/admin/products` | `access products` |
| **Update Product** | `PUT /api/v1/admin/products/{id}` | `access products` |
| **Toggle Status** | `PATCH /api/v1/admin/products/{id}/toggle-status` | `access products` |
| **Delete Product** | `DELETE /api/v1/admin/products/{id}` | `access products` |
| **Upload Product Media** | `POST /api/v1/admin/products/{id}/media` | `access products` |
| **Set Cover Image** | `PATCH /api/v1/admin/products/{id}/media/{media_id}/primary` | `access products` |
| **Delete Media** | `DELETE /api/v1/admin/products/{id}/media/{media_id}` | `access products` |
| **View Task Routing** | `GET /api/v1/admin/products/{id}/routing` | `access products` |
| **Configure Task Routing** | `POST /api/v1/admin/products/{id}/routing` | `access products` |

---

## 2. API Endpoints Specification

### 2.1 Fetch Form Options & Reference Data
Retrieve categories tree, customer discount levels, available tags, and manufacturing tasks required to populate mobile creation/edit form dropdowns.

- **HTTP Method**: `GET`
- **Full URL**: `https://konnadia.empoweredtechinnovations.org/api/v1/admin/products/options`
- **Response `200 OK`**:
```json
{
  "success": true,
  "data": {
    "categories": [
      {
        "id": 1,
        "title": "Shirts",
        "parent_id": null,
        "is_leaf": true,
        "default_product_config": {
          "base_price": "799.00",
          "hsn_code": "6205",
          "gst_percentage": "12.00",
          "minimum_order_quantity": 1,
          "product_type": "retail"
        }
      }
    ],
    "customer_levels": [
      {
        "id": 1,
        "name": "Wholesale Gold",
        "discount_percentage": 15.0
      }
    ],
    "tags": [
      {
        "id": 1,
        "name": "Cotton",
        "slug": "cotton"
      }
    ],
    "manufacturing_tasks": [
      {
        "id": 1,
        "name": "Cutting",
        "code": "CUT-01"
      },
      {
        "id": 2,
        "name": "Stitching",
        "code": "STITCH-01"
      }
    ],
    "product_types": [
      { "key": "retail", "label": "Retail Product" },
      { "key": "manufactured", "label": "Manufactured Product" }
    ],
    "default_units": {
      "level1_name": "Piece",
      "level1_code": "pcs"
    }
  }
}
```

---

### 2.2 List Products (Paginated with Filters)
List products with optional search, category filter, active status, product type, stock status, and pagination.

- **HTTP Method**: `GET`
- **Full URL**: `https://konnadia.empoweredtechinnovations.org/api/v1/admin/products`
- **Query Parameters**:
  - `search` *(optional string)*: Search by title or SKU.
  - `category_id` *(optional int)*: Filter by category ID.
  - `status` *(optional string)*: `active` or `inactive`.
  - `product_type` *(optional string)*: `retail` or `manufactured`.
  - `stock_status` *(optional string)*: `instock` or `outofstock`.
  - `per_page` *(optional int, default 10)*: Number of items per page.
  - `page` *(optional int, default 1)*: Page number.

- **Response `200 OK`**:
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "title": "Premium Oxford Cotton Shirt",
      "slug": "premium-oxford-cotton-shirt",
      "sku": "KT-OXF-001",
      "base_price": 799.0,
      "formatted_base_price": "₹799.00",
      "hsn_code": "6205",
      "gst_percentage": 12.0,
      "minimum_order_quantity": 5,
      "description": "High quality formal oxford shirt.",
      "is_active": true,
      "product_type": "retail",
      "stock_quantity": 100,
      "is_in_stock": true,
      "primary_image_url": "https://konnadia.empoweredtechinnovations.org/storage/products/shirt1.jpg",
      "categories": [
        { "id": 1, "title": "Shirts", "slug": "shirts" }
      ],
      "units": {
        "level1": { "name": "Piece", "short_code": "pcs" },
        "level2": { "name": "Box", "short_code": "box", "conversion_to_base": 10.0 }
      },
      "created_at": "30-Jul-2026 00:00",
      "updated_at": "30-Jul-2026 00:00"
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

### 2.3 Get Product Details
Retrieve complete detailed information for a single product.

- **HTTP Method**: `GET`
- **Full URL**: `https://konnadia.empoweredtechinnovations.org/api/v1/admin/products/{id}`
- **Response `200 OK`**:
```json
{
  "success": true,
  "data": {
    "id": 1,
    "title": "Premium Oxford Cotton Shirt",
    "slug": "premium-oxford-cotton-shirt",
    "sku": "KT-OXF-001",
    "base_price": 799.0,
    "formatted_base_price": "₹799.00",
    "hsn_code": "6205",
    "gst_percentage": 12.0,
    "minimum_order_quantity": 5,
    "description": "High quality formal oxford shirt.",
    "is_active": true,
    "product_type": "retail",
    "stock_quantity": 100,
    "categories": [
      { "id": 1, "title": "Shirts", "slug": "shirts" }
    ],
    "tags": [
      { "id": 1, "name": "Cotton", "slug": "cotton" }
    ],
    "media": [
      {
        "id": 1,
        "file_path": "products/shirt1.jpg",
        "file_url": "https://konnadia.empoweredtechinnovations.org/storage/products/shirt1.jpg",
        "file_type": "image",
        "mime_type": "image/jpeg",
        "size": 102400,
        "sort_order": 0,
        "is_primary": true
      }
    ],
    "units": {
      "level1_name": "Piece",
      "level1_code": "pcs",
      "level2_name": "Box",
      "level2_code": "box",
      "level2_conversion": 10.0
    },
    "customer_level_prices": [
      {
        "id": 1,
        "customer_level_id": 1,
        "customer_level_name": "Wholesale Gold",
        "discount_percentage": 20.0
      }
    ],
    "variation_groups": [
      {
        "id": 1,
        "name": "Color",
        "display_type": "text",
        "has_images": false,
        "sort_order": 0,
        "values": [
          {
            "id": 1,
            "value": "White",
            "color_hex": "#ffffff",
            "is_default": true,
            "sort_order": 0,
            "media": []
          }
        ]
      }
    ],
    "combinations": [
      {
        "id": 1,
        "sku": "KT-OXF-001-WHITE",
        "combination_values": { "Color": "White" },
        "price": 799.0,
        "stock_quantity": 100,
        "is_active": true
      }
    ],
    "routing_tasks": [],
    "created_at": "30-Jul-2026 00:00",
    "updated_at": "30-Jul-2026 00:00"
  }
}
```

---

### 2.4 Create Product
Create a new product with basic info, categories, tags, units, variation groups, combinations, and customer level discount overrides.

- **HTTP Method**: `POST`
- **Full URL**: `https://konnadia.empoweredtechinnovations.org/api/v1/admin/products`
- **Request Body (JSON)**:
```json
{
  "title": "Premium Oxford Cotton Shirt",
  "sku": "KT-OXF-001",
  "base_price": 799.00,
  "description": "High quality formal oxford shirt.",
  "hsn_code": "6205",
  "gst_percentage": 12.0,
  "minimum_order_quantity": 5,
  "product_type": "retail",
  "is_active": true,
  "stock_quantity": 100,
  "category_ids": [1],
  "tag_ids": [1],
  "units": {
    "level1_name": "Piece",
    "level1_code": "pcs",
    "level2_name": "Box",
    "level2_code": "box",
    "level2_conversion": 10
  },
  "customer_level_prices": [
    {
      "customer_level_id": 1,
      "discount_percentage": 20.0
    }
  ],
  "variation_groups": [
    {
      "name": "Color",
      "display_type": "text",
      "values": [
        { "value": "White", "color_hex": "#FFFFFF", "is_default": true },
        { "value": "Blue", "color_hex": "#0000FF", "is_default": false }
      ]
    }
  ],
  "combinations": [
    {
      "combination_values": { "Color": "White" },
      "sku": "KT-OXF-001-WHITE",
      "price": 799.00,
      "stock_quantity": 50,
      "is_active": true
    },
    {
      "combination_values": { "Color": "Blue" },
      "sku": "KT-OXF-001-BLUE",
      "price": 799.00,
      "stock_quantity": 50,
      "is_active": true
    }
  ]
}
```

- **Response `201 Created`**: Returns product detail object.

---

### 2.5 Update Product
Update an existing product's attributes.

- **HTTP Method**: `PUT` or `PATCH`
- **Full URL**: `https://konnadia.empoweredtechinnovations.org/api/v1/admin/products/{id}`
- **Request Body (JSON)**: Same structure as Create Product.
- **Response `200 OK`**: Returns updated product detail object.

---

### 2.6 Toggle Active Status
Quick toggle to activate or deactivate a product.

- **HTTP Method**: `PATCH`
- **Full URL**: `https://konnadia.empoweredtechinnovations.org/api/v1/admin/products/{id}/toggle-status`
- **Response `200 OK`**:
```json
{
  "success": true,
  "message": "Product deactivated successfully.",
  "data": {
    "id": 1,
    "is_active": false
  }
}
```

---

### 2.7 Delete Product
Soft delete a product.

- **HTTP Method**: `DELETE`
- **Full URL**: `https://konnadia.empoweredtechinnovations.org/api/v1/admin/products/{id}`
- **Response `200 OK`**:
```json
{
  "success": true,
  "message": "Product deleted successfully."
}
```

---

### 2.8 Upload Product Images/Media
Upload one or multiple images/videos for a product gallery.

- **HTTP Method**: `POST`
- **Full URL**: `https://konnadia.empoweredtechinnovations.org/api/v1/admin/products/{id}/media`
- **Header**: `Content-Type: multipart/form-data`
- **Form Data Parameters**:
  - `images[]`: File array (`image/jpeg`, `image/png`, `image/webp`, max 4MB per file).

- **Response `200 OK`**: Returns updated product detail object with uploaded media gallery.

---

### 2.9 Set Primary Cover Image
Set a specific media item as the primary cover image.

- **HTTP Method**: `PATCH`
- **Full URL**: `https://konnadia.empoweredtechinnovations.org/api/v1/admin/products/{id}/media/{media_id}/primary`
- **Response `200 OK`**: Returns updated product detail object.

---

### 2.10 Delete Media Item
Delete a single image from the product gallery.

- **HTTP Method**: `DELETE`
- **Full URL**: `https://konnadia.empoweredtechinnovations.org/api/v1/admin/products/{id}/media/{media_id}`
- **Response `200 OK`**: Returns updated product detail object.

---

### 2.11 View Manufacturing Task Routing
Get the production workflow tasks configured for a manufactured product.

- **HTTP Method**: `GET`
- **Full URL**: `https://konnadia.empoweredtechinnovations.org/api/v1/admin/products/{id}/routing`
- **Response `200 OK`**:
```json
{
  "success": true,
  "data": {
    "product_id": 1,
    "sku": "KT-MP-001",
    "product_title": "Manufactured Bed Sheet",
    "routing_tasks": [
      {
        "task_id": 1,
        "task_name": "Cutting",
        "sequence_number": 1,
        "standard_labor_rate": 20.0,
        "is_final_step": false
      },
      {
        "task_id": 2,
        "task_name": "Stitching",
        "sequence_number": 2,
        "standard_labor_rate": 25.0,
        "is_final_step": true
      }
    ]
  }
}
```

---

### 2.12 Configure Manufacturing Task Routing
Configure/save production workflow tasks, labor rates, sequence, and final step for a manufactured product.

- **HTTP Method**: `POST`
- **Full URL**: `https://konnadia.empoweredtechinnovations.org/api/v1/admin/products/{id}/routing`
- **Request Body (JSON)**:
```json
{
  "routing_tasks": [
    {
      "task_id": 1,
      "sequence_number": 1,
      "standard_labor_rate": 20.00,
      "is_final_step": false
    },
    {
      "task_id": 2,
      "sequence_number": 2,
      "standard_labor_rate": 25.00,
      "is_final_step": true
    }
  ]
}
```

- **Validation Rules**:
  - `routing_tasks`: Required array with at least 1 task step.
  - Exactly **ONE** task must have `"is_final_step": true`.
  - Task IDs must be unique per product routing.

- **Response `200 OK`**:
```json
{
  "success": true,
  "message": "Task routing configured successfully.",
  "data": {
    "product_id": 1,
    "sku": "KT-MP-001",
    "routing_tasks": [
      {
        "task_id": 1,
        "sequence_number": 1,
        "standard_labor_rate": 20.0,
        "is_final_step": false
      },
      {
        "task_id": 2,
        "sequence_number": 2,
        "standard_labor_rate": 25.0,
        "is_final_step": true
      }
    ]
  }
}
```
