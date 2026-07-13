<?php
$pageTitle = htmlspecialchars($program['name']);
$role      = \Core\Session::get('user_role');
$canEdit   = hasPerm('programs');
$canManage = in_array($role, ['admin','nutritionist']);
$color     = htmlspecialchars($program['color']);
$baseUrl   = APP_URL . '/programs/' . strtolower($program['code']);
?>

<div class="d-flex justify-content-between align-items-center my-3">
    <div>
        <h4 class="mb-0">
            <i class="bi <?= htmlspecialchars($program['icon']) ?> me-2 text-<?= $color ?>"></i>
            <?= htmlspecialchars($program['name']) ?>
            <span class="badge bg-<?= $color ?> ms-2 fs-6"><?= htmlspecialchars($program['code']) ?></span>
        </h4>
        <?php if (!empty($program['description'])): ?>
        <p class="text-muted small mb-0 mt-1"><?= htmlspecialchars($program['description']) ?></p>
        <?php endif; ?>
    </div>
    <a href="<?= $baseUrl ?>/export?year=<?= $year ?>&barangay=<?= urlencode($barangay) ?>"
       class="btn btn-sm btn-outline-success">
        <i class="bi bi-file-earmark-excel me-1"></i>Export Excel
    </a>
</div>

<!-- Summary Cards -->
<div class="row g-3 mb-3">
    <div class="col-sm-4">
        <div class="card border-0 shadow-sm text-center py-3">
            <div class="fs-2 fw-bold text-<?= $color ?>"><?= $stats['Active'] ?></div>
            <div class="small text-muted">Total Active</div>
        </div>
    </div>
    <div class="col-sm-4">
        <div class="card border-0 shadow-sm text-center py-3">
            <div class="fs-2 fw-bold text-success"><?= $stats['Completed'] ?></div>
            <div class="small text-muted">Completed (All Time)</div>
        </div>
    </div>
    <div class="col-sm-4">
        <div class="card border-0 shadow-sm text-center py-3">
            <div class="fs-2 fw-bold text-danger"><?= $stats['Dropped'] ?></div>
            <div class="small text-muted">Dropped (All Time)</div>
        </div>
    </div>
</div>

<!-- Filter Bar -->
<div class="card border-0 shadow-sm mb-3">
    <div class="card-body py-2">
        <form method="get" class="row g-2 align-items-end">
            <div class="col-md-2">
                <label class="form-label small mb-1">Year</label>
                <input type="number" name="year" class="form-control form-control-sm"
                       value="<?= $year ?>" min="2000" max="<?= date('Y') + 1 ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label small mb-1">Barangay</label>
                <select name="barangay" class="form-select form-select-sm">
                    <option value="">All Barangays</option>
                    <?php foreach ($barangays as $b): ?>
                    <option value="<?= htmlspecialchars($b['barangay']) ?>"
                        <?= $barangay === $b['barangay'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($b['barangay']) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-auto d-flex gap-1">
                <button type="submit" class="btn btn-sm btn-primary">
                    <i class="bi bi-funnel me-1"></i>Filter
                </button>
                <a href="<?= $baseUrl ?>" class="btn btn-sm btn-outline-secondary" title="Clear">
                    <i class="bi bi-x-lg"></i>
                </a>
            </div>
        </form>
    </div>
</div>

<div class="row g-3">
    <!-- Active Enrollments -->
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <span class="fw-semibold">
                    <i class="bi bi-person-check me-2"></i>Active Enrollments
                    <span class="text-muted fw-normal small ms-1">(<?= $year ?><?= $barangay ? ', ' . htmlspecialchars($barangay) : '' ?>)</span>
                </span>
                <span class="badge bg-<?= $color ?>"><?= count($active) ?></span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-sm table-hover mb-0 align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Name</th><th>Barangay</th><th>Age</th><th>Enrolled</th><th>Notes</th>
                                <?php if ($canManage): ?><th></th><?php endif; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($active)): ?>
                            <tr><td colspan="6" class="text-center text-muted py-4">
                                <i class="bi bi-inbox fs-3 d-block mb-1"></i>
                                No active enrollments for <?= $year ?><?= $barangay ? ' in ' . htmlspecialchars($barangay) : '' ?>.
                            </td></tr>
                            <?php endif; ?>
                            <?php foreach ($active as $e): ?>
                            <tr>
                                <td class="fw-semibold">
                                    <a href="<?= APP_URL ?>/beneficiaries/<?= $e['beneficiary_id'] ?>" class="text-decoration-none">
                                        <?= htmlspecialchars($e['last_name'] . ', ' . $e['first_name']) ?>
                                    </a>
                                </td>
                                <td><?= htmlspecialchars($e['barangay']) ?></td>
                                <td class="small text-muted"><?= \DateHelper::formatAge(\DateHelper::ageInMonths($e['date_of_birth'])) ?></td>
                                <td class="small"><?= \DateHelper::formatDate($e['enrollment_date'], 'M j, Y') ?></td>
                                <td class="text-muted small" style="max-width:180px; white-space:normal;">
                                    <?= htmlspecialchars($e['notes'] ?? '') ?>
                                </td>
                                <?php if ($canManage): ?>
                                <td class="text-nowrap">
                                    <button type="button" class="btn btn-xs btn-outline-secondary py-0 px-1"
                                            data-bs-toggle="modal" data-bs-target="#editModal"
                                            data-id="<?= $e['id'] ?>"
                                            data-name="<?= htmlspecialchars($e['last_name'] . ', ' . $e['first_name']) ?>"
                                            data-year="<?= $e['cycle_year'] ?>"
                                            data-notes="<?= htmlspecialchars($e['notes'] ?? '') ?>"
                                            title="Edit">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    <form method="post" action="<?= $baseUrl ?>/discharge" class="d-inline">
                                        <?= \Core\Session::csrfField() ?>
                                        <input type="hidden" name="enrollment_id" value="<?= $e['id'] ?>">
                                        <button type="button"
                                                class="btn btn-xs btn-outline-success py-0 px-1 confirm-trigger"
                                                data-confirm-title="Complete Enrollment"
                                                data-confirm-message="Mark this enrollment as completed?"
                                                data-confirm-btn="Complete"
                                                data-confirm-class="btn-success"
                                                data-action-name="action"
                                                data-action-value="complete"
                                                title="Complete">
                                            <i class="bi bi-check2"></i>
                                        </button>
                                        <button type="button"
                                                class="btn btn-xs btn-outline-danger py-0 px-1 confirm-trigger"
                                                data-confirm-title="Drop Enrollment"
                                                data-confirm-message="Drop this enrollment? The beneficiary will be marked as dropped."
                                                data-confirm-btn="Drop"
                                                data-confirm-class="btn-danger"
                                                data-action-name="action"
                                                data-action-value="drop"
                                                title="Drop">
                                            <i class="bi bi-x"></i>
                                        </button>
                                    </form>
                                </td>
                                <?php endif; ?>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- History -->
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white fw-semibold d-flex justify-content-between align-items-center"
                 style="cursor:pointer;" data-bs-toggle="collapse" data-bs-target="#historyTable">
                <span>
                    <i class="bi bi-clock-history me-2 text-muted"></i>
                    Enrollment History — <?= $year ?>
                    <span class="badge bg-secondary ms-1"><?= count($history) ?></span>
                </span>
                <i class="bi bi-chevron-down small"></i>
            </div>
            <div class="collapse" id="historyTable">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-sm mb-0 align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>Name</th><th>Barangay</th><th>Enrolled</th><th>Ended</th><th>Status</th><th>Notes</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($history)): ?>
                                <tr><td colspan="6" class="text-center text-muted py-3">No completed or dropped enrollments for <?= $year ?>.</td></tr>
                                <?php endif; ?>
                                <?php foreach ($history as $h): ?>
                                <tr>
                                    <td>
                                        <a href="<?= APP_URL ?>/beneficiaries/<?= $h['beneficiary_id'] ?>" class="text-decoration-none">
                                            <?= htmlspecialchars($h['last_name'] . ', ' . $h['first_name']) ?>
                                        </a>
                                    </td>
                                    <td><?= htmlspecialchars($h['barangay']) ?></td>
                                    <td class="small"><?= \DateHelper::formatDate($h['enrollment_date'], 'M j, Y') ?></td>
                                    <td class="small"><?= $h['end_date'] ? \DateHelper::formatDate($h['end_date'], 'M j, Y') : '—' ?></td>
                                    <td>
                                        <span class="badge bg-<?= $h['status'] === 'Completed' ? 'success' : 'danger' ?>">
                                            <?= htmlspecialchars($h['status']) ?>
                                        </span>
                                    </td>
                                    <td class="text-muted small"><?= htmlspecialchars($h['notes'] ?? '') ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Enroll Form -->
    <?php if ($canEdit): ?>
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white fw-semibold">
                <i class="bi bi-person-plus me-2"></i>Enroll Beneficiary
            </div>
            <div class="card-body">
                <form method="post" action="<?= $baseUrl ?>/enroll">
                    <?= \Core\Session::csrfField() ?>
                    <div class="mb-2">
                        <label class="form-label small fw-semibold">Beneficiary <span class="text-danger">*</span></label>
                        <select name="beneficiary_id" class="form-select form-select-sm" required>
                            <option value="">— Select —</option>
                            <?php foreach ($notEnrolled as $b): ?>
                            <option value="<?= $b['id'] ?>">
                                <?= htmlspecialchars($b['last_name'] . ', ' . $b['first_name']) ?>
                                (<?= htmlspecialchars($b['barangay']) ?>)
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-2">
                        <label class="form-label small fw-semibold">Year</label>
                        <input type="number" name="cycle_year" class="form-control form-control-sm" value="<?= $year ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Notes</label>
                        <textarea name="notes" class="form-control form-control-sm" rows="3"
                                  placeholder="Any relevant details for this enrollment…"></textarea>
                    </div>
                    <button type="submit" class="btn btn-sm btn-<?= $color ?> w-100">
                        <i class="bi bi-person-plus me-1"></i>Enroll
                    </button>
                </form>
            </div>
        </div>

        <!-- Quick Links -->
        <div class="card border-0 shadow-sm mt-3">
            <div class="card-header bg-white fw-semibold">
                <i class="bi bi-lightning me-2 text-warning"></i>Quick Links
            </div>
            <div class="list-group list-group-flush">
                <a href="<?= APP_URL ?>/dispensing/create" class="list-group-item list-group-item-action small">
                    <i class="bi bi-prescription2 me-2 text-primary"></i>Record Dispensing
                </a>
                <a href="<?= APP_URL ?>/dispensing?program=<?= urlencode($program['code']) ?>" class="list-group-item list-group-item-action small">
                    <i class="bi bi-list-ul me-2 text-secondary"></i>View Dispensing Records
                </a>
                <a href="<?= APP_URL ?>/beneficiaries" class="list-group-item list-group-item-action small">
                    <i class="bi bi-people me-2 text-success"></i>Beneficiaries List
                </a>
                <a href="<?= $baseUrl ?>/export?year=<?= $year ?>&barangay=<?= urlencode($barangay) ?>" class="list-group-item list-group-item-action small">
                    <i class="bi bi-file-earmark-excel me-2 text-success"></i>Export Enrollments (<?= $year ?>)
                </a>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<!-- Edit Enrollment Modal -->
<?php if ($canManage): ?>
<div class="modal fade" id="editModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="post" action="<?= $baseUrl ?>/update">
                <?= \Core\Session::csrfField() ?>
                <input type="hidden" name="enrollment_id" id="editEnrollmentId">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-pencil me-2"></i>Edit Enrollment</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="mb-3">Editing: <strong id="editName"></strong></p>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Year</label>
                        <input type="number" name="cycle_year" id="editYear" class="form-control"
                               min="2000" max="<?= date('Y') + 1 ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Notes</label>
                        <textarea name="notes" id="editNotes" class="form-control" rows="4"
                                  placeholder="Any relevant details…"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-save me-1"></i>Save</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.getElementById('editModal').addEventListener('show.bs.modal', function(e) {
    const btn = e.relatedTarget;
    document.getElementById('editEnrollmentId').value = btn.dataset.id;
    document.getElementById('editName').textContent    = btn.dataset.name;
    document.getElementById('editYear').value          = btn.dataset.year;
    document.getElementById('editNotes').value         = btn.dataset.notes;
});
</script>
<?php endif; ?>
