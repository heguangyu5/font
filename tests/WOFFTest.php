<?php

include __DIR__ . '/../lib/WOFF.php';

$fontFile = __DIR__ . '/bootstrap-glyphicons-halflings-regular.woff';

$font = new WOFF();
$font->loadFromFile($fontFile);
$font->dump();
