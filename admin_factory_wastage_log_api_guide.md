# Konnadia Textiles — Factory Wastage & Scrap Log API
## Mobile Integration Guide

**Base URL:** `https://your-domain.com/api/v1`
**Auth:** Bearer Token (JWT) — include `Authorization: Bearer {token}` on all requests  
**Permission Required:** `access production`

---

## Overview

The Wastage & Scrap Log module tracks unaccounted item losses, scrap, and damaged goods generated during production batch executions. It mirrors the `/factory/wastage-log` web page with full feature parity.

### Feature Parity with Web Page

| Web Feature | API Support |
|---|---|
| KPI summary cards (Total Wastage, Loss Incidents, Avg Loss Rate) | `GET /factory/wastage-log/stats` |
| Paginated wastage table | `GET /factory/wastage-log` |
| Full-text search (job code, reason, product, batch, task) | `?search=` query param |
| Wastage type filter (Scrap / Damaged) | `?wastage_type=` query param |
| Task / production stage filter | `?task_id=` query param |
| Date range filter | `?date_from=&date_to=` |
| Wastage type labels ("Scrap (Unusable)" / "Damaged (Resold)") | Included in each record |
| Pattern name fallback chain | Handled server-side in resource |
| Create new wastage log entry | `POST /factory/wastage-log` |
| Edit existing entry | `PUT/PATCH /factory/wastage-log/{id}` |
| Delete entry | `DELETE /factory/wastage-log/{id}` |
| Picker data (tasks, types, recent jobs) | `GET /factory/wastage-log/options` |

---

## Route Aliases

Both route namespaces are available and functionally identical:

| Prefix | Use Case |
|---|---|
| `/api/v1/factory/wastage-log` | Factory-facing alias (recommended for mobile) |
| `/api/v1/admin/production/wastage-log` | Admin/production namespace alias |

---

## Endpoints Reference

### 1. KPI Stats
```
GET /api/v1/factory/wastage-log/stats
```

Returns the four KPI summary cards shown on the web dashboard.

**Response:**
```json
{
  "success": true,
  "data": {
    "total_wastage_qty": 6.0,
    "loss_incidents_count": 2,
    "impacted_batch_count": 2,
    "avg_loss_rate": 4.6,
    "avg_loss_rate_label": "4.6%"
  }
}
```

| Field | Description |
|---|---|
| `total_wastage_qty` | Sum of all `quantity_wasted` where qty > 0 |
| `loss_incidents_count` | Count of wastage records with qty > 0 |
| `impacted_batch_count` | Unique production batches affected |
| `avg_loss_rate` | `(total_wasted / total_target_qty) * 100`, rounded to 1 decimal |

---

### 2. Options / Picker Data
```
GET /api/v1/factory/wastage-log/options
```

Provides all picker data for forms (task select, type select, job search).

**Response:**
```json
{
  "success": true,
  "data": {
    "tasks": [
      { "id": 1, "name": "Cutting", "code": "TSK-CUT" },
      { "id": 2, "name": "Ironing & Folding", "code": "TSK-IRN" }
    ],
    "wastage_types": [
      { "value": "scrap",   "label": "Scrap (Unusable)" },
      { "value": "damaged", "label": "Damaged (Resold)" }
    ],
    "recent_jobs": [
      {
        "id": 12,
        "job_code": "JOB-2026-0019",
        "production_batch_id": "PB-2026-0019",
        "manufacturing_product_id": 3,
        "manufacturing_product": "KTC Bed Sheet 2-Side",
        "status": "completed"
      }
    ]
  }
}
```

---

### 3. List Wastage Entries (Paginated)
```
GET /api/v1/factory/wastage-log
```

**Query Parameters:**

| Parameter | Type | Description |
|---|---|---|
| `search` | string | Full-text search: job code, reason, product name/code, batch code, task name, pattern name |
| `wastage_type` | string | `scrap` or `damaged` (normalised — `damage` and `damaged` both match damaged records) |
| `task_id` | integer | Filter by production stage task ID |
| `date_from` | date (Y-m-d) | Filter log entries from this date |
| `date_to` | date (Y-m-d) | Filter log entries to this date |
| `production_job_id` | integer | Filter by specific production job |
| `per_page` | integer | Items per page (1–100, default 15) |
| `page` | integer | Page number |

**Response:**
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
      "manufacturing_product_id": 3,
      "manufacturing_product": {
        "id": 3,
        "name": "KTC Bed Sheet 2-Side",
        "code": "MP-KTC-001"
      },
      "pattern_id": null,
      "pattern_name": "Standard Pattern",
      "task_id": 1,
      "task": {
        "id": 1,
        "name": "Ironing & Folding",
        "code": "TSK-IRN"
      },
      "stage_lost": "Ironing & Folding",
      "inventory_bale_roll_id": null,
      "logged_date": "2026-09-01",
      "logged_at": "2026-09-01T10:00:00+00:00",
      "updated_at": "2026-09-01T10:00:00+00:00"
    }
  ],
  "meta": {
    "current_page": 1,
    "per_page": 15,
    "total": 1,
    "last_page": 1
  },
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

### 4. Get Single Wastage Entry
```
GET /api/v1/factory/wastage-log/{id}
```

Returns full detail of a single wastage log entry with all related data eagerly loaded.

**Response:** Same shape as individual items in the list above.

**Error (404):**
```json
{ "message": "No query results for model [App\\Models\\JobWastage] 999" }
```

---

### 5. Create Wastage Log Entry
```
POST /api/v1/factory/wastage-log
Content-Type: application/json
```

**Request Body:**

| Field | Type | Required | Description |
|---|---|---|---|
| `wastage_type` | string | ✅ Yes | `scrap`, `damage`, or `damaged` |
| `quantity_wasted` | numeric | ✅ Yes | Min: 0.01 |
| `task_id` | integer | Recommended | Production stage where wastage occurred (task.id) |
| `job_code` | string | Optional | Auto-resolved from `production_job_id` if omitted |
| `production_job_id` | integer | Optional | Links to a production job |
| `manufacturing_product_id` | integer | Optional | Links to a manufacturing product |
| `pattern_id` | integer | Optional | Links to a product pattern |
| `inventory_bale_roll_id` | integer | Optional | Links to an inventory bale roll |
| `reason` | string | Optional | Notes / cause description (max 1000 chars) |

> **Note:** If `job_code` is omitted and `production_job_id` is provided, the `job_code` is auto-resolved from the linked job. If neither is provided, a placeholder code (`WST-DIRECT-{timestamp}`) is generated.

**Request Example:**
```json
{
  "wastage_type": "scrap",
  "quantity_wasted": 4.0,
  "task_id": 1,
  "production_job_id": 12,
  "manufacturing_product_id": 3,
  "reason": "Unaccounted scrap during final batch completion"
}
```

**Response (201):**
```json
{
  "success": true,
  "message": "Wastage log entry WST-2026-0003 created successfully.",
  "data": { /* AdminWastageLogResource */ }
}
```

**Validation Errors (422):**
```json
{
  "message": "Wastage type must be one of: scrap, damage, or damaged.",
  "errors": {
    "wastage_type": ["Wastage type must be one of: scrap, damage, or damaged."],
    "quantity_wasted": ["Quantity wasted is required."]
  }
}
```

---

### 6. Update Wastage Log Entry
```
PUT  /api/v1/factory/wastage-log/{id}
PATCH /api/v1/factory/wastage-log/{id}
Content-Type: application/json
```

All fields are optional (partial updates supported). Only fields included in the request will be updated.

**Request Example (PATCH):**
```json
{
  "reason": "Updated: Edge defect confirmed non-alterable",
  "quantity_wasted": 5.0
}
```

**Response (200):**
```json
{
  "success": true,
  "message": "Wastage log entry WST-2026-0001 updated successfully.",
  "data": { /* AdminWastageLogResource */ }
}
```

---

### 7. Delete Wastage Log Entry
```
DELETE /api/v1/factory/wastage-log/{id}
```

Permanently deletes the wastage log entry.

**Response (200):**
```json
{
  "success": true,
  "message": "Wastage log entry WST-2026-0001 deleted successfully."
}
```

**Error (404):** Returns standard 404 if entry not found.

---

## Wastage Code Format

Each wastage entry is assigned a display code (server-side, not stored):

```
WST-{YEAR}-{PADDED_ID}
```

**Examples:**
- `WST-2026-0001` — Entry with ID=1 created in 2026
- `WST-2026-0012` — Entry with ID=12 created in 2026

This code appears in:
- `data.wastage_code` in all API responses
- Success messages in create/update/delete responses

---

## Wastage Type Reference

| API value | Web label | `is_damage` | Meaning |
|---|---|---|---|
| `scrap` | Scrap (Unusable) | `false` | Material cannot be recovered or resold |
| `damaged` | Damaged (Resold) | `true` | Material is damaged but can be sold at a discount |
| `damage` | Damaged (Resold) | `true` | Alias for `damaged` — normalised in filters |

> **Important:** When filtering, both `damage` and `damaged` return the same records. When creating, use `scrap` or `damaged` (or `damage`) as the value.

---

## Resource Fields Reference

Every wastage log entry returned by the API includes these fields:

| Field | Type | Description |
|---|---|---|
| `id` | integer | Database record ID |
| `wastage_code` | string | Formatted code `WST-YYYY-NNNN` |
| `job_code` | string | Job code string (from production job) |
| `source_batch_code` | string | Source batch code (resolved from job or job_code field) |
| `wastage_type` | string | Normalised type: `scrap` or `damaged` |
| `wastage_type_label` | string | Human label: "Scrap (Unusable)" or "Damaged (Resold)" |
| `is_damage` | boolean | `true` if damaged/resold, `false` if scrap |
| `quantity_wasted` | float | Quantity lost (decimal) |
| `formatted_quantity_wasted` | string | e.g. `"4 Pcs"` |
| `reason` | string\|null | Notes / cause description |
| `production_job_id` | integer\|null | FK to production job |
| `production_job` | object\|null | `{ id, job_code, status }` |
| `manufacturing_product_id` | integer\|null | FK to manufacturing product |
| `manufacturing_product` | object\|null | `{ id, name, code }` |
| `pattern_id` | integer\|null | FK to pattern |
| `pattern_name` | string | Pattern name (fallback chain: direct → job pattern → product's first pattern → "Standard Pattern") |
| `pattern` | object\|null | `{ id, name }` if pattern linked |
| `task_id` | integer\|null | FK to task |
| `task` | object\|null | `{ id, name, code }` |
| `stage_lost` | string | Task name or "Production Stage" fallback |
| `inventory_bale_roll_id` | integer\|null | FK to inventory bale roll |
| `logged_date` | string | `YYYY-MM-DD` format |
| `logged_at` | string | ISO 8601 datetime |
| `updated_at` | string | ISO 8601 datetime |

---

## Implementation Checklist for Mobile Developers

### Screen: Wastage & Scrap Log (List View)

- [ ] On screen load, call `GET /stats` and display 3 KPI cards:
  - **Total Wastage Logged** → `data.total_wastage_qty` + "Pcs" suffix
  - **Loss Incidents Recorded** → `data.loss_incidents_count`
  - **Avg Loss Rate** → `data.avg_loss_rate_label`
- [ ] Call `GET /factory/wastage-log` to populate the list
- [ ] Implement search bar that calls the endpoint with `?search=` (debounced, 300ms)
- [ ] Implement "Waste Type" filter picker with values from `options.wastage_types`
- [ ] Implement "Stage" filter picker with tasks from `options.tasks`
- [ ] Optionally add date range filter (`?date_from=` / `?date_to=`)
- [ ] Display each row:
  - Wastage Code (bold, monospace): `wastage_code`
  - Source Batch (primary color): `source_batch_code`
  - Manufacturing Product: `manufacturing_product.name`
  - Pattern Type: `pattern_name`
  - Waste Type badge: use `wastage_type_label`, style using `is_damage`
  - Quantity badge: `formatted_quantity_wasted`
  - Stage Lost: `stage_lost`
  - Logged Date: `logged_date`
  - Reason/Cause: `reason` (truncated)
- [ ] Handle empty state when no records returned

### Screen: Create Wastage Log Entry

- [ ] Call `GET /factory/wastage-log/options` to populate pickers
- [ ] Show: Wastage Type picker, Quantity field, Task picker, Job picker (optional), Product (optional), Reason (optional)
- [ ] Submit via `POST /factory/wastage-log`
- [ ] Show created wastage code from `message` in success toast

### Screen: Edit Wastage Log Entry

- [ ] Pre-populate form with data from `GET /factory/wastage-log/{id}`
- [ ] Submit via `PATCH /factory/wastage-log/{id}`

### Delete Wastage Log Entry

- [ ] Show confirmation dialog
- [ ] Call `DELETE /factory/wastage-log/{id}`
- [ ] Refresh list and KPI stats after deletion

---

## Error Handling

| HTTP Code | Meaning | Action |
|---|---|---|
| 401 | Unauthenticated | Redirect to login |
| 403 | No `access production` permission | Show "Access Denied" screen |
| 404 | Entry not found | Show error toast, go back |
| 422 | Validation error | Show field-level errors from `errors` object |
| 500 | Server error | Show generic error message |

---

## Example: Filter by Type and Task

```http
GET /api/v1/factory/wastage-log?wastage_type=scrap&task_id=1&per_page=20
Authorization: Bearer {token}
```

---

## Example: Create Entry (Dart/Flutter)

```dart
final response = await http.post(
  Uri.parse('$baseUrl/factory/wastage-log'),
  headers: {
    'Authorization': 'Bearer $token',
    'Content-Type': 'application/json',
    'Accept': 'application/json',
  },
  body: jsonEncode({
    'wastage_type': 'scrap',
    'quantity_wasted': 4.0,
    'task_id': 1,
    'production_job_id': 12,
    'reason': 'Unaccounted scrap during final batch',
  }),
);

if (response.statusCode == 201) {
  final data = jsonDecode(response.body);
  final wastageCode = data['data']['wastage_code'];
  showSnackbar('Created: $wastageCode');
}
```

---

## Quick Reference Card

```
Auth: Bearer Token  |  Permission: access production

Stats:    GET  /factory/wastage-log/stats
Options:  GET  /factory/wastage-log/options
List:     GET  /factory/wastage-log[?search=&wastage_type=&task_id=&date_from=&date_to=]
Show:     GET  /factory/wastage-log/{id}
Create:   POST /factory/wastage-log
Update:   PUT|PATCH /factory/wastage-log/{id}
Delete:   DELETE /factory/wastage-log/{id}

Wastage Types: scrap | damaged (or damage as alias)
Code Format:   WST-{YEAR}-{PADDED_ID}
```
