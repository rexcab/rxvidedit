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
    echo 'File not found or already downloaded. Please export again.';
    exit;
}

// Clean up any other files older than 5 minutes
cleanupOldFiles();

// Serve the file to the browser
header('Content-Type: video/mp4');
header('Content-Length: ' . filesize($file));
header('Content-Disposition: attachment; filename="number-counter.mp4"');
header('Cache-Control: no-store, no-cache, must-revalidate');

readfile($file);
flush();
exit;

