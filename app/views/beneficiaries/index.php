<?php
$pageTitle = 'Beneficiaries';
$__role = \Core\Session::get('user_role');
$__isAdminView = in_array(strtolower($__role), ['admin', 'nutritionist']);
?>

<div class="d-flex justify-content-between align-items-center my-3">
    <h4 class="mb-0">
        <i class="bi bi-people-fill me-2"></i>Beneficiaries
        <?php if ($__isAdminView): ?>
        <span class="badge bg-info text-dark ms-2 fs-6 fw-normal">Submitted Records</span>
        <?php endif; ?>
    </h4>
    <div class="d-flex gap-2">
        <?php if (hasPerm('beneficiaries')): ?>
        <a href="<?= APP_URL ?>/beneficiaries/create" class="btn btn-primary">
            <i class="bi bi-plus-circle me-1"></i>Add Beneficiary
        </a>
        <?php endif; ?>
        <?php if ($__isAdminView): ?>
        <a href="<?= APP_URL ?>/beneficiaries/trash" class="btn btn-outline-secondary" title="View deleted beneficiaries">
            <i class="bi bi-trash3"></i>
        </a>
        <?php endif; ?>
    </div>
</div>

<div class="card border-0 shadow-sm mb-3">
    <div class="card-body py-2">
        <form method="get" class="row g-2 align-items-end">
            <div class="col-md-5">
                <input type="text" name="search" class="form-control" placeholder="Search by name..."
                       value="<?= htmlspecialchars($search ?? '') ?>">
            </div>
            <div class="col-md-3">
                <select name="barangay" class="form-select">
                    <option value="">All Barangays</option>
                    <?php foreach ($barangays as $b): ?>
                    <option value="<?= htmlspecialchars($b['barangay']) ?>"
                        <?= ($filterBar ?? '') === $b['barangay'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($b['barangay']) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <select name="source" class="form-select">
                    <option value="">All Sources</option>
                    <option value="Walk-in" <?= ($source ?? '') === 'Walk-in' ? 'selected' : '' ?>>Walk-in</option>
                    <option value="Excel" <?= ($source ?? '') === 'Excel' ? 'selected' : '' ?>>Excel (Device)</option>
                    <option value="Google" <?= ($source ?? '') === 'Google' ? 'selected' : '' ?>>Google Drive</option>
                </select>
            </div>
            <div class="col-md-2">
                <select name="age_status" class="form-select">
                    <option value="">All Ages</option>
                    <option value="active"    <?= ($ageStatus ?? '') === 'active'    ? 'selected' : '' ?>>Active (0–59 mo)</option>
                    <option value="aged_out"  <?= ($ageStatus ?? '') === 'aged_out'  ? 'selected' : '' ?>>Aged Out (&gt;59 mo)</option>
                    <option value="recovered" <?= ($ageStatus ?? '') === 'recovered' ? 'selected' : '' ?>>Recovered</option>
                </select>
            </div>
            <div class="col-md-1">
                <button type="submit" class="btn btn-outline-primary w-100"><i class="bi bi-search"></i></button>
            </div>
        </form>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle">
                <thead class="table-light">
                    <tr>
                        <th>#</th><th>Name</th><th>DOB</th><th>Age</th><th>Sex</th><th>Barangay</th><th>Last Assessed</th><th>Source</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($rows)): ?>
                    <tr><td colspan="9" class="text-center text-muted py-4"><i class="bi bi-inbox fs-3 d-block mb-1"></i>No beneficiaries found.</td></tr>
                    <?php endif; ?>
                    <?php $rowNum = (($page ?? 1) - 1) * 25 + 1; ?>
                    <?php foreach ($rows as $b): ?>
                    <tr>
                        <td class="text-muted small"><?= $rowNum++ ?></td>
                        <td>
                            <a href="<?= APP_URL ?>/beneficiaries/<?= $b['id'] ?>" class="fw-semibold text-decoration-none">
                                <?= htmlspecialchars($b['last_name'] . ', ' . $b['first_name']) ?>
                                <?= $b['middle_name'] ? htmlspecialchars(' ' . $b['middle_name']) : '' ?>
                            </a>
                            <?php if (($b['validation_status'] ?? 'validated') === 'pending'): ?>
                            <span class="badge status-pending ms-1" title="Awaiting midwife validation"><i class="bi bi-hourglass-split me-1"></i>Pending</span>
                            <?php elseif (($b['validation_status'] ?? '') === 'rejected'): ?>
                            <span class="badge status-rejected ms-1" title="Registration rejected"><i class="bi bi-x-circle me-1"></i>Rejected</span>
                            <?php elseif (!empty($b['submitted_at'])): ?>
                            <span class="badge bg-info text-dark ms-1" title="Submitted to admin"><i class="bi bi-send-check me-1"></i>Submitted</span>
                            <?php elseif (DateHelper::ageInMonths($b['date_of_birth']) > 59): ?>
                            <span class="badge bg-secondary ms-1" title="Child is over 59 months old">Aged Out</span>
                            <?php elseif (!empty($b['is_recovered'])): ?>
                            <span class="badge bg-success ms-1" title="Child was previously malnourished and has recovered to Normal status"><i class="bi bi-heart-fill me-1"></i>Recovered</span>
                            <?php endif; ?>
                        </td>
                        <td><?= DateHelper::formatDate($b['date_of_birth'], 'M j, Y') ?></td>
                        <td class="text-nowrap"><?= DateHelper::formatAge(DateHelper::ageInMonths($b['date_of_birth'])) ?></td>
                        <td><?= htmlspecialchars($b['sex']) ?></td>
                        <td><?= htmlspecialchars($b['barangay']) ?></td>
                        <td class="text-nowrap small">
                            <?php if (!empty($b['last_assessed'])): ?>
                            <?php $daysAgo = (int)((time() - strtotime($b['last_assessed'])) / 86400); ?>
                            <span title="<?= DateHelper::formatDate($b['last_assessed'], 'M j, Y') ?>"
                                  class="<?= $daysAgo > 180 ? 'text-danger fw-semibold' : ($daysAgo > 90 ? 'text-warning' : 'text-success') ?>">
                                <?= DateHelper::formatDate($b['last_assessed'], 'M j, Y') ?>
                            </span>
                            <?php else: ?>
                            <span class="text-muted fst-italic">Never</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if (!empty($b['source'])): ?>
                            <?php
                                $sourceLabels = ['Walk-in' => 'Walk-in', 'Excel' => 'Excel (Device)', 'Excel Import' => 'Excel (Device)', 'Google' => 'Google Drive'];
                                $sourceLabel  = $sourceLabels[$b['source']] ?? $b['source'];
                                $sourceBadge  = $b['source'] === 'Walk-in' ? 'bg-success' : ($b['source'] === 'Google' ? 'bg-primary' : 'bg-secondary');
                            ?>
                            <span class="badge <?= $sourceBadge ?> bg-opacity-75">
                                <?= htmlspecialchars($sourceLabel) ?>
                            </span>
                            <?php else: ?><span class="text-muted small">—</span><?php endif; ?>
                        </td>
                        <td class="text-end">
                            <a href="<?= APP_URL ?>/beneficiaries/<?= $b['id'] ?>" class="btn btn-sm btn-outline-secondary">
                                <i class="bi bi-eye"></i>
                            </a>
                            <?php if (hasPerm('beneficiaries')): ?>
                            <a href="<?= APP_URL ?>/beneficiaries/<?= $b['id'] ?>/edit" class="btn btn-sm btn-outline-primary">
                                <i class="bi bi-pencil"></i>
                            </a>
                            <?php endif; ?>
                            <?php if (strtolower(\Core\Session::get('user_role')) === 'bns'
                                    && ($b['validation_status'] ?? '') === 'validated'
                                    && empty($b['submitted_at'])): ?>
                            <form action="<?= APP_URL ?>/beneficiaries/<?= $b['id'] ?>/submit" method="post" class="d-inline">
                                <?= \Core\Session::csrfField() ?>
                                <button type="submit" class="btn btn-sm btn-success" title="Submit to Admin">
                                    <i class="bi bi-send"></i>
                                </button>
                            </form>
                            <?php endif; ?>
                            <?php if (in_array(\Core\Session::get('user_role'), ['admin','nutritionist'])): ?>
                            <form action="<?= APP_URL ?>/beneficiaries/<?= $b['id'] ?>/delete" method="post" class="d-inline">
                                <?= \Core\Session::csrfField() ?>
                                <button type="button" class="btn btn-sm btn-outline-danger confirm-trigger"
                                        data-confirm-title="Delete Beneficiary"
                                        data-confirm-message="Move <strong><?= htmlspecialchars($b['last_name'] . ', ' . $b['first_name']) ?></strong> to trash?"
                                        data-confirm-btn="Delete"
                                        data-confirm-class="btn-danger">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php if (($totalPages ?? 1) > 1): ?>
    <div class="card-footer bg-white d-flex justify-content-between align-items-center">
        <span class="text-muted small">Showing <?= count($rows) ?> of <?= number_format($total) ?> beneficiaries</span>
        <nav>
            <ul class="pagination pagination-sm mb-0">
                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                    <a class="page-link" href="?page=<?= $i ?>&search=<?= urlencode($search ?? '') ?>&barangay=<?= urlencode($filterBar ?? '') ?>&source=<?= urlencode($source ?? '') ?>&age_status=<?= urlencode($ageStatus ?? '') ?>"><?= $i ?></a>
                </li>
                <?php endfor; ?>
            </ul>
        </nav>
    </div>
    <?php endif; ?>
</div>
