<?php
require 'vendor/autoload.php';
$m = new ReflectionMethod('Smalot\PdfParser\Parser', 'parseContent');
foreach($m->getParameters() as $p) {
    echo $p->getName() . PHP_EOL;
}
