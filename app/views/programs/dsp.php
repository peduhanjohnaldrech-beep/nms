<?php $pageTitle = 'DSP — Dietary Supplementation Program'; ?>

<div class="d-flex justify-content-between align-items-center my-3">
    <div>
        <h4 class="mb-0"><i class="bi bi-egg-fried me-2"></i>Dietary Supplementation Program</h4>
        <small class="text-muted">Feeding and nutrition intervention for malnourished children (wasted / severely wasted)</small>
    </div>
    <a href="<?= APP_URL ?>/reports/export?type=dsp&format=excel&year=<?= date('Y') ?>" class="btn btn-sm btn-success">
        <i class="bi bi-file-earmark-excel me-1"></i>Export Excel
    </a>
</div>

<!-- Filter Bar -->
<form method="get" action="" class="card border-0 shadow-sm mb-3 p-3">
    <div class="row g-2 align-items-end">
        <div class="col-md-4">
            <label class="form-label small fw-semibold mb-1">Barangay</label>
            <select name="barangay" class="form-select form-select-sm" onchange="this.form.submit()">
                <option value="">— All Barangays —</option>
                <?php foreach ($barangays as $b): ?>
                <option value="<?= htmlspecialchars($b['barangay']) ?>" <?= $filterBarangay === $b['barangay'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($b['barangay']) ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-3">
            <label class="form-label small fw-semibold mb-1">Purok / Zone</label>
            <select name="purok" class="form-select form-select-sm" <?= empty($puroks) ? 'disabled' : '' ?>>
                <option value="">— All Puroks —</option>
                <?php foreach ($puroks as $p): ?>
                <option value="<?= htmlspecialchars($p) ?>" <?= $filterPurok === $p ? 'selected' : '' ?>>
                    <?= htmlspecialchars($p) ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-auto">
            <button type="submit" class="btn btn-sm btn-primary">
                <i class="bi bi-funnel me-1"></i>Filter
            </button>
            <?php if ($filterBarangay !== '' || $filterPurok !== ''): ?>
            <a href="?" class="btn btn-sm btn-outline-secondary ms-1">
                <i class="bi bi-x-circle me-1"></i>Clear
            </a>
            <?php endif; ?>
        </div>
        <?php if ($filterBarangay !== '' || $filterPurok !== ''): ?>
        <div class="col-auto">
            <span class="badge bg-primary">
                Filtered: <?= htmlspecialchars($filterBarangay ?: 'All') ?><?= $filterPurok ? ' / ' . htmlspecialchars($filterPurok) : '' ?>
            </span>
        </div>
        <?php endif; ?>
        <?php if ($historyYear): ?>
        <input type="hidden" name="history_year" value="<?= $historyYear ?>">
        <?php endif; ?>
    </div>
</form>

<!-- Summary Stats -->
<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm text-center py-3">
            <div class="fs-2 fw-bold text-warning"><?= count($eligible) ?></div>
            <div class="small text-muted">Eligible</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm text-center py-3">
            <div class="fs-2 fw-bold text-success"><?= count($active) ?></div>
            <div class="small text-muted">Active</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm text-center py-3">
            <div class="fs-2 fw-bold text-primary"><?= count($readyToDischarge) ?></div>
            <div class="small text-muted">Ready to Discharge</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm text-center py-3">
            <div class="fs-2 fw-bold text-secondary"><?= count($history) ?></div>
            <div class="small text-muted">History (<?= $historyYear ?>)</div>
        </div>
    </div>
</div>

<!-- Ready to Discharge (shown only when relevant) -->
<?php if (!empty($readyToDischarge)): ?>
<div class="card border-0 shadow-sm mb-3 border-start border-4 border-success">
    <div class="card-header bg-success bg-opacity-10 text-success fw-semibold d-flex justify-content-between align-items-center">
        <span>
            <i class="bi bi-arrow-up-circle me-2"></i>
            Ready to Discharge
            <span class="badge bg-success ms-2"><?= count($readyToDischarge) ?></span>
            <span class="fw-normal small ms-1 text-success opacity-75">— 2 consecutive normal assessments + MUAC recorded</span>
        </span>
        <a href="<?= APP_URL ?>/reports/export?type=dsp_ready&format=excel" class="btn btn-sm btn-success">
            <i class="bi bi-file-earmark-excel me-1"></i>Export
        </a>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-sm mb-0 align-middle">
                <thead class="table-light">
                    <tr><th>Name</th><th>Barangay</th><th>Cycle</th><th>Intervention</th><th>WFL/H</th><th>WFA</th><th>MUAC</th><th>Last Assessment</th><th>Action</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($readyToDischarge as $r): ?>
                    <tr>
                        <td>
                            <a href="<?= APP_URL ?>/beneficiaries/<?= $r['beneficiary_id'] ?>" class="text-decoration-none fw-semibold">
                                <?= htmlspecialchars($r['last_name'] . ', ' . $r['first_name']) ?>
                            </a>
                            <?php if (DateHelper::ageInMonths($r['date_of_birth']) > 59): ?>
                            <span class="badge bg-secondary ms-1">Aged Out</span>
                            <?php endif; ?>
                        </td>
                        <td><?= htmlspecialchars($r['barangay']) ?></td>
                        <td><span class="badge bg-secondary">Cycle <?= $r['cycle_number'] ?></span></td>
                        <td>
                            <?php if (!empty($r['intervention_type'])): ?>
                            <span class="badge bg-info text-dark"><?= htmlspecialchars($r['intervention_type']) ?></span>
                            <?php else: ?>—<?php endif; ?>
                        </td>
                        <td>
                            <?php if (!empty($r['wflh_status'])): ?>
                            <span class="badge status-<?= strtolower($r['wflh_status']) ?>"><?= $r['wflh_status'] ?></span>
                            <?php else: ?><span class="badge bg-success">Normal</span><?php endif; ?>
                        </td>
                        <td><span class="badge status-<?= strtolower($r['nutritional_status']) ?>"><?= $r['nutritional_status'] ?></span></td>
                        <td><?= !empty($r['muac_cm']) ? $r['muac_cm'] . ' cm' : '<span class="badge bg-secondary">—</span>' ?></td>
                        <td><?= DateHelper::formatDate($r['assessment_date'], 'M j, Y') ?></td>
                        <td class="text-nowrap">
                            <button type="button" class="btn btn-sm btn-success"
                                    data-bs-toggle="modal" data-bs-target="#completeModal"
                                    data-id="<?= $r['id'] ?>"
                                    data-name="<?= htmlspecialchars($r['last_name'] . ', ' . $r['first_name']) ?>"
                                    data-weight="<?= $r['weight_kg'] ?? '' ?>"
                                    title="Discharge">
                                <i class="bi bi-check2-circle me-1"></i>Discharge
                            </button>
                            <form action="<?= APP_URL ?>/programs/dsp/discharge" method="post" class="d-inline">
                                <?= \Core\Session::csrfField() ?>
                                <input type="hidden" name="enrollment_id" value="<?= $r['id'] ?>">
                                <input type="hidden" name="action" value="drop">
                                <button type="button" class="btn btn-sm btn-outline-danger confirm-trigger" title="Drop"
                                        data-confirm-title="Drop Enrollment"
                                        data-confirm-message="Drop this enrollment? The beneficiary will be marked as dropped."
                                        data-confirm-btn="Drop"
                                        data-confirm-class="btn-danger">
                                    <i class="bi bi-x-circle"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Eligible -->
<div class="card border-0 shadow-sm mb-3">
    <div class="card-header fw-semibold d-flex align-items-center justify-content-between"
         style="cursor:pointer" data-bs-toggle="collapse" data-bs-target="#eligibleCollapse">
        <span>
            <i class="bi bi-exclamation-triangle me-2 text-warning"></i>
            Eligible for DSP
            <span class="badge bg-warning text-dark ms-2"><?= count($eligible) ?></span>
            <span class="fw-normal small text-muted ms-1">— wasted / severely wasted, not yet enrolled</span>
        </span>
        <div class="d-flex align-items-center gap-2">
            <?php if (!empty($eligible)): ?>
            <form action="<?= APP_URL ?>/programs/dsp/enroll-all" method="post" class="d-inline" onclick="event.stopPropagation()">
                <?= \Core\Session::csrfField() ?>
                <button type="button" class="btn btn-sm btn-warning confirm-trigger"
                        data-confirm-title="Enroll All Eligible"
                        data-confirm-message="Enroll all <?= count($eligible) ?> eligible beneficiar<?= count($eligible) === 1 ? 'y' : 'ies' ?> in DSP automatically?"
                        data-confirm-btn="Enroll All"
                        data-confirm-class="btn-dark">
                    <i class="bi bi-people-fill me-1"></i>Enroll All
                </button>
            </form>
            <?php endif; ?>
            <i class="bi bi-chevron-down small"></i>
        </div>
    </div>
    <div class="collapse <?= !empty($eligible) ? 'show' : '' ?>" id="eligibleCollapse">
        <?php if (empty($eligible)): ?>
        <div class="card-body text-center text-muted py-4">
            <?php if (!empty($active)): ?>
            <i class="bi bi-check-circle fs-3 d-block mb-1 text-success"></i>
            All malnourished children are already enrolled in DSP.
            <?php else: ?>
            <i class="bi bi-emoji-smile fs-3 d-block mb-1 text-success"></i>
            No malnourished children found — all children have normal nutritional status.
            <?php endif; ?>
        </div>
        <?php else: ?>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-sm mb-0 align-middle">
                    <thead class="table-light">
                        <tr><th>Name</th><th>Barangay</th><th>WFL/H</th><th>WFA</th><th>Last Assessment</th><th>Action</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($eligible as $e): ?>
                        <tr>
                            <td>
                                <a href="<?= APP_URL ?>/beneficiaries/<?= $e['id'] ?>" class="text-decoration-none fw-semibold">
                                    <?= htmlspecialchars($e['last_name'] . ', ' . $e['first_name']) ?>
                                </a>
                            </td>
                            <td><?= htmlspecialchars($e['barangay']) ?></td>
                            <td>
                                <?php if (!empty($e['wflh_status'])): ?>
                                <span class="badge status-<?= strtolower($e['wflh_status']) ?>"><?= $e['wflh_status'] ?></span>
                                <?php else: ?>—<?php endif; ?>
                            </td>
                            <td><span class="badge status-<?= strtolower($e['nutritional_status']) ?>"><?= $e['nutritional_status'] ?></span></td>
                            <td><?= DateHelper::formatDate($e['assessment_date'], 'M j, Y') ?></td>
                            <td>
                                <button type="button" class="btn btn-sm btn-warning"
                                        data-bs-toggle="modal" data-bs-target="#enrollModal"
                                        data-id="<?= $e['id'] ?>"
                                        data-name="<?= htmlspecialchars($e['last_name'] . ', ' . $e['first_name']) ?>"
                                        data-weight="<?= $e['weight_kg'] ?? '' ?>"
                                        data-wflh="<?= htmlspecialchars($e['wflh_status'] ?? '') ?>"
                                        data-wfa="<?= htmlspecialchars($e['nutritional_status'] ?? '') ?>"
                                        data-date="<?= htmlspecialchars($e['assessment_date'] ?? '') ?>">
                                    <i class="bi bi-plus-circle"></i> Enroll
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Active Enrollments -->
<div class="card border-0 shadow-sm mb-3">
    <div class="card-header bg-success text-white fw-semibold">
        <i class="bi bi-check-circle me-2"></i>Active Enrollments
        <span class="badge bg-light text-dark ms-2"><?= count($active) ?></span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-sm mb-0 align-middle">
                <thead class="table-light">
                    <tr><th>Name</th><th>Barangay</th><th>Cycle</th><th>Intervention</th><th>Latest Status</th><th>Progress</th><th>Pre → Post Weight</th><th>Enrolled</th><th>Checks</th><th>Actions</th></tr>
                </thead>
                <tbody>
                    <?php if (empty($active)): ?>
                    <tr><td colspan="10" class="text-center text-muted py-4"><i class="bi bi-inbox fs-3 d-block mb-1"></i>No active DSP enrollments.</td></tr>
                    <?php endif; ?>
                    <?php foreach ($active as $a): ?>
                    <?php
                        $latestNormal = !empty($a['nutritional_status'])
                            && !in_array($a['nutritional_status'], ['SUW','UW'])
                            && !in_array($a['wflh_status'] ?? '', ['SW','MW']);
                        $prevNormal = !empty($a['prev_nutritional_status'])
                            && !in_array($a['prev_nutritional_status'], ['SUW','UW'])
                            && !in_array($a['prev_wflh_status'] ?? '', ['SW','MW']);
                        $consec = $latestNormal ? ($prevNormal ? 2 : 1) : 0;
                        $overdue = !empty($a['assessment_date'])
                            && (new DateTime())->diff(new DateTime($a['assessment_date']))->days > 30;
                        $noMuac = empty($a['muac_cm']);
                    ?>
                    <tr <?= ($a['age_months'] ?? 0) > 59 ? 'class="table-warning"' : '' ?>>
                        <td>
                            <a href="<?= APP_URL ?>/beneficiaries/<?= $a['beneficiary_id'] ?>" class="text-decoration-none fw-semibold">
                                <?= htmlspecialchars($a['last_name'] . ', ' . $a['first_name']) ?>
                            </a>
                            <?php if (($a['age_months'] ?? 0) > 59): ?>
                            <span class="badge bg-secondary ms-1" title="Child is over 59 months old"><i class="bi bi-exclamation-triangle me-1"></i>Aged Out</span>
                            <?php endif; ?>
                            <?php if ($overdue): ?>
                            <span class="badge bg-danger ms-1" title="Last assessment was more than 30 days ago"><i class="bi bi-clock me-1"></i>Overdue</span>
                            <?php endif; ?>
                        </td>
                        <td><?= htmlspecialchars($a['barangay']) ?></td>
                        <td>
                            <span class="badge bg-secondary">Cycle <?= $a['cycle_number'] ?></span>
                            <span class="text-muted small ms-1"><?= $a['cycle_year'] ?></span>
                        </td>
                        <td>
                            <?php if (!empty($a['intervention_type'])): ?>
                            <span class="badge bg-info text-dark"><?= htmlspecialchars($a['intervention_type']) ?></span>
                            <?php else: ?>—<?php endif; ?>
                        </td>
                        <td>
                            <?php if (!empty($a['wflh_status'])): ?>
                            <span class="badge status-<?= strtolower($a['wflh_status']) ?>"><?= $a['wflh_status'] ?></span>
                            <?php endif; ?>
                            <?php if (!empty($a['nutritional_status'])): ?>
                            <span class="badge status-<?= strtolower($a['nutritional_status']) ?>"><?= $a['nutritional_status'] ?></span>
                            <?php endif; ?>
                            <?php if (empty($a['wflh_status']) && empty($a['nutritional_status'])): ?>—<?php endif; ?>
                        </td>
                        <td class="text-nowrap">
                            <?php if (empty($a['assessment_date'])): ?>
                            <span class="badge bg-secondary">Not assessed</span>
                            <?php else: ?>
                            <span class="badge <?= $consec >= 2 ? 'bg-success' : ($consec === 1 ? 'bg-warning text-dark' : 'bg-danger') ?>"
                                  title="<?= $consec ?>/2 consecutive normal assessments required for discharge">
                                <?= $consec ?>/2 Normal
                            </span>
                            <?php if ($noMuac): ?>
                            <span class="badge bg-warning text-dark ms-1" title="MUAC not recorded — required for discharge">No MUAC</span>
                            <?php endif; ?>
                            <?php endif; ?>
                        </td>
                        <td class="text-nowrap">
                            <?= !empty($a['pre_weight_kg']) ? $a['pre_weight_kg'] . ' kg' : '—' ?>
                            <?php if (!empty($a['pre_weight_kg']) || !empty($a['post_weight_kg'])): ?>
                            <span class="text-muted">→</span> <?= !empty($a['post_weight_kg']) ? $a['post_weight_kg'] . ' kg' : '—' ?>
                            <?php endif; ?>
                        </td>
                        <td><?= DateHelper::formatDate($a['enrollment_date'], 'M j, Y') ?></td>
                        <?php $monRecords = $monitoringByEnrollment[$a['id']] ?? []; ?>
                        <td class="text-center">
                            <?php if (!empty($monRecords)): ?>
                            <button class="btn btn-sm btn-outline-info" type="button"
                                    data-bs-toggle="collapse" data-bs-target="#mon-<?= $a['id'] ?>"
                                    title="View monthly checks">
                                <i class="bi bi-clock-history me-1"></i><?= count($monRecords) ?>
                            </button>
                            <?php else: ?>
                            <span class="text-muted small">—</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-nowrap">
                            <button type="button" class="btn btn-sm btn-primary" title="Record Follow-up"
                                    data-bs-toggle="modal" data-bs-target="#monitoringModal"
                                    data-enrollment="<?= $a['id'] ?>"
                                    data-beneficiary="<?= $a['beneficiary_id'] ?>"
                                    data-name="<?= htmlspecialchars($a['last_name'] . ', ' . $a['first_name']) ?>"
                                    data-dob="<?= $a['date_of_birth'] ?>"
                                    data-sex="<?= $a['sex'] ?>">
                                <i class="bi bi-clipboard2-pulse"></i>
                            </button>
                            <form action="<?= APP_URL ?>/programs/dsp/discharge" method="post" class="d-inline">
                                <?= \Core\Session::csrfField() ?>
                                <input type="hidden" name="enrollment_id" value="<?= $a['id'] ?>">
                                <input type="hidden" name="action" value="drop">
                                <button type="button" class="btn btn-sm btn-outline-danger confirm-trigger" title="Drop"
                                        data-confirm-title="Drop Enrollment"
                                        data-confirm-message="Drop this enrollment? The beneficiary will be marked as dropped."
                                        data-confirm-btn="Drop"
                                        data-confirm-class="btn-danger">
                                    <i class="bi bi-x-circle"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                    <?php if (!empty($monRecords)): ?>
                    <tr class="collapse" id="mon-<?= $a['id'] ?>">
                        <td colspan="10" class="p-0">
                            <div class="bg-light border-top border-bottom px-3 py-2">
                                <div class="small fw-semibold text-info mb-2">
                                    <i class="bi bi-clock-history me-1"></i>Monthly Monitoring Records — <?= htmlspecialchars($a['last_name'] . ', ' . $a['first_name']) ?>
                                </div>
                                <table class="table table-sm table-bordered mb-0" style="font-size:.85rem">
                                    <thead class="table-info">
                                        <tr>
                                            <th>#</th><th>Date</th><th>Weight</th><th>Height</th><th>MUAC</th>
                                            <th>WFA Status</th><th>WFL/H</th><th>Monitored By</th><th>Validation</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach (array_reverse($monRecords) as $ri => $r): ?>
                                        <tr>
                                            <td class="text-muted"><?= count($monRecords) - $ri ?></td>
                                            <td><?= htmlspecialchars($r['monitoring_date']) ?></td>
                                            <td><?= $r['weight_kg'] ? $r['weight_kg'] . ' kg' : '—' ?></td>
                                            <td><?= $r['height_cm'] ? $r['height_cm'] . ' cm' : '—' ?></td>
                                            <td><?= $r['muac_cm']   ? $r['muac_cm']   . ' cm' : '—' ?></td>
                                            <td>
                                                <?php if (!empty($r['nutritional_status'])): ?>
                                                <span class="badge status-<?= strtolower($r['nutritional_status']) ?>"><?= $r['nutritional_status'] ?></span>
                                                <?php else: ?>—<?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if (!empty($r['wflh_status'])): ?>
                                                <span class="badge status-<?= strtolower($r['wflh_status']) ?>"><?= $r['wflh_status'] ?></span>
                                                <?php else: ?>—<?php endif; ?>
                                            </td>
                                            <td><?= htmlspecialchars($r['monitored_by'] ?? '—') ?></td>
                                            <td>
                                                <span class="badge <?= ($r['validation_status'] ?? '') === 'validated' ? 'bg-success' : 'bg-warning text-dark' ?>">
                                                    <?= ucfirst($r['validation_status'] ?? 'pending') ?>
                                                </span>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </td>
                    </tr>
                    <?php endif; ?>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- History -->
<div class="card border-0 shadow-sm mb-4">
    <div class="card-header fw-semibold" style="cursor:pointer"
         data-bs-toggle="collapse" data-bs-target="#historyCollapse">
        <i class="bi bi-clock-history me-2 text-secondary"></i>History — Graduated &amp; Dropped
        <span class="badge bg-secondary ms-2"><?= count($history) ?></span>
        <i class="bi bi-chevron-down small float-end mt-1"></i>
    </div>
    <div class="collapse show" id="historyCollapse">
        <div class="card-body border-bottom pb-2 pt-3">
            <form method="get" action="" class="row g-2 align-items-end">
                <div class="col-auto">
                    <label class="form-label small fw-semibold mb-1">Cycle Year</label>
                    <select name="history_year" class="form-select form-select-sm" onchange="this.form.submit()">
                        <?php for ($y = (int)date('Y'); $y >= (int)date('Y') - 4; $y--): ?>
                        <option value="<?= $y ?>" <?= $y === $historyYear ? 'selected' : '' ?>><?= $y ?></option>
                        <?php endfor; ?>
                    </select>
                </div>
            </form>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-sm mb-0 align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Name</th><th>Barangay</th><th>Cycle</th><th>Intervention</th>
                            <th>Pre → Post Weight</th><th>Gain</th><th>Enrolled</th><th>Ended</th><th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($history)): ?>
                        <tr><td colspan="9" class="text-center text-muted py-4">
                            <i class="bi bi-inbox fs-3 d-block mb-1"></i>
                            No completed or dropped DSP enrollments for <?= $historyYear ?>.
                        </td></tr>
                        <?php endif; ?>
                        <?php foreach ($history as $h): ?>
                        <?php
                            $gain = (isset($h['pre_weight_kg'], $h['post_weight_kg']) && $h['pre_weight_kg'] > 0 && $h['post_weight_kg'] > 0)
                                ? round($h['post_weight_kg'] - $h['pre_weight_kg'], 2)
                                : null;
                        ?>
                        <tr>
                            <td>
                                <a href="<?= APP_URL ?>/beneficiaries/<?= $h['beneficiary_id'] ?>" class="text-decoration-none fw-semibold">
                                    <?= htmlspecialchars($h['last_name'] . ', ' . $h['first_name']) ?>
                                </a>
                            </td>
                            <td><?= htmlspecialchars($h['barangay']) ?></td>
                            <td><span class="badge bg-secondary">Cycle <?= $h['cycle_number'] ?></span></td>
                            <td>
                                <?php if (!empty($h['intervention_type'])): ?>
                                <span class="badge bg-info text-dark"><?= htmlspecialchars($h['intervention_type']) ?></span>
                                <?php else: ?>—<?php endif; ?>
                            </td>
                            <td class="text-nowrap">
                                <?= !empty($h['pre_weight_kg']) ? $h['pre_weight_kg'] . ' kg' : '—' ?>
                                <?php if (!empty($h['pre_weight_kg']) || !empty($h['post_weight_kg'])): ?>
                                <span class="text-muted">→</span> <?= !empty($h['post_weight_kg']) ? $h['post_weight_kg'] . ' kg' : '—' ?>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($gain !== null): ?>
                                <span class="fw-semibold <?= $gain >= 0 ? 'text-success' : 'text-danger' ?>">
                                    <?= ($gain >= 0 ? '+' : '') . $gain ?> kg
                                </span>
                                <?php else: ?>—<?php endif; ?>
                            </td>
                            <td><?= DateHelper::formatDate($h['enrollment_date'], 'M j, Y') ?></td>
                            <td><?= !empty($h['end_date']) ? DateHelper::formatDate($h['end_date'], 'M j, Y') : '—' ?></td>
                            <td>
                                <?php if ($h['status'] === 'Completed'): ?>
                                <span class="badge bg-success">Graduated</span>
                                <?php else: ?>
                                <span class="badge bg-danger">Dropped</span>
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

<!-- Enroll Modal -->
<div class="modal fade" id="enrollModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="<?= APP_URL ?>/programs/dsp/enroll" method="post">
                <?= \Core\Session::csrfField() ?>
                <input type="hidden" name="beneficiary_id" id="enrollBeneficiaryId">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-egg-fried me-2"></i>Enroll in DSP</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="mb-2">Enrolling: <strong id="enrollName"></strong></p>
                    <div class="alert alert-light border small py-2 mb-3">
                        <span class="text-muted"><i class="bi bi-clipboard2-pulse me-1"></i>Last assessment:</span>
                        <span id="enrollSummaryDate" class="ms-1"></span> —
                        Weight: <strong id="enrollSummaryWeight"></strong> kg,
                        WFA: <span id="enrollSummaryWfa" class="badge"></span>
                        WFL/H: <span id="enrollSummaryWflh" class="badge"></span>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Intervention Type</label>
                        <select name="intervention_type" class="form-select" id="enrollIntervention">
                            <option value="">— Select —</option>
                            <option value="RUSF">RUSF (Moderately Wasted)</option>
                            <option value="RUTF">RUTF (Severely Wasted)</option>
                            <option value="Supplementary Feeding">Supplementary Feeding</option>
                            <option value="Health Education">Health Education</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Pre-Weight (kg)</label>
                        <input type="number" step="0.01" name="pre_weight_kg" id="enrollPreWeight" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Notes</label>
                        <textarea name="notes" class="form-control" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-warning"><i class="bi bi-plus-circle me-1"></i>Enroll</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Complete Modal -->
<div class="modal fade" id="completeModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="<?= APP_URL ?>/programs/dsp/discharge" method="post">
                <?= \Core\Session::csrfField() ?>
                <input type="hidden" name="action" value="complete">
                <input type="hidden" name="enrollment_id" id="completeEnrollmentId">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-check2-circle me-2"></i>Complete DSP Enrollment</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Completing enrollment for: <strong id="completeName"></strong></p>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Post-Weight (kg)</label>
                        <input type="number" step="0.01" name="post_weight_kg" id="completePostWeight" class="form-control">
                        <div class="form-text" id="completeWeightNote"></div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success"><i class="bi bi-check-circle me-1"></i>Mark Completed</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Weights Modal -->
<div class="modal fade" id="editModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="<?= APP_URL ?>/programs/dsp/update" method="post">
                <?= \Core\Session::csrfField() ?>
                <input type="hidden" name="enrollment_id" id="editEnrollmentId">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-pencil me-2"></i>Edit DSP Enrollment</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="mb-3">Editing: <strong id="editName"></strong></p>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Intervention Type</label>
                        <select name="intervention_type" class="form-select" id="editIntervention">
                            <option value="">— Select —</option>
                            <option value="RUSF">RUSF (Moderately Wasted)</option>
                            <option value="RUTF">RUTF (Severely Wasted)</option>
                            <option value="Supplementary Feeding">Supplementary Feeding</option>
                            <option value="Health Education">Health Education</option>
                        </select>
                    </div>
                    <div class="row g-3">
                        <div class="col-6">
                            <label class="form-label fw-semibold">Pre-Weight (kg)</label>
                            <input type="number" step="0.01" name="pre_weight_kg" id="editPreWeight" class="form-control" placeholder="e.g. 10.5">
                        </div>
                        <div class="col-6">
                            <label class="form-label fw-semibold">Post-Weight (kg)</label>
                            <input type="number" step="0.01" name="post_weight_kg" id="editPostWeight" class="form-control" placeholder="e.g. 11.2">
                        </div>
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

<!-- DSP Monitoring Modal -->
<div class="modal fade" id="monitoringModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="<?= APP_URL ?>/programs/dsp/monitoring" method="post">
                <?= \Core\Session::csrfField() ?>
                <input type="hidden" name="enrollment_id"  id="monEnrollmentId">
                <input type="hidden" name="beneficiary_id" id="monBeneficiaryId">
                <input type="hidden" name="date_of_birth"  id="monDob">
                <input type="hidden" name="sex"            id="monSex">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-clipboard2-pulse me-2"></i>Record DSP Follow-up</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="mb-3">Child: <strong id="monName"></strong></p>
                    <input type="hidden" name="monitoring_date" value="<?= date('Y-m-d') ?>">
                    <input type="hidden" name="monitored_by" value="<?= htmlspecialchars(\Core\Session::get('user_name') ?? '') ?>">
                    <div class="row g-3">
                        <div class="col-4">
                            <label class="form-label fw-semibold">Weight (kg) <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" name="weight_kg" class="form-control" placeholder="e.g. 10.5" required>
                        </div>
                        <div class="col-4">
                            <label class="form-label fw-semibold">Height (cm)</label>
                            <input type="number" step="0.01" name="height_cm" class="form-control" placeholder="e.g. 85.0">
                        </div>
                        <div class="col-4">
                            <label class="form-label fw-semibold">MUAC (cm) <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" name="muac_cm" class="form-control" placeholder="e.g. 13.5" required>
                        </div>
                    </div>
                    <div class="mt-3">
                        <label class="form-label fw-semibold">Remarks</label>
                        <textarea name="remarks" class="form-control" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-save me-1"></i>Save Record</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.getElementById('enrollModal').addEventListener('show.bs.modal', function(e) {
    const btn = e.relatedTarget;
    document.getElementById('enrollBeneficiaryId').value = btn.dataset.id;
    document.getElementById('enrollName').textContent     = btn.dataset.name;
    document.getElementById('enrollPreWeight').value      = btn.dataset.weight || '';

    const statusColors = { SUW:'danger', UW:'warning', Normal:'success', OW:'info', OB:'secondary', SW:'danger', MW:'warning' };
    const wfa  = btn.dataset.wfa  || '—';
    const wflh = btn.dataset.wflh || '—';
    document.getElementById('enrollSummaryDate').textContent   = btn.dataset.date || '—';
    document.getElementById('enrollSummaryWeight').textContent = btn.dataset.weight || '—';
    const wfaEl  = document.getElementById('enrollSummaryWfa');
    const wflhEl = document.getElementById('enrollSummaryWflh');
    wfaEl.textContent  = wfa;  wfaEl.className  = 'badge bg-' + (statusColors[wfa]  || 'secondary');
    wflhEl.textContent = wflh; wflhEl.className = 'badge bg-' + (statusColors[wflh] || 'secondary');

    const sel = document.getElementById('enrollIntervention');
    if (wflh === 'SW') sel.value = 'RUTF';
    else if (wflh === 'MW') sel.value = 'RUSF';
    else sel.value = 'Health Education';
});
document.getElementById('completeModal').addEventListener('show.bs.modal', function(e) {
    const btn = e.relatedTarget;
    document.getElementById('completeEnrollmentId').value = btn.dataset.id;
    document.getElementById('completeName').textContent   = btn.dataset.name;
    const w = btn.dataset.weight || '';
    document.getElementById('completePostWeight').value   = w;
    document.getElementById('completeWeightNote').textContent = w
        ? 'Auto-filled from latest assessment (' + w + ' kg). Edit if needed.'
        : '';
});
document.getElementById('editModal').addEventListener('show.bs.modal', function(e) {
    const btn = e.relatedTarget;
    document.getElementById('editEnrollmentId').value    = btn.dataset.id;
    document.getElementById('editName').textContent      = btn.dataset.name;
    document.getElementById('editPreWeight').value       = btn.dataset.pre  || '';
    document.getElementById('editPostWeight').value      = btn.dataset.post || '';
    document.getElementById('editIntervention').value    = btn.dataset.intervention || '';
});
document.getElementById('monitoringModal').addEventListener('show.bs.modal', function(e) {
    const btn = e.relatedTarget;
    document.getElementById('monEnrollmentId').value  = btn.dataset.enrollment;
    document.getElementById('monBeneficiaryId').value = btn.dataset.beneficiary;
    document.getElementById('monDob').value           = btn.dataset.dob;
    document.getElementById('monSex').value           = btn.dataset.sex;
    document.getElementById('monName').textContent    = btn.dataset.name;
});
</script>
