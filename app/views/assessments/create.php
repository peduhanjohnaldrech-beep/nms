<?php $pageTitle = 'Record Assessment'; ?>
<?php
$curPeriod = (int)date('n') <= 6 ? 'January' : 'July';
$curYear   = (int)date('Y');
?>

<div class="d-flex align-items-center my-3">
    <a href="<?= isset($beneficiary) ? APP_URL . '/beneficiaries/' . $beneficiary['id'] : APP_URL . '/beneficiaries' ?>"
       class="btn btn-sm btn-outline-secondary me-3"><i class="bi bi-arrow-left"></i></a>
    <h4 class="mb-0"><i class="bi bi-clipboard2-plus me-2"></i>Record Assessment (OPT)</h4>
</div>

<div class="row justify-content-center">
    <div class="col-lg-10">

        <?php if (!empty($existingThisPeriod)): ?>
        <div class="alert alert-warning d-flex align-items-start gap-2 mb-3">
            <i class="bi bi-exclamation-triangle-fill fs-5 mt-1 flex-shrink-0"></i>
            <div>
                <strong>Duplicate Warning</strong> — This child already has an assessment for the
                <strong><?= $curPeriod ?> <?= $curYear ?></strong> period
                (recorded <?= DateHelper::formatDate($existingThisPeriod['assessment_date'], 'M j, Y') ?>).
                You may still save a new one for follow-up purposes.
            </div>
        </div>
        <?php endif; ?>

        <div class="row g-4 align-items-start">

            <!-- Form -->
            <div class="<?= !empty($lastAssessment) ? 'col-lg-7' : 'col-lg-7 mx-auto' ?>">
                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <form action="<?= APP_URL ?>/assessments/create" method="post">
                            <?= \Core\Session::csrfField() ?>

                            <?php if (isset($beneficiary)): ?>
                            <?php $__ageMonths = DateHelper::ageInMonths($beneficiary['date_of_birth']); ?>
                            <input type="hidden" name="beneficiary_id" value="<?= $beneficiary['id'] ?>">
                            <div class="alert alert-<?= $__ageMonths > 59 ? 'warning' : 'info' ?> py-2 mb-3">
                                <i class="bi bi-person-circle me-2"></i>
                                <strong><?= htmlspecialchars($beneficiary['last_name'] . ', ' . $beneficiary['first_name']) ?></strong>
                                &mdash; <?= htmlspecialchars($beneficiary['sex']) ?>,
                                DOB: <?= DateHelper::formatDate($beneficiary['date_of_birth']) ?>
                                (<?= DateHelper::formatAge($__ageMonths) ?>)
                                <?php if ($__ageMonths > 59): ?>
                                <br><strong>Note:</strong> This child is <?= $__ageMonths ?> months old and has aged out of the 0–59 month monitoring range. You may still record a measurement for historical purposes.
                                <?php endif; ?>
                            </div>
                            <?php else: ?>
                            <div class="mb-3">
                                <label class="form-label fw-semibold">Beneficiary <span class="text-danger">*</span></label>
                                <input type="hidden" name="beneficiary_id" id="beneId" required>
                                <input type="text" id="beneSearch" class="form-control"
                                       placeholder="Type name or barangay to search..." autocomplete="off">
                                <div id="beneDropdown" class="border rounded mt-1 bg-white shadow-sm d-none"
                                     style="max-height:200px; overflow-y:auto; position:relative; z-index:10;"></div>
                                <div id="beneInfo" class="d-none mt-2"></div>
                            </div>
                            <?php endif; ?>

                            <div class="mb-3">
                                <label class="form-label fw-semibold">Assessment Date <span class="text-danger">*</span></label>
                                <input type="date" name="assessment_date" id="assessmentDate" class="form-control"
                                       required value="<?= date('Y-m-d') ?>">
                                <div class="form-text" id="periodHint"></div>
                            </div>

                            <div class="row g-3 mb-1">
                                <div class="col-md-4">
                                    <label class="form-label fw-semibold">Weight (kg) <span class="text-danger">*</span></label>
                                    <input type="number" step="0.01" min="0" max="100" name="weight_kg"
                                           class="form-control" required placeholder="e.g. 8.50">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label fw-semibold">Height (cm)</label>
                                    <input type="number" step="0.1" min="0" max="200" name="height_cm"
                                           class="form-control" placeholder="e.g. 72.5">
                                    <div class="form-text"><i class="bi bi-info-circle text-primary"></i> Required for WFL/H z-score</div>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label fw-semibold">MUAC (cm)</label>
                                    <input type="number" step="0.1" min="0" max="50" name="muac_cm"
                                           id="muacInput" class="form-control" placeholder="e.g. 13.5">
                                    <div class="form-text">
                                        <a href="#muacRef" data-bs-toggle="collapse" class="text-decoration-none small">
                                            <i class="bi bi-question-circle"></i> MUAC reference
                                        </a>
                                    </div>
                                </div>
                            </div>

                            <div class="collapse mb-3" id="muacRef">
                                <div class="alert alert-light border small py-2 mb-0 mt-2">
                                    <strong><i class="bi bi-rulers me-1"></i>MUAC Reference — children 6–59 months</strong>
                                    <table class="table table-sm table-borderless mb-0 mt-1">
                                        <tr>
                                            <td class="py-0">&lt; 11.5 cm</td>
                                            <td class="py-0"><span class="badge bg-danger">SAM</span></td>
                                            <td class="py-0 text-muted">Severe Acute Malnutrition</td>
                                        </tr>
                                        <tr>
                                            <td class="py-0">11.5 – 12.4 cm</td>
                                            <td class="py-0"><span class="badge bg-warning text-dark">MAM</span></td>
                                            <td class="py-0 text-muted">Moderate Acute Malnutrition</td>
                                        </tr>
                                        <tr>
                                            <td class="py-0">≥ 12.5 cm</td>
                                            <td class="py-0"><span class="badge bg-success">Normal</span></td>
                                            <td class="py-0 text-muted">Normal</td>
                                        </tr>
                                    </table>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-semibold">Assessed By</label>
                                <input type="hidden" name="assessed_by" value="<?= htmlspecialchars(\Core\Session::get('user_name', '')) ?>">
                                <input type="text" class="form-control bg-light" value="<?= htmlspecialchars(\Core\Session::get('user_name', '')) ?>" readonly>
                            </div>

                            <div class="mb-4">
                                <label class="form-label fw-semibold">Remarks</label>
                                <textarea name="remarks" class="form-control" rows="2" placeholder="Optional notes..."></textarea>
                            </div>

                            <div class="alert alert-light border small mb-3">
                                <i class="bi bi-info-circle me-1 text-primary"></i>
                                Z-scores are auto-computed using WHO 2006 LMS reference data.
                                Children classified as <strong>SW / MW</strong> (WFL/H) are auto-enrolled in DSP with <strong>RUTF / RUSF</strong>.
                                Children classified as <strong>SUW / UW</strong> (WFA) are enrolled with <strong>Health Education</strong>.
                            </div>

                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-primary px-4">
                                    <i class="bi bi-save me-1"></i>Save Assessment
                                </button>
                                <a href="<?= isset($beneficiary) ? APP_URL . '/beneficiaries/' . $beneficiary['id'] : APP_URL . '/beneficiaries' ?>"
                                   class="btn btn-outline-secondary">Cancel</a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Previous Assessment Sidebar -->
            <?php if (!empty($lastAssessment)): ?>
            <div class="col-lg-5">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white fw-semibold small">
                        <i class="bi bi-clock-history me-2 text-muted"></i>Previous Assessment
                        <span class="text-muted fw-normal ms-1">— <?= DateHelper::formatDate($lastAssessment['assessment_date'], 'M j, Y') ?></span>
                    </div>
                    <div class="card-body">
                        <div class="row g-2 text-center mb-3">
                            <div class="col-4">
                                <div class="fs-4 fw-bold text-primary"><?= $lastAssessment['weight_kg'] ?></div>
                                <div class="text-muted small">kg</div>
                            </div>
                            <div class="col-4">
                                <div class="fs-4 fw-bold text-secondary"><?= $lastAssessment['height_cm'] ?: '—' ?></div>
                                <div class="text-muted small">cm height</div>
                            </div>
                            <div class="col-4">
                                <div class="fs-4 fw-bold text-secondary"><?= $lastAssessment['muac_cm'] ?: '—' ?></div>
                                <div class="text-muted small">cm MUAC</div>
                            </div>
                        </div>
                        <table class="table table-sm table-borderless mb-0 small">
                            <tr>
                                <td class="text-muted pe-2">WFA</td>
                                <td>
                                    <span class="badge status-<?= strtolower($lastAssessment['nutritional_status']) ?>"><?= $lastAssessment['nutritional_status'] ?></span>
                                    <span class="text-muted ms-1">Z = <?= number_format($lastAssessment['weight_for_age_zscore'] ?? 0, 2) ?></span>
                                </td>
                            </tr>
                            <?php if (!empty($lastAssessment['wflh_status'])): ?>
                            <tr>
                                <td class="text-muted pe-2">WFL/H</td>
                                <td>
                                    <span class="badge status-<?= strtolower($lastAssessment['wflh_status']) ?>"><?= $lastAssessment['wflh_status'] ?></span>
                                    <span class="text-muted ms-1">Z = <?= number_format($lastAssessment['wflh_zscore'] ?? 0, 2) ?></span>
                                </td>
                            </tr>
                            <?php endif; ?>
                            <?php if (!empty($lastAssessment['hfa_status'])): ?>
                            <tr>
                                <td class="text-muted pe-2">HFA</td>
                                <td>
                                    <span class="badge status-<?= strtolower($lastAssessment['hfa_status']) ?>"><?= $lastAssessment['hfa_status'] ?></span>
                                    <span class="text-muted ms-1">Z = <?= number_format($lastAssessment['height_for_age_zscore'] ?? 0, 2) ?></span>
                                </td>
                            </tr>
                            <?php endif; ?>
                            <tr>
                                <td class="text-muted pe-2">Period</td>
                                <td><?= $lastAssessment['period'] ?> <?= $lastAssessment['assessment_year'] ?></td>
                            </tr>
                            <?php if (!empty($lastAssessment['assessed_by'])): ?>
                            <tr>
                                <td class="text-muted pe-2">By</td>
                                <td><?= htmlspecialchars($lastAssessment['assessed_by']) ?></td>
                            </tr>
                            <?php endif; ?>
                        </table>
                    </div>
                </div>
            </div>
            <?php endif; ?>

        </div>
    </div>
</div>

<script>
// 1. Dynamic period hint
(function () {
    const input = document.getElementById('assessmentDate');
    const hint  = document.getElementById('periodHint');
    if (!input || !hint) return;
    function update() {
        if (!input.value) { hint.textContent = 'Jan–Jun → January period | Jul–Dec → July period'; return; }
        const d      = new Date(input.value + 'T00:00:00');
        const period = d.getMonth() + 1 <= 6 ? 'January' : 'July';
        hint.textContent = period + ' period (' + d.getFullYear() + ')';
    }
    input.addEventListener('change', update);
    update();
})();

// 2. Age helpers for live beneficiary search
function ageInMonths(dob) {
    const d   = new Date(dob + 'T00:00:00');
    const now = new Date();
    let m = (now.getFullYear() - d.getFullYear()) * 12 + (now.getMonth() - d.getMonth());
    if (now.getDate() < d.getDate()) m--;
    return m;
}
function formatAge(m) {
    if (m < 0)  return 'not yet born';
    if (m < 12) return m + ' month' + (m !== 1 ? 's' : '');
    const y = Math.floor(m / 12), mo = m % 12;
    return y + ' yr' + (y !== 1 ? 's' : '') + (mo ? ' ' + mo + ' mo' : '');
}

// 3. Beneficiary search with live age display
const beneSearch   = document.getElementById('beneSearch');
const beneDropdown = document.getElementById('beneDropdown');
const beneId       = document.getElementById('beneId');
const beneInfo     = document.getElementById('beneInfo');

const alreadyAssessedIds = <?= json_encode(array_keys($alreadyAssessedIds ?? [])) ?>;
const alreadyAssessedSet  = new Set(alreadyAssessedIds.map(String));

const allBeneficiaries = <?= json_encode(array_map(fn($b) => [
    'id'   => $b['id'],
    'text' => $b['last_name'] . ', ' . $b['first_name'] . ' (' . $b['barangay'] . ')',
    'dob'  => $b['date_of_birth'],
    'sex'  => $b['sex'],
], $allBeneficiaries ?? [])) ?>;

if (beneSearch) {
    beneSearch.addEventListener('input', function () {
        const q = this.value.trim().toLowerCase();
        beneDropdown.innerHTML = '';
        if (!q) { beneDropdown.classList.add('d-none'); return; }

        const matches = allBeneficiaries.filter(b => b.text.toLowerCase().includes(q)).slice(0, 30);
        if (!matches.length) {
            beneDropdown.innerHTML = '<div class="px-3 py-2 text-muted small">No results found</div>';
        } else {
            matches.forEach(b => {
                const done = alreadyAssessedSet.has(String(b.id));
                const div  = document.createElement('div');
                div.className = 'px-3 py-2 small border-bottom d-flex justify-content-between align-items-center';
                div.style.cursor = 'pointer';
                const nameSpan = document.createElement('span');
                nameSpan.textContent = b.text;
                div.appendChild(nameSpan);
                if (done) {
                    const badge = document.createElement('span');
                    badge.className = 'badge bg-success ms-2 flex-shrink-0';
                    badge.textContent = 'Done';
                    div.appendChild(badge);
                }
                div.addEventListener('mouseenter', () => div.classList.add('bg-light'));
                div.addEventListener('mouseleave', () => div.classList.remove('bg-light'));
                div.addEventListener('click', () => {
                    beneId.value     = b.id;
                    beneSearch.value = b.text;
                    beneDropdown.classList.add('d-none');

                    const months   = ageInMonths(b.dob);
                    const isOver   = months > 59;
                    const isDone   = alreadyAssessedSet.has(String(b.id));
                    const alertCls = isOver ? 'warning' : (isDone ? 'success' : 'info');
                    beneInfo.className = 'alert alert-' + alertCls + ' py-2 mt-2';
                    beneInfo.innerHTML = '<i class="bi bi-person-circle me-2"></i>'
                        + '<strong>' + b.text + '</strong> &mdash; ' + b.sex
                        + ', DOB: ' + b.dob + ' (' + formatAge(months) + ')'
                        + (isDone  ? ' <span class="badge bg-success ms-1">Already assessed this period</span>' : '')
                        + (isOver  ? '<br><strong>Note:</strong> This child has aged out of the 0–59 month monitoring range.' : '');
                    beneInfo.classList.remove('d-none');
                });
                beneDropdown.appendChild(div);
            });
        }
        beneDropdown.classList.remove('d-none');
    });

    document.addEventListener('click', function (e) {
        if (!beneSearch.contains(e.target) && !beneDropdown.contains(e.target))
            beneDropdown.classList.add('d-none');
    });
}
</script>
