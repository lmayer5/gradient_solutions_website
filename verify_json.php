<?php
// verify_json.php
ini_set('display_errors', 1);
error_reporting(E_ALL);

$ordersFile = __DIR__ . '/../private_data/orders.json';

echo "Checking $ordersFile ...\n";

if (!file_exists($ordersFile)) {
    die("File not found.");
}

$content = file_get_contents($ordersFile);
$data = json_decode($content, true);

if (json_last_error() === JSON_ERROR_NONE) {
    echo "JSON isValid. Count: " . count($data) . "\n";
    print_r($data);
} else {
    echo "JSON ERROR: " . json_last_error_msg() . "\n";
    echo "Raw Content Preview:\n";
    echo substr($content, 0, 500);
}
?>
