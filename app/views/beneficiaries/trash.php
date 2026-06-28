<?php $pageTitle = 'Deleted Beneficiaries'; ?>

<div class="d-flex align-items-center my-3">
    <a href="<?= APP_URL ?>/beneficiaries" class="btn btn-sm btn-outline-secondary me-3">
        <i class="bi bi-arrow-left"></i>
    </a>
    <h4 class="mb-0"><i class="bi bi-trash3 me-2 text-danger"></i>Deleted Beneficiaries</h4>
</div>

<div class="alert alert-info border-0 shadow-sm small mb-3">
    <i class="bi bi-info-circle me-1"></i>
    These beneficiaries have been soft-deleted. Their records and assessment history are preserved. You can restore them at any time.
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Name</th><th>DOB</th><th>Age</th><th>Sex</th><th>Barangay</th>
                        <th>Last Assessed</th><th>Deleted On</th><th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($rows)): ?>
                    <tr>
                        <td colspan="8" class="text-center text-muted py-5">
                            <i class="bi bi-trash3 fs-3 d-block mb-1"></i>No deleted beneficiaries.
                        </td>
                    </tr>
                    <?php endif; ?>
                    <?php foreach ($rows as $b): ?>
                    <tr class="text-muted">
                        <td>
                            <span class="fw-semibold text-dark">
                                <?= htmlspecialchars($b['last_name'] . ', ' . $b['first_name']) ?>
                                <?= $b['middle_name'] ? htmlspecialchars(' ' . $b['middle_name']) : '' ?>
                            </span>
                        </td>
                        <td><?= DateHelper::formatDate($b['date_of_birth'], 'M j, Y') ?></td>
                        <td class="text-nowrap"><?= DateHelper::formatAge(DateHelper::ageInMonths($b['date_of_birth'])) ?></td>
                        <td><?= htmlspecialchars($b['sex']) ?></td>
                        <td><?= htmlspecialchars($b['barangay']) ?></td>
                        <td class="small">
                            <?= !empty($b['last_assessed']) ? DateHelper::formatDate($b['last_assessed'], 'M j, Y') : '<span class="fst-italic">Never</span>' ?>
                        </td>
                        <td class="small"><?= DateHelper::formatDate($b['deleted_at'], 'M j, Y') ?></td>
                        <td class="text-end">
                            <form action="<?= APP_URL ?>/beneficiaries/<?= $b['id'] ?>/restore" method="post" class="d-inline">
                                <?= \Core\Session::csrfField() ?>
                                <button type="button" class="btn btn-sm btn-outline-success confirm-trigger"
                                        data-confirm-title="Restore Beneficiary"
                                        data-confirm-message="Restore <strong><?= htmlspecialchars($b['last_name'] . ', ' . $b['first_name']) ?></strong>?"
                                        data-confirm-btn="Restore"
                                        data-confirm-class="btn-success">
                                    <i class="bi bi-arrow-counterclockwise me-1"></i>Restore
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
