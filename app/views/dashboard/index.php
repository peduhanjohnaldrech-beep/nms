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
</div>

<!-- Stat Cards -->
<div class="row g-3 mb-4">
    <div class="col-6 col-xl-2">
        <a href="<?= APP_URL ?>/beneficiaries" class="text-decoration-none" aria-label="<?= number_format($totalBeneficiaries) ?> Total Beneficiaries">
        <div class="card border-0 shadow-sm h-100 card-hover">
            <div class="card-body d-flex align-items-center gap-3 py-3">
                <div class="rounded-circle bg-primary bg-opacity-10 d-flex align-items-center justify-content-center flex-shrink-0" style="width:44px;height:44px" aria-hidden="true">
                    <i class="bi bi-people-fill fs-5 text-primary"></i>
                </div>
                <div>
                    <div class="fs-3 fw-bold lh-1 text-primary" aria-hidden="true"><?= number_format($totalBeneficiaries) ?></div>
                    <div class="text-muted small mt-1">Total Beneficiaries</div>
                </div>
            </div>
        </div>
        </a>
    </div>
    <div class="col-6 col-xl-2">
        <a href="<?= APP_URL ?>/programs/opt" class="text-decoration-none" aria-label="<?= number_format($activeOpt) ?> OPT Assessed">
        <div class="card border-0 shadow-sm h-100 card-hover">
            <div class="card-body d-flex align-items-center gap-3 py-3">
                <div class="rounded-circle bg-success bg-opacity-10 d-flex align-items-center justify-content-center flex-shrink-0" style="width:44px;height:44px" aria-hidden="true">
                    <i class="bi bi-clipboard-heart fs-5 text-success"></i>
                </div>
                <div>
                    <div class="fs-3 fw-bold lh-1 text-success" aria-hidden="true"><?= number_format($activeOpt) ?></div>
                    <div class="text-muted small mt-1">OPT Assessed</div>
                </div>
            </div>
        </div>
        </a>
    </div>
    <div class="col-6 col-xl-2">
        <a href="<?= APP_URL ?>/programs/dsp" class="text-decoration-none" aria-label="<?= number_format($activeDsp) ?> Active DSP">
        <div class="card border-0 shadow-sm h-100 card-hover">
            <div class="card-body d-flex align-items-center gap-3 py-3">
                <div class="rounded-circle bg-warning bg-opacity-10 d-flex align-items-center justify-content-center flex-shrink-0" style="width:44px;height:44px" aria-hidden="true">
                    <i class="bi bi-egg-fried fs-5 text-warning"></i>
                </div>
                <div>
                    <div class="fs-3 fw-bold lh-1 text-warning" aria-hidden="true"><?= number_format($activeDsp) ?></div>
                    <div class="text-muted small mt-1">Active DSP</div>
                </div>
            </div>
        </div>
        </a>
    </div>
    <div class="col-6 col-xl-2">
        <a href="<?= APP_URL ?>/programs/mns" class="text-decoration-none" aria-label="<?= number_format($mnsCoverage) ?> MNS Coverage">
        <div class="card border-0 shadow-sm h-100 card-hover">
            <div class="card-body d-flex align-items-center gap-3 py-3">
                <div class="rounded-circle bg-info bg-opacity-10 d-flex align-items-center justify-content-center flex-shrink-0" style="width:44px;height:44px" aria-hidden="true">
                    <i class="bi bi-capsule fs-5 text-info"></i>
                </div>
                <div>
                    <div class="fs-3 fw-bold lh-1 text-info" aria-hidden="true"><?= number_format($mnsCoverage) ?></div>
                    <div class="text-muted small mt-1">MNS Coverage</div>
                </div>
            </div>
        </div>
        </a>
    </div>
    <div class="col-6 col-xl-2">
        <a href="<?= APP_URL ?>/beneficiaries?age_status=active" class="text-decoration-none" aria-label="<?= number_format($notAssessedCount) ?> Not Yet Assessed this <?= $periodLabel ?> <?= $currentYear ?> period">
        <div class="card border-0 shadow-sm h-100 card-hover <?= $notAssessedCount > 0 ? 'border-warning border-2' : '' ?>">
            <div class="card-body d-flex align-items-center gap-3 py-3">
                <div class="rounded-circle bg-warning bg-opacity-10 d-flex align-items-center justify-content-center flex-shrink-0" style="width:44px;height:44px" aria-hidden="true">
                    <i class="bi bi-clipboard2-x fs-5 text-warning"></i>
                </div>
                <div>
                    <div class="fs-3 fw-bold lh-1 text-warning" aria-hidden="true"><?= number_format($notAssessedCount) ?></div>
                    <div class="text-muted small mt-1">Not Yet Assessed</div>
                    <div class="text-muted" style="font-size:0.7rem;" aria-hidden="true"><?= $periodLabel ?> <?= $currentYear ?></div>
                </div>
            </div>
        </div>
        </a>
    </div>
</div>

<!-- Mobile: Pending Validation alert -->
<?php if ($__pendingValidation > 0 && hasPerm('validation')): ?>
<div class="d-md-none mb-3"><div class="alert alert-warning border-0 shadow-sm py-2 px-3 mb-0 d-flex align-items-center gap-2">
    <i class="bi bi-shield-exclamation fs-5 text-warning"></i>
    <div class="flex-grow-1 small">
        <strong><?= $__pendingValidation ?></strong> pending validation<?= $__pendingValidation > 1 ? 's' : '' ?> awaiting review
    </div>
    <a href="<?= APP_URL ?>/beneficiaries/validation" class="btn btn-sm btn-warning flex-shrink-0">Review</a>
</div></div>
<?php endif; ?>

<!-- Mobile: Quick Actions -->
<div class="d-md-none mb-4">
    <p class="text-muted small fw-semibold text-uppercase mb-2 ps-1"><i class="bi bi-lightning-fill me-1"></i>Quick Actions</p>
    <div class="row g-2">
        <?php if (hasPerm('beneficiaries')): ?>
        <div class="col-4">
            <a href="<?= APP_URL ?>/beneficiaries/create" class="quick-action-card">
                <div class="qa-icon bg-primary bg-opacity-10"><i class="bi bi-person-plus-fill text-primary"></i></div>
                <span class="qa-label">Add Beneficiary</span>
            </a>
        </div>
        <?php endif; ?>
        <div class="col-4">
            <a href="<?= APP_URL ?>/beneficiaries" class="quick-action-card">
                <div class="qa-icon bg-secondary bg-opacity-10"><i class="bi bi-people-fill text-secondary"></i></div>
                <span class="qa-label">Beneficiaries</span>
            </a>
        </div>
        <?php if (hasPerm('assessments')): ?>
        <div class="col-4">
            <a href="<?= APP_URL ?>/assessments" class="quick-action-card">
                <div class="qa-icon bg-success bg-opacity-10"><i class="bi bi-clipboard-heart text-success"></i></div>
                <span class="qa-label">OPT Weighing</span>
            </a>
        </div>
        <?php endif; ?>
        <?php if (hasPerm('validation')): ?>
        <div class="col-4">
            <a href="<?= APP_URL ?>/beneficiaries/validation" class="quick-action-card">
                <div class="qa-icon bg-warning bg-opacity-10 position-relative">
                    <i class="bi bi-shield-check text-warning"></i>
                    <?php if ($__pendingValidation > 0): ?>
                    <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" style="font-size:0.6rem;padding:0.2em 0.4em"><?= $__pendingValidation ?></span>
                    <?php endif; ?>
                </div>
                <span class="qa-label">Validation</span>
            </a>
        </div>
        <?php endif; ?>
        <?php if (hasPerm('reports')): ?>
        <div class="col-4">
            <a href="<?= APP_URL ?>/reports" class="quick-action-card">
                <div class="qa-icon bg-info bg-opacity-10"><i class="bi bi-file-earmark-bar-graph text-info"></i></div>
                <span class="qa-label">Reports</span>
            </a>
        </div>
        <?php endif; ?>
        <?php if ($isAdmin): ?>
        <div class="col-4">
            <a href="<?= APP_URL ?>/users" class="quick-action-card">
                <div class="qa-icon bg-danger bg-opacity-10"><i class="bi bi-person-gear text-danger"></i></div>
                <span class="qa-label">Manage Users</span>
            </a>
        </div>
        <div class="col-4">
            <a href="<?= APP_URL ?>/activity" class="quick-action-card">
                <div class="qa-icon bg-purple bg-opacity-10"><i class="bi bi-journal-text" style="color:var(--status-ob)"></i></div>
                <span class="qa-label">Activity Log</span>
            </a>
        </div>
        <?php endif; ?>
    </div>
</div>

<div class="d-none d-md-block">
<!-- Charts Row 1 -->
<div class="row g-3 mb-4">
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header border-bottom py-2 px-3">
                <span class="fw-semibold small text-uppercase text-muted">
                    <i class="bi bi-bar-chart-fill me-1 text-primary"></i>Nutritional Status by Barangay — <?= date('Y') ?>
                </span>
            </div>
            <div class="card-body pt-3"><canvas id="barangayChart" height="100" role="img" aria-label="Bar chart of nutritional status by barangay for <?= date('Y') ?>"></canvas></div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header border-bottom py-2 px-3">
                <span class="fw-semibold small text-uppercase text-muted">
                    <i class="bi bi-pie-chart-fill me-1 text-info"></i>Program Enrollment
                </span>
            </div>
            <div class="card-body d-flex align-items-center justify-content-center">
                <canvas id="enrollmentPie" height="200" role="img" aria-label="Pie chart of program enrollment breakdown"></canvas>
            </div>
        </div>
    </div>
</div>

<!-- Charts Row 2 -->
<div class="row g-3 mb-4">
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header border-bottom py-2 px-3">
                <span class="fw-semibold small text-uppercase text-muted">
                    <i class="bi bi-graph-up me-1 text-success"></i>OPT Trend — Nutritional Status Over Periods
                </span>
            </div>
            <div class="card-body pt-3"><canvas id="trendChart" height="120" role="img" aria-label="Line chart of OPT nutritional status trend over periods"></canvas></div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header border-bottom py-2 px-3">
                <span class="fw-semibold small text-uppercase text-muted">
                    <i class="bi bi-bar-chart me-1 text-danger"></i>Malnutrition Rate by Barangay — <?= date('Y') ?>
                </span>
                <span class="text-muted fw-normal" style="font-size:0.7rem;"> &nbsp;% SUW + UW of assessed</span>
            </div>
            <div class="card-body pt-3"><canvas id="malnutritionRateChart" height="120" role="img" aria-label="Bar chart of malnutrition rate by barangay for <?= date('Y') ?>"></canvas></div>
        </div>
    </div>
</div>
</div><!-- /d-none d-md-block charts -->

<script>
window.__dashboardData = {
    statusData: <?= json_encode($statusData) ?>,
    trendData: <?= json_encode($trendData) ?>,
    enrollmentData: <?= json_encode($enrollmentBreakdown) ?>
};
</script>
