<?php

namespace App\Helpers;

use Core\Database;

/**
 * Fills the OPT Plus community-level tool (.xltx template).
 *
 * Template: storage/templates/eopt_region6.xltx (OPT PLUS YANOG format)
 *
 * Nut_StatusTool data-entry columns (rows 10–1009):
 *   A = seq#              B = purok/address      C = mother name
 *   D = child full name   E = IP group (YES/NO)  F = sex (M/F)
 *   G = DOB (date serial) H = date measured (date serial)
 *   I = weight kg         J = height cm
 *   K+ = auto-computed by Excel formulas — do NOT overwrite
 *
 * Header cells (Nut_StatusTool):
 *   E6 = Barangay   I6 = City/Municipality   M6 = Province   M1 = Year
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

    /** Style index for date cells (G/H) in the YANOG template */
    private const DATE_STYLE = '335';

    public function __construct(int $year, string $period, string $barangay)
    {
        $this->year     = $year;
        $this->period   = $period;
        $this->barangay = $barangay;
        $this->db       = Database::getInstance();
    }

    // ─── Public entry point ───────────────────────────────────────────────────

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

        // Convert xltx → xlsx so Excel opens it as a workbook, not a template
        $ct = $zip->getFromName('[Content_Types].xml');
        $ct = str_replace(
            'application/vnd.openxmlformats-officedocument.spreadsheetml.template.main+xml',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml',
            $ct
        );
        $zip->addFromString('[Content_Types].xml', $ct, \ZipArchive::FL_OVERWRITE);

        // Remove calcChain so Excel recalculates every formula on open
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
        if ($this->period)   { $where .= ' AND a.period = ?';    $params[] = $this->period; }
        if ($this->barangay) { $where .= ' AND b.barangay = ?';  $params[] = $this->barangay; }

        $stmt = $this->db->prepare(
            "SELECT b.last_name, b.first_name, b.mother_name, b.purok_zone,
                    b.sex, b.date_of_birth, b.is_indigenous_people,
                    b.city_municipality, b.province, b.region,
                    a.age_in_months, a.weight_kg, a.height_cm,
                    a.nutritional_status, a.hfa_status, a.wflh_status,
                    a.assessment_date
             FROM assessments a
             JOIN beneficiaries b ON b.id = a.beneficiary_id
             WHERE b.deleted_at IS NULL AND b.validation_status = 'validated' AND $where
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

        if (empty($rows)) {
            $detail = "Year={$this->year}";
            if ($this->period)   $detail .= ", Period={$this->period}";
            if ($this->barangay) $detail .= ", Barangay={$this->barangay}";
            throw new \RuntimeException(
                "No validated records found ({$detail}). Please check your filters."
            );
        }

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
     * Fill BNS_Printout header:
     *   K1  = Barangay name
     *   BH6 = Year
     */
    private function writeBnsPrintoutHeader(\ZipArchive $zip, array $map): void
    {
        if (!isset($map['BNS_Printout'])) return;
        $xml = $zip->getFromName($map['BNS_Printout']);

        $xml = $this->injectRowCells($xml, 1, [
            'K'  => ['s', $this->barangay],
        ]);
        $xml = $this->injectRowCells($xml, 6, [
            'BH' => ['s', (string)$this->year],
        ]);

        $zip->addFromString($map['BNS_Printout'], $xml, \ZipArchive::FL_OVERWRITE);
    }

    /**
     * Fill OPT_Form1A header:
     *   E10 / AG10 / BI10 = Year (3 side-by-side copies)
     *   AE11 / BE11       = Date printed
     */
    private function writeForm1AHeader(\ZipArchive $zip, array $map): void
    {
        if (!isset($map['OPT_Form1A'])) return;
        $xml   = $zip->getFromName($map['OPT_Form1A']);
        $today = date('m/d/Y');

        $xml = $this->injectRowCells($xml, 10, [
            'E'  => ['s', (string)$this->year],
            'AG' => ['s', (string)$this->year],
            'BI' => ['s', (string)$this->year],
        ]);
        $xml = $this->injectRowCells($xml, 11, [
            'AE' => ['s', $today],
            'BE' => ['s', $today],
        ]);

        $zip->addFromString($map['OPT_Form1A'], $xml, \ZipArchive::FL_OVERWRITE);
    }

    /**
     * Fill Nut_StatusTool:
     *  - Header rows (location + year)
     *  - Data rows 10–1009: columns A–J only (K+ are formula cells)
     *
     * Writing G/H as Excel date serials (numeric, style 335) lets the tool's
     * built-in VLOOKUP formulas auto-compute age, WFA, HFA, and WFLH status,
     * which then cascade into Summary → Data-Export automatically.
     */
    private function writeNutStatusTool(\ZipArchive $zip, array $map, array $children): void
    {
        $key = null;
        foreach (array_keys($map) as $k) {
            if (stripos($k, 'Nut_Status') !== false) { $key = $k; break; }
        }
        if (!$key) return;

        $xml = $zip->getFromName($map[$key]);

        // ── Header cells ────────────────────────────────────────────────────────
        // M1 = Year (K1 is =TODAY() — leave the formula intact)
        $xml = $this->injectRowCells($xml, 1, [
            'M' => ['n', $this->year],
        ]);

        // Row 6: E6 = Barangay, I6 = City/Municipality, M6 = Province
        $xml = $this->injectRowCells($xml, 6, [
            'E' => ['s', strtoupper($this->barangay)],
            'I' => ['s', strtoupper($this->municipality)],
            'M' => ['s', $this->province],
        ]);

        // ── Data rows ───────────────────────────────────────────────────────────
        $prevBacktrack = ini_set('pcre.backtrack_limit', 10000000);
        $prevRecursion = ini_set('pcre.recursion_limit',   500000);

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

                // Clear only user-input columns A–J; preserve K+ formula cells
                $rowBody = preg_replace('/<c r="[A-J]\d+"[^>]*>.*?<\/c>/s', '', $rowBody);
                $rowBody = preg_replace('/<c r="[A-J]\d+"[^>]*\/>/s',        '', $rowBody);

                if (!isset($children[$childIdx])) {
                    return '<row r="' . $rowNum . '"' . $rowAttrs . '>' . $rowBody . '</row>';
                }

                $c = $children[$childIdx];

                $dobSerial  = $c['date_of_birth']   ? $this->dateSerial($c['date_of_birth'])   : null;
                $dateSerial = $c['assessment_date'] ? $this->dateSerial($c['assessment_date']) : null;

                $cells = $this->buildRowCells($rowNum, [
                    'A' => ['n', $childIdx + 1],
                    'B' => ['s', $c['purok_zone'] ?? ''],
                    'C' => ['s', $this->motherName($c['mother_name'])],
                    'D' => ['s', $this->childName($c)],
                    'E' => ['s', !empty($c['is_indigenous_people']) ? 'YES' : 'NO'],
                    'F' => ['s', strtolower($c['sex']) === 'female' ? 'F' : 'M'],
                    'G' => ['d', $dobSerial],
                    'H' => ['d', $dateSerial],
                    'I' => ['n', $c['weight_kg'] !== null ? (float)$c['weight_kg'] : null],
                    'J' => ['n', $c['height_cm'] !== null ? (float)$c['height_cm'] : null],
                ]);

                $fullBody = $this->sortRowCells($cells . $rowBody);
                return '<row r="' . $rowNum . '"' . $rowAttrs . '>' . $fullBody . '</row>';
            },
            $xml
        );

        ini_set('pcre.backtrack_limit', $prevBacktrack);
        ini_set('pcre.recursion_limit',  $prevRecursion);

        if ($xml === null || preg_last_error() !== PREG_NO_ERROR) {
            throw new \RuntimeException(
                'PCRE failed on Nut_StatusTool XML (error ' . preg_last_error() . '). '
                . 'The sheet XML is too large for PHP regex processing.'
            );
        }

        $zip->addFromString($map[$key], $xml, \ZipArchive::FL_OVERWRITE);
    }

    // ─── XML helpers ──────────────────────────────────────────────────────────

    /**
     * Inject / replace specific cells in an existing row by reference.
     * Works for both existing cells and cells not yet present in the row.
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
                    $rq  = preg_quote($ref, '/');
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

                return '<row r="' . $rowNum . '"' . $attrs . '>' . $this->sortRowCells($rowBody) . '</row>';
            },
            $xml
        );
    }

    /**
     * Re-order all <c> elements within a row body by column reference.
     * XLSX requires cells to be in ascending column order.
     */
    private function sortRowCells(string $rowBody): string
    {
        $cellMap = [];

        preg_match_all('/(<c r="([^"]+)"[^>\/]*>.*?<\/c>)/s', $rowBody, $m1, PREG_SET_ORDER);
        foreach ($m1 as $m) {
            $col = preg_replace('/\d+/', '', $m[2]);
            $cellMap[$col] = $m[1];
        }

        preg_match_all('/(<c r="([^"]+)"[^>]*\/>)/s', $rowBody, $m2, PREG_SET_ORDER);
        foreach ($m2 as $m) {
            $col = preg_replace('/\d+/', '', $m[2]);
            if (!isset($cellMap[$col])) {
                $cellMap[$col] = $m[1];
            }
        }

        // Sort: shorter column letters first (A before AA), then alphabetically
        uksort($cellMap, function (string $a, string $b): int {
            $la = strlen($a); $lb = strlen($b);
            return $la !== $lb ? $la - $lb : strcmp($a, $b);
        });

        return implode('', array_values($cellMap));
    }

    /**
     * Build cell XML for a column→[type,value] map in a given row.
     *
     * Types:
     *   's' = inline string   'n' = number   'd' = Excel date serial (style 335)
     */
    private function buildRowCells(int $rowNum, array $cells): string
    {
        $out = '';
        foreach ($cells as $col => [$type, $val]) {
            if ($val === null) continue;

            $ref = $col . $rowNum;
            if ($type === 's') {
                $str = (string)$val;
                if ($str === '') {
                    $out .= '<c r="' . $ref . '"/>';
                } else {
                    $escaped = htmlspecialchars($str, ENT_XML1 | ENT_QUOTES, 'UTF-8');
                    $out    .= '<c r="' . $ref . '" t="inlineStr"><is><t>' . $escaped . '</t></is></c>';
                }
            } elseif ($type === 'd') {
                // Date serial: numeric cell with the template's date-format style
                $out .= '<c r="' . $ref . '" s="' . self::DATE_STYLE . '"><v>' . (int)$val . '</v></c>';
            } else {
                $out .= '<c r="' . $ref . '"><v>' . $val . '</v></c>';
            }
        }
        return $out;
    }

    // ─── Formatters ───────────────────────────────────────────────────────────

    /**
     * Convert a Y-m-d date string to an Excel serial date number.
     * Excel epoch = Dec 30 1899 (accounts for the 1900 leap-year bug).
     */
    private function dateSerial(string $dateStr): int
    {
        $d = \DateTime::createFromFormat('Y-m-d', $dateStr);
        if (!$d) return 0;
        return (int)(new \DateTime('1899-12-30'))->diff($d)->days;
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
