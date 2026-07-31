# Admin Dashboard Analytics & Contact Messages Mobile API Integration Guide
**Kanodia Textiles — Enterprise Manufacturing & ERP Platform**  
**Version:** 1.0  
**Base URL:** `https://konnadia.empoweredtechinnovations.org/api/v1`  
**Authentication Standard:** JWT Bearer Token (`Authorization: Bearer <token>`)

---

## TABLE OF CONTENTS
1. [Overview & Authentication](#1-overview--authentication)
2. [Section A: Admin Dashboard Page Analytics (`/api/v1/admin/dashboard`)](#section-a-admin-dashboard-page-analytics-apiv1admindashboard)
   - [A.1 Dashboard Endpoint Specification](#a1-dashboard-endpoint-specification)
   - [A.2 Field Definitions & Data Structure](#a2-field-definitions--data-structure)
   - [A.3 Complete Sample Response](#a3-complete-sample-response)
3. [Section B: Contact Messages System](#section-b-contact-messages-system)
   - [B.1 Public Customer Contact Form Submission (`POST /api/v1/contact`)](#b1-public-customer-contact-form-submission-post-apiv1contact)
   - [B.2 Admin Contact Messages Summary Statistics (`GET /api/v1/admin/contact-messages/stats`)](#b2-admin-contact-messages-summary-statistics-get-apiv1admincontact-messagesstats)
   - [B.3 Admin List Contact Messages (`GET /api/v1/admin/contact-messages`)](#b3-admin-list-contact-messages-get-apiv1admincontact-messages)
   - [B.4 Admin View Contact Message Detail (`GET /api/v1/admin/contact-messages/{id}`)](#b4-admin-view-contact-message-detail-get-apiv1admincontact-messagesid)
   - [B.5 Admin Mark Message as Unread (`PATCH /api/v1/admin/contact-messages/{id}/mark-unread`)](#b5-admin-mark-message-as-unread-patch-apiv1admincontact-messagesidmark-unread)
   - [B.6 Admin Delete Contact Message (`DELETE /api/v1/admin/contact-messages/{id}`)](#b6-admin-delete-contact-message-delete-apiv1admincontact-messagesid)
4. [Section C: Error Handling & Status Codes](#section-c-error-handling--status-codes)

---

## 1. Overview & Authentication

The **Admin Dashboard Analytics** and **Admin Contact Messages Management** endpoints provide complete API parity with the web administration panel.

### Required Headers
```http
Content-Type: application/json
Accept: application/json
Authorization: Bearer <admin_jwt_token>
```
*(Note: Public Contact Submission `POST /api/v1/contact` does not require authentication).*

---

## SECTION A: ADMIN DASHBOARD PAGE ANALYTICS (`/api/v1/admin/dashboard`)

The Admin Dashboard API consolidates executive KPI metrics, sales performance, pending approvals, credit exposure, stock alerts, recent orders, and recent customers into a single high-performance payload.

---

### A.1 Dashboard Endpoint Specification

- **HTTP Method**: `GET`
- **Endpoint**: `https://konnadia.empoweredtechinnovations.org/api/v1/admin/dashboard`
- **Authentication**: Admin Bearer Token
- **Query Parameters**:
  - `date_range` *(optional string, default `"30_days"`)*:
    - `"today"`: Filter metrics for today.
    - `"7_days"`: Last 7 days.
    - `"30_days"`: Last 30 days (default).
    - `"90_days"`: Last 90 days.
    - `"this_month"`: Current calendar month.
    - `"this_year"`: Current calendar year.
    - `"all_time"`: All historical records.

---

### A.2 Field Definitions & Data Structure

The returned payload contains 11 core data blocks:

1. **`kpis`**: Executive metric cards array (Total Customers, Active Customers, Total Products, Pending Orders, Approved Orders, Credit Exposure, Low Stock Items).
2. **`orders`**: Order breakdown by status, total order count, and total monetary value.
3. **`customers`**: Total customers, active vs inactive count, new customer count for period, and breakdown by customer level.
4. **`products`**: Total products, active vs inactive count, and catalog count.
5. **`credit`**: Total credit limit sum, outstanding balance sum, available credit sum, utilization %, and counts for customers over limit or on credit hold.
6. **`inventory`**: Low stock count, out of stock products count, and total stock volume.
7. **`pending_approvals`**: Count and list of orders awaiting admin review/approval (`submitted`, `under_review`, `pending_approval`).
8. **`recent_customers`**: Recent customer signups with company details and outstanding credit status.
9. **`recent_orders`**: Recent placed orders list with order number, customer name, status, and formatted total amount.
10. **`alerts`**: Real-time system warning items (missing GST configuration, credit limit breaches, low stock alerts, pending order approvals).
11. **`quick_actions`**: Recommended navigation action items.

---

### A.3 Complete Sample Response

#### `GET /api/v1/admin/dashboard?date_range=30_days` (`200 OK`)
```json
{
  "success": true,
  "data": {
    "kpis": [
      {
        "key": "total_customers",
        "label": "Total Customers",
        "value": 150,
        "formatted_value": "150",
        "icon": "groups",
        "trend": null,
        "status": { "value": "normal", "label": "Active Network" },
        "route": "/admin/customers"
      },
      {
        "key": "active_customers",
        "label": "Active Customers",
        "value": 142,
        "formatted_value": "142",
        "icon": "person_check",
        "trend": null,
        "status": { "value": "normal", "label": "Online" },
        "route": "/admin/customers"
      },
      {
        "key": "total_products",
        "label": "Total Products",
        "value": 420,
        "formatted_value": "420",
        "icon": "inventory_2",
        "trend": null,
        "status": { "value": "normal", "label": "Catalog" },
        "route": "/admin/products"
      },
      {
        "key": "pending_orders",
        "label": "Pending Orders",
        "value": 6,
        "formatted_value": "6",
        "icon": "pending_actions",
        "trend": null,
        "status": { "value": "warning", "label": "Action Needed" },
        "route": "/admin/orders?status=pending"
      },
      {
        "key": "approved_orders",
        "label": "Approved Orders",
        "value": 78,
        "formatted_value": "78",
        "icon": "check_circle",
        "trend": null,
        "status": { "value": "success", "label": "On Track" },
        "route": "/admin/orders?status=approved"
      },
      {
        "key": "credit_exposure",
        "label": "Credit Exposure",
        "value": 450000.0,
        "formatted_value": "₹4.50L",
        "icon": "account_balance_wallet",
        "trend": null,
        "status": { "value": "info", "label": "Outstanding" },
        "route": "/admin/credit-management"
      },
      {
        "key": "low_stock_items",
        "label": "Low Stock Items",
        "value": 3,
        "formatted_value": "3",
        "icon": "warning",
        "trend": null,
        "status": { "value": "warning", "label": "Reorder" },
        "route": "/admin/inventory"
      }
    ],
    "orders": {
      "total_orders": 84,
      "by_status": {
        "approved": { "status": "approved", "count": 78, "total_value": "1250000.00" },
        "under_review": { "status": "under_review", "count": 6, "total_value": "95000.00" }
      },
      "total_value": 1345000.0,
      "formatted_total_value": "₹13.45L"
    },
    "customers": {
      "total_customers": 150,
      "active_customers": 142,
      "inactive_customers": 8,
      "new_customers_period": 12,
      "by_level": [
        { "customer_level_id": 1, "count": 80 },
        { "customer_level_id": 2, "count": 70 }
      ]
    },
    "products": {
      "total_products": 420,
      "active_products": 410,
      "inactive_products": 10
    },
    "credit": {
      "total_credit_limit": 1000000.0,
      "formatted_total_limit": "₹10.00L",
      "total_outstanding": 450000.0,
      "formatted_outstanding": "₹4.50L",
      "available_credit": 550000.0,
      "formatted_available": "₹5.50L",
      "utilization_percent": 45.0,
      "customers_over_limit": 1,
      "customers_near_limit": 3,
      "on_hold_count": 0
    },
    "inventory": {
      "low_stock_threshold": 10,
      "low_stock_count": 3,
      "total_stock_value": 15400.0,
      "products_out_of_stock": 0
    },
    "pending_approvals": {
      "count": 6,
      "orders": [
        {
          "id": 102,
          "order_number": "KT-ORD-100008",
          "customer_name": "Surat Textiles Ltd",
          "customer_id": 5,
          "status": "under_review",
          "total_amount": 12500.0,
          "formatted_amount": "₹12,500.00",
          "created_at": "2026-07-30T18:00:00.000000Z",
          "formatted_date": "30 Jul 2026"
        }
      ]
    },
    "recent_customers": [
      {
        "id": 15,
        "company_name": "Apex Traders",
        "customer_number": "KT-015",
        "contact_person": "Rajesh Sharma",
        "contact_number": "+91 9876543210",
        "is_active": true,
        "status": "Active",
        "level_name": "Wholesale Tier A",
        "outstanding_credit": 0.0,
        "credit_limit": 50000.0,
        "initials": "AP"
      }
    ],
    "recent_orders": [
      {
        "id": 102,
        "order_number": "KT-ORD-100008",
        "customer_name": "Surat Textiles Ltd",
        "customer_id": 5,
        "status": "under_review",
        "status_label": "Under review",
        "total_amount": 12500.0,
        "formatted_amount": "₹12,500.00",
        "created_at": "2026-07-30T18:00:00.000000Z",
        "formatted_date": "30 Jul 2026, 06:00 PM"
      }
    ],
    "alerts": [
      {
        "id": "pending_approvals",
        "type": "info",
        "icon": "info",
        "title": "Pending Order Approvals",
        "message": "6 order(s) awaiting approval.",
        "action_label": "Review Orders",
        "action_route": "/admin/orders?status=pending",
        "severity": "info"
      }
    ],
    "quick_actions": [
      { "icon": "person_add", "label": "Add Customer", "route": "/admin/customers" },
      { "icon": "add_circle", "label": "Add Product", "route": "/admin/products" },
      { "icon": "pending_actions", "label": "Review Orders", "route": "/admin/orders?status=pending" }
    ],
    "metadata": {
      "date_range": "30_days",
      "generated_at": "2026-07-31T20:50:00+00:00",
      "currency": "INR"
    }
  }
}
```

---

## SECTION B: CONTACT MESSAGES SYSTEM

The contact message system consists of two parts:
1. **Public Customer Submission**: Allows guest users or mobile app customers to send inquiries via the contact form.
2. **Admin Management Suite**: Allows administrators to view summary statistics, list messages, view message details, mark messages as unread, and delete messages.

---

### B.1 Public Customer Contact Form Submission (`POST /api/v1/contact`)

Mobile app users and website visitors can submit contact form inquiries directly to the admin dashboard.

- **HTTP Method**: `POST`
- **Endpoint**: `https://konnadia.empoweredtechinnovations.org/api/v1/contact`
- **Authentication**: None (Public Open Endpoint)
- **Headers**: `Content-Type: application/json`, `Accept: application/json`

#### Request Body
```json
{
  "name": "Karan Sharma",
  "email": "karan@gmail.com",
  "phone": "+91 9876543210",
  "subject": "Bulk Wholesale Inquiry",
  "message": "We would like to request catalog prices and minimum order quantities for bulk saree purchases."
}
```

#### Request Validation Rules
- `name` *(string, required, max 180 chars)*: Full name of sender.
- `email` *(string, required, valid email, max 180 chars)*: Email address.
- `phone` *(string, optional, max 30 chars)*: Contact number.
- `subject` *(string, optional, max 255 chars)*: Subject (defaults to `"General Inquiry"` if omitted).
- `message` *(string, required, max 3000 chars)*: Message body.

#### Success Response (`201 Created`)
```json
{
  "success": true,
  "message": "Thank you! Your message has been sent successfully. Our team will contact you shortly.",
  "data": {
    "id": 25,
    "created_at": "31-Jul-2026 20:51"
  }
}
```

---

### B.2 Admin Contact Messages Summary Statistics (`GET /api/v1/admin/contact-messages/stats`)

Retrieves counters for total messages, unread messages, and read messages to populate metric cards on the admin dashboard.

- **HTTP Method**: `GET`
- **Endpoint**: `https://konnadia.empoweredtechinnovations.org/api/v1/admin/contact-messages/stats`
- **Authentication**: Admin Bearer Token (Permission: `access contact-messages`)

#### Success Response (`200 OK`)
```json
{
  "success": true,
  "data": {
    "total_messages": 45,
    "unread_messages": 5,
    "read_messages": 40
  }
}
```

---

### B.3 Admin List Contact Messages (`GET /api/v1/admin/contact-messages`)

Retrieves a paginated list of received messages with search and read status filtering.

- **HTTP Method**: `GET`
- **Endpoint**: `https://konnadia.empoweredtechinnovations.org/api/v1/admin/contact-messages`
- **Authentication**: Admin Bearer Token (Permission: `access contact-messages`)
- **Query Parameters**:
  - `search` *(optional string)*: Search by sender name, email, phone, subject, or message text.
  - `status` *(optional string)*: Filter by `unread` or `read`.
  - `per_page` *(optional int, default 10)*: Number of items per page.
  - `page` *(optional int, default 1)*: Page number.

#### Sample Request
`GET /api/v1/admin/contact-messages?status=unread&search=Wholesale&per_page=10`

#### Success Response (`200 OK`)
```json
{
  "success": true,
  "data": [
    {
      "id": 25,
      "name": "Karan Sharma",
      "email": "karan@gmail.com",
      "phone": "+91 9876543210",
      "subject": "Bulk Wholesale Inquiry",
      "message": "We would like to request catalog prices for bulk saree purchases.",
      "is_read": false,
      "created_at": "31-Jul-2026 20:51"
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

### B.4 Admin View Contact Message Detail (`GET /api/v1/admin/contact-messages/{id}`)

Retrieves complete details for a single message. **Fetching the detail automatically marks `is_read = true`**.

- **HTTP Method**: `GET`
- **Endpoint**: `https://konnadia.empoweredtechinnovations.org/api/v1/admin/contact-messages/{id}`
- **Authentication**: Admin Bearer Token (Permission: `access contact-messages`)

#### Success Response (`200 OK`)
```json
{
  "success": true,
  "data": {
    "id": 25,
    "name": "Karan Sharma",
    "email": "karan@gmail.com",
    "phone": "+91 9876543210",
    "subject": "Bulk Wholesale Inquiry",
    "message": "We would like to request catalog prices for bulk saree purchases.",
    "is_read": true,
    "created_at": "31-Jul-2026 20:51"
  }
}
```

---

### B.5 Admin Mark Message as Unread (`PATCH /api/v1/admin/contact-messages/{id}/mark-unread`)

Reverts a message back to unread status (`is_read = false`).

- **HTTP Method**: `PATCH`
- **Endpoint**: `https://konnadia.empoweredtechinnovations.org/api/v1/admin/contact-messages/{id}/mark-unread`
- **Authentication**: Admin Bearer Token (Permission: `access contact-messages`)

#### Success Response (`200 OK`)
```json
{
  "success": true,
  "message": "Message marked as unread.",
  "data": {
    "id": 25,
    "is_read": false
  }
}
```

---

### B.6 Admin Delete Contact Message (`DELETE /api/v1/admin/contact-messages/{id}`)

Deletes a contact message record.

- **HTTP Method**: `DELETE`
- **Endpoint**: `https://konnadia.empoweredtechinnovations.org/api/v1/admin/contact-messages/{id}`
- **Authentication**: Admin Bearer Token (Permission: `access contact-messages`)

#### Success Response (`200 OK`)
```json
{
  "success": true,
  "message": "Message deleted successfully."
}
```

---

## SECTION C: ERROR HANDLING & STATUS CODES

Standard HTTP status codes are returned across all endpoints:

| Status Code | Meaning | Cause / Solution |
| :--- | :--- | :--- |
| `200 OK` | Request Successful | Standard response payload returned. |
| `201 Created` | Resource Created | Public contact form submitted or admin resource created. |
| `401 Unauthorized` | Invalid Token | Expired, missing, or malformed JWT token. Re-authenticate via `/api/v1/admin/auth/login`. |
| `403 Forbidden` | Insufficient Permissions | Admin user lacks required permission (e.g. `access contact-messages`). |
| `404 Not Found` | Resource Not Found | Invalid message ID or invalid path. |
| `422 Unprocessable Entity` | Validation Failed | Missing required fields (e.g., `name` or `email` omitted in contact form). Returns JSON object mapping field names to error messages. |
| `500 Server Error` | Unexpected Exception | Internal error; consult server logs. |

---

## 🛠️ Code Implementation Files & Test Verification

- **Dashboard Controller**: [AdminDashboardAnalyticsController.php](file:///c:/Users/USER/Desktop/projects/Konnadia%20textiles/app/Http/Controllers/Api/V1/Admin/AdminDashboardAnalyticsController.php)
- **Public Contact Controller**: [PublicContactController.php](file:///c:/Users/USER/Desktop/projects/Konnadia%20textiles/app/Http/Controllers/Api/V1/PublicContactController.php)
- **Admin Contact Controller**: [AdminContactMessageController.php](file:///c:/Users/USER/Desktop/projects/Konnadia%20textiles/app/Http/Controllers/Api/V1/Admin/AdminContactMessageController.php)
- **Routes Configuration**: Registered in [routes/api.php](file:///c:/Users/USER/Desktop/projects/Konnadia%20textiles/routes/api.php)
- **Unit & Feature Test Suite**: [AdminSettingsAndContentApiTest.php](file:///c:/Users/USER/Desktop/projects/Konnadia%20textiles/tests/Feature/Admin/AdminSettingsAndContentApiTest.php) (9/9 passed, 36 assertions)
