<?php
require 'vendor/autoload.php';
$p = new \Smalot\PdfParser\Parser();
var_dump(method_exists($p, 'parseContent'));
