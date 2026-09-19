<?php
require __DIR__ . '/config.php';

$jobId = preg_replace('/[^a-zA-Z0-9_-]/', '', (string) ($_GET['job'] ?? ''));
if ($jobId === '') {
    http_response_code(400);
    echo 'Invalid job.';
    exit;
}

$file = $outputDir . '/' . $jobId . '.mp4';
if (!is_file($file)) {
    http_response_code(404);
    echo 'File not found. Export again.';
    exit;
}

cleanupOldFiles();

header('Content-Type: video/mp4');
header('Content-Length: ' . filesize($file));
header('Content-Disposition: attachment; filename="number-counter.mp4"');
header('Cache-Control: no-store');
readfile($file);
