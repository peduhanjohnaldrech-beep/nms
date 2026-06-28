<?php $pageTitle = $editing ? 'Edit Program' : 'Add Program'; ?>

<div class="d-flex align-items-center my-3">
    <a href="<?= APP_URL ?>/programs-admin" class="btn btn-sm btn-outline-secondary me-3">
        <i class="bi bi-arrow-left"></i>
    </a>
    <h4 class="mb-0"><i class="bi bi-grid me-2"></i><?= $editing ? 'Edit' : 'Add' ?> Program</h4>
</div>

<div class="row justify-content-center">
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <form method="post">
                    <input type="hidden" name="csrf_token" value="<?= \Core\Session::generateCsrf() ?>">

                    <?php if (!$editing): ?>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Program Code <span class="text-danger">*</span></label>
                        <input type="text" name="code" class="form-control text-uppercase"
                               value="<?= htmlspecialchars($data['code'] ?? '') ?>"
                               placeholder="e.g. IYCF, SBFP, ECCD" maxlength="20" required>
                        <div class="form-text">Short unique code (letters only, will be uppercased).</div>
                    </div>
                    <?php else: ?>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Program Code</label>
                        <input type="text" class="form-control bg-light"
                               value="<?= htmlspecialchars($data['code'] ?? '') ?>" disabled>
                    </div>
                    <?php endif; ?>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Program Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control"
                               value="<?= htmlspecialchars($data['name'] ?? '') ?>"
                               placeholder="e.g. Supplementary Feeding Program" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Description</label>
                        <textarea name="description" class="form-control" rows="2"
                                  placeholder="Brief description of this program..."><?= htmlspecialchars($data['description'] ?? '') ?></textarea>
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Icon <span class="text-muted small">(Bootstrap Icons)</span></label>
                            <input type="text" name="icon" class="form-control"
                                   value="<?= htmlspecialchars($data['icon'] ?? 'bi-clipboard-check') ?>"
                                   placeholder="bi-clipboard-check">
                            <div class="form-text">See <a href="https://icons.getbootstrap.com" target="_blank">icons.getbootstrap.com</a></div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Color</label>
                            <select name="color" class="form-select">
                                <?php foreach (['primary','success','warning','danger','info','secondary'] as $c): ?>
                                <option value="<?= $c ?>" <?= ($data['color'] ?? 'primary') === $c ? 'selected' : '' ?>>
                                    <?= ucfirst($c) ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Type</label>
                            <select name="type" class="form-select">
                                <?php foreach (['assessment','supplementation','micronutrient','generic'] as $t): ?>
                                <option value="<?= $t ?>" <?= ($data['type'] ?? 'generic') === $t ? 'selected' : '' ?>>
                                    <?= ucfirst($t) ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Sort Order</label>
                            <input type="number" name="sort_order" class="form-control"
                                   value="<?= (int)($data['sort_order'] ?? 0) ?>" min="0">
                        </div>
                    </div>

                    <?php if ($editing): ?>
                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="is_active"
                                   id="isActive" <?= !empty($data['is_active']) ? 'checked' : '' ?>>
                            <label class="form-check-label" for="isActive">Active (visible in navigation)</label>
                        </div>
                    </div>
                    <?php endif; ?>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-circle me-1"></i><?= $editing ? 'Save Changes' : 'Create Program' ?>
                        </button>
                        <a href="<?= APP_URL ?>/programs-admin" class="btn btn-outline-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
