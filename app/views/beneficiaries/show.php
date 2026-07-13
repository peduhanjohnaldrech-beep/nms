<?php
$pageTitle = htmlspecialchars($beneficiary['last_name'] . ', ' . $beneficiary['first_name']);
$role = \Core\Session::get('user_role');
?>

<style>
.collapse-header { cursor: pointer; user-select: none; }
.collapse-header:hover { background-color: var(--primary-subtle) !important; color: var(--primary) !important; }
.collapse-header:hover .text-muted { color: var(--primary) !important; }
.collapse-header .chevron { transition: transform 0.2s ease; }
.collapse-header[aria-expanded="false"] .chevron { transform: rotate(-90deg); }
</style>

<?php
$__valStatus = $beneficiary['validation_status'] ?? 'validated';
$__canValidate = in_array($role, ['midwife','admin','nutritionist']);
?>
<?php if ($__valStatus === 'pending'): ?>
<div class="alert border-0 shadow-sm mt-3 d-flex align-items-center gap-3 no-print" style="background:rgba(217,119,6,.12);border-left:4px solid #d97706 !important;">
    <i class="bi bi-hourglass-split fs-5" style="color:#d97706"></i>
    <div class="flex-grow-1">
        <strong>Pending Validation</strong> — This registration was submitted via mobile and is awaiting midwife approval. Assessments are locked until validated.
        <?php if (!empty($beneficiary['rejection_note'])): ?>
        <div class="small mt-1 text-muted">Previous note: <?= htmlspecialchars($beneficiary['rejection_note']) ?></div>
        <?php endif; ?>
    </div>
    <?php if ($__canValidate): ?>
    <div class="d-flex gap-2 flex-shrink-0">
        <form action="<?= APP_URL ?>/beneficiaries/<?= $beneficiary['id'] ?>/validate" method="post" class="d-inline">
            <?= \Core\Session::csrfField() ?>
            <button class="btn btn-sm btn-success"><i class="bi bi-check-lg me-1"></i>Approve</button>
        </form>
        <button class="btn btn-sm btn-danger" data-bs-toggle="modal" data-bs-target="#rejectModal">
            <i class="bi bi-x-lg me-1"></i>Reject
        </button>
    </div>
    <?php endif; ?>
</div>
<?php elseif ($__valStatus === 'rejected'): ?>
<div class="alert border-0 shadow-sm mt-3 d-flex align-items-center gap-3 no-print" style="background:rgba(220,38,38,.10);border-left:4px solid #dc2626 !important;">
    <i class="bi bi-x-circle-fill fs-5" style="color:#dc2626"></i>
    <div class="flex-grow-1">
        <strong>Registration Rejected</strong>
        <?php if (!empty($beneficiary['rejection_note'])): ?>
        — <?= htmlspecialchars($beneficiary['rejection_note']) ?>
        <?php endif; ?>
        <?php if (!empty($beneficiary['validated_by_name'])): ?>
        <div class="small text-muted mt-1">By <?= htmlspecialchars($beneficiary['validated_by_name']) ?> on <?= \DateHelper::formatDate($beneficiary['validated_at'] ?? '', 'M j, Y') ?></div>
        <?php endif; ?>
    </div>
    <?php if ($__canValidate): ?>
    <form action="<?= APP_URL ?>/beneficiaries/<?= $beneficiary['id'] ?>/validate" method="post" class="d-inline">
        <?= \Core\Session::csrfField() ?>
        <button class="btn btn-sm btn-success"><i class="bi bi-check-lg me-1"></i>Approve Anyway</button>
    </form>
    <?php endif; ?>
</div>
<?php endif; ?>

<?php if ($__canValidate && $__valStatus === 'pending'): ?>
<!-- Reject Modal -->
<div class="modal fade" id="rejectModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form action="<?= APP_URL ?>/beneficiaries/<?= $beneficiary['id'] ?>/reject" method="post">
                <?= \Core\Session::csrfField() ?>
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-x-circle text-danger me-2"></i>Reject Registration</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <label class="form-label fw-semibold">Reason / Notes <span class="text-danger">*</span></label>
                    <textarea name="rejection_note" class="form-control" rows="3" placeholder="Explain why this registration is being rejected..." required></textarea>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger"><i class="bi bi-x-lg me-1"></i>Reject</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<?php if ($__valStatus === 'validated' && empty($beneficiary['submitted_at']) && strtolower($role) === 'bns'): ?>
<div class="alert border-0 shadow-sm mt-3 d-flex align-items-center gap-3 no-print" style="background:rgba(22,163,74,.10);border-left:4px solid #16a34a !important;">
    <i class="bi bi-send fs-5" style="color:#16a34a"></i>
    <div class="flex-grow-1">
        <strong>Ready to Submit</strong> — This beneficiary has been validated. You can now submit to the admin.
    </div>
    <form action="<?= APP_URL ?>/beneficiaries/<?= $beneficiary['id'] ?>/submit" method="post" class="d-inline flex-shrink-0">
        <?= \Core\Session::csrfField() ?>
        <button class="btn btn-sm btn-success"><i class="bi bi-send me-1"></i>Submit to Admin</button>
    </form>
</div>
<?php elseif (!empty($beneficiary['submitted_at'])): ?>
<div class="alert border-0 shadow-sm mt-3 d-flex align-items-center gap-3 no-print" style="background:rgba(6,182,212,.10);border-left:4px solid #0891b2 !important;">
    <i class="bi bi-send-check fs-5" style="color:#0891b2"></i>
    <div>
        <strong>Submitted to Admin</strong> — <?= \DateHelper::formatDate($beneficiary['submitted_at'], 'M j, Y g:i A') ?>
    </div>
</div>
<?php endif; ?>

<div class="d-flex align-items-center justify-content-between my-3 no-print">
    <div class="d-flex align-items-center">
        <a href="<?= APP_URL ?>/beneficiaries" class="btn btn-sm btn-outline-secondary me-3"><i class="bi bi-arrow-left"></i></a>
        <h4 class="mb-0"><i class="bi bi-person-circle me-2"></i>Beneficiary Profile</h4>
    </div>
    <button onclick="window.print()" class="btn btn-sm btn-outline-secondary">
        <i class="bi bi-printer me-1"></i>Print Card
    </button>
</div>

<!-- Print Card (hidden on screen, visible on print) -->
<div class="print-card-only" style="display:none;">
    <div style="border:2px solid #334155;border-radius:8px;padding:16px;max-width:480px;font-family:Arial,sans-serif;font-size:12px;">
        <div style="text-align:center;border-bottom:1px solid #cbd5e1;padding-bottom:10px;margin-bottom:10px;">
            <strong style="font-size:15px;">NUTRITION MONITORING SYSTEM</strong><br>
            <span style="color:#64748b;font-size:11px;">Beneficiary Record Card</span>
        </div>
        <table style="width:100%;border-collapse:collapse;">
            <tr>
                <?php if (!empty($beneficiary['photo'])): ?>
                <td style="width:80px;vertical-align:top;padding-right:12px;">
                    <img src="<?= APP_URL ?>/uploads/photos/<?= htmlspecialchars($beneficiary['photo']) ?>"
                         style="width:72px;height:72px;object-fit:cover;border:1px solid #cbd5e1;border-radius:4px;">
                </td>
                <?php endif; ?>
                <td style="vertical-align:top;">
                    <strong style="font-size:14px;"><?= htmlspecialchars($beneficiary['last_name'] . ', ' . $beneficiary['first_name'] . ($beneficiary['suffix'] ? ' ' . $beneficiary['suffix'] : '')) ?></strong>
                    <?= $beneficiary['middle_name'] ? '<br><span style="color:#64748b;">' . htmlspecialchars($beneficiary['middle_name']) . '</span>' : '' ?>
                    <br><span style="color:#64748b;">ID #<?= $beneficiary['id'] ?></span>
                </td>
            </tr>
        </table>
        <table style="width:100%;border-collapse:collapse;margin-top:10px;font-size:11px;">
            <tr><td style="width:35%;color:#64748b;padding:2px 0;">Date of Birth</td><td><?= DateHelper::formatDate($beneficiary['date_of_birth'], 'F j, Y') ?></td></tr>
            <tr><td style="color:#64748b;padding:2px 0;">Age</td><td><?= DateHelper::formatAge(DateHelper::ageInMonths($beneficiary['date_of_birth'])) ?></td></tr>
            <tr><td style="color:#64748b;padding:2px 0;">Sex</td><td><?= htmlspecialchars($beneficiary['sex']) ?></td></tr>
            <tr><td style="color:#64748b;padding:2px 0;">Barangay</td><td><?= htmlspecialchars($beneficiary['barangay']) ?></td></tr>
            <tr><td style="color:#64748b;padding:2px 0;">Purok/Zone</td><td><?= htmlspecialchars($beneficiary['purok_zone'] ?? '—') ?></td></tr>
            <tr><td style="color:#64748b;padding:2px 0;">Household No.</td><td><?= htmlspecialchars($beneficiary['household_no'] ?? '—') ?></td></tr>
            <tr><td style="color:#64748b;padding:2px 0;">Mother</td><td><?= htmlspecialchars($beneficiary['mother_name'] ?? '—') ?></td></tr>
            <tr><td style="color:#64748b;padding:2px 0;">Father</td><td><?= htmlspecialchars($beneficiary['father_name'] ?? '—') ?></td></tr>
            <tr><td style="color:#64748b;padding:2px 0;">Contact No.</td><td><?= htmlspecialchars($beneficiary['contact_number'] ?? '—') ?></td></tr>
            <?php if (!empty($assessments)): $la = $assessments[0]; ?>
            <tr><td style="color:#64748b;padding:2px 0;">Last Assessment</td><td><?= DateHelper::formatDate($la['assessment_date'], 'M j, Y') ?> — <strong><?= $la['nutritional_status'] ?></strong> (<?= number_format((float)$la['weight_kg'], 1) ?> kg)</td></tr>
            <?php endif; ?>
        </table>
        <div style="border-top:1px solid #cbd5e1;margin-top:10px;padding-top:6px;font-size:10px;color:#94a3b8;text-align:center;">
            Printed: <?= date('F j, Y') ?>
        </div>
    </div>
</div>

<div class="row g-3">
    <div class="col-lg-4">
        <!-- Profile Card (always visible) -->
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-header bg-white d-flex justify-content-between align-items-center py-2">
                <span class="fw-semibold small text-muted">Profile</span>
                <?php if (hasPerm('beneficiaries')): ?>
                <a href="<?= APP_URL ?>/beneficiaries/<?= $beneficiary['id'] ?>/edit" class="btn btn-sm btn-outline-primary">
                    <i class="bi bi-pencil me-1"></i>Edit
                </a>
                <?php endif; ?>
            </div>
            <div class="card-body text-center py-4">
                <?php if (!empty($beneficiary['photo'])): ?>
                <img src="<?= APP_URL ?>/uploads/photos/<?= htmlspecialchars($beneficiary['photo']) ?>"
                     alt="Photo" class="rounded-circle mb-2"
                     style="width:90px;height:90px;object-fit:cover;border:3px solid #e2e8f0;">
                <?php else: ?>
                <div class="display-4 text-primary mb-2"><i class="bi bi-person-circle"></i></div>
                <?php endif; ?>
                <h5 class="fw-bold mb-0">
                    <?= htmlspecialchars($beneficiary['last_name'] . ', ' . $beneficiary['first_name']) ?>
                    <?= $beneficiary['suffix'] ? htmlspecialchars(' ' . $beneficiary['suffix']) : '' ?>
                </h5>
                <?= $beneficiary['middle_name'] ? '<p class="text-muted mb-0">' . htmlspecialchars($beneficiary['middle_name']) . '</p>' : '' ?>
                <p class="text-muted small mt-1">ID #<?= $beneficiary['id'] ?>
                    <?php if (!empty($beneficiary['source'])): ?>
                    &nbsp;<span class="badge <?= $beneficiary['source'] === 'Walk-in' ? 'bg-success' : ($beneficiary['source'] === 'Google' ? 'bg-primary' : 'bg-secondary') ?> bg-opacity-75">
                        <?= htmlspecialchars($beneficiary['source']) ?>
                    </span>
                    <?php endif; ?>
                </p>
                <?php
                $__latestStatus  = !empty($assessments) ? $assessments[0]['nutritional_status'] : null;
                $__hadMalnutrition = !empty(array_filter($assessments, fn($a) => in_array($a['nutritional_status'], ['SUW','UW'])));
                $__isRecovered   = $__latestStatus === 'Normal' && $__hadMalnutrition;
                ?>
                <?php if (DateHelper::ageInMonths($beneficiary['date_of_birth']) > 59): ?>
                <div class="mt-2">
                    <span class="badge bg-secondary" title="This child is over 59 months old and is no longer in the active monitoring age range.">
                        <i class="bi bi-clock-history me-1"></i>Aged Out
                    </span>
                </div>
                <?php elseif ($__isRecovered): ?>
                <div class="mt-2">
                    <span class="badge bg-success" title="This child was previously malnourished (SUW/UW) and has recovered to Normal nutritional status.">
                        <i class="bi bi-heart-fill me-1"></i>Recovered
                    </span>
                </div>
                <?php elseif (!empty($trend)): ?>
                <div class="mt-2">
                    <?php if ($trend === 'improved'): ?>
                    <span class="badge bg-success"><i class="bi bi-arrow-up-circle"></i> Improving</span>
                    <?php elseif ($trend === 'worsened'): ?>
                    <span class="badge bg-danger"><i class="bi bi-arrow-down-circle"></i> Worsening</span>
                    <?php else: ?>
                    <span class="badge bg-secondary"><i class="bi bi-dash-circle"></i> Stable</span>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </div>
            <div class="card-footer bg-light small">
                <div class="row text-center">
                    <div class="col">
                        <div class="fw-bold"><?= htmlspecialchars($beneficiary['sex']) ?></div>
                        <div class="text-muted">Sex</div>
                    </div>
                    <div class="col">
                        <div class="fw-bold"><?= DateHelper::formatAge(DateHelper::ageInMonths($beneficiary['date_of_birth'])) ?></div>
                        <div class="text-muted">Age</div>
                    </div>
                    <div class="col">
                        <div class="fw-bold"><?= htmlspecialchars($beneficiary['barangay']) ?></div>
                        <div class="text-muted">Barangay</div>
                    </div>
                </div>
            </div>
        </div>

        <?php
        $__checks = [
            'Photo'              => !empty($beneficiary['photo']),
            'Place of Birth'     => !empty($beneficiary['place_of_birth']),
            'Purok/Zone'         => !empty($beneficiary['purok_zone']),
            'Household No.'      => !empty($beneficiary['household_no']),
            'Mother\'s Name'     => !empty($beneficiary['mother_name']),
            'Contact Number'     => !empty($beneficiary['contact_number']),
            'Income Class'       => !empty($beneficiary['income_classification']),
            'PhilHealth Status'  => !empty($beneficiary['philhealth_status']),
            'Has Assessment'     => !empty($assessments),
        ];
        $__filled = count(array_filter($__checks));
        $__total  = count($__checks);
        $__pct    = (int)round($__filled / $__total * 100);
        $__color  = $__pct >= 80 ? 'success' : ($__pct >= 50 ? 'warning' : 'danger');
        ?>

        <!-- Record Completeness (collapsible, collapsed by default) -->
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-header bg-white collapse-header d-flex justify-content-between align-items-center py-2"
                 data-bs-toggle="collapse" data-bs-target="#collapseCompleteness" aria-expanded="false">
                <span class="small fw-semibold"><i class="bi bi-check2-circle me-1 text-<?= $__color ?>"></i>Record Completeness</span>
                <div class="d-flex align-items-center gap-2">
                    <span class="badge bg-<?= $__color ?>"><?= $__pct ?>%</span>
                    <i class="bi bi-chevron-down small chevron"></i>
                </div>
            </div>
            <div class="collapse" id="collapseCompleteness">
                <div class="card-body py-2">
                    <div class="progress mb-2" style="height:8px;">
                        <div class="progress-bar bg-<?= $__color ?>" style="width:<?= $__pct ?>%"></div>
                    </div>
                    <div class="row g-1">
                        <?php foreach ($__checks as $__label => $__done): ?>
                        <div class="col-6 small <?= $__done ? 'text-success' : 'text-muted' ?>">
                            <i class="bi bi-<?= $__done ? 'check-circle-fill' : 'circle' ?> me-1"></i><?= $__label ?>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Personal Details (collapsible, collapsed by default) -->
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white collapse-header fw-semibold d-flex justify-content-between align-items-center py-2"
                 data-bs-toggle="collapse" data-bs-target="#collapseDetails" aria-expanded="false">
                <span class="small fw-semibold"><i class="bi bi-card-list me-1"></i>Personal Details</span>
                <i class="bi bi-chevron-down small chevron"></i>
            </div>
            <div class="collapse" id="collapseDetails">
                <div class="card-body small">
                    <dl class="row mb-0">
                        <dt class="col-5 text-muted">DOB</dt>
                        <dd class="col-7"><?= DateHelper::formatDate($beneficiary['date_of_birth']) ?></dd>
                        <dt class="col-5 text-muted">Purok</dt>
                        <dd class="col-7"><?= htmlspecialchars($beneficiary['purok_zone'] ?? '—') ?></dd>
                        <dt class="col-5 text-muted">HH No.</dt>
                        <dd class="col-7"><?= htmlspecialchars($beneficiary['household_no'] ?? '—') ?></dd>
                        <dt class="col-5 text-muted">Mother</dt>
                        <dd class="col-7"><?= htmlspecialchars($beneficiary['mother_name'] ?? '—') ?></dd>
                        <dt class="col-5 text-muted">Father</dt>
                        <dd class="col-7"><?= htmlspecialchars($beneficiary['father_name'] ?? '—') ?></dd>
                        <dt class="col-5 text-muted">Contact</dt>
                        <dd class="col-7"><?= htmlspecialchars($beneficiary['contact_number'] ?? '—') ?></dd>
                        <dt class="col-5 text-muted">Income Class</dt>
                        <dd class="col-7"><?= htmlspecialchars($beneficiary['income_classification'] ?? '—') ?></dd>
                        <dt class="col-5 text-muted">PhilHealth</dt>
                        <dd class="col-7"><?= htmlspecialchars($beneficiary['philhealth_status'] ?? '—') ?></dd>
                        <dt class="col-5 text-muted">4Ps</dt>
                        <dd class="col-7"><?= $beneficiary['is_4ps_member'] ? 'Yes' : 'No' ?></dd>
                    </dl>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-8">

        <!-- Z-Score Legend (collapsible, collapsed by default) -->
        <div class="card border-0 shadow-sm mb-2">
            <div class="card-header bg-white collapse-header py-2 d-flex justify-content-between align-items-center"
                 data-bs-toggle="collapse" data-bs-target="#collapseZscore" aria-expanded="false">
                <span class="small fw-semibold"><i class="bi bi-info-circle me-1 text-info"></i>Z-Score &amp; Status Legend</span>
                <i class="bi bi-chevron-down small chevron"></i>
            </div>
            <div class="collapse" id="collapseZscore">
                <div class="card-body py-2 small">
                    <strong>WFA</strong>:
                    <span class="badge status-suw">SUW</span> &lt;-3 &nbsp;
                    <span class="badge status-uw">UW</span> -3 to -2 &nbsp;
                    <span class="badge status-normal">Normal</span> -2 to +2<br>
                    <strong>WFL/H</strong>:
                    <span class="badge status-sw">SW</span> &lt;-3 &nbsp;
                    <span class="badge status-mw">MW</span> -3 to -2<br>
                    <span class="text-muted">Based on WHO 2006 Child Growth Standards</span>
                </div>
            </div>
        </div>

        <!-- Assessment History (collapsible, OPEN by default) -->
        <div class="card border-0 shadow-sm mb-2">
            <div class="card-header bg-white collapse-header d-flex justify-content-between align-items-center"
                 data-bs-toggle="collapse" data-bs-target="#collapseAssessments" aria-expanded="true">
                <span class="fw-semibold d-flex align-items-center gap-2">
                    <i class="bi bi-clipboard2-pulse me-1"></i>Assessment History
                    <span class="badge bg-secondary fw-normal"><?= count($assessments) ?></span>
                </span>
                <div class="d-flex align-items-center gap-2">
                    <?php if (hasPerm('assessments') && !in_array(strtolower($role), ['admin', 'nutritionist'])): ?>
                    <a href="<?= APP_URL ?>/assessments/create?bid=<?= $beneficiary['id'] ?>" class="btn btn-sm btn-success"
                       onclick="event.stopPropagation()">
                        <i class="bi bi-plus"></i> New Assessment
                    </a>
                    <?php endif; ?>
                    <i class="bi bi-chevron-down small chevron"></i>
                </div>
            </div>
            <div class="collapse show" id="collapseAssessments">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-sm mb-0 align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>Date</th><th>Age (mo)</th><th>Wt</th><th>Ht</th>
                                    <th>WFA Z</th><th>WFA</th><th>HFA</th><th>WFL/H</th><th>Period</th>
                                    <?php if (in_array($role, ['admin','nutritionist'])): ?><th></th><?php endif; ?>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($assessments)): ?>
                                <tr><td colspan="10" class="text-center text-muted py-4"><i class="bi bi-clipboard2-x fs-3 d-block mb-1"></i>No assessments recorded.</td></tr>
                                <?php endif; ?>
                                <?php foreach ($assessments as $a): ?>
                                <tr>
                                    <td><?= DateHelper::formatDate($a['assessment_date'], 'M j, Y') ?></td>
                                    <td><?= $a['age_in_months'] ?></td>
                                    <td><?= $a['weight_kg'] ?></td>
                                    <td><?= $a['height_cm'] ?: '—' ?></td>
                                    <td>
                                        <?php $z = $a['weight_for_age_zscore']; ?>
                                        <span class="<?= $z !== null && $z < -3 ? 'text-danger fw-bold' : ($z !== null && $z < -2 ? 'text-warning fw-bold' : '') ?>">
                                            <?= $z !== null ? number_format($z, 2) : '—' ?>
                                        </span>
                                    </td>
                                    <td><span class="badge status-<?= strtolower($a['nutritional_status']) ?>"><?= $a['nutritional_status'] ?></span></td>
                                    <td>
                                        <?php if (!empty($a['hfa_status'])): ?>
                                        <span class="badge status-<?= strtolower($a['hfa_status']) ?>"><?= $a['hfa_status'] ?></span>
                                        <?php else: ?>—<?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if (!empty($a['wflh_status'])): ?>
                                        <span class="badge status-<?= strtolower($a['wflh_status']) ?>"><?= $a['wflh_status'] ?></span>
                                        <?php else: ?>—<?php endif; ?>
                                    </td>
                                    <td><?= htmlspecialchars($a['period'] . ' ' . $a['assessment_year']) ?></td>
                                    <?php if (in_array($role, ['admin','nutritionist'])): ?>
                                    <td>
                                        <form action="<?= APP_URL ?>/assessments/<?= $a['id'] ?>/delete" method="post" class="d-inline">
                                            <?= \Core\Session::csrfField() ?>
                                            <button type="button" class="btn btn-sm btn-outline-danger confirm-trigger"
                                                    data-confirm-title="Delete Assessment"
                                                    data-confirm-message="Delete this assessment record? This cannot be undone."
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
        </div>

        <!-- Growth Chart (collapsible, COLLAPSED by default — lazy init) -->
        <?php if (count($assessments) >= 1): ?>
        <div class="card border-0 shadow-sm mb-2">
            <div class="card-header bg-white collapse-header d-flex justify-content-between align-items-center"
                 data-bs-toggle="collapse" data-bs-target="#collapseGrowthChart" aria-expanded="false">
                <span class="fw-semibold"><i class="bi bi-graph-up me-2 text-success"></i>Growth Chart</span>
                <div class="d-flex align-items-center gap-2">
                    <div class="btn-group btn-group-sm" role="group" onclick="event.stopPropagation()">
                        <button type="button" class="btn btn-outline-secondary active" id="chartToggleWeight">Weight</button>
                        <button type="button" class="btn btn-outline-secondary" id="chartToggleHeight">Height</button>
                    </div>
                    <i class="bi bi-chevron-down small chevron"></i>
                </div>
            </div>
            <div class="collapse" id="collapseGrowthChart">
                <div class="card-body">
                    <canvas id="growthChart" height="100"></canvas>
                    <div class="mt-2 d-flex gap-3 small text-muted">
                        <span><span style="display:inline-block;width:12px;height:3px;background:#ef4444;vertical-align:middle;"></span> SUW (&lt;-3 SD)</span>
                        <span><span style="display:inline-block;width:12px;height:3px;background:#f97316;vertical-align:middle;"></span> UW (&lt;-2 SD)</span>
                        <span><span style="display:inline-block;width:12px;height:3px;background:#22c55e;vertical-align:middle;"></span> Normal</span>
                        <span><span style="display:inline-block;width:12px;height:3px;background:#3b82f6;vertical-align:middle;"></span> WHO Median</span>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Program Enrollments (collapsible, collapsed by default) -->
        <div class="card border-0 shadow-sm mb-2">
            <div class="card-header bg-white collapse-header d-flex justify-content-between align-items-center"
                 data-bs-toggle="collapse" data-bs-target="#collapseEnrollments" aria-expanded="false">
                <span class="fw-semibold d-flex align-items-center gap-2">
                    <i class="bi bi-journal-check me-1"></i>Program Enrollments
                    <span class="badge bg-secondary fw-normal"><?= count($enrollments ?? []) ?></span>
                </span>
                <i class="bi bi-chevron-down small chevron"></i>
            </div>
            <div class="collapse" id="collapseEnrollments">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-sm mb-0">
                            <thead class="table-light">
                                <tr><th>Program</th><th>Intervention</th><th>Enrolled</th><th>Status</th><th>End Date</th></tr>
                            </thead>
                            <tbody>
                                <?php if (empty($enrollments)): ?>
                                <tr><td colspan="5" class="text-center text-muted py-4"><i class="bi bi-inbox fs-3 d-block mb-1"></i>No enrollments.</td></tr>
                                <?php endif; ?>
                                <?php foreach ($enrollments as $e): ?>
                                <tr>
                                    <td><span class="badge bg-primary"><?= htmlspecialchars($e['program']) ?></span></td>
                                    <td><?= !empty($e['intervention_type']) ? '<span class="badge bg-info text-dark">' . htmlspecialchars($e['intervention_type']) . '</span>' : '—' ?></td>
                                    <td><?= DateHelper::formatDate($e['enrollment_date'], 'M j, Y') ?></td>
                                    <td>
                                        <?php $sc = ['Active'=>'success','Completed'=>'secondary','Dropped'=>'danger']; ?>
                                        <span class="badge bg-<?= $sc[$e['status']] ?? 'secondary' ?>"><?= $e['status'] ?></span>
                                    </td>
                                    <td><?= $e['end_date'] ? DateHelper::formatDate($e['end_date'], 'M j, Y') : '—' ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Vitamin A Records (collapsible, collapsed by default) -->
        <div class="card border-0 shadow-sm mb-2">
            <div class="card-header bg-white collapse-header d-flex justify-content-between align-items-center"
                 data-bs-toggle="collapse" data-bs-target="#collapseVitaminA" aria-expanded="false">
                <span class="fw-semibold d-flex align-items-center gap-2">
                    <i class="bi bi-capsule me-1"></i>Vitamin A Records
                    <span class="badge bg-secondary fw-normal"><?= count($vitaminRecs ?? []) ?></span>
                </span>
                <i class="bi bi-chevron-down small chevron"></i>
            </div>
            <div class="collapse" id="collapseVitaminA">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-sm mb-0">
                            <thead class="table-light">
                                <tr><th>Date</th><th>Round</th><th>Year</th><th>Dosage</th><th>Capsule</th></tr>
                            </thead>
                            <tbody>
                                <?php if (empty($vitaminRecs)): ?>
                                <tr><td colspan="5" class="text-center text-muted py-4"><i class="bi bi-capsule fs-3 d-block mb-1"></i>No Vitamin A records.</td></tr>
                                <?php endif; ?>
                                <?php foreach ($vitaminRecs ?? [] as $v): ?>
                                <tr>
                                    <td><?= DateHelper::formatDate($v['distribution_date'], 'M j, Y') ?></td>
                                    <td><?= htmlspecialchars($v['round']) ?></td>
                                    <td><?= $v['year'] ?></td>
                                    <td><?= number_format($v['dosage_iu']) ?> IU</td>
                                    <td><span class="badge bg-<?= $v['capsule_color'] === 'Blue' ? 'primary' : 'danger' ?>"><?= $v['capsule_color'] ?></span></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- MNP / LNS-SQ Records (collapsible, collapsed by default) -->
        <?php $__mnpLnsCount = count($mnpRecs ?? []) + count($lnsRecs ?? []); ?>
        <div class="card border-0 shadow-sm mb-2">
            <div class="card-header bg-white collapse-header d-flex justify-content-between align-items-center"
                 data-bs-toggle="collapse" data-bs-target="#collapseMnpLns" aria-expanded="false">
                <span class="fw-semibold d-flex align-items-center gap-2">
                    <i class="bi bi-prescription2 me-1"></i>MNP / LNS-SQ Records
                    <span class="badge bg-secondary fw-normal"><?= $__mnpLnsCount ?></span>
                </span>
                <i class="bi bi-chevron-down small chevron"></i>
            </div>
            <div class="collapse" id="collapseMnpLns">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-sm mb-0">
                            <thead class="table-light">
                                <tr><th>Type</th><th>Date</th><th>Year</th><th>Age Group</th><th>Completed</th></tr>
                            </thead>
                            <tbody>
                                <?php if ($__mnpLnsCount === 0): ?>
                                <tr><td colspan="5" class="text-center text-muted py-4"><i class="bi bi-inbox fs-3 d-block mb-1"></i>No MNP / LNS-SQ records.</td></tr>
                                <?php endif; ?>
                                <?php foreach ($mnpRecs ?? [] as $m): ?>
                                <tr>
                                    <td><span class="badge bg-primary">MNP</span></td>
                                    <td><?= DateHelper::formatDate($m['date_given'], 'M j, Y') ?></td>
                                    <td><?= $m['year'] ?></td>
                                    <td><?= htmlspecialchars($m['age_group']) ?></td>
                                    <td><?= $m['completed_routine'] ? '<span class="text-success">Yes</span>' : '<span class="text-muted">No</span>' ?></td>
                                </tr>
                                <?php endforeach; ?>
                                <?php foreach ($lnsRecs ?? [] as $l): ?>
                                <tr>
                                    <td><span class="badge bg-success">LNS-SQ</span></td>
                                    <td><?= DateHelper::formatDate($l['date_given'], 'M j, Y') ?></td>
                                    <td><?= $l['year'] ?></td>
                                    <td><?= htmlspecialchars($l['age_group']) ?></td>
                                    <td><?= $l['completed_routine'] ? '<span class="text-success">Yes</span>' : '<span class="text-muted">No</span>' ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Dispensing Records (collapsible, collapsed by default) -->
        <div class="card border-0 shadow-sm mb-2">
            <div class="card-header bg-white collapse-header d-flex justify-content-between align-items-center"
                 data-bs-toggle="collapse" data-bs-target="#collapseDispensing" aria-expanded="false">
                <span class="fw-semibold d-flex align-items-center gap-2">
                    <i class="bi bi-prescription2 me-1 text-primary"></i>Medicine &amp; Supplement Dispensing
                    <span class="badge bg-secondary fw-normal"><?= count($dispensingRecs ?? []) ?></span>
                </span>
                <div class="d-flex align-items-center gap-2">
                    <?php if (hasPerm('dispensing')): ?>
                    <a href="<?= APP_URL ?>/dispensing/create?bid=<?= $beneficiary['id'] ?>" class="btn btn-sm btn-primary"
                       onclick="event.stopPropagation()">
                        <i class="bi bi-plus"></i> Record
                    </a>
                    <?php endif; ?>
                    <i class="bi bi-chevron-down small chevron"></i>
                </div>
            </div>
            <div class="collapse" id="collapseDispensing">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-sm mb-0">
                            <thead class="table-light">
                                <tr><th>Date</th><th>Program</th><th>Supplement / Medicine</th><th>Qty</th><th>Unit</th><th>Notes</th></tr>
                            </thead>
                            <tbody>
                                <?php if (empty($dispensingRecs)): ?>
                                <tr><td colspan="6" class="text-center text-muted py-4"><i class="bi bi-inbox fs-3 d-block mb-1"></i>No dispensing records yet.</td></tr>
                                <?php endif; ?>
                                <?php foreach ($dispensingRecs ?? [] as $d): ?>
                                <tr>
                                    <td class="text-nowrap"><?= \DateHelper::formatDate($d['date_dispensed'], 'M j, Y') ?></td>
                                    <td><span class="badge bg-primary"><?= htmlspecialchars($d['program']) ?></span></td>
                                    <td class="fw-semibold"><?= htmlspecialchars($d['supplement_type']) ?></td>
                                    <td><?= number_format((float)$d['quantity'], 1) ?></td>
                                    <td class="text-muted"><?= htmlspecialchars($d['unit']) ?></td>
                                    <td class="text-muted small"><?= htmlspecialchars($d['notes'] ?? '') ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>

<?php if (count($assessments) >= 1): ?>
<script>
window.__growthData = <?= json_encode(array_map(fn($a) => [
    'date'      => $a['assessment_date'],
    'ageMonths' => (int)$a['age_in_months'],
    'weight'    => (float)$a['weight_kg'],
    'height'    => $a['height_cm'] ? (float)$a['height_cm'] : null,
    'status'    => $a['nutritional_status'],
], array_reverse($assessments))) ?>;
window.__beneficiarySex = <?= json_encode($beneficiary['sex']) ?>;

// Lazy-init the chart only when the panel is first shown
(function () {
    var rendered = false;
    document.getElementById('collapseGrowthChart').addEventListener('shown.bs.collapse', function () {
        if (!rendered) {
            rendered = true;
            if (typeof initGrowthChart === 'function') initGrowthChart();
        }
    });
})();
</script>
<?php endif; ?>
