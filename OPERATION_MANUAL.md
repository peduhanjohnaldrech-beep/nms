# NMS — Nutrition Monitoring System
## Operation & Troubleshooting Manual

---

## Table of Contents

1. [System Overview](#1-system-overview)
2. [System Requirements](#2-system-requirements)
3. [Starting and Stopping the System](#3-starting-and-stopping-the-system)
4. [Configuration](#4-configuration)
5. [User Roles & Access](#5-user-roles--access)
6. [Common Errors & Fixes](#6-common-errors--fixes)
7. [Database Troubleshooting](#7-database-troubleshooting)
8. [File Upload Troubleshooting](#8-file-upload-troubleshooting)
9. [Import Troubleshooting](#9-import-troubleshooting)
10. [Report & Export Troubleshooting](#10-report--export-troubleshooting)
11. [User Account Management](#11-user-account-management)
12. [Activity Log](#12-activity-log)
13. [Accessing from Other Devices (LAN)](#13-accessing-from-other-devices-lan)
14. [Backup & Restore](#14-backup--restore)
15. [Quick Reference](#15-quick-reference)

---

## 1. System Overview

**NMS (Nutrition Monitoring System)** is a web-based system for the City Health Office to monitor nutrition programs for children aged 0–59 months.

| Item | Value |
|---|---|
| App Name | NMS |
| Platform | PHP 8 + SQLite |
| Default URL | http://127.0.0.1:3000 |
| Database | `database/nms.sqlite` |
| Default Admin | admin / Admin@1234 |

**Programs tracked:**
- **OPT** — Operation Timbang (weight monitoring)
- **DSP** — Dietary Supplementation Program
- **MNS** — Micronutrient Supplementation (Vitamin A, MNP, LNS-SQ)
- **Custom programs** — created via Program Manager

**Key automatic behaviors:**
- When a child is assessed as UW or SUW → automatically flagged as eligible for DSP
- When DSP is completed with a post-weight → a new assessment is auto-created; eligibility is re-evaluated
- When Vitamin A, MNP, or LNS-SQ is recorded → a dispensing record is automatically created
- `dispensing_records` table is auto-created on first run — no migration needed

---

## 2. System Requirements

| Component | Requirement |
|---|---|
| PHP | Version 8.0 or higher |
| PHP Extensions | `pdo_sqlite`, `sqlite3` |
| Browser | Chrome, Edge, Firefox (latest) |
| Optional | XAMPP (Apache) for multi-device LAN access |

---

## 3. Starting and Stopping the System

### Starting (PHP built-in server — recommended)

Double-click **`start.bat`** in the project root, or run:

```bash
php -S 127.0.0.1:3000 -t public
```

Open browser at: `http://127.0.0.1:3000`

### Starting via XAMPP Apache

1. Open XAMPP Control Panel
2. Click **Start** next to **Apache**
3. Open browser at: `http://localhost/nms/public`

### Stopping

- PHP built-in server: close the terminal window that's running it
- XAMPP: click **Stop** next to Apache in XAMPP Control Panel

### If start.bat shows a port error

**Cause:** Port 3000 is already in use.

**Fix:**
1. Open `start.bat` in Notepad
2. Change `3000` to another port (e.g., `3001`)
3. Update `.env`: `APP_URL=http://127.0.0.1:3001`
4. Save and re-run

### If XAMPP Apache won't start

**Cause:** Port 80 is in use (Skype, IIS, etc.).

**Fix:**
1. XAMPP → Apache → Config → `httpd.conf`
2. Change `Listen 80` to `Listen 8080`
3. Restart Apache; update `.env`: `APP_URL=http://localhost:8080/nms/public`

---

## 4. Configuration

### .env File

Location: `C:\xampp\htdocs\nms\.env`

```
APP_NAME=NMS
APP_URL=http://127.0.0.1:3000
APP_ENV=development
```

**When to edit:**
- Changing the server URL or port
- Switching from built-in server to XAMPP (change URL accordingly)
- Adding Google Drive API keys

### Google Drive Integration (optional)

```env
GOOGLE_API_KEY=your_api_key_here
GOOGLE_CLIENT_ID=your_client_id.apps.googleusercontent.com
```

### Branding Images

Place in `C:\xampp\htdocs\nms\public\img\`:

| File | Shown In |
|---|---|
| `logo.jpg` | Navbar and Login page |
| `background.jpg` | Page background (with light overlay) |

System works without these — falls back to text and plain background.

---

## 5. User Roles & Access

| Role | Description | Restrictions |
|---|---|---|
| **admin** | Full access to everything | None |
| **nutritionist** | Full access except user management and demo seeder | Cannot manage users or seed data |
| **encoder** | Add/edit beneficiaries and assessments | No deletions, no import, no discharge |
| **bhw** | Barangay Health Worker | Only their assigned barangay; no Programs Admin |

---

## 6. Common Errors & Fixes

### "404 — Page Not Found"

| Cause | Fix |
|---|---|
| Wrong URL | Go to `http://127.0.0.1:3000` (or your configured URL) |
| Apache mod_rewrite not enabled | See fix below |
| `.htaccess` file missing | Check `public/.htaccess` exists |

**Enabling mod_rewrite (XAMPP only):**
1. XAMPP → Apache → Config → `httpd.conf`
2. Uncomment `LoadModule rewrite_module`
3. Set `AllowOverride All` in the htdocs Directory block
4. Restart Apache

---

### "Database connection failed"

**SQLite-specific:** Verify `database/nms.sqlite` exists and is readable/writable.

```
C:\xampp\htdocs\nms\database\nms.sqlite
```

If the file is missing, initialize from schema:
```bash
php database/init_sqlite.php
```

---

### "Table not found" errors

**Example:** `no such table: dispensing_records`

`dispensing_records` is auto-created on first use — this error should not occur after the first request. If it does:

1. Ensure `app/models/DispensingRecord.php` is loaded (check autoloader)
2. Visit any dispensing-related page to trigger auto-creation

For other missing tables, see Section 7.

---

### "Invalid form token. Please try again."

**Cause:** CSRF token expired (left form open too long, or browser back button).

**Fix:** Refresh the page and resubmit the form.

---

### "Access denied." after login

**Cause:** User's role doesn't have permission for that page.

**Fix:** Use an account with the required role, or update the role in User Management.

---

### Blank white page

1. Open `.env` and set `APP_ENV=development`
2. Check `C:\xampp\apache\logs\error.log` (XAMPP) or the terminal window (built-in server) for the actual error

---

### Growth chart not showing on Beneficiary Profile

**Cause:** Previously required 2+ assessments. Now shows from 1 assessment.

If chart still doesn't appear, check browser console (F12) for JS errors.

---

### MNS record not appearing in Dispensing Tracker

**Cause:** The `dispensing_records` table may have a broken FK (from `fix_fk.php` running partially).

**Fix:** This was resolved in June 2026 by recreating the table with the correct FK. If it recurs, check the table's DDL:
```php
php -r "define('BASE_PATH',__DIR__); require 'config/config.php'; require 'core/Database.php'; echo Core\Database::getInstance()->query(\"SELECT sql FROM sqlite_master WHERE name='dispensing_records'\")->fetchColumn();"
```
The FK should reference `program_enrollments`, not `program_enrollments_tmp`.

---

### MNP / LNS-SQ child not appearing in "Not Yet Received" list

**Cause:** Child may be outside the 6–23 month age range, or already has a record for this year.

**Check:**
- Verify the child's date of birth (must be 6–23 months from today)
- Check if an MNP/LNS-SQ record already exists in their profile

---

### Child appears in DSP eligible list after completing the program

**Cause:** No post-weight was entered on completion — no new assessment was created.

**Fix:** Edit the enrollment to add the post-weight. If the child recovered (Normal weight), the auto-created assessment removes them from the eligible list.

---

### Photo not showing after upload

1. Verify `C:\xampp\htdocs\nms\storage\uploads\photos\` exists
2. Create it manually if missing

---

## 7. Database Troubleshooting

The database is a single SQLite file: `C:\xampp\htdocs\nms\database\nms.sqlite`

### Checking tables

```bash
php -r "
define('BASE_PATH', __DIR__);
require 'config/config.php';
require 'core/Database.php';
\$tables = Core\Database::getInstance()->query(\"SELECT name FROM sqlite_master WHERE type='table' ORDER BY name\")->fetchAll(PDO::FETCH_COLUMN);
echo implode(\"\n\", \$tables);
"
```

Expected tables: `users`, `beneficiaries`, `assessments`, `program_enrollments`, `vitamin_a_records`, `mnp_records`, `lns_sq_records`, `dispensing_records`, `import_logs`, `activity_logs`, `stored_files`, `who_growth_standards`, `programs`

### Resetting admin password

```bash
php -r "
define('BASE_PATH', __DIR__);
require 'config/config.php';
require 'core/Database.php';
\$hash = password_hash('Admin@1234', PASSWORD_BCRYPT);
\$stmt = Core\Database::getInstance()->prepare('UPDATE users SET password_hash = ? WHERE username = ?');
\$stmt->execute([\$hash, 'admin']);
echo 'Password reset to Admin@1234';
"
```

### Checking record counts

```bash
php -r "
define('BASE_PATH', __DIR__);
require 'config/config.php';
require 'core/Database.php';
\$db = Core\Database::getInstance();
echo 'Beneficiaries: ' . \$db->query('SELECT COUNT(*) FROM beneficiaries WHERE deleted_at IS NULL')->fetchColumn() . \"\n\";
echo 'Assessments: ' . \$db->query('SELECT COUNT(*) FROM assessments')->fetchColumn() . \"\n\";
echo 'Dispensing: ' . \$db->query('SELECT COUNT(*) FROM dispensing_records')->fetchColumn() . \"\n\";
echo 'Users: ' . \$db->query('SELECT COUNT(*) FROM users')->fetchColumn() . \"\n\";
"
```

---

## 8. File Upload Troubleshooting

### Upload directory structure

```
public/
└── uploads/            ← Temporary Excel import files

storage/
├── uploads/
│   └── photos/         ← Beneficiary photos
├── imports/            ← Saved beneficiary import files (by folder)
└── files/              ← General uploaded files (Other Files tab)
```

> `storage/` is protected — files are served only through controller endpoints, never directly via URL.

### Photo upload fails

**Checklist:**
1. File under 2MB
2. File type is JPG, PNG, WEBP, or GIF
3. `storage/uploads/photos/` exists and is writable
4. Check `upload_max_filesize` in `php.ini`

### Changing max upload size

In `C:\xampp\php\php.ini`:
```
upload_max_filesize = 20M
post_max_size = 25M
```
Restart Apache (XAMPP) or restart the PHP built-in server after changing.

---

## 9. Import Troubleshooting

### Expected column order (A to V)

1. Last Name, 2. First Name, 3. Middle Name, 4. Suffix, 5. Date of Birth, 6. Sex, 7. Barangay, 8. Purok/Zone, 9. Household No., 10. InCode, 11. Mother's Name, 12. Father's Name, 13. Contact Number, 14. Income Classification, 15. Monthly Household Income, 16. 4Ps Member, 17. NHTS-PR Status, 18. PhilHealth Status, 19. Assessment Date, 20. Weight (kg), 21. Height (cm), 22. MUAC (cm)

### "Column mismatch" error

Ensure the Excel file uses exactly these headers in this order, starting from row 1.

### Import succeeds but no records appear

Review the preview — rows marked **Error** in red are skipped. Fix the data in Excel and re-upload.

### Saved import file missing from Storage Browser

Verify `C:\xampp\htdocs\nms\storage\imports\` exists. Create it manually if missing.

---

## 10. Report & Export Troubleshooting

### Excel export blank/corrupt

```bash
composer install
```

### PDF export garbled

```bash
composer install
```

### Report shows "No records found"

1. Check the **Year** filter
2. Check the **Period** filter (January = Jan–Jun, July = Jul–Dec)
3. Clear the **Barangay** filter to see all
4. For Summary/Comparison reports, ensure assessments exist for the selected year

### eOPT Export — "template file not found"

Place the `eopt_slim.xlsx` file in:
```
C:\xampp\htdocs\nms\storage\templates\eopt_slim.xlsx
```
Create the `templates\` folder if it doesn't exist.

### eOPT Export — downloaded file won't open / Excel repair dialog

The file is a valid `.xlsx` ZIP archive. If Excel shows a repair dialog on open, accept the repair — it is normal for the first open because `calcChain.xml` is intentionally removed so Excel recalculates all formula cells. After saving once in Excel, the file will open cleanly in future.

### eOPT Export — Summary / Data-Export cells still show 0

1. Ensure assessments exist for the selected year and period
2. Check that `nutritional_status`, `hfa_status`, and `wflh_status` are populated in those assessments (not NULL)
3. If Summary totals show but F1K (First 1000 Days) columns are 0 — this was a known bug resolved in June 2026; ensure `EoptExport.php` is up to date

---

## 11. User Account Management

### Creating a user (Admin only)
1. **Admin → User Management → Create User**
2. Fill in username, full name, password, role, barangay (BHW requires this)
3. Save

### Resetting a password
1. **User Management** → Find user → **Edit**
2. Enter a new password → Save

### Disabling an account
1. **User Management** → Find user → **Edit**
2. Uncheck **Active** → Save

### BHW can't see beneficiaries

The barangay field in the user account must match exactly (case-sensitive) the barangay value in beneficiary records. Check User Management → Edit the BHW account.

---

## 12. Activity Log

**Access:** Admin / Nutritionist → Sidebar → **Activity Log**

| Event | Triggered by |
|---|---|
| `login` / `login_failed` / `logout` | Auth actions |
| `beneficiary_create` / `update` / `delete` | Beneficiary changes |
| `beneficiary_restore` | Trash restore |
| `assessment_create` / `delete` | Assessment changes |
| `batch_assessment` | Batch save |
| `dsp_enroll` / `dsp_discharge` | DSP actions |
| `dispensing_create` | Supplement dispensed |
| `import_complete` | Excel import confirmed |
| `program_create` / `program_update` | Programs Admin changes |

---

## 13. Accessing from Other Devices (LAN)

### Setup (XAMPP required for LAN access)

1. Find server IP: Command Prompt → `ipconfig` → IPv4 Address (e.g., `192.168.1.5`)
2. Update `.env`: `APP_URL=http://192.168.1.5/nms/public`
3. Allow Apache through Windows Firewall (port 80)
4. BHW opens: `http://192.168.1.5/nms/public`

Both devices must be on the same Wi-Fi network.

> The PHP built-in server (`start.bat`) only accepts connections from localhost by default. For LAN access use XAMPP Apache, or change start.bat to bind to `0.0.0.0:3000` (less secure).

---

## 14. Backup & Restore

### Backing up (SQLite)

**Simplest method — copy the file:**
```
C:\xampp\htdocs\nms\database\nms.sqlite
```
Copy this file to a USB or Google Drive. That's the entire database.

**Via the system:**
Go to **Admin → Backup** (if available) to download the SQLite file directly.

**Back up uploads and storage too:**
```
C:\xampp\htdocs\nms\storage\
C:\xampp\htdocs\nms\public\img\
```

**Recommended:** Back up weekly, or before any major import or seeding.

### Restoring

Replace `database/nms.sqlite` with the backed-up copy. Restart the server.

### Fresh installation

1. Run `composer install`
2. Place `database/nms.sqlite` (from backup or fresh schema init)
3. Set `.env` values
4. Place `logo.jpg` and `background.jpg` in `public/img/`
5. Run `start.bat` and login with `admin` / `Admin@1234`

---

## 15. Quick Reference

### URLs (default: port 3000)

| Page | URL |
|---|---|
| Login | http://127.0.0.1:3000/login |
| Dashboard | http://127.0.0.1:3000/dashboard |
| Beneficiaries | http://127.0.0.1:3000/beneficiaries |
| Beneficiary Trash | http://127.0.0.1:3000/beneficiaries/trash |
| Batch Assessment | http://127.0.0.1:3000/assessments/batch |
| For Follow-up | http://127.0.0.1:3000/beneficiaries/followup |
| OPT Program | http://127.0.0.1:3000/programs/opt |
| DSP Program | http://127.0.0.1:3000/programs/dsp |
| MNS Program | http://127.0.0.1:3000/programs/mns |
| Dispensing Tracker | http://127.0.0.1:3000/dispensing |
| OPT Report | http://127.0.0.1:3000/reports/opt |
| DSP Report | http://127.0.0.1:3000/reports/dsp |
| MNS Report | http://127.0.0.1:3000/reports/mns |
| Outcome Report | http://127.0.0.1:3000/reports/outcome |
| Summary Report | http://127.0.0.1:3000/reports/summary |
| Period Comparison | http://127.0.0.1:3000/reports/comparison |
| Distribution Report | http://127.0.0.1:3000/reports/distribution |
| eOPT Export | http://127.0.0.1:3000/reports/export-eopt |
| Import | http://127.0.0.1:3000/import |
| Import Storage | http://127.0.0.1:3000/import/storage |
| Activity Log | http://127.0.0.1:3000/activity |
| Program Manager | http://127.0.0.1:3000/programs-admin |
| User Management | http://127.0.0.1:3000/users |
| Demo Seeder | http://127.0.0.1:3000/admin/seed |

### Key File Locations

| File/Folder | Path |
|---|---|
| Environment config | `C:\xampp\htdocs\nms\.env` |
| SQLite database | `C:\xampp\htdocs\nms\database\nms.sqlite` |
| City logo | `C:\xampp\htdocs\nms\public\img\logo.jpg` |
| Background image | `C:\xampp\htdocs\nms\public\img\background.jpg` |
| Beneficiary photos | `C:\xampp\htdocs\nms\storage\uploads\photos\` |
| Saved import files | `C:\xampp\htdocs\nms\storage\imports\` |
| Other uploaded files | `C:\xampp\htdocs\nms\storage\files\` |
| eOPT template | `C:\xampp\htdocs\nms\storage\templates\eopt_slim.xlsx` |
| PHP config | `C:\xampp\php\php.ini` |
| Apache error log | `C:\xampp\apache\logs\error.log` |

---

*Document version: June 2026 — NMS v1.5*
