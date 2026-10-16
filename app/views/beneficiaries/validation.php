<?php $pageTitle = 'Validation Queue'; ?>
<?php $totalPending = count($rows) + count($pendingAssessments ?? []); ?>

<div class="d-flex justify-content-between align-items-center mt-3 mb-2">
    <div>
        <h4 class="mb-0"><i class="bi bi-shield-check me-2 text-warning"></i>Validation Queue</h4>
        <p class="text-muted small mb-0 mt-1">Review and approve pending beneficiary registrations and assessments</p>
    </div>
    <span class="badge bg-warning text-dark fs-6"><?= $totalPending ?> Pending</span>
</div>

<?php if (empty($sessionBrgy) && !empty($barangays)): ?>
<form method="get" class="mb-3 d-flex gap-2 align-items-center">
    <select name="barangay" class="form-select form-select-sm w-auto" onchange="this.form.submit()">
        <option value="">All Barangays</option>
        <?php foreach ($barangays as $b): ?>
        <option value="<?= htmlspecialchars($b['barangay']) ?>" <?= $filterBrgy === $b['barangay'] ? 'selected' : '' ?>>
            <?= htmlspecialchars($b['barangay']) ?>
        </option>
        <?php endforeach; ?>
    </select>
    <?php if ($filterBrgy): ?>
    <a href="<?= APP_URL ?>/beneficiaries/validation" class="btn btn-sm btn-outline-secondary">Clear</a>
    <?php endif; ?>
</form>
<?php endif; ?>

<?php if (empty($rows)): ?>
<div class="card border-0 shadow-sm text-center py-5">
    <div class="card-body">
        <i class="bi bi-shield-check display-4 text-success opacity-60 d-block mb-3"></i>
        <h5 class="fw-semibold">All clear!</h5>
        <p class="text-muted">No pending beneficiary registrations to review.</p>
    </div>
</div>
<?php else: ?>
<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Name</th>
                        <th>DOB / Age</th>
                        <th>Sex</th>
                        <th>Barangay</th>
                        <th>Submitted By</th>
                        <th>Submitted At</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($rows as $b): ?>
                    <tr>
                        <td>
                            <a href="<?= APP_URL ?>/beneficiaries/<?= $b['id'] ?>" class="fw-semibold text-decoration-none">
                                <?= htmlspecialchars($b['last_name'] . ', ' . $b['first_name']) ?>
                                <?= $b['middle_name'] ? htmlspecialchars(' ' . $b['middle_name']) : '' ?>
                            </a>
                            <?php if (!empty($b['source'])): ?>
                            <span class="badge bg-secondary bg-opacity-50 ms-1 small"><?= htmlspecialchars($b['source']) ?></span>
                            <?php endif; ?>
                            <?php if (!empty($b['assessments'])): ?>
                            <button class="btn btn-link btn-sm p-0 ms-2 text-decoration-none"
                                    type="button"
                                    data-bs-toggle="collapse"
                                    data-bs-target="#assessments<?= $b['id'] ?>"
                                    title="View assessments">
                                <span class="badge bg-info text-dark">
                                    <i class="bi bi-clipboard-pulse me-1"></i><?= count($b['assessments']) ?> assessment<?= count($b['assessments']) > 1 ? 's' : '' ?>
                                </span>
                            </button>
                            <?php endif; ?>
                        </td>
                        <td class="text-nowrap">
                            <?= DateHelper::formatDate($b['date_of_birth'], 'M j, Y') ?>
                            <div class="small text-muted"><?= DateHelper::formatAge(DateHelper::ageInMonths($b['date_of_birth'])) ?></div>
                        </td>
                        <td><?= htmlspecialchars($b['sex']) ?></td>
                        <td>
                            <?= htmlspecialchars($b['barangay']) ?>
                            <?php if (!empty($b['purok_zone'])): ?>
                            <div class="small text-muted"><?= htmlspecialchars($b['purok_zone']) ?></div>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if (!empty($b['created_by_name'])): ?>
                            <span class="small"><?= htmlspecialchars($b['created_by_name']) ?></span>
                            <?php else: ?>
                            <span class="text-muted small">—</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-nowrap small text-muted">
                            <?= DateHelper::formatDate($b['created_at'] ?? '', 'M j, Y g:i a') ?>
                        </td>
                        <td class="text-end">
                            <a href="<?= APP_URL ?>/beneficiaries/<?= $b['id'] ?>" class="btn btn-sm btn-outline-secondary me-1" title="View profile">
                                <i class="bi bi-eye"></i>
                            </a>
                            <form action="<?= APP_URL ?>/beneficiaries/<?= $b['id'] ?>/validate" method="post" class="d-inline">
                                <?= \Core\Session::csrfField() ?>
                                <button class="btn btn-sm btn-success me-1" title="Approve">
                                    <i class="bi bi-check-lg"></i> Approve
                                </button>
                            </form>
                            <button class="btn btn-sm btn-outline-warning"
                                    data-bs-toggle="modal"
                                    data-bs-target="#returnModal<?= $b['id'] ?>"
                                    title="Return for Correction">
                                <i class="bi bi-reply me-1"></i>Return
                            </button>

                            <!-- Return modal for this row -->
                            <div class="modal fade" id="returnModal<?= $b['id'] ?>" tabindex="-1">
                                <div class="modal-dialog modal-dialog-centered">
                                    <div class="modal-content">
                                        <form action="<?= APP_URL ?>/beneficiaries/<?= $b['id'] ?>/reject" method="post">
                                            <?= \Core\Session::csrfField() ?>
                                            <div class="modal-header">
                                                <h5 class="modal-title"><i class="bi bi-reply text-warning me-2"></i>Return for Correction</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                            </div>
                                            <div class="modal-body">
                                                <div class="alert alert-warning py-2 small mb-3">
                                                    <i class="bi bi-info-circle me-1"></i>This will be sent back to the BHW for correction.
                                                </div>
                                                <p class="text-muted small mb-2">
                                                    Returning: <strong><?= htmlspecialchars($b['last_name'] . ', ' . $b['first_name']) ?></strong>
                                                </p>
                                                <label class="form-label fw-semibold">Note to BHW <span class="text-danger">*</span></label>
                                                <textarea name="rejection_note" class="form-control" rows="3"
                                                          placeholder="Explain what needs to be corrected..." required></textarea>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                <button type="submit" class="btn btn-warning"><i class="bi bi-reply me-1"></i>Return</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </td>
                    </tr>
                    <?php if (!empty($b['assessments'])): ?>
                    <tr class="collapse" id="assessments<?= $b['id'] ?>">
                        <td colspan="7" class="p-0">
                            <div class="bg-light border-top border-bottom px-4 py-2">
                                <p class="text-muted small mb-2 fw-semibold"><i class="bi bi-clipboard-pulse me-1"></i>Pending Assessments</p>
                                <div class="table-responsive">
                                    <table class="table table-sm table-bordered mb-0 bg-white">
                                        <thead class="table-secondary">
                                            <tr>
                                                <th>Date</th>
                                                <th>Period</th>
                                                <th>Age (mo)</th>
                                                <th>Weight</th>
                                                <th>Height</th>
                                                <th>MUAC</th>
                                                <th>Status</th>
                                                <th>Assessed By</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($b['assessments'] as $a): ?>
                                            <tr>
                                                <td class="text-nowrap small"><?= DateHelper::formatDate($a['assessment_date'], 'M j, Y') ?></td>
                                                <td class="small"><?= htmlspecialchars($a['period'] . ' ' . $a['assessment_year']) ?></td>
                                                <td class="small"><?= (int)$a['age_in_months'] ?></td>
                                                <td class="small"><?= number_format((float)$a['weight_kg'], 2) ?> kg</td>
                                                <td class="small"><?= $a['height_cm'] ? number_format((float)$a['height_cm'], 1) . ' cm' : '—' ?></td>
                                                <td class="small"><?= $a['muac_cm'] ? number_format((float)$a['muac_cm'], 1) . ' cm' : '—' ?></td>
                                                <td>
                                                    <?php
                                                        $ns = $a['nutritional_status'] ?? '—';
                                                        $nsClass = match($ns) {
                                                            'Normal' => 'success',
                                                            'UW'     => 'warning',
                                                            'SUW'    => 'danger',
                                                            'OW','OB'=> 'info',
                                                            default  => 'secondary',
                                                        };
                                                    ?>
                                                    <span class="badge bg-<?= $nsClass ?>"><?= htmlspecialchars($ns) ?></span>
                                                </td>
                                                <td class="small text-muted"><?= htmlspecialchars($a['assessed_by'] ?? '—') ?></td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
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

<div class="mt-3 text-end">
    <a href="<?= APP_URL ?>/beneficiaries" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left me-1"></i>Back to Beneficiaries
    </a>
</div>
<?php endif; ?>

<?php if (!empty($pendingAssessments)): ?>
<div class="mt-4">
    <p class="text-muted small fw-semibold text-uppercase mb-2 ps-1">
        <i class="bi bi-clipboard-pulse me-1"></i>Pending Assessments
        <span class="badge bg-info text-dark ms-1"><?= count($pendingAssessments) ?></span>
    </p>
    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0 align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Beneficiary</th>
                            <th>Barangay</th>
                            <th>Date</th>
                            <th>Period</th>
                            <th>Age (mo)</th>
                            <th>Weight</th>
                            <th>Status</th>
                            <th>Submitted By</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($pendingAssessments as $a): ?>
                        <tr>
                            <td>
                                <a href="<?= APP_URL ?>/beneficiaries/<?= $a['beneficiary_id'] ?>" class="fw-semibold text-decoration-none">
                                    <?= htmlspecialchars($a['last_name'] . ', ' . $a['first_name']) ?>
                                </a>
                            </td>
                            <td><?= htmlspecialchars($a['barangay']) ?></td>
                            <td class="text-nowrap small"><?= DateHelper::formatDate($a['assessment_date'], 'M j, Y') ?></td>
                            <td class="small"><?= htmlspecialchars($a['period'] . ' ' . $a['assessment_year']) ?></td>
                            <td class="small"><?= (int)$a['age_in_months'] ?></td>
                            <td class="small"><?= number_format((float)$a['weight_kg'], 2) ?> kg</td>
                            <td>
                                <?php
                                $ns = $a['nutritional_status'] ?? '—';
                                $nsClass = match($ns) { 'Normal'=>'success','UW'=>'warning','SUW'=>'danger','OW','OB'=>'info',default=>'secondary' };
                                ?>
                                <span class="badge bg-<?= $nsClass ?>"><?= htmlspecialchars($ns) ?></span>
                            </td>
                            <td class="small text-muted"><?= htmlspecialchars($a['created_by_name'] ?? '—') ?></td>
                            <td class="text-end">
                                <form action="<?= APP_URL ?>/assessments/<?= $a['id'] ?>/validate" method="post" class="d-inline">
                                    <?= \Core\Session::csrfField() ?>
                                    <button class="btn btn-sm btn-success me-1" title="Approve">
                                        <i class="bi bi-check-lg"></i> Approve
                                    </button>
                                </form>
                                <button class="btn btn-sm btn-outline-warning"
                                        data-bs-toggle="modal"
                                        data-bs-target="#returnAssessmentModal<?= $a['id'] ?>"
                                        title="Return for Correction">
                                    <i class="bi bi-reply me-1"></i>Return
                                </button>
                                <div class="modal fade" id="returnAssessmentModal<?= $a['id'] ?>" tabindex="-1">
                                    <div class="modal-dialog modal-dialog-centered">
                                        <div class="modal-content">
                                            <form action="<?= APP_URL ?>/assessments/<?= $a['id'] ?>/reject" method="post">
                                                <?= \Core\Session::csrfField() ?>
                                                <div class="modal-header">
                                                    <h5 class="modal-title"><i class="bi bi-reply text-warning me-2"></i>Return Assessment</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                </div>
                                                <div class="modal-body">
                                                    <p class="text-muted small mb-2">
                                                        Assessment for: <strong><?= htmlspecialchars($a['last_name'] . ', ' . $a['first_name']) ?></strong>
                                                        (<?= htmlspecialchars($a['period'] . ' ' . $a['assessment_year']) ?>)
                                                    </p>
                                                    <label class="form-label fw-semibold">Note <span class="text-danger">*</span></label>
                                                    <textarea name="rejection_note" class="form-control" rows="3"
                                                              placeholder="Explain what needs to be corrected..." required></textarea>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                    <button type="submit" class="btn btn-warning"><i class="bi bi-reply me-1"></i>Return</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>
