<?php
// get_settings.php
header('Content-Type: application/json');

// Path to settings file
// Using internal path strictly for local compat, ensure deployment guide mentions moving it if consistent structure desired
// For now, consistent with where I wrote the file:
$settingsFile = __DIR__ . '/private_data/settings.json';

// Fallback logic for production if moved outside
if (!file_exists($settingsFile)) {
    $settingsFile = __DIR__ . '/../private_data/settings.json';
}

if (file_exists($settingsFile)) {
    echo file_get_contents($settingsFile);
} else {
    // Default fallback if file missing
    echo json_encode([
        "address" => "10138 Red Pine Road",
        "email" => "bill@stubberfield.ca",
        "phone" => "519-733-2010",
        "timezone" => "America/Toronto",
        "about_title" => "About Martini Golf Tees",
        "about_text" => "We are a Canadian distributor dedicated to providing the best golf tees on the market. Martini Tees are durable, consistent, and proven to help you drive farther.",
        "pricing" => [
            "1" => 16, "2" => 28, "3" => 38, "4" => 47, "5" => 56, "6" => 66, "extra" => 11
        ]
    ]);
}
exit;
