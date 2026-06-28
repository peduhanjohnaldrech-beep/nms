<?php $pageTitle = 'User Management'; ?>

<div class="d-flex justify-content-between align-items-center my-3">
    <h4 class="mb-0"><i class="bi bi-person-gear me-2"></i>User Management</h4>
    <a href="<?= APP_URL ?>/users/create" class="btn btn-primary">
        <i class="bi bi-person-plus me-1"></i>Add User
    </a>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle">
                <thead class="table-light">
                    <tr>
                        <th>#</th><th>Username</th><th>Full Name</th><th>Role</th>
                        <th>Barangay</th><th>Status</th><th>Created</th><th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($users)): ?>
                    <tr><td colspan="8" class="text-center text-muted py-4">No users found.</td></tr>
                    <?php endif; ?>
                    <?php $rowNum = 1; ?>
                    <?php foreach ($users as $u): ?>
                    <tr>
                        <td class="text-muted small"><?= $rowNum++ ?></td>
                        <td class="fw-semibold"><?= htmlspecialchars($u['username']) ?></td>
                        <td><?= htmlspecialchars($u['full_name'] ?? '—') ?></td>
                        <td>
                            <?php $badgeColor = strtolower($u['role']) === 'admin' ? 'danger' : 'secondary'; ?>
                            <span class="badge bg-<?= $badgeColor ?>"><?= htmlspecialchars($u['role']) ?></span>
                        </td>
                        <td><?= htmlspecialchars($u['barangay'] ?? '—') ?></td>
                        <td>
                            <span class="badge bg-<?= $u['is_active'] ? 'success' : 'secondary' ?>">
                                <?= $u['is_active'] ? 'Active' : 'Inactive' ?>
                            </span>
                        </td>
                        <td><?= DateHelper::formatDate($u['created_at'], 'M j, Y') ?></td>
                        <td class="text-end">
                            <a href="<?= APP_URL ?>/users/<?= $u['id'] ?>/edit" class="btn btn-sm btn-outline-primary">
                                <i class="bi bi-pencil"></i>
                            </a>
                            <?php if ($u['id'] !== (int)\Core\Session::get('user_id')): ?>
                            <?php if ($u['is_active']): ?>
                            <form action="<?= APP_URL ?>/users/<?= $u['id'] ?>/delete" method="post" class="d-inline">
                                <?= \Core\Session::csrfField() ?>
                                <button type="button" class="btn btn-sm btn-outline-warning confirm-trigger" title="Deactivate"
                                        data-confirm-title="Deactivate User"
                                        data-confirm-message="Deactivate <strong><?= htmlspecialchars($u['username']) ?></strong>? They will no longer be able to log in."
                                        data-confirm-btn="Deactivate"
                                        data-confirm-class="btn-warning">
                                    <i class="bi bi-person-x"></i>
                                </button>
                            </form>
                            <?php else: ?>
                            <form action="<?= APP_URL ?>/users/<?= $u['id'] ?>/activate" method="post" class="d-inline">
                                <?= \Core\Session::csrfField() ?>
                                <button type="button" class="btn btn-sm btn-outline-success confirm-trigger" title="Activate"
                                        data-confirm-title="Activate User"
                                        data-confirm-message="Activate <strong><?= htmlspecialchars($u['username']) ?></strong>? They will be able to log in again."
                                        data-confirm-btn="Activate"
                                        data-confirm-class="btn-success">
                                    <i class="bi bi-person-check"></i>
                                </button>
                            </form>
                            <?php endif; ?>
                            <form action="<?= APP_URL ?>/users/<?= $u['id'] ?>/destroy" method="post" class="d-inline">
                                <?= \Core\Session::csrfField() ?>
                                <button type="button" class="btn btn-sm btn-danger confirm-trigger" title="Delete permanently"
                                        data-confirm-title="Delete User"
                                        data-confirm-message="Permanently delete <strong><?= htmlspecialchars($u['username']) ?></strong>? This action cannot be undone."
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
</div>

