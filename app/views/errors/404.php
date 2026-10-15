<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>404 — Page Not Found</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
</head>
<body class="bg-light d-flex align-items-center justify-content-center" style="min-height:100vh;">
    <div class="text-center px-3">
        <div class="mb-3">
            <i class="bi bi-exclamation-circle text-primary" style="font-size:5rem;"></i>
        </div>
        <h1 class="display-1 fw-bold text-primary mb-1">404</h1>
        <h4 class="fw-semibold mb-2">Page Not Found</h4>
        <p class="text-muted mb-4">The page you are looking for does not exist or has been moved.</p>
        <a href="<?= defined('APP_URL') ? APP_URL : '/' ?>/dashboard" class="btn btn-primary px-4">
            <i class="bi bi-house me-1"></i>Go to Dashboard
        </a>
        <a href="javascript:history.back()" class="btn btn-outline-secondary ms-2 px-4">
            <i class="bi bi-arrow-left me-1"></i>Go Back
        </a>
    </div>
</body>
</html>
