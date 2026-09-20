<?php
/**
 * Tracker Endpoint
 * Receives lightweight analytics beacons from client browsers.
 * Filters out bots and monitoring pingers (e.g. UptimeRobot).
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/analytics_db.php';

// Detect Bots and Automated Monitors
$userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
$botSignatures = [
    'uptimerobot', 'bot', 'spider', 'crawler', 'curl', 'wget', 'python',
    'headlesschrome', 'lighthouse', 'slurp', 'facebookexternalhit',
    'go-http-client', 'postman', 'monitor', 'pingdom', 'googlebot'
];

$isBot = false;
$uaLower = strtolower($userAgent);
foreach ($botSignatures as $sig) {
    if (strpos($uaLower, $sig) !== false) {
        $isBot = true;
        break;
    }
}

if ($isBot || empty($userAgent)) {
    echo json_encode(['status' => 'ignored', 'reason' => 'bot_or_empty_ua']);
    exit;
}

// Parse Payload (Supports JSON body, POST, and GET)
$rawBody = file_get_contents('php://input');
$data = json_decode($rawBody, true) ?: [];

$page = $data['page'] ?? $_POST['page'] ?? $_GET['page'] ?? 'homepage';
$device = $data['device'] ?? $_POST['device'] ?? $_GET['device'] ?? 'Desktop';
$referrer = $data['referrer'] ?? $_POST['referrer'] ?? $_GET['referrer'] ?? 'Direct';

// Anonymized Visitor Hash (Salted SHA-256 of IP + UA)
$ip = $_SERVER['HTTP_CF_CONNECTING_IP'] 
    ?? $_SERVER['HTTP_X_FORWARDED_FOR'] 
    ?? $_SERVER['REMOTE_ADDR'] 
    ?? '127.0.0.1';
$ip = trim(explode(',', $ip)[0]);
$salt = 'rx_editor_analytics_salt_secure_2026';
$visitorHash = hash('sha256', $ip . '|' . $userAgent . '|' . $salt);

// Record in SQLite
$success = AnalyticsDB::recordVisit($page, $visitorHash, $device, $referrer);

echo json_encode(['status' => $success ? 'ok' : 'error']);
