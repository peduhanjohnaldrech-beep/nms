<?php $pageTitle = 'OPT Report'; ?>

<div class="d-flex align-items-start my-3">
    <a href="<?= APP_URL ?>/reports" class="btn btn-sm btn-outline-secondary me-3 mt-1"><i class="bi bi-arrow-left"></i></a>
    <div>
        <div class="fs-2 text-success mb-1"><i class="bi bi-clipboard-heart"></i></div>
        <h4 class="mb-0">OPT Report</h4>
        <p class="text-muted small mb-0">Operation Timbang — Nutritional Status Assessment Records</p>
    </div>
</div>

<div class="card border-0 shadow-sm mb-3">
    <div class="card-body py-2">
        <form method="get" class="row g-2 align-items-end">
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
            <div class="col-md-2">
                <label class="form-label small">Period</label>
                <select name="period" class="form-select form-select-sm">
                    <option value="">All</option>
                    <option value="January" <?= $period === 'January' ? 'selected' : '' ?>>January</option>
                    <option value="July"    <?= $period === 'July'    ? 'selected' : '' ?>>July</option>
                </select>
            </div>
            <?php if (!$isBhw): ?>
            <div class="col-md-3">
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
                <a href="<?= APP_URL ?>/reports/opt" class="btn btn-sm btn-outline-secondary" title="Clear filters"><i class="bi bi-x-lg"></i></a>
            </div>
            <?php
            $exportQs = 'type=opt&year=' . $year . '&period=' . urlencode($period) . '&barangay=' . urlencode($barangay);
            if (!empty($dateFrom)) $exportQs .= '&date_from=' . urlencode($dateFrom);
            if (!empty($dateTo))   $exportQs .= '&date_to='   . urlencode($dateTo);
            ?>
            <div class="col text-end d-flex gap-1 justify-content-end">
                <a href="<?= APP_URL ?>/reports/export?<?= $exportQs ?>&format=csv"   class="btn btn-sm btn-outline-success"><i class="bi bi-filetype-csv me-1"></i>CSV</a>
                <a href="<?= APP_URL ?>/reports/export?<?= $exportQs ?>&format=excel" class="btn btn-sm btn-success"><i class="bi bi-file-earmark-excel me-1"></i>Excel</a>
                <a href="<?= APP_URL ?>/reports/export?<?= $exportQs ?>&format=pdf"   class="btn btn-sm btn-danger"><i class="bi bi-file-earmark-pdf me-1"></i>PDF</a>
                <a href="<?= APP_URL ?>/reports/export-eopt?year=<?= $year ?>&period=<?= urlencode($period) ?>&barangay=<?= urlencode($barangay) ?>" class="btn btn-sm btn-warning text-dark"><i class="bi bi-file-earmark-spreadsheet me-1"></i>eOPT</a>
            </div>
        </form>
    </div>
</div>

<?php if (!empty($ageGroupStats)): ?>
<!-- Age-Group Breakdown -->
<div class="card border-0 shadow-sm mb-3">
    <div class="card-header bg-white fw-semibold">
        <i class="bi bi-bar-chart me-2 text-primary"></i>Age-Group Breakdown
        <button class="btn btn-sm btn-outline-secondary float-end" type="button"
                data-bs-toggle="collapse" data-bs-target="#ageGroupTable">
            Toggle
        </button>
    </div>
    <div class="collapse show" id="ageGroupTable">
        <div class="card-body p-0">
            <?php
            $groups   = [];
            $statuses = [];
            foreach ($ageGroupStats as $s) {
                $groups[$s['age_group']]     = true;
                $statuses[$s['nutritional_status']] = true;
            }
            $groups   = array_keys($groups);
            $statuses = array_keys($statuses);
            $lookup   = [];
            foreach ($ageGroupStats as $s) {
                $lookup[$s['age_group']][$s['nutritional_status']] = (int)$s['cnt'];
            }
            $statusColors = ['SUW'=>'danger','UW'=>'warning','Normal'=>'success','OW'=>'info','OB'=>'secondary'];
            ?>
            <div class="table-responsive">
                <table class="table table-sm mb-0 text-center">
                    <thead class="table-light">
                        <tr>
                            <th class="text-start">Age Group</th>
                            <?php foreach ($statuses as $st): ?>
                            <th><span class="badge bg-<?= $statusColors[$st] ?? 'secondary' ?>"><?= $st ?></span></th>
                            <?php endforeach; ?>
                            <th>Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($groups as $g): ?>
                        <?php $total = array_sum($lookup[$g] ?? []); ?>
                        <tr>
                            <td class="text-start fw-semibold"><?= htmlspecialchars($g) ?></td>
                            <?php foreach ($statuses as $st): ?>
                            <td><?= $lookup[$g][$st] ?? 0 ?></td>
                            <?php endforeach; ?>
                            <td class="fw-bold"><?= $total ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<div class="card border-0 shadow-sm">
    <div class="card-header bg-white fw-semibold">
        Results <span class="badge bg-secondary ms-2"><?= count($rows) ?></span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-sm table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Name</th><th>Barangay</th><th>Sex</th><th>Age (mo)</th>
                        <th>Wt</th><th>Ht</th><th>WFA Z</th><th>WFA</th><th>HFA</th><th>WFL/H</th><th>MUAC</th><th>Period</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($rows)): ?>
                    <tr><td colspan="12" class="text-center text-muted py-3">No records found.</td></tr>
                    <?php endif; ?>
                    <?php foreach ($rows as $r): ?>
                    <tr>
                        <td><a href="<?= APP_URL ?>/beneficiaries/<?= $r['beneficiary_id'] ?>" class="text-decoration-none"><?= htmlspecialchars($r['last_name'] . ', ' . $r['first_name']) ?></a></td>
                        <td><?= htmlspecialchars($r['barangay']) ?></td>
                        <td><?= $r['sex'] ?></td>
                        <td><?= $r['age_in_months'] ?></td>
                        <td><?= $r['weight_kg'] ?></td>
                        <td><?= $r['height_cm'] ?: '—' ?></td>
                        <td><?= $r['weight_for_age_zscore'] !== null ? number_format($r['weight_for_age_zscore'], 2) : '—' ?></td>
                        <td><span class="badge status-<?= strtolower($r['nutritional_status']) ?>"><?= $r['nutritional_status'] ?></span></td>
                        <td><?= !empty($r['hfa_status']) ? '<span class="badge status-' . strtolower($r['hfa_status']) . '">' . $r['hfa_status'] . '</span>' : '—' ?></td>
                        <td><?= !empty($r['wflh_status']) ? '<span class="badge status-' . strtolower($r['wflh_status']) . '">' . $r['wflh_status'] . '</span>' : '—' ?></td>
                        <td><?= $r['muac_cm'] ?: '—' ?></td>
                        <td><?= $r['period'] . ' ' . $r['assessment_year'] ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
