# Admin Complete Integration Guide & API Reference
*(Home Content CMS, Staff & Admin Users, System Settings, Contact Messages, Dashboard Analytics, Credit Management, Reports & Analytics, Units, and Notifications)*

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
1. [Section 1: Dashboard Analytics & Overview (`/admin/dashboard`)](#section-1-dashboard-analytics--overview-admindashboard)
2. [Section 2: Home Content Management (`/admin/home-content`)](#section-2-home-content-management-adminhome-content)
3. [Section 3: Staff & Admin Users Management (`/admin/admins`)](#section-3-staff--admin-users-management-adminadmins)
4. [Section 4: Credit Management (`/admin/credit-management`)](#section-4-credit-management-admincredit-management)
5. [Section 5: System Settings & Profile (`/admin/settings`)](#section-5-system-settings--profile-adminsettings)
6. [Section 6: Contact Messages & Support (`/admin/contact-messages`)](#section-6-contact-messages--support-admincontact-messages)
7. [Section 7: Business Reports & Analytics (`/admin/reports`)](#section-7-business-reports--analytics-adminreports)
8. [Section 8: Units Configuration & Notifications (`/admin/units`, `/admin/notifications`)](#section-8-units-configuration--notifications-adminunits-adminnotifications)

---

# SECTION 1: DASHBOARD ANALYTICS & OVERVIEW (`/admin/dashboard`)

## 1.1 Permission & Access Control
- **Required Permission**: Authenticated Admin

## 1.2 Endpoints Specification

### 1.2.1 Get Admin Dashboard Analytics & KPIs
Retrieve total customer network count, active catalog count, pending order action counts, credit exposure sum, low stock warning counters, recent orders list, recent customers list, and system alerts.

- **HTTP Method**: `GET`
- **Full URL**: `https://konnadia.empoweredtechinnovations.org/api/v1/admin/dashboard`
- **Query Parameters**:
  - `date_range` *(optional string)*: `today`, `7_days`, `30_days`, `90_days`, `this_month`, `this_year`, or `all_time` (default `30_days`).

- **Response `200 OK`**:
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
        "route": "/admin/customers"
      },
      {
        "key": "pending_orders",
        "label": "Pending Orders",
        "value": 6,
        "formatted_value": "6",
        "icon": "pending_actions",
        "route": "/admin/orders?status=pending"
      },
      {
        "key": "credit_exposure",
        "label": "Credit Exposure",
        "value": 450000.0,
        "formatted_value": "₹4.50L",
        "icon": "account_balance_wallet",
        "route": "/admin/credit-management"
      }
    ],
    "orders": {
      "total_orders": 85,
      "total_value": 1250000.0,
      "formatted_total_value": "₹12.50L"
    },
    "recent_orders": [
      {
        "id": 101,
        "order_number": "KT-ORD-90001",
        "customer_name": "Surat Textiles Ltd",
        "status": "approved",
        "total_amount": 4480.0,
        "formatted_amount": "₹4,480.00"
      }
    ],
    "alerts": [
      {
        "id": "pending_approvals",
        "type": "info",
        "title": "Pending Order Approvals",
        "message": "6 order(s) awaiting approval."
      }
    ],
    "metadata": {
      "date_range": "30_days",
      "currency": "INR"
    }
  }
}
```

---

# SECTION 2: HOME CONTENT MANAGEMENT (`/admin/home-content`)

## 2.1 Permission & Access Control
- **Required Permission**: `access home-content`

## 2.2 Endpoints Specification

### 2.2.1 Get Home Content Statistics
Retrieve summary metric cards for home content sections.

- **HTTP Method**: `GET`
- **Full URL**: `https://konnadia.empoweredtechinnovations.org/api/v1/admin/home-content/stats`

---

### 2.2.2 List Home Content Sections
- **HTTP Method**: `GET`
- **Full URL**: `https://konnadia.empoweredtechinnovations.org/api/v1/admin/home-content`

---

### 2.2.3 Fetch Options & Pickers Reference Data
- **HTTP Method**: `GET`
- **Full URL**: `https://konnadia.empoweredtechinnovations.org/api/v1/admin/home-content/options`

---

### 2.2.4 Reorder Home Content Sections
- **HTTP Method**: `POST`
- **Full URL**: `https://konnadia.empoweredtechinnovations.org/api/v1/admin/home-content/reorder`
- **Request Body (JSON)**: `{ "ordered_ids": [3, 1, 2, 4] }`

---

# SECTION 3: STAFF & ADMIN USERS MANAGEMENT (`/admin/admins`)

## 3.1 Permission & Access Control
- **Required Permission**: `access admins`

## 3.2 Endpoints Specification

### 3.3.1 Fetch Permissions Checklist
- **HTTP Method**: `GET`
- **Full URL**: `https://konnadia.empoweredtechinnovations.org/api/v1/admin/admins/permissions-list`

---

### 3.3.2 List & Manage Admin Users
- **List Admins**: `GET /api/v1/admin/admins`
- **Create Admin**: `POST /api/v1/admin/admins` (`{ "name": "Staff Name", "email": "staff@konnadia.com", "password": "pass", "password_confirmation": "pass", "permissions": ["access products", "access orders"] }`)
- **Update Admin**: `PUT /api/v1/admin/admins/{id}`
- **Toggle Status**: `PATCH /api/v1/admin/admins/{id}/toggle-status`
- **Delete Admin**: `DELETE /api/v1/admin/admins/{id}`

---

# SECTION 4: CREDIT MANAGEMENT (`/admin/credit-management`)

## 4.1 Permission & Access Control
- **Required Permission**: Authenticated Admin

## 4.2 Endpoints Specification

### 4.2.1 Get Credit Exposure Statistics
- **HTTP Method**: `GET`
- **Full URL**: `https://konnadia.empoweredtechinnovations.org/api/v1/admin/credit-management/stats`

---

### 4.2.2 List Customers with Credit Limits & Outstanding Amounts
- **HTTP Method**: `GET`
- **Full URL**: `https://konnadia.empoweredtechinnovations.org/api/v1/admin/credit-management`
- **Query Parameters**: `search`, `level_id`, `credit_status`, `allow_beyond_limit`, `credit_hold`, `per_page`, `page`.

---

### 4.2.3 View Customer Credit Ledger
- **HTTP Method**: `GET`
- **Full URL**: `https://konnadia.empoweredtechinnovations.org/api/v1/admin/credit-management/customers/{id}/ledger`

---

### 4.2.4 Credit Action Endpoints
- **Update Credit Limit**: `POST /api/v1/admin/credit-management/customers/{id}/limit` (`{ "credit_limit": 150000, "note": "Tier upgrade" }`)
- **Record Customer Payment**: `POST /api/v1/admin/credit-management/customers/{id}/payment` (`{ "amount": 25000, "note": "Cheque clearance" }`)
- **Adjust Outstanding Balance**: `POST /api/v1/admin/credit-management/customers/{id}/adjust` (`{ "amount": 1000, "direction": "decrease", "note": "Discount credit" }`)
- **Toggle Credit Privilege**: `PATCH /api/v1/admin/credit-management/customers/{id}/toggle-beyond-limit`
- **Place Credit Hold**: `POST /api/v1/admin/credit-management/customers/{id}/hold` (`{ "reason": "Overdue payment" }`)
- **Release Credit Hold**: `POST /api/v1/admin/credit-management/customers/{id}/release-hold` (`{ "note": "Payment settled" }`)

---

# SECTION 5: SYSTEM SETTINGS & PROFILE (`/admin/settings`)

## 5.1 Endpoints Specification
- **Get Profile & Permissions**: `GET /api/v1/admin/settings`
- **Change Password**: `POST /api/v1/admin/settings/change-password` (`{ "current_password": "old", "new_password": "new", "new_password_confirmation": "new" }`)

---

# SECTION 6: CONTACT MESSAGES & SUPPORT (`/admin/contact-messages`)

## 6.1 Endpoints Specification
- **Stats**: `GET /api/v1/admin/contact-messages/stats`
- **Listing**: `GET /api/v1/admin/contact-messages` (`search`, `status`)
- **View Detail (Auto Mark Read)**: `GET /api/v1/admin/contact-messages/{id}`
- **Mark Unread**: `PATCH /api/v1/admin/contact-messages/{id}/mark-unread`
- **Delete Message**: `DELETE /api/v1/admin/contact-messages/{id}`

---

# SECTION 7: BUSINESS REPORTS & ANALYTICS (`/admin/reports`)

## 7.1 Endpoints Specification
- **Sales Report**: `GET /api/v1/admin/reports/sales` (`date_from`, `date_to`)
- **Top Customers Performance**: `GET /api/v1/admin/reports/customers`
- **Inventory Stock Valuation**: `GET /api/v1/admin/reports/inventory`

---

# SECTION 8: UNITS CONFIGURATION & NOTIFICATIONS (`/admin/units`, `/admin/notifications`)

## 8.1 Endpoints Specification
- **Default Units Template**: `GET /api/v1/admin/units`
- **List Admin Notifications**: `GET /api/v1/admin/notifications`
- **Mark Single Notification Read**: `POST /api/v1/admin/notifications/mark-read` (`{ "notification_id": "uuid" }`)
- **Mark All Notifications Read**: `POST /api/v1/admin/notifications/mark-all-read`
