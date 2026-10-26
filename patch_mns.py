import sys

path = 'C:/xampp/htdocs/nms/app/controllers/ProgramController.php'
with open(path, 'r', encoding='utf-8') as f:
    src = f.read()

# ── 1. mnsVitaminA ──────────────────────────────────────────────────────────
old1 = "            // Age at the actual distribution date, not today\n            $ageMonths = \\DateHelper::ageInMonths($dob, $date);\n            $dose  = $ageMonths <= 11 ? '100,000 IU Vitamin A (Blue)' : '200,000 IU Vitamin A (Red)';"
new1 = """            $ageMonths = \\DateHelper::ageInMonths($dob, $date);

            // Age eligibility
            if ($ageMonths < 6) {
                Session::flash('error', "Child is only {$ageMonths} month(s) old — Vitamin A eligibility starts at 6 months.");
                $this->redirect('/programs/mns?tab=vitaminA&round=' . urlencode($round) . '&year=' . $year);
                return;
            }
            if ($ageMonths >= 60) {
                Session::flash('error', "Child is {$ageMonths} months old — Vitamin A program ends at 60 months (5 years).");
                $this->redirect('/programs/mns?tab=vitaminA&round=' . urlencode($round) . '&year=' . $year);
                return;
            }

            // 6-month cooldown
            $lastVStmt = Database::getInstance()->prepare(
                "SELECT distribution_date FROM vitamin_a_records WHERE beneficiary_id = ? ORDER BY distribution_date DESC LIMIT 1"
            );
            $lastVStmt->execute([$bid]);
            $lastVDate = $lastVStmt->fetchColumn();
            if ($lastVDate) {
                $monthsSince = \\DateHelper::ageInMonths($lastVDate, $date);
                if ($monthsSince < 5) {
                    Session::flash('error',
                        'Vitamin A was last given on ' . \\DateHelper::formatDate($lastVDate, 'M j, Y') .
                        " ({$monthsSince} month(s) ago). Minimum 5-month gap required to prevent toxicity."
                    );
                    $this->redirect('/programs/mns?tab=vitaminA&round=' . urlencode($round) . '&year=' . $year);
                    return;
                }
            }

            $dose = $ageMonths <= 11 ? '100,000 IU Vitamin A (Blue)' : '200,000 IU Vitamin A (Red)';"""

if old1 in src:
    src = src.replace(old1, new1, 1)
    print('OK mnsVitaminA')
else:
    print('FAIL mnsVitaminA')
    sys.exit(1)

# ── 2. mnsMnp ───────────────────────────────────────────────────────────────
old2 = """            (new MnpRecord())->recordDistribution([
                'beneficiary_id'      => $bid,
                'given_by'            => Session::get('user_id'),
                'date_given'          => $date,
                'year'                => $year,
                'age_group'           => $_POST['age_group'] ?? '',"""
new2 = """            // Age validation
            $dobMnpStmt = Database::getInstance()->prepare('SELECT date_of_birth FROM beneficiaries WHERE id = ?');
            $dobMnpStmt->execute([$bid]);
            $dobMnp = $dobMnpStmt->fetchColumn();
            $ageMnp = $dobMnp ? \\DateHelper::ageInMonths($dobMnp, $date) : -1;
            if ($ageMnp >= 0 && $ageMnp < 6) {
                Session::flash('error', "Child is only {$ageMnp} month(s) old — MNP starts at 6 months.");
                $this->redirect('/programs/mns?tab=mnp&year=' . $year); return;
            }
            if ($ageMnp >= 24) {
                Session::flash('error', "Child is {$ageMnp} months old — MNP ends at 24 months.");
                $this->redirect('/programs/mns?tab=mnp&year=' . $year); return;
            }
            $autoMnpGroup = $ageMnp <= 11 ? '6-11 months' : '12-23 months';

            (new MnpRecord())->recordDistribution([
                'beneficiary_id'      => $bid,
                'given_by'            => Session::get('user_id'),
                'date_given'          => $date,
                'year'                => $year,
                'age_group'           => $autoMnpGroup,"""

if old2 in src:
    src = src.replace(old2, new2, 1)
    print('OK mnsMnp')
else:
    print('FAIL mnsMnp')
    sys.exit(1)

# ── 3. mnsLnsSq ─────────────────────────────────────────────────────────────
old3 = """            (new LnsSqRecord())->recordDistribution([
                'beneficiary_id'      => $bid,
                'given_by'            => Session::get('user_id'),
                'date_given'          => $date,
                'year'                => $year,
                'age_group'           => $_POST['age_group'] ?? '',"""
new3 = """            // Age validation
            $dobLnsStmt = Database::getInstance()->prepare('SELECT date_of_birth FROM beneficiaries WHERE id = ?');
            $dobLnsStmt->execute([$bid]);
            $dobLns = $dobLnsStmt->fetchColumn();
            $ageLns = $dobLns ? \\DateHelper::ageInMonths($dobLns, $date) : -1;
            if ($ageLns >= 0 && $ageLns < 6) {
                Session::flash('error', "Child is only {$ageLns} month(s) old — LNS-SQ starts at 6 months.");
                $this->redirect('/programs/mns?tab=lnssq&year=' . $year); return;
            }
            if ($ageLns >= 24) {
                Session::flash('error', "Child is {$ageLns} months old — LNS-SQ ends at 24 months.");
                $this->redirect('/programs/mns?tab=lnssq&year=' . $year); return;
            }
            // 30-day cooldown
            $lastLnsStmt = Database::getInstance()->prepare(
                'SELECT date_given FROM lns_sq_records WHERE beneficiary_id = ? ORDER BY date_given DESC LIMIT 1'
            );
            $lastLnsStmt->execute([$bid]);
            $lastLns = $lastLnsStmt->fetchColumn();
            if ($lastLns) {
                $daysSince = (int)(new \\DateTime($date))->diff(new \\DateTime($lastLns))->days;
                if ($daysSince < 25) {
                    Session::flash('error',
                        'LNS-SQ was already given on ' . \\DateHelper::formatDate($lastLns, 'M j, Y') .
                        " ({$daysSince} day(s) ago). Minimum 25-day gap required for monthly distribution."
                    );
                    $this->redirect('/programs/mns?tab=lnssq&year=' . $year); return;
                }
            }
            $autoLnsGroup = $ageLns <= 11 ? '6-11 months' : '12-23 months';

            (new LnsSqRecord())->recordDistribution([
                'beneficiary_id'      => $bid,
                'given_by'            => Session::get('user_id'),
                'date_given'          => $date,
                'year'                => $year,
                'age_group'           => $autoLnsGroup,"""

if old3 in src:
    src = src.replace(old3, new3, 1)
    print('OK mnsLnsSq')
else:
    print('FAIL mnsLnsSq')
    sys.exit(1)

with open(path, 'w', encoding='utf-8') as f:
    f.write(src)
print('All done.')
