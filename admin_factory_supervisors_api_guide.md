# Admin Factory Supervisors Mobile API Documentation & Integration Guide
*(Factory Floor Supervisors Management for Production Batches)*

**Kanodia Textiles — Enterprise Manufacturing & ERP Platform**  
**Module:** Factory Floor / Production Management (`/factory/supervisors`)  
**Target Platform:** Mobile App (iOS / Android — Flutter / React Native / Native)  
**Version:** 1.0  
**Base URL:** `https://konnadia.empoweredtechinnovations.org/`  
**API Prefix:** `/api/v1`  
**Endpoints:**
- Direct Factory Alias: `/api/v1/factory/supervisors`
- Production Floor Admin: `/api/v1/admin/production/supervisors`  
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
   - [3.1 List Supervisors (Paginated with Search & Filters)](#31-list-supervisors-paginated-with-search--filters)
   - [3.2 Supervisor Pickers & Options](#32-supervisor-pickers--options)
   - [3.3 Get Single Supervisor Details](#33-get-single-supervisor-details)
   - [3.4 Create Supervisor](#34-create-supervisor)
   - [3.5 Update Supervisor](#35-update-supervisor)
   - [3.6 Toggle Active Status](#36-toggle-active-status)
   - [3.7 Delete Supervisor](#37-delete-supervisor)
4. [Data Models & Types (TypeScript / Dart)](#4-data-models--types-typescript--dart)
5. [UI/UX Best Practices & Mobile Dev Rules](#5-uiux-best-practices--mobile-dev-rules)
6. [Error Handling & Edge Cases](#6-error-handling--edge-cases)

---

# 1. OVERVIEW & ACCESS CONTROL

The **Factory Floor Supervisors** module allows shop floor managers and admins to view, create, edit, activate/deactivate, and manage factory supervisors who oversee production batches (from raw material cutting through stitching to final quality control).

### Access Control
- **Required Permission:** `access production`
- **Permission Check:** Verify `permissions.includes('access production')` or `access_matrix.can_access_production` in the authenticated profile payload (`POST /api/v1/admin/auth/login` or `GET /api/v1/admin/auth/me`).

---

# 2. MOBILE APP SCREENS & COMPONENT ARCHITECTURE

The mobile app should implement the following **3 primary screens** and **1 reusable dropdown picker component**:

```
 ┌────────────────────────────────────────────────────────┐
 │ 1. Supervisors Directory Screen                        │
 │    - Real-time search bar                              │
 │    - Status filter chips (All / Active / Inactive)     │
 │    - Pull-to-refresh & Infinite pagination              │
 │    - Supervisor card list with Call/Email actions      │
 │    - Floating Action Button (+ Add Supervisor)         │
 └───────────────────────────┬────────────────────────────┘
                             │
            ┌────────────────┴────────────────┐
            ▼                                 ▼
 ┌───────────────────────────┐   ┌───────────────────────────┐
 │ 2. Supervisor Detail      │   │ 3. Create / Edit Form     │
 │    - Profile Header & Code│   │    - Full Name *          │
 │    - Department Pill      │   │    - Supervisor Code      │
 │    - Quick Call / Email   │   │    - Mobile Phone         │
 │    - Status Toggle Switch │   │    - Department Input     │
 │    - Linked Batches List  │   │    - Notes Textarea       │
 └───────────────────────────┘   │    - Active Status Switch │
                                 └───────────────────────────┘
                                              ▲
 ┌────────────────────────────────────────────┴──────────────┐
 │ 4. Production Batch Supervisor Picker (Reusable Component) │
 │    - BottomSheet / Dialog picker for Batch creation form  │
 └───────────────────────────────────────────────────────────┘
```

---

# 3. ENDPOINTS SPECIFICATION

## 3.1 List Supervisors (Paginated with Search & Filters)

Retrieve paginated or full list of factory supervisors with batch counts.

- **HTTP Method:** `GET`
- **Full URL:** `https://konnadia.empoweredtechinnovations.org/api/v1/factory/supervisors` (or `/api/v1/admin/production/supervisors`)
- **Query Parameters:**
  - `search` *(optional string)*: Search by supervisor name, code, phone, email, or department.
  - `status` / `status_filter` *(optional string)*: `active` or `inactive`.
  - `department` *(optional string)*: Filter by department name (e.g. `Cutting`, `Stitching`, `QC`).
  - `paginate` *(optional string)*: `true` to force pagination.
  - `per_page` *(optional int, default 15)*: Page size limit.
  - `page` *(optional int, default 1)*: Page number for infinite scroll.

- **Response `200 OK`**:
```json
{
  "success": true,
  "message": "Supervisors retrieved successfully.",
  "data": [
    {
      "id": 1,
      "code": "SUP-0001",
      "name": "Ramesh Kumar",
      "phone": "+91 98765 43210",
      "email": "ramesh@factory.in",
      "department": "Cutting",
      "notes": "Senior cutting floor supervisor",
      "is_active": true,
      "status_label": "Active",
      "production_batches_count": 5,
      "created_at": "2026-09-15T19:30:00+01:00",
      "updated_at": "2026-09-15T19:30:00+01:00"
    }
  ],
  "meta": {
    "current_page": 1,
    "per_page": 15,
    "total": 1,
    "last_page": 1
  }
}
```

---

## 3.2 Supervisor Pickers & Options

Retrieve lightweight list of active supervisors and department names for dropdown selection in production batch creation dialogs.

- **HTTP Method:** `GET`
- **Full URL:** `https://konnadia.empoweredtechinnovations.org/api/v1/factory/supervisors/options` (or `/api/v1/admin/production/supervisors/options`)
- **Response `200 OK`**:
```json
{
  "success": true,
  "data": {
    "supervisors": [
      {
        "id": 1,
        "name": "Ramesh Kumar",
        "code": "SUP-0001",
        "department": "Cutting",
        "phone": "+91 98765 43210",
        "email": "ramesh@factory.in"
      },
      {
        "id": 2,
        "name": "Priya Shah",
        "code": "SUP-0002",
        "department": "Stitching",
        "phone": "+91 91234 56789",
        "email": "priya@factory.in"
      }
    ],
    "departments": [
      "Cutting",
      "Quality Control",
      "Stitching"
    ]
  }
}
```

---

## 3.3 Get Single Supervisor Details

Retrieve complete supervisor profile including recent supervised production batches.

- **HTTP Method:** `GET`
- **Full URL:** `https://konnadia.empoweredtechinnovations.org/api/v1/factory/supervisors/{id}` (or `/api/v1/admin/production/supervisors/{id}`)
- **Response `200 OK`**:
```json
{
  "success": true,
  "data": {
    "id": 1,
    "code": "SUP-0001",
    "name": "Ramesh Kumar",
    "phone": "+91 98765 43210",
    "email": "ramesh@factory.in",
    "department": "Cutting",
    "notes": "Senior cutting floor supervisor",
    "is_active": true,
    "status_label": "Active",
    "production_batches_count": 1,
    "recent_batches": [
      {
        "id": 10,
        "batch_code": "PB-2026-0031",
        "status": "in_progress",
        "target_quantity": 200,
        "manufacturing_product": {
          "id": 4,
          "name": "KTC King 108×108",
          "code": "PROD-KING-108"
        },
        "created_at": "2026-09-15T18:00:00+01:00"
      }
    ],
    "created_at": "2026-09-15T19:30:00+01:00",
    "updated_at": "2026-09-15T19:30:00+01:00"
  }
}
```

---

## 3.4 Create Supervisor

Create a new supervisor. 

> **Auto-Code Generation Rule:** If `code` is left blank or omitted, the server automatically generates a code formatted as `SUP-0001`, `SUP-0002`, etc.

- **HTTP Method:** `POST`
- **Full URL:** `https://konnadia.empoweredtechinnovations.org/api/v1/factory/supervisors` (or `/api/v1/admin/production/supervisors`)
- **Request Body (JSON)**:
```json
{
  "name": "Priya Shah",
  "code": "SUP-0002",
  "phone": "+91 91234 56789",
  "email": "priya@factory.in",
  "department": "Stitching",
  "notes": "Stitching supervisor",
  "is_active": true
}
```

- **Parameters**:
  - `name` *(required string, max 255)*: Full name of supervisor.
  - `code` *(optional string, max 50, unique)*: Supervisor code. Auto-generated formatted code if blank.
  - `phone` *(optional string, max 50)*: Contact phone number.
  - `email` *(optional string, email, max 255, unique)*: Email address.
  - `department` *(optional string, max 255)*: Department name.
  - `notes` *(optional string)*: Notes/responsibilities description.
  - `is_active` *(optional boolean, default true)*: Active status.

- **Response `201 Created`**:
```json
{
  "success": true,
  "message": "Supervisor \"Priya Shah\" created successfully.",
  "data": {
    "id": 2,
    "code": "SUP-0002",
    "name": "Priya Shah",
    "phone": "+91 91234 56789",
    "email": "priya@factory.in",
    "department": "Stitching",
    "notes": "Stitching supervisor",
    "is_active": true,
    "status_label": "Active",
    "production_batches_count": 0,
    "created_at": "2026-09-15T19:40:00+01:00",
    "updated_at": "2026-09-15T19:40:00+01:00"
  }
}
```

---

## 3.5 Update Supervisor

Update an existing supervisor record.

- **HTTP Method:** `PUT` or `PATCH`
- **Full URL:** `https://konnadia.empoweredtechinnovations.org/api/v1/factory/supervisors/{id}` (or `/api/v1/admin/production/supervisors/{id}`)
- **Request Body (JSON)**:
```json
{
  "name": "Priya V. Shah",
  "department": "Quality Assurance",
  "is_active": true
}
```

- **Response `200 OK`**:
```json
{
  "success": true,
  "message": "Supervisor \"Priya V. Shah\" updated successfully.",
  "data": {
    "id": 2,
    "code": "SUP-0002",
    "name": "Priya V. Shah",
    "phone": "+91 91234 56789",
    "email": "priya@factory.in",
    "department": "Quality Assurance",
    "notes": "Stitching supervisor",
    "is_active": true,
    "status_label": "Active",
    "production_batches_count": 0,
    "created_at": "2026-09-15T19:40:00+01:00",
    "updated_at": "2026-09-15T19:45:00+01:00"
  }
}
```

---

## 3.6 Toggle Active Status

Instantly activate (`true`) or deactivate (`false`) a supervisor.

- **HTTP Method:** `PATCH`
- **Full URL:** `https://konnadia.empoweredtechinnovations.org/api/v1/factory/supervisors/{id}/toggle-status` (or `/api/v1/admin/production/supervisors/{id}/toggle-status`)
- **Response `200 OK`**:
```json
{
  "success": true,
  "message": "Supervisor SUP-0002 set to Inactive.",
  "data": {
    "id": 2,
    "code": "SUP-0002",
    "name": "Priya V. Shah",
    "is_active": false,
    "status_label": "Inactive"
  }
}
```

---

## 3.7 Delete Supervisor

Soft delete a supervisor record.

> **Deletion Protection Rule:** If the supervisor has `production_batches_count > 0`, the backend rejects deletion with `422 Unprocessable Content`.

- **HTTP Method:** `DELETE`
- **Full URL:** `https://konnadia.empoweredtechinnovations.org/api/v1/factory/supervisors/{id}` (or `/api/v1/admin/production/supervisors/{id}`)

- **Response `200 OK` (When delete succeeds)**:
```json
{
  "success": true,
  "message": "Supervisor \"Priya V. Shah\" deleted successfully."
}
```

- **Response `422 Unprocessable Content` (When supervisor is linked to production batches)**:
```json
{
  "success": false,
  "message": "Cannot delete supervisor [Ramesh Kumar] — they are linked to 5 production batch(es).",
  "linked_batches_count": 5
}
```

---

# 4. DATA MODELS & TYPES (TYPESCRIPT / DART)

### TypeScript Interfaces
```typescript
export interface FactorySupervisor {
  id: number;
  code: string;
  name: string;
  phone?: string | null;
  email?: string | null;
  department?: string | null;
  notes?: string | null;
  is_active: boolean;
  status_label: 'Active' | 'Inactive';
  production_batches_count: number;
  recent_batches?: RecentProductionBatch[];
  created_at?: string;
  updated_at?: string;
}

export interface RecentProductionBatch {
  id: number;
  batch_code: string;
  status: string;
  target_quantity: number;
  manufacturing_product?: {
    id: number;
    name: string;
    code: string;
  } | null;
  created_at?: string;
}

export interface SupervisorOptionsData {
  supervisors: Array<{
    id: number;
    name: string;
    code: string;
    department?: string | null;
    phone?: string | null;
    email?: string | null;
  }>;
  departments: string[];
}

export interface CreateSupervisorPayload {
  name: string;
  code?: string;
  phone?: string;
  email?: string;
  department?: string;
  notes?: string;
  is_active?: boolean;
}
```

### Dart (Flutter) Models
```dart
class FactorySupervisor {
  final int id;
  final String code;
  final String name;
  final String? phone;
  final String? email;
  final String? department;
  final String? notes;
  final bool isActive;
  final String statusLabel;
  final int productionBatchesCount;

  FactorySupervisor({
    required this.id,
    required this.code,
    required this.name,
    this.phone,
    this.email,
    this.department,
    this.notes,
    required this.isActive,
    required this.statusLabel,
    required this.productionBatchesCount,
  });

  factory FactorySupervisor.fromJson(Map<String, dynamic> json) {
    return FactorySupervisor(
      id: json['id'],
      code: json['code'] ?? '',
      name: json['name'] ?? '',
      phone: json['phone'],
      email: json['email'],
      department: json['department'],
      notes: json['notes'],
      isActive: json['is_active'] ?? true,
      statusLabel: json['status_label'] ?? 'Active',
      productionBatchesCount: json['production_batches_count'] ?? 0,
    );
  }
}
```

---

# 5. UI/UX BEST PRACTICES & MOBILE DEV RULES

1. **Real-time Search Debouncing:**
   - Debounce search input by **300ms** before calling `GET /factory/supervisors?search=query` to avoid redundant network requests.
2. **Native Phone & Email Actions:**
   - Wire the phone number tap action to launch native dialer (`url_launcher` with `tel:+919876543210`).
   - Wire the email tap action to launch email client (`mailto:ramesh@factory.in`).
3. **Optimistic Status Toggling:**
   - When the user flips the Active Status switch on a card, update local UI state immediately, then call `PATCH /factory/supervisors/{id}/toggle-status`. On failure, revert local UI state and show a toast.
4. **Auto-Capitalize Code Input:**
   - Automatically convert Supervisor Code text field input to uppercase (e.g. `sup-0001` -> `SUP-0001`).
5. **Handling Linked Deletion Protection (`422` Error):**
   - When calling `DELETE /factory/supervisors/{id}`, catch HTTP `422` response. If `linked_batches_count > 0`, present an alert dialog:
     - **Title:** Cannot Delete Supervisor
     - **Message:** `"Ramesh Kumar is assigned to 5 production batch(es). You cannot delete a linked supervisor."`
     - **Action:** Offer a button to **"Set Inactive Instead"**, which invokes `PATCH /factory/supervisors/{id}/toggle-status`.
6. **Form Validation Feedback:**
   - Validate that `Name` is non-empty before submitting.
   - If email is provided, validate regex email format before sending API request.

---

# 6. ERROR HANDLING & EDGE CASES

| Status Code | Description | Cause / Resolution |
| :--- | :--- | :--- |
| `401 Unauthorized` | Missing or invalid Bearer token | Authenticate via `POST /api/v1/admin/auth/login` |
| `403 Forbidden` | Missing required permission | Ensure admin user possesses `access production` permission |
| `404 Not Found` | Supervisor ID does not exist | Verify supervisor ID |
| `422 Unprocessable Content` | Validation error (e.g. duplicate email/code) or linked batch deletion block | Resolve validation errors or deactivate supervisor instead of deleting |
