<?php $pageTitle = 'Dispensing Tracker'; ?>

<?php
$monthNames   = ['','January','February','March','April','May','June','July','August','September','October','November','December'];
$totalRecords = count($records);
$totalQty     = array_sum(array_column($records, 'quantity'));
$uniqueBenes  = count(array_unique(array_column($records, 'beneficiary_id')));
?>

<!-- Page Header -->
<div class="d-flex justify-content-between align-items-center mt-3 mb-4">
    <div class="d-flex align-items-center gap-3">
        <div class="rounded-3 bg-primary bg-opacity-10 d-flex align-items-center justify-content-center flex-shrink-0" style="width:48px;height:48px">
            <i class="bi bi-prescription2 fs-4 text-primary" aria-hidden="true"></i>
        </div>
        <div>
            <h4 class="mb-0 fw-bold">Dispensing Tracker</h4>
            <p class="text-muted small mb-0">Record and view medicines &amp; supplements given to beneficiaries</p>
        </div>
    </div>
    <?php if (hasPerm('dispensing')): ?>
    <a href="<?= APP_URL ?>/dispensing/create" class="btn btn-primary">
        <i class="bi bi-plus-circle me-1" aria-hidden="true"></i>Record Dispensing
    </a>
    <?php endif; ?>
</div>

<?php if (!empty($dbError)): ?>
<div class="alert alert-danger d-flex align-items-center gap-2 mb-3" role="alert">
    <i class="bi bi-exclamation-triangle-fill fs-5 flex-shrink-0" aria-hidden="true"></i>
    <div><?= $dbError ?></div>
</div>
<?php endif; ?>

<!-- Stats Row -->
<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body py-3 px-3 d-flex align-items-center gap-3">
                <div class="rounded-circle bg-primary bg-opacity-10 d-flex align-items-center justify-content-center flex-shrink-0" style="width:40px;height:40px" aria-hidden="true">
                    <i class="bi bi-journal-text text-primary"></i>
                </div>
                <div>
                    <div class="fs-4 fw-bold lh-1 text-primary"><?= number_format($totalRecords) ?></div>
                    <div class="small text-muted mt-1">Total Records</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body py-3 px-3 d-flex align-items-center gap-3">
                <div class="rounded-circle bg-success bg-opacity-10 d-flex align-items-center justify-content-center flex-shrink-0" style="width:40px;height:40px" aria-hidden="true">
                    <i class="bi bi-people text-success"></i>
                </div>
                <div>
                    <div class="fs-4 fw-bold lh-1 text-success"><?= number_format($uniqueBenes) ?></div>
                    <div class="small text-muted mt-1">Beneficiaries Served</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body py-3 px-3 d-flex align-items-center gap-3">
                <div class="rounded-circle bg-warning bg-opacity-10 d-flex align-items-center justify-content-center flex-shrink-0" style="width:40px;height:40px" aria-hidden="true">
                    <i class="bi bi-box-seam text-warning"></i>
                </div>
                <div>
                    <div class="fs-4 fw-bold lh-1 text-warning"><?= number_format($totalQty, 1) ?></div>
                    <div class="small text-muted mt-1">Total Qty Dispensed</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body py-3 px-3 d-flex align-items-center gap-3">
                <div class="rounded-circle bg-info bg-opacity-10 d-flex align-items-center justify-content-center flex-shrink-0" style="width:40px;height:40px" aria-hidden="true">
                    <i class="bi bi-capsule text-info"></i>
                </div>
                <div>
                    <div class="fs-4 fw-bold lh-1 text-info"><?= count($summary) ?></div>
                    <div class="small text-muted mt-1">Supplement Types</div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Tab Navigation -->
<ul class="nav nav-tabs mb-0 border-bottom-0" id="dispTabs" role="tablist">
    <li class="nav-item" role="presentation">
        <button class="nav-link active fw-semibold" id="tab-records" data-bs-toggle="tab" data-bs-target="#pane-records"
                type="button" role="tab" aria-controls="pane-records" aria-selected="true">
            <i class="bi bi-list-ul me-1" aria-hidden="true"></i>Dispensing Records
            <span class="badge bg-secondary ms-1"><?= $totalRecords ?></span>
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link fw-semibold" id="tab-mns" data-bs-toggle="tab" data-bs-target="#pane-mns"
                type="button" role="tab" aria-controls="pane-mns" aria-selected="false">
            <i class="bi bi-capsule me-1" aria-hidden="true"></i>MNS Monthly Log
            <span class="badge bg-primary ms-1"><?= count($mnpMonthRecords) + count($lnsMonthRecords) ?></span>
        </button>
    </li>
</ul>

<div class="tab-content border border-top-0 rounded-bottom shadow-sm bg-surface mb-4">

    <!-- ── Tab 1: Dispensing Records ── -->
    <div class="tab-pane fade show active" id="pane-records" role="tabpanel" aria-labelledby="tab-records">

        <!-- Filters -->
        <div class="border-bottom px-3 py-3">
            <form method="get" class="row g-2 align-items-end">
                <div class="col-md-1">
                    <label class="form-label small mb-1 fw-semibold" for="f_year">Year</label>
                    <input type="number" id="f_year" name="year" class="form-control form-control-sm" value="<?= $year ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label small mb-1 fw-semibold" for="f_program">Program</label>
                    <select id="f_program" name="program" class="form-select form-select-sm">
                        <option value="">All Programs</option>
                        <?php foreach ($activePrograms as $p): ?>
                        <option value="<?= htmlspecialchars($p['code']) ?>" <?= $program === $p['code'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($p['code']) ?> — <?= htmlspecialchars($p['name']) ?>
                        </option>
                        <?php endforeach; ?>
                        <option value="General" <?= $program === 'General' ? 'selected' : '' ?>>General / Other</option>
                    </select>
                </div>
                <?php if (!$isBhw): ?>
                <div class="col-md-2">
                    <label class="form-label small mb-1 fw-semibold" for="f_barangay">Barangay</label>
                    <select id="f_barangay" name="barangay" class="form-select form-select-sm">
                        <option value="">All Barangays</option>
                        <?php foreach ($barangays as $b): ?>
                        <option value="<?= htmlspecialchars($b['barangay']) ?>" <?= $barangay === $b['barangay'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($b['barangay']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php endif; ?>
                <div class="col-md-3" style="position:relative;">
                    <label class="form-label small mb-1 fw-semibold" for="filterBeneSearch">Beneficiary</label>
                    <input type="hidden" name="beneficiary_id" id="filterBeneId" value="<?= $beneficiaryId ?: '' ?>">
                    <input type="text" id="filterBeneSearch" class="form-control form-control-sm"
                           placeholder="All beneficiaries…" autocomplete="off"
                           value="<?php
                               if ($beneficiaryId) {
                                   foreach ($allBeneficiaries as $ab) {
                                       if ($ab['id'] === $beneficiaryId) {
                                           echo htmlspecialchars($ab['last_name'] . ', ' . $ab['first_name'] . ' (' . $ab['barangay'] . ')');
                                           break;
                                       }
                                   }
                               }
                           ?>">
                    <div id="filterBeneDropdown" class="border rounded bg-white shadow-sm d-none"
                         style="position:absolute;z-index:200;left:0;right:0;max-height:180px;overflow-y:auto;top:100%;"></div>
                </div>
                <div class="col-auto d-flex gap-1">
                    <button type="submit" class="btn btn-sm btn-primary"><i class="bi bi-search me-1" aria-hidden="true"></i>Filter</button>
                    <a href="<?= APP_URL ?>/dispensing" class="btn btn-sm btn-outline-secondary">Clear</a>
                </div>
                <div class="col text-end d-flex gap-1 justify-content-end flex-wrap">
                    <a href="<?= APP_URL ?>/dispensing/export?year=<?= $year ?>&program=<?= urlencode($program) ?>&barangay=<?= urlencode($barangay) ?>&beneficiary_id=<?= $beneficiaryId ?>&format=excel"
                       class="btn btn-sm btn-outline-success">
                        <i class="bi bi-file-earmark-excel me-1" aria-hidden="true"></i>Excel
                    </a>
                    <a href="<?= APP_URL ?>/dispensing/export?year=<?= $year ?>&program=<?= urlencode($program) ?>&barangay=<?= urlencode($barangay) ?>&beneficiary_id=<?= $beneficiaryId ?>&format=pdf"
                       class="btn btn-sm btn-outline-danger">
                        <i class="bi bi-file-earmark-pdf me-1" aria-hidden="true"></i>PDF
                    </a>
                </div>
            </form>
        </div>

        <!-- Supplement Breakdown (collapsed by default) -->
        <?php if (!empty($summary)): ?>
        <div class="border-bottom px-3 py-2">
            <button class="btn btn-link btn-sm text-muted text-decoration-none p-0 fw-semibold collapsed"
                    type="button" data-bs-toggle="collapse" data-bs-target="#summaryCollapse"
                    aria-expanded="false" aria-controls="summaryCollapse">
                <i class="bi bi-bar-chart me-1" aria-hidden="true"></i>Breakdown by Supplement
                <i class="bi bi-chevron-down small ms-1 summary-chev" aria-hidden="true"></i>
            </button>
        </div>
        <div class="collapse" id="summaryCollapse">
            <div class="border-bottom">
                <div class="table-responsive">
                    <table class="table table-sm mb-0 align-middle">
                        <thead class="table-light">
                            <tr>
                                <th scope="col" class="ps-3">Supplement / Medicine</th>
                                <th scope="col">Records</th>
                                <th scope="col">Total Qty</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($summary as $s): ?>
                            <tr>
                                <td class="fw-semibold ps-3"><?= htmlspecialchars($s['supplement_type']) ?></td>
                                <td><span class="badge bg-secondary rounded-pill"><?= $s['cnt'] ?></span></td>
                                <td><?= number_format($s['total_qty'], 1) ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Records Table -->
        <?php if (empty($records)): ?>
        <div class="text-center py-5 text-muted">
            <i class="bi bi-inbox fs-1 d-block mb-2 opacity-25" aria-hidden="true"></i>
            <p class="mb-1">No dispensing records found.</p>
            <small>Adjust your filters or <a href="<?= APP_URL ?>/dispensing/create">record a new dispensing</a>.</small>
        </div>
        <?php else: ?>
        <div class="table-responsive">
            <table class="table table-sm table-hover mb-0 align-middle">
                <thead class="table-light">
                    <tr>
                        <th scope="col" class="ps-3">Date</th>
                        <th scope="col">Beneficiary</th>
                        <th scope="col">Barangay</th>
                        <th scope="col">Program</th>
                        <th scope="col">Supplement / Medicine</th>
                        <th scope="col">Qty</th>
                        <th scope="col">Unit</th>
                        <th scope="col">Dispensed By</th>
                        <th scope="col">Notes</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $pc = ['OPT'=>'success','DSP'=>'warning','MNS'=>'primary','General'=>'secondary'];
                    foreach ($activePrograms as $__p) $pc[$__p['code']] = $__p['color'] ?? 'secondary';
                    ?>
                    <?php foreach ($records as $r): ?>
                    <tr>
                        <td class="text-nowrap ps-3 text-muted small"><?= \DateHelper::formatDate($r['date_dispensed'], 'M j, Y') ?></td>
                        <td>
                            <a href="<?= APP_URL ?>/beneficiaries/<?= $r['beneficiary_id'] ?>" class="text-decoration-none fw-semibold">
                                <?= htmlspecialchars($r['last_name'] . ', ' . $r['first_name']) ?>
                            </a>
                        </td>
                        <td class="text-muted small"><?= htmlspecialchars($r['barangay']) ?></td>
                        <td>
                            <span class="badge bg-<?= $pc[$r['program']] ?? 'secondary' ?> bg-opacity-15 text-<?= $pc[$r['program']] ?? 'secondary' ?> border border-<?= $pc[$r['program']] ?? 'secondary' ?> border-opacity-25 fw-semibold">
                                <?= htmlspecialchars($r['program']) ?>
                            </span>
                        </td>
                        <td class="fw-semibold"><?= htmlspecialchars($r['supplement_type']) ?></td>
                        <td><?= number_format($r['quantity'], 1) ?></td>
                        <td class="text-muted small"><?= htmlspecialchars($r['unit']) ?></td>
                        <td class="text-muted small"><?= htmlspecialchars($r['dispensed_by_name'] ?? '—') ?></td>
                        <td class="text-muted small"><?= htmlspecialchars($r['notes'] ?? '') ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div><!-- /pane-records -->

    <!-- ── Tab 2: MNS Monthly Log ── -->
    <div class="tab-pane fade" id="pane-mns" role="tabpanel" aria-labelledby="tab-mns">
        <div class="p-3 border-bottom d-flex align-items-center gap-3 flex-wrap">
            <div>
                <span class="fw-semibold"><i class="bi bi-capsule me-1 text-primary" aria-hidden="true"></i>MNS Monthly Log</span>
                <span class="text-muted small ms-2">— MNP &amp; LNS-SQ given per beneficiary</span>
            </div>
            <form method="get" class="d-flex align-items-center gap-2 ms-auto mb-0">
                <input type="hidden" name="year" value="<?= $year ?>">
                <input type="hidden" name="barangay" value="<?= htmlspecialchars($barangay) ?>">
                <label class="form-label small mb-0 fw-semibold me-1" for="mns_month">Month</label>
                <select id="mns_month" name="month" class="form-select form-select-sm" style="width:auto" onchange="this.form.submit()">
                    <?php for ($m = 1; $m <= 12; $m++): ?>
                    <option value="<?= $m ?>" <?= $m === $selectedMonth ? 'selected' : '' ?>><?= $monthNames[$m] ?> <?= $year ?></option>
                    <?php endfor; ?>
                </select>
                <a href="<?= APP_URL ?>/reports/export?type=mns_monthly&format=excel&year=<?= $year ?>&month=<?= $selectedMonth ?>&barangay=<?= urlencode($barangay) ?>"
                   class="btn btn-sm btn-outline-success">
                    <i class="bi bi-file-earmark-excel me-1" aria-hidden="true"></i>Export
                </a>
            </form>
        </div>

        <div class="p-3">
            <div class="row g-3">
                <!-- MNP -->
                <div class="col-lg-6">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <span class="fw-semibold"><i class="bi bi-prescription me-1 text-primary" aria-hidden="true"></i>MNP</span>
                        <span class="badge bg-primary rounded-pill"><?= count($mnpMonthRecords) ?></span>
                        <span class="text-muted small">Micronutrient Powder (ages 6–59 months)</span>
                    </div>
                    <div class="border rounded overflow-hidden">
                        <div class="table-responsive" style="max-height:360px;overflow-y:auto;">
                            <table class="table table-sm table-hover mb-0 align-middle">
                                <thead class="table-light sticky-top">
                                    <tr>
                                        <th scope="col">Name</th>
                                        <th scope="col">Barangay</th>
                                        <th scope="col">Date</th>
                                        <th scope="col">Age Group</th>
                                        <th scope="col" class="text-center">Done</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($mnpMonthRecords)): ?>
                                    <tr><td colspan="5" class="text-center text-muted py-4">
                                        <i class="bi bi-inbox d-block fs-4 mb-1 opacity-25" aria-hidden="true"></i>
                                        No MNP records for <?= $monthNames[$selectedMonth] ?> <?= $year ?>.
                                    </td></tr>
                                    <?php endif; ?>
                                    <?php foreach ($mnpMonthRecords as $rec): ?>
                                    <tr>
                                        <td>
                                            <a href="<?= APP_URL ?>/beneficiaries/<?= $rec['beneficiary_id'] ?>" class="text-decoration-none fw-semibold">
                                                <?= htmlspecialchars($rec['last_name'] . ', ' . $rec['first_name']) ?>
                                            </a>
                                        </td>
                                        <td class="text-muted small"><?= htmlspecialchars($rec['barangay']) ?></td>
                                        <td class="text-nowrap small"><?= \DateHelper::formatDate($rec['date_given'], 'M j') ?></td>
                                        <td><span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25"><?= htmlspecialchars($rec['age_group']) ?></span></td>
                                        <td class="text-center">
                                            <?php if ($rec['completed_routine']): ?>
                                                <i class="bi bi-check-circle-fill text-success" aria-label="Completed"></i>
                                            <?php else: ?>
                                                <i class="bi bi-circle text-secondary opacity-50" aria-label="Incomplete"></i>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <!-- LNS-SQ -->
                <div class="col-lg-6">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <span class="fw-semibold"><i class="bi bi-prescription2 me-1 text-success" aria-hidden="true"></i>LNS-SQ</span>
                        <span class="badge bg-success rounded-pill"><?= count($lnsMonthRecords) ?></span>
                        <span class="text-muted small">Lipid-based Nutrient Supplement (ages 6–23 months)</span>
                    </div>
                    <div class="border rounded overflow-hidden">
                        <div class="table-responsive" style="max-height:360px;overflow-y:auto;">
                            <table class="table table-sm table-hover mb-0 align-middle">
                                <thead class="table-light sticky-top">
                                    <tr>
                                        <th scope="col">Name</th>
                                        <th scope="col">Barangay</th>
                                        <th scope="col">Date</th>
                                        <th scope="col">Age Group</th>
                                        <th scope="col" class="text-center">Done</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($lnsMonthRecords)): ?>
                                    <tr><td colspan="5" class="text-center text-muted py-4">
                                        <i class="bi bi-inbox d-block fs-4 mb-1 opacity-25" aria-hidden="true"></i>
                                        No LNS-SQ records for <?= $monthNames[$selectedMonth] ?> <?= $year ?>.
                                    </td></tr>
                                    <?php endif; ?>
                                    <?php foreach ($lnsMonthRecords as $rec): ?>
                                    <tr>
                                        <td>
                                            <a href="<?= APP_URL ?>/beneficiaries/<?= $rec['beneficiary_id'] ?>" class="text-decoration-none fw-semibold">
                                                <?= htmlspecialchars($rec['last_name'] . ', ' . $rec['first_name']) ?>
                                            </a>
                                        </td>
                                        <td class="text-muted small"><?= htmlspecialchars($rec['barangay']) ?></td>
                                        <td class="text-nowrap small"><?= \DateHelper::formatDate($rec['date_given'], 'M j') ?></td>
                                        <td><span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25"><?= htmlspecialchars($rec['age_group']) ?></span></td>
                                        <td class="text-center">
                                            <?php if ($rec['completed_routine']): ?>
                                                <i class="bi bi-check-circle-fill text-success" aria-label="Completed"></i>
                                            <?php else: ?>
                                                <i class="bi bi-circle text-secondary opacity-50" aria-label="Incomplete"></i>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div><!-- /pane-mns -->

</div><!-- /tab-content -->

<script>
const filterBeneSearch   = document.getElementById('filterBeneSearch');
const filterBeneDropdown = document.getElementById('filterBeneDropdown');
const filterBeneId       = document.getElementById('filterBeneId');

const filterBeneficiaries = <?= json_encode(array_map(fn($b) => [
    'id'   => $b['id'],
    'text' => $b['last_name'] . ', ' . $b['first_name'] . ' (' . $b['barangay'] . ')'
], $allBeneficiaries ?? [])) ?>;

if (filterBeneSearch) {
    filterBeneSearch.addEventListener('input', function () {
        const q = this.value.trim().toLowerCase();
        filterBeneDropdown.innerHTML = '';
        if (!q) {
            filterBeneId.value = '';
            filterBeneDropdown.classList.add('d-none');
            return;
        }
        const matches = filterBeneficiaries.filter(b => b.text.toLowerCase().includes(q)).slice(0, 30);
        if (!matches.length) {
            filterBeneDropdown.innerHTML = '<div class="px-3 py-2 text-muted small">No results found</div>';
        } else {
            matches.forEach(b => {
                const div = document.createElement('div');
                div.className = 'px-3 py-2 small border-bottom';
                div.style.cursor = 'pointer';
                div.textContent  = b.text;
                div.addEventListener('mouseenter', () => div.classList.add('bg-light'));
                div.addEventListener('mouseleave', () => div.classList.remove('bg-light'));
                div.addEventListener('click', () => {
                    filterBeneId.value     = b.id;
                    filterBeneSearch.value = b.text;
                    filterBeneDropdown.classList.add('d-none');
                });
                filterBeneDropdown.appendChild(div);
            });
        }
        filterBeneDropdown.classList.remove('d-none');
    });

    document.addEventListener('click', function (e) {
        if (!filterBeneSearch.contains(e.target) && !filterBeneDropdown.contains(e.target))
            filterBeneDropdown.classList.add('d-none');
    });
}

// Chevron rotate on summary collapse
const summaryChev = document.querySelector('.summary-chev');
const summaryEl   = document.getElementById('summaryCollapse');
if (summaryEl && summaryChev) {
    summaryEl.addEventListener('show.bs.collapse',  () => summaryChev.style.transform = 'rotate(180deg)');
    summaryEl.addEventListener('hide.bs.collapse',  () => summaryChev.style.transform = '');
}

// Restore active tab from URL hash
const hash = window.location.hash;
if (hash === '#mns') {
    const tabEl = document.getElementById('tab-mns');
    if (tabEl) bootstrap.Tab.getOrCreateInstance(tabEl).show();
}
</script>
