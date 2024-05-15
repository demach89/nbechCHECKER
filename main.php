<?php

/**
 * Парсер отчёта НБКИ на предмет ошибок
 * Результат парсинга представлен в виде ассоциаций:
 * 1) клиент => ошибки
 * 2) ошибка => клиенты
 */

require_once __DIR__ . '/autoload.php';
require_once __DIR__ . '/src/Core/env.php';

use Core\NBKI\NBKIChecker;
use Core\NBKI\NBKIReport;

$reportFile = 'example';

$report = (new NBKIReport())
    ->import($reportFile, "\t", 0)
    ->parse();

$checker = (new NBKIChecker($report))
    ->C25_ARREAR()
    ->C26_DUEARREAR()
    ->C27_PASTDUEARREAR()
    ->C28_PAYMT()
    ->C29_MONTHAVERPAYMT()
;

foreach ($checker->getErrors() as $contractGroupCounter => $errors) {
    echo "GROUPHEADER #{$contractGroupCounter}:\n";

    foreach ($errors as $error) {
        echo "\t{$error}\n";
    }
}

if (empty($checker->getErrors())) {
    echo "ALL GOOD";
}

