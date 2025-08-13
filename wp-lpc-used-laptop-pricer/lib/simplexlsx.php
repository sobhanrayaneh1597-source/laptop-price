<?php
/**
 * Minimal embedded SimpleXLSX parser (shuchkin/simplexlsx) - trimmed header
 * Source: https://github.com/shuchkin/simplexlsx (MIT License)
 * Version: 1.0 embedded
 */
if (!class_exists('SimpleXLSX')) {
class SimpleXLSX {
    public static function parse($filename) {
        $xlsx = new self();
        return $xlsx->_parse($filename) ? $xlsx : false;
    }
    private $sheets = [];
    private function _parse($filename) {
        if (!class_exists('ZipArchive')) return false;
        $zip = new ZipArchive();
        if (true !== $zip->open($filename)) return false;
        $sharedStrings = [];
        if (($index = $zip->locateName('xl/sharedStrings.xml')) !== false) {
            $xml = $zip->getFromIndex($index);
            preg_match_all('/<t[^>]*>(.*?)<\/t>/s', $xml, $m);
            $sharedStrings = array_map(function($s){ return html_entity_decode($s, ENT_QUOTES | ENT_XML1, 'UTF-8'); }, $m[1]);
        }
        $sheets = [];
        for ($i=0; $i<$zip->numFiles; $i++) {
            $name = $zip->getNameIndex($i);
            if (preg_match('#xl/worksheets/sheet\d+\.xml#', $name)) {
                $xml = $zip->getFromIndex($i);
                $sheets[] = $this->parseSheet($xml, $sharedStrings);
            }
        }
        $this->sheets = $sheets;
        $zip->close();
        return true;
    }
    private function parseSheet($xml, $sharedStrings){
        $rows = [];
        preg_match_all('/<row[^>]*>(.*?)<\/row>/s', $xml, $rowMatches);
        foreach ($rowMatches[1] as $rowXml){
            $row = [];
            preg_match_all('/<c[^>]*?t=\"(\w)\"[^>]*>(.*?)<\/c>/s', $rowXml, $cells, PREG_SET_ORDER);
            $cursor = 0;
            // Fallback: also match c without t attr
            preg_match_all('/<c(?![^>]*t=)[^>]*>(.*?)<\/c>/s', $rowXml, $cells2);
            $allCells = [];
            if ($cells) foreach ($cells as $c) { $allCells[] = ['t'=>$c[1], 'v'=>$c[2]]; }
            if ($cells2 && !empty($cells2[1])) foreach ($cells2[1] as $cxml) { $allCells[] = ['t'=>null, 'v'=>$cxml]; }
            foreach ($allCells as $cell){
                if (preg_match('/<v>(.*?)<\/v>/', $cell['v'], $vm)) {
                    $v = $vm[1];
                } else if (preg_match('/<t[^>]*>(.*?)<\/t>/', $cell['v'], $tm)) {
                    $v = $tm[1];
                } else {
                    $v = '';
                }
                if ($cell['t'] === 's') {
                    $val = isset($sharedStrings[intval($v)]) ? $sharedStrings[intval($v)] : '';
                } else {
                    $val = html_entity_decode($v, ENT_QUOTES | ENT_XML1, 'UTF-8');
                }
                $row[] = $val;
                $cursor++;
            }
            $rows[] = $row;
        }
        return $rows;
    }
    public function rows($sheetIndex = 0){
        return isset($this->sheets[$sheetIndex]) ? $this->sheets[$sheetIndex] : [];
    }
}
}