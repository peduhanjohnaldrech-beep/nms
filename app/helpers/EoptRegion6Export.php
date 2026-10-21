<?php

namespace App\Helpers;

use Core\Database;

/**
 * Fills the Region6 eOPT Plus Community Level Tool (.xltx) template.
 *
 * Strategy: only write to the Nut_StatusTool sheet (the primary data-entry
 * sheet). Every other sheet (Summary, Form1A/1B, Lists, BNS_Printout, etc.)
 * is formula-driven and auto-populates when Excel opens the file.
 *
 * Column layout for Nut_StatusTool data rows (row 10+):
 *   A=seq#  E=purok  F=mother  G=DOB  H=child_name  I=sex
 *   J=assessed_date  K=age_months  L=weight_kg  M=height_cm
 *   N=ip_status  P=WFA_status  Q=HFA_status  R=WFLH_status
 */
class EoptRegion6Export
{
    private \PDO   $db;
    private int    $year;
    private string $period;
    private string $barangay;

    private string $municipality = '';
    private string $province     = '';
    private string $region       = '';

    public function __construct(int $year, string $period, string $barangay)
    {
        $this->year     = $year;
        $this->period   = $period;
        $this->barangay = $barangay;
        $this->db       = Database::getInstance();
    }

    /**
     * Build the filled workbook and return a path to a temp .xlsx file.
     */
    public function generate(): string
    {
        $src = BASE_PATH . '/storage/templates/eopt_region6.xltx';
        $tmp = tempnam(sys_get_temp_dir(), 'eopt_r6_') . '.xlsx';
        copy($src, $tmp);

        $zip = new \ZipArchive();
        $zip->open($tmp);

        // Fix content type: xltx -> xlsx so Excel opens it as a workbook
        $ct = $zip->getFromName('[Content_Types].xml');
        $ct = str_replace(
            'application/vnd.openxmlformats-officedocument.spreadsheetml.template.main+xml',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml',
            $ct
        );
        $zip->addFromString('[Content_Types].xml', $ct, \ZipArchive::FL_OVERWRITE);

        // Delete calcChain so Excel recalculates all formulas on open
        $zip->deleteName('xl/calcChain.xml');

        $sheetMap = $this->getSheetFileMap($zip);

        $children = $this->fetchChildren();

        $this->writeNutStatusTool($zip, $sheetMap, $children);
        $this->writeBnsPrintoutHeader($zip, $sheetMap);
        $this->writeForm1AHeader($zip, $sheetMap);

        $zip->close();
        return $tmp;
    }

    // ─── Sheet map ────────────────────────────────────────────────────────────

    private function getSheetFileMap(\ZipArchive $zip): array
    {
        $wbXml   = $zip->getFromName('xl/workbook.xml');
        $relsXml = $zip->getFromName('xl/_rels/workbook.xml.rels');

        preg_match_all('/Id="([^"]+)"[^>]+Target="([^"]+)"/', $relsXml, $rm, PREG_SET_ORDER);
        $ridToPath = [];
        foreach ($rm as $m) {
            $ridToPath[$m[1]] = 'xl/' . $m[2];
        }

        preg_match_all('/<sheet\s[^>]*name="([^"]+)"[^>]*r:id="([^"]+)"/', $wbXml, $sm, PREG_SET_ORDER);
        if (empty($sm)) {
            preg_match_all('/<sheet\s[^>]*r:id="([^"]+)"[^>]*name="([^"]+)"/', $wbXml, $sm2, PREG_SET_ORDER);
            $map = [];
            foreach ($sm2 as $m) {
                if (isset($ridToPath[$m[1]])) $map[$m[2]] = $ridToPath[$m[1]];
            }
            return $map;
        }

        $map = [];
        foreach ($sm as $m) {
            if (isset($ridToPath[$m[2]])) $map[$m[1]] = $ridToPath[$m[2]];
        }
        return $map;
    }

    // ─── Data retrieval ───────────────────────────────────────────────────────

    private function fetchChildren(): array
    {
        $params = [$this->year];
        $where  = 'a.assessment_year = ?';
        if ($this->period)   { $where .= ' AND a.period = ?';   $params[] = $this->period; }
        if ($this->barangay) { $where .= ' AND b.barangay = ?'; $params[] = $this->barangay; }

        $stmt = $this->db->prepare(
            "SELECT b.last_name, b.first_name, b.mother_name, b.purok_zone,
                    b.sex, b.date_of_birth, b.is_indigenous_people,
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

        foreach ($rows as $r) {
            if (!$this->municipality && !empty($r['city_municipality'])) $this->municipality = $r['city_municipality'];
            if (!$this->province     && !empty($r['province']))          $this->province     = $r['province'];
            if (!$this->region       && !empty($r['region']))            $this->region       = $r['region'];
            if ($this->municipality && $this->province && $this->region) break;
        }

        return $rows;
    }

    // ─── Sheet writers ────────────────────────────────────────────────────────

    /**
     * Fill the BNS_Printout sheet header with location/year data.
     * K1  = Barangay (after J1='Barangay:')
     * BH6 = Year    (after BG6='Year:')
     * Population/PSGC fields are NOT filled — not tracked in NMS.
     */
    private function writeBnsPrintoutHeader(\ZipArchive $zip, array $map): void
    {
        if (!isset($map['BNS_Printout'])) return;
        $xml = $zip->getFromName($map['BNS_Printout']);

        $xml = $this->injectRowCells($xml, 1, [
            'K' => ['s', $this->barangay],
        ]);
        $xml = $this->injectRowCells($xml, 6, [
            'BH' => ['s', (string)$this->year],
        ]);

        $zip->addFromString($map['BNS_Printout'], $xml, \ZipArchive::FL_OVERWRITE);
    }

    /**
     * Fill OPT_Form1A header fields we can derive from our data.
     * E10  = Year of Last OPT Plus
     * AE11 = Date printed (today)
     * BE11 = Date printed (today, 3rd section copy)
     * Total/Estimated Population fields are NOT filled — not tracked in NMS.
     */
    private function writeForm1AHeader(\ZipArchive $zip, array $map): void
    {
        if (!isset($map['OPT_Form1A'])) return;
        $xml  = $zip->getFromName($map['OPT_Form1A']);
        $today = date('m/d/Y');

        // Row 10: Year of Last OPT Plus (E10), appears 3× across the 3 side-by-side sections
        $xml = $this->injectRowCells($xml, 10, [
            'E'  => ['s', (string)$this->year],
            'AG' => ['s', (string)$this->year],
            'BI' => ['s', (string)$this->year],
        ]);
        // Row 11: Date printed
        $xml = $this->injectRowCells($xml, 11, [
            'AE' => ['s', $today],
            'BE' => ['s', $today],
        ]);

        $zip->addFromString($map['OPT_Form1A'], $xml, \ZipArchive::FL_OVERWRITE);
    }

    private function writeNutStatusTool(\ZipArchive $zip, array $map, array $children): void
    {
        $key = null;
        foreach (array_keys($map) as $k) {
            if (stripos($k, 'Nut_Status') !== false) { $key = $k; break; }
        }
        if (!$key) return;

        $xml = $zip->getFromName($map[$key]);

        // Fill header cells (inject into header rows)
        $today = date('m/d/Y');
        $xml = $this->injectRowCells($xml, 6, [
            'C' => ['s', $this->barangay],
            'F' => ['s', $this->municipality],
            'N' => ['s', $this->province],
        ]);
        $xml = $this->injectRowCells($xml, 7, [
            'I' => ['s', $today],
            'M' => ['s', (string)$this->year],
        ]);
        $xml = $this->injectRowCells($xml, 8, [
            'E' => ['s', $this->barangay],
            'J' => ['s', $this->region],
        ]);

        // Fill data rows (10 to 1009) — preserve existing formula cells (AI+)
        $xml = preg_replace_callback(
            '/<row r="(\d+)"([^>]*)>(.*?)<\/row>/s',
            function ($m) use ($children) {
                $rowNum   = (int)$m[1];
                $rowAttrs = $m[2];
                $rowBody  = $m[3];

                if ($rowNum < 10 || $rowNum > 1009) {
                    return $m[0]; // leave header / footer rows untouched
                }

                $childIdx = $rowNum - 10;

                // Remove existing cells in data-input columns A-R (single-letter refs)
                $rowBody = preg_replace('/<c r="[A-R]\d+"[^>]*>.*?<\/c>/s', '', $rowBody);
                $rowBody = preg_replace('/<c r="[A-R]\d+"[^>]*\/>/s',        '', $rowBody);

                if (!isset($children[$childIdx])) {
                    // No child for this slot — blank row, keep formula cols
                    return '<row r="' . $rowNum . '"' . $rowAttrs . '>' . $rowBody . '</row>';
                }

                $c   = $children[$childIdx];
                $dob = $c['date_of_birth']   ? \DateTime::createFromFormat('Y-m-d', $c['date_of_birth'])   : null;
                $adm = $c['assessment_date'] ? \DateTime::createFromFormat('Y-m-d', $c['assessment_date']) : null;

                $cells = $this->buildRowCells($rowNum, [
                    'A' => ['n', $childIdx + 1],
                    'E' => ['s', $c['purok_zone'] ?? ''],
                    'F' => ['s', $this->motherName($c['mother_name'])],
                    'G' => ['s', $dob ? $dob->format('m/d/Y') : ''],
                    'H' => ['s', $this->childName($c)],
                    'I' => ['s', strtolower($c['sex']) === 'female' ? 'F' : 'M'],
                    'J' => ['s', $adm ? $adm->format('m/d/Y') : ''],
                    'K' => ['n', (int)$c['age_in_months']],
                    'L' => ['n', $c['weight_kg'] !== null ? (float)$c['weight_kg'] : 0],
                    'M' => ['n', $c['height_cm'] !== null ? (float)$c['height_cm'] : 0],
                    'N' => ['s', (!empty($c['is_indigenous_people'])) ? 'YES' : 'NO'],
                    'P' => ['s', $this->wfaCode($c['nutritional_status'] ?? '')],
                    'Q' => ['s', $this->hfaCode($c['hfa_status'] ?? '')],
                    'R' => ['s', $this->wflhCode($c['wflh_status'] ?? '')],
                ]);

                return '<row r="' . $rowNum . '"' . $rowAttrs . '>' . $cells . $rowBody . '</row>';
            },
            $xml
        );

        $zip->addFromString($map[$key], $xml, \ZipArchive::FL_OVERWRITE);
    }

    // ─── XML helpers ──────────────────────────────────────────────────────────

    /**
     * Inject/replace specific cells in an existing row.
     * Works for both existing cells and cells that don't yet exist in the row.
     */
    private function injectRowCells(string $xml, int $rowNum, array $cells): string
    {
        return preg_replace_callback(
            '/<row r="' . $rowNum . '"([^>]*)>(.*?)<\/row>/s',
            function ($m) use ($rowNum, $cells) {
                $attrs   = $m[1];
                $rowBody = $m[2];

                foreach ($cells as $col => [$type, $val]) {
                    $ref = $col . $rowNum;
                    // Remove existing cell with this exact ref (formula or blank)
                    $rq      = preg_quote($ref, '/');
                    $rowBody = preg_replace('/<c r="' . $rq . '"[^>\/]*\s*>.*?<\/c>/s', '', $rowBody);
                    $rowBody = preg_replace('/<c r="' . $rq . '"[^>]*\/>/s',            '', $rowBody);

                    if ($val === '' || $val === null) continue;

                    if ($type === 's') {
                        $escaped  = htmlspecialchars((string)$val, ENT_XML1 | ENT_QUOTES, 'UTF-8');
                        $rowBody .= '<c r="' . $ref . '" t="inlineStr"><is><t>' . $escaped . '</t></is></c>';
                    } else {
                        $rowBody .= '<c r="' . $ref . '"><v>' . $val . '</v></c>';
                    }
                }

                return '<row r="' . $rowNum . '"' . $attrs . '>' . $rowBody . '</row>';
            },
            $xml
        );
    }

    /**
     * Build cell XML string for a set of column→value pairs in a given row.
     */
    private function buildRowCells(int $rowNum, array $cells): string
    {
        $out = '';
        foreach ($cells as $col => [$type, $val]) {
            $ref = $col . $rowNum;
            if ($type === 's') {
                $str = (string)$val;
                if ($str === '') {
                    $out .= '<c r="' . $ref . '"/>';
                } else {
                    $escaped = htmlspecialchars($str, ENT_XML1 | ENT_QUOTES, 'UTF-8');
                    $out    .= '<c r="' . $ref . '" t="inlineStr"><is><t>' . $escaped . '</t></is></c>';
                }
            } else {
                $out .= '<c r="' . $ref . '"><v>' . $val . '</v></c>';
            }
        }
        return $out;
    }

    // ─── Formatters ───────────────────────────────────────────────────────────

    private function wfaCode(string $s): string
    {
        return match ($s) { 'Normal' => 'N', 'UW' => 'UW', 'SUW' => 'SUW', 'OW', 'OB' => 'OW', default => '' };
    }

    private function hfaCode(string $s): string
    {
        return match ($s) { 'Normal' => 'N', 'St' => 'St', 'SSt' => 'SSt', default => '' };
    }

    private function wflhCode(string $s): string
    {
        return match ($s) { 'Normal' => 'N', 'MW' => 'MW', 'SW' => 'SW', 'OW' => 'OW', 'OB' => 'Ob', default => '' };
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
