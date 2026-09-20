<?php
require __DIR__ . '/config.php';
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

$jobId = preg_replace('/[^a-zA-Z0-9_-]/', '', (string) ($_GET['job'] ?? ''));
if ($jobId === '') {
    echo json_encode(['ok' => false, 'status' => 'error', 'error' => 'Missing job id.']);
    exit;
}

$file = $tempDir . '/' . $jobId . '_progress.json';
if (!is_file($file)) {
    echo json_encode([
        'ok' => true,
        'status' => 'waiting',
        'percent' => 0,
        'frame' => 0,
        'totalFrames' => 0,
        'message' => 'Waiting…',
        'jobId' => $jobId,
    ]);
    exit;
}

$json = file_get_contents($file);
$data = json_decode($json, true);
echo $json !== false && is_array($data) ? $json : json_encode(['ok' => false, 'status' => 'error', 'error' => 'Bad progress file.']);
