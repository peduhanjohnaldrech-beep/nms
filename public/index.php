<?php
define('BASE_PATH', dirname(__DIR__));

require BASE_PATH . '/config/config.php';
require BASE_PATH . '/core/Database.php';
require BASE_PATH . '/core/Session.php';
require BASE_PATH . '/core/Model.php';
require BASE_PATH . '/core/View.php';
require BASE_PATH . '/core/Controller.php';
require BASE_PATH . '/core/Router.php';
require BASE_PATH . '/app/helpers/DateHelper.php';
require BASE_PATH . '/app/helpers/ZScoreHelper.php';
require BASE_PATH . '/app/helpers/ActivityLog.php';

// Autoload app classes
spl_autoload_register(function (string $class) {
    $map = [
        'App\\Controllers\\' => BASE_PATH . '/app/controllers/',
        'App\\Models\\'      => BASE_PATH . '/app/models/',
        'App\\Helpers\\'     => BASE_PATH . '/app/helpers/',
    ];
    foreach ($map as $prefix => $dir) {
        if (str_starts_with($class, $prefix)) {
            $file = $dir . substr($class, strlen($prefix)) . '.php';
            if (file_exists($file)) require $file;
        }
    }
});

// Vendor autoload (for phpspreadsheet, dompdf)
$vendorAutoload = BASE_PATH . '/vendor/autoload.php';
if (file_exists($vendorAutoload)) {
    require $vendorAutoload;
}

Core\Session::start();

$router = new Core\Router();

// Auth
$router->get('/login',  'AuthController@showLogin');
$router->post('/login', 'AuthController@login');
$router->get('/logout', 'AuthController@logout');

// Dashboard
$router->get('/',          'DashboardController@index');
$router->get('/dashboard', 'DashboardController@index');

// Beneficiaries
$router->get('/beneficiaries',             'BeneficiaryController@index');
$router->get('/beneficiaries/create',      'BeneficiaryController@create');
$router->post('/beneficiaries/create',     'BeneficiaryController@create');
$router->get('/beneficiaries/followup',          'BeneficiaryController@followup');
$router->get('/beneficiaries/check-duplicate',   'BeneficiaryController@checkDuplicate');
$router->get('/beneficiaries/trash',             'BeneficiaryController@trash');
$router->post('/beneficiaries/{id}/restore',     'BeneficiaryController@restore');
$router->get('/beneficiaries/{id}',              'BeneficiaryController@show');
$router->get('/beneficiaries/{id}/edit',   'BeneficiaryController@edit');
$router->post('/beneficiaries/{id}/edit',  'BeneficiaryController@edit');
$router->post('/beneficiaries/{id}/delete','BeneficiaryController@delete');

// Assessments
$router->get('/assessments/create',    'AssessmentController@create');
$router->post('/assessments/create',   'AssessmentController@create');
$router->get('/assessments/batch',     'AssessmentController@batch');
$router->post('/assessments/batch',    'AssessmentController@batch');
$router->post('/assessments/{id}/delete', 'AssessmentController@delete');

// Programs
$router->get('/programs',             'ProgramController@index');
$router->get('/programs/opt',             'ProgramController@opt');
$router->get('/programs/dsp',             'ProgramController@dsp');
$router->post('/programs/dsp/enroll',     'ProgramController@dspEnroll');
$router->post('/programs/dsp/discharge',  'ProgramController@dspDischarge');
$router->post('/programs/dsp/update',     'ProgramController@dspUpdate');
$router->get('/programs/mns',            'ProgramController@mns');
$router->post('/programs/mns/vitamina',              'ProgramController@mnsVitaminA');
$router->post('/programs/mns/vita/{id}/delete',      'ProgramController@mnsVitaminADelete');
$router->post('/programs/mns/mnp',              'ProgramController@mnsMnp');
$router->post('/programs/mns/mnp/{id}/complete',  'ProgramController@mnsMnpComplete');
$router->post('/programs/mns/lnssq',             'ProgramController@mnsLnsSq');
$router->post('/programs/mns/lnssq/{id}/complete','ProgramController@mnsLnsSqComplete');

// Generic program routes (for custom programs added via admin)
$router->get('/programs/{code}',            'ProgramController@generic');
$router->post('/programs/{code}/enroll',    'ProgramController@genericEnroll');
$router->post('/programs/{code}/discharge', 'ProgramController@genericDischarge');
$router->post('/programs/{code}/update',    'ProgramController@genericUpdate');
$router->get('/programs/{code}/export',     'ProgramController@genericExport');

// Program Admin (manage programs)
$router->get('/programs-admin',               'ProgramsAdminController@index');
$router->get('/programs-admin/create',        'ProgramsAdminController@create');
$router->post('/programs-admin/create',       'ProgramsAdminController@create');
$router->get('/programs-admin/{id}/edit',     'ProgramsAdminController@edit');
$router->post('/programs-admin/{id}/edit',    'ProgramsAdminController@edit');
$router->post('/programs-admin/{id}/toggle',  'ProgramsAdminController@toggle');

// Dispensing Tracker
$router->get('/dispensing',         'DispensingController@index');
$router->get('/dispensing/create',  'DispensingController@create');
$router->post('/dispensing/create', 'DispensingController@create');
$router->get('/dispensing/export',  'DispensingController@export');

// Reports
$router->get('/reports',              'ReportController@index');
$router->get('/reports/opt',          'ReportController@opt');
$router->get('/reports/dsp',          'ReportController@dsp');
$router->get('/reports/mns',          'ReportController@mns');
$router->get('/reports/outcome',      'ReportController@outcome');
$router->get('/reports/summary',      'ReportController@summary');
$router->get('/reports/comparison',   'ReportController@comparison');
$router->get('/reports/distribution', 'ReportController@distribution');
$router->get('/reports/export',       'ReportController@export');
$router->get('/reports/export-eopt',  'ReportController@exportEopt');

// Import
$router->get('/import',                  'ImportController@index');
$router->get('/import/storage',                    'ImportController@storage');
$router->post('/import/storage/files/upload',      'ImportController@uploadOtherFile');
$router->post('/import/storage/files/gdrive',      'ImportController@uploadOtherFileFromGdrive');
$router->get('/import/storage/files/{id}/download','ImportController@downloadOtherFile');
$router->get('/import/storage/files/{id}/view',    'ImportController@viewOtherFile');
$router->post('/import/storage/files/{id}/delete', 'ImportController@deleteOtherFile');
$router->post('/import/storage/files/folders/create','ImportController@createOtherFolder');
$router->post('/import/storage/files/folders/delete','ImportController@deleteOtherFolder');
$router->get('/import/template',                   'ImportController@downloadTemplate');
$router->post('/import/upload',                    'ImportController@upload');
$router->post('/import/gdrive',                    'ImportController@uploadFromGdrive');
$router->post('/import/confirm',                   'ImportController@confirm');
$router->post('/import/folders/create',            'ImportController@createFolder');
$router->post('/import/folders/delete',            'ImportController@deleteFolder');
$router->get('/import/{id}/view',                  'ImportController@viewImportFile');
$router->get('/import/{id}/download',              'ImportController@download');
$router->post('/import/{id}/delete',               'ImportController@delete');

// Activity Log
$router->get('/activity', 'ActivityController@index');

// Help
$router->get('/help', 'HelpController@index');

// Demo Seeder (admin only)
$router->get('/admin/seed',       'SeederController@index');
$router->post('/admin/seed/run',  'SeederController@run');
$router->post('/admin/seed/clear','SeederController@clear');

// Backup (admin only — streams the SQLite file)
$router->get('/backup/download', 'BackupController@download');

// Users (admin only)
$router->get('/users',           'UserController@index');
$router->get('/users/create',    'UserController@create');
$router->post('/users/create',   'UserController@create');
$router->get('/users/{id}/edit', 'UserController@edit');
$router->post('/users/{id}/edit','UserController@edit');
$router->post('/users/{id}/delete',   'UserController@delete');
$router->post('/users/{id}/activate', 'UserController@activate');
$router->post('/users/{id}/destroy',  'UserController@destroy');

$router->dispatch();
