<?php $pageTitle = 'Reports'; ?>

<div class="d-flex align-items-center mt-3 mb-4 gap-3">
    <div class="rounded-3 bg-primary bg-opacity-10 d-flex align-items-center justify-content-center flex-shrink-0" style="width:42px;height:42px">
        <i class="bi bi-bar-chart-fill fs-5 text-primary" aria-hidden="true"></i>
    </div>
    <div>
        <h5 class="mb-0 fw-bold">Reports</h5>
        <p class="text-muted small mb-0">Generate, view, and export program data for monitoring and reporting</p>
    </div>
</div>

<!-- Quick Guide -->
<div class="alert alert-light border mb-4 py-3 px-4">
    <div class="fw-semibold mb-1"><i class="bi bi-lightbulb me-1 text-warning" aria-hidden="true"></i>Which report do I need?</div>
    <ul class="mb-0 small ps-3">
        <li><strong>Monthly weighing results?</strong> → <a href="<?= APP_URL ?>/reports/opt" class="alert-link">OPT Report</a></li>
        <li><strong>Who is enrolled in the feeding program?</strong> → <a href="<?= APP_URL ?>/reports/dsp" class="alert-link">DSP Report</a></li>
        <li><strong>Overall malnutrition rate per barangay?</strong> → <a href="<?= APP_URL ?>/reports/summary" class="alert-link">Summary Report</a></li>
        <li><strong>Vitamin A or MNP/LNS coverage?</strong> → <a href="<?= APP_URL ?>/reports/mns?tab=vita" class="alert-link">MNS Reports</a></li>
        <li><strong>Has malnutrition improved over time?</strong> → <a href="<?= APP_URL ?>/reports/comparison" class="alert-link">Period Comparison</a></li>
    </ul>
</div>

<!-- Core Programs -->
<p class="text-muted small fw-semibold text-uppercase mb-2 ps-1 letter-spacing-1">
    <i class="bi bi-clipboard-data me-1" aria-hidden="true"></i>Core Programs
</p>
<div class="row g-3 mb-4">
    <div class="col-sm-6 col-lg-4">
        <div class="card h-100 border-0 shadow-sm report-card">
            <div class="card-body p-4">
                <div class="d-flex align-items-center gap-3 mb-3">
                    <div class="rounded-circle bg-success bg-opacity-10 d-inline-flex align-items-center justify-content-center flex-shrink-0" style="width:48px;height:48px">
                        <i class="bi bi-clipboard-heart fs-4 text-success" aria-hidden="true"></i>
                    </div>
                    <div>
                        <h6 class="fw-bold mb-0">OPT Report</h6>
                        <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 small">Operation Timbang Plus</span>
                    </div>
                </div>
                <p class="text-muted small mb-3">Weight and height measurements per child. Filter by barangay, period (Jan/Jul), and year. Export to Excel.</p>
                <a href="<?= APP_URL ?>/reports/opt" class="btn btn-success btn-sm w-100"><i class="bi bi-eye me-1" aria-hidden="true"></i>View OPT Report</a>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-lg-4">
        <div class="card h-100 border-0 shadow-sm report-card">
            <div class="card-body p-4">
                <div class="d-flex align-items-center gap-3 mb-3">
                    <div class="rounded-circle bg-warning bg-opacity-10 d-inline-flex align-items-center justify-content-center flex-shrink-0" style="width:48px;height:48px">
                        <i class="bi bi-egg-fried fs-4 text-warning" aria-hidden="true"></i>
                    </div>
                    <div>
                        <h6 class="fw-bold mb-0">DSP Report</h6>
                        <span class="badge bg-warning bg-opacity-10 text-warning border border-warning border-opacity-25 small">Dietary Supplementation</span>
                    </div>
                </div>
                <p class="text-muted small mb-3">Enrollment list for the feeding program — wasted and severely wasted children receiving RUTF/RUSF intervention.</p>
                <a href="<?= APP_URL ?>/reports/dsp" class="btn btn-warning btn-sm w-100"><i class="bi bi-eye me-1" aria-hidden="true"></i>View DSP Report</a>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-lg-4">
        <div class="card h-100 border-0 shadow-sm report-card">
            <div class="card-body p-4">
                <div class="d-flex align-items-center gap-3 mb-3">
                    <div class="rounded-circle bg-secondary bg-opacity-10 d-inline-flex align-items-center justify-content-center flex-shrink-0" style="width:48px;height:48px">
                        <i class="bi bi-graph-up-arrow fs-4 text-secondary" aria-hidden="true"></i>
                    </div>
                    <div>
                        <h6 class="fw-bold mb-0">Program Outcome</h6>
                        <span class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary border-opacity-25 small">DSP Pre vs Post</span>
                    </div>
                </div>
                <p class="text-muted small mb-3">Weight gain tracking for DSP graduates — shows improvement from pre-enrollment weight to discharge weight.</p>
                <a href="<?= APP_URL ?>/reports/outcome" class="btn btn-secondary btn-sm w-100"><i class="bi bi-eye me-1" aria-hidden="true"></i>View Outcome Report</a>
            </div>
        </div>
    </div>
</div>

<!-- Micronutrient Supplementation -->
<p class="text-muted small fw-semibold text-uppercase mb-2 ps-1">
    <i class="bi bi-capsule me-1" aria-hidden="true"></i>Micronutrient Supplementation (MNS)
</p>
<div class="row g-3 mb-4">
    <div class="col-sm-6 col-lg-4">
        <div class="card h-100 border-0 shadow-sm report-card">
            <div class="card-body p-4">
                <div class="d-flex align-items-center gap-3 mb-3">
                    <div class="rounded-circle bg-warning bg-opacity-10 d-inline-flex align-items-center justify-content-center flex-shrink-0" style="width:48px;height:48px">
                        <i class="bi bi-capsule fs-4 text-warning" aria-hidden="true"></i>
                    </div>
                    <div>
                        <h6 class="fw-bold mb-0">Vitamin A</h6>
                        <span class="badge bg-warning bg-opacity-10 text-warning border border-warning border-opacity-25 small">Feb &amp; Aug Rounds</span>
                    </div>
                </div>
                <p class="text-muted small mb-3">Coverage report for Vitamin A supplementation — February and August distribution rounds per barangay.</p>
                <a href="<?= APP_URL ?>/reports/mns?tab=vita" class="btn btn-warning btn-sm w-100"><i class="bi bi-eye me-1" aria-hidden="true"></i>View Vitamin A Report</a>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-lg-4">
        <div class="card h-100 border-0 shadow-sm report-card">
            <div class="card-body p-4">
                <div class="d-flex align-items-center gap-3 mb-3">
                    <div class="rounded-circle bg-primary bg-opacity-10 d-inline-flex align-items-center justify-content-center flex-shrink-0" style="width:48px;height:48px">
                        <i class="bi bi-prescription fs-4 text-primary" aria-hidden="true"></i>
                    </div>
                    <div>
                        <h6 class="fw-bold mb-0">MNP Report</h6>
                        <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 small">Ages 6–59 months</span>
                    </div>
                </div>
                <p class="text-muted small mb-3">Micronutrient Powder distribution records — monthly tracking for children 6–59 months old.</p>
                <a href="<?= APP_URL ?>/reports/mns?tab=mnp" class="btn btn-primary btn-sm w-100"><i class="bi bi-eye me-1" aria-hidden="true"></i>View MNP Report</a>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-lg-4">
        <div class="card h-100 border-0 shadow-sm report-card">
            <div class="card-body p-4">
                <div class="d-flex align-items-center gap-3 mb-3">
                    <div class="rounded-circle bg-success bg-opacity-10 d-inline-flex align-items-center justify-content-center flex-shrink-0" style="width:48px;height:48px">
                        <i class="bi bi-prescription2 fs-4 text-success" aria-hidden="true"></i>
                    </div>
                    <div>
                        <h6 class="fw-bold mb-0">LNS-SQ Report</h6>
                        <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 small">Ages 6–23 months</span>
                    </div>
                </div>
                <p class="text-muted small mb-3">Lipid-based Nutrient Supplement distribution — monthly tracking for infants and young children 6–23 months old.</p>
                <a href="<?= APP_URL ?>/reports/mns?tab=lns" class="btn btn-success btn-sm w-100"><i class="bi bi-eye me-1" aria-hidden="true"></i>View LNS-SQ Report</a>
            </div>
        </div>
    </div>
</div>

<!-- Analytics -->
<p class="text-muted small fw-semibold text-uppercase mb-2 ps-1">
    <i class="bi bi-graph-up me-1" aria-hidden="true"></i>Analytics &amp; Summary
</p>
<div class="row g-3">
    <div class="col-sm-6 col-lg-4">
        <div class="card h-100 border-0 shadow-sm report-card">
            <div class="card-body p-4">
                <div class="d-flex align-items-center gap-3 mb-3">
                    <div class="rounded-circle bg-success bg-opacity-10 d-inline-flex align-items-center justify-content-center flex-shrink-0" style="width:48px;height:48px">
                        <i class="bi bi-file-earmark-bar-graph fs-4 text-success" aria-hidden="true"></i>
                    </div>
                    <div>
                        <h6 class="fw-bold mb-0">Summary Report</h6>
                        <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 small">All Barangays</span>
                    </div>
                </div>
                <p class="text-muted small mb-3">One-page overview: coverage %, malnutrition rate, DSP active, and Vitamin A coverage — broken down per barangay.</p>
                <a href="<?= APP_URL ?>/reports/summary" class="btn btn-success btn-sm w-100"><i class="bi bi-eye me-1" aria-hidden="true"></i>View Summary Report</a>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-lg-4">
        <div class="card h-100 border-0 shadow-sm report-card">
            <div class="card-body p-4">
                <div class="d-flex align-items-center gap-3 mb-3">
                    <div class="rounded-circle bg-primary bg-opacity-10 d-inline-flex align-items-center justify-content-center flex-shrink-0" style="width:48px;height:48px">
                        <i class="bi bi-arrow-left-right fs-4 text-primary" aria-hidden="true"></i>
                    </div>
                    <div>
                        <h6 class="fw-bold mb-0">Period Comparison</h6>
                        <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 small">Jan vs Jul</span>
                    </div>
                </div>
                <p class="text-muted small mb-3">Side-by-side comparison of January and July OPT rounds — shows whether malnutrition rates improved or worsened.</p>
                <a href="<?= APP_URL ?>/reports/comparison" class="btn btn-primary btn-sm w-100"><i class="bi bi-eye me-1" aria-hidden="true"></i>View Period Comparison</a>
            </div>
        </div>
    </div>
</div>

<style>
.report-card { transition: transform .15s ease, box-shadow .15s ease; cursor: pointer; }
.report-card:hover { transform: translateY(-3px); box-shadow: 0 .5rem 1.5rem rgba(0,0,0,.1) !important; }
</style>
