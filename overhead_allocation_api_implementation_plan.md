# Overhead Allocation API Implementation Plan

## Executive Summary
This document details the backend architecture, controller logic, route namespaces, and test verification matrix for the **Overhead Allocation Module (`/factory/overhead-allocation`)**.

---

## 1. System Architecture & Route Namespaces

```
/api/v1/factory/
  └── overhead-allocation/
        ├── /options               (GET category options)
        ├── /history               (GET 12-month historical allocations)
        ├── /                      (GET load month calculation & stock reconciliation)
        └── /                      (POST save monthly allocation)
```

Direct aliases are also registered under `/api/v1/production/overhead-allocation` and `/api/v1/admin/production/overhead-allocation`.

---

## 2. Controller & Service Responsibilities

### 2.1 `OverheadAllocationController`
- **Options (`options`)**: Returns pre-defined expense categories (`General Consumables`, `Electricity & Utilities`, `Factory Rent Share`, etc.).
- **Show (`show`)**: Automatically computes monthly production value (via `MonthlyProductionValueService`), material opening stock, purchases, unit costs, closing stock defaults, and salaried staff payroll.
- **Store (`store`)**: Accepts updated closing stock quantities and custom non-material overhead expense rows. Updates/creates `MonthlyOverheadAllocation`, `MonthlyOverheadMaterialItem`, and `MonthlyOverheadOtherItem` records.
- **History (`history`)**: Returns 12-month historical list of saved overhead allocations.

---

## 3. Test Verification Matrix

| Test Suite | Covered Features | Assertions | Status |
| :--- | :--- | :--- | :--- |
| `OverheadAllocationApiTest` | Options, show calculation, store allocation, history, alias endpoints | 24 | PASS |
| `OverheadAllocationPageTest` | Stock reconciliation math, custom category creation | 22 | PASS |
| `MonthlyProductionValueServiceTest` | Completed job valuation, storefront packaging calculations | 14 | PASS |

---

## 4. Deployment Checklist
- [x] Route groups mounted under `/api/v1/factory/...`, `/api/v1/production/...`, and `/api/v1/admin/production/...`.
- [x] Controller calculations and stock reconciliation verified.
- [x] Unit test suite verified (100% pass rate across 6 API tests).
- [x] Mobile developer guide (`mobile_dev_overhead_allocation_api_guide.md`) published.
- [x] Pushed to remote repositories (`origin` and `upstream`/`neoscar`).
