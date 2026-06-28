# NMS Deployment Guide

## Prerequisites

- Windows with PHP 8.0+ (via XAMPP or standalone)
- Composer (https://getcomposer.org)
- A modern browser (Chrome 90+, Firefox 90+, Edge 90+)
- PHP extensions: `pdo_sqlite`, `sqlite3` (enabled by default in XAMPP)

---

## Step-by-Step Deployment

### 1. Place Project Files

Copy the `nms/` directory into your XAMPP `htdocs` folder:

```
C:\xampp\htdocs\nms\
```

### 2. Install Composer Dependencies

```bash
cd C:\xampp\htdocs\nms
composer install
```

This installs:
- `phpoffice/phpspreadsheet` — Excel import
- `dompdf/dompdf` — PDF export

> Note: The eOPT export uses direct ZIP + XML manipulation and does **not** require PhpSpreadsheet.

### 3. Initialize the Database

The SQLite database file should be at `database/nms.sqlite`.

- If deploying from a backup: place the `.sqlite` file there directly.
- If doing a fresh install: run the schema init script:

```bash
php database/init_sqlite.php
```

> `dispensing_records` is auto-created by the `DispensingRecord` model on first request — no extra migration needed.

### 4. Configure Environment

Edit `.env` in the project root:

```env
APP_NAME=NMS
APP_URL=http://127.0.0.1:3000
APP_ENV=production
```

For XAMPP Apache access:
```env
APP_URL=http://localhost/nms/public
```

For LAN access (BHWs on other devices):
```env
APP_URL=http://192.168.1.5/nms/public
```

### 5. (Optional) Configure Google Drive Import

To allow importing files directly from Google Drive:

1. Go to [https://console.cloud.google.com](https://console.cloud.google.com) and create a project
2. Enable **Google Drive API** and **Google Picker API**
3. Create an **API Key** and an **OAuth 2.0 Client ID** (Web application type)
4. Add your server URL to *Authorized JavaScript origins*
5. Add to `.env`:

```env
GOOGLE_API_KEY=your_api_key_here
GOOGLE_CLIENT_ID=your_client_id.apps.googleusercontent.com
```

Once configured, "From Google Drive" becomes active in Import and Storage Browser.

### 6. Add Branding Images

Create `public/img/` and place:

```
public/img/logo.jpg        ← City/LGU seal (circular, shown in navbar and login)
public/img/background.jpg  ← Background image (shown behind page content with overlay)
```

System works without these — falls back to text-only navbar and plain background.

### 7. Configure PHP Settings

Open `C:\xampp\php\php.ini` and set:

```ini
file_uploads = On
upload_max_filesize = 20M
post_max_size = 25M
max_execution_time = 120
memory_limit = 256M
```

Restart Apache after changes (not needed for built-in server — it re-reads php.ini on each request by default).

### 8. Verify Upload and Storage Directories

Ensure these exist and are writable:

```
public/uploads/              ← temporary Excel files during upload
public/img/                  ← branding images
storage/uploads/photos/      ← beneficiary photos
storage/imports/             ← saved beneficiary import files
storage/files/               ← general uploaded files (Other Files tab)
storage/templates/           ← Excel templates (eopt_slim.xlsx required for eOPT export)
```

On Windows/XAMPP they are writable by default. On Linux/Mac:

```bash
chmod 755 public/img storage/uploads storage/uploads/photos storage/imports storage/files
```

> `storage/` contains a `.htaccess` (Deny from all). Files are served only through controller endpoints.

### 9. Start the System

**Recommended — PHP built-in server:**

Double-click `start.bat`, or run:

```bash
php -S 127.0.0.1:3000 -t public
```

**XAMPP Apache (for LAN access or multi-user):**

1. Enable `mod_rewrite` in `httpd.conf` (uncomment `LoadModule rewrite_module`)
2. Set `AllowOverride All` in the `<Directory "C:/xampp/htdocs">` block
3. Start Apache in XAMPP Control Panel

### 10. First Login

Navigate to your configured URL (e.g., `http://127.0.0.1:3000`):

- **Username:** `admin`
- **Password:** `Admin@1234`

> **Security:** Change the admin password immediately after first login via Admin → User Management.

### 11. (Optional) Seed Demo Data

After logging in, go to **Admin → Demo Seeder** (`/admin/seed`) and click **Seed Demo Data** to insert ~30 realistic beneficiaries with assessments, DSP enrollments, and Vitamin A records for testing.

Click **Clear Demo Data** when done testing — only demo records are removed; real data is untouched.

---

## Verification Checklist

- [ ] App loads at configured URL and redirects to login
- [ ] Login with `admin` / `Admin@1234` succeeds
- [ ] Logo and background appear correctly
- [ ] Dashboard loads with stat cards and charts
- [ ] Add a beneficiary → profile shows all sections including completeness indicator
- [ ] Record an assessment → Z-score and nutritional status computed
- [ ] Growth chart appears on profile (shows from 1 assessment; toggle Weight/Height)
- [ ] Duplicate check fires on add/edit beneficiary form
- [ ] Delete a beneficiary → appears in Trash; Restore works
- [ ] **OPT** → Manual Enroll modal works
- [ ] **DSP** → Eligible list shows UW/SUW children; Manual Enrollment works
- [ ] **DSP** → Complete with post-weight → new assessment auto-created
- [ ] **MNS / Vitamin A** → Eligible list shows uncovered children; Record → appears in Dispensing Tracker
- [ ] **MNS / MNP** → "Not Yet Received" list shows eligible children; Record pre-fills modal; saved record moves to "Records Given"
- [ ] **MNS / LNS-SQ** → same as MNP above
- [ ] MNP/LNS-SQ records appear in Beneficiary Profile
- [ ] Dispensing Tracker → MNS records auto-appear; manual record works
- [ ] Import → upload `.xlsx` → preview → folder → confirm → records imported
- [ ] Storage Browser → Beneficiary Imports and Other Files tabs work
- [ ] Reports → OPT / DSP / MNS / Outcome / Summary / Comparison → load with data
- [ ] CSV / Excel / PDF export works for at least one report
- [ ] eOPT Export → select year/period → downloads a valid `.xlsx` file with all sheets populated
- [ ] Activity Log shows data entry actions
- [ ] Demo Seeder → Seed and Clear both work correctly
- [ ] Print Card → browser print dialog opens with compact card layout

---

## Troubleshooting

| Issue | Solution |
|-------|----------|
| 404 on all pages (Apache) | Enable `mod_rewrite` and set `AllowOverride All` |
| Blank page | Set `APP_ENV=development` in `.env`; check terminal/error log |
| SQLite error: database not found | Ensure `database/nms.sqlite` exists |
| Z-score shows null | Ensure `who_growth_standards` table is populated (run schema init) |
| Import fails | Check `upload_max_filesize` in `php.ini`; ensure `public/uploads/` is writable |
| Excel/PDF export fails | Run `composer install` |
| Photo upload fails | Ensure `storage/uploads/photos/` exists and is writable |
| Dispensing FK error | Check `dispensing_records` DDL — FK must reference `program_enrollments`, not `program_enrollments_tmp` |
| MNP/LNS-SQ child missing from list | Verify child is 6–23 months old today and has no record for this year |
| Growth chart not showing | Verify at least 1 assessment exists; check browser console for JS errors |
| Logo/background not showing | Place files in `public/img/`; hard refresh (`Ctrl+Shift+R`) |
| BHW sees no beneficiaries | Set barangay in User Management — must match beneficiary records exactly |
| eOPT export — template not found | Place `eopt_slim.xlsx` in `storage/templates/` |
| eOPT export — sheets show 0 values | Ensure assessments exist for the selected year/period and barangay; `calcChain.xml` is deleted automatically so Excel will recalculate on open |
