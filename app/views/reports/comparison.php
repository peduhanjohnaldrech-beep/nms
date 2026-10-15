<?php $pageTitle = 'Period Comparison'; ?>

<div class="d-flex align-items-center mt-3 mb-2">
    <a href="<?= APP_URL ?>/reports" class="btn btn-sm btn-outline-secondary me-3"><i class="bi bi-arrow-left"></i></a>
    <div class="rounded-3 bg-primary bg-opacity-10 d-flex align-items-center justify-content-center me-3 flex-shrink-0" style="width:48px;height:48px">
        <i class="bi bi-arrow-left-right fs-4 text-primary"></i>
    </div>
    <h4 class="mb-0">Period Comparison</h4>
</div>

<div class="card border-0 shadow-sm mb-3">
    <div class="card-body py-2">
        <form method="get" class="row g-2 align-items-end">
            <div class="col-md-2">
                <label class="form-label small mb-1">Year</label>
                <input type="number" name="year" class="form-control form-control-sm"
                       value="<?= $year ?>" min="2000" max="<?= date('Y') + 1 ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label small mb-1">Barangay</label>
                <select name="barangay" class="form-select form-select-sm" onchange="this.form.submit()">
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
                <label class="form-label small mb-1">Purok</label>
                <select name="purok" class="form-select form-select-sm">
                    <option value="">All Puroks</option>
                    <?php foreach ($puroks ?? [] as $p): ?>
                    <option value="<?= htmlspecialchars($p) ?>" <?= ($purok ?? '') === $p ? 'selected' : '' ?>>
                        <?= htmlspecialchars($p) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-auto d-flex gap-1">
                <button type="submit" class="btn btn-sm btn-primary"><i class="bi bi-funnel me-1"></i>Filter</button>
                <a href="<?= APP_URL ?>/reports/comparison" class="btn btn-sm btn-outline-secondary"><i class="bi bi-x-lg"></i></a>
                <a href="?year=<?= $year ?>&barangay=<?= urlencode($barangay) ?>&purok=<?= urlencode($purok ?? '') ?>&export=excel"
                   class="btn btn-sm btn-outline-success"><i class="bi bi-file-earmark-excel me-1"></i>Excel</a>
            </div>
        </form>
    </div>
</div>

<?php
// Excel export
if (!empty($_GET['export']) && $_GET['export'] === 'excel' && !empty($rows) && class_exists('\PhpOffice\PhpSpreadsheet\Spreadsheet')):
    $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet()->setTitle('Comparison');
    $headers = ['Barangay','Jan Assessed','Jan SUW','Jan UW','Jan Rate %','Jul Assessed','Jul SUW','Jul UW','Jul Rate %','Change %'];
    foreach ($headers as $i => $h) {
        $cell = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($i+1) . '1';
        $sheet->setCellValue($cell, $h);
        $sheet->getStyle($cell)->getFont()->setBold(true);
        $sheet->getStyle($cell)->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setRGB('D6E4F0');
    }
    foreach ($rows as $ri => $r) {
        $rowData = [
            $r['barangay'], $r['janTotal'], $r['jan']['SUW'] ?? 0, $r['jan']['UW'] ?? 0, $r['janRate'],
            $r['julTotal'], $r['jul']['SUW'] ?? 0, $r['jul']['UW'] ?? 0, $r['julRate'], $r['change'],
        ];
        foreach ($rowData as $ci => $val) {
            $sheet->setCellValue(\PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($ci+1) . ($ri+2), $val);
        }
    }
    foreach (range(1, count($headers)) as $c) $sheet->getColumnDimensionByColumn($c)->setAutoSize(true);
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header("Content-Disposition: attachment; filename=\"Period_Comparison_{$year}.xlsx\"");
    (new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet))->save('php://output');
    exit;
endif;
?>

<div class="alert alert-info border-0 shadow-sm small mb-3">
    <i class="bi bi-info-circle me-1"></i>
    Compares <strong>January</strong> (1st semester OPT) vs <strong>July</strong> (2nd semester OPT) for <?= $year ?>.
    A <span class="text-success fw-semibold">negative change</span> means malnutrition rate decreased (improved).
    A <span class="text-danger fw-semibold">positive change</span> means it increased (worsened).
</div>

<?php if (empty($rows)): ?>
<div class="card border-0 shadow-sm">
    <div class="card-body text-center text-muted py-5">
        <i class="bi bi-clipboard2-x fs-3 d-block mb-2"></i>
        No assessment data for <?= $year ?>. Both January and July periods must have data for comparison.
    </div>
</div>
<?php else: ?>

<!-- Chart -->
<div class="card border-0 shadow-sm mb-3">
    <div class="card-header fw-semibold">
        <i class="bi bi-bar-chart me-2 text-primary"></i>Malnutrition Rate: January vs July <?= $year ?>
    </div>
    <div class="card-body"><canvas id="comparisonChart" height="60"></canvas></div>
</div>

<!-- Table -->
<div class="card border-0 shadow-sm">
    <div class="card-header fw-semibold">
        <i class="bi bi-table me-2"></i>Detailed Comparison by Barangay
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-sm table-hover mb-0 align-middle">
                <thead class="table-light">
                    <tr>
                        <th rowspan="2" class="align-middle">Barangay</th>
                        <th colspan="4" class="text-center bg-primary bg-opacity-10 border-start">January (1st Sem)</th>
                        <th colspan="4" class="text-center bg-success bg-opacity-10 border-start">July (2nd Sem)</th>
                        <th rowspan="2" class="align-middle text-center border-start">Change</th>
                    </tr>
                    <tr>
                        <th class="text-center bg-primary bg-opacity-10 border-start">Assessed</th>
                        <th class="text-center bg-primary bg-opacity-10 text-danger">SUW</th>
                        <th class="text-center bg-primary bg-opacity-10 text-warning">UW</th>
                        <th class="text-center bg-primary bg-opacity-10">Rate</th>
                        <th class="text-center bg-success bg-opacity-10 border-start">Assessed</th>
                        <th class="text-center bg-success bg-opacity-10 text-danger">SUW</th>
                        <th class="text-center bg-success bg-opacity-10 text-warning">UW</th>
                        <th class="text-center bg-success bg-opacity-10">Rate</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($rows as $r): ?>
                    <tr>
                        <td class="fw-semibold"><?= htmlspecialchars($r['barangay']) ?></td>
                        <!-- January -->
                        <td class="text-center border-start"><?= $r['janTotal'] ?: '—' ?></td>
                        <td class="text-center <?= ($r['jan']['SUW'] ?? 0) > 0 ? 'text-danger fw-bold' : 'text-muted' ?>"><?= $r['jan']['SUW'] ?? 0 ?></td>
                        <td class="text-center <?= ($r['jan']['UW']  ?? 0) > 0 ? 'text-warning fw-semibold' : 'text-muted' ?>"><?= $r['jan']['UW'] ?? 0 ?></td>
                        <td class="text-center">
                            <?php if ($r['janRate'] !== null): ?>
                            <span class="badge bg-<?= $r['janRate'] >= 30 ? 'danger' : ($r['janRate'] >= 10 ? 'warning text-dark' : 'success') ?>">
                                <?= $r['janRate'] ?>%
                            </span>
                            <?php else: ?>—<?php endif; ?>
                        </td>
                        <!-- July -->
                        <td class="text-center border-start"><?= $r['julTotal'] ?: '—' ?></td>
                        <td class="text-center <?= ($r['jul']['SUW'] ?? 0) > 0 ? 'text-danger fw-bold' : 'text-muted' ?>"><?= $r['jul']['SUW'] ?? 0 ?></td>
                        <td class="text-center <?= ($r['jul']['UW']  ?? 0) > 0 ? 'text-warning fw-semibold' : 'text-muted' ?>"><?= $r['jul']['UW'] ?? 0 ?></td>
                        <td class="text-center">
                            <?php if ($r['julRate'] !== null): ?>
                            <span class="badge bg-<?= $r['julRate'] >= 30 ? 'danger' : ($r['julRate'] >= 10 ? 'warning text-dark' : 'success') ?>">
                                <?= $r['julRate'] ?>%
                            </span>
                            <?php else: ?>—<?php endif; ?>
                        </td>
                        <!-- Change -->
                        <td class="text-center border-start fw-bold">
                            <?php if ($r['change'] !== null): ?>
                            <span class="<?= $r['change'] < 0 ? 'text-success' : ($r['change'] > 0 ? 'text-danger' : 'text-muted') ?>">
                                <?= $r['change'] > 0 ? '+' : '' ?><?= $r['change'] ?>%
                                <?= $r['change'] < 0 ? '<i class="bi bi-arrow-down-short"></i>' : ($r['change'] > 0 ? '<i class="bi bi-arrow-up-short"></i>' : '') ?>
                            </span>
                            <?php else: ?>
                            <span class="text-muted small">N/A</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
                <?php if (!empty($rows)):
                    $tJanTotal = array_sum(array_column($rows, 'janTotal'));
                    $tJulTotal = array_sum(array_column($rows, 'julTotal'));
                    $tJanSuw   = array_sum(array_map(fn($r) => $r['jan']['SUW'] ?? 0, $rows));
                    $tJanUw    = array_sum(array_map(fn($r) => $r['jan']['UW']  ?? 0, $rows));
                    $tJulSuw   = array_sum(array_map(fn($r) => $r['jul']['SUW'] ?? 0, $rows));
                    $tJulUw    = array_sum(array_map(fn($r) => $r['jul']['UW']  ?? 0, $rows));
                    $tJanRate  = $tJanTotal > 0 ? round(($tJanSuw + $tJanUw) / $tJanTotal * 100, 1) : null;
                    $tJulRate  = $tJulTotal > 0 ? round(($tJulSuw + $tJulUw) / $tJulTotal * 100, 1) : null;
                    $tChange   = ($tJanRate !== null && $tJulRate !== null) ? round($tJulRate - $tJanRate, 1) : null;
                ?>
                <tfoot class="table-secondary fw-semibold">
                    <tr>
                        <td>TOTAL</td>
                        <td class="text-center border-start"><?= $tJanTotal ?></td>
                        <td class="text-center text-danger"><?= $tJanSuw ?></td>
                        <td class="text-center text-warning"><?= $tJanUw ?></td>
                        <td class="text-center"><?= $tJanRate !== null ? '<span class="badge bg-' . ($tJanRate >= 30 ? 'danger' : ($tJanRate >= 10 ? 'warning text-dark' : 'success')) . '">' . $tJanRate . '%</span>' : '—' ?></td>
                        <td class="text-center border-start"><?= $tJulTotal ?></td>
                        <td class="text-center text-danger"><?= $tJulSuw ?></td>
                        <td class="text-center text-warning"><?= $tJulUw ?></td>
                        <td class="text-center"><?= $tJulRate !== null ? '<span class="badge bg-' . ($tJulRate >= 30 ? 'danger' : ($tJulRate >= 10 ? 'warning text-dark' : 'success')) . '">' . $tJulRate . '%</span>' : '—' ?></td>
                        <td class="text-center border-start fw-bold <?= $tChange !== null ? ($tChange < 0 ? 'text-success' : ($tChange > 0 ? 'text-danger' : 'text-muted')) : '' ?>">
                            <?= $tChange !== null ? ($tChange > 0 ? '+' : '') . $tChange . '%' : '—' ?>
                        </td>
                    </tr>
                </tfoot>
                <?php endif; ?>
            </table>
        </div>
    </div>
</div>

<script>
(function() {
    const rows = <?= json_encode($rows) ?>;
    const labels = rows.map(r => r.barangay);
    const janRates = rows.map(r => r.janRate ?? 0);
    const julRates = rows.map(r => r.julRate ?? 0);

    new Chart(document.getElementById('comparisonChart'), {
        type: 'bar',
        data: {
            labels,
            datasets: [
                { label: 'January', data: janRates, backgroundColor: '#3b82f688', borderColor: '#3b82f6', borderWidth: 1, borderRadius: 3 },
                { label: 'July',    data: julRates, backgroundColor: '#22c55e88', borderColor: '#22c55e', borderWidth: 1, borderRadius: 3 },
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
