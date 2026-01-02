<?php
// debug_json.php
// To verify if the server is outputting garbage before PHP starts

ob_start();
ini_set('display_errors', 0);
error_reporting(E_ALL);

// Simulate some work
$data = ['status' => 'success', 'message' => 'Clean JSON test'];

ob_clean();
header('Content-Type: application/json');
echo json_encode($data);
ob_end_flush();
