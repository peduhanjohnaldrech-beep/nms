<?php $pageTitle = 'DSP Masterlist of Beneficiaries'; ?>

<style>
@media print {
    .no-print, nav, .sidebar, .nms-navbar, footer, .breadcrumb-area,
    .page-content > .d-flex:first-child { display: none !important; }
    .page-content { margin: 0 !important; padding: 0 !important; }
    .card { box-shadow: none !important; border: 1px solid #ccc !important; }
    body, body::before, body::after { background: none !important; }
    @page { size: landscape; margin: 10mm; }
    .table { font-size: 7.5pt !important; }
    th, td { padding: 2px 4px !important; }
    .badge { font-size: 7pt !important; padding: 1px 4px !important; }
}
.ml-header th { white-space: nowrap; font-size: 0.72rem; }
.ml-body  td  { font-size: 0.78rem; vertical-align: middle; }
.col-group { background: #f1f5f9; }
[data-theme="dark"] .col-group { background: var(--bg-surface-2); }
</style>

<!-- Page Header -->
<div class="d-flex justify-content-between align-items-center my-3 no-print">
    <div>
        <h4 class="mb-0"><i class="bi bi-journal-text me-2"></i>Masterlist of Beneficiaries</h4>
        <small class="text-muted">Dietary Supplementation Program — Official Beneficiary Registry</small>
    </div>
    <div class="d-flex gap-2">
        <button onclick="window.print()" class="btn btn-sm btn-outline-secondary">
            <i class="bi bi-printer me-1"></i>Print
        </button>
        <a href="<?= APP_URL ?>/programs/dsp" class="btn btn-sm btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i>Back to DSP
        </a>
    </div>
</div>

<!-- Filter -->
<form method="get" class="card border-0 shadow-sm mb-3 p-3 no-print">
    <div class="row g-2 align-items-end">
        <div class="col-md-4">
            <label class="form-label small fw-semibold mb-1">Barangay</label>
            <select name="barangay" class="form-select form-select-sm">
                <option value="">— All Barangays —</option>
                <?php foreach ($barangays as $b): ?>
                <option value="<?= htmlspecialchars($b['barangay'] ?? $b) ?>"
                    <?= $filterBarangay === ($b['barangay'] ?? $b) ? 'selected' : '' ?>>
                    <?= htmlspecialchars($b['barangay'] ?? $b) ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-2">
            <label class="form-label small fw-semibold mb-1">Cycle Year</label>
            <select name="year" class="form-select form-select-sm">
                <?php foreach ($years as $y): ?>
                <option value="<?= $y ?>" <?= $filterYear == $y ? 'selected' : '' ?>><?= $y ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-2">
            <label class="form-label small fw-semibold mb-1">Status</label>
            <select name="status" class="form-select form-select-sm">
                <option value="">— All Statuses —</option>
                <option value="Active"    <?= ($filterStatus ?? '') === 'Active'    ? 'selected' : '' ?>>Active</option>
                <option value="Completed" <?= ($filterStatus ?? '') === 'Completed' ? 'selected' : '' ?>>Graduated</option>
                <option value="Dropped"   <?= ($filterStatus ?? '') === 'Dropped'   ? 'selected' : '' ?>>Dropped</option>
            </select>
        </div>
        <div class="col-auto">
            <button type="submit" class="btn btn-sm btn-primary">
                <i class="bi bi-funnel me-1"></i>Filter
            </button>
        </div>
    </div>
</form>

<!-- Print Header (visible only when printing) -->
<div class="text-center mb-3 d-none d-print-block">
    <h5 class="mb-0 fw-bold">MASTERLIST OF BENEFICIARIES</h5>
    <div class="fw-semibold">Dietary Supplementation Program (DSP)</div>
    <div class="small">
        <?= $filterBarangay ? 'Barangay ' . htmlspecialchars($filterBarangay) : 'All Barangays' ?>
        &nbsp;|&nbsp; Cycle Year: <?= $filterYear ?>
        &nbsp;|&nbsp; Total Enrolled: <?= count($enrollments) ?>
        &nbsp;|&nbsp; Printed: <?= date('F j, Y') ?>
    </div>
</div>

<!-- Summary chips -->
<?php
$byStatus = array_count_values(array_column($enrollments, 'status'));
$active    = $byStatus['Active']    ?? 0;
$completed = $byStatus['Completed'] ?? 0;
$dropped   = $byStatus['Dropped']   ?? 0;
?>
<div class="d-flex gap-2 flex-wrap mb-3 no-print">
    <span class="badge bg-secondary fs-6 fw-normal">Total: <?= count($enrollments) ?></span>
    <span class="badge bg-success  fs-6 fw-normal">Active: <?= $active ?></span>
    <span class="badge bg-primary  fs-6 fw-normal">Graduated: <?= $completed ?></span>
    <span class="badge bg-danger   fs-6 fw-normal">Dropped: <?= $dropped ?></span>
</div>

<?php if (empty($enrollments)): ?>
<div class="card border-0 shadow-sm">
    <div class="card-body text-center text-muted py-5">
        <i class="bi bi-journal-x fs-2 d-block mb-2"></i>
        No DSP enrollments found for <?= $filterBarangay ? htmlspecialchars($filterBarangay) : 'any barangay' ?> in <?= $filterYear ?>.
    </div>
</div>
<?php else: ?>
<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-bordered table-sm mb-0 ml-body">
                <thead>
                    <!-- Column group headers -->
                    <tr class="ml-header">
                        <th rowspan="2" class="text-center col-group">#</th>
                        <th rowspan="2" class="col-group">Name</th>
                        <th rowspan="2" class="text-center col-group">Sex</th>
                        <th rowspan="2" class="text-center col-group">DOB</th>
                        <th rowspan="2" class="text-center col-group">Age<br><span class="fw-normal">(mos.)</span></th>
                        <th rowspan="2" class="col-group">Barangay / Purok</th>
                        <th rowspan="2" class="col-group">Intervention</th>
                        <th rowspan="2" class="text-center col-group">Enrolled</th>
                        <th colspan="4" class="text-center table-warning">Baseline (Pre-Enrollment)</th>
                        <th colspan="4" class="text-center table-info">Mid-Check (~3 mos.)</th>
                        <th colspan="4" class="text-center table-success">Endline / Exit</th>
                        <th rowspan="2" class="text-center">Wt.<br>Gain</th>
                        <th rowspan="2" class="text-center">Checks</th>
                        <th rowspan="2" class="text-center">Exit Status</th>
                        <th rowspan="2" class="text-center no-print">End Date</th>
                    </tr>
                    <tr class="ml-header">
                        <!-- Baseline -->
                        <th class="text-center table-warning">Date</th>
                        <th class="text-center table-warning">Wt (kg)</th>
                        <th class="text-center table-warning">MUAC</th>
                        <th class="text-center table-warning">Status</th>
                        <!-- Mid -->
                        <th class="text-center table-info">Date</th>
                        <th class="text-center table-info">Wt (kg)</th>
                        <th class="text-center table-info">MUAC</th>
                        <th class="text-center table-info">Status</th>
                        <!-- Endline -->
                        <th class="text-center table-success">Date</th>
                        <th class="text-center table-success">Wt (kg)</th>
                        <th class="text-center table-success">MUAC</th>
                        <th class="text-center table-success">Status</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($enrollments as $i => $e):
                    $baseline = $e['baseline'];
                    $midpoint = $e['midpoint'];
                    $endline  = $e['endline'];

                    // Weight gain: endline weight vs pre-enrollment weight
                    $preWt  = (float)($e['pre_weight_kg'] ?? 0);
                    $endWt  = $endline ? (float)($endline['weight_kg'] ?? 0) : (float)($e['post_weight_kg'] ?? 0);
                    $gain   = ($preWt > 0 && $endWt > 0) ? round($endWt - $preWt, 2) : null;

                    $statusClass = [
                        'SUW' => 'status-suw', 'UW' => 'status-uw', 'Normal' => 'status-normal',
                        'OW'  => 'status-ow',  'OB' => 'status-ob', 'SW' => 'status-sw', 'MW' => 'status-mw',
                    ];
                    $exitClass = match($e['status']) {
                        'Completed' => 'bg-success',
                        'Dropped'   => 'bg-danger',
                        default     => 'bg-secondary',
                    };
                    $rowClass = match($e['status']) {
                        'Completed' => '',
                        'Dropped'   => 'table-danger',
                        default     => '',
                    };
                ?>
                <tr class="<?= $rowClass ?>">
                    <td class="text-center text-muted"><?= $i + 1 ?></td>
                    <td class="fw-semibold text-nowrap">
                        <?= htmlspecialchars($e['last_name'] . ', ' . $e['first_name']) ?>
                        <?php if (!empty($e['middle_name'])): ?>
                        <span class="text-muted fw-normal"><?= htmlspecialchars(substr($e['middle_name'], 0, 1)) ?>.</span>
                        <?php endif; ?>
                    </td>
                    <td class="text-center"><?= htmlspecialchars($e['sex']) ?></td>
                    <td class="text-nowrap"><?= \DateHelper::formatDate($e['date_of_birth'], 'M d, Y') ?></td>
                    <td class="text-center"><?= $e['age_at_enrollment'] ?></td>
                    <td>
                        <?= htmlspecialchars($e['barangay']) ?>
                        <?php if (!empty($e['purok_zone'])): ?>
                        <br><span class="text-muted small"><?= htmlspecialchars($e['purok_zone']) ?></span>
                        <?php endif; ?>
                    </td>
                    <td><?= !empty($e['intervention_type']) ? htmlspecialchars($e['intervention_type']) : '<span class="text-muted">—</span>' ?></td>
                    <td class="text-nowrap"><?= \DateHelper::formatDate($e['enrollment_date'], 'M d, Y') ?></td>

                    <!-- Baseline -->
                    <td class="text-nowrap"><?= $baseline ? \DateHelper::formatDate($baseline['monitoring_date'], 'M d, Y') : '—' ?></td>
                    <td class="text-center"><?= $baseline ? $baseline['weight_kg'] : '—' ?></td>
                    <td class="text-center"><?= $baseline && $baseline['muac_cm'] ? $baseline['muac_cm'] : '—' ?></td>
                    <td class="text-center">
                        <?php if ($baseline && !empty($baseline['nutritional_status'])): ?>
                        <span class="badge <?= $statusClass[$baseline['nutritional_status']] ?? 'bg-secondary' ?>"><?= $baseline['nutritional_status'] ?></span>
                        <?php else: ?>—<?php endif; ?>
                    </td>

                    <!-- Mid-check -->
                    <td class="text-nowrap"><?= $midpoint ? \DateHelper::formatDate($midpoint['monitoring_date'], 'M d, Y') : '—' ?></td>
                    <td class="text-center"><?= $midpoint ? $midpoint['weight_kg'] : '—' ?></td>
                    <td class="text-center"><?= $midpoint && $midpoint['muac_cm'] ? $midpoint['muac_cm'] : '—' ?></td>
                    <td class="text-center">
                        <?php if ($midpoint && !empty($midpoint['nutritional_status'])): ?>
                        <span class="badge <?= $statusClass[$midpoint['nutritional_status']] ?? 'bg-secondary' ?>"><?= $midpoint['nutritional_status'] ?></span>
                        <?php else: ?>—<?php endif; ?>
                    </td>

                    <!-- Endline -->
                    <td class="text-nowrap"><?= $endline ? \DateHelper::formatDate($endline['monitoring_date'], 'M d, Y') : '—' ?></td>
                    <td class="text-center"><?= $endline ? $endline['weight_kg'] : '—' ?></td>
                    <td class="text-center"><?= $endline && $endline['muac_cm'] ? $endline['muac_cm'] : '—' ?></td>
                    <td class="text-center">
                        <?php if ($endline && !empty($endline['nutritional_status'])): ?>
                        <span class="badge <?= $statusClass[$endline['nutritional_status']] ?? 'bg-secondary' ?>"><?= $endline['nutritional_status'] ?></span>
                        <?php else: ?>—<?php endif; ?>
                    </td>

                    <!-- Weight gain -->
                    <td class="text-center fw-semibold <?= $gain !== null ? ($gain >= 0 ? 'text-success' : 'text-danger') : '' ?>">
                        <?= $gain !== null ? ($gain >= 0 ? '+' : '') . $gain . ' kg' : '—' ?>
                    </td>

                    <!-- Check count -->
                    <td class="text-center">
                        <span class="badge bg-secondary"><?= $e['check_count'] ?></span>
                    </td>

                    <!-- Exit status -->
                    <td class="text-center">
                        <span class="badge <?= $exitClass ?>"><?= htmlspecialchars($e['status']) ?></span>
                    </td>

                    <!-- End date (screen only) -->
                    <td class="text-nowrap no-print">
                        <?= !empty($e['end_date']) ? \DateHelper::formatDate($e['end_date'], 'M d, Y') : '<span class="text-muted">—</span>' ?>
                    </td>
                </tr>
                <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr class="table-light fw-semibold">
                        <td colspan="8" class="text-end">Total:</td>
                        <td colspan="12" class="text-muted small">
                            <?= count($enrollments) ?> enrolled
                            &nbsp;|&nbsp; <?= $active ?> active
                            &nbsp;|&nbsp; <?= $completed ?> graduated
                            &nbsp;|&nbsp; <?= $dropped ?> dropped
                        </td>
                        <td colspan="4"></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>

<!-- Signature block (print only) -->
<div class="d-none d-print-block mt-4">
    <div class="row">
        <div class="col-4 text-center">
            <div style="border-top:1px solid #000;margin-top:40px;padding-top:4px">
                <div class="fw-semibold small">Prepared by</div>
                <div class="small text-muted">BNS / CDW</div>
            </div>
        </div>
        <div class="col-4 text-center">
            <div style="border-top:1px solid #000;margin-top:40px;padding-top:4px">
                <div class="fw-semibold small">Verified by</div>
                <div class="small text-muted">Nutritionist-Dietitian</div>
            </div>
        </div>
        <div class="col-4 text-center">
            <div style="border-top:1px solid #000;margin-top:40px;padding-top:4px">
                <div class="fw-semibold small">Noted by</div>
                <div class="small text-muted">Local Chief Executive</div>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>
