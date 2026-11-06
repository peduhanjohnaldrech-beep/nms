# NMS — Nutrition Monitoring System

A web-based system for the City Health Office Nutrition Department to monitor and manage child nutrition programs for beneficiaries aged 0–59 months.

---

## Tech Stack

| Layer | Technology |
|-------|-----------|
| Language | PHP 8.0+ |
| Framework | Custom MVC (no external framework) |
| Database | **MySQL / MariaDB 10.4+** (database name: `nms`) |
| Frontend | Bootstrap 5.3, Bootstrap Icons, Chart.js |
| Excel Import | PhpSpreadsheet 1.29+ |
| eOPT Excel Export | Direct ZIP + XML manipulation (no external library) |
| PDF Export | Dompdf 2.0+ |
| Email | PHPMailer 6.9+ (SMTP) with PHP `mail()` fallback |
| Server | PHP built-in server via `start.bat` (port 3000) or XAMPP Apache |

---

## Requirements

- PHP 8.0 or higher
- Composer
- MySQL / MariaDB 10.4+ (included in XAMPP)
- XAMPP recommended (includes Apache, MySQL, PHP)

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

Start XAMPP and ensure MySQL is running. Then create and import the schema:

```bash
# In XAMPP Shell or Command Prompt
mysql -u root -e "CREATE DATABASE IF NOT EXISTS nms CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
mysql -u root nms < database/nms.sql
```

Or restore from a backup:

```bash
mysql -u root nms < database/backups/nms_backup_YYYY-MM-DD_HHiiss.sql
```

### 3a. Configure the database connection

Edit `config/database.php`:

```php
return [
    'host'   => 'localhost',
    'dbname' => 'nms',
    'user'   => 'root',
    'pass'   => '',   // set if your MySQL root has a password
];
```

### 4. Configure environment

Edit `.env` in the project root:

```env
APP_NAME=NMS
APP_URL=http://127.0.0.1:3000
APP_ENV=development

# SMTP — required for admin email OTP login (optional: falls back to PHP mail())
SMTP_HOST=smtp.gmail.com
SMTP_PORT=587
SMTP_USER=youremail@gmail.com
SMTP_PASS=your-app-password
SMTP_FROM=youremail@gmail.com
SMTP_FROM_NAME=NMS
```

> **Admin login OTP:** Admin accounts with an email address set will receive a 6-digit verification code via email on every login. Set the email in **Users → Edit**. If no SMTP is configured, it falls back to PHP `mail()`. If no email is set for the admin account, login proceeds without OTP.

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
| **admin** | Full access — user management, all modules, reports, database backup; email OTP required on login if email is set |
| **nutritionist** | Access to all modules except user management; validates mobile submissions |
| **encoder** | Can add/edit beneficiaries and record assessments; no deletions |
| **bns** | Barangay Nutrition Scholar — scoped to assigned barangay; beneficiaries, assessments, and reports; mobile app only for data entry |
| **bhw** | Barangay Health Worker — scoped to assigned barangay; mobile app only; cannot log in to web |
| **midwife** | Barangay-scoped; can record assessments (including on pending-validation beneficiaries); accesses Validation Queue |

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
- Data completeness indicator (progress bar + 8-field checklist)
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
- **Vitamin A** — April & October rounds; auto-calculates dosage (Blue 100,000 IU for 6–11 mo, Red 200,000 IU for 12–59 mo); eligibility list (6–59 months not yet covered); server-side age validation and 5-month cooldown enforcement
- **MNP (Micronutrient Powder)** — "Not Yet Received" list for 6–23 months; Cycle 1 (6–11 mo) and Cycle 2 (12–23 mo) auto-assigned from DOB; age validated server-side
- **LNS-SQ** — monthly 30-sachet distribution for at-risk children 6–23 months; age group auto-assigned from DOB; 25-day minimum gap between doses enforced server-side; Mark Recovered feature for graduated children
- All MNS recordings auto-create a Dispensing Tracker entry
- Real-time eligibility panel in recording modals shows dose type, cycle, and out-of-range warnings before submission

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
- Stat cards: Total Beneficiaries, OPT Assessed, DSP Active, MNS Coverage, Not Yet Assessed this period
- Charts: Nutritional status by barangay (stacked bar), Program enrollment (donut), OPT trend (line), Malnutrition rate by barangay (bar)
- **Mobile layout**: charts hidden on small screens; Quick Actions grid (role-based) for fast navigation; pending validation alert

### Mobile Web Support
- Fully usable on mobile browsers (same URL, no app install needed)
- Beneficiaries page shows card-style list on mobile; switches to full table on desktop
- Dashboard Quick Actions grid with role-based shortcuts (Add Beneficiary, OPT Weighing, Validation, Reports, etc.)
- Stat cards display 2-per-row on mobile

### Admin Tools
- User management (with primary + secondary email for OTP)
- Activity log viewer (includes mobile app actions)
- Program Manager
- Database backup (auto daily via sync + manual "Create Backup Now"; download live dump or saved backups)
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
│   ├── Database.php        # PDO singleton (MySQL)
│   └── Session.php         # Session & CSRF management
├── config/
│   └── database.php        # MySQL connection settings
├── database/
│   ├── nms.sql             # MySQL schema (for fresh install)
│   └── backups/            # Auto-generated mysqldump backups (.sql)
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
- **Email OTP** — Admin accounts with a registered email receive a 6-digit one-time code on every login (primary + secondary email supported)
