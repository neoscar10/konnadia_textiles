# Mobile Developer API & Integration Guide: Factory Wastage & Scrap Log

> **Module:** Factory Management / Production Wastage Log  
> **Target Audience:** Mobile App Developers (Flutter / React Native / Native iOS & Android)  
> **Authentication:** `Bearer {token}` (Header: `Authorization: Bearer <JWT/Sanctum Token>`)  
> **Required Permission:** `access production` (or Admin Role)

---

## 1. Overview & Feature Parity

The **Wastage & Scrap Log** module tracks unaccounted item losses, scrap, and damaged goods generated during production batch executions. It mirrors the `/factory/wastage-log` web page with full feature parity.

### API Base Paths
Both routes below point to the exact same controller and support identical payloads:
- **Recommended (Factory Namespace):** `/api/v1/factory/wastage-log`
- **Alias (Admin Production Namespace):** `/api/v1/admin/production/wastage-log`

---

## 2. API Endpoints Summary

| Feature | HTTP Method | Endpoint Path | Description |
|---|---|---|---|
| **KPI Summary Stats** | `GET` | `/api/v1/factory/wastage-log/stats` | Returns total wastage qty, loss incidents, impacted batches, and avg loss rate. |
| **Form Options / Pickers** | `GET` | `/api/v1/factory/wastage-log/options` | Returns active tasks, wastage types, and recent jobs for pickers. |
| **Paginated Log List** | `GET` | `/api/v1/factory/wastage-log` | Returns filtered, paginated list of wastage entries. |
| **Single Entry Detail** | `GET` | `/api/v1/factory/wastage-log/{id}` | Returns full details of a specific wastage entry. |
| **Create Entry** | `POST` | `/api/v1/factory/wastage-log` | Creates a new wastage record. |
| **Update Entry** | `PUT` / `PATCH` | `/api/v1/factory/wastage-log/{id}` | Updates an existing wastage record (supports partial updates). |
| **Delete Entry** | `DELETE` | `/api/v1/factory/wastage-log/{id}` | Permanently deletes a wastage record. |

---

## 3. Detailed Endpoint Specs

### 3.1 KPI Summary Stats
```http
GET /api/v1/factory/wastage-log/stats
Authorization: Bearer {token}
Accept: application/json
```

**Response Example (200 OK):**
```json
{
  "success": true,
  "data": {
    "total_wastage_qty": 6.0,
    "total_wastage_qty_formatted": "6 Pcs",
    "loss_incidents_count": 2,
    "impacted_batch_count": 2,
    "avg_loss_rate": 3.2,
    "avg_loss_rate_label": "3.2%"
  }
}
```

---

### 3.2 Form Options & Pickers
```http
GET /api/v1/factory/wastage-log/options
Authorization: Bearer {token}
Accept: application/json
```

**Response Example (200 OK):**
```json
{
  "success": true,
  "data": {
    "tasks": [
      { "id": 1, "name": "Stitching", "code": "TSK-STITCH" },
      { "id": 2, "name": "Ironing & Folding", "code": "TSK-IRN" }
    ],
    "wastage_types": [
      { "value": "scrap", "label": "Scrap (Unusable)" },
      { "value": "damaged", "label": "Damaged (Resold)" }
    ],
    "recent_jobs": [
      {
        "id": 12,
        "job_code": "JOB-2026-0019",
        "production_batch_id": "PB-2026-0019",
        "manufacturing_product_id": 1,
        "manufacturing_product": "KTC Bed Sheet 2-Side",
        "status": "completed"
      }
    ]
  }
}
```

---

### 3.3 List Wastage Logs (Paginated & Filterable)
```http
GET /api/v1/factory/wastage-log?search=Edge&wastage_type=scrap&task_id=1&per_page=15&page=1
Authorization: Bearer {token}
Accept: application/json
```

**Supported Query Parameters:**
- `search` (string, optional): Full-text search across job code, reason, product name/code, pattern name, batch code, task name.
- `wastage_type` (string, optional): `scrap` or `damaged` (also accepts `damage` as alias).
- `task_id` (integer, optional): Filter by production task/stage ID.
- `production_job_id` (integer, optional): Filter by specific production job ID.
- `date_from` (string `YYYY-MM-DD`, optional): Start date range filter.
- `date_to` (string `YYYY-MM-DD`, optional): End date range filter.
- `per_page` (integer, optional): Number of items per page (default: `15`, max: `100`).
- `page` (integer, optional): Page number (default: `1`).

**Response Example (200 OK):**
```json
{
  "success": true,
  "message": "Wastage log entries retrieved successfully.",
  "data": [
    {
      "id": 1,
      "wastage_code": "WST-2026-0001",
      "job_code": "JOB-2026-0019",
      "source_batch_code": "PB-2026-0019",
      "wastage_type": "scrap",
      "wastage_type_label": "Scrap (Unusable)",
      "is_damage": false,
      "quantity_wasted": 4.0,
      "formatted_quantity_wasted": "4 Pcs",
      "reason": "Unaccounted scrap during final batch completion",
      "production_job_id": 12,
      "production_job": {
        "id": 12,
        "job_code": "JOB-2026-0019",
        "status": "completed"
      },
      "manufacturing_product_id": 1,
      "manufacturing_product": {
        "id": 1,
        "name": "KTC Bed Sheet 2-Side",
        "code": "MP-KTC-001"
      },
      "pattern_id": null,
      "pattern_name": "Standard Pattern",
      "task_id": 2,
      "task": {
        "id": 2,
        "name": "Ironing & Folding",
        "code": "TSK-IRN"
      },
      "stage_lost": "Ironing & Folding",
      "logged_date": "2026-09-10",
      "logged_at": "2026-09-10T14:30:00.000000Z"
    }
  ],
  "pagination": {
    "total": 1,
    "count": 1,
    "per_page": 15,
    "current_page": 1,
    "total_pages": 1
  }
}
```

---

### 3.4 Create Wastage Log Entry
```http
POST /api/v1/factory/wastage-log
Authorization: Bearer {token}
Content-Type: application/json
Accept: application/json
```

**Request Fields:**
| Field | Type | Required | Description |
|---|---|---|---|
| `wastage_type` | string | **Yes** | `scrap` or `damaged` (or `damage`) |
| `quantity_wasted` | number | **Yes** | Must be numeric and > 0 |
| `task_id` | integer | No | ID of task/stage where loss occurred |
| `production_job_id` | integer | No | Linked production job ID |
| `manufacturing_product_id` | integer | No | Linked manufacturing product ID |
| `reason` | string | No | Cause or notes |
| `job_code` | string | No | Auto-resolved from `production_job_id` if omitted |

**Request Example:**
```json
{
  "wastage_type": "scrap",
  "quantity_wasted": 4.0,
  "task_id": 2,
  "production_job_id": 12,
  "manufacturing_product_id": 1,
  "reason": "Edge tear defect non-alterable"
}
```

**Response Example (201 Created):**
```json
{
  "success": true,
  "message": "Wastage log entry WST-2026-0003 created successfully.",
  "data": {
    "id": 3,
    "wastage_code": "WST-2026-0003",
    "job_code": "JOB-2026-0019",
    "source_batch_code": "PB-2026-0019",
    "wastage_type": "scrap",
    "quantity_wasted": 4.0,
    "formatted_quantity_wasted": "4 Pcs",
    "reason": "Edge tear defect non-alterable",
    "logged_date": "2026-09-25"
  }
}
```

---

### 3.5 Update Wastage Log Entry
```http
PATCH /api/v1/factory/wastage-log/{id}
Authorization: Bearer {token}
Content-Type: application/json
Accept: application/json
```

**Request Example (Partial update):**
```json
{
  "quantity_wasted": 5.0,
  "reason": "Updated defect note"
}
```

---

### 3.6 Delete Wastage Log Entry
```http
DELETE /api/v1/factory/wastage-log/{id}
Authorization: Bearer {token}
Accept: application/json
```

**Response Example (200 OK):**
```json
{
  "success": true,
  "message": "Wastage log entry WST-2026-0003 deleted successfully."
}
```

---

## 4. Business Logic & Reference Rules

### Wastage Code Display Format
Each wastage log entry receives a formatted display code generated on server:
```
WST-{YEAR}-{PADDED_ID}
```
*Example:* Entry `ID = 3` created in `2026` becomes `WST-2026-0003`.

### Wastage Types
- `scrap`: Unusable material loss (Badge color: Red/Rose).
- `damaged` (alias `damage`): Damaged goods resold at a discount (Badge color: Amber/Yellow).

---

## 5. Mobile Implementation Checklist

- [ ] **List Screen**:
  - Fetch `GET /stats` for 3 KPI Header Cards.
  - Fetch `GET /factory/wastage-log` for paginated list.
  - Add search bar with `?search=` debounce (300ms).
  - Add filter picker for `wastage_type` and `task_id`.
  - Render list items with `wastage_code`, `source_batch_code`, `formatted_quantity_wasted`, and `stage_lost`.
- [ ] **Create Form**:
  - Fetch `GET /options` on modal opening.
  - Dropdown pickers for `wastage_type`, `task_id`, `recent_jobs`.
  - Input field for `quantity_wasted` and `reason`.
  - Submit `POST /factory/wastage-log`.
- [ ] **Edit & Delete**:
  - Pre-fill values from list item.
  - Submit `PATCH /factory/wastage-log/{id}` or `DELETE /factory/wastage-log/{id}` with confirm modal.

---

## 6. Dart / Flutter Sample Code

```dart
import 'dart:convert';
import 'package:http/http.dart' as http;

class WastageService {
  final String baseUrl;
  final String token;

  WastageService({required this.baseUrl, required this.token});

  Map<String, String> get _headers => {
    'Authorization': 'Bearer $token',
    'Content-Type': 'application/json',
    'Accept': 'application/json',
  };

  /// Fetch KPI Stats
  Future<Map<String, dynamic>> fetchStats() async {
    final response = await http.get(
      Uri.parse('$baseUrl/api/v1/factory/wastage-log/stats'),
      headers: _headers,
    );
    if (response.statusCode == 200) {
      return jsonDecode(response.body)['data'];
    }
    throw Exception('Failed to load stats');
  }

  /// Create New Wastage Log
  Future<Map<String, dynamic>> createWastage({
    required String wastageType,
    required double quantity,
    int? taskId,
    int? jobId,
    String? reason,
  }) async {
    final response = await http.post(
      Uri.parse('$baseUrl/api/v1/factory/wastage-log'),
      headers: _headers,
      body: jsonEncode({
        'wastage_type': wastageType,
        'quantity_wasted': quantity,
        if (taskId != null) 'task_id': taskId,
        if (jobId != null) 'production_job_id': jobId,
        if (reason != null) 'reason': reason,
      }),
    );

    if (response.statusCode == 201) {
      return jsonDecode(response.body);
    }
    throw Exception('Failed to create wastage log');
  }
}
```
