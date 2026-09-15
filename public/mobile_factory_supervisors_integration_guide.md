# Factory Supervisors Mobile App Integration Guide
**Kanodia Textiles — Enterprise Manufacturing & ERP Platform**  
**Module:** Factory Floor / Production Management (`/factory/supervisors`)  
**Target Platform:** Mobile App (iOS / Android — Flutter / React Native / Native)  
**Version:** 1.0  
**Base URL:** `https://konnadia.empoweredtechinnovations.org/api/v1`

---

## 1. OVERVIEW & AUTHENTICATION STANDARD

The **Factory Floor Supervisors** module allows shop floor managers and admins to view, create, edit, activate/deactivate, and manage factory supervisors who oversee production batches (from cutting through stitching to final quality control).

### Common Headers Required
```http
Content-Type: application/json
Accept: application/json
Authorization: Bearer <YOUR_JWT_TOKEN>
```

### Access Control & Permissions
- **Required Permission:** `access production`
- **User Permission Check:** Verify `permissions.includes('access production')` or `access_matrix.can_access_production` in the auth user profile payload before rendering the Supervisors section in the app navigation drawer or tab bar.

---

## 2. APP SCREENS & COMPONENT ARCHITECTURE

The mobile developer should implement the following **3 primary screens** and **1 reusable dropdown picker component**:

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

## 3. ENDPOINTS SPECIFICATION & PAYLOADS

### 3.1 Fetch Supervisors List (Directory Screen)
Fetch paginated supervisor list with multi-field search and status/department filtering.

- **HTTP Method:** `GET`
- **Endpoint:** `/factory/supervisors` (or `/admin/production/supervisors`)
- **Query Parameters:**
  - `search` *(optional string)*: Search by supervisor name, code, phone, email, or department.
  - `status` *(optional string)*: `active` or `inactive`.
  - `department` *(optional string)*: Filter by department name.
  - `page` *(optional int, default 1)*: Page number for infinite scroll.
  - `per_page` *(optional int, default 15)*: Items per page.

#### Request Example
`GET /api/v1/factory/supervisors?search=Ramesh&status=active&page=1&per_page=15`

#### Success Response (`200 OK`)
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

### 3.2 Supervisor Picker Options (For Production Batch Creation Form)
Lightweight endpoint returning active supervisors and existing department names. Use this inside BottomSheets or dropdowns when creating/editing production batches.

- **HTTP Method:** `GET`
- **Endpoint:** `/factory/supervisors/options` (or `/admin/production/supervisors/options`)

#### Success Response (`200 OK`)
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

### 3.3 Get Supervisor Details (Details Screen)
Fetch full profile details and recent production batches supervised by this supervisor.

- **HTTP Method:** `GET`
- **Endpoint:** `/factory/supervisors/{id}` (or `/admin/production/supervisors/{id}`)

#### Success Response (`200 OK`)
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
    "production_batches_count": 2,
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

### 3.4 Create Supervisor (Form Screen / Modal)
Create a new supervisor. 

> **Auto-Code Rule:** If `code` is left blank or empty string `""`, the server automatically generates a code formatted as `SUP-0001`, `SUP-0002`, etc.

- **HTTP Method:** `POST`
- **Endpoint:** `/factory/supervisors` (or `/admin/production/supervisors`)

#### Request Body
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

#### Success Response (`201 Created`)
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

### 3.5 Update Supervisor (Form Screen / Modal)
Update an existing supervisor profile.

- **HTTP Method:** `PUT` or `PATCH`
- **Endpoint:** `/factory/supervisors/{id}` (or `/admin/production/supervisors/{id}`)

#### Request Body
```json
{
  "name": "Priya V. Shah",
  "department": "Quality Assurance",
  "is_active": true
}
```

#### Success Response (`200 OK`)
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

### 3.6 Toggle Active Status (List Card Switch)
Instantly activate or deactivate a supervisor from the list item toggle switch.

- **HTTP Method:** `PATCH`
- **Endpoint:** `/factory/supervisors/{id}/toggle-status`

#### Success Response (`200 OK`)
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

### 3.7 Delete Supervisor (Swipe Action / Menu Item)
Soft delete a supervisor record. 

> **Deletion Protection Rule:** If the supervisor has `production_batches_count > 0`, the backend rejects deletion with `422 Unprocessable Content`. The mobile app should catch this error and display an alert dialog explaining that linked supervisors cannot be deleted and should be set to inactive instead.

- **HTTP Method:** `DELETE`
- **Endpoint:** `/factory/supervisors/{id}`

#### Success Response (`200 OK`)
```json
{
  "success": true,
  "message": "Supervisor \"Priya V. Shah\" deleted successfully."
}
```

#### Deletion Blocked Response (`422 Unprocessable Content`)
```json
{
  "success": false,
  "message": "Cannot delete supervisor [Ramesh Kumar] — they are linked to 5 production batch(es).",
  "linked_batches_count": 5
}
```

---

## 4. DATA TYPES & INTERFACES (TypeScript / Dart)

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

## 5. UI/UX BEST PRACTICES FOR MOBILE DEV

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
