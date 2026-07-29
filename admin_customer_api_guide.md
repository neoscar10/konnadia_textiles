# Customer Management & Discount Tiers Mobile API Integration Guide
**Kanodia Textiles — Enterprise Manufacturing & ERP Platform**  
**Version:** 1.0  
**Base URL:** `https://konnadia.empoweredtechinnovations.org/api/v1`

---

## 1. Overview & Authentication Standard

All Customer Management APIs require **JWT Bearer Token Authentication** and are restricted to Admin users with the **`access customers`** or **`access customer-levels`** permissions (or `super_admin`).

### Request Headers
```http
Content-Type: application/json
Accept: application/json
Authorization: Bearer <YOUR_JWT_TOKEN>
```

---

## 2. Customer Management Endpoints (`/api/v1/admin/customers`)

---

### 2.1 List All Customers
Retrieves paginated customers with search, customer level, and active status filters.

- **HTTP Method:** `GET`
- **Endpoint:** `https://konnadia.empoweredtechinnovations.org/api/v1/admin/customers`
- **Query Parameters:**
  - `search` *(optional)*: Matches customer number, company name, contact person, mobile number, email, or GST number.
  - `level_id` *(optional)*: Filter by `CustomerLevel` ID.
  - `status` *(optional)*: `active` or `inactive`.
  - `per_page` *(optional, default 15)*: Page size.

#### Sample Request
`GET /api/v1/admin/customers?search=Apex&status=active&per_page=10`

#### Success Response (`200 OK`)
```json
{
  "success": true,
  "message": "Customers retrieved successfully.",
  "data": {
    "customers": [
      {
        "id": 1,
        "customer_number": "KT-001",
        "company_name": "Apex Fabrics Pvt Ltd",
        "gst_number": "29ABCDE1234F1Z5",
        "contact_person": "Rajesh Sharma",
        "mobile_number": "9876543210",
        "email": "rajesh@apexfabrics.com",
        "customer_level_id": 2,
        "level": {
          "id": 2,
          "name": "Wholesale Tier A",
          "discount_percentage": 15.0,
          "default_credit_limit": 50000.0,
          "sort_order": 1,
          "is_active": true
        },
        "credit_limit": 75000.0,
        "outstanding_amount": 0.0,
        "available_credit": 75000.0,
        "overdue_amount": 0.0,
        "allow_credit_beyond_limit": false,
        "billing_address": "123 Textile Market\nSurat, Gujarat - 395002",
        "address": "123 Textile Market",
        "city": "Surat",
        "state": "Gujarat",
        "pincode": "395002",
        "is_active": true,
        "user": {
          "id": 15,
          "name": "Rajesh Sharma",
          "email": "rajesh@apexfabrics.com",
          "mobile_number": "9876543210",
          "is_active": true
        },
        "created_at": "2026-07-29T18:45:00+00:00"
      }
    ],
    "pagination": {
      "total": 1,
      "per_page": 10,
      "current_page": 1,
      "last_page": 1
    }
  }
}
```

---

### 2.2 Get Single Customer Details
Retrieves detailed profile, level, user login status, and credit limits for a single customer.

- **HTTP Method:** `GET`
- **Endpoint:** `https://konnadia.empoweredtechinnovations.org/api/v1/admin/customers/{id}`

#### Success Response (`200 OK`)
```json
{
  "success": true,
  "message": "Customer details retrieved successfully.",
  "data": {
    "id": 1,
    "customer_number": "KT-001",
    "company_name": "Apex Fabrics Pvt Ltd",
    "gst_number": "29ABCDE1234F1Z5",
    "contact_person": "Rajesh Sharma",
    "mobile_number": "9876543210",
    "email": "rajesh@apexfabrics.com",
    "customer_level_id": 2,
    "level": {
      "id": 2,
      "name": "Wholesale Tier A",
      "discount_percentage": 15.0
    },
    "credit_limit": 75000.0,
    "outstanding_amount": 0.0,
    "available_credit": 75000.0,
    "allow_credit_beyond_limit": false,
    "is_active": true
  }
}
```

---

### 2.3 Create New Customer
Creates a new customer profile, generates their unique Customer ID (`KT-XXX`), and creates a linked mobile/web user account.

- **HTTP Method:** `POST`
- **Endpoint:** `https://konnadia.empoweredtechinnovations.org/api/v1/admin/customers`

#### Request Body
```json
{
  "customer_level_id": 2,
  "company_name": "Apex Fabrics Pvt Ltd",
  "gst_number": "29ABCDE1234F1Z5",
  "contact_person": "Rajesh Sharma",
  "mobile_number": "9876543210",
  "email": "rajesh@apexfabrics.com",
  "credit_limit": 75000,
  "allow_credit_beyond_limit": false,
  "address": "123 Textile Market",
  "city": "Surat",
  "state": "Gujarat",
  "pincode": "395002",
  "is_active": true,
  "password_mode": "auto"
}
```
> **Password Modes:**
> - `"password_mode": "auto"` (Default): Auto-generates a secure password and returns it in `generated_password` inside the response.
> - `"password_mode": "manual"`: Pass `"password"` and `"password_confirmation"` explicitly.

#### Success Response (`201 Created`)
```json
{
  "success": true,
  "message": "Customer created successfully.",
  "data": {
    "id": 1,
    "customer_number": "KT-001",
    "company_name": "Apex Fabrics Pvt Ltd",
    "contact_person": "Rajesh Sharma",
    "mobile_number": "9876543210",
    "generated_password": "xK9mP2qL0v",
    "is_active": true
  }
}
```

---

### 2.4 Update Customer Profile
Updates existing customer information and syncs linked user credentials.

- **HTTP Method:** `PUT` or `PATCH`
- **Endpoint:** `https://konnadia.empoweredtechinnovations.org/api/v1/admin/customers/{id}`

#### Request Body
```json
{
  "company_name": "Apex Fabrics International",
  "contact_person": "Rajesh Kumar Sharma",
  "credit_limit": 100000,
  "allow_credit_beyond_limit": true
}
```

---

### 2.5 Toggle Active Status
Quickly activates or deactivates a customer and their user account.

- **HTTP Method:** `PATCH`
- **Endpoint:** `https://konnadia.empoweredtechinnovations.org/api/v1/admin/customers/{id}/toggle-status`

#### Success Response (`200 OK`)
```json
{
  "success": true,
  "message": "Customer deactivated successfully.",
  "data": {
    "id": 1,
    "customer_number": "KT-001",
    "is_active": false
  }
}
```

---

### 2.6 Admin Reset Customer Password
Allows administrators to update a customer's login password.

- **HTTP Method:** `POST`
- **Endpoint:** `https://konnadia.empoweredtechinnovations.org/api/v1/admin/customers/{id}/reset-password`

#### Request Body
```json
{
  "password": "newpassword123",
  "password_confirmation": "newpassword123"
}
```

---

### 2.7 Delete Customer
Soft deletes a customer and deactivates their linked user account.

- **HTTP Method:** `DELETE`
- **Endpoint:** `https://konnadia.empoweredtechinnovations.org/api/v1/admin/customers/{id}`

---

## 3. Customer Levels & Discount Tiers (`/api/v1/admin/customer-levels`)

Customer Levels define wholesale/retail discount percentages and default credit limits.

---

### 3.1 List Customer Levels
- **HTTP Method:** `GET`
- **Endpoint:** `https://konnadia.empoweredtechinnovations.org/api/v1/admin/customer-levels`

#### Success Response (`200 OK`)
```json
{
  "success": true,
  "message": "Customer levels retrieved successfully.",
  "data": {
    "levels": [
      {
        "id": 1,
        "name": "Wholesale Tier A",
        "discount_percentage": 15.0,
        "default_credit_limit": 50000.0,
        "sort_order": 1,
        "description": "Standard wholesale customer discount tier",
        "is_active": true
      },
      {
        "id": 2,
        "name": "VIP Platinum Tier",
        "discount_percentage": 25.0,
        "default_credit_limit": 200000.0,
        "sort_order": 2,
        "description": "High volume bulk purchaser tier",
        "is_active": true
      }
    ]
  }
}
```

---

### 3.2 Create Customer Level / Discount Tier
- **HTTP Method:** `POST`
- **Endpoint:** `https://konnadia.empoweredtechinnovations.org/api/v1/admin/customer-levels`

#### Request Body
```json
{
  "name": "Super Bulk Tier",
  "discount_percentage": 22.5,
  "default_credit_limit": 150000,
  "sort_order": 3,
  "description": "Special discount tier for super bulk orders",
  "is_active": true
}
```

---

### 3.3 Update Customer Level / Discount Tier
- **HTTP Method:** `PUT` or `PATCH`
- **Endpoint:** `https://konnadia.empoweredtechinnovations.org/api/v1/admin/customer-levels/{id}`

---

### 3.4 Delete Customer Level
- **HTTP Method:** `DELETE`
- **Endpoint:** `https://konnadia.empoweredtechinnovations.org/api/v1/admin/customer-levels/{id}`
