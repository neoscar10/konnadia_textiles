# Kannodia Textiles Mobile App API Implementation Guide
## Module: Manufacturing Products & Product Categories Management

---

## 1. Overview & Context

The **Manufacturing Products & Categories** module manages factory production items, Bill of Materials (BOM) patterns, task routing sequences, subsidiary/stitching raw materials, and category templates. It enables mobile app developers to implement forms for defining factory products (e.g., *King Size Bedsheet*, *Pillowcase Standard*), assigning default task workflows (Cutting, Stitching, Quality Check), configuring pattern fabric dimensions, and linking WIP manufacturing items to storefront retail products.

### Key Features Covered:
1. **Product Categories**: Category directory, default task routing sequences, and active category pickers.
2. **Manufacturing Product Catalog**: Product directory with category filtering, status toggling (`active` / `inactive`), pattern fabric width/length specs, and BOM raw material consumption.
3. **Form Lookup Options**: Comprehensive `/options` endpoints returning auto-generated product code previews (`MP-2026-xxxx`), active categories, fabric width options, raw material lists, length units, and storefront product combinations.
4. **CRUD Management**: Create, update, soft-delete, and toggle active statuses for products and categories.

---

## 2. Global Authentication & API Standards

* **Base URL**: `https://konnadia.empoweredtechinnovations.org/api/v1` (or local environment `http://localhost/api/v1`)
* **Route Aliases Available**:
  - **Manufacturing Products**: `/factory/products`, `/production/products`, `/production/manufacturing-products`, `/admin/production/manufacturing-products`
  - **Product Categories**: `/factory/product-categories`, `/production/product-categories`, `/admin/production/product-categories`
* **Headers Required**:
  - `Authorization`: `Bearer <JWT_BEARER_TOKEN>`
  - `Accept`: `application/json`
  - `Content-Type`: `application/json`
* **Permission Required**: `access production` or `super_admin` role.

---

## 3. Endpoints Reference: Product Categories

### A. List Product Categories

Retrieve all manufacturing product categories with search, status filtering, and optional pagination.

* **HTTP Method**: `GET`
* **Route**: `/factory/product-categories` (Aliases: `/production/product-categories`, `/admin/production/product-categories`)

#### Query Parameters
| Parameter | Type | Default | Description |
| :--- | :--- | :--- | :--- |
| `search` | `string` | `null` | Filter by category name |
| `status` | `string` | `"all"` | Filter by status (`"active"`, `"inactive"`, `"all"`) |
| `paginate` | `boolean` | `false` | Pass `"true"` to enable pagination |
| `page` | `integer` | `1` | Page number |
| `per_page` | `integer` | `15` | Items per page (1 to 100) |

#### Response Example (`200 OK`)
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "name": "Bedsheet Sets",
      "status": true,
      "status_label": "Active",
      "manufacturing_products_count": 8,
      "default_tasks": [
        {
          "id": 1,
          "task_id": 1,
          "task_code": "TSK-CUT",
          "task_name": "Cutting Stage",
          "sequence_number": 1,
          "is_final_step": false,
          "standard_labor_rate": 12.0
        },
        {
          "id": 2,
          "task_id": 2,
          "task_code": "TSK-STITCH",
          "task_name": "Stitching Stage",
          "sequence_number": 2,
          "is_final_step": true,
          "standard_labor_rate": 15.5
        }
      ],
      "created_at": "2026-09-24T18:00:00Z"
    }
  ]
}
```

---

### B. Category Lookup Options

Fetch active categories for dropdown pickers.

* **HTTP Method**: `GET`
* **Route**: `/factory/product-categories/options` (Aliases: `/production/product-categories/options`, `/admin/production/product-categories/options`)

#### Response Example (`200 OK`)
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "name": "Bedsheet Sets",
      "status": true,
      "default_tasks": [ ... ]
    }
  ]
}
```

---

### C. Show Category Details

Fetch details of a single category with default task routing sequence.

* **HTTP Method**: `GET`
* **Route**: `/factory/product-categories/{id}` (Aliases: `/production/product-categories/{id}`, `/admin/production/product-categories/{id}`)

#### Response Example (`200 OK`)
```json
{
  "success": true,
  "data": {
    "id": 1,
    "name": "Bedsheet Sets",
    "status": true,
    "manufacturing_products_count": 8,
    "default_tasks": [ ... ]
  }
}
```

---

### D. Create Product Category

Create a new manufacturing product category with optional default task sequence.

* **HTTP Method**: `POST`
* **Route**: `/factory/product-categories` (Aliases: `/production/product-categories`, `/admin/production/product-categories`)

#### Payload Specification
```json
{
  "name": "Pillowcases & Covers",
  "status": true,
  "default_tasks": [
    {
      "task_id": 1,
      "sequence_number": 1,
      "standard_labor_rate": 8.50,
      "is_final_step": false
    },
    {
      "task_id": 2,
      "sequence_number": 2,
      "standard_labor_rate": 10.00,
      "is_final_step": true
    }
  ]
}
```

#### Response Example (`201 Created`)
```json
{
  "success": true,
  "message": "Manufacturing product category \"Pillowcases & Covers\" created successfully.",
  "data": {
    "id": 2,
    "name": "Pillowcases & Covers",
    "status": true,
    "manufacturing_products_count": 0,
    "default_tasks": [ ... ]
  }
}
```

---

### E. Update Product Category

* **HTTP Method**: `PUT` or `PATCH`
* **Route**: `/factory/product-categories/{id}` (Aliases: `/production/product-categories/{id}`, `/admin/production/product-categories/{id}`)

---

### F. Toggle Category Status

* **HTTP Method**: `PATCH`
* **Route**: `/factory/product-categories/{id}/toggle-status` (Aliases: `/production/product-categories/{id}/toggle-status`, `/admin/production/product-categories/{id}/toggle-status`)

---

### G. Delete Product Category

Soft-delete a category. **Fails with HTTP 422 if category is linked to manufacturing products.**

* **HTTP Method**: `DELETE`
* **Route**: `/factory/product-categories/{id}` (Aliases: `/production/product-categories/{id}`, `/admin/production/product-categories/{id}`)

#### Error Response (`422 Unprocessable Entity`)
```json
{
  "success": false,
  "message": "Cannot delete \"Bedsheet Sets\" — it is linked to 8 manufacturing product(s). Deactivate it instead.",
  "linked_products_count": 8
}
```

---

## 4. Endpoints Reference: Manufacturing Products

### A. List Manufacturing Products

Retrieve paginated manufacturing products with category, pattern, and task routing details.

* **HTTP Method**: `GET`
* **Route**: `/factory/products` (Aliases: `/production/products`, `/production/manufacturing-products`, `/admin/production/manufacturing-products`)

#### Query Parameters
| Parameter | Type | Default | Description |
| :--- | :--- | :--- | :--- |
| `search` | `string` | `null` | Search by product name or code (`MP-2026-xxxx`) |
| `category_id` | `integer` | `null` | Filter by category ID |
| `status` | `string` | `"all"` | Filter by status (`"active"`, `"inactive"`, `"all"`) |
| `page` | `integer` | `1` | Page number |
| `per_page` | `integer` | `15` | Items per page (1 to 100) |

#### Response Example (`200 OK`)
```json
{
  "success": true,
  "data": [
    {
      "id": 8,
      "name": "King Size Bedsheet",
      "code": "MP-2026-0008",
      "manufacturing_product_category_id": 1,
      "category_name": "Bedsheet Sets",
      "status": "active",
      "status_label": "Active",
      "standard_labor_rate": 27.5,
      "image_url": "https://konnadia.empoweredtechinnovations.org/storage/manufacturing_products/bedsheet_king.jpg",
      "is_fabric_used": true,
      "standard_fabric_width": 60.0,
      "standard_fabric_length": 2.75,
      "fabric_width_unit": "in",
      "fabric_length_unit": "m",
      "is_common_subsidiary": true,
      "is_subsidiary_used": true,
      "is_stitching_used": true,
      "patterns": [
        {
          "id": 14,
          "name": "Pattern Standard 60in",
          "fabric_width": 60.0,
          "fabric_length": 2.75,
          "fabric_length_unit": "m",
          "is_default": true
        }
      ],
      "tasks": [
        {
          "id": 1,
          "task_id": 1,
          "code": "TSK-CUT",
          "name": "Cutting Stage",
          "sequence_number": 1,
          "standard_labor_rate": 12.0,
          "is_final_step": false
        },
        {
          "id": 2,
          "task_id": 2,
          "code": "TSK-STITCH",
          "name": "Stitching Stage",
          "sequence_number": 2,
          "standard_labor_rate": 15.5,
          "is_final_step": true
        }
      ],
      "created_at": "2026-09-24T18:00:00Z"
    }
  ],
  "meta": {
    "current_page": 1,
    "last_page": 1,
    "per_page": 15,
    "total": 1
  }
}
```

---

### B. Product Form Lookup Options

Retrieve all necessary form options for creating or editing a manufacturing product.

* **HTTP Method**: `GET`
* **Route**: `/factory/products/options` (Aliases: `/production/products/options`, `/production/manufacturing-products/options`, `/admin/production/manufacturing-products/options`)

#### Response Example (`200 OK`)
```json
{
  "success": true,
  "data": {
    "auto_generated_code": "MP-2026-0009",
    "categories": [
      { "id": 1, "name": "Bedsheet Sets", "status": true }
    ],
    "fabric_widths": [
      { "id": 1, "name": "44 Inch", "value": 44.0, "unit": "in" },
      { "id": 2, "name": "60 Inch", "value": 60.0, "unit": "in" }
    ],
    "available_tasks": [
      { "id": 1, "code": "TSK-CUT", "name": "Cutting Stage", "default_rate_per_piece": 12.0 },
      { "id": 2, "code": "TSK-STITCH", "name": "Stitching Stage", "default_rate_per_piece": 15.5 }
    ],
    "subsidiary_raw_materials": [
      { "id": 5, "name": "Elastic Thread 5mm", "unit": "Meters" }
    ],
    "stitching_raw_materials": [
      { "id": 9, "name": "Cotton Thread Spool", "unit": "Spools" }
    ],
    "length_units": [
      { "id": 1, "name": "Meter", "short_code": "m", "is_base": true },
      { "id": 2, "name": "Yard", "short_code": "yd", "is_base": false }
    ],
    "storefront_products": [
      { "id": 12, "title": "Premium King Sheet", "sku": "SKU-KS-01" }
    ]
  }
}
```

---

### C. Show Manufacturing Product Details

Fetch complete 360-degree product details including patterns, task routing, subsidiary materials, and storefront product link.

* **HTTP Method**: `GET`
* **Route**: `/factory/products/{id}` (Aliases: `/production/products/{id}`, `/production/manufacturing-products/{id}`, `/admin/production/manufacturing-products/{id}`)

---

### D. Create Manufacturing Product

Create a new manufacturing product record with patterns, task routing sequence, and BOM materials.

* **HTTP Method**: `POST`
* **Route**: `/factory/products` (Aliases: `/production/products`, `/production/manufacturing-products`, `/admin/production/manufacturing-products`)

#### Payload Specification (`multipart/form-data` or `application/json`)
```json
{
  "name": "Single Bedsheet Standard",
  "code": "MP-2026-0009",
  "manufacturing_product_category_id": 1,
  "status": "active",
  "standard_labor_rate": 22.00,
  "is_fabric_used": true,
  "standard_fabric_width": 44.00,
  "standard_fabric_length": 2.25,
  "fabric_width_unit": "in",
  "fabric_length_unit": "m",
  "is_common_subsidiary": true,
  "is_subsidiary_used": true,
  "is_stitching_used": true,
  "patterns": [
    {
      "name": "Standard 44in Pattern",
      "fabric_width_id": 1,
      "fabric_length": 2.25,
      "fabric_length_unit": "m",
      "standard_labor_rate": 22.00,
      "widths": [
        {
          "fabric_width_id": 1,
          "fabric_length": 2.25,
          "fabric_length_unit": "m"
        }
      ],
      "tasks": [
        {
          "task_id": 1,
          "sequence_number": 1,
          "standard_labor_rate": 10.00,
          "is_final_step": false
        },
        {
          "task_id": 2,
          "sequence_number": 2,
          "standard_labor_rate": 12.00,
          "is_final_step": true
        }
      ]
    }
  ],
  "subsidiary_materials": [
    {
      "raw_material_id": 5,
      "consumption_quantity": 1.50
    }
  ],
  "stitching_materials": [9]
}
```

#### Field Validation Rules:
| Field | Type | Validation | Description |
| :--- | :--- | :--- | :--- |
| `name` | `string` | Required, max 255 | Product title |
| `code` | `string` | Optional, max 50, unique | Code (Auto-generated if empty) |
| `manufacturing_product_category_id` | `integer` | Required, exists:manufacturing_product_categories,id | Must link to an **active** category |
| `status` | `string` | Optional, `active` / `inactive` | Product status |
| `standard_labor_rate` | `numeric` | Optional, min 0 | Default labor rate |
| `image` | `file` | Optional, image (jpeg, png, webp), max 5MB | Product photo |
| `patterns` | `array` | Optional | Array of pattern specs with tasks and fabric widths |

#### Response Example (`201 Created`)
```json
{
  "success": true,
  "message": "Manufacturing Product Single Bedsheet Standard created successfully!",
  "data": {
    "id": 9,
    "name": "Single Bedsheet Standard",
    "code": "MP-2026-0009",
    "manufacturing_product_category_id": 1,
    "status": "active",
    "status_label": "Active"
  }
}
```

---

### E. Update Manufacturing Product

* **HTTP Method**: `PUT` or `PATCH`
* **Route**: `/factory/products/{id}` (Aliases: `/production/products/{id}`, `/production/manufacturing-products/{id}`, `/admin/production/manufacturing-products/{id}`)

---

### F. Toggle Product Status

* **HTTP Method**: `PATCH`
* **Route**: `/factory/products/{id}/toggle-status` (Aliases: `/production/products/{id}/toggle-status`, `/production/manufacturing-products/{id}/toggle-status`, `/admin/production/manufacturing-products/{id}/toggle-status`)

#### Response Example (`200 OK`)
```json
{
  "success": true,
  "message": "Manufacturing Product Single Bedsheet Standard status set to inactive.",
  "data": {
    "id": 9,
    "status": "inactive",
    "status_label": "Inactive"
  }
}
```

---

### G. Delete Manufacturing Product

Soft-delete a manufacturing product. **Fails with HTTP 422 if product is linked to active production jobs or batches.**

* **HTTP Method**: `DELETE`
* **Route**: `/factory/products/{id}` (Aliases: `/production/products/{id}`, `/production/manufacturing-products/{id}`, `/admin/production/manufacturing-products/{id}`)

#### Success Response (`200 OK`)
```json
{
  "success": true,
  "message": "Manufacturing Product Single Bedsheet Standard deleted successfully."
}
```

#### Error Response (`422 Unprocessable Entity`)
```json
{
  "success": false,
  "message": "Cannot delete manufacturing product [Single Bedsheet Standard]: It is linked to 5 production jobs, 2 production batches, or active allocations."
}
```
