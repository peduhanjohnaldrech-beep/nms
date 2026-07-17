# Nutrition Monitoring System (NMS)
## System Documentation

---

## Table of Contents

1. [System Overview](#1-system-overview)
2. [Objectives](#2-objectives)
3. [System Architecture](#3-system-architecture)
4. [Technology Stack](#4-technology-stack)
5. [User Roles and Permissions](#5-user-roles-and-permissions)
6. [System Features and Modules](#6-system-features-and-modules)
   - 6.1 [Web System](#61-web-system)
   - 6.2 [Mobile Application](#62-mobile-application)
7. [Database Design](#7-database-design)
8. [REST API Documentation](#8-rest-api-documentation)
9. [Data Synchronization](#9-data-synchronization)
10. [Security Implementation](#10-security-implementation)
11. [Deployment and Infrastructure](#11-deployment-and-infrastructure)
12. [Mobile Application Build and Installation](#12-mobile-application-build-and-installation)
13. [System Administration](#13-system-administration)

---

## 1. System Overview

The **Nutrition Monitoring System (NMS)** is an integrated web and mobile information system developed for the City Health Office Nutrition Department. It is designed to monitor, record, and manage nutrition programs for children aged 0 to 59 months (under five years old).

The system consists of two components that operate on a shared central database:

1. **Web System** — A browser-based application used by administrative staff, nutritionists, and supervisors for program management, data validation, reporting, and export.
2. **Mobile Application** — An Android application used by field health workers (Barangay Health Workers, Barangay Nutrition Scholars, Midwives, and Encoders) to record data in the field and synchronize it with the central server.

Both components share a single backend REST API and MySQL database hosted on a cloud server, ensuring that all data entered through either platform is immediately reflected across the system.

---

## 2. Objectives

The NMS was developed to address the following needs:

- Centralize and digitize child nutrition records previously maintained in paper-based logbooks
- Automate nutritional status classification using WHO Z-score standards (WFA, HFA, WFL/H)
- Track beneficiary participation in supplementation and feeding programs (OPT, DSP, MNS)
- Enable field health workers to record data using a mobile device without requiring a desktop computer
- Provide real-time reports and analytics to support evidence-based decision-making
- Generate the official eOPT Plus Community Level Tool workbook for submission to the municipal level
- Maintain a complete audit trail of all data entries and user actions

---

## 3. System Architecture

The NMS follows a **three-tier client-server architecture**:

```
+---------------------------+       +---------------------------+
|     WEB BROWSER           |       |   ANDROID MOBILE APP      |
|  (Admin, Nutritionist,    |       |  (BHW, BNS, Encoder,      |
|   Encoder — Desktop)      |       |   Midwife — Field Use)    |
+-------------+-------------+       +-------------+-------------+
              |  HTTP/HTTPS                        |  HTTP/HTTPS
              |  (HTML Response)                   |  (JSON REST API)
              v                                    v
+--------------------------------------------------------------+
|              CLOUD SERVER (DigitalOcean Droplet)             |
|                     IP: 152.42.197.110                       |
|                                                              |
|   +------------------+      +---------------------------+    |
|   |   Nginx Web       |      |   PHP 8.3-FPM              |   |
|   |   Server (Proxy)  +----->+   Custom MVC Application   |   |
|   +------------------+      |   /var/www/nms/public/      |   |
|                              +-------------+---------------+   |
|                                            |                   |
|                              +-------------v---------------+   |
|                              |     MySQL 8 Database        |   |
|                              |     Database: nms           |   |
|                              +-----------------------------+   |
+--------------------------------------------------------------+
```

### Communication Flow

- **Web browser** requests are handled by Nginx, which forwards PHP requests to PHP-FPM. The PHP application renders full HTML pages using the MVC pattern and returns them to the browser.
- **Mobile app** requests are sent as JSON to the REST API endpoints (all prefixed `/api/`). The same PHP application handles these requests through dedicated API controllers and returns JSON responses.
- All authentication for API requests uses **Bearer token** authentication via the `api_tokens` table.
- Both web and mobile access the same MySQL database, ensuring data consistency.

---

## 4. Technology Stack

### Web System (Backend)

| Component | Technology |
|-----------|-----------|
| Language | PHP 8.3 |
| Architecture | Custom MVC (no framework) |
| Database | MySQL 8.0 |
| Database Access | PDO with prepared statements |
| Web Server | Nginx + PHP 8.3-FPM |
| PDF Export | Dompdf 2.0 |
| Excel Import/Export | PhpSpreadsheet 1.29 |
| eOPT Export | Direct ZIP + XML manipulation |
| Frontend | Bootstrap 5.3, Bootstrap Icons, Chart.js |
| Session Management | PHP Sessions with CSRF token validation |

### Mobile Application (Frontend)

| Component | Technology |
|-----------|-----------|
| Framework | Flutter 3.32.2 |
| Language | Dart 3.8.1 |
| State Management | Provider (ChangeNotifier) |
| HTTP Client | http package |
| Secure Storage | flutter_secure_storage |
| Local Database | sqflite (SQLite for offline use) |
| Preferences | shared_preferences |
| Target Platform | Android (ARM64) |
| Minimum Android Version | Android 5.0 (API 21) |

### Infrastructure

| Component | Details |
|-----------|---------|
| Cloud Provider | DigitalOcean |
| Server Location | Singapore (SGP1) |
| Server Tier | Premium Intel ($8/month) |
| Operating System | Ubuntu 24.04 LTS |
| Web Server | Nginx + Let's Encrypt SSL |
| PHP Runtime | PHP 8.3-FPM |
| Database Server | MySQL 8 |
| Production URL | https://kabnms.duckdns.org |
| API Base URL | https://kabnms.duckdns.org/api |

---

## 5. User Roles and Permissions

The system implements role-based access control (RBAC). Six roles are defined, each with a distinct set of permissions. Field roles (BHW, BNS, Midwife) are additionally scoped to their assigned barangay — they cannot view or modify records from other barangays.

### Role Definitions

| Role | Description |
|------|-------------|
| **admin** | Full system access: user management, all modules, reports, program management, database backup, and demo seeder |
| **nutritionist** | Full data access except user management; validates assessment submissions from field workers; can import data and discharge DSP enrollments |
| **encoder** | Data entry access; can add and edit beneficiaries and record assessments; cannot delete, import, or discharge |
| **bns** | Barangay Nutrition Scholar; field data entry restricted to assigned barangay; same access level as BHW |
| **bhw** | Barangay Health Worker; field data entry restricted to assigned barangay |
| **midwife** | Can record and validate assessments; restricted to assigned barangay |

### Permission Matrix

| Feature | Admin | Nutritionist | Encoder | BNS | BHW | Midwife |
|---------|:-----:|:------------:|:-------:|:---:|:---:|:-------:|
| Dashboard | Yes | Yes | Yes | Yes | Yes | Yes |
| View Beneficiaries (all barangays) | Yes | Yes | Yes | — | — | — |
| View Beneficiaries (own barangay) | Yes | Yes | Yes | Yes | Yes | Yes |
| Add Beneficiary | Yes | Yes | Yes | Yes | Yes | Yes |
| Edit Beneficiary | Yes | Yes | Yes | — | — | — |
| Delete Beneficiary (soft delete) | Yes | Yes | — | — | — | — |
| Restore Deleted Beneficiary | Yes | Yes | — | — | — | — |
| Record Assessment | Yes | Yes | Yes | Yes | Yes | Yes |
| Delete Assessment | Yes | Yes | — | — | — | — |
| Batch Assessment Entry | Yes | Yes | Yes | Yes | Yes | Yes |
| OPT Module | Yes | Yes | Yes | Yes | Yes | Yes |
| OPT Manual Enroll | Yes | Yes | Yes | — | — | — |
| DSP Module | Yes | Yes | Yes | Yes | Yes | Yes |
| DSP Enroll | Yes | Yes | Yes | — | — | — |
| DSP Discharge / Drop | Yes | Yes | — | — | — | — |
| MNS — Vitamin A | Yes | Yes | Yes | Yes | Yes | Yes |
| MNS — MNP | Yes | Yes | Yes | Yes | Yes | Yes |
| MNS — LNS-SQ | Yes | Yes | Yes | Yes | Yes | Yes |
| Dispensing Tracker | Yes | Yes | Yes | Yes | Yes | Yes |
| Import from Excel | Yes | Yes | — | — | — | — |
| Reports (all types) | Yes | Yes | Yes | Yes* | Yes* | Yes* |
| Export CSV / Excel / PDF | Yes | Yes | Yes | Yes* | Yes* | Yes* |
| Validation Queue | Yes | Yes | — | — | — | Yes |
| My Submissions | — | — | Yes | Yes | Yes | Yes |
| Activity Log | Yes | Yes | — | — | — | — |
| User Management | Yes | — | — | — | — | — |
| Program Manager | Yes | — | — | — | — | — |
| Database Backup | Yes | — | — | — | — | — |

*Scoped to assigned barangay only.

---

## 6. System Features and Modules

### 6.1 Web System

#### Dashboard

The dashboard provides a real-time summary of the nutrition program status for the current year. It displays the following statistical indicators:

- **Total Beneficiaries** — count of all active (not deleted) child beneficiaries in the system
- **OPT Assessed** — number of children weighed during the current year
- **Active DSP** — number of children currently enrolled in the Dietary Supplementation Program
- **MNS Coverage** — number of children who received Vitamin A supplementation this year
- **For Follow-up** — children whose nutritional status has worsened since their last assessment
- **Not Yet Assessed** — children not yet weighed for the current OPT period (January or July)

Four charts are presented: nutritional status distribution by barangay (stacked bar), program enrollment breakdown (donut), OPT trend over the past two years (line), and malnutrition rate by barangay (bar).

#### Beneficiary Management

The beneficiary module manages the central registry of child beneficiaries. Each record includes:

- Personal information: full name, date of birth, sex, and philhealth status
- Complete address: region, province, city/municipality, barangay, purok/zone, and household number
- Guardian information: guardian name and relationship, mother's name, father's name, and contact number
- Socioeconomic information: 4Ps membership, NHTS-PR status, income classification, and PWD household flag
- Source of record: Walk-in, Excel Import, Google Drive Import, or Demo

Key features:
- **Duplicate detection**: an AJAX check runs as the user types, displaying a warning if a matching record (same name and date of birth) already exists
- **Age-based filtering**: beneficiaries are categorized as Active (0–59 months) or Aged Out (>59 months) based on their date of birth and the current date
- **Last Assessed indicator**: a color-coded column shows the number of days since the last assessment — green (under 90 days), yellow (under 180 days), red (over 180 days)
- **Soft delete**: deleted records are moved to a Trash page and can be restored by authorized users
- **Beneficiary Profile**: a comprehensive profile page showing data completeness, growth chart (with WHO reference lines), all assessment records, program enrollments, supplementation history, and dispensing history
- **Print Card**: a compact printable summary card that hides all web UI elements and shows only the beneficiary's key information

#### Nutritional Assessment (OPT)

Assessments record weight, height, and MUAC (mid-upper arm circumference) measurements for each child per OPT period. The system automatically:

1. Computes the child's age in months from date of birth and assessment date
2. Determines the OPT period: January (for assessments recorded January–June) or July (for July–December)
3. Calculates Z-scores using WHO LMS reference values:
   - **WFA (Weight-for-Age Z-score)** — classifies as Severely Underweight (SUW), Underweight (UW), Normal, Overweight (OW), or Obese (OB)
   - **HFA (Height-for-Age Z-score)** — classifies as Severely Stunted (SSt), Stunted (St), or Normal
   - **WFL/H (Weight-for-Length/Height Z-score)** — classifies as Severely Wasted (SW), Moderately Wasted (MW), Normal, Overweight (OW), or Obese (OB)
4. Assigns nutritional status based on WFA Z-score
5. Auto-recommends DSP enrollment if the child is classified as UW, SUW, SW, or MW

A unique database constraint enforces one assessment per child per OPT period per year.

**Z-Score Formula (WHO LMS Method):**

```
Z = ((X / M)^L - 1) / (L × S)
Special case (L = 0): Z = log(X / M) / S
```

Where X is the observed measurement, and L, M, S are the WHO reference values for the child's sex and age in months.

**Batch Assessment**: allows authorized users to enter assessments for multiple children at once, filtered by barangay and period.

#### Programs

**OPT (Operation Timbang)**
The official government child weighing program. Conducted twice a year (January and July periods). The system automatically enrolls children into OPT when an assessment is recorded. Administrators and nutritionists can also manually enroll or remove children.

**DSP (Dietary Supplementation Program)**
A supplementary feeding and intervention program for malnourished children. Eligible children are those classified as UW, SUW, SW, or MW in their most recent assessment.

- Intervention types: RUTF (Ready-to-Use Therapeutic Food), RUSF (Ready-to-Use Supplementary Food), Health Education, Supplementary Feeding
- Pre-weight and post-weight are recorded for outcome tracking
- On completion, a new assessment is automatically generated using the post-weight, and eligibility is re-evaluated
- If the child recovers (Normal status), they are removed from the eligible list

**MNS (Micronutrient Supplementation)**
Tracks three types of micronutrient supplementation:

- **Vitamin A**: distributed in February and August rounds. Eligible children are aged 6–59 months who have not yet been covered for the selected round/year. Dosage is auto-assigned: 100,000 IU (Blue) for ages 6–11 months; 200,000 IU (Red) for ages 12–59 months.
- **MNP (Micronutrient Powder)**: for children aged 6–23 months. A powdered supplement mixed into soft food. The system shows a "Not Yet Received" list and a "Records Given" panel side by side.
- **LNS-SQ (Lipid-based Nutrient Supplement – Small Quantity)**: for children aged 6–23 months. Same layout as MNP.

All MNS recordings automatically create a corresponding entry in the Dispensing Tracker.

**Custom Programs**: administrators can define additional programs (e.g., Iron Supplementation, Deworming) via the Program Manager. Each custom program has a unique code, name, icon, and color, and uses a generic enrollment page.

#### Dispensing Tracker

Provides a centralized record of all supplements and medicines dispensed across all programs. Records are auto-created from MNS entries (Vitamin A, MNP, LNS-SQ) and can also be added manually. Filterable by year, program type, and barangay. Exportable to Excel and PDF.

#### Data Import

Authorized users can import beneficiary records and initial assessments from Excel files (.xlsx or .xls). The import workflow includes:
1. Upload file (from device, Google Drive, or URL)
2. System validates and previews rows with color coding (green = new, yellow = update, red = error)
3. User selects destination folder and confirms import
4. Rows with errors are skipped; successful rows are inserted

#### Reports and Exports

| Report | Description |
|--------|-------------|
| OPT Report | Assessments by nutritional status, year, period, and barangay |
| DSP Report | Enrollment and outcome data by year and barangay |
| MNS Report | Vitamin A, MNP, and LNS-SQ coverage by round/year and barangay |
| Outcome Report | Pre- and post-weight comparison for completed DSP enrollments |
| Summary Report | Per-barangay totals: beneficiary count, malnutrition rate, DSP active, Vitamin A coverage |
| Period Comparison | January vs. July OPT malnutrition rate comparison per barangay with trend chart |
| Distribution Report | Supplement dispensing summary by type across all programs |
| eOPT Export | Fully populated eOPT Plus Community Level Tool (.xlsx) workbook for municipal submission |

Export formats: CSV, Excel (.xlsx), PDF.

The eOPT Export populates all required sheets of the official eOPT Plus template: Summary, OPT_Form1A, OPT_Form1B, classification lists (UW, SUW, Stunted, Severely Stunted, Wasted, Severely Wasted), BNS_Printout, Nut_StatusTool, Clean&Update, and Data-Export.

#### Data Validation Workflow

Assessments and beneficiary records submitted by field workers (BHW, BNS, Encoder, Midwife) through the mobile application receive a `validation_status` of `pending`. Nutritionists and administrators can review these submissions through the Validation Queue and either validate or reject them with a note. Validated records are included in official reports; pending records are flagged for review.

---

### 6.2 Mobile Application

#### Overview

The NMS Mobile Application is an Android application built with the Flutter framework. It provides field health workers with the ability to:
- View and add beneficiaries from the field
- Record nutritional assessments and supplementation activities
- Monitor program data (OPT, DSP, MNS)
- Sync data with the central server
- Access summary reports and dashboard statistics
- Work with local offline storage when connectivity is unavailable

#### Application Structure

```
lib/
├── main.dart                    — App entry point, navigation shell, splash router
├── config/
│   └── app_config.dart          — Base URL configuration (configurable at runtime)
├── models/
│   ├── beneficiary_model.dart   — Beneficiary data model
│   ├── assessment_model.dart    — Assessment data model
│   └── user_model.dart          — User/session data model
├── services/
│   ├── api_service.dart         — All HTTP REST API calls (singleton)
│   ├── auth_provider.dart       — Authentication state (ChangeNotifier/Provider)
│   ├── local_db_service.dart    — Local SQLite database for offline storage
│   └── sync_service.dart        — Push/pull sync with server API
└── screens/
    ├── login/                   — Login screen with server URL configuration
    ├── dashboard/               — Home screen with statistics
    ├── beneficiaries/           — Beneficiary list, form, detail, trash, follow-up
    ├── assessments/             — Assessment form and batch assessment
    ├── programs/                — OPT, DSP, MNS program screens
    ├── dispensing/              — Dispensing tracker
    ├── reports/                 — Report screens
    ├── validation/              — Validation queue and submission tracking
    ├── admin/                   — User management and program manager
    ├── activity/                — Activity log
    └── help/                    — Help and documentation screen
```

#### Key Mobile Features

- **Configurable Server URL**: the API base URL can be changed at runtime from the login screen (Settings icon). The URL is persisted in SharedPreferences. Default: `https://kabnms.duckdns.org/api`
- **Token-based Authentication**: upon login, a Bearer token is issued and stored in encrypted SharedPreferences. The token is included in all subsequent API requests.
- **Automatic Session Expiry**: a 401 response from the API triggers automatic logout and redirect to the login screen.
- **Offline Local Database**: beneficiary data can be stored locally in SQLite using sqflite, allowing data entry when connectivity is unavailable. Data is pushed to the server when the connection is restored.
- **Animated Splash Screen**: displays a branded loading screen while the authentication state is being checked.
- **Role-aware Navigation**: navigation menu items are shown or hidden based on the logged-in user's role and permissions.
- **Barangay Scoping**: field-role users (BHW, BNS, Midwife) only see data for their assigned barangay.
- **Validation Workflow**: field workers submit records to the admin/nutritionist for validation. They can track the status of their submissions through the My Submissions screen.

---

## 7. Database Design

### Database Management System

**MySQL 8.0** running on the DigitalOcean cloud server. All queries use PDO prepared statements to prevent SQL injection. The connection uses the `utf8mb4` character set to support full Unicode including special characters.

### Entity-Relationship Overview

```
users
  |
  +-- created_by ---------> beneficiaries
  |                              |
  |                              +-- assessments (WFA, HFA, WFL/H Z-scores)
  |                              |       |
  |                              |       +-- [auto-flags for DSP if UW/SUW/SW/MW]
  |                              |       +-- [auto-created on DSP completion]
  |                              |
  |                              +-- program_enrollments (OPT / DSP / custom)
  |                              |       DSP: intervention_type, pre/post weight
  |                              |
  |                              +-- vitamin_a_records  --> dispensing_records
  |                              +-- mnp_records        --> dispensing_records
  |                              +-- lns_sq_records     --> dispensing_records
  |
  +-- activity_logs
  +-- import_logs
  +-- stored_files

programs (OPT, DSP, MNS, and admin-created custom programs)
who_growth_standards (WHO LMS reference values — no FK, referenced by code)
api_tokens (Bearer tokens for mobile app authentication)
```

### Table Descriptions

| Table | Description |
|-------|-------------|
| `users` | System user accounts with role, assigned barangay, and active status |
| `api_tokens` | Bearer tokens issued to mobile app users; linked to user account |
| `beneficiaries` | Child beneficiary records with full demographic, guardian, and socioeconomic data; soft-deletable |
| `assessments` | Anthropometric measurements (weight, height, MUAC), computed Z-scores, and nutritional status classifications per child per OPT period |
| `programs` | Program definitions — built-in (OPT, DSP, MNS) and administrator-created custom programs |
| `program_enrollments` | Enrollment records for OPT, DSP, MNS, and custom programs; DSP includes pre/post weight |
| `vitamin_a_records` | Vitamin A distribution records per child per round/year |
| `mnp_records` | Micronutrient Powder (MNP) distribution records per child per year |
| `lns_sq_records` | LNS-SQ (Lipid-based Nutrient Supplement) distribution records per child per year |
| `dispensing_records` | All supplement and medicine dispensing events; auto-populated from MNS recordings |
| `activity_logs` | Timestamped audit trail of all user data actions |
| `import_logs` | Audit records for Excel bulk import operations |
| `stored_files` | Metadata for files uploaded to the general file storage |
| `who_growth_standards` | WHO LMS reference parameters (L, M, S values) for ages 0–60 months by sex and measurement type |

### Key Table Details

**`beneficiaries`** — Central table. Notable columns:
- `barangay`, `purok_zone`, `incode` — geographic location fields
- `is_4ps_member`, `is_pwd_household`, `is_indigenous_people` — socioeconomic flags
- `source` — origin of the record: `Walk-in`, `Excel`, `Google`, `Demo`, or `Mobile`
- `deleted_at` — soft-delete timestamp (NULL = active)
- `submitted_at` — timestamp when submitted for admin validation (mobile workflow)
- `validation_status` — `pending` or `validated`

**`assessments`** — One record per weighing event. Notable columns:
- `period` — `January` or `July` (derived server-side from assessment date)
- `assessment_year` — integer year
- `weight_kg`, `height_cm`, `muac_cm` — measurements
- `wfa_zscore`, `hfa_zscore`, `wflh_zscore` — computed Z-scores
- `nutritional_status` — `SUW`, `UW`, `Normal`, `OW`, `OB` (from WFA)
- `hfa_status` — `SSt`, `St`, `Normal`
- `wflh_status` — `SW`, `MW`, `Normal`, `OW`, `OB`
- `validation_status` — `pending` or `validated`
- `validated_by`, `validated_at` — validation tracking
- Unique constraint: one assessment per `(beneficiary_id, period, assessment_year)`

**`api_tokens`** — Bearer tokens for mobile API authentication:
- `user_id` — foreign key to `users.id`
- `token` — hashed Bearer token value
- `device_name` — name of the issuing device
- `last_used_at` — timestamp of last API request using this token
- `created_at` — token issue timestamp

### Database Indexes

| Table | Index | Purpose |
|-------|-------|---------|
| `beneficiaries` | `idx_bene_barangay` | Barangay-scoped queries for field roles |
| `beneficiaries` | `idx_bene_name` | Name search |
| `beneficiaries` | `idx_bene_dob` | Age calculation and aged-out detection |
| `beneficiaries` | `idx_bene_deleted_at` | Soft-delete filtering |
| `assessments` | `idx_assess_year` | Period/year filtering for reports |
| `assessments` | `idx_assess_status` | Nutritional status distribution queries |
| `assessments` | `idx_one_per_period` | Unique constraint enforcement |
| `who_growth_standards` | `uk_who_lms` | Unique lookup by sex, age, and measurement type |

---

## 8. REST API Documentation

### Base URL

```
https://kabnms.duckdns.org/api
```

### Authentication

All endpoints (except login) require a Bearer token in the `Authorization` header:

```
Authorization: Bearer {token}
Content-Type: application/json
```

Tokens are issued upon successful login and remain valid until the user logs out. A `401 Unauthorized` response is returned for missing or invalid tokens.

### Standard Response Format

All API responses return JSON with the following structure:

**Success:**
```json
{
  "success": true,
  "data": { ... },
  "message": "Optional message"
}
```

**Failure:**
```json
{
  "success": false,
  "message": "Error description"
}
```

---

### Authentication Endpoints

#### POST /api/auth/login
Authenticates a user and returns a Bearer token.

**Request Body:**
```json
{
  "username":    "string",
  "password":    "string",
  "device_name": "string"
}
```

**Response (success):**
```json
{
  "success": true,
  "data": {
    "token": "string",
    "user": {
      "id":        1,
      "username":  "string",
      "full_name": "string",
      "role":      "string",
      "barangay":  "string",
      "is_active": 1
    }
  }
}
```

---

#### POST /api/auth/logout
Invalidates the current Bearer token.

**Headers:** `Authorization: Bearer {token}`

---

#### GET /api/auth/me
Returns the authenticated user's profile.

---

### Beneficiary Endpoints

#### GET /api/beneficiaries
Returns a paginated list of beneficiaries.

**Query Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `page` | integer | Page number (default: 1) |
| `per_page` | integer | Records per page (default: 30) |
| `barangay` | string | Filter by barangay name |
| `search` | string | Search by name |
| `status` | string | `active`, `aged_out`, or blank (all) |
| `source` | string | Filter by record source |
| `updated_since` | datetime | Return only records updated after this timestamp |
| `validated_only` | integer | `1` to return only validated records |

---

#### GET /api/beneficiaries/{id}
Returns a single beneficiary record by ID.

---

#### POST /api/beneficiaries
Creates a new beneficiary record.

---

#### PUT /api/beneficiaries/{id}
Updates an existing beneficiary record.

---

#### DELETE /api/beneficiaries/{id}
Soft-deletes a beneficiary record (sets `deleted_at`).

---

#### POST /api/beneficiaries/{id}/restore
Restores a soft-deleted beneficiary.

---

#### POST /api/beneficiaries/{id}/submit
Submits a beneficiary record for admin validation (mobile workflow).

---

#### GET /api/beneficiaries/trash
Returns all soft-deleted beneficiaries.

---

#### GET /api/beneficiaries/followup
Returns beneficiaries flagged for follow-up (worsened status).

**Query Parameters:** `year`, `barangay`

---

#### GET /api/beneficiaries/check-duplicate
Checks for a duplicate beneficiary record by name and date of birth.

**Query Parameters:** `first_name`, `last_name`, `date_of_birth`

---

#### GET /api/beneficiaries/ready-to-submit
Returns beneficiaries in the local draft state that are ready to be submitted.

---

#### POST /api/beneficiaries/batch-submit
Submits multiple beneficiary records for validation at once.

**Request Body:**
```json
{ "ids": [1, 2, 3] }
```

---

### Assessment Endpoints

#### GET /api/assessments
Returns a paginated list of assessments.

**Query Parameters:** `beneficiary_id`, `barangay`, `year`, `period`, `page`, `per_page`

---

#### POST /api/assessments
Creates a new assessment. The server computes Z-scores and nutritional status automatically.

**Request Body:**
```json
{
  "beneficiary_id":  1,
  "assessment_date": "2026-01-15",
  "weight_kg":       12.5,
  "height_cm":       85.0,
  "muac_cm":         13.5
}
```

---

#### DELETE /api/assessments/{id}
Deletes an assessment record.

---

#### POST /api/assessments/batch
Records multiple assessments in a single request.

**Request Body:**
```json
{
  "assessments": [
    { "beneficiary_id": 1, "assessment_date": "...", "weight_kg": 12.5 },
    { "beneficiary_id": 2, "assessment_date": "...", "weight_kg": 10.0 }
  ]
}
```

---

### Program Endpoints

#### GET /api/programs/{type}
Returns program data. `type` can be: `opt`, `dsp`, `mns`, or a custom program code.

**Query Parameters:** `year`, `period`, `barangay`, `tab`, `status`

For MNS: `round` parameter accepts `February` or `August`.

---

#### POST /api/programs/dsp/enroll
Enrolls a beneficiary in the DSP.

---

#### POST /api/programs/dsp/update
Updates a DSP enrollment record.

---

#### POST /api/programs/dsp/discharge
Discharges (completes or drops) a DSP enrollment. If `post_weight_kg` is provided, a new assessment is automatically created.

---

#### POST /api/programs/mns/vitamina
Records a Vitamin A distribution event.

---

#### DELETE /api/programs/mns/vitamina/{id}
Deletes a Vitamin A record.

---

#### POST /api/programs/mns/mnp
Records an MNP distribution event.

---

#### POST /api/programs/mns/mnp/{id}/complete
Marks an MNP record as routine completed.

---

#### POST /api/programs/mns/lnssq
Records an LNS-SQ distribution event.

---

#### POST /api/programs/mns/lnssq/{id}/complete
Marks an LNS-SQ record as routine completed.

---

#### GET /api/programs/{code}
Returns data for a custom program by its code.

---

#### POST /api/programs/{code}/enroll
Enrolls a beneficiary in a custom program.

---

#### POST /api/programs/{code}/discharge
Discharges a beneficiary from a custom program.

---

### Sync Endpoints

#### GET /api/sync/pull
Pulls updated records from the server since a given timestamp.

**Query Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `since` | datetime | Return records updated after this timestamp |
| `barangay` | string | Limit pull to a specific barangay |

**Response data includes:** beneficiaries and assessments updated since the given timestamp.

---

#### POST /api/sync/push
Pushes locally created or updated records to the server.

**Request Body:**
```json
{
  "beneficiaries": [ { ... }, { ... } ],
  "assessments":   [ { ... }, { ... } ]
}
```

---

### Dispensing Endpoints

#### GET /api/dispensing
Returns dispensing records.

**Query Parameters:** `year`, `barangay`

---

#### POST /api/dispensing
Creates a manual dispensing record.

---

### Statistics Endpoint

#### GET /api/stats/dashboard
Returns dashboard statistics (beneficiary counts, program enrollment, assessment coverage).

**Query Parameters:** `barangay`

---

### Report Endpoints

| Endpoint | Description |
|----------|-------------|
| `GET /api/reports/summary` | Summary report per barangay |
| `GET /api/reports/opt` | OPT assessment report |
| `GET /api/reports/dsp` | DSP enrollment and outcome report |
| `GET /api/reports/mns` | MNS coverage report |
| `GET /api/reports/outcome` | DSP pre/post weight outcome report |
| `GET /api/reports/comparison` | Period comparison report |
| `GET /api/reports/distribution` | Dispensing distribution report |

All report endpoints accept `year`, `period`, and `barangay` as query parameters.

---

### Validation Endpoints

| Endpoint | Description |
|----------|-------------|
| `GET /api/validation/pending` | Assessments pending validation |
| `GET /api/validation/my-submissions` | Field worker's submitted assessments |
| `GET /api/validation/counts` | Count of pending items |
| `POST /api/validation/batch` | Batch validate or reject multiple records |
| `POST /api/validation/{id}/validate` | Validate a single assessment |
| `POST /api/validation/{id}/reject` | Reject a single assessment with a note |
| `GET /api/validation/beneficiaries/pending` | Beneficiary records pending validation |
| `GET /api/validation/beneficiaries/my-submissions` | Field worker's submitted beneficiaries |
| `POST /api/validation/beneficiaries/{id}/validate` | Validate a beneficiary submission |
| `POST /api/validation/beneficiaries/{id}/reject` | Reject a beneficiary submission |

---

### User Management Endpoints *(Admin only)*

| Endpoint | Description |
|----------|-------------|
| `GET /api/users` | List all system users |
| `POST /api/users` | Create a new user account |
| `PUT /api/users/{id}` | Update a user account |
| `DELETE /api/users/{id}` | Delete a user account |
| `POST /api/users/{id}/activate` | Toggle a user's active status |

---

### Program Admin Endpoints *(Admin/Nutritionist)*

| Endpoint | Description |
|----------|-------------|
| `GET /api/programs/list` | List all active programs |
| `GET /api/programs-admin` | List all programs (admin view) |
| `POST /api/programs-admin` | Create a new custom program |
| `PUT /api/programs-admin/{id}` | Update a program |
| `POST /api/programs-admin/{id}/toggle` | Toggle a program's active status |

---

### Miscellaneous Endpoints

| Endpoint | Description |
|----------|-------------|
| `GET /api/barangays` | Returns a list of all barangay names in the system |
| `GET /api/activity` | Returns the activity log (paginated) |
| `GET /api/backup/list` | Lists available database backup files |
| `GET /api/backup/{filename}` | Downloads a specific database backup file |

---

## 9. Data Synchronization

The NMS Mobile Application implements a push-pull synchronization model to keep local and server data consistent.

### Pull Synchronization

The mobile app calls `GET /api/sync/pull` with a `since` timestamp to retrieve all records updated on the server after the last successful sync. This is used to keep the local SQLite database up to date. For field-role users, the pull is scoped to their assigned barangay.

### Push Synchronization

The mobile app calls `POST /api/sync/push` to send locally created or updated records to the server. The server processes each record, inserts or updates it in the MySQL database, and returns the result.

### Sync Triggers

- Automatic sync runs at configurable intervals when the app is connected to the internet
- Manual sync can be triggered by the user from the app interface
- Each successful sync updates the `last_sync_at` timestamp stored in SharedPreferences, which is used as the `since` parameter for the next pull

### Offline Capability

When the device is offline, the app continues to function using data stored in the local SQLite database. New records created offline are queued and pushed to the server when connectivity is restored.

---

## 10. Security Implementation

### Web System

| Security Measure | Implementation |
|-----------------|----------------|
| Password hashing | bcrypt via PHP `password_hash()` |
| SQL injection prevention | PDO prepared statements on all queries |
| XSS prevention | `htmlspecialchars()` applied to all output |
| CSRF protection | CSRF token generated per session, validated on every POST request |
| Session management | PHP sessions with `session_regenerate_id()` on login |
| Role-based access control | Every route checks the user's role before processing |
| Barangay data isolation | Field roles (BHW, BNS, Midwife) are restricted to their assigned barangay at the model layer |
| Soft deletes | Records are never permanently deleted; `deleted_at` timestamp enables audit trail |
| File upload validation | Extension and MIME type validation; files stored outside the public directory |
| Storage protection | `storage/` directory has `.htaccess` (Deny from all) — files served only through controller endpoints |

### Mobile Application / API

| Security Measure | Implementation |
|-----------------|----------------|
| Bearer token authentication | All API requests require a valid token in the `Authorization` header |
| Encrypted token storage | Bearer token stored using `flutter_secure_storage` with Android encrypted SharedPreferences |
| HTTPS-ready | App and API support HTTPS; can be configured with SSL on the server |
| Automatic session expiry | 401 response triggers automatic logout and redirect to login screen |
| Token invalidation on logout | Server deletes the token from `api_tokens` table; client clears local storage |
| Input validation | All API inputs are validated server-side before database operations |

---

## 11. Deployment and Infrastructure

### Cloud Server

The system is deployed on a DigitalOcean Droplet with the following specifications:

| Property | Value |
|----------|-------|
| Provider | DigitalOcean |
| Region | Singapore (SGP1) |
| Plan | Premium Intel ($8/month) |
| Operating System | Ubuntu 24.04 LTS |
| Web Server | Nginx + Let's Encrypt SSL |
| PHP Runtime | PHP 8.3-FPM |
| Database | MySQL 8.0 |
| Public IP | 152.42.197.110 |
| Domain | kabnms.duckdns.org (DuckDNS) |
| SSL | Let's Encrypt (auto-renews every 90 days) |
| PWA | Installable as desktop/mobile app via Chrome/Edge |
| Web URL | https://kabnms.duckdns.org |
| API Base URL | https://kabnms.duckdns.org/api |
| Application Directory | /var/www/nms |

### Nginx Configuration

Nginx acts as a reverse proxy. It serves static files (CSS, JS, images) directly and forwards PHP requests to PHP-FPM. The configuration routes all requests to `public/index.php`, which serves as the application's single entry point.

### Server Software Stack

```
Internet Request
       |
       v
   Nginx (Port 80)
       |
       +-- Static files (CSS, JS, images) --> served directly
       |
       +-- PHP requests --> PHP 8.3-FPM --> /var/www/nms/public/index.php
                                                    |
                                                    v
                                            Custom MVC Router
                                                    |
                                    +---------------+---------------+
                                    |                               |
                             Web Controllers                  API Controllers
                          (HTML responses)               (JSON responses, /api/*)
                                    |                               |
                                    +---------------+---------------+
                                                    |
                                                    v
                                              MySQL Database
```

### Environment Configuration

The server environment is configured through the `.env` file at `/var/www/nms/.env`:

```env
APP_URL=https://kabnms.duckdns.org
APP_ENV=production
DB_HOST=localhost
DB_NAME=nms
DB_USER=nmsuser
DB_PASS=NmsAdmin@2026
```

### Deployment Procedure

Updates to the PHP backend are deployed using Git:

1. Developer pushes changes to GitHub: `git push origin master`
2. On the server: `cd /var/www/nms && git pull && composer install --no-dev`
3. Nginx does not need to be restarted for PHP code changes

---

## 12. Mobile Application Build and Installation

### Development Environment Requirements

| Requirement | Version |
|-------------|---------|
| Flutter SDK | 3.32.2 |
| Dart SDK | 3.8.1 |
| Android SDK | API 21+ |
| Operating System | Windows / macOS / Linux |

### Building the Release APK

```bash
# Navigate to the project directory
cd C:\xampp\htdocs\nms_mobile

# Build release APK for ARM64 Android devices
flutter build apk --release --target-platform android-arm64
```

**Output file:** `build\app\outputs\flutter-apk\app-release.apk`

### Installation

1. Enable **"Install from unknown sources"** in Android device settings
2. Transfer the APK file to the Android device
3. Open the APK file on the device to install
4. Launch the application

### Server URL Configuration

On first launch, the app connects to the default server URL (`https://kabnms.duckdns.org/api`). The URL can be changed by tapping the settings icon on the login screen. This is useful for local development or testing against a different server instance.

### Application Permissions

The mobile application requests the following Android permissions:

- **INTERNET** — required for all API communication with the server

---

## 13. System Administration

### Default Administrator Credentials

| Field | Value |
|-------|-------|
| Username | `admin` |
| Password | `Admin@1234` |

> **Important:** The default password must be changed immediately after the initial setup.

### User Account Management

User accounts are managed by the administrator through **Admin → User Management**. Each account requires:
- Full name and username
- Password (bcrypt-hashed)
- Role assignment
- Barangay assignment (required for BHW, BNS, and Midwife roles)
- Active/inactive status

### Database Backup

Backups are full SQL dumps generated by `mysqldump` and stored on the server at `/var/www/nms/database/backups/`. Up to 7 daily backups are retained; older files are pruned automatically.

- **Automatic backup**: triggered once per 24 hours when the mobile app performs a sync push
- **Manual backup**: Admin → Database Backup → Create Backup Now
- **Download live dump**: Admin → Database Backup → Download Live DB

**Restore command:**
```bash
mysql -u nmsuser -p nms < nms_backup_YYYY-MM-DD_HHiiss.sql
```

### Activity Log

A complete audit trail of all user data actions is maintained in the `activity_logs` table. Logged events include: login, logout, failed login, beneficiary create/update/delete/restore, assessment create/delete, batch assessment, DSP enroll/discharge, dispensing record, and data import.

The Activity Log is accessible to administrators and nutritionists through the web system sidebar.

### Demo Data Seeder

The system includes a demo data seeder accessible to administrators at `/admin/seed`. It inserts approximately 30 realistic child beneficiary records with assessments, DSP enrollments, and Vitamin A records across three sample barangays for testing purposes. All demo records are tagged with `source = 'Demo'` and can be removed with the Clear Demo Data function without affecting real data.

---

*Document version: July 2026 — NMS v1.1 | HTTPS + PWA + Git Deployment*
