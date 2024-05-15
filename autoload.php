<?php


spl_autoload_register(function($name) {
    $filePath = str_replace('\\', '/', __DIR__ . "/src/$name") . ".php";

    if (file_exists($filePath)) {
        include_once $filePath;
    }
});

