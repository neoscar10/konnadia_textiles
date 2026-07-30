# Admin Store Operations Mobile API Documentation & Integration Guide
*(Inventory, Retail Shops, Product Transfers, and Order Management & Fractional Dispatch)*

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
1. [Section 1: Inventory Management (`/admin/inventory`)](#section-1-inventory-management-admininventory)
2. [Section 2: Retail Shops Management (`/admin/retail-shops`)](#section-2-retail-shops-management-adminretail-shops)
3. [Section 3: Product Transfers Management (`/admin/product-transfers`)](#section-3-product-transfers-management-adminproduct-transfers)
4. [Section 4: Order Management & Fractional Dispatch (`/admin/orders`)](#section-4-order-management--fractional-dispatch-adminorders)

---

# SECTION 1: INVENTORY MANAGEMENT (`/admin/inventory`)

## 1.1 Permission & Access Control
- **Required Permission**: `access inventory`

## 1.2 Endpoints Specification

### 1.2.1 Get Inventory Dashboard Statistics & Metrics
Retrieve summary dashboard cards (total items, total stock value in INR, low stock count, out of stock count).

- **HTTP Method**: `GET`
- **Full URL**: `https://konnadia.empoweredtechinnovations.org/api/v1/admin/inventory/stats`
- **Response `200 OK`**:
```json
{
  "success": true,
  "data": {
    "total_items": 1250,
    "formatted_total_items": "1,250",
    "total_value": 1875000.00,
    "formatted_total_value": "₹1,875,000.00",
    "low_stock": 14,
    "out_of_stock": 3
  }
}
```

---

### 1.2.2 List Inventory Items (Paginated with Filters)
Retrieve paginated list of products with stock quantities and inventory valuations.

- **HTTP Method**: `GET`
- **Full URL**: `https://konnadia.empoweredtechinnovations.org/api/v1/admin/inventory`
- **Query Parameters**:
  - `search` *(optional string)*: Search by product title or SKU.
  - `category_id` *(optional int)*: Filter products by category ID (includes descendant categories).
  - `stock_status` *(optional string)*: `instock`, `lowstock`, or `outofstock`.
  - `per_page` *(optional int, default 10)*: Number of items per page.
  - `page` *(optional int, default 1)*: Page number.

- **Response `200 OK`**:
```json
{
  "success": true,
  "data": [
    {
      "id": 10,
      "title": "Linen Trousers",
      "slug": "linen-trousers",
      "sku": "KT-TROUSER-01",
      "base_price": 1500.00,
      "formatted_base_price": "₹1,500.00",
      "product_type": "retail",
      "is_active": true,
      "primary_image_url": "https://konnadia.empoweredtechinnovations.org/storage/products/trouser.jpg",
      "total_stock": 25,
      "stock_status": "in_stock",
      "stock_label": "25 In Stock",
      "inventory_value": 37500.00,
      "formatted_inventory_value": "₹37,500.00",
      "categories": [
        { "id": 5, "title": "Trousers", "slug": "trousers" }
      ]
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

### 1.2.3 Adjust Single Product Stock
Adjust stock quantity for a single product.

- **HTTP Method**: `POST`
- **Full URL**: `https://konnadia.empoweredtechinnovations.org/api/v1/admin/inventory/adjust`
- **Request Body (JSON)**:
```json
{
  "product_id": 10,
  "adjustment_type": "add",
  "quantity": 10,
  "reason": "Restock shipment arrival"
}
```

- **Parameters**:
  - `product_id` *(required int)*: Target product ID.
  - `adjustment_type` *(required string)*: `'set'` (overwrite stock value), `'add'` (increment stock), or `'deduct'` (decrement stock).
  - `quantity` *(required int, min 0)*: Quantity to adjust.
  - `reason` *(optional string)*: Reason description.

- **Response `200 OK`**:
```json
{
  "success": true,
  "message": "Stock adjusted successfully.",
  "data": {
    "product_id": 10,
    "new_stock_quantity": 35,
    "product_total_stock": 35
  }
}
```

---

# SECTION 2: RETAIL SHOPS MANAGEMENT (`/admin/retail-shops`)

## 2.1 Permission & Access Control
- **Required Permission**: `access retail-shops`

## 2.2 Endpoints Specification

### 2.2.1 List Retail Shops (Paginated with Filters)
Retrieve paginated list of retail shop outlets.

- **HTTP Method**: `GET`
- **Full URL**: `https://konnadia.empoweredtechinnovations.org/api/v1/admin/retail-shops`
- **Query Parameters**:
  - `search` *(optional string)*: Search by shop name, code, city, contact person, or phone.
  - `status` *(optional string)*: `active` or `inactive`.
  - `per_page` *(optional int, default 10)*: Page size.
  - `page` *(optional int, default 1)*: Page number.

- **Response `200 OK`**:
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "shop_code": "RSH-10001",
      "name": "Konnadia Flagship Store",
      "address": "123 Textile Market Road",
      "city": "Surat",
      "state": "Gujarat",
      "pincode": "395002",
      "contact_person": "Rajesh Sharma",
      "contact_phone": "+91 9876543210",
      "is_active": true,
      "transfers_count": 12,
      "created_at": "30-Jul-2026 00:19"
    }
  ]
}
```

---

### 2.2.2 Fetch Active Retail Shop Options
Get a lightweight dropdown options list of active retail shops for transfer creation pickers.

- **HTTP Method**: `GET`
- **Full URL**: `https://konnadia.empoweredtechinnovations.org/api/v1/admin/retail-shops/options`
- **Response `200 OK`**:
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "shop_code": "RSH-10001",
      "name": "Konnadia Flagship Store",
      "city": "Surat"
    }
  ]
}
```

---

### 2.2.3 Create Retail Shop
Create a new retail shop location.

- **HTTP Method**: `POST`
- **Full URL**: `https://konnadia.empoweredtechinnovations.org/api/v1/admin/retail-shops`
- **Request Body (JSON)**:
```json
{
  "name": "Konnadia Retail Outlet 2",
  "address": "45 MG Road",
  "city": "Ahmedabad",
  "state": "Gujarat",
  "pincode": "380001",
  "contact_person": "Amit Patel",
  "contact_phone": "+91 9123456789",
  "is_active": true
}
```

- **Response `201 Created`**: Returns created retail shop resource object with auto-generated `shop_code`.

---

### 2.2.4 Update Retail Shop
- **HTTP Method**: `PUT` or `PATCH`
- **Full URL**: `https://konnadia.empoweredtechinnovations.org/api/v1/admin/retail-shops/{id}`

---

### 2.2.5 Toggle Active Status
- **HTTP Method**: `PATCH`
- **Full URL**: `https://konnadia.empoweredtechinnovations.org/api/v1/admin/retail-shops/{id}/toggle-status`

---

### 2.2.6 Delete Retail Shop
- **HTTP Method**: `DELETE`
- **Full URL**: `https://konnadia.empoweredtechinnovations.org/api/v1/admin/retail-shops/{id}`

---

# SECTION 3: PRODUCT TRANSFERS MANAGEMENT (`/admin/product-transfers`)

## 3.1 Permission & Access Control
- **Required Permission**: `access product-transfers`

## 3.2 Endpoints Specification

### 3.2.1 List Product Transfers
Retrieve paginated stock transfer records to retail shops.

- **HTTP Method**: `GET`
- **Full URL**: `https://konnadia.empoweredtechinnovations.org/api/v1/admin/product-transfers`
- **Query Parameters**:
  - `search` *(optional string)*: Search by transfer reference number (e.g. `TRF-10001`).
  - `retail_shop_id` *(optional int)*: Filter by target retail shop.
  - `status` *(optional string)*: `completed` or `cancelled`.

- **Response `200 OK`**:
```json
{
  "success": true,
  "data": [
    {
      "id": 5,
      "reference_number": "TRF-10005",
      "transfer_date": "30-Jul-2026",
      "status": "completed",
      "notes": "Dispatching 10 saree sets to Surat Central Shop",
      "total_base_quantity": 10.0,
      "items_count": 1,
      "retail_shop": {
        "id": 1,
        "shop_code": "RSH-20001",
        "name": "Surat Central Shop",
        "city": "Surat"
      },
      "creator": {
        "id": 2,
        "name": "Super Admin",
        "email": "super_admin@konnadia.com"
      }
    }
  ]
}
```

---

### 3.2.2 Fetch Transfer Creation Options
Retrieve available active retail shops and product categories for wizard setup.

- **HTTP Method**: `GET`
- **Full URL**: `https://konnadia.empoweredtechinnovations.org/api/v1/admin/product-transfers/options`

---

### 3.2.3 Fetch Product Unit & Stock Details for Transfer
Get dual unit configuration (`level 1` and `level 2` units with conversion factors) and current stock availability before adding an item to a transfer list.

- **HTTP Method**: `GET`
- **Full URL**: `https://konnadia.empoweredtechinnovations.org/api/v1/admin/product-transfers/products/{id}/transfer-info`
- **Response `200 OK`**:
```json
{
  "success": true,
  "data": {
    "product_id": 12,
    "product_title": "Silk Saree Set",
    "product_sku": "KT-SAREE-SET-01",
    "available_stock": 100,
    "units": [
      {
        "id": 4,
        "level": 1,
        "name": "Piece",
        "short_code": "pcs",
        "conversion_to_base": 1.0
      },
      {
        "id": 5,
        "level": 2,
        "name": "Set (4 Pcs)",
        "short_code": "set",
        "conversion_to_base": 4.0
      }
    ]
  }
}
```

---

### 3.2.4 View Transfer Details
Retrieve complete details and line item breakdown of a product transfer.

- **HTTP Method**: `GET`
- **Full URL**: `https://konnadia.empoweredtechinnovations.org/api/v1/admin/product-transfers/{id}`

---

### 3.2.5 Create & Complete Product Transfer
Submit and execute a stock transfer to a retail shop. Automatically validates available inventory, deducts stock, and logs the transfer transaction.

- **HTTP Method**: `POST`
- **Full URL**: `https://konnadia.empoweredtechinnovations.org/api/v1/admin/product-transfers`
- **Request Body (JSON)**:
```json
{
  "retail_shop_id": 1,
  "transfer_date": "2026-07-30",
  "notes": "Dispatching 10 saree sets to Surat Central Shop",
  "items": [
    {
      "product_id": 12,
      "product_unit_id": 5,
      "quantity": 10,
      "note": "First batch"
    }
  ]
}
```

- **Response `201 Created`**: Returns detailed transfer object.

---

# SECTION 4: ORDER MANAGEMENT & FRACTIONAL DISPATCH (`/admin/orders`)

## 4.1 Permission & Access Control
- **Required Permission**: `access orders`

## 4.2 Key Feature: Fractional Order Item Dispatch
Administrators can perform **fractional or partial dispatches** (e.g. dispatching `0.5 sets` or `2 pieces` out of a `4-piece set`).
- When a partial quantity is dispatched, the system automatically:
  1. Deducts inventory stock for the dispatched quantity.
  2. Updates the dispatched order item record.
  3. Splits the order item by creating a new `pending_dispatch` order item for the remaining balance.
  4. Generates an official `dispatch_number` (e.g., `DISP-KT-ORD-100008-1`).
  5. Updates the order status to `partially_dispatched`.

---

## 4.3 Endpoints Specification

### 4.3.1 Get Order Dashboard Statistics
- **HTTP Method**: `GET`
- **Full URL**: `https://konnadia.empoweredtechinnovations.org/api/v1/admin/orders/stats`
- **Response `200 OK`**:
```json
{
  "success": true,
  "data": {
    "total_orders": 45,
    "pending_review": 6,
    "pending_payment_verification": 3,
    "approved_orders": 28,
    "rejected_orders": 4,
    "total_value": 458000.00,
    "formatted_total_value": "₹458,000.00"
  }
}
```

---

### 4.3.2 List Orders (Paginated with Filters)
- **HTTP Method**: `GET`
- **Full URL**: `https://konnadia.empoweredtechinnovations.org/api/v1/admin/orders`
- **Query Parameters**:
  - `search` *(optional string)*: Search by order number, customer name, or company.
  - `status` *(optional string)*: `submitted`, `under_review`, `pending_payment_verification`, `pending_approval`, `approved`, `partially_dispatched`, `dispatched`, `rejected`, `cancelled`.
  - `checkout_method` *(optional string)*: `credit` or `upfront`.
  - `payment_status` *(optional string)*: `pending`, `verified`, `rejected`.
  - `credit_status` *(optional string)*: `approved`, `over_limit`.
  - `date_from` / `date_to` *(optional YYYY-MM-DD)*: Date range.

---

### 4.3.3 Get Order Details (With Allowed Actions)
- **HTTP Method**: `GET`
- **Full URL**: `https://konnadia.empoweredtechinnovations.org/api/v1/admin/orders/{idOrNumber}`
- **Response `200 OK`**: Returns complete order details with customer profile, items list, unit conversion breakdown, payment receipt files, status timeline, and `allowed_actions` object.

---

### 4.3.4 Order Status Action Endpoints
- **Mark Under Review**: `POST /api/v1/admin/orders/{id}/mark-under-review` (`{ "admin_comment": "Reviewing" }`)
- **Verify Receipt**: `POST /api/v1/admin/orders/{id}/verify-receipt` (`{ "admin_comment": "Receipt verified" }`)
- **Reject Receipt**: `POST /api/v1/admin/orders/{id}/reject-receipt` (`{ "rejection_reason": "Illegible receipt photo" }`)
- **Approve Order**: `POST /api/v1/admin/orders/{id}/approve` (`{ "admin_comment": "Approved" }`)
- **Reject Order**: `POST /api/v1/admin/orders/{id}/reject` (`{ "rejection_reason": "Out of stock" }`)
- **Cancel Order**: `POST /api/v1/admin/orders/{id}/cancel` (`{ "admin_comment": "Cancelled per customer request" }`)

---

### 4.3.5 Single & Fractional Item Dispatch
Dispatch a single order item with support for fractional quantities (e.g. entering `0.5` sets).

- **HTTP Method**: `POST`
- **Full URL**: `https://konnadia.empoweredtechinnovations.org/api/v1/admin/orders/items/{itemId}/dispatch`
- **Request Body (JSON)**:
```json
{
  "quantity": 0.5,
  "note": "Dispatching 2 pieces out of 4 (0.5 set)"
}
```

- **Response `200 OK`**:
```json
{
  "success": true,
  "message": "Order item dispatched successfully.",
  "data": {
    "dispatch_number": "DISP-KT-ORD-90001-1",
    "dispatched_quantity": 0.5,
    "order": { /* Updated order object */ }
  }
}
```

---

### 4.3.6 Bulk Items Dispatch
Dispatch multiple order items simultaneously.

- **HTTP Method**: `POST`
- **Full URL**: `https://konnadia.empoweredtechinnovations.org/api/v1/admin/orders/{id}/bulk-dispatch`
- **Request Body (JSON)**:
```json
{
  "items": [
    { "order_item_id": 101, "quantity": 1.0 },
    { "order_item_id": 102, "quantity": 0.5 }
  ],
  "note": "Bulk dispatch batch #1"
}
```

---

### 4.3.7 Cancel Single Order Item
- **HTTP Method**: `POST`
- **Full URL**: `https://konnadia.empoweredtechinnovations.org/api/v1/admin/orders/items/{itemId}/cancel`
