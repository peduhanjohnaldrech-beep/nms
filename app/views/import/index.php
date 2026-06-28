<?php $pageTitle = 'Import Data'; ?>

<div class="d-flex justify-content-between align-items-center my-3">
    <h4 class="mb-0"><i class="bi bi-file-earmark-excel me-2"></i>Import Data</h4>
    <a href="<?= APP_URL ?>/import/storage" class="btn btn-sm btn-outline-secondary">
        <i class="bi bi-hdd me-1"></i>View Storage
    </a>
</div>

<!-- Top row: Upload + Folders -->
<div class="row g-3 mb-3">

    <!-- Upload -->
    <div class="<?= \Core\Session::get('user_role') === 'admin' ? 'col-lg-8' : 'col-12' ?>">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white fw-semibold d-flex justify-content-between align-items-center">
                <span><i class="bi bi-upload me-2 text-primary"></i>Upload Excel File</span>
                <a href="<?= APP_URL ?>/import/template" class="btn btn-sm btn-success">
                    <i class="bi bi-file-earmark-arrow-down me-1"></i>Download Template
                </a>
            </div>
            <div class="card-body">
                <?php $hasGoogle = GOOGLE_API_KEY && GOOGLE_CLIENT_ID; ?>

                <?php if ($hasGoogle): ?>
                <script src="https://apis.google.com/js/api.js"></script>
                <script src="https://accounts.google.com/gsi/client"></script>
                <?php endif; ?>

                <form action="<?= APP_URL ?>/import/upload" method="post" enctype="multipart/form-data" id="importForm">
                    <?= \Core\Session::csrfField() ?>
                    <input type="hidden" name="import_source" id="importSourceInput" value="Excel">
                    <input type="file" name="excel_file" id="excelFileInput" accept=".xlsx,.xls" required class="d-none">

                    <!-- Drop zone -->
                    <div id="dropZone" class="border border-2 rounded-3 text-center p-4 mb-3"
                         style="border-style:dashed; border-color:#adb5bd; transition:background .2s, border-color .2s;">

                        <i class="bi bi-file-earmark-excel fs-1 text-success d-block mb-2"></i>
                        <p class="fw-semibold mb-1">Drag &amp; drop your Excel file here</p>
                        <p class="text-muted small mb-3">Supports .xlsx and .xls</p>

                        <!-- Source picker button — always show all sources -->
                        <div class="btn-group">
                            <button type="button" class="btn btn-primary px-4" id="fromDeviceBtn">
                                <i class="bi bi-folder2-open me-2"></i>Choose File
                            </button>
                            <button type="button" class="btn btn-primary dropdown-toggle dropdown-toggle-split"
                                    data-bs-toggle="dropdown" aria-expanded="false">
                                <span class="visually-hidden">More sources</span>
                            </button>
                            <ul class="dropdown-menu text-start shadow">
                                <li>
                                    <a class="dropdown-item" href="#" id="fromDeviceItem">
                                        <i class="bi bi-laptop me-2"></i>From Device
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item <?= !$hasGoogle ? 'text-muted' : '' ?>" href="#" id="fromGoogleBtn">
                                        <svg class="me-2" width="16" height="16" viewBox="0 0 48 48" style="vertical-align:text-top;">
                                            <path fill="#4285F4" d="M46.145 24.503c0-1.6-.144-3.14-.413-4.617H24v8.736h12.434c-.536 2.892-2.167 5.342-4.617 6.99v5.81h7.476c4.373-4.028 6.852-9.964 6.852-16.919z"/>
                                            <path fill="#34A853" d="M24 47c6.29 0 11.565-2.085 15.42-5.641l-7.477-5.811c-2.075 1.392-4.727 2.215-7.943 2.215-6.11 0-11.283-4.127-13.132-9.673H3.12v6.003C6.957 42.918 14.896 47 24 47z"/>
                                            <path fill="#FBBC05" d="M10.868 28.09A14.908 14.908 0 0 1 10.1 24c0-1.424.245-2.808.768-4.09v-6.003H3.12A23.98 23.98 0 0 0 0 24c0 3.868.928 7.527 2.573 10.757l7.476-6.003-.181.336z" transform="translate(.547 -.664)"/>
                                            <path fill="#EA4335" d="M24 9.555c3.44 0 6.528 1.183 8.96 3.508l6.717-6.718C35.558 2.627 30.282 0 24 0 14.896 0 6.957 4.082 3.12 10.337l7.748 6.003C12.717 13.682 17.89 9.555 24 9.555z"/>
                                        </svg>From Google Drive
                                        <?php if (!$hasGoogle): ?><small class="text-muted ms-1">(setup required)</small><?php endif; ?>
                                    </a>
                                </li>
                                <li><hr class="dropdown-divider"></li>
                                <li>
                                    <a class="dropdown-item" href="#" id="fromUrlItem">
                                        <i class="bi bi-link-45deg me-2"></i>From URL
                                    </a>
                                </li>
                            </ul>
                        </div>

                        <!-- URL input (shown on demand) -->
                        <div id="urlInputGroup" class="d-none mt-3 mx-auto" style="max-width:420px;">
                            <div class="input-group input-group-sm">
                                <input type="url" id="urlInput" class="form-control"
                                       placeholder="https://example.com/data.xlsx">
                                <button type="button" class="btn btn-outline-secondary" id="fetchUrlBtn">
                                    <i class="bi bi-cloud-download me-1"></i>Fetch
                                </button>
                            </div>
                            <div class="text-muted" style="font-size:.75rem;margin-top:4px;">
                                The URL must point directly to an .xlsx or .xls file.
                            </div>
                        </div>

                        <!-- Loading indicator -->
                        <div id="fileLoading" class="mt-3 d-none text-muted small">
                            <span class="spinner-border spinner-border-sm me-2"></span>Fetching file…
                        </div>

                        <!-- Chosen file badge -->
                        <div id="fileChosen" class="mt-3 d-none">
                            <span class="badge bg-success fs-6 fw-normal px-3 py-2">
                                <i class="bi bi-file-earmark-check me-2"></i>
                                <span id="fileChosenName"></span>
                                <button type="button" id="clearFileBtn"
                                        class="btn-close btn-close-white ms-2"
                                        style="font-size:.6rem;" aria-label="Clear"></button>
                            </span>
                        </div>
                    </div>

                    <details class="mb-3">
                        <summary class="text-muted small" style="cursor:pointer;">
                            <i class="bi bi-info-circle me-1"></i>View required columns (A–V)
                        </summary>
                        <div class="alert alert-info small mt-2 mb-0">
                            Last Name, First Name, Middle Name, Suffix, Date of Birth, Sex, Barangay,
                            Purok/Zone, Household No., InCode, Mother's Name, Father's Name,
                            Contact Number, Income Classification, Monthly Household Income,
                            4Ps Member, NHTS-PR Status, PhilHealth Status, Assessment Date,
                            Weight (kg), Height (cm), MUAC (cm)
                        </div>
                    </details>

                    <button type="submit" class="btn btn-success px-4" id="uploadBtn" disabled>
                        <i class="bi bi-upload me-1"></i>Upload &amp; Preview
                    </button>
                </form>

                <script>
                (function () {
                    const zone       = document.getElementById('dropZone');
                    const input      = document.getElementById('excelFileInput');
                    const chosenBox  = document.getElementById('fileChosen');
                    const chosenName = document.getElementById('fileChosenName');
                    const clearBtn   = document.getElementById('clearFileBtn');
                    const submitBtn  = document.getElementById('uploadBtn');
                    const loading    = document.getElementById('fileLoading');
                    const urlGroup   = document.getElementById('urlInputGroup');
                    const urlInput   = document.getElementById('urlInput');
                    const fetchBtn   = document.getElementById('fetchUrlBtn');

                    // ── helpers ──────────────────────────────────────────
                    function setFile(file) {
                        chosenName.textContent = file.name;
                        chosenBox.classList.remove('d-none');
                        submitBtn.disabled = false;
                        zone.style.borderColor = '#16a34a';
                        zone.style.background  = '#f0fdf4';
                        urlGroup.classList.add('d-none');
                        loading.classList.add('d-none');
                    }

                    function clearFile() {
                        input.value = '';
                        chosenBox.classList.add('d-none');
                        submitBtn.disabled = true;
                        zone.style.borderColor = '#adb5bd';
                        zone.style.background  = '';
                    }

                    function showLoading(on) {
                        loading.classList.toggle('d-none', !on);
                        fetchBtn && (fetchBtn.disabled = on);
                    }

                    async function blobToInput(blob, filename) {
                        const ext = filename.split('.').pop().toLowerCase();
                        if (!['xlsx','xls'].includes(ext)) {
                            alert('Only .xlsx or .xls files are supported.');
                            showLoading(false);
                            return;
                        }
                        const file = new File([blob], filename, { type: blob.type || 'application/octet-stream' });
                        const dt = new DataTransfer();
                        dt.items.add(file);
                        input.files = dt.files;
                        setFile(file);
                    }

                    // ── From Device ──────────────────────────────────────
                    document.getElementById('fromDeviceBtn').addEventListener('click', () => input.click());
                    const fromDeviceItem = document.getElementById('fromDeviceItem');
                    if (fromDeviceItem) fromDeviceItem.addEventListener('click', e => { e.preventDefault(); input.click(); });

                    input.addEventListener('change', () => {
                        if (input.files[0]) setFile(input.files[0]);
                    });

                    // ── From URL ─────────────────────────────────────────
                    const fromUrlItem = document.getElementById('fromUrlItem');
                    if (fromUrlItem) {
                        fromUrlItem.addEventListener('click', e => {
                            e.preventDefault();
                            urlGroup.classList.toggle('d-none');
                            urlInput.focus();
                        });
                    }

                    if (fetchBtn) {
                        fetchBtn.addEventListener('click', async () => {
                            const url = urlInput.value.trim();
                            if (!url) return;
                            showLoading(true);
                            try {
                                const resp = await fetch(url);
                                if (!resp.ok) throw new Error('HTTP ' + resp.status);
                                const blob = await resp.blob();
                                const name = url.split('/').pop().split('?')[0] || 'import.xlsx';
                                await blobToInput(blob, name);
                            } catch (err) {
                                showLoading(false);
                                alert('Could not fetch the file: ' + err.message + '\nMake sure the URL is publicly accessible and allows cross-origin requests.');
                            }
                        });
                    }

                    // ── Drag & drop ──────────────────────────────────────
                    zone.addEventListener('dragover', e => {
                        e.preventDefault();
                        zone.style.background = '#eff6ff';
                        zone.style.borderColor = '#2563eb';
                    });
                    zone.addEventListener('dragleave', () => {
                        zone.style.background = '';
                        zone.style.borderColor = '#adb5bd';
                    });
                    zone.addEventListener('drop', e => {
                        e.preventDefault();
                        zone.style.background = '';
                        zone.style.borderColor = '#adb5bd';
                        const file = e.dataTransfer.files[0];
                        if (!file) return;
                        const ext = file.name.split('.').pop().toLowerCase();
                        if (!['xlsx','xls'].includes(ext)) {
                            alert('Only .xlsx or .xls files are allowed.');
                            return;
                        }
                        const dt = new DataTransfer();
                        dt.items.add(file);
                        input.files = dt.files;
                        setFile(file);
                    });

                    // ── Clear ────────────────────────────────────────────
                    clearBtn.addEventListener('click', clearFile);

                    // ── Google Drive ──────────────────────────────────────
                    document.getElementById('fromGoogleBtn').addEventListener('click', e => {
                        e.preventDefault();
                        <?php if ($hasGoogle): ?>
                        if (!window.__googleTokenClient) {
                            window.__googleTokenClient = google.accounts.oauth2.initTokenClient({
                                client_id: <?= json_encode(GOOGLE_CLIENT_ID) ?>,
                                scope: 'https://www.googleapis.com/auth/drive.readonly',
                                callback: (resp) => {
                                    window.__googleToken = resp.access_token;
                                    loadGooglePicker();
                                },
                            });
                        }
                        window.__googleToken ? loadGooglePicker() : window.__googleTokenClient.requestAccessToken();
                        <?php else: ?>
                        alert('Google Drive is not configured yet.\n\nAdd GOOGLE_API_KEY and GOOGLE_CLIENT_ID to your .env file to enable this option.\nSet up credentials at: https://console.cloud.google.com');
                        <?php endif; ?>
                    });

                    <?php if ($hasGoogle): ?>
                    function loadGooglePicker() {
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
                                .setOAuthToken(window.__googleToken)
                                .setDeveloperKey(<?= json_encode(GOOGLE_API_KEY) ?>)
                                .setCallback(async (data) => {
                                    if (data.action !== google.picker.Action.PICKED) return;
                                    const f        = data.docs[0];
                                    const isGApps  = f.mimeType && f.mimeType.startsWith('application/vnd.google-apps.');
                                    const xlsxMime = 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';
                                    const filename  = isGApps
                                        ? (f.name.endsWith('.xlsx') ? f.name : f.name + '.xlsx')
                                        : f.name;
                                    loading.classList.remove('d-none');
                                    loading.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Downloading <strong>' + filename + '</strong> from Google Drive…';
                                    try {
                                        const url  = isGApps
                                            ? `https://www.googleapis.com/drive/v3/files/${f.id}/export?mimeType=${encodeURIComponent(xlsxMime)}`
                                            : `https://www.googleapis.com/drive/v3/files/${f.id}?alt=media`;
                                        let resp = await fetch(url, { headers: { Authorization: `Bearer ${window.__googleToken}` } });
                                        // If direct download fails, try export as xlsx
                                        if (!resp.ok && !isGApps) {
                                            resp = await fetch(
                                                `https://www.googleapis.com/drive/v3/files/${f.id}/export?mimeType=${encodeURIComponent(xlsxMime)}`,
                                                { headers: { Authorization: `Bearer ${window.__googleToken}` } }
                                            );
                                        }
                                        if (!resp.ok) {
                                            const errText = await resp.text();
                                            throw new Error(`HTTP ${resp.status} — ${errText.substring(0, 120)}`);
                                        }
                                        const buffer = await resp.arrayBuffer();
                                        const blob   = new Blob([buffer], { type: xlsxMime });
                                        document.getElementById('importSourceInput').value = 'Google';
                                        await blobToInput(blob, filename);
                                    } catch (err) {
                                        loading.classList.add('d-none');
                                        alert('Could not download from Google Drive:\n' + err.message);
                                    }
                                })
                                .build()
                                .setVisible(true);
                        });
                    }
                    <?php endif; ?>

                })();
                </script>
            </div>
        </div>
    </div>

    <!-- Folders (admin only) -->
    <?php if (\Core\Session::get('user_role') === 'admin'): ?>
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white fw-semibold">
                <i class="bi bi-folder2-open me-2 text-warning"></i>Import Folders
            </div>
            <div class="card-body d-flex flex-column gap-3">
                <form action="<?= APP_URL ?>/import/folders/create" method="post" class="d-flex gap-2">
                    <?= \Core\Session::csrfField() ?>
                    <input type="text" name="folder_name" class="form-control form-control-sm"
                           placeholder="New folder name…" required
                           pattern="[\w\s\-]+" title="Letters, numbers, spaces, hyphens, underscores only">
                    <button type="submit" class="btn btn-sm btn-success text-nowrap">
                        <i class="bi bi-folder-plus me-1"></i>Create
                    </button>
                </form>

                <?php if (empty($folders)): ?>
                <p class="text-muted small mb-0">No folders yet. Create one above to organise your imports.</p>
                <?php else: ?>
                <ul class="list-group list-group-flush">
                    <?php foreach ($folders as $f): ?>
                    <li class="list-group-item d-flex justify-content-between align-items-center px-0 py-2">
                        <span>
                            <i class="bi bi-folder2 me-2 text-warning"></i>
                            <span class="fw-medium"><?= htmlspecialchars($f['name']) ?></span>
                            <span class="text-muted small ms-1">(<?= $f['file_count'] ?> file<?= $f['file_count'] !== 1 ? 's' : '' ?>)</span>
                        </span>
                        <?php if ($f['file_count'] === 0): ?>
                        <form method="post" action="<?= APP_URL ?>/import/folders/delete">
                            <?= \Core\Session::csrfField() ?>
                            <input type="hidden" name="folder_name" value="<?= htmlspecialchars($f['name']) ?>">
                            <button type="button" class="btn btn-xs btn-outline-danger py-0 px-1 confirm-trigger" title="Delete folder"
                                    data-confirm-title="Delete Folder"
                                    data-confirm-message="Delete folder <strong><?= htmlspecialchars($f['name']) ?></strong>?"
                                    data-confirm-btn="Delete"
                                    data-confirm-class="btn-danger">
                                <i class="bi bi-trash"></i>
                            </button>
                        </form>
                        <?php else: ?>
                        <span class="badge bg-light text-muted border">not empty</span>
                        <?php endif; ?>
                    </li>
                    <?php endforeach; ?>
                </ul>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php endif; ?>

</div>

<!-- Bottom: Import History (full width) -->
<div class="card border-0 shadow-sm">
    <div class="card-header bg-white fw-semibold d-flex justify-content-between align-items-center">
        <span><i class="bi bi-clock-history me-2 text-secondary"></i>Import History</span>
        <span class="text-muted small"><?= count($logs) ?> record<?= count($logs) !== 1 ? 's' : '' ?></span>
    </div>

    <!-- Filters -->
    <div class="card-body border-bottom pb-3">
        <form method="get" action="<?= APP_URL ?>/import" class="row g-2 align-items-end">
            <div class="col-sm-3 col-md-2">
                <label class="form-label small mb-1">Date From</label>
                <input type="date" name="date_from" class="form-control form-control-sm"
                       value="<?= htmlspecialchars($filters['date_from']) ?>">
            </div>
            <div class="col-sm-3 col-md-2">
                <label class="form-label small mb-1">Date To</label>
                <input type="date" name="date_to" class="form-control form-control-sm"
                       value="<?= htmlspecialchars($filters['date_to']) ?>">
            </div>
            <div class="col-sm-3 col-md-2">
                <label class="form-label small mb-1">Folder</label>
                <select name="folder" class="form-select form-select-sm">
                    <option value="">All folders</option>
                    <option value="__none__" <?= $filters['folder'] === '__none__' ? 'selected' : '' ?>>— No folder —</option>
                    <?php foreach ($folders as $f): ?>
                    <option value="<?= htmlspecialchars($f['name']) ?>"
                        <?= $filters['folder'] === $f['name'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($f['name']) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <input type="hidden" name="sort" value="<?= htmlspecialchars($sort) ?>">
            <input type="hidden" name="dir"  value="<?= htmlspecialchars($dir) ?>">
            <div class="col-sm-3 col-md-2 d-flex gap-1">
                <button type="submit" class="btn btn-sm btn-primary flex-fill">
                    <i class="bi bi-funnel me-1"></i>Filter
                </button>
                <a href="<?= APP_URL ?>/import" class="btn btn-sm btn-outline-secondary" title="Clear filters">
                    <i class="bi bi-x-lg"></i>
                </a>
            </div>
        </form>
    </div>

    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-sm table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <?php
                        $cols = [
                            'import_date'      => 'Date',
                            'imported_by_name' => 'Imported By',
                            'folder'           => 'Folder',
                            'total_rows'       => 'Total',
                            'success_count'    => 'Success',
                            'error_count'      => 'Errors',
                        ];
                        foreach ($cols as $col => $label):
                            $nextDir = ($sort === $col && $dir === 'ASC') ? 'DESC' : 'ASC';
                            $icon    = $sort === $col
                                ? ($dir === 'ASC' ? 'bi-sort-up' : 'bi-sort-down')
                                : 'bi-arrow-down-up text-muted opacity-50';
                            $qs = http_build_query(array_merge($filters, ['sort' => $col, 'dir' => $nextDir]));
                        ?>
                        <th>
                            <a href="<?= APP_URL ?>/import?<?= $qs ?>"
                               class="text-decoration-none text-dark d-flex align-items-center gap-1">
                                <?= $label ?><i class="bi <?= $icon ?> small"></i>
                            </a>
                        </th>
                        <?php endforeach; ?>
                        <th>File</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($logs)): ?>
                    <tr><td colspan="8" class="text-center text-muted py-4">No imports found.</td></tr>
                    <?php endif; ?>
                    <?php foreach ($logs as $l): ?>
                    <tr>
                        <td class="text-nowrap"><?= DateHelper::formatDate($l['import_date'], 'M j, Y g:i A') ?></td>
                        <td><?= htmlspecialchars($l['imported_by_name'] ?? '—') ?></td>
                        <td>
                            <?php if (!empty($l['folder'])): ?>
                            <span class="badge bg-light text-dark border">
                                <i class="bi bi-folder2 text-warning me-1"></i><?= htmlspecialchars($l['folder']) ?>
                            </span>
                            <?php else: ?>
                            <span class="text-muted small">—</span>
                            <?php endif; ?>
                        </td>
                        <td><?= $l['total_rows'] ?></td>
                        <td><span class="badge bg-success"><?= $l['success_count'] ?></span></td>
                        <td><span class="badge bg-<?= $l['error_count'] > 0 ? 'danger' : 'secondary' ?>"><?= $l['error_count'] ?></span></td>
                        <td>
                            <?php if (!empty($l['saved_filename'])): ?>
                            <a href="<?= APP_URL ?>/import/<?= $l['id'] ?>/download"
                               class="btn btn-xs btn-outline-primary py-0 px-1 small"
                               title="<?= htmlspecialchars($l['filename'] ?? $l['saved_filename']) ?>">
                                <i class="bi bi-download me-1"></i><?= htmlspecialchars(
                                    mb_strimwidth($l['filename'] ?? $l['saved_filename'], 0, 20, '…')
                                ) ?>
                            </a>
                            <?php else: ?>
                            <span class="text-muted small">—</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <form method="post" action="<?= APP_URL ?>/import/<?= $l['id'] ?>/delete">
                                <?= \Core\Session::csrfField() ?>
                                <button type="button" class="btn btn-xs btn-outline-danger py-0 px-1 confirm-trigger"
                                        title="Delete record<?= !empty($l['saved_filename']) ? ' and file' : '' ?>"
                                        data-confirm-title="Delete Import Record"
                                        data-confirm-message="Delete this import record<?= !empty($l['saved_filename']) ? ' and its saved file' : '' ?>?"
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
    </div>
</div>
