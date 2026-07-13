<?php $pageTitle = 'Barangay Directory'; ?>

<div class="d-flex justify-content-between align-items-center my-3">
    <div>
        <h4 class="mb-0"><i class="bi bi-geo-alt-fill me-2 text-primary"></i>Barangay Directory</h4>
        <p class="text-muted small mb-0"><?= count($barangays) ?> barangays — click any row to view its beneficiaries</p>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Barangay</th>
                        <th class="text-center">Total</th>
                        <th class="text-center">Active<br><small class="fw-normal text-muted">0–59 mo</small></th>
                        <th class="text-center">Pending<br><small class="fw-normal text-muted">Validation</small></th>
                        <th colspan="5" class="text-center">Nutritional Status <small class="fw-normal text-muted">(current year, latest assessment)</small></th>
                        <th></th>
                    </tr>
                    <tr class="table-light border-top-0">
                        <th colspan="4"></th>
                        <th class="text-center"><span class="badge status-suw">SUW</span></th>
                        <th class="text-center"><span class="badge status-uw">UW</span></th>
                        <th class="text-center"><span class="badge status-normal">Normal</span></th>
                        <th class="text-center"><span class="badge status-ow">OW</span></th>
                        <th class="text-center"><span class="badge status-ob">OB</span></th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($barangays)): ?>
                    <tr><td colspan="10" class="text-center text-muted py-4">No barangays found.</td></tr>
                    <?php endif; ?>
                    <?php foreach ($barangays as $b): ?>
                    <?php
                    $assessed   = $b['suw'] + $b['uw'] + $b['normal'] + $b['ow'] + $b['ob'];
                    $malnut     = $b['suw'] + $b['uw'];
                    $malnutRate = $assessed > 0 ? round($malnut / $assessed * 100) : 0;
                    ?>
                    <tr style="cursor:pointer" onclick="location.href='<?= APP_URL ?>/beneficiaries?barangay=<?= urlencode($b['name']) ?>'">
                        <td class="fw-semibold">
                            <i class="bi bi-geo-alt me-1 text-muted"></i><?= htmlspecialchars($b['name']) ?>
                        </td>
                        <td class="text-center fw-bold"><?= $b['total'] ?: '—' ?></td>
                        <td class="text-center">
                            <?php if ($b['active'] > 0): ?>
                            <span class="badge bg-success bg-opacity-75"><?= $b['active'] ?></span>
                            <?php else: ?>
                            <span class="text-muted">—</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-center">
                            <?php if ($b['pending'] > 0): ?>
                            <span class="badge bg-warning text-dark"><?= $b['pending'] ?></span>
                            <?php else: ?>
                            <span class="text-muted">—</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-center"><?= $b['suw']    > 0 ? '<span class="badge status-suw">'    . $b['suw']    . '</span>' : '<span class="text-muted">—</span>' ?></td>
                        <td class="text-center"><?= $b['uw']     > 0 ? '<span class="badge status-uw">'     . $b['uw']     . '</span>' : '<span class="text-muted">—</span>' ?></td>
                        <td class="text-center"><?= $b['normal'] > 0 ? '<span class="badge status-normal">' . $b['normal'] . '</span>' : '<span class="text-muted">—</span>' ?></td>
                        <td class="text-center"><?= $b['ow']     > 0 ? '<span class="badge status-ow">'     . $b['ow']     . '</span>' : '<span class="text-muted">—</span>' ?></td>
                        <td class="text-center"><?= $b['ob']     > 0 ? '<span class="badge status-ob">'     . $b['ob']     . '</span>' : '<span class="text-muted">—</span>' ?></td>
                        <td class="text-nowrap">
                            <a href="<?= APP_URL ?>/beneficiaries?barangay=<?= urlencode($b['name']) ?>"
                               class="btn btn-sm btn-outline-primary"
                               onclick="event.stopPropagation()">
                                <i class="bi bi-people me-1"></i>View All
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
