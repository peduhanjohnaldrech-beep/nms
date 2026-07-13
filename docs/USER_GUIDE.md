# NMS User Guide

## Role Permissions

| Feature | Admin | Nutritionist | Encoder | BNS | BHW | Midwife |
|---------|:-----:|:------------:|:-------:|:---:|:---:|:-------:|
| Dashboard | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ |
| View Beneficiaries (all barangays) | ✓ | ✓ | ✓ | — | — | — |
| View Beneficiaries (own barangay) | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ |
| Add Beneficiary | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ |
| Edit Beneficiary | ✓ | ✓ | ✓ | — | — | — |
| Delete Beneficiary (soft) | ✓ | ✓ | — | — | — | — |
| Restore Deleted Beneficiary | ✓ | ✓ | — | — | — | — |
| For Follow-up List | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ |
| Record Assessment | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ |
| Delete Assessment | ✓ | ✓ | — | — | — | — |
| Batch Assessment | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ |
| OPT Module (view) | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ |
| OPT Manual Enroll | ✓ | ✓ | ✓ | — | — | — |
| OPT Remove Enrollment | ✓ | ✓ | — | — | — | — |
| DSP Module (view) | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ |
| DSP Enroll | ✓ | ✓ | ✓ | — | — | — |
| DSP Discharge / Drop | ✓ | ✓ | — | — | — | — |
| MNS / Vitamin A | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ |
| MNS / MNP | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ |
| MNS / LNS-SQ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ |
| Custom Programs (view/enroll) | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ |
| Custom Programs (discharge) | ✓ | ✓ | — | — | — | — |
| Programs Admin (view) | ✓ | ✓ | — | — | — | — |
| Programs Admin (create/edit/toggle) | ✓ | — | — | — | — | — |
| Dispensing Tracker (view/add) | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ |
| Import from Excel | ✓ | ✓ | — | — | — | — |
| Import Storage Browser | ✓ | ✓ | — | — | — | — |
| Create / Delete Import Folders | ✓ | — | — | — | — | — |
| Reports (all) | ✓ | ✓ | ✓ | ✓* | ✓* | ✓* |
| Export CSV / Excel / PDF | ✓ | ✓ | ✓ | ✓* | ✓* | ✓* |
| Summary Report | ✓ | ✓ | ✓ | ✓* | ✓* | ✓* |
| Period Comparison Report | ✓ | ✓ | ✓ | ✓* | ✓* | ✓* |
| Activity Log | ✓ | ✓ | — | — | — | — |
| User Management | ✓ | — | — | — | — | — |
| Database Backup | ✓ | — | — | — | — | — |
| Demo Seeder | ✓ | — | — | — | — | — |

*Scoped to their assigned barangay.

---

## Module Guide

### Dashboard

Displays summary statistics and charts for the current year:
- **Total Beneficiaries** — all active records
- **OPT Assessed** — children weighed in the current year
- **Active DSP** — children currently in the feeding program
- **MNS Coverage** — children who received Vitamin A this year
- **For Follow-up** — children whose status has worsened since last assessment
- **Not Yet Assessed** — children not yet weighed for the current OPT period (January or July)

Charts:
- Nutritional status by barangay (stacked bar)
- Program enrollment breakdown (donut)
- OPT trend over past 2 years (line)
- Malnutrition rate by barangay (bar)

---

### Beneficiary Management

**Adding a Beneficiary:**
1. Go to **Beneficiaries → Add Beneficiary**
2. Fill in Personal, Address, Guardian, and Socioeconomic sections
3. Required fields: Last Name, First Name, Date of Birth, Sex, Barangay
4. Click **Save Beneficiary**

> A duplicate check runs automatically as you type — a yellow warning appears if a matching record already exists.

**Search/Filter:**
- Search by name; filter by barangay, source, and age status
- **Age Status filter:** All Ages / Active (0–59 months) / Aged Out (>59 months)
- **Last Assessed column:** color-coded — green < 90 days, yellow < 180, red > 180, "Never" for no assessments
- Children over 59 months show an **Aged Out** badge

**Trash & Restore:**
- Deleted beneficiaries go to the Trash page (`/beneficiaries/trash`)
- Admin and Nutritionist can restore them with the Restore button

**Beneficiary Profile:**
- **Data completeness indicator** — progress bar and checklist of 9 key fields
- **Growth chart** — plots weight or height against WHO reference lines (-3SD, -2SD, median); toggle Weight/Height; shows with 1+ assessment
- **Print card** — click Print Card to print a compact summary card; all UI is hidden automatically
- All records in one view: assessments, program enrollments, Vitamin A, MNP/LNS-SQ, dispensing history

---

### Assessment Module (OPT)

**Recording an Assessment:**
1. Open a beneficiary's profile or go to **New Assessment**
2. Enter date, weight (required), height, MUAC (optional)
3. Click **Save Assessment**

The system automatically:
- Computes age in months from DOB and assessment date
- Determines the OPT period (January or July)
- Computes WFA, HFA, and WFL/H Z-scores using WHO LMS values
- Assigns nutritional status (SUW / UW / Normal / OW / OB)
- Flags the child for DSP if status is UW or SUW

> **Aged-out warning:** If the beneficiary is over 59 months, the assessment form shows a yellow warning. The form still works — it's a warning only.

**Batch Assessment:**
Go to **Assessments → Batch Assessment** to record for multiple children at once, filtered by barangay and period.

---

### OPT (Operation Timbang)

Shows all beneficiaries enrolled in the OPT weighing program.

**Manually Enrolling:**
1. Go to **Programs → OPT**
2. Click **Enroll Beneficiary**
3. Select beneficiary, period (January/July), year
4. Click **Enroll**

---

### DSP (Dietary Supplementation Program)

**Eligible List:**
Children whose latest assessment is UW, SUW, SW, or MW and not yet in an active DSP cycle. The system auto-recommends an intervention type.

**Enrolling:**
Click **Enroll** next to an eligible child, or use **Manual Enrollment** for any beneficiary regardless of status.

**Discharge:**
- **Complete** — enter post-weight; the system auto-creates a new assessment using the post-weight and last known height, recalculating eligibility
- **Drop** — mark as dropped (moved away, refused, etc.)

> If the child recovers (Normal status after completion), they will not reappear in the eligible list. If still malnourished, they reappear for a new cycle.

---

### MNS (Micronutrient Supplementation)

Three tabs. All recordings automatically create a Dispensing Tracker entry.

#### Vitamin A

Eligible children: **6–59 months**, not yet covered for the selected round/year.

- Filter by **Round** (February / August) and **Year**
- Click **Record** next to a child in the eligible list, or use **Record Vitamin A** (top-right) for any beneficiary
- Dosage is auto-assigned: 6–11 months → 100,000 IU (Blue); 12–59 months → 200,000 IU (Red)
- Right panel shows coverage by barangay

#### MNP (Micronutrient Powder)

For children **6–23 months**. A powdered sachet mixed into soft food.

Two panels side by side:
- **Not Yet Received** — children 6–23 months who have no MNP record this year; click **Record** to pre-fill the modal with their name and age group
- **Records Given** — all individual records for the year (name, barangay, age group, date, routine completed yes/no)

**Adding a record:**
1. Click **Record** next to a child (or **Add MNP Record** for any beneficiary)
2. Confirm/adjust date and age group (6–11 months or 12–23 months)
3. Check **Routine completed** if the child finished the full course
4. Click **Save**

#### LNS-SQ (Lipid-based Nutrient Supplement – Small Quantity)

Same layout as MNP — "Not Yet Received" + "Records Given" panels. For children **6–23 months**.

> MNP, LNS-SQ, and Vitamin A are separate interventions. A child can receive all three — they are not mutually exclusive.

MNP and LNS-SQ records also appear in the **Beneficiary Profile** under "MNP / LNS-SQ Records".

---

### Programs Admin *(Admin: create/edit — Nutritionist: view only)*

**Creating a Custom Program** *(Admin only):*
1. Go to **Admin → Program Manager → Add Program**
2. Fill in: Code (unique uppercase, e.g. `IRON`), Name, Description
3. Choose an icon (Bootstrap Icon class) and color
4. Set a sort order
5. Click **Save**

Custom programs appear at `/programs/{code}` with basic enrollment tracking (Active / Completed / Dropped).

**Toggling active status:** Click the play/pause button next to a program. Inactive programs are hidden from navigation; enrollment data is preserved.

---

### Dispensing Tracker

Tracks all medicines and supplements dispensed across all programs.

**Auto-created when:**
- A Vitamin A, MNP, or LNS-SQ record is saved in MNS

**Manual recording:**
Click **Record Dispensing** to manually add any item (iron supplements, deworming, etc.).

**Filters:** Year, Program, Barangay (BHW users see only their barangay)

**Export:** Excel or PDF via the buttons in the filter bar.

---

### Import from Excel *(Admin and Nutritionist only)*

1. Go to **Import**
2. Upload an `.xlsx` / `.xls` file (device, Google Drive, or URL)
3. Review the color-coded preview: Green = new, Yellow = update, Red = error
4. Optionally select a folder
5. Click **Confirm Import**

**Storage Browser** (`/import/storage`):
- **Beneficiary Imports tab** — browse, download, delete saved import files by folder; in-browser preview
- **Other Files tab** — upload, browse, download, delete general files; create/delete folders (Admin only)

---

### Reports

All roles can view and export reports. BHW users see only their assigned barangay.

| Report | Description |
|--------|-------------|
| **OPT Report** | Assessments by status, year, period, barangay |
| **DSP Report** | Enrollments and outcomes by year, barangay |
| **MNS Report** | Vitamin A/MNP/LNS-SQ coverage by round, year, barangay |
| **Outcome Report** | DSP pre/post weight comparison |
| **Summary Report** | Per-barangay totals: coverage %, malnutrition rate, DSP active, Vitamin A |
| **Period Comparison** | January vs July OPT malnutrition rate per barangay with trend chart |
| **Distribution Report** | Supplement dispensing summary by type across all programs |
| **eOPT Export** | Full eOPT Plus Community Level Tool workbook (.xlsx) |

Export formats: **CSV**, **Excel (.xlsx)**, **PDF**

---

### eOPT Export *(Admin and Nutritionist only)*

Generates a fully-populated eOPT Plus Community Level Tool `.xlsx` workbook for submission to the municipal level.

**How to export:**
1. Go to **Reports → eOPT Export** (or use the Export eOPT button on the OPT report page)
2. Select **Year**, **Period** (January / July), and optionally filter by **Barangay**
3. Click **Export eOPT**

**Sheets populated:**

| Sheet | Contents |
|-------|----------|
| **Summary** | WFA/HFA/WFLH counts by age group and sex; F1K (First 1000 Days) columns; grand totals; barangay/municipality/province/region header |
| **OPT_Form1A** | Per-age-group breakdown (WFA, HFA, WFLH) with IP and year header cells |
| **OPT_Form1B** | Individual child list with nutritional status codes |
| **List_UW / List_SUW / List_St / List_SSt / List_MW / List_SW** | Filtered lists of children per classification |
| **BNS_Printout** | Child roster with DOB, Date Last Measured, weight, height, age in months |
| **Nut_StatusTool** | Full child data rows (columns A–N) for Nut Status tool calculations |
| **Clean&Update** | Auto-populated from BNS_Printout via Excel formulas — no manual entry needed |
| **Data-Export** | Row 3 filled with 156 per-age/sex/status breakdown values for municipal aggregation |

> **Requirement:** The template file `storage/templates/eopt_slim.xlsx` must be present. Contact the system administrator if the export button shows a template-not-found error.

---

### Activity Log *(Admin and Nutritionist only)*

Go to **Admin → Activity Log** for a timestamped audit trail of all data actions.

---

### User Management *(Admin only)*

**Adding a User:**
1. Go to **Admin → User Management → Add User**
2. Fill in full name, username, password, role, assigned barangay (required for BHW)
3. Click **Save User**

**Roles:**
- **Admin** — full system access including user management, demo seeder, programs admin, and database backup
- **Nutritionist** — full access except user management; validates mobile submissions; can import and discharge DSP
- **Encoder** — data entry; can add/edit beneficiaries and record assessments; cannot import, discharge, or delete
- **BNS** — Barangay Nutrition Scholar; same access as BHW, restricted to assigned barangay
- **BHW** — Barangay Health Worker; restricted to assigned barangay; can view, record assessments, and record supplementation
- **Midwife** — restricted to assigned barangay; can record and validate assessments

---

### Demo Seeder *(Admin only)*

Go to **Admin → Demo Seeder** (`/admin/seed`).

- **Seed Demo Data** — inserts ~30 realistic beneficiaries across 3 sample barangays (Mabuhay, Masagana, Maliwanag) with assessments, DSP enrollments, and Vitamin A records. All tagged `source = 'Demo'`.
- **Clear Demo Data** — removes all records tagged as Demo. Real data (Walk-in, Excel, Google) is never touched.

---

## Tips

- **Duplicate check:** When adding or editing a beneficiary, a yellow warning appears if a matching record (same name + DOB) already exists. You can still save — it's a warning, not a block.
- **Aged-out children:** Children over 59 months show an "Aged Out" badge. They remain in the system for historical records but can be filtered out using the Age Status filter.
- **DSP re-eligibility:** Always enter the post-weight when completing DSP so the system can auto-assess recovery. Without a post-weight, no new assessment is created and eligibility is unchanged.
- **MNS → Dispensing:** Vitamin A, MNP, and LNS-SQ recordings automatically appear in the Dispensing Tracker — no separate entry needed.
- **Growth chart:** Shows from the first assessment. Toggle between Weight and Height using the buttons above the chart.
- **Print card:** On any beneficiary profile, click **Print Card** to open a browser print dialog with a compact summary card. All UI elements hide automatically.
- **BHW barangay lock:** BHW users cannot see data outside their assigned barangay. Verify the user's barangay setting in User Management if data appears missing.
- **Vitamin A eligibility:** Uses today's age (6–59 months) and round/year. If a child doesn't appear in the eligible list, use the manual "Record Vitamin A" button.
- **MNP/LNS-SQ eligibility:** Uses today's age (6–23 months). Children age out of the list automatically each year.
