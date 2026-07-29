# Admin Design Catalog Mobile API Documentation & Integration Guide

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
| **Design Catalog Visual Grid** | `GET /api/v1/admin/design-catalog` | `access design-catalog` |
| **Filter Options Metadata** | `GET /api/v1/admin/design-catalog/options` | `access design-catalog` |
| **Share Filtered Catalog** | `POST /api/v1/admin/design-catalog/share` | `access design-catalog` |

---

## 2. API Endpoints Specification

### 2.1 Fetch Design Catalog Options & Dropdown Metadata
Retrieve leaf categories with full breadcrumb paths and available tags to populate design catalog filter selectors on mobile.

- **HTTP Method**: `GET`
- **Full URL**: `https://konnadia.empoweredtechinnovations.org/api/v1/admin/design-catalog/options`
- **Response `200 OK`**:
```json
{
  "success": true,
  "data": {
    "leaf_categories": [
      {
        "id": 1,
        "title": "Shirts",
        "full_path": "Home > Products > Shirts"
      }
    ],
    "tags": [
      {
        "id": 1,
        "name": "Cotton",
        "slug": "cotton"
      }
    ]
  }
}
```

---

### 2.2 List Design Catalog (Visual Grid Preview with Stock Badges)
Retrieve high-fidelity product design grid items pre-formatted with image preview, full category breadcrumb path, and computed stock availability status.

- **HTTP Method**: `GET`
- **Full URL**: `https://konnadia.empoweredtechinnovations.org/api/v1/admin/design-catalog`
- **Query Parameters**:
  - `search` *(optional string)*: Search by title, SKU, or description.
  - `category_id` *(optional int)*: Filter by leaf category ID.
  - `tag_id` *(optional int)*: Filter by tag ID.
  - `per_page` *(optional int, default 10)*: Number of designs per page.
  - `page` *(optional int, default 1)*: Page number.

- **Response `200 OK`**:
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "title": "Royal Gold Embroidered Silk",
      "slug": "royal-gold-embroidered-silk",
      "sku": "KT-EMB-001",
      "base_price": 2499.0,
      "formatted_base_price": "₹2,499.00",
      "description": "Exquisite gold embroidered silk fabric design.",
      "is_active": true,
      "primary_image_url": "https://konnadia.empoweredtechinnovations.org/storage/products/silk1.jpg",
      "category_paths": [
        "Home > Products > Embroidery Fabrics"
      ],
      "categories": [
        { "id": 1, "title": "Embroidery Fabrics", "slug": "embroidery-fabrics" }
      ],
      "tags": [
        { "id": 1, "name": "Handcrafted", "slug": "handcrafted" }
      ],
      "stock_details": {
        "computed_stock": 25,
        "is_unlimited": false,
        "stock_status": "in_stock",
        "stock_label": "25 In Stock"
      },
      "created_at": "30-Jul-2026 00:30"
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

#### Stock Status Badges Values:
- `in_stock`: Stock count > 10. `stock_label`: e.g. `"25 In Stock"`.
- `low_stock`: Stock count between 1 and 10. `stock_label`: e.g. `"5 Low Stock"`.
- `out_of_stock`: Stock count is 0. `stock_label`: `"Out of Stock"`.
- `unlimited`: Manufactured product or null stock. `stock_label`: `"Unlimited"`.

---

### 2.3 Generate Shareable Filtered Catalog Link
Generate a shareable web link pre-filtered by search term, category, and tag to share with customers via WhatsApp, Email, or SMS.

- **HTTP Method**: `POST`
- **Full URL**: `https://konnadia.empoweredtechinnovations.org/api/v1/admin/design-catalog/share`
- **Request Body (JSON)**:
```json
{
  "search": "Royal",
  "category_id": 1,
  "tag_id": 1
}
```

- **Response `200 OK`**:
```json
{
  "success": true,
  "data": {
    "share_url": "https://konnadia.empoweredtechinnovations.org/portal/products?search=Royal&category=1&selectedTags%5B0%5D=1",
    "share_text": "Check out our latest textile design catalog on Kanodia Textiles: https://konnadia.empoweredtechinnovations.org/portal/products?search=Royal&category=1&selectedTags%5B0%5D=1",
    "applied_filters": {
      "search": "Royal",
      "category": 1,
      "selectedTags": [1]
    }
  }
}
```
