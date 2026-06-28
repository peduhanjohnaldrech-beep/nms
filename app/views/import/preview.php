<?php $pageTitle = 'Import Preview'; ?>

<?php
$newCount    = count(array_filter($rows, fn($r) => $r['status'] === 'new'));
$updateCount = count(array_filter($rows, fn($r) => $r['status'] === 'update'));
$errorCount  = count(array_filter($rows, fn($r) => $r['status'] === 'error'));
?>

<div class="d-flex align-items-center my-3">
    <a href="<?= APP_URL ?>/import" class="btn btn-sm btn-outline-secondary me-3"><i class="bi bi-arrow-left"></i></a>
    <h4 class="mb-0"><i class="bi bi-eye me-2"></i>Import Preview</h4>
</div>

<div class="alert alert-info mb-3">
    <i class="bi bi-file-earmark-excel me-1"></i>
    Previewing: <strong><?= htmlspecialchars($filename) ?></strong>
    &mdash; <?= count($rows) ?> rows found.
</div>

<?php if ($updateCount > 0): ?>
<!-- Duplicate handling option -->
<div class="card border-warning border-2 shadow-sm mb-3">
    <div class="card-header bg-warning bg-opacity-10 fw-semibold text-warning-emphasis">
        <i class="bi bi-exclamation-triangle-fill me-2"></i>
        <?= $updateCount ?> duplicate<?= $updateCount !== 1 ? 's' : '' ?> found — choose how to handle them
    </div>
    <div class="card-body pb-2">
        <div class="row g-3">
            <div class="col-md-6">
                <div class="form-check border rounded p-3 h-100 duplicate-option" id="optionUpdateCard" style="cursor:pointer;">
                    <input class="form-check-input" type="radio" name="duplicate_action_ui"
                           id="dupeUpdate" value="update" checked>
                    <label class="form-check-label w-100" for="dupeUpdate" style="cursor:pointer;">
                        <span class="fw-semibold d-block mb-1">
                            <i class="bi bi-arrow-repeat text-warning me-1"></i>Update existing records
                        </span>
                        <span class="text-muted small">
                            Overwrite the matching child's profile with the new data from the file.
                            The assessment will still be added.
                        </span>
                    </label>
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-check border rounded p-3 h-100 duplicate-option" id="optionSkipCard" style="cursor:pointer;">
                    <input class="form-check-input" type="radio" name="duplicate_action_ui"
                           id="dupeSkip" value="skip">
                    <label class="form-check-label w-100" for="dupeSkip" style="cursor:pointer;">
                        <span class="fw-semibold d-block mb-1">
                            <i class="bi bi-skip-forward-fill text-secondary me-1"></i>Skip duplicates
                        </span>
                        <span class="text-muted small">
                            Leave existing records unchanged. Only new children will be imported.
                        </span>
                    </label>
                </div>
            </div>
        </div>
        <p class="text-muted small mt-3 mb-0">
            <i class="bi bi-info-circle me-1"></i>
            You can also <strong>skip individual rows</strong> using the checkboxes in the table below.
        </p>
    </div>
</div>
<?php endif; ?>

<!-- Preview table -->
<div class="card border-0 shadow-sm mb-3">
    <div class="card-body p-0">
        <div class="table-responsive" style="max-height:460px; overflow-y:auto;">
            <table class="table table-sm mb-0 align-middle">
                <thead class="table-light sticky-top">
                    <tr>
                        <th style="width:40px;">#</th>
                        <th>Status</th>
                        <th>Name</th>
                        <th>DOB</th>
                        <th>Sex</th>
                        <th>Barangay</th>
                        <th>Weight</th>
                        <th>Error</th>
                        <th style="width:80px;" class="text-center">Skip</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($rows as $r): ?>
                    <?php $data = $r['data']; $isUpdate = $r['status'] === 'update'; $isError = $r['status'] === 'error'; ?>
                    <tr class="<?= $isError ? 'table-danger' : ($isUpdate ? 'table-warning' : '') ?>"
                        id="row-<?= $r['rowNum'] ?>">
                        <td><?= $r['rowNum'] ?></td>
                        <td>
                            <?php if ($r['status'] === 'new'): ?>
                            <span class="badge bg-success">New</span>
                            <?php elseif ($isUpdate): ?>
                            <span class="badge bg-warning text-dark">Duplicate</span>
                            <?php else: ?>
                            <span class="badge bg-danger">Error</span>
                            <?php endif; ?>
                        </td>
                        <td><?= htmlspecialchars($data['last_name'] . ', ' . $data['first_name']) ?></td>
                        <td><?= htmlspecialchars($data['date_of_birth']) ?></td>
                        <td><?= htmlspecialchars($data['sex']) ?></td>
                        <td><?= htmlspecialchars($data['barangay']) ?></td>
                        <td><?= $data['weight_kg'] ?? '—' ?></td>
                        <td><?= $isError ? '<span class="text-danger small">' . htmlspecialchars($r['error']) . '</span>' : '' ?></td>
                        <td class="text-center">
                            <?php if (!$isError): ?>
                            <input type="checkbox"
                                   class="form-check-input skip-row-check <?= $isUpdate ? 'is-duplicate' : '' ?>"
                                   data-row="<?= $r['rowNum'] ?>"
                                   title="Skip this row"
                                   <?= $isError ? 'disabled' : '' ?>>
                            <?php else: ?>
                            <span class="text-muted small">—</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Confirm form -->
<?php if ($newCount + $updateCount > 0): ?>
<form action="<?= APP_URL ?>/import/confirm" method="post" id="confirmForm">
    <?= \Core\Session::csrfField() ?>

    <!-- Hidden fields populated by JS -->
    <input type="hidden" name="duplicate_action" id="duplicateActionInput" value="update">
    <input type="hidden" name="skip_rows"        id="skipRowsInput"        value="">

    <div class="d-flex flex-wrap gap-2 align-items-center">
        <div class="me-auto d-flex gap-2 align-items-center flex-wrap">
            <span class="badge bg-success fs-6" id="summaryNew"><?= $newCount ?> New</span>
            <span class="badge bg-warning text-dark fs-6" id="summaryUpdate"><?= $updateCount ?> will update</span>
            <span class="badge bg-secondary fs-6" id="summarySkip" style="display:none!important;">0 skipped</span>
            <?php if ($errorCount): ?>
            <span class="badge bg-danger fs-6"><?= $errorCount ?> Error<?= $errorCount !== 1 ? 's' : '' ?></span>
            <?php endif; ?>
        </div>

        <select name="folder" class="form-select form-select-sm" style="max-width:200px" title="Save file into folder">
            <option value="">— No folder —</option>
            <?php foreach ($folders as $f): ?>
            <option value="<?= htmlspecialchars($f['name']) ?>"><?= htmlspecialchars($f['name']) ?></option>
            <?php endforeach; ?>
        </select>

        <a href="<?= APP_URL ?>/import" class="btn btn-outline-secondary">Cancel</a>

        <button type="submit" class="btn btn-primary" id="confirmBtn">
            <i class="bi bi-check-circle me-1"></i>
            Confirm Import (<span id="confirmCount"><?= $newCount + $updateCount ?></span> records)
        </button>
    </div>
</form>
<?php else: ?>
<div class="d-flex gap-2 align-items-center">
    <span class="badge bg-danger fs-6"><?= $errorCount ?> Error<?= $errorCount !== 1 ? 's' : '' ?> — nothing to import</span>
    <a href="<?= APP_URL ?>/import" class="btn btn-outline-secondary ms-auto">Back</a>
</div>
<?php endif; ?>

<script>
(function () {
    const dupeUpdate    = document.getElementById('dupeUpdate');
    const dupeSkip      = document.getElementById('dupeSkip');
    const actionInput   = document.getElementById('duplicateActionInput');
    const skipInput     = document.getElementById('skipRowsInput');
    const confirmCount  = document.getElementById('confirmCount');
    const summaryUpdate = document.getElementById('summaryUpdate');
    const summarySkip   = document.getElementById('summarySkip');

    const totalNew    = <?= $newCount ?>;
    const totalUpdate = <?= $updateCount ?>;

    function getManuallySkipped() {
        return [...document.querySelectorAll('.skip-row-check:checked')].map(c => c.dataset.row);
    }

    function refresh() {
        const action = dupeSkip && dupeSkip.checked ? 'skip' : 'update';
        actionInput.value = action;

        // Sync duplicate checkboxes to global setting
        document.querySelectorAll('.skip-row-check.is-duplicate').forEach(cb => {
            if (!cb._manuallySet) cb.checked = (action === 'skip');
        });

        const manualSkips = getManuallySkipped();
        skipInput.value = manualSkips.join(',');

        // Count how many duplicates are being skipped
        const dupesSkipped = [...document.querySelectorAll('.skip-row-check.is-duplicate:checked')].length;
        const nonDupesSkipped = [...document.querySelectorAll('.skip-row-check:not(.is-duplicate):checked')].length;
        const totalSkipped = dupesSkipped + nonDupesSkipped;
        const totalImport  = (totalNew - nonDupesSkipped) + (totalUpdate - dupesSkipped);

        confirmCount.textContent = Math.max(0, totalImport);

        if (summaryUpdate) {
            summaryUpdate.textContent = (totalUpdate - dupesSkipped) + ' will update';
            summaryUpdate.style.display = (totalUpdate - dupesSkipped) > 0 ? '' : 'none';
        }
        if (summarySkip) {
            summarySkip.textContent = totalSkipped + ' skipped';
            summarySkip.style.setProperty('display', totalSkipped > 0 ? 'inline-block' : 'none', 'important');
        }

        // Highlight skipped rows
        document.querySelectorAll('.skip-row-check').forEach(cb => {
            const row = document.getElementById('row-' + cb.dataset.row);
            if (row) row.style.opacity = cb.checked ? '0.4' : '';
        });
    }

    // Global toggle
    [dupeUpdate, dupeSkip].forEach(el => el && el.addEventListener('change', () => {
        // Reset manual flags on global change
        document.querySelectorAll('.skip-row-check.is-duplicate').forEach(cb => cb._manuallySet = false);
        refresh();
    }));

    // Per-row manual override
    document.querySelectorAll('.skip-row-check').forEach(cb => {
        cb.addEventListener('change', () => {
            cb._manuallySet = true;
            refresh();
        });
    });

    // Highlight selected option card
    [dupeUpdate, dupeSkip].forEach(el => {
        if (!el) return;
        const card = el.closest('.duplicate-option');
        el.addEventListener('change', () => {
            document.querySelectorAll('.duplicate-option').forEach(c => c.classList.remove('border-primary'));
            if (el.checked) card && card.classList.add('border-primary');
        });
    });
    // Init highlight
    const checkedOption = document.querySelector('input[name="duplicate_action_ui"]:checked');
    if (checkedOption) {
        const card = checkedOption.closest('.duplicate-option');
        if (card) card.classList.add('border-primary');
    }

    refresh();
})();
</script>
