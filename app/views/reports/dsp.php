<?php $pageTitle = 'DSP Report'; ?>

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

<div class="d-flex align-items-center mt-3 mb-1 no-print">
    <a href="<?= APP_URL ?>/reports" class="btn btn-sm btn-outline-secondary me-3" aria-label="Back to Reports"><i class="bi bi-arrow-left" aria-hidden="true"></i></a>
    <div class="rounded-3 bg-warning bg-opacity-10 d-flex align-items-center justify-content-center me-3 flex-shrink-0" style="width:48px;height:48px">
        <i class="bi bi-egg-fried fs-4 text-warning" aria-hidden="true"></i>
    </div>
    <div class="flex-grow-1">
        <h4 class="mb-0">DSP Report</h4>
        <p class="text-muted small mb-0">Dietary Supplementation Program — enrollment and weight gain tracking</p>
    </div>
    <button onclick="window.print()" class="btn btn-sm btn-outline-secondary">
        <i class="bi bi-printer me-1"></i>Print
    </button>
</div>

<!-- Print Header -->
<div class="d-none d-print-block text-center mb-3">
    <h5 class="mb-0 fw-bold">DSP REPORT — DIETARY SUPPLEMENTATION PROGRAM</h5>
    <div class="small">
        Year: <?= $year ?>
        <?php if ($barangay): ?> &nbsp;|&nbsp; Barangay: <?= htmlspecialchars($barangay) ?><?php endif; ?>
        &nbsp;|&nbsp; Printed: <?= date('F j, Y') ?>
    </div>
</div>

<!-- Filters -->
<div class="card border-0 shadow-sm mb-3 mt-3 no-print">
    <div class="card-body py-2">
        <form method="get" class="row g-2 align-items-end">
            <div class="col-md-1">
                <label for="dsp_year" class="form-label small mb-1">Year</label>
                <select id="dsp_year" name="year" class="form-select form-select-sm">
                    <?php for ($y = date('Y') + 1; $y >= 2020; $y--): ?>
                    <option value="<?= $y ?>" <?= $year === $y ? 'selected' : '' ?>><?= $y ?></option>
                    <?php endfor; ?>
                </select>
            </div>
            <?php if (!$isBhw): ?>
            <div class="col-md-2">
                <label for="dsp_barangay" class="form-label small mb-1">Barangay</label>
                <select id="dsp_barangay" name="barangay" class="form-select form-select-sm">
                    <option value="">All Barangays</option>
                    <?php foreach ($barangays as $b): ?>
                    <option value="<?= htmlspecialchars($b['barangay']) ?>" <?= $barangay === $b['barangay'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($b['barangay']) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <label for="dsp_purok" class="form-label small mb-1">Purok</label>
                <select id="dsp_purok" name="purok" class="form-select form-select-sm">
                    <option value="">All Puroks</option>
                    <?php foreach ($puroks ?? [] as $p): ?>
                    <option value="<?= htmlspecialchars($p) ?>" <?= ($purok ?? '') === $p ? 'selected' : '' ?>>
                        <?= htmlspecialchars($p) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <label for="dsp_source" class="form-label small mb-1">Source</label>
                <select id="dsp_source" name="source" class="form-select form-select-sm">
                    <option value="">All Sources</option>
                    <option value="Walk-in" <?= ($source ?? '') === 'Walk-in' ? 'selected' : '' ?>>Walk-in</option>
                    <option value="Mobile"  <?= ($source ?? '') === 'Mobile'  ? 'selected' : '' ?>>Mobile</option>
                    <option value="Excel"   <?= ($source ?? '') === 'Excel'   ? 'selected' : '' ?>>Excel Import</option>
                </select>
            </div>
            <div class="col-md-2">
                <label for="dsp_status" class="form-label small mb-1">Status</label>
                <select id="dsp_status" name="status" class="form-select form-select-sm">
                    <option value="">All Statuses</option>
                    <option value="Active"    <?= ($status ?? '') === 'Active'    ? 'selected' : '' ?>>Active</option>
                    <option value="Completed" <?= ($status ?? '') === 'Completed' ? 'selected' : '' ?>>Graduated</option>
                    <option value="Dropped"   <?= ($status ?? '') === 'Dropped'   ? 'selected' : '' ?>>Dropped</option>
                </select>
            </div>
            <?php endif; ?>
            <div class="col-auto d-flex gap-1">
                <button type="submit" class="btn btn-sm btn-primary"><i class="bi bi-search me-1" aria-hidden="true"></i>Filter</button>
                <a href="<?= APP_URL ?>/reports/dsp" class="btn btn-sm btn-outline-secondary" title="Clear filters" aria-label="Clear filters"><i class="bi bi-x-lg" aria-hidden="true"></i></a>
            </div>
            <?php
            $exportQs = 'type=dsp&year=' . $year . '&barangay=' . urlencode($barangay);
            if (!empty($dateFrom)) $exportQs .= '&date_from=' . urlencode($dateFrom);
            if (!empty($dateTo))   $exportQs .= '&date_to='   . urlencode($dateTo);
            ?>
            <div class="col text-end d-flex gap-1 justify-content-end align-items-end">
                <span class="text-muted small me-1">Export:</span>
                <a href="<?= APP_URL ?>/reports/export?<?= $exportQs ?>&format=csv"   class="btn btn-sm btn-outline-success"><i class="bi bi-filetype-csv me-1" aria-hidden="true"></i>CSV</a>
                <a href="<?= APP_URL ?>/reports/export?<?= $exportQs ?>&format=excel" class="btn btn-sm btn-success"><i class="bi bi-file-earmark-excel me-1" aria-hidden="true"></i>Excel</a>
                <a href="<?= APP_URL ?>/reports/export?<?= $exportQs ?>&format=pdf"   class="btn btn-sm btn-danger"><i class="bi bi-file-earmark-pdf me-1" aria-hidden="true"></i>PDF</a>
            </div>
        </form>
    </div>
</div>

<?php
// Pre-compute summary stats
$active    = array_filter($rows, fn($r) => $r['status'] === 'Active');
$completed = array_filter($rows, fn($r) => $r['status'] === 'Completed');
$dropped   = array_filter($rows, fn($r) => $r['status'] === 'Dropped');
$withBoth  = array_filter($rows, fn($r) => $r['pre_weight_kg'] && $r['post_weight_kg']);
$improved  = array_filter($withBoth, fn($r) => $r['post_weight_kg'] > $r['pre_weight_kg']);
$avgGain   = count($withBoth) > 0
    ? array_sum(array_map(fn($r) => $r['post_weight_kg'] - $r['pre_weight_kg'], $withBoth)) / count($withBoth)
    : null;
?>

<!-- Results Summary Banner -->
<div class="row g-2 mb-3">
    <?php foreach ([
        ['Total Enrolled',  count($rows),       'primary',   'person-lines-fill'],
        ['Active',          count($active),      'success',   'check-circle-fill'],
        ['Completed',       count($completed),   'secondary', 'patch-check-fill'],
        ['Dropped',         count($dropped),     'danger',    'x-circle-fill'],
        ['Weight Improved', count($improved),    'info',      'arrow-up-circle'],
        ['Avg. Gain',       $avgGain !== null ? '+' . number_format($avgGain, 2) . ' kg' : '—', 'success', 'graph-up'],
    ] as [$label, $val, $color, $icon]): ?>
    <div class="col-6 col-sm-4 col-lg-2">
        <div class="card border-0 shadow-sm text-center py-2 px-1">
            <div class="fs-5 fw-bold text-<?= $color ?>"><?= $val ?></div>
            <div class="text-muted" style="font-size:.72rem"><?= $label ?></div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<!-- Records Table -->
<div class="card border-0 shadow-sm">
    <div class="card-header fw-semibold d-flex align-items-center gap-2">
        <i class="bi bi-table me-1 text-warning" aria-hidden="true"></i>
        DSP Records
        <span class="badge bg-secondary ms-1"><?= count($rows) ?></span>
        <span class="fw-normal small text-muted ms-1">— <?= $year ?><?= !empty($barangay) ? ' · ' . htmlspecialchars($barangay) : '' ?></span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-sm table-hover mb-0 align-middle">
                <thead class="table-light">
                    <tr>
                        <th scope="col">Name</th>
                        <th scope="col">Barangay</th>
                        <th scope="col">DOB</th>
                        <th scope="col">Intervention</th>
                        <th scope="col" class="text-center">Pre-Wt<br><small class="fw-normal text-muted">(kg)</small></th>
                        <th scope="col" class="text-center">Post-Wt<br><small class="fw-normal text-muted">(kg)</small></th>
                        <th scope="col" class="text-center">Gain/Loss</th>
                        <th scope="col">Enrolled</th>
                        <th scope="col">Status</th>
                        <th scope="col">End Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($rows)): ?>
                    <tr><td colspan="10" class="text-center text-muted py-4">
                        <i class="bi bi-inbox fs-4 d-block mb-1" aria-hidden="true"></i>
                        No DSP records found for the selected filters.
                    </td></tr>
                    <?php endif; ?>
                    <?php foreach ($rows as $r): ?>
                    <?php
                    $gain = (isset($r['pre_weight_kg'], $r['post_weight_kg']) && $r['pre_weight_kg'] > 0 && $r['post_weight_kg'] > 0)
                        ? round($r['post_weight_kg'] - $r['pre_weight_kg'], 2)
                        : null;
                    $sc = ['Active'=>'success','Completed'=>'secondary','Dropped'=>'danger'];
                    ?>
                    <tr>
                        <td><a href="<?= APP_URL ?>/beneficiaries/<?= $r['beneficiary_id'] ?>" class="text-decoration-none fw-semibold"><?= htmlspecialchars($r['last_name'] . ', ' . $r['first_name']) ?></a></td>
                        <td><?= htmlspecialchars($r['barangay']) ?></td>
                        <td class="small text-muted"><?= DateHelper::formatDate($r['date_of_birth'], 'M j, Y') ?></td>
                        <td><?= !empty($r['intervention_type']) ? '<span class="badge bg-info text-dark">' . htmlspecialchars($r['intervention_type']) . '</span>' : '<span class="text-muted">—</span>' ?></td>
                        <td class="text-center"><?= $r['pre_weight_kg'] ?? '<span class="text-muted">—</span>' ?></td>
                        <td class="text-center"><?= $r['post_weight_kg'] ?? '<span class="text-muted">—</span>' ?></td>
                        <td class="text-center fw-semibold">
                            <?php if ($gain !== null): ?>
                            <span class="<?= $gain > 0 ? 'text-success' : ($gain < 0 ? 'text-danger' : 'text-muted') ?>">
                                <?= ($gain > 0 ? '+' : '') . $gain ?> kg
                            </span>
                            <?php else: ?><span class="text-muted">—</span><?php endif; ?>
                        </td>
                        <td class="small"><?= DateHelper::formatDate($r['enrollment_date'], 'M j, Y') ?></td>
                        <td><span class="badge bg-<?= $sc[$r['status']] ?? 'secondary' ?>"><?= $r['status'] ?></span></td>
                        <td class="small text-muted"><?= $r['end_date'] ? DateHelper::formatDate($r['end_date'], 'M j, Y') : '—' ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php if (!empty($rows)): ?>
    <div class="card-footer bg-transparent border-top text-muted" style="font-size:.78rem">
        <strong>Status:</strong>
        <span class="badge bg-success me-1">Active</span> Currently enrolled &nbsp;·&nbsp;
        <span class="badge bg-secondary me-1">Completed</span> Graduated from DSP &nbsp;·&nbsp;
        <span class="badge bg-danger me-1">Dropped</span> Did not complete
    </div>
    <?php endif; ?>
</div>
