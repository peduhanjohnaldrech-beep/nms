<?php $pageTitle = 'Reports'; ?>

<div class="d-flex align-items-center justify-content-between mt-3 mb-4">
    <h5 class="mb-0 fw-bold"><i class="bi bi-bar-chart-fill me-2 text-primary"></i>Reports</h5>
</div>

<div class="card border-0 shadow-sm">
    <div class="list-group list-group-flush">

        <div class="list-group-item px-4 py-2 bg-light border-bottom">
            <span class="text-muted small fw-semibold text-uppercase">Core Programs</span>
        </div>

        <a href="<?= APP_URL ?>/reports/opt" class="list-group-item list-group-item-action d-flex align-items-center gap-3 px-4 py-3">
            <div class="rounded-circle bg-success bg-opacity-10 d-flex align-items-center justify-content-center flex-shrink-0" style="width:36px;height:36px">
                <i class="bi bi-clipboard-heart text-success"></i>
            </div>
            <div class="flex-grow-1 min-width-0">
                <div class="fw-semibold">OPT Report</div>
                <div class="text-muted small">Weight &amp; height per child — filter by barangay, period, year</div>
            </div>
            <i class="bi bi-chevron-right text-muted small"></i>
        </a>

        <a href="<?= APP_URL ?>/reports/dsp" class="list-group-item list-group-item-action d-flex align-items-center gap-3 px-4 py-3">
            <div class="rounded-circle bg-warning bg-opacity-10 d-flex align-items-center justify-content-center flex-shrink-0" style="width:36px;height:36px">
                <i class="bi bi-egg-fried text-warning"></i>
            </div>
            <div class="flex-grow-1 min-width-0">
                <div class="fw-semibold">DSP Report</div>
                <div class="text-muted small">Feeding program enrollments — RUTF/RUSF interventions</div>
            </div>
            <i class="bi bi-chevron-right text-muted small"></i>
        </a>

        <a href="<?= APP_URL ?>/reports/outcome" class="list-group-item list-group-item-action d-flex align-items-center gap-3 px-4 py-3">
            <div class="rounded-circle bg-secondary bg-opacity-10 d-flex align-items-center justify-content-center flex-shrink-0" style="width:36px;height:36px">
                <i class="bi bi-graph-up-arrow text-secondary"></i>
            </div>
            <div class="flex-grow-1 min-width-0">
                <div class="fw-semibold">Program Outcome</div>
                <div class="text-muted small">Pre vs post weight for DSP graduates</div>
            </div>
            <i class="bi bi-chevron-right text-muted small"></i>
        </a>

        <div class="list-group-item px-4 py-2 bg-light border-bottom border-top">
            <span class="text-muted small fw-semibold text-uppercase">Micronutrient Supplementation</span>
        </div>

        <a href="<?= APP_URL ?>/reports/mns" class="list-group-item list-group-item-action d-flex align-items-center gap-3 px-4 py-3">
            <div class="rounded-circle bg-primary bg-opacity-10 d-flex align-items-center justify-content-center flex-shrink-0" style="width:36px;height:36px">
                <i class="bi bi-capsule text-primary"></i>
            </div>
            <div class="flex-grow-1 min-width-0">
                <div class="fw-semibold">MNS Report</div>
                <div class="text-muted small">Vitamin A, MNP, and LNS-SQ distribution coverage</div>
            </div>
            <i class="bi bi-chevron-right text-muted small"></i>
        </a>

        <a href="<?= APP_URL ?>/reports/dispensing" class="list-group-item list-group-item-action d-flex align-items-center gap-3 px-4 py-3">
            <div class="rounded-circle bg-info bg-opacity-10 d-flex align-items-center justify-content-center flex-shrink-0" style="width:36px;height:36px">
                <i class="bi bi-box-seam text-info"></i>
            </div>
            <div class="flex-grow-1 min-width-0">
                <div class="fw-semibold">Dispensing Report</div>
                <div class="text-muted small">Summary of all supplements and medicines dispensed</div>
            </div>
            <i class="bi bi-chevron-right text-muted small"></i>
        </a>

        <div class="list-group-item px-4 py-2 bg-light border-bottom border-top">
            <span class="text-muted small fw-semibold text-uppercase">Analytics</span>
        </div>

        <a href="<?= APP_URL ?>/reports/summary" class="list-group-item list-group-item-action d-flex align-items-center gap-3 px-4 py-3">
            <div class="rounded-circle bg-success bg-opacity-10 d-flex align-items-center justify-content-center flex-shrink-0" style="width:36px;height:36px">
                <i class="bi bi-file-earmark-bar-graph text-success"></i>
            </div>
            <div class="flex-grow-1 min-width-0">
                <div class="fw-semibold">Summary Report</div>
                <div class="text-muted small">Coverage %, malnutrition rate, DSP &amp; Vitamin A — per barangay</div>
            </div>
            <i class="bi bi-chevron-right text-muted small"></i>
        </a>

        <a href="<?= APP_URL ?>/reports/comparison" class="list-group-item list-group-item-action d-flex align-items-center gap-3 px-4 py-3">
            <div class="rounded-circle bg-primary bg-opacity-10 d-flex align-items-center justify-content-center flex-shrink-0" style="width:36px;height:36px">
                <i class="bi bi-arrow-left-right text-primary"></i>
            </div>
            <div class="flex-grow-1 min-width-0">
                <div class="fw-semibold">Year Comparison</div>
                <div class="text-muted small">Compare OPT malnutrition rates between two years</div>
            </div>
            <i class="bi bi-chevron-right text-muted small"></i>
        </a>

    </div>
</div>
