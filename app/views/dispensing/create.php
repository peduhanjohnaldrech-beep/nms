<?php $pageTitle = 'Record Dispensing'; ?>

<div class="d-flex align-items-center my-3">
    <a href="<?= $beneficiaryId ? APP_URL . '/beneficiaries/' . $beneficiaryId : APP_URL . '/dispensing' ?>"
       class="btn btn-sm btn-outline-secondary me-3"><i class="bi bi-arrow-left"></i></a>
    <h4 class="mb-0"><i class="bi bi-prescription2 me-2 text-primary"></i>Record Dispensing</h4>
</div>

<div class="row justify-content-center">
    <div class="col-lg-7">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white fw-semibold">
                <i class="bi bi-plus-circle me-1 text-primary"></i>New Dispensing Record
            </div>
            <div class="card-body">
                <form method="post" action="<?= APP_URL ?>/dispensing/create<?= $beneficiaryId ? '?bid=' . $beneficiaryId : '' ?>">
                    <?= \Core\Session::csrfField() ?>

                    <!-- Beneficiary -->
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Beneficiary <span class="text-danger">*</span></label>
                        <?php if ($beneficiary): ?>
                            <input type="hidden" name="beneficiary_id" value="<?= $beneficiary['id'] ?>">
                            <div class="form-control bg-light">
                                <i class="bi bi-person-fill me-1 text-primary"></i>
                                <strong><?= htmlspecialchars($beneficiary['last_name'] . ', ' . $beneficiary['first_name']) ?></strong>
                                <span class="text-muted small ms-2"><?= htmlspecialchars($beneficiary['barangay']) ?></span>
                            </div>
                        <?php else: ?>
                            <input type="hidden" name="beneficiary_id" id="dispBeneId" required>
                            <input type="text" id="dispBeneSearch" class="form-control"
                                   placeholder="Type name or barangay to search..." autocomplete="off">
                            <div id="dispBeneDropdown" class="border rounded mt-1 bg-white shadow-sm d-none"
                                 style="max-height:200px; overflow-y:auto; position:relative; z-index:10;"></div>
                            <div id="dispBeneLabel" class="form-text text-primary fw-semibold mt-1"></div>
                        <?php endif; ?>
                    </div>

                    <div class="row g-3 mb-3">
                        <!-- Program -->
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Program <span class="text-danger">*</span></label>
                            <select name="program" class="form-select" required id="programSelect">
                                <option value="">— Select Program —</option>
                                <?php foreach ($activePrograms as $p): ?>
                                <?php if ($p['code'] === 'OPT') continue; ?>
                                <option value="<?= htmlspecialchars($p['code']) ?>">
                                    <?= htmlspecialchars($p['code'] . ' — ' . $p['name']) ?>
                                </option>
                                <?php endforeach; ?>
                                <option value="General">General / Other</option>
                            </select>
                        </div>
                        <!-- Date -->
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Date Dispensed <span class="text-danger">*</span></label>
                            <input type="date" name="date_dispensed" class="form-control"
                                   value="<?= date('Y-m-d') ?>" required>
                        </div>
                    </div>

                    <!-- Enrollment Link (optional) -->
                    <?php if (!empty($enrollments)): ?>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Link to Enrollment <span class="text-muted small fw-normal">(optional)</span></label>
                        <select name="enrollment_id" class="form-select" id="enrollmentSelect">
                            <option value="" data-program="">— None —</option>
                            <?php foreach ($enrollments as $e): ?>
                            <option value="<?= $e['id'] ?>" data-program="<?= htmlspecialchars($e['program']) ?>">
                                <?= htmlspecialchars($e['program']) ?> — <?= htmlspecialchars($e['status']) ?>
                                (<?= \DateHelper::formatDate($e['enrollment_date'], 'M j, Y') ?>)
                            </option>
                            <?php endforeach; ?>
                        </select>
                        <div id="enrollmentMismatchWarn" class="alert alert-warning small py-2 mt-1 d-none">
                            <i class="bi bi-exclamation-triangle me-1"></i>
                            The selected enrollment is for a different program than the one chosen above.
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- Supplement Type -->
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Supplement / Medicine <span class="text-danger">*</span></label>
                        <select name="supplement_type" class="form-select" required id="supplementSelect">
                            <option value="">— Select Program first —</option>
                        </select>
                        <input type="text" name="supplement_type_custom" id="customType"
                               class="form-control mt-2 d-none" placeholder="Specify supplement/medicine name...">
                    </div>

                    <div class="row g-3 mb-3">
                        <!-- Quantity -->
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Quantity <span class="text-danger">*</span></label>
                            <input type="number" name="quantity" class="form-control" value="1" min="0.1" step="0.1" required>
                        </div>
                        <!-- Unit -->
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Unit <span class="text-danger">*</span></label>
                            <select name="unit" class="form-select" id="unitSelect" required>
                                <option value="sachet">sachet</option>
                                <option value="capsule">capsule</option>
                                <option value="tablet">tablet</option>
                                <option value="pack">pack</option>
                                <option value="bottle">bottle</option>
                                <option value="ml">ml</option>
                                <option value="piece(s)">piece(s)</option>
                                <option value="session">session</option>
                            </select>
                        </div>
                        <!-- Qty visual indicator -->
                        <div class="col-md-4 d-flex align-items-end">
                            <div id="doseHint" class="alert alert-info py-2 px-3 small mb-0 w-100 d-none"></div>
                        </div>
                    </div>

                    <!-- Notes -->
                    <div class="mb-4">
                        <label class="form-label fw-semibold">Notes</label>
                        <textarea name="notes" class="form-control" rows="2"
                                  placeholder="Optional remarks or instructions..."></textarea>
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-circle me-1"></i>Save Record
                        </button>
                        <a href="<?= $beneficiaryId ? APP_URL . '/beneficiaries/' . $beneficiaryId : APP_URL . '/dispensing' ?>"
                           class="btn btn-outline-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
// Beneficiary search
const dispBeneSearch   = document.getElementById('dispBeneSearch');
const dispBeneDropdown = document.getElementById('dispBeneDropdown');
const dispBeneId       = document.getElementById('dispBeneId');
const dispBeneLabel    = document.getElementById('dispBeneLabel');

const dispBeneficiaries = <?= json_encode(array_map(fn($b) => [
    'id'   => $b['id'],
    'text' => $b['last_name'] . ', ' . $b['first_name'] . ' (' . $b['barangay'] . ')'
], $allBeneficiaries ?? [])) ?>;

if (dispBeneSearch) {
    dispBeneSearch.addEventListener('input', function () {
        const q = this.value.trim().toLowerCase();
        dispBeneDropdown.innerHTML = '';
        if (!q) { dispBeneDropdown.classList.add('d-none'); return; }
        const matches = dispBeneficiaries.filter(b => b.text.toLowerCase().includes(q)).slice(0, 30);
        if (!matches.length) {
            dispBeneDropdown.innerHTML = '<div class="px-3 py-2 text-muted small">No results found</div>';
        } else {
            matches.forEach(b => {
                const div = document.createElement('div');
                div.className = 'px-3 py-2 small border-bottom';
                div.style.cursor = 'pointer';
                div.textContent = b.text;
                div.addEventListener('mouseenter', () => div.classList.add('bg-light'));
                div.addEventListener('mouseleave', () => div.classList.remove('bg-light'));
                div.addEventListener('click', () => {
                    dispBeneId.value = b.id;
                    dispBeneSearch.value = b.text;
                    dispBeneLabel.textContent = '✓ Selected';
                    dispBeneDropdown.classList.add('d-none');
                });
                dispBeneDropdown.appendChild(div);
            });
        }
        dispBeneDropdown.classList.remove('d-none');
    });
    document.addEventListener('click', function (e) {
        if (!dispBeneSearch.contains(e.target) && !dispBeneDropdown.contains(e.target))
            dispBeneDropdown.classList.add('d-none');
    });
}

const supplements = {
    DSP: [
        { val: 'RUSF (Ready-to-Use Supplementary Food)',  label: 'RUSF (Ready-to-Use Supplementary Food)',  unit: 'sachet' },
        { val: 'RUTF (Ready-to-Use Therapeutic Food)',    label: 'RUTF (Ready-to-Use Therapeutic Food)',    unit: 'sachet' },
        { val: 'Supplementary Feeding',                   label: 'Supplementary Feeding',                   unit: 'session' },
        { val: 'Health Education',                        label: 'Health Education',                        unit: 'session' },
        { val: 'Other',                                   label: 'Other',                                   unit: 'piece(s)' },
    ],
    MNS: [
        { val: 'Vitamin A (Blue - 100,000 IU)',               label: 'Vitamin A Blue — 100,000 IU',         unit: 'capsule', hint: 'For children 6–11 months' },
        { val: 'Vitamin A (Red - 200,000 IU)',                label: 'Vitamin A Red — 200,000 IU',          unit: 'capsule', hint: 'For children 12–59 months' },
        { val: 'MNP (Micronutrient Powder)',                  label: 'MNP (Micronutrient Powder)',          unit: 'sachet' },
        { val: 'LNS-SQ (Lipid-based Nutrient Supplement)',   label: 'LNS-SQ (Lipid-based Nutrient Supplement)', unit: 'sachet' },
        { val: 'Other',                                       label: 'Other',                               unit: 'piece(s)' },
    ],
    General: [
        { val: 'Iron Supplement',          label: 'Iron Supplement',          unit: 'tablet' },
        { val: 'Deworming (Albendazole)',   label: 'Deworming (Albendazole)',  unit: 'tablet' },
        { val: 'Zinc Supplement',          label: 'Zinc Supplement',          unit: 'tablet' },
        { val: 'Vitamin D',                label: 'Vitamin D',                unit: 'capsule' },
        { val: 'Iodine Capsule',           label: 'Iodine Capsule',           unit: 'capsule' },
        { val: 'Ferrous Sulfate',          label: 'Ferrous Sulfate',          unit: 'tablet' },
        { val: 'Folic Acid',               label: 'Folic Acid',               unit: 'tablet' },
        { val: 'Other',                    label: 'Other',                    unit: 'piece(s)' },
    ],
};
// Custom programs fall back to a generic list
<?php foreach ($activePrograms as $p): ?>
<?php if (!in_array($p['code'], ['OPT','DSP','MNS'])): ?>
if (!supplements['<?= addslashes($p['code']) ?>']) {
    supplements['<?= addslashes($p['code']) ?>'] = [
        { val: 'Other', label: 'Specify below', unit: 'piece(s)' },
    ];
}
<?php endif; ?>
<?php endforeach; ?>

const programSelect   = document.getElementById('programSelect');
const supplementSelect = document.getElementById('supplementSelect');
const unitSelect      = document.getElementById('unitSelect');
const customType      = document.getElementById('customType');
const doseHint        = document.getElementById('doseHint');

programSelect.addEventListener('change', function () {
    const list = supplements[this.value] || [];
    supplementSelect.innerHTML = list.length
        ? '<option value="">— Select Supplement —</option>'
        : '<option value="">— Select Program first —</option>';
    list.forEach(s => {
        const opt = document.createElement('option');
        opt.value = s.val;
        opt.textContent = s.label;
        opt.dataset.unit = s.unit || 'piece(s)';
        opt.dataset.hint = s.hint || '';
        supplementSelect.appendChild(opt);
    });
    customType.classList.add('d-none');
    customType.required = false;
    doseHint.classList.add('d-none');
});

// Enrollment/program mismatch warning
const enrollmentSelect = document.getElementById('enrollmentSelect');
const mismatchWarn     = document.getElementById('enrollmentMismatchWarn');
function checkEnrollmentMismatch() {
    if (!enrollmentSelect || !mismatchWarn) return;
    const enrollProgram = enrollmentSelect.options[enrollmentSelect.selectedIndex]?.dataset.program || '';
    const selectedProgram = programSelect.value;
    if (enrollProgram && selectedProgram && enrollProgram !== selectedProgram) {
        mismatchWarn.classList.remove('d-none');
    } else {
        mismatchWarn.classList.add('d-none');
    }
}
if (enrollmentSelect) enrollmentSelect.addEventListener('change', checkEnrollmentMismatch);
programSelect.addEventListener('change', checkEnrollmentMismatch);

supplementSelect.addEventListener('change', function () {
    const opt = this.options[this.selectedIndex];
    if (this.value === 'Other') {
        customType.classList.remove('d-none');
        customType.required = true;
    } else {
        customType.classList.add('d-none');
        customType.required = false;
        customType.value = '';
    }
    if (opt && opt.dataset.unit) {
        unitSelect.value = opt.dataset.unit;
    }
    if (opt && opt.dataset.hint) {
        doseHint.textContent = opt.dataset.hint;
        doseHint.classList.remove('d-none');
    } else {
        doseHint.classList.add('d-none');
    }
});
</script>
