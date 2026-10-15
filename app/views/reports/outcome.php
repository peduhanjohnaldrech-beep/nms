<?php $pageTitle = 'Program Outcome Report'; ?>

<div class="d-flex align-items-center mt-3 mb-2">
    <a href="<?= APP_URL ?>/reports" class="btn btn-sm btn-outline-secondary me-3">
        <i class="bi bi-arrow-left"></i>
    </a>
    <div class="rounded-3 bg-secondary bg-opacity-10 d-flex align-items-center justify-content-center me-3 flex-shrink-0" style="width:48px;height:48px">
        <i class="bi bi-graph-up-arrow fs-4 text-secondary"></i>
    </div>
    <h4 class="mb-0">Program Outcome Report (DSP)</h4>
</div>

<div class="card border-0 shadow-sm mb-3">
    <div class="card-body py-2">
        <form method="get" class="row g-2 align-items-end">
            <div class="col-md-2">
                <label class="form-label small mb-1">Year</label>
                <select name="year" class="form-select form-select-sm">
                    <?php for ($y = date('Y'); $y >= date('Y') - 4; $y--): ?>
                    <option value="<?= $y ?>" <?= $year == $y ? 'selected' : '' ?>><?= $y ?></option>
                    <?php endfor; ?>
                </select>
            </div>
            <?php if (!$isBhw): ?>
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
            <?php endif; ?>
            <div class="col-auto">
                <button class="btn btn-sm btn-primary">Filter</button>
            </div>
        </form>
    </div>
</div>

<?php
$withData   = array_filter($rows, fn($r) => $r['pre_weight_kg'] && $r['post_weight_kg']);
$improved   = array_filter($withData, fn($r) => $r['post_weight_kg'] > $r['pre_weight_kg']);
$notImproved= array_filter($withData, fn($r) => $r['post_weight_kg'] <= $r['pre_weight_kg']);
$avgGain    = count($withData) > 0
    ? array_sum(array_map(fn($r) => $r['post_weight_kg'] - $r['pre_weight_kg'], $withData)) / count($withData)
    : 0;
$improvedPct = count($withData) > 0 ? round(count($improved) / count($withData) * 100) : 0;
?>

<div class="row g-3 mb-4">
    <div class="col-sm-3">
        <div class="card border-0 shadow-sm text-center py-3">
            <div class="fs-2 fw-bold text-primary"><?= count($rows) ?></div>
            <div class="text-muted small">Total DSP Enrollees</div>
        </div>
    </div>
    <div class="col-sm-3">
        <div class="card border-0 shadow-sm text-center py-3">
            <div class="fs-2 fw-bold text-success"><?= count($improved) ?></div>
            <div class="text-muted small">Gained Weight</div>
        </div>
    </div>
    <div class="col-sm-3">
        <div class="card border-0 shadow-sm text-center py-3">
            <div class="fs-2 fw-bold text-info"><?= number_format($avgGain, 2) ?> kg</div>
            <div class="text-muted small">Avg. Weight Gain</div>
        </div>
    </div>
    <div class="col-sm-3">
        <div class="card border-0 shadow-sm text-center py-3">
            <div class="fs-2 fw-bold text-<?= $improvedPct >= 70 ? 'success' : ($improvedPct >= 40 ? 'warning' : 'danger') ?>"><?= $improvedPct ?>%</div>
            <div class="text-muted small">Improvement Rate</div>
            <div class="progress mx-3 mt-1" style="height:5px;">
                <div class="progress-bar bg-<?= $improvedPct >= 70 ? 'success' : ($improvedPct >= 40 ? 'warning' : 'danger') ?>" style="width:<?= $improvedPct ?>%"></div>
            </div>
        </div>
    </div>
</div>

<?php if (!empty($withData)): ?>
<div class="card border-0 shadow-sm mb-3">
    <div class="card-header fw-semibold">
        <i class="bi bi-bar-chart me-2 text-success"></i>Pre vs Post Weight — DSP Enrollees (<?= $year ?>)
    </div>
    <div class="card-body"><canvas id="outcomeChart" height="70"></canvas></div>
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

<div class="card border-0 shadow-sm">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span class="fw-semibold">DSP Enrollees — Pre/Post Weight Comparison (<?= $year ?>)</span>
        <a href="<?= APP_URL ?>/reports/export?type=dsp&format=excel&year=<?= $year ?>&barangay=<?= urlencode($barangay) ?>&purok=<?= urlencode($purok ?? '') ?>"
           class="btn btn-sm btn-success">
            <i class="bi bi-file-earmark-excel me-1"></i>Export Excel
        </a>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-sm table-hover mb-0 align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Name</th>
                        <th>Barangay</th>
                        <th>Enrolled</th>
                        <th>Intervention</th>
                        <th>Pre-Weight</th>
                        <th>Post-Weight</th>
                        <th>Gain/Loss</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($rows)): ?>
                    <tr><td colspan="8" class="text-center text-muted py-4">No DSP records found.</td></tr>
                    <?php endif; ?>
                    <?php foreach ($rows as $r): ?>
                    <?php
                    $diff = ($r['pre_weight_kg'] && $r['post_weight_kg'])
                          ? round($r['post_weight_kg'] - $r['pre_weight_kg'], 2)
                          : null;
                    ?>
                    <tr>
                        <td>
                            <a href="<?= APP_URL ?>/beneficiaries/<?= $r['beneficiary_id'] ?>">
                                <?= htmlspecialchars($r['last_name'] . ', ' . $r['first_name']) ?>
                            </a>
                        </td>
                        <td><?= htmlspecialchars($r['barangay']) ?></td>
                        <td class="small"><?= DateHelper::formatDate($r['enrollment_date'], 'M j, Y') ?></td>
                        <td><?= $r['intervention_type'] ? '<span class="badge bg-info text-dark">' . htmlspecialchars($r['intervention_type']) . '</span>' : '—' ?></td>
                        <td><?= $r['pre_weight_kg']  ? $r['pre_weight_kg']  . ' kg' : '—' ?></td>
                        <td><?= $r['post_weight_kg'] ? $r['post_weight_kg'] . ' kg' : '—' ?></td>
                        <td>
                            <?php if ($diff !== null): ?>
                            <span class="<?= $diff > 0 ? 'text-success' : ($diff < 0 ? 'text-danger' : 'text-muted') ?> fw-semibold">
                                <?= ($diff > 0 ? '+' : '') . $diff ?> kg
                            </span>
                            <?php else: ?>—<?php endif; ?>
                        </td>
                        <td>
                            <?php $sc = ['Active'=>'success','Completed'=>'secondary','Dropped'=>'danger']; ?>
                            <span class="badge bg-<?= $sc[$r['status']] ?? 'secondary' ?>"><?= $r['status'] ?></span>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
