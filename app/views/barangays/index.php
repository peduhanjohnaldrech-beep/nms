<?php
$pageTitle  = 'Barangay Directory';
$isAdmin    = strtolower(\Core\Session::get('user_role', '')) === 'admin';
?>

<div class="d-flex justify-content-between align-items-center mt-3 mb-2 page-title-row">
    <div>
        <h4 class="mb-0"><i class="bi bi-geo-alt-fill me-2 text-primary"></i>Barangay Directory</h4>
        <p class="text-muted small mb-0"><?= count($barangays) ?> barangays — click any row to view its beneficiaries</p>
    </div>
    <?php if ($isAdmin): ?>
    <button class="btn btn-outline-secondary btn-sm" data-bs-toggle="modal" data-bs-target="#manageBarangaysModal">
        <i class="bi bi-pencil-square me-1"></i>Manage Barangays
    </button>
    <?php endif; ?>
</div>

<?php if ($isAdmin): ?>
<!-- Manage Barangays Modal -->
<div class="modal fade" id="manageBarangaysModal" tabindex="-1" aria-labelledby="manageBarangaysModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-md">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="manageBarangaysModalLabel"><i class="bi bi-geo-alt me-2"></i>Manage Barangays</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-0">
                <!-- Add new -->
                <div class="p-3 border-bottom bg-light">
                    <form method="POST" action="<?= APP_URL ?>/barangays" class="d-flex gap-2">
                        <input type="text" name="name" class="form-control form-control-sm" placeholder="New barangay name" required maxlength="100">
                        <button type="submit" class="btn btn-primary btn-sm text-nowrap">
                            <i class="bi bi-plus-lg me-1"></i>Add
                        </button>
                    </form>
                </div>
                <!-- Barangay list -->
                <div style="max-height:380px;overflow-y:auto;">
                    <table class="table table-sm table-hover align-middle mb-0">
                        <tbody>
                            <?php foreach ($barangays as $b): ?>
                            <tr>
                                <td class="ps-3"><i class="bi bi-geo-alt me-1 text-muted"></i><?= htmlspecialchars($b['name']) ?></td>
                                <td class="text-end pe-3" style="width:1%">
                                    <?php if (isset($b['id'])): ?>
                                    <form method="POST" action="<?= APP_URL ?>/barangays/<?= $b['id'] ?>/delete"
                                          onsubmit="return confirm('Delete \'<?= htmlspecialchars(addslashes($b['name'])) ?>\'?')">
                                        <button type="submit" class="btn btn-sm btn-outline-danger">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                    <?php else: ?>
                                    <span class="text-muted small">legacy</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th rowspan="2" class="align-middle">Barangay</th>
                        <th rowspan="2" class="text-center align-middle">0–59 mo</th>
                        <th rowspan="2" class="text-center align-middle">Weighed<br><small class="fw-normal text-muted">this year</small></th>
                        <th rowspan="2" class="text-center align-middle">Pending</th>
                        <th colspan="5" class="text-center border-start border-end py-1"><small class="fw-semibold">Weight-for-Age</small></th>
                        <th colspan="2" class="text-center border-end py-1"><small class="fw-semibold">Height-for-Age</small></th>
                        <th colspan="2" class="text-center border-end py-1"><small class="fw-semibold">Wt-for-Lt/Ht</small></th>
                        <th rowspan="2" class="align-middle"></th>
                    </tr>
                    <tr>
                        <th class="text-center border-start"><span class="badge status-suw">SUW</span></th>
                        <th class="text-center"><span class="badge status-uw">UW</span></th>
                        <th class="text-center"><span class="badge status-normal">Normal</span></th>
                        <th class="text-center"><span class="badge status-ow">OW</span></th>
                        <th class="text-center border-end"><span class="badge status-ob">OB</span></th>
                        <th class="text-center"><span class="badge status-sst">SSt</span></th>
                        <th class="text-center border-end"><span class="badge status-st">St</span></th>
                        <th class="text-center"><span class="badge status-sw">SW</span></th>
                        <th class="text-center border-end"><span class="badge status-mw">MW</span></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($barangays)): ?>
                    <tr><td colspan="14" class="text-center text-muted py-4">No barangays found.</td></tr>

                    <?php endif; ?>
                    <?php foreach ($barangays as $b): ?>
                    <?php $dash = '<span class="text-muted">—</span>'; ?>
                    <tr style="cursor:pointer" onclick="location.href='<?= APP_URL ?>/beneficiaries?barangay=<?= urlencode($b['name']) ?>'">
                        <td class="fw-semibold">
                            <i class="bi bi-geo-alt me-1 text-muted"></i><?= htmlspecialchars($b['name']) ?>
                        </td>
                        <td class="text-center fw-semibold"><?= $b['active'] ?: $dash ?></td>
                        <td class="text-center"><?= $b['weighed'] ?: $dash ?></td>
                        <td class="text-center">
                            <?php if ($b['pending'] > 0): ?>
                            <span class="badge bg-warning text-dark"><?= $b['pending'] ?></span>
                            <?php else: ?>
                            <?= $dash ?>
                            <?php endif; ?>
                        </td>
                        <td class="text-center border-start"><?= $b['suw']    > 0 ? '<span class="badge status-suw">'    . $b['suw']    . '</span>' : $dash ?></td>
                        <td class="text-center"><?= $b['uw']     > 0 ? '<span class="badge status-uw">'     . $b['uw']     . '</span>' : $dash ?></td>
                        <td class="text-center"><?= $b['normal'] > 0 ? '<span class="badge status-normal">' . $b['normal'] . '</span>' : $dash ?></td>
                        <td class="text-center"><?= $b['ow']     > 0 ? '<span class="badge status-ow">'     . $b['ow']     . '</span>' : $dash ?></td>
                        <td class="text-center border-end"><?= $b['ob']     > 0 ? '<span class="badge status-ob">'     . $b['ob']     . '</span>' : $dash ?></td>
                        <td class="text-center"><?= $b['sst'] > 0 ? '<span class="badge status-sst">' . $b['sst'] . '</span>' : $dash ?></td>
                        <td class="text-center border-end"><?= $b['st']  > 0 ? '<span class="badge status-st">'  . $b['st']  . '</span>' : $dash ?></td>
                        <td class="text-center"><?= $b['sw']  > 0 ? '<span class="badge status-sw">'  . $b['sw']  . '</span>' : $dash ?></td>
                        <td class="text-center border-end"><?= $b['mw']  > 0 ? '<span class="badge status-mw">'  . $b['mw']  . '</span>' : $dash ?></td>
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
