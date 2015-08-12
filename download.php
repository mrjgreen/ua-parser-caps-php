<?php

$files = array(
    'https://raw.githubusercontent.com/commenthol/ua-parser-caps/master/caps_device_type.yaml' => __DIR__ . '/resources/caps_device_type.yaml',
    'https://raw.githubusercontent.com/commenthol/ua-parser-caps/master/caps_user_view.yaml' => __DIR__ . '/resources/caps_user_view.yaml',
    'https://raw.githubusercontent.com/commenthol/ua-parser-caps/master/test/resources/test_capabilities.json' => __DIR__ . '/resources/test_capabilities.json',
);

foreach($files as $source => $target)
{
    echo "Fetching $source...";
    file_put_contents($target, file_get_contents($source));
    echo "\rWritten $source to $target\n";
}