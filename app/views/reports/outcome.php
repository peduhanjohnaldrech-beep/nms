<?php $pageTitle = 'Program Outcome Report'; ?>

<div class="d-flex align-items-center my-3">
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
            <?php endif; ?>
            <div class="col-auto">
                <button class="btn btn-sm btn-primary">Filter</button>
            </div>
        </form>
    </div>
</div>

<?php
$withData = array_filter($rows, fn($r) => $r['pre_weight_kg'] && $r['post_weight_kg']);
$improved = array_filter($withData, fn($r) => $r['post_weight_kg'] > $r['pre_weight_kg']);
$avgGain  = count($withData) > 0
    ? array_sum(array_map(fn($r) => $r['post_weight_kg'] - $r['pre_weight_kg'], $withData)) / count($withData)
    : 0;
?>

<div class="row g-3 mb-4">
    <div class="col-sm-4">
        <div class="card border-0 shadow-sm text-center py-3">
            <div class="fs-2 fw-bold text-primary"><?= count($rows) ?></div>
            <div class="text-muted small">Total DSP Enrollees</div>
        </div>
    </div>
    <div class="col-sm-4">
        <div class="card border-0 shadow-sm text-center py-3">
            <div class="fs-2 fw-bold text-success"><?= count($improved) ?></div>
            <div class="text-muted small">Gained Weight</div>
        </div>
    </div>
    <div class="col-sm-4">
        <div class="card border-0 shadow-sm text-center py-3">
            <div class="fs-2 fw-bold text-info"><?= number_format($avgGain, 2) ?> kg</div>
            <div class="text-muted small">Avg. Weight Gain</div>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-header bg-white d-flex justify-content-between align-items-center">
        <span class="fw-semibold">DSP Enrollees — Pre/Post Weight Comparison (<?= $year ?>)</span>
        <a href="<?= APP_URL ?>/reports/export?type=dsp&format=excel&year=<?= $year ?>&barangay=<?= urlencode($barangay) ?>"
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
