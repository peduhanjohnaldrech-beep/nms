<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?= \Core\Session::generateCsrf() ?>">
    <title><?= isset($pageTitle) ? htmlspecialchars($pageTitle) . ' — ' : '' ?><?= APP_NAME ?></title>
    <link rel="stylesheet" href="<?= APP_URL ?>/css/bootstrap.min.css">
    <link rel="stylesheet" href="<?= APP_URL ?>/css/bootstrap-icons.css">
    <link rel="stylesheet" href="<?= APP_URL ?>/css/app.css">
    <style>
        body {
            background-image: linear-gradient(rgba(241,245,249,0.88), rgba(241,245,249,0.88)),
                              url('<?= APP_URL ?>/img/background.jpg');
            background-size: cover;
            background-attachment: fixed;
            background-position: center;
        }
        .sidebar-chevron { transition: transform .2s ease; }
        .nav-link[data-bs-toggle="collapse"].collapsed .sidebar-chevron { transform: rotate(-90deg); }
        .sidebar .collapse .nav-link { font-size: .92em; }
        @media print {
            .no-print, nav, .sidebar, footer, .btn, .alert:not(.print-show) { display: none !important; }
            .print-card-only { display: block !important; }
            body { background: none !important; }
            .col-lg-4, .col-lg-8 { width: 100% !important; }
        }
    </style>
</head>
<body>

<?php
$userRole  = \Core\Session::get('user_role', '');
$userName  = \Core\Session::get('user_name', '');
$isAdmin   = strtolower($userRole) === 'admin';
function hasPerm(string $m): bool {
    static $isAdm, $perms;
    if ($isAdm === null) {
        $isAdm = strtolower(\Core\Session::get('user_role', '')) === 'admin';
        $perms = (array)(\Core\Session::get('user_permissions') ?? []);
    }
    return $isAdm || in_array($m, $perms);
}
$__uri    = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$__assessOpen  = str_contains($__uri, '/assessments') || str_contains($__uri, '/beneficiaries/followup');
$__programsOpen = str_contains($__uri, '/programs');
$__reportsOpen = str_contains($__uri, '/reports');
$__dataOpen    = str_contains($__uri, '/dispensing') || str_contains($__uri, '/import');
$__adminOpen   = str_contains($__uri, '/activity') || str_contains($__uri, '/users') || str_contains($__uri, '/programs-admin');

// Active sidebar helper
$__basePath = rtrim(parse_url(APP_URL, PHP_URL_PATH) ?? '', '/');
$__relPath  = substr($__uri, strlen($__basePath)) ?: '/';
if ($__relPath === '' || $__relPath[0] !== '/') $__relPath = '/' . $__relPath;
function __navActive(string $path): string {
    global $__relPath;
    return str_starts_with($__relPath, $path) ? 'active' : '';
}

// Breadcrumbs
$__labelMap = [
    'dashboard'=>'Dashboard','beneficiaries'=>'Beneficiaries','assessments'=>'Assessments',
    'programs'=>'Programs','reports'=>'Reports','dispensing'=>'Dispensing','import'=>'Import',
    'users'=>'Users','activity'=>'Activity Log','programs-admin'=>'Program Manager',
    'help'=>'Help','opt'=>'OPT','dsp'=>'DSP','mns'=>'MNS','create'=>'New',
    'edit'=>'Edit','batch'=>'Batch Assessment','followup'=>'For Follow-up',
    'storage'=>'Storage Browser','outcome'=>'Outcome Report','distribution'=>'Distribution Report',
    'backup'=>'Backup',
];
$__crumbSegs   = array_values(array_filter(explode('/', trim($__relPath, '/'))));
$__breadcrumbs = [];
$__crumbPath   = $__basePath;
foreach ($__crumbSegs as $seg) {
    $__crumbPath   .= '/' . $seg;
    $__breadcrumbs[] = [
        'label' => $__labelMap[$seg] ?? (is_numeric($seg) ? 'Details' : ucfirst(str_replace('-', ' ', $seg))),
        'url'   => $__crumbPath,
    ];
}
?>

<nav class="navbar navbar-expand-lg navbar-dark bg-primary sticky-top">
    <div class="container-fluid">
        <button class="btn btn-outline-light btn-sm d-lg-none me-1 px-2" id="sidebarToggle" type="button">
            <i class="bi bi-list fs-5"></i>
        </button>
        <a class="navbar-brand fw-bold d-flex align-items-center" href="<?= APP_URL ?>/dashboard">
            <img src="<?= APP_URL ?>/img/logo.jpg" alt="Logo" style="height:36px;width:36px;object-fit:cover;border-radius:50%;margin-right:8px;">
            <?= APP_NAME ?>
        </a>
        <div class="collapse navbar-collapse" id="navMain">
            <ul class="navbar-nav ms-auto align-items-center">
                <li class="nav-item">
                    <span class="nav-link text-white-50">
                        <i class="bi bi-person-circle me-1"></i>
                        <?= htmlspecialchars($userName) ?>
                        <span class="badge bg-light text-dark ms-1"><?= htmlspecialchars(ucfirst($userRole)) ?></span>
                    </span>
                </li>
                <li class="nav-item">
                    <a href="<?= APP_URL ?>/logout" class="btn btn-sm btn-outline-light ms-2">
                        <i class="bi bi-box-arrow-right"></i> Logout
                    </a>
                </li>
            </ul>
        </div>
    </div>
</nav>

<div class="d-flex" id="wrapper">
    <div class="sidebar bg-dark text-white" id="sidebar">
        <div class="sidebar-header p-3 border-bottom border-secondary">
            <small class="text-muted">Navigation</small>
        </div>
        <nav class="nav flex-column p-2">
            <a class="nav-link text-white <?= __navActive('/dashboard') ?>" href="<?= APP_URL ?>/dashboard">
                <i class="bi bi-speedometer2 me-2"></i>Dashboard
            </a>

            <?php if (hasPerm('beneficiaries')): ?>
            <a class="nav-link text-white <?= __navActive('/beneficiaries') ?>" href="<?= APP_URL ?>/beneficiaries">
                <i class="bi bi-people-fill me-2"></i>Beneficiaries
            </a>

            <?php if (hasPerm('assessments')): ?>
            <?php /* Assessments dropdown */ ?>
            <a class="nav-link text-white d-flex justify-content-between align-items-center <?= $__assessOpen ? '' : 'collapsed' ?>"
               data-bs-toggle="collapse" href="#sidebarAssessments" role="button"
               aria-expanded="<?= $__assessOpen ? 'true' : 'false' ?>">
                <span><i class="bi bi-clipboard2-pulse me-2"></i>Assessments</span>
                <i class="bi bi-chevron-down small sidebar-chevron"></i>
            </a>
            <div class="collapse <?= $__assessOpen ? 'show' : '' ?>" id="sidebarAssessments">
                <a class="nav-link text-white ps-4 <?= __navActive('/assessments/create') ?>" href="<?= APP_URL ?>/assessments/create">
                    <i class="bi bi-plus-circle me-2"></i>New Assessment
                </a>
                <a class="nav-link text-white ps-4 <?= __navActive('/assessments/batch') ?>" href="<?= APP_URL ?>/assessments/batch">
                    <i class="bi bi-clipboard2-data me-2"></i>Batch Assessment
                </a>
                <a class="nav-link text-white ps-4 <?= __navActive('/beneficiaries/followup') ?>" href="<?= APP_URL ?>/beneficiaries/followup">
                    <i class="bi bi-exclamation-triangle-fill me-2 text-warning"></i>For Follow-up
                </a>
            </div>

            <?php endif; /* assessments */ ?>

            <?php if (hasPerm('programs')): ?>
            <?php /* Programs dropdown */ ?>
            <?php
            $__sidebarPrograms = [];
            try {
                $__sidebarPrograms = (new App\Models\Program())->getActive();
            } catch (\Throwable $e) {}
            ?>
            <a class="nav-link text-white d-flex justify-content-between align-items-center <?= $__programsOpen ? '' : 'collapsed' ?>"
               data-bs-toggle="collapse" href="#sidebarPrograms" role="button"
               aria-expanded="<?= $__programsOpen ? 'true' : 'false' ?>">
                <span><i class="bi bi-grid me-2"></i>Programs</span>
                <i class="bi bi-chevron-down small sidebar-chevron"></i>
            </a>
            <div class="collapse <?= $__programsOpen ? 'show' : '' ?>" id="sidebarPrograms">
                <?php if (empty($__sidebarPrograms)): ?>
                <span class="nav-link text-muted ps-4 small">
                    <i class="bi bi-info-circle me-1"></i>No active programs.
                    <?php if ($isAdmin): ?>
                    <a href="<?= APP_URL ?>/programs-admin" class="text-warning">Set up</a>
                    <?php endif; ?>
                </span>
                <?php else: ?>
                <?php foreach ($__sidebarPrograms as $__prog): ?>
                    <?php
                    $__progUrl = match($__prog['code']) {
                        'OPT' => APP_URL . '/programs/opt',
                        'DSP' => APP_URL . '/programs/dsp',
                        'MNS' => APP_URL . '/programs/mns',
                        default => APP_URL . '/programs/' . strtolower($__prog['code']),
                    };
                    $__shortName = match($__prog['code']) {
                        'OPT' => 'OPT (Timbang)',
                        'DSP' => 'DSP (Feeding)',
                        'MNS' => 'MNS (Vitamin A)',
                        default => htmlspecialchars($__prog['code'] . ' — ' . $__prog['name']),
                    };
                    ?>
                <a class="nav-link text-white ps-4" href="<?= $__progUrl ?>">
                    <i class="bi <?= htmlspecialchars($__prog['icon']) ?> me-2"></i><?= $__shortName ?>
                </a>
                <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <?php endif; /* programs */ ?>

            <?php if (hasPerm('reports')): ?>
            <?php /* Reports dropdown */ ?>
            <a class="nav-link text-white d-flex justify-content-between align-items-center <?= $__reportsOpen ? '' : 'collapsed' ?>"
               data-bs-toggle="collapse" href="#sidebarReports" role="button"
               aria-expanded="<?= $__reportsOpen ? 'true' : 'false' ?>">
                <span><i class="bi bi-bar-chart-fill me-2"></i>Reports</span>
                <i class="bi bi-chevron-down small sidebar-chevron"></i>
            </a>
            <div class="collapse <?= $__reportsOpen ? 'show' : '' ?>" id="sidebarReports">
                <a class="nav-link text-white ps-4" href="<?= APP_URL ?>/reports">
                    <i class="bi bi-bar-chart-fill me-2"></i>All Reports
                </a>
                <a class="nav-link text-white ps-4" href="<?= APP_URL ?>/reports/summary">
                    <i class="bi bi-file-earmark-bar-graph me-2"></i>Summary Report
                </a>
                <a class="nav-link text-white ps-4" href="<?= APP_URL ?>/reports/comparison">
                    <i class="bi bi-arrow-left-right me-2"></i>Period Comparison
                </a>
                <a class="nav-link text-white ps-4" href="<?= APP_URL ?>/reports/outcome">
                    <i class="bi bi-graph-up-arrow me-2"></i>Outcome Report
                </a>
                <a class="nav-link text-white ps-4" href="<?= APP_URL ?>/reports/distribution">
                    <i class="bi bi-cloud-upload me-2"></i>Report Distribution
                </a>
            </div>

            <?php endif; /* reports */ ?>

            <?php if (hasPerm('dispensing')): ?>
            <a class="nav-link text-white <?= __navActive('/dispensing') ?>" href="<?= APP_URL ?>/dispensing">
                <i class="bi bi-prescription2 me-2"></i>Dispensing Tracker
            </a>
            <?php endif; ?>

            <?php if (hasPerm('import')): ?>
            <a class="nav-link text-white <?= __navActive('/import') ?>" href="<?= APP_URL ?>/import">
                <i class="bi bi-file-earmark-excel me-2"></i>Import
            </a>
            <?php endif; ?>
            <?php endif; /* beneficiaries */ ?>

            <a class="nav-link text-white <?= __navActive('/help') ?>" href="<?= APP_URL ?>/help">
                <i class="bi bi-question-circle me-2"></i>Help
            </a>

            <?php if (hasPerm('activity_log') || $isAdmin): ?>
            <?php /* Admin dropdown */ ?>
            <a class="nav-link text-white d-flex justify-content-between align-items-center <?= $__adminOpen ? '' : 'collapsed' ?>"
               data-bs-toggle="collapse" href="#sidebarAdmin" role="button"
               aria-expanded="<?= $__adminOpen ? 'true' : 'false' ?>">
                <span><i class="bi bi-gear-fill me-2"></i>Admin</span>
                <i class="bi bi-chevron-down small sidebar-chevron"></i>
            </a>
            <div class="collapse <?= $__adminOpen ? 'show' : '' ?>" id="sidebarAdmin">
                <?php if (hasPerm('activity_log')): ?>
                <a class="nav-link text-white ps-4" href="<?= APP_URL ?>/activity">
                    <i class="bi bi-journal-text me-2"></i>Activity Log
                </a>
                <?php endif; ?>
                <?php if ($isAdmin): ?>
                <a class="nav-link text-white ps-4" href="<?= APP_URL ?>/users">
                    <i class="bi bi-person-gear me-2"></i>User Management
                </a>
                <a class="nav-link text-white ps-4" href="<?= APP_URL ?>/admin/seed">
                    <i class="bi bi-database-fill-gear me-2"></i>Demo Seeder
                </a>
                <?php endif; ?>
                <?php if (hasPerm('programs_admin') || $isAdmin): ?>
                <a class="nav-link text-white ps-4" href="<?= APP_URL ?>/programs-admin">
                    <i class="bi bi-grid me-2"></i>Program Manager
                </a>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </nav>
    </div>

    <div class="page-content flex-grow-1">
        <div class="container-fluid pt-3 px-4">
            <?php foreach (\Core\Session::getAllFlash() as $type => $message): ?>
                <?php $bsType = match($type) { 'error' => 'danger', 'warning' => 'warning', 'info' => 'info', default => 'success' }; ?>
                <div class="alert alert-<?= $bsType ?> alert-dismissible fade show" role="alert">
                    <?= $message ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endforeach; ?>
        </div>
        <?php if (count($__breadcrumbs) > 1): ?>
        <nav aria-label="breadcrumb" class="px-4 pt-2">
            <ol class="breadcrumb small mb-0">
                <li class="breadcrumb-item">
                    <a href="<?= APP_URL ?>/dashboard" class="text-decoration-none">
                        <i class="bi bi-house-fill"></i> Home
                    </a>
                </li>
                <?php foreach ($__breadcrumbs as $i => $crumb): ?>
                <?php if ($i === count($__breadcrumbs) - 1): ?>
                <li class="breadcrumb-item active"><?= htmlspecialchars($crumb['label']) ?></li>
                <?php else: ?>
                <li class="breadcrumb-item">
                    <a href="<?= $crumb['url'] ?>" class="text-decoration-none"><?= htmlspecialchars($crumb['label']) ?></a>
                </li>
                <?php endif; ?>
                <?php endforeach; ?>
            </ol>
        </nav>
        <?php endif; ?>
        <main class="container-fluid px-4 pb-4">
            <?= $content ?>
        </main>
    </div>
</div>

<script src="<?= APP_URL ?>/js/bootstrap.bundle.min.js"></script>
<script src="<?= APP_URL ?>/js/chart.umd.min.js"></script>
<script src="<?= APP_URL ?>/js/app.js"></script>
<script>
// Init Bootstrap tooltips
document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(el => {
    new bootstrap.Tooltip(el, { trigger: 'hover' });
});
</script>

<!-- Global Confirm Modal -->
<div class="modal fade" id="globalConfirmModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="globalConfirmModalLabel"></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="globalConfirmModalBody"></div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn" id="globalConfirmModalOk"></button>
            </div>
        </div>
    </div>
</div>
<script>
(function () {
    const modalEl = document.getElementById('globalConfirmModal');
    const modal   = new bootstrap.Modal(modalEl);
    const title   = document.getElementById('globalConfirmModalLabel');
    const body    = document.getElementById('globalConfirmModalBody');
    const okBtn   = document.getElementById('globalConfirmModalOk');
    let pending   = null;

    document.addEventListener('click', function (e) {
        const trigger = e.target.closest('.confirm-trigger');
        if (!trigger) return;
        e.preventDefault();
        const form = trigger.closest('form');
        title.textContent = trigger.dataset.confirmTitle   || 'Confirm';
        body.innerHTML    = trigger.dataset.confirmMessage || 'Are you sure?';
        okBtn.textContent = trigger.dataset.confirmBtn     || 'Confirm';
        okBtn.className   = 'btn ' + (trigger.dataset.confirmClass || 'btn-primary');
        pending = {
            form,
            actionName:  trigger.dataset.actionName  || null,
            actionValue: trigger.dataset.actionValue || null,
        };
        modal.show();
    });

    okBtn.addEventListener('click', function () {
        if (!pending) return;
        modal.hide();
        const { form, actionName, actionValue } = pending;
        pending = null;
        if (actionName) {
            const hidden = document.createElement('input');
            hidden.type  = 'hidden';
            hidden.name  = actionName;
            hidden.value = actionValue;
            form.appendChild(hidden);
        }
        form.submit();
    });
})();
</script>
</body>
</html>
