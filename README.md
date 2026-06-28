# NMS — Nutrition Monitoring System

A web-based system for the City Health Office Nutrition Department to monitor and manage child nutrition programs for beneficiaries aged 0–59 months.

---

## Tech Stack

| Layer | Technology |
|-------|-----------|
| Language | PHP 8.0+ |
| Framework | Custom MVC (no external framework) |
| Database | **SQLite** (file at `database/nms.sqlite`) |
| Frontend | Bootstrap 5.3, Bootstrap Icons, Chart.js |
| Excel Import | PhpSpreadsheet 1.29+ |
| eOPT Excel Export | Direct ZIP + XML manipulation (no external library) |
| PDF Export | Dompdf 2.0+ |
| Server | PHP built-in server via `start.bat` (port 3000) or XAMPP Apache |

---

## Requirements

- PHP 8.0 or higher
- Composer
- XAMPP (optional — system also runs via PHP built-in server)

---

## Installation

### 1. Clone / Copy the project

Place the project folder inside your XAMPP `htdocs` directory:

```
C:\xampp\htdocs\nms\
```

### 2. Install dependencies

```bash
composer install
```

### 3. Initialize the database

The SQLite database file is at `database/nms.sqlite`. To create a fresh one from schema:

```bash
php database/init_sqlite.php
```

Or use the existing `database/nms.sqlite` if present.

> `dispensing_records` is auto-created by the `DispensingRecord` model on first use — no migration needed.

### 4. Configure environment

Edit `.env` in the project root:

```env
APP_NAME=NMS
APP_URL=http://127.0.0.1:3000
APP_ENV=development
```

### 5. Add logo and background images

Place your branding images in `public/img/`:

```
public/img/logo.jpg        ← City/LGU seal (shown in navbar and login page)
public/img/background.jpg  ← Background image shown behind page content
```

### 6. Start the server

Double-click `start.bat` or run:

```bash
php -S 127.0.0.1:3000 -t public
```

### 7. Access the system

```
http://127.0.0.1:3000
```

---

## Default Login

| Field | Value |
|-------|-------|
| Username | `admin` |
| Password | `Admin@1234` |

> **Note:** Change the admin password after first login.

---

## User Roles

| Role | Description |
|------|-------------|
| **admin** | Full access — user management, all modules, reports, demo seeder |
| **nutritionist** | Access to all modules except user management |
| **encoder** | Can add/edit beneficiaries and record assessments; no deletions |
| **bhw** | Barangay Health Worker — restricted to their assigned barangay only |

---

## Features

### Beneficiary Management
- Add, edit, and soft-delete beneficiaries
- Full demographic profile: personal info, address, parent/guardian, socioeconomic status
- Age filter: Active (0–59 months) / Aged Out (>59 months) with badge indicator
- Last Assessed column with color coding (green < 90 days, yellow < 180, red > 180)
- Duplicate check (AJAX) on add/edit forms
- Trash & Restore page at `/beneficiaries/trash`
- Paginated list (25 per page), search and filter by name, barangay, source, age status

### Beneficiary Profile
- Data completeness indicator (progress bar + 9-field checklist)
- Growth chart with WHO reference lines (-3SD, -2SD, median); toggle Weight/Height
- Print-ready card (browser print — hides UI, shows compact summary card)
- All records in one view: assessments, enrollments, Vitamin A, MNP/LNS-SQ, dispensing

### Nutritional Assessment
- Record weight, height, and MUAC measurements
- Automatic WHO Z-score calculations (WFA, HFA, WFL/H)
- Nutritional status classification (SUW, UW, Normal, OW, OB)
- Auto-enrollment into DSP for malnourished children
- Batch assessment entry by barangay and period

### Programs

#### OPT — Operation Timbang
- View all assessed beneficiaries by nutritional status, year, and period

#### DSP — Dietary Supplementation Program
- Enroll eligible (malnourished) beneficiaries
- Intervention types: RUTF, RUSF, Health Education, Supplementary Feeding
- Track pre/post weight and discharge status
- Auto-creates a new assessment on DSP completion with post-weight

#### MNS — Micronutrient Supplementation
- **Vitamin A** — Feb/Aug rounds; auto-calculates dosage; eligibility list (6–59 months not yet covered)
- **MNP** — "Not Yet Received" list (6–23 months) + individual records panel per year
- **LNS-SQ** — same as MNP; both show who hasn't received and who has
- All MNS recordings auto-create a Dispensing Tracker entry

#### Custom Programs
- Admins can create additional programs via Program Manager
- Each gets a unique code, name, icon, color, and sort order
- Generic enrollment page at `/programs/{code}`

### Dispensing Tracker
- Tracks all medicines and supplements dispensed across programs
- Auto-populated from MNS recordings (Vitamin A, MNP, LNS-SQ)
- Manual entries supported
- Filter by year, program, barangay
- Export to Excel and PDF

### Excel Import & File Storage
- Upload `.xlsx` / `.xls` files with preview and row-level validation
- Duplicate detection; preview before confirming
- Storage Browser: Beneficiary Imports + Other Files tabs
- Folder management; in-browser file preview

### Reports & Export
- **OPT, DSP, MNS, Outcome** reports with CSV/Excel/PDF export
- **Summary Report** — per-barangay coverage, malnutrition rates, DSP, Vitamin A
- **Period Comparison** — January vs July OPT malnutrition rate per barangay with chart
- **Distribution Report** — supplement dispensing summary
- **eOPT Export** — generates a fully-populated eOPT Plus Community Level Tool `.xlsx` workbook; fills Summary, OPT_Form1A, OPT_Form1B, List sheets (UW/SUW/St/SSt/MW/SW), BNS_Printout, Nut_StatusTool, Clean&Update, and Data-Export sheets. Template file required at `storage/templates/eopt_slim.xlsx`.

### Dashboard
- 6 stat cards: Total Beneficiaries, OPT Assessed, DSP Active, MNS Coverage, For Follow-up, Not Yet Assessed this period
- Charts: Nutritional status by barangay (stacked bar), Program enrollment (donut), OPT trend (line), Malnutrition rate by barangay (bar)

### Admin Tools
- User management
- Activity log viewer
- Program Manager
- Database backup download
- **Demo data seeder** (`/admin/seed`) — seeds ~30 realistic beneficiaries with assessments/enrollments; clear button removes all demo data safely

---

## Project Structure

```
nms/
├── app/
│   ├── controllers/        # Request handlers
│   ├── models/             # Database logic
│   ├── views/              # HTML templates
│   └── helpers/            # DateHelper, ZScoreHelper, ActivityLog, EoptExport
├── config/
│   └── config.php          # App constants and .env loader
├── core/
│   ├── Router.php
│   ├── Controller.php
│   ├── Model.php
│   ├── View.php
│   ├── Database.php        # PDO singleton (SQLite)
│   └── Session.php         # Session & CSRF management
├── database/
│   ├── nms.sqlite          # Live SQLite database
│   └── nms_sqlite.sql      # SQLite schema (for fresh install)
├── public/
│   ├── index.php           # Application entry point + all routes
│   ├── css/
│   ├── js/app.js           # Dashboard charts, growth chart, sidebar
│   └── img/                # logo.jpg, background.jpg
├── storage/
│   ├── imports/            # Saved beneficiary import files
│   ├── files/              # General uploaded files
│   └── templates/          # Excel templates (eopt_slim.xlsx for eOPT export)
├── vendor/                 # Composer dependencies
├── start.bat               # Starts PHP built-in server on port 3000
└── .env                    # Environment configuration
```

---

## Database Tables

| Table | Description |
|-------|-------------|
| `users` | System user accounts and roles |
| `beneficiaries` | Child beneficiary records (soft-deletable) |
| `assessments` | Anthropometric measurements and Z-scores |
| `programs` | Program definitions (OPT, DSP, MNS, and custom) |
| `program_enrollments` | OPT/DSP/custom program enrollment records |
| `vitamin_a_records` | Vitamin A distribution records |
| `mnp_records` | Micronutrient Powder distribution records |
| `lns_sq_records` | LNS-SQ distribution records |
| `dispensing_records` | All supplement/medicine dispensing (auto-populated from MNS) |
| `activity_logs` | Timestamped audit trail of all user data actions |
| `import_logs` | Excel import history |
| `stored_files` | General uploaded files (Other Files storage tab) |
| `who_growth_standards` | WHO LMS values for Z-score calculations |

---

## Security

- CSRF token validation on all POST forms
- Password hashing with bcrypt
- SQL injection prevention via PDO prepared statements
- XSS prevention via `htmlspecialchars()` on all output
- Session regeneration on login
- Role-based access control on every route
- BHW data isolation by assigned barangay
- File upload validation (extension + MIME type)
- Soft deletes to preserve audit trail
