<?php
declare(strict_types=1);

/**
 * Pembaca/penulis XLSX ringan untuk kebutuhan import/export Perpustakaan SDS.
 * Tidak membutuhkan Composer. Memakai ZipArchive dan SimpleXML bawaan PHP.
 */
final class PerpusXlsxLite
{
    public static function read(string $path, ?string $preferredSheet = null, int $maxRows = 20000): array
    {
        if (!class_exists('ZipArchive')) {
            throw new RuntimeException('Ekstensi PHP ZipArchive belum aktif. Aktifkan extension=zip pada php.ini.');
        }
        if (!function_exists('simplexml_load_string')) {
            throw new RuntimeException('Ekstensi PHP SimpleXML belum aktif. Aktifkan extension=simplexml pada php.ini.');
        }
        $zip = new ZipArchive();
        if ($zip->open($path) !== true) {
            throw new RuntimeException('File Excel tidak dapat dibuka atau rusak.');
        }
        try {
            self::validateArchive($zip);
            $shared = self::readSharedStrings($zip);
            $workbookXml = $zip->getFromName('xl/workbook.xml');
            $relsXml = $zip->getFromName('xl/_rels/workbook.xml.rels');
            if ($workbookXml === false || $relsXml === false) {
                throw new RuntimeException('Struktur workbook Excel tidak lengkap.');
            }
            $wb = self::parseXml($workbookXml);
            $rels = self::parseXml($relsXml);
            if ($wb === false || $rels === false) throw new RuntimeException('XML workbook Excel tidak dapat dibaca.');

            $wb->registerXPathNamespace('m', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');
            $wb->registerXPathNamespace('r', 'http://schemas.openxmlformats.org/officeDocument/2006/relationships');
            $rels->registerXPathNamespace('r', 'http://schemas.openxmlformats.org/package/2006/relationships');
            $relMap = [];
            foreach ($rels->xpath('//r:Relationship') ?: [] as $rel) {
                $a = $rel->attributes();
                $target = str_replace('\\', '/', (string)$a['Target']);
                if (str_starts_with($target, '/')) $target = ltrim($target, '/');
                elseif (!str_starts_with($target, 'xl/')) $target = 'xl/' . ltrim($target, '/');
                $relMap[(string)$a['Id']] = $target;
            }

            $chosenPath = '';
            $chosenName = '';
            $sheets = $wb->xpath('//m:sheets/m:sheet') ?: [];
            foreach ($sheets as $sheet) {
                $name = (string)$sheet['name'];
                $rAttrs = $sheet->attributes('http://schemas.openxmlformats.org/officeDocument/2006/relationships');
                $rid = (string)$rAttrs['id'];
                if ($chosenPath === '' || ($preferredSheet !== null && mb_strtolower($name) === mb_strtolower($preferredSheet))) {
                    $chosenPath = $relMap[$rid] ?? '';
                    $chosenName = $name;
                }
                if ($preferredSheet !== null && mb_strtolower($name) === mb_strtolower($preferredSheet)) break;
            }
            if ($chosenPath === '') throw new RuntimeException('Sheet Excel tidak ditemukan.');
            $sheetXml = $zip->getFromName($chosenPath);
            if ($sheetXml === false) throw new RuntimeException('Isi sheet Excel tidak ditemukan.');
            $sheet = self::parseXml($sheetXml);
            if ($sheet === false) throw new RuntimeException('XML sheet Excel tidak dapat dibaca.');
            $sheet->registerXPathNamespace('m', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');

            $rows = [];
            $rowCount = 0;
            foreach ($sheet->xpath('//m:sheetData/m:row') ?: [] as $rowNode) {
                if (++$rowCount > $maxRows) throw new RuntimeException('Jumlah baris melebihi batas ' . number_format($maxRows, 0, ',', '.') . '.');
                $row = [];
                foreach ($rowNode->xpath('./*[local-name()="c"]') ?: [] as $cell) {
                    $ref = (string)$cell['r'];
                    $col = self::columnIndex($ref);
                    $type = (string)$cell['t'];
                    $value = '';
                    if ($type === 'inlineStr') {
                        $parts = $cell->xpath('./*[local-name()="is"]//*[local-name()="t"]') ?: [];
                        foreach ($parts as $part) $value .= (string)$part;
                    } else {
                        $v = $cell->xpath('./*[local-name()="v"]');
                        $raw = isset($v[0]) ? (string)$v[0] : '';
                        if ($type === 's') $value = $shared[(int)$raw] ?? '';
                        elseif ($type === 'b') $value = $raw === '1' ? '1' : '0';
                        else $value = $raw;
                    }
                    $row[$col] = trim((string)$value);
                }
                if ($row) {
                    ksort($row);
                    $last = max(array_keys($row));
                    $dense = [];
                    for ($i = 0; $i <= $last; $i++) $dense[] = $row[$i] ?? '';
                    $rows[] = $dense;
                } else {
                    $rows[] = [];
                }
            }
            return ['sheet' => $chosenName, 'rows' => $rows];
        } finally {
            $zip->close();
        }
    }

    public static function rowsWithHeader(array $rows, int $scanRows = 20): array
    {
        $headerIndex = -1;
        $headers = [];
        $limit = min(count($rows), $scanRows);
        for ($i = 0; $i < $limit; $i++) {
            $candidate = array_map([self::class, 'normalizeHeader'], $rows[$i] ?? []);
            $nonEmpty = array_values(array_filter($candidate, static fn($v) => $v !== ''));
            if (count($nonEmpty) >= 2 && (in_array('judul', $candidate, true) || in_array('barcode', $candidate, true) || in_array('nomor_anggota', $candidate, true))) {
                $headerIndex = $i;
                $headers = $candidate;
                break;
            }
        }
        if ($headerIndex < 0) throw new RuntimeException('Baris header tidak ditemukan pada 20 baris pertama.');
        $result = [];
        for ($i = $headerIndex + 1, $n = count($rows); $i < $n; $i++) {
            $values = $rows[$i] ?? [];
            if (!array_filter($values, static fn($v) => trim((string)$v) !== '')) continue;
            $item = [];
            foreach ($headers as $col => $header) {
                if ($header !== '') $item[$header] = trim((string)($values[$col] ?? ''));
            }
            $item['_row'] = $i + 1;
            $result[] = $item;
        }
        return ['header_row' => $headerIndex + 1, 'headers' => $headers, 'data' => $result];
    }

    public static function download(string $filename, array $sheets): never
    {
        $tmp = tempnam(sys_get_temp_dir(), 'perpus_xlsx_');
        if ($tmp === false) throw new RuntimeException('File sementara tidak dapat dibuat.');
        try {
            self::write($tmp, $sheets);
            if (headers_sent()) throw new RuntimeException('Header download sudah terkirim.');
            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            header('Content-Disposition: attachment; filename="' . str_replace(['"',"\r","\n"], '', $filename) . '"; filename*=UTF-8\'\'' . rawurlencode($filename));
            header('Content-Length: ' . filesize($tmp));
            header('Cache-Control: no-store, no-cache, must-revalidate');
            readfile($tmp);
        } finally {
            @unlink($tmp);
        }
        exit;
    }

    public static function write(string $path, array $sheets): void
    {
        if (!class_exists('ZipArchive')) throw new RuntimeException('Ekstensi PHP ZipArchive belum aktif.');
        if (!$sheets) throw new RuntimeException('Tidak ada data yang akan diekspor.');
        $zip = new ZipArchive();
        if ($zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException('File XLSX tidak dapat dibuat.');
        }
        try {
            $sheetNames = [];
            $sheetXmls = [];
            foreach (array_values($sheets) as $index => $sheet) {
                $name = self::safeSheetName((string)($sheet['name'] ?? ('Sheet' . ($index + 1))), $sheetNames);
                $sheetNames[] = $name;
                $sheetXmls[] = self::buildSheetXml($sheet);
            }
            $zip->addFromString('[Content_Types].xml', self::contentTypes(count($sheetNames)));
            $zip->addFromString('_rels/.rels', self::rootRels());
            $zip->addFromString('docProps/app.xml', self::appProps($sheetNames));
            $zip->addFromString('docProps/core.xml', self::coreProps());
            $zip->addFromString('xl/workbook.xml', self::workbookXml($sheetNames));
            $zip->addFromString('xl/_rels/workbook.xml.rels', self::workbookRels(count($sheetNames)));
            $zip->addFromString('xl/styles.xml', self::stylesXml());
            foreach ($sheetXmls as $i => $xml) $zip->addFromString('xl/worksheets/sheet' . ($i + 1) . '.xml', $xml);
        } finally {
            $zip->close();
        }
    }

    private static function readSharedStrings(ZipArchive $zip): array
    {
        $xml = $zip->getFromName('xl/sharedStrings.xml');
        if ($xml === false) return [];
        $sx = self::parseXml($xml);
        if ($sx === false) return [];
        $sx->registerXPathNamespace('m', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');
        $result = [];
        foreach ($sx->xpath('//m:si') ?: [] as $si) {
            $text = '';
            foreach ($si->xpath('.//*[local-name()="t"]') ?: [] as $t) $text .= (string)$t;
            $result[] = $text;
        }
        return $result;
    }

    private static function normalizeHeader(string $value): string
    {
        $value = mb_strtolower(trim($value));
        $value = preg_replace('/[^a-z0-9]+/u', '_', $value) ?? '';
        return trim($value, '_');
    }

    private static function validateArchive(ZipArchive $zip): void
    {
        if ($zip->numFiles <= 0 || $zip->numFiles > 2000) throw new RuntimeException('Struktur arsip Excel tidak wajar.');
        $total = 0;
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $stat = $zip->statIndex($i);
            if (!is_array($stat)) throw new RuntimeException('Arsip Excel tidak dapat diperiksa.');
            $size = (int)($stat['size'] ?? 0);
            if ($size > 32 * 1024 * 1024) throw new RuntimeException('Bagian arsip Excel terlalu besar.');
            $total += $size;
            if ($total > 64 * 1024 * 1024) throw new RuntimeException('Isi arsip Excel melewati batas aman.');
        }
    }

    private static function parseXml(string $xml): SimpleXMLElement
    {
        if (strlen($xml) > 32 * 1024 * 1024) throw new RuntimeException('XML Excel terlalu besar.');
        $previous = libxml_use_internal_errors(true);
        try {
            $node = simplexml_load_string($xml, SimpleXMLElement::class, LIBXML_NONET | LIBXML_COMPACT);
            if ($node === false) throw new RuntimeException('XML Excel tidak valid.');
            return $node;
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previous);
        }
    }

    private static function columnIndex(string $ref): int
    {
        if (!preg_match('/^([A-Z]+)/i', $ref, $m)) return 0;
        $letters = strtoupper($m[1]);
        $n = 0;
        for ($i = 0; $i < strlen($letters); $i++) $n = $n * 26 + (ord($letters[$i]) - 64);
        return max(0, $n - 1);
    }

    private static function columnLetter(int $index): string
    {
        $n = $index + 1;
        $s = '';
        while ($n > 0) {
            $n--;
            $s = chr(65 + ($n % 26)) . $s;
            $n = intdiv($n, 26);
        }
        return $s;
    }

    private static function safeSheetName(string $name, array $existing): string
    {
        $name = preg_replace('~[\\/?*\[\]:]~u', ' ', trim($name)) ?: 'Sheet';
        $name = mb_substr($name, 0, 31);
        $base = $name;
        $i = 2;
        while (in_array(mb_strtolower($name), array_map('mb_strtolower', $existing), true)) {
            $suffix = ' ' . $i++;
            $name = mb_substr($base, 0, 31 - mb_strlen($suffix)) . $suffix;
        }
        return $name;
    }

    private static function xml(string $value): string
    {
        // XML 1.0 melarang sebagian besar karakter kontrol. Data hasil migrasi
        // dapat mengandung karakter tersebut dan membuat Excel melaporkan file rusak.
        $value = preg_replace('~[^\x{0009}\x{000A}\x{000D}\x{0020}-\x{D7FF}\x{E000}-\x{FFFD}\x{10000}-\x{10FFFF}]~u', '', $value) ?? '';
        return htmlspecialchars($value, ENT_XML1 | ENT_COMPAT, 'UTF-8');
    }

    private static function buildSheetXml(array $sheet): string
    {
        $title = trim((string)($sheet['title'] ?? ''));
        $subtitle = trim((string)($sheet['subtitle'] ?? ''));
        $headers = array_values($sheet['headers'] ?? []);
        $rows = array_values($sheet['rows'] ?? []);
        $widths = array_values($sheet['widths'] ?? []);
        $textColumns = array_map('intval', array_values($sheet['text_columns'] ?? []));
        $maxCols = max(1, count($headers));
        foreach ($rows as $row) $maxCols = max($maxCols, count($row));
        $currentRow = 1;
        $rowXml = [];
        if ($title !== '') {
            $rowXml[] = self::rowXml($currentRow++, [$title], 1, $maxCols);
        }
        if ($subtitle !== '') {
            $rowXml[] = self::rowXml($currentRow++, [$subtitle], 0, $maxCols);
        }
        if ($title !== '' || $subtitle !== '') $currentRow++;
        $headerRow = $currentRow;
        if ($headers) $rowXml[] = self::rowXml($currentRow++, $headers, 2);
        foreach ($rows as $row) $rowXml[] = self::rowXml($currentRow++, array_values($row), 0, 0, $textColumns);
        $lastRow = max(1, $currentRow - 1);
        $lastCol = self::columnLetter($maxCols - 1);
        $colsXml = '';
        if ($widths) {
            $cols = [];
            for ($i = 0; $i < $maxCols; $i++) {
                $w = (float)($widths[$i] ?? 18);
                $cols[] = '<col min="' . ($i + 1) . '" max="' . ($i + 1) . '" width="' . max(8, min(60, $w)) . '" customWidth="1"/>';
            }
            $colsXml = '<cols>' . implode('', $cols) . '</cols>';
        }
        $mergeXml = '';
        $merges = [];
        if ($title !== '') $merges[] = 'A1:' . $lastCol . '1';
        if ($subtitle !== '') $merges[] = 'A2:' . $lastCol . '2';
        if ($merges) {
            $mergeXml = '<mergeCells count="' . count($merges) . '">' . implode('', array_map(static fn($r) => '<mergeCell ref="' . $r . '"/>', $merges)) . '</mergeCells>';
        }
        $filterXml = $headers ? '<autoFilter ref="A' . $headerRow . ':' . $lastCol . $lastRow . '"/>' : '';
        $paneXml = $headers ? '<sheetViews><sheetView workbookViewId="0"><pane ySplit="' . $headerRow . '" topLeftCell="A' . ($headerRow + 1) . '" activePane="bottomLeft" state="frozen"/></sheetView></sheetViews>' : '<sheetViews><sheetView workbookViewId="0"/></sheetViews>';
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            . '<dimension ref="A1:' . $lastCol . $lastRow . '"/>' . $paneXml . $colsXml
            . '<sheetData>' . implode('', $rowXml) . '</sheetData>' . $filterXml . $mergeXml
            . '<pageMargins left="0.3" right="0.3" top="0.5" bottom="0.5" header="0.2" footer="0.2"/>'
            . '</worksheet>';
    }

    private static function rowXml(int $rowNumber, array $values, int $style = 0, int $mergeWidth = 0, array $textColumns = []): string
    {
        $cells = [];
        foreach ($values as $i => $value) {
            if ($value === null) $value = '';
            $ref = self::columnLetter($i) . $rowNumber;
            $cellStyle = $style;
            if ($style === 0 && !in_array($i, $textColumns, true) && is_numeric($value) && !preg_match('/^0\d+$/', (string)$value)) {
                $cells[] = '<c r="' . $ref . '" s="' . $cellStyle . '" t="n"><v>' . self::xml((string)$value) . '</v></c>';
            } else {
                $text = (string)$value;
                $space = ($text !== trim($text)) ? ' xml:space="preserve"' : '';
                $cells[] = '<c r="' . $ref . '" s="' . $cellStyle . '" t="inlineStr"><is><t' . $space . '>' . self::xml($text) . '</t></is></c>';
            }
        }
        if ($mergeWidth > 1) {
            for ($i = count($values); $i < $mergeWidth; $i++) {
                $ref = self::columnLetter($i) . $rowNumber;
                $cells[] = '<c r="' . $ref . '" s="' . $style . '"/>';
            }
        }
        return '<row r="' . $rowNumber . '">' . implode('', $cells) . '</row>';
    }

    private static function contentTypes(int $count): string
    {
        $sheets = '';
        for ($i = 1; $i <= $count; $i++) $sheets .= '<Override PartName="/xl/worksheets/sheet' . $i . '.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>';
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
            . '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Default Extension="xml" ContentType="application/xml"/>'
            . '<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
            . '<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>'
            . '<Override PartName="/docProps/core.xml" ContentType="application/vnd.openxmlformats-package.core-properties+xml"/>'
            . '<Override PartName="/docProps/app.xml" ContentType="application/vnd.openxmlformats-officedocument.extended-properties+xml"/>'
            . $sheets . '</Types>';
    }

    private static function rootRels(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>'
            . '<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/package/2006/relationships/metadata/core-properties" Target="docProps/core.xml"/>'
            . '<Relationship Id="rId3" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/extended-properties" Target="docProps/app.xml"/>'
            . '</Relationships>';
    }

    private static function workbookXml(array $sheetNames): string
    {
        $sheets = '';
        foreach ($sheetNames as $i => $name) $sheets .= '<sheet name="' . self::xml($name) . '" sheetId="' . ($i + 1) . '" r:id="rId' . ($i + 1) . '"/>';
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"><bookViews><workbookView/></bookViews><sheets>' . $sheets . '</sheets></workbook>';
    }

    private static function workbookRels(int $count): string
    {
        $rels = '';
        for ($i = 1; $i <= $count; $i++) $rels .= '<Relationship Id="rId' . $i . '" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet' . $i . '.xml"/>';
        $rels .= '<Relationship Id="rId' . ($count + 1) . '" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>';
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">' . $rels . '</Relationships>';
    }

    private static function stylesXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            . '<fonts count="3"><font><sz val="10"/><name val="Calibri"/></font><font><b/><sz val="16"/><color rgb="FFFFFFFF"/><name val="Calibri"/></font><font><b/><sz val="10"/><color rgb="FFFFFFFF"/><name val="Calibri"/></font></fonts>'
            . '<fills count="4"><fill><patternFill patternType="none"/></fill><fill><patternFill patternType="gray125"/></fill><fill><patternFill patternType="solid"><fgColor rgb="FF17365D"/><bgColor indexed="64"/></patternFill></fill><fill><patternFill patternType="solid"><fgColor rgb="FF2F75B5"/><bgColor indexed="64"/></patternFill></fill></fills>'
            . '<borders count="2"><border/><border><left style="thin"><color rgb="FFD9E2F3"/></left><right style="thin"><color rgb="FFD9E2F3"/></right><top style="thin"><color rgb="FFD9E2F3"/></top><bottom style="thin"><color rgb="FFD9E2F3"/></bottom></border></borders>'
            . '<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>'
            . '<cellXfs count="3"><xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0" applyAlignment="1"><alignment vertical="top" wrapText="1"/></xf><xf numFmtId="0" fontId="1" fillId="2" borderId="0" xfId="0" applyAlignment="1"><alignment vertical="center" horizontal="left"/></xf><xf numFmtId="0" fontId="2" fillId="3" borderId="1" xfId="0" applyAlignment="1"><alignment vertical="center" horizontal="center" wrapText="1"/></xf></cellXfs>'
            . '<cellStyles count="1"><cellStyle name="Normal" xfId="0" builtinId="0"/></cellStyles></styleSheet>';
    }

    private static function coreProps(): string
    {
        $now = gmdate('Y-m-d\TH:i:s\Z');
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><cp:coreProperties xmlns:cp="http://schemas.openxmlformats.org/package/2006/metadata/core-properties" xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:dcterms="http://purl.org/dc/terms/" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"><dc:creator>SDS E-Perpustakaan</dc:creator><cp:lastModifiedBy>SDS E-Perpustakaan</cp:lastModifiedBy><dcterms:created xsi:type="dcterms:W3CDTF">' . $now . '</dcterms:created><dcterms:modified xsi:type="dcterms:W3CDTF">' . $now . '</dcterms:modified></cp:coreProperties>';
    }

    private static function appProps(array $sheetNames): string
    {
        $titles = implode('', array_map(static fn($n) => '<vt:lpstr>' . self::xml($n) . '</vt:lpstr>', $sheetNames));
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Properties xmlns="http://schemas.openxmlformats.org/officeDocument/2006/extended-properties" xmlns:vt="http://schemas.openxmlformats.org/officeDocument/2006/docPropsVTypes"><Application>SDS E-Perpustakaan</Application><TitlesOfParts><vt:vector size="' . count($sheetNames) . '" baseType="lpstr">' . $titles . '</vt:vector></TitlesOfParts></Properties>';
    }
}
