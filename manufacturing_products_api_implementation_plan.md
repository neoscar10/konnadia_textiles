# Manufacturing Products & Categories API Implementation Plan

---

## Executive Summary

This document provides the implementation plan for the **Manufacturing Products & Product Categories API** module in the Kannodia Textiles backend platform. It covers architectural design, database schemas, Eloquent relationship patterns, form validation rules, route aliases, security checks, and automated feature testing strategies.

---

## 1. Core Objectives & Scope

1. **Manufacturing Product Catalog**: Enable creation, listing, updating, soft deletion, and status toggling (`active` / `inactive`) of manufacturing WIP products.
2. **Product Categories & Task Sequence Templates**: Maintain categories and default task routing sequences (e.g., Cutting -> Stitching -> QC).
3. **Bill of Materials (BOM) & Pattern Routing**: Support multiple patterns per product, fabric width/length specs, subsidiary raw material consumption, and stitching material links.
4. **Unified Namespace Aliasing**: Ensure seamless access across three route alias namespaces:
   - `/api/v1/factory/products` & `/api/v1/factory/product-categories`
   - `/api/v1/production/products` & `/api/v1/production/product-categories`
   - `/api/v1/admin/production/manufacturing-products` & `/api/v1/admin/production/product-categories`
5. **Relational Integrity**: Block deletion of categories linked to products, or products linked to active production batches/jobs (`HTTP 422`).

---

## 2. System Architecture & Relational Mapping

```
                               +-------------------------------------+
                               |   ManufacturingProductCategory      |
                               +-------------------------------------+
                               | id                                  |
                               | name                                |
                               | status (bool)                       |
                               +-------------------------------------+
                                                  |
                                                  | 1:N
                                                  v
+-----------------------------------------------------------------------------------+
|                              ManufacturingProduct                                 |
+-----------------------------------------------------------------------------------+
| id                                                                                |
| name, code (e.g., MP-2026-0001)                                                   |
| manufacturing_product_category_id                                                 |
| status ('active' | 'inactive')                                                    |
| standard_labor_rate, standard_fabric_width, standard_fabric_length                |
| fabric_width_unit ('in'), fabric_length_unit ('m')                                |
| is_common_subsidiary, is_subsidiary_used, is_stitching_used                       |
| product_id, product_combination_id (Link to Storefront Retail Item)              |
+-----------------------------------------------------------------------------------+
       | 1:N                                     | N:M                                | N:M
       v                                         v                                    v
+-----------------------------------+  +-------------------+               +-------------------+
| ManufacturingProductPattern      |  | SubsidiaryMaterial|               | StitchingMaterial |
+-----------------------------------+  +-------------------+               +-------------------+
| id, name, fabric_width_id, length |  | raw_material_id   |               | raw_material_id   |
| is_default, standard_labor_rate   |  | consumption_qty   |               +-------------------+
+-----------------------------------+  +-------------------+
```

---

## 3. Database Schema & Models

### Model 1: `App\Models\ManufacturingProductCategory`
* **Table**: `manufacturing_product_categories`
* **Relationships**:
  - `manufacturingProducts()`: `HasMany` to `ManufacturingProduct`
  - `defaultTasks()`: `BelongsToMany` to `Task` via `category_default_tasks` pivot (`sequence_number`, `standard_labor_rate`, `is_final_step`)

### Model 2: `App\Models\ManufacturingProduct`
* **Table**: `manufacturing_products`
* **Relationships**:
  - `category()`: `BelongsTo` to `ManufacturingProductCategory`
  - `patterns()`: `HasMany` to `ManufacturingProductPattern`
  - `tasks()`: `BelongsToMany` to `Task` via `manufacturing_product_tasks` pivot
  - `subsidiaryMaterials()`: `BelongsToMany` to `RawMaterial` via `manufacturing_product_subsidiaries` pivot
  - `stitchingMaterials()`: `BelongsToMany` to `RawMaterial` via `manufacturing_product_stitching` pivot
  - `frontendProduct()`: `BelongsTo` to `Product`
  - `frontendCombination()`: `BelongsTo` to `ProductCombination`

---

## 4. API Endpoints Specification

### Category Endpoints (`AdminManufacturingCategoryController`)
| Method | Route | Description |
| :--- | :--- | :--- |
| `GET` | `/factory/product-categories` | List categories with search, status filter, and pagination |
| `GET` | `/factory/product-categories/options` | Active category list for pickers |
| `GET` | `/factory/product-categories/{id}` | Show category detail with default tasks |
| `POST` | `/factory/product-categories` | Create category |
| `PUT/PATCH` | `/factory/product-categories/{id}` | Update category |
| `PATCH` | `/factory/product-categories/{id}/toggle-status` | Toggle active/inactive status |
| `DELETE` | `/factory/product-categories/{id}` | Delete category (fails with 422 if products attached) |

### Product Endpoints (`AdminManufacturingProductController`)
| Method | Route | Description |
| :--- | :--- | :--- |
| `GET` | `/factory/products` | List manufacturing products with category, pattern, and task routing |
| `GET` | `/factory/products/options` | Form lookup options (auto code preview `MP-2026-xxxx`, categories, widths, raw materials, length units, storefront products) |
| `GET` | `/factory/products/{id}` | Show 360-degree product detail |
| `POST` | `/factory/products` | Create manufacturing product with patterns & BOM |
| `PUT/PATCH` | `/factory/products/{id}` | Update manufacturing product |
| `PATCH` | `/factory/products/{id}/toggle-status` | Toggle product active/inactive status |
| `DELETE` | `/factory/products/{id}` | Delete manufacturing product (fails with 422 if linked to jobs/batches) |

---

## 5. Validation Rules Matrix

### `StoreManufacturingProductRequest`
```php
public function rules(): array
{
    return [
        'name'                              => 'required|string|max:255',
        'code'                              => 'nullable|string|max:50|unique:manufacturing_products,code',
        'manufacturing_product_category_id' => 'required|exists:manufacturing_product_categories,id',
        'status'                            => 'nullable|string|in:active,inactive',
        'standard_labor_rate'               => 'nullable|numeric|min:0',
        'image'                             => 'nullable|image|mimes:jpeg,png,jpg,webp|max:5120',
        'is_fabric_used'                    => 'nullable|boolean',
        'standard_fabric_width'             => 'nullable|numeric|min:0',
        'standard_fabric_length'            => 'nullable|numeric|min:0',
        'fabric_width_unit'                 => 'nullable|string|max:10',
        'fabric_length_unit'                => 'nullable|string|max:10',
        'is_common_subsidiary'              => 'nullable|boolean',
        'is_subsidiary_used'                => 'nullable|boolean',
        'is_stitching_used'                 => 'nullable|boolean',
        'product_id'                        => 'nullable|exists:products,id',
        'product_combination_id'            => 'nullable|exists:product_combinations,id',
        'patterns'                          => 'nullable|array',
        'subsidiary_materials'              => 'nullable|array',
        'stitching_materials'               => 'nullable|array',
    ];
}
```

---

## 6. Testing & Quality Assurance Plan

Automated feature test suites:
- `tests/Feature/AdminManufacturingProductApiTest.php` (4 test cases / 22 assertions)
- `tests/Feature/AdminManufacturingCategoryApiTest.php` (8 test cases / 40 assertions)

### Key Test Scenarios:
1. **Category Pickers & Validation**: Verify duplicate name validation, active category listing, and task routing sequence syncing.
2. **Auto Code Generation**: Verify sequential `MP-2026-0001` code generation when `code` parameter is omitted.
3. **Category Validation Check**: Verify attempting to create a product linked to an inactive category throws validation error.
4. **Relational Deletion Safeguards**:
   - Deleting a category linked to manufacturing products returns `422 Unprocessable Entity`.
   - Deleting a manufacturing product linked to production batches or jobs returns `422 Unprocessable Entity`.
5. **Route Alias Coverage**: Verify all endpoints return `200 OK` across `/factory/products`, `/production/products`, and `/admin/production/manufacturing-products`.

---

## 7. Deployment & Verification Checklist

- [x] Run database migrations for `manufacturing_products` and `manufacturing_product_categories`.
- [x] Verify routes under `/factory/products`, `/factory/product-categories`, `/production/products`, `/production/product-categories`, `/admin/production/manufacturing-products`, and `/admin/production/product-categories`.
- [x] Run feature test suites (`AdminManufacturingProductApiTest` & `AdminManufacturingCategoryApiTest`).
- [x] Commit and push changes to remote repositories (`origin` & `upstream`).
