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
            <div class="card-header fw-semibold d-flex justify-content-between align-items-center">
                <span>
                    <i class="bi bi-people me-1 text-warning"></i>
                    Not Yet Received — <?= $year ?>
                    <span class="badge bg-secondary ms-1"><?= count($mnpNotYet) ?></span>
                    <small class="text-muted fw-normal ms-2">(ages as of <?= htmlspecialchars($asOfDate) ?>)</small>
                </span>
                <?php if ($canRecord): ?>
                <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#mnpModal">
                    <i class="bi bi-plus-circle me-1"></i>Record MNP
                </button>
                <?php endif; ?>
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
            <div class="card-header fw-semibold d-flex justify-content-between align-items-center">
                <span>
                    <i class="bi bi-people me-1 text-warning"></i>
                    Not Yet Received — <?= $year ?>
                    <span class="badge bg-secondary ms-1"><?= count($lnsNotYet) ?></span>
                    <small class="text-muted fw-normal ms-2">(ages as of <?= htmlspecialchars($asOfDate) ?>)</small>
                </span>
                <?php if ($canRecord): ?>
                <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#lnsModal">
                    <i class="bi bi-plus-circle me-1"></i>Record LNS-SQ
                </button>
                <?php endif; ?>
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
                            <tr><th>Name</th><th>Barangay</th><th>Age Group</th><th>Date</th><th>Sachets</th><th>Weight</th><th>Done</th><?php if ($canRecord): ?><th></th><?php endif; ?></tr>
                        </thead>
                        <tbody>
                            <?php if (empty($lnsRecords)): ?>
                            <tr><td colspan="<?= $canRecord ? 8 : 7 ?>" class="text-center text-muted py-4">No LNS-SQ records for <?= $year ?>.</td></tr>
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
                                <td class="text-center">
                                    <?php if (isset($l['weight_kg']) && $l['weight_kg'] !== null): ?>
                                    <span class="small"><?= number_format((float)$l['weight_kg'], 1) ?> kg</span>
                                    <?php else: ?>
                                    <span class="text-muted">—</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge bg-<?= $l['completed_routine'] ? 'success' : 'warning text-dark' ?>">
                                        <?= $l['completed_routine'] ? 'Yes' : 'No' ?>
                                    </span>
                                </td>
                                <?php if ($canRecord): ?>
                                <td>
                                    <button type="button" class="btn btn-xs btn-outline-success py-0 px-1 lns-recover-btn"
                                            style="font-size:.75rem;"
                                            data-id="<?= $l['beneficiary_id'] ?>"
                                            data-name="<?= htmlspecialchars($l['last_name'] . ', ' . $l['first_name']) ?>"
                                            data-bs-toggle="modal" data-bs-target="#lnsRecoverModal"
                                            title="Mark as recovered/graduated">
                                        <i class="bi bi-heart-pulse"></i>
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
<?php
// Determine which rounds have passed based on today's date
$today       = date('Y-m-d');
$aprPassed   = $today >= "{$year}-04-15";
$octPassed   = $today >= "{$year}-10-15";

// Summary counts
$tclTotal    = count($tclList);
$tclMissing  = 0;
foreach ($tclList as $_r) {
    $_lnsE  = in_array($_r['opt_status'] ?? '', ['SUW','UW']) || in_array($_r['wflh_status'] ?? '', ['SW','MW']) || in_array($_r['hfa_status'] ?? '', ['SSt','St']);
    $_mnpE  = ($_r['age_months'] >= 6 && $_r['age_months'] <= 23);
    $_vitaE = ($_r['age_months'] >= 6 && $_r['age_months'] <= 59);
    if (($_vitaE && $aprPassed && !$_r['vita_april_date'])
     || ($_vitaE && $octPassed && !$_r['vita_oct_date'])
     || ($_mnpE  && !$_r['mnp_date'])
     || ($_lnsE  && !$_r['lns_date'])) {
        $tclMissing++;
    }
}
$tclComplete = $tclTotal - $tclMissing;
$tclPct      = $tclTotal > 0 ? round($tclComplete / $tclTotal * 100) : 0;
?>
<!-- Summary bar -->
<div class="row g-2 mb-3">
    <div class="col-4">
        <div class="card border-0 shadow-sm text-center py-2">
            <div class="fs-3 fw-bold"><?= $tclTotal ?></div>
            <div class="small text-muted">Total Children</div>
        </div>
    </div>
    <div class="col-4">
        <div class="card border-0 shadow-sm text-center py-2">
            <div class="fs-3 fw-bold text-success"><?= $tclComplete ?></div>
            <div class="small text-muted">Fully Covered</div>
        </div>
    </div>
    <div class="col-4">
        <div class="card border-0 shadow-sm text-center py-2">
            <div class="fs-3 fw-bold text-danger"><?= $tclMissing ?></div>
            <div class="small text-muted">Need Follow-up</div>
        </div>
    </div>
</div>

<!-- TCL table card -->
<div class="card border-0 shadow-sm">
    <div class="card-header d-flex justify-content-between align-items-center py-2">
        <span class="fw-semibold">
            <i class="bi bi-table me-1 text-primary"></i>
            Target Client List — <?= $year ?>
            <span class="badge bg-secondary ms-1"><?= $tclTotal ?></span>
        </span>
        <div class="d-flex align-items-center gap-2">
            <input type="text" id="tclSearch" class="form-control form-control-sm" placeholder="Search name…" style="width:180px;" oninput="tclFilter(this.value)">
            <button onclick="window.print()" class="btn btn-sm btn-outline-secondary">
                <i class="bi bi-printer me-1"></i>Print
            </button>
        </div>
    </div>
    <!-- Legend -->
    <div class="px-3 py-2 border-bottom bg-light d-flex flex-wrap gap-3 small text-muted">
        <span><i class="bi bi-check-circle-fill text-success me-1"></i>Given (date shown)</span>
        <span><i class="bi bi-x-circle-fill text-danger me-1"></i>Missed (round passed)</span>
        <span><i class="bi bi-clock text-warning me-1"></i>Upcoming</span>
        <span class="text-muted">— Not eligible</span>
    </div>
    <div class="table-responsive">
        <table class="table table-sm table-bordered table-hover mb-0 align-middle" id="tclTable" style="font-size:.82rem;">
            <thead class="table-light">
                <tr>
                    <th rowspan="2" class="align-middle">#</th>
                    <th rowspan="2" class="align-middle">Name</th>
                    <th rowspan="2" class="align-middle text-center">Age</th>
                    <th rowspan="2" class="align-middle">Barangay</th>
                    <th rowspan="2" class="align-middle">Purok</th>
                    <th rowspan="2" class="align-middle text-center">OPT</th>
                    <th colspan="2" class="text-center" style="background:#fffde7;">Vitamin A</th>
                    <th rowspan="2" class="align-middle text-center" style="background:#e3f2fd;">MNP<br><small class="fw-normal text-muted">6–23 mo</small></th>
                    <th rowspan="2" class="align-middle text-center" style="background:#e8f5e9;">LNS-SQ<br><small class="fw-normal text-muted">Wasted/Stunted</small></th>
                </tr>
                <tr>
                    <th class="text-center" style="background:#fffde7;">April</th>
                    <th class="text-center" style="background:#fffde7;">October</th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($tclList)): ?>
            <tr><td colspan="10" class="text-center text-muted py-5">
                <i class="bi bi-people fs-2 d-block mb-2 text-muted"></i>
                No children aged 0–59 months found<?= $filterBarangay ? ' in ' . htmlspecialchars($filterBarangay) : '' ?>.
            </td></tr>
            <?php endif; ?>
            <?php $rowNum = 0; foreach ($tclList as $r):
                $rowNum++;
                $lnsElig  = in_array($r['opt_status'] ?? '', ['SUW','UW'])
                         || in_array($r['wflh_status'] ?? '', ['SW','MW'])
                         || in_array($r['hfa_status']  ?? '', ['SSt','St']);
                $mnpElig  = ($r['age_months'] >= 6 && $r['age_months'] <= 23);
                $vitaElig = ($r['age_months'] >= 6 && $r['age_months'] <= 59);

                // A row is flagged only for past-due gaps
                $rowAlert = ($vitaElig && $aprPassed && !$r['vita_april_date'])
                         || ($vitaElig && $octPassed && !$r['vita_oct_date'])
                         || ($mnpElig  && !$r['mnp_date'])
                         || ($lnsElig  && !$r['lns_date']);

                $statusColors = ['SUW'=>'danger','UW'=>'warning text-dark','Normal'=>'success','OW'=>'info text-dark','OB'=>'primary'];
                $statusColor  = $statusColors[$r['opt_status'] ?? ''] ?? 'secondary';
            ?>
            <tr class="<?= $rowAlert ? 'table-danger bg-opacity-10' : '' ?>">
                <td class="text-muted text-center" style="font-size:.75rem;"><?= $rowNum ?></td>
                <td>
                    <a href="<?= APP_URL ?>/beneficiaries/<?= $r['id'] ?>" class="text-decoration-none fw-semibold">
                        <?= htmlspecialchars($r['last_name'] . ', ' . $r['first_name']) ?>
                    </a>
                </td>
                <td class="text-center text-nowrap"><?= $r['age_months'] ?> mo</td>
                <td><?= htmlspecialchars($r['barangay']) ?></td>
                <td class="text-muted"><?= htmlspecialchars($r['purok_zone'] ?? '—') ?></td>
                <td class="text-center">
                    <?php if ($r['opt_status']): ?>
                    <span class="badge bg-<?= $statusColor ?>"><?= htmlspecialchars($r['opt_status']) ?></span>
                    <?php else: ?>
                    <span class="text-muted">—</span>
                    <?php endif; ?>
                </td>
                <!-- Vitamin A April -->
                <?php
                $aprMissed = $vitaElig && $aprPassed && !$r['vita_april_date'];
                $aprUpcoming = $vitaElig && !$aprPassed && !$r['vita_april_date'];
                ?>
                <td class="text-center <?= $aprMissed ? 'bg-danger bg-opacity-10' : '' ?>" style="background:<?= $r['vita_april_date'] ? '#f1fdf4' : '' ?>">
                    <?php if (!$vitaElig): ?>
                        <span class="text-muted">—</span>
                    <?php elseif ($r['vita_april_date']): ?>
                        <i class="bi bi-check-circle-fill text-success"></i>
                        <div class="text-success" style="font-size:.72rem;"><?= htmlspecialchars($r['vita_april_date']) ?></div>
                    <?php elseif ($aprMissed): ?>
                        <i class="bi bi-x-circle-fill text-danger"></i>
                    <?php else: ?>
                        <i class="bi bi-clock text-warning"></i>
                    <?php endif; ?>
                </td>
                <!-- Vitamin A October -->
                <?php
                $octMissed = $vitaElig && $octPassed && !$r['vita_oct_date'];
                $octUpcoming = $vitaElig && !$octPassed && !$r['vita_oct_date'];
                ?>
                <td class="text-center <?= $octMissed ? 'bg-danger bg-opacity-10' : '' ?>" style="background:<?= $r['vita_oct_date'] ? '#f1fdf4' : '' ?>">
                    <?php if (!$vitaElig): ?>
                        <span class="text-muted">—</span>
                    <?php elseif ($r['vita_oct_date']): ?>
                        <i class="bi bi-check-circle-fill text-success"></i>
                        <div class="text-success" style="font-size:.72rem;"><?= htmlspecialchars($r['vita_oct_date']) ?></div>
                    <?php elseif ($octMissed): ?>
                        <i class="bi bi-x-circle-fill text-danger"></i>
                    <?php else: ?>
                        <i class="bi bi-clock text-warning"></i>
                    <?php endif; ?>
                </td>
                <!-- MNP -->
                <td class="text-center <?= $mnpElig && !$r['mnp_date'] ? 'bg-danger bg-opacity-10' : '' ?>" style="background:<?= $r['mnp_date'] ? '#f1fdf4' : '' ?>">
                    <?php if (!$mnpElig): ?>
                        <span class="text-muted">—</span>
                    <?php elseif ($r['mnp_date']): ?>
                        <i class="bi bi-check-circle-fill text-success"></i>
                        <div class="text-success" style="font-size:.72rem;"><?= htmlspecialchars($r['mnp_date']) ?></div>

                    <?php else: ?>
                        <i class="bi bi-x-circle-fill text-danger"></i>
                    <?php endif; ?>
                </td>
                <!-- LNS-SQ -->
                <td class="text-center <?= $lnsElig && !$r['lns_date'] ? 'bg-danger bg-opacity-10' : '' ?>" style="background:<?= $r['lns_date'] ? '#f1fdf4' : '' ?>">
                    <?php if (!$lnsElig): ?>
                        <span class="text-muted">—</span>
                    <?php elseif ($r['lns_date']): ?>
                        <i class="bi bi-check-circle-fill text-success"></i>
                        <div class="text-success" style="font-size:.72rem;"><?= htmlspecialchars($r['lns_date']) ?></div>

                    <?php else: ?>
                        <i class="bi bi-x-circle-fill text-danger"></i>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<script>
function tclFilter(q) {
    q = q.toLowerCase();
    document.querySelectorAll('#tclTable tbody tr').forEach(function(row) {
        var name = row.cells[1] ? row.cells[1].textContent.toLowerCase() : '';
        row.style.display = name.includes(q) ? '' : 'none';
    });
}
</script>
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
                    <div id="vitaEligPanel" class="alert py-2 px-3 small mb-3 d-none"></div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Distribution Date</label>
                        <input type="date" name="distribution_date" id="vitaDate" class="form-control" value="<?= date('Y-m-d') ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Administered By</label>
                        <input type="text" name="administered_by" class="form-control"
                               value="<?= htmlspecialchars(\Core\Session::get('user_name', '')) ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Delivery Mode</label>
                        <select name="delivery_mode" class="form-select">
                            <option value="Health Post">Health Post</option>
                            <option value="Home Visit">Home Visit</option>
                            <option value="Outreach">Outreach</option>
                        </select>
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
                    <div id="manualVitaEligPanel" class="alert py-2 px-3 small mb-3 d-none"></div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Distribution Date</label>
                        <input type="date" name="distribution_date" id="manualVitaDate" class="form-control" value="<?= date('Y-m-d') ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Administered By</label>
                        <input type="text" name="administered_by" class="form-control"
                               value="<?= htmlspecialchars(\Core\Session::get('user_name', '')) ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Delivery Mode</label>
                        <select name="delivery_mode" class="form-select">
                            <option value="Health Post">Health Post</option>
                            <option value="Home Visit">Home Visit</option>
                            <option value="Outreach">Outreach</option>
                        </select>
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

<!-- ── MNP Record Modal ── -->
<div class="modal fade" id="mnpModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="<?= APP_URL ?>/programs/mns/mnp" method="post">
                <?= \Core\Session::csrfField() ?>
                <input type="hidden" name="year" value="<?= $year ?>">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-prescription2 me-2"></i>Record MNP Distribution</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Beneficiary <span class="text-danger">*</span></label>
                        <select name="beneficiary_id" class="form-select" required id="mnpBeneficiarySelect">
                            <option value="">— Select Beneficiary —</option>
                            <?php foreach ($allBeneficiaries as $b): ?>
                            <option value="<?= $b['id'] ?>" data-dob="<?= $b['date_of_birth'] ?>">
                                <?= htmlspecialchars($b['last_name'] . ', ' . $b['first_name'] . ' (' . $b['barangay'] . ')') ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div id="mnpEligPanel" class="alert py-2 px-3 small mb-3 d-none"></div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Date Given</label>
                        <input type="date" name="date_given" id="mnpDate" class="form-control" value="<?= date('Y-m-d') ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Age Group <span class="text-muted fw-normal small">(auto-set)</span></label>
                        <input type="text" id="mnpAgeGroupDisplay" class="form-control bg-light" readonly>
                        <input type="hidden" name="age_group" id="mnpAgeGroupHidden">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Sachets Distributed</label>
                        <input type="number" name="sachets_distributed" class="form-control" value="90" min="1" max="90" placeholder="90 sachets per box">
                    </div>
                    <div class="mb-2">
                        <div class="form-check">
                            <input type="checkbox" name="counseling_given" id="mnpCounseling" class="form-check-input" value="1">
                            <label for="mnpCounseling" class="form-check-label fw-semibold">Counseling / IYCF orientation given</label>
                        </div>
                    </div>
                    <div class="mb-3">
                        <div class="form-check">
                            <input type="checkbox" name="completed_routine" id="mnpCompleted" class="form-check-input" value="1">
                            <label for="mnpCompleted" class="form-check-label">Routine completed</label>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Notes</label>
                        <input type="text" name="notes" class="form-control" placeholder="Optional">
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

<!-- ── LNS-SQ Record Modal ── -->
<div class="modal fade" id="lnsModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="<?= APP_URL ?>/programs/mns/lnssq" method="post">
                <?= \Core\Session::csrfField() ?>
                <input type="hidden" name="year" value="<?= $year ?>">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-bag-plus me-2"></i>Record LNS-SQ Distribution</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Beneficiary <span class="text-danger">*</span></label>
                        <select name="beneficiary_id" class="form-select" required>
                            <option value="">— Select Beneficiary —</option>
                            <?php foreach ($allBeneficiaries as $b): ?>
                            <option value="<?= $b['id'] ?>" data-dob="<?= $b['date_of_birth'] ?>">
                                <?= htmlspecialchars($b['last_name'] . ', ' . $b['first_name'] . ' (' . $b['barangay'] . ')') ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div id="lnsEligPanel" class="alert py-2 px-3 small mb-3 d-none"></div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Date Given</label>
                        <input type="date" name="date_given" id="lnsDate" class="form-control" value="<?= date('Y-m-d') ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Age Group <span class="text-muted fw-normal small">(auto-set)</span></label>
                        <input type="text" id="lnsAgeGroupDisplay" class="form-control bg-light" readonly>
                        <input type="hidden" name="age_group" id="lnsAgeGroupHidden">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Sachets Distributed</label>
                        <input type="number" name="sachets_distributed" class="form-control" value="30" min="1" max="30" placeholder="30 sachets per pack">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Current Weight (kg)</label>
                        <input type="number" name="weight_kg" class="form-control" step="0.01" min="1" max="30" placeholder="e.g. 8.5">
                        <div class="form-text">Optional — record at each monthly distribution</div>
                    </div>
                    <div class="mb-3">
                        <div class="form-check">
                            <input type="checkbox" name="completed_routine" id="lnsCompleted" class="form-check-input" value="1">
                            <label for="lnsCompleted" class="form-check-label">Routine completed</label>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Notes</label>
                        <input type="text" name="notes" class="form-control" placeholder="Optional">
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

<!-- ── LNS-SQ Recovery Modal ── -->
<div class="modal fade" id="lnsRecoverModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="<?= APP_URL ?>/programs/mns/lnssq/recover" method="post">
                <?= \Core\Session::csrfField() ?>
                <input type="hidden" name="year" value="<?= $year ?>">
                <input type="hidden" name="beneficiary_id" id="lnsRecoverBid">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-heart-pulse me-2 text-success"></i>Mark Child as Recovered</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted small mb-3">Marking a child as recovered/graduated removes them from the LNS-SQ not-yet-received list for this year. This is irreversible unless deleted manually.</p>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Child</label>
                        <div class="fw-semibold" id="lnsRecoverName"></div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Recovery / Graduation Date</label>
                        <input type="date" name="recovery_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Notes</label>
                        <input type="text" name="notes" class="form-control" placeholder="e.g. Reached normal weight, graduated program">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success"><i class="bi bi-check-circle me-1"></i>Confirm Recovery</button>
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
    updateVitaPanel(btn.dataset.dob, document.getElementById('vitaDate').value);
});
document.getElementById('vitaDate')?.addEventListener('change', function() {
    const dob = document.getElementById('vitaDob').value;
    updateVitaPanel(dob, this.value);
});

document.getElementById('manualVitaBeneficiary')?.addEventListener('change', function() {
    const dob = this.options[this.selectedIndex].dataset.dob || '';
    document.getElementById('manualVitaDob').value = dob;
    updateVitaPanel(dob, document.getElementById('manualVitaDate').value, 'manualVitaEligPanel');
});
document.getElementById('manualVitaDate')?.addEventListener('change', function() {
    const dob = document.getElementById('manualVitaDob').value;
    updateVitaPanel(dob, this.value, 'manualVitaEligPanel');
});

document.getElementById('mnpBeneficiarySelect')?.addEventListener('change', function() {
    const dob = this.options[this.selectedIndex].dataset.dob || '';
    updateMnpPanel(dob, document.getElementById('mnpDate').value);
});
document.getElementById('mnpDate')?.addEventListener('change', function() {
    const sel = document.getElementById('mnpBeneficiarySelect');
    const dob = sel ? (sel.options[sel.selectedIndex]?.dataset.dob || '') : '';
    updateMnpPanel(dob, this.value);
});

document.getElementById('lnsModal')?.querySelector('select[name="beneficiary_id"]')
    ?.addEventListener('change', function() {
        const dob = this.options[this.selectedIndex].dataset.dob || '';
        updateLnsPanel(dob, document.getElementById('lnsDate').value);
    });
document.getElementById('lnsDate')?.addEventListener('change', function() {
    const sel = document.getElementById('lnsModal')?.querySelector('select[name="beneficiary_id"]');
    const dob = sel ? (sel.options[sel.selectedIndex]?.dataset.dob || '') : '';
    updateLnsPanel(dob, this.value);
});

document.querySelectorAll('.lns-recover-btn').forEach(function(btn) {
    btn.addEventListener('click', function() {
        document.getElementById('lnsRecoverBid').value  = this.dataset.id;
        document.getElementById('lnsRecoverName').textContent = this.dataset.name;
    });
});

// ── Eligibility helper functions ─────────────────────────────────────────────
function ageInMonths(dob, refDate) {
    if (!dob || !refDate) return -1;
    const d = new Date(dob), r = new Date(refDate);
    if (isNaN(d) || isNaN(r)) return -1;
    let m = (r.getFullYear() - d.getFullYear()) * 12 + (r.getMonth() - d.getMonth());
    if (r.getDate() < d.getDate()) m--;
    return m;
}

function showPanel(panelId, type, msg) {
    const el = document.getElementById(panelId);
    if (!el) return;
    el.className = 'alert py-2 px-3 small mb-3 alert-' + type;
    el.textContent = msg;
}

function hidePanel(panelId) {
    const el = document.getElementById(panelId);
    if (el) el.className = 'alert py-2 px-3 small mb-3 d-none';
}

function updateVitaPanel(dob, date, panelId) {
    panelId = panelId || 'vitaEligPanel';
    const mo = ageInMonths(dob, date);
    if (mo < 0) { hidePanel(panelId); return; }
    if (mo < 6)  { showPanel(panelId, 'danger',  '\u26a0 Not yet eligible — child is only ' + mo + ' month(s) old (minimum: 6 months).'); return; }
    if (mo >= 60) { showPanel(panelId, 'danger', '\u26a0 Age exceeded — child is ' + mo + ' months old (Vitamin A ends at 60 months / 5 years).'); return; }
    const dose = mo <= 11 ? '100,000 IU — Blue capsule' : '200,000 IU — Red capsule';
    showPanel(panelId, 'success', '\u2713 Age ' + mo + ' month(s) — Dose: ' + dose);
}

function updateMnpPanel(dob, date) {
    const mo = ageInMonths(dob, date);
    const hidden  = document.getElementById('mnpAgeGroupHidden');
    const display = document.getElementById('mnpAgeGroupDisplay');
    if (mo < 0) { hidePanel('mnpEligPanel'); if (display) display.value = ''; return; }
    if (mo < 6)  {
        showPanel('mnpEligPanel', 'danger', '\u26a0 Not yet eligible — child is only ' + mo + ' month(s) old (MNP starts at 6 months).');
        if (display) display.value = ''; if (hidden) hidden.value = ''; return;
    }
    if (mo >= 24) {
        showPanel('mnpEligPanel', 'danger', '\u26a0 Age exceeded — child is ' + mo + ' months old (MNP ends at 24 months).');
        if (display) display.value = ''; if (hidden) hidden.value = ''; return;
    }
    const grp = mo <= 11 ? '6-11 months' : '12-23 months';
    const cycle = mo <= 11 ? 'Cycle 1 (6–11 mo)' : 'Cycle 2 (12–23 mo)';
    if (display) display.value = grp;
    if (hidden)  hidden.value  = grp;
    showPanel('mnpEligPanel', 'success', '\u2713 Age ' + mo + ' month(s) — ' + cycle + ' \u2192 ' + grp);
}

function updateLnsPanel(dob, date) {
    const mo = ageInMonths(dob, date);
    const hidden  = document.getElementById('lnsAgeGroupHidden');
    const display = document.getElementById('lnsAgeGroupDisplay');
    if (mo < 0) { hidePanel('lnsEligPanel'); if (display) display.value = ''; return; }
    if (mo < 6)  {
        showPanel('lnsEligPanel', 'danger', '\u26a0 Not yet eligible — child is only ' + mo + ' month(s) old (LNS-SQ starts at 6 months).');
        if (display) display.value = ''; if (hidden) hidden.value = ''; return;
    }
    if (mo >= 24) {
        showPanel('lnsEligPanel', 'danger', '\u26a0 Age exceeded — child is ' + mo + ' months old (LNS-SQ ends at 24 months).');
        if (display) display.value = ''; if (hidden) hidden.value = ''; return;
    }
    const grp = mo <= 11 ? '6-11 months' : '12-23 months';
    if (display) display.value = grp;
    if (hidden)  hidden.value  = grp;
    showPanel('lnsEligPanel', 'success', '\u2713 Age ' + mo + ' month(s) — Group: ' + grp + ' (monthly 30-sachet pack)');
}

</script>
