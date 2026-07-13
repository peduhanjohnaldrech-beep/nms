<?php $pageTitle = 'OPT — Operation Timbang'; ?>

<?php
$weighed   = count(array_unique(array_column($rows, 'beneficiary_id')));
$eligible  = $totalEligible ?? 0;
$coverage  = $eligible > 0 ? min(100, round($weighed / $eligible * 100)) : 0;
$coverageClass = $coverage >= 80 ? 'success' : ($coverage >= 50 ? 'warning' : 'danger');
?>

<div class="d-flex justify-content-between align-items-center my-3">
    <div>
        <h4 class="mb-0"><i class="bi bi-clipboard-heart me-2"></i>OPT — Operation Timbang</h4>
        <small class="text-muted">Nutrition screening — weight and height measurement of children aged 0–59 months</small>
    </div>
</div>

<div class="card border-0 shadow-sm mb-3">
    <div class="card-body py-2">
        <form method="get" class="row g-2 align-items-end">
            <div class="col-md-3">
                <label class="form-label small">Year</label>
                <input type="number" name="year" class="form-control form-control-sm"
                       value="<?= $year ?>" min="2000" max="<?= date('Y') + 1 ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label small">Period</label>
                <select name="period" class="form-select form-select-sm">
                    <option value="">All Periods</option>
                    <option value="January" <?= $period === 'January' ? 'selected' : '' ?>>January</option>
                    <option value="July"    <?= $period === 'July'    ? 'selected' : '' ?>>July</option>
                </select>
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-sm btn-primary"><i class="bi bi-search me-1"></i>Filter</button>
            </div>
            <div class="col-md-4 text-end">
                <a href="<?= APP_URL ?>/reports/export?type=opt&format=excel&year=<?= $year ?>&period=<?= urlencode($period) ?>" class="btn btn-sm btn-success">
                    <i class="bi bi-file-earmark-excel me-1"></i>Export Excel
                </a>
            </div>
        </form>
    </div>
</div>

<!-- Coverage Summary -->
<div class="row g-3 mb-3">
    <div class="col-md-4">
        <div class="card border-0 shadow-sm text-center py-3">
            <div class="fs-2 fw-bold text-primary"><?= $eligible ?></div>
            <div class="text-muted small">Eligible Children (0–59 mo)</div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-0 shadow-sm text-center py-3">
            <div class="fs-2 fw-bold text-success"><?= $weighed ?></div>
            <div class="text-muted small">Weighed <?= $period ? "($period $year)" : "($year)" ?></div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-0 shadow-sm text-center py-3">
            <div class="fs-2 fw-bold text-<?= $coverageClass ?>"><?= $coverage ?>%</div>
            <div class="text-muted small">Weighing Coverage</div>
            <div class="progress mx-3 mt-1" style="height:6px;">
                <div class="progress-bar bg-<?= $coverageClass ?>" style="width:<?= $coverage ?>%"></div>
            </div>
        </div>
    </div>
</div>

<!-- Not Yet Weighed -->
<?php if (!empty($notYetWeighed)): ?>
<div class="card border-0 shadow-sm mb-4 border-start border-warning border-3">
    <div class="card-header bg-white fw-semibold text-warning">
        <i class="bi bi-exclamation-circle me-2"></i>Not Yet Weighed
        <span class="badge bg-warning text-dark ms-2"><?= count($notYetWeighed) ?></span>
        <span class="text-muted fw-normal small ms-2">— eligible children with no assessment for <?= $period ? "$period $year" : $year ?></span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-sm mb-0 align-middle">
                <thead class="table-light">
                    <tr><th>Name</th><th>Barangay</th><th>Sex</th><th>Age</th><th></th></tr>
                </thead>
                <tbody>
                    <?php foreach ($notYetWeighed as $b): ?>
                    <tr>
                        <td>
                            <a href="<?= APP_URL ?>/beneficiaries/<?= $b['id'] ?>" class="text-decoration-none fw-semibold">
                                <?= htmlspecialchars($b['last_name'] . ', ' . $b['first_name']) ?>
                            </a>
                        </td>
                        <td><?= htmlspecialchars($b['barangay']) ?></td>
                        <td><?= htmlspecialchars($b['sex']) ?></td>
                        <td><?= DateHelper::formatAge($b['age_months']) ?></td>
                        <td>
                            <?php if (!in_array(strtolower(\Core\Session::get('user_role', '')), ['admin', 'nutritionist'])): ?>
                            <a href="<?= APP_URL ?>/assessments/create?bid=<?= $b['id'] ?>" class="btn btn-sm btn-outline-primary">
                                <i class="bi bi-plus-circle me-1"></i>Record Weight
                            </a>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Z-Score Legend -->
<div class="card border-0 shadow-sm mb-3">
    <div class="card-header bg-white fw-semibold" style="cursor:pointer;" data-bs-toggle="collapse" data-bs-target="#zscoreLegend">
        <i class="bi bi-info-circle me-2 text-info"></i>Z-Score &amp; Status Legend
        <i class="bi bi-chevron-down small float-end mt-1"></i>
    </div>
    <div class="collapse" id="zscoreLegend">
        <div class="card-body py-2">
            <div class="row g-3 small">
                <div class="col-md-4">
                    <strong>WFA — Weight-for-Age</strong><br>
                    <span class="badge status-suw">SUW</span> Severely Underweight: Z &lt; -3<br>
                    <span class="badge status-uw">UW</span> Underweight: Z -3 to -2<br>
                    <span class="badge status-normal">Normal</span> Z -2 to +2<br>
                    <span class="badge status-ow">OW</span> Overweight: Z &gt; +2<br>
                    <span class="badge status-ob">OB</span> Obese: Z &gt; +3
                </div>
                <div class="col-md-4">
                    <strong>WFL/H — Weight-for-Length/Height</strong><br>
                    <span class="badge status-sw">SW</span> Severely Wasted: Z &lt; -3<br>
                    <span class="badge status-mw">MW</span> Moderately Wasted: Z -3 to -2<br>
                    <span class="badge status-normal">Normal</span> Z -2 to +2<br>
                    <span class="badge status-ow">OW</span> Overweight / At Risk: Z &gt; +2
                </div>
                <div class="col-md-4">
                    <strong>HFA — Height-for-Age</strong><br>
                    Stunted: Z &lt; -2<br>
                    Normal: Z -2 to +3<br>
                    Tall: Z &gt; +3<br><br>
                    <span class="text-muted">Z-scores based on WHO 2006 Child Growth Standards</span>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Assessment Records -->
<div class="card border-0 shadow-sm">
    <div class="card-header bg-white fw-semibold">
        Weighed Children
        <span class="badge bg-secondary ms-2"><?= $weighed ?></span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-sm table-hover mb-0 align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Name</th><th>Barangay</th><th>Sex</th><th>Age (mo)</th>
                        <th>Wt (kg)</th><th>Ht (cm)</th><th>WFA Z</th><th>WFA</th>
                        <th>HFA</th><th>WFL/H</th><th>Period</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($rows)): ?>
                    <tr><td colspan="11" class="text-center text-muted py-3">No records found.</td></tr>
                    <?php endif; ?>
                    <?php foreach ($rows as $r): ?>
                    <tr>
                        <td>
                            <a href="<?= APP_URL ?>/beneficiaries/<?= $r['beneficiary_id'] ?>" class="text-decoration-none">
                                <?= htmlspecialchars($r['last_name'] . ', ' . $r['first_name']) ?>
                            </a>
                        </td>
                        <td><?= htmlspecialchars($r['barangay']) ?></td>
                        <td><?= htmlspecialchars($r['sex']) ?></td>
                        <td><?= $r['age_in_months'] ?></td>
                        <td><?= $r['weight_kg'] ?></td>
                        <td><?= $r['height_cm'] ?: '—' ?></td>
                        <td><?= $r['weight_for_age_zscore'] !== null ? number_format($r['weight_for_age_zscore'], 2) : '—' ?></td>
                        <td><span class="badge status-<?= strtolower($r['nutritional_status']) ?>"><?= $r['nutritional_status'] ?></span></td>
                        <td><?= !empty($r['hfa_status']) ? '<span class="badge status-' . strtolower($r['hfa_status']) . '">' . $r['hfa_status'] . '</span>' : '—' ?></td>
                        <td><?= !empty($r['wflh_status']) ? '<span class="badge status-' . strtolower($r['wflh_status']) . '">' . $r['wflh_status'] . '</span>' : '—' ?></td>
                        <td><?= htmlspecialchars($r['period'] . ' ' . $r['assessment_year']) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
