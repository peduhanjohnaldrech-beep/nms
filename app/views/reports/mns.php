<?php
$tabLabel = match($tab) {
    'mnp'        => 'MNP (Micronutrient Powder)',
    'lns'        => 'LNS-SQ (Lipid-based Nutrient Supplement)',
    'monthly'    => 'Monthly Distribution Log',
    'indicators' => 'MNS Indicators',
    default      => 'Vitamin A Distribution',
};
$pageTitle = 'MNS Report — ' . $tabLabel;
$monthNames = ['','January','February','March','April','May','June','July','August','September','October','November','December'];
?>

<div class="d-flex align-items-center mt-3 mb-2">
    <a href="<?= APP_URL ?>/reports" class="btn btn-sm btn-outline-secondary me-3"><i class="bi bi-arrow-left"></i></a>
    <div class="rounded-3 bg-primary bg-opacity-10 d-flex align-items-center justify-content-center me-3 flex-shrink-0" style="width:48px;height:48px">
        <i class="bi bi-capsule fs-4 text-primary"></i>
    </div>
    <h4 class="mb-0">MNS Report — <?= $tabLabel ?></h4>
</div>

<div class="card border-0 shadow-sm mb-3">
    <div class="card-body py-2">
        <form method="get" class="row g-2 align-items-end">
            <input type="hidden" name="tab" value="<?= htmlspecialchars($tab) ?>">
            <div class="col-md-2">
                <label class="form-label small">Date From</label>
                <input type="date" name="date_from" class="form-control form-control-sm" value="<?= htmlspecialchars($dateFrom ?? '') ?>">
            </div>
            <div class="col-md-2">
                <label class="form-label small">Date To</label>
                <input type="date" name="date_to" class="form-control form-control-sm" value="<?= htmlspecialchars($dateTo ?? '') ?>">
            </div>
            <div class="col-md-1">
                <label class="form-label small">Year</label>
                <select name="year" class="form-select form-select-sm">
                    <?php for ($y = date('Y') + 1; $y >= 2020; $y--): ?>
                    <option value="<?= $y ?>" <?= $year === $y ? 'selected' : '' ?>><?= $y ?></option>
                    <?php endfor; ?>
                </select>
            </div>
            <?php if ($tab === 'vita'): ?>
            <div class="col-md-2">
                <label class="form-label small">Round</label>
                <select name="round" class="form-select form-select-sm">
                    <option value="">All Rounds</option>
                    <option value="February" <?= $round === 'February' ? 'selected' : '' ?>>February</option>
                    <option value="August"   <?= $round === 'August'   ? 'selected' : '' ?>>August</option>
                </select>
            </div>
            <?php endif; ?>
            <?php if (!$isBhw): ?>
            <div class="col-md-2">
                <label class="form-label small">Barangay</label>
                <select name="barangay" class="form-select form-select-sm">
                    <option value="">All Barangays</option>
                    <?php foreach ($barangays as $b): ?>
                    <option value="<?= htmlspecialchars($b['barangay']) ?>" <?= $barangay === $b['barangay'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($b['barangay']) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small">Purok</label>
                <select name="purok" class="form-select form-select-sm">
                    <option value="">All Puroks</option>
                    <?php foreach ($puroks ?? [] as $p): ?>
                    <option value="<?= htmlspecialchars($p) ?>" <?= ($purok ?? '') === $p ? 'selected' : '' ?>>
                        <?= htmlspecialchars($p) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php endif; ?>
            <?php if (!$isBhw): ?>
            <div class="col-md-2">
                <label class="form-label small">Source</label>
                <select name="source" class="form-select form-select-sm">
                    <option value="">All Sources</option>
                    <option value="Walk-in" <?= ($source ?? '') === 'Walk-in' ? 'selected' : '' ?>>Walk-in</option>
                    <option value="Mobile"  <?= ($source ?? '') === 'Mobile'  ? 'selected' : '' ?>>Mobile</option>
                    <option value="Excel"   <?= ($source ?? '') === 'Excel'   ? 'selected' : '' ?>>Excel Import</option>
                </select>
            </div>
            <?php endif; ?>
            <div class="col-auto d-flex gap-1">
                <button type="submit" class="btn btn-sm btn-primary"><i class="bi bi-search me-1"></i>Filter</button>
                <a href="<?= APP_URL ?>/reports/mns" class="btn btn-sm btn-outline-secondary" title="Clear filters"><i class="bi bi-x-lg"></i></a>
            </div>
            <?php if ($tab !== 'monthly'):
                $exportQs = 'type=' . $exportType . '&year=' . $year . '&period=' . urlencode($round) . '&barangay=' . urlencode($barangay);
                if (!empty($dateFrom)) $exportQs .= '&date_from=' . urlencode($dateFrom);
                if (!empty($dateTo))   $exportQs .= '&date_to='   . urlencode($dateTo);
            ?>
            <div class="col text-end d-flex gap-1 justify-content-end">
                <a href="<?= APP_URL ?>/reports/export?<?= $exportQs ?>&format=csv"   class="btn btn-sm btn-outline-success"><i class="bi bi-filetype-csv me-1"></i>CSV</a>
                <a href="<?= APP_URL ?>/reports/export?<?= $exportQs ?>&format=excel" class="btn btn-sm btn-success"><i class="bi bi-file-earmark-excel me-1"></i>Excel</a>
                <a href="<?= APP_URL ?>/reports/export?<?= $exportQs ?>&format=pdf"   class="btn btn-sm btn-danger"><i class="bi bi-file-earmark-pdf me-1"></i>PDF</a>
            </div>
            <?php endif; ?>
        </form>
    </div>
</div>

<!-- Tabs -->
<ul class="nav nav-tabs mb-3">
    <li class="nav-item">
        <a class="nav-link <?= $tab === 'vita' ? 'active' : '' ?>"
           href="?tab=vita&year=<?= $year ?>&barangay=<?= urlencode($barangay) ?>">
            <i class="bi bi-capsule me-1"></i>Vitamin A
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link <?= $tab === 'mnp' ? 'active' : '' ?>"
           href="?tab=mnp&year=<?= $year ?>&barangay=<?= urlencode($barangay) ?>">
            <i class="bi bi-prescription me-1"></i>MNP
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link <?= $tab === 'lns' ? 'active' : '' ?>"
           href="?tab=lns&year=<?= $year ?>&barangay=<?= urlencode($barangay) ?>">
            <i class="bi bi-prescription2 me-1"></i>LNS-SQ
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link <?= $tab === 'monthly' ? 'active' : '' ?>"
           href="?tab=monthly&year=<?= $year ?>&barangay=<?= urlencode($barangay) ?>">
            <i class="bi bi-calendar3 me-1"></i>Monthly Log
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link <?= $tab === 'indicators' ? 'active' : '' ?>"
           href="?tab=indicators&year=<?= $year ?>&barangay=<?= urlencode($barangay) ?>">
            <i class="bi bi-bar-chart-steps me-1"></i>Indicators
        </a>
    </li>
</ul>

<?php if ($tab === 'monthly'): ?>

<!-- Month filter -->
<div class="card border-0 shadow-sm mb-3">
    <div class="card-body py-2">
        <form method="get" class="row g-2 align-items-end">
            <input type="hidden" name="tab" value="monthly">
            <input type="hidden" name="year" value="<?= $year ?>">
            <?php if (!$isBhw): ?>
            <input type="hidden" name="barangay" value="<?= htmlspecialchars($barangay) ?>">
            <?php endif; ?>
            <div class="col-auto">
                <label class="form-label small">Month</label>
                <select name="month" class="form-select form-select-sm" onchange="this.form.submit()">
                    <?php for ($m = 1; $m <= 12; $m++): ?>
                    <option value="<?= $m ?>" <?= $m === $selectedMonth ? 'selected' : '' ?>><?= $monthNames[$m] ?></option>
                    <?php endfor; ?>
                </select>
            </div>
            <div class="col-auto">
                <a href="<?= APP_URL ?>/reports/export?type=mns_monthly&format=excel&year=<?= $year ?>&month=<?= $selectedMonth ?>&barangay=<?= urlencode($barangay) ?>"
                   class="btn btn-sm btn-success">
                    <i class="bi bi-file-earmark-excel me-1"></i>Export <?= $monthNames[$selectedMonth] ?>
                </a>
            </div>
        </form>
    </div>
</div>

<!-- Records for selected month -->
<div class="row g-3">
    <!-- MNP -->
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header fw-semibold">
                <i class="bi bi-prescription me-1 text-warning"></i>
                MNP — <?= $monthNames[$selectedMonth] ?> <?= $year ?>
                <span class="badge bg-secondary ms-1"><?= count($mnpMonthRecords) ?></span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-sm table-hover mb-0 align-middle">
                        <thead class="table-light">
                            <tr><th>Name</th><th>Barangay</th><th>Date</th><th>Age Group</th><th>Done</th></tr>
                        </thead>
                        <tbody>
                            <?php if (empty($mnpMonthRecords)): ?>
                            <tr><td colspan="5" class="text-center text-muted py-4">
                                <i class="bi bi-inbox fs-3 d-block mb-1"></i>
                                No MNP records for <?= $monthNames[$selectedMonth] ?> <?= $year ?>.
                            </td></tr>
                            <?php endif; ?>
                            <?php foreach ($mnpMonthRecords as $rec): ?>
                            <tr>
                                <td><a href="<?= APP_URL ?>/beneficiaries/<?= $rec['beneficiary_id'] ?>" class="text-decoration-none fw-semibold"><?= htmlspecialchars($rec['last_name'] . ', ' . $rec['first_name']) ?></a></td>
                                <td><?= htmlspecialchars($rec['barangay']) ?></td>
                                <td><?= DateHelper::formatDate($rec['date_given'], 'M j') ?></td>
                                <td><span class="badge bg-secondary"><?= htmlspecialchars($rec['age_group']) ?></span></td>
                                <td><?= $rec['completed_routine'] ? '<span class="badge bg-success">Yes</span>' : '<span class="badge bg-warning text-dark">No</span>' ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- LNS-SQ -->
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header fw-semibold">
                <i class="bi bi-prescription2 me-1 text-success"></i>
                LNS-SQ — <?= $monthNames[$selectedMonth] ?> <?= $year ?>
                <span class="badge bg-secondary ms-1"><?= count($lnsMonthRecords) ?></span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-sm table-hover mb-0 align-middle">
                        <thead class="table-light">
                            <tr><th>Name</th><th>Barangay</th><th>Date</th><th>Age Group</th><th>Done</th></tr>
                        </thead>
                        <tbody>
                            <?php if (empty($lnsMonthRecords)): ?>
                            <tr><td colspan="5" class="text-center text-muted py-4">
                                <i class="bi bi-inbox fs-3 d-block mb-1"></i>
                                No LNS-SQ records for <?= $monthNames[$selectedMonth] ?> <?= $year ?>.
                            </td></tr>
                            <?php endif; ?>
                            <?php foreach ($lnsMonthRecords as $rec): ?>
                            <tr>
                                <td><a href="<?= APP_URL ?>/beneficiaries/<?= $rec['beneficiary_id'] ?>" class="text-decoration-none fw-semibold"><?= htmlspecialchars($rec['last_name'] . ', ' . $rec['first_name']) ?></a></td>
                                <td><?= htmlspecialchars($rec['barangay']) ?></td>
                                <td><?= DateHelper::formatDate($rec['date_given'], 'M j') ?></td>
                                <td><span class="badge bg-secondary"><?= htmlspecialchars($rec['age_group']) ?></span></td>
                                <td><?= $rec['completed_routine'] ? '<span class="badge bg-success">Yes</span>' : '<span class="badge bg-warning text-dark">No</span>' ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php else: ?>

<div class="card border-0 shadow-sm">
    <div class="card-header fw-semibold">
        <?php if ($tab === 'vita'): ?>
            <i class="bi bi-capsule me-1 text-primary"></i>Vitamin A Distribution Records
        <?php elseif ($tab === 'mnp'): ?>
            <i class="bi bi-prescription me-1 text-warning"></i>MNP Records
        <?php else: ?>
            <i class="bi bi-prescription2 me-1 text-success"></i>LNS-SQ Records
        <?php endif; ?>
        <span class="badge bg-secondary ms-2"><?= count($rows) ?></span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <?php if ($tab === 'vita'): ?>
            <table class="table table-sm table-hover mb-0">
                <thead class="table-light">
                    <tr><th>Name</th><th>Barangay</th><th>DOB</th><th>Date Given</th><th>Round</th><th>Year</th><th>Dosage</th><th>Capsule</th></tr>
                </thead>
                <tbody>
                    <?php if (empty($rows)): ?>
                    <tr><td colspan="8" class="text-center text-muted py-3">No records found.</td></tr>
                    <?php endif; ?>
                    <?php foreach ($rows as $r): ?>
                    <tr>
                        <td><a href="<?= APP_URL ?>/beneficiaries/<?= $r['beneficiary_id'] ?>" class="text-decoration-none"><?= htmlspecialchars($r['last_name'] . ', ' . $r['first_name']) ?></a></td>
                        <td><?= htmlspecialchars($r['barangay']) ?></td>
                        <td><?= DateHelper::formatDate($r['date_of_birth'], 'M j, Y') ?></td>
                        <td><?= DateHelper::formatDate($r['distribution_date'], 'M j, Y') ?></td>
                        <td><?= htmlspecialchars($r['round']) ?></td>
                        <td><?= $r['year'] ?></td>
                        <td><?= number_format($r['dosage_iu']) ?> IU</td>
                        <td><span class="badge bg-<?= $r['capsule_color'] === 'Blue' ? 'primary' : 'danger' ?>"><?= $r['capsule_color'] ?></span></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php else: ?>
            <table class="table table-sm table-hover mb-0">
                <thead class="table-light">
                    <tr><th>Name</th><th>Barangay</th><th>DOB</th><th>Date Given</th><th>Year</th><th>Age Group</th><th>Routine Completed</th></tr>
                </thead>
                <tbody>
                    <?php if (empty($rows)): ?>
                    <tr><td colspan="7" class="text-center text-muted py-3">No records found.</td></tr>
                    <?php endif; ?>
                    <?php foreach ($rows as $r): ?>
                    <tr>
                        <td><a href="<?= APP_URL ?>/beneficiaries/<?= $r['beneficiary_id'] ?>" class="text-decoration-none"><?= htmlspecialchars($r['last_name'] . ', ' . $r['first_name']) ?></a></td>
                        <td><?= htmlspecialchars($r['barangay']) ?></td>
                        <td><?= DateHelper::formatDate($r['date_of_birth'], 'M j, Y') ?></td>
                        <td><?= DateHelper::formatDate($r['date_given'], 'M j, Y') ?></td>
                        <td><?= $r['year'] ?></td>
                        <td><span class="badge bg-secondary"><?= htmlspecialchars($r['age_group']) ?></span></td>
                        <td>
                            <?php if ($r['completed_routine']): ?>
                            <span class="badge bg-success">Yes</span>
                            <?php else: ?>
                            <span class="badge bg-warning text-dark">No</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php endif; ?>

<?php if ($tab === 'indicators'): ?>
<div class="alert alert-light border mb-3 py-2 px-3 small">
    <i class="bi bi-info-circle me-1 text-primary"></i>
    Coverage for <strong><?= $year ?></strong><?= $barangay ? ' — ' . htmlspecialchars($barangay) : ' — All Barangays' ?>.
    Denominator = validated beneficiaries in that age group as of today.
</div>

<div class="card border-0 shadow-sm">
    <div class="card-header fw-semibold d-flex align-items-center justify-content-between">
        <span><i class="bi bi-bar-chart-steps me-2 text-primary"></i>MNS Indicators — <?= $year ?>
        <?php if ($barangay): ?>
        <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 ms-2"><?= htmlspecialchars($barangay) ?></span>
        <?php endif; ?></span>
        <a href="?tab=indicators&year=<?= $year ?>&barangay=<?= urlencode($barangay) ?>&export=excel"
           class="btn btn-sm btn-success"><i class="bi bi-file-earmark-excel me-1"></i>Export Excel</a>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-sm table-hover mb-0 align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Indicator</th>
                        <th class="text-center" style="width:120px">Given / Completed</th>
                        <th class="text-center" style="width:120px">Eligible Pop.</th>
                        <th class="text-center" style="width:100px">Coverage</th>
                        <th style="width:160px">Progress</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($mnsIndicators as $ind):
                        $pct = $ind['eligible'] > 0 ? min(100, round($ind['given'] / $ind['eligible'] * 100, 1)) : 0;
                        $color = $pct >= 90 ? 'success' : ($pct >= 70 ? 'warning' : 'danger');
                    ?>
                    <tr>
                        <td class="fw-semibold"><?= htmlspecialchars($ind['label']) ?></td>
                        <td class="text-center fs-5 fw-bold text-primary"><?= $ind['given'] ?></td>
                        <td class="text-center text-muted"><?= $ind['eligible'] ?></td>
                        <td class="text-center">
                            <span class="badge bg-<?= $color ?> px-2 py-1"><?= $pct ?>%</span>
                        </td>
                        <td>
                            <div class="progress" style="height:10px" title="<?= $pct ?>% coverage">
                                <div class="progress-bar bg-<?= $color ?>" style="width:<?= $pct ?>%"
                                     role="progressbar" aria-valuenow="<?= $pct ?>" aria-valuemin="0" aria-valuemax="100"></div>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <div class="card-footer bg-transparent border-top text-muted small">
        <span class="badge bg-success me-1">≥90%</span> Good &nbsp;·&nbsp;
        <span class="badge bg-warning text-dark me-1">70–89%</span> Needs improvement &nbsp;·&nbsp;
        <span class="badge bg-danger me-1">&lt;70%</span> Critical
    </div>
</div>

<?php
if (!empty($_GET['export']) && $_GET['export'] === 'excel' && class_exists('\PhpOffice\PhpSpreadsheet\Spreadsheet')):
    $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet()->setTitle('MNS Indicators');
    $headers = ['Indicator', 'Given / Completed', 'Eligible Population', 'Coverage %'];
    foreach ($headers as $i => $h) {
        $cell = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($i+1) . '1';
        $sheet->setCellValue($cell, $h);
        $sheet->getStyle($cell)->getFont()->setBold(true);
        $sheet->getStyle($cell)->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setRGB('D6E4F0');
    }
    foreach ($mnsIndicators as $ri => $ind) {
        $pct = $ind['eligible'] > 0 ? round($ind['given'] / $ind['eligible'] * 100, 1) : 0;
        $row = $ri + 2;
        $sheet->setCellValue('A'.$row, $ind['label']);
        $sheet->setCellValue('B'.$row, $ind['given']);
        $sheet->setCellValue('C'.$row, $ind['eligible']);
        $sheet->setCellValue('D'.$row, $pct);
    }
    foreach (range(1,4) as $c) $sheet->getColumnDimensionByColumn($c)->setAutoSize(true);
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="MNS_Indicators_'.$year.'.xlsx"');
    (new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet))->save('php://output');
    exit;
endif;
?>
<?php endif; ?>
