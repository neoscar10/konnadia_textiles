# Mobile Developer Guide: Raw Materials, Suppliers, Fabric Widths, Purchases, Inventory Batches & Units API

This comprehensive API integration guide provides mobile app developers (iOS / Android / Flutter / React Native) with complete specification for integrating the **Raw Materials & Inventory Management Ecosystem**.

---

## Table of Contents
1. [Authentication & Base URLs](#1-authentication--base-urls)
2. [Raw Materials Master API](#2-raw-materials-master-api)
   - [GET /factory/raw-materials/options (Form Select Options)](#21-get-factoryraw-materialsoptions)
   - [GET /factory/raw-materials (Catalog Listing & Filters)](#22-get-factoryraw-materials)
   - [GET /factory/raw-materials/{id} (Single Record Detail)](#23-get-factoryraw-materialsids)
   - [POST /factory/raw-materials (Create Raw Material)](#24-post-factoryraw-materials)
   - [PUT /factory/raw-materials/{id} (Update Record)](#25-put-factoryraw-materialsids)
   - [PATCH /factory/raw-materials/{id}/toggle-status (Toggle Active Status)](#26-patch-factoryraw-materialsids-toggle-status)
   - [DELETE /factory/raw-materials/{id} (Delete Raw Material)](#27-delete-factoryraw-materialsids)
3. [Suppliers Management API](#3-suppliers-management-api)
   - [GET /factory/raw-materials/suppliers/options (Supplier Picker Options)](#31-get-factoryraw-materialssuppliersoptions)
   - [GET /factory/raw-materials/suppliers (List Suppliers)](#32-get-factoryraw-materialssuppliers)
   - [POST /factory/raw-materials/suppliers (Create Supplier)](#33-post-factoryraw-materialssuppliers)
   - [PUT /factory/raw-materials/suppliers/{id} (Update Supplier)](#34-put-factoryraw-materialssuppliersids)
   - [DELETE /factory/raw-materials/suppliers/{id} (Delete Supplier)](#35-delete-factoryraw-materialssuppliersids)
4. [Fabric Widths Management API](#4-fabric-widths-management-api)
   - [GET /factory/fabric-widths/options (Fabric Width Options)](#41-get-factoryfabric-widthsoptions)
   - [GET /factory/fabric-widths (List Fabric Widths)](#42-get-factoryfabric-widths)
   - [POST /factory/fabric-widths (Create Fabric Width)](#43-post-factoryfabric-widths)
   - [PUT /factory/fabric-widths/{id} (Update Fabric Width)](#44-put-factoryfabric-widthsids)
   - [PATCH /factory/fabric-widths/{id}/toggle-status (Toggle Status)](#45-patch-factoryfabric-widthsids-toggle-status)
   - [DELETE /factory/fabric-widths/{id} (Delete Fabric Width)](#46-delete-factoryfabric-widthsids)
5. [Raw Material Purchase Entries API](#5-raw-material-purchase-entries-api)
   - [GET /factory/raw-materials/purchase/options (Purchase Form Options)](#51-get-factoryraw-materialspurchaseoptions)
   - [POST /factory/raw-materials/purchase (Submit Purchase Entry)](#52-post-factoryraw-materialspurchase)
6. [Inventory Batches & Roll/Bale Opening API](#6-inventory-batches--rollbale-opening-api)
   - [GET /factory/raw-materials/batches (List Inventory Batches)](#61-get-factoryraw-materialsbatches)
   - [GET /factory/raw-materials/batches/{id} (Batch Detail & Bales/Rolls)](#62-get-factoryraw-materialsbatchesids)
   - [POST /factory/raw-materials/batches/{id}/bales/{baleId}/open (Open Bale/Roll)](#63-post-factoryraw-materialsbatchesidsbalesbaleidopen)
   - [POST /factory/raw-materials/batches/{id}/adjust-quantity (Stock Adjustment)](#64-post-factoryraw-materialsbatchesidsadjust-quantity)
7. [Units & Unit Groups Management API](#7-units--unit-groups-management-api)
   - [GET /admin/units/templates (Unit Group Templates)](#71-get-adminunitstemplates)
   - [GET /admin/units/groups (List Unit Groups)](#72-get-adminunitsgroups)
   - [POST /admin/units/groups (Create Unit Group)](#73-post-adminunitsgroups)
   - [GET /admin/units (List Unit Records)](#74-get-adminunits)
   - [POST /admin/units (Create Unit Record)](#75-post-adminunits)
   - [POST /admin/units/{id}/set-base (Set Group Base Unit)](#76-post-adminunitsids-set-base)
   - [POST /admin/units/convert (Unit Conversion Calculator)](#77-post-adminunitsconvert)
   - [POST /admin/units/preview-relationship (Preview Conversion Factors)](#78-post-adminunitspreview-relationship)

---

## 1. Authentication & Base URLs

### Headers
Every API request must include the Sanctum bearer token obtained from login:
```http
Authorization: Bearer <your_jwt_or_sanctum_token>
Accept: application/json
Content-Type: application/json
```

### URL Aliases & Fallbacks
All raw material routes are accessible under multiple equivalent route prefixes:

| Web Module | Primary Mobile Endpoint | Equivalent Aliases |
| :--- | :--- | :--- |
| **Raw Materials** | `GET/POST /api/v1/factory/raw-materials` | `/api/v1/production/raw-materials`, `/api/v1/admin/production/raw-materials`, `/api/v1/admin/raw-materials` |
| **Suppliers** | `GET/POST /api/v1/factory/suppliers` | `/api/v1/factory/raw-materials/suppliers`, `/api/v1/production/suppliers`, `/api/v1/admin/suppliers` |
| **Fabric Widths** | `GET/POST /api/v1/factory/fabric-widths` | `/api/v1/factory/raw-materials/fabric-widths`, `/api/v1/production/fabric-widths`, `/api/v1/admin/fabric-widths` |
| **Purchase Entry** | `POST /api/v1/factory/raw-materials/purchase` | `/api/v1/production/raw-material-purchases`, `/api/v1/admin/raw-material-purchases` |
| **Inventory Batches** | `GET /api/v1/factory/raw-materials/batches` | `/api/v1/production/inventory-batches`, `/api/v1/admin/inventory-batches` |
| **Units & Groups** | `GET/POST /api/v1/admin/units` | `/api/v1/factory/units`, `/api/v1/production/units` |

---

## 2. Raw Materials Master API

### 2.1 GET `/factory/raw-materials/options`
Retrieves options for creating and filtering raw materials, including categories, available unit groups, and fabric standard widths.

#### Response Example (`HTTP 200 OK`):
```json
{
  "success": true,
  "data": {
    "categories": [
      {
        "id": 1,
        "name": "Fabrics",
        "code": "FAB",
        "unit_type": "length"
      },
      {
        "id": 2,
        "name": "Stitching Material",
        "code": "STITCH",
        "unit_type": "quantity"
      }
    ],
    "unit_types": ["length", "weight", "quantity"],
    "units": ["Meters", "Yards", "Inches", "Kg", "Grams", "Pcs", "Rolls", "Bales"],
    "fabric_widths": [
      { "id": 1, "width": 44, "unit": "Inches" },
      { "id": 2, "width": 54, "unit": "Inches" },
      { "id": 3, "width": 60, "unit": "Inches" }
    ]
  }
}
```

---

### 2.2 GET `/factory/raw-materials`
Returns a paginated list of raw materials with search, category filtering, unit type filtering, and status filtering.

#### Query Parameters:
- `search` (string, optional): Filter by material name or code (`RM-FAB-001`).
- `category_id` (integer, optional): Filter by raw material category ID.
- `unit_type` (string, optional): `length`, `weight`, or `quantity`.
- `status` (string, optional): `active` or `inactive`.
- `per_page` (integer, default `15`).

#### Response Example (`HTTP 200 OK`):
```json
{
  "success": true,
  "data": {
    "current_page": 1,
    "data": [
      {
        "id": 10,
        "name": "100% Cotton Satin Fabric 300TC",
        "code": "RM-FAB-300TC",
        "raw_material_category_id": 1,
        "category_name": "Fabrics",
        "unit_type": "length",
        "unit": "Meters",
        "fabric_standard_width_id": 3,
        "fabric_width_label": "60 Inches",
        "minimum_stock_level": 500,
        "current_stock": 2450.5,
        "status": "active",
        "created_at": "2026-09-20T10:15:00Z"
      }
    ],
    "total": 1
  }
}
```

---

### 2.3 GET `/factory/raw-materials/{id}`
Returns details for a specific raw material along with its current inventory batch summary.

#### Response Example (`HTTP 200 OK`):
```json
{
  "success": true,
  "data": {
    "id": 10,
    "name": "100% Cotton Satin Fabric 300TC",
    "code": "RM-FAB-300TC",
    "raw_material_category_id": 1,
    "unit": "Meters",
    "fabric_standard_width_id": 3,
    "minimum_stock_level": 500,
    "status": "active",
    "batches_count": 4,
    "total_available_stock": 2450.5
  }
}
```

---

### 2.4 POST `/factory/raw-materials`
Creates a new raw material record.

#### Request Body (`JSON`):
```json
{
  "name": "Poly-Cotton Twill Gray 240 GSM",
  "code": "RM-FAB-PCT240",
  "raw_material_category_id": 1,
  "unit": "Meters",
  "fabric_standard_width_id": 2,
  "minimum_stock_level": 300,
  "status": "active"
}
```

---

### 2.5 PUT `/factory/raw-materials/{id}`
Updates an existing raw material record.

---

### 2.6 PATCH `/factory/raw-materials/{id}/toggle-status`
Toggles active/inactive status.

---

### 2.7 DELETE `/factory/raw-materials/{id}`
Deletes raw material if no active inventory batches or production allocations are linked to it.

---

## 3. Suppliers Management API

### 3.1 GET `/factory/raw-materials/suppliers/options`
Returns option pickers for supplier state dropdowns.

### 3.2 GET `/factory/raw-materials/suppliers`
Returns paginated list of material suppliers with search (`name`, `code`, `phone`, `gst_number`).

### 3.3 POST `/factory/raw-materials/suppliers`
#### Request Body (`JSON`):
```json
{
  "name": "Apex Textile Mills Ltd",
  "supplier_code": "SUP-APEX-01",
  "contact_person": "Rajesh Kumar",
  "phone": "+91 98765 43210",
  "email": "sales@apextextiles.com",
  "gst_number": "27AAACA12341Z1",
  "address": "Plot 42, Industrial Area Phase 2",
  "city": "Surat",
  "state": "Gujarat",
  "pincode": "395003"
}
```

---

## 4. Fabric Widths Management API

### 4.1 GET `/factory/fabric-widths`
Returns standard fabric widths used in production cutting math.

#### Response Example (`HTTP 200 OK`):
```json
{
  "success": true,
  "data": [
    { "id": 1, "width": 44.0, "unit": "Inches", "description": "Standard Shirt / Dress Width", "is_active": true },
    { "id": 2, "width": 54.0, "unit": "Inches", "description": "Upholstery & Suiting Width", "is_active": true },
    { "id": 3, "width": 60.0, "unit": "Inches", "description": "Wide Sheeting & Curtain Width", "is_active": true },
    { "id": 4, "width": 108.0, "unit": "Inches", "description": "Extra Wide Bedsheet Width", "is_active": true }
  ]
}
```

### 4.2 POST `/factory/fabric-widths`
```json
{
  "width": 72.0,
  "unit": "Inches",
  "description": "72 inch Special Weave Width",
  "is_active": true
}
```

---

## 5. Raw Material Purchase Entries API

### 5.1 GET `/factory/raw-materials/purchase/options`
Returns suppliers, raw materials, fabric widths, and unit pickers for recording inbound stock shipments.

### 5.2 POST `/factory/raw-materials/purchase`
Submits an inbound shipment invoice entry and automatically generates inventory batch numbers and individual roll/bale tracking records.

#### Request Body (`JSON`):
```json
{
  "supplier_id": 1,
  "invoice_number": "INV-2026-0891",
  "purchase_date": "2026-09-25",
  "raw_material_id": 10,
  "batch_code": "BATCH-COT-2026-09A",
  "unit_cost": 185.50,
  "total_quantity": 1000.0,
  "bale_count": 10,
  "equal_length_bales": true,
  "bale_length": 100.0,
  "bales": [
    { "bale_number": "BALE-01", "length": 100.0, "shade": "Off-White" },
    { "bale_number": "BALE-02", "length": 100.0, "shade": "Off-White" }
  ],
  "remarks": "Received in good condition via VRL Logistics"
}
```

#### Response Example (`HTTP 201 Created`):
```json
{
  "success": true,
  "message": "Purchase entry recorded and batch inventory generated successfully.",
  "data": {
    "batch_id": 45,
    "batch_code": "BATCH-COT-2026-09A",
    "total_quantity": 1000.0,
    "bales_created": 10
  }
}
```

---

## 6. Inventory Batches & Roll/Bale Opening API

### 6.1 GET `/factory/raw-materials/batches`
Lists inventory batches with filter options (`raw_material_id`, `supplier_id`, `status`).

### 6.2 GET `/factory/raw-materials/batches/{id}`
Returns batch information and list of individual bales/rolls with their open/unopened states.

### 6.3 POST `/factory/raw-materials/batches/{id}/bales/{baleId}/open`
Marks a specific roll or bale as opened for cutting/production processing.

#### Response Example (`HTTP 200 OK`):
```json
{
  "success": true,
  "message": "Bale BALE-01 marked as OPENED for production.",
  "data": {
    "bale_id": 102,
    "status": "opened",
    "opened_at": "2026-09-26T00:05:00Z"
  }
}
```

### 6.4 POST `/factory/raw-materials/batches/{id}/adjust-quantity`
Allows stock reconciliation and manual stock adjustments.

---

## 7. Units & Unit Groups Management API

### 7.1 GET `/admin/units/templates`
Provides pre-configured unit group templates (Length, Weight, Volume, Count).

### 7.2 GET `/admin/units/groups`
Lists unit groups (e.g. Length Group: Meter, Yard, Inch, Feet).

### 7.3 POST `/admin/units/groups`
```json
{
  "name": "Fabric Packaging Count Group",
  "code": "PKG_COUNT",
  "description": "Packaging box and carton count conversions",
  "is_active": true
}
```

### 7.4 GET `/admin/units`
Lists all unit records with conversion factors relative to their group base unit.

### 7.5 POST `/admin/units`
```json
{
  "unit_group_id": 1,
  "name": "Centimeter",
  "code": "cm",
  "conversion_factor": 0.01,
  "is_base": false,
  "is_active": true
}
```

### 7.6 POST `/admin/units/{id}/set-base`
Sets the specified unit as the base reference unit for its group (recalculating conversion factors).

### 7.7 POST `/admin/units/convert`
Calculates live conversions between units within the same group.

#### Request Body (`JSON`):
```json
{
  "from_unit_id": 1,
  "to_unit_id": 3,
  "quantity": 100.0
}
```

#### Response Example (`HTTP 200 OK`):
```json
{
  "success": true,
  "data": {
    "from_quantity": 100.0,
    "from_unit": "Meter",
    "to_quantity": 3937.01,
    "to_unit": "Inch",
    "conversion_factor": 39.3701
  }
}
```

### 7.8 POST `/admin/units/preview-relationship`
Calculates live preview of base and target unit conversion dynamics.

---

## Summary for Mobile Developers
1. **Full API Parity**: Every action on the web application (Raw Materials, Suppliers, Fabric Widths, Purchases, Batches, Units) is fully exposed via clean REST endpoints.
2. **Robust Route Interceptors**: Non-numeric actions (`/options`, `/templates`, `/groups`, `/convert`, `/preview-relationship`) are guaranteed not to trigger `{id}` route conflicts.
3. **Optimized for Mobile UI**: Option pickers populate dynamic spinners/dropdowns instantly without redundant client calculations.
