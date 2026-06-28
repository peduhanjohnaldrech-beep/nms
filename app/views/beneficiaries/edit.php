<?php $pageTitle = 'Edit Beneficiary'; ?>

<div class="d-flex align-items-center my-3">
    <a href="<?= APP_URL ?>/beneficiaries/<?= $beneficiary['id'] ?>" class="btn btn-sm btn-outline-secondary me-3">
        <i class="bi bi-arrow-left"></i>
    </a>
    <h4 class="mb-0"><i class="bi bi-pencil-square me-2"></i>Edit Beneficiary</h4>
</div>

<form action="<?= APP_URL ?>/beneficiaries/<?= $beneficiary['id'] ?>/edit" method="post" enctype="multipart/form-data">
    <?= \Core\Session::csrfField() ?>

    <!-- Personal Information -->
    <div class="card border-0 shadow-sm mb-3">
        <div class="card-header bg-primary text-white fw-semibold">
            <i class="bi bi-person-badge me-2"></i>Personal Information
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">Last Name <span class="text-danger">*</span></label>
                    <input type="text" name="last_name" class="form-control" required
                           value="<?= htmlspecialchars($data['last_name'] ?? '') ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">First Name <span class="text-danger">*</span></label>
                    <input type="text" name="first_name" class="form-control" required
                           value="<?= htmlspecialchars($data['first_name'] ?? '') ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Middle Name</label>
                    <input type="text" name="middle_name" class="form-control"
                           value="<?= htmlspecialchars($data['middle_name'] ?? '') ?>">
                </div>
                <div class="col-md-1">
                    <label class="form-label">Suffix</label>
                    <input type="text" name="suffix" class="form-control"
                           value="<?= htmlspecialchars($data['suffix'] ?? '') ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Date of Birth <span class="text-danger">*</span></label>
                    <input type="date" name="date_of_birth" class="form-control" required
                           min="<?= date('Y-m-d', strtotime('-59 months')) ?>"
                           max="<?= date('Y-m-d') ?>"
                           value="<?= htmlspecialchars($data['date_of_birth'] ?? '') ?>">
                    <div class="form-text text-muted">0–59 months only (born <?= date('M Y', strtotime('-59 months')) ?> – today).</div>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Sex <span class="text-danger">*</span></label>
                    <select name="sex" class="form-select" required>
                        <option value="">Select...</option>
                        <option value="Male"   <?= ($data['sex'] ?? '') === 'Male'   ? 'selected' : '' ?>>Male</option>
                        <option value="Female" <?= ($data['sex'] ?? '') === 'Female' ? 'selected' : '' ?>>Female</option>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Place of Birth</label>
                    <input type="text" name="place_of_birth" class="form-control"
                           value="<?= htmlspecialchars($data['place_of_birth'] ?? '') ?>">
                </div>
            </div>
        </div>
    </div>

    <!-- Address / Location -->
    <div class="card border-0 shadow-sm mb-3">
        <div class="card-header bg-success text-white fw-semibold">
            <i class="bi bi-geo-alt me-2"></i>Address / Location
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">Region</label>
                    <input type="text" name="region" class="form-control"
                           value="<?= htmlspecialchars($data['region'] ?? '') ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Province</label>
                    <input type="text" name="province" class="form-control"
                           value="<?= htmlspecialchars($data['province'] ?? '') ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">City/Municipality</label>
                    <input type="text" name="city_municipality" class="form-control"
                           value="<?= htmlspecialchars($data['city_municipality'] ?? '') ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Barangay <span class="text-danger">*</span></label>
                    <input type="text" name="barangay" class="form-control" required
                           list="barangay-list"
                           value="<?= htmlspecialchars($data['barangay'] ?? '') ?>">
                    <datalist id="barangay-list">
                        <?php foreach ($barangays ?? [] as $b): ?>
                        <option value="<?= htmlspecialchars($b['barangay']) ?>">
                        <?php endforeach; ?>
                    </datalist>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Purok/Zone</label>
                    <input type="text" name="purok_zone" class="form-control"
                           value="<?= htmlspecialchars($data['purok_zone'] ?? '') ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Household No.</label>
                    <input type="text" name="household_no" class="form-control"
                           value="<?= htmlspecialchars($data['household_no'] ?? '') ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">InCode (PSA)</label>
                    <input type="text" name="incode" class="form-control"
                           value="<?= htmlspecialchars($data['incode'] ?? '') ?>">
                </div>
            </div>
        </div>
    </div>

    <!-- Parent / Guardian -->
    <div class="card border-0 shadow-sm mb-3">
        <div class="card-header bg-info text-white fw-semibold">
            <i class="bi bi-people me-2"></i>Parent / Guardian Information
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">Mother's Name</label>
                    <input type="text" name="mother_name" class="form-control"
                           value="<?= htmlspecialchars($data['mother_name'] ?? '') ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Father's Name</label>
                    <input type="text" name="father_name" class="form-control"
                           value="<?= htmlspecialchars($data['father_name'] ?? '') ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Contact Number</label>
                    <input type="text" name="contact_number" class="form-control"
                           value="<?= htmlspecialchars($data['contact_number'] ?? '') ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Guardian Name</label>
                    <input type="text" name="guardian_name" class="form-control"
                           value="<?= htmlspecialchars($data['guardian_name'] ?? '') ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Guardian Relationship</label>
                    <input type="text" name="guardian_relationship" class="form-control"
                           value="<?= htmlspecialchars($data['guardian_relationship'] ?? '') ?>">
                </div>
            </div>
        </div>
    </div>

    <!-- Socioeconomic Status -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-warning text-dark fw-semibold">
            <i class="bi bi-cash-coin me-2"></i>Socioeconomic Status
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">Income Classification</label>
                    <select name="income_classification" class="form-select">
                        <option value="">Select...</option>
                        <?php foreach (['Poor','Near Poor','Non-Poor'] as $ic): ?>
                        <option value="<?= $ic ?>" <?= ($data['income_classification'] ?? '') === $ic ? 'selected' : '' ?>><?= $ic ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Monthly Household Income (PHP)</label>
                    <input type="number" step="0.01" name="household_monthly_income" class="form-control"
                           value="<?= htmlspecialchars($data['household_monthly_income'] ?? '') ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Income Source</label>
                    <input type="text" name="income_source" class="form-control"
                           value="<?= htmlspecialchars($data['income_source'] ?? '') ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">PhilHealth Status</label>
                    <select name="philhealth_status" class="form-select">
                        <option value="">Select...</option>
                        <?php foreach (['Member','Indigent','Non-member'] as $ps): ?>
                        <option value="<?= $ps ?>" <?= ($data['philhealth_status'] ?? '') === $ps ? 'selected' : '' ?>><?= $ps ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">NHTS-PR Status</label>
                    <select name="nhts_pr_status" class="form-select">
                        <option value="">Select...</option>
                        <option value="Poor"     <?= ($data['nhts_pr_status'] ?? '') === 'Poor'     ? 'selected' : '' ?>>Poor</option>
                        <option value="Not Poor" <?= ($data['nhts_pr_status'] ?? '') === 'Not Poor' ? 'selected' : '' ?>>Not Poor</option>
                    </select>
                </div>
                <div class="col-md-4 d-flex align-items-end">
                    <div class="form-check me-3">
                        <input class="form-check-input" type="checkbox" name="is_4ps_member" id="is4ps"
                               <?= !empty($data['is_4ps_member']) ? 'checked' : '' ?>>
                        <label class="form-check-label" for="is4ps">4Ps Member</label>
                    </div>
                    <div class="form-check me-3">
                        <input class="form-check-input" type="checkbox" name="is_pwd_household" id="isPwd"
                               <?= !empty($data['is_pwd_household']) ? 'checked' : '' ?>>
                        <label class="form-check-label" for="isPwd">PWD in Household</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="is_indigenous_people" id="isIp"
                               <?= !empty($data['is_indigenous_people']) ? 'checked' : '' ?>>
                        <label class="form-check-label" for="isIp">Indigenous People</label>
                    </div>
                </div>
                <div class="col-md-4" id="ipGroupWrapper" <?= empty($data['is_indigenous_people']) ? 'style="display:none"' : '' ?>>
                    <label class="form-label">IP Group Name</label>
                    <input type="text" name="ip_group" class="form-control"
                           value="<?= htmlspecialchars($data['ip_group'] ?? '') ?>">
                </div>
            </div>
        </div>
    </div>

    <!-- Photo Upload -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-secondary text-white fw-semibold">
            <i class="bi bi-camera me-2"></i>Photo
        </div>
        <div class="card-body">
            <div class="row g-3 align-items-center">
                <?php if (!empty($beneficiary['photo'])): ?>
                <div class="col-auto">
                    <img src="<?= APP_URL ?>/uploads/photos/<?= htmlspecialchars($beneficiary['photo']) ?>"
                         alt="Current Photo" class="rounded"
                         style="width:80px;height:80px;object-fit:cover;border:2px solid #e2e8f0;">
                    <div class="form-text">Current photo</div>
                </div>
                <?php endif; ?>
                <div class="col-md-4">
                    <label class="form-label">Upload New Photo</label>
                    <input type="file" name="photo" class="form-control" accept="image/*" id="photoInput">
                    <div class="form-text">JPG, PNG or WEBP. Max 2MB. Leave blank to keep current.</div>
                </div>
                <div class="col-md-2">
                    <img id="photoPreview" src="#" alt="Preview"
                         class="rounded d-none"
                         style="width:80px;height:80px;object-fit:cover;border:2px solid #e2e8f0;">
                </div>
            </div>
        </div>
    </div>

    <div class="d-flex gap-2">
        <button type="submit" class="btn btn-primary px-4">
            <i class="bi bi-check-circle me-1"></i>Update Beneficiary
        </button>
        <a href="<?= APP_URL ?>/beneficiaries/<?= $beneficiary['id'] ?>" class="btn btn-outline-secondary">Cancel</a>
    </div>
</form>

<div id="duplicateAlert" class="alert alert-warning d-none mb-3">
    <i class="bi bi-exclamation-triangle-fill me-2"></i>
    Another beneficiary with this name and date of birth may already exist. Please verify before saving.
</div>

<script>
(function() {
    let dupTimer;
    function checkDuplicate() {
        const ln  = document.querySelector('[name=last_name]').value.trim();
        const fn  = document.querySelector('[name=first_name]').value.trim();
        const dob = document.querySelector('[name=date_of_birth]').value.trim();
        if (!ln || !fn || !dob) return;
        clearTimeout(dupTimer);
        dupTimer = setTimeout(() => {
            fetch(`<?= APP_URL ?>/beneficiaries/check-duplicate?last_name=${encodeURIComponent(ln)}&first_name=${encodeURIComponent(fn)}&date_of_birth=${encodeURIComponent(dob)}&exclude_id=<?= $beneficiary['id'] ?>`)
                .then(r => r.json())
                .then(d => {
                    document.getElementById('duplicateAlert').classList.toggle('d-none', !d.duplicate);
                });
        }, 500);
    }
    ['last_name','first_name','date_of_birth'].forEach(n => {
        const el = document.querySelector(`[name=${n}]`);
        if (el) el.addEventListener('change', checkDuplicate);
    });
})();

document.getElementById('isIp').addEventListener('change', function() {
    document.getElementById('ipGroupWrapper').style.display = this.checked ? '' : 'none';
});
document.getElementById('photoInput').addEventListener('change', function() {
    const preview = document.getElementById('photoPreview');
    if (this.files && this.files[0]) {
        preview.src = URL.createObjectURL(this.files[0]);
        preview.classList.remove('d-none');
    }
});
</script>
