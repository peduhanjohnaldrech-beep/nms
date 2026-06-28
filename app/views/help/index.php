<?php $pageTitle = 'Help & User Guide'; ?>

<div class="d-flex justify-content-between align-items-center my-3">
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
                <p>The <strong>Nutrition Monitoring System (NMS)</strong> is an offline, desktop-based system designed for the City Health Nutrition Department to manage child beneficiary records, conduct nutritional assessments, monitor program enrollments, and generate reports.</p>
                <p class="mb-0">The system operates fully offline — no internet connection is required for day-to-day use. All data is stored locally in a secure SQLite database file on the computer.</p>
            </div>
        </div>

        <!-- Login -->
        <div class="card border-0 shadow-sm mb-4" id="login">
            <div class="card-body">
                <h5 class="fw-bold text-primary"><i class="bi bi-box-arrow-in-right me-2"></i>Logging In</h5>
                <ol class="mb-0">
                    <li>Double-click <strong>start.bat</strong> to launch the system server.</li>
                    <li>A browser window will open automatically at <code>http://127.0.0.1:3000</code>.</li>
                    <li>Enter your <strong>Username</strong> and <strong>Password</strong>, then click <strong>Sign In</strong>.</li>
                    <li>To close the system, double-click <strong>stop.bat</strong>.</li>
                </ol>
                <div class="alert alert-warning mt-3 mb-0 py-2">
                    <i class="bi bi-exclamation-triangle me-1"></i>
                    Never share your password. The system logs all login activity.
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
                <p>Tracks enrollment in nutrition intervention programs.</p>
                <ul>
                    <li><strong>OPT (Operation Timbang)</strong> — weight monitoring program. Enroll beneficiaries and track admission/discharge weight.</li>
                    <li><strong>DSP (Dietary Supplementation Program)</strong> — supplemental feeding. Records feeding sessions and food packets.</li>
                    <li><strong>MNS (Micronutrient Supplementation)</strong> — tracks Vitamin A, MNP, and LNS-SQ distributions.</li>
                </ul>
                <p class="mb-0">To enroll a beneficiary: click <em>Enroll</em> on the program page and search for the beneficiary. To discharge: click <em>Discharge</em> on the enrolled record.</p>
            </div>
        </div>

        <!-- Reports -->
        <div class="card border-0 shadow-sm mb-4" id="reports">
            <div class="card-body">
                <h5 class="fw-bold text-primary"><i class="bi bi-bar-chart-fill me-2"></i>Reports</h5>
                <ul>
                    <li><strong>OPT Reports</strong> — summary tables of nutritional status by barangay and age group for a selected quarter and year. Exportable to Excel.</li>
                    <li><strong>Outcome Report</strong> — shows improvement or deterioration of nutritional status for enrolled beneficiaries between two assessment periods.</li>
                    <li><strong>Report Distribution</strong> — upload and store completed report files (PDF, Excel) for record-keeping.</li>
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
                <p>The system stores all data in a single file: <code>database/nms.sqlite</code> inside the NMS folder.</p>
                <p><strong>To back up your data:</strong></p>
                <ol>
                    <li>Go to the <strong>Dashboard</strong> and click the <strong><i class="bi bi-database-down"></i> Backup Database</strong> button (Admin only).</li>
                    <li>A copy of the database file will be downloaded to your computer.</li>
                    <li>Save the downloaded file to a USB drive or external storage.</li>
                </ol>
                <div class="alert alert-warning mb-0 py-2">
                    <i class="bi bi-exclamation-triangle me-1"></i>
                    <strong>Recommended:</strong> Perform a backup at least once a week, especially after bulk data entry or imports.
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
