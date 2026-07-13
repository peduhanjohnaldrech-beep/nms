# NMS Database Documentation

## Overview

The database is **MySQL** (MariaDB 10.4+) running via XAMPP. All queries use PDO prepared statements. Connection is configured in `config/database.php`.

---

## Table Overview

| Table | Purpose |
|-------|---------|
| `users` | System accounts with role-based access |
| `beneficiaries` | Children enrolled in nutrition programs |
| `assessments` | OPT weight/height measurements per child per period |
| `programs` | Program definitions — built-in (OPT, DSP, MNS) and admin-created custom programs |
| `program_enrollments` | Enrollment records for OPT, DSP, MNS, and custom programs |
| `vitamin_a_records` | Vitamin A distribution per child per round |
| `mnp_records` | Micronutrient Powder (MNP) distribution records |
| `lns_sq_records` | Lipid-based Nutrient Supplement – Small Quantity (LNS-SQ) distribution records |
| `dispensing_records` | All supplement/medicine dispensing events (auto-populated from MNS recordings) |
| `activity_logs` | Timestamped audit trail of all data actions by users |
| `import_logs` | Audit trail for Excel import operations |
| `stored_files` | General files uploaded via the "Other Files" storage tab |
| `who_growth_standards` | WHO LMS reference values for Z-score computation |

---

## Entity Relationships

```
users ──────────────────────────┐
  │                             │
  │ created_by / enrolled_by    │ created_by
  ▼                             ▼
beneficiaries ──────────── assessments
  │                             │ (auto-flags for DSP if UW/SUW/SW/MW)
  │                             │ (auto-created on DSP completion with post-weight)
  │                             ▼
  ├──── program_enrollments ────┘
  │         (program: OPT / DSP / custom)
  │         DSP: intervention_type, pre/post weight
  │         OPT: period (January/July), cycle_year
  │
  ├──── vitamin_a_records  (February / August rounds)
  ├──── mnp_records         (Micronutrient Powder — 6–23 months)
  ├──── lns_sq_records      (LNS-SQ — 6–23 months)
  └──── dispensing_records  (auto-created when any MNS record is saved)

who_growth_standards  ← referenced by ZScoreHelper (no FK)
import_logs           ← standalone audit log for Excel imports
stored_files          ← general file uploads (Other Files storage tab)
activity_logs         ← audit trail for all user data actions
```

---

## Key Tables

### `beneficiaries`

The central table. Key columns:
- Full address: `region`, `province`, `city_municipality`, `barangay`, `purok_zone`, `incode`
- Guardian info: `guardian_name`, `guardian_relationship`, `mother_name`, `father_name`, `contact_number`
- Socioeconomic: `is_4ps_member`, `is_pwd_household`, `is_indigenous_people`, `nhts_pr_status`, `income_classification`
- `source` — how the record was created: `'Walk-in'`, `'Excel'`, `'Google'`, `'Demo'`
- `deleted_at` — soft-delete (NULL = active, timestamp = deleted)

**Aged-out detection:** Children > 59 months are determined at query time using `DATE_SUB(CURDATE(), INTERVAL 59 MONTH)`. They are not removed — just flagged visually and filterable in the list.

---

### `assessments`

One record per weighing event.
- `period` — `'January'` or `'July'` (always derived server-side from assessment date; Jan–Jun → January, Jul–Dec → July)
- `assessment_year` — integer year
- `nutritional_status` — `'SUW'`, `'UW'`, `'Normal'`, `'OW'`, `'OB'` (from WFA Z-score)
- `hfa_status` — `'SSt'`, `'St'`, `'Normal'`
- `wflh_status` — `'SW'`, `'MW'`, `'Normal'`, `'OW'`, `'OB'`
- `remarks` — includes `'Post-DSP assessment (auto-generated from program completion)'` for auto-created records
- `validation_status` — `'pending'` or `'validated'` (mobile submissions from BHW/encoder start as pending)
- `validated_by` / `validated_at` — set when a nutritionist/admin validates the record

**Unique constraint:** `UNIQUE KEY idx_one_per_period (beneficiary_id, period, assessment_year)` — enforces one assessment per child per OPT period at the database level.

**Auto-creation:** When a DSP enrollment is completed with a post-weight, a new assessment is automatically inserted using the post-weight and last known height.

---

### `program_enrollments`

Unified enrollment table for OPT, DSP, and custom programs:
- `program` — `'OPT'`, `'DSP'`, or custom program code
- `status` — `'Active'`, `'Completed'`, `'Dropped'`
- `cycle_year` — the year the enrollment belongs to

**DSP-specific columns:**
- `intervention_type` — `'RUSF'`, `'RUTF'`, `'Health Education'`, `'Supplementary Feeding'`
- `pre_weight_kg` / `post_weight_kg` — for outcome tracking

**DSP auto-enrollment:** After any assessment, if the child's `wflh_status IN ('SW','MW')` OR `nutritional_status IN ('SUW','UW')` and they are not already Active in DSP, the system auto-enrolls them.

---

### `vitamin_a_records`

- `round` — `'February'` or `'August'`
- `year` — integer year
- `age_months` — age at time of distribution
- `dose` — auto-assigned: 6–11 months → `'100,000 IU Vitamin A (Blue)'`; 12–59 months → `'200,000 IU Vitamin A (Red)'`
- **A `dispensing_records` row is auto-created when saved**

---

### `mnp_records` / `lns_sq_records`

Supplemental micronutrient distribution records:
- `age_group` — `'6-11 months'` or `'12-23 months'`
- `year` — integer year
- `completed_routine` — boolean (1 = full course completed)
- **A `dispensing_records` row is auto-created when saved**

**Eligibility for "Not Yet Received" list:** Children aged 6–23 months who have no record for the current year.

Duplicate prevention: `hasDuplicate()` checks for same `beneficiary_id` + `year` + `age_group` before inserting.

---

### `dispensing_records`

Tracks all supplement and medicine dispensing events:
- `beneficiary_id` — FK to `beneficiaries.id`
- `enrollment_id` — optional FK to `program_enrollments.id` (NULL for MNS records)
- `program` — e.g., `'MNS'`, `'DSP'`, `'General'`
- `supplement_type` — e.g., `'200,000 IU Vitamin A (Red)'`, `'MNP (Micronutrient Powder)'`, `'LNS-SQ (Lipid-based Nutrient Supplement)'`
- `quantity`, `unit`, `date_dispensed`, `dispensed_by`, `notes`

**Auto-population:** Any MNS recording (Vitamin A, MNP, LNS-SQ) inserts a row here automatically. Manual entries can also be created via the Dispensing Tracker.

---

### `programs`

Defines all programs — built-in and custom:
- `code` — unique uppercase identifier (`'OPT'`, `'DSP'`, `'MNS'`, or custom like `'IRON'`)
- `name`, `description`, `icon` (Bootstrap Icon class), `color` (Bootstrap color name)
- `type` — `'generic'` for custom programs
- `is_active` — 1 = visible in nav/lists
- `sort_order` — controls display order

Built-in programs (OPT, DSP, MNS) have dedicated controllers. Custom programs use the generic enrollment page at `/programs/{code}`.

---

### `activity_logs`

Audit trail of all user data actions:
- `user_id` — FK to `users.id`
- `action` — short name (e.g., `beneficiary_create`, `dsp_discharge`, `dispensing_create`)
- `description` — human-readable detail
- `created_at` — timestamp

Actions logged: `login`, `login_failed`, `logout`, `beneficiary_create`, `beneficiary_update`, `beneficiary_delete`, `beneficiary_restore`, `assessment_create`, `assessment_delete`, `batch_assessment`, `dsp_enroll`, `dsp_discharge`, `dispensing_create`, `import_complete`, `program_create`, `program_update`, `backup`.

---

### `import_logs`

Audit trail for every Excel import:
- `filename` — original filename
- `saved_filename` — filename on disk
- `folder` — subfolder under `storage/imports/`
- `success_count` / `error_count` — row-level results
- `error_details` — JSON array of per-row error messages

---

### `stored_files`

General files uploaded via the Other Files tab:
- `original_filename`, `saved_filename`, `folder`, `file_size`, `mime_type`, `uploaded_by`, `uploaded_at`

---

### `who_growth_standards`

WHO LMS parameters for ages 0–60 months, Male and Female, for WFA and HFA.

**Lookup example:**
```sql
SELECT l_value, m_value, s_value
FROM who_growth_standards
WHERE sex = 'Male' AND age_months = 12 AND measurement_type = 'WFA';
```

**Z-score formula:**
```
Z = ((X/M)^L - 1) / (L × S)
Special case (L=0): Z = log(X/M) / S
```

Used by `app/helpers/ZScoreHelper.php`. No FK — referenced by code only.

---

## Indexes

| Table | Index | Purpose |
|-------|-------|---------|
| `beneficiaries` | `idx_bene_barangay` | BHW/BNS filtering by barangay |
| `beneficiaries` | `idx_bene_name` | Name search |
| `beneficiaries` | `idx_bene_dob` | Age-based queries and aged-out detection |
| `beneficiaries` | `idx_bene_deleted_at` | Soft-delete filter |
| `beneficiaries` | `idx_bene_source` | Walk-in vs imported filter |
| `assessments` | `idx_assess_year` | Period/year filtering |
| `assessments` | `idx_assess_status` | Status distribution queries |
| `assessments` | `idx_one_per_period` | Unique constraint: one assessment per child per period/year |
| `program_enrollments` | `idx_enroll_program` | Active program queries |
| `vitamin_a_records` | `idx_vita_round` | Round/year filtering |
| `activity_logs` | `idx_activity_user` | Filter log by user |
| `who_growth_standards` | `uk_who_lms` | Unique constraint + lookup |

---

## Backup

Backups are full SQL dumps created by `mysqldump`. They are stored in `database/backups/` and named `nms_backup_YYYY-MM-DD_HHiiss.sql`. Up to 7 daily backups are kept; older ones are pruned automatically.

**Automatic backups:** Triggered once per 24 hours whenever the mobile app syncs data (`SyncController::push()`).

**Manual backup:** Admin → Database Backup → "Create Backup Now".

**Download live dump:** Admin → Database Backup → "Download Live DB" (streams a fresh `mysqldump` immediately).

**Restore:**
```bash
mysql -u root nms < nms_backup_YYYY-MM-DD_HHiiss.sql
```

**Windows Task Scheduler (optional):** Run `scripts/backup.bat` daily at 2 AM for guaranteed backups independent of app usage.

---

## Connection Configuration

`config/database.php`:
```php
return [
    'host'   => 'localhost',
    'dbname' => 'nms',
    'user'   => 'root',
    'pass'   => '',
];
```

The `Core\Database` singleton connects via `PDO` using `mysql:host=...;dbname=...;charset=utf8mb4`.
