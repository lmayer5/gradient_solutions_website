<?php
// admin/invite_github.php
// Triggers GitHub API to invite a user to a private repository.

// 0. STRICT OUTPUT BUFFERING - Prevent any stray output from breaking JSON
ob_start();
ini_set('display_errors', 0);
ini_set('log_errors', 1);
error_reporting(E_ALL);

session_start();

header('Content-Type: application/json');

function sendJsonResponse($status, $message, $data = []) {
    ob_clean();
    $response = ['status' => $status, 'message' => $message];
    if (!empty($data)) $response['details'] = $data;
    echo json_encode($response);
    ob_end_flush();
    exit;
}

try {
    // Auth check
    if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
        throw new Exception('Unauthorized');
    }

    require_once __DIR__ . '/../env_loader.php';

    $username = $_POST['username'] ?? '';
    $orderId = $_POST['order_id'] ?? '';

    if (empty($username)) {
        throw new Exception('Username is required');
    }

    // 1. Load Credentials
    $privateDataDir = realpath(__DIR__ . '/../../private_data') ?: (__DIR__ . '/../../private_data');
    $settingsFile = $privateDataDir . '/settings.json';
    $settings = [];
    if (file_exists($settingsFile)) {
        $settings = json_decode(file_get_contents($settingsFile), true) ?? [];
    }

    $githubToken = !empty($settings['github_pat']) ? $settings['github_pat'] : get_config('GITHUB_PAT');
    $defaultRepo = !empty($settings['github_repo']) ? $settings['github_repo'] : get_config('GITHUB_REPO');

    if (empty($githubToken)) {
        throw new Exception('GitHub PAT not configured. Set it in Admin Settings.');
    }

    if (empty($defaultRepo)) {
        throw new Exception('GitHub Repository not configured. Set it in Admin Settings.');
    }

    // 2. Load Order & Resolve Repositories
    $ordersFile = $privateDataDir . '/orders.json';
    $orders = [];
    if (file_exists($ordersFile)) {
        $orders = json_decode(file_get_contents($ordersFile), true) ?? [];
    }

    $order = null;
    foreach ($orders as $o) {
        if ($o['id'] === $orderId) {
            $order = $o;
            break;
        }
    }

    if (!$order) {
        throw new Exception('Order not found');
    }

    $items = $order['details']['cart'] ?? [];
    $mappings = $settings['repo_mappings'] ?? [];
    $reposToInvite = [];

    if (empty($items)) {
        if (!empty($defaultRepo)) $reposToInvite[] = $defaultRepo;
    } else {
        foreach ($items as $item) {
            $productId = $item['id'] ?? '';
            $mappedRepo = '';
            foreach ($mappings as $map) {
                if ($map['product_id'] === $productId) {
                    $mappedRepo = $map['repo'];
                    break;
                }
            }
            $targetRepo = !empty($mappedRepo) ? $mappedRepo : $defaultRepo;
            if (!empty($targetRepo)) {
                $reposToInvite[] = $targetRepo;
            }
        }
    }

    $reposToInvite = array_unique($reposToInvite);

    if (empty($reposToInvite)) {
        throw new Exception('No repository configured for these products.');
    }

    // 3. Trigger GitHub API
    $results = [];
    $allSuccess = true;

    foreach ($reposToInvite as $r) {
        $rawRepo = trim($r);
        $targetRepo = str_replace('https://github.com/', '', $rawRepo);
        $targetRepo = trim($targetRepo, '/');

        if (empty($targetRepo)) continue;

        $url = "https://api.github.com/repos/$targetRepo/collaborators/$username";

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $githubToken,
            'Accept: application/vnd.github.v3+json',
            'User-Agent: Gradient-Solutions-Admin'
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode >= 200 && $httpCode < 300) {
            $results[] = ["repo" => $targetRepo, "status" => "success"];
            error_log("GitHub Invitation sent to $username for Repo $targetRepo (Order: $orderId)");
        } else {
            $allSuccess = false;
            $errData = json_decode($response, true);
            $errMsg = $errData['message'] ?? 'API Error ' . $httpCode;
            $results[] = ["repo" => $targetRepo, "status" => "error", "message" => $errMsg];
            error_log("GitHub API Error for $targetRepo: $errMsg");
        }
    }

    if ($allSuccess) {
        sendJsonResponse('success', 'Invitations sent to ' . count($reposToInvite) . ' repository/ies!');
    } else {
        $msg = "Some invitations failed. Check logs.";
        if (count($reposToInvite) === 1 && isset($results[0]['message'])) {
            $msg = $results[0]['message'];
        }
        sendJsonResponse('error', $msg, $results);
    }

} catch (Exception $e) {
    error_log("invite_github.php error: " . $e->getMessage());
    sendJsonResponse('error', $e->getMessage());
}
