<?php $pageTitle = 'Reports'; ?>

<!-- Page Header -->
<div class="d-flex align-items-center mt-3 mb-4 gap-3">
    <div class="rounded-3 bg-primary bg-opacity-10 d-flex align-items-center justify-content-center flex-shrink-0" style="width:48px;height:48px">
        <i class="bi bi-bar-chart-fill fs-4 text-primary"></i>
    </div>
    <div>
        <h4 class="mb-0 fw-bold">Reports</h4>
        <p class="text-muted small mb-0">View and export program data for all modules</p>
    </div>
</div>

<!-- Section: Core Programs -->
<div class="text-muted small fw-semibold text-uppercase mb-2 ps-1">
    <i class="bi bi-clipboard-data me-1"></i>Core Programs
</div>
<div class="row g-3 mb-4">
    <div class="col-md-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body d-flex align-items-center gap-3 p-4">
                <div class="rounded-3 bg-success bg-opacity-10 d-flex align-items-center justify-content-center flex-shrink-0" style="width:52px;height:52px">
                    <i class="bi bi-clipboard-heart fs-4 text-success"></i>
                </div>
                <div class="flex-grow-1">
                    <h6 class="fw-bold mb-1">OPT Report</h6>
                    <p class="text-muted small mb-0">Operation Timbang — Nutritional Status Assessment Records</p>
                </div>
                <a href="<?= APP_URL ?>/reports/opt" class="btn btn-success btn-sm flex-shrink-0">
                    <i class="bi bi-eye me-1"></i>View
                </a>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body d-flex align-items-center gap-3 p-4">
                <div class="rounded-3 bg-warning bg-opacity-10 d-flex align-items-center justify-content-center flex-shrink-0" style="width:52px;height:52px">
                    <i class="bi bi-egg-fried fs-4 text-warning"></i>
                </div>
                <div class="flex-grow-1">
                    <h6 class="fw-bold mb-1">DSP Report</h6>
                    <p class="text-muted small mb-0">Dietary Supplementation Program — Enrollment and Discharge Records</p>
                </div>
                <a href="<?= APP_URL ?>/reports/dsp" class="btn btn-warning btn-sm flex-shrink-0">
                    <i class="bi bi-eye me-1"></i>View
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Section: MNS -->
<div class="text-muted small fw-semibold text-uppercase mb-2 ps-1">
    <i class="bi bi-capsule me-1"></i>Micronutrient Supplementation (MNS)
</div>
<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body d-flex flex-column p-4">
                <div class="d-flex align-items-center gap-3 mb-3">
                    <div class="rounded-3 bg-warning bg-opacity-10 d-flex align-items-center justify-content-center flex-shrink-0" style="width:44px;height:44px">
                        <i class="bi bi-capsule fs-5 text-warning"></i>
                    </div>
                    <h6 class="fw-bold mb-0">Vitamin A</h6>
                </div>
                <p class="text-muted small flex-grow-1 mb-3">Vitamin A Distribution Records — February &amp; August Rounds</p>
                <a href="<?= APP_URL ?>/reports/mns?tab=vita" class="btn btn-warning btn-sm">
                    <i class="bi bi-eye me-1"></i>View Report
                </a>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body d-flex flex-column p-4">
                <div class="d-flex align-items-center gap-3 mb-3">
                    <div class="rounded-3 bg-primary bg-opacity-10 d-flex align-items-center justify-content-center flex-shrink-0" style="width:44px;height:44px">
                        <i class="bi bi-prescription fs-5 text-primary"></i>
                    </div>
                    <h6 class="fw-bold mb-0">MNP Report</h6>
                </div>
                <p class="text-muted small flex-grow-1 mb-3">Micronutrient Powder Distribution — Ages 6–59 Months</p>
                <a href="<?= APP_URL ?>/reports/mns?tab=mnp" class="btn btn-primary btn-sm">
                    <i class="bi bi-eye me-1"></i>View Report
                </a>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body d-flex flex-column p-4">
                <div class="d-flex align-items-center gap-3 mb-3">
                    <div class="rounded-3 bg-success bg-opacity-10 d-flex align-items-center justify-content-center flex-shrink-0" style="width:44px;height:44px">
                        <i class="bi bi-prescription2 fs-5 text-success"></i>
                    </div>
                    <h6 class="fw-bold mb-0">LNS-SQ Report</h6>
                </div>
                <p class="text-muted small flex-grow-1 mb-3">Lipid-based Nutrient Supplement Distribution — Ages 6–23 Months</p>
                <a href="<?= APP_URL ?>/reports/mns?tab=lns" class="btn btn-success btn-sm">
                    <i class="bi bi-eye me-1"></i>View Report
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Section: Analytics -->
<div class="text-muted small fw-semibold text-uppercase mb-2 ps-1">
    <i class="bi bi-graph-up me-1"></i>Analytics
</div>
<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body d-flex flex-column p-4">
                <div class="d-flex align-items-center gap-3 mb-3">
                    <div class="rounded-3 bg-success bg-opacity-10 d-flex align-items-center justify-content-center flex-shrink-0" style="width:44px;height:44px">
                        <i class="bi bi-file-earmark-bar-graph fs-5 text-success"></i>
                    </div>
                    <h6 class="fw-bold mb-0">Summary Report</h6>
                </div>
                <p class="text-muted small flex-grow-1 mb-3">Consolidated overview — coverage, malnutrition rates, DSP, and Vitamin A per barangay</p>
                <a href="<?= APP_URL ?>/reports/summary" class="btn btn-success btn-sm">
                    <i class="bi bi-eye me-1"></i>View Report
                </a>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body d-flex flex-column p-4">
                <div class="d-flex align-items-center gap-3 mb-3">
                    <div class="rounded-3 bg-primary bg-opacity-10 d-flex align-items-center justify-content-center flex-shrink-0" style="width:44px;height:44px">
                        <i class="bi bi-arrow-left-right fs-5 text-primary"></i>
                    </div>
                    <h6 class="fw-bold mb-0">Period Comparison</h6>
                </div>
                <p class="text-muted small flex-grow-1 mb-3">January vs July — see if malnutrition improved or worsened between OPT rounds</p>
                <a href="<?= APP_URL ?>/reports/comparison" class="btn btn-primary btn-sm">
                    <i class="bi bi-eye me-1"></i>View Report
                </a>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body d-flex flex-column p-4">
                <div class="d-flex align-items-center gap-3 mb-3">
                    <div class="rounded-3 bg-secondary bg-opacity-10 d-flex align-items-center justify-content-center flex-shrink-0" style="width:44px;height:44px">
                        <i class="bi bi-graph-up-arrow fs-5 text-secondary"></i>
                    </div>
                    <h6 class="fw-bold mb-0">Outcome Report</h6>
                </div>
                <p class="text-muted small flex-grow-1 mb-3">DSP Cycle Outcomes — Pre/Post Weight Analysis</p>
                <a href="<?= APP_URL ?>/reports/outcome" class="btn btn-secondary btn-sm">
                    <i class="bi bi-eye me-1"></i>View Report
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Section: Other -->
<div class="text-muted small fw-semibold text-uppercase mb-2 ps-1">
    <i class="bi bi-folder me-1"></i>Other
</div>
<div class="row g-3">
    <div class="col-md-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body d-flex align-items-center gap-3 p-4">
                <div class="rounded-3 bg-info bg-opacity-10 d-flex align-items-center justify-content-center flex-shrink-0" style="width:52px;height:52px">
                    <i class="bi bi-prescription2 fs-4 text-info"></i>
                </div>
                <div class="flex-grow-1">
                    <h6 class="fw-bold mb-1">Dispensing Report</h6>
                    <p class="text-muted small mb-0">Medicine &amp; Supplement Dispensing — All Programs</p>
                </div>
                <a href="<?= APP_URL ?>/dispensing" class="btn btn-info btn-sm text-white flex-shrink-0">
                    <i class="bi bi-eye me-1"></i>View
                </a>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body d-flex align-items-center gap-3 p-4">
                <div class="rounded-3 bg-primary bg-opacity-10 d-flex align-items-center justify-content-center flex-shrink-0" style="width:52px;height:52px">
                    <i class="bi bi-cloud-upload-fill fs-4 text-primary"></i>
                </div>
                <div class="flex-grow-1">
                    <h6 class="fw-bold mb-1">Report Distribution</h6>
                    <p class="text-muted small mb-0">Generate &amp; distribute reports — Print or Upload to Google Drive</p>
                </div>
                <a href="<?= APP_URL ?>/reports/distribution" class="btn btn-outline-primary btn-sm flex-shrink-0">
                    <i class="bi bi-cloud-upload me-1"></i>Open
                </a>
            </div>
        </div>
    </div>
</div>
