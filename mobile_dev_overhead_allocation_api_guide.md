# Mobile Developer Guide: Overhead Allocation Module API

This comprehensive integration guide provides mobile app developers (iOS / Android / Flutter / React Native) with complete API specifications for the **Overhead Allocation & Factory Cost Engine (`/factory/overhead-allocation`)**.

---

## Table of Contents
1. [Authentication & Base URLs](#1-authentication--base-urls)
2. [Category Lookup Options API](#2-category-lookup-options-api)
   - [GET /factory/overhead-allocation/options (Other Expense Category Options)](#21-get-factoryoverhead-allocationoptions)
3. [Monthly Calculation & Stock Reconciliation API](#3-monthly-calculation--stock-reconciliation-api)
   - [GET /factory/overhead-allocation (Load Monthly Overhead Data)](#31-get-factoryoverhead-allocation)
4. [Save & Calculate Allocation API](#4-save--calculate-allocation-api)
   - [POST /factory/overhead-allocation (Save Monthly Allocation)](#41-post-factoryoverhead-allocation)
5. [Allocation History API](#5-allocation-history-api)
   - [GET /factory/overhead-allocation/history (12-Month Historical Log)](#51-get-factoryoverhead-allocationhistory)

---

## 1. Authentication & Base URLs

### Headers
Every API request must include the Sanctum bearer token:
```http
Authorization: Bearer <your_jwt_or_sanctum_token>
Accept: application/json
Content-Type: application/json
```

### Route Aliases
All overhead allocation routes are accessible under equivalent route prefixes:

| Web Module | Primary Mobile Endpoint | Equivalent Aliases |
| :--- | :--- | :--- |
| **Overhead Allocation** | `GET/POST /api/v1/factory/overhead-allocation` | `/api/v1/production/overhead-allocation`, `/api/v1/admin/production/overhead-allocation` |

---

## 2. Category Lookup Options API

### 2.1 GET `/factory/overhead-allocation/options`
Retrieves pre-defined expense categories for non-material overhead entries.

#### Response Example (`HTTP 200 OK`):
```json
{
  "success": true,
  "data": {
    "other_category_options": [
      "General Consumables",
      "Electricity & Utilities",
      "Factory Rent Share",
      "Machine Maintenance & Repairs",
      "Water & Waste Handling",
      "Freight & Logistics Share",
      "Miscellaneous Expenses",
      "Other"
    ]
  }
}
```

---

## 3. Monthly Calculation & Stock Reconciliation API

### 3.1 GET `/factory/overhead-allocation`
Fetches monthly allocation breakdown, including auto-calculated production value, stitching material inventory stock reconciliation, salaried staff payroll obligations, and general overhead rows.

#### Query Parameters:
- `year` (integer, optional, default: current year, e.g. `2026`).
- `month` (integer `1-12`, optional, default: current month).

#### Response Example (`HTTP 200 OK`):
```json
{
  "success": true,
  "data": {
    "id": 14,
    "year": 2026,
    "month": 9,
    "period_formatted": "Sep 2026",
    "production_value": 500000.0,
    "stitching_material_total": 450.0,
    "salaried_staff_total": 25000.0,
    "other_overheads_total": 7300.0,
    "total_overhead": 32750.0,
    "overhead_percentage": 6.55,
    "status": "saved",
    "material_rows": [
      {
        "raw_material_id": 12,
        "name": "Stitching Thread Reel Red",
        "code": "RM-ST-001",
        "unit": "Rolls",
        "unit_cost": 15.0,
        "opening_stock_qty": 100.0,
        "opening_stock_value": 1500.0,
        "purchases_qty": 10.0,
        "purchases_value": 150.0,
        "closing_stock_qty": 80.0,
        "closing_stock_value": 1200.0,
        "consumed_qty": 30.0,
        "consumed_cost": 450.0
      }
    ],
    "salaried_labors": [
      {
        "id": 3,
        "name": "John Supervisor",
        "role": "Supervisor",
        "monthly_salary": 25000.0
      }
    ],
    "other_overhead_rows": [
      {
        "category": "Electricity & Utilities",
        "custom_category": "",
        "category_name": "Electricity & Utilities",
        "amount": 4500.0
      },
      {
        "category": "Other",
        "custom_category": "Generator Diesel Expense",
        "category_name": "Generator Diesel Expense",
        "amount": 2800.0
      }
    ]
  }
}
```

---

## 4. Save & Calculate Allocation API

### 4.1 POST `/factory/overhead-allocation`
Saves or updates the overhead allocation record for a specific year and month. It reconciles closing stock inputs, recomputes material consumption costs, sums salaried staff payroll obligations, calculates total overhead amount, and determines final overhead percentage.

#### Request Body (`JSON`):
```json
{
  "year": 2026,
  "month": 9,
  "production_value": 500000.00,
  "material_rows": [
    {
      "raw_material_id": 12,
      "closing_stock_qty": 80.00
    }
  ],
  "other_overhead_rows": [
    {
      "category": "Electricity & Utilities",
      "custom_category": "",
      "amount": 4500.00
    },
    {
      "category": "Other",
      "custom_category": "Generator Diesel Expense",
      "amount": 2800.00
    }
  ]
}
```

#### Field Descriptions:
- `year` (integer, required): Target year (e.g. `2026`).
- `month` (integer `1-12`, required): Target month.
- `production_value` (numeric, required, min `1.0`): Gross monthly production value in INR.
- `material_rows` (array, optional): Array of raw material closing stock records (`raw_material_id`, `closing_stock_qty`).
- `other_overhead_rows` (array, optional): Non-material expense line items (`category`, `custom_category`, `amount`).

#### Response Example (`HTTP 200 OK`):
```json
{
  "success": true,
  "message": "Overhead allocation saved successfully.",
  "data": {
    "id": 14,
    "year": 2026,
    "month": 9,
    "period_formatted": "Sep 2026",
    "production_value": 500000.0,
    "stitching_material_total": 450.0,
    "salaried_staff_total": 25000.0,
    "other_overheads_total": 7300.0,
    "total_overhead": 32750.0,
    "overhead_percentage": 6.55,
    "status": "saved"
  }
}
```

---

## 5. Allocation History API

### 5.1 GET `/factory/overhead-allocation/history`
Returns a 12-month historical list of saved overhead allocations.

#### Response Example (`HTTP 200 OK`):
```json
{
  "success": true,
  "data": [
    {
      "id": 14,
      "year": 2026,
      "month": 9,
      "period_formatted": "Sep 2026",
      "production_value": 500000.0,
      "stitching_material_total": 450.0,
      "salaried_staff_total": 25000.0,
      "other_overheads_total": 7300.0,
      "total_overhead": 32750.0,
      "overhead_percentage": 6.55,
      "status": "saved"
    },
    {
      "id": 12,
      "year": 2026,
      "month": 8,
      "period_formatted": "Aug 2026",
      "production_value": 450000.0,
      "stitching_material_total": 520.0,
      "salaried_staff_total": 25000.0,
      "other_overheads_total": 6000.0,
      "total_overhead": 31520.0,
      "overhead_percentage": 7.0,
      "status": "saved"
    }
  ]
}
```

---

## Summary for Mobile Developers
1. **Full Feature Parity**: Mobile clients can render monthly stock reconciliation forms, calculate overhead percentages, and track 12-month factory overhead trends matching the web interface.
2. **Auto-Calculated Base Inputs**: Monthly production values and salaried staff payroll sums are pre-calculated automatically by the server.
