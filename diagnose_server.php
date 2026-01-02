<?php
// diagnose_server.php
// Checks permissions and configuration
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once 'env_loader.php';

echo "<h2>Server Diagnostics</h2>";

$privateDataDir = __DIR__ . '/../private_data';
echo "<b>Private Data Dir:</b> " . $privateDataDir . "<br>";
echo "Exists: " . (is_dir($privateDataDir) ? "YES" : "NO") . "<br>";
echo "Writable: " . (is_writable($privateDataDir) ? "YES" : "<span style='color:red'>NO</span>") . "<br>";

$ordersFile = $privateDataDir . '/orders.json';
echo "<b>Orders File:</b> " . $ordersFile . "<br>";
if (file_exists($ordersFile)) {
    echo "Exists: YES<br>";
    echo "Writable: " . (is_writable($ordersFile) ? "YES" : "<span style='color:red'>NO</span>") . "<br>";
    echo "Size: " . filesize($ordersFile) . " bytes<br>";
} else {
    echo "Exists: NO (Will attempt to create)<br>";
    if (is_writable($privateDataDir)) {
        file_put_contents($ordersFile, "[]");
        echo "Created empty orders.json: " . (file_exists($ordersFile) ? "SUCCESS" : "FAILED") . "<br>";
    }
}

echo "<hr>";
echo "<h3>SMTP Config</h3>";
echo "SMTP_USER: " . (get_config('SMTP_USER') ? "SET (Length: " . strlen(get_config('SMTP_USER')) . ")" : "<span style='color:red'>NOT SET / FALSE</span>") . "<br>";
echo "SMTP_HOST: " . (get_config('SMTP_HOST') ?: "Default") . "<br>";
echo "SMTP_PORT: " . (get_config('SMTP_PORT') ?: "Default") . "<br>";
echo "OpenSSL: " . (extension_loaded('openssl') ? "Loaded" : "<span style='color:red'>NOT LOADED</span>") . "<br>";

echo "<hr>";
echo "<h3>Debug Log</h3>";
$debugLog = $privateDataDir . '/debug.log';
if (file_exists($debugLog)) {
    echo "<pre>" . htmlspecialchars(file_get_contents($debugLog)) . "</pre>";
} else {
    echo "No debug.log found.";
}
?>
