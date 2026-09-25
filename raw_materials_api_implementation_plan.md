# Raw Materials & Inventory API Implementation Plan

## Executive Summary
This document details the backend architecture, controller logic, route namespaces, and test verification matrix for the **Raw Materials Ecosystem API**. The API covers:
1. **Raw Materials Master**: `/factory/raw-materials`
2. **Suppliers Master**: `/factory/raw-materials/suppliers` & `/factory/suppliers`
3. **Fabric Widths Master**: `/factory/fabric-widths`
4. **Raw Material Purchase Entries**: `/factory/raw-materials/purchase` & `/factory/raw-material-purchases`
5. **Inventory Batches & Bale Opening**: `/factory/raw-materials/batches` & `/factory/inventory-batches`
6. **Units & Unit Groups Master**: `/admin/units` & `/factory/units`

---

## 1. System Architecture & Route Namespaces

To ensure seamless integration for both mobile apps and web administrative dashboards, all endpoints are mounted under unified route aliases with permission middleware (`api.permission:access production` / `api.admin`).

### Route Structure Overview
```
/api/v1/factory/
  ├── raw-materials/
  │     ├── /                     (GET list, POST create)
  │     ├── /options              (GET picker options)
  │     ├── /{id}                 (GET, PUT, PATCH, DELETE)
  │     ├── /{id}/toggle-status   (PATCH toggle status)
  │     ├── /purchase/options     (GET purchase options)
  │     ├── /purchase             (POST purchase entry)
  │     ├── /batches              (GET batch list)
  │     ├── /batches/{id}         (GET batch details)
  │     ├── /batches/{id}/bales/{baleId}/open (POST open roll/bale)
  │     ├── /batches/{id}/adjust-quantity    (POST quantity adjustment)
  │     ├── /suppliers            (GET, POST suppliers)
  │     └── /fabric-widths        (GET, POST fabric widths)
  ├── suppliers/                  (Direct alias group)
  ├── fabric-widths/              (Direct alias group)
  └── units/                      (Direct alias group)
```

---

## 2. Controller & Service Responsibilities

### 2.1 `AdminRawMaterialController`
- **Options (`options`)**: Fetches categories, standard unit types, unit lists, and active fabric standard widths.
- **Listing (`index`)**: Filters by name/code search, category ID, unit type, and status with pagination.
- **CRUD Operations**: Enforces unique code check per raw material and protects materials linked to inventory batches from accidental deletion.

### 2.2 `AdminSupplierController`
- **Picker Options (`options`)**: Returns state options and active suppliers for dropdowns.
- **Supplier CRUD**: Validates GST number format, contact info, and supplier code uniqueness.

### 2.3 `AdminFabricWidthController`
- **Master Records**: Manages standard fabric width inches (e.g. 44", 54", 60", 108") for production cut optimization math.

### 2.4 `AdminRawMaterialPurchaseController`
- **Inbound Shipments (`store`)**: Accepts unit cost, total purchase quantity, bale counts, and individual roll/bale length breakdown.
- **Batch Generation**: Auto-generates batch numbers and creates corresponding `InventoryBatch` and `InventoryBale` records.

### 2.5 `AdminInventoryBatchController`
- **Batch Tracking (`index`, `show`)**: Returns open/unopened bale status, remaining lengths, and ledger history.
- **Bale Opening (`openBale`)**: Changes bale state to `opened` and records timestamp and operator ID.
- **Stock Reconciliation (`adjustQuantity`)**: Creates stock adjustment log and updates batch quantity.

### 2.6 `AdminUnitController` & `AdminUnitGroupController`
- **Group Management**: Standard unit groups (Length, Weight, Count, Volume).
- **Unit Records**: Unit codes, base unit flags, and conversion factors relative to group base.
- **Conversion Utility (`convert`, `previewRelationship`)**: Performs accurate live unit conversion calculations.

---

## 3. Route Parameter Constraints & Safety

To eliminate 500 server errors caused by route collisions (e.g. `GET /factory/raw-materials/options` accidentally triggering `GET /factory/raw-materials/{id}` with `{id} = 'options'`), all `{id}` parameters are explicitly constrained with regular expressions:

```php
Route::get('/{id}', [AdminRawMaterialController::class, 'show'])->where('id', '[0-9]+');
Route::put('/{id}', [AdminRawMaterialController::class, 'update'])->where('id', '[0-9]+');
```

---

## 4. Test Matrix & Verification Coverage

| Test Suite | Covered Features | Assertions | Status |
| :--- | :--- | :--- | :--- |
| `AdminRawMaterialApiTest` | Filter, options, CRUD, batch delete protection | 12 | PASS |
| `AdminRawMaterialPurchaseApiTest` | Inbound purchase, batch auto-generation | 8 | PASS |
| `AdminRawMaterialsApiTest` | Categories, suppliers, fabric widths, inventory batches | 98 | PASS |
| `RawMaterialMasterTest` | Unit type validation, length conversions, status toggle | 114 | PASS |
| `RawMaterialPurchaseEntryTest` | Invoice validation, bale length math, lot numbers | 68 | PASS |
| `AdminUnitApiTest` | Unit groups, base setting, conversions, preview | 130 | PASS |

---

## 5. Deployment & Integration Checklist
- [x] API routes registered under `/api/v1/factory/...`, `/api/v1/production/...`, and `/api/v1/admin/...`.
- [x] Parameter regex constraints applied (`where('id', '[0-9]+')`).
- [x] Unit test coverage verified (100% passing).
- [x] Mobile developer guide written and published.
- [x] Changes committed and pushed to git remotes (`origin` and `upstream`/`neoscar`).
