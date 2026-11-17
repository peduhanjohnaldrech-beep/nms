<?php
namespace Core;

class View
{
    public static function render(string $view, array $data = [], bool $withLayout = true): void
    {
        // Prevent browsers from caching HTML pages so stale data never shows
        // after navigating back or when new features are deployed.
        if (!headers_sent()) {
            header('Cache-Control: no-store');
        }

        // Make data keys available as variables
        extract($data);

        $viewFile = BASE_PATH . '/app/views/' . str_replace('.', '/', $view) . '.php';

        if (!file_exists($viewFile)) {
            die("View not found: {$viewFile}");
        }

        if ($withLayout) {
            ob_start();
            require $viewFile;
            $content = ob_get_clean();
            require BASE_PATH . '/app/views/layout.php';
        } else {
            require $viewFile;
        }
    }
}
