<?php $pageTitle = 'Validation Queue'; ?>

<div class="d-flex justify-content-between align-items-center my-3">
    <div>
        <h4 class="mb-0"><i class="bi bi-shield-check me-2 text-warning"></i>Validation Queue</h4>
        <p class="text-muted small mb-0 mt-1">Review and approve/reject pending beneficiary registrations from mobile</p>
    </div>
    <span class="badge bg-warning text-dark fs-6"><?= count($rows) ?> Pending</span>
</div>

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
                            <button class="btn btn-sm btn-outline-danger"
                                    data-bs-toggle="modal"
                                    data-bs-target="#rejectModal<?= $b['id'] ?>"
                                    title="Reject">
                                <i class="bi bi-x-lg"></i> Reject
                            </button>

                            <!-- Reject modal for this row -->
                            <div class="modal fade" id="rejectModal<?= $b['id'] ?>" tabindex="-1">
                                <div class="modal-dialog modal-dialog-centered">
                                    <div class="modal-content">
                                        <form action="<?= APP_URL ?>/beneficiaries/<?= $b['id'] ?>/reject" method="post">
                                            <?= \Core\Session::csrfField() ?>
                                            <div class="modal-header">
                                                <h5 class="modal-title"><i class="bi bi-x-circle text-danger me-2"></i>Reject Registration</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                            </div>
                                            <div class="modal-body">
                                                <p class="text-muted small mb-3">
                                                    Rejecting: <strong><?= htmlspecialchars($b['last_name'] . ', ' . $b['first_name']) ?></strong>
                                                </p>
                                                <label class="form-label fw-semibold">Reason / Notes <span class="text-danger">*</span></label>
                                                <textarea name="rejection_note" class="form-control" rows="3"
                                                          placeholder="Explain why this registration is being rejected..." required></textarea>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                <button type="submit" class="btn btn-danger"><i class="bi bi-x-lg me-1"></i>Reject</button>
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

<div class="mt-3 text-end">
    <a href="<?= APP_URL ?>/beneficiaries" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left me-1"></i>Back to Beneficiaries
    </a>
</div>
<?php endif; ?>
