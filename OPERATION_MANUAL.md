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

**NMS (Nutrition Monitoring System)** is a web and mobile system for the City Health Office to monitor nutrition programs for children aged 0–59 months.

| Item | Value |
|---|---|
| App Name | NMS |
| Platform | PHP 8.3 + MySQL 8 |
| Web Server | Nginx + PHP 8.3-FPM |
| Production URL | http://152.42.197.110 |
| API URL | http://152.42.197.110/api |
| Database | MySQL — database name: `nms` |
| Default Admin | admin / Admin@1234 |

**Programs tracked:**
- **OPT** — Operation Timbang (child weight monitoring)
- **DSP** — Dietary Supplementation Program
- **MNS** — Micronutrient Supplementation (Vitamin A, MNP, LNS-SQ)
- **Custom programs** — created via Program Manager

**Key automatic behaviors:**
- When a child is assessed as UW, SUW, SW, or MW → automatically flagged as eligible for DSP
- When DSP is completed with a post-weight → a new assessment is auto-created; eligibility is re-evaluated
- When Vitamin A, MNP, or LNS-SQ is recorded → a dispensing record is automatically created
- Mobile app submissions arrive as `validation_status = pending`; a nutritionist or admin must validate them

---

## 2. System Requirements

### Web Server (Production — DigitalOcean)

| Component | Requirement |
|---|---|
| OS | Ubuntu 24.04 LTS |
| Web Server | Nginx |
| PHP | PHP 8.3-FPM |
| PHP Extensions | `pdo_mysql`, `mysqli`, `gd`, `mbstring`, `zip` |
| Database | MySQL 8.0 |
| Composer | Latest version |

### Local Development (XAMPP)

| Component | Requirement |
|---|---|
| PHP | Version 8.0 or higher |
| PHP Extensions | `pdo_mysql`, `mysqli` (enabled by default in XAMPP) |
| Database | MySQL / MariaDB 10.4+ (included in XAMPP) |
| Browser | Chrome, Edge, Firefox (latest) |

---

## 3. Starting and Stopping the System

### Production Server (DigitalOcean)

The production server runs continuously. Nginx and PHP-FPM start automatically on server boot.

**Check service status (run on server console):**
```bash
systemctl status nginx
systemctl status php8.3-fpm
systemctl status mysql
```

**Restart services if needed:**
```bash
systemctl restart nginx
systemctl restart php8.3-fpm
systemctl restart mysql
```

**Application files location:** `/var/www/nms`

### Local Development (XAMPP)

**Starting (PHP built-in server — recommended):**

Double-click **`start.bat`** in the project root, or run:
```bash
php -S 127.0.0.1:3000 -t public
```
Open browser at: `http://127.0.0.1:3000`

**Starting via XAMPP Apache:**
1. Open XAMPP Control Panel
2. Click **Start** next to **Apache** and **MySQL**
3. Open browser at: `http://localhost/nms/public`

**Stopping:**
- PHP built-in server: close the terminal window that's running it
- XAMPP: click **Stop** next to Apache and MySQL in XAMPP Control Panel

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

**Production location:** `/var/www/nms/.env`
**Local location:** `C:\xampp\htdocs\nms\.env`

**Production `.env` contents:**
```
APP_URL=http://152.42.197.110
APP_ENV=production
DB_HOST=localhost
DB_NAME=nms
DB_USER=nmsuser
DB_PASS=NmsAdmin@2026
```

**Local development `.env` contents:**
```
APP_NAME=NMS
APP_URL=http://127.0.0.1:3000
APP_ENV=development
DB_HOST=localhost
DB_NAME=nms
DB_USER=root
DB_PASS=
```

**When to edit:**
- Changing the server URL or port
- Switching from built-in server to XAMPP
- Adding Google Drive API keys
- Changing database credentials

### Google Drive Integration (optional)

```env
GOOGLE_API_KEY=your_api_key_here
GOOGLE_CLIENT_ID=your_client_id.apps.googleusercontent.com
```

### Branding Images

Place in `public/img/`:

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
| **bns** | Barangay Nutrition Scholar | Only their assigned barangay |
| **bhw** | Barangay Health Worker | Only their assigned barangay; no Programs Admin |
| **midwife** | Can record and validate assessments | Only their assigned barangay |

---

## 6. Common Errors & Fixes

### "404 — Page Not Found"

| Cause | Fix |
|---|---|
| Wrong URL | Go to the correct URL (production: `http://152.42.197.110`) |
| Apache mod_rewrite not enabled | See fix below |
| `.htaccess` file missing | Check `public/.htaccess` exists |

**Enabling mod_rewrite (XAMPP only):**
1. XAMPP → Apache → Config → `httpd.conf`
2. Uncomment `LoadModule rewrite_module`
3. Set `AllowOverride All` in the htdocs Directory block
4. Restart Apache

---

### "Database connection failed"

**Check the `.env` file** — verify `DB_HOST`, `DB_NAME`, `DB_USER`, and `DB_PASS` are correct.

**Test MySQL connection manually:**
```bash
mysql -u nmsuser -p nms
# Enter password when prompted
```

If the error says `Access denied for user`, the credentials in `.env` are wrong.

If the error says `Unknown database 'nms'`, the database hasn't been created yet:
```bash
mysql -u root -e "CREATE DATABASE nms CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
mysql -u root nms < /var/www/nms/database/nms_mysql.sql
```

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
2. Check the Nginx error log (production): `tail -f /var/log/nginx/error.log`
3. Check the PHP-FPM log (production): `tail -f /var/log/php8.3-fpm.log`
4. Check XAMPP Apache log (local): `C:\xampp\apache\logs\error.log`

---

### Growth chart not showing on Beneficiary Profile

Shows from 1 assessment. If it still doesn't appear, check browser console (F12) for JavaScript errors.

---

### MNS record not appearing in Dispensing Tracker

The `dispensing_records` table may have a broken foreign key constraint. Check the table structure:
```sql
SHOW CREATE TABLE dispensing_records;
```
The FK should reference `program_enrollments`, not any temporary table.

---

### MNP / LNS-SQ child not appearing in "Not Yet Received" list

**Cause:** Child may be outside the 6–23 month age range, or already has a record for this year.

**Check:**
- Verify the child's date of birth (must be 6–23 months from today)
- Check if a record already exists in their beneficiary profile

---

### Child appears in DSP eligible list after completing the program

**Cause:** No post-weight was entered on completion — no new assessment was created.

**Fix:** Edit the enrollment to add the post-weight. If the child recovered, the auto-created assessment removes them from the eligible list.

---

### Photo not showing after upload

Verify `storage/uploads/photos/` exists and is writable. Create it manually if missing.

---

## 7. Database Troubleshooting

### Database Server

The database is **MySQL 8.0** running on the DigitalOcean server.

- **Production:** `localhost` on the droplet (accessed via MySQL socket)
- **Database name:** `nms`
- **User:** `nmsuser`
- **Application files:** `/var/www/nms`

### Connecting to MySQL (on server console)

```bash
mysql -u nmsuser -p nms
# Enter: NmsAdmin@2026
```

Or as root:
```bash
mysql -u root
```

---

### Checking Tables

```sql
USE nms;
SHOW TABLES;
```

Expected tables:
`users`, `api_tokens`, `beneficiaries`, `assessments`, `programs`, `program_enrollments`, `vitamin_a_records`, `mnp_records`, `lns_sq_records`, `dispensing_records`, `import_logs`, `activity_logs`, `stored_files`, `who_growth_standards`

---

### Checking Record Counts

```sql
SELECT 'Beneficiaries' AS tbl, COUNT(*) AS cnt FROM beneficiaries WHERE deleted_at IS NULL
UNION ALL
SELECT 'Assessments', COUNT(*) FROM assessments
UNION ALL
SELECT 'Users', COUNT(*) FROM users
UNION ALL
SELECT 'Dispensing', COUNT(*) FROM dispensing_records;
```

---

### Resetting Admin Password

```sql
UPDATE users
SET password_hash = '$2y$10$YourNewHashHere'
WHERE username = 'admin';
```

Or use PHP from the server console:
```bash
cd /var/www/nms
php -r "echo password_hash('Admin@1234', PASSWORD_BCRYPT);"
```
Copy the output and use it in the SQL UPDATE above.

---

### Adding a Missing Column

If a column is missing (e.g., `submitted_at` on `beneficiaries`):
```sql
ALTER TABLE beneficiaries ADD COLUMN submitted_at TIMESTAMP NULL DEFAULT NULL;
```

---

### Re-importing the Schema (fresh install only)

```bash
mysql -u root -e "DROP DATABASE IF EXISTS nms; CREATE DATABASE nms CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
mysql -u root nms < /var/www/nms/database/nms_mysql.sql
```

> **Warning:** This destroys all existing data. Only use for fresh installations.

---

## 8. File Upload Troubleshooting

### Upload Directory Structure

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

### Photo Upload Fails

**Checklist:**
1. File under 2MB
2. File type is JPG, PNG, WEBP, or GIF
3. `storage/uploads/photos/` exists and is writable
4. Check `upload_max_filesize` in `php.ini`

### Changing Max Upload Size

In `C:\xampp\php\php.ini` (local) or `/etc/php/8.3/fpm/php.ini` (production):
```
upload_max_filesize = 20M
post_max_size = 25M
```
Restart the server after changing.

---

## 9. Import Troubleshooting

### Expected Column Order (A to V)

1. Last Name, 2. First Name, 3. Middle Name, 4. Suffix, 5. Date of Birth, 6. Sex, 7. Barangay, 8. Purok/Zone, 9. Household No., 10. InCode, 11. Mother's Name, 12. Father's Name, 13. Contact Number, 14. Income Classification, 15. Monthly Household Income, 16. 4Ps Member, 17. NHTS-PR Status, 18. PhilHealth Status, 19. Assessment Date, 20. Weight (kg), 21. Height (cm), 22. MUAC (cm)

### "Column mismatch" error

Ensure the Excel file uses exactly these headers in this order, starting from row 1.

### Import succeeds but no records appear

Review the preview — rows marked **Error** in red are skipped. Fix the data in Excel and re-upload.

### Saved import file missing from Storage Browser

Verify `storage/imports/` exists. Create it manually if missing.

---

## 10. Report & Export Troubleshooting

### Excel export blank/corrupt

```bash
cd /var/www/nms && composer install --no-dev
```

### PDF export garbled

```bash
cd /var/www/nms && composer install --no-dev
```

### Report shows "No records found"

1. Check the **Year** filter
2. Check the **Period** filter (January = Jan–Jun, July = Jul–Dec)
3. Clear the **Barangay** filter to see all
4. For Summary/Comparison reports, ensure assessments exist for the selected year

### eOPT Export — "template file not found"

Place the `eopt_slim.xlsx` file in:
```
storage/templates/eopt_slim.xlsx
```
Create the `templates/` folder if it doesn't exist.

### eOPT Export — downloaded file won't open / Excel repair dialog

Accept the Excel repair — it is normal for the first open because `calcChain.xml` is intentionally removed so Excel recalculates all formula cells. After saving once in Excel, the file will open cleanly in future.

### eOPT Export — Summary / Data-Export cells still show 0

1. Ensure assessments exist for the selected year and period
2. Check that `nutritional_status`, `hfa_status`, and `wflh_status` are populated (not NULL) in those assessment records

---

## 11. User Account Management

### Creating a user (Admin only)
1. **Admin → User Management → Create User**
2. Fill in username, full name, password, role, barangay (BHW, BNS, and Midwife require a barangay)
3. Save

### Resetting a password
1. **User Management** → Find user → **Edit**
2. Enter a new password → Save

### Disabling an account
1. **User Management** → Find user → **Edit**
2. Uncheck **Active** → Save

### BHW can't see beneficiaries

The barangay field in the user account must exactly match (case-sensitive) the barangay value stored in beneficiary records. Check User Management → Edit the user account.

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
| `backup` | Database backup created |

---

## 13. Accessing from Other Devices (LAN)

### Setup (XAMPP required for LAN access)

1. Find server IP: Command Prompt → `ipconfig` → IPv4 Address (e.g., `192.168.1.5`)
2. Update `.env`: `APP_URL=http://192.168.1.5/nms/public`
3. Allow Apache through Windows Firewall (port 80)
4. Other device opens: `http://192.168.1.5/nms/public`

Both devices must be on the same Wi-Fi network.

> The PHP built-in server (`start.bat`) only accepts localhost connections by default. For LAN access use XAMPP Apache.

---

## 14. Backup & Restore

### Database Backups

The system uses **MySQL** (`mysqldump`) for all backups. Backup files are stored in `database/backups/` and named `nms_backup_YYYY-MM-DD_HHiiss.sql`. Up to 7 daily backups are retained; older ones are pruned automatically.

**Automatic backups:** Triggered once per 24 hours when the mobile app performs a sync push.

**Manual backup:** Admin → Database Backup → **Create Backup Now**

**Download live dump:** Admin → Database Backup → **Download Live DB** (streams a fresh `mysqldump` immediately)

### Creating a Manual Backup (from server console)

```bash
mysqldump -u nmsuser -p nms > /var/www/nms/database/backups/nms_manual_backup.sql
# Enter: NmsAdmin@2026
```

### Restoring from a Backup

```bash
mysql -u nmsuser -p nms < nms_backup_YYYY-MM-DD_HHiiss.sql
# Enter: NmsAdmin@2026
```

### Backing Up Uploaded Files

Back up these directories in addition to the database:
```
/var/www/nms/storage/uploads/photos/   ← Beneficiary photos
/var/www/nms/storage/imports/           ← Saved import files
/var/www/nms/storage/files/             ← General uploaded files
/var/www/nms/public/img/                ← Branding images (logo, background)
```

### Fresh Installation

1. Set up MySQL: create database `nms` and user `nmsuser`
2. Import schema: `mysql -u root nms < database/nms_mysql.sql`
3. Run `composer install --no-dev`
4. Set `.env` values
5. Place `logo.jpg` and `background.jpg` in `public/img/`
6. Navigate to the configured URL and login with `admin` / `Admin@1234`

---

## 15. Quick Reference

### URLs (Production Server)

| Page | URL |
|---|---|
| Login | http://152.42.197.110/login |
| Dashboard | http://152.42.197.110/dashboard |
| Beneficiaries | http://152.42.197.110/beneficiaries |
| Beneficiary Trash | http://152.42.197.110/beneficiaries/trash |
| Batch Assessment | http://152.42.197.110/assessments/batch |
| For Follow-up | http://152.42.197.110/beneficiaries/followup |
| OPT Program | http://152.42.197.110/programs/opt |
| DSP Program | http://152.42.197.110/programs/dsp |
| MNS Program | http://152.42.197.110/programs/mns |
| Dispensing Tracker | http://152.42.197.110/dispensing |
| OPT Report | http://152.42.197.110/reports/opt |
| DSP Report | http://152.42.197.110/reports/dsp |
| MNS Report | http://152.42.197.110/reports/mns |
| Outcome Report | http://152.42.197.110/reports/outcome |
| Summary Report | http://152.42.197.110/reports/summary |
| Period Comparison | http://152.42.197.110/reports/comparison |
| Distribution Report | http://152.42.197.110/reports/distribution |
| eOPT Export | http://152.42.197.110/reports/export-eopt |
| Import | http://152.42.197.110/import |
| Import Storage | http://152.42.197.110/import/storage |
| Activity Log | http://152.42.197.110/activity |
| Program Manager | http://152.42.197.110/programs-admin |
| User Management | http://152.42.197.110/users |
| Demo Seeder | http://152.42.197.110/admin/seed |
| Validation Queue | http://152.42.197.110/validation/queue |

### Key File Locations (Production Server)

| File/Folder | Path |
|---|---|
| Environment config | `/var/www/nms/.env` |
| MySQL schema | `/var/www/nms/database/nms_mysql.sql` |
| Database backups | `/var/www/nms/database/backups/` |
| City logo | `/var/www/nms/public/img/logo.jpg` |
| Background image | `/var/www/nms/public/img/background.jpg` |
| Beneficiary photos | `/var/www/nms/storage/uploads/photos/` |
| Saved import files | `/var/www/nms/storage/imports/` |
| Other uploaded files | `/var/www/nms/storage/files/` |
| eOPT template | `/var/www/nms/storage/templates/eopt_slim.xlsx` |
| Nginx config | `/etc/nginx/sites-enabled/nms` |
| PHP-FPM config | `/etc/php/8.3/fpm/php.ini` |
| Nginx error log | `/var/log/nginx/error.log` |

### Key File Locations (Local Development — XAMPP)

| File/Folder | Path |
|---|---|
| Environment config | `C:\xampp\htdocs\nms\.env` |
| Database config | `C:\xampp\htdocs\nms\config\database.php` |
| City logo | `C:\xampp\htdocs\nms\public\img\logo.jpg` |
| Background image | `C:\xampp\htdocs\nms\public\img\background.jpg` |
| PHP config | `C:\xampp\php\php.ini` |
| Apache error log | `C:\xampp\apache\logs\error.log` |

---

*Document version: July 2026 — NMS v1.0*
