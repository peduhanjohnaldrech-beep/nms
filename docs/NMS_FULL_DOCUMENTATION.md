# NMS — Nutrition Monitoring System
### Full System Documentation (Web + Mobile)

> City Health Office — Nutrition Department, Kabankalan City
> Last updated: November 2026

---

## Table of Contents

1. [Overview](#overview)
2. [Tech Stack](#tech-stack)
3. [User Roles](#user-roles)
4. [Web System](#web-system)
   - [Installation](#installation)
   - [Environment Configuration](#environment-configuration)
   - [Features](#web-features)
   - [Mobile Web](#mobile-web)
5. [Mobile App (NMS Mobile)](#mobile-app)
   - [Build & Install](#build--install)
   - [Features](#mobile-features)
6. [Login & Security](#login--security)
7. [API Reference](#api-reference)
8. [Database Schema](#database-schema)
9. [Project Structure](#project-structure)

---

## Overview

The **Nutrition Monitoring System (NMS)** is a two-part system for managing child nutrition programs for beneficiaries aged 0–59 months.

| Component | Description |
|-----------|-------------|
| **Web System** | PHP-based web app at `https://kabnms.duckdns.org`. Used by Admin, Nutritionist, and Midwife for program management, validation, and reporting. Also works on mobile browsers. |
| **Mobile App** | Flutter Android app for BHW, BNS, and Midwife field workers. Supports offline data entry with automatic sync. |

---

## Tech Stack

### Web
| Layer | Technology |
|-------|-----------|
| Language | PHP 8.0+ |
| Framework | Custom MVC (no external framework) |
| Database | MySQL / MariaDB 10.4+ |
| Frontend | Bootstrap 5.3, Bootstrap Icons, Chart.js |
| Excel Import/Export | PhpSpreadsheet 1.29+ |
| eOPT Export | Direct ZIP + XML manipulation |
| PDF Export | Dompdf 2.0+ |
| Email (OTP) | PHPMailer 6.9+ with PHP `mail()` fallback |
| Server | XAMPP Apache (local) / Nginx + PHP-FPM (production) |

### Mobile
| Component | Technology |
|-----------|-----------|
| Framework | Flutter 3.32+ |
| Language | Dart 3.8+ |
| State Management | Provider (ChangeNotifier) |
| HTTP Client | `http` package |
| Secure Storage | `flutter_secure_storage` |
| Local Database | `sqflite` (SQLite) |
| Target Platform | Android 5.0+ (API 21+) |

---

## User Roles

| Role | Web | Mobile | Access |
|------|-----|--------|--------|
| **Admin** | ✓ | ✓ | Full access — all modules, user management, program manager, activity log, backup. Email OTP required on login if email is set. |
| **Nutritionist** | ✓ | ✓ | All modules except user management; validates mobile submissions |
| **Midwife** | ✓ | ✓ | Barangay-scoped; beneficiaries, assessments (including on pending-validation records), validation queue, reports |
| **BNS** | ✗ | ✓ | Barangay-scoped; beneficiaries, assessments, reports; submit records for validation |
| **BHW** | ✗ | ✓ | Barangay-scoped; beneficiaries, assessments; mobile app only |
| **Encoder** | ✓ | — | Add/edit beneficiaries and record assessments; no deletions |

---

## Web System

### Installation

**Requirements:** PHP 8.0+, Composer, MySQL/MariaDB 10.4+, XAMPP (local)

```bash
# 1. Place project in htdocs
# C:\xampp\htdocs\nms\

# 2. Install dependencies
composer install

# 3. Create and import database
mysql -u root -e "CREATE DATABASE IF NOT EXISTS nms CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
mysql -u root nms < database/nms.sql

# 4. Start server (local)
php -S 127.0.0.1:3000 -t public
# or double-click start.bat
```

### Environment Configuration

Edit `.env` in the project root:

```env
APP_NAME=NMS
APP_URL=https://kabnms.duckdns.org
APP_ENV=production

DB_HOST=localhost
DB_NAME=nms
DB_USER=root
DB_PASS=

# SMTP — required for admin email OTP (falls back to PHP mail() if not set)
SMTP_HOST=smtp.gmail.com
SMTP_PORT=587
SMTP_USER=youremail@gmail.com
SMTP_PASS=your-app-password
SMTP_FROM=youremail@gmail.com
SMTP_FROM_NAME=NMS
```

> **Gmail App Password:** Go to [myaccount.google.com](https://myaccount.google.com) → Security → 2-Step Verification → App passwords → create one for "NMS".

### Web Features

#### Beneficiary Management
- Add, edit, soft-delete beneficiaries with full demographic profile
- Search and filter by name, barangay, purok, age status
- Duplicate check on add/edit
- Trash & Restore page
- Paginated list (25/page); card view on mobile, table on desktop

#### Beneficiary Profile
- Data completeness indicator (progress bar)
- Growth chart with WHO reference lines (toggle Weight/Height)
- Print-ready profile card
- All records in one view: assessments, enrollments, Vitamin A, MNP/LNS-SQ, dispensing

#### Nutritional Assessment
- Record weight, height, MUAC
- Automatic WHO Z-score calculations (WFA, HFA, WFL/H)
- Status classification: SUW, UW, Normal, OW, OB
- Batch assessment entry by barangay and period

#### Programs

**OPT — Operation Timbang**
- Semi-annual weighing (January & July)
- Coverage tracking per barangay; highlights unweighed children

**DSP — Dietary Supplementation Program**
- Enroll malnourished (SUW/UW) children manually
- Intervention types: RUTF, RUSF, Health Education, Supplementary Feeding
- Track monitoring visits, pre/post weight, discharge status
- Graduation criteria: 2 consecutive normal validated assessments

**MNS — Micronutrient Supplementation**
- Vitamin A: April & October rounds (Blue 100,000 IU for 6–11 mo, Red 200,000 IU for 12–59 mo)
- MNP: Cycle 1 (6–11 mo) and Cycle 2 (12–23 mo)
- LNS-SQ: monthly 30-sachet distribution for 6–23 months; 25-day minimum gap enforced
- All MNS recordings auto-create a Dispensing Tracker entry

**Custom Programs**
- Admin can create additional programs via Program Manager
- Generic enrollment page at `/programs/{code}`

#### Reports
- **OPT Report** — nutritional status by barangay, period, year; Excel export
- **DSP Report** — enrollment list with intervention details
- **MNS Report** — Vitamin A, MNP, LNS-SQ coverage by barangay
- **Outcome Report** — pre vs. post weight for DSP graduates
- **Summary Report** — per-barangay coverage %, malnutrition rate, DSP, Vitamin A
- **Period Comparison** — January vs July OPT side-by-side with chart
- **Dispensing Report** — supplement dispensing summary
- **eOPT Export** — fully-populated eOPT Plus Community Level Tool `.xlsx` workbook

#### Dashboard
- Stat cards: Total Beneficiaries, OPT Assessed, DSP Active, MNS Coverage, Not Yet Assessed
- Charts: status by barangay, program enrollment, OPT trend, malnutrition rate
- **Mobile**: charts hidden; Quick Actions grid (role-based); pending validation alert

#### Admin Tools
- User Management — add, edit, deactivate; set primary + secondary email for OTP
- Program Manager — add/deactivate programs in sidebar
- Activity Log — full audit trail (web + mobile actions)
- Database Backup — manual or scheduled; download SQL dump

#### Validation Queue
- Review pending beneficiary registrations and assessments submitted from mobile
- Approve or return with a correction note
- Midwife can add assessments to pending-validation beneficiaries

### Mobile Web

The web system is fully usable on mobile browsers — no app install needed.

- **Beneficiaries**: card-style list on mobile, full table on desktop
- **Dashboard**: Quick Actions grid with role-based shortcuts; stat cards 2-per-row
- **Charts**: hidden on mobile screens
- **Responsive**: all forms, filters, and tables adapt to small screens

---

## Mobile App

### Build & Install

**Requirements:** Flutter 3.32+, Android SDK (API 21+)

```bash
cd C:\xampp\htdocs\nms_mobile

# Install dependencies
flutter pub get

# Build release APK
flutter build apk --release
# Output: build/app/outputs/flutter-apk/app-release.apk
```

Transfer the APK to the Android device and install it. Enable **"Install from unknown sources"** if prompted.

> The latest APK is also available for download from the **Help** page in the web system.

### Mobile Features

#### Login
- Bearer token authentication via NMS API; session lasts 30 days
- Admin accounts: OTP sent to registered email on every login

#### Beneficiary Management
- Register, view, and edit child beneficiaries scoped to assigned barangay
- Full profile with assessment history, growth info, program records
- Search and filter

#### OPT Weighing
- Record weight, height, MUAC for individual children
- Submitted records queued for validation; appear in web system after approval

#### DSP Follow-up
- Submit monitoring follow-up for DSP-enrolled children
- Weight and measurement entry; submitted for validation

#### My Submissions / Track Records
- View status of all submitted records: Pending, Validated, Returned
- Returned records show correction note from validator
- Admin/Nutritionist: Track Records shows all submissions across barangays

#### Validation Queue *(Midwife / Admin / Nutritionist)*
- Review and approve or return pending beneficiary registrations and assessments

#### Programs
- **DSP** — view enrollments, track progress
- **MNS** — record Vitamin A, MNP, LNS-SQ distributions

#### Reports
- OPT, DSP, MNS, Outcome, Comparison, Summary reports
- Filter by year, period, barangay

#### Admin Features *(Admin only)*
- User Management — create, edit, deactivate accounts; set email for OTP
- Program Manager — add/deactivate programs
- Activity Log — full audit trail

#### Offline Mode
- Data entered without internet saved to local SQLite database
- Auto-syncs when connection is restored
- Manual sync via dashboard sync button

---

## Login & Security

### Web Login Flow
1. Enter username and password
2. **Admin with email set** → 6-digit OTP sent to primary + secondary email → enter code → logged in
3. **Admin without email** → logs in directly (set email in Users → Edit to enable OTP)
4. **Other roles** → logs in directly

### Security Features
- CSRF token validation on all POST forms
- Password hashing with bcrypt
- PDO prepared statements (SQL injection prevention)
- `htmlspecialchars()` on all output (XSS prevention)
- Session regeneration on login
- Role-based access control on every route
- Barangay-scoped data isolation for BHW/BNS/Midwife
- File upload validation (extension + MIME type)
- Soft deletes to preserve audit trail
- Email OTP for admin login (primary + secondary email)
- Activity log records all actions

### Default Admin Credentials
| Field | Value |
|-------|-------|
| Username | `admin` |
| Password | `Admin@1234` |

> Change the admin password after first login. Set an email to enable OTP.

---

## API Reference

Base URL: `https://kabnms.duckdns.org/api`

Authentication: `Authorization: Bearer {token}`

| Method | Endpoint | Description |
|--------|----------|-------------|
| `POST` | `/api/auth/login` | Login — returns Bearer token |
| `POST` | `/api/auth/logout` | Logout — revokes token |
| `GET` | `/api/auth/me` | Current user info |
| `GET` | `/api/beneficiaries` | List beneficiaries |
| `POST` | `/api/beneficiaries` | Create beneficiary |
| `GET` | `/api/beneficiaries/{id}` | Beneficiary detail |
| `PUT` | `/api/beneficiaries/{id}` | Update beneficiary |
| `GET` | `/api/beneficiaries/{id}/programs` | Program history |
| `POST` | `/api/assessments` | Record single assessment |
| `POST` | `/api/assessments/batch` | OPT batch weighing |
| `GET` | `/api/programs/mns` | MNS data |
| `POST` | `/api/programs/mns/vitamina` | Record Vitamin A |
| `POST` | `/api/programs/mns/mnp` | Record MNP |
| `POST` | `/api/programs/mns/lnssq` | Record LNS-SQ |
| `GET` | `/api/validation/queue` | Pending submissions |
| `POST` | `/api/validation/validate/{id}` | Validate submission |
| `GET` | `/api/sync` | Pull latest data |
| `POST` | `/api/sync/push` | Push offline records |

---

## Database Schema

| Table | Description |
|-------|-------------|
| `users` | Accounts, roles, permissions, email (primary + secondary), device token |
| `beneficiaries` | Child records (soft-deletable), validation status, source |
| `assessments` | Weight/height/MUAC measurements, Z-scores, nutritional status |
| `programs` | Program definitions (OPT, DSP, MNS, custom) |
| `program_enrollments` | DSP and custom program enrollment records |
| `vitamin_a_records` | Vitamin A distribution records |
| `mnp_records` | Micronutrient Powder distribution records |
| `lns_sq_records` | LNS-SQ distribution records |
| `dispensing_records` | All supplement dispensing (auto-populated from MNS) |
| `api_tokens` | Mobile app Bearer tokens |
| `activity_logs` | Audit trail (web + mobile) |
| `import_logs` | Excel import history |
| `stored_files` | Uploaded files (file storage) |
| `who_growth_standards` | WHO LMS values for Z-score calculations |

---

## Project Structure

### Web (`nms/`)
```
nms/
├── app/
│   ├── controllers/        # Request handlers
│   ├── models/             # Database models
│   ├── views/              # PHP templates (layout, dashboard, beneficiaries, etc.)
│   └── helpers/            # DateHelper, ZScoreHelper, ActivityLog, Mailer, EoptExport
├── config/
│   └── config.php          # App constants and .env loader
├── core/                   # Router, Controller, Model, View, Database, Session
├── database/
│   ├── nms.sql             # Schema for fresh install
│   └── backups/            # Auto-generated SQL backups
├── public/
│   ├── index.php           # Entry point + all routes
│   ├── css/app.css         # Custom styles (responsive, dark theme)
│   ├── js/app.js           # Charts, growth chart, sidebar
│   └── img/                # logo.jpg, background.jpg
├── storage/
│   ├── imports/            # Beneficiary import files
│   ├── files/              # General uploaded files
│   └── templates/          # Excel templates (eopt_slim.xlsx)
├── vendor/                 # Composer dependencies
├── .env                    # Environment config
└── start.bat               # Local dev server (port 3000)
```

### Mobile (`nms_mobile/`)
```
nms_mobile/
├── lib/
│   ├── main.dart                    # Entry point, theme, routes
│   ├── models/                      # Data models (UserModel, BeneficiaryModel, etc.)
│   ├── services/
│   │   ├── api_service.dart         # All API calls
│   │   ├── auth_provider.dart       # Auth state (Provider)
│   │   ├── local_db_service.dart    # SQLite operations
│   │   └── sync_service.dart        # Sync logic
│   └── screens/
│       ├── login/
│       ├── dashboard/               # Home, quick actions
│       ├── beneficiaries/           # List, detail, form, follow-up
│       ├── assessments/             # Assessment form
│       ├── programs/                # DSP, MNS
│       ├── reports/
│       ├── validation/              # Validation queue, submissions
│       ├── admin/                   # User management, program manager
│       └── help/
├── android/
├── pubspec.yaml
└── build/app/outputs/flutter-apk/  # Built APKs
```

---

*NMS — City Health Office, Kabankalan City — November 2026*
