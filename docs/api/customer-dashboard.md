# Customer Dashboard API

Endpoint to retrieve B2B customer dashboard data for mobile applications (Flutter).

---

## Get Dashboard Details

Retrieve a comprehensive overview of the customer profile, credit status, cart status, order metrics, recent orders, alerts, quick actions, and recommended products.

- **URL:** `/api/v1/dashboard`
- **Method:** `GET`
- **Headers:**
  - `Accept: application/json`
  - `Authorization: Bearer <JWT_TOKEN>`

### Success Response

- **Status Code:** `200 OK`
- **Content-Type:** `application/json`

```json
{
  "success": true,
  "message": "Dashboard details retrieved successfully.",
  "data": {
    "customer": {
      "id": 1,
      "customer_number": "CUST-DB-99",
      "company_name": "Dashboard Test Corp",
      "contact_person": "Dashboard Contact",
      "level": "Gold Tier",
      "is_active": true
    },
    "credit": {
      "credit_limit": 200000.00,
      "outstanding_amount": 50000.00,
      "available_credit": 150000.00,
      "overdue_amount": 0.00,
      "credit_hold": false,
      "credit_hold_reason": null,
      "allow_credit_beyond_limit": false,
      "status": {
        "value": "healthy",
        "label": "Healthy",
        "badge": "success",
        "message": "Credit account in good standing."
      },
      "risk_level": {
        "level": "Low",
        "color": "text-[#0F8A46]"
      },
      "utilization_percentage": 25.0,
      "formatted_credit_limit": "₹2,00,000.00",
      "formatted_outstanding_amount": "₹50,000.00",
      "formatted_available_credit": "₹1,50,000.00",
      "formatted_overdue_amount": "₹0.00"
    },
    "cart": {
      "exists": true,
      "cart_id": 4,
      "items_count": 2,
      "total_amount": 12500.00,
      "formatted_total_amount": "₹12,500.00"
    },
    "orders": {
      "total_orders": 15,
      "pending_orders": 2,
      "approved_orders": 8,
      "rejected_orders": 1,
      "dispatched_orders": 4,
      "total_order_value": 450000.00,
      "formatted_total_order_value": "₹4,50,000.00"
    },
    "recent_orders": [
      {
        "id": 12,
        "order_number": "KT-ORD-100245",
        "status": {
          "label": "Under Review",
          "type": "warning",
          "badge": "warning"
        },
        "payment_status": {
          "label": "Not Required",
          "type": "secondary",
          "badge": "secondary"
        },
        "total_amount": 58800.00,
        "formatted_total_amount": "₹58,800.00",
        "items_count": 3,
        "submitted_at": "2026-06-12T10:15:30Z",
        "created_at": "2026-06-12T10:15:30Z"
      }
    ],
    "alerts": [
      {
        "type": "warning",
        "title": "Approaching Credit Limit",
        "message": "You have utilized over 85% of your credit limit.",
        "priority": 7
      }
    ],
    "quick_actions": [
      {
        "id": "shop_catalog",
        "label": "Browse Catalog",
        "icon": "shopping_bag",
        "route": "customer.products.index",
        "badge": null
      }
    ],
    "recommended_products": []
  }
}
```

### Error Responses

- **Status Code:** `401 Unauthorized` (Invalid/expired token)
- **Status Code:** `403 Forbidden` (User is an admin, inactive, or not a customer)
