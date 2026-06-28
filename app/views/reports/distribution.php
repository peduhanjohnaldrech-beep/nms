<?php $pageTitle = 'Report Distribution'; ?>

<div class="d-flex justify-content-between align-items-center my-3">
    <div>
        <h4 class="mb-0"><i class="bi bi-cloud-upload me-2 text-primary"></i>Report Distribution Center</h4>
        <p class="text-muted small mb-0 mt-1">Generate ready-to-use reports — print or upload to Google Drive</p>
    </div>
    <a href="<?= APP_URL ?>/reports" class="btn btn-sm btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i>Back to Reports
    </a>
</div>

<!-- Dual-mode explanation -->
<div class="alert alert-info d-flex gap-3 align-items-start mb-4">
    <i class="bi bi-info-circle-fill fs-4 mt-1"></i>
    <div>
        <strong>Dual-Mode Report Distribution:</strong> This system generates professional PDF and Excel reports that can be used in two ways:
        <div class="row mt-2 g-2">
            <div class="col-md-5">
                <div class="card border-success border-opacity-50 h-100">
                    <div class="card-body py-2 px-3">
                        <div class="d-flex align-items-center mb-1">
                            <i class="bi bi-printer text-success me-2 fs-5"></i>
                            <strong class="text-success">Print Mode</strong>
                        </div>
                        <small class="text-muted">Download as PDF and print as hard copy — for barangays without computer or internet access.</small>
                    </div>
                </div>
            </div>
            <div class="col-md-5">
                <div class="card border-primary border-opacity-50 h-100">
                    <div class="card-body py-2 px-3">
                        <div class="d-flex align-items-center mb-1">
                            <i class="bi bi-google text-primary me-2 fs-5"></i>
                            <strong class="text-primary">Google Drive Mode</strong>
                        </div>
                        <small class="text-muted">Download as Excel (.xlsx) and upload manually to Google Drive — for digital filing and sharing.</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>


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

<!-- Google Drive Upload Instructions -->
<div class="card border-0 shadow-sm">
    <div class="card-header bg-white fw-semibold">
        <i class="bi bi-google me-2 text-primary"></i>How to Upload to Google Drive
    </div>
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-3 text-center">
                <div class="bg-primary bg-opacity-10 rounded-circle d-inline-flex align-items-center justify-content-center mb-2"
                     style="width:60px;height:60px;">
                    <span class="fw-bold fs-4 text-primary">1</span>
                </div>
                <p class="small fw-semibold mb-1">Download the File</p>
                <p class="small text-muted">Click "Download Excel" on the report above. The file will save to your Downloads folder.</p>
            </div>
            <div class="col-md-3 text-center">
                <div class="bg-primary bg-opacity-10 rounded-circle d-inline-flex align-items-center justify-content-center mb-2"
                     style="width:60px;height:60px;">
                    <span class="fw-bold fs-4 text-primary">2</span>
                </div>
                <p class="small fw-semibold mb-1">Open Google Drive</p>
                <p class="small text-muted">Go to drive.google.com in any browser (Chrome, Firefox, Edge). Sign in to your Google account.</p>
            </div>
            <div class="col-md-3 text-center">
                <div class="bg-primary bg-opacity-10 rounded-circle d-inline-flex align-items-center justify-content-center mb-2"
                     style="width:60px;height:60px;">
                    <span class="fw-bold fs-4 text-primary">3</span>
                </div>
                <p class="small fw-semibold mb-1">Upload the File</p>
                <p class="small text-muted">Click "+ New" &rarr; "File Upload" &rarr; find your downloaded file &rarr; click "Open". It uploads automatically.</p>
            </div>
            <div class="col-md-3 text-center">
                <div class="bg-primary bg-opacity-10 rounded-circle d-inline-flex align-items-center justify-content-center mb-2"
                     style="width:60px;height:60px;">
                    <span class="fw-bold fs-4 text-primary">4</span>
                </div>
                <p class="small fw-semibold mb-1">Organize &amp; Share</p>
                <p class="small text-muted">Move the file to the correct folder (e.g., "NMS Reports / 2025"). Right-click &rarr; "Share" to share with supervisors.</p>
            </div>
        </div>
        <div class="alert alert-light border mt-3 mb-0">
            <i class="bi bi-lightbulb text-warning me-2"></i>
            <strong>Tip:</strong> Name your folders in Google Drive by year and barangay for easy organization —
            e.g., <code>NMS Reports / 2025 / Barangay San Jose</code>
        </div>
    </div>
</div>
