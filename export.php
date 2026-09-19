<?php
/**
 * Export job – renders frames, encodes MP4 with alpha (PNG codec), cleans temp.
 */
require __DIR__ . '/config.php';

header('Content-Type: application/json; charset=utf-8');
set_time_limit(0);
ignore_user_abort(true);

cleanupOldFiles();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'POST required.']);
    exit;
}

$raw = file_get_contents('php://input');
$data = json_decode($raw, true);
if (!is_array($data)) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Invalid request.']);
    exit;
}

function fail(string $msg, int $code = 400): void {
    http_response_code($code);
    echo json_encode(['ok' => false, 'error' => $msg]);
    exit;
}

function writeProgress(string $file, array $p): void {
    file_put_contents($file, json_encode($p));
}

// ---- Validate ----
if (!ffmpegExists()) {
    fail('FFmpeg was not found. Please install FFmpeg and configure the path in config.php.', 500);
}

if (!function_exists('imagecreatetruecolor') || !function_exists('imagettftext')) {
    fail('PHP GD with FreeType is required.', 500);
}

$startRaw = $data['start'] ?? null;
$endRaw   = $data['end'] ?? null;
if (!is_numeric($startRaw) || !is_numeric($endRaw)) {
    fail('Please enter valid numbers.');
}
$start = (float) $startRaw;
$end   = (float) $endRaw;

$duration = isset($data['duration']) ? (float) $data['duration'] : 0;
if ($duration <= 0) {
    fail('Duration must be greater than 0.');
}
if ($duration > 120) {
    fail('Duration must be 120 seconds or less.');
}

$fps = (int) ($data['fps'] ?? 30);
if (!in_array($fps, [24, 30, 60], true)) {
    fail('FPS must be 24, 30, or 60.');
}

$width  = (int) ($data['width'] ?? 1920);
$height = (int) ($data['height'] ?? 1080);
$validResolutions = [
    '1920x1080', '1280x720', '640x360',
    '1080x1920', '720x1280', '360x640',
    '1080x1080', '720x720', '360x360',
];
if (!in_array($width . 'x' . $height, $validResolutions, true)) {
    fail('Please choose a 1080p, 720p, or 360p resolution.');
}

$fontSize = (int) ($data['fontSize'] ?? 120);
if ($fontSize < 8 || $fontSize > 800) {
    fail('Font size must be between 8 and 800.');
}

$fonts = listAvailableFonts();
$fontName = (string) ($data['font'] ?? 'Arial Bold');
if (!isset($fonts[$fontName])) {
    fail('Selected font could not be found.');
}
$fontPath = $fonts[$fontName];
if (!is_file($fontPath)) {
    fail('Selected font could not be found.');
}

$textColor   = (string) ($data['textColor'] ?? '#FFFFFF');
$colorMode   = (string) ($data['colorMode'] ?? 'solid');
$gradientStart = (string) ($data['gradientStart'] ?? '#FF2D55');
$gradientEnd = (string) ($data['gradientEnd'] ?? '#7C3AED');
$depthEnabled = !empty($data['depthEnabled']);
$depth = max(0, min(40, (int) ($data['depth'] ?? 0)));
$depthAngle = (string) ($data['depthAngle'] ?? 'down-right');
$depthColor = (string) ($data['depthColor'] ?? '#111827');
$glowEnabled = !empty($data['glowEnabled']);
$glowDistance = max(0, min(50, (int) ($data['glowDistance'] ?? 0)));
$glowStrength = max(1, min(100, (int) ($data['glowStrength'] ?? 65)));
$glowColor = (string) ($data['glowColor'] ?? '#38BDF8');
$borderOn    = !empty($data['borderEnabled']);
$borderColor = (string) ($data['borderColor'] ?? '#000000');
$borderWidth = max(0, min(40, (int) ($data['borderWidth'] ?? 0)));
$letterSpacing = max(-40, min(200, (int) ($data['letterSpacing'] ?? 0)));
$prefix      = (string) ($data['prefix'] ?? '');
$suffix      = (string) ($data['suffix'] ?? '');
$commas      = !empty($data['commas']);
$easing      = (string) ($data['easing'] ?? 'linear');
if (!in_array($easing, ['linear', 'easeIn', 'easeOut', 'easeInOut'], true)) {
    $easing = 'linear';
}

$align = (string) ($data['align'] ?? 'center');
$validAlign = [
    'top-left','top-center','top-right',
    'center-left','center','center-right',
    'bottom-left','bottom-center','bottom-right',
];
if (!in_array($align, $validAlign, true)) {
    $align = 'center';
}

$autoCenter = !isset($data['autoCenter']) || !empty($data['autoCenter']);
$posX = (int) ($data['posX'] ?? 0);
$posY = (int) ($data['posY'] ?? 0);

$decimals = detectDecimals((string) $startRaw, (string) $endRaw);
$countFrames = max(1, (int) round($duration * $fps));
$holdFrames = $fps * 2;
$totalFrames = $countFrames + $holdFrames;

$jobId = preg_replace('/[^a-zA-Z0-9_-]/', '', (string) ($data['jobId'] ?? ''));
if ($jobId === '' || strlen($jobId) > 64) {
    $jobId = bin2hex(random_bytes(8));
}

$jobDir = $tempDir . '/' . $jobId;
$progressFile = $tempDir . '/' . $jobId . '_progress.json';
$outputFile = $outputDir . '/' . $jobId . '.mp4';

if (is_dir($jobDir)) {
    deleteDir($jobDir);
}
mkdir($jobDir, 0777, true);
if (!is_dir($outputDir)) {
    mkdir($outputDir, 0777, true);
}

[$tr, $tg, $tb] = hexToRgb($textColor);
[$gsr, $gsg, $gsb] = hexToRgb($gradientStart);
[$ger, $geg, $geb] = hexToRgb($gradientEnd);
[$dr, $dg, $db] = hexToRgb($depthColor);
[$gr, $gg, $gb] = hexToRgb($glowColor);
$depthSigns = [
    'down-right' => [1, 1], 'down-left' => [-1, 1],
    'up-right' => [1, -1], 'up-left' => [-1, -1],
];
[$depthX, $depthY] = $depthSigns[$depthAngle] ?? $depthSigns['down-right'];
[$br, $bg, $bb] = hexToRgb($borderColor);

writeProgress($progressFile, [
    'ok' => true,
    'status' => 'rendering',
    'percent' => 0,
    'frame' => 0,
    'totalFrames' => $totalFrames,
    'message' => 'Rendering frames…',
    'jobId' => $jobId,
]);

// Flush early so the client can start polling (best-effort)
if (function_exists('fastcgi_finish_request')) {
    // Keep running after response — only if we return early; we need full response at end.
}

try {
    // ---- Render frames ----
    for ($i = 0; $i < $totalFrames; $i++) {
        $progress = ($i < $countFrames && $countFrames > 1)
            ? ($i / ($countFrames - 1))
            : 1.0;
        $t = applyEasing($progress, $easing);
        $value = $start + ($end - $start) * $t;
        $label = $prefix . formatCounterNumber($value, $decimals, $commas) . $suffix;

        $im = imagecreatetruecolor($width, $height);
        imagesavealpha($im, true);
        imagealphablending($im, false);
        $transparent = imagecolorallocatealpha($im, 0, 0, 0, 127);
        imagefilledrectangle($im, 0, 0, $width, $height, $transparent);
        imagealphablending($im, true);

        $metrics = measureSpacedText($fontSize, $fontPath, $label, $letterSpacing);
        $textW = $metrics['w'];
        $textH = $metrics['h'];
        $asc = $metrics['asc'];

        if ($autoCenter) {
            switch ($align) {
                case 'top-left':
                    $x = 40; $y = 40 + $asc; break;
                case 'top-center':
                    $x = (int) (($width - $textW) / 2); $y = 40 + $asc; break;
                case 'top-right':
                    $x = $width - $textW - 40; $y = 40 + $asc; break;
                case 'center-left':
                    $x = 40; $y = (int) (($height + $asc - ($textH - $asc)) / 2); break;
                case 'center-right':
                    $x = $width - $textW - 40; $y = (int) (($height + $asc - ($textH - $asc)) / 2); break;
                case 'bottom-left':
                    $x = 40; $y = $height - 40; break;
                case 'bottom-center':
                    $x = (int) (($width - $textW) / 2); $y = $height - 40; break;
                case 'bottom-right':
                    $x = $width - $textW - 40; $y = $height - 40; break;
                case 'center':
                default:
                    $x = (int) (($width - $textW) / 2);
                    $y = (int) (($height + $asc - ($textH - $asc)) / 2);
                    break;
            }
        } else {
            $x = $posX;
            $y = $posY;
        }

        // 1. Glow (Background-most layer, true 2D Gaussian blur with exact intensity scaling)
        if ($glowEnabled && $glowDistance > 0 && $glowStrength > 0) {
            drawGlowText($im, $fontSize, $x, $y, $fontPath, $label, $letterSpacing, [$gr, $gg, $gb], $glowDistance, $glowStrength, $width, $height);
        }

        // 2. 3D Depth (Layered extrusion shadow)
        if ($depthEnabled && $depth > 0) {
            for ($step = $depth; $step > 0; $step--) {
                $depthCol = imagecolorallocate($im, $dr, $dg, $db);
                drawSpacedText($im, $fontSize, $x + ($depthX * $step), $y + ($depthY * $step), $depthCol, $fontPath, $label, $letterSpacing);
            }
        }

        // 3. Border Outline (Crisp stroke around text, on top of glow and depth)
        if ($borderOn && $borderWidth > 0) {
            $bordCol = imagecolorallocate($im, $br, $bg, $bb);
            for ($ox = -$borderWidth; $ox <= $borderWidth; $ox++) {
                for ($oy = -$borderWidth; $oy <= $borderWidth; $oy++) {
                    if ($ox * $ox + $oy * $oy > $borderWidth * $borderWidth) {
                        continue;
                    }
                    drawSpacedText($im, $fontSize, $x + $ox, $y + $oy, $bordCol, $fontPath, $label, $letterSpacing);
                }
            }
        }

        // 4. Foreground Text Fill (Solid color or Gradient)
        if ($colorMode === 'gradient') {
            drawGradientText($im, $fontSize, $x, $y, $fontPath, $label, $letterSpacing, [$gsr, $gsg, $gsb], [$ger, $geg, $geb]);
        } else {
            $textCol = imagecolorallocate($im, $tr, $tg, $tb);
            drawSpacedText($im, $fontSize, $x, $y, $textCol, $fontPath, $label, $letterSpacing);
        }

        $framePath = sprintf('%s/%05d.png', $jobDir, $i + 1);
        // Low PNG compression keeps frame rendering and disk I/O quick; pixels and alpha stay lossless.
        imagepng($im, $framePath, 1);
        imagedestroy($im);

        // Frame render = 0–85%
        $pct = (int) floor((($i + 1) / $totalFrames) * 85);
        if ($i === 0 || $i === $totalFrames - 1 || ($i + 1) % 3 === 0) {
            writeProgress($progressFile, [
                'ok' => true,
                'status' => 'rendering',
                'percent' => $pct,
                'frame' => $i + 1,
                'totalFrames' => $totalFrames,
                'message' => 'Rendering frames…',
                'jobId' => $jobId,
            ]);
        }
    }

    writeProgress($progressFile, [
        'ok' => true,
        'status' => 'encoding',
        'percent' => 88,
        'frame' => $totalFrames,
        'totalFrames' => $totalFrames,
        'message' => 'Encoding MP4…',
        'jobId' => $jobId,
    ]);

    // PNG codec in MP4 preserves RGBA alpha (not H.264 — see help text).
    // Use proc_open argv array so Windows cmd does not expand %05d.
    $pattern = $jobDir . DIRECTORY_SEPARATOR . '%05d.png';
    $ffLog = $tempDir . DIRECTORY_SEPARATOR . $jobId . '_ffmpeg.log';
    $cmd = [
        $ffmpegPath,
        '-y',
        '-framerate', (string) (int) $fps,
        '-i', $pattern,
        '-c:v', 'png',
            '-compression_level', '1',
            '-pred', 'sub',
            '-threads', '0',
        '-pix_fmt', 'rgba',
        $outputFile,
    ];

    $descriptors = [
        0 => ['pipe', 'r'],
        1 => ['pipe', 'w'],
        2 => ['file', $ffLog, 'w'],
    ];
    $proc = proc_open($cmd, $descriptors, $pipes, null, null, ['bypass_shell' => true]);
    $code = 1;
    if (is_resource($proc)) {
        fclose($pipes[0]);
        stream_get_contents($pipes[1]);
        fclose($pipes[1]);
        $code = proc_close($proc);
    }

    if ($code !== 0 || !is_file($outputFile) || filesize($outputFile) < 32) {
        deleteDir($jobDir);
        writeProgress($progressFile, [
            'ok' => false,
            'status' => 'error',
            'percent' => 0,
            'frame' => 0,
            'totalFrames' => $totalFrames,
            'message' => 'Export failed. Check the FFmpeg configuration.',
            'jobId' => $jobId,
            'error' => 'Export failed. Check the FFmpeg configuration.',
        ]);
        fail('Export failed. Check the FFmpeg configuration.', 500);
    }

    // Cleanup frames
    deleteDir($jobDir);
    @unlink($ffLog);

    writeProgress($progressFile, [
        'ok' => true,
        'status' => 'done',
        'percent' => 100,
        'frame' => $totalFrames,
        'totalFrames' => $totalFrames,
        'message' => 'Encoding complete — 100%',
        'jobId' => $jobId,
        'download' => 'download.php?job=' . urlencode($jobId),
    ]);

    echo json_encode([
        'ok' => true,
        'jobId' => $jobId,
        'download' => 'download.php?job=' . urlencode($jobId),
        'totalFrames' => $totalFrames,
    ]);
} catch (Throwable $e) {
    if (is_dir($jobDir)) {
        deleteDir($jobDir);
    }
    writeProgress($progressFile, [
        'ok' => false,
        'status' => 'error',
        'percent' => 0,
        'message' => 'Export failed. Check the FFmpeg configuration.',
        'error' => 'Export failed. Check the FFmpeg configuration.',
        'jobId' => $jobId,
    ]);
    fail('Export failed. Check the FFmpeg configuration.', 500);
}
