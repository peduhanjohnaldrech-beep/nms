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
<p class="text-muted small fw-semibold text-uppercase mb-2 ps-1 letter-spacing-1">
    <i class="bi bi-clipboard-data me-1"></i>Core Programs
</p>
<div class="row g-3 mb-4">
    <div class="col-sm-6 col-lg-4">
        <div class="card h-100 border-0 shadow-sm report-card">
            <div class="card-body text-center p-4">
                <div class="rounded-circle bg-success bg-opacity-10 d-inline-flex align-items-center justify-content-center mb-3" style="width:56px;height:56px">
                    <i class="bi bi-clipboard-heart fs-4 text-success"></i>
                </div>
                <h6 class="fw-bold mb-1">OPT Report</h6>
                <p class="text-muted small mb-3">Operation Timbang Plus — nutritional status assessment per barangay</p>
                <a href="<?= APP_URL ?>/reports/opt" class="btn btn-success btn-sm w-100"><i class="bi bi-eye me-1"></i>View Report</a>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-lg-4">
        <div class="card h-100 border-0 shadow-sm report-card">
            <div class="card-body text-center p-4">
                <div class="rounded-circle bg-warning bg-opacity-10 d-inline-flex align-items-center justify-content-center mb-3" style="width:56px;height:56px">
                    <i class="bi bi-egg-fried fs-4 text-warning"></i>
                </div>
                <h6 class="fw-bold mb-1">DSP Report</h6>
                <p class="text-muted small mb-3">Dietary Supplementation Program — enrollment, status, and discharge</p>
                <a href="<?= APP_URL ?>/reports/dsp" class="btn btn-warning btn-sm w-100"><i class="bi bi-eye me-1"></i>View Report</a>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-lg-4">
        <div class="card h-100 border-0 shadow-sm report-card">
            <div class="card-body text-center p-4">
                <div class="rounded-circle bg-secondary bg-opacity-10 d-inline-flex align-items-center justify-content-center mb-3" style="width:56px;height:56px">
                    <i class="bi bi-graph-up-arrow fs-4 text-secondary"></i>
                </div>
                <h6 class="fw-bold mb-1">Program Outcome</h6>
                <p class="text-muted small mb-3">DSP pre vs post weight analysis — track improvement rates</p>
                <a href="<?= APP_URL ?>/reports/outcome" class="btn btn-secondary btn-sm w-100"><i class="bi bi-eye me-1"></i>View Report</a>
            </div>
        </div>
    </div>
</div>

<!-- Micronutrient Supplementation -->
<p class="text-muted small fw-semibold text-uppercase mb-2 ps-1">
    <i class="bi bi-capsule me-1"></i>Micronutrient Supplementation (MNS)
</p>
<div class="row g-3 mb-4">
    <div class="col-sm-6 col-lg-4">
        <div class="card h-100 border-0 shadow-sm report-card">
            <div class="card-body text-center p-4">
                <div class="rounded-circle bg-warning bg-opacity-10 d-inline-flex align-items-center justify-content-center mb-3" style="width:56px;height:56px">
                    <i class="bi bi-capsule fs-4 text-warning"></i>
                </div>
                <h6 class="fw-bold mb-1">Vitamin A</h6>
                <p class="text-muted small mb-3">Supplementation coverage — February &amp; August distribution rounds</p>
                <a href="<?= APP_URL ?>/reports/mns?tab=vita" class="btn btn-warning btn-sm w-100"><i class="bi bi-eye me-1"></i>View Report</a>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-lg-4">
        <div class="card h-100 border-0 shadow-sm report-card">
            <div class="card-body text-center p-4">
                <div class="rounded-circle bg-primary bg-opacity-10 d-inline-flex align-items-center justify-content-center mb-3" style="width:56px;height:56px">
                    <i class="bi bi-prescription fs-4 text-primary"></i>
                </div>
                <h6 class="fw-bold mb-1">MNP Report</h6>
                <p class="text-muted small mb-3">Micronutrient Powder distribution — ages 6–59 months</p>
                <a href="<?= APP_URL ?>/reports/mns?tab=mnp" class="btn btn-primary btn-sm w-100"><i class="bi bi-eye me-1"></i>View Report</a>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-lg-4">
        <div class="card h-100 border-0 shadow-sm report-card">
            <div class="card-body text-center p-4">
                <div class="rounded-circle bg-success bg-opacity-10 d-inline-flex align-items-center justify-content-center mb-3" style="width:56px;height:56px">
                    <i class="bi bi-prescription2 fs-4 text-success"></i>
                </div>
                <h6 class="fw-bold mb-1">LNS-SQ Report</h6>
                <p class="text-muted small mb-3">Lipid-based Nutrient Supplement — ages 6–23 months</p>
                <a href="<?= APP_URL ?>/reports/mns?tab=lns" class="btn btn-success btn-sm w-100"><i class="bi bi-eye me-1"></i>View Report</a>
            </div>
        </div>
    </div>
</div>

<!-- Analytics -->
<p class="text-muted small fw-semibold text-uppercase mb-2 ps-1">
    <i class="bi bi-graph-up me-1"></i>Analytics
</p>
<div class="row g-3">
    <div class="col-sm-6 col-lg-4">
        <div class="card h-100 border-0 shadow-sm report-card">
            <div class="card-body text-center p-4">
                <div class="rounded-circle bg-success bg-opacity-10 d-inline-flex align-items-center justify-content-center mb-3" style="width:56px;height:56px">
                    <i class="bi bi-file-earmark-bar-graph fs-4 text-success"></i>
                </div>
                <h6 class="fw-bold mb-1">Summary Report</h6>
                <p class="text-muted small mb-3">Coverage and malnutrition rates per barangay with Excel export</p>
                <a href="<?= APP_URL ?>/reports/summary" class="btn btn-success btn-sm w-100"><i class="bi bi-eye me-1"></i>View Report</a>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-lg-4">
        <div class="card h-100 border-0 shadow-sm report-card">
            <div class="card-body text-center p-4">
                <div class="rounded-circle bg-primary bg-opacity-10 d-inline-flex align-items-center justify-content-center mb-3" style="width:56px;height:56px">
                    <i class="bi bi-arrow-left-right fs-4 text-primary"></i>
                </div>
                <h6 class="fw-bold mb-1">Period Comparison</h6>
                <p class="text-muted small mb-3">January vs July OPT rounds — track change in malnutrition rates</p>
                <a href="<?= APP_URL ?>/reports/comparison" class="btn btn-primary btn-sm w-100"><i class="bi bi-eye me-1"></i>View Report</a>
            </div>
        </div>
    </div>
</div>

<style>
.report-card { transition: transform .15s ease, box-shadow .15s ease; }
.report-card:hover { transform: translateY(-3px); box-shadow: 0 .5rem 1.5rem rgba(0,0,0,.1) !important; }
</style>
