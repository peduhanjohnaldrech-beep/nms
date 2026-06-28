<?php $pageTitle = 'For Follow-up'; ?>

<div class="d-flex justify-content-between align-items-center my-3">
    <h4 class="mb-0"><i class="bi bi-exclamation-triangle-fill text-danger me-2"></i>For Follow-up</h4>
    <span class="text-muted small"><?= count($rows) ?> beneficiaries with worsening status</span>
</div>

<div class="alert alert-warning border-0 shadow-sm">
    <i class="bi bi-info-circle me-2"></i>
    These beneficiaries had a <strong>worse nutritional status</strong> in their most recent assessment compared to the previous one.
    Immediate follow-up is recommended.
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-sm table-hover mb-0 align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Name</th>
                        <th>Barangay</th>
                        <th>Age</th>
                        <th>Previous Status</th>
                        <th>Current Status</th>
                        <th>Last Assessment</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($rows)): ?>
                    <tr><td colspan="7" class="text-center text-muted py-4">
                        <i class="bi bi-check-circle text-success fs-4 d-block mb-2"></i>
                        No beneficiaries flagged for follow-up.
                    </td></tr>
                    <?php endif; ?>
                    <?php foreach ($rows as $r): ?>
                    <?php
                    $statusColors = ['SUW'=>'danger','UW'=>'warning','Normal'=>'success','OW'=>'info','OB'=>'secondary'];
                    $prevColor    = $statusColors[$r['prev_status']] ?? 'secondary';
                    $currColor    = $statusColors[$r['curr_status']] ?? 'secondary';
                    ?>
                    <tr>
                        <td class="fw-semibold">
                            <a href="<?= APP_URL ?>/beneficiaries/<?= $r['id'] ?>">
                                <?= htmlspecialchars($r['last_name'] . ', ' . $r['first_name']) ?>
                            </a>
                        </td>
                        <td><?= htmlspecialchars($r['barangay']) ?></td>
                        <td><?= DateHelper::formatAge(DateHelper::ageInMonths($r['date_of_birth'])) ?></td>
                        <td><span class="badge bg-<?= $prevColor ?>"><?= htmlspecialchars($r['prev_status']) ?></span></td>
                        <td>
                            <span class="badge bg-<?= $currColor ?>">
                                <i class="bi bi-arrow-down-circle me-1"></i><?= htmlspecialchars($r['curr_status']) ?>
                            </span>
                        </td>
                        <td class="text-muted small"><?= DateHelper::formatDate($r['last_assessment_date'], 'M j, Y') ?></td>
                        <td class="text-nowrap">
                            <a href="<?= APP_URL ?>/assessments/create?bid=<?= $r['id'] ?>" class="btn btn-sm btn-primary me-1" title="Record a new assessment for this child">
                                <i class="bi bi-clipboard2-plus me-1"></i>New Assessment
                            </a>
                            <a href="<?= APP_URL ?>/beneficiaries/<?= $r['id'] ?>" class="btn btn-sm btn-outline-secondary" title="View profile">
                                <i class="bi bi-person me-1"></i>View
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
