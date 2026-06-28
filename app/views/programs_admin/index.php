<?php $pageTitle = 'Manage Programs'; ?>

<div class="d-flex justify-content-between align-items-center my-3">
    <h4 class="mb-0"><i class="bi bi-grid-fill me-2 text-primary"></i>Program Management</h4>
    <a href="<?= APP_URL ?>/programs-admin/create" class="btn btn-primary btn-sm">
        <i class="bi bi-plus-circle me-1"></i>Add Program
    </a>
</div>

<div class="alert alert-info small">
    <i class="bi bi-info-circle me-1"></i>
    <strong>OPT, DSP, and MNS</strong> have dedicated pages with specialized features.
    New programs you add here will use the <strong>generic enrollment page</strong> with basic enrollment tracking.
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-sm table-hover mb-0 align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Order</th>
                        <th>Icon</th>
                        <th>Code</th>
                        <th>Name</th>
                        <th>Type</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($programs as $p): ?>
                    <tr class="<?= !$p['is_active'] ? 'text-muted' : '' ?>">
                        <td><?= $p['sort_order'] ?></td>
                        <td><i class="bi <?= htmlspecialchars($p['icon']) ?> text-<?= htmlspecialchars($p['color']) ?> fs-5"></i></td>
                        <td><span class="badge bg-<?= htmlspecialchars($p['color']) ?>"><?= htmlspecialchars($p['code']) ?></span></td>
                        <td>
                            <div class="fw-semibold"><?= htmlspecialchars($p['name']) ?></div>
                            <div class="small text-muted"><?= htmlspecialchars($p['description'] ?? '') ?></div>
                        </td>
                        <td><span class="badge bg-secondary"><?= ucfirst($p['type']) ?></span></td>
                        <td>
                            <?php if ($p['is_active']): ?>
                            <span class="badge bg-success">Active</span>
                            <?php else: ?>
                            <span class="badge bg-danger">Inactive</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div class="d-flex gap-1">
                                <?php if (!in_array($p['code'], ['OPT','DSP','MNS'])): ?>
                                <a href="<?= APP_URL ?>/programs/<?= strtolower($p['code']) ?>"
                                   class="btn btn-sm btn-outline-<?= htmlspecialchars($p['color']) ?>">
                                    <i class="bi bi-eye"></i>
                                </a>
                                <?php endif; ?>
                                <a href="<?= APP_URL ?>/programs-admin/<?= $p['id'] ?>/edit"
                                   class="btn btn-sm btn-outline-secondary">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <form method="post" action="<?= APP_URL ?>/programs-admin/<?= $p['id'] ?>/toggle" class="d-inline">
                                    <input type="hidden" name="csrf_token" value="<?= \Core\Session::generateCsrf() ?>">
                                    <button type="button" class="btn btn-sm <?= $p['is_active'] ? 'btn-outline-danger' : 'btn-outline-success' ?> confirm-trigger"
                                            data-confirm-title="<?= $p['is_active'] ? 'Deactivate' : 'Activate' ?> Program"
                                            data-confirm-message="<?= $p['is_active'] ? 'Deactivate' : 'Activate' ?> the program <strong><?= htmlspecialchars($p['name']) ?></strong>?"
                                            data-confirm-btn="<?= $p['is_active'] ? 'Deactivate' : 'Activate' ?>"
                                            data-confirm-class="<?= $p['is_active'] ? 'btn-danger' : 'btn-success' ?>">
                                        <i class="bi bi-<?= $p['is_active'] ? 'pause' : 'play' ?>-circle"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
