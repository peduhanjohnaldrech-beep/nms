<?php $pageTitle = 'Micronutrient Supplementation (MNS)'; ?>

<div class="d-flex justify-content-between align-items-center my-3">
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
            <div class="col-md-3">
                <label class="form-label small">Round</label>
                <select name="round" class="form-select form-select-sm">
                    <option value="February" <?= $round === 'February' ? 'selected' : '' ?>>February</option>
                    <option value="August"   <?= $round === 'August'   ? 'selected' : '' ?>>August</option>
                </select>
            </div>
            <?php else: ?>
            <input type="hidden" name="round" value="<?= htmlspecialchars($round) ?>">
            <?php endif; ?>
            <div class="col-md-2">
                <label class="form-label small">Year</label>
                <input type="number" name="year" class="form-control form-control-sm"
                       value="<?= $year ?>" min="2000" max="<?= date('Y') + 1 ?>">
            </div>
            <div class="col-auto">
                <button type="submit" class="btn btn-sm btn-primary"><i class="bi bi-search me-1"></i>Filter</button>
            </div>
            <div class="col-auto ms-auto">
                <?php
                $exportType = match($tab) {
                    'mnp'   => 'mns_mnp',
                    'lnssq' => 'mns_lns',
                    default => 'mns_vita',
                };
                ?>
                <a href="<?= APP_URL ?>/reports/export?type=<?= $exportType ?>&format=excel&year=<?= $year ?>&period=<?= urlencode($round) ?>" class="btn btn-sm btn-success">
                    <i class="bi bi-file-earmark-excel me-1"></i>Export Excel
                </a>
            </div>
            <input type="hidden" name="tab" value="<?= htmlspecialchars($tab) ?>">
        </form>
    </div>
</div>

<!-- Tabs -->
<ul class="nav nav-tabs mb-3" id="mnsTabs">
    <li class="nav-item">
        <a class="nav-link <?= $tab === 'vitaminA' ? 'active' : '' ?>"
           href="?round=<?= urlencode($round) ?>&year=<?= $year ?>&tab=vitaminA">
            <i class="bi bi-capsule me-1"></i>Vitamin A
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link <?= $tab === 'mnp' ? 'active' : '' ?>"
           href="?round=<?= urlencode($round) ?>&year=<?= $year ?>&tab=mnp">
            <i class="bi bi-prescription me-1"></i>MNP
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link <?= $tab === 'lnssq' ? 'active' : '' ?>"
           href="?round=<?= urlencode($round) ?>&year=<?= $year ?>&tab=lnssq">
            <i class="bi bi-prescription2 me-1"></i>LNS-SQ
        </a>
    </li>
</ul>

<?php $canRecord = hasPerm('programs'); ?>

<!-- ── Vitamin A Tab ── -->
<?php if ($tab === 'vitaminA'): ?>
<div class="row g-3">
    <!-- Not Yet Covered -->
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white fw-semibold d-flex justify-content-between align-items-center">
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
            <div class="card-header bg-white fw-semibold">
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
            <div class="card-header bg-white fw-semibold">
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
<div class="row g-3">
    <!-- Not Yet Received -->
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white fw-semibold d-flex justify-content-between align-items-center">
                <span>
                    <i class="bi bi-people me-1 text-warning"></i>
                    Not Yet Received — <?= $year ?>
                    <span class="badge bg-secondary ms-1"><?= count($mnpNotYet) ?></span>
                    <small class="text-muted fw-normal ms-2">(ages as of <?= htmlspecialchars($asOfDate) ?>)</small>
                </span>
                <?php if ($canRecord): ?>
                <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#mnpModal">
                    <i class="bi bi-plus-circle me-1"></i>Add MNP Record
                </button>
                <?php endif; ?>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive" style="max-height:400px;overflow-y:auto;">
                    <table class="table table-sm table-hover mb-0 align-middle">
                        <thead class="table-light">
                            <tr><th>Name</th><th>Barangay</th><th>Age (mo)</th>
                            <?php if ($canRecord): ?><th></th><?php endif; ?></tr>
                        </thead>
                        <tbody>
                            <?php if (empty($mnpNotYet)): ?>
                            <tr><td colspan="4" class="text-center text-muted py-4">
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
                                <?php if ($canRecord): ?>
                                <td>
                                    <button type="button" class="btn btn-sm btn-outline-primary mnp-quick-btn"
                                            data-bs-toggle="modal" data-bs-target="#mnpModal"
                                            data-id="<?= $b['id'] ?>"
                                            data-name="<?= htmlspecialchars($b['last_name'] . ', ' . $b['first_name']) ?>"
                                            data-age="<?= $b['age_months'] ?>">
                                        <i class="bi bi-prescription me-1"></i>Record
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

    <!-- Records Given -->
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white fw-semibold">
                <i class="bi bi-check2-all me-1 text-success"></i>
                MNP Records Given — <?= $year ?>
                <span class="badge bg-success ms-1"><?= count($mnpRecords) ?></span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive" style="max-height:400px;overflow-y:auto;">
                    <table class="table table-sm table-hover mb-0 align-middle">
                        <thead class="table-light">
                            <tr><th>Name</th><th>Barangay</th><th>Age Group</th><th>Date</th><th>Done</th></tr>
                        </thead>
                        <tbody>
                            <?php if (empty($mnpRecords)): ?>
                            <tr><td colspan="5" class="text-center text-muted py-4">No MNP records for <?= $year ?>.</td></tr>
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
                                <td>
                                    <?php if ($m['completed_routine']): ?>
                                    <span class="badge bg-success">Yes</span>
                                    <?php elseif ($canRecord): ?>
                                    <form method="post" action="<?= APP_URL ?>/programs/mns/mnp/<?= $m['id'] ?>/complete" class="d-inline">
                                        <?= \Core\Session::csrfField() ?>
                                        <input type="hidden" name="year" value="<?= $year ?>">
                                        <button type="submit" class="btn btn-xs btn-outline-success py-0 px-1" style="font-size:.75rem;">
                                            <i class="bi bi-check2"></i> Mark Done
                                        </button>
                                    </form>
                                    <?php else: ?>
                                    <span class="badge bg-warning text-dark">No</span>
                                    <?php endif; ?>
                                </td>
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
<?php else: ?>
<?php if ($year < (int)date('Y')): ?>
<div class="alert alert-info py-2 mb-3">
    <i class="bi bi-info-circle me-1"></i>
    Viewing records for <strong><?= $year ?></strong>. Age eligibility is calculated as of <strong><?= htmlspecialchars(date('F j, Y', strtotime($asOfDate))) ?></strong> (Dec 31 of that year).
</div>
<?php endif; ?>
<div class="row g-3">
    <!-- Not Yet Received -->
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white fw-semibold d-flex justify-content-between align-items-center">
                <span>
                    <i class="bi bi-people me-1 text-warning"></i>
                    Not Yet Received — <?= $year ?>
                    <span class="badge bg-secondary ms-1"><?= count($lnsNotYet) ?></span>
                    <small class="text-muted fw-normal ms-2">(ages as of <?= htmlspecialchars($asOfDate) ?>)</small>
                </span>
                <?php if ($canRecord): ?>
                <button class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#lnssqModal">
                    <i class="bi bi-plus-circle me-1"></i>Add LNS-SQ Record
                </button>
                <?php endif; ?>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive" style="max-height:400px;overflow-y:auto;">
                    <table class="table table-sm table-hover mb-0 align-middle">
                        <thead class="table-light">
                            <tr><th>Name</th><th>Barangay</th><th>Age (mo)</th>
                            <?php if ($canRecord): ?><th></th><?php endif; ?></tr>
                        </thead>
                        <tbody>
                            <?php if (empty($lnsNotYet)): ?>
                            <tr><td colspan="4" class="text-center text-muted py-4">
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
                                <?php if ($canRecord): ?>
                                <td>
                                    <button type="button" class="btn btn-sm btn-outline-success lns-quick-btn"
                                            data-bs-toggle="modal" data-bs-target="#lnssqModal"
                                            data-id="<?= $b['id'] ?>"
                                            data-name="<?= htmlspecialchars($b['last_name'] . ', ' . $b['first_name']) ?>"
                                            data-age="<?= $b['age_months'] ?>">
                                        <i class="bi bi-prescription2 me-1"></i>Record
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

    <!-- Records Given -->
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white fw-semibold">
                <i class="bi bi-check2-all me-1 text-success"></i>
                LNS-SQ Records Given — <?= $year ?>
                <span class="badge bg-success ms-1"><?= count($lnsRecords) ?></span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive" style="max-height:400px;overflow-y:auto;">
                    <table class="table table-sm table-hover mb-0 align-middle">
                        <thead class="table-light">
                            <tr><th>Name</th><th>Barangay</th><th>Age Group</th><th>Date</th><th>Done</th></tr>
                        </thead>
                        <tbody>
                            <?php if (empty($lnsRecords)): ?>
                            <tr><td colspan="5" class="text-center text-muted py-4">No LNS-SQ records for <?= $year ?>.</td></tr>
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
                                <td>
                                    <?php if ($l['completed_routine']): ?>
                                    <span class="badge bg-success">Yes</span>
                                    <?php elseif ($canRecord): ?>
                                    <form method="post" action="<?= APP_URL ?>/programs/mns/lnssq/<?= $l['id'] ?>/complete" class="d-inline">
                                        <?= \Core\Session::csrfField() ?>
                                        <input type="hidden" name="year" value="<?= $year ?>">
                                        <button type="submit" class="btn btn-xs btn-outline-success py-0 px-1" style="font-size:.75rem;">
                                            <i class="bi bi-check2"></i> Mark Done
                                        </button>
                                    </form>
                                    <?php else: ?>
                                    <span class="badge bg-warning text-dark">No</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
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

<!-- ── MNP Modal ── -->
<div class="modal fade" id="mnpModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="<?= APP_URL ?>/programs/mns/mnp" method="post">
                <?= \Core\Session::csrfField() ?>
                <input type="hidden" name="year" value="<?= $year ?>">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-prescription me-2"></i>Add MNP Record</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Beneficiary <span class="text-danger">*</span></label>
                        <select name="beneficiary_id" class="form-select" required id="mnpBeneficiarySelect">
                            <option value="">— Select Beneficiary —</option>
                            <?php foreach ($allBeneficiaries as $b): ?>
                            <option value="<?= $b['id'] ?>">
                                <?= htmlspecialchars($b['last_name'] . ', ' . $b['first_name'] . ' (' . $b['barangay'] . ')') ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Date Given</label>
                            <input type="date" name="date_given" class="form-control" value="<?= date('Y-m-d') ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Age Group</label>
                            <select name="age_group" class="form-select" id="mnpAgeGroupSelect">
                                <option value="6-11 months">6–11 months</option>
                                <option value="12-23 months">12–23 months</option>
                                <option value="24-59 months">24–59 months</option>
                            </select>
                        </div>
                    </div>
                    <div class="mt-3">
                        <label class="form-label fw-semibold">Notes</label>
                        <input type="text" name="notes" class="form-control" placeholder="Optional">
                    </div>
                    <div class="form-check mt-3">
                        <input class="form-check-input" type="checkbox" name="completed_routine" value="1" id="mnpComplete">
                        <label class="form-check-label" for="mnpComplete">Routine completed</label>
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

<!-- ── LNS-SQ Modal ── -->
<div class="modal fade" id="lnssqModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="<?= APP_URL ?>/programs/mns/lnssq" method="post">
                <?= \Core\Session::csrfField() ?>
                <input type="hidden" name="year" value="<?= $year ?>">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-prescription2 me-2"></i>Add LNS-SQ Record</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Beneficiary <span class="text-danger">*</span></label>
                        <select name="beneficiary_id" class="form-select" required id="lnsBeneficiarySelect">
                            <option value="">— Select Beneficiary —</option>
                            <?php foreach ($allBeneficiaries as $b): ?>
                            <option value="<?= $b['id'] ?>">
                                <?= htmlspecialchars($b['last_name'] . ', ' . $b['first_name'] . ' (' . $b['barangay'] . ')') ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Date Given</label>
                            <input type="date" name="date_given" class="form-control" value="<?= date('Y-m-d') ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Age Group</label>
                            <select name="age_group" class="form-select" id="lnsAgeGroupSelect">
                                <option value="6-11 months">6–11 months</option>
                                <option value="12-23 months">12–23 months</option>
                            </select>
                        </div>
                    </div>
                    <div class="mt-3">
                        <label class="form-label fw-semibold">Notes</label>
                        <input type="text" name="notes" class="form-control" placeholder="Optional">
                    </div>
                    <div class="form-check mt-3">
                        <input class="form-check-input" type="checkbox" name="completed_routine" value="1" id="lnsComplete">
                        <label class="form-check-label" for="lnsComplete">Routine completed</label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success"><i class="bi bi-save me-1"></i>Save</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.getElementById('vitaModal')?.addEventListener('show.bs.modal', function(e) {
    const btn = e.relatedTarget;
    document.getElementById('vitaBeneficiaryId').value = btn.dataset.id;
    document.getElementById('vitaName').textContent     = btn.dataset.name;
    document.getElementById('vitaDob').value            = btn.dataset.dob;
});
document.getElementById('manualVitaBeneficiary')?.addEventListener('change', function() {
    document.getElementById('manualVitaDob').value = this.options[this.selectedIndex].dataset.dob || '';
});

// MNP quick-record: pre-select beneficiary and age group from "Not Yet Received" list
document.getElementById('mnpModal')?.addEventListener('show.bs.modal', function(e) {
    const btn = e.relatedTarget;
    if (!btn || !btn.dataset.id) return;
    const sel = document.getElementById('mnpBeneficiarySelect');
    if (sel) sel.value = btn.dataset.id;
    const age = parseInt(btn.dataset.age || '0', 10);
    const ag  = document.getElementById('mnpAgeGroupSelect');
    if (ag) ag.value = age <= 11 ? '6-11 months' : (age <= 23 ? '12-23 months' : '24-59 months');
});

// LNS-SQ quick-record: pre-select beneficiary and age group
document.getElementById('lnssqModal')?.addEventListener('show.bs.modal', function(e) {
    const btn = e.relatedTarget;
    if (!btn || !btn.dataset.id) return;
    const sel = document.getElementById('lnsBeneficiarySelect');
    if (sel) sel.value = btn.dataset.id;
    const age = parseInt(btn.dataset.age || '0', 10);
    const ag  = document.getElementById('lnsAgeGroupSelect');
    if (ag) ag.value = age <= 11 ? '6-11 months' : '12-23 months';
});
</script>
