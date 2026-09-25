# Factory Floor Supervisors API Implementation Plan

---

## Executive Summary

This document provides the complete, step-by-step implementation plan for the **Factory Floor Supervisors Management API** module in the Kannodia Textiles backend ecosystem. It outlines the architectural design, route aliases, controller and resource schemas, validation rules, security requirements, and automated testing strategy.

---

## 1. Core Objectives & Scope

1. **Supervisor Directory Management**: Enable creation, lookup, update, soft deletion, and status toggling (`is_active`) of factory floor supervisors.
2. **Unified Route Aliasing**: Support standard REST endpoints across three route namespaces:
   - `/api/v1/factory/supervisors`
   - `/api/v1/production/supervisors`
   - `/api/v1/admin/production/supervisors`
3. **Data Integrity & Relational Safety**: Prevent deletion of supervisors linked to existing production batches, returning actionable error details (`HTTP 422`).
4. **Mobile Optimization**: Provide a lightweight `/options` endpoint for dropdown pickers in mobile/web UIs and return paginated or unpaginated resource collections.

---

## 2. Architecture & Component Mapping

```
+-----------------------------------------------------------------------+
|                            HTTP Request                               |
|   GET/POST/PUT/PATCH/DELETE /api/v1/{factory|production|admin}/supervisors|
+-----------------------------------------------------------------------+
                                   |
                                   v
+-----------------------------------------------------------------------+
|                           Middleware Layer                            |
|       auth:api -> api.admin -> api.permission:access production       |
+-----------------------------------------------------------------------+
                                   |
                                   v
+-----------------------------------------------------------------------+
|                       AdminSupervisorController                        |
|   index() | options() | show() | store() | update() | destroy()       |
+-----------------------------------------------------------------------+
                                   |
         +-------------------------+-------------------------+
         |                                                   |
         v                                                   v
+------------------------+                          +------------------------+
|   Form Request Layer   |                          |     Database Model     |
| StoreSupervisorRequest |                          |   FactorySupervisor    |
| UpdateSupervisorRequest|                          |  (app/Models/...)      |
+------------------------+                          +------------------------+
                                                             |
                                                             v
                                                    +------------------------+
                                                    |     JSON Resource      |
                                                    | AdminSupervisorResource|
                                                    +------------------------+
```

---

## 3. Database Schema & Model Definition

### Model: `App\Models\FactorySupervisor`
* **Table**: `factory_supervisors`
* **Soft Deletes**: Enabled (`use SoftDeletes;`)

#### Schema Attributes:
| Field | Type | Modifiers | Description |
| :--- | :--- | :--- | :--- |
| `id` | `bigint` | Primary, Auto Increment | Unique Identifier |
| `code` | `string(50)` | Unique, Nullable | Supervisor Code (e.g., `SUP-0001`) |
| `name` | `string(255)` | Non-nullable | Full Name |
| `phone` | `string(50)` | Nullable | Contact Phone Number |
| `email` | `string(255)` | Unique, Nullable | Email Address |
| `department` | `string(255)` | Nullable | Factory Department (e.g., Cutting, Stitching) |
| `notes` | `text` | Nullable | Additional Notes / Qualifications |
| `is_active` | `boolean` | Default: `true` | Operational Active Status |
| `created_at` | `timestamp` | Nullable | Creation Timestamp |
| `updated_at` | `timestamp` | Nullable | Last Update Timestamp |
| `deleted_at` | `timestamp` | Nullable | Soft Delete Timestamp |

#### Eloquent Relationships:
- `productionBatches()`: `HasMany` relationship to `App\Models\ProductionBatch` via `factory_supervisor_id`.

---

## 4. API Endpoints Specification

### 1. List Supervisors
- **Method**: `GET`
- **Paths**:
  - `/api/v1/factory/supervisors`
  - `/api/v1/production/supervisors`
  - `/api/v1/admin/production/supervisors`
- **Query Params**: `search`, `status` (`active`|`inactive`|`all`), `department`, `paginate` (`true`|`false`), `page`, `per_page`.
- **Response**: List of `AdminSupervisorResource` with optional pagination metadata.

### 2. Form Lookup Options
- **Method**: `GET`
- **Paths**:
  - `/api/v1/factory/supervisors/options`
  - `/api/v1/production/supervisors/options`
  - `/api/v1/admin/production/supervisors/options`
- **Response**: Active supervisor list (`id`, `name`, `code`, `department`, `phone`, `email`) and distinct department strings.

### 3. Supervisor Details
- **Method**: `GET`
- **Paths**:
  - `/api/v1/factory/supervisors/{id}`
  - `/api/v1/production/supervisors/{id}`
  - `/api/v1/admin/production/supervisors/{id}`
- **Response**: `AdminSupervisorResource` containing total assigned production batches count and recent 10 assigned production batches.

### 4. Create Supervisor
- **Method**: `POST`
- **Paths**:
  - `/api/v1/factory/supervisors`
  - `/api/v1/production/supervisors`
  - `/api/v1/admin/production/supervisors`
- **Body**: `StoreSupervisorRequest`
- **Response**: `201 Created` with created supervisor resource. Auto-generates `code` if omitted.

### 5. Update Supervisor
- **Method**: `PUT` / `PATCH`
- **Paths**:
  - `/api/v1/factory/supervisors/{id}`
  - `/api/v1/production/supervisors/{id}`
  - `/api/v1/admin/production/supervisors/{id}`
- **Body**: `UpdateSupervisorRequest`
- **Response**: `200 OK` with updated resource.

### 6. Toggle Active Status
- **Method**: `PATCH`
- **Paths**:
  - `/api/v1/factory/supervisors/{id}/toggle-status`
  - `/api/v1/production/supervisors/{id}/toggle-status`
  - `/api/v1/admin/production/supervisors/{id}/toggle-status`
- **Response**: Toggles `is_active` state and returns updated supervisor object.

### 7. Delete Supervisor
- **Method**: `DELETE`
- **Paths**:
  - `/api/v1/factory/supervisors/{id}`
  - `/api/v1/production/supervisors/{id}`
  - `/api/v1/admin/production/supervisors/{id}`
- **Response**: `200 OK` on success, or `422 Unprocessable Entity` if linked to production batches.

---

## 5. Validation Rules Matrix

### `StoreSupervisorRequest`
```php
public function rules(): array
{
    return [
        'name'       => 'required|string|max:255',
        'code'       => 'nullable|string|max:50|unique:factory_supervisors,code',
        'phone'      => 'nullable|string|max:50',
        'email'      => 'nullable|email|max:255|unique:factory_supervisors,email',
        'department' => 'nullable|string|max:255',
        'notes'      => 'nullable|string',
        'is_active'  => 'nullable|boolean',
    ];
}
```

### `UpdateSupervisorRequest`
```php
public function rules(): array
{
    $id = $this->route('id') ?? $this->route('supervisor');

    return [
        'name'       => 'sometimes|required|string|max:255',
        'code'       => ['nullable', 'string', 'max:50', Rule::unique('factory_supervisors', 'code')->ignore($id)],
        'phone'      => 'nullable|string|max:50',
        'email'      => ['nullable', 'email', 'max:255', Rule::unique('factory_supervisors', 'email')->ignore($id)],
        'department' => 'nullable|string|max:255',
        'notes'      => 'nullable|string',
        'is_active'  => 'nullable|boolean',
    ];
}
```

---

## 6. Testing & Quality Assurance Plan

Automated feature tests are implemented in `tests/Feature/Admin/AdminSupervisorApiTest.php` to verify:

1. **Authentication & Security**: Unauthenticated or unauthorized requests return `401 Unauthorized` or `403 Forbidden`.
2. **Resource Retrieval**: Listing, filtering (`search`, `status`, `department`), options dropdowns, and single resource details work cleanly.
3. **Creation Logic**: Verified manual code assignment and auto code creation.
4. **Update & Status Toggle**: Verified fields update accurately and status toggles properly.
5. **Relational Integrity Protection**: Confirmed `DELETE` request fails with `422` when linked batches exist, and succeeds when unlinked.
6. **Alias Coverage**: All tests run against `/factory/supervisors`, `/production/supervisors`, and `/admin/production/supervisors` to ensure zero broken routes.

---

## 7. Deployment & Verification Checklist

- [x] Run database migrations and verify `factory_supervisors` table structure.
- [x] Register routes under `/factory/supervisors`, `/production/supervisors`, and `/admin/production/supervisors` in `routes/api.php`.
- [x] Add parameter constraints `where('id', '[0-9]+')` in `routes/web.php` to avoid SPA/Livewire route collisions.
- [x] Execute unit and feature tests (`php artisan test --filter=AdminSupervisorApiTest`).
- [x] Push updates to remote Git repositories (`origin` & `upstream`).
