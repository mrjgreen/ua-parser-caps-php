<?php

include __DIR__ . '/../vendor/autoload.php';

$source = __DIR__ . '/../resources/*yaml';
$output = __DIR__ . '/../resources/capabilities_tree.php';

$files = new GlobIterator($source);

echo "Building cache file\n";

$tree = new \UACapabilities\Tree();

foreach($files as $file)
{
    echo "Loading data file " . basename($file) . "...";
    $tree->load($file);
    echo "Done\n";
}

$data = $tree->get();

file_put_contents($output, '<?php return ' . var_export($data, 1) . ';');

echo "Written cache file $output\n";