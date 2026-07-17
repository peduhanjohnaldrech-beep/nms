<?php $pageTitle = 'Reports'; ?>

<div class="d-flex align-items-center mt-3 mb-4 gap-3">
    <div class="rounded-3 bg-primary bg-opacity-10 d-flex align-items-center justify-content-center flex-shrink-0" style="width:42px;height:42px">
        <i class="bi bi-bar-chart-fill fs-5 text-primary"></i>
    </div>
    <div>
        <h5 class="mb-0 fw-bold">Reports</h5>
        <p class="text-muted small mb-0">View and export program data</p>
    </div>
</div>

<!-- Core Programs -->
<p class="text-muted small fw-semibold text-uppercase mb-2 ps-1"><i class="bi bi-clipboard-data me-1"></i>Core Programs</p>
<div class="row g-2 mb-3">
    <div class="col-md-6">
        <div class="card h-100">
            <div class="card-body d-flex align-items-center gap-3 p-3">
                <div class="rounded-3 bg-success bg-opacity-10 d-flex align-items-center justify-content-center flex-shrink-0" style="width:40px;height:40px">
                    <i class="bi bi-clipboard-heart fs-5 text-success"></i>
                </div>
                <div class="flex-grow-1 min-width-0">
                    <h6 class="fw-bold mb-0">OPT Report</h6>
                    <p class="text-muted small mb-0">Operation Timbang — Nutritional Status Assessment</p>
                </div>
                <a href="<?= APP_URL ?>/reports/opt" class="btn btn-success btn-sm flex-shrink-0"><i class="bi bi-eye me-1"></i>View</a>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card h-100">
            <div class="card-body d-flex align-items-center gap-3 p-3">
                <div class="rounded-3 bg-warning bg-opacity-10 d-flex align-items-center justify-content-center flex-shrink-0" style="width:40px;height:40px">
                    <i class="bi bi-egg-fried fs-5 text-warning"></i>
                </div>
                <div class="flex-grow-1 min-width-0">
                    <h6 class="fw-bold mb-0">DSP Report</h6>
                    <p class="text-muted small mb-0">Dietary Supplementation — Enrollment & Discharge</p>
                </div>
                <a href="<?= APP_URL ?>/reports/dsp" class="btn btn-warning btn-sm flex-shrink-0"><i class="bi bi-eye me-1"></i>View</a>
            </div>
        </div>
    </div>
</div>

<!-- MNS -->
<p class="text-muted small fw-semibold text-uppercase mb-2 ps-1"><i class="bi bi-capsule me-1"></i>Micronutrient Supplementation (MNS)</p>
<div class="row g-2 mb-3">
    <div class="col-md-4">
        <div class="card h-100">
            <div class="card-body d-flex align-items-center gap-3 p-3">
                <div class="rounded-3 bg-warning bg-opacity-10 d-flex align-items-center justify-content-center flex-shrink-0" style="width:40px;height:40px">
                    <i class="bi bi-capsule fs-5 text-warning"></i>
                </div>
                <div class="flex-grow-1 min-width-0">
                    <h6 class="fw-bold mb-0">Vitamin A</h6>
                    <p class="text-muted small mb-0">Feb & Aug distribution rounds</p>
                </div>
                <a href="<?= APP_URL ?>/reports/mns?tab=vita" class="btn btn-warning btn-sm flex-shrink-0"><i class="bi bi-eye"></i></a>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card h-100">
            <div class="card-body d-flex align-items-center gap-3 p-3">
                <div class="rounded-3 bg-primary bg-opacity-10 d-flex align-items-center justify-content-center flex-shrink-0" style="width:40px;height:40px">
                    <i class="bi bi-prescription fs-5 text-primary"></i>
                </div>
                <div class="flex-grow-1 min-width-0">
                    <h6 class="fw-bold mb-0">MNP Report</h6>
                    <p class="text-muted small mb-0">Ages 6–59 months</p>
                </div>
                <a href="<?= APP_URL ?>/reports/mns?tab=mnp" class="btn btn-primary btn-sm flex-shrink-0"><i class="bi bi-eye"></i></a>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card h-100">
            <div class="card-body d-flex align-items-center gap-3 p-3">
                <div class="rounded-3 bg-success bg-opacity-10 d-flex align-items-center justify-content-center flex-shrink-0" style="width:40px;height:40px">
                    <i class="bi bi-prescription2 fs-5 text-success"></i>
                </div>
                <div class="flex-grow-1 min-width-0">
                    <h6 class="fw-bold mb-0">LNS-SQ Report</h6>
                    <p class="text-muted small mb-0">Ages 6–23 months</p>
                </div>
                <a href="<?= APP_URL ?>/reports/mns?tab=lns" class="btn btn-success btn-sm flex-shrink-0"><i class="bi bi-eye"></i></a>
            </div>
        </div>
    </div>
</div>

<!-- Analytics & Other -->
<p class="text-muted small fw-semibold text-uppercase mb-2 ps-1"><i class="bi bi-graph-up me-1"></i>Analytics & Other</p>
<div class="row g-2">
    <div class="col-md-3">
        <div class="card h-100">
            <div class="card-body d-flex align-items-center gap-3 p-3">
                <div class="rounded-3 bg-success bg-opacity-10 d-flex align-items-center justify-content-center flex-shrink-0" style="width:40px;height:40px">
                    <i class="bi bi-file-earmark-bar-graph fs-5 text-success"></i>
                </div>
                <div class="flex-grow-1 min-width-0">
                    <h6 class="fw-bold mb-0">Summary</h6>
                    <p class="text-muted small mb-0">Coverage & malnutrition per barangay</p>
                </div>
                <a href="<?= APP_URL ?>/reports/summary" class="btn btn-success btn-sm flex-shrink-0"><i class="bi bi-eye"></i></a>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card h-100">
            <div class="card-body d-flex align-items-center gap-3 p-3">
                <div class="rounded-3 bg-primary bg-opacity-10 d-flex align-items-center justify-content-center flex-shrink-0" style="width:40px;height:40px">
                    <i class="bi bi-arrow-left-right fs-5 text-primary"></i>
                </div>
                <div class="flex-grow-1 min-width-0">
                    <h6 class="fw-bold mb-0">Comparison</h6>
                    <p class="text-muted small mb-0">Jan vs July OPT rounds</p>
                </div>
                <a href="<?= APP_URL ?>/reports/comparison" class="btn btn-primary btn-sm flex-shrink-0"><i class="bi bi-eye"></i></a>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card h-100">
            <div class="card-body d-flex align-items-center gap-3 p-3">
                <div class="rounded-3 bg-secondary bg-opacity-10 d-flex align-items-center justify-content-center flex-shrink-0" style="width:40px;height:40px">
                    <i class="bi bi-graph-up-arrow fs-5 text-secondary"></i>
                </div>
                <div class="flex-grow-1 min-width-0">
                    <h6 class="fw-bold mb-0">Outcome</h6>
                    <p class="text-muted small mb-0">DSP pre/post weight analysis</p>
                </div>
                <a href="<?= APP_URL ?>/reports/outcome" class="btn btn-secondary btn-sm flex-shrink-0"><i class="bi bi-eye"></i></a>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card h-100">
            <div class="card-body d-flex align-items-center gap-3 p-3">
                <div class="rounded-3 bg-info bg-opacity-10 d-flex align-items-center justify-content-center flex-shrink-0" style="width:40px;height:40px">
                    <i class="bi bi-cloud-upload-fill fs-5 text-info"></i>
                </div>
                <div class="flex-grow-1 min-width-0">
                    <h6 class="fw-bold mb-0">Distribution</h6>
                    <p class="text-muted small mb-0">Print or upload to Google Drive</p>
                </div>
                <a href="<?= APP_URL ?>/reports/distribution" class="btn btn-outline-info btn-sm flex-shrink-0"><i class="bi bi-eye"></i></a>
            </div>
        </div>
    </div>
</div>
