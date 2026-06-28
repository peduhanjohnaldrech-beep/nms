<?php $pageTitle = 'Period Comparison'; ?>

<div class="d-flex align-items-center my-3">
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
            <div class="col-auto">
                <button type="submit" class="btn btn-sm btn-primary"><i class="bi bi-funnel me-1"></i>Filter</button>
                <a href="<?= APP_URL ?>/reports/comparison" class="btn btn-sm btn-outline-secondary"><i class="bi bi-x-lg"></i></a>
            </div>
        </form>
    </div>
</div>

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
    <div class="card-header bg-white fw-semibold">
        <i class="bi bi-bar-chart me-2 text-primary"></i>Malnutrition Rate: January vs July <?= $year ?>
    </div>
    <div class="card-body"><canvas id="comparisonChart" height="60"></canvas></div>
</div>

<!-- Table -->
<div class="card border-0 shadow-sm">
    <div class="card-header bg-white fw-semibold">
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
