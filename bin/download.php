<?php

$base = "https://raw.githubusercontent.com/commenthol/ua-parser-caps/master/";
$output = __DIR__ . '/../resources/';

$files = array(
    $base . 'caps_device_type.yaml'                 => $output . 'caps_device_type.yaml',
    $base . 'caps_user_view.yaml'                   => $output . 'caps_user_view.yaml',
    $base . 'test/resources/test_capabilities.json' => $output . 'test_capabilities.json',
);

foreach($files as $source => $target)
{
    $bn = basename($source);

    echo "Fetching $bn...";
    file_put_contents($target, file_get_contents($source));
    echo "\rWritten $bn to $target\n";
}