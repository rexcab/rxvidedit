<?php
/**
 * Number Counter – configuration
 * Change $ffmpegPath if FFmpeg is not in your PATH.
 */

// Change this if FFmpeg is not found (Apache often has a limited PATH).
// Examples:
// $ffmpegPath = 'C:/ffmpeg/bin/ffmpeg.exe';
if (PHP_OS_FAMILY === 'Windows') {
    $ffmpegPath = 'C:/Users/rexcab/AppData/Local/Microsoft/WinGet/Packages/Gyan.FFmpeg_Microsoft.Winget.Source_8wekyb3d8bbwe/ffmpeg-9.0.1-full_build/bin/ffmpeg.exe';
} else {
    $ffmpegPath = getenv('FFMPEG_PATH') ?: 'ffmpeg';
}

// Admin Panel Configuration (override with environment variables on Render)
$adminUsername = getenv('ADMIN_USERNAME') ?: 'admin';
$adminPassword = getenv('ADMIN_PASSWORD') ?: 'admin123';


$tempDir   = __DIR__ . '/temp';
$outputDir = __DIR__ . '/output';
$fontsDir  = __DIR__ . '/fonts';

// Maximum MP4 video files to keep in output/ (FIFO: oldest is deleted when 11th arrives)
$maxOutputFiles = 10;

// Max age of temporary logs/progress in temp/ (seconds)
$cleanupMaxAge = 86400;


/** Windows system fonts are prioritized in the font picker. */
$builtinFonts = [
    'Arial'              => 'C:/Windows/Fonts/arial.ttf',
    'Arial Bold'         => 'C:/Windows/Fonts/arialbd.ttf',
    'Arial Italic'       => 'C:/Windows/Fonts/ariali.ttf',
    'Arial Bold Italic'  => 'C:/Windows/Fonts/arialbi.ttf',
    'Arial Black'        => 'C:/Windows/Fonts/ariblk.ttf',
    'Calibri'            => 'C:/Windows/Fonts/calibri.ttf',
    'Calibri Bold'       => 'C:/Windows/Fonts/calibrib.ttf',
    'Calibri Italic'     => 'C:/Windows/Fonts/calibrii.ttf',
    'Calibri Bold Italic'=> 'C:/Windows/Fonts/calibriz.ttf',
    'Segoe UI'           => 'C:/Windows/Fonts/segoeui.ttf',
    'Segoe UI Bold'      => 'C:/Windows/Fonts/segoeuib.ttf',
    'Segoe UI Italic'    => 'C:/Windows/Fonts/segoeuii.ttf',
    'Segoe UI Bold Italic' => 'C:/Windows/Fonts/segoeuiz.ttf',
    'Tahoma'             => 'C:/Windows/Fonts/tahoma.ttf',
    'Tahoma Bold'        => 'C:/Windows/Fonts/tahomabd.ttf',
    'Verdana'            => 'C:/Windows/Fonts/verdana.ttf',
    'Verdana Bold'       => 'C:/Windows/Fonts/verdanab.ttf',
    'Verdana Italic'     => 'C:/Windows/Fonts/verdanai.ttf',
    'Verdana Bold Italic' => 'C:/Windows/Fonts/verdanaz.ttf',
    'Georgia'            => 'C:/Windows/Fonts/georgia.ttf',
    'Georgia Bold'       => 'C:/Windows/Fonts/georgiab.ttf',
    'Georgia Italic'     => 'C:/Windows/Fonts/georgiai.ttf',
    'Georgia Bold Italic'=> 'C:/Windows/Fonts/georgiaz.ttf',
    'Times New Roman'    => 'C:/Windows/Fonts/times.ttf',
    'Times New Roman Italic' => 'C:/Windows/Fonts/timesi.ttf',
    'Times New Roman Bold Italic' => 'C:/Windows/Fonts/timesbi.ttf',
    'Impact'             => 'C:/Windows/Fonts/impact.ttf',
    'Comic Sans MS'      => 'C:/Windows/Fonts/comic.ttf',
    'Comic Sans MS Italic' => 'C:/Windows/Fonts/comici.ttf',
    'Consolas'           => 'C:/Windows/Fonts/consola.ttf',
    'Consolas Italic'    => 'C:/Windows/Fonts/consolai.ttf',
    'Consolas Bold Italic' => 'C:/Windows/Fonts/consolaz.ttf',
];

/** Prefer these display names / order in the dropdown (from /fonts). */
$creatorFontOrder = [
    'Montserrat-Bold' => 'Montserrat Bold',
    'Poppins-Bold' => 'Poppins Bold',
    'BebasNeue-Regular' => 'Bebas Neue',
    'Oswald-Bold' => 'Oswald Bold',
    'Anton-Regular' => 'Anton',
    'ArchivoBlack-Regular' => 'Archivo Black',
    'Roboto-Bold' => 'Roboto Bold',
    'OpenSans-Bold' => 'Open Sans Bold',
    'Lato-Bold' => 'Lato Bold',
    'Raleway-Bold' => 'Raleway Bold',
    'Inter-Bold' => 'Inter Bold',
    'PlayfairDisplay-Bold' => 'Playfair Display Bold',
    'Nunito-Bold' => 'Nunito Bold',
    'Rubik-Bold' => 'Rubik Bold',
    'Barlow-Bold' => 'Barlow Bold',
    'SpaceGrotesk-Bold' => 'Space Grotesk Bold',
    'Outfit-Bold' => 'Outfit Bold',
    'Manrope-Bold' => 'Manrope Bold',
    'WorkSans-Bold' => 'Work Sans Bold',
    'DMSans-Bold' => 'DM Sans Bold',
    'JosefinSans-Bold' => 'Josefin Sans Bold',
    'Cabin-Bold' => 'Cabin Bold',
    'Comfortaa-Bold' => 'Comfortaa Bold',
    'Quicksand-Bold' => 'Quicksand Bold',
    'Fredoka-Bold' => 'Fredoka Bold',
    'Teko-Bold' => 'Teko Bold',
    'Exo2-Bold' => 'Exo 2 Bold',
    'Kanit-Bold' => 'Kanit Bold',
    'RussoOne-Regular' => 'Russo One',
    'Bangers-Regular' => 'Bangers',
    'AlfaSlabOne-Regular' => 'Alfa Slab One',
    'PassionOne-Bold' => 'Passion One Bold',
    'BlackOpsOne-Regular' => 'Black Ops One',
    'Pacifico-Regular' => 'Pacifico',
    'Lobster-Regular' => 'Lobster',
];

function fontDisplayName(string $filename): string {
    global $creatorFontOrder;
    if (isset($creatorFontOrder[$filename])) {
        return $creatorFontOrder[$filename];
    }
    $style = '';
    $family = preg_replace_callback('/-(BoldItalic|Italic|Bold|Regular)$/i', function (array $matches) use (&$style): string {
        $style = strtolower($matches[1]) === 'bolditalic' ? 'Bold Italic' : ucfirst(strtolower($matches[1]));
        return '';
    }, $filename) ?? $filename;
    $family = str_replace('-', ' ', $family);
    return $style === '' ? $family : $family . ' ' . $style;
}

function listAvailableFonts(): array {
    global $builtinFonts, $fontsDir, $creatorFontOrder;
    $out = [];

    // System fonts first because they are already installed and most familiar.
    foreach ($builtinFonts as $name => $path) {
        if (is_file($path)) {
            $out[$name] = $path;
        }
    }

    // Existing creator / Google fonts retain their curated order.
    if (is_dir($fontsDir)) {
        foreach ($creatorFontOrder as $fileBase => $label) {
            foreach (['.ttf', '.otf', '.TTF', '.OTF'] as $ext) {
                $path = $fontsDir . '/' . $fileBase . $ext;
                if (is_file($path)) {
                    $out[$label] = $path;
                    break;
                }
            }
        }
        // Additional imported fonts are listed alphabetically after the curated set.
        $extra = [];
        foreach (glob($fontsDir . '/*.{ttf,otf,TTF,OTF}', GLOB_BRACE) ?: [] as $file) {
            $base = pathinfo($file, PATHINFO_FILENAME);
            $label = fontDisplayName($base);
            if (!isset($out[$label]) && !isset($extra[$label])) {
                $extra[$label] = $file;
            }
        }
        uksort($extra, 'strnatcasecmp');
        $out += $extra;
    }

    return $out;
}

/** Fonts under /fonts for @font-face preview (label => relative url). */
function listWebFonts(): array {
    global $fontsDir;
    $out = [];
    foreach (listAvailableFonts() as $label => $path) {
        $realFonts = realpath($fontsDir);
        $realPath = realpath($path);
        if ($realFonts && $realPath && str_starts_with($realPath, $realFonts)) {
            $out[$label] = 'fonts/' . basename($path);
        }
    }
    return $out;
}

/** Split UTF-8 string into characters. */
function utf8Chars(string $text): array {
    return preg_split('//u', $text, -1, PREG_SPLIT_NO_EMPTY) ?: [];
}

function fontGlyphWidth(float $size, string $fontPath, string $char): int {
    static $cache = [];
    $key = $fontPath . '|' . $size . '|' . $char;
    if (!isset($cache[$key])) {
        $bbox = imagettfbbox($size, 0, $fontPath, $char);
        $cache[$key] = abs($bbox[2] - $bbox[0]);
    }
    return $cache[$key];
}

function measureSpacedText(float $size, string $fontPath, string $text, int $letterSpacing): array {
    $chars = utf8Chars($text);
    if (!$chars) {
        return ['w' => 0, 'h' => 0, 'asc' => 0];
    }
    $w = 0;
    $n = count($chars);
    foreach ($chars as $i => $ch) {
        $w += fontGlyphWidth($size, $fontPath, $ch);
        if ($i < $n - 1) {
            $w += $letterSpacing;
        }
    }
    $bbox = imagettfbbox($size, 0, $fontPath, $text);
    $h = abs($bbox[7] - $bbox[1]);
    $asc = abs($bbox[7]);
    return ['w' => (int) round($w), 'h' => (int) $h, 'asc' => (int) $asc];
}

function drawSpacedText($im, float $size, int $x, int $y, $color, string $fontPath, string $text, int $letterSpacing): void {
    $chars = utf8Chars($text);
    $cx = $x;
    foreach ($chars as $i => $ch) {
        imagettftext($im, $size, 0, (int) round($cx), $y, $color, $fontPath, $ch);
        $cx += fontGlyphWidth($size, $fontPath, $ch) + $letterSpacing;
    }
}

function drawGradientText($im, float $size, int $x, int $y, string $fontPath, string $text, int $letterSpacing, array $start, array $end): void {
    $metrics = measureSpacedText($size, $fontPath, $text, $letterSpacing);
    $mask = imagecreatetruecolor(max(1, $metrics['w'] + 4), max(1, $metrics['h'] + 4));
    imagealphablending($mask, false);
    imagesavealpha($mask, true);
    $clear = imagecolorallocatealpha($mask, 0, 0, 0, 127);
    imagefilledrectangle($mask, 0, 0, imagesx($mask) - 1, imagesy($mask) - 1, $clear);
    $white = imagecolorallocatealpha($mask, 255, 255, 255, 0);
    drawSpacedText($mask, $size, 2, $metrics['asc'] + 2, $white, $fontPath, $text, $letterSpacing);

    $colorCache = [];
    for ($py = 0; $py < imagesy($mask); $py++) {
        $ratio = imagesy($mask) <= 1 ? 0.0 : $py / (imagesy($mask) - 1);
        $r = (int) round($start[0] + (($end[0] - $start[0]) * $ratio));
        $g = (int) round($start[1] + (($end[1] - $start[1]) * $ratio));
        $b = (int) round($start[2] + (($end[2] - $start[2]) * $ratio));
        for ($px = 0; $px < imagesx($mask); $px++) {
            $maskPixel = imagecolorat($mask, $px, $py);
            $alpha = ($maskPixel >> 24) & 0x7F;
            if ($alpha >= 127) {
                continue;
            }
            $key = $r . ',' . $g . ',' . $b . ',' . $alpha;
            if (!isset($colorCache[$key])) {
                $colorCache[$key] = imagecolorallocatealpha($im, $r, $g, $b, $alpha);
            }
            imagesetpixel($im, $x + $px - 2, $y - $metrics['asc'] + $py - 2, $colorCache[$key]);
        }
    }
    imagedestroy($mask);
}

function boxBlur1D(array &$src, int $w, int $h, int $r): array {
    if ($r < 1) {
        return $src;
    }
    $dst = array_fill(0, $w * $h, 0);
    $div = $r + $r + 1;
    for ($py = 0; $py < $h; $py++) {
        $row = $py * $w;
        $sum = 0;
        for ($i = -$r; $i <= $r; $i++) {
            $ix = max(0, min($w - 1, $i));
            $sum += $src[$row + $ix];
        }
        for ($px = 0; $px < $w; $px++) {
            $dst[$row + $px] = (int) ($sum / $div);
            $left = max(0, $px - $r);
            $right = min($w - 1, $px + $r + 1);
            $sum += $src[$row + $right] - $src[$row + $left];
        }
    }
    $out = array_fill(0, $w * $h, 0);
    for ($px = 0; $px < $w; $px++) {
        $sum = 0;
        for ($i = -$r; $i <= $r; $i++) {
            $iy = max(0, min($h - 1, $i));
            $sum += $dst[$iy * $w + $px];
        }
        for ($py = 0; $py < $h; $py++) {
            $out[$py * $w + $px] = (int) ($sum / $div);
            $top = max(0, $py - $r);
            $bot = min($h - 1, $py + $r + 1);
            $sum += $dst[$bot * $w + $px] - $dst[$top * $w + $px];
        }
    }
    return $out;
}

function drawGlowText($im, float $size, int $x, int $y, string $fontPath, string $text, int $letterSpacing, array $glowRgb, int $glowDistance, int $glowStrength, int $canvasWidth, int $canvasHeight): void {
    if ($glowDistance <= 0 || $glowStrength <= 0) {
        return;
    }

    $metrics = measureSpacedText($size, $fontPath, $text, $letterSpacing);
    $pad = $glowDistance + 4;
    $mw = $metrics['w'] + ($pad * 2);
    $mh = $metrics['h'] + ($pad * 2);

    $mask = imagecreatetruecolor($mw, $mh);
    imagealphablending($mask, false);
    imagesavealpha($mask, true);
    $trans = imagecolorallocatealpha($mask, 0, 0, 0, 127);
    imagefilledrectangle($mask, 0, 0, $mw - 1, $mh - 1, $trans);
    imagealphablending($mask, true);
    $white = imagecolorallocatealpha($mask, 255, 255, 255, 0);
    drawSpacedText($mask, $size, $pad, $pad + $metrics['asc'], $white, $fontPath, $text, $letterSpacing);

    $buf = array_fill(0, $mw * $mh, 0);
    for ($py = 0; $py < $mh; $py++) {
        for ($px = 0; $px < $mw; $px++) {
            $pixel = imagecolorat($mask, $px, $py);
            $alpha = ($pixel >> 24) & 0x7F;
            if ($alpha < 127) {
                $buf[$py * $mw + $px] = (int) round((1 - ($alpha / 127)) * 255);
            }
        }
    }
    imagedestroy($mask);

    $radius = max(1, (int) round($glowDistance / 2));
    $b1 = boxBlur1D($buf, $mw, $mh, $radius);
    $b2 = boxBlur1D($b1, $mw, $mh, $radius);

    $strength = $glowStrength / 100.0;
    $originX = $x - $pad;
    $originY = $y - $metrics['asc'] - $pad;
    $colorCache = [];
    [$gr, $gg, $gb] = $glowRgb;

    for ($py = 0; $py < $mh; $py++) {
        $destY = $originY + $py;
        if ($destY < 0 || $destY >= $canvasHeight) {
            continue;
        }
        for ($px = 0; $px < $mw; $px++) {
            $destX = $originX + $px;
            if ($destX < 0 || $destX >= $canvasWidth) {
                continue;
            }
            $v = $b2[$py * $mw + $px];
            if ($v <= 0) {
                continue;
            }
            $opacity = $strength * ($v / 255.0);
            if ($opacity <= 0.005) {
                continue;
            }
            $alpha = max(0, min(127, (int) round(127 * (1.0 - $opacity))));
            if ($alpha >= 127) {
                continue;
            }

            if (!isset($colorCache[$alpha])) {
                $colorCache[$alpha] = imagecolorallocatealpha($im, $gr, $gg, $gb, $alpha);
            }
            imagesetpixel($im, $destX, $destY, $colorCache[$alpha]);
        }
    }
}

function ffmpegExists(): bool {
    global $ffmpegPath;
    if ($ffmpegPath !== 'ffmpeg' && is_file($ffmpegPath)) {
        return true;
    }
    $cmd = escapeshellarg($ffmpegPath) . ' -version 2>&1';
    exec($cmd, $lines, $code);
    return $code === 0;
}

function cleanupOldFiles(): void {
    global $tempDir, $outputDir, $cleanupMaxAge, $maxOutputFiles;
    $now = time();

    // Clean temp directory for files older than $cleanupMaxAge
    if (is_dir($tempDir)) {
        foreach (glob($tempDir . '/*') ?: [] as $path) {
            $base = basename($path);
            if ($base === '.htaccess' || $base === '.gitkeep') {
                continue;
            }
            if (is_file($path) && ($now - filemtime($path)) > $cleanupMaxAge) {
                @unlink($path);
            }
            if (is_dir($path) && ($now - filemtime($path)) > $cleanupMaxAge) {
                deleteDir($path);
            }
        }
    }

    // Enforce 10-file maximum in output/ (FIFO: oldest deleted when > 10)
    enforceMaxOutputFiles($maxOutputFiles ?? 10);
}

/**
 * Enforce maximum saved MP4 videos in output/ directory.
 * Implements FIFO (First In, First Out): if count > $maxFiles,
 * removes the oldest modified file(s) so only the latest $maxFiles remain.
 */
function enforceMaxOutputFiles(int $maxFiles = 10): int {
    global $outputDir, $tempDir;
    if (!is_dir($outputDir)) {
        return 0;
    }

    $files = glob($outputDir . '/*.mp4') ?: [];
    if (count($files) <= $maxFiles) {
        return 0;
    }

    // Sort by file modification time (oldest first)
    usort($files, function ($a, $b) {
        return filemtime($a) - filemtime($b);
    });

    $deleted = 0;
    while (count($files) > $maxFiles) {
        $oldest = array_shift($files);
        if (is_file($oldest)) {
            @unlink($oldest);
            $deleted++;

            // Clean up associated temp progress and log files
            $jobId = pathinfo($oldest, PATHINFO_FILENAME);
            if (!empty($jobId)) {
                $json = $tempDir . '/' . $jobId . '.json';
                if (is_file($json)) @unlink($json);
                $log = $tempDir . '/' . $jobId . '_ffmpeg.log';
                if (is_file($log)) @unlink($log);
            }
        }
    }

    return $deleted;
}


function deleteDir(string $dir): void {
    if (!is_dir($dir)) {
        return;
    }
    foreach (scandir($dir) ?: [] as $item) {
        if ($item === '.' || $item === '..') {
            continue;
        }
        $path = $dir . DIRECTORY_SEPARATOR . $item;
        is_dir($path) ? deleteDir($path) : @unlink($path);
    }
    @rmdir($dir);
}

function getDirSize(string $dir): array {
    $size = 0;
    $count = 0;
    if (is_dir($dir)) {
        foreach (glob($dir . '/*') ?: [] as $path) {
            $base = basename($path);
            if ($base === '.htaccess' || $base === '.gitkeep') {
                continue;
            }
            if (is_file($path)) {
                $size += filesize($path);
                $count++;
            } elseif (is_dir($path)) {
                $sub = getDirSize($path);
                $size += $sub['size'];
                $count += $sub['count'];
            }
        }
    }
    return [
        'bytes' => $size,
        'count' => $count,
        'mb'    => round($size / (1024 * 1024), 2)
    ];
}

function purgeStorageFiles(): int {
    global $tempDir, $outputDir;
    $deleted = 0;
    foreach ([$tempDir, $outputDir] as $dir) {
        if (!is_dir($dir)) {
            continue;
        }
        foreach (glob($dir . '/*') ?: [] as $path) {
            $base = basename($path);
            if ($base === '.htaccess' || $base === '.gitkeep') {
                continue;
            }
            if (is_file($path)) {
                @unlink($path);
                $deleted++;
            } elseif (is_dir($path)) {
                deleteDir($path);
                $deleted++;
            }
        }
    }
    return $deleted;
}


function hexToRgb(string $hex): array {
    $hex = ltrim($hex, '#');
    if (strlen($hex) === 3) {
        $hex = $hex[0].$hex[0].$hex[1].$hex[1].$hex[2].$hex[2];
    }
    return [
        hexdec(substr($hex, 0, 2)),
        hexdec(substr($hex, 2, 2)),
        hexdec(substr($hex, 4, 2)),
    ];
}

function applyEasing(float $t, string $easing): float {
    $t = max(0.0, min(1.0, $t));
    switch ($easing) {
        case 'easeIn':
            return $t * $t;
        case 'easeOut':
            return $t * (2 - $t);
        case 'easeInOut':
            return $t < 0.5 ? 2 * $t * $t : -1 + (4 - 2 * $t) * $t;
        default:
            return $t;
    }
}

function formatCounterNumber(float $value, int $decimals, bool $commas): string {
    if ($decimals > 0) {
        return $commas
            ? number_format($value, $decimals, '.', ',')
            : number_format($value, $decimals, '.', '');
    }
    $int = (int) round($value);
    return $commas ? number_format($int, 0, '.', ',') : (string) $int;
}

function detectDecimals($start, $end): int {
    $count = function ($v): int {
        $s = trim((string) $v);
        if (strpos($s, '.') === false) {
            return 0;
        }
        return strlen(rtrim(substr($s, strpos($s, '.') + 1), '0')) ?: 0;
    };
    return min(6, max($count($start), $count($end)));
}
