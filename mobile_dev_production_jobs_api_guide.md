# Kannodia Textiles Mobile App API Implementation Guide
## Module: Production Jobs, Batches & Floor Execution Management

---

## 1. Overview & Context

The **Production Jobs & Floor Management** module allows factory supervisors and floor operators to track manufacturing batches, assign workers to task stages, log daily output and material consumption, record product alterations for defective/damaged pieces, and convert completed batch outputs into storefront finished goods inventory with printable barcodes.

### Key Workflows Covered:
1. **Production Job Listing & Filters**: View jobs by stage, status (`pending`, `in_progress`, `completed`, `cancelled`), or search term.
2. **Supervisor Workbench**: Real-time dashboard showing active jobs and jobs requiring worker assignment.
3. **Worker Allocation**: Assign laborers and piece rates (`rate_per_piece`) to a job stage.
4. **Recording Output & Material Consumption**: Log completed/damaged/rejected pieces, record raw material batch consumption (e.g. fabric meters), and auto-advance the job to the next stage in sequence.
5. **Same-Batch Product Alterations**: When defective or excess fabric is recorded during quality check or cutting, an alteration is created. **Alteration jobs are automatically created inside the SAME parent batch** (e.g. `PB-2026-0053`). The parent batch remains `In Progress` until all jobs (including alteration jobs) are fully completed.
6. **Batch Details & Storefront Conversion**: View all main and alteration jobs under a batch, inspect costs/ledger, and convert completed WIP items directly to storefront products and print barcodes.

---

## 2. Global Authentication & API Standards

* **Base URL**: `https://konnadia.empoweredtechinnovations.org/api/v1` (or local environment `http://localhost/api/v1`)
* **Headers Required**:
  * `Authorization`: `Bearer <JWT_BEARER_TOKEN>`
  * `Accept`: `application/json`
  * `Content-Type`: `application/json`
* **Permission Required**: `access production` or `super_admin` role.

---

## 3. Endpoints Reference: Production Jobs

### A. List Production Jobs

Retrieve a paginated list of production jobs with KPI summary metrics.

* **HTTP Method**: `GET`
* **Route**: `/admin/production/jobs`
* **Alias Route**: `/factory/jobs`

#### Query Parameters
| Parameter | Type | Default | Description |
| :--- | :--- | :--- | :--- |
| `search` | `string` | `null` | Filter by Job Code, Batch Code, Product Name/Code, or Task Name/Code |
| `status` | `string` | `"all"` | Filter by job status (`"pending"`, `"in_progress"`, `"completed"`, `"cancelled"`, `"all"`) |
| `task_id` | `integer` | `null` | Filter by specific stage/task ID |
| `page` | `integer` | `1` | Page number |
| `per_page` | `integer` | `15` | Items per page (1 to 100) |

#### Response Example (`200 OK`)
```json
{
  "success": true,
  "message": "Production jobs retrieved successfully.",
  "summary": {
    "total_jobs": 42,
    "in_progress_jobs": 14,
    "completed_jobs": 26,
    "unconverted_pieces": 320
  },
  "data": [
    {
      "id": 105,
      "job_code": "JOB-2026-0105",
      "production_batch_id": "PB-2026-0053",
      "production_batch_db_id": 53,
      "task": {
        "id": 2,
        "code": "TSK-STITCH",
        "name": "Stitching Stage"
      },
      "manufacturing_product": {
        "id": 8,
        "product_code": "MP-BED-K",
        "title": "Bedsheet King Size"
      },
      "target_quantity": 100,
      "completed_quantity": 40,
      "rejected_quantity": 2,
      "damaged_quantity": 1,
      "remaining_unconverted_quantity": 40,
      "status": "in_progress",
      "is_alteration_job": false,
      "parent_job_id": null,
      "created_at": "2026-09-24T18:30:00Z"
    }
  ],
  "meta": {
    "current_page": 1,
    "per_page": 15,
    "total": 42,
    "last_page": 3
  }
}
```

---

### B. Production Job Lookup Options & Workbench

#### 1. Form Lookup Options
Retrieve tasks, manufacturing products, laborers, and job status lists for dropdown pickers.

* **HTTP Method**: `GET`
* **Route**: `/admin/production/jobs/options` (Alias: `/factory/jobs/options`)

```json
{
  "success": true,
  "data": {
    "tasks": [
      { "id": 1, "code": "TSK-CUT", "name": "Cutting Stage", "consumes_raw_material": true, "default_rate_per_piece": 12.0 },
      { "id": 2, "code": "TSK-STITCH", "name": "Stitching Stage", "consumes_raw_material": false, "default_rate_per_piece": 15.5 }
    ],
    "manufacturing_products": [
      { "id": 5, "product_code": "MP-BED-K", "title": "Bedsheet King Size" },
      { "id": 8, "product_code": "MP-PIL-S", "title": "Pillowcase Standard" }
    ],
    "laborers": [
      { "id": 12, "labor_code": "LAB-012", "name": "Ramesh Kumar", "phone": "9876543210", "piece_rate": 15.5 }
    ],
    "statuses": [
      { "value": "pending", "label": "Pending" },
      { "value": "in_progress", "label": "In Progress" },
      { "value": "completed", "label": "Completed" },
      { "value": "cancelled", "label": "Cancelled" }
    ]
  }
}
```

#### 2. Supervisor Workbench Summary
* **HTTP Method**: `GET`
* **Route**: `/admin/production/jobs/workbench` (Alias: `/factory/jobs/workbench`)

```json
{
  "success": true,
  "active_jobs": [ ... ],
  "pending_laborer_assignments": [ ... ]
}
```

---

### C. Show Production Job Details

Retrieve full 360-degree job details, assigned laborers, material consumptions logged, output history, alterations, and available fabric inventory batches for allocation.

* **HTTP Method**: `GET`
* **Route**: `/admin/production/jobs/{id}` (Alias: `/factory/jobs/{id}`)

#### Response Example (`200 OK`)
```json
{
  "success": true,
  "message": "Job detail retrieved successfully.",
  "job": {
    "id": 105,
    "job_code": "JOB-2026-0105",
    "production_batch_id": "PB-2026-0053",
    "target_quantity": 100,
    "completed_quantity": 40,
    "status": "in_progress",
    "task": { "id": 2, "name": "Stitching Stage" },
    "manufacturing_product": { "id": 8, "title": "Bedsheet King Size" },
    "labor_allocations": [
      {
        "id": 44,
        "labor": { "id": 12, "name": "Ramesh Kumar", "code": "LAB-012" },
        "rate_per_piece": 15.5,
        "assigned_quantity": 100,
        "quantity_processed": 40,
        "calculated_wage": 620.0
      }
    ],
    "material_consumptions": [
      {
        "id": 18,
        "raw_material_name": "Cotton Fabric 60 inch",
        "inventory_batch_number": "BAT-FAB-0012",
        "quantity_consumed": 250.0,
        "wastage_quantity": 5.0,
        "unit": "Meters",
        "total_cost": 18750.0
      }
    ],
    "outputs": [
      {
        "id": 82,
        "completed_quantity": 40,
        "rejected_quantity": 2,
        "damaged_quantity": 1,
        "recorded_by": { "id": 3, "name": "Supervisor John" },
        "created_at": "2026-09-24T19:00:00Z"
      }
    ],
    "alterations": []
  },
  "available_material_batches": [
    {
      "id": 12,
      "batch_number": "BAT-FAB-0012",
      "raw_material_name": "Cotton Fabric 60 inch",
      "remaining_quantity": 450.0,
      "unit": "Meters",
      "purchase_rate": 75.0
    }
  ]
}
```

---

### D. Assign Laborers to Job

Assign one or more workers and specify piece rates for a job stage.

* **HTTP Method**: `POST`
* **Route**: `/admin/production/jobs/{id}/assign-laborers` (Alias: `/factory/jobs/{id}/assign-laborers`)

#### Payload Specification
```json
{
  "labor_allocations": [
    {
      "labor_id": 12,
      "rate_per_piece": 15.50,
      "assigned_quantity": 100
    }
  ],
  "notes": "Assigned for main batch stitching"
}
```

---

### E. Record Job Output & Raw Material Consumption

Record production quantity completed, damaged, or rejected. Optionally log raw material batch deductions (e.g. fabric meters consumed). Automatically calculates labor piece-rate payouts and advances job workflow.

* **HTTP Method**: `POST`
* **Route**: `/admin/production/jobs/{id}/record-output` (Alias: `/factory/jobs/{id}/record-output`)

#### Payload Specification
```json
{
  "completed_quantity": 40,
  "rejected_quantity": 2,
  "damaged_quantity": 1,
  "notes": "Completed 40 bedsheets, 2 rejected due to seam misalignment",
  "raw_material_consumptions": [
    {
      "inventory_batch_id": 12,
      "quantity_consumed": 250.0,
      "wastage_quantity": 5.0
    }
  ]
}
```

#### Response Example (`200 OK`)
```json
{
  "success": true,
  "message": "Job output recorded and workflow advanced successfully!",
  "workflow": { ... },
  "job": { ... }
}
```

---

### F. Record Product Alteration (Same-Batch Job Creation)

When a defective piece or excess area is converted into a smaller product (e.g., converting a defective King Bedsheet into Pillowcases), record an alteration.

> [!IMPORTANT]
> **Same-Batch Architecture**: Recording an alteration creates a new alteration job **inside the same parent batch** (`production_batch_id`). The parent batch will remain `In Progress` until all jobs—including this newly created alteration job—are completed.

* **HTTP Method**: `POST`
* **Route**: `/admin/production/jobs/{id}/record-alteration` (Alias: `/factory/jobs/{id}/record-alteration`)

#### Payload Specification
```json
{
  "target_manufacturing_product_id": 8,
  "quantity": 2,
  "reason": "Corner fabric defect on bedsheet",
  "notes": "Alter into 2 standard pillowcases"
}
```

#### Response Example (`200 OK`)
```json
{
  "success": true,
  "message": "Product alteration recorded successfully.",
  "alteration": {
    "id": 14,
    "production_job_id": 105,
    "source_manufacturing_product_id": 5,
    "target_manufacturing_product_id": 8,
    "quantity": 2,
    "reason": "Corner fabric defect on bedsheet",
    "notes": "Alter into 2 standard pillowcases"
  },
  "job": { ... }
}
```

---

## 4. Endpoints Reference: Production Batches & Storefront Conversion

### A. List Production Batches

* **HTTP Method**: `GET`
* **Route**: `/admin/production/batches` (Alias: `/factory/batches`)

#### Query Parameters: `search`, `status` (`"In Progress"`, `"Completed"`, `"all"`), `date_from`, `date_to`, `page`, `per_page`.

---

### B. Get Batch Jobs & Conversion Readiness

Retrieve all main jobs and alteration jobs linked to a production batch. Use this endpoint on the **Batch Details Screen** (`/admin/production/batches/PB-2026-0053/jobs`).

* **HTTP Method**: `GET`
* **Route**: `/admin/production/batches/{batchCode}/jobs` (Alias: `/factory/batches/{batchCode}/jobs`)

#### Response Example (`200 OK`)
```json
{
  "success": true,
  "message": "Batch jobs retrieved successfully.",
  "batch": {
    "id": 53,
    "batch_code": "PB-2026-0053",
    "status": "In Progress",
    "planned_quantity": 100,
    "is_fully_completed": false,
    "is_ready_for_conversion": false,
    "created_at": "2026-09-24T18:00:00Z"
  },
  "jobs": [
    {
      "id": 105,
      "job_code": "JOB-2026-0105",
      "manufacturing_product": { "title": "Bedsheet King Size" },
      "task": { "name": "Stitching Stage" },
      "target_quantity": 100,
      "completed_quantity": 100,
      "status": "completed",
      "is_alteration_job": false
    },
    {
      "id": 109,
      "job_code": "JOB-2026-0105-A1",
      "manufacturing_product": { "title": "Pillowcase Standard" },
      "task": { "name": "Stitching Stage" },
      "target_quantity": 2,
      "completed_quantity": 0,
      "status": "in_progress",
      "is_alteration_job": true,
      "parent_job_id": 105
    }
  ]
}
```

---

### C. Storefront Conversion & Barcode Options

Fetch available completed WIP job quantities for a batch and target storefront products for inventory conversion and barcode printing.

* **HTTP Method**: `GET`
* **Route**: `/admin/production/batches/{id}/convert-options` (Alias: `/factory/batches/{id}/convert-options`)

---

### D. Execute Batch Conversion to Storefront Inventory

Convert completed WIP quantities from a batch into storefront inventory and generate unique barcodes.

* **HTTP Method**: `POST`
* **Route**: `/admin/production/batches/{id}/convert` (Alias: `/factory/batches/{id}/convert`)

#### Payload Specification
```json
{
  "target_product_id": 45,
  "target_unit_level": 1,
  "conversion_notes": "Batch PB-2026-0053 final conversion to storefront stock",
  "components": [
    {
      "production_job_id": 105,
      "quantity_used": 100
    },
    {
      "production_job_id": 109,
      "quantity_used": 2
    }
  ],
  "packaging": [
    {
      "raw_material_id": 12,
      "quantity_used": 100
    }
  ]
}
```

#### Response Example (`200 OK`)
```json
{
  "success": true,
  "message": "Finished goods conversion completed successfully!",
  "data": {
    "conversion_batch_id": "FG-CONV-2026-0089",
    "units_added": 100,
    "barcodes": [
      {
        "barcode_number": "BC-FG-0089-001",
        "product_name": "Premium King Bedsheet Set",
        "qr_code_svg": "..."
      }
    ]
  }
}
```

---

## 5. Mobile UI & Workflow Implementation Guide

### Mobile Screen 1: Floor Jobs List & Supervisor Workbench
1. **Tabs**:
   - **Active Jobs**: Display jobs with `status == 'in_progress'`. Show progress bar (`completed_quantity / target_quantity`).
   - **Needs Worker**: Highlight jobs where `labor_allocations` is empty.
   - **Completed / Ready for Conversion**: Show jobs with `status == 'completed'`.
2. **Same-Batch Alteration Badge**:
   - If `is_alteration_job == true`, render an **"Alteration Job (Parent: JOB-XXX)"** badge in orange.

---

### Mobile Screen 2: Job Execution & Output Logging
1. When a worker finishes a run, tap **Record Daily Output**.
2. **Input Fields**:
   - Completed Quantity, Rejected Quantity, Damaged Quantity.
   - Fabric / Inventory Batch Picker (pre-loaded from `available_material_batches`).
   - Quantity Consumed (Meters/Pcs) & Scrap Wastage.
3. If defects occur, tap **Record Alteration**:
   - Select Target Smaller Product (e.g. *Pillowcase*).
   - Enter Quantity & Defect Reason.
   - Submitting will create a new job in the same parent batch and notify the floor operator.

---

### Mobile Screen 3: Batch Detail & Barcode Conversion
1. Open Batch Detail page (`GET /factory/batches/{batch_code}/jobs`).
2. Display all jobs linked to the batch.
3. **Convert & Print Barcodes Button**:
   - If `batch.is_ready_for_conversion == true`, enable the **Convert to Storefront Goods** button.
   - If `batch.is_ready_for_conversion == false`, display a warning: *"All batch jobs (including alteration jobs) must be completed before conversion."*
4. Upon successful conversion, present the **Print Barcode** button to print thermal labels directly via mobile Bluetooth/Wi-Fi printer.
