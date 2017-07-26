<?php

if ($argc < 2) {
    echo "Usage: php woff-dump.php font.woff [output-dir]\n";
    exit;
}

$fontFile  = $argv[1];
$outputDir = $argc == 3 ? $argv[2] : '';

include __DIR__ . '/lib/WOFF.php';

$font = new WOFF();
$font->loadFromFile($fontFile);
$font->dump($outputDir);
