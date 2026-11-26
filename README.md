# NMS — Nutrition Monitoring System

A nutrition monitoring platform for the City/Municipal Health Unit Nutrition Department. Tracks children 0–59 months across OPT, DSP, and MNS programs. Consists of a **PHP web system** for coordinators and encoders, and a **Flutter Android app** for field health workers.

- **Web:** https://kabnms.duckdns.org
- **API:** https://kabnms.duckdns.org/api
- **Web repo:** https://github.com/peduhanjohnaldrech-beep/nms
- **Mobile repo:** https://github.com/peduhanjohnaldrech-beep/nmsMobile

---

## System Overview

```
┌─────────────────────────────────────────────────────────────┐
│                   NMS Web System (PHP)                      │
│   Nutrition Coordinators, Midwives, Encoders, BNS/BHW       │
│   https://kabnms.duckdns.org                                │
└───────────────────────────┬─────────────────────────────────┘
                            │  REST API (/api/*)
                            │  Bearer token auth
┌───────────────────────────▼─────────────────────────────────┐
│               NMS Mobile App (Flutter/Android)              │
│        BHW, BNS, Midwife — field data entry & sync          │
└─────────────────────────────────────────────────────────────┘
                            │
                            │  MySQL (kabnms.duckdns.org)
┌───────────────────────────▼─────────────────────────────────┐
│                  Shared MySQL Database                       │
│          DigitalOcean Droplet — /var/www/nms                 │
└─────────────────────────────────────────────────────────────┘
```

---

## User Roles

| Role | Web | Mobile | Scope |
|------|-----|--------|-------|
| **admin** (Nutrition Coordinator) | Full access | Full access | System-wide |
| **nutritionist** | All clinical features, reports | Full access | System-wide |
| **midwife** | Beneficiaries, assessments, validation, reports | Assessments, validation queue | Assigned barangay |
| **bns** (Barangay Nutrition Scholar) | Beneficiaries, assessments, programs, reports | Full field features | Assigned barangay |
| **bhw** (Barangay Health Worker) | Web login disabled | Register beneficiaries, record assessments | Assigned barangay |
| **encoder** | Add/edit beneficiaries and assessments, no deletions | — | System-wide |

---

## Web System

**Path:** `C:\xampp\htdocs\nms`
**Stack:** PHP 8, Custom MVC, MySQL, Bootstrap 5.3, Chart.js

### Features

#### Beneficiary Management
- Register children 0–59 months with full address, guardian, and socioeconomic data
- Soft delete with Trash / Restore; duplicate detection on add/edit
- Data completeness indicator on each profile
- BHW/BNS scoped to their assigned barangay

#### Assessments
- Record weight, height, and MUAC per OPT period (January / July)
- WHO Z-score computation (WFA, HFA, WFL/H) with nutritional status classification
- Growth chart with WHO reference lines (−3SD, −2SD, median)
- Batch assessment entry for quick OPT weighing
- Validation workflow: BHW/BNS submissions start as `pending`; midwife/admin validates

#### Programs
- **OPT** — enrollment per period, eligible list, barangay coverage
- **DSP** — feeding program enrollments; pre/post weight outcome tracking; auto-enrollment when child is flagged malnourished
- **MNS** — Vitamin A (February/August rounds), MNP, and LNS-SQ distributions; real-time eligibility lists
- Custom programs via Program Manager

#### Dispensing Tracker
- Unified log of all supplement/medicine dispensing (MNS auto-populates here)
- Filter by year, program, barangay, purok, or specific beneficiary
- **Due This Month** tab — enrolled beneficiaries with no dispensing record for the selected month
- Quick Dispense modal directly from the Due list; Edit and Delete existing records
- Monthly MNS log (MNP, LNS-SQ, Vitamin A) for the selected month
- Excel and PDF export

#### Barangay Directory
- Per-barangay beneficiary counts and nutritional status summary
- Rename a barangay — cascades automatically to all beneficiary records

#### Import / Export
- Excel import with preview and row-level validation (Standard format and OPT Plus)
- **OPT Plus** — imports directly from the official eOPT Community Level Tool (21-sheet workbook); no memory overhead; reads barangay from E6, data from row 10 of `Nut_StatusTool`
- Google Drive import (optional)
- **eOPT Export** — generates a fully-populated eOPT `.xlsx` workbook with all sheets

#### Reports
- OPT, DSP, MNS, Outcome, Summary, Period Comparison, Distribution reports
- CSV / Excel / PDF export for all reports

#### Admin Tools
- User management, Program Manager, Activity log, Database backup
- Demo data seeder (`/admin/seed`) for training/testing

### Quick Start (Local)

```bash
# 1. Install dependencies
cd C:\xampp\htdocs\nms
composer install

# 2. Create and import database
mysql -u root -e "CREATE DATABASE IF NOT EXISTS nms CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
mysql -u root nms < database/nms_mysql.sql

# 3. Configure .env
APP_URL=http://localhost/nms/public
APP_ENV=production

# 4. Start Apache in XAMPP, then browse to:
#    http://localhost/nms/public
# Or use built-in server:
start.bat    # → http://127.0.0.1:3000
```

**Default login:** `admin` / `Admin@1234`

### Key Files

| File | Purpose |
|------|---------|
| `public/index.php` | Entry point — all routes defined here |
| `app/views/layout.php` | Main layout and sidebar nav |
| `app/models/DispensingRecord.php` | Dispensing CRUD + Due This Month query |
| `app/models/Import.php` | Standard and OPT Plus import parsers |
| `app/helpers/ZScoreHelper.php` | WHO Z-score calculations |
| `app/helpers/EoptExport.php` | eOPT workbook generation |
| `docs/DATABASE.md` | Table and column reference |
| `docs/DEPLOYMENT.md` | Full setup and troubleshooting guide |

---

## Mobile App

**Path:** `C:\xampp\htdocs\nms_mobile`
**Stack:** Flutter 3.32.2, Dart 3.8.1, Provider, sqflite, flutter_secure_storage

### Features

- **Login** — Bearer token auth; session stored securely on-device
- **Beneficiary Management** — Register, view, edit; scoped to assigned barangay
- **Beneficiary Profile** — Assessment history, program records (Vitamin A, MNP, LNS-SQ, DSP), WFA/HFA/WFLH status badges
- **Assessment Recording** — Weight, height, MUAC; auto OPT period detection
- **OPT Batch Weighing** — Enter weights for multiple children, submit in one API call
- **MNS** — Vitamin A (April/October), MNP, LNS-SQ; real-time eligibility panel; age group auto-set from DOB
- **DSP** — View and manage feeding program enrollments
- **Submit to Admin** — BNS submits pending records to midwife/admin for validation
- **Validation Queue** — Midwife/admin reviews and validates pending submissions
- **Reports** — OPT, DSP, MNS, summary reports by barangay
- **Offline Support** — sqflite local DB; records sync on next connection

### Build

```bash
cd C:\xampp\htdocs\nms_mobile

# Debug
flutter build apk --debug --target-platform android-arm64

# Release
flutter build apk --release --target-platform android-arm64
# Output: build\app\outputs\flutter-apk\app-release.apk
```

Install by transferring the APK to the device. Enable "Install from unknown sources" if prompted.

### API URL

Default: `http://152.42.197.110/api` (set in `lib/config/app_config.dart`)

Can be changed at runtime via the app's Settings screen (stored in SharedPreferences). Update `defaultBaseUrl` and rebuild to change the default for new installs.

### Key Files

| File | Purpose |
|------|---------|
| `lib/main.dart` | App entry, theme, main shell, drawer nav |
| `lib/config/app_config.dart` | Base URL config |
| `lib/services/api_service.dart` | All HTTP calls (singleton) |
| `lib/services/auth_provider.dart` | Auth state (ChangeNotifier) |
| `lib/services/local_db_service.dart` | SQLite operations |
| `lib/services/sync_service.dart` | Push/pull sync |

---

## Production Server

| Item | Value |
|------|-------|
| Host | DigitalOcean Droplet — 152.42.197.110 (Singapore) |
| OS | Ubuntu 24.04 LTS |
| Stack | Nginx + PHP 8.3-FPM + MySQL |
| Web URL | https://kabnms.duckdns.org |
| API URL | https://kabnms.duckdns.org/api |
| Files | /var/www/nms |
| Email | Resend HTTP API (SMTP blocked by DigitalOcean network) |
| SSH | Blocked by local ISP — use DigitalOcean web console |

### Deploy Web Updates

```bash
# 1. Local (Git Bash)
cd /c/xampp/htdocs/nms
git add .
git commit -m "description"
git push

# 2. Server (DigitalOcean web console)
cd /var/www/nms && git pull && composer install --no-dev
```

---

## Database Tables

| Table | Description |
|-------|-------------|
| `users` | System accounts with role-based access |
| `barangays` | Managed barangay list; rename cascades to `beneficiaries.barangay` |
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
| `stored_files` | General uploaded files |
| `who_growth_standards` | WHO LMS values for Z-score calculations |

See `docs/DATABASE.md` for full column reference and relationships.

---

## Security

- CSRF token validation on all POST forms
- Passwords hashed with bcrypt
- PDO prepared statements throughout (no SQL injection)
- XSS prevention via `htmlspecialchars()` on all output
- Role-based access control on every route
- BHW/BNS data isolation by assigned barangay
- Email OTP for admin login (Resend API)
- Bearer token auth for mobile API

---

*NMS — Nutrition Monitoring System — November 2026*
