<?php
require __DIR__ . '/vendor/autoload.php';

use OpenSpout\Reader\XLSX\Reader;

$path = __DIR__ . '/database/migrations/ppp_secret/ppp_secrets_arjosari.xlsx';
$reader = new Reader();
$reader->open($path);

foreach ($reader->getSheetIterator() as $sheet) {
    foreach ($sheet->getRowIterator() as $row) {
        $cells = $row->getCells();
        $data = [];
        foreach ($cells as $cell) {
            $data[] = $cell->getValue();
        }
        echo "Headers: " . implode(' | ', $data) . "\n";
        break 2; // Stop after first row
    }
}

$reader->close();
