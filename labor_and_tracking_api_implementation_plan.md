# Labor, Labor Categories, Payroll & Tracking History API Implementation Plan

## Executive Summary
This document details the backend architecture, controller logic, route namespaces, and test verification matrix for:
1. **Labor Management**: `/admin/labor` & `/factory/labor`
2. **Labor Categories Management**: `/admin/labor-categories` & `/factory/labor-categories`
3. **Payroll & Wage Summary**: `/factory/labor/payroll/summary` & `/admin/wages/summary`
4. **Production Tracking History**: `/admin/production/tracking-history` & `/factory/tracking-history`

---

## 1. System Architecture & Route Namespaces

```
/api/v1/factory/
  ├── labor/
  │     ├── /                      (GET list, POST create)
  │     ├── /options               (GET worker picker options & stats)
  │     ├── /payroll/summary       (GET payroll summary)
  │     ├── /categories            (GET/POST sub-alias for categories)
  │     ├── /categories/options    (GET category options)
  │     ├── /{id}                  (GET, PUT, PATCH, DELETE worker)
  │     ├── /{id}/stats            (GET profile analytics & batch breakdown)
  │     ├── /{id}/detail           (GET profile analytics & batch breakdown)
  │     └── /{id}/toggle-status    (PATCH toggle status)
  ├── labor-categories/            (Direct alias group)
  │     ├── /                      (GET list, POST create)
  │     ├── /options               (GET category options)
  │     ├── /{id}                  (GET, PUT, PATCH, DELETE category)
  │     └── /{id}/toggle-status    (PATCH toggle status)
  └── tracking-history/            (Direct alias group)
        ├── /                      (GET production audit log)
        └── /options               (GET lookup options)
```

---

## 2. Controller Responsibilities

### 2.1 `AdminLaborController`
- **Options (`options`)**: Returns active task list, cost types, default piece rates, payment methods, and worker summary stats.
- **Index (`index`)**: Supports search (name, code, mobile), payment method filtering, status filtering, and task ID authorization filtering.
- **Detail Stats (`detailStats`)**: Calculates total pieces processed, direct wages earned, job cost valuation, unique batch breakdowns, and filtered allocations.
- **Payroll Summary (`payrollSummary`)**: Aggregates total monthly salary obligations and total piece rate wages paid.

### 2.2 `AdminLaborCategoryController`
- **Index & Options (`index`, `options`)**: Manages labor departments/skill categories with search, status filtering, `tasks_count`, and `labors_count`.
- **CRUD & Safety (`store`, `update`, `toggleStatus`, `destroy`)**: Protects category deletion when assigned to active tasks or workers.

### 2.3 `AdminProductionJobController`
- **Tracking History Options (`trackingHistoryOptions`)**: Returns worker pickers, active jobs, tasks, and payment methods.
- **Audit Tracking (`trackingHistory`)**: Provides complete audit logs with search, worker ID filtering, job ID filtering, and date range presets.

---

## 3. Test Verification Matrix

| Test Suite | Covered Features | Assertions | Status |
| :--- | :--- | :--- | :--- |
| `AdminLaborApiTest` | List, search, options, create, update, toggle, delete protection | 24 | PASS |
| `AdminLaborCategoryApiTest` | Category list, options, create, update, toggle, delete protection | 17 | PASS |
| `AdminLaborStatsApiTest` | Detail stats, batch breakdown, payroll summary | 12 | PASS |
| `LaborCategoryManagementTest` | Task linking, category task preselection | 18 | PASS |
| `LaborPatternTrackingTest` | Pattern rate integration, tracking history API, worker wages API | 21 | PASS |

---

## 4. Deployment Checklist
- [x] API controllers (`AdminLaborController`, `AdminLaborCategoryController`, `AdminProductionJobController`) configured.
- [x] Route groups registered with regex constraints (`where('id', '[0-9]+')`).
- [x] Unit test suite verified (100% pass rate across 31 tests).
- [x] Mobile developer guide (`mobile_dev_labor_and_tracking_api_guide.md`) published.
- [x] Pushed to remote repositories (`origin` and `upstream`/`neoscar`).
