<?php $pageTitle = 'Micronutrient Supplementation (MNS)'; ?>

<div class="d-flex justify-content-between align-items-center mt-3 mb-2">
    <div>
        <h4 class="mb-0"><i class="bi bi-capsule me-2"></i>MNS — Micronutrient Supplementation</h4>
        <small class="text-muted">Vitamin A, Micronutrient Powder (MNP), and LNS-SQ distribution for children aged 6–59 months</small>
    </div>
</div>

<!-- Filter -->
<div class="card border-0 shadow-sm mb-3">
    <div class="card-body py-2">
        <form method="get" class="row g-2 align-items-end">
            <input type="hidden" name="tab" value="<?= htmlspecialchars($tab) ?>">
            <?php if ($tab === 'vitaminA'): ?>
            <div class="col-md-2">
                <label class="form-label small">Round</label>
                <select name="round" class="form-select form-select-sm">
                    <option value="April"   <?= $round === 'April'   ? 'selected' : '' ?>>April</option>
                    <option value="October" <?= $round === 'October' ? 'selected' : '' ?>>October</option>
                </select>
            </div>
            <?php else: ?>
            <input type="hidden" name="round" value="<?= htmlspecialchars($round) ?>">
            <?php endif; ?>
            <div class="col-md-1">
                <label class="form-label small">Year</label>
                <input type="number" name="year" class="form-control form-control-sm"
                       value="<?= $year ?>" min="2000" max="<?= date('Y') + 1 ?>">
            </div>
            <?php if (!empty($barangays)): ?>
            <div class="col-md-2">
                <label class="form-label small">Barangay</label>
                <select name="barangay" class="form-select form-select-sm" id="mnsBarangayFilter">
                    <option value="">All Barangays</option>
                    <?php foreach ($barangays as $brgy): ?>
                    <option value="<?= htmlspecialchars($brgy['barangay']) ?>" <?= $filterBarangay === $brgy['barangay'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($brgy['barangay']) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small">Purok</label>
                <select name="purok" class="form-select form-select-sm" id="mnsPurokFilter"
                        <?= empty($filterBarangay) ? 'disabled' : '' ?>>
                    <option value="">All Puroks</option>
                    <?php foreach ($puroks as $p): ?>
                    <option value="<?= htmlspecialchars($p) ?>" <?= $filterPurok === $p ? 'selected' : '' ?>>
                        <?= htmlspecialchars($p) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php endif; ?>
            <div class="col-auto">
                <button type="submit" class="btn btn-sm btn-primary"><i class="bi bi-search me-1"></i>Filter</button>
                <?php if ($filterBarangay !== '' || $filterPurok !== ''): ?>
                <a href="?tab=<?= urlencode($tab) ?>&round=<?= urlencode($round) ?>&year=<?= $year ?>" class="btn btn-sm btn-outline-secondary ms-1">
                    <i class="bi bi-x-circle me-1"></i>Clear
                </a>
                <?php endif; ?>
            </div>
            <?php if ($tab !== 'defaulter'): ?>
            <div class="col-auto ms-auto">
                <?php
                $exportType = match($tab) {
                    'mnp'   => 'mns_mnp',
                    'lnssq' => 'mns_lns',
                    default => 'mns_vita',
                };
                $exportExtra = ($filterBarangay !== '' ? '&barangay=' . urlencode($filterBarangay) : '')
                             . ($filterPurok    !== '' ? '&purok='    . urlencode($filterPurok)    : '');
                ?>
                <a href="<?= APP_URL ?>/reports/export?type=<?= $exportType ?>&format=excel&year=<?= $year ?>&period=<?= urlencode($round) ?><?= $exportExtra ?>" class="btn btn-sm btn-success">
                    <i class="bi bi-file-earmark-excel me-1"></i>Export Excel
                </a>
            </div>
            <?php endif; ?>
        </form>
    </div>
</div>

<!-- Tabs -->
<?php
$tabBase = 'round=' . urlencode($round) . '&year=' . $year
    . ($filterBarangay !== '' ? '&barangay=' . urlencode($filterBarangay) : '')
    . ($filterPurok    !== '' ? '&purok='    . urlencode($filterPurok)    : '');
?>
<ul class="nav nav-tabs mb-3" id="mnsTabs">
    <li class="nav-item">
        <a class="nav-link <?= $tab === 'vitaminA' ? 'active' : '' ?>"
           href="?<?= $tabBase ?>&tab=vitaminA">
            <i class="bi bi-capsule me-1"></i>Vitamin A
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link <?= $tab === 'mnp' ? 'active' : '' ?>"
           href="?<?= $tabBase ?>&tab=mnp">
            <i class="bi bi-prescription me-1"></i>MNP
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link <?= $tab === 'lnssq' ? 'active' : '' ?>"
           href="?<?= $tabBase ?>&tab=lnssq">
            <i class="bi bi-prescription2 me-1"></i>LNS-SQ
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link <?= $tab === 'tcl' ? 'active' : '' ?>"
           href="?<?= $tabBase ?>&tab=tcl">
            <i class="bi bi-table me-1"></i>TCL Master List
        </a>
    </li>
    <li class="nav-item ms-auto">
        <a class="nav-link <?= $tab === 'defaulter' ? 'active text-danger' : 'text-danger' ?>"
           href="?<?= $tabBase ?>&tab=defaulter">
            <i class="bi bi-exclamation-triangle me-1"></i>Defaulter List
        </a>
    </li>
</ul>

<?php // $canRecord is passed from controller (hasPermission('programs')) ?>

<!-- ── Vitamin A Tab ── -->
<?php if ($tab === 'vitaminA'): ?>
<!-- Vitamin A tracker — both rounds -->
<div class="card border-0 shadow-sm mb-3">
    <div class="card-body py-2 px-3">
        <div class="row g-0 text-center align-items-center">
            <div class="col-2 fw-semibold small text-muted text-start">Round</div>
            <div class="col-2 fw-semibold small text-muted">Covered</div>
            <div class="col-2 fw-semibold small text-muted">Remaining</div>
            <div class="col-2 fw-semibold small text-muted">Total</div>
            <div class="col-4 fw-semibold small text-muted">Coverage</div>
        </div>
        <?php foreach ([$tracker[0], $tracker[1]] as $_t):
            $_total = $_t['covered'] + $_t['remaining'];
            $_pct   = $_total > 0 ? min(100, round($_t['covered'] / $_total * 100)) : 0;
            $_cls   = $_pct >= 80 ? 'success' : ($_pct >= 50 ? 'warning' : 'danger');
            $_rnd   = str_replace('Vitamin A — ', '', $_t['label']);
        ?>
        <div class="row g-0 text-center align-items-center py-1 border-top">
            <div class="col-2 fw-semibold small text-start"><?= $_rnd ?></div>
            <div class="col-2 fw-bold text-success"><?= $_t['covered'] ?></div>
            <div class="col-2 text-secondary"><?= $_t['remaining'] ?></div>
            <div class="col-2"><?= $_total ?></div>
            <div class="col-4">
                <div class="d-flex align-items-center gap-2">
                    <div class="progress flex-grow-1" style="height:8px;">
                        <div class="progress-bar bg-<?= $_cls ?>" style="width:<?= $_pct ?>%"></div>
                    </div>
                    <span class="small fw-semibold text-<?= $_cls ?>" style="min-width:36px"><?= $_pct ?>%</span>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>
<div class="row g-3">
    <!-- Not Yet Covered -->
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header fw-semibold d-flex justify-content-between align-items-center">
                <span>
                    <i class="bi bi-people me-1 text-primary"></i>
                    Eligible — Not Yet Covered
                    <span class="badge bg-secondary ms-1"><?= count($eligible) ?></span>
                </span>
                <?php if ($canRecord): ?>
                <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#vitaManualModal">
                    <i class="bi bi-plus-circle me-1"></i>Record Vitamin A
                </button>
                <?php endif; ?>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive" style="max-height:400px;overflow-y:auto;">
                    <table class="table table-sm table-hover mb-0 align-middle">
                        <thead class="table-light">
                            <tr><th>Name</th><th>Barangay</th><th>Age (mo)</th><th>Dosage</th>
                            <?php if ($canRecord): ?><th></th><?php endif; ?></tr>
                        </thead>
                        <tbody>
                            <?php if (empty($eligible)): ?>
                            <tr><td colspan="5" class="text-center text-muted py-4"><i class="bi bi-check-circle fs-3 d-block mb-1 text-success"></i>All eligible children covered for <?= htmlspecialchars($round) ?> <?= $year ?>.</td></tr>
                            <?php endif; ?>
                            <?php foreach ($eligible as $e): ?>
                            <?php $age = $e['age_months']; $dosage = $age <= 11 ? '100,000 IU (Blue)' : '200,000 IU (Red)'; ?>
                            <tr>
                                <td>
                                    <a href="<?= APP_URL ?>/beneficiaries/<?= $e['id'] ?>" class="text-decoration-none fw-semibold">
                                        <?= htmlspecialchars($e['last_name'] . ', ' . $e['first_name']) ?>
                                    </a>
                                </td>
                                <td><?= htmlspecialchars($e['barangay']) ?></td>
                                <td><?= $age ?></td>
                                <td>
                                    <span class="badge bg-<?= $age <= 11 ? 'primary' : 'danger' ?>">
                                        <?= $dosage ?>
                                    </span>
                                </td>
                                <?php if ($canRecord): ?>
                                <td>
                                    <button type="button" class="btn btn-sm btn-outline-primary"
                                            data-bs-toggle="modal" data-bs-target="#vitaModal"
                                            data-id="<?= $e['id'] ?>"
                                            data-name="<?= htmlspecialchars($e['last_name'] . ', ' . $e['first_name']) ?>"
                                            data-dob="<?= $e['date_of_birth'] ?>">
                                        <i class="bi bi-capsule me-1"></i>Record
                                    </button>
                                </td>
                                <?php endif; ?>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Records Given + Coverage stacked on right -->
    <div class="col-lg-6 d-flex flex-column gap-3">
        <div class="card border-0 shadow-sm">
            <div class="card-header fw-semibold">
                <i class="bi bi-check2-all me-1 text-success"></i>
                Records Given — <?= htmlspecialchars($round) ?> <?= $year ?>
                <span class="badge bg-success ms-1"><?= count($vitaRecords) ?></span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive" style="max-height:280px;overflow-y:auto;">
                    <table class="table table-sm table-hover mb-0 align-middle">
                        <?php $canDelete = in_array(\Core\Session::get('user_role'), ['admin','nutritionist']); ?>
                        <thead class="table-light">
                            <tr><th>Name</th><th>Barangay</th><th>Dosage</th><th>Date</th><?php if ($canDelete): ?><th></th><?php endif; ?></tr>
                        </thead>
                        <tbody>
                            <?php if (empty($vitaRecords)): ?>
                            <tr><td colspan="<?= $canDelete ? 5 : 4 ?>" class="text-center text-muted py-4">No records for <?= htmlspecialchars($round) ?> <?= $year ?>.</td></tr>
                            <?php endif; ?>
                            <?php foreach ($vitaRecords as $v): ?>
                            <tr>
                                <td>
                                    <a href="<?= APP_URL ?>/beneficiaries/<?= $v['beneficiary_id'] ?>" class="text-decoration-none fw-semibold">
                                        <?= htmlspecialchars($v['last_name'] . ', ' . $v['first_name']) ?>
                                    </a>
                                </td>
                                <td><?= htmlspecialchars($v['barangay']) ?></td>
                                <td>
                                    <span class="badge bg-<?= $v['capsule_color'] === 'Blue' ? 'primary' : 'danger' ?>">
                                        <?= number_format($v['dosage_iu']) ?> IU (<?= $v['capsule_color'] ?>)
                                    </span>
                                </td>
                                <td><?= htmlspecialchars($v['distribution_date']) ?></td>
                                <?php if ($canDelete): ?>
                                <td>
                                    <form method="post" action="<?= APP_URL ?>/programs/mns/vita/<?= $v['id'] ?>/delete" class="d-inline">
                                        <?= \Core\Session::csrfField() ?>
                                        <input type="hidden" name="year"  value="<?= $year ?>">
                                        <input type="hidden" name="round" value="<?= htmlspecialchars($round) ?>">
                                        <button type="button" class="btn btn-xs btn-outline-danger py-0 px-1 confirm-trigger" style="font-size:.75rem;"
                                                data-confirm-title="Delete Vitamin A Record"
                                                data-confirm-message="Delete this Vitamin A distribution record?"
                                                data-confirm-btn="Delete"
                                                data-confirm-class="btn-danger">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                </td>
                                <?php endif; ?>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="card border-0 shadow-sm">
            <div class="card-header fw-semibold">
                <i class="bi bi-bar-chart me-1 text-success"></i>Coverage by Barangay
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-sm mb-0">
                        <thead class="table-light">
                            <tr><th>Barangay</th><th>Covered</th><th>Blue</th><th>Red</th></tr>
                        </thead>
                        <tbody>
                            <?php if (empty($coverage)): ?>
                            <tr><td colspan="4" class="text-center text-muted py-3">No data.</td></tr>
                            <?php endif; ?>
                            <?php foreach ($coverage as $c): ?>
                            <tr>
                                <td><?= htmlspecialchars($c['barangay']) ?></td>
                                <td><strong><?= $c['covered'] ?></strong></td>
                                <td><span class="badge bg-primary"><?= $c['blue_count'] ?></span></td>
                                <td><span class="badge bg-danger"><?= $c['red_count'] ?></span></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ── MNP Tab ── -->
<?php elseif ($tab === 'mnp'): ?>
<?php if ($year < (int)date('Y')): ?>
<div class="alert alert-info py-2 mb-3">
    <i class="bi bi-info-circle me-1"></i>
    Viewing records for <strong><?= $year ?></strong>. Age eligibility is calculated as of <strong><?= htmlspecialchars(date('F j, Y', strtotime($asOfDate))) ?></strong> (Dec 31 of that year).
</div>
<?php endif; ?>
<!-- MNP tracker -->
<?php
$_t   = $tracker[2];
$_tot = $_t['covered'] + $_t['remaining'];
$_pct = $_tot > 0 ? min(100, round($_t['covered'] / $_tot * 100)) : 0;
$_cls = $_pct >= 80 ? 'success' : ($_pct >= 50 ? 'warning' : 'danger');
?>
<div class="row g-2 mb-3">
    <div class="col-4">
        <div class="card border-0 shadow-sm text-center py-2">
            <div class="fs-3 fw-bold text-success"><?= $_t['covered'] ?></div>
            <div class="small text-muted">Covered</div>
        </div>
    </div>
    <div class="col-4">
        <div class="card border-0 shadow-sm text-center py-2">
            <div class="fs-3 fw-bold text-secondary"><?= $_t['remaining'] ?></div>
            <div class="small text-muted">Remaining</div>
        </div>
    </div>
    <div class="col-4">
        <div class="card border-0 shadow-sm text-center py-2">
            <div class="fs-3 fw-bold text-<?= $_cls ?>"><?= $_pct ?>%</div>
            <div class="small text-muted">Coverage</div>
            <div class="progress mx-3 mt-1" style="height:4px;">
                <div class="progress-bar bg-<?= $_cls ?>" style="width:<?= $_pct ?>%"></div>
            </div>
        </div>
    </div>
</div>
<div class="row g-3">
    <!-- Not Yet Received -->
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header fw-semibold">
                <i class="bi bi-people me-1 text-warning"></i>
                Not Yet Received — <?= $year ?>
                <span class="badge bg-secondary ms-1"><?= count($mnpNotYet) ?></span>
                <small class="text-muted fw-normal ms-2">(ages as of <?= htmlspecialchars($asOfDate) ?>)</small>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive" style="max-height:400px;overflow-y:auto;">
                    <table class="table table-sm table-hover mb-0 align-middle">
                        <thead class="table-light">
                            <tr><th>Name</th><th>Barangay</th><th>Age (mo)</th></tr>
                        </thead>
                        <tbody>
                            <?php if (empty($mnpNotYet)): ?>
                            <tr><td colspan="3" class="text-center text-muted py-4">
                                <i class="bi bi-check-circle fs-3 d-block mb-1 text-success"></i>
                                All eligible children covered for <?= $year ?>.
                            </td></tr>
                            <?php endif; ?>
                            <?php foreach ($mnpNotYet as $b): ?>
                            <tr>
                                <td>
                                    <a href="<?= APP_URL ?>/beneficiaries/<?= $b['id'] ?>" class="text-decoration-none fw-semibold">
                                        <?= htmlspecialchars($b['last_name'] . ', ' . $b['first_name']) ?>
                                    </a>
                                </td>
                                <td><?= htmlspecialchars($b['barangay']) ?></td>
                                <td><?= $b['age_months'] ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Records Given -->
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header fw-semibold">
                <i class="bi bi-check2-all me-1 text-success"></i>
                MNP Records Given — <?= $year ?>
                <span class="badge bg-success ms-1"><?= count($mnpRecords) ?></span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive" style="max-height:400px;overflow-y:auto;">
                    <table class="table table-sm table-hover mb-0 align-middle">
                        <thead class="table-light">
                            <tr><th>Name</th><th>Barangay</th><th>Age Group</th><th>Date</th><th>Sachets</th><th>Done</th><th>Notes</th></tr>
                        </thead>
                        <tbody>
                            <?php if (empty($mnpRecords)): ?>
                            <tr><td colspan="6" class="text-center text-muted py-4">No MNP records for <?= $year ?>.</td></tr>
                            <?php endif; ?>
                            <?php foreach ($mnpRecords as $m): ?>
                            <tr>
                                <td>
                                    <a href="<?= APP_URL ?>/beneficiaries/<?= $m['beneficiary_id'] ?>" class="text-decoration-none fw-semibold">
                                        <?= htmlspecialchars($m['last_name'] . ', ' . $m['first_name']) ?>
                                    </a>
                                </td>
                                <td><?= htmlspecialchars($m['barangay']) ?></td>
                                <td><span class="badge bg-secondary"><?= htmlspecialchars($m['age_group']) ?></span></td>
                                <td><?= htmlspecialchars($m['date_given']) ?></td>
                                <td class="text-center">
                                    <?php if (isset($m['sachets_distributed']) && $m['sachets_distributed'] !== null): ?>
                                    <span class="badge bg-secondary"><?= (int)$m['sachets_distributed'] ?></span>
                                    <?php else: ?>
                                    <span class="text-muted">—</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge bg-<?= $m['completed_routine'] ? 'success' : 'warning text-dark' ?>">
                                        <?= $m['completed_routine'] ? 'Yes' : 'No' ?>
                                    </span>
                                </td>
                                <td class="small text-muted"><?= htmlspecialchars($m['notes'] ?? '') ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ── LNS-SQ Tab ── -->
<?php elseif ($tab === 'lnssq'): ?>
<?php if ($year < (int)date('Y')): ?>
<div class="alert alert-info py-2 mb-3">
    <i class="bi bi-info-circle me-1"></i>
    Viewing records for <strong><?= $year ?></strong>. Age eligibility is calculated as of <strong><?= htmlspecialchars(date('F j, Y', strtotime($asOfDate))) ?></strong> (Dec 31 of that year).
</div>
<?php endif; ?>
<!-- LNS-SQ tracker -->
<?php
$_t   = $tracker[3];
$_tot = $_t['covered'] + $_t['remaining'];
$_pct = $_tot > 0 ? min(100, round($_t['covered'] / $_tot * 100)) : 0;
$_cls = $_pct >= 80 ? 'success' : ($_pct >= 50 ? 'warning' : 'danger');
?>
<div class="row g-2 mb-3">
    <div class="col-4">
        <div class="card border-0 shadow-sm text-center py-2">
            <div class="fs-3 fw-bold text-success"><?= $_t['covered'] ?></div>
            <div class="small text-muted">Covered</div>
        </div>
    </div>
    <div class="col-4">
        <div class="card border-0 shadow-sm text-center py-2">
            <div class="fs-3 fw-bold text-secondary"><?= $_t['remaining'] ?></div>
            <div class="small text-muted">Remaining</div>
        </div>
    </div>
    <div class="col-4">
        <div class="card border-0 shadow-sm text-center py-2">
            <div class="fs-3 fw-bold text-<?= $_cls ?>"><?= $_pct ?>%</div>
            <div class="small text-muted">Coverage</div>
            <div class="progress mx-3 mt-1" style="height:4px;">
                <div class="progress-bar bg-<?= $_cls ?>" style="width:<?= $_pct ?>%"></div>
            </div>
        </div>
    </div>
</div>
<div class="row g-3">
    <!-- Not Yet Received -->
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header fw-semibold">
                <i class="bi bi-people me-1 text-warning"></i>
                Not Yet Received — <?= $year ?>
                <span class="badge bg-secondary ms-1"><?= count($lnsNotYet) ?></span>
                <small class="text-muted fw-normal ms-2">(ages as of <?= htmlspecialchars($asOfDate) ?>)</small>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive" style="max-height:400px;overflow-y:auto;">
                    <table class="table table-sm table-hover mb-0 align-middle">
                        <thead class="table-light">
                            <tr><th>Name</th><th>Barangay</th><th>Age (mo)</th></tr>
                        </thead>
                        <tbody>
                            <?php if (empty($lnsNotYet)): ?>
                            <tr><td colspan="3" class="text-center text-muted py-4">
                                <i class="bi bi-check-circle fs-3 d-block mb-1 text-success"></i>
                                All eligible children covered for <?= $year ?>.
                            </td></tr>
                            <?php endif; ?>
                            <?php foreach ($lnsNotYet as $b): ?>
                            <tr>
                                <td>
                                    <a href="<?= APP_URL ?>/beneficiaries/<?= $b['id'] ?>" class="text-decoration-none fw-semibold">
                                        <?= htmlspecialchars($b['last_name'] . ', ' . $b['first_name']) ?>
                                    </a>
                                </td>
                                <td><?= htmlspecialchars($b['barangay']) ?></td>
                                <td><?= $b['age_months'] ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Records Given -->
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header fw-semibold">
                <i class="bi bi-check2-all me-1 text-success"></i>
                LNS-SQ Records Given — <?= $year ?>
                <span class="badge bg-success ms-1"><?= count($lnsRecords) ?></span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive" style="max-height:400px;overflow-y:auto;">
                    <table class="table table-sm table-hover mb-0 align-middle">
                        <thead class="table-light">
                            <tr><th>Name</th><th>Barangay</th><th>Age Group</th><th>Date</th><th>Sachets</th><th>Done</th><th>Notes</th></tr>
                        </thead>
                        <tbody>
                            <?php if (empty($lnsRecords)): ?>
                            <tr><td colspan="6" class="text-center text-muted py-4">No LNS-SQ records for <?= $year ?>.</td></tr>
                            <?php endif; ?>
                            <?php foreach ($lnsRecords as $l): ?>
                            <tr>
                                <td>
                                    <a href="<?= APP_URL ?>/beneficiaries/<?= $l['beneficiary_id'] ?>" class="text-decoration-none fw-semibold">
                                        <?= htmlspecialchars($l['last_name'] . ', ' . $l['first_name']) ?>
                                    </a>
                                </td>
                                <td><?= htmlspecialchars($l['barangay']) ?></td>
                                <td><span class="badge bg-secondary"><?= htmlspecialchars($l['age_group']) ?></span></td>
                                <td><?= htmlspecialchars($l['date_given']) ?></td>
                                <td class="text-center">
                                    <?php if (isset($l['sachets_distributed']) && $l['sachets_distributed'] !== null): ?>
                                    <span class="badge bg-secondary"><?= (int)$l['sachets_distributed'] ?></span>
                                    <?php else: ?>
                                    <span class="text-muted">—</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge bg-<?= $l['completed_routine'] ? 'success' : 'warning text-dark' ?>">
                                        <?= $l['completed_routine'] ? 'Yes' : 'No' ?>
                                    </span>
                                </td>
                                <td class="small text-muted"><?= htmlspecialchars($l['notes'] ?? '') ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ── Defaulter List Tab ── -->
<?php elseif ($tab === 'defaulter'): ?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <span class="fw-semibold">Children missing at least one supplement — <?= $year ?></span>
        <?php if ($filterBarangay !== ''): ?>
        <span class="ms-2 text-muted small">(<?= htmlspecialchars($filterBarangay) ?><?= $filterPurok !== '' ? ' · ' . htmlspecialchars($filterPurok) : '' ?>)</span>
        <?php endif; ?>
    </div>
    <button onclick="window.print()" class="btn btn-sm btn-outline-secondary">
        <i class="bi bi-printer me-1"></i>Print List
    </button>
</div>
<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-sm table-bordered mb-0 align-middle">
                <thead class="table-warning">
                    <tr>
                        <th rowspan="2" class="align-middle">Name</th>
                        <th rowspan="2" class="align-middle">Barangay</th>
                        <th rowspan="2" class="align-middle">Purok</th>
                        <th rowspan="2" class="align-middle text-center">Age<br><small>(mo)</small></th>
                        <th colspan="2" class="text-center">Vitamin A</th>
                        <th rowspan="2" class="align-middle text-center">MNP</th>
                        <th rowspan="2" class="align-middle text-center">LNS-SQ</th>
                        <th rowspan="2" class="align-middle">Reason / Note</th>
                    </tr>
                    <tr>
                        <th class="text-center">Apr</th>
                        <th class="text-center">Oct</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($defaulterList)): ?>
                    <tr><td colspan="9" class="text-center text-muted py-5">
                        <i class="bi bi-check-circle-fill text-success fs-3 d-block mb-2"></i>
                        All eligible children covered for <?= $year ?>!
                    </td></tr>
                    <?php endif; ?>
                    <?php foreach ($defaulterList as $d):
                        $cols = [
                            'vita_april' => ['elig' => (bool)$d['elig_vita_april'], 'got' => (bool)$d['got_vita_april']],
                            'vita_oct'   => ['elig' => (bool)$d['elig_vita_oct'],   'got' => (bool)$d['got_vita_oct']],
                            'mnp'        => ['elig' => (bool)$d['elig_mnp'],        'got' => (bool)$d['got_mnp']],
                            'lns'        => ['elig' => (bool)$d['elig_lns'],        'got' => (bool)$d['got_lns']],
                        ];
                        $reasons = $defaulterReasons[$d['id']] ?? [];
                        // First missing supplement
                        $firstMissing = null;
                        foreach ($cols as $key => $col) { if ($col['elig'] && !$col['got']) { $firstMissing = $key; break; } }
                        $existingReason = $firstMissing ? ($reasons[$firstMissing] ?? '') : '';
                    ?>
                    <tr>
                        <td>
                            <a href="<?= APP_URL ?>/beneficiaries/<?= $d['id'] ?>" class="text-decoration-none fw-semibold">
                                <?= htmlspecialchars($d['last_name'] . ', ' . $d['first_name']) ?>
                            </a>
                        </td>
                        <td><?= htmlspecialchars($d['barangay']) ?></td>
                        <td><?= htmlspecialchars($d['purok_zone'] ?? '—') ?></td>
                        <td class="text-center"><?= $d['age_months'] ?></td>
                        <?php foreach ($cols as $key => $col): ?>
                            <?php if (!$col['elig']): ?>
                            <td class="text-center text-muted bg-light small">N/A</td>
                            <?php elseif ($col['got']): ?>
                            <td class="text-center text-success fw-bold">✓</td>
                            <?php else: ?>
                            <td class="text-center text-danger fw-bold" title="<?= htmlspecialchars($reasons[$key] ?? '') ?>">✗</td>
                            <?php endif; ?>
                        <?php endforeach; ?>
                        <td style="min-width:180px;">
                            <?php if ($firstMissing && hasPerm('programs')): ?>
                            <form method="post" action="<?= APP_URL ?>/programs/mns/defaulter/reason" class="d-flex gap-1">
                                <?= \Core\Session::csrfField() ?>
                                <input type="hidden" name="beneficiary_id" value="<?= $d['id'] ?>">
                                <input type="hidden" name="supplement"     value="<?= $firstMissing ?>">
                                <input type="hidden" name="year"           value="<?= $year ?>">
                                <select name="reason" class="form-select form-select-sm" style="font-size:.75rem;">
                                    <option value="">— Select reason —</option>
                                    <?php foreach (['Child was sick','Mother refused','Child moved/transferred','Not found at home','Will follow up','Other'] as $opt): ?>
                                    <option value="<?= $opt ?>" <?= $existingReason === $opt ? 'selected' : '' ?>><?= $opt ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <button type="submit" class="btn btn-sm btn-outline-secondary py-0 px-1" style="font-size:.75rem;" title="Save reason">
                                    <i class="bi bi-check2"></i>
                                </button>
                            </form>
                            <?php elseif ($existingReason): ?>
                            <span class="small text-muted"><?= htmlspecialchars($existingReason) ?></span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- ── TCL Master List Tab ── -->
<?php elseif ($tab === 'tcl'): ?>
<div class="card border-0 shadow-sm">
    <div class="card-header fw-semibold d-flex justify-content-between align-items-center">
        <span>
            <i class="bi bi-table me-1 text-primary"></i>
            TCL — Target Client List &nbsp;<span class="badge bg-secondary"><?= count($tclList) ?></span>
        </span>
        <small class="text-muted fw-normal">One row per child · blank cell = missed supplement</small>
    </div>
    <div class="table-responsive">
        <table class="table table-sm table-bordered table-hover mb-0 align-middle" style="font-size:.83rem;">
            <thead class="table-light">
                <tr>
                    <th rowspan="2" class="align-middle">Name</th>
                    <th rowspan="2" class="align-middle text-center">Age<br>(mo)</th>
                    <th rowspan="2" class="align-middle">Barangay / Purok</th>
                    <th rowspan="2" class="align-middle text-center">OPT Status</th>
                    <th colspan="2" class="text-center bg-warning bg-opacity-10">Vitamin A</th>
                    <th rowspan="2" class="align-middle text-center bg-info bg-opacity-10">MNP<br><small class="fw-normal text-muted">6–23 mo</small></th>
                    <th rowspan="2" class="align-middle text-center bg-success bg-opacity-10">LNS-SQ<br><small class="fw-normal text-muted">Wasted/Stunted</small></th>
                </tr>
                <tr>
                    <th class="text-center bg-warning bg-opacity-10">April</th>
                    <th class="text-center bg-warning bg-opacity-10">October</th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($tclList)): ?>
            <tr><td colspan="8" class="text-center text-muted py-5">No beneficiaries found.</td></tr>
            <?php endif; ?>
            <?php foreach ($tclList as $r):
                $lnsElig = in_array($r['opt_status'] ?? '', ['SUW','UW'])
                        || in_array($r['wflh_status'] ?? '', ['SW','MW'])
                        || in_array($r['hfa_status']  ?? '', ['SSt','St']);
                $mnpElig = ($r['age_months'] >= 6 && $r['age_months'] <= 23);
                $vitaElig = ($r['age_months'] >= 6 && $r['age_months'] <= 59);
                // highlight row if any eligible supplement is blank
                $rowMissing = ($vitaElig && !$r['vita_april_date'])
                           || ($vitaElig && !$r['vita_oct_date'])
                           || ($mnpElig  && !$r['mnp_date'])
                           || ($lnsElig  && !$r['lns_date']);
                $statusColors = ['SUW'=>'danger','UW'=>'warning text-dark','Normal'=>'success','OW'=>'info text-dark','OB'=>'primary'];
                $statusColor  = $statusColors[$r['opt_status'] ?? ''] ?? 'secondary';
            ?>
            <tr class="<?= $rowMissing ? 'table-warning' : '' ?>">
                <td>
                    <a href="<?= APP_URL ?>/beneficiaries/<?= $r['id'] ?>" class="text-decoration-none fw-semibold">
                        <?= htmlspecialchars($r['last_name'] . ', ' . $r['first_name']) ?>
                    </a>
                </td>
                <td class="text-center"><?= $r['age_months'] ?></td>
                <td><?= htmlspecialchars($r['barangay']) ?><?= $r['purok_zone'] ? ' · ' . htmlspecialchars($r['purok_zone']) : '' ?></td>
                <td class="text-center">
                    <?php if ($r['opt_status']): ?>
                    <span class="badge bg-<?= $statusColor ?>"><?= htmlspecialchars($r['opt_status']) ?></span>
                    <?php else: ?>
                    <span class="text-muted small">No OPT</span>
                    <?php endif; ?>
                </td>
                <!-- Vitamin A April -->
                <td class="text-center <?= $vitaElig && !$r['vita_april_date'] ? 'bg-danger bg-opacity-10' : '' ?>">
                    <?php if (!$vitaElig): ?>
                    <span class="text-muted">—</span>
                    <?php elseif ($r['vita_april_date']): ?>
                    <span class="text-success fw-semibold small"><?= htmlspecialchars($r['vita_april_date']) ?></span>
                    <?php else: ?>
                    <span class="text-danger small">Missed</span>
                    <?php endif; ?>
                </td>
                <!-- Vitamin A October -->
                <td class="text-center <?= $vitaElig && !$r['vita_oct_date'] ? 'bg-danger bg-opacity-10' : '' ?>">
                    <?php if (!$vitaElig): ?>
                    <span class="text-muted">—</span>
                    <?php elseif ($r['vita_oct_date']): ?>
                    <span class="text-success fw-semibold small"><?= htmlspecialchars($r['vita_oct_date']) ?></span>
                    <?php else: ?>
                    <span class="text-danger small">Missed</span>
                    <?php endif; ?>
                </td>
                <!-- MNP -->
                <td class="text-center <?= $mnpElig && !$r['mnp_date'] ? 'bg-danger bg-opacity-10' : '' ?>">
                    <?php if (!$mnpElig): ?>
                    <span class="text-muted">—</span>
                    <?php elseif ($r['mnp_date']): ?>
                    <span class="text-success fw-semibold small"><?= htmlspecialchars($r['mnp_date']) ?></span>
                    <?php if ($r['mnp_sachets']): ?><br><span class="text-muted" style="font-size:.75rem;"><?= $r['mnp_sachets'] ?> sachets</span><?php endif; ?>
                    <?php else: ?>
                    <span class="text-danger small">Missed</span>
                    <?php endif; ?>
                </td>
                <!-- LNS-SQ -->
                <td class="text-center <?= $lnsElig && !$r['lns_date'] ? 'bg-danger bg-opacity-10' : '' ?>">
                    <?php if (!$lnsElig): ?>
                    <span class="text-muted">—</span>
                    <?php elseif ($r['lns_date']): ?>
                    <span class="text-success fw-semibold small"><?= htmlspecialchars($r['lns_date']) ?></span>
                    <?php if ($r['lns_sachets']): ?><br><span class="text-muted" style="font-size:.75rem;"><?= $r['lns_sachets'] ?> packets</span><?php endif; ?>
                    <?php else: ?>
                    <span class="text-danger small">Missed</span>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<!-- ── Distribution Completions Banner ── -->
<?php if (!empty($distributionCompletions)): ?>
<div class="mt-4">
    <h6 class="fw-semibold mb-2"><i class="bi bi-check2-circle me-1 text-success"></i>BNS Distribution Completed</h6>
    <div class="d-flex flex-wrap gap-2">
        <?php foreach ($distributionCompletions as $dc): ?>
        <div class="alert alert-success py-1 px-3 mb-0 small d-flex align-items-center gap-2">
            <i class="bi bi-person-check"></i>
            <span>
                <strong><?= htmlspecialchars($dc['full_name']) ?></strong>
                — <?= htmlspecialchars($dc['barangay']) ?>
                · <?= strtoupper(htmlspecialchars($dc['supplement'])) ?>
                <span class="text-muted ms-1"><?= date('M j', strtotime($dc['completed_at'])) ?></span>
            </span>
        </div>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<!-- ── BNS Coverage per Barangay ── -->
<?php if (!empty($bnsCoverage)): ?>
<div class="card border-0 shadow-sm mt-4">
    <div class="card-header fw-semibold">
        <i class="bi bi-people-fill me-1 text-primary"></i>BNS Coverage Progress — <?= $year ?>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-sm mb-0 align-middle">
                <thead class="table-light">
                    <tr><th>Barangay</th><th>BNS</th><th>Eligible (6–23mo)</th><th>MNP Covered</th><th>LNS-SQ Covered</th><th>MNP %</th><th>LNS-SQ %</th><th>Status</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($bnsCoverage as $b):
                        $mnpPct = $b['eligible_count'] > 0 ? min(100, round($b['mnp_covered'] / $b['eligible_count'] * 100)) : 0;
                        $lnsPct = $b['eligible_count'] > 0 ? min(100, round($b['lns_covered'] / $b['eligible_count'] * 100)) : 0;
                        $mnpClass = $mnpPct >= 80 ? 'success' : ($mnpPct >= 50 ? 'warning' : 'danger');
                        $lnsClass = $lnsPct >= 80 ? 'success' : ($lnsPct >= 50 ? 'warning' : 'danger');
                    ?>
                    <tr>
                        <td class="fw-semibold"><?= htmlspecialchars($b['barangay']) ?></td>
                        <td class="small text-muted"><?= htmlspecialchars($b['bns_names']) ?></td>
                        <td class="text-center"><?= $b['eligible_count'] ?></td>
                        <td class="text-center"><?= $b['mnp_covered'] ?></td>
                        <td class="text-center"><?= $b['lns_covered'] ?></td>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                <div class="progress flex-grow-1" style="height:6px;">
                                    <div class="progress-bar bg-<?= $mnpClass ?>" style="width:<?= $mnpPct ?>%"></div>
                                </div>
                                <span class="small text-<?= $mnpClass ?>"><?= $mnpPct ?>%</span>
                            </div>
                        </td>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                <div class="progress flex-grow-1" style="height:6px;">
                                    <div class="progress-bar bg-<?= $lnsClass ?>" style="width:<?= $lnsPct ?>%"></div>
                                </div>
                                <span class="small text-<?= $lnsClass ?>"><?= $lnsPct ?>%</span>
                            </div>
                        </td>
                        <td>
                            <?php
                            // Check if BNS marked complete
                            $mnpDone = array_filter($distributionCompletions, fn($c) => $c['barangay'] === $b['barangay'] && $c['supplement'] === 'mnp');
                            $lnsDone = array_filter($distributionCompletions, fn($c) => $c['barangay'] === $b['barangay'] && $c['supplement'] === 'lnssq');
                            ?>
                            <?php if ($mnpDone): ?><span class="badge bg-success me-1">MNP Done</span><?php endif; ?>
                            <?php if ($lnsDone): ?><span class="badge bg-success">LNS Done</span><?php endif; ?>
                            <?php if (!$mnpDone && !$lnsDone): ?><span class="badge bg-secondary">In Progress</span><?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- ── Vitamin A Quick-Record Modal (from eligible list) ── -->
<div class="modal fade" id="vitaModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="<?= APP_URL ?>/programs/mns/vitamina" method="post">
                <?= \Core\Session::csrfField() ?>
                <input type="hidden" name="beneficiary_id" id="vitaBeneficiaryId">
                <input type="hidden" name="date_of_birth"  id="vitaDob">
                <input type="hidden" name="round" value="<?= htmlspecialchars($round) ?>">
                <input type="hidden" name="year"  value="<?= $year ?>">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-capsule me-2"></i>Record Vitamin A</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="mb-3">Recording for: <strong id="vitaName"></strong></p>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Distribution Date</label>
                        <input type="date" name="distribution_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Administered By</label>
                        <input type="text" name="administered_by" class="form-control"
                               value="<?= htmlspecialchars(\Core\Session::get('user_name', '')) ?>">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-save me-1"></i>Save</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ── Vitamin A Manual Modal (any beneficiary) ── -->
<div class="modal fade" id="vitaManualModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="<?= APP_URL ?>/programs/mns/vitamina" method="post">
                <?= \Core\Session::csrfField() ?>
                <input type="hidden" name="round" value="<?= htmlspecialchars($round) ?>">
                <input type="hidden" name="year"  value="<?= $year ?>">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-capsule me-2"></i>Record Vitamin A</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Beneficiary <span class="text-danger">*</span></label>
                        <select name="beneficiary_id" class="form-select" required id="manualVitaBeneficiary">
                            <option value="">— Select Beneficiary —</option>
                            <?php foreach ($allBeneficiaries as $b): ?>
                            <option value="<?= $b['id'] ?>" data-dob="<?= $b['date_of_birth'] ?>">
                                <?= htmlspecialchars($b['last_name'] . ', ' . $b['first_name'] . ' (' . $b['barangay'] . ')') ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                        <input type="hidden" name="date_of_birth" id="manualVitaDob">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Distribution Date</label>
                        <input type="date" name="distribution_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Administered By</label>
                        <input type="text" name="administered_by" class="form-control"
                               value="<?= htmlspecialchars(\Core\Session::get('user_name', '')) ?>">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-save me-1"></i>Save</button>
                </div>
            </form>
        </div>
    </div>
</div>


<script>
// Enable purok filter only when a barangay is selected
document.getElementById('mnsBarangayFilter')?.addEventListener('change', function() {
    const purok = document.getElementById('mnsPurokFilter');
    if (!purok) return;
    purok.disabled = this.value === '';
    if (this.value === '') purok.value = '';
});

document.getElementById('vitaModal')?.addEventListener('show.bs.modal', function(e) {
    const btn = e.relatedTarget;
    document.getElementById('vitaBeneficiaryId').value = btn.dataset.id;
    document.getElementById('vitaName').textContent     = btn.dataset.name;
    document.getElementById('vitaDob').value            = btn.dataset.dob;
});
document.getElementById('manualVitaBeneficiary')?.addEventListener('change', function() {
    document.getElementById('manualVitaDob').value = this.options[this.selectedIndex].dataset.dob || '';
});

</script>
