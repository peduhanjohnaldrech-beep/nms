<?php $pageTitle = 'Dispensing Report'; ?>

<style>
@media print {
    .no-print { display: none !important; }
    .print-header { display: block !important; }
    body { font-size: 12px; }
    .card { border: 1px solid #dee2e6 !important; box-shadow: none !important; }
    .badge { border: 1px solid #ccc !important; color: #000 !important; background: #f8f9fa !important; }
}
.print-header { display: none; }
</style>

<!-- Print Header (hidden on screen, shown when printing) -->
<div class="print-header mb-3">
    <div class="text-center border-bottom pb-2 mb-2">
        <h5 class="fw-bold mb-0">Dispensing Summary Report</h5>
        <p class="small text-muted mb-0">
            Year: <?= $year ?>
            <?= $barangay ? " | Barangay: $barangay" : ' | All Barangays' ?>
            <?= $program  ? " | Program: $program"   : ' | All Programs' ?>
            &nbsp;|&nbsp; Printed: <?= date('F j, Y') ?>
        </p>
    </div>
</div>

<div class="d-flex align-items-center justify-content-between mt-3 mb-3 no-print">
    <div>
        <h4 class="mb-0 fw-bold"><i class="bi bi-prescription2 me-2 text-primary"></i>Dispensing Report</h4>
        <p class="text-muted small mb-0">Summary of medicines &amp; supplements given to beneficiaries</p>
    </div>
    <a href="<?= APP_URL ?>/reports" class="btn btn-sm btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i>Back to Reports
    </a>
</div>

<!-- Filters -->
<div class="card border-0 shadow-sm mb-3 no-print">
    <div class="card-body py-2 px-3">
        <form method="get" class="row g-2 align-items-end">
            <div class="col-auto">
                <label class="form-label small mb-1 fw-semibold">Year</label>
                <input type="number" name="year" class="form-control form-control-sm" value="<?= $year ?>" style="width:90px">
            </div>
            <div class="col-auto">
                <label class="form-label small mb-1 fw-semibold">Program</label>
                <select name="program" class="form-select form-select-sm" style="min-width:130px">
                    <option value="">All Programs</option>
                    <option value="MNS"     <?= $program === 'MNS'     ? 'selected' : '' ?>>MNS</option>
                    <option value="DSP"     <?= $program === 'DSP'     ? 'selected' : '' ?>>DSP</option>
                    <option value="General" <?= $program === 'General' ? 'selected' : '' ?>>General</option>
                </select>
            </div>
            <div class="col-auto">
                <label class="form-label small mb-1 fw-semibold">Barangay</label>
                <select name="barangay" class="form-select form-select-sm" style="min-width:150px">
                    <option value="">All Barangays</option>
                    <?php foreach ($barangays as $b): ?>
                    <option value="<?= htmlspecialchars($b['barangay']) ?>" <?= $barangay === $b['barangay'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($b['barangay']) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-auto d-flex gap-1">
                <button type="submit" class="btn btn-sm btn-primary"><i class="bi bi-search me-1"></i>Filter</button>
                <a href="<?= APP_URL ?>/reports/dispensing" class="btn btn-sm btn-outline-secondary">Clear</a>
            </div>
        </form>
    </div>
</div>

<!-- Summary Table -->
<?php if (empty($summary)): ?>
<div class="card border-0 shadow-sm">
    <div class="card-body text-center py-5 text-muted">
        <i class="bi bi-inbox fs-1 d-block mb-2 opacity-25"></i>
        No dispensing records found for <?= $year ?><?= $barangay ? " — $barangay" : '' ?>.
    </div>
</div>
<?php else: ?>
<div class="card border-0 shadow-sm">
    <div class="card-header py-2 px-3 d-flex justify-content-between align-items-center">
        <span class="fw-semibold small">
            <?= $year ?><?= $barangay ? " — $barangay" : '' ?>
            <span class="text-muted fw-normal ms-1">(<?= count($summary) ?> supplement types)</span>
        </span>
        <div class="d-flex gap-1 no-print">
            <a href="<?= APP_URL ?>/dispensing/export?year=<?= $year ?>&barangay=<?= urlencode($barangay) ?>&program=<?= urlencode($program) ?>&format=excel"
               class="btn btn-sm btn-outline-success">
                <i class="bi bi-file-earmark-excel me-1"></i>Excel
            </a>
            <button onclick="window.print()" class="btn btn-sm btn-outline-secondary">
                <i class="bi bi-printer me-1"></i>Print
            </button>
        </div>
    </div>
    <div class="table-responsive">
        <table class="table table-sm table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th class="ps-3">Program</th>
                    <th>Supplement / Medicine</th>
                    <th class="text-center">Records</th>
                    <th class="text-center">Beneficiaries</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $pc = ['MNS'=>'primary','DSP'=>'warning','General'=>'secondary','OPT'=>'success'];
                $currentProgram = null;
                $progTotals = [];
                foreach ($summary as $r) {
                    $progTotals[$r['program']]['records']       = ($progTotals[$r['program']]['records']       ?? 0) + $r['records'];
                    $progTotals[$r['program']]['beneficiaries'] = ($progTotals[$r['program']]['beneficiaries'] ?? 0) + $r['beneficiaries'];
                }
                ?>
                <?php foreach ($summary as $r): ?>
                <?php
                $color   = $pc[$r['program']] ?? 'secondary';
                $txtCls  = $color === 'warning' ? 'text-dark' : 'text-white';
                ?>
                <tr>
                    <td class="ps-3">
                        <span class="badge bg-<?= $color ?> <?= $txtCls ?>"><?= htmlspecialchars($r['program']) ?></span>
                    </td>
                    <td class="fw-semibold"><?= htmlspecialchars($r['supplement_type']) ?></td>
                    <td class="text-center">
                        <span class="badge bg-secondary rounded-pill"><?= number_format($r['records']) ?></span>
                    </td>
                    <td class="text-center text-muted"><?= number_format($r['beneficiaries']) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot class="table-light fw-semibold">
                <tr>
                    <td class="ps-3" colspan="2">Total</td>
                    <td class="text-center"><?= number_format(array_sum(array_column($summary, 'records'))) ?></td>
                    <td class="text-center text-muted"><?= number_format(array_sum(array_column($summary, 'beneficiaries'))) ?></td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>
<?php endif; ?>
