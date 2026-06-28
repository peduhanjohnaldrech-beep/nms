<?php

namespace App\Helpers;

use Core\Database;

/**
 * Generates a filled eOPT Plus Community Level Tool Excel file.
 *
 * Uses direct ZIP + string/regex manipulation so the template's logos,
 * styles, namespace declarations, and merged cells are preserved byte-for-byte.
 */
class EoptExport
{
    private \PDO   $db;
    private int    $year;
    private string $period;
    private string $barangay;

    // Header metadata derived from beneficiary data
    private string $municipality = '';
    private string $province     = '';
    private string $region       = '';
    private int    $ipTotal      = 0;
    private int    $ipMale       = 0;
    private int    $ipFemale     = 0;
    private string $ipGroups     = '';

    public function __construct(int $year, string $period, string $barangay)
    {
        $this->year     = $year;
        $this->period   = $period;
        $this->barangay = $barangay;
        $this->db       = Database::getInstance();
    }

    /**
     * Build the filled workbook and return a path to a temp .xlsx file.
     * Caller is responsible for streaming and deleting it.
     */
    public function generate(): string
    {
        $src = BASE_PATH . '/storage/templates/eopt_slim.xlsx';
        $tmp = tempnam(sys_get_temp_dir(), 'eopt_') . '.xlsx';
        copy($src, $tmp);

        $zip = new \ZipArchive();
        $zip->open($tmp);

        // Remove calcChain — it references formula cells we're overwriting with values.
        // Excel will silently regenerate it on open.
        $zip->deleteName('xl/calcChain.xml');

        $sheetMap = $this->getSheetFileMap($zip);

        [$counts, $children] = $this->fetchData();

        $this->writeSummary($zip, $sheetMap, $counts);
        $this->writeForm1A($zip, $sheetMap, $counts);
        $this->writeForm1B($zip, $sheetMap, $children);
        $this->writeLists($zip, $sheetMap, $children);
        $this->writeBNSPrintout($zip, $sheetMap, $children);
        $this->writeNutStatusTool($zip, $sheetMap, $children);
        $this->writeDataExport($zip, $sheetMap, $counts);

        $zip->close();
        return $tmp;
    }

    // ─── Sheet name → file path map ───────────────────────────────────────────

    private function getSheetFileMap(\ZipArchive $zip): array
    {
        // Parse workbook.xml for rId → name
        $wbXml  = $zip->getFromName('xl/workbook.xml');
        $relsXml = $zip->getFromName('xl/_rels/workbook.xml.rels');

        // Extract rId → target from rels
        preg_match_all('/Id="([^"]+)"[^>]+Target="([^"]+)"/', $relsXml, $rm, PREG_SET_ORDER);
        $ridToPath = [];
        foreach ($rm as $m) {
            $ridToPath[$m[1]] = 'xl/' . $m[2];
        }

        // Extract name → rId from workbook
        preg_match_all('/<sheet\s[^>]*name="([^"]+)"[^>]*r:id="([^"]+)"/', $wbXml, $sm, PREG_SET_ORDER);
        // Also handle id before name
        if (empty($sm)) {
            preg_match_all('/<sheet\s[^>]*r:id="([^"]+)"[^>]*name="([^"]+)"/', $wbXml, $sm2, PREG_SET_ORDER);
            $map = [];
            foreach ($sm2 as $m) {
                $rid = $m[1]; $name = $m[2];
                if (isset($ridToPath[$rid])) $map[$name] = $ridToPath[$rid];
            }
            return $map;
        }

        $map = [];
        foreach ($sm as $m) {
            $name = $m[1]; $rid = $m[2];
            if (isset($ridToPath[$rid])) $map[$name] = $ridToPath[$rid];
        }
        return $map;
    }

    // ─── Data retrieval ────────────────────────────────────────────────────────

    private function fetchData(): array
    {
        $params = [$this->year];
        $where  = 'a.assessment_year = ?';
        if ($this->period)   { $where .= ' AND a.period = ?';   $params[] = $this->period; }
        if ($this->barangay) { $where .= ' AND b.barangay = ?'; $params[] = $this->barangay; }

        $stmt = $this->db->prepare(
            "SELECT b.last_name, b.first_name, b.mother_name, b.purok_zone,
                    b.sex, b.date_of_birth, b.is_indigenous_people, b.ip_group,
                    b.city_municipality, b.province, b.region,
                    a.age_in_months, a.weight_kg, a.height_cm,
                    a.nutritional_status, a.hfa_status, a.wflh_status,
                    a.assessment_date
             FROM assessments a
             JOIN beneficiaries b ON b.id = a.beneficiary_id
             WHERE b.deleted_at IS NULL AND $where
               AND a.id = (
                   SELECT id FROM assessments a2
                   WHERE a2.beneficiary_id = a.beneficiary_id
                     AND a2.assessment_year = a.assessment_year
                     AND a2.period = a.period
                   ORDER BY a2.assessment_date DESC LIMIT 1
               )
             ORDER BY b.last_name, b.first_name"
        );
        $stmt->execute($params);
        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        // Derive location from first child that has data
        foreach ($rows as $r) {
            if (!$this->municipality && !empty($r['city_municipality'])) $this->municipality = $r['city_municipality'];
            if (!$this->province     && !empty($r['province']))          $this->province     = $r['province'];
            if (!$this->region       && !empty($r['region']))            $this->region       = $r['region'];
            if ($this->municipality && $this->province && $this->region) break;
        }

        // Compute indigenous preschooler stats
        $groups = [];
        foreach ($rows as $r) {
            if (!empty($r['is_indigenous_people'])) {
                $this->ipTotal++;
                if (strtolower($r['sex']) === 'female') $this->ipFemale++; else $this->ipMale++;
                if (!empty($r['ip_group'])) $groups[$r['ip_group']] = true;
            }
        }
        $this->ipGroups = implode(', ', array_keys($groups));

        $ageBands = [
            '0-5'   => [0,  5],
            '6-11'  => [6,  11],
            '12-23' => [12, 23],
            '24-35' => [24, 35],
            '36-47' => [36, 47],
            '48-59' => [48, 59],
        ];

        $counts = [];
        foreach (array_keys($ageBands) as $ag) {
            $counts[$ag] = ['M' => $this->emptyCount(), 'F' => $this->emptyCount()];
        }

        foreach ($rows as $r) {
            $age = (int)$r['age_in_months'];
            $ag  = null;
            foreach ($ageBands as $label => [$lo, $hi]) {
                if ($age >= $lo && $age <= $hi) { $ag = $label; break; }
            }
            if (!$ag) continue;

            $sex  = strtolower($r['sex']) === 'female' ? 'F' : 'M';
            $wfa  = $r['nutritional_status'] ?? '';
            $hfa  = $r['hfa_status']         ?? '';
            $wflh = $r['wflh_status']        ?? '';

            match ($wfa) {
                'Normal'    => $counts[$ag][$sex]['wfa_n']++,
                'UW'        => $counts[$ag][$sex]['wfa_uw']++,
                'SUW'       => $counts[$ag][$sex]['wfa_suw']++,
                'OW', 'OB' => $counts[$ag][$sex]['wfa_ow']++,
                default     => null,
            };
            match ($hfa) {
                'Normal'    => $counts[$ag][$sex]['hfa_n']++,
                'St'        => $counts[$ag][$sex]['hfa_st']++,
                'SSt'       => $counts[$ag][$sex]['hfa_sst']++,
                default     => null,
            };
            match ($wflh) {
                'Normal'    => $counts[$ag][$sex]['wflh_n']++,
                'MW'        => $counts[$ag][$sex]['wflh_mw']++,
                'SW'        => $counts[$ag][$sex]['wflh_sw']++,
                'OW'        => $counts[$ag][$sex]['wflh_ow']++,
                'OB'        => $counts[$ag][$sex]['wflh_ob']++,
                default     => null,
            };
        }

        return [$counts, $rows];
    }

    private function emptyCount(): array
    {
        return [
            'wfa_n'   => 0, 'wfa_ow'  => 0, 'wfa_uw'  => 0, 'wfa_suw' => 0,
            'hfa_n'   => 0, 'hfa_t'   => 0, 'hfa_st'  => 0, 'hfa_sst' => 0,
            'wflh_n'  => 0, 'wflh_ow' => 0, 'wflh_ob' => 0,
            'wflh_mw' => 0, 'wflh_sw' => 0,
        ];
    }

    // ─── Sheet writers ─────────────────────────────────────────────────────────

    private function writeSummary(\ZipArchive $zip, array $map, array $counts): void
    {
        if (!isset($map['Summary'])) return;
        $xml = $zip->getFromName($map['Summary']);

        // M/F input columns and their Total column (always fCol+1 alphabetically)
        $ageCols = [
            '0-5'   => ['B', 'C', 'D'],
            '6-11'  => ['E', 'F', 'G'],
            '12-23' => ['H', 'I', 'J'],
            '24-35' => ['K', 'L', 'M'],
            '36-47' => ['N', 'O', 'P'],
            '48-59' => ['Q', 'R', 'S'],
        ];
        $statusRows = [
            7  => 'wfa_n',  8  => 'wfa_ow',  9  => 'wfa_uw',  10 => 'wfa_suw',
            11 => 'hfa_n',  12 => 'hfa_t',   13 => 'hfa_st',  14 => 'hfa_sst',
            15 => 'wflh_n', 16 => 'wflh_ow', 17 => 'wflh_ob', 18 => 'wflh_mw', 19 => 'wflh_sw',
        ];

        $wfaTotal = 0; $hfaTotal = 0; $wflhTotal = 0;

        foreach ($statusRows as $row => $key) {
            $wRow = 0;
            foreach ($ageCols as $ag => [$mCol, $fCol, $tCol]) {
                $m = $counts[$ag]['M'][$key];
                $f = $counts[$ag]['F'][$key];
                $t = $m + $f;
                $xml = $this->setCellNum($xml, $mCol . $row, $m);
                $xml = $this->setCellNum($xml, $fCol . $row, $f);
                $xml = $this->setCellNum($xml, $tCol . $row, $t);  // Total column
                $wRow += $t;
            }
            $xml = $this->setCellNum($xml, 'W' . $row, $wRow);     // W = grand total across all ages

            if ($row <= 10)      $wfaTotal  += $wRow;
            elseif ($row <= 14)  $hfaTotal  += $wRow;
            else                 $wflhTotal += $wRow;
        }

        // Summary row 4 WFA/HFA/WFLH totals (referenced by Form1A O8/AK8/BM8)
        $xml = $this->setCellNum($xml, 'M4', $wfaTotal);
        $xml = $this->setCellNum($xml, 'P4', $hfaTotal);
        $xml = $this->setCellNum($xml, 'S4', $wflhTotal);

        // F1K (First 1000 Days = 0-23 months) Boys/Girls/Total per status row
        // AA/AB/AC reference Nut_StatusTool aggregate columns that we can't fill via formula chain
        $f1kAges = ['0-5', '6-11', '12-23'];
        foreach ($statusRows as $row => $key) {
            $f1kM = 0; $f1kF = 0;
            foreach ($f1kAges as $ag) {
                $f1kM += $counts[$ag]['M'][$key];
                $f1kF += $counts[$ag]['F'][$key];
            }
            $xml = $this->setCellNum($xml, 'AA' . $row, $f1kM);
            $xml = $this->setCellNum($xml, 'AB' . $row, $f1kF);
            $xml = $this->setCellNum($xml, 'AC' . $row, $f1kM + $f1kF);
        }

        // Summary header: B2=barangay, B3=municipality, H1=province, L1=region
        // L1 is referenced by Form1A rows 10 (region cells B10/AD10/BF10)
        $xml = $this->setCellStr($xml, 'B2', $this->barangay);
        $xml = $this->setCellStr($xml, 'B3', $this->municipality);
        $xml = $this->setCellStr($xml, 'H1', $this->province);
        $xml = $this->setCellStr($xml, 'L1', $this->region);

        $zip->addFromString($map['Summary'], $xml, \ZipArchive::FL_OVERWRITE);
    }

    private function writeForm1A(\ZipArchive $zip, array $map, array $counts): void
    {
        if (!isset($map['OPT_Form1A'])) return;
        $xml = $zip->getFromName($map['OPT_Form1A']);

        $ageRows = ['0-5'=>18,'6-11'=>21,'12-23'=>24,'24-35'=>27,'36-47'=>30,'48-59'=>33];

        foreach ($ageRows as $ag => $row) {
            $m = $counts[$ag]['M'];
            $f = $counts[$ag]['F'];
            foreach ([
                'B'=>$m['wfa_n'],   'D'=>$f['wfa_n'],
                'F'=>$m['wfa_uw'],  'H'=>$f['wfa_uw'],
                'J'=>$m['wfa_suw'], 'L'=>$f['wfa_suw'],
                'N'=>$m['wfa_ow'],  'P'=>$f['wfa_ow'],
                'AD'=>$m['hfa_n'],  'AF'=>$f['hfa_n'],
                'AH'=>$m['hfa_st'], 'AJ'=>$f['hfa_st'],
                'AL'=>$m['hfa_sst'],'AN'=>$f['hfa_sst'],
                'AP'=>$m['hfa_t'],  'AR'=>$f['hfa_t'],
                'BF'=>$m['wflh_n'], 'BH'=>$f['wflh_n'],
                'BJ'=>$m['wflh_mw'],'BL'=>$f['wflh_mw'],
                'BN'=>$m['wflh_sw'],'BP'=>$f['wflh_sw'],
                'BR'=>$m['wflh_ow'],'BT'=>$f['wflh_ow'],
                'BV'=>$m['wflh_ob'],'BX'=>$f['wflh_ob'],
            ] as $col => $val) {
                $xml = $this->setCellNum($xml, $col . $row, $val);
            }
        }

        // Form1A has 3 identical sections (WFA / HFA / WFLH) side-by-side.
        // Barangay row 7, Municipality row 8, Province row 9 (formula value cols C / AE / BG)
        foreach (['C7','AE7','BG7'] as $c) $xml = $this->setCellStr($xml, $c, $this->barangay);
        foreach (['C8','AE8','BG8'] as $c) $xml = $this->setCellStr($xml, $c, $this->municipality);
        foreach (['C9','AE9','BG9'] as $c) $xml = $this->setCellStr($xml, $c, $this->province);
        // IP counts row 10 (3 sections)
        foreach (['N10','AP10','BR10'] as $c) $xml = $this->setCellNum($xml, $c, $this->ipTotal);
        foreach (['P10','AR10','BT10'] as $c) $xml = $this->setCellNum($xml, $c, $this->ipMale);
        foreach (['R10','AT10','BV10'] as $c) $xml = $this->setCellNum($xml, $c, $this->ipFemale);
        // Year row 8 (3 sections — AA8/BC8/CI8 are formula cells referencing Nut_StatusTool)
        foreach (['AA8','BC8','CI8'] as $c) $xml = $this->setCellStr($xml, $c, (string)$this->year);
        // IP groups row 11 — N11 is the input cell; AP11/BR11 are formulas =N11 and auto-populate
        $xml = $this->setCellStr($xml, 'N11', $this->ipGroups);

        $zip->addFromString($map['OPT_Form1A'], $xml, \ZipArchive::FL_OVERWRITE);
    }

    private function writeForm1B(\ZipArchive $zip, array $map, array $children): void
    {
        if (!isset($map['OPT_Form1B'])) return;
        $xml = $zip->getFromName($map['OPT_Form1B']);

        $dataRows = [];
        foreach (array_values($children) as $i => $c) {
            if ($i >= 1000) break;
            $row = $i + 14;
            $dataRows[$row] = $this->buildRow($row, [
                'A' => ['n', $i + 1],
                'B' => ['s', $c['purok_zone'] ?? ''],
                'C' => ['s', $this->motherName($c['mother_name'])],
                'D' => ['s', $this->childName($c)],
                'E' => ['s', strtolower($c['sex']) === 'female' ? 'F' : 'M'],
                'F' => ['n', (int)$c['age_in_months']],
                'G' => ['s', $this->wfaCode($c['nutritional_status'] ?? '')],
                'H' => ['s', $this->hfaCode($c['hfa_status'] ?? '')],
                'I' => ['s', $this->wflhCode($c['wflh_status'] ?? '')],
            ]);
        }

        $xml = $this->replaceDataRows($xml, 14, 1013, $dataRows);

        // Restore header labels & values blanked by replaceDataRows step 1
        $xml = $this->setCellStr($xml, 'B3',  (string)$this->year);
        $xml = $this->setCellStr($xml, 'C8',  $this->barangay);
        $xml = $this->setCellStr($xml, 'A9',  'Municipality:');
        $xml = $this->setCellStr($xml, 'C9',  $this->municipality);
        $xml = $this->setCellStr($xml, 'B10', 'Province:');
        $xml = $this->setCellStr($xml, 'C10', $this->province);
        $xml = $this->setCellStr($xml, 'C11', $this->region);

        $zip->addFromString($map['OPT_Form1B'], $xml, \ZipArchive::FL_OVERWRITE);
    }

    private function writeLists(\ZipArchive $zip, array $map, array $children): void
    {
        $filters = [
            'List_UW'  => fn($c) => ($c['nutritional_status'] ?? '') === 'UW',
            'List_SUW' => fn($c) => ($c['nutritional_status'] ?? '') === 'SUW',
            'List_St'  => fn($c) => ($c['hfa_status']         ?? '') === 'St',
            'List_SSt' => fn($c) => ($c['hfa_status']         ?? '') === 'SSt',
            'List_MW'  => fn($c) => ($c['wflh_status']        ?? '') === 'MW',
            'List_SW'  => fn($c) => ($c['wflh_status']        ?? '') === 'SW',
        ];

        foreach ($filters as $sheetName => $filter) {
            if (!isset($map[$sheetName])) continue;
            $xml      = $zip->getFromName($map[$sheetName]);
            $filtered = array_values(array_filter($children, $filter));

            $dataRows = [];
            foreach ($filtered as $i => $c) {
                if ($i >= 1000) break;
                $row = $i + 9;

                // Col G and layout differ by sheet type (follows the Excel template):
                //   UW/SUW  → G=WFA classfn, H=Weight(kg)
                //   St/SSt  → G=HFA classfn, H=Weight(kg), I=Height(cm)
                //   MW/SW   → G=WFH classfn, H=Weight(kg)
                // Cells MUST be defined in ascending column order for valid XLSX.
                if (str_starts_with($sheetName, 'List_St') || str_starts_with($sheetName, 'List_SSt')) {
                    $cells = [
                        'A' => ['n', $i + 1],
                        'B' => ['s', $c['purok_zone'] ?? ''],
                        'C' => ['s', $this->motherName($c['mother_name'])],
                        'D' => ['s', $this->childName($c)],
                        'E' => ['s', strtolower($c['sex']) === 'female' ? 'F' : 'M'],
                        'F' => ['n', (int)$c['age_in_months']],
                        'G' => ['s', $this->hfaCode($c['hfa_status'] ?? '')],
                        'H' => ['n', $c['weight_kg'] !== null ? (float)$c['weight_kg'] : 0],
                        'I' => ['n', $c['height_cm'] !== null ? (float)$c['height_cm'] : 0],
                    ];
                } elseif (str_starts_with($sheetName, 'List_MW') || str_starts_with($sheetName, 'List_SW')) {
                    $cells = [
                        'A' => ['n', $i + 1],
                        'B' => ['s', $c['purok_zone'] ?? ''],
                        'C' => ['s', $this->motherName($c['mother_name'])],
                        'D' => ['s', $this->childName($c)],
                        'E' => ['s', strtolower($c['sex']) === 'female' ? 'F' : 'M'],
                        'F' => ['n', (int)$c['age_in_months']],
                        'G' => ['s', $this->wflhCode($c['wflh_status'] ?? '')],
                        'H' => ['n', $c['weight_kg'] !== null ? (float)$c['weight_kg'] : 0],
                    ];
                } else {
                    $cells = [
                        'A' => ['n', $i + 1],
                        'B' => ['s', $c['purok_zone'] ?? ''],
                        'C' => ['s', $this->motherName($c['mother_name'])],
                        'D' => ['s', $this->childName($c)],
                        'E' => ['s', strtolower($c['sex']) === 'female' ? 'F' : 'M'],
                        'F' => ['n', (int)$c['age_in_months']],
                        'G' => ['s', $this->wfaCode($c['nutritional_status'] ?? '')],
                        'H' => ['n', $c['weight_kg'] !== null ? (float)$c['weight_kg'] : 0],
                    ];
                }

                $dataRows[$row] = $this->buildRow($row, $cells);
            }

            $xml = $this->replaceDataRows($xml, 9, 1008, $dataRows);

            // Restore header labels & values blanked by replaceDataRows step 1
            // Row 3: Barangay (C3), Province label (D3), Province (E3), Region (I3)
            // Row 4: Municipality label (A4), Municipality (C4), Year (E4)
            $xml = $this->setCellStr($xml, 'C3', $this->barangay);
            $xml = $this->setCellStr($xml, 'D3', 'Province:');
            $xml = $this->setCellStr($xml, 'E3', $this->province);
            $xml = $this->setCellStr($xml, 'I3', $this->region);
            $xml = $this->setCellStr($xml, 'A4', 'Municipality:');
            $xml = $this->setCellStr($xml, 'C4', $this->municipality);
            $xml = $this->setCellStr($xml, 'E4', (string)$this->year);

            $zip->addFromString($map[$sheetName], $xml, \ZipArchive::FL_OVERWRITE);
        }
    }

    private function writeBNSPrintout(\ZipArchive $zip, array $map, array $children): void
    {
        if (!isset($map['BNS_Printout'])) return;
        $xml = $zip->getFromName($map['BNS_Printout']);

        $dataRows = [];
        foreach (array_values($children) as $i => $c) {
            if ($i >= 1000) break;
            $row = $i + 8;

            $dob          = $c['date_of_birth']   ? \DateTime::createFromFormat('Y-m-d', $c['date_of_birth'])   : null;
            $assessedDate = $c['assessment_date'] ? \DateTime::createFromFormat('Y-m-d', $c['assessment_date']) : null;

            $dataRows[$row] = $this->buildRow($row, [
                'A' => ['n', $i + 1],
                'B' => ['s', $c['purok_zone'] ?? ''],
                'C' => ['s', $this->motherName($c['mother_name'])],
                'D' => ['s', $this->childName($c)],
                'E' => ['s', ($c['is_indigenous_people'] ?? 0) ? 'YES' : 'NO'],
                'F' => ['s', strtolower($c['sex']) === 'female' ? 'F' : 'M'],
                'G' => ['s', $dob          ? $dob->format('m/d/Y')          : ''],
                'H' => ['s', $assessedDate ? $assessedDate->format('m/d/Y') : ''],
                'I' => ['n', $c['weight_kg'] !== null ? (float)$c['weight_kg'] : 0],
                'J' => ['n', $c['height_cm'] !== null ? (float)$c['height_cm'] : 0],
                'K' => ['n', (int)$c['age_in_months']],
            ]);
        }

        $xml = $this->replaceDataRows($xml, 8, 1007, $dataRows);

        // Restore header labels & values blanked by replaceDataRows step 1
        // Row 1: Barangay (D1), Municipality label (E1), Municipality (G1), Year (I1)
        $xml = $this->setCellStr($xml, 'D1', $this->barangay);
        $xml = $this->setCellStr($xml, 'E1', 'Municipality:');
        $xml = $this->setCellStr($xml, 'G1', $this->municipality);
        $xml = $this->setCellStr($xml, 'I1', (string)$this->year);

        $zip->addFromString($map['BNS_Printout'], $xml, \ZipArchive::FL_OVERWRITE);
    }

    private function writeNutStatusTool(\ZipArchive $zip, array $map, array $children): void
    {
        $key = null;
        foreach (array_keys($map) as $k) {
            if (stripos($k, 'Nut_Status') !== false) { $key = $k; break; }
        }
        if (!$key) return;
        $xml = $zip->getFromName($map[$key]);

        $dataRows = [];
        foreach (array_values($children) as $i => $c) {
            if ($i >= 1000) break;
            $row = $i + 10;

            $dob          = $c['date_of_birth']   ? \DateTime::createFromFormat('Y-m-d', $c['date_of_birth'])   : null;
            $assessedDate = $c['assessment_date'] ? \DateTime::createFromFormat('Y-m-d', $c['assessment_date']) : null;

            $dataRows[$row] = $this->buildRow($row, [
                'A' => ['n', $i + 1],
                'B' => ['s', $c['purok_zone'] ?? ''],
                'C' => ['s', $this->motherName($c['mother_name'])],
                'D' => ['s', $this->childName($c)],
                'E' => ['s', ($c['is_indigenous_people'] ?? 0) ? 'YES' : 'NO'],
                'F' => ['s', strtolower($c['sex']) === 'female' ? 'F' : 'M'],
                'G' => ['s', $dob          ? $dob->format('m/d/Y')          : ''],
                'H' => ['s', $assessedDate ? $assessedDate->format('m/d/Y') : ''],
                'I' => ['n', $c['weight_kg'] !== null ? (float)$c['weight_kg'] : 0],
                'J' => ['n', $c['height_cm'] !== null ? (float)$c['height_cm'] : 0],
                'K' => ['n', (int)$c['age_in_months']],
                'L' => ['s', $this->wfaCode($c['nutritional_status'] ?? '')],
                'M' => ['s', $this->hfaCode($c['hfa_status'] ?? '')],
                'N' => ['s', $this->wflhCode($c['wflh_status'] ?? '')],
            ]);
        }

        $xml = $this->replaceDataRows($xml, 10, 1009, $dataRows);

        // Year (M1) and period (O1) header
        $xml = $this->setCellStr($xml, 'M1', (string)$this->year);
        $xml = $this->setCellStr($xml, 'O1', $this->period);

        $zip->addFromString($map[$key], $xml, \ZipArchive::FL_OVERWRITE);
    }

    private function writeDataExport(\ZipArchive $zip, array $map, array $counts): void
    {
        // Locate Data-Export sheet (may also be spelled "Data_Export" etc.)
        $sheetKey = null;
        if (isset($map['Data-Export'])) {
            $sheetKey = 'Data-Export';
        } else {
            foreach (array_keys($map) as $k) {
                if (stripos($k, 'data') !== false && stripos($k, 'export') !== false) {
                    $sheetKey = $k; break;
                }
            }
        }
        if (!$sheetKey) return;

        $xml = $zip->getFromName($map[$sheetKey]);

        // Row 3, starting at column G (=7), has 156 cells:
        // For each age group, male then female, in this status order:
        //   WFA: N, OW, SUW, UW  (4)
        //   HFA: N, SSt, St, T   (4)
        //   WFLH: N, OW, Ob, SW, MW (5)
        //   = 13 per sex per age group × 2 sexes × 6 age groups = 156
        $statusKeys = [
            'wfa_n', 'wfa_ow', 'wfa_suw', 'wfa_uw',
            'hfa_n', 'hfa_sst', 'hfa_st', 'hfa_t',
            'wflh_n', 'wflh_ow', 'wflh_ob', 'wflh_sw', 'wflh_mw',
        ];
        $ageGroups = ['0-5', '6-11', '12-23', '24-35', '36-47', '48-59'];

        $colNum = 7; // G
        foreach ($ageGroups as $ag) {
            foreach (['M', 'F'] as $sex) {
                foreach ($statusKeys as $sk) {
                    $val = $counts[$ag][$sex][$sk] ?? 0;
                    $xml = $this->setCellNum($xml, $this->colLetter($colNum) . '3', $val);
                    $colNum++;
                }
            }
        }

        $zip->addFromString($map[$sheetKey], $xml, \ZipArchive::FL_OVERWRITE);
    }

    // ─── XML string helpers ────────────────────────────────────────────────────

    /**
     * Replace the value of an existing cell, removing any formula.
     * Preserves all existing attributes (style, etc.) byte-for-byte.
     */
    private function setCellNum(string $xml, string $ref, int|float $value): string
    {
        return preg_replace_callback(
            '/<c r="' . preg_quote($ref, '/') . '"([^>]*)>.*?<\/c>/s',
            function ($m) use ($ref, $value) {
                // Strip t="str" if present (formula string result type)
                $attrs = preg_replace('/\s*t="[^"]*"/', '', $m[1]);
                return '<c r="' . $ref . '"' . $attrs . '><v>' . $value . '</v></c>';
            },
            $xml
        );
    }

    /**
     * Set a string value in an existing header cell (regular or self-closing).
     * Strips any formula/type and writes an inlineStr cell.
     */
    private function setCellStr(string $xml, string $ref, string $value): string
    {
        if ($value === '') return $xml;
        $escaped = htmlspecialchars($value, ENT_XML1 | ENT_QUOTES, 'UTF-8');
        $replace = function (string $attrs) use ($ref, $escaped): string {
            $attrs = preg_replace('/\s*t="[^"]*"/', '', $attrs);
            return '<c r="' . $ref . '"' . $attrs . ' t="inlineStr"><is><t>' . $escaped . '</t></is></c>';
        };
        // Regular cell — exclude / from attrs so self-closing <c .../> isn't matched
        $result = preg_replace_callback(
            '/<c r="' . preg_quote($ref, '/') . '"([^>\/]*)\s*>.*?<\/c>/s',
            fn($m) => $replace($m[1]),
            $xml
        );
        if ($result !== $xml) return $result;
        // Self-closing cell (blanked by replaceDataRows step 1)
        return preg_replace_callback(
            '/<c r="' . preg_quote($ref, '/') . '"([^>]*)\/>/s',
            fn($m) => $replace($m[1]),
            $xml
        );
    }

    /**
     * Build a raw XML <row> string for the given row number and cell data.
     * $cells: ['A' => ['n'|'s', value], ...]
     */
    private function buildRow(int $rowNum, array $cells): string
    {
        $out = '<row r="' . $rowNum . '">';
        foreach ($cells as $col => [$type, $val]) {
            $ref = $col . $rowNum;
            if ($type === 's') {
                $str = (string)$val;
                if ($str === '') {
                    // Empty string — write blank cell; Excel rejects empty <t></t>
                    $out .= '<c r="' . $ref . '"/>';
                } else {
                    $escaped = htmlspecialchars($str, ENT_XML1 | ENT_QUOTES, 'UTF-8');
                    $out .= '<c r="' . $ref . '" t="inlineStr"><is><t>' . $escaped . '</t></is></c>';
                }
            } else {
                $out .= '<c r="' . $ref . '"><v>' . $val . '</v></c>';
            }
        }
        $out .= '</row>';
        return $out;
    }

    /**
     * Remove all rows from startRow onwards, then insert the provided data rows.
     * Also sanitizes header rows and cleans up orphaned shared-formula elements.
     */
    private function replaceDataRows(string $xml, int $startRow, int $endRow, array $dataRows): string
    {
        // 1. Sanitize formula cells in header rows (rows < startRow).
        //    The template has t="str" formula cells with empty <v/> cached values
        //    that Excel flags as "Cell information" errors.
        //    Strategy: match entire header rows first, then process cells WITHIN
        //    each row — prevents the cell regex from spanning across row boundaries.
        $xml = preg_replace_callback(
            '/<row r="(\d+)"[^>]*>.*?<\/row>/s',
            function ($rowMatch) use ($startRow) {
                if ((int)$rowMatch[1] >= $startRow) return $rowMatch[0]; // keep data rows

                // Process formula cells within this header row.
                // Use [^>\/]* for attributes to avoid matching self-closing <c ... />
                // tags (the / would be captured as part of attrs, causing the regex
                // to span across the next cell's </c> instead).
                return preg_replace_callback(
                    '/<c\b([^>\/]*)\s*>(.*?)<\/c>/s',
                    function ($m) {
                        $attrs   = $m[1];
                        $content = $m[2];
                        if (!str_contains($content, '<f')) return $m[0];

                        preg_match('/\br="([^"]+)"/i',      $attrs,   $refM);
                        preg_match('/\bs="(\d+)"/i',        $attrs,   $sM);
                        preg_match('/\bt="([^"]*)"/i',      $attrs,   $tM);
                        preg_match('/<v[^>]*>(.*?)<\/v>/s', $content, $vM);

                        $ref   = $refM[1] ?? '';
                        $style = isset($sM[1]) ? ' s="' . $sM[1] . '"' : '';
                        $type  = $tM[1]  ?? '';
                        $val   = $vM[1]  ?? '';

                        if ($type === 'str') {
                            // Always blank — cached values reference Nut_StatusTool which we don't fill.
                            // inlineStr in header rows (esp. inside merged cells) triggers Excel repair.
                            return '<c r="' . $ref . '"' . $style . '/>';
                        }

                        // Numeric/untyped formula — strip formula, keep value if useful
                        if ($val === '' || $val === '0') {
                            return '<c r="' . $ref . '"' . $style . '/>';
                        }
                        return '<c r="' . $ref . '"' . $style . '><v>' . $val . '</v></c>';
                    },
                    $rowMatch[0]
                );
            },
            $xml
        );

        // 2. Remove ALL rows from startRow onwards (not just up to endRow).
        //    The template may have footer/placeholder rows beyond endRow that
        //    would otherwise remain and push our new rows out of order.
        $xml = preg_replace_callback(
            '/<row r="(\d+)"[^>]*>.*?<\/row>/s',
            function ($m) use ($startRow) {
                return ((int)$m[1] >= $startRow) ? '' : $m[0];
            },
            $xml
        );

        // 3. Fix shared/array formula master cells in header rows whose ref
        //    range extended into the now-removed data area.
        $xml = preg_replace_callback(
            '/<f\b([^>]*)\bref="([A-Z]+(\d+):[A-Z]+(\d+))"([^>]*)>/i',
            function ($m) use ($startRow) {
                if ((int)$m[4] >= $startRow) {
                    $attrs = $m[1] . $m[5];
                    $attrs = preg_replace('/\s*\bref="[^"]*"/',       '', $attrs);
                    $attrs = preg_replace('/\s*\bsi="\d+"/',           '', $attrs);
                    $attrs = preg_replace('/\s*\bt="(shared|array)"/', '', $attrs);
                    return '<f' . $attrs . '>';
                }
                return $m[0];
            },
            $xml
        );

        // 4. Strip any remaining orphaned shared-formula slave elements.
        $xml = preg_replace('/<f\b[^>]*\bt="shared"[^>]*\/>/i', '', $xml);
        $xml = preg_replace('/<f\b[^>]*\bsi="\d+"[^>]*\/>/i',   '', $xml);

        // 5. Insert new data rows before </sheetData>
        $newRows = implode('', array_values($dataRows));

        if (str_contains($xml, '<sheetData/>')) {
            return str_replace('<sheetData/>', '<sheetData>' . $newRows . '</sheetData>', $xml);
        }
        return str_replace('</sheetData>', $newRows . '</sheetData>', $xml);
    }

    /** Convert 1-based column number to Excel letter(s). e.g. 1→A, 27→AA, 703→AAA */
    private function colLetter(int $n): string
    {
        $result = '';
        while ($n > 0) {
            $n--;
            $result = chr(65 + ($n % 26)) . $result;
            $n      = (int)($n / 26);
        }
        return $result;
    }

    // ─── Formatters ───────────────────────────────────────────────────────────

    private function wfaCode(string $s): string
    {
        return match ($s) { 'Normal' => 'N', 'UW' => 'UW', 'SUW' => 'SUW', 'OW', 'OB' => 'OW', default => '' };
    }

    private function hfaCode(string $s): string
    {
        return match ($s) { 'Normal' => 'N', 'St' => 'S', 'SSt' => 'SS', default => 'N' };
    }

    private function wflhCode(string $s): string
    {
        return match ($s) { 'Normal' => 'N', 'MW' => 'MW', 'SW' => 'SW', 'OW' => 'OW', 'OB' => 'Ob', default => 'N' };
    }

    private function motherName(?string $name): string
    {
        return $name ? strtoupper(trim($name)) : '';
    }

    private function childName(array $c): string
    {
        return strtoupper(trim($c['last_name'] ?? '')) . ', ' . ucfirst(strtolower(trim($c['first_name'] ?? '')));
    }
}
