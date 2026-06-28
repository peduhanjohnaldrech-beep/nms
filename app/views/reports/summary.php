<?php $pageTitle = 'Summary Report'; ?>

<div class="d-flex align-items-center my-3">
    <a href="<?= APP_URL ?>/reports" class="btn btn-sm btn-outline-secondary me-3"><i class="bi bi-arrow-left"></i></a>
    <div class="rounded-3 bg-success bg-opacity-10 d-flex align-items-center justify-content-center me-3 flex-shrink-0" style="width:48px;height:48px">
        <i class="bi bi-file-earmark-bar-graph fs-4 text-success"></i>
    </div>
    <h4 class="mb-0">Summary Report</h4>
</div>

<!-- Filters -->
<div class="card border-0 shadow-sm mb-3">
    <div class="card-body py-2">
        <form method="get" class="row g-2 align-items-end">
            <div class="col-md-2">
                <label class="form-label small mb-1">Year</label>
                <input type="number" name="year" class="form-control form-control-sm"
                       value="<?= $year ?>" min="2000" max="<?= date('Y') + 1 ?>">
            </div>
            <div class="col-md-2">
                <label class="form-label small mb-1">Period</label>
                <select name="period" class="form-select form-select-sm">
                    <option value="">Both Periods</option>
                    <option value="January" <?= $period === 'January' ? 'selected' : '' ?>>January (1st Sem)</option>
                    <option value="July"    <?= $period === 'July'    ? 'selected' : '' ?>>July (2nd Sem)</option>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label small mb-1">Barangay</label>
                <select name="barangay" class="form-select form-select-sm">
                    <option value="">All Barangays</option>
                    <?php foreach ($barangays as $b): ?>
                    <option value="<?= htmlspecialchars($b['barangay']) ?>"
                        <?= $barangay === $b['barangay'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($b['barangay']) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-auto d-flex gap-1">
                <button type="submit" class="btn btn-sm btn-primary"><i class="bi bi-funnel me-1"></i>Filter</button>
                <a href="<?= APP_URL ?>/reports/summary" class="btn btn-sm btn-outline-secondary"><i class="bi bi-x-lg"></i></a>
                <a href="?year=<?= $year ?>&period=<?= urlencode($period) ?>&barangay=<?= urlencode($barangay) ?>&export=excel"
                   class="btn btn-sm btn-outline-success"><i class="bi bi-file-earmark-excel me-1"></i>Excel</a>
            </div>
        </form>
    </div>
</div>

<?php
// Export to Excel
if (!empty($_GET['export']) && $_GET['export'] === 'excel' && class_exists('\PhpOffice\PhpSpreadsheet\Spreadsheet')):
    $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet()->setTitle('Summary');
    $headers = ['Barangay','Total (0-59mo)','Assessed','Coverage %','SUW','UW','Normal','Malnourished','Malnut. Rate %','DSP Active','Vita-A Covered'];
    foreach ($headers as $i => $h) {
        $cell = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($i+1) . '1';
        $sheet->setCellValue($cell, $h);
        $sheet->getStyle($cell)->getFont()->setBold(true);
        $sheet->getStyle($cell)->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setRGB('D6E4F0');
    }
    foreach ($summaryRows as $ri => $r) {
        $rowData = [$r['barangay'],$r['total'],$r['assessed'],$r['coverage_pct'],$r['suw'],$r['uw'],$r['normal'],$r['malnourished'],$r['malnut_rate'],$r['dsp_active'],$r['vita_covered']];
        foreach ($rowData as $ci => $val) {
            $sheet->setCellValue(\PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($ci+1) . ($ri+2), $val);
        }
    }
    foreach (range(1, count($headers)) as $c) $sheet->getColumnDimensionByColumn($c)->setAutoSize(true);
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    $fn = "Summary_Report_{$year}" . ($period ? "_{$period}" : '') . '.xlsx';
    header("Content-Disposition: attachment; filename=\"$fn\"");
    (new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet))->save('php://output');
    exit;
endif;
?>

<!-- Totals Banner -->
<?php
$totals = ['total'=>0,'assessed'=>0,'suw'=>0,'uw'=>0,'normal'=>0,'dsp_active'=>0,'vita_covered'=>0];
foreach ($summaryRows as $r) foreach ($totals as $k => $_) $totals[$k] += $r[$k];
$totCoverage = $totals['total'] > 0 ? round($totals['assessed'] / $totals['total'] * 100, 1) : 0;
$totMalnut   = $totals['assessed'] > 0 ? round(($totals['suw'] + $totals['uw']) / $totals['assessed'] * 100, 1) : 0;
?>
<div class="row g-3 mb-3">
    <?php foreach ([
        ['Total Children', $totals['total'], 'primary', 'people-fill'],
        ['Assessed', $totals['assessed'], 'success', 'clipboard2-check'],
        ['Coverage', $totCoverage . '%', 'info', 'percent'],
        ['SUW', $totals['suw'], 'danger', 'exclamation-triangle-fill'],
        ['UW', $totals['uw'], 'warning', 'dash-circle'],
        ['Malnut. Rate', $totMalnut . '%', 'danger', 'heart-pulse'],
        ['DSP Active', $totals['dsp_active'], 'secondary', 'egg-fried'],
        ['Vita-A Covered', $totals['vita_covered'], 'success', 'capsule'],
    ] as [$label, $val, $color, $icon]): ?>
    <div class="col-6 col-sm-3 col-xl-auto flex-xl-fill">
        <div class="card border-0 shadow-sm text-center py-2">
            <div class="fs-4 fw-bold text-<?= $color ?>"><?= $val ?></div>
            <div class="small text-muted"><?= $label ?></div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<!-- Table -->
<div class="card border-0 shadow-sm">
    <div class="card-header bg-white fw-semibold d-flex justify-content-between align-items-center">
        <span><i class="bi bi-table me-2"></i>By Barangay
            <span class="text-muted fw-normal small ms-1"><?= $year ?><?= $period ? " — $period" : '' ?></span>
        </span>
        <span class="badge bg-secondary"><?= count($summaryRows) ?> barangays</span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-sm table-hover mb-0 align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Barangay</th>
                        <th class="text-center">Total<br><span class="fw-normal small text-muted">0–59 mo</span></th>
                        <th class="text-center">Assessed</th>
                        <th class="text-center">Coverage</th>
                        <th class="text-center text-danger">SUW</th>
                        <th class="text-center text-warning">UW</th>
                        <th class="text-center text-success">Normal</th>
                        <th class="text-center">Malnut. Rate</th>
                        <th class="text-center">DSP Active</th>
                        <th class="text-center">Vita-A</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($summaryRows)): ?>
                    <tr><td colspan="10" class="text-center text-muted py-4">No data for the selected filters.</td></tr>
                    <?php endif; ?>
                    <?php foreach ($summaryRows as $r): ?>
                    <tr>
                        <td class="fw-semibold"><?= htmlspecialchars($r['barangay']) ?></td>
                        <td class="text-center"><?= $r['total'] ?></td>
                        <td class="text-center"><?= $r['assessed'] ?></td>
                        <td class="text-center">
                            <div class="d-flex align-items-center gap-1">
                                <div class="progress flex-grow-1" style="height:6px;">
                                    <div class="progress-bar bg-<?= $r['coverage_pct'] >= 80 ? 'success' : ($r['coverage_pct'] >= 50 ? 'warning' : 'danger') ?>"
                                         style="width:<?= min(100, $r['coverage_pct']) ?>%"></div>
                                </div>
                                <small><?= $r['coverage_pct'] ?>%</small>
                            </div>
                        </td>
                        <td class="text-center <?= $r['suw'] > 0 ? 'text-danger fw-bold' : 'text-muted' ?>"><?= $r['suw'] ?></td>
                        <td class="text-center <?= $r['uw']  > 0 ? 'text-warning fw-semibold' : 'text-muted' ?>"><?= $r['uw'] ?></td>
                        <td class="text-center text-success"><?= $r['normal'] ?></td>
                        <td class="text-center">
                            <span class="badge bg-<?= $r['malnut_rate'] >= 30 ? 'danger' : ($r['malnut_rate'] >= 10 ? 'warning text-dark' : 'success') ?>">
                                <?= $r['malnut_rate'] ?>%
                            </span>
                        </td>
                        <td class="text-center"><?= $r['dsp_active'] ?></td>
                        <td class="text-center"><?= $r['vita_covered'] ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
                <?php if (!empty($summaryRows)): ?>
                <tfoot class="table-secondary fw-semibold">
                    <tr>
                        <td>TOTAL</td>
                        <td class="text-center"><?= $totals['total'] ?></td>
                        <td class="text-center"><?= $totals['assessed'] ?></td>
                        <td class="text-center"><?= $totCoverage ?>%</td>
                        <td class="text-center text-danger"><?= $totals['suw'] ?></td>
                        <td class="text-center text-warning"><?= $totals['uw'] ?></td>
                        <td class="text-center text-success"><?= $totals['normal'] ?></td>
                        <td class="text-center"><span class="badge bg-<?= $totMalnut >= 30 ? 'danger' : ($totMalnut >= 10 ? 'warning text-dark' : 'success') ?>"><?= $totMalnut ?>%</span></td>
                        <td class="text-center"><?= $totals['dsp_active'] ?></td>
                        <td class="text-center"><?= $totals['vita_covered'] ?></td>
                    </tr>
                </tfoot>
                <?php endif; ?>
            </table>
        </div>
    </div>
</div>
