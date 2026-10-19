<?php $pageTitle = 'OPT Report'; ?>

<style>
@media print {
    .no-print, .nms-navbar, .sidebar, nav[aria-label="breadcrumb"] { display: none !important; }
    #wrapper { display: block !important; }
    .page-content { margin: 0 !important; padding: 0 !important; }
    .card { box-shadow: none !important; border: 1px solid #ccc !important; }
    .table { font-size: 8pt !important; }
    th, td { padding: 3px 5px !important; }
    @page { size: landscape; margin: 10mm; }
}
</style>

<?php
// Pre-compute status summary from rows
$statusCounts = ['SUW'=>0,'UW'=>0,'Normal'=>0,'OW'=>0,'OB'=>0];
$wflhCounts   = ['SW'=>0,'MW'=>0];
foreach ($rows as $r) {
    if (isset($statusCounts[$r['nutritional_status']])) $statusCounts[$r['nutritional_status']]++;
    if (!empty($r['wflh_status']) && isset($wflhCounts[$r['wflh_status']])) $wflhCounts[$r['wflh_status']]++;
}
$totalRows    = count($rows);
$malnourished = $statusCounts['SUW'] + $statusCounts['UW'];
?>

<!-- Page Header -->
<div class="d-flex align-items-center gap-3 mt-3 mb-4 no-print">
    <a href="<?= APP_URL ?>/reports" class="btn btn-sm btn-outline-secondary" aria-label="Back to Reports">
        <i class="bi bi-arrow-left" aria-hidden="true"></i>
    </a>
    <div class="rounded-3 bg-success bg-opacity-10 d-flex align-items-center justify-content-center flex-shrink-0" style="width:42px;height:42px">
        <i class="bi bi-clipboard-heart fs-5 text-success" aria-hidden="true"></i>
    </div>
    <div class="flex-grow-1">
        <h4 class="mb-0 fw-bold">OPT Report</h4>
        <p class="text-muted small mb-0">Operation Timbang Plus — child weight &amp; height assessments</p>
    </div>
    <button onclick="window.print()" class="btn btn-sm btn-outline-secondary">
        <i class="bi bi-printer me-1"></i>Print
    </button>
</div>

<!-- Print Header -->
<div class="d-none d-print-block text-center mb-3">
    <h5 class="mb-0 fw-bold">OPT REPORT — OPERATION TIMBANG PLUS</h5>
    <div class="small">
        Year: <?= $year ?>
        <?php if ($period): ?> &nbsp;|&nbsp; Period: <?= htmlspecialchars($period) ?><?php endif; ?>
        <?php if ($barangay): ?> &nbsp;|&nbsp; Barangay: <?= htmlspecialchars($barangay) ?><?php endif; ?>
        &nbsp;|&nbsp; Total Records: <?= $totalRows ?>
        &nbsp;|&nbsp; Printed: <?= date('F j, Y') ?>
    </div>
</div>

<!-- Filter Card -->
<div class="card border-0 shadow-sm mb-3 no-print">
    <div class="card-header fw-semibold py-2 px-3">
        <i class="bi bi-funnel me-1 text-primary" aria-hidden="true"></i>Filters
        <?php if ($barangay || $period || ($dateFrom && $dateTo)): ?>
        <span class="badge bg-primary ms-2 small">Active</span>
        <?php endif; ?>
    </div>
    <div class="card-body py-3">
        <form method="get" class="row g-2 align-items-end">
            <div class="col-6 col-md-1">
                <label class="form-label small fw-semibold mb-1" for="f_year">Year</label>
                <select id="f_year" name="year" class="form-select form-select-sm">
                    <?php for ($y = date('Y') + 1; $y >= 2020; $y--): ?>
                    <option value="<?= $y ?>" <?= $year === $y ? 'selected' : '' ?>><?= $y ?></option>
                    <?php endfor; ?>
                </select>
            </div>
            <div class="col-6 col-md-2">
                <label class="form-label small fw-semibold mb-1" for="f_period">Period</label>
                <select id="f_period" name="period" class="form-select form-select-sm">
                    <option value="">All Periods</option>
                    <option value="January" <?= $period === 'January' ? 'selected' : '' ?>>January</option>
                    <option value="July"    <?= $period === 'July'    ? 'selected' : '' ?>>July</option>
                </select>
            </div>
            <?php if (!$isBhw): ?>
            <div class="col-6 col-md-2">
                <label class="form-label small fw-semibold mb-1" for="f_barangay">Barangay</label>
                <select id="f_barangay" name="barangay" class="form-select form-select-sm">
                    <option value="">All Barangays</option>
                    <?php foreach ($barangays as $b): ?>
                    <option value="<?= htmlspecialchars($b['barangay']) ?>" <?= $barangay === $b['barangay'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($b['barangay']) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-6 col-md-2">
                <label class="form-label small fw-semibold mb-1" for="f_purok">Purok</label>
                <select id="f_purok" name="purok" class="form-select form-select-sm">
                    <option value="">All Puroks</option>
                    <?php foreach ($puroks ?? [] as $p): ?>
                    <option value="<?= htmlspecialchars($p) ?>" <?= ($purok ?? '') === $p ? 'selected' : '' ?>>
                        <?= htmlspecialchars($p) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php endif; ?>
            <div class="col-auto d-flex gap-1 flex-wrap">
                <button type="submit" class="btn btn-sm btn-primary">
                    <i class="bi bi-search me-1" aria-hidden="true"></i>Apply
                </button>
                <a href="<?= APP_URL ?>/reports/opt" class="btn btn-sm btn-outline-secondary" title="Clear all filters">
                    <i class="bi bi-x-lg" aria-hidden="true"></i> Clear
                </a>
            </div>
            <?php
            $exportQs = 'type=opt&year=' . $year . '&period=' . urlencode($period) . '&barangay=' . urlencode($barangay);
            ?>
            <div class="col-12 col-md text-md-end d-flex gap-1 flex-wrap justify-content-md-end mt-1 mt-md-0">
                <span class="text-muted small align-self-center me-1">Export:</span>
                <a href="<?= APP_URL ?>/reports/export?<?= $exportQs ?>&format=csv"   class="btn btn-sm btn-outline-secondary"><i class="bi bi-filetype-csv me-1" aria-hidden="true"></i>CSV</a>
                <a href="<?= APP_URL ?>/reports/export?<?= $exportQs ?>&format=excel" class="btn btn-sm btn-outline-success"><i class="bi bi-file-earmark-excel me-1" aria-hidden="true"></i>Excel</a>
                <a href="<?= APP_URL ?>/reports/export?<?= $exportQs ?>&format=pdf"   class="btn btn-sm btn-outline-danger"><i class="bi bi-file-earmark-pdf me-1" aria-hidden="true"></i>PDF</a>
                <a href="<?= APP_URL ?>/reports/export-eopt?year=<?= $year ?>&period=<?= urlencode($period) ?>&barangay=<?= urlencode($barangay) ?>" class="btn btn-sm btn-outline-warning"><i class="bi bi-file-earmark-spreadsheet me-1" aria-hidden="true"></i>eOPT</a>
            </div>
        </form>
    </div>
</div>

<!-- Results Summary Banner -->
<?php if ($totalRows > 0): ?>
<div class="row g-2 mb-3">
    <div class="col-6 col-sm-4 col-lg-2">
        <div class="card border-0 shadow-sm text-center py-2 px-1">
            <div class="fs-4 fw-bold text-primary"><?= $totalRows ?></div>
            <div class="small text-muted">Total Assessed</div>
        </div>
    </div>
    <div class="col-6 col-sm-4 col-lg-2">
        <div class="card border-0 shadow-sm text-center py-2 px-1 <?= $statusCounts['SUW'] > 0 ? 'border-danger border-2' : '' ?>">
            <div class="fs-4 fw-bold text-danger"><?= $statusCounts['SUW'] ?></div>
            <div class="small text-muted">Severely Underweight</div>
        </div>
    </div>
    <div class="col-6 col-sm-4 col-lg-2">
        <div class="card border-0 shadow-sm text-center py-2 px-1">
            <div class="fs-4 fw-bold text-warning"><?= $statusCounts['UW'] ?></div>
            <div class="small text-muted">Underweight</div>
        </div>
    </div>
    <div class="col-6 col-sm-4 col-lg-2">
        <div class="card border-0 shadow-sm text-center py-2 px-1">
            <div class="fs-4 fw-bold text-success"><?= $statusCounts['Normal'] ?></div>
            <div class="small text-muted">Normal</div>
        </div>
    </div>
    <div class="col-6 col-sm-4 col-lg-2">
        <div class="card border-0 shadow-sm text-center py-2 px-1 <?= ($wflhCounts['SW'] + $wflhCounts['MW']) > 0 ? 'border-warning border-2' : '' ?>">
            <div class="fs-4 fw-bold text-danger"><?= $wflhCounts['SW'] + $wflhCounts['MW'] ?></div>
            <div class="small text-muted">Wasted (WFL/H)</div>
        </div>
    </div>
    <div class="col-6 col-sm-4 col-lg-2">
        <div class="card border-0 shadow-sm text-center py-2 px-1">
            <div class="fs-4 fw-bold <?= $malnourished > 0 ? 'text-danger' : 'text-success' ?>">
                <?= $totalRows > 0 ? round($malnourished / $totalRows * 100, 1) : 0 ?>%
            </div>
            <div class="small text-muted">Malnut. Rate</div>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Active filter label -->
<?php if ($barangay || $period || ($dateFrom && $dateTo)): ?>
<div class="mb-3 d-flex align-items-center gap-2 flex-wrap">
    <span class="text-muted small">Showing:</span>
    <?php if ($period): ?>
    <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25"><?= $period ?> Period</span>
    <?php endif; ?>
    <?php if ($barangay): ?>
    <span class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary border-opacity-25"><?= htmlspecialchars($barangay) ?></span>
    <?php endif; ?>
    <?php if ($dateFrom && $dateTo): ?>
    <span class="badge bg-info bg-opacity-10 text-info border border-info border-opacity-25"><?= $dateFrom ?> → <?= $dateTo ?></span>
    <?php endif; ?>
    <a href="<?= APP_URL ?>/reports/opt" class="small text-muted"><i class="bi bi-x-circle me-1" aria-hidden="true"></i>Clear filters</a>
</div>
<?php endif; ?>

<!-- Age-Group Breakdown -->
<?php if (!empty($ageGroupStats)): ?>
<div class="card border-0 shadow-sm mb-3">
    <div class="card-header fw-semibold d-flex justify-content-between align-items-center py-2">
        <span><i class="bi bi-bar-chart me-2 text-primary" aria-hidden="true"></i>Breakdown by Age Group</span>
        <button class="btn btn-sm btn-outline-secondary" type="button"
                data-bs-toggle="collapse" data-bs-target="#ageGroupTable"
                aria-expanded="true" aria-controls="ageGroupTable">
            <i class="bi bi-chevron-up" id="ageChev" aria-hidden="true"></i>
        </button>
    </div>
    <div class="collapse show" id="ageGroupTable">
        <div class="card-body p-0">
            <?php
            $groups = $statuses = [];
            foreach ($ageGroupStats as $s) {
                $groups[$s['age_group']]               = true;
                $statuses[$s['nutritional_status']]    = true;
            }
            $groups   = array_keys($groups);
            $statuses = array_keys($statuses);
            $lookup   = [];
            foreach ($ageGroupStats as $s) {
                $lookup[$s['age_group']][$s['nutritional_status']] = (int)$s['cnt'];
            }
            $statusColors = ['SUW'=>'danger','UW'=>'warning','Normal'=>'success','OW'=>'info','OB'=>'secondary'];
            ?>
            <div class="table-responsive">
                <table class="table table-sm mb-0 text-center">
                    <thead class="table-light">
                        <tr>
                            <th scope="col" class="text-start">Age Group</th>
                            <?php foreach ($statuses as $st): ?>
                            <th scope="col"><span class="badge bg-<?= $statusColors[$st] ?? 'secondary' ?>"><?= $st ?></span></th>
                            <?php endforeach; ?>
                            <th scope="col">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($groups as $g): ?>
                        <?php $total = array_sum($lookup[$g] ?? []); ?>
                        <tr>
                            <td class="text-start fw-semibold"><?= htmlspecialchars($g) ?></td>
                            <?php foreach ($statuses as $st): ?>
                            <td><?= $lookup[$g][$st] ?? 0 ?></td>
                            <?php endforeach; ?>
                            <td class="fw-bold"><?= $total ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<script>
document.getElementById('ageGroupTable').addEventListener('show.bs.collapse',  () => document.getElementById('ageChev').className = 'bi bi-chevron-up');
document.getElementById('ageGroupTable').addEventListener('hide.bs.collapse', () => document.getElementById('ageChev').className = 'bi bi-chevron-down');
</script>
<?php endif; ?>

<!-- Results Table -->
<div class="card border-0 shadow-sm">
    <div class="card-header fw-semibold d-flex justify-content-between align-items-center py-2">
        <span><i class="bi bi-table me-1" aria-hidden="true"></i>Assessment Records</span>
        <span class="badge bg-secondary"><?= $totalRows ?> records</span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-sm table-hover mb-0 align-middle">
                <thead class="table-light">
                    <tr>
                        <th scope="col">Child Name</th>
                        <th scope="col">Barangay</th>
                        <th scope="col">Sex</th>
                        <th scope="col" title="Age in months">Age<br><small class="fw-normal text-muted">(months)</small></th>
                        <th scope="col" title="Weight in kilograms">Weight<br><small class="fw-normal text-muted">(kg)</small></th>
                        <th scope="col" title="Height in centimeters">Height<br><small class="fw-normal text-muted">(cm)</small></th>
                        <th scope="col" title="Weight-for-Age Z-score">WFA Z<br><small class="fw-normal text-muted">score</small></th>
                        <th scope="col" title="Weight-for-Age status">WFA<br><small class="fw-normal text-muted">status</small></th>
                        <th scope="col" title="Height-for-Age status">HFA<br><small class="fw-normal text-muted">status</small></th>
                        <th scope="col" title="Weight-for-Length/Height status">WFL/H<br><small class="fw-normal text-muted">status</small></th>
                        <th scope="col" title="Mid-Upper Arm Circumference">MUAC<br><small class="fw-normal text-muted">(cm)</small></th>
                        <th scope="col">Period</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($rows)): ?>
                    <tr>
                        <td colspan="12" class="text-center text-muted py-5">
                            <i class="bi bi-inbox fs-2 d-block mb-2 opacity-25" aria-hidden="true"></i>
                            No assessment records found for the selected filters.
                        </td>
                    </tr>
                    <?php endif; ?>
                    <?php foreach ($rows as $r): ?>
                    <tr>
                        <td>
                            <a href="<?= APP_URL ?>/beneficiaries/<?= $r['beneficiary_id'] ?>" class="text-decoration-none fw-semibold">
                                <?= htmlspecialchars($r['last_name'] . ', ' . $r['first_name']) ?>
                            </a>
                        </td>
                        <td class="text-muted small"><?= htmlspecialchars($r['barangay']) ?></td>
                        <td class="text-muted small"><?= $r['sex'] ?></td>
                        <td class="text-center"><?= $r['age_in_months'] ?></td>
                        <td class="text-center"><?= $r['weight_kg'] ?></td>
                        <td class="text-center"><?= $r['height_cm'] ?: '<span class="text-muted">—</span>' ?></td>
                        <td class="text-center text-muted small"><?= $r['weight_for_age_zscore'] !== null ? number_format($r['weight_for_age_zscore'], 2) : '<span class="text-muted">—</span>' ?></td>
                        <td><span class="badge status-<?= strtolower($r['nutritional_status']) ?>"><?= $r['nutritional_status'] ?></span></td>
                        <td><?= !empty($r['hfa_status'])  ? '<span class="badge status-' . strtolower($r['hfa_status'])  . '">' . $r['hfa_status']  . '</span>' : '<span class="text-muted">—</span>' ?></td>
                        <td><?= !empty($r['wflh_status']) ? '<span class="badge status-' . strtolower($r['wflh_status']) . '">' . $r['wflh_status'] . '</span>' : '<span class="text-muted">—</span>' ?></td>
                        <td class="text-center"><?= $r['muac_cm'] ?: '<span class="text-muted">—</span>' ?></td>
                        <td class="text-muted small text-nowrap"><?= $r['period'] . ' ' . $r['assessment_year'] ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php if (!empty($rows)): ?>
        <div class="px-3 py-2 border-top small text-muted d-flex gap-3 flex-wrap">
            <span><strong>WFA</strong> = Weight-for-Age</span>
            <span><strong>HFA</strong> = Height-for-Age</span>
            <span><strong>WFL/H</strong> = Weight-for-Length/Height</span>
            <span><strong>MUAC</strong> = Mid-Upper Arm Circumference</span>
            <span><strong>SUW</strong> = Severely Underweight &nbsp;|&nbsp; <strong>UW</strong> = Underweight &nbsp;|&nbsp; <strong>SW</strong> = Severely Wasted &nbsp;|&nbsp; <strong>MW</strong> = Moderately Wasted</span>
        </div>
        <?php endif; ?>
    </div>
</div>
