<?php $pageTitle = 'Database Backup'; ?>

<div class="d-flex justify-content-between align-items-center my-3">
    <h4 class="mb-0"><i class="bi bi-database-fill-down me-2"></i>Database Backup</h4>
    <div class="d-flex gap-2">
        <a href="<?= APP_URL ?>/backup/download" class="btn btn-outline-primary">
            <i class="bi bi-download me-1"></i>Download Live DB
        </a>
        <form action="<?= APP_URL ?>/backup/create" method="post" class="d-inline">
            <?= \Core\Session::csrfField() ?>
            <button type="submit" class="btn btn-primary">
                <i class="bi bi-plus-circle me-1"></i>Create Backup Now
            </button>
        </form>
    </div>
</div>

<!-- Info card -->
<div class="alert alert-info d-flex align-items-start gap-2 mb-3">
    <i class="bi bi-info-circle-fill mt-1"></i>
    <div>
        <strong>Automatic backups</strong> are created once per day via <code>mysqldump</code> whenever the mobile app syncs data.
        Up to <strong>7 daily backups</strong> are kept — older ones are pruned automatically.
        Use <em>Create Backup Now</em> to force an immediate dump.
        Backups are full SQL dumps that can be restored with <code>mysql -u root nms &lt; file.sql</code>.
    </div>
</div>

<!-- Backup list -->
<div class="card border-0 shadow-sm">
    <div class="card-header bg-white d-flex align-items-center gap-2 py-2">
        <i class="bi bi-archive text-primary"></i>
        <span class="fw-semibold">Saved Backups</span>
        <span class="badge bg-secondary ms-1"><?= count($backups) ?></span>
    </div>
    <div class="card-body p-0">
        <?php if (empty($backups)): ?>
        <div class="text-center text-muted py-5">
            <i class="bi bi-archive fs-1 d-block mb-2 opacity-25"></i>
            <p class="mb-1">No backups yet.</p>
            <small>Click <strong>Create Backup Now</strong> or wait for the next sync.</small>
        </div>
        <?php else: ?>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>#</th>
                        <th>Filename</th>
                        <th>Created</th>
                        <th>Size</th>
                        <th class="text-end">Action</th>
                    </tr>
                </thead>
                <tbody>
                <?php $i = 1; foreach ($backups as $b): ?>
                <?php
                    $ts      = date('M j, Y  H:i:s', $b['created_at']);
                    $sizeKb  = number_format($b['size_bytes'] / 1024, 1);
                    $sizeMb  = $b['size_bytes'] >= 1048576
                               ? ' (' . number_format($b['size_bytes'] / 1048576, 2) . ' MB)'
                               : '';
                    $isLatest = $i === 1;
                ?>
                <tr>
                    <td class="text-muted small"><?= $i++ ?></td>
                    <td>
                        <i class="bi bi-file-earmark-zip text-primary me-1"></i>
                        <span class="font-monospace small"><?= htmlspecialchars($b['filename']) ?></span>
                        <?php if ($isLatest): ?>
                        <span class="badge bg-success ms-1">Latest</span>
                        <?php endif; ?>
                    </td>
                    <td class="text-muted small"><?= $ts ?></td>
                    <td class="text-muted small"><?= $sizeKb ?> KB<?= $sizeMb ?> <span class="badge bg-light text-secondary border ms-1">SQL</span></td>
                    <td class="text-end">
                        <a href="<?= APP_URL ?>/backup/<?= urlencode($b['filename']) ?>"
                           class="btn btn-sm btn-outline-primary">
                            <i class="bi bi-download me-1"></i>Download
                        </a>
                    </td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
</div>

<p class="text-muted small mt-3">
    <i class="bi bi-folder2 me-1"></i>
    Stored in: <code>database/backups/</code> &nbsp;•&nbsp;
    Restore with: <code>mysql -u root nms &lt; nms_backup_YYYY-MM-DD_HHiiss.sql</code>
</p>

<!-- Backup Log -->
<div class="card border-0 shadow-sm mt-3">
    <div class="card-header bg-white d-flex align-items-center gap-2 py-2">
        <i class="bi bi-journal-text text-secondary"></i>
        <span class="fw-semibold">Backup Log</span>
        <span class="badge bg-secondary ms-1"><?= count($logLines) ?></span>
        <span class="text-muted small ms-auto">Last 50 entries • newest first</span>
    </div>
    <div class="card-body p-0">
        <?php if (empty($logLines)): ?>
        <div class="text-center text-muted py-4">
            <i class="bi bi-journal-x fs-2 d-block mb-2 opacity-25"></i>
            <small>No log entries yet. Log is written after each backup run.</small>
        </div>
        <?php else: ?>
        <div style="max-height: 300px; overflow-y: auto;">
            <table class="table table-sm mb-0 align-middle">
                <tbody>
                <?php foreach ($logLines as $line): ?>
                <?php
                    $isSuccess = str_contains($line, 'SUCCESS');
                    $isFailed  = str_contains($line, 'FAILED');
                ?>
                <tr class="<?= $isFailed ? 'table-danger' : '' ?>">
                    <td class="ps-3" style="width:18px;">
                        <?php if ($isSuccess): ?>
                            <i class="bi bi-check-circle-fill text-success"></i>
                        <?php elseif ($isFailed): ?>
                            <i class="bi bi-x-circle-fill text-danger"></i>
                        <?php else: ?>
                            <i class="bi bi-dash-circle text-muted"></i>
                        <?php endif; ?>
                    </td>
                    <td class="font-monospace small py-1"><?= htmlspecialchars($line) ?></td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
</div>
