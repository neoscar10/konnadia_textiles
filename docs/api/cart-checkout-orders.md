# Cart, Checkout, and Orders API Documentation

This document describes the REST API endpoints available for the mobile Flutter application for managing the customer shopping cart, performing checkout operations, and tracking order statuses.

All requests must include the JWT authentication token in the headers:
```http
Authorization: Bearer <your_jwt_token>
```

---

## 1. Shopping Cart API

### Get Cart Details
Retrieve the current active cart items, subtotal, GST, and grand total.

- **URL:** `/api/v1/cart`
- **Method:** `GET`
- **Headers:** `Accept: application/json`

**Success Response (200 OK):**
```json
{
  "success": true,
  "message": "Cart retrieved successfully.",
  "data": {
    "id": 12,
    "status": "active",
    "items_count": 1,
    "items": [
      {
        "id": 24,
        "product": {
          "id": 5,
          "slug": "silk-saree",
          "title": "Silk Saree",
          "sku": "SLK-001",
          "image_url": "http://localhost/storage/products/saree.jpg"
        },
        "combination": {
          "id": 3,
          "label": "Red / Gold",
          "values": ["Red", "Gold"]
        },
        "unit": {
          "id": 1,
          "name": "Meter",
          "short_code": "Mtr",
          "conversion_to_base": 1.0,
          "label": "Meter (1 Pc)"
        },
        "quantity": 10,
        "base_quantity": 10,
        "pricing": {
          "currency": "INR",
          "base_unit_price": 5000.0,
          "customer_unit_price": 4500.0,
          "line_subtotal": 45000.0,
          "gst_percentage": 5.0,
          "gst_amount": 2250.0,
          "line_total": 47250.0,
          "formatted_line_total": "₹47,250.00"
        },
        "availability": {
          "available_quantity": 100,
          "is_available": true,
          "message": "In Stock"
        }
      }
    ],
    "summary": {
      "currency": "INR",
      "subtotal": 45000.0,
      "gst_amount": 2250.0,
      "total": 47250.0,
      "formatted_subtotal": "₹45,000.00",
      "formatted_gst_amount": "₹2,250.00",
      "formatted_total": "₹47,250.00"
    }
  }
}
```

---

### Add Item to Cart
Add a product, combination, and unit to the shopping cart.

- **URL:** `/api/v1/cart/items`
- **Method:** `POST`
- **Headers:** `Content-Type: application/json`

**Request Body Parameters:**
| Parameter | Type | Required | Description |
|---|---|---|---|
| `product_id` | Integer | Yes | ID of the product. |
| `combination_id` | Integer | No | ID of the variation combination (nullable). |
| `unit_id` | Integer | Yes | ID of the unit (Meter, Bundle, Box etc.). |
| `quantity` | Integer | Yes | Quantity to add (minimum 1). |

**Success Response (201 Created):**
*(Returns the updated cart payload, same as GET `/cart`)*

---

### Update Cart Item
Modify the quantity or unit of an existing item in the cart.

- **URL:** `/api/v1/cart/items/{cartItem}`
- **Method:** `PATCH`
- **Headers:** `Content-Type: application/json`

**Request Body Parameters:**
| Parameter | Type | Required | Description |
|---|---|---|---|
| `quantity` | Integer | Yes | New quantity (minimum 1). |
| `unit_id` | Integer | No | Modify unit type (optional). |

**Success Response (200 OK):**
*(Returns the updated cart payload)*

---

### Remove Cart Item
Remove a specific item from the cart.

- **URL:** `/api/v1/cart/items/{cartItem}`
- **Method:** `DELETE`

**Success Response (200 OK):**
*(Returns the updated cart payload)*

---

### Clear Cart
Remove all items from the active cart.

- **URL:** `/api/v1/cart`
- **Method:** `DELETE`

**Success Response (200 OK):**
*(Returns the empty cart payload)*

---

## 2. Checkout API

### Get Checkout Summary
Retrieve checkout summary info, including current totals, customer credit details, and credit eligibility check.

- **URL:** `/api/v1/checkout/summary`
- **Method:** `GET`

**Success Response (200 OK):**
```json
{
  "success": true,
  "message": "Checkout summary retrieved successfully.",
  "data": {
    "cart": {
      "items_count": 1,
      "items": [...],
      "summary": {
        "currency": "INR",
        "subtotal": 45000.0,
        "gst_amount": 2250.0,
        "total": 47250.0,
        "formatted_total": "₹47,250.00"
      }
    },
    "customer_credit": {
      "credit_limit": 100000.0,
      "outstanding_amount": 10000.0,
      "available_credit": 90000.0,
      "overdue_amount": 0.0,
      "allow_credit_beyond_limit": false
    },
    "credit_eligibility": {
      "can_use_credit": true,
      "is_within_limit": true,
      "is_privileged_override": false,
      "credit_limit": 100000.0,
      "available_credit": 90000.0,
      "order_total": 47250.0,
      "excess_amount": 0.0,
      "message": "Eligible for credit purchase."
    },
    "checkout_methods": [
      {
        "value": "manual_payment",
        "label": "Manual Payment with Receipt",
        "enabled": true,
        "description": "Upload proof of payment for admin verification."
      },
      {
        "value": "credit",
        "label": "Use Available Credit",
        "enabled": true,
        "description": "Eligible for credit purchase."
      }
    ]
  }
}
```

---

### Submit Order (Checkout)
Submit an order from the active cart. Supports two payment methods: manual payment (multipart/form-data upload of a receipt) or credit.

- **URL:** `/api/v1/checkout/submit`
- **Method:** `POST`
- **Headers:** `Content-Type: multipart/form-data`

**Request Body Parameters:**
| Parameter | Type | Required | Description |
|---|---|---|---|
| `checkout_method` | String | Yes | Either `manual_payment` or `credit`. |
| `receipt_file` | File | Yes (if manual) | Image or PDF of receipt (max 5MB). |
| `customer_notes` | String | No | Custom customer note (max 1000 chars). |

**Success Response (201 Created):**
*(Returns full order detail, same as GET `/orders/{order}`)*

---

## 3. Orders API

### List Orders
Retrieve a paginated list of the customer's submitted orders.

- **URL:** `/api/v1/orders`
- **Method:** `GET`

**Query Parameters:**
- `status`: Filter by status (e.g., `submitted`, `approved`, `pending_payment_verification`, etc.).
- `search`: Search by order number.
- `date_from`, `date_to`: Date range filters.
- `per_page`: Number of items (max 50, default 10).
- `page`: Page index (default 1).

**Success Response (200 OK):**
```json
{
  "success": true,
  "message": "Orders retrieved successfully.",
  "data": [
    {
      "id": 1,
      "order_number": "ORD-2026-0001",
      "status": "submitted",
      "status_label": "Submitted",
      "checkout_method": "credit",
      "checkout_method_label": "Credit Purchase",
      "payment_status": "not_required",
      "credit_status": "within_limit",
      "totals": {
        "currency": "INR",
        "subtotal": 4000.0,
        "gst_amount": 200.0,
        "total_amount": 4200.0,
        "formatted_total": "₹4,200.00"
      },
      "items_count": 1,
      "submitted_at": "2026-06-16T16:00:00+01:00",
      "created_at": "2026-06-16T16:00:00+01:00"
    }
  ],
  "links": {
    "first": "http://localhost/api/v1/orders?page=1",
    "last": "http://localhost/api/v1/orders?page=1",
    "prev": null,
    "next": null
  },
  "meta": {
    "current_page": 1,
    "from": 1,
    "last_page": 1,
    "path": "http://localhost/api/v1/orders",
    "per_page": 10,
    "to": 1,
    "total": 1
  }
}
```

---

### Get Order Details
Retrieve full details of a specific order including items, receipt files, and timeline.

- **URL:** `/api/v1/orders/{order}`
- **Method:** `GET`

**Success Response (200 OK):**
```json
{
  "success": true,
  "message": "Order retrieved successfully.",
  "data": {
    "id": 1,
    "order_number": "ORD-2026-0001",
    "status": "submitted",
    "status_label": "Submitted",
    "checkout_method": "credit",
    "checkout_method_label": "Credit Purchase",
    "payment_status": "not_required",
    "credit_status": "within_limit",
    "customer_notes": "Urgent delivery requested.",
    "credit_limit_at_order": 10000.0,
    "available_credit_at_order": 10000.0,
    "used_credit_override_privilege": false,
    "totals": {
      "currency": "INR",
      "subtotal": 4000.0,
      "gst_amount": 200.0,
      "total_amount": 4200.0,
      "formatted_total": "₹4,200.00"
    },
    "items": [
      {
        "id": 1,
        "product": {
          "id": 1,
          "title": "Test Cotton Product",
          "sku": "COT-001",
          "image_url": "http://localhost/storage/products/cotton.jpg"
        },
        "combination": null,
        "unit": {
          "name": "Meter",
          "short_code": "Mtr",
          "conversion_to_base": 1.0,
          "label": "Meter (1 Pc)"
        },
        "quantity": 50,
        "base_quantity": 50,
        "pricing": {
          "base_unit_price": 100.0,
          "customer_unit_price": 80.0,
          "line_subtotal": 4000.0,
          "gst_percentage": 5.0,
          "gst_amount": 200.0,
          "line_total": 4200.0,
          "formatted_line_total": "₹4,200.00"
        }
      }
    ],
    "receipts": [],
    "timeline": [
      {
        "id": 1,
        "from_status": null,
        "to_status": "submitted",
        "note": "Order submitted by customer.",
        "changed_by": {
          "id": 1,
          "name": "Test Customer"
        },
        "created_at": "2026-06-16T16:00:00+01:00"
      }
    ],
    "submitted_at": "2026-06-16T16:00:00+01:00",
    "created_at": "2026-06-16T16:00:00+01:00"
  }
}
```
