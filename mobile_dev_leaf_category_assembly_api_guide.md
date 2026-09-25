# Kannodia Textiles Mobile App API Implementation Guide
## Module: Leaf Category Configuration & Manufacturing Product Assembly

---

## 1. Overview & Context

In the Kannodia Textiles ERP system, **Leaf Categories** define standard product template configurations, pricing defaults, tax rules, and manufacturing assembly specifications for storefront products.

When products are manufactured or converted from factory batches, the system uses the **Leaf Category Configuration** to automatically assemble manufacturing components and calculate storefront stock.

### Key Capabilities Configured per Leaf Category:
1. **Default Product Configuration**: Base price, description, HSN code, GST %, Minimum Order Quantity (MOQ), Product Type (`retail` vs `manufactured`), and dual-level Units of Measure (`level1` e.g., *Piece/pcs* & `level2` e.g., *Carton/ctn*).
2. **Customer Discount Tiers**: Percentage discount overrides mapped to active Customer Levels.
3. **Manufacturing Product Assembly**: The bill of materials (components) required to assemble 1 storefront unit (e.g. 1 Bed Sheet Set = 1 *Bedsheet King* + 2 *Pillowcases*).
4. **Packaging Requirements**: Raw packaging materials consumed during conversion (e.g. 1 *Polybag Clear XL*).

---

## 2. Global Authentication & API Standards

* **Base URL**: `https://konnadia.empoweredtechinnovations.org/api/v1` (or local environment `http://localhost/api/v1`)
* **Headers Required**:
  * `Authorization`: `Bearer <JWT_BEARER_TOKEN>`
  * `Accept`: `application/json`
  * `Content-Type`: `application/json`
* **Permission Required**: `access categories` or `super_admin` role.

---

## 3. Endpoints Reference

### A. Get Leaf Category Configuration, Assembly & Picker Options

Fetch full category defaults, linked assembly configuration (if configured), and picker options for manufacturing products and packaging raw materials.

* **HTTP Method**: `GET`
* **Route**: `/admin/categories/{id}/defaults`
* **Alias Route**: `/admin/categories/{id}/assembly`

#### Query Parameters (Optional)
None.

#### Response Example (`200 OK`)
```json
{
  "success": true,
  "data": {
    "category_id": 14,
    "category_name": "Premium Bedsheet Sets",
    "category_full_path": "Home Textiles > Bedding > Premium Bedsheet Sets",
    "is_leaf": true,
    "defaults": {
      "base_price": 1250.00,
      "description": "Default configuration for 100% cotton premium bedsheet sets",
      "hsn_code": "6302",
      "gst_percentage": 12,
      "minimum_order_quantity": 1,
      "product_type": "manufactured",
      "units": {
        "level1_name": "Piece",
        "level1_code": "pcs",
        "level2_name": "Carton",
        "level2_code": "ctn",
        "level2_conversion": 10
      },
      "pricingOverrides": {
        "1": 10.0,
        "2": 15.0
      }
    },
    "assembly_config": {
      "id": 8,
      "sku": "SKU-FEP-008",
      "name": "Premium Bedsheet Sets",
      "category_id": 14,
      "is_active": true,
      "components": [
        {
          "id": 12,
          "manufacturing_product_id": 5,
          "manufacturing_product": {
            "id": 5,
            "name": "Bedsheet King Size",
            "code": "MP-BED-K"
          },
          "quantity": 1
        },
        {
          "id": 13,
          "manufacturing_product_id": 8,
          "manufacturing_product": {
            "id": 8,
            "name": "Pillowcase Standard",
            "code": "MP-PIL-S"
          },
          "quantity": 2
        }
      ],
      "packaging_items": [
        {
          "id": 4,
          "raw_material_id": 12,
          "raw_material": {
            "id": 12,
            "name": "Polybag Clear XL",
            "code": "RM-PKG-01"
          },
          "quantity": 1
        }
      ]
    },
    "picker_options": {
      "manufacturing_products": [
        { "id": 5, "name": "Bedsheet King Size", "code": "MP-BED-K", "status": "active" },
        { "id": 8, "name": "Pillowcase Standard", "code": "MP-PIL-S", "status": "active" }
      ],
      "packaging_materials": [
        { "id": 12, "name": "Polybag Clear XL", "code": "RM-PKG-01", "unit": "Pcs" }
      ]
    }
  }
}
```

---

### B. Save / Update Leaf Category Configuration & Assembly Mapping

Save default product parameters, tax, dual units, and manufacturing assembly bill of materials.

* **HTTP Method**: `POST`
* **Route**: `/admin/categories/{id}/defaults`
* **Alias Route**: `/admin/categories/{id}/assembly`

#### Request Payload Specification
| Field | Type | Required | Description |
| :--- | :--- | :--- | :--- |
| `base_price` | `numeric` | Yes | Default base selling price (`>= 0`) |
| `description` | `string` | No | Category default description |
| `hsn_code` | `string` | No | HSN Code for taxation (max 20 chars) |
| `gst_percentage` | `numeric` | Yes | GST % tax rate (`0` to `100`) |
| `minimum_order_quantity` | `integer` | Yes | Minimum purchase order quantity (`>= 1`) |
| `product_type` | `string` | Yes | Either `"retail"` or `"manufactured"` |
| `units` | `object` | Yes | Dual units dictionary |
| `units.level1_name` | `string` | Yes | Unit 1 display name (e.g. `"Piece"`) |
| `units.level1_code` | `string` | Yes | Unit 1 short code (e.g. `"pcs"`) |
| `units.level2_name` | `string` | No | Unit 2 display name (e.g. `"Carton"`) |
| `units.level2_code` | `string` | No | Unit 2 short code (e.g. `"ctn"`) |
| `units.level2_conversion` | `numeric` | No | Conversion ratio: 1 Unit 2 = N Unit 1s |
| `pricingOverrides` | `object` | No | Map of `{customer_level_id: discount_percentage}` |
| `components` | `array` | No | List of manufacturing product components |
| `components[].manufacturing_product_id` | `integer` | Yes | ID of valid Manufacturing Product |
| `components[].quantity` | `integer` | Yes | Quantity required per storefront set (`>= 1`) |
| `packaging_items` | `array` | No | List of required packaging materials |
| `packaging_items[].raw_material_id` | `integer` | Yes | ID of valid Raw Material |
| `packaging_items[].quantity` | `integer` | Yes | Quantity required per storefront set (`>= 1`) |

#### Request Payload Example
```json
{
  "base_price": 1250.00,
  "description": "Default configuration for 100% cotton premium bedsheet sets",
  "hsn_code": "6302",
  "gst_percentage": 12,
  "minimum_order_quantity": 1,
  "product_type": "manufactured",
  "units": {
    "level1_name": "Piece",
    "level1_code": "pcs",
    "level2_name": "Carton",
    "level2_code": "ctn",
    "level2_conversion": 10
  },
  "pricingOverrides": {
    "1": 10.0,
    "2": 15.0
  },
  "components": [
    {
      "manufacturing_product_id": 5,
      "quantity": 1
    },
    {
      "manufacturing_product_id": 8,
      "quantity": 2
    }
  ],
  "packaging_items": [
    {
      "raw_material_id": 12,
      "quantity": 1
    }
  ]
}
```

#### Response Example (`200 OK`)
```json
{
  "success": true,
  "message": "Category default configuration and manufacturing product assembly saved successfully.",
  "data": {
    "category_id": 14,
    "defaults": { ... },
    "assembly_config": { ... }
  }
}
```

---

### C. List All Leaf Category Assembly Configurations (Dashboard View)

Retrieve a paginated list of all leaf categories alongside their assembly status and component details.

* **HTTP Method**: `GET`
* **Route**: `/factory/front-end-products`

#### Query Parameters
| Parameter | Type | Default | Description |
| :--- | :--- | :--- | :--- |
| `search` | `string` | `null` | Filter by category name or full path |
| `status` | `string` | `null` | Filter by configuration status: `"configured"` or `"unconfigured"` |
| `page` | `integer` | `1` | Page number |
| `per_page` | `integer` | `12` | Items per page (max 100) |

#### Response Example (`200 OK`)
```json
{
  "success": true,
  "message": "Front-end product configurations retrieved successfully.",
  "data": [
    {
      "category_id": 14,
      "category_name": "Premium Bedsheet Sets",
      "category_full_path": "Home Textiles > Bedding > Premium Bedsheet Sets",
      "is_configured": true,
      "front_end_product": {
        "id": 8,
        "sku": "SKU-FEP-008",
        "name": "Premium Bedsheet Sets",
        "is_active": true,
        "components": [
          {
            "id": 12,
            "manufacturing_product_id": 5,
            "manufacturing_product": { "id": 5, "name": "Bedsheet King Size", "code": "MP-BED-K" },
            "quantity": 1
          }
        ],
        "packaging_items": [
          {
            "id": 4,
            "raw_material_id": 12,
            "raw_material": { "id": 12, "name": "Polybag Clear XL", "code": "RM-PKG-01" },
            "quantity": 1
          }
        ]
      }
    }
  ],
  "pagination": {
    "total": 24,
    "count": 12,
    "per_page": 12,
    "current_page": 1,
    "total_pages": 2
  }
}
```

---

### D. Assembly KPI Summary Stats

* **HTTP Method**: `GET`
* **Route**: `/factory/front-end-products/stats`

#### Response Example (`200 OK`)
```json
{
  "success": true,
  "data": {
    "total_leaf_categories": 24,
    "configured_count": 18,
    "unconfigured_count": 6,
    "total_components_defined": 42,
    "configuration_rate": 75.0
  }
}
```

---

## 4. Mobile App Integration Instructions

### Step 1: Opening the Category Configuration Modal
1. When the user taps **Configure Category** on any leaf category in the mobile app, fetch data from `GET /api/v1/admin/categories/{id}/defaults`.
2. Populate the form fields with `defaults`:
   - Base Price, Description, HSN Code, GST Rate, MOQ, Product Type (`"retail"` or `"manufactured"`).
   - Dual Unit fields (`level1_name`, `level1_code`, `level2_name`, `level2_code`, `level2_conversion`).
   - Customer Level discount overrides.

### Step 2: Rendering Manufacturing Component Assembly Pickers
1. If `defaults.product_type == "manufactured"`, render the **Manufacturing Assembly Components** card.
2. If `assembly_config` exists, pre-populate existing rows from `assembly_config.components` and `assembly_config.packaging_items`.
3. If `assembly_config` is `null`, initialize with 1 empty component row and 1 empty packaging row.
4. Use `picker_options.manufacturing_products` to populate component dropdown pickers.
5. Use `picker_options.packaging_materials` to populate packaging material pickers.

### Step 3: Submitting Configuration
1. Collect all inputs and validate:
   - Base price `>= 0`, GST `%` between `0` and `100`, MOQ `>= 1`.
   - Each component row must have a valid `manufacturing_product_id` and `quantity >= 1`.
   - Each packaging row must have a valid `raw_material_id` and `quantity >= 1`.
2. Post payload to `POST /api/v1/admin/categories/{id}/defaults`.
3. Handle responses:
   - `200 OK`: Show success message, dismiss modal, and refresh list.
   - `422 Unprocessable Entity`: Display field-specific validation errors returned under the `errors` dictionary.
