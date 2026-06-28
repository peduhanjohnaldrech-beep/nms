<?php $pageTitle = 'Demo Data Seeder'; ?>

<div class="d-flex align-items-center my-3">
    <h4 class="mb-0"><i class="bi bi-database-fill-gear me-2 text-warning"></i>Demo Data Seeder</h4>
</div>

<div class="alert alert-warning border-0 shadow-sm mb-4">
    <i class="bi bi-exclamation-triangle-fill me-2"></i>
    <strong>Admin Only.</strong> This tool inserts realistic sample data for demonstration purposes.
    All demo records are tagged with <code>source = 'Demo'</code> and can be cleared in one click.
</div>

<div class="row g-3">
    <div class="col-md-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white fw-semibold">
                <i class="bi bi-database-fill-add me-2 text-success"></i>Seed Demo Data
            </div>
            <div class="card-body">
                <p class="text-muted small mb-3">Creates <strong>~30 beneficiaries</strong> across 3 sample barangays (Mabuhay, Masagana, Maliwanag) with:</p>
                <ul class="small text-muted mb-4">
                    <li>Realistic names, ages (3–55 months), and demographics</li>
                    <li>1–3 OPT assessments per child (with Z-scores calculated)</li>
                    <li>Automatic DSP enrollment for malnourished children</li>
                    <li>Vitamin A records for ~50% of children</li>
                </ul>
                <form method="post" action="<?= APP_URL ?>/admin/seed/run">
                    <?= \Core\Session::csrfField() ?>
                    <button type="button" class="btn btn-success w-100 confirm-trigger"
                            data-confirm-title="Seed Demo Data"
                            data-confirm-message="This will add ~30 beneficiaries with assessments and program enrollments. Continue?"
                            data-confirm-btn="Seed"
                            data-confirm-class="btn-success">
                        <i class="bi bi-database-fill-add me-2"></i>Seed Demo Data
                    </button>
                </form>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white fw-semibold">
                <i class="bi bi-database-fill-x me-2 text-danger"></i>Clear Demo Data
            </div>
            <div class="card-body">
                <p class="text-muted small mb-3">Removes <strong>all records</strong> tagged as <code>source = 'Demo'</code>, including their assessments, program enrollments, and Vitamin A records.</p>
                <p class="text-muted small mb-4">Real data (Walk-in, Excel, Google) is <strong>never touched</strong>.</p>
                <form method="post" action="<?= APP_URL ?>/admin/seed/clear">
                    <?= \Core\Session::csrfField() ?>
                    <button type="button" class="btn btn-outline-danger w-100 confirm-trigger"
                            data-confirm-title="Clear Demo Data"
                            data-confirm-message="Delete <strong>all demo data</strong>? This cannot be undone. Real data will not be affected."
                            data-confirm-btn="Clear"
                            data-confirm-class="btn-danger">
                        <i class="bi bi-database-fill-x me-2"></i>Clear Demo Data
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
