<?php $pageTitle = 'Dispensing Tracker'; ?>

<?php
$monthNames   = ['','January','February','March','April','May','June','July','August','September','October','November','December'];
$totalRecords = count($records);
$uniqueBenes  = count(array_unique(array_column($records, 'beneficiary_id')));
$mnsTotal     = count($mnpMonthRecords) + count($lnsMonthRecords) + count($vitaMonthRecords);

// Merge MNS monthly records into one list
$mnsLog = [];
foreach ($mnpMonthRecords  as $r) $mnsLog[] = array_merge($r, ['_type' => 'MNP',      '_color' => 'primary']);
foreach ($lnsMonthRecords  as $r) $mnsLog[] = array_merge($r, ['_type' => 'LNS-SQ',   '_color' => 'success']);
foreach ($vitaMonthRecords as $r) $mnsLog[] = array_merge($r, ['_type' => 'Vitamin A', '_color' => 'warning', 'age_group' => null]);
usort($mnsLog, fn($a, $b) => strcmp($b['date_given'] ?? $b['date_given'], $a['date_given'] ?? $a['date_given']));
?>

<!-- Page Header -->
<div class="d-flex justify-content-between align-items-center mt-3 mb-3">
    <div>
        <h4 class="mb-0 fw-bold"><i class="bi bi-prescription2 me-2 text-primary"></i>Dispensing Tracker</h4>
        <p class="text-muted small mb-0">Medicines &amp; supplements given to beneficiaries</p>
    </div>
    <?php if (hasPerm('dispensing')): ?>
    <a href="<?= APP_URL ?>/dispensing/create" class="btn btn-primary btn-sm">
        <i class="bi bi-plus-circle me-1"></i>Record Dispensing
    </a>
    <?php endif; ?>
</div>

<?php if (!empty($dbError)): ?>
<div class="alert alert-danger d-flex align-items-center gap-2 mb-3">
    <i class="bi bi-exclamation-triangle-fill flex-shrink-0"></i>
    <div><?= $dbError ?></div>
</div>
<?php endif; ?>

<!-- Stats -->
<div class="row g-2 mb-3">
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm">
            <div class="card-body py-2 px-3 d-flex align-items-center gap-2">
                <i class="bi bi-journal-text text-primary fs-5"></i>
                <div>
                    <div class="fw-bold text-primary"><?= number_format($totalRecords) ?></div>
                    <div class="small text-muted">Total Records</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm">
            <div class="card-body py-2 px-3 d-flex align-items-center gap-2">
                <i class="bi bi-people text-success fs-5"></i>
                <div>
                    <div class="fw-bold text-success"><?= number_format($uniqueBenes) ?></div>
                    <div class="small text-muted">Beneficiaries Served</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm">
            <div class="card-body py-2 px-3 d-flex align-items-center gap-2">
                <i class="bi bi-capsule text-warning fs-5"></i>
                <div>
                    <div class="fw-bold text-warning"><?= $mnsTotal ?></div>
                    <div class="small text-muted">MNS This Month</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm">
            <div class="card-body py-2 px-3 d-flex align-items-center gap-2">
                <i class="bi bi-box-seam text-info fs-5"></i>
                <div>
                    <div class="fw-bold text-info"><?= count($summary) ?></div>
                    <div class="small text-muted">Supplement Types</div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Tabs -->
<ul class="nav nav-tabs mb-0 border-bottom-0" id="dispTabs" role="tablist">
    <li class="nav-item">
        <button class="nav-link active fw-semibold" id="tab-records" data-bs-toggle="tab" data-bs-target="#pane-records" type="button">
            <i class="bi bi-list-ul me-1"></i>All Records
            <span class="badge bg-secondary ms-1"><?= $totalRecords ?></span>
        </button>
    </li>
    <li class="nav-item">
        <button class="nav-link fw-semibold" id="tab-mns" data-bs-toggle="tab" data-bs-target="#pane-mns" type="button">
            <i class="bi bi-capsule me-1"></i>MNS Monthly Log
            <span class="badge bg-primary ms-1"><?= $mnsTotal ?></span>
        </button>
    </li>
</ul>

<div class="tab-content border border-top-0 rounded-bottom shadow-sm bg-surface mb-4">

    <!-- ── Tab 1: All Records ── -->
    <div class="tab-pane fade show active" id="pane-records">

        <!-- Filters -->
        <div class="border-bottom px-3 py-2">
            <form method="get" class="row g-2 align-items-end">
                <div class="col-md-1">
                    <label class="form-label small mb-1 fw-semibold">Year</label>
                    <input type="number" name="year" class="form-control form-control-sm" value="<?= $year ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label small mb-1 fw-semibold">Program</label>
                    <select name="program" class="form-select form-select-sm">
                        <option value="">All Programs</option>
                        <?php foreach ($activePrograms as $p): ?>
                        <option value="<?= htmlspecialchars($p['code']) ?>" <?= $program === $p['code'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($p['code']) ?>
                        </option>
                        <?php endforeach; ?>
                        <option value="General" <?= $program === 'General' ? 'selected' : '' ?>>General</option>
                    </select>
                </div>
                <?php if (!$isBhw): ?>
                <div class="col-md-2">
                    <label class="form-label small mb-1 fw-semibold">Barangay</label>
                    <select name="barangay" class="form-select form-select-sm">
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
                    <label class="form-label small mb-1 fw-semibold">Beneficiary</label>
                    <input type="hidden" name="beneficiary_id" id="filterBeneId" value="<?= $beneficiaryId ?: '' ?>">
                    <input type="text" id="filterBeneSearch" class="form-control form-control-sm"
                           placeholder="Search name…" autocomplete="off"
                           value="<?php
                               if ($beneficiaryId) {
                                   foreach ($allBeneficiaries as $ab) {
                                       if ($ab['id'] === $beneficiaryId) {
                                           echo htmlspecialchars($ab['last_name'] . ', ' . $ab['first_name']);
                                           break;
                                       }
                                   }
                               }
                           ?>">
                    <div id="filterBeneDropdown" class="border rounded bg-white shadow-sm d-none"
                         style="position:absolute;z-index:200;left:0;right:0;max-height:180px;overflow-y:auto;top:100%;"></div>
                </div>
                <div class="col-auto d-flex gap-1">
                    <button type="submit" class="btn btn-sm btn-primary"><i class="bi bi-search me-1"></i>Filter</button>
                    <a href="<?= APP_URL ?>/dispensing" class="btn btn-sm btn-outline-secondary">Clear</a>
                </div>
                <div class="col text-end d-flex gap-1 justify-content-end">
                    <a href="<?= APP_URL ?>/dispensing/export?year=<?= $year ?>&program=<?= urlencode($program) ?>&barangay=<?= urlencode($barangay) ?>&beneficiary_id=<?= $beneficiaryId ?>&format=excel"
                       class="btn btn-sm btn-outline-success">
                        <i class="bi bi-file-earmark-excel me-1"></i>Excel
                    </a>
                    <a href="<?= APP_URL ?>/dispensing/export?year=<?= $year ?>&program=<?= urlencode($program) ?>&barangay=<?= urlencode($barangay) ?>&beneficiary_id=<?= $beneficiaryId ?>&format=pdf"
                       class="btn btn-sm btn-outline-danger">
                        <i class="bi bi-file-earmark-pdf me-1"></i>PDF
                    </a>
                </div>
            </form>
        </div>

        <!-- Records Table -->
        <?php if (empty($records)): ?>
        <div class="text-center py-5 text-muted">
            <i class="bi bi-inbox fs-1 d-block mb-2 opacity-25"></i>
            <p class="mb-1">No records found.</p>
            <small>Adjust your filters or <a href="<?= APP_URL ?>/dispensing/create">record a new dispensing</a>.</small>
        </div>
        <?php else: ?>
        <div class="table-responsive">
            <table class="table table-sm table-hover mb-0 align-middle">
                <thead class="table-light">
                    <tr>
                        <th class="ps-3">Date</th>
                        <th>Beneficiary</th>
                        <th>Barangay</th>
                        <th>Program</th>
                        <th>Supplement / Medicine</th>
                        <th>Qty</th>
                        <th>Unit</th>
                        <th>Dispensed By</th>
                        <th>Notes</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $pc = ['OPT'=>'success','DSP'=>'warning','MNS'=>'primary','General'=>'secondary']; ?>
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
                            <?php $color = $pc[$r['program']] ?? 'secondary';
                                  $textCls = $color === 'warning' ? 'text-dark' : 'text-white'; ?>
                            <span class="badge bg-<?= $color ?> <?= $textCls ?>"><?= htmlspecialchars($r['program']) ?></span>
                        </td>
                        <td class="fw-semibold"><?= htmlspecialchars($r['supplement_type']) ?></td>
                        <td><?= $r['program'] === 'DSP' ? '<span class="text-muted">—</span>' : ($r['quantity'] == (int)$r['quantity'] ? (int)$r['quantity'] : number_format($r['quantity'], 1)) ?></td>
                        <td class="text-muted small"><?= $r['program'] === 'DSP' ? '<span class="text-muted">—</span>' : htmlspecialchars($r['unit']) ?></td>
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
    <div class="tab-pane fade" id="pane-mns">

        <!-- Filters -->
        <div class="border-bottom px-3 py-2">
            <form method="get" action="<?= APP_URL ?>/dispensing#mns" class="row g-2 align-items-end">
                <input type="hidden" name="year" value="<?= $year ?>">
                <div class="col-auto">
                    <label class="form-label small mb-1 fw-semibold">Month</label>
                    <select name="month" class="form-select form-select-sm" style="width:auto">
                        <?php for ($m = 1; $m <= 12; $m++): ?>
                        <option value="<?= $m ?>" <?= $m === $selectedMonth ? 'selected' : '' ?>><?= $monthNames[$m] ?> <?= $year ?></option>
                        <?php endfor; ?>
                    </select>
                </div>
                <?php if (!$isBhw): ?>
                <div class="col-auto">
                    <label class="form-label small mb-1 fw-semibold">Barangay</label>
                    <select name="barangay" class="form-select form-select-sm" style="min-width:150px" onchange="this.form.submit()">
                        <option value="">All Barangays</option>
                        <?php foreach ($barangays as $b): ?>
                        <option value="<?= htmlspecialchars($b['barangay']) ?>" <?= $barangay === $b['barangay'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($b['barangay']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-auto">
                    <label class="form-label small mb-1 fw-semibold">Purok</label>
                    <select name="purok" class="form-select form-select-sm" style="min-width:120px">
                        <option value="">All Puroks</option>
                        <?php foreach ($puroks ?? [] as $p): ?>
                        <option value="<?= htmlspecialchars($p) ?>" <?= $purok === $p ? 'selected' : '' ?>><?= htmlspecialchars($p) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php endif; ?>
                <div class="col-auto d-flex gap-1">
                    <button type="submit" class="btn btn-sm btn-primary"><i class="bi bi-search me-1"></i>Filter</button>
                    <a href="<?= APP_URL ?>/dispensing#mns" class="btn btn-sm btn-outline-secondary">Clear</a>
                </div>
                <div class="col-auto ms-auto">
                    <a href="<?= APP_URL ?>/reports/export?type=mns_monthly&format=excel&year=<?= $year ?>&month=<?= $selectedMonth ?>&barangay=<?= urlencode($barangay) ?>&purok=<?= urlencode($purok) ?>"
                       class="btn btn-sm btn-outline-success">
                        <i class="bi bi-file-earmark-excel me-1"></i>Export
                    </a>
                </div>
            </form>
        </div>

        <!-- Summary badges -->
        <div class="px-3 pt-2 pb-1 d-flex gap-2 flex-wrap border-bottom">
            <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 px-2 py-1">
                MNP: <?= count($mnpMonthRecords) ?>
            </span>
            <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 px-2 py-1">
                LNS-SQ: <?= count($lnsMonthRecords) ?>
            </span>
            <span class="badge bg-warning bg-opacity-10 text-warning border border-warning border-opacity-25 px-2 py-1">
                Vitamin A: <?= count($vitaMonthRecords) ?>
            </span>
            <span class="text-muted small ms-1 align-self-center">
                <?= $monthNames[$selectedMonth] ?> <?= $year ?>
            </span>
        </div>

        <!-- Unified table -->
        <?php if (empty($mnsLog)): ?>
        <div class="text-center py-5 text-muted">
            <i class="bi bi-inbox fs-1 d-block mb-2 opacity-25"></i>
            <p class="mb-0">No MNS records for <?= $monthNames[$selectedMonth] ?> <?= $year ?>.</p>
        </div>
        <?php else: ?>
        <div class="table-responsive">
            <table class="table table-sm table-hover mb-0 align-middle">
                <thead class="table-light">
                    <tr>
                        <th class="ps-3">Name</th>
                        <th>Barangay</th>
                        <th>Type</th>
                        <th>Age Group</th>
                        <th>Date</th>
                        <th class="text-center">Done</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($mnsLog as $rec): ?>
                    <tr>
                        <td class="ps-3">
                            <a href="<?= APP_URL ?>/beneficiaries/<?= $rec['beneficiary_id'] ?>" class="text-decoration-none fw-semibold">
                                <?= htmlspecialchars($rec['last_name'] . ', ' . $rec['first_name']) ?>
                            </a>
                        </td>
                        <td class="text-muted small"><?= htmlspecialchars($rec['barangay']) ?></td>
                        <td>
                            <span class="badge bg-<?= $rec['_color'] ?> bg-opacity-10 text-<?= $rec['_color'] ?> border border-<?= $rec['_color'] ?> border-opacity-25">
                                <?= $rec['_type'] ?>
                            </span>
                        </td>
                        <td class="text-muted small">
                            <?= !empty($rec['age_group']) ? htmlspecialchars($rec['age_group']) : '—' ?>
                        </td>
                        <td class="text-nowrap small text-muted"><?= \DateHelper::formatDate($rec['date_given'], 'M j') ?></td>
                        <td class="text-center">
                            <?php if (isset($rec['completed_routine'])): ?>
                                <?php if ($rec['completed_routine']): ?>
                                    <i class="bi bi-check-circle-fill text-success"></i>
                                <?php else: ?>
                                    <i class="bi bi-circle text-secondary opacity-50"></i>
                                <?php endif; ?>
                            <?php else: ?>
                                <span class="text-muted">—</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
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
        if (!q) { filterBeneId.value = ''; filterBeneDropdown.classList.add('d-none'); return; }
        const matches = filterBeneficiaries.filter(b => b.text.toLowerCase().includes(q)).slice(0, 30);
        if (!matches.length) {
            filterBeneDropdown.innerHTML = '<div class="px-3 py-2 text-muted small">No results found</div>';
        } else {
            matches.forEach(b => {
                const div = document.createElement('div');
                div.className = 'px-3 py-2 small border-bottom';
                div.style.cursor = 'pointer';
                div.textContent = b.text;
                div.addEventListener('mouseenter', () => div.classList.add('bg-light'));
                div.addEventListener('mouseleave', () => div.classList.remove('bg-light'));
                div.addEventListener('click', () => {
                    filterBeneId.value = b.id;
                    filterBeneSearch.value = b.text;
                    filterBeneDropdown.classList.add('d-none');
                });
                filterBeneDropdown.appendChild(div);
            });
        }
        filterBeneDropdown.classList.remove('d-none');
    });
    document.addEventListener('click', e => {
        if (!filterBeneSearch.contains(e.target) && !filterBeneDropdown.contains(e.target))
            filterBeneDropdown.classList.add('d-none');
    });
}

// Restore MNS tab from URL hash
if (window.location.hash === '#mns') {
    const tabEl = document.getElementById('tab-mns');
    if (tabEl) bootstrap.Tab.getOrCreateInstance(tabEl).show();
}
document.querySelectorAll('#dispTabs button[data-bs-toggle="tab"]').forEach(btn => {
    btn.addEventListener('shown.bs.tab', e => {
        const target = e.target.getAttribute('data-bs-target');
        history.replaceState(null, '', target === '#pane-mns' ? '#mns' : location.pathname + location.search);
    });
});
</script>
