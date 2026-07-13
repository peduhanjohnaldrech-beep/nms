<?php $pageTitle = 'Add Beneficiary'; ?>
<?php
$d        = $data ?? [];
$loc      = $locationDefaults ?? [];
$locked   = $lockedBarangay ?? false;
$userBrgy = \Core\Session::get('user_barangay', '');
?>

<div class="d-flex align-items-center my-3">
    <a href="<?= APP_URL ?>/beneficiaries" class="btn btn-sm btn-outline-secondary me-3">
        <i class="bi bi-arrow-left"></i>
    </a>
    <h4 class="mb-0"><i class="bi bi-person-plus me-2"></i>Add Beneficiary</h4>
</div>

<div id="duplicateAlert" class="alert alert-warning d-none mb-3">
    <i class="bi bi-exclamation-triangle-fill me-2"></i>
    A beneficiary with this name and date of birth may already exist. Please verify before saving.
</div>

<form action="<?= APP_URL ?>/beneficiaries/create" method="post" enctype="multipart/form-data">
    <?= \Core\Session::csrfField() ?>

    <!-- ── REQUIRED FIELDS ── -->
    <div class="card border-0 shadow-sm mb-3">
        <div class="card-header bg-primary text-white fw-semibold">
            <i class="bi bi-person-badge me-2"></i>Child Information
        </div>
        <div class="card-body">
            <div class="row g-3">
                <!-- Name -->
                <div class="col-md-4">
                    <label class="form-label">Last Name <span class="text-danger">*</span></label>
                    <input type="text" name="last_name" class="form-control" required
                           value="<?= htmlspecialchars($d['last_name'] ?? '') ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">First Name <span class="text-danger">*</span></label>
                    <input type="text" name="first_name" class="form-control" required
                           value="<?= htmlspecialchars($d['first_name'] ?? '') ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Middle Name</label>
                    <input type="text" name="middle_name" class="form-control"
                           value="<?= htmlspecialchars($d['middle_name'] ?? '') ?>">
                </div>
                <div class="col-md-1">
                    <label class="form-label">Suffix</label>
                    <input type="text" name="suffix" class="form-control"
                           value="<?= htmlspecialchars($d['suffix'] ?? '') ?>">
                </div>

                <!-- DOB + Sex -->
                <div class="col-md-3">
                    <label class="form-label">Date of Birth <span class="text-danger">*</span></label>
                    <input type="date" name="date_of_birth" class="form-control" required
                           max="<?= date('Y-m-d') ?>"
                           value="<?= htmlspecialchars($d['date_of_birth'] ?? '') ?>">
                    <div class="form-text">0–59 months old</div>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Sex <span class="text-danger">*</span></label>
                    <select name="sex" class="form-select" required>
                        <option value="">Select…</option>
                        <option value="Male"   <?= ($d['sex'] ?? '') === 'Male'   ? 'selected' : '' ?>>Male</option>
                        <option value="Female" <?= ($d['sex'] ?? '') === 'Female' ? 'selected' : '' ?>>Female</option>
                    </select>
                </div>

                <!-- IP Status -->
                <div class="col-md-3">
                    <label class="form-label">Belongs to IP Group?</label>
                    <select name="is_indigenous_people" id="ipSelect" class="form-select">
                        <option value="0" <?= empty($d['is_indigenous_people']) ? 'selected' : '' ?>>No</option>
                        <option value="1" <?= !empty($d['is_indigenous_people']) ? 'selected' : '' ?>>Yes</option>
                    </select>
                </div>
                <div class="col-md-4" id="ipGroupWrapper" <?= empty($d['is_indigenous_people']) ? 'style="display:none"' : '' ?>>
                    <label class="form-label">IP Group Name</label>
                    <input type="text" name="ip_group" class="form-control"
                           value="<?= htmlspecialchars($d['ip_group'] ?? '') ?>">
                </div>
            </div>
        </div>
    </div>

    <!-- ── LOCATION ── -->
    <div class="card border-0 shadow-sm mb-3">
        <div class="card-header bg-success text-white fw-semibold">
            <i class="bi bi-geo-alt me-2"></i>Location
        </div>
        <div class="card-body">
            <div class="row g-3">
                <!-- Region -->
                <div class="col-md-3">
                    <label class="form-label">Region</label>
                    <?php if (!empty($loc['region'])): ?>
                    <input type="text" name="region" class="form-control bg-light"
                           value="<?= htmlspecialchars($loc['region']) ?>" readonly>
                    <?php else: ?>
                    <input type="text" name="region" class="form-control"
                           value="<?= htmlspecialchars($d['region'] ?? '') ?>">
                    <?php endif; ?>
                </div>
                <!-- Province -->
                <div class="col-md-3">
                    <label class="form-label">Province</label>
                    <?php if (!empty($loc['province'])): ?>
                    <input type="text" name="province" class="form-control bg-light"
                           value="<?= htmlspecialchars($loc['province']) ?>" readonly>
                    <?php else: ?>
                    <input type="text" name="province" class="form-control"
                           value="<?= htmlspecialchars($d['province'] ?? '') ?>">
                    <?php endif; ?>
                </div>
                <!-- City/Municipality -->
                <div class="col-md-3">
                    <label class="form-label">City/Municipality</label>
                    <?php if (!empty($loc['city_municipality'])): ?>
                    <input type="text" name="city_municipality" class="form-control bg-light"
                           value="<?= htmlspecialchars($loc['city_municipality']) ?>" readonly>
                    <?php else: ?>
                    <input type="text" name="city_municipality" class="form-control"
                           value="<?= htmlspecialchars($d['city_municipality'] ?? '') ?>">
                    <?php endif; ?>
                </div>
                <!-- Barangay -->
                <div class="col-md-3">
                    <label class="form-label">Barangay <span class="text-danger">*</span></label>
                    <?php if ($locked): ?>
                    <input type="text" class="form-control bg-light" value="<?= htmlspecialchars($userBrgy) ?>" readonly>
                    <input type="hidden" name="barangay" value="<?= htmlspecialchars($userBrgy) ?>">
                    <?php else: ?>
                    <select name="barangay" class="form-select" required>
                        <option value="">— Select Barangay —</option>
                        <?php foreach ($barangays as $b): ?>
                        <option value="<?= htmlspecialchars($b['barangay']) ?>"
                            <?= ($d['barangay'] ?? '') === $b['barangay'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($b['barangay']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                    <?php endif; ?>
                </div>
                <!-- Purok -->
                <div class="col-md-4">
                    <label class="form-label">Purok / Zone / Block</label>
                    <input type="text" name="purok_zone" class="form-control"
                           placeholder="e.g. Purok 3, Block 5"
                           value="<?= htmlspecialchars($d['purok_zone'] ?? '') ?>">
                </div>
            </div>
        </div>
    </div>

    <!-- ── PARENT / GUARDIAN ── -->
    <div class="card border-0 shadow-sm mb-3">
        <div class="card-header bg-info text-white fw-semibold">
            <i class="bi bi-people me-2"></i>Parent / Guardian
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">Mother's Name</label>
                    <input type="text" name="mother_name" class="form-control"
                           placeholder="Surname, First Name"
                           value="<?= htmlspecialchars($d['mother_name'] ?? '') ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Father's Name</label>
                    <input type="text" name="father_name" class="form-control"
                           placeholder="Surname, First Name"
                           value="<?= htmlspecialchars($d['father_name'] ?? '') ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Contact Number</label>
                    <input type="text" name="contact_number" class="form-control"
                           value="<?= htmlspecialchars($d['contact_number'] ?? '') ?>">
                </div>
                <div class="col-md-5">
                    <label class="form-label">Guardian Name <small class="text-muted">(if different from parents)</small></label>
                    <input type="text" name="guardian_name" class="form-control"
                           placeholder="Surname, First Name"
                           value="<?= htmlspecialchars($d['guardian_name'] ?? '') ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Guardian Relationship</label>
                    <select name="guardian_relationship" class="form-select">
                        <option value="">— Select —</option>
                        <?php foreach (['Mother','Father','Grandmother','Grandfather','Aunt','Uncle','Sibling','Other Relative','Guardian'] as $rel): ?>
                        <option value="<?= $rel ?>" <?= ($d['guardian_relationship'] ?? '') === $rel ? 'selected' : '' ?>><?= $rel ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
        </div>
    </div>

    <!-- ── OPTIONAL: SOCIOECONOMIC (collapsed) ── -->
    <div class="card border-0 shadow-sm mb-3">
        <div class="card-header bg-warning text-dark fw-semibold collapse-header d-flex justify-content-between align-items-center"
             data-bs-toggle="collapse" data-bs-target="#collapseSocio" aria-expanded="false" style="cursor:pointer">
            <span><i class="bi bi-cash-coin me-2"></i>Socioeconomic Status <small class="fw-normal">(optional)</small></span>
            <i class="bi bi-chevron-down small chevron"></i>
        </div>
        <div class="collapse" id="collapseSocio">
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">Income Classification</label>
                        <select name="income_classification" class="form-select">
                            <option value="">Select…</option>
                            <?php foreach (['Poor','Near Poor','Non-Poor'] as $ic): ?>
                            <option value="<?= $ic ?>" <?= ($d['income_classification'] ?? '') === $ic ? 'selected' : '' ?>><?= $ic ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Monthly Household Income (PHP)</label>
                        <input type="number" step="0.01" name="household_monthly_income" class="form-control"
                               value="<?= htmlspecialchars($d['household_monthly_income'] ?? '') ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Income Source</label>
                        <input type="text" name="income_source" class="form-control"
                               value="<?= htmlspecialchars($d['income_source'] ?? '') ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">PhilHealth Status</label>
                        <select name="philhealth_status" class="form-select">
                            <option value="">Select…</option>
                            <?php foreach (['Member','Indigent','Non-member'] as $ps): ?>
                            <option value="<?= $ps ?>" <?= ($d['philhealth_status'] ?? '') === $ps ? 'selected' : '' ?>><?= $ps ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">NHTS-PR Status</label>
                        <select name="nhts_pr_status" class="form-select">
                            <option value="">Select…</option>
                            <option value="Poor"     <?= ($d['nhts_pr_status'] ?? '') === 'Poor'     ? 'selected' : '' ?>>Poor</option>
                            <option value="Not Poor" <?= ($d['nhts_pr_status'] ?? '') === 'Not Poor' ? 'selected' : '' ?>>Not Poor</option>
                        </select>
                    </div>
                    <div class="col-md-4 d-flex align-items-end gap-3 flex-wrap">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="is_4ps_member" id="is4ps"
                                   <?= !empty($d['is_4ps_member']) ? 'checked' : '' ?>>
                            <label class="form-check-label" for="is4ps">4Ps Member</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="is_pwd_household" id="isPwd"
                                   <?= !empty($d['is_pwd_household']) ? 'checked' : '' ?>>
                            <label class="form-check-label" for="isPwd">PWD in Household</label>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Household No.</label>
                        <input type="text" name="household_no" class="form-control"
                               value="<?= htmlspecialchars($d['household_no'] ?? '') ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">InCode (PSA)</label>
                        <input type="text" name="incode" class="form-control"
                               value="<?= htmlspecialchars($d['incode'] ?? '') ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Place of Birth</label>
                        <input type="text" name="place_of_birth" class="form-control"
                               value="<?= htmlspecialchars($d['place_of_birth'] ?? '') ?>">
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ── OPTIONAL: PHOTO (collapsed) ── -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-secondary text-white fw-semibold collapse-header d-flex justify-content-between align-items-center"
             data-bs-toggle="collapse" data-bs-target="#collapsePhoto" aria-expanded="false" style="cursor:pointer">
            <span><i class="bi bi-camera me-2"></i>Photo <small class="fw-normal">(optional)</small></span>
            <i class="bi bi-chevron-down small chevron"></i>
        </div>
        <div class="collapse" id="collapsePhoto">
            <div class="card-body">
                <div class="row g-3 align-items-center">
                    <div class="col-md-4">
                        <label class="form-label">Upload Photo</label>
                        <input type="file" name="photo" class="form-control" accept="image/*" id="photoInput">
                        <div class="form-text">JPG, PNG or WEBP. Max 2MB.</div>
                    </div>
                    <div class="col-md-2">
                        <img id="photoPreview" src="#" alt="Preview"
                             class="rounded d-none"
                             style="width:80px;height:80px;object-fit:cover;border:2px solid #e2e8f0;">
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="d-flex gap-2">
        <button type="submit" class="btn btn-primary px-4">
            <i class="bi bi-check-circle me-1"></i>Save Beneficiary
        </button>
        <a href="<?= APP_URL ?>/beneficiaries" class="btn btn-outline-secondary">Cancel</a>
    </div>
</form>

<script>
// Duplicate check
(function() {
    let dupTimer;
    function checkDuplicate() {
        const ln  = document.querySelector('[name=last_name]').value.trim();
        const fn  = document.querySelector('[name=first_name]').value.trim();
        const dob = document.querySelector('[name=date_of_birth]').value.trim();
        if (!ln || !fn || !dob) return;
        clearTimeout(dupTimer);
        dupTimer = setTimeout(() => {
            fetch(`<?= APP_URL ?>/beneficiaries/check-duplicate?last_name=${encodeURIComponent(ln)}&first_name=${encodeURIComponent(fn)}&date_of_birth=${encodeURIComponent(dob)}`)
                .then(r => r.json())
                .then(d => { document.getElementById('duplicateAlert').classList.toggle('d-none', !d.duplicate); });
        }, 500);
    }
    ['last_name','first_name','date_of_birth'].forEach(n => {
        const el = document.querySelector(`[name=${n}]`);
        if (el) el.addEventListener('change', checkDuplicate);
    });
})();

// IP group toggle
document.getElementById('ipSelect').addEventListener('change', function() {
    document.getElementById('ipGroupWrapper').style.display = this.value === '1' ? '' : 'none';
});

// Photo preview
document.getElementById('photoInput')?.addEventListener('change', function() {
    const preview = document.getElementById('photoPreview');
    if (this.files && this.files[0]) {
        preview.src = URL.createObjectURL(this.files[0]);
        preview.classList.remove('d-none');
    }
});

// Collapse chevron rotation
document.querySelectorAll('.collapse-header').forEach(header => {
    const target = document.querySelector(header.dataset.bsTarget);
    const chevron = header.querySelector('.chevron');
    if (!target || !chevron) return;
    target.addEventListener('show.bs.collapse',  () => chevron.style.transform = 'rotate(180deg)');
    target.addEventListener('hide.bs.collapse',  () => chevron.style.transform = '');
});
</script>
