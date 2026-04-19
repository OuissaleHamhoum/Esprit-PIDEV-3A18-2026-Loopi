<?php
$opts = ['http' => ['method' => 'GET', 'ignore_errors' => true, 'follow_location' => false]];
$ctx = stream_context_create($opts);
$r = @file_get_contents('http://localhost:8000/organisateur', false, $ctx);
echo 'len=' . strlen($r) . PHP_EOL;
var_export($http_response_header);
