<?php
$pageTitle = 'Add User';
$modules = [
    'beneficiaries'  => ['label' => 'Beneficiaries',          'icon' => 'bi-people-fill'],
    'assessments'    => ['label' => 'Assessments',             'icon' => 'bi-clipboard2-pulse'],
    'programs'       => ['label' => 'Programs (OPT/DSP/MNS)',  'icon' => 'bi-grid'],
    'reports'        => ['label' => 'Reports',                 'icon' => 'bi-bar-chart-fill'],
    'dispensing'     => ['label' => 'Dispensing Tracker',      'icon' => 'bi-prescription2'],
    'import'         => ['label' => 'Import',                  'icon' => 'bi-file-earmark-excel'],
    'activity_log'   => ['label' => 'Activity Log',            'icon' => 'bi-journal-text'],
    'programs_admin' => ['label' => 'Program Manager',         'icon' => 'bi-gear'],
];
$selected = array_keys(array_filter($_POST['permissions'] ?? [], fn($v) => $v == '1'));
?>

<div class="d-flex align-items-center my-3">
    <a href="<?= APP_URL ?>/users" class="btn btn-sm btn-outline-secondary me-3"><i class="bi bi-arrow-left"></i></a>
    <h4 class="mb-0"><i class="bi bi-person-plus me-2"></i>Add User</h4>
</div>

<div class="row justify-content-center">
    <div class="col-lg-7">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <form action="<?= APP_URL ?>/users/create" method="post">
                    <?= \Core\Session::csrfField() ?>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Username <span class="text-danger">*</span></label>
                            <input type="text" name="username" class="form-control" required
                                   value="<?= htmlspecialchars($data['username'] ?? '') ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Full Name <span class="text-danger">*</span></label>
                            <input type="text" name="full_name" class="form-control" required
                                   value="<?= htmlspecialchars($data['full_name'] ?? '') ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Password <span class="text-danger">*</span></label>
                            <input type="password" name="password" class="form-control" required minlength="8"
                                   placeholder="Minimum 8 characters">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Role / Position <span class="text-danger">*</span></label>
                            <input type="text" name="role" class="form-control" required
                                   placeholder="e.g. Nutritionist, BHW, Encoder"
                                   value="<?= htmlspecialchars($data['role'] ?? '') ?>">
                            <div class="form-text">Custom label shown on their profile.</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Barangay Assignment</label>
                            <input type="text" name="barangay" class="form-control"
                                   value="<?= htmlspecialchars($data['barangay'] ?? '') ?>"
                                   placeholder="Optional">
                        </div>
                    </div>

                    <hr>
                    <div class="mb-3">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <label class="form-label fw-semibold mb-0">
                                <i class="bi bi-shield-check me-1 text-primary"></i>Access Permissions
                            </label>
                            <div>
                                <button type="button" class="btn btn-sm btn-outline-secondary me-1" id="btnSelectAll">Select All</button>
                                <button type="button" class="btn btn-sm btn-outline-secondary" id="btnClearAll">Clear All</button>
                            </div>
                        </div>
                        <div class="form-text mb-2">Choose what this user can access.</div>
                        <div class="row g-2">
                            <?php foreach ($modules as $key => $mod): ?>
                            <div class="col-md-6">
                                <div class="form-check border rounded p-2 ps-4 perm-item">
                                    <input class="form-check-input perm-check" type="checkbox"
                                           name="permissions[<?= $key ?>]" value="1"
                                           id="perm_<?= $key ?>"
                                           <?= in_array($key, $selected) ? 'checked' : '' ?>>
                                    <label class="form-check-label w-100" for="perm_<?= $key ?>">
                                        <i class="bi <?= $mod['icon'] ?> me-1 text-primary"></i>
                                        <?= $mod['label'] ?>
                                    </label>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <div class="d-flex gap-2 mt-3">
                        <button type="submit" class="btn btn-primary px-4"><i class="bi bi-save me-1"></i>Create User</button>
                        <a href="<?= APP_URL ?>/users" class="btn btn-outline-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
document.getElementById('btnSelectAll').addEventListener('click', function() {
    document.querySelectorAll('.perm-check').forEach(cb => { cb.checked = true; cb.closest('.perm-item').classList.add('bg-primary','bg-opacity-10','border-primary'); });
});
document.getElementById('btnClearAll').addEventListener('click', function() {
    document.querySelectorAll('.perm-check').forEach(cb => { cb.checked = false; cb.closest('.perm-item').classList.remove('bg-primary','bg-opacity-10','border-primary'); });
});
document.querySelectorAll('.perm-check').forEach(cb => {
    if (cb.checked) cb.closest('.perm-item').classList.add('bg-primary','bg-opacity-10','border-primary');
    cb.addEventListener('change', function() {
        this.closest('.perm-item').classList.toggle('bg-primary', this.checked);
        this.closest('.perm-item').classList.toggle('bg-opacity-10', this.checked);
        this.closest('.perm-item').classList.toggle('border-primary', this.checked);
    });
});
</script>
