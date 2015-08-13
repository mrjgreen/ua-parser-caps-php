<?php

require __DIR__ . '/../vendor/autoload.php';

define('TEST_FILE', __DIR__ . '/../resources/test_capabilities.json');
define('CAPS_FILE_BASE', __DIR__ . '/../resources/caps_device_type.yaml');
define('CAPS_USER_VIEW_FILE', __DIR__ . '/../resources/caps_user_view.yaml');
define('CAPS_CACHE_FILE', __DIR__ . '/../resources/caps_device_type.cache.php');

$loadFiles = array(CAPS_FILE_BASE, CAPS_USER_VIEW_FILE);

if(!is_file(CAPS_CACHE_FILE) || filemtime(CAPS_CACHE_FILE) < max(array_map('filemtime', $loadFiles)))
{
    echo "Building data file\n";

    $tree = new \UACapabilities\Tree();

    foreach($loadFiles as $file)
    {
        $tree->load($file);
    }

    $data = $tree->get();

    file_put_contents(CAPS_CACHE_FILE, '<?php return ' . var_export($data, 1) . ';');
}else{
    $data = require CAPS_CACHE_FILE;
}

$parser = new UACapabilities\Parser($data);

// Read 1 object at a time
$jsonFile = fopen(TEST_FILE, 'r');

fgets($jsonFile); // Throw away line 1

$total = intval(shell_exec('wc -l ' . TEST_FILE));

$stopOnFail = false;

$i = 0;
$failed = array();

while($line = fgets($jsonFile))
{
    $test = json_decode(rtrim(rtrim($line), ','), true);

    if(!$test) continue;

    $input = new \UACapabilities\InputData($test["string"], $test["ua"], $test["os"], $test["device"]);

    $capabilities = $parser->parse($input);

    $capabilities = array_intersect_key($capabilities, $test['capabilities']); // Only check keys we have in the expectation

    $i++;

    if (!compare($test['capabilities'], $capabilities)) {
        $msg = "";
        $msg .= "\e[41m\e[97m✘ $i / $total {$test["string"]}\e[49m\e[39m";
        $msg .= "\n===================================\n";
        $msg .= "Test Data:\n";
        $msg .= format(array_diff_key($test, ['capabilities' => 1]));
        $msg .= "\n===================================\n";
        $msg .= "Expected:\n";
        $msg .= format($test['capabilities']);
        $msg .= "\n===================================\n";
        $msg .= "Actual:\n";
        $msg .= format($capabilities);
        $msg .= "\n===================================\n";
        $msg .= "Path:\n";
        $msg .= format($parser->path);
        $msg .= "\n===================================\n";
        echo $msg;
        if($stopOnFail) die();
        else $failed[] = $msg;
    }else{
        echo "\e[32m✔ $i / $total {$test["string"]}\e[39m\n";
    }
}

$failCount = count($failed);

$passed = $i - $failCount;

echo implode("\n\n", $failed);

echo "\n\e[32m✔ Passed: $passed / $i\e[39m\n";
echo "\e[41m\e[97m✘ Failed: $failCount / $i\e[49m\e[39m\n\n";

function compare(array $expected, array $actual)
{
    foreach($expected as $key => $item)
    {
        if(!isset($actual[$key]))
        {
            return false;
        }

        if(is_array($item) && is_array($actual[$key]))
        {
            return compare($item, $actual[$key]);
        }

        if($item !== $actual[$key])
        {
            return false;
        }
    }

    return true;
}

function format($data)
{
    return trim(substr(json_encode($data, JSON_PRETTY_PRINT), 1, -1), "\n");
}