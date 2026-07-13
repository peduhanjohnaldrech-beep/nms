<?php $pageTitle = 'DSP Report'; ?>

<div class="d-flex align-items-center my-3">
    <a href="<?= APP_URL ?>/reports" class="btn btn-sm btn-outline-secondary me-3"><i class="bi bi-arrow-left"></i></a>
    <div class="rounded-3 bg-warning bg-opacity-10 d-flex align-items-center justify-content-center me-3 flex-shrink-0" style="width:48px;height:48px">
        <i class="bi bi-egg-fried fs-4 text-warning"></i>
    </div>
    <h4 class="mb-0">DSP Report</h4>
</div>

<div class="card border-0 shadow-sm mb-3">
    <div class="card-body py-2">
        <form method="get" class="row g-2 align-items-end">
            <div class="col-md-2">
                <label class="form-label small">Year</label>
                <input type="number" name="year" class="form-control form-control-sm" value="<?= $year ?>">
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
                <a href="<?= APP_URL ?>/reports/dsp" class="btn btn-sm btn-outline-secondary" title="Clear filters"><i class="bi bi-x-lg"></i></a>
            </div>
            <div class="col text-end d-flex gap-1 justify-content-end">
                <a href="<?= APP_URL ?>/reports/export?type=dsp&format=csv&year=<?= $year ?>&barangay=<?= urlencode($barangay) ?>"
                   class="btn btn-sm btn-outline-success">
                    <i class="bi bi-filetype-csv me-1"></i>CSV
                </a>
                <a href="<?= APP_URL ?>/reports/export?type=dsp&format=excel&year=<?= $year ?>&barangay=<?= urlencode($barangay) ?>"
                   class="btn btn-sm btn-success">
                    <i class="bi bi-file-earmark-excel me-1"></i>Excel
                </a>
                <a href="<?= APP_URL ?>/reports/export?type=dsp&format=pdf&year=<?= $year ?>&barangay=<?= urlencode($barangay) ?>"
                   class="btn btn-sm btn-danger">
                    <i class="bi bi-file-earmark-pdf me-1"></i>PDF
                </a>
            </div>
        </form>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-header bg-white fw-semibold">
        DSP Records <span class="badge bg-secondary ms-2"><?= count($rows) ?></span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-sm table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Name</th><th>Barangay</th><th>DOB</th><th>Intervention</th>
                        <th>Pre-Wt</th><th>Post-Wt</th><th>Weight Gain</th><th>Enrolled</th><th>Status</th><th>End Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($rows)): ?>
                    <tr><td colspan="10" class="text-center text-muted py-3">No records found.</td></tr>
                    <?php endif; ?>
                    <?php foreach ($rows as $r): ?>
                    <tr>
                        <td><a href="<?= APP_URL ?>/beneficiaries/<?= $r['beneficiary_id'] ?>" class="text-decoration-none"><?= htmlspecialchars($r['last_name'] . ', ' . $r['first_name']) ?></a></td>
                        <td><?= htmlspecialchars($r['barangay']) ?></td>
                        <td><?= DateHelper::formatDate($r['date_of_birth'], 'M j, Y') ?></td>
                        <td><?= !empty($r['intervention_type']) ? '<span class="badge bg-info text-dark">' . htmlspecialchars($r['intervention_type']) . '</span>' : '—' ?></td>
                        <td><?= $r['pre_weight_kg'] ?? '—' ?></td>
                        <td><?= $r['post_weight_kg'] ?? '—' ?></td>
                        <td>
                            <?php
                            if (isset($r['pre_weight_kg'], $r['post_weight_kg']) && $r['pre_weight_kg'] > 0 && $r['post_weight_kg'] > 0):
                                $gain = round($r['post_weight_kg'] - $r['pre_weight_kg'], 2);
                            ?>
                            <span class="fw-semibold <?= $gain >= 0 ? 'text-success' : 'text-danger' ?>">
                                <?= ($gain >= 0 ? '+' : '') . $gain ?> kg
                            </span>
                            <?php else: ?>—<?php endif; ?>
                        </td>
                        <td><?= DateHelper::formatDate($r['enrollment_date'], 'M j, Y') ?></td>
                        <td>
                            <?php $sc = ['Active'=>'success','Completed'=>'secondary','Dropped'=>'danger']; ?>
                            <span class="badge bg-<?= $sc[$r['status']] ?? 'secondary' ?>"><?= $r['status'] ?></span>
                        </td>
                        <td><?= $r['end_date'] ? DateHelper::formatDate($r['end_date'], 'M j, Y') : '—' ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
