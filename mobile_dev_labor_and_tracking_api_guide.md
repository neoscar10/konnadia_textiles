# Mobile Developer Guide: Labor, Labor Categories, Payroll & Production Tracking History API

This comprehensive integration guide provides mobile app developers (iOS / Android / Flutter / React Native) with complete API specifications for **Labor Management (`/admin/labor`)**, **Labor Categories (`/admin/labor-categories`)**, **Payroll Summaries**, and **Production Tracking History (`/admin/production/tracking-history`)**.

---

## Table of Contents
1. [Authentication & Base URLs](#1-authentication--base-urls)
2. [Labor Management API](#2-labor-management-api)
   - [GET /factory/labor/options (Worker Picker Options & Summary)](#21-get-factorylaboroptions)
   - [GET /factory/labor (List & Filter Workers)](#22-get-factorylabor)
   - [GET /factory/labor/{id} (Single Worker Details)](#23-get-factorylaborids)
   - [GET /factory/labor/{id}/detail (Worker Profile Analytics & Batch Breakdown)](#24-get-factorylaboridsdetail)
   - [POST /factory/labor (Create Worker)](#25-post-factorylabor)
   - [PUT /factory/labor/{id} (Update Worker)](#26-put-factorylaborids)
   - [PATCH /factory/labor/{id}/toggle-status (Toggle Active Status)](#27-patch-factorylaborids-toggle-status)
   - [DELETE /factory/labor/{id} (Delete Worker)](#28-delete-factorylaborids)
3. [Labor Categories API](#3-labor-categories-api)
   - [GET /factory/labor-categories/options (Category Picker Options)](#31-get-factorylabor-categoriesoptions)
   - [GET /factory/labor-categories (List & Search Categories)](#32-get-factorylabor-categories)
   - [GET /factory/labor-categories/{id} (Category Detail)](#33-get-factorylabor-categoriesids)
   - [POST /factory/labor-categories (Create Category)](#34-post-factorylabor-categories)
   - [PUT /factory/labor-categories/{id} (Update Category)](#35-put-factorylabor-categoriesids)
   - [PATCH /factory/labor-categories/{id}/toggle-status (Toggle Status)](#36-patch-factorylabor-categoriesids-toggle-status)
   - [DELETE /factory/labor-categories/{id} (Delete Category)](#37-delete-factorylabor-categoriesids)
4. [Payroll & Wage Summary API](#4-payroll--wage-summary-api)
   - [GET /factory/labor/payroll/summary (Payroll Metrics)](#41-get-factorylaborpayrollsummary)
5. [Production Tracking History API](#5-production-tracking-history-api)
   - [GET /factory/tracking-history/options (Tracking History Options)](#51-get-factorytracking-historyoptions)
   - [GET /factory/tracking-history (Production Audit History)](#52-get-factorytracking-history)

---

## 1. Authentication & Base URLs

### Headers
Include the Sanctum Bearer token with every request:
```http
Authorization: Bearer <your_jwt_or_sanctum_token>
Accept: application/json
Content-Type: application/json
```

### Route Aliases
All routes are accessible under equivalent production/admin prefixes:

| Web Module | Primary Mobile Endpoint | Equivalent Aliases |
| :--- | :--- | :--- |
| **Labor Management** | `GET/POST /api/v1/factory/labor` | `/api/v1/production/labor`, `/api/v1/admin/labor`, `/api/v1/admin/production/labor` |
| **Labor Categories** | `GET/POST /api/v1/factory/labor-categories` | `/api/v1/factory/labor/categories`, `/api/v1/production/labor-categories`, `/api/v1/admin/labor-categories` |
| **Payroll Summary** | `GET /api/v1/factory/labor/payroll/summary` | `/api/v1/admin/wages/summary` |
| **Tracking History** | `GET /api/v1/factory/tracking-history` | `/api/v1/production/tracking-history`, `/api/v1/admin/production/tracking-history` |

---

## 2. Labor Management API

### 2.1 GET `/factory/labor/options`
Provides lightweight task lookup data, payment method options, and summary stats for mobile forms.

#### Response Example (`HTTP 200 OK`):
```json
{
  "success": true,
  "data": {
    "tasks": [
      {
        "id": 1,
        "name": "Fabric Cutting",
        "code": "CUT-01",
        "is_labor_required": true,
        "cost_type": "piece_rate",
        "default_piece_rate": 15.0
      },
      {
        "id": 2,
        "name": "Stitching & Seam Assembly",
        "code": "STITCH-01",
        "is_labor_required": true,
        "cost_type": "piece_rate",
        "default_piece_rate": 25.0
      }
    ],
    "payment_methods": [
      { "value": "monthly_salary", "label": "Monthly Salary" },
      { "value": "job_work", "label": "Job Work (Piece Rate)" }
    ],
    "stats": {
      "total_labors": 12,
      "active_labors": 10,
      "inactive_labors": 2,
      "monthly_salary_labors": 4,
      "job_work_labors": 8
    }
  }
}
```

---

### 2.2 GET `/factory/labor`
Returns a list of factory workers with filter parameters.

#### Query Parameters:
- `search` (string, optional): Worker name, code (`LBR-001`), or mobile number.
- `payment_method` (string, optional): `monthly_salary` or `job_work`.
- `status` (string/boolean, optional): `active` or `inactive`.
- `task_id` (integer, optional): Filter workers authorized for a specific task ID.
- `paginate` (boolean, optional): Set to `true` for paginated response.
- `per_page` (integer, default `15`).

#### Response Example (`HTTP 200 OK`):
```json
{
  "success": true,
  "summary": {
    "total_count": 12,
    "active_count": 10,
    "inactive_count": 2,
    "monthly_salary_count": 4,
    "job_work_count": 8
  },
  "data": [
    {
      "id": 5,
      "name": "Ramesh Kumar",
      "code": "LBR-005",
      "mobile_number": "+91 98765 12345",
      "status": true,
      "payment_method": "job_work",
      "payment_method_label": "Job Work (Piece Rate)",
      "monthly_salary": null,
      "authorized_tasks": [
        { "id": 1, "name": "Fabric Cutting", "code": "CUT-01" }
      ],
      "allocations_count": 42,
      "created_at": "2026-09-01T08:00:00Z"
    }
  ]
}
```

---

### 2.3 GET `/factory/labor/{id}`
Returns profile details and basic performance summary for a worker.

---

### 2.4 GET `/factory/labor/{id}/detail`
Returns full profile stats, date preset filtering (`this_month`, `last_30`, `this_year`), piece-rate earnings, job valuation math, and per-batch allocation breakdown.

#### Query Parameters:
- `preset` (string, optional): `this_month`, `last_30`, `this_year`.
- `date_from` (string `YYYY-MM-DD`, optional).
- `date_to` (string `YYYY-MM-DD`, optional).
- `batch_filter` (string, optional): Filter by batch code or job ID.
- `task_filter` (integer, optional): Filter by task ID.

#### Response Example (`HTTP 200 OK`):
```json
{
  "success": true,
  "worker": {
    "id": 5,
    "name": "Ramesh Kumar",
    "code": "LBR-005"
  },
  "performance_metrics": {
    "total_pieces_processed": 1450,
    "total_direct_wages": 21750.0,
    "total_job_cost_valuation": 21750.0,
    "total_batches_count": 3,
    "total_jobs_count": 8,
    "batch_breakdown": [
      {
        "batch_code": "BATCH-2026-09A",
        "total_pieces": 600,
        "total_wages": 9000.0,
        "total_valuation": 9000.0,
        "jobs_count": 3
      }
    ]
  },
  "data": [
    {
      "id": 108,
      "job_code": "JOB-2026-0042",
      "production_batch_id": "BATCH-2026-09A",
      "task_name": "Fabric Cutting",
      "manufacturing_product_title": "Premium King Bedsheet",
      "pattern_name": "Standard Cutting Pattern A",
      "quantity_processed": 150,
      "piece_rate": 15.0,
      "calculated_wage": 2250.0,
      "created_at": "2026-09-24T14:30:00Z"
    }
  ]
}
```

---

### 2.5 POST `/factory/labor`
Creates a new factory worker record.

#### Request Body (`JSON`):
```json
{
  "name": "Suresh Patel",
  "code": "LBR-006",
  "mobile_number": "+91 91234 56789",
  "payment_method": "job_work",
  "monthly_salary": null,
  "status": true,
  "authorized_tasks": [1, 2]
}
```

---

### 2.6 PUT `/factory/labor/{id}`
Updates worker profile and authorized tasks.

---

### 2.7 PATCH `/factory/labor/{id}/toggle-status`
Toggles active/inactive status.

---

### 2.8 DELETE `/factory/labor/{id}`
Deletes worker if no allocation logs are attached.

---

## 3. Labor Categories API

### 3.1 GET `/factory/labor-categories/options`
Returns active labor category dropdown options.

### 3.2 GET `/factory/labor-categories`
Returns list of labor categories (departments / skill types) with task and worker count metadata.

#### Response Example (`HTTP 200 OK`):
```json
{
  "success": true,
  "summary": {
    "total_count": 4,
    "active_count": 4,
    "inactive_count": 0
  },
  "data": [
    {
      "id": 1,
      "name": "Cutting Specialists",
      "code": "CAT-CUT",
      "description": "Master cutters and automatic spreading operators",
      "status": true,
      "tasks_count": 2,
      "labors_count": 5
    }
  ]
}
```

### 3.3 GET `/factory/labor-categories/{id}`
Returns details for a single category.

### 3.4 POST `/factory/labor-categories`
```json
{
  "name": "Stitching & Seamster Team",
  "code": "CAT-STITCH",
  "description": "High precision lockstitch machine operators",
  "status": true
}
```

### 3.5 PUT `/factory/labor-categories/{id}`
Updates labor category.

### 3.6 PATCH `/factory/labor-categories/{id}/toggle-status`
Toggles active/inactive status.

### 3.7 DELETE `/factory/labor-categories/{id}`
Deletes category if not currently assigned to tasks or workers.

---

## 4. Payroll & Wage Summary API

### 4.1 GET `/factory/labor/payroll/summary`
Returns high-level aggregate payroll obligations.

#### Response Example (`HTTP 200 OK`):
```json
{
  "success": true,
  "data": {
    "total_laborers": 12,
    "active_laborers": 10,
    "inactive_laborers": 2,
    "monthly_salary_obligations": 85000.0,
    "piece_rate_wages_earned": 142500.0,
    "formatted_monthly_salary": "₹85,000.00",
    "formatted_piece_rate_wages": "₹142,500.00"
  }
}
```

---

## 5. Production Tracking History API

### 5.1 GET `/factory/tracking-history/options`
Returns worker pickers, active jobs, tasks, and payment methods for tracking history search filters.

### 5.2 GET `/factory/tracking-history`
Returns complete production audit log of all completed job task allocations, quantities, and piece rates.

#### Query Parameters:
- `search` (string, optional): Job code, batch ID, worker name, or product title.
- `payment_method` (string, optional): `monthly_salary` or `job_work`.
- `job_id` (string/integer, optional): Filter by job ID or job code.
- `worker_id` / `labor_id` (integer, optional): Filter by worker ID.
- `date_from` (string `YYYY-MM-DD`, optional).
- `date_to` (string `YYYY-MM-DD`, optional).
- `per_page` (integer, default `15`).

#### Response Example (`HTTP 200 OK`):
```json
{
  "success": true,
  "message": "Tracking history retrieved successfully.",
  "summary": {
    "total_allocations": 154,
    "total_pieces_processed": 18450,
    "total_wages_paid": 276750.0,
    "formatted_total_wages_paid": "₹2,76,750.00"
  },
  "data": [
    {
      "id": 204,
      "job_code": "JOB-2026-0098",
      "production_batch_id": "BATCH-2026-09C",
      "worker": {
        "id": 5,
        "name": "Ramesh Kumar",
        "code": "LBR-005",
        "payment_method": "job_work"
      },
      "task": {
        "id": 1,
        "name": "Fabric Cutting",
        "code": "CUT-01"
      },
      "manufacturing_product": {
        "id": 3,
        "title": "Cotton Satin 300TC Bedsheet",
        "product_code": "MP-BED-300"
      },
      "pattern": {
        "id": 2,
        "name": "King Bedsheet Cutting Layout"
      },
      "roll_info": {
        "roll_id": 12,
        "roll_number": "ROLL-004",
        "bale_number": "BALE-01"
      },
      "assigned_quantity": 200,
      "quantity_processed": 200,
      "piece_rate": 15.0,
      "calculated_wage": 3000.0,
      "created_at": "2026-09-25T16:00:00Z"
    }
  ]
}
```

---

## Summary for Mobile Developers
1. **Full Sub-Page Coverage**: Every feature on `/admin/labor`, `/admin/labor-categories`, `/admin/wages`, and `/admin/production/tracking-history` is completely mirrored in API endpoints.
2. **Unified Navigation**: Workers, categories, payroll metrics, and tracking history audits update seamlessly without requiring local client math.
