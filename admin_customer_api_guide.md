# Customer Management & Discount Tiers Mobile API Integration Guide
**Kanodia Textiles — Enterprise Manufacturing & ERP Platform**  
**Version:** 1.1  
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

---

### 2.2 Export Customers to CSV
Streams a `.csv` file download of customer records based on applied search and filter criteria.

- **HTTP Method:** `GET`
- **Endpoint:** `https://konnadia.empoweredtechinnovations.org/api/v1/admin/customers/export`
- **Query Parameters:**
  - `search` *(optional)*: Search string filter.
  - `level_id` *(optional)*: Filter by customer level ID.
  - `status` *(optional)*: `active` or `inactive`.

- **Response Header:** `Content-Type: text/csv`, `Content-Disposition: attachment; filename="customers-YYYY-MM-DD.csv"`
- **Response Body:** Binary CSV file stream containing Customer Number, Company Name, Contact Person, Mobile, Email, GST Number, Customer Level, Credit Limit, Outstanding Amount, and Active Status.

---

### 2.3 Get Single Customer Details
Retrieves detailed profile, level, user login status, and credit limits for a single customer.

- **HTTP Method:** `GET`
- **Endpoint:** `https://konnadia.empoweredtechinnovations.org/api/v1/admin/customers/{id}`

---

### 2.4 Create New Customer
Creates a new customer profile, generates their unique Customer ID (`KT-XXX`), and creates a linked mobile/web user account.

- **HTTP Method:** `POST`
- **Endpoint:** `https://konnadia.empoweredtechinnovations.org/api/v1/admin/customers`

---

### 2.5 Update Customer Profile
Updates existing customer information and syncs linked user credentials.

- **HTTP Method:** `PUT` or `PATCH`
- **Endpoint:** `https://konnadia.empoweredtechinnovations.org/api/v1/admin/customers/{id}`

---

### 2.6 Toggle Active Status
Quickly activates or deactivates a customer and their user account.

- **HTTP Method:** `PATCH`
- **Endpoint:** `https://konnadia.empoweredtechinnovations.org/api/v1/admin/customers/{id}/toggle-status`

---

### 2.7 Admin Reset Customer Password
Allows administrators to update a customer's login password.

- **HTTP Method:** `POST`
- **Endpoint:** `https://konnadia.empoweredtechinnovations.org/api/v1/admin/customers/{id}/reset-password`

---

### 2.8 Delete Customer
Soft deletes a customer and deactivates their linked user account.

- **HTTP Method:** `DELETE`
- **Endpoint:** `https://konnadia.empoweredtechinnovations.org/api/v1/admin/customers/{id}`

---

## 3. Customer Levels & Discount Tiers (`/api/v1/admin/customer-levels`)

Customer Levels define wholesale/retail discount percentages and default credit limits.

- **List Customer Levels:** `GET /api/v1/admin/customer-levels`
- **Create Customer Level:** `POST /api/v1/admin/customer-levels`
- **Update Customer Level:** `PUT /api/v1/admin/customer-levels/{id}`
- **Delete Customer Level:** `DELETE /api/v1/admin/customer-levels/{id}`
