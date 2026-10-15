<?php $pageTitle = 'Help & User Guide'; ?>

<div class="d-flex justify-content-between align-items-center mt-3 mb-2">
    <h4 class="mb-0"><i class="bi bi-question-circle me-2"></i>Help &amp; User Guide</h4>
</div>

<div class="row g-4">
    <!-- Quick navigation -->
    <div class="col-lg-3">
        <div class="card border-0 shadow-sm">
            <div class="card-body p-3">
                <h6 class="fw-bold text-muted text-uppercase small mb-3">Contents</h6>
                <nav class="nav flex-column gap-1">
                    <a class="nav-link py-1 px-2 rounded" href="#overview">Overview</a>
                    <a class="nav-link py-1 px-2 rounded" href="#login">Logging In</a>
                    <a class="nav-link py-1 px-2 rounded" href="#dashboard">Dashboard</a>
                    <a class="nav-link py-1 px-2 rounded" href="#beneficiaries">Beneficiaries</a>
                    <a class="nav-link py-1 px-2 rounded" href="#assessments">Assessments</a>
                    <a class="nav-link py-1 px-2 rounded" href="#programs">Programs</a>
                    <a class="nav-link py-1 px-2 rounded" href="#reports">Reports</a>
                    <a class="nav-link py-1 px-2 rounded" href="#dispensing">Dispensing Tracker</a>
                    <a class="nav-link py-1 px-2 rounded" href="#import">Import</a>
                    <a class="nav-link py-1 px-2 rounded" href="#admin">Admin Panel</a>
                    <a class="nav-link py-1 px-2 rounded" href="#backup">Backup</a>
                    <a class="nav-link py-1 px-2 rounded" href="#mobile">Mobile App</a>
                    <a class="nav-link py-1 px-2 rounded" href="#privacy">Data Privacy</a>
                </nav>
            </div>
        </div>
    </div>

    <!-- Content -->
    <div class="col-lg-9">

        <!-- Overview -->
        <div class="card border-0 shadow-sm mb-4" id="overview">
            <div class="card-body">
                <h5 class="fw-bold text-primary"><i class="bi bi-info-circle me-2"></i>System Overview</h5>
                <p>The <strong>Nutrition Monitoring System (NMS)</strong> is a cloud-based system developed for the City Health Nutrition Department of Kabankalan City to manage child beneficiary records, conduct nutritional assessments, monitor program enrollments, and generate reports.</p>
                <p>The system consists of two components:</p>
                <ul class="mb-0">
                    <li><strong>Web System</strong> — accessible via any browser at <a href="https://kabnms.duckdns.org" target="_blank">https://kabnms.duckdns.org</a>. Used by the Nutritionist and Admin for program management, validation, and report generation.</li>
                    <li><strong>Mobile App (NMS Mobile)</strong> — an Android application used by BNS, BHW, and Midwife for field data collection. Supports offline data entry and automatically syncs to the server when internet is available.</li>
                </ul>
            </div>
        </div>

        <!-- Login -->
        <div class="card border-0 shadow-sm mb-4" id="login">
            <div class="card-body">
                <h5 class="fw-bold text-primary"><i class="bi bi-box-arrow-in-right me-2"></i>Logging In</h5>
                <p class="mb-2"><strong>Web System:</strong></p>
                <ol>
                    <li>Open a browser and go to <a href="https://kabnms.duckdns.org" target="_blank">https://kabnms.duckdns.org</a>.</li>
                    <li>Enter your <strong>Username</strong> and <strong>Password</strong>, then click <strong>Sign In</strong>.</li>
                    <li>Your session will automatically expire after <strong>1 hour of inactivity</strong> for security.</li>
                </ol>
                <p class="mb-2"><strong>Mobile App:</strong></p>
                <ol class="mb-0">
                    <li>Open the <strong>NMS Mobile</strong> app on your Android device.</li>
                    <li>Enter your assigned username and password, then tap <strong>Sign In</strong>.</li>
                    <li>Your login session lasts <strong>30 days</strong> before re-authentication is required.</li>
                </ol>
                <div class="alert alert-warning mt-3 mb-0 py-2">
                    <i class="bi bi-exclamation-triangle me-1"></i>
                    Never share your password. The system logs all login activity. BNS, BHW, and Midwife accounts can only log in via the mobile app.
                </div>
            </div>
        </div>

        <!-- Dashboard -->
        <div class="card border-0 shadow-sm mb-4" id="dashboard">
            <div class="card-body">
                <h5 class="fw-bold text-primary"><i class="bi bi-speedometer2 me-2"></i>Dashboard</h5>
                <p>The dashboard shows a real-time summary of the current year's data:</p>
                <ul class="mb-0">
                    <li><strong>Total Beneficiaries</strong> — all registered children in the system.</li>
                    <li><strong>OPT Assessed</strong> — children with a weight/height assessment this year.</li>
                    <li><strong>Malnourished</strong> — children flagged as SUW or UW.</li>
                    <li><strong>For Follow-up</strong> — beneficiaries pending a follow-up assessment.</li>
                    <li>Charts show nutritional status distribution and program enrollment counts.</li>
                </ul>
            </div>
        </div>

        <!-- Beneficiaries -->
        <div class="card border-0 shadow-sm mb-4" id="beneficiaries">
            <div class="card-body">
                <h5 class="fw-bold text-primary"><i class="bi bi-people-fill me-2"></i>Beneficiaries</h5>
                <p>Manages the master list of child beneficiaries.</p>
                <ul>
                    <li><strong>Add Beneficiary</strong> — click <em>Add Beneficiary</em> and fill in the required fields (name, date of birth, address, guardian).</li>
                    <li><strong>Search / Filter</strong> — use the search bar or barangay filter to find records quickly.</li>
                    <li><strong>Edit</strong> — click the pencil icon on any row to update a beneficiary's information.</li>
                    <li><strong>View Profile</strong> — opens the full profile with assessment history and program enrollments.</li>
                    <li><strong>For Follow-up</strong> — lists children who need a follow-up based on their last assessment date.</li>
                </ul>
                <p class="mb-0 text-muted small">Fields marked with <span class="text-danger">*</span> are required.</p>
            </div>
        </div>

        <!-- Assessments -->
        <div class="card border-0 shadow-sm mb-4" id="assessments">
            <div class="card-body">
                <h5 class="fw-bold text-primary"><i class="bi bi-clipboard2-pulse me-2"></i>Assessments</h5>
                <p>Records weight and height measurements to determine nutritional status using WHO standards.</p>
                <ul>
                    <li><strong>New Assessment</strong> — select a beneficiary and enter weight (kg) and height (cm). The system automatically computes age in months, WAZ, HAZ, WHZ, and assigns a nutritional status.</li>
                    <li><strong>Batch Assessment</strong> — record multiple assessments at once for a barangay or group.</li>
                    <li><strong>Status Classifications:</strong>
                        <ul>
                            <li><span class="badge bg-danger">SUW</span> Severely Underweight</li>
                            <li><span class="badge bg-warning text-dark">UW</span> Underweight</li>
                            <li><span class="badge bg-success">Normal</span> Normal</li>
                            <li><span class="badge bg-info text-dark">OW</span> Overweight</li>
                            <li><span class="badge bg-dark">OB</span> Obese</li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>

        <!-- Programs -->
        <div class="card border-0 shadow-sm mb-4" id="programs">
            <div class="card-body">
                <h5 class="fw-bold text-primary"><i class="bi bi-grid me-2"></i>Programs</h5>
                <p>Tracks nutrition intervention programs for eligible child beneficiaries.</p>
                <ul>
                    <li><strong>OPT Plus (Operation Timbang)</strong> — semi-annual weighing program (January and July). Tracks nutritional status coverage per barangay and identifies children not yet weighed.</li>
                    <li><strong>DSP (Dietary Supplementation Program)</strong> — manages enrollment of malnourished children (SUW/UW) into feeding intervention cycles. Tracks monitoring visits, weight progress, and discharge status. Enrollment is <strong>manual</strong> — not all eligible children are enrolled due to budget constraints.</li>
                    <li><strong>MNS (Micronutrient Supplementation)</strong> — tracks Vitamin A (6–59 months), MNP, and LNS-SQ (6–23 months) distributions by barangay and round.</li>
                </ul>
                <p class="mb-0">To enroll a beneficiary in DSP: click <em>Enroll</em> on the DSP page. To discharge: click <em>Discharge</em> on the enrolled record once graduation criteria are met (2 consecutive normal validated checks).</p>
            </div>
        </div>

        <!-- Reports -->
        <div class="card border-0 shadow-sm mb-4" id="reports">
            <div class="card-body">
                <h5 class="fw-bold text-primary"><i class="bi bi-bar-chart-fill me-2"></i>Reports</h5>
                <ul class="mb-0">
                    <li><strong>OPT Report</strong> — nutritional status summary by barangay and age group for a selected period and year. Exportable to Excel.</li>
                    <li><strong>DSP Report</strong> — list of enrolled, graduated, and dropped beneficiaries with intervention details.</li>
                    <li><strong>MNS Report</strong> — Vitamin A, MNP, and LNS-SQ coverage summary.</li>
                    <li><strong>Outcome Report</strong> — pre vs. post weight comparison for DSP enrollees showing nutritional improvement.</li>
                    <li><strong>Summary Report</strong> — per-barangay coverage rates, malnutrition rates, DSP and Vitamin A summary.</li>
                    <li><strong>Period Comparison</strong> — January vs. July OPT side-by-side showing change in malnutrition rate per barangay.</li>
                    <li><strong>Report Distribution</strong> — upload and store finalized report files (PDF, Excel) for record-keeping.</li>
                </ul>
            </div>
        </div>

        <!-- Dispensing -->
        <div class="card border-0 shadow-sm mb-4" id="dispensing">
            <div class="card-body">
                <h5 class="fw-bold text-primary"><i class="bi bi-prescription2 me-2"></i>Dispensing Tracker</h5>
                <p>Records the dispensing of medicines, supplements, or supplies to beneficiaries.</p>
                <ul class="mb-0">
                    <li>Click <strong>Add Record</strong> to log a dispensing event — select beneficiary, item, quantity, and date.</li>
                    <li>Use the export button to download dispensing records as Excel.</li>
                </ul>
            </div>
        </div>

        <!-- Import -->
        <div class="card border-0 shadow-sm mb-4" id="import">
            <div class="card-body">
                <h5 class="fw-bold text-primary"><i class="bi bi-file-earmark-excel me-2"></i>Import</h5>
                <p>Bulk-imports beneficiary records from an Excel file (.xlsx).</p>
                <ol>
                    <li>Download the <strong>Excel Template</strong> from the Import page.</li>
                    <li>Fill in the template with beneficiary data. Do not change the column headers.</li>
                    <li>Upload the filled template and click <strong>Confirm Import</strong> to save records.</li>
                </ol>
                <div class="alert alert-info mb-0 py-2">
                    <i class="bi bi-info-circle me-1"></i>
                    The import page also includes a <strong>File Storage</strong> section where you can upload and organize documents (PDF, images, Excel) into folders.
                </div>
            </div>
        </div>

        <!-- Admin -->
        <div class="card border-0 shadow-sm mb-4" id="admin">
            <div class="card-body">
                <h5 class="fw-bold text-primary"><i class="bi bi-gear-fill me-2"></i>Admin Panel</h5>
                <p>Accessible only to the <span class="badge bg-danger">Admin</span> account.</p>
                <ul>
                    <li><strong>User Management</strong> — add, edit, or deactivate user accounts. Assign a custom role label and select which modules each user can access.</li>
                    <li><strong>Program Manager</strong> — add or deactivate nutrition programs shown in the sidebar.</li>
                    <li><strong>Activity Log</strong> — a full audit trail of all actions performed in the system (logins, data changes, imports, backups).</li>
                </ul>
            </div>
        </div>

        <!-- Backup -->
        <div class="card border-0 shadow-sm mb-4" id="backup">
            <div class="card-body">
                <h5 class="fw-bold text-primary"><i class="bi bi-database-down me-2"></i>Backup</h5>
                <p>The system data is stored in a MySQL database on the cloud server (DigitalOcean). Regular backups are strongly recommended.</p>
                <p><strong>To back up your data:</strong></p>
                <ol>
                    <li>Go to the <strong>Dashboard</strong> and click the <strong><i class="bi bi-database-down"></i> Backup Database</strong> button (Admin only).</li>
                    <li>A SQL dump file will be downloaded to your computer.</li>
                    <li>Save the downloaded file to a USB drive or external storage.</li>
                </ol>
                <div class="alert alert-warning mb-0 py-2">
                    <i class="bi bi-exclamation-triangle me-1"></i>
                    <strong>Recommended:</strong> Perform a backup at least once a week, especially after bulk data entry or imports.
                </div>
            </div>
        </div>

        <!-- Mobile App -->
        <div class="card border-0 shadow-sm mb-4" id="mobile">
            <div class="card-body">
                <h5 class="fw-bold text-primary"><i class="bi bi-phone me-2"></i>Mobile App (NMS Mobile)</h5>
                <p>The NMS Mobile app is for <strong>BNS, BHW, and Midwife</strong> field workers. It is not available on the web system.</p>
                <ul>
                    <li><strong>Beneficiary Registration</strong> — register new child beneficiaries in the field. Submissions are sent to the Nutritionist for validation.</li>
                    <li><strong>OPT Batch Weighing</strong> — record weight, height, and MUAC for multiple children at once. Submitted records appear in the web system after validation.</li>
                    <li><strong>DSP Follow-up</strong> — submit monitoring follow-up records for DSP-enrolled children.</li>
                    <li><strong>My Submissions</strong> — view the status of all your submitted records (Pending, Validated, Returned).</li>
                    <li><strong>Offline Mode</strong> — data can be entered without internet and will sync automatically when a connection is available.</li>
                </ul>
                <div class="alert alert-info mb-0 py-2">
                    <i class="bi bi-info-circle me-1"></i>
                    Mobile accounts are app-only — they cannot log in to the web system.
                </div>
            </div>
        </div>

        <!-- Privacy -->
        <div class="card border-0 shadow-sm mb-4" id="privacy">
            <div class="card-body">
                <h5 class="fw-bold text-primary"><i class="bi bi-shield-lock me-2"></i>Data Privacy</h5>
                <p>This system processes personal data of children and their guardians in compliance with <strong>Republic Act No. 10173</strong> — the <em>Data Privacy Act of 2012</em>.</p>
                <ul class="mb-0">
                    <li>Access is restricted to authorized personnel only.</li>
                    <li>Each user account is assigned specific module permissions by the administrator.</li>
                    <li>All system actions are recorded in the Activity Log for accountability.</li>
                    <li>Do not share login credentials or leave the system unattended while logged in.</li>
                    <li>Back up data regularly and store backup files securely.</li>
                </ul>
            </div>
        </div>

    </div><!-- col -->
</div><!-- row -->
