<?php
// TEMPORARY DIAGNOSTIC — DELETE AFTER USE
ini_set('display_errors', 1);
error_reporting(E_ALL);

define('BASE_PATH', dirname(__DIR__));
require BASE_PATH . '/config/config.php';
require BASE_PATH . '/core/Database.php';

try {
    $pdo = \Core\Database::getInstance();

    echo "<h3>DB Connection: OK</h3>";

    // Check tables
    $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    echo "<b>Tables:</b> " . implode(', ', $tables) . "<br><br>";

    // Check dsp_monitoring exists
    if (in_array('dsp_monitoring', $tables)) {
        echo "<b>dsp_monitoring:</b> EXISTS<br>";
        $cols = $pdo->query("DESCRIBE dsp_monitoring")->fetchAll(PDO::FETCH_COLUMN);
        echo "Columns: " . implode(', ', $cols) . "<br><br>";
    } else {
        echo "<b style='color:red'>dsp_monitoring: MISSING — run migration!</b><br><br>";
    }

    // Test the getActive DSP query
    echo "<b>Testing getActive DSP query...</b><br>";
    $pdo->query("SELECT pe.id FROM program_enrollments pe
        JOIN beneficiaries b ON b.id = pe.beneficiary_id
        LEFT JOIN dsp_monitoring dm ON dm.id = (
            SELECT id FROM dsp_monitoring WHERE enrollment_id = pe.id
            AND validation_status = 'validated'
            ORDER BY monitoring_date DESC, id DESC LIMIT 1
        )
        WHERE pe.program = 'DSP' AND pe.status = 'Active'
        LIMIT 1");
    echo "getActive: OK<br>";

    // Test getReadyToDischarge query
    echo "<b>Testing getReadyToDischarge query...</b><br>";
    $pdo->query("SELECT pe.id FROM program_enrollments pe
        JOIN beneficiaries b ON b.id = pe.beneficiary_id
        LEFT JOIN dsp_monitoring dm ON dm.id = (
            SELECT id FROM dsp_monitoring WHERE enrollment_id = pe.id
            AND validation_status = 'validated'
            ORDER BY monitoring_date DESC, id DESC LIMIT 1
        )
        WHERE pe.program = 'DSP' AND pe.status = 'Active'
        AND (
            TIMESTAMPDIFF(MONTH, b.date_of_birth, NOW()) > 59
            OR (
                dm.nutritional_status NOT IN ('SUW','UW')
                AND (dm.wflh_status IS NULL OR dm.wflh_status NOT IN ('SW','MW'))
                AND dm.muac_cm IS NOT NULL
                AND 2 = (
                    SELECT COUNT(*) FROM dsp_monitoring dm_c
                    WHERE dm_c.enrollment_id = pe.id
                      AND dm_c.validation_status = 'validated'
                      AND dm_c.nutritional_status NOT IN ('SUW','UW')
                      AND (dm_c.wflh_status IS NULL OR dm_c.wflh_status NOT IN ('SW','MW'))
                      AND (SELECT COUNT(*) FROM dsp_monitoring dm_r
                           WHERE dm_r.enrollment_id = pe.id
                             AND dm_r.validation_status = 'validated'
                             AND (dm_r.monitoring_date > dm_c.monitoring_date
                                  OR (dm_r.monitoring_date = dm_c.monitoring_date AND dm_r.id > dm_c.id))
                          ) < 2
                )
            )
        )
        LIMIT 1");
    echo "getReadyToDischarge: OK<br>";

    // Test full model methods
    echo "<b>Testing model methods...</b><br>";
    require BASE_PATH . '/core/Model.php';
    require BASE_PATH . '/app/helpers/ZScoreHelper.php';
    require BASE_PATH . '/app/helpers/DateHelper.php';
    require BASE_PATH . '/app/models/ProgramEnrollment.php';
    require BASE_PATH . '/app/models/Beneficiary.php';
    require BASE_PATH . '/app/models/DspMonitoring.php';

    $enrollModel = new \App\Models\ProgramEnrollment();
    $beneModel   = new \App\Models\Beneficiary();

    echo "getEligibleForDSP: ";
    $eligible = $enrollModel->getEligibleForDSP();
    echo "OK (" . count($eligible) . " rows)<br>";

    echo "getActive DSP: ";
    $active = $enrollModel->getActive('DSP');
    echo "OK (" . count($active) . " rows)<br>";

    echo "getNotEnrolledInProgram DSP: ";
    $notEnrolled = $enrollModel->getNotEnrolledInProgram('DSP');
    echo "OK (" . count($notEnrolled) . " rows)<br>";

    echo "getReadyToDischarge: ";
    $discharge = $enrollModel->getReadyToDischarge();
    echo "OK (" . count($discharge) . " rows)<br>";

    echo "getHistory DSP: ";
    $history = $enrollModel->getHistory('DSP', date('Y'));
    echo "OK (" . count($history) . " rows)<br>";

    echo "getBarangays: ";
    $barangays = $beneModel->getBarangays();
    echo "OK (" . count($barangays) . " rows)<br>";

    echo "getPuroksByBarangay: ";
    $puroks = $beneModel->getPuroksByBarangay('');
    echo "OK (" . count($puroks) . " rows)<br>";

    echo "<br><b style='color:green'>All checks passed — issue is in the view file.</b><br>";

} catch (\Throwable $e) {
    echo "<b style='color:red'>ERROR: " . htmlspecialchars($e->getMessage()) . "</b><br>";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
}
