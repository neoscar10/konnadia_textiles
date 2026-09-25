# Kannodia Textiles Mobile App API Implementation Guide
## Module: Factory Floor Supervisors & Department Management

---

## 1. Overview & Context

The **Factory Floor Supervisors** module allows floor managers, plant administrators, and supervisors to maintain factory floor supervisory records, assign department responsibilities (Cutting, Stitching, Quality Check, Master Cutting, Packing), inspect production batch assignments, and toggle active statuses.

### Key Features Covered:
1. **Supervisor Directory Listing & Filters**: Paginated and unpaginated lists with search by supervisor name, code, phone, email, or department, and status filters (`active`, `inactive`, `all`).
2. **Picker Lookup Options**: Lightweight options endpoint returning active supervisors and existing departments for dropdown selection.
3. **Supervisor Details & Batch Links**: 360-degree view of supervisor info including total assigned production batch count and recent 10 assigned production batches.
4. **CRUD Management**: Create, update, soft-delete, and toggle active status (`is_active`).

---

## 2. Global Authentication & API Standards

* **Base URL**: `https://konnadia.empoweredtechinnovations.org/api/v1` (or local environment `http://localhost/api/v1`)
* **Route Aliases Available**:
  - `/factory/supervisors`
  - `/production/supervisors`
  - `/admin/production/supervisors`
* **Headers Required**:
  - `Authorization`: `Bearer <JWT_BEARER_TOKEN>`
  - `Accept`: `application/json`
  - `Content-Type`: `application/json`
* **Permission Required**: `access production` or `super_admin` role.

---

## 3. Endpoints Reference: Factory Supervisors

### A. List Supervisors

Retrieve supervisors with search, status, and department filtering.

* **HTTP Method**: `GET`
* **Route**: `/factory/supervisors` (Aliases: `/production/supervisors`, `/admin/production/supervisors`)

#### Query Parameters
| Parameter | Type | Default | Description |
| :--- | :--- | :--- | :--- |
| `search` | `string` | `null` | Search by supervisor name, code (`SUP-xxxx`), phone, email, or department |
| `status` / `status_filter` | `string` | `"all"` | Filter by status (`"active"`, `"inactive"`, `"all"`) |
| `department` | `string` | `null` | Filter by specific department |
| `paginate` | `boolean` | `false` | Pass `"true"` to enable pagination |
| `page` | `integer` | `1` | Page number (enables pagination automatically) |
| `per_page` | `integer` | `15` | Items per page (1 to 100) |

#### Response Example (`200 OK`)
```json
{
  "success": true,
  "message": "Supervisors retrieved successfully.",
  "data": [
    {
      "id": 4,
      "code": "SUP-0001",
      "name": "Ramesh Kumar",
      "phone": "+91 98765 43210",
      "email": "ramesh@factory.in",
      "department": "Cutting",
      "notes": "Senior floor supervisor",
      "is_active": true,
      "status_label": "Active",
      "production_batches_count": 12,
      "created_at": "2026-09-24T18:00:00Z",
      "updated_at": "2026-09-25T10:15:00Z"
    }
  ]
}
```

---

### B. Form Lookup Options

Fetch active supervisors and distinct departments for dropdown pickers.

* **HTTP Method**: `GET`
* **Route**: `/factory/supervisors/options` (Aliases: `/production/supervisors/options`, `/admin/production/supervisors/options`)

#### Response Example (`200 OK`)
```json
{
  "success": true,
  "data": {
    "supervisors": [
      {
        "id": 4,
        "name": "Ramesh Kumar",
        "code": "SUP-0001",
        "department": "Cutting",
        "phone": "+91 98765 43210",
        "email": "ramesh@factory.in"
      }
    ],
    "departments": [
      "Cutting",
      "Master Cutting",
      "Packing",
      "Quality Check",
      "Stitching"
    ]
  }
}
```

---

### C. Show Supervisor Details

Fetch full detail for a single supervisor including recent assigned batches.

* **HTTP Method**: `GET`
* **Route**: `/factory/supervisors/{id}` (Aliases: `/production/supervisors/{id}`, `/admin/production/supervisors/{id}`)

#### Response Example (`200 OK`)
```json
{
  "success": true,
  "data": {
    "id": 4,
    "code": "SUP-0001",
    "name": "Ramesh Kumar",
    "phone": "+91 98765 43210",
    "email": "ramesh@factory.in",
    "department": "Cutting",
    "notes": "Senior floor supervisor",
    "is_active": true,
    "status_label": "Active",
    "production_batches_count": 12,
    "recent_batches": [
      {
        "id": 53,
        "batch_code": "PB-2026-0053",
        "status": "In Progress",
        "target_quantity": 100.0,
        "manufacturing_product": {
          "id": 8,
          "name": "King Size Bedsheet",
          "code": "MP-BED-K"
        },
        "created_at": "2026-09-24T18:00:00Z"
      }
    ],
    "created_at": "2026-09-24T18:00:00Z",
    "updated_at": "2026-09-25T10:15:00Z"
  }
}
```

---

### D. Create Supervisor

Create a new supervisor record. Code auto-generates if omitted (e.g. `SUP-0005`).

* **HTTP Method**: `POST`
* **Route**: `/factory/supervisors` (Aliases: `/production/supervisors`, `/admin/production/supervisors`)

#### Payload Specification
```json
{
  "name": "Priya Shah",
  "code": "SUP-0002",
  "phone": "+91 91234 56789",
  "email": "priya@factory.in",
  "department": "Stitching",
  "notes": "Stitching floor head",
  "is_active": true
}
```

#### Field Rules:
| Field | Type | Validation | Description |
| :--- | :--- | :--- | :--- |
| `name` | `string` | Required, max 255 | Full name |
| `code` | `string` | Optional, max 50, unique | Code (Auto-generated if empty) |
| `phone` | `string` | Optional, max 50 | Phone number |
| `email` | `string` | Optional, email, max 255, unique | Email address |
| `department` | `string` | Optional, max 255 | Department name |
| `notes` | `string` | Optional | Additional notes |
| `is_active` | `boolean` | Optional, default true | Active state |

#### Response Example (`201 Created`)
```json
{
  "success": true,
  "message": "Supervisor \"Priya Shah\" created successfully.",
  "data": {
    "id": 5,
    "code": "SUP-0002",
    "name": "Priya Shah",
    "phone": "+91 91234 56789",
    "email": "priya@factory.in",
    "department": "Stitching",
    "notes": "Stitching floor head",
    "is_active": true,
    "status_label": "Active",
    "production_batches_count": 0,
    "created_at": "2026-09-25T22:50:00Z",
    "updated_at": "2026-09-25T22:50:00Z"
  }
}
```

---

### E. Update Supervisor

Update an existing supervisor record.

* **HTTP Method**: `PUT` or `PATCH`
* **Route**: `/factory/supervisors/{id}` (Aliases: `/production/supervisors/{id}`, `/admin/production/supervisors/{id}`)

#### Payload Specification
```json
{
  "name": "Ramesh V. Kumar",
  "department": "Master Cutting",
  "phone": "+91 98765 43210"
}
```

#### Response Example (`200 OK`)
```json
{
  "success": true,
  "message": "Supervisor \"Ramesh V. Kumar\" updated successfully.",
  "data": {
    "id": 4,
    "code": "SUP-0001",
    "name": "Ramesh V. Kumar",
    "department": "Master Cutting",
    "is_active": true,
    "status_label": "Active"
  }
}
```

---

### F. Toggle Active Status

Toggle the active state of a supervisor between `Active` and `Inactive`.

* **HTTP Method**: `PATCH`
* **Route**: `/factory/supervisors/{id}/toggle-status` (Aliases: `/production/supervisors/{id}/toggle-status`, `/admin/production/supervisors/{id}/toggle-status`)

#### Response Example (`200 OK`)
```json
{
  "success": true,
  "message": "Supervisor SUP-0001 set to Inactive.",
  "data": {
    "id": 4,
    "code": "SUP-0001",
    "name": "Ramesh V. Kumar",
    "is_active": false,
    "status_label": "Inactive"
  }
}
```

---

### G. Delete Supervisor

Soft-delete a supervisor. **Fails with HTTP 422 if supervisor is linked to existing production batches.**

* **HTTP Method**: `DELETE`
* **Route**: `/factory/supervisors/{id}` (Aliases: `/production/supervisors/{id}`, `/admin/production/supervisors/{id}`)

#### Success Response (`200 OK`)
```json
{
  "success": true,
  "message": "Supervisor \"Priya Shah\" deleted successfully."
}
```

#### Error Response when linked to batches (`422 Unprocessable Entity`)
```json
{
  "success": false,
  "message": "Cannot delete supervisor [Ramesh Kumar] — they are linked to 12 production batch(es).",
  "linked_batches_count": 12
}
```
