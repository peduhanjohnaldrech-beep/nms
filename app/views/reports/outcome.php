<?php $pageTitle = 'Program Outcome Report'; ?>

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

<div class="d-flex align-items-center mt-3 mb-2 no-print">
    <a href="<?= APP_URL ?>/reports" class="btn btn-sm btn-outline-secondary me-3" aria-label="Back to Reports">
        <i class="bi bi-arrow-left" aria-hidden="true"></i>
    </a>
    <div class="rounded-3 bg-secondary bg-opacity-10 d-flex align-items-center justify-content-center me-3 flex-shrink-0" style="width:48px;height:48px">
        <i class="bi bi-graph-up-arrow fs-4 text-secondary" aria-hidden="true"></i>
    </div>
    <div class="flex-grow-1">
        <h4 class="mb-0">Program Outcome Report</h4>
        <p class="text-muted small mb-0">DSP pre-enrollment vs discharge weight — shows whether the feeding program worked</p>
    </div>
    <button onclick="window.print()" class="btn btn-sm btn-outline-secondary">
        <i class="bi bi-printer me-1"></i>Print
    </button>
</div>

<!-- Print Header -->
<div class="d-none d-print-block text-center mb-3">
    <h5 class="mb-0 fw-bold">PROGRAM OUTCOME REPORT — DSP</h5>
    <div class="small">
        Year: <?= $year ?>
        <?php if ($barangay): ?> &nbsp;|&nbsp; Barangay: <?= htmlspecialchars($barangay) ?><?php endif; ?>
        &nbsp;|&nbsp; Printed: <?= date('F j, Y') ?>
    </div>
</div>

<div class="card border-0 shadow-sm mb-3 mt-3 no-print">
    <div class="card-body py-2">
        <form method="get" class="row g-2 align-items-end">
            <div class="col-md-2">
                <label for="out_year" class="form-label small mb-1">Year</label>
                <select id="out_year" name="year" class="form-select form-select-sm">
                    <?php for ($y = date('Y'); $y >= date('Y') - 4; $y--): ?>
                    <option value="<?= $y ?>" <?= $year == $y ? 'selected' : '' ?>><?= $y ?></option>
                    <?php endfor; ?>
                </select>
            </div>
            <?php if (!$isBhw): ?>
            <div class="col-md-3">
                <label for="out_barangay" class="form-label small mb-1">Barangay</label>
                <select id="out_barangay" name="barangay" class="form-select form-select-sm" onchange="this.form.submit()">
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
                <label for="out_purok" class="form-label small mb-1">Purok</label>
                <select id="out_purok" name="purok" class="form-select form-select-sm">
                    <option value="">All Puroks</option>
                    <?php foreach ($puroks ?? [] as $p): ?>
                    <option value="<?= htmlspecialchars($p) ?>" <?= ($purok ?? '') === $p ? 'selected' : '' ?>>
                        <?= htmlspecialchars($p) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php endif; ?>
            <div class="col-auto d-flex gap-1">
                <button class="btn btn-sm btn-primary"><i class="bi bi-funnel me-1" aria-hidden="true"></i>Filter</button>
                <a href="<?= APP_URL ?>/reports/outcome" class="btn btn-sm btn-outline-secondary" aria-label="Clear filters"><i class="bi bi-x-lg" aria-hidden="true"></i></a>
            </div>
        </form>
    </div>
</div>

<?php
$withData    = array_filter($rows, fn($r) => $r['pre_weight_kg'] && $r['post_weight_kg']);
$improved    = array_filter($withData, fn($r) => $r['post_weight_kg'] > $r['pre_weight_kg']);
$notImproved = array_filter($withData, fn($r) => $r['post_weight_kg'] <= $r['pre_weight_kg']);
$avgGain     = count($withData) > 0
    ? array_sum(array_map(fn($r) => $r['post_weight_kg'] - $r['pre_weight_kg'], $withData)) / count($withData)
    : 0;
$improvedPct = count($withData) > 0 ? round(count($improved) / count($withData) * 100) : 0;
?>

<!-- Results Banner -->
<div class="row g-2 mb-4">
    <div class="col-6 col-sm-3">
        <div class="card border-0 shadow-sm text-center py-3">
            <div class="fs-2 fw-bold text-primary"><?= count($rows) ?></div>
            <div class="text-muted small">Total Enrollees</div>
        </div>
    </div>
    <div class="col-6 col-sm-3">
        <div class="card border-0 shadow-sm text-center py-3">
            <div class="fs-2 fw-bold text-success"><?= count($improved) ?></div>
            <div class="text-muted small">Gained Weight</div>
        </div>
    </div>
    <div class="col-6 col-sm-3">
        <div class="card border-0 shadow-sm text-center py-3">
            <div class="fs-2 fw-bold text-info">+<?= number_format($avgGain, 2) ?> kg</div>
            <div class="text-muted small">Avg. Weight Gain</div>
        </div>
    </div>
    <div class="col-6 col-sm-3">
        <div class="card border-0 shadow-sm text-center py-3">
            <div class="fs-2 fw-bold text-<?= $improvedPct >= 70 ? 'success' : ($improvedPct >= 40 ? 'warning' : 'danger') ?>"><?= $improvedPct ?>%</div>
            <div class="text-muted small">Improvement Rate</div>
            <div class="progress mx-3 mt-1" style="height:5px;">
                <div class="progress-bar bg-<?= $improvedPct >= 70 ? 'success' : ($improvedPct >= 40 ? 'warning' : 'danger') ?>" style="width:<?= $improvedPct ?>%" role="progressbar" aria-valuenow="<?= $improvedPct ?>" aria-valuemin="0" aria-valuemax="100"></div>
            </div>
        </div>
    </div>
</div>

<?php if (!empty($withData)): ?>
<!-- Chart -->
<div class="card border-0 shadow-sm mb-3">
    <div class="card-header fw-semibold d-flex align-items-center justify-content-between">
        <span><i class="bi bi-bar-chart me-2 text-success" aria-hidden="true"></i>Pre vs Post Weight — DSP Enrollees (<?= $year ?>)</span>
        <span class="text-muted fw-normal small"><?= count($withData) ?> with complete weight data</span>
    </div>
    <div class="card-body"><canvas id="outcomeChart" height="70" role="img" aria-label="Bar chart comparing pre-enrollment and post-discharge weight for DSP enrollees in <?= $year ?>"></canvas></div>
</div>
<script>
(function(){
    const data = <?= json_encode(array_values(array_filter($rows, fn($r) => $r['pre_weight_kg'] && $r['post_weight_kg']))) ?>;
    const labels = data.map(r => r.last_name + ', ' + r.first_name.charAt(0) + '.');
    new Chart(document.getElementById('outcomeChart'), {
        type: 'bar',
        data: {
            labels,
            datasets: [
                { label: 'Pre-Weight (kg)',  data: data.map(r => r.pre_weight_kg),  backgroundColor: '#3b82f688', borderColor: '#3b82f6', borderWidth: 1, borderRadius: 3 },
                { label: 'Post-Weight (kg)', data: data.map(r => r.post_weight_kg), backgroundColor: '#22c55e88', borderColor: '#22c55e', borderWidth: 1, borderRadius: 3 },
            ],
        },
        options: {
            responsive: true,
            plugins: { legend: { position: 'top' } },
            scales: { y: { beginAtZero: false, ticks: { callback: v => v + ' kg' } } },
        },
    });
})();
</script>
<?php endif; ?>

<!-- Table -->
<div class="card border-0 shadow-sm">
    <div class="card-header fw-semibold d-flex justify-content-between align-items-center flex-wrap gap-2">
        <span>
            <i class="bi bi-table me-1 text-secondary" aria-hidden="true"></i>
            DSP Enrollees — Pre/Post Weight Comparison
            <span class="badge bg-secondary ms-1"><?= count($rows) ?></span>
            <span class="fw-normal small text-muted ms-1">— <?= $year ?></span>
        </span>
        <div class="d-flex gap-1 no-print">
            <a href="<?= APP_URL ?>/reports/export?type=dsp&format=excel&year=<?= $year ?>&barangay=<?= urlencode($barangay) ?>&purok=<?= urlencode($purok ?? '') ?>"
               class="btn btn-sm btn-success"><i class="bi bi-file-earmark-excel me-1" aria-hidden="true"></i>Excel</a>
        </div>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-sm table-hover mb-0 align-middle">
                <thead class="table-light">
                    <tr>
                        <th scope="col">Name</th>
                        <th scope="col">Barangay</th>
                        <th scope="col">Enrolled</th>
                        <th scope="col">Intervention</th>
                        <th scope="col" class="text-center">Pre-Weight<br><small class="fw-normal text-muted">(kg)</small></th>
                        <th scope="col" class="text-center">Post-Weight<br><small class="fw-normal text-muted">(kg)</small></th>
                        <th scope="col" class="text-center">Gain / Loss</th>
                        <th scope="col">Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($rows)): ?>
                    <tr><td colspan="8" class="text-center text-muted py-4">
                        <i class="bi bi-inbox fs-4 d-block mb-1" aria-hidden="true"></i>
                        No DSP records found.
                    </td></tr>
                    <?php endif; ?>
                    <?php foreach ($rows as $r): ?>
                    <?php
                    $diff = ($r['pre_weight_kg'] && $r['post_weight_kg'])
                          ? round($r['post_weight_kg'] - $r['pre_weight_kg'], 2)
                          : null;
                    $sc = ['Active'=>'success','Completed'=>'secondary','Dropped'=>'danger'];
                    ?>
                    <tr>
                        <td>
                            <a href="<?= APP_URL ?>/beneficiaries/<?= $r['beneficiary_id'] ?>" class="text-decoration-none fw-semibold">
                                <?= htmlspecialchars($r['last_name'] . ', ' . $r['first_name']) ?>
                            </a>
                        </td>
                        <td><?= htmlspecialchars($r['barangay']) ?></td>
                        <td class="small text-muted"><?= DateHelper::formatDate($r['enrollment_date'], 'M j, Y') ?></td>
                        <td><?= $r['intervention_type'] ? '<span class="badge bg-info text-dark">' . htmlspecialchars($r['intervention_type']) . '</span>' : '<span class="text-muted">—</span>' ?></td>
                        <td class="text-center"><?= $r['pre_weight_kg']  ? $r['pre_weight_kg']  . ' kg' : '<span class="text-muted">—</span>' ?></td>
                        <td class="text-center"><?= $r['post_weight_kg'] ? $r['post_weight_kg'] . ' kg' : '<span class="text-muted">—</span>' ?></td>
                        <td class="text-center fw-semibold">
                            <?php if ($diff !== null): ?>
                            <span class="<?= $diff > 0 ? 'text-success' : ($diff < 0 ? 'text-danger' : 'text-muted') ?>">
                                <?= ($diff > 0 ? '+' : '') . $diff ?> kg
                            </span>
                            <?php else: ?><span class="text-muted">—</span><?php endif; ?>
                        </td>
                        <td><span class="badge bg-<?= $sc[$r['status']] ?? 'secondary' ?>"><?= $r['status'] ?></span></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php if (!empty($rows)): ?>
    <div class="card-footer bg-transparent border-top text-muted" style="font-size:.78rem">
        <strong>Pre-Weight</strong> = weight at enrollment &nbsp;·&nbsp;
        <strong>Post-Weight</strong> = weight at discharge or latest weigh-in &nbsp;·&nbsp;
        <span class="text-success">Green</span> = weight gained &nbsp;·&nbsp;
        <span class="text-danger">Red</span> = weight lost &nbsp;·&nbsp;
        Improvement rate ≥70% is considered a successful program outcome
    </div>
    <?php endif; ?>
</div>
