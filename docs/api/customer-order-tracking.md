# Customer Mobile Order Tracking APIs

All endpoints require JWT Authentication (`Authorization: Bearer <token>`) and are restricted to users with the `customer` role.

Base URL: `[API_URL]/api/v1/orders`

---

## 1. List Orders
`GET /api/v1/orders`

Retrieves a paginated list of the customer's orders.

### Query Parameters (Optional)
- `page` (int)
- `per_page` (int, default 10)
- `status` (string, e.g. `submitted`, `approved`, `dispatched`, `rejected`)
- `checkout_method` (string, e.g. `manual_payment`, `credit`)
- `payment_status` (string, e.g. `pending_verification`, `verified`, `rejected`)
- `credit_status` (string)
- `search` (string) - Searches order number and product titles.
- `date_from` (YYYY-MM-DD)
- `date_to` (YYYY-MM-DD)
- `sort` (string, `newest`, `oldest`, `total_high`, `total_low`)

### Response
```json
{
  "success": true,
  "message": "Orders retrieved successfully.",
  "data": [
    {
      "id": 1,
      "order_number": "ORD-12345",
      "status": {
        "bg": "bg-emerald-50...",
        "text": "text-emerald-700",
        "label": "Approved",
        "type": "success"
      },
      "summary": {
        "currency": "INR",
        "total": 5000.00,
        "formatted_total": "₹5,000.00"
      },
      "items_count": 3,
      "first_item": {
        "product_title": "Denim Shirt",
        "image_url": "https://..."
      },
      "submitted_at": "2024-05-10T10:00:00Z",
      "created_at": "2024-05-10T10:00:00Z"
    }
  ],
  "meta": {
    "current_page": 1,
    "last_page": 5,
    "per_page": 10,
    "total": 50
  }
}
```

---

## 2. Order Summary Dashboard
`GET /api/v1/orders/summary`

Retrieves overview metrics for the customer's orders.

### Response
```json
{
  "success": true,
  "message": "Order summary retrieved successfully.",
  "data": {
    "total_orders": 50,
    "pending_orders": 2,
    "approved_orders": 40,
    "rejected_orders": 1,
    "dispatched_orders": 7,
    "pending_payment_verification": 1,
    "total_order_value": 250000.00,
    "formatted_total_order_value": "₹250,000.00",
    "last_order": {
      "order_number": "ORD-12345",
      "status": { "label": "Submitted", "type": "info" },
      "total": 5000.00,
      "formatted_total": "₹5,000.00"
    }
  }
}
```

---

## 3. Order Filters
`GET /api/v1/orders/filters`

Returns valid filter options for dropdowns in the mobile app.

---

## 4. Order Details
`GET /api/v1/orders/{order_id_or_number}`

Retrieves full details of a specific order. 

### Response (Partial)
```json
{
  "success": true,
  "message": "Order retrieved successfully.",
  "data": {
    "id": 1,
    "order_number": "ORD-12345",
    "status": { "label": "Approved", "type": "success" },
    "important_message": {
      "type": "success",
      "title": "Order approved",
      "message": "Your order has been approved and is being prepared."
    },
    "summary": {
      "currency": "INR",
      "subtotal": 4500.00,
      "gst_amount": 500.00,
      "total": 5000.00
    },
    "credit_snapshot": {
      "credit_limit_at_order": 10000.00,
      "available_credit_at_order": 10000.00,
      "used_credit_override_privilege": false
    },
    "items": [
      {
        "product_title": "Denim Shirt",
        "image_url": "https://...",
        "quantity": 10,
        "tax": {
          "hsn_code": "6205",
          "gst_percentage": 12,
          "gst_amount": 500.00
        },
        "pricing": {
          "currency": "INR",
          "base_unit_price": 450.00,
          "line_total": 5000.00
        }
      }
    ]
  }
}
```

---

## 5. Order Timeline
`GET /api/v1/orders/{order_id_or_number}/timeline`

Retrieves the status history timeline for an order.

### Response
```json
{
  "success": true,
  "data": [
    {
      "status": "approved",
      "label": "Approved",
      "badge": "success",
      "note": "Approved by admin",
      "created_at": "2024-05-11T10:00:00Z"
    }
  ]
}
```

---

## 6. Order Payment Receipt
`GET /api/v1/orders/{order_id_or_number}/receipt`

Retrieves the uploaded payment receipt for manual payment orders.

### Response
```json
{
  "success": true,
  "data": {
    "id": 1,
    "url": "https://.../storage/receipts/receipt.pdf",
    "original_name": "receipt.pdf",
    "mime_type": "application/pdf",
    "status": { "label": "Verified", "type": "success" },
    "admin_note": "Verified by accounts.",
    "uploaded_at": "2024-05-10T10:00:00Z"
  }
}
```
