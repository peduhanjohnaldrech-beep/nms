<?php
$pageTitle = 'Import Storage';

function fmtSize(int $bytes): string {
    if ($bytes >= 1048576) return round($bytes / 1048576, 1) . ' MB';
    if ($bytes >= 1024)    return round($bytes / 1024, 1) . ' KB';
    return $bytes . ' B';
}

function previewable(string $mime, string $ext): bool {
    return str_starts_with($mime, 'image/')
        || $mime === 'application/pdf'
        || str_starts_with($mime, 'text/')
        || in_array($ext, ['xlsx', 'xls']);
}

$isAdmin = \Core\Session::get('user_role') === 'admin';
?>

<div class="d-flex justify-content-between align-items-center my-3">
    <div class="d-flex align-items-center gap-2">
        <a href="<?= APP_URL ?>/import" class="btn btn-sm btn-outline-secondary">
            <i class="bi bi-arrow-left"></i>
        </a>
        <h4 class="mb-0"><i class="bi bi-hdd me-2"></i>Import Storage</h4>
    </div>
</div>

<!-- Tabs -->
<ul class="nav nav-tabs mb-3">
    <li class="nav-item">
        <a class="nav-link <?= $tab === 'imports' ? 'active' : '' ?>"
           href="<?= APP_URL ?>/import/storage?tab=imports">
            <i class="bi bi-file-earmark-excel me-1 text-success"></i>Beneficiary Imports
            <span class="badge bg-secondary ms-1"><?= count($importFiles) ?></span>
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link <?= $tab === 'files' ? 'active' : '' ?>"
           href="<?= APP_URL ?>/import/storage?tab=files">
            <i class="bi bi-folder2 me-1 text-warning"></i>Other Files
            <span class="badge bg-secondary ms-1"><?= count($otherFiles) ?></span>
        </a>
    </li>
</ul>

<?php
/* ========================================================
   TAB: BENEFICIARY IMPORTS
   ======================================================== */
if ($tab === 'imports'):
?>
<div class="row g-3">
    <!-- Import Folder Sidebar -->
    <div class="col-lg-3">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white fw-semibold small">
                <i class="bi bi-folder me-1"></i>Folders
            </div>
            <div class="list-group list-group-flush">
                <a href="<?= APP_URL ?>/import/storage?tab=imports"
                   class="list-group-item list-group-item-action d-flex justify-content-between align-items-center <?= $folder === '' ? 'active' : '' ?>">
                    <span><i class="bi bi-hdd me-2"></i>All files</span>
                </a>
                <?php foreach ($importFolders as $f): ?>
                <a href="<?= APP_URL ?>/import/storage?tab=imports&folder=<?= urlencode($f['name']) ?>"
                   class="list-group-item list-group-item-action d-flex justify-content-between align-items-center
                          <?= $folder === $f['name'] ? 'active' : '' ?>">
                    <span>
                        <i class="bi bi-folder2<?= $folder === $f['name'] ? '-open' : '' ?> me-2
                           text-<?= $folder === $f['name'] ? 'white' : 'warning' ?>"></i>
                        <?= htmlspecialchars($f['name']) ?>
                    </span>
                    <span class="badge bg-<?= $folder === $f['name'] ? 'light text-dark' : 'secondary' ?>">
                        <?= $f['file_count'] ?>
                    </span>
                </a>
                <?php endforeach; ?>
                <?php if (empty($importFolders)): ?>
                <div class="list-group-item text-muted small">No folders yet.</div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Import Files -->
    <div class="col-lg-9">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <nav aria-label="breadcrumb" class="mb-0">
                    <ol class="breadcrumb mb-0 small">
                        <li class="breadcrumb-item"><a href="<?= APP_URL ?>/import/storage?tab=imports">Beneficiary Imports</a></li>
                        <?php if ($folder !== ''): ?>
                        <li class="breadcrumb-item active"><?= htmlspecialchars($folder) ?></li>
                        <?php endif; ?>
                    </ol>
                </nav>
                <span class="text-muted small">
                    <?= count($importFiles) ?> file<?= count($importFiles) !== 1 ? 's' : '' ?>
                    <?php if (!empty($importFiles)): ?>
                    &mdash; <?= fmtSize(array_sum(array_column($importFiles, 'size'))) ?>
                    <?php endif; ?>
                </span>
            </div>
            <div class="card-body p-0">
                <?php if (empty($importFiles)): ?>
                <div class="text-center text-muted py-5">
                    <i class="bi bi-file-earmark-excel fs-1 d-block mb-2 text-success opacity-50"></i>
                    No import files <?= $folder !== '' ? "in \"" . htmlspecialchars($folder) . "\"" : 'here' ?>.
                </div>
                <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-sm table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>File Name</th>
                                <th>Size</th>
                                <th>Imported By</th>
                                <th>Date</th>
                                <th class="text-center">Rows</th>
                                <th class="text-center">OK</th>
                                <th class="text-center">Err</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($importFiles as $f): ?>
                        <tr>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <i class="bi bi-file-earmark-excel text-success fs-5"></i>
                                    <div>
                                        <div class="fw-semibold small"><?= htmlspecialchars($f['original_filename']) ?></div>
                                        <?php if ($f['original_filename'] !== $f['saved_filename']): ?>
                                        <div class="text-muted" style="font-size:.72rem"><?= htmlspecialchars($f['saved_filename']) ?></div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </td>
                            <td class="small text-nowrap"><?= fmtSize($f['size']) ?></td>
                            <td class="small"><?= htmlspecialchars($f['imported_by_name']) ?></td>
                            <td class="small text-nowrap">
                                <?= $f['import_date'] ? DateHelper::formatDate($f['import_date'], 'M j, Y') : '—' ?>
                                <?php if ($f['import_date']): ?>
                                <div class="text-muted" style="font-size:.72rem"><?= DateHelper::formatDate($f['import_date'], 'g:i A') ?></div>
                                <?php endif; ?>
                            </td>
                            <td class="text-center small"><?= $f['total_rows'] ?></td>
                            <td class="text-center">
                                <?php if (is_numeric($f['success_count'])): ?>
                                <span class="badge bg-success"><?= $f['success_count'] ?></span>
                                <?php else: ?><span class="text-muted">—</span><?php endif; ?>
                            </td>
                            <td class="text-center">
                                <?php if (is_numeric($f['error_count'])): ?>
                                <span class="badge bg-<?= $f['error_count'] > 0 ? 'danger' : 'secondary' ?>"><?= $f['error_count'] ?></span>
                                <?php else: ?><span class="text-muted">—</span><?php endif; ?>
                            </td>
                            <td class="text-end text-nowrap">
                                <?php if ($f['log_id']): ?>
                                <button class="btn btn-xs btn-outline-secondary py-0 px-2"
                                        onclick="openPreview('<?= APP_URL ?>/import/<?= $f['log_id'] ?>/view','<?= htmlspecialchars(addslashes($f['original_filename'])) ?>')"
                                        title="View">
                                    <i class="bi bi-eye"></i>
                                </button>
                                <a href="<?= APP_URL ?>/import/<?= $f['log_id'] ?>/download"
                                   class="btn btn-xs btn-outline-primary py-0 px-2" title="Download">
                                    <i class="bi bi-download"></i>
                                </a>
                                <form method="post" action="<?= APP_URL ?>/import/<?= $f['log_id'] ?>/delete" class="d-inline">
                                    <?= \Core\Session::csrfField() ?>
                                    <button type="button" class="btn btn-xs btn-outline-danger py-0 px-2 confirm-trigger" title="Delete"
                                            data-confirm-title="Delete File"
                                            data-confirm-message="Delete <strong><?= htmlspecialchars($f['original_filename']) ?></strong> and its import record?"
                                            data-confirm-btn="Delete"
                                            data-confirm-class="btn-danger">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                                <?php else: ?>
                                <span class="text-muted small">No record</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php
/* ========================================================
   TAB: OTHER FILES
   ======================================================== */
else:
?>
<div class="row g-3">
    <!-- Other Files Sidebar: Folders + Upload -->
    <div class="col-lg-3">
        <!-- Upload -->
        <?php $hasGoogle = GOOGLE_API_KEY && GOOGLE_CLIENT_ID; ?>
        <?php if ($hasGoogle): ?>
        <script src="https://apis.google.com/js/api.js"></script>
        <script src="https://accounts.google.com/gsi/client"></script>
        <?php endif; ?>

        <div class="card border-0 shadow-sm mb-3">
            <div class="card-header bg-white fw-semibold small">
                <i class="bi bi-upload me-1"></i>Upload File
            </div>
            <div class="card-body">
                <form action="<?= APP_URL ?>/import/storage/files/upload" method="post" enctype="multipart/form-data" id="storageUploadForm">
                    <?= \Core\Session::csrfField() ?>
                    <div class="mb-2">
                        <input type="file" name="other_file" id="storageFileInput" class="form-control form-control-sm" required>
                    </div>
                    <div id="storageFileChosen" class="d-none mb-2">
                        <span class="badge bg-success fw-normal px-2 py-1 w-100 text-start text-truncate d-block">
                            <i class="bi bi-file-earmark-check me-1"></i>
                            <span id="storageFileChosenName"></span>
                        </span>
                    </div>
                    <div class="mb-2">
                        <select name="folder" class="form-select form-select-sm">
                            <option value="">— No folder —</option>
                            <?php foreach ($fileFolders as $f): ?>
                            <option value="<?= htmlspecialchars($f['name']) ?>"
                                <?= $folder === $f['name'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($f['name']) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-sm btn-primary w-100 mb-1">
                        <i class="bi bi-cloud-upload me-1"></i>Upload
                    </button>
                    <?php if ($hasGoogle): ?>
                    <button type="button" id="storageGdriveBtn" class="btn btn-sm btn-outline-secondary w-100">
                        <svg class="me-1" width="14" height="14" viewBox="0 0 48 48" style="vertical-align:text-top;">
                            <path fill="#4285F4" d="M46.145 24.503c0-1.6-.144-3.14-.413-4.617H24v8.736h12.434c-.536 2.892-2.167 5.342-4.617 6.99v5.81h7.476c4.373-4.028 6.852-9.964 6.852-16.919z"/>
                            <path fill="#34A853" d="M24 47c6.29 0 11.565-2.085 15.42-5.641l-7.477-5.811c-2.075 1.392-4.727 2.215-7.943 2.215-6.11 0-11.283-4.127-13.132-9.673H3.12v6.003C6.957 42.918 14.896 47 24 47z"/>
                            <path fill="#FBBC05" d="M10.868 28.09A14.908 14.908 0 0 1 10.1 24c0-1.424.245-2.808.768-4.09v-6.003H3.12A23.98 23.98 0 0 0 0 24c0 3.868.928 7.527 2.573 10.757l7.476-6.003-.181.336z" transform="translate(.547 -.664)"/>
                            <path fill="#EA4335" d="M24 9.555c3.44 0 6.528 1.183 8.96 3.508l6.717-6.718C35.558 2.627 30.282 0 24 0 14.896 0 6.957 4.082 3.12 10.337l7.748 6.003C12.717 13.682 17.89 9.555 24 9.555z"/>
                        </svg>From Google Drive
                    </button>
                    <?php endif; ?>
                </form>

                <?php if ($hasGoogle): ?>
                <script>
                (function () {
                    const btn = document.getElementById('storageGdriveBtn');

                    function loadPicker(token) {
                        gapi.load('picker', () => {
                            const myDriveView = new google.picker.DocsView()
                                .setIncludeFolders(true)
                                .setSelectFolderEnabled(false);
                            const sharedWithMeView = new google.picker.DocsView()
                                .setIncludeFolders(true)
                                .setSelectFolderEnabled(false)
                                .setOwnedByMe(false);
                            new google.picker.PickerBuilder()
                                .addView(myDriveView)
                                .addView(sharedWithMeView)
                                .setOAuthToken(token)
                                .setDeveloperKey(<?= json_encode(GOOGLE_API_KEY) ?>)
                                .setCallback(async (data) => {
                                    if (data.action !== google.picker.Action.PICKED) return;
                                    const f = data.docs[0];
                                    btn.disabled = true;
                                    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Downloading <strong>' + f.name + '</strong>…';
                                    try {
                                        let resp = await fetch(
                                            `https://www.googleapis.com/drive/v3/files/${f.id}?alt=media`,
                                            { headers: { Authorization: `Bearer ${token}` } }
                                        );
                                        // Google Workspace files (Docs, Sheets) can't use alt=media — export as xlsx
                                        if (!resp.ok && f.mimeType && f.mimeType.startsWith('application/vnd.google-apps.')) {
                                            const xlsxMime = 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';
                                            resp = await fetch(
                                                `https://www.googleapis.com/drive/v3/files/${f.id}/export?mimeType=${encodeURIComponent(xlsxMime)}`,
                                                { headers: { Authorization: `Bearer ${token}` } }
                                            );
                                        }
                                        if (!resp.ok) {
                                            const errText = await resp.text();
                                            throw new Error(`HTTP ${resp.status} — ${errText.substring(0, 120)}`);
                                        }
                                        const buffer = await resp.arrayBuffer();
                                        const blob   = new Blob([buffer], { type: f.mimeType || 'application/octet-stream' });
                                        const file   = new File([blob], f.name, { type: blob.type });
                                        const fileInput = document.getElementById('storageFileInput');
                                        const dt = new DataTransfer();
                                        dt.items.add(file);
                                        fileInput.files = dt.files;
                                        document.getElementById('storageFileChosenName').textContent = f.name;
                                        document.getElementById('storageFileChosen').classList.remove('d-none');
                                        fileInput.classList.add('d-none');
                                        document.getElementById('storageUploadForm').submit();
                                    } catch (err) {
                                        alert('Could not download from Google Drive: ' + err.message);
                                    }
                                    btn.disabled = false;
                                    btn.innerHTML = '<svg class="me-1" width="14" height="14" viewBox="0 0 48 48" style="vertical-align:text-top;"><path fill="#4285F4" d="M46.145 24.503c0-1.6-.144-3.14-.413-4.617H24v8.736h12.434c-.536 2.892-2.167 5.342-4.617 6.99v5.81h7.476c4.373-4.028 6.852-9.964 6.852-16.919z"/><path fill="#34A853" d="M24 47c6.29 0 11.565-2.085 15.42-5.641l-7.477-5.811c-2.075 1.392-4.727 2.215-7.943 2.215-6.11 0-11.283-4.127-13.132-9.673H3.12v6.003C6.957 42.918 14.896 47 24 47z"/><path fill="#FBBC05" d="M10.868 28.09A14.908 14.908 0 0 1 10.1 24c0-1.424.245-2.808.768-4.09v-6.003H3.12A23.98 23.98 0 0 0 0 24c0 3.868.928 7.527 2.573 10.757l7.476-6.003-.181.336z" transform="translate(.547 -.664)"/><path fill="#EA4335" d="M24 9.555c3.44 0 6.528 1.183 8.96 3.508l6.717-6.718C35.558 2.627 30.282 0 24 0 14.896 0 6.957 4.082 3.12 10.337l7.748 6.003C12.717 13.682 17.89 9.555 24 9.555z"/></svg>From Google Drive';
                                })
                                .build()
                                .setVisible(true);
                        });
                    }

                    btn.addEventListener('click', () => {
                        if (!window.__storageGoogleTokenClient) {
                            window.__storageGoogleTokenClient = google.accounts.oauth2.initTokenClient({
                                client_id: <?= json_encode(GOOGLE_CLIENT_ID) ?>,
                                scope: 'https://www.googleapis.com/auth/drive.readonly',
                                callback: (resp) => {
                                    window.__storageGoogleToken = resp.access_token;
                                    loadPicker(resp.access_token);
                                },
                            });
                        }
                        window.__storageGoogleToken
                            ? loadPicker(window.__storageGoogleToken)
                            : window.__storageGoogleTokenClient.requestAccessToken();
                    });
                })();
                </script>
                <?php endif; ?>
            </div>
        </div>

        <!-- Folder Management (admin only) -->
        <?php if ($isAdmin): ?>
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white fw-semibold small">
                <i class="bi bi-folder-plus me-1"></i>Folders
            </div>
            <div class="card-body pb-2">
                <form action="<?= APP_URL ?>/import/storage/files/folders/create" method="post"
                      class="d-flex gap-1 mb-2">
                    <?= \Core\Session::csrfField() ?>
                    <input type="text" name="folder_name" class="form-control form-control-sm"
                           placeholder="New folder…" required pattern="[\w\s\-]+">
                    <button type="submit" class="btn btn-sm btn-success text-nowrap">
                        <i class="bi bi-plus-lg"></i>
                    </button>
                </form>
            </div>
            <div class="list-group list-group-flush">
                <a href="<?= APP_URL ?>/import/storage?tab=files"
                   class="list-group-item list-group-item-action d-flex justify-content-between align-items-center
                          <?= $folder === '' ? 'active' : '' ?> small">
                    <span><i class="bi bi-hdd me-1"></i>All files</span>
                </a>
                <?php foreach ($fileFolders as $f): ?>
                <div class="list-group-item d-flex justify-content-between align-items-center small p-1 ps-3">
                    <a href="<?= APP_URL ?>/import/storage?tab=files&folder=<?= urlencode($f['name']) ?>"
                       class="text-decoration-none flex-fill text-<?= $folder === $f['name'] ? 'primary fw-semibold' : 'dark' ?>">
                        <i class="bi bi-folder2 me-1 text-warning"></i>
                        <?= htmlspecialchars($f['name']) ?>
                        <span class="text-muted">(<?= $f['file_count'] ?>)</span>
                    </a>
                    <?php if ($f['file_count'] === 0): ?>
                    <form method="post" action="<?= APP_URL ?>/import/storage/files/folders/delete">
                        <?= \Core\Session::csrfField() ?>
                        <input type="hidden" name="folder_name" value="<?= htmlspecialchars($f['name']) ?>">
                        <button type="button" class="btn btn-xs btn-outline-danger py-0 px-1 confirm-trigger"
                                data-confirm-title="Delete Folder"
                                data-confirm-message="Delete folder <strong><?= htmlspecialchars($f['name']) ?></strong>?"
                                data-confirm-btn="Delete"
                                data-confirm-class="btn-danger">
                            <i class="bi bi-trash"></i>
                        </button>
                    </form>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
                <?php if (empty($fileFolders)): ?>
                <div class="list-group-item text-muted small">No folders yet.</div>
                <?php endif; ?>
            </div>
        </div>
        <?php else: ?>
        <!-- Non-admin folder list -->
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white fw-semibold small"><i class="bi bi-folder me-1"></i>Folders</div>
            <div class="list-group list-group-flush">
                <a href="<?= APP_URL ?>/import/storage?tab=files"
                   class="list-group-item list-group-item-action d-flex justify-content-between align-items-center
                          <?= $folder === '' ? 'active' : '' ?> small">
                    <span><i class="bi bi-hdd me-1"></i>All files</span>
                </a>
                <?php foreach ($fileFolders as $f): ?>
                <a href="<?= APP_URL ?>/import/storage?tab=files&folder=<?= urlencode($f['name']) ?>"
                   class="list-group-item list-group-item-action d-flex justify-content-between align-items-center
                          <?= $folder === $f['name'] ? 'active' : '' ?> small">
                    <span>
                        <i class="bi bi-folder2 me-2 text-warning"></i><?= htmlspecialchars($f['name']) ?>
                    </span>
                    <span class="badge bg-secondary"><?= $f['file_count'] ?></span>
                </a>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <!-- Other Files List -->
    <div class="col-lg-9">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <nav aria-label="breadcrumb" class="mb-0">
                    <ol class="breadcrumb mb-0 small">
                        <li class="breadcrumb-item"><a href="<?= APP_URL ?>/import/storage?tab=files">Other Files</a></li>
                        <?php if ($folder !== ''): ?>
                        <li class="breadcrumb-item active"><?= htmlspecialchars($folder) ?></li>
                        <?php endif; ?>
                    </ol>
                </nav>
                <span class="text-muted small">
                    <?= count($otherFiles) ?> file<?= count($otherFiles) !== 1 ? 's' : '' ?>
                    <?php if (!empty($otherFiles)): ?>
                    &mdash; <?= fmtSize(array_sum(array_column($otherFiles, 'file_size'))) ?>
                    <?php endif; ?>
                </span>
            </div>
            <div class="card-body p-0">
                <?php if (empty($otherFiles)): ?>
                <div class="text-center text-muted py-5">
                    <i class="bi bi-folder2-open fs-1 d-block mb-2 opacity-50"></i>
                    No files <?= $folder !== '' ? "in \"" . htmlspecialchars($folder) . "\"" : 'here' ?>.
                    <div class="small mt-1">Use the upload panel on the left.</div>
                </div>
                <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-sm table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>File Name</th>
                                <th>Size</th>
                                <th>Type</th>
                                <th>Uploaded By</th>
                                <th>Date</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($otherFiles as $f): ?>
                        <?php
                            $mime = $f['mime_type'] ?? '';
                            $icon = str_contains($mime, 'pdf')   ? 'bi-file-earmark-pdf text-danger'
                                  : (str_contains($mime, 'word') || str_contains($mime, 'document') ? 'bi-file-earmark-word text-primary'
                                  : (str_contains($mime, 'sheet') || str_contains($mime, 'excel')   ? 'bi-file-earmark-excel text-success'
                                  : (str_contains($mime, 'image')  ? 'bi-file-earmark-image text-info'
                                  : 'bi-file-earmark text-secondary')));
                        ?>
                        <tr>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <i class="bi <?= $icon ?> fs-5"></i>
                                    <div>
                                        <div class="fw-semibold small"><?= htmlspecialchars($f['original_filename']) ?></div>
                                        <div class="text-muted" style="font-size:.72rem"><?= htmlspecialchars($f['saved_filename']) ?></div>
                                    </div>
                                </div>
                            </td>
                            <td class="small text-nowrap"><?= fmtSize($f['file_size']) ?></td>
                            <td class="small text-muted"><?= htmlspecialchars($mime) ?></td>
                            <td class="small"><?= htmlspecialchars($f['uploaded_by_name'] ?? '—') ?></td>
                            <td class="small text-nowrap">
                                <?= DateHelper::formatDate($f['uploaded_at'], 'M j, Y') ?>
                                <div class="text-muted" style="font-size:.72rem"><?= DateHelper::formatDate($f['uploaded_at'], 'g:i A') ?></div>
                            </td>
                            <td class="text-end text-nowrap">
                                <?php $ext = strtolower(pathinfo($f['original_filename'], PATHINFO_EXTENSION)); ?>
                                <?php if (previewable($mime, $ext)): ?>
                                <button class="btn btn-xs btn-outline-secondary py-0 px-2"
                                        onclick="openPreview('<?= APP_URL ?>/import/storage/files/<?= $f['id'] ?>/view','<?= htmlspecialchars(addslashes($f['original_filename'])) ?>')"
                                        title="View">
                                    <i class="bi bi-eye"></i>
                                </button>
                                <?php endif; ?>
                                <a href="<?= APP_URL ?>/import/storage/files/<?= $f['id'] ?>/download"
                                   class="btn btn-xs btn-outline-primary py-0 px-2" title="Download">
                                    <i class="bi bi-download"></i>
                                </a>
                                <form method="post" action="<?= APP_URL ?>/import/storage/files/<?= $f['id'] ?>/delete" class="d-inline">
                                    <?= \Core\Session::csrfField() ?>
                                    <button type="button" class="btn btn-xs btn-outline-danger py-0 px-2 confirm-trigger" title="Delete"
                                            data-confirm-title="Delete File"
                                            data-confirm-message="Delete <strong><?= htmlspecialchars($f['original_filename']) ?></strong>?"
                                            data-confirm-btn="Delete"
                                            data-confirm-class="btn-danger">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Preview Modal -->
<div class="modal fade" id="previewModal" tabindex="-1">
    <div class="modal-dialog modal-xl modal-dialog-scrollable" style="max-width:90vw">
        <div class="modal-content" style="height:90vh">
            <div class="modal-header py-2">
                <h6 class="modal-title d-flex align-items-center gap-2">
                    <i class="bi bi-eye"></i>
                    <span id="previewFilename" class="text-truncate" style="max-width:60vw"></span>
                </h6>
                <div class="d-flex gap-2 align-items-center ms-auto me-2">
                    <a id="previewDownloadBtn" href="#" class="btn btn-sm btn-outline-primary">
                        <i class="bi bi-download me-1"></i>Download
                    </a>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-0 position-relative">
                <div id="previewSpinner" class="position-absolute top-50 start-50 translate-middle">
                    <div class="spinner-border text-secondary"></div>
                </div>
                <iframe id="previewFrame" src="about:blank"
                        style="width:100%;height:100%;border:0;display:block;"
                        onload="document.getElementById('previewSpinner').style.display='none'">
                </iframe>
            </div>
        </div>
    </div>
</div>

<script>
function openPreview(viewUrl, filename) {
    const modal   = new bootstrap.Modal(document.getElementById('previewModal'));
    const frame   = document.getElementById('previewFrame');
    const spinner = document.getElementById('previewSpinner');

    document.getElementById('previewFilename').textContent = filename;
    document.getElementById('previewDownloadBtn').href = viewUrl.replace('/view', '/download');
    spinner.style.display = '';
    frame.src = viewUrl;
    modal.show();

    document.getElementById('previewModal').addEventListener('hidden.bs.modal', function () {
        frame.src = 'about:blank';
    }, { once: true });
}
</script>
