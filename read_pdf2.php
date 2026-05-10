<?php
require 'vendor/autoload.php';

$parser = new \Smalot\PdfParser\Parser();
$pdf    = $parser->parseFile('Workshop_doctor_doctrine.pdf');
$text = $pdf->getText();
echo mb_substr($text, 0, 5000); // Output the first 5000 chars
