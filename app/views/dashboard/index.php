<?php $pageTitle = 'Dashboard'; ?>

<?php if ($totalBeneficiaries === 0): ?>
<div class="alert alert-info border-0 shadow-sm mt-3 d-flex gap-3" role="alert">
    <div class="fs-3 pt-1"><i class="bi bi-rocket-takeoff text-info"></i></div>
    <div>
        <h6 class="fw-bold mb-1">Getting Started</h6>
        <p class="mb-2 small">Welcome to NMS! Here's how to get started:</p>
        <ol class="mb-1 small">
            <li><strong>Add a Beneficiary</strong> — <a href="<?= APP_URL ?>/beneficiaries/create" class="alert-link">Beneficiaries → Add Beneficiary</a></li>
            <li><strong>Record an Assessment</strong> — Open the beneficiary's profile and click <strong>New Assessment</strong>.</li>
            <li><strong>Enroll in Programs</strong> — Go to <a href="<?= APP_URL ?>/programs/mns" class="alert-link">MNS</a> to record Vitamin A and supplements.</li>
            <li><strong>Generate Reports</strong> — Go to <a href="<?= APP_URL ?>/reports" class="alert-link">Reports</a> to export data.</li>
        </ol>
        <p class="mb-0 small text-muted">This guide disappears once you add your first beneficiary.</p>
    </div>
</div>
<?php endif; ?>

<!-- Page Header -->
<div class="d-flex justify-content-between align-items-center mt-3 mb-4">
    <div>
        <h4 class="mb-0 fw-bold"><i class="bi bi-speedometer2 me-2 text-primary"></i>Dashboard</h4>
        <p class="text-muted small mb-0 mt-1">Overview for <?= date('Y') ?></p>
    </div>
    <?php if (strtolower(\Core\Session::get('user_role','')) === 'admin'): ?>
    <a href="<?= APP_URL ?>/backup/download" class="btn btn-sm btn-outline-secondary" title="Download a backup of the database">
        <i class="bi bi-database-down me-1"></i>Backup Database
    </a>
    <?php endif; ?>
</div>

<!-- Stat Cards -->
<div class="row g-3 mb-4">
    <div class="col-sm-6 col-xl-2">
        <a href="<?= APP_URL ?>/beneficiaries" class="text-decoration-none">
        <div class="card border-0 shadow-sm h-100 card-hover">
            <div class="card-body d-flex align-items-center gap-3 py-3">
                <div class="rounded-circle bg-primary bg-opacity-10 d-flex align-items-center justify-content-center flex-shrink-0" style="width:44px;height:44px">
                    <i class="bi bi-people-fill fs-5 text-primary"></i>
                </div>
                <div>
                    <div class="fs-3 fw-bold lh-1 text-primary"><?= number_format($totalBeneficiaries) ?></div>
                    <div class="text-muted small mt-1">Total Beneficiaries</div>
                </div>
            </div>
        </div>
        </a>
    </div>
    <div class="col-sm-6 col-xl-2">
        <a href="<?= APP_URL ?>/programs/opt" class="text-decoration-none">
        <div class="card border-0 shadow-sm h-100 card-hover">
            <div class="card-body d-flex align-items-center gap-3 py-3">
                <div class="rounded-circle bg-success bg-opacity-10 d-flex align-items-center justify-content-center flex-shrink-0" style="width:44px;height:44px">
                    <i class="bi bi-clipboard-heart fs-5 text-success"></i>
                </div>
                <div>
                    <div class="fs-3 fw-bold lh-1 text-success"><?= number_format($activeOpt) ?></div>
                    <div class="text-muted small mt-1">OPT Assessed</div>
                </div>
            </div>
        </div>
        </a>
    </div>
    <div class="col-sm-6 col-xl-2">
        <a href="<?= APP_URL ?>/programs/dsp" class="text-decoration-none">
        <div class="card border-0 shadow-sm h-100 card-hover">
            <div class="card-body d-flex align-items-center gap-3 py-3">
                <div class="rounded-circle bg-warning bg-opacity-10 d-flex align-items-center justify-content-center flex-shrink-0" style="width:44px;height:44px">
                    <i class="bi bi-egg-fried fs-5 text-warning"></i>
                </div>
                <div>
                    <div class="fs-3 fw-bold lh-1 text-warning"><?= number_format($activeDsp) ?></div>
                    <div class="text-muted small mt-1">Active DSP</div>
                </div>
            </div>
        </div>
        </a>
    </div>
    <div class="col-sm-6 col-xl-2">
        <a href="<?= APP_URL ?>/programs/mns" class="text-decoration-none">
        <div class="card border-0 shadow-sm h-100 card-hover">
            <div class="card-body d-flex align-items-center gap-3 py-3">
                <div class="rounded-circle bg-info bg-opacity-10 d-flex align-items-center justify-content-center flex-shrink-0" style="width:44px;height:44px">
                    <i class="bi bi-capsule fs-5 text-info"></i>
                </div>
                <div>
                    <div class="fs-3 fw-bold lh-1 text-info"><?= number_format($mnsCoverage) ?></div>
                    <div class="text-muted small mt-1">MNS Coverage</div>
                </div>
            </div>
        </div>
        </a>
    </div>
    <div class="col-sm-6 col-xl-2">
        <a href="<?= APP_URL ?>/beneficiaries/followup" class="text-decoration-none">
        <div class="card border-0 shadow-sm h-100 card-hover <?= $followupCount > 0 ? 'border-danger border-2 card-pulse' : '' ?>">
            <div class="card-body d-flex align-items-center gap-3 py-3">
                <div class="rounded-circle bg-danger bg-opacity-10 d-flex align-items-center justify-content-center flex-shrink-0" style="width:44px;height:44px">
                    <i class="bi bi-exclamation-triangle-fill fs-5 text-danger <?= $followupCount > 0 ? 'icon-shake' : '' ?>"></i>
                </div>
                <div>
                    <div class="fs-3 fw-bold lh-1 text-danger"><?= number_format($followupCount) ?></div>
                    <div class="text-muted small mt-1">For Follow-up</div>
                    <?php if ($followupCount > 0): ?>
                    <div class="text-danger" style="font-size:0.7rem;"><i class="bi bi-exclamation-circle me-1"></i>Needs attention</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        </a>
    </div>
    <div class="col-sm-6 col-xl-2">
        <a href="<?= APP_URL ?>/beneficiaries?age_status=active" class="text-decoration-none">
        <div class="card border-0 shadow-sm h-100 card-hover <?= $notAssessedCount > 0 ? 'border-warning border-2' : '' ?>">
            <div class="card-body d-flex align-items-center gap-3 py-3">
                <div class="rounded-circle bg-warning bg-opacity-10 d-flex align-items-center justify-content-center flex-shrink-0" style="width:44px;height:44px">
                    <i class="bi bi-clipboard2-x fs-5 text-warning"></i>
                </div>
                <div>
                    <div class="fs-3 fw-bold lh-1 text-warning"><?= number_format($notAssessedCount) ?></div>
                    <div class="text-muted small mt-1">Not Yet Assessed</div>
                    <div class="text-muted" style="font-size:0.7rem;"><?= $periodLabel ?> <?= $currentYear ?></div>
                </div>
            </div>
        </div>
        </a>
    </div>
</div>

<!-- Charts Row 1 -->
<div class="row g-3 mb-4">
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white border-bottom py-2 px-3">
                <span class="fw-semibold small text-uppercase text-muted">
                    <i class="bi bi-bar-chart-fill me-1 text-primary"></i>Nutritional Status by Barangay — <?= date('Y') ?>
                </span>
            </div>
            <div class="card-body pt-3"><canvas id="barangayChart" height="100"></canvas></div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white border-bottom py-2 px-3">
                <span class="fw-semibold small text-uppercase text-muted">
                    <i class="bi bi-pie-chart-fill me-1 text-info"></i>Program Enrollment
                </span>
            </div>
            <div class="card-body d-flex align-items-center justify-content-center">
                <canvas id="enrollmentPie" height="200"></canvas>
            </div>
        </div>
    </div>
</div>

<!-- Charts Row 2 -->
<div class="row g-3 mb-4">
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white border-bottom py-2 px-3">
                <span class="fw-semibold small text-uppercase text-muted">
                    <i class="bi bi-graph-up me-1 text-success"></i>OPT Trend — Nutritional Status Over Periods
                </span>
            </div>
            <div class="card-body pt-3"><canvas id="trendChart" height="120"></canvas></div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white border-bottom py-2 px-3">
                <span class="fw-semibold small text-uppercase text-muted">
                    <i class="bi bi-bar-chart me-1 text-danger"></i>Malnutrition Rate by Barangay — <?= date('Y') ?>
                </span>
                <span class="text-muted fw-normal" style="font-size:0.7rem;"> &nbsp;% SUW + UW of assessed</span>
            </div>
            <div class="card-body pt-3"><canvas id="malnutritionRateChart" height="120"></canvas></div>
        </div>
    </div>
</div>

<script>
window.__dashboardData = {
    statusData: <?= json_encode($statusData) ?>,
    trendData: <?= json_encode($trendData) ?>,
    enrollmentData: <?= json_encode($enrollmentBreakdown) ?>
};
</script>
