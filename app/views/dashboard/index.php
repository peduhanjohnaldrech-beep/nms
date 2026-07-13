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

<?php if (!empty($pendingValidation) && $pendingValidation > 0): ?>
<div class="alert border-0 shadow-sm mb-4 d-flex align-items-center gap-3" style="background:rgba(217,119,6,.12);border-left:4px solid #d97706 !important;">
    <i class="bi bi-shield-exclamation fs-4" style="color:#d97706"></i>
    <div class="flex-grow-1">
        <strong><?= $pendingValidation ?> beneficiary registration<?= $pendingValidation > 1 ? 's' : '' ?> pending validation.</strong>
        Review and approve or reject them to unlock assessments.
    </div>
    <a href="<?= APP_URL ?>/beneficiaries/validation" class="btn btn-sm btn-warning text-white">Review</a>
</div>
<?php endif; ?>

<?php if (!empty($pendingAssessmentValidation) && $pendingAssessmentValidation > 0): ?>
<div class="alert border-0 shadow-sm mb-4 d-flex align-items-center gap-3" style="background:rgba(37,99,235,.1);border-left:4px solid #2563eb !important;">
    <i class="bi bi-clipboard2-pulse fs-4 text-primary"></i>
    <div class="flex-grow-1">
        <strong><?= $pendingAssessmentValidation ?> assessment<?= $pendingAssessmentValidation > 1 ? 's' : '' ?> from mobile pending validation.</strong>
        These were submitted via the BNS mobile app and need midwife review.
    </div>
    <a href="<?= APP_URL ?>/beneficiaries/validation" class="btn btn-sm btn-primary">Review</a>
</div>
<?php endif; ?>

<?php if (in_array(strtolower(\Core\Session::get('user_role','')), ['admin','nutritionist']) && ($submittedCount > 0 || !empty($recentSubmissions))): ?>
<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-white d-flex justify-content-between align-items-center py-2">
        <span class="fw-semibold small text-uppercase text-muted">
            <i class="bi bi-phone me-1 text-primary"></i>Mobile Submissions Inbox
        </span>
        <a href="<?= APP_URL ?>/beneficiaries?source=mobile" class="btn btn-sm btn-outline-primary">
            View All <span class="badge bg-primary ms-1"><?= $submittedCount ?></span>
        </a>
    </div>
    <?php if (!empty($recentSubmissions)): ?>
    <div class="table-responsive">
        <table class="table table-sm table-hover mb-0 align-middle">
            <thead class="table-light">
                <tr>
                    <th>Beneficiary</th>
                    <th>Barangay</th>
                    <th>Submitted By (BNS)</th>
                    <th>Submitted At</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($recentSubmissions as $s): ?>
                <tr>
                    <td class="fw-semibold">
                        <a href="<?= APP_URL ?>/beneficiaries/<?= $s['id'] ?>" class="text-decoration-none">
                            <?= htmlspecialchars($s['last_name'] . ', ' . $s['first_name']) ?>
                        </a>
                    </td>
                    <td><?= htmlspecialchars($s['barangay']) ?></td>
                    <td><?= htmlspecialchars($s['submitted_by_name'] ?? '—') ?></td>
                    <td class="text-muted small"><?= date('M j, Y g:i a', strtotime($s['submitted_at'])) ?></td>
                    <td>
                        <a href="<?= APP_URL ?>/beneficiaries/<?= $s['id'] ?>" class="btn btn-sm btn-outline-secondary">
                            <i class="bi bi-person me-1"></i>View
                        </a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php else: ?>
    <div class="card-body text-center text-muted py-3 small">
        <i class="bi bi-inbox fs-4 d-block mb-1"></i>No submissions yet.
    </div>
    <?php endif; ?>
</div>
<?php endif; ?>

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
