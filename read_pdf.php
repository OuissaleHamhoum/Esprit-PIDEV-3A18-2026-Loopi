<?php
$file = 'Workshop_doctor_doctrine.pdf';
$content = file_get_contents($file);

// Extract text between BT and ET (PDF text blocks)
preg_match_all('/BT\s*(.*?)\s*ET/s', $content, $matches);

$text = '';
foreach ($matches[1] as $block) {
    // Extract strings from Tj and TJ operators
    preg_match_all('/\(((?:[^()\\\\]|\\\\.)*)\)\s*Tj/', $block, $tj);
    preg_match_all('/\[((?:[^\[\]]|\((?:[^()\\\\]|\\\\.)*\))*)\]\s*TJ/', $block, $tjArray);
    
    foreach ($tj[1] as $str) {
        $text .= $str . ' ';
    }
    foreach ($tjArray[1] as $str) {
        preg_match_all('/\(((?:[^()\\\\]|\\\\.)*)\)/', $str, $inner);
        foreach ($inner[1] as $s) {
            $text .= $s;
        }
        $text .= ' ';
    }
}

// Clean up escape sequences
$text = str_replace(['\\(', '\\)', '\\\\', '\\n', '\\r'], ['(', ')', '\\', "\n", "\r"], $text);

echo $text;
