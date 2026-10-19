<?php $pageTitle = 'Report Distribution'; ?>

<div class="d-flex justify-content-between align-items-center mt-3 mb-2">
    <div>
        <h4 class="mb-0"><i class="bi bi-cloud-upload me-2 text-primary"></i>Report Distribution Center</h4>
        <p class="text-muted small mb-0 mt-1">Generate ready-to-use reports — print or upload to Google Drive</p>
    </div>
    <a href="<?= APP_URL ?>/reports" class="btn btn-sm btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i>Back to Reports
    </a>
</div>

<p class="text-muted small mb-3">
    <i class="bi bi-printer me-1"></i>Download as <strong>PDF</strong> to print hard copies &nbsp;·&nbsp;
    <i class="bi bi-file-earmark-excel me-1 text-success"></i>Download as <strong>Excel</strong> to upload to Google Drive for digital filing.
</p>


<!-- Report Cards -->
<div class="row g-3 mb-4">
    <!-- OPT Report -->
    <div class="col-md-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-success bg-opacity-10 border-0">
                <div class="d-flex align-items-center">
                    <i class="bi bi-clipboard-heart fs-3 text-success me-3"></i>
                    <div>
                        <div class="fw-bold">OPT Report</div>
                        <div class="small text-muted">Operation Timbang — Nutritional Status</div>
                    </div>
                </div>
            </div>
            <div class="card-body d-flex flex-column">
                <p class="small text-muted mb-3 flex-grow-1">Nutritional assessment records including WHO Z-scores, weight, height, and nutritional status classification. Filter by period, barangay, and source before exporting.</p>
                <a href="<?= APP_URL ?>/reports/opt" class="btn btn-sm btn-success">
                    <i class="bi bi-box-arrow-up-right me-1"></i>Go to OPT Report
                </a>
            </div>
        </div>
    </div>

    <!-- DSP Report -->
    <div class="col-md-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-warning bg-opacity-10 border-0">
                <div class="d-flex align-items-center">
                    <i class="bi bi-egg-fried fs-3 text-warning me-3"></i>
                    <div>
                        <div class="fw-bold">DSP Report</div>
                        <div class="small text-muted">Dietary Supplementation Program</div>
                    </div>
                </div>
            </div>
            <div class="card-body d-flex flex-column">
                <p class="small text-muted mb-3 flex-grow-1">Program enrollments, intervention types, pre/post weight tracking, and discharge records. Filter by year and barangay before exporting.</p>
                <a href="<?= APP_URL ?>/reports/dsp" class="btn btn-sm btn-warning">
                    <i class="bi bi-box-arrow-up-right me-1"></i>Go to DSP Report
                </a>
            </div>
        </div>
    </div>

    <!-- MNS Report -->
    <div class="col-md-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-primary bg-opacity-10 border-0">
                <div class="d-flex align-items-center">
                    <i class="bi bi-capsule fs-3 text-primary me-3"></i>
                    <div>
                        <div class="fw-bold">MNS Report</div>
                        <div class="small text-muted">Micronutrient Supplementation — Vitamin A</div>
                    </div>
                </div>
            </div>
            <div class="card-body d-flex flex-column">
                <p class="small text-muted mb-3 flex-grow-1">Vitamin A distribution records with dosage, capsule color, and coverage by barangay. Filter by round and barangay before exporting.</p>
                <a href="<?= APP_URL ?>/reports/mns" class="btn btn-sm btn-primary">
                    <i class="bi bi-box-arrow-up-right me-1"></i>Go to MNS Report
                </a>
            </div>
        </div>
    </div>

    <!-- Dispensing Report -->
    <div class="col-md-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-info bg-opacity-10 border-0">
                <div class="d-flex align-items-center">
                    <i class="bi bi-prescription2 fs-3 text-info me-3"></i>
                    <div>
                        <div class="fw-bold">Dispensing Report</div>
                        <div class="small text-muted">Medicine &amp; Supplement Dispensing Tracker</div>
                    </div>
                </div>
            </div>
            <div class="card-body d-flex flex-column">
                <p class="small text-muted mb-3 flex-grow-1">Complete dispensing history across all programs — RUSF, RUTF, Iron, Vitamin A, MNP, LNS-SQ, and more. Filter by program and supplement type before exporting.</p>
                <a href="<?= APP_URL ?>/dispensing" class="btn btn-sm btn-info text-white">
                    <i class="bi bi-box-arrow-up-right me-1"></i>Go to Dispensing Tracker
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Google Drive Upload Instructions (collapsible) -->
<div class="card border-0 shadow-sm">
    <div class="card-header fw-semibold d-flex justify-content-between align-items-center py-2" style="cursor:pointer"
         data-bs-toggle="collapse" data-bs-target="#gdriveTips" aria-expanded="false" aria-controls="gdriveTips">
        <span><i class="bi bi-google me-2 text-primary"></i>How to upload to Google Drive</span>
        <i class="bi bi-chevron-down" id="gdriveChev"></i>
    </div>
    <div class="collapse" id="gdriveTips">
        <div class="card-body">
            <ol class="small text-muted mb-2 ps-3">
                <li class="mb-1">Click <strong>Excel</strong> on any report above — the file downloads to your Downloads folder.</li>
                <li class="mb-1">Go to <strong>drive.google.com</strong> and sign in.</li>
                <li class="mb-1">Click <strong>+ New → File Upload</strong>, select the file, and click Open.</li>
                <li>Move it to the right folder (e.g., <code>NMS Reports / <?= date('Y') ?></code>) and share with supervisors.</li>
            </ol>
        </div>
    </div>
</div>
<script>
document.getElementById('gdriveTips').addEventListener('show.bs.collapse',  () => document.getElementById('gdriveChev').className = 'bi bi-chevron-up');
document.getElementById('gdriveTips').addEventListener('hide.bs.collapse', () => document.getElementById('gdriveChev').className = 'bi bi-chevron-down');
</script>
