# Admin Factory Tasks Mobile API Documentation & Integration Guide
*(Factory Tasks Master Configuration & Sequence Manager for Production Routings)*

**Kanodia Textiles — Enterprise Manufacturing & ERP Platform**  
**Module:** Factory Floor / Production Management (`/factory/tasks`)  
**Target Platform:** Mobile App (iOS / Android — Flutter / React Native / Native)  
**Version:** 1.0  
**Base URL:** `https://konnadia.empoweredtechinnovations.org/`  
**API Prefix:** `/api/v1`  
**Endpoints:**
- Direct Factory Alias: `/api/v1/factory/tasks`
- Production Floor Admin: `/api/v1/admin/production/tasks`  
**Authentication:** Bearer Token (`Authorization: Bearer <token>`) issued via `POST /api/v1/admin/auth/login`.  
**Required Permission:** `access production`  
**Headers Required:**
```http
Authorization: Bearer <admin_jwt_token>
Accept: application/json
Content-Type: application/json
```

---

# TABLE OF CONTENTS
1. [Overview & Access Control](#1-overview--access-control)
2. [Mobile App Screens & Component Architecture](#2-mobile-app-screens--component-architecture)
3. [Endpoints Specification](#3-endpoints-specification)
   - [3.1 List Tasks (Ordered & Paginated with Search & Filters)](#31-list-tasks-ordered--paginated-with-search--filters)
   - [3.2 Task Pickers & Options](#32-task-pickers--options)
   - [3.3 Get Single Task Details](#33-get-single-task-details)
   - [3.4 Create Task Master](#34-create-task-master)
   - [3.5 Reorder Task Sequence Numbers](#35-reorder-task-sequence-numbers)
   - [3.6 Update Task Master](#36-update-task-master)
   - [3.7 Toggle Active Status](#37-toggle-active-status)
   - [3.8 Delete Task Master](#38-delete-task-master)
4. [Data Models & Types (TypeScript / Dart)](#4-data-models--types-typescript--dart)
5. [UI/UX Best Practices & Mobile Dev Rules](#5-uiux-best-practices--mobile-dev-rules)
6. [Error Handling & Edge Cases](#6-error-handling--edge-cases)

---

# 1. OVERVIEW & ACCESS CONTROL

The **Task Master Configuration** module allows factory floor managers and admins to define reusable manufacturing step sequences (e.g. Cutting, Stitching, Quality Control, Packaging), map allowed raw material categories for stock consumption, configure worker authorization for piece-rate wage rollups, and reorder process flow sequence numbers.

### Access Control
- **Required Permission:** `access production`
- **Permission Check:** Verify `permissions.includes('access production')` or `access_matrix.can_access_production` in the authenticated user profile payload (`POST /api/v1/admin/auth/login` or `GET /api/v1/admin/auth/me`).

---

# 2. MOBILE APP SCREENS & COMPONENT ARCHITECTURE

The mobile app should implement the following **3 primary screens** and **1 reusable dropdown picker component**:

```
 ┌────────────────────────────────────────────────────────┐
 │ 1. Task Master Sequence Directory Screen               │
 │    - Drag handles for sequence reordering              │
 │    - Real-time search bar (by name or code)            │
 │    - Consumes Stock & Labor Dependent badges           │
 │    - Sequence numbers (#1, #2, #3...)                  │
 │    - Quick Active/Inactive toggle switches             │
 │    - Action buttons: Edit, Delete, Reorder             │
 └───────────────────────────┬────────────────────────────┘
                             │
            ┌────────────────┴────────────────┐
            ▼                                 ▼
 ┌───────────────────────────┐   ┌───────────────────────────┐
 │ 2. Task Details / Form    │   │ 3. Reorder Sequence Modal │
 │    - Task Name * & Code   │   │    - Drag-and-drop list   │
 │    - Sequence Number      │   │    - Save Sequence Order  │
 │    - Consumes Stock Switch│   └───────────────────────────┘
 │      └ Raw Material Cats  │
 │    - Labor Dependent Sw   │
 │      └ Authorized Tasks   │
 │    - Operational Status   │
 └───────────────────────────┘
               ▲
 ┌─────────────┴─────────────────────────────────────────────┐
 │ 4. Task Master Picker Component (Reusable Widget)         │
 │    - BottomSheet / Dropdown for product routing setup     │
 └───────────────────────────────────────────────────────────┘
```

---

# 3. ENDPOINTS SPECIFICATION

## 3.1 List Tasks (Ordered & Paginated with Search & Filters)

Retrieve factory tasks sorted by `sequence_number` ascending.

- **HTTP Method:** `GET`
- **Full URL:** `https://konnadia.empoweredtechinnovations.org/api/v1/factory/tasks` (or `/api/v1/admin/production/tasks`)
- **Query Parameters:**
  - `search` *(optional string)*: Search by task name or task code.
  - `status` *(optional string)*: `active` or `inactive`.
  - `paginate` *(optional string)*: `true` to force pagination.
  - `per_page` *(optional int, default 15)*: Items per page.
  - `page` *(optional int, default 1)*: Page number for infinite scroll.

- **Response `200 OK`**:
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "name": "Fabric Cutting & Aligning",
      "code": "TSK-CUT-01",
      "status": true,
      "status_label": "Active",
      "sequence_number": 1,
      "consumes_raw_material": true,
      "is_labor_required": true,
      "raw_material_categories": [
        {
          "id": 2,
          "name": "Cotton Fabrics",
          "code": "CAT-FAB-01",
          "unit_type": "length_based"
        }
      ],
      "raw_material_category_ids": [2],
      "authorized_labor_tasks": [
        {
          "id": 1,
          "name": "Fabric Cutting & Aligning",
          "code": "TSK-CUT-01",
          "status": true
        }
      ],
      "selected_authorized_task_ids": [1],
      "manufacturing_products_count": 4,
      "created_at": "2026-09-15T19:00:00+01:00",
      "updated_at": "2026-09-15T19:00:00+01:00"
    }
  ]
}
```

---

## 3.2 Task Pickers & Options

Retrieve lightweight options list containing active tasks, raw material categories, and all labor tasks for dropdown selection in manufacturing product routing setup.

- **HTTP Method:** `GET`
- **Full URL:** `https://konnadia.empoweredtechinnovations.org/api/v1/factory/tasks/options` (or `/api/v1/admin/production/tasks/options`)
- **Response `200 OK`**:
```json
{
  "success": true,
  "data": {
    "tasks": [
      {
        "id": 1,
        "name": "Fabric Cutting & Aligning",
        "code": "TSK-CUT-01",
        "consumes_raw_material": true,
        "is_labor_required": true,
        "sequence_number": 1
      },
      {
        "id": 2,
        "name": "Stitching & Hemming",
        "code": "TSK-STITCH-01",
        "consumes_raw_material": false,
        "is_labor_required": true,
        "sequence_number": 2
      }
    ],
    "raw_material_categories": [
      {
        "id": 2,
        "name": "Cotton Fabrics",
        "code": "CAT-FAB-01",
        "unit_type": "length_based"
      }
    ],
    "all_labor_tasks": [
      {
        "id": 1,
        "name": "Fabric Cutting & Aligning",
        "code": "TSK-CUT-01",
        "status": true,
        "sequence_number": 1
      },
      {
        "id": 2,
        "name": "Stitching & Hemming",
        "code": "TSK-STITCH-01",
        "status": true,
        "sequence_number": 2
      }
    ]
  }
}
```

---

## 3.3 Get Single Task Details

Retrieve full configuration details of a specific task master.

- **HTTP Method:** `GET`
- **Full URL:** `https://konnadia.empoweredtechinnovations.org/api/v1/factory/tasks/{id}` (or `/api/v1/admin/production/tasks/{id}`)
- **Response `200 OK`**:
```json
{
  "success": true,
  "data": {
    "id": 1,
    "name": "Fabric Cutting & Aligning",
    "code": "TSK-CUT-01",
    "status": true,
    "status_label": "Active",
    "sequence_number": 1,
    "consumes_raw_material": true,
    "is_labor_required": true,
    "raw_material_categories": [
      {
        "id": 2,
        "name": "Cotton Fabrics",
        "code": "CAT-FAB-01",
        "unit_type": "length_based"
      }
    ],
    "raw_material_category_ids": [2],
    "authorized_labor_tasks": [
      {
        "id": 1,
        "name": "Fabric Cutting & Aligning",
        "code": "TSK-CUT-01",
        "status": true
      }
    ],
    "selected_authorized_task_ids": [1],
    "manufacturing_products_count": 4,
    "created_at": "2026-09-15T19:00:00+01:00",
    "updated_at": "2026-09-15T19:00:00+01:00"
  }
}
```

---

## 3.4 Create Task Master

Configure a new manufacturing task master stage.

- **HTTP Method:** `POST`
- **Full URL:** `https://konnadia.empoweredtechinnovations.org/api/v1/factory/tasks` (or `/api/v1/admin/production/tasks`)
- **Request Body (JSON)**:
```json
{
  "name": "Embroidery & Quilting",
  "code": "TSK-EMB-01",
  "status": true,
  "consumes_raw_material": true,
  "is_labor_required": true,
  "selected_category_ids": [2],
  "selected_authorized_task_ids": [1, 2],
  "sequence_number": 3
}
```

- **Parameters**:
  - `name` *(required string, max 255, unique)*: Task step title.
  - `code` *(optional string, max 50, unique)*: Unique task code.
  - `status` *(optional boolean, default true)*: Active operational status.
  - `consumes_raw_material` *(required boolean)*: True if task stage deducts raw materials stock.
  - `is_labor_required` *(required boolean)*: True if task requires worker assignment and wage tracking.
  - `selected_category_ids` *(required if consumes_raw_material is true, array of ints)*: Allowed raw material category IDs.
  - `selected_authorized_task_ids` *(optional array of ints)*: Authorized worker task IDs qualified to execute this stage.
  - `sequence_number` *(optional int, min 1)*: Process flow order sequence number. Auto-assigned to max + 1 if omitted.

- **Response `201 Created`**:
```json
{
  "success": true,
  "message": "Factory Task \"Embroidery & Quilting\" created successfully.",
  "data": {
    "id": 3,
    "name": "Embroidery & Quilting",
    "code": "TSK-EMB-01",
    "status": true,
    "status_label": "Active",
    "sequence_number": 3,
    "consumes_raw_material": true,
    "is_labor_required": true,
    "raw_material_categories": [
      {
        "id": 2,
        "name": "Cotton Fabrics",
        "code": "CAT-FAB-01",
        "unit_type": "length_based"
      }
    ],
    "raw_material_category_ids": [2],
    "selected_authorized_task_ids": [1, 2],
    "manufacturing_products_count": 0,
    "created_at": "2026-09-15T21:00:00+01:00",
    "updated_at": "2026-09-15T21:00:00+01:00"
  }
}
```

---

## 3.5 Reorder Task Sequence Numbers

Reorder the process sequence of tasks via drag-and-drop. Pass an array of task IDs in the desired order. The server updates their `sequence_number` values to 1, 2, 3, etc., sequentially.

- **HTTP Method:** `POST`
- **Full URL:** `https://konnadia.empoweredtechinnovations.org/api/v1/factory/tasks/reorder` (or `/api/v1/admin/production/tasks/reorder`)
- **Request Body (JSON)**:
```json
{
  "ordered_ids": [3, 1, 2]
}
```

- **Response `200 OK`**:
```json
{
  "success": true,
  "message": "Task sequence order updated successfully.",
  "data": [
    {
      "id": 3,
      "name": "Embroidery & Quilting",
      "sequence_number": 1
    },
    {
      "id": 1,
      "name": "Fabric Cutting & Aligning",
      "sequence_number": 2
    },
    {
      "id": 2,
      "name": "Stitching & Hemming",
      "sequence_number": 3
    }
  ]
}
```

---

## 3.6 Update Task Master

Modify an existing task master configuration.

- **HTTP Method:** `PUT` or `PATCH`
- **Full URL:** `https://konnadia.empoweredtechinnovations.org/api/v1/factory/tasks/{id}` (or `/api/v1/admin/production/tasks/{id}`)
- **Request Body (JSON)**:
```json
{
  "name": "Fabric Cutting & Precision Trimming",
  "consumes_raw_material": true,
  "is_labor_required": true,
  "selected_category_ids": [2],
  "selected_authorized_task_ids": [1]
}
```

- **Response `200 OK`**:
```json
{
  "success": true,
  "message": "Factory Task \"Fabric Cutting & Precision Trimming\" updated successfully.",
  "data": {
    "id": 1,
    "name": "Fabric Cutting & Precision Trimming",
    "code": "TSK-CUT-01",
    "status": true,
    "status_label": "Active",
    "sequence_number": 1,
    "consumes_raw_material": true,
    "is_labor_required": true,
    "raw_material_category_ids": [2],
    "selected_authorized_task_ids": [1]
  }
}
```

---

## 3.7 Toggle Active Status

Quickly activate or deactivate a task stage.

- **HTTP Method:** `PATCH`
- **Full URL:** `https://konnadia.empoweredtechinnovations.org/api/v1/factory/tasks/{id}/toggle-status` (or `/api/v1/admin/production/tasks/{id}/toggle-status`)
- **Response `200 OK`**:
```json
{
  "success": true,
  "message": "Factory Task \"TSK-CUT-01\" deactivated successfully.",
  "data": {
    "id": 1,
    "code": "TSK-CUT-01",
    "name": "Fabric Cutting & Precision Trimming",
    "status": false,
    "status_label": "Inactive"
  }
}
```

---

## 3.8 Delete Task Master

Delete a task master stage.

> **Routing Link Protection Rule:** If the task is mapped to one or more manufacturing product routings (`manufacturing_products_count > 0`), deletion is blocked and returns `422 Unprocessable Content`. Deactivate the task instead.

- **HTTP Method:** `DELETE`
- **Full URL:** `https://konnadia.empoweredtechinnovations.org/api/v1/factory/tasks/{id}` (or `/api/v1/admin/production/tasks/{id}`)

- **Response `200 OK` (Success)**:
```json
{
  "success": true,
  "message": "Factory Task \"Embroidery & Quilting\" deleted successfully."
}
```

- **Response `422 Unprocessable Content` (Blocked by linked product routings)**:
```json
{
  "success": false,
  "message": "Cannot delete task \"Fabric Cutting & Precision Trimming\" — it is currently linked to 4 manufacturing product routing(s). Deactivate it instead.",
  "linked_products_count": 4
}
```

---

# 4. DATA MODELS & TYPES (TYPESCRIPT / DART)

### TypeScript Interfaces
```typescript
export interface FactoryTask {
  id: number;
  name: string;
  code?: string | null;
  status: boolean;
  status_label: 'Active' | 'Inactive';
  sequence_number?: number | null;
  consumes_raw_material: boolean;
  is_labor_required: boolean;
  raw_material_categories?: RawMaterialCategoryOption[];
  raw_material_category_ids?: number[];
  authorized_labor_tasks?: AuthorizedLaborTaskOption[];
  selected_authorized_task_ids?: number[];
  manufacturing_products_count?: number;
  created_at?: string;
  updated_at?: string;
}

export interface RawMaterialCategoryOption {
  id: number;
  name: string;
  code: string;
  unit_type?: string;
}

export interface AuthorizedLaborTaskOption {
  id: number;
  name: string;
  code: string;
  status: boolean;
}

export interface TaskOptionsData {
  tasks: FactoryTask[];
  raw_material_categories: RawMaterialCategoryOption[];
  all_labor_tasks: AuthorizedLaborTaskOption[];
}

export interface CreateTaskPayload {
  name: string;
  code?: string;
  status?: boolean;
  consumes_raw_material: boolean;
  is_labor_required: boolean;
  selected_category_ids?: number[];
  selected_authorized_task_ids?: number[];
  sequence_number?: number;
}

export interface ReorderTasksPayload {
  ordered_ids: number[];
}
```

### Dart (Flutter) Models
```dart
class FactoryTask {
  final int id;
  final String name;
  final String? code;
  final bool status;
  final String statusLabel;
  final int? sequenceNumber;
  final bool consumesRawMaterial;
  final bool isLaborRequired;
  final List<int> rawMaterialCategoryIds;
  final List<int> selectedAuthorizedTaskIds;
  final int manufacturingProductsCount;

  FactoryTask({
    required this.id,
    required this.name,
    this.code,
    required this.status,
    required this.statusLabel,
    this.sequenceNumber,
    required this.consumesRawMaterial,
    required this.isLaborRequired,
    required this.rawMaterialCategoryIds,
    required this.selectedAuthorizedTaskIds,
    required this.manufacturingProductsCount,
  });

  factory FactoryTask.fromJson(Map<String, dynamic> json) {
    return FactoryTask(
      id: json['id'],
      name: json['name'] ?? '',
      code: json['code'],
      status: json['status'] ?? true,
      statusLabel: json['status_label'] ?? 'Active',
      sequenceNumber: json['sequence_number'],
      consumesRawMaterial: json['consumes_raw_material'] ?? false,
      isLaborRequired: json['is_labor_required'] ?? true,
      rawMaterialCategoryIds: List<int>.from(json['raw_material_category_ids'] ?? []),
      selectedAuthorizedTaskIds: List<int>.from(json['selected_authorized_task_ids'] ?? []),
      manufacturingProductsCount: json['manufacturing_products_count'] ?? 0,
    );
  }
}
```

---

# 5. UI/UX BEST PRACTICES & MOBILE DEV RULES

1. **Drag-and-Drop Sequence Reordering:**
   - In the Directory Screen list view, include a drag handle icon (`drag_indicator`) next to each task row.
   - When the user drags and reorders task cards, send the new order of IDs to `POST /factory/tasks/reorder` (`{ "ordered_ids": [3, 1, 2] }`).
2. **Dynamic Form Visibility Dependencies:**
   - When **Consumes Stock** switch is enabled (`consumes_raw_material: true`), dynamically show the **Raw Material Categories** checkbox grid. Require at least 1 category selected. If switched off, clear category selections.
   - When **Labor Dependent** switch is enabled (`is_labor_required: true`), dynamically show the **Authorized Tasks** checkbox grid.
3. **Optimistic Status Switch:**
   - When flipping the task Active switch on a card, update local UI immediately, then call `PATCH /factory/tasks/{id}/toggle-status`. Revert on failure.
4. **Handling Linked Routing Deletion Block (`422` Error):**
   - When calling `DELETE /factory/tasks/{id}`, catch HTTP `422` error responses. If `linked_products_count > 0`, present an alert dialog:
     - **Title:** Cannot Delete Task
     - **Message:** `"Task [Fabric Cutting] is linked to 4 product routings. Deactivate it instead."`
     - **Action:** Offer a button to **"Deactivate Instead"**, which invokes `PATCH /factory/tasks/{id}/toggle-status`.
5. **Real-time Search Debouncing:**
   - Debounce search input by **250ms** before invoking `GET /factory/tasks?search=query`.

---

# 6. ERROR HANDLING & EDGE CASES

| Status Code | Description | Cause / Resolution |
| :--- | :--- | :--- |
| `401 Unauthorized` | Missing or invalid Bearer token | Authenticate via `POST /api/v1/admin/auth/login` |
| `403 Forbidden` | Missing required permission | Ensure user possesses `access production` permission |
| `404 Not Found` | Task ID does not exist | Verify task ID |
| `422 Unprocessable Content` | Validation error (e.g. non-unique task name/code) or linked product routing deletion block | Resolve validation errors or deactivate task instead of deleting |
