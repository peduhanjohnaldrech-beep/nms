<?php $pageTitle = 'Year-over-Year Comparison'; ?>

<div class="d-flex align-items-center mt-3 mb-2">
    <a href="<?= APP_URL ?>/reports" class="btn btn-sm btn-outline-secondary me-3" aria-label="Back to Reports"><i class="bi bi-arrow-left" aria-hidden="true"></i></a>
    <div class="rounded-3 bg-primary bg-opacity-10 d-flex align-items-center justify-content-center me-3 flex-shrink-0" style="width:48px;height:48px">
        <i class="bi bi-arrow-left-right fs-4 text-primary" aria-hidden="true"></i>
    </div>
    <div>
        <h4 class="mb-0">Year-over-Year Comparison</h4>
        <p class="text-muted small mb-0">Compare OPT (Q1) malnutrition rates between two years</p>
    </div>
</div>

<div class="card border-0 shadow-sm mb-3">
    <div class="card-body py-2">
        <form method="get" class="row g-2 align-items-end">
            <div class="col-md-2">
                <label for="cmp_year_a" class="form-label small mb-1">Year A (earlier)</label>
                <input type="number" id="cmp_year_a" name="year_a" class="form-control form-control-sm"
                       value="<?= $yearA ?>" min="2000" max="<?= date('Y') + 1 ?>">
            </div>
            <div class="col-md-2">
                <label for="cmp_year_b" class="form-label small mb-1">Year B (later)</label>
                <input type="number" id="cmp_year_b" name="year_b" class="form-control form-control-sm"
                       value="<?= $yearB ?>" min="2000" max="<?= date('Y') + 1 ?>">
            </div>
            <div class="col-md-3">
                <label for="cmp_barangay" class="form-label small mb-1">Barangay</label>
                <select id="cmp_barangay" name="barangay" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="">All Barangays</option>
                    <?php foreach ($barangays as $b): ?>
                    <option value="<?= htmlspecialchars($b['barangay']) ?>"
                        <?= $barangay === $b['barangay'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($b['barangay']) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <label for="cmp_purok" class="form-label small mb-1">Purok</label>
                <select id="cmp_purok" name="purok" class="form-select form-select-sm">
                    <option value="">All Puroks</option>
                    <?php foreach ($puroks ?? [] as $p): ?>
                    <option value="<?= htmlspecialchars($p) ?>" <?= ($purok ?? '') === $p ? 'selected' : '' ?>>
                        <?= htmlspecialchars($p) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-auto d-flex gap-1">
                <button type="submit" class="btn btn-sm btn-primary"><i class="bi bi-funnel me-1" aria-hidden="true"></i>Filter</button>
                <a href="<?= APP_URL ?>/reports/comparison" class="btn btn-sm btn-outline-secondary" aria-label="Clear filters"><i class="bi bi-x-lg" aria-hidden="true"></i></a>
            </div>
            <div class="col text-end d-flex gap-1 justify-content-end align-items-end">
                <span class="text-muted small me-1">Export:</span>
                <a href="?year_a=<?= $yearA ?>&year_b=<?= $yearB ?>&barangay=<?= urlencode($barangay) ?>&purok=<?= urlencode($purok ?? '') ?>&export=excel"
                   class="btn btn-sm btn-success"><i class="bi bi-file-earmark-excel me-1" aria-hidden="true"></i>Excel</a>
            </div>
        </form>
    </div>
</div>

<?php
// Excel export
if (!empty($_GET['export']) && $_GET['export'] === 'excel' && !empty($rows) && class_exists('\PhpOffice\PhpSpreadsheet\Spreadsheet')):
    $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet()->setTitle('Comparison');
    $headers = ['Barangay', $yearA.' Assessed', $yearA.' SUW', $yearA.' UW', $yearA.' Rate %', $yearB.' Assessed', $yearB.' SUW', $yearB.' UW', $yearB.' Rate %', 'Change %'];
    foreach ($headers as $i => $h) {
        $cell = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($i+1) . '1';
        $sheet->setCellValue($cell, $h);
        $sheet->getStyle($cell)->getFont()->setBold(true);
        $sheet->getStyle($cell)->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setRGB('D6E4F0');
    }
    foreach ($rows as $ri => $r) {
        $rowData = [
            $r['barangay'], $r['aTotal'], $r['a']['SUW'] ?? 0, $r['a']['UW'] ?? 0, $r['aRate'],
            $r['bTotal'], $r['b']['SUW'] ?? 0, $r['b']['UW'] ?? 0, $r['bRate'], $r['change'],
        ];
        foreach ($rowData as $ci => $val) {
            $sheet->setCellValue(\PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($ci+1) . ($ri+2), $val);
        }
    }
    foreach (range(1, count($headers)) as $c) $sheet->getColumnDimensionByColumn($c)->setAutoSize(true);
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header("Content-Disposition: attachment; filename=\"YoY_Comparison_{$yearA}_vs_{$yearB}.xlsx\"");
    (new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet))->save('php://output');
    exit;
endif;
?>

<div class="alert alert-light border mb-3 py-2 px-3 small">
    <i class="bi bi-info-circle me-1 text-primary" aria-hidden="true"></i>
    Comparing OPT Q1 results: <strong><?= $yearA ?></strong> vs <strong><?= $yearB ?></strong>.
    A <span class="text-success fw-semibold"><i class="bi bi-arrow-down-short" aria-hidden="true"></i>negative change</span> means malnutrition rate <strong>decreased</strong> (improved).
    A <span class="text-danger fw-semibold"><i class="bi bi-arrow-up-short" aria-hidden="true"></i>positive change</span> means it <strong>increased</strong> (worsened).
</div>

<?php if (empty($rows)): ?>
<div class="card border-0 shadow-sm">
    <div class="card-body text-center text-muted py-5">
        <i class="bi bi-clipboard2-x fs-3 d-block mb-2" aria-hidden="true"></i>
        No OPT data found for <?= $yearA ?> or <?= $yearB ?>. Make sure both years have January period assessments.
    </div>
</div>
<?php else:

$tATotal = array_sum(array_column($rows, 'aTotal'));
$tBTotal = array_sum(array_column($rows, 'bTotal'));
$tASuw   = array_sum(array_map(fn($r) => $r['a']['SUW'] ?? 0, $rows));
$tAUw    = array_sum(array_map(fn($r) => $r['a']['UW']  ?? 0, $rows));
$tBSuw   = array_sum(array_map(fn($r) => $r['b']['SUW'] ?? 0, $rows));
$tBUw    = array_sum(array_map(fn($r) => $r['b']['UW']  ?? 0, $rows));
$tARate  = $tATotal > 0 ? round(($tASuw + $tAUw) / $tATotal * 100, 1) : null;
$tBRate  = $tBTotal > 0 ? round(($tBSuw + $tBUw) / $tBTotal * 100, 1) : null;
$tChange = ($tARate !== null && $tBRate !== null) ? round($tBRate - $tARate, 1) : null;
?>

<!-- Summary Banner -->
<div class="row g-2 mb-3">
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm text-center py-2 border-start border-3 border-primary">
            <div class="text-muted" style="font-size:.72rem"><?= $yearA ?> Assessed</div>
            <div class="fs-4 fw-bold text-primary"><?= $tATotal ?></div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm text-center py-2 border-start border-3 border-success">
            <div class="text-muted" style="font-size:.72rem"><?= $yearB ?> Assessed</div>
            <div class="fs-4 fw-bold text-success"><?= $tBTotal ?></div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm text-center py-2">
            <div class="text-muted" style="font-size:.72rem"><?= $yearA ?> Malnut. Rate</div>
            <div class="fs-4 fw-bold <?= $tARate !== null ? 'text-' . ($tARate >= 30 ? 'danger' : ($tARate >= 10 ? 'warning' : 'success')) : 'text-muted' ?>">
                <?= $tARate !== null ? $tARate . '%' : '—' ?>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm text-center py-2 border-start border-3 <?= $tChange !== null ? ($tChange < 0 ? 'border-success' : ($tChange > 0 ? 'border-danger' : 'border-secondary')) : 'border-secondary' ?>">
            <div class="text-muted" style="font-size:.72rem">Overall Change</div>
            <div class="fs-4 fw-bold <?= $tChange !== null ? ($tChange < 0 ? 'text-success' : ($tChange > 0 ? 'text-danger' : 'text-muted')) : 'text-muted' ?>">
                <?php if ($tChange !== null): ?>
                <?= ($tChange > 0 ? '+' : '') . $tChange ?>%
                <?= $tChange < 0 ? '<i class="bi bi-arrow-down-short" aria-hidden="true"></i>' : ($tChange > 0 ? '<i class="bi bi-arrow-up-short" aria-hidden="true"></i>' : '') ?>
                <?php else: ?>—<?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Chart -->
<div class="card border-0 shadow-sm mb-3">
    <div class="card-header fw-semibold">
        <i class="bi bi-bar-chart me-2 text-primary" aria-hidden="true"></i>Malnutrition Rate: <?= $yearA ?> vs <?= $yearB ?>
    </div>
    <div class="card-body"><canvas id="comparisonChart" height="60" role="img" aria-label="Grouped bar chart comparing <?= $yearA ?> and <?= $yearB ?> malnutrition rates by barangay"></canvas></div>
</div>

<!-- Table -->
<div class="card border-0 shadow-sm">
    <div class="card-header fw-semibold">
        <i class="bi bi-table me-2" aria-hidden="true"></i>Detailed Comparison by Barangay
        <span class="badge bg-secondary ms-1"><?= count($rows) ?> barangays</span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-sm table-hover mb-0 align-middle">
                <thead class="table-light">
                    <tr>
                        <th scope="col" rowspan="2" class="align-middle">Barangay</th>
                        <th scope="col" colspan="4" class="text-center bg-primary bg-opacity-10 border-start"><?= $yearA ?></th>
                        <th scope="col" colspan="4" class="text-center bg-success bg-opacity-10 border-start"><?= $yearB ?></th>
                        <th scope="col" rowspan="2" class="align-middle text-center border-start">Change</th>
                    </tr>
                    <tr>
                        <th scope="col" class="text-center bg-primary bg-opacity-10 border-start">Assessed</th>
                        <th scope="col" class="text-center bg-primary bg-opacity-10 text-danger">SUW</th>
                        <th scope="col" class="text-center bg-primary bg-opacity-10 text-warning">UW</th>
                        <th scope="col" class="text-center bg-primary bg-opacity-10">Rate</th>
                        <th scope="col" class="text-center bg-success bg-opacity-10 border-start">Assessed</th>
                        <th scope="col" class="text-center bg-success bg-opacity-10 text-danger">SUW</th>
                        <th scope="col" class="text-center bg-success bg-opacity-10 text-warning">UW</th>
                        <th scope="col" class="text-center bg-success bg-opacity-10">Rate</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($rows as $r): ?>
                    <tr>
                        <td class="fw-semibold"><?= htmlspecialchars($r['barangay']) ?></td>
                        <td class="text-center border-start"><?= $r['aTotal'] ?: '<span class="text-muted">—</span>' ?></td>
                        <td class="text-center <?= ($r['a']['SUW'] ?? 0) > 0 ? 'text-danger fw-bold' : 'text-muted' ?>"><?= $r['a']['SUW'] ?? 0 ?></td>
                        <td class="text-center <?= ($r['a']['UW']  ?? 0) > 0 ? 'text-warning fw-semibold' : 'text-muted' ?>"><?= $r['a']['UW'] ?? 0 ?></td>
                        <td class="text-center">
                            <?php if ($r['aRate'] !== null): ?>
                            <span class="badge bg-<?= $r['aRate'] >= 30 ? 'danger' : ($r['aRate'] >= 10 ? 'warning text-dark' : 'success') ?>">
                                <?= $r['aRate'] ?>%
                            </span>
                            <?php else: ?><span class="text-muted small">N/A</span><?php endif; ?>
                        </td>
                        <td class="text-center border-start"><?= $r['bTotal'] ?: '<span class="text-muted">—</span>' ?></td>
                        <td class="text-center <?= ($r['b']['SUW'] ?? 0) > 0 ? 'text-danger fw-bold' : 'text-muted' ?>"><?= $r['b']['SUW'] ?? 0 ?></td>
                        <td class="text-center <?= ($r['b']['UW']  ?? 0) > 0 ? 'text-warning fw-semibold' : 'text-muted' ?>"><?= $r['b']['UW'] ?? 0 ?></td>
                        <td class="text-center">
                            <?php if ($r['bRate'] !== null): ?>
                            <span class="badge bg-<?= $r['bRate'] >= 30 ? 'danger' : ($r['bRate'] >= 10 ? 'warning text-dark' : 'success') ?>">
                                <?= $r['bRate'] ?>%
                            </span>
                            <?php else: ?><span class="text-muted small">N/A</span><?php endif; ?>
                        </td>
                        <td class="text-center border-start fw-bold">
                            <?php if ($r['change'] !== null): ?>
                            <span class="<?= $r['change'] < 0 ? 'text-success' : ($r['change'] > 0 ? 'text-danger' : 'text-muted') ?>">
                                <?= $r['change'] > 0 ? '+' : '' ?><?= $r['change'] ?>%
                                <?= $r['change'] < 0 ? '<i class="bi bi-arrow-down-short" aria-hidden="true"></i>' : ($r['change'] > 0 ? '<i class="bi bi-arrow-up-short" aria-hidden="true"></i>' : '') ?>
                            </span>
                            <?php else: ?>
                            <span class="text-muted small">N/A</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot class="table-secondary fw-semibold">
                    <tr>
                        <td>TOTAL</td>
                        <td class="text-center border-start"><?= $tATotal ?></td>
                        <td class="text-center text-danger"><?= $tASuw ?></td>
                        <td class="text-center text-warning"><?= $tAUw ?></td>
                        <td class="text-center"><?= $tARate !== null ? '<span class="badge bg-' . ($tARate >= 30 ? 'danger' : ($tARate >= 10 ? 'warning text-dark' : 'success')) . '">' . $tARate . '%</span>' : '—' ?></td>
                        <td class="text-center border-start"><?= $tBTotal ?></td>
                        <td class="text-center text-danger"><?= $tBSuw ?></td>
                        <td class="text-center text-warning"><?= $tBUw ?></td>
                        <td class="text-center"><?= $tBRate !== null ? '<span class="badge bg-' . ($tBRate >= 30 ? 'danger' : ($tBRate >= 10 ? 'warning text-dark' : 'success')) . '">' . $tBRate . '%</span>' : '—' ?></td>
                        <td class="text-center border-start fw-bold <?= $tChange !== null ? ($tChange < 0 ? 'text-success' : ($tChange > 0 ? 'text-danger' : 'text-muted')) : '' ?>">
                            <?= $tChange !== null ? ($tChange > 0 ? '+' : '') . $tChange . '%' : '—' ?>
                        </td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
    <div class="card-footer bg-transparent border-top text-muted" style="font-size:.78rem">
        <strong>SUW</strong> = Severely Underweight &nbsp;·&nbsp; <strong>UW</strong> = Underweight &nbsp;·&nbsp;
        <strong>Rate</strong> = (SUW + UW) ÷ Total Assessed &nbsp;·&nbsp;
        <span class="text-success">Green change</span> = malnutrition improved &nbsp;·&nbsp; <span class="text-danger">Red change</span> = malnutrition worsened
    </div>
</div>

<script>
(function() {
    const rows = <?= json_encode($rows) ?>;
    const labels = rows.map(r => r.barangay);
    const aRates = rows.map(r => r.aRate ?? 0);
    const bRates = rows.map(r => r.bRate ?? 0);

    new Chart(document.getElementById('comparisonChart'), {
        type: 'bar',
        data: {
            labels,
            datasets: [
                { label: '<?= $yearA ?>', data: aRates, backgroundColor: '#3b82f688', borderColor: '#3b82f6', borderWidth: 1, borderRadius: 3 },
                { label: '<?= $yearB ?>', data: bRates, backgroundColor: '#22c55e88', borderColor: '#22c55e', borderWidth: 1, borderRadius: 3 },
            ],
        },
        options: {
            responsive: true,
            plugins: { legend: { position: 'top' } },
            scales: { y: { beginAtZero: true, max: 100, ticks: { callback: v => v + '%' } } },
        },
    });
})();
</script>
<?php endif; ?>
