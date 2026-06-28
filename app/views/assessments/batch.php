<?php $pageTitle = 'Batch Assessment Entry'; ?>

<div class="d-flex align-items-center my-3">
    <a href="<?= APP_URL ?>/beneficiaries" class="btn btn-sm btn-outline-secondary me-3">
        <i class="bi bi-arrow-left"></i>
    </a>
    <h4 class="mb-0"><i class="bi bi-clipboard2-data me-2"></i>Batch Assessment Entry (OPT)</h4>
</div>

<div class="card border-0 shadow-sm mb-3">
    <div class="card-body py-2">
        <form method="get" class="row g-2 align-items-end">
            <div class="col-md-3">
                <label class="form-label small mb-1">Barangay</label>
                <select name="barangay" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="">-- Select Barangay --</option>
                    <?php foreach ($barangays as $b): ?>
                    <option value="<?= htmlspecialchars($b['barangay']) ?>"
                        <?= $selectedBarangay === $b['barangay'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($b['barangay']) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label small mb-1">Assessment Date</label>
                <input type="date" name="assessment_date" class="form-control form-control-sm"
                       value="<?= htmlspecialchars($assessmentDate) ?>">
            </div>
            <div class="col-auto">
                <button class="btn btn-sm btn-primary">Load</button>
            </div>
        </form>
    </div>
</div>

<?php if (!empty($beneficiaries)): ?>
<form action="<?= APP_URL ?>/assessments/batch" method="post">
    <?= \Core\Session::csrfField() ?>
    <input type="hidden" name="assessment_date" value="<?= htmlspecialchars($assessmentDate) ?>">

    <?php $alreadyCount = count($alreadyWeighedMap ?? []); ?>
    <?php if ($alreadyCount > 0): ?>
    <div class="d-flex align-items-center gap-2 mb-2">
        <span class="badge bg-success"><?= $alreadyCount ?> already weighed</span>
        <span class="badge bg-secondary"><?= count($beneficiaries ?? []) ?> remaining</span>
        <?php if ($showAll): ?>
        <a href="?barangay=<?= urlencode($selectedBarangay) ?>&assessment_date=<?= urlencode($assessmentDate) ?>"
           class="btn btn-sm btn-outline-secondary py-0">Show Unweighed Only</a>
        <?php else: ?>
        <a href="?barangay=<?= urlencode($selectedBarangay) ?>&assessment_date=<?= urlencode($assessmentDate) ?>&show_all=1"
           class="btn btn-sm btn-outline-secondary py-0">Show All <?= $totalInBarangay ?></a>
        <?php endif; ?>
    </div>
    <?php endif; ?>
    <div class="alert alert-info border-0 small mb-2">
        <i class="bi bi-info-circle me-1"></i>
        Assessment Date: <strong><?= date('F j, Y', strtotime($assessmentDate)) ?></strong>
        &mdash; Period: <strong><?= (int)date('n', strtotime($assessmentDate)) <= 6 ? 'January' : 'July' ?></strong>
        &mdash; Leave weight blank to skip a beneficiary.
    </div>
    <div class="row g-2 mb-3 align-items-center">
        <div class="col-auto">
            <label class="form-label fw-semibold small mb-0">Assessed By</label>
        </div>
        <div class="col-md-3">
            <input type="hidden" name="assessed_by" value="<?= htmlspecialchars(\Core\Session::get('user_name', '')) ?>">
            <input type="text" class="form-control form-control-sm bg-light"
                   value="<?= htmlspecialchars(\Core\Session::get('user_name', '')) ?>" readonly>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-sm table-bordered mb-0 align-middle">
                    <thead class="table-primary">
                        <tr>
                            <th style="width:40px">#</th>
                            <th>Name</th>
                            <th style="width:60px">Age</th>
                            <th style="width:60px">Sex</th>
                            <th style="width:100px">Weight (kg) <span class="text-danger">*</span></th>
                            <th style="width:100px">Height (cm)</th>
                            <th style="width:90px">MUAC (cm)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($beneficiaries as $i => $b): ?>
                        <?php $alreadyWeighed = isset($alreadyWeighedMap[$b['id']]); ?>
                        <input type="hidden" name="entries[<?= $i ?>][beneficiary_id]" value="<?= $b['id'] ?>">
                        <tr class="<?= $alreadyWeighed ? 'table-warning' : '' ?>">
                            <td class="text-muted small"><?= $i + 1 ?></td>
                            <td>
                                <strong><?= htmlspecialchars($b['last_name'] . ', ' . $b['first_name']) ?></strong>
                                <?php if ($alreadyWeighed): ?>
                                <span class="badge bg-warning text-dark ms-1" style="font-size:.7rem;">Already Weighed</span>
                                <?php endif; ?>
                                <br><span class="text-muted small"><?= htmlspecialchars($b['purok_zone'] ?? '') ?></span>
                            </td>
                            <td class="small"><?= DateHelper::formatAge(DateHelper::ageInMonths($b['date_of_birth'])) ?></td>
                            <td class="small"><?= htmlspecialchars($b['sex']) ?></td>
                            <td>
                                <input type="number" step="0.01" min="0" max="100"
                                       name="entries[<?= $i ?>][weight_kg]"
                                       class="form-control form-control-sm"
                                       placeholder="kg">
                            </td>
                            <td>
                                <input type="number" step="0.1" min="0" max="200"
                                       name="entries[<?= $i ?>][height_cm]"
                                       class="form-control form-control-sm"
                                       placeholder="cm">
                            </td>
                            <td>
                                <input type="number" step="0.1" min="0" max="50"
                                       name="entries[<?= $i ?>][muac_cm]"
                                       class="form-control form-control-sm"
                                       placeholder="cm">
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="d-flex gap-2 mt-3">
        <button type="submit" class="btn btn-primary px-4">
            <i class="bi bi-save me-1"></i>Save All Assessments
        </button>
        <a href="<?= APP_URL ?>/beneficiaries" class="btn btn-outline-secondary">Cancel</a>
    </div>
</form>
<?php elseif ($selectedBarangay !== ''): ?>
<div class="alert alert-warning">No beneficiaries found for barangay <strong><?= htmlspecialchars($selectedBarangay) ?></strong>.</div>
<?php else: ?>
<div class="alert alert-light border text-muted">Select a barangay above to load beneficiaries.</div>
<?php endif; ?>
