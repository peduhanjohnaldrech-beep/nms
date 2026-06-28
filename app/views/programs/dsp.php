<?php $pageTitle = 'DSP — Dietary Supplementation Program'; ?>

<div class="d-flex justify-content-between align-items-center my-3">
    <div>
        <h4 class="mb-0"><i class="bi bi-egg-fried me-2"></i>DSP — Dietary Supplementation Program</h4>
        <small class="text-muted">Feeding and nutrition intervention for malnourished children (wasted / severely wasted)</small>
    </div>
    <a href="<?= APP_URL ?>/reports/export?type=dsp&format=excel&year=<?= date('Y') ?>" class="btn btn-sm btn-success">
        <i class="bi bi-file-earmark-excel me-1"></i>Export Excel
    </a>
</div>

<!-- Eligible -->
<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-warning text-dark fw-semibold">
        <i class="bi bi-exclamation-triangle me-2"></i>
        Eligible for DSP (Wasted / Severely Wasted — Not Yet Enrolled)
        <span class="badge bg-dark ms-2"><?= count($eligible) ?></span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-sm mb-0 align-middle">
                <thead class="table-light">
                    <tr><th>Name</th><th>Barangay</th><th>WFL/H</th><th>WFA</th><th>Last Assessment</th><th>Action</th></tr>
                </thead>
                <tbody>
                    <?php if (empty($eligible)): ?>
                    <tr><td colspan="6" class="text-center text-muted py-4">
                        <?php if (!empty($active)): ?>
                        <i class="bi bi-check-circle fs-3 d-block mb-1 text-success"></i>
                        All malnourished children are already enrolled in DSP.
                        <?php else: ?>
                        <i class="bi bi-emoji-smile fs-3 d-block mb-1 text-success"></i>
                        No malnourished children found. All children have normal nutritional status.
                        <?php endif; ?>
                    </td></tr>
                    <?php endif; ?>
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
</div>

<!-- Ready to Discharge -->
<?php if (!empty($readyToDischarge)): ?>
<div class="card border-0 shadow-sm mb-4 border-start border-4 border-success">
    <div class="card-header bg-success bg-opacity-10 text-success fw-semibold d-flex justify-content-between align-items-center">
        <span>
            <i class="bi bi-arrow-up-circle me-2"></i>
            Ready to Discharge — Latest Assessment Shows Normal Status
            <span class="badge bg-success ms-2"><?= count($readyToDischarge) ?></span>
        </span>
        <a href="<?= APP_URL ?>/reports/export?type=dsp_ready&format=excel" class="btn btn-sm btn-success">
            <i class="bi bi-file-earmark-excel me-1"></i>Export
        </a>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-sm mb-0 align-middle">
                <thead class="table-light">
                    <tr><th>Name</th><th>Barangay</th><th>Cycle</th><th>Intervention</th><th>WFL/H</th><th>WFA</th><th>Last Assessment</th><th>Action</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($readyToDischarge as $r): ?>
                    <tr>
                        <td>
                            <a href="<?= APP_URL ?>/beneficiaries/<?= $r['beneficiary_id'] ?>" class="text-decoration-none fw-semibold">
                                <?= htmlspecialchars($r['last_name'] . ', ' . $r['first_name']) ?>
                            </a>
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
                        <td><?= DateHelper::formatDate($r['assessment_date'], 'M j, Y') ?></td>
                        <td>
                            <button type="button" class="btn btn-sm btn-success"
                                    data-bs-toggle="modal" data-bs-target="#completeModal"
                                    data-id="<?= $r['id'] ?>"
                                    data-name="<?= htmlspecialchars($r['last_name'] . ', ' . $r['first_name']) ?>"
                                    title="Discharge (Mark Completed)">
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

<!-- Active Enrollments -->
<div class="card border-0 shadow-sm">
    <div class="card-header bg-success text-white fw-semibold">
        <i class="bi bi-check-circle me-2"></i>Active DSP Enrollments
        <span class="badge bg-light text-dark ms-2"><?= count($active) ?></span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-sm mb-0 align-middle">
                <thead class="table-light">
                    <tr><th>Name</th><th>Barangay</th><th>Cycle</th><th>Intervention</th><th>Pre-Weight</th><th>Post-Weight</th><th>Enrolled</th><th>Year</th><th>Actions</th></tr>
                </thead>
                <tbody>
                    <?php if (empty($active)): ?>
                    <tr><td colspan="9" class="text-center text-muted py-4"><i class="bi bi-inbox fs-3 d-block mb-1"></i>No active DSP enrollments.</td></tr>
                    <?php endif; ?>
                    <?php foreach ($active as $a): ?>
                    <tr>
                        <td>
                            <a href="<?= APP_URL ?>/beneficiaries/<?= $a['beneficiary_id'] ?>" class="text-decoration-none fw-semibold">
                                <?= htmlspecialchars($a['last_name'] . ', ' . $a['first_name']) ?>
                            </a>
                        </td>
                        <td><?= htmlspecialchars($a['barangay']) ?></td>
                        <td><span class="badge bg-secondary">Cycle <?= $a['cycle_number'] ?></span></td>
                        <td>
                            <?php if (!empty($a['intervention_type'])): ?>
                            <span class="badge bg-info text-dark"><?= htmlspecialchars($a['intervention_type']) ?></span>
                            <?php else: ?>—<?php endif; ?>
                        </td>
                        <td><?= !empty($a['pre_weight_kg']) ? $a['pre_weight_kg'] . ' kg' : '—' ?></td>
                        <td><?= !empty($a['post_weight_kg']) ? $a['post_weight_kg'] . ' kg' : '—' ?></td>
                        <td><?= DateHelper::formatDate($a['enrollment_date'], 'M j, Y') ?></td>
                        <td><?= $a['cycle_year'] ?></td>
                        <td>
                            <button type="button" class="btn btn-sm btn-outline-primary"
                                    data-bs-toggle="modal" data-bs-target="#editModal"
                                    data-id="<?= $a['id'] ?>"
                                    data-name="<?= htmlspecialchars($a['last_name'] . ', ' . $a['first_name']) ?>"
                                    data-pre="<?= $a['pre_weight_kg'] ?? '' ?>"
                                    data-post="<?= $a['post_weight_kg'] ?? '' ?>"
                                    data-intervention="<?= htmlspecialchars($a['intervention_type'] ?? '') ?>"
                                    title="Edit Weights">
                                <i class="bi bi-pencil"></i>
                            </button>
                            <button type="button" class="btn btn-sm btn-success"
                                    data-bs-toggle="modal" data-bs-target="#completeModal"
                                    data-id="<?= $a['id'] ?>"
                                    data-name="<?= htmlspecialchars($a['last_name'] . ', ' . $a['first_name']) ?>"
                                    title="Mark Completed">
                                <i class="bi bi-check2-circle"></i>
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
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Manual Enrollment Card -->
<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-primary text-white fw-semibold">
        <i class="bi bi-person-plus me-2"></i>Manual Enrollment
        <small class="fw-normal ms-2 opacity-75">Enroll any beneficiary regardless of nutritional status</small>
    </div>
    <div class="card-body">
        <form action="<?= APP_URL ?>/programs/dsp/enroll" method="post" class="row g-3 align-items-end">
            <?= \Core\Session::csrfField() ?>
            <div class="col-md-4">
                <label class="form-label fw-semibold small">Beneficiary <span class="text-danger">*</span></label>
                <select name="beneficiary_id" class="form-select form-select-sm" required>
                    <option value="">— Select Beneficiary —</option>
                    <?php foreach ($notEnrolled as $b): ?>
                    <option value="<?= $b['id'] ?>">
                        <?= htmlspecialchars($b['last_name'] . ', ' . $b['first_name'] . ' (' . $b['barangay'] . ')') ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label fw-semibold small">Intervention Type</label>
                <select name="intervention_type" class="form-select form-select-sm">
                    <option value="">— Select —</option>
                    <option value="RUSF">RUSF (Moderately Wasted)</option>
                    <option value="RUTF">RUTF (Severely Wasted)</option>
                    <option value="Supplementary Feeding">Supplementary Feeding</option>
                    <option value="Health Education">Health Education</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label fw-semibold small">Pre-Weight (kg)</label>
                <input type="number" step="0.01" name="pre_weight_kg" class="form-control form-control-sm" placeholder="e.g. 10.5">
            </div>
            <div class="col-md-2">
                <label class="form-label fw-semibold small">Notes</label>
                <input type="text" name="notes" class="form-control form-control-sm" placeholder="Optional">
            </div>
            <div class="col-md-1 d-flex align-items-end">
                <button type="submit" class="btn btn-primary btn-sm w-100"><i class="bi bi-plus-lg"></i></button>
            </div>
        </form>
    </div>
</div>

<!-- History -->
<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-secondary bg-opacity-10 fw-semibold" style="cursor:pointer"
         data-bs-toggle="collapse" data-bs-target="#historyCollapse">
        <i class="bi bi-clock-history me-2"></i>DSP History — Graduated &amp; Dropped
        <span class="badge bg-secondary ms-2"><?= count($history) ?></span>
        <i class="bi bi-chevron-down float-end mt-1"></i>
    </div>
    <div class="collapse" id="historyCollapse">
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
                            <th>Name</th>
                            <th>Barangay</th>
                            <th>Cycle</th>
                            <th>Intervention</th>
                            <th>Pre-Weight</th>
                            <th>Post-Weight</th>
                            <th>Weight Gain</th>
                            <th>Enrolled</th>
                            <th>Ended</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($history)): ?>
                        <tr><td colspan="10" class="text-center text-muted py-4">
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
                            <td><?= !empty($h['pre_weight_kg'])  ? $h['pre_weight_kg']  . ' kg' : '—' ?></td>
                            <td><?= !empty($h['post_weight_kg']) ? $h['post_weight_kg'] . ' kg' : '—' ?></td>
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
                    <div class="alert alert-light border small py-2 mb-3" id="enrollAssessmentSummary">
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
                        <input type="number" step="0.01" name="post_weight_kg" class="form-control">
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

<script>
document.getElementById('enrollModal').addEventListener('show.bs.modal', function(e) {
    const btn = e.relatedTarget;
    document.getElementById('enrollBeneficiaryId').value = btn.dataset.id;
    document.getElementById('enrollName').textContent     = btn.dataset.name;
    document.getElementById('enrollPreWeight').value      = btn.dataset.weight || '';

    // Assessment summary
    const statusColors = { SUW:'danger', UW:'warning', Normal:'success', OW:'info', OB:'secondary', SW:'danger', MW:'warning' };
    const wfa  = btn.dataset.wfa  || '—';
    const wflh = btn.dataset.wflh || '—';
    document.getElementById('enrollSummaryDate').textContent   = btn.dataset.date || '—';
    document.getElementById('enrollSummaryWeight').textContent = btn.dataset.weight || '—';
    const wfaEl  = document.getElementById('enrollSummaryWfa');
    const wflhEl = document.getElementById('enrollSummaryWflh');
    wfaEl.textContent  = wfa;
    wfaEl.className    = 'badge bg-' + (statusColors[wfa]  || 'secondary');
    wflhEl.textContent = wflh;
    wflhEl.className   = 'badge bg-' + (statusColors[wflh] || 'secondary');

    const sel = document.getElementById('enrollIntervention');
    if (wflh === 'SW') sel.value = 'RUTF';
    else if (wflh === 'MW') sel.value = 'RUSF';
    else sel.value = 'Health Education';
});
document.getElementById('completeModal').addEventListener('show.bs.modal', function(e) {
    const btn = e.relatedTarget;
    document.getElementById('completeEnrollmentId').value = btn.dataset.id;
    document.getElementById('completeName').textContent = btn.dataset.name;
});
document.getElementById('editModal').addEventListener('show.bs.modal', function(e) {
    const btn = e.relatedTarget;
    document.getElementById('editEnrollmentId').value = btn.dataset.id;
    document.getElementById('editName').textContent = btn.dataset.name;
    document.getElementById('editPreWeight').value = btn.dataset.pre || '';
    document.getElementById('editPostWeight').value = btn.dataset.post || '';
    document.getElementById('editIntervention').value = btn.dataset.intervention || '';
});
</script>
