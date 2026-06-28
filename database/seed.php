<?php
/**
 * NMS Sample Data Seeder
 * Run from CLI: php database/seed.php
 */

define('BASE_PATH', dirname(__DIR__));
require BASE_PATH . '/config/config.php';
require BASE_PATH . '/core/Database.php';
require BASE_PATH . '/core/Session.php';
require BASE_PATH . '/core/Model.php';
require BASE_PATH . '/app/helpers/DateHelper.php';
require BASE_PATH . '/app/helpers/ZScoreHelper.php';

// Bootstrap a fake session so created_by FK can reference admin (id=1)
$CREATED_BY = 1;

$db = Core\Database::getInstance();
echo "Connected to database.\n";

// ── Beneficiaries ────────────────────────────────────────────────
$beneficiaries = [
    // name                        dob           sex     barangay         purok     4ps  philhealth
    ['Reyes',    'Maria Clara',  'L', '2023-07-12', 'Female', 'Poblacion',       'Purok 1', 1, 'Indigent'],
    ['Santos',   'Juan',         'A', '2022-03-05', 'Male',   'Poblacion',       'Purok 2', 0, 'Member'],
    ['Garcia',   'Ana',          'B', '2024-01-18', 'Female', 'San Jose',        'Purok 1', 1, 'Indigent'],
    ['Cruz',     'Pedro',        'C', '2021-11-22', 'Male',   'San Jose',        'Purok 3', 0, 'Non-member'],
    ['Mendoza',  'Rosa',         'D', '2023-05-30', 'Female', 'Santa Cruz',      'Purok 2', 1, 'Indigent'],
    ['Bautista', 'Jose Jr.',     'E', '2022-08-14', 'Male',   'Santa Cruz',      'Purok 1', 0, 'Member'],
    ['Aquino',   'Liza',         'F', '2024-06-03', 'Female', 'Bagong Barrio',   'Purok 4', 1, 'Indigent'],
    ['Ramos',    'Carlos',       'G', '2023-02-17', 'Male',   'Bagong Barrio',   'Purok 2', 1, 'Indigent'],
    ['Villanueva','Diana',       'H', '2021-09-08', 'Female', 'Magsaysay',       'Purok 3', 0, 'Member'],
    ['Dela Cruz','Marco',        'I', '2022-12-25', 'Male',   'Magsaysay',       'Purok 1', 0, 'Non-member'],
    ['Flores',   'Nina',         'J', '2020-04-15', 'Female', 'Del Pilar',       'Purok 2', 1, 'Indigent'],
    ['Aguilar',  'Luis',         'K', '2024-10-01', 'Male',   'Del Pilar',       'Purok 1', 0, 'Member'],
    ['Hernandez','Teresa',       'L', '2023-08-20', 'Female', 'Rizal',           'Purok 3', 1, 'Indigent'],
    ['Castillo', 'Ramon',        'M', '2022-06-11', 'Male',   'Rizal',           'Purok 2', 0, 'Member'],
    ['Torres',   'Carmela',      'N', '2024-03-07', 'Female', 'Poblacion',       'Purok 4', 1, 'Indigent'],
    ['Navarro',  'Roberto',      'O', '2021-07-19', 'Male',   'San Jose',        'Purok 1', 0, 'Non-member'],
    ['Gomez',    'Isabella',     'P', '2023-12-02', 'Female', 'Santa Cruz',      'Purok 3', 1, 'Indigent'],
    ['Morales',  'Eduardo',      'Q', '2022-04-28', 'Male',   'Bagong Barrio',   'Purok 2', 0, 'Member'],
    ['Jimenez',  'Patricia',     'R', '2024-08-15', 'Female', 'Magsaysay',       'Purok 1', 1, 'Indigent'],
    ['Reyes',    'Kenneth',      'S', '2020-11-30', 'Male',   'Del Pilar',       'Purok 4', 0, 'Non-member'],
];

// Weights to produce various nutritional statuses
// [weight_kg, height_cm, muac_cm]  — chosen to produce SUW/UW/Normal/OW mix
$measurements = [
    // SUW — very underweight
    [6.8,  72.0, 10.5],   // Reyes Maria Clara, 30mo F → SUW
    [9.5,  82.0, 11.0],   // Santos Juan, 46mo M → SUW
    // UW
    [6.2,  64.0, 11.5],   // Garcia Ana, 24mo F → UW
    [11.2, 88.5, 12.0],   // Cruz Pedro, 50mo M → UW
    [8.1,  77.0, 12.0],   // Mendoza Rosa, 32mo F → UW
    // Normal
    [11.5, 87.0, 14.5],   // Bautista Jose Jr., 41mo M → Normal
    [7.1,  66.0, 13.5],   // Aquino Liza, 19mo F → Normal
    [10.8, 84.0, 14.0],   // Ramos Carlos, 35mo M → Normal
    [15.2, 98.0, 15.0],   // Villanueva Diana, 52mo F → Normal
    [13.5, 92.5, 14.5],   // Dela Cruz Marco, 37mo M → Normal
    [17.8, 108.0, 15.5],  // Flores Nina, 71mo F → Normal
    [8.5,  72.0, 14.0],   // Aguilar Luis, 15mo M → Normal
    // UW
    [8.4,  76.0, 11.8],   // Hernandez Teresa, 29mo F → UW
    // Normal/OW
    [15.5, 93.0, 15.5],   // Castillo Ramon, 43mo M → Normal
    [7.8,  68.0, 14.0],   // Torres Carmela, 22mo F → Normal
    [19.0, 110.0, 16.0],  // Navarro Roberto, 56mo M → OW
    [9.5,  78.0, 13.5],   // Gomez Isabella, 25mo F → Normal
    [13.8, 90.0, 15.0],   // Morales Eduardo, 45mo M → Normal
    [6.5,  65.0, 12.5],   // Jimenez Patricia, 17mo F → UW
    [22.0, 112.0, 17.0],  // Reyes Kenneth, 64mo M → OW
];

$assessmentDate = '2026-01-15'; // January OPT period

echo "Inserting beneficiaries and assessments...\n";

$insertedIds = [];
foreach ($beneficiaries as $i => $b) {
    [$lastName, $firstName, $midInitial, $dob, $sex, $barangay, $purok, $is4ps, $philhealth] = $b;

    // Check if already exists
    $existing = $db->prepare("SELECT id FROM beneficiaries WHERE last_name=? AND first_name=? AND date_of_birth=?");
    $existing->execute([$lastName, $firstName, $dob]);
    if ($row = $existing->fetch()) {
        echo "  [SKIP] $lastName, $firstName already exists (id={$row['id']})\n";
        $insertedIds[] = $row['id'];
        continue;
    }

    $stmt = $db->prepare(
        "INSERT INTO beneficiaries
         (last_name, first_name, middle_name, date_of_birth, sex, barangay, purok_zone,
          is_4ps_member, philhealth_status, income_classification, source, created_by)
         VALUES (?,?,?,?,?,?,?,?,?,?,?,?)"
    );
    $stmt->execute([
        $lastName, $firstName, $midInitial . '.', $dob, $sex, $barangay, $purok,
        $is4ps, $philhealth,
        $is4ps ? 'Poor' : 'Near Poor',
        'Walk-in', $CREATED_BY,
    ]);
    $bid = (int)$db->lastInsertId();
    $insertedIds[] = $bid;
    echo "  [OK] Inserted beneficiary: $lastName, $firstName (id=$bid)\n";
}

// ── Assessments ──────────────────────────────────────────────────
echo "\nInserting assessments...\n";
$assessmentIds = [];

foreach ($insertedIds as $i => $bid) {
    // Skip if already has assessment on this date
    $check = $db->prepare("SELECT id FROM assessments WHERE beneficiary_id=? AND assessment_date=?");
    $check->execute([$bid, $assessmentDate]);
    if ($existing = $check->fetch()) {
        echo "  [SKIP] Beneficiary $bid already has assessment on $assessmentDate\n";
        $assessmentIds[] = $existing['id'];
        continue;
    }

    $b = $db->prepare("SELECT * FROM beneficiaries WHERE id=?")->execute([$bid])
        ?: null;
    $bRow = $db->query("SELECT * FROM beneficiaries WHERE id=$bid")->fetch();
    if (!$bRow) continue;

    [$weightKg, $heightCm, $muac] = $measurements[$i] ?? [10.0, 80.0, 13.0];

    $ageMonths = DateHelper::ageInMonths($bRow['date_of_birth'], $assessmentDate);
    $period    = 'January';
    $year      = 2026;

    $wfa = ZScoreHelper::computeWFA($weightKg, $ageMonths, $bRow['sex']);
    $status = $wfa !== null ? ZScoreHelper::classifyWFA($wfa) : 'Normal';
    $hfa = $hfaStatus = $wflh = $wflhStatus = null;
    if ($heightCm) {
        $hfa = ZScoreHelper::computeHFA($heightCm, $ageMonths, $bRow['sex']);
        $hfaStatus = $hfa !== null ? ZScoreHelper::classifyHFA($hfa) : null;
        $w = ZScoreHelper::computeWFLH($weightKg, $heightCm, $bRow['sex']);
        if ($w) { $wflh = $w['zscore']; $wflhStatus = $w['status']; }
    }

    $stmt = $db->prepare(
        "INSERT INTO assessments
         (beneficiary_id, assessment_date, age_in_months, weight_kg, height_cm, muac_cm,
          weight_for_age_zscore, height_for_age_zscore, hfa_status,
          wflh_zscore, wflh_status, nutritional_status,
          period, assessment_year, assessed_by, created_by)
         VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)"
    );
    $stmt->execute([
        $bid, $assessmentDate, $ageMonths, $weightKg, $heightCm, $muac,
        $wfa !== null ? round($wfa, 3) : null,
        $hfa !== null ? round($hfa, 3) : null,
        $hfaStatus,
        $wflh, $wflhStatus,
        $status,
        $period, $year,
        'BHW ' . $bRow['barangay'],
        $CREATED_BY,
    ]);
    $aid = (int)$db->lastInsertId();
    $assessmentIds[] = $aid;
    echo "  [OK] Assessment for $bid ({$bRow['last_name']}, {$bRow['first_name']}): $status (Z=" . ($wfa !== null ? round($wfa,2) : 'N/A') . ") age={$ageMonths}mo wt={$weightKg}kg\n";
}

// ── DSP Enrollments (UW and SUW children) ───────────────────────
echo "\nInserting DSP enrollments...\n";
foreach ($assessmentIds as $aid) {
    $a = $db->query("SELECT * FROM assessments WHERE id=$aid")->fetch();
    if (!$a || !in_array($a['nutritional_status'], ['UW','SUW'])) continue;

    $existing = $db->prepare("SELECT id FROM program_enrollments WHERE beneficiary_id=? AND program='DSP' AND status='Active'");
    $existing->execute([$a['beneficiary_id']]);
    if ($existing->fetch()) {
        echo "  [SKIP] Beneficiary {$a['beneficiary_id']} already in DSP\n";
        continue;
    }

    $interventions = ['RUSF','RUTF','Supplementary Feeding','Health Education'];
    $intervention  = $interventions[array_rand($interventions)];

    $db->prepare(
        "INSERT INTO program_enrollments
         (beneficiary_id, program, enrollment_date, status, cycle_year, intervention_type, pre_weight_kg, enrolled_by)
         VALUES (?,?,?,?,?,?,?,?)"
    )->execute([
        $a['beneficiary_id'], 'DSP', $assessmentDate, 'Active', 2026,
        $intervention, $a['weight_kg'], $CREATED_BY,
    ]);
    $b = $db->query("SELECT last_name, first_name FROM beneficiaries WHERE id={$a['beneficiary_id']}")->fetch();
    echo "  [OK] DSP enrolled: {$b['last_name']}, {$b['first_name']} — $intervention\n";
}

// Add a couple of completed DSP enrollments for outcome data
$completedEnrollments = [
    // beneficiary_id 1 (if it exists), pre_weight, post_weight, status
    [1, '2025-07-01', '2025-10-01', 'Completed', 7.2, 9.1, 'RUSF'],
];
foreach ($completedEnrollments as [$bid, $enrollDate, $endDate, $status, $preWt, $postWt, $type]) {
    $existing = $db->prepare("SELECT id FROM program_enrollments WHERE beneficiary_id=? AND program='DSP' AND enrollment_date=?");
    $existing->execute([$bid, $enrollDate]);
    if ($existing->fetch()) continue;
    $db->prepare(
        "INSERT INTO program_enrollments
         (beneficiary_id, program, enrollment_date, end_date, status, cycle_year, intervention_type, pre_weight_kg, post_weight_kg, enrolled_by)
         VALUES (?,?,?,?,?,?,?,?,?,?)"
    )->execute([$bid, 'DSP', $enrollDate, $endDate, $status, 2025, $type, $preWt, $postWt, $CREATED_BY]);
    echo "  [OK] DSP completed record added for beneficiary $bid\n";
}

// ── OPT Program Enrollments ──────────────────────────────────────
echo "\nInserting OPT enrollments...\n";
foreach ($assessmentIds as $aid) {
    $a = $db->query("SELECT * FROM assessments WHERE id=$aid")->fetch();
    if (!$a) continue;
    $existing = $db->prepare("SELECT id FROM program_enrollments WHERE beneficiary_id=? AND program='OPT' AND cycle_year=2026");
    $existing->execute([$a['beneficiary_id']]);
    if ($existing->fetch()) continue;
    $db->prepare(
        "INSERT INTO program_enrollments (beneficiary_id, program, enrollment_date, status, cycle_year, enrolled_by)
         VALUES (?,?,?,?,?,?)"
    )->execute([$a['beneficiary_id'], 'OPT', $assessmentDate, 'Active', 2026, $CREATED_BY]);
}
echo "  [OK] OPT enrollments created.\n";

// ── Vitamin A Records ────────────────────────────────────────────
echo "\nInserting Vitamin A records...\n";
$vitaminADate = '2026-02-10';
foreach ($insertedIds as $i => $bid) {
    if ($i % 3 === 0) continue; // skip every 3rd to leave some uncovered

    $existing = $db->prepare("SELECT id FROM vitamin_a_records WHERE beneficiary_id=? AND round='February' AND year=2026");
    $existing->execute([$bid]);
    if ($existing->fetch()) continue;

    $bRow = $db->query("SELECT * FROM beneficiaries WHERE id=$bid")->fetch();
    $ageMonths = DateHelper::ageInMonths($bRow['date_of_birth'], $vitaminADate);
    if ($ageMonths < 6 || $ageMonths > 59) continue;

    $dosage  = $ageMonths < 12 ? 100000 : 200000;
    $capsule = $ageMonths < 12 ? 'Blue'  : 'Red';

    $db->prepare(
        "INSERT INTO vitamin_a_records
         (beneficiary_id, distribution_date, round, year, dosage_iu, capsule_color, administered_by, created_by)
         VALUES (?,?,?,?,?,?,?,?)"
    )->execute([
        $bid, $vitaminADate, 'February', 2026,
        $dosage, $capsule,
        'BHW ' . $bRow['barangay'],
        $CREATED_BY,
    ]);
    echo "  [OK] Vitamin A: {$bRow['last_name']}, {$bRow['first_name']} — {$dosage} IU ($capsule)\n";
}

// ── MNP Records ─────────────────────────────────────────────────
echo "\nInserting MNP records...\n";
$mnpDate = '2026-01-20';
foreach ($insertedIds as $i => $bid) {
    if ($i % 2 !== 0) continue; // alternate

    $bRow = $db->query("SELECT * FROM beneficiaries WHERE id=$bid")->fetch();
    $ageMonths = DateHelper::ageInMonths($bRow['date_of_birth'], $mnpDate);
    if ($ageMonths < 6 || $ageMonths > 23) continue;

    $existing = $db->prepare("SELECT id FROM mnp_records WHERE beneficiary_id=? AND year=2026");
    $existing->execute([$bid]);
    if ($existing->fetch()) continue;

    $ageGroup = $ageMonths < 12 ? '6-11 months' : '12-23 months';
    $db->prepare(
        "INSERT INTO mnp_records
         (beneficiary_id, given_by, date_given, year, age_group, completed_routine, notes)
         VALUES (?,?,?,?,?,?,?)"
    )->execute([
        $bid, $CREATED_BY, $mnpDate, 2026, $ageGroup,
        rand(0,1), 'Given during home visit.',
    ]);
    echo "  [OK] MNP: {$bRow['last_name']}, {$bRow['first_name']} — $ageGroup\n";
}

// ── LNS-SQ Records ──────────────────────────────────────────────
echo "\nInserting LNS-SQ records...\n";
$lnsDate = '2026-01-25';
foreach ($insertedIds as $i => $bid) {
    if ($i % 2 === 0) continue; // other alternates

    $bRow = $db->query("SELECT * FROM beneficiaries WHERE id=$bid")->fetch();
    $ageMonths = DateHelper::ageInMonths($bRow['date_of_birth'], $lnsDate);
    if ($ageMonths < 6 || $ageMonths > 23) continue;

    $existing = $db->prepare("SELECT id FROM lns_sq_records WHERE beneficiary_id=? AND year=2026");
    $existing->execute([$bid]);
    if ($existing->fetch()) continue;

    $ageGroup = $ageMonths < 12 ? '6-11 months' : '12-23 months';
    $db->prepare(
        "INSERT INTO lns_sq_records
         (beneficiary_id, given_by, date_given, year, age_group, completed_routine, notes)
         VALUES (?,?,?,?,?,?,?)"
    )->execute([
        $bid, $CREATED_BY, $lnsDate, 2026, $ageGroup,
        rand(0,1), 'Distributed during feeding session.',
    ]);
    echo "  [OK] LNS-SQ: {$bRow['last_name']}, {$bRow['first_name']} — $ageGroup\n";
}

// ── Summary ──────────────────────────────────────────────────────
echo "\n=== Done! Summary ===\n";
$counts = $db->query(
    "SELECT
        (SELECT COUNT(*) FROM beneficiaries WHERE deleted_at IS NULL) as beneficiaries,
        (SELECT COUNT(*) FROM assessments) as assessments,
        (SELECT COUNT(*) FROM program_enrollments WHERE program='DSP') as dsp,
        (SELECT COUNT(*) FROM program_enrollments WHERE program='OPT') as opt,
        (SELECT COUNT(*) FROM vitamin_a_records) as vitamin_a,
        (SELECT COUNT(*) FROM mnp_records) as mnp,
        (SELECT COUNT(*) FROM lns_sq_records) as lns_sq"
)->fetch();
foreach ($counts as $k => $v) {
    if (!is_int($k)) echo "  $k: $v\n";
}
