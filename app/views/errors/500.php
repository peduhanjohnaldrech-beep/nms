<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>500 — Server Error</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
</head>
<body class="bg-light d-flex align-items-center justify-content-center" style="min-height:100vh;">
    <div class="text-center px-3">
        <div class="mb-3">
            <i class="bi bi-exclamation-triangle text-danger" style="font-size:5rem;"></i>
        </div>
        <h1 class="display-1 fw-bold text-danger mb-1">500</h1>
        <h4 class="fw-semibold mb-2">Something Went Wrong</h4>
        <p class="text-muted mb-4">An unexpected error occurred. Please try again or contact your system administrator.</p>
        <?php if (isset($e)): ?>
        <pre class="text-start bg-dark text-warning p-3 rounded small mb-4" style="max-width:800px;margin:0 auto;overflow:auto;"><?= htmlspecialchars($e->getMessage() . "\nin " . $e->getFile() . ':' . $e->getLine()) ?></pre>
        <?php endif; ?>
        <a href="<?= defined('APP_URL') ? APP_URL : '/' ?>/dashboard" class="btn btn-primary px-4">
            <i class="bi bi-house me-1"></i>Go to Dashboard
        </a>
        <a href="javascript:history.back()" class="btn btn-outline-secondary ms-2 px-4">
            <i class="bi bi-arrow-left me-1"></i>Go Back
        </a>
    </div>
</body>
</html>
