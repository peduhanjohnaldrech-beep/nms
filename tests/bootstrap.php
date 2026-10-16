<?php

define('BASE_PATH', dirname(__DIR__));

// Load config without database (tests don't need DB connection)
if (file_exists(BASE_PATH . '/config/config.php')) {
    require BASE_PATH . '/config/config.php';
}

require BASE_PATH . '/app/helpers/DateHelper.php';
require BASE_PATH . '/app/helpers/ZScoreHelper.php';
require BASE_PATH . '/app/helpers/ReportCache.php';
