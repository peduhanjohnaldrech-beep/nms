<?php $pageTitle = 'Activity Log'; ?>

<div class="d-flex justify-content-between align-items-center my-3">
    <h4 class="mb-0"><i class="bi bi-journal-text me-2"></i>Activity Log</h4>
    <span class="text-muted small"><?= number_format($total) ?> entries</span>
</div>

<div class="card border-0 shadow-sm mb-3">
    <div class="card-body py-2">
        <form class="row g-2 align-items-end" method="get">
            <div class="col-md-4">
                <label class="form-label small mb-1">Action</label>
                <select name="action" class="form-select form-select-sm">
                    <option value="">All Actions</option>
                    <?php foreach ($actions as $a): ?>
                    <option value="<?= htmlspecialchars($a) ?>" <?= $filterAction === $a ? 'selected' : '' ?>>
                        <?= htmlspecialchars($a) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label small mb-1">User</label>
                <input type="text" name="user" class="form-control form-control-sm"
                       value="<?= htmlspecialchars($filterUser) ?>" placeholder="Search user...">
            </div>
            <div class="col-auto">
                <button class="btn btn-sm btn-primary">Filter</button>
                <a href="<?= APP_URL ?>/activity" class="btn btn-sm btn-outline-secondary ms-1">Reset</a>
            </div>
        </form>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-sm table-hover mb-0 align-middle">
                <thead class="table-light">
                    <tr>
                        <th style="width:160px">Date/Time</th>
                        <th>User</th>
                        <th>Action</th>
                        <th>Description</th>
                        <th>IP Address</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($logs)): ?>
                    <tr><td colspan="5" class="text-center text-muted py-4">No activity logs found.</td></tr>
                    <?php endif; ?>
                    <?php foreach ($logs as $log): ?>
                    <tr>
                        <td class="text-muted small"><?= date('M j, Y H:i', strtotime($log['created_at'])) ?></td>
                        <td><?= htmlspecialchars($log['user_name'] ?? '—') ?></td>
                        <td>
                            <?php
                            $badgeClass = match(true) {
                                str_contains($log['action'], 'login')    => 'bg-success',
                                str_contains($log['action'], 'logout')   => 'bg-secondary',
                                str_contains($log['action'], 'delete')   => 'bg-danger',
                                str_contains($log['action'], 'create')   => 'bg-primary',
                                str_contains($log['action'], 'update')   => 'bg-info text-dark',
                                default                                  => 'bg-light text-dark border',
                            };
                            ?>
                            <span class="badge <?= $badgeClass ?>"><?= htmlspecialchars($log['action']) ?></span>
                        </td>
                        <td class="small"><?= htmlspecialchars($log['description'] ?? '') ?></td>
                        <td class="text-muted small"><?= htmlspecialchars($log['ip_address'] ?? '') ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php if ($totalPages > 1): ?>
<nav class="mt-3">
    <ul class="pagination pagination-sm justify-content-center">
        <?php for ($p = 1; $p <= $totalPages; $p++): ?>
        <li class="page-item <?= $p === $page ? 'active' : '' ?>">
            <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $p])) ?>"><?= $p ?></a>
        </li>
        <?php endfor; ?>
    </ul>
</nav>
<?php endif; ?>
