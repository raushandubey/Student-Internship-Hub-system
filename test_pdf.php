<?php
require 'vendor/autoload.php';
$p = new \Smalot\PdfParser\Parser();
$c = file_get_contents('test_resume.pdf');
try {
    $pdf = $p->parseContent($c);
    echo "Success: " . strlen($pdf->getText());
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage();
}
