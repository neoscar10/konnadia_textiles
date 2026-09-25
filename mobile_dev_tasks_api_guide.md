# Factory Task Master Management API - Mobile Developer Integration Guide

## 1. Overview & Authentication
This guide documents the RESTful API endpoints for managing **Factory Tasks** (Task Master) in the Konnadia Textiles Factory Operations Suite.

- **Base URL:** `https://your-domain.com/api/v1`
- **Authentication:** HTTP Bearer Token (Sanctum / API Guard)
- **Headers:**
  - `Authorization: Bearer <user_api_token>`
  - `Accept: application/json`
  - `Content-Type: application/json` (for POST, PUT, PATCH requests)
- **Permission Required:** `access production`

> **Note:** Direct aliases are provided under `/factory/tasks` (e.g. `/api/v1/factory/tasks`) as well as `/admin/tasks` and `/tasks`.

---

## 2. Core Features & Business Logic Rules

1. **Raw Material Consumption (`consumes_raw_material`)**:
   - If `consumes_raw_material` is set to `true`, the request **must** supply an array of raw material category IDs (`selected_category_ids`).
   - If `consumes_raw_material` is `false`, attached raw material categories are detached.

2. **Labor Requirement (`is_labor_required`) & Labor Category (`labor_category_id`)**:
   - If `is_labor_required` is `true`, a primary `labor_category_id` can be specified to automatically target eligible labor workers.
   - If `is_labor_required` is `false`, `labor_category_id` is automatically set to `null` and any authorized sub-tasks are detached.

3. **Authorized Labor Tasks (`selected_authorized_task_ids`)**:
   - Allows designating specific sub-tasks or authorization skills required for a worker to perform this task.

4. **Sequence Numbering (`sequence_number`)**:
   - Tasks are ordered by `sequence_number ASC`, then `name ASC`.
   - If omitted during creation, `sequence_number` automatically defaults to `MAX(sequence_number) + 1`.

5. **Routing Protection on Deletion**:
   - A task currently linked to manufacturing product routings **cannot** be deleted (returns `HTTP 422 Unprocessable Content`). The mobile app should prompt the user to deactivate the task instead.

---

## 3. API Endpoints Quick Reference

| Method | Endpoint | Description |
| :--- | :--- | :--- |
| `GET` | `/api/v1/factory/tasks` | List, search, filter, and paginate factory tasks |
| `GET` | `/api/v1/factory/tasks/options` | Fetch form picker options (tasks, raw material categories, labor categories) |
| `GET` | `/api/v1/factory/tasks/{id}` | Fetch single task details |
| `POST` | `/api/v1/factory/tasks` | Create a new factory task |
| `PUT` | `/api/v1/factory/tasks/{id}` | Update an existing factory task |
| `PATCH` | `/api/v1/factory/tasks/{id}/toggle-status` | Toggle active/inactive status |
| `POST` | `/api/v1/factory/tasks/reorder` | Reorder task sequence numbers |
| `DELETE` | `/api/v1/factory/tasks/{id}` | Delete task (protected against routing dependencies) |

---

## 4. Endpoint Specifications & Payload Examples

### 4.1 List Tasks
`GET /api/v1/factory/tasks`

#### Query Parameters:
- `search` *(optional string)*: Filter by task name or task code.
- `status` *(optional string)*: `'active'` or `'inactive'`.
- `paginate` *(optional string)*: `'true'` to return paginated response.
- `per_page` *(optional integer)*: Page size when paginated (default: 15).

#### Example Response (`HTTP 200 OK`):
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "name": "Fabric Cutting",
      "code": "TSK-0001",
      "status": true,
      "status_label": "Active",
      "sequence_number": 1,
      "consumes_raw_material": true,
      "is_labor_required": true,
      "labor_category_id": 2,
      "labor_category": {
        "id": 2,
        "name": "Cutting Specialists",
        "code": "LCAT-0002",
        "status": true
      },
      "raw_material_categories": [
        {
          "id": 5,
          "name": "Cotton Fabric",
          "code": "CAT-COTTON",
          "unit_type": "length_based"
        }
      ],
      "raw_material_category_ids": [5],
      "authorized_labor_tasks": [
        {
          "id": 3,
          "name": "Sub-Cutting Inspection",
          "code": "TSK-0003",
          "status": true
        }
      ],
      "selected_authorized_task_ids": [3],
      "manufacturing_products_count": 12,
      "created_at": "2026-09-25T08:30:00+00:00",
      "updated_at": "2026-09-25T10:15:00+00:00"
    }
  ]
}
```

---

### 4.2 Get Form Options
`GET /api/v1/factory/tasks/options`

Use this endpoint when initializing the Task Create/Edit modal to populate dropdowns and multi-select pickers.

#### Example Response (`HTTP 200 OK`):
```json
{
  "success": true,
  "data": {
    "tasks": [
      {
        "id": 1,
        "name": "Fabric Cutting",
        "code": "TSK-0001",
        "consumes_raw_material": true,
        "is_labor_required": true,
        "sequence_number": 1
      }
    ],
    "raw_material_categories": [
      {
        "id": 5,
        "name": "Cotton Fabric",
        "code": "CAT-COTTON",
        "unit_type": "length_based"
      }
    ],
    "all_labor_tasks": [
      {
        "id": 1,
        "name": "Fabric Cutting",
        "code": "TSK-0001",
        "status": true,
        "sequence_number": 1
      }
    ],
    "labor_categories": [
      {
        "id": 2,
        "name": "Cutting Specialists",
        "code": "LCAT-0002",
        "status": true
      }
    ]
  }
}
```

---

### 4.3 Get Single Task Detail
`GET /api/v1/factory/tasks/{id}`

#### Example Response (`HTTP 200 OK`):
```json
{
  "success": true,
  "data": {
    "id": 1,
    "name": "Fabric Cutting",
    "code": "TSK-0001",
    "status": true,
    "status_label": "Active",
    "sequence_number": 1,
    "consumes_raw_material": true,
    "is_labor_required": true,
    "labor_category_id": 2,
    "labor_category": {
      "id": 2,
      "name": "Cutting Specialists",
      "code": "LCAT-0002",
      "status": true
    },
    "raw_material_categories": [
      {
        "id": 5,
        "name": "Cotton Fabric",
        "code": "CAT-COTTON",
        "unit_type": "length_based"
      }
    ],
    "raw_material_category_ids": [5],
    "authorized_labor_tasks": [],
    "selected_authorized_task_ids": [],
    "manufacturing_products_count": 0,
    "created_at": "2026-09-25T08:30:00+00:00",
    "updated_at": "2026-09-25T10:15:00+00:00"
  }
}
```

---

### 4.4 Create Task
`POST /api/v1/factory/tasks`

#### Request Body Payload:
```json
{
  "name": "Precision Stitching",
  "code": "TSK-STITCH-01",
  "status": true,
  "consumes_raw_material": true,
  "is_labor_required": true,
  "labor_category_id": 2,
  "selected_category_ids": [5, 8],
  "selected_authorized_task_ids": [1],
  "sequence_number": 3
}
```

#### Field Validation Rules:
- `name` *(required string, max:255, unique:tasks,name)*
- `code` *(optional string, max:50, unique:tasks,code)*
- `status` *(optional boolean, default: true)*
- `consumes_raw_material` *(required boolean)*
- `is_labor_required` *(required boolean)*
- `labor_category_id` *(optional integer, exists:labor_categories,id)*
- `selected_category_ids` *(required if `consumes_raw_material` = true, array of integer category IDs)*
- `selected_authorized_task_ids` *(optional array of integer task IDs)*
- `sequence_number` *(optional integer, min:1)*

#### Response (`HTTP 201 Created`):
```json
{
  "success": true,
  "message": "Factory Task \"Precision Stitching\" created successfully.",
  "data": {
    "id": 4,
    "name": "Precision Stitching",
    "code": "TSK-STITCH-01",
    "status": true,
    "status_label": "Active",
    "sequence_number": 3,
    "consumes_raw_material": true,
    "is_labor_required": true,
    "labor_category_id": 2,
    "labor_category": {
      "id": 2,
      "name": "Cutting Specialists",
      "code": "LCAT-0002",
      "status": true
    },
    "raw_material_categories": [...],
    "raw_material_category_ids": [5, 8],
    "authorized_labor_tasks": [...],
    "selected_authorized_task_ids": [1],
    "manufacturing_products_count": 0,
    "created_at": "2026-09-25T11:00:00+00:00",
    "updated_at": "2026-09-25T11:00:00+00:00"
  }
}
```

---

### 4.5 Update Task
`PUT /api/v1/factory/tasks/{id}`

#### Request Body Payload:
```json
{
  "name": "Precision Stitching & Finishing",
  "code": "TSK-STITCH-01",
  "status": true,
  "consumes_raw_material": false,
  "is_labor_required": true,
  "labor_category_id": 2,
  "sequence_number": 3
}
```

#### Response (`HTTP 200 OK`):
```json
{
  "success": true,
  "message": "Factory Task \"Precision Stitching & Finishing\" updated successfully.",
  "data": { ... }
}
```

---

### 4.6 Toggle Task Status
`PATCH /api/v1/factory/tasks/{id}/toggle-status`

Quick toggle endpoint for active/inactive state. No request payload required.

#### Response (`HTTP 200 OK`):
```json
{
  "success": true,
  "message": "Factory Task \"Fabric Cutting\" deactivated successfully.",
  "data": {
    "id": 1,
    "status": false,
    "status_label": "Inactive",
    ...
  }
}
```

---

### 4.7 Reorder Tasks Sequence
`POST /api/v1/factory/tasks/reorder`

Send an array of task IDs in the new desired order. Sequence numbers will automatically be updated `1..N`.

#### Request Body Payload:
```json
{
  "ordered_ids": [4, 1, 2, 3]
}
```

#### Response (`HTTP 200 OK`):
```json
{
  "success": true,
  "message": "Task sequence order updated successfully.",
  "data": [ ... array of updated tasks ... ]
}
```

---

### 4.8 Delete Task
`DELETE /api/v1/factory/tasks/{id}`

#### Success Response (`HTTP 200 OK`):
```json
{
  "success": true,
  "message": "Factory Task \"Precision Stitching\" deleted successfully."
}
```

#### Routing Conflict Error Response (`HTTP 422 Unprocessable Content`):
```json
{
  "success": false,
  "message": "Cannot delete task \"Fabric Cutting\" because it is currently linked to 12 manufacturing product routing(s). Deactivate it instead.",
  "linked_products_count": 12
}
```

---

## 5. Mobile UX Implementation Recommendations

1. **Dynamic Form Visibility**:
   - When **"Consumes Raw Material"** toggle is enabled, show the Multi-Select Picker for Raw Material Categories. Require at least 1 selection.
   - When **"Requires Labor"** toggle is enabled, show the Single-Select Dropdown for **Labor Category** and optional Multi-Select for **Authorized Sub-Tasks**.

2. **Drag & Drop Reordering**:
   - Implement a reorderable list view using the `sequence_number`. Call `POST /api/v1/factory/tasks/reorder` when dragging finishes.

3. **Status Toggle Switch**:
   - Use direct toggle calls to `PATCH /api/v1/factory/tasks/{id}/toggle-status` for inline switches in list rows.

4. **Deletion Guard Dialog**:
   - If deletion returns `422 Unprocessable Content`, display an alert dialog explaining that the task is linked to manufacturing products and offer a direct "Deactivate Task" button instead.