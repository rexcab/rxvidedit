<?php
require __DIR__ . '/config.php';
$fonts = listAvailableFonts();
$webFonts = listWebFonts();
$ffmpegOk = ffmpegExists();
$defaultFont = isset($fonts['Montserrat Bold']) ? 'Montserrat Bold' : (array_key_first($fonts) ?: 'Arial Bold');
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Number Counter Studio • Pro Motion Generator</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:ital,wght@0,400;0,500;0,600;0,700;0,800;1,700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="style.css">
  <style>
<?php foreach ($webFonts as $label => $url): ?>
    @font-face {
      font-family: <?= json_encode($label) ?>;
      src: url(<?= json_encode($url) ?>) format('truetype');
      font-display: swap;
    }
<?php endforeach; ?>
  </style>
</head>
<body>
  <div class="wrap">
    <!-- Studio Header -->
    <header class="studio-header">
      <div class="brand-wrap">
        <div class="brand-icon">
          <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
            <polyline points="22 12 18 12 15 21 9 3 6 12 2 12"></polyline>
          </svg>
        </div>
        <div class="brand-title">
          <h1>Number Counter Studio <span class="badge">PRO 2.0</span></h1>
          <p class="brand-subtitle">High-FPS Animated Motion Numbers with Transparent Alpha MP4 for CapCut, Premiere, &amp; DaVinci</p>
        </div>
      </div>
      <div class="header-status">
        <?php if ($ffmpegOk): ?>
          <div class="status-pill" title="FFmpeg rendering engine is ready">
            <span class="status-dot"></span>
            <span>FFmpeg Ready</span>
          </div>
        <?php else: ?>
          <div class="status-pill error" title="FFmpeg was not detected">
            <span class="status-dot"></span>
            <span>FFmpeg Missing</span>
          </div>
        <?php endif; ?>
        <span class="tagline-pill">Lossless PNG-in-MP4 Alpha</span>
      </div>
    </header>

    <?php if (!$ffmpegOk): ?>
      <div class="banner err">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
        <div>FFmpeg executable was not detected. Please verify your binary path in <code>config.php</code>.</div>
      </div>
    <?php endif; ?>

    <!-- Main Workspace Grid -->
    <div class="studio-grid">
      <!-- Left Column: Controls Deck -->
      <form id="form" class="deck" autocomplete="off">
        
        <!-- Card 1: Numbers & Dynamics -->
        <div class="deck-card">
          <div class="card-header">
            <div class="card-title">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="8 10 12 14 16 10"/></svg>
              <span>Number Dynamics</span>
            </div>
            <span class="card-tag">Value &amp; Speed</span>
          </div>

          <!-- Quick Presets -->
          <div class="presets-strip">
            <span class="presets-label">
              <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/></svg>
              Presets:
            </span>
            <button type="button" class="preset-chip" data-preset="money">$0 → $1,000</button>
            <button type="button" class="preset-chip" data-preset="countdown">10s Countdown</button>
            <button type="button" class="preset-chip" data-preset="subs">0 → 100K Subs</button>
            <button type="button" class="preset-chip" data-preset="percent">0% → 100%</button>
          </div>

          <div class="field-grid">
            <div class="field">
              <label class="field-label" for="start">Start Number</label>
              <input type="text" id="start" name="start" value="1" required>
            </div>
            <div class="field">
              <label class="field-label" for="end">End Number</label>
              <input type="text" id="end" name="end" value="1000" required>
            </div>
          </div>

          <div class="field-grid">
            <div class="field">
              <label class="field-label" for="duration">
                <span>Duration (seconds)</span>
                <span id="durationVal" class="field-val-badge">5s</span>
              </label>
              <div class="slider-input-combo">
                <input type="range" id="durationRange" min="0.5" max="60" step="0.5" value="5">
                <input type="number" id="duration" name="duration" value="5" min="0.1" max="120" step="0.1" required>
              </div>
            </div>
            <div class="field">
              <label class="field-label" for="fps">Target FPS</label>
              <select id="fps" name="fps">
                <option value="24" selected>24 FPS (Cinematic)</option>
                <option value="30">30 FPS (Standard Web)</option>
                <option value="60">60 FPS (Ultra Smooth)</option>
              </select>
            </div>
          </div>

          <div class="toggle-row">
            <label class="toggle-label" for="commas">
              <span class="toggle-icon">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M7 20l4-16m2 16l4-16M6 9h14M4 15h14"/></svg>
              </span>
              <span>Use comma separators (e.g. 1,000,000)</span>
            </label>
            <label class="toggle-switch">
              <input type="checkbox" id="commas" checked>
              <span class="slider"></span>
            </label>
          </div>
        </div>

        <!-- Card 2: Canvas & Framing -->
        <div class="deck-card">
          <div class="card-header">
            <div class="card-title">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><rect x="2" y="3" width="20" height="14" rx="2" ry="2"/><line x1="8" y1="21" x2="16" y2="21"/><line x1="12" y1="17" x2="12" y2="21"/></svg>
              <span>Canvas &amp; Framing</span>
            </div>
            <span class="card-tag">Format</span>
          </div>

          <!-- Visual Aspect Ratio Pill Switcher -->
          <div class="pill-selector" id="aspectPills">
            <button type="button" class="pill-btn active" data-orientation="landscape">
              <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="6" width="20" height="12" rx="2"/></svg>
              <span>Landscape</span>
              <span class="pill-sub">16 : 9 (YouTube)</span>
            </button>
            <button type="button" class="pill-btn" data-orientation="portrait">
              <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="6" y="2" width="12" height="20" rx="2"/></svg>
              <span>Portrait</span>
              <span class="pill-sub">9 : 16 (Reels/Shorts)</span>
            </button>
            <button type="button" class="pill-btn" data-orientation="square">
              <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="4" y="4" width="16" height="16" rx="2"/></svg>
              <span>Square</span>
              <span class="pill-sub">1 : 1 (Feed/Posts)</span>
            </button>
          </div>

          <div class="field-grid">
            <div class="field">
              <label class="field-label" for="orientation">Orientation Mode</label>
              <select id="orientation">
                <option value="landscape" selected>Landscape (16:9)</option>
                <option value="portrait">Portrait (9:16)</option>
                <option value="square">Square (1:1)</option>
              </select>
            </div>
            <div class="field">
              <label class="field-label" for="resolution">Export Resolution</label>
              <select id="resolution">
                <option value="1920x1080">1080p (1920 × 1080)</option>
                <option value="1280x720">720p (1280 × 720)</option>
                <option value="640x360" selected>360p (640 × 360)</option>
              </select>
            </div>
          </div>
        </div>

        <!-- Card 3: Typography & Colors -->
        <div class="deck-card">
          <div class="card-header">
            <div class="card-title">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><polyline points="4 7 4 4 20 4 20 7"/><line x1="9" y1="20" x2="15" y2="20"/><line x1="12" y1="4" x2="12" y2="20"/></svg>
              <span>Typography &amp; Colors</span>
            </div>
            <span class="card-tag">Style</span>
          </div>

          <div class="field">
            <label class="field-label" for="font">Font Family</label>
            <select id="font">
              <?php foreach ($fonts as $name => $path): ?>
                <option value="<?= htmlspecialchars($name) ?>"<?= $name === $defaultFont ? ' selected' : '' ?>><?= htmlspecialchars($name) ?></option>
              <?php endforeach; ?>
            </select>
            <p class="hint">Includes installed system fonts, 35 curated creator typefaces, and Google Fonts. Drop custom <code>.ttf</code>/<code>.otf</code> files into <code>fonts/</code>.</p>
          </div>

          <div class="field-grid">
            <div class="field">
              <label class="field-label" for="fontSize">
                <span>Font Size</span>
                <span id="fontSizeVal" class="field-val-badge">70px</span>
              </label>
              <div class="slider-input-combo">
                <input type="range" id="fontSizeRange" min="20" max="400" value="70">
                <input type="number" id="fontSize" value="70" min="8" max="800">
              </div>
            </div>
            <div class="field">
              <label class="field-label" for="letterSpacing">
                <span>Letter Spacing</span>
                <span id="letterSpacingVal" class="field-val-badge">0px</span>
              </label>
              <div class="slider-input-combo">
                <input type="range" id="letterSpacingRange" min="-20" max="60" value="0">
                <input type="number" id="letterSpacing" value="0" min="-40" max="200" step="1">
              </div>
            </div>
          </div>

          <!-- Color Engine -->
          <div class="color-picker" id="colorPicker">
            <div class="picker-tabs" role="tablist" aria-label="Text color mode">
              <button type="button" class="picker-tab active" data-color-mode="solid">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor"><circle cx="12" cy="12" r="10"/></svg>
                <span>Solid Color</span>
              </button>
              <button type="button" class="picker-tab" data-color-mode="gradient">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2v20M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
                <span>Vibrant Gradient</span>
              </button>
            </div>

            <div class="color-mode" id="solidColors">
              <div class="field">
                <label class="field-label">Text Color</label>
                <div class="color-input-row">
                  <input type="color" id="textColor" value="#ffffff" aria-label="Text color picker">
                  <input type="text" id="textColorHex" value="#ffffff" maxlength="7" aria-label="Text color hex value">
                </div>
              </div>
              <div class="swatches" id="solidSwatches" aria-label="Solid text colors"></div>
            </div>

            <div class="color-mode" id="gradientColors" hidden>
              <div class="field">
                <label class="field-label">Gradient Color Stops (Left → Right)</label>
                <div class="gradient-inputs">
                  <input type="color" id="gradientStart" value="#ff2d55" aria-label="Gradient start color">
                  <input type="color" id="gradientEnd" value="#7c3aed" aria-label="Gradient end color">
                </div>
              </div>
              <div class="gradient-swatches" id="gradientSwatches" aria-label="Gradient presets"></div>
            </div>
          </div>

          <!-- Border / Stroke -->
          <div class="toggle-row">
            <label class="toggle-label" for="borderEnabled">
              <span class="toggle-icon">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><circle cx="12" cy="12" r="10"/><path d="M12 2a10 10 0 0 0 0 20"/></svg>
              </span>
              <span>Enable Border Outline</span>
            </label>
            <label class="toggle-switch">
              <input type="checkbox" id="borderEnabled" checked>
              <span class="slider"></span>
            </label>
          </div>

          <div class="field-grid" id="borderControls">
            <div class="field">
              <label class="field-label" for="borderColor">Border Color</label>
              <div class="color-input-row">
                <input type="color" id="borderColor" value="#000000">
              </div>
            </div>
            <div class="field">
              <label class="field-label" for="borderWidth">
                <span>Border Width</span>
                <span id="borderWidthVal" class="field-val-badge">4px</span>
              </label>
              <div class="slider-input-combo">
                <input type="range" id="borderWidthRange" min="0" max="30" value="4">
                <input type="number" id="borderWidth" value="4" min="0" max="40">
              </div>
            </div>
          </div>
        </div>

        <!-- Card 4: 3D Depth & Glow FX -->
        <div class="deck-card">
          <div class="card-header">
            <div class="card-title">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/><polyline points="3.27 6.96 12 12.01 20.73 6.96"/><line x1="12" y1="22.08" x2="12" y2="12"/></svg>
              <span>3D Depth &amp; Glow FX</span>
            </div>
            <span class="card-tag">VFX</span>
          </div>

          <!-- 3D Depth Section -->
          <div class="toggle-row">
            <label class="toggle-label" for="depthEnabled">
              <span class="toggle-icon">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M12 2l10 6.5v7L12 22 2 15.5v-7L12 2z"/></svg>
              </span>
              <span>Enable 3D Extrusion</span>
            </label>
            <label class="toggle-switch">
              <input type="checkbox" id="depthEnabled">
              <span class="slider"></span>
            </label>
          </div>

          <div class="field-grid">
            <div class="field">
              <label class="field-label" for="depth">
                <span>Depth Distance</span>
                <span id="depthVal" class="field-val-badge">8px</span>
              </label>
              <div class="slider-input-combo">
                <input type="range" id="depthRange" min="1" max="30" value="8">
                <input type="number" id="depth" value="8" min="1" max="40">
              </div>
            </div>
            <div class="field">
              <label class="field-label" for="depthAngle">Extrusion Angle</label>
              <select id="depthAngle">
                <option value="down-right" selected>↘ Down Right</option>
                <option value="down-left">↙ Down Left</option>
                <option value="up-right">↗ Up Right</option>
                <option value="up-left">↖ Up Left</option>
              </select>
            </div>
          </div>

          <div class="field">
            <label class="field-label" for="depthColor">3D Shadow Tone</label>
            <div class="color-input-row">
              <input type="color" id="depthColor" value="#111827">
            </div>
          </div>

          <hr style="border: 0; border-top: 1px solid var(--border); margin: 18px 0;">

          <!-- Glow Section -->
          <div class="toggle-row">
            <label class="toggle-label" for="glowEnabled">
              <span class="toggle-icon">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><circle cx="12" cy="12" r="5"/><line x1="12" y1="1" x2="12" y2="3"/><line x1="12" y1="21" x2="12" y2="23"/><line x1="4.22" y1="4.22" x2="5.64" y2="5.64"/><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"/><line x1="1" y1="12" x2="3" y2="12"/><line x1="21" y1="12" x2="23" y2="12"/><line x1="4.22" y1="19.78" x2="5.64" y2="18.36"/><line x1="18.36" y1="5.64" x2="19.78" y2="4.22"/></svg>
              </span>
              <span>Enable Neon Glow</span>
            </label>
            <label class="toggle-switch">
              <input type="checkbox" id="glowEnabled">
              <span class="slider"></span>
            </label>
          </div>

          <div class="field-grid">
            <div class="field">
              <label class="field-label" for="glowDistance">
                <span>Glow Spread</span>
                <span id="glowDistanceVal" class="field-val-badge">18px</span>
              </label>
              <div class="slider-input-combo">
                <input type="range" id="glowDistanceRange" min="2" max="50" value="18">
                <input type="number" id="glowDistance" value="18" min="1" max="50">
              </div>
            </div>
            <div class="field">
              <label class="field-label" for="glowStrength">
                <span>Intensity (%)</span>
                <span id="glowStrengthVal" class="field-val-badge">65%</span>
              </label>
              <div class="slider-input-combo">
                <input type="range" id="glowStrengthRange" min="10" max="100" value="65">
                <input type="number" id="glowStrength" value="65" min="1" max="100">
              </div>
            </div>
          </div>

          <div class="field">
            <label class="field-label" for="glowColor">Glow Color</label>
            <div class="color-input-row">
              <input type="color" id="glowColor" value="#38bdf8">
            </div>
          </div>
        </div>

        <!-- Card 5: Composition & Motion Curve -->
        <div class="deck-card">
          <div class="card-header">
            <div class="card-title">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"/></svg>
              <span>Composition &amp; Motion</span>
            </div>
            <span class="card-tag">Placement</span>
          </div>

          <div class="field-grid">
            <div class="field">
              <label class="field-label" for="prefix">Prefix</label>
              <input type="text" id="prefix" placeholder="e.g. $" maxlength="20">
              <div class="symbol-chips">
                <button type="button" class="chip-btn" data-target="prefix" data-char="$">$</button>
                <button type="button" class="chip-btn" data-target="prefix" data-char="+">+</button>
                <button type="button" class="chip-btn" data-target="prefix" data-char="€">€</button>
                <button type="button" class="chip-btn" data-target="prefix" data-char="#">#</button>
              </div>
            </div>
            <div class="field">
              <label class="field-label" for="suffix">Suffix</label>
              <input type="text" id="suffix" placeholder="e.g. +" maxlength="20">
              <div class="symbol-chips">
                <button type="button" class="chip-btn" data-target="suffix" data-char="+">+</button>
                <button type="button" class="chip-btn" data-target="suffix" data-char="%">%</button>
                <button type="button" class="chip-btn" data-target="suffix" data-char="K">K</button>
                <button type="button" class="chip-btn" data-target="suffix" data-char="M">M</button>
              </div>
            </div>
          </div>

          <div class="field-grid">
            <div class="field">
              <label class="field-label" for="align">Framing Alignment</label>
              <select id="align">
                <option value="top-left">Top Left</option>
                <option value="top-center">Top Center</option>
                <option value="top-right">Top Right</option>
                <option value="center-left">Center Left</option>
                <option value="center" selected>Center</option>
                <option value="center-right">Center Right</option>
                <option value="bottom-left">Bottom Left</option>
                <option value="bottom-center">Bottom Center</option>
                <option value="bottom-right">Bottom Right</option>
              </select>
              <!-- 3x3 Visual Matrix Companion -->
              <div class="align-matrix-wrap">
                <div class="align-grid" id="alignGrid">
                  <button type="button" class="align-btn" data-align="top-left" title="Top Left"><span class="align-dot"></span></button>
                  <button type="button" class="align-btn" data-align="top-center" title="Top Center"><span class="align-dot"></span></button>
                  <button type="button" class="align-btn" data-align="top-right" title="Top Right"><span class="align-dot"></span></button>
                  <button type="button" class="align-btn" data-align="center-left" title="Center Left"><span class="align-dot"></span></button>
                  <button type="button" class="align-btn active" data-align="center" title="Center"><span class="align-dot"></span></button>
                  <button type="button" class="align-btn" data-align="center-right" title="Center Right"><span class="align-dot"></span></button>
                  <button type="button" class="align-btn" data-align="bottom-left" title="Bottom Left"><span class="align-dot"></span></button>
                  <button type="button" class="align-btn" data-align="bottom-center" title="Bottom Center"><span class="align-dot"></span></button>
                  <button type="button" class="align-btn" data-align="bottom-right" title="Bottom Right"><span class="align-dot"></span></button>
                </div>
              </div>
            </div>

            <div class="field">
              <label class="field-label" for="easing">Animation Curve</label>
              <select id="easing">
                <option value="linear" selected>Linear (Constant Speed)</option>
                <option value="easeIn">Ease In (Accelerate)</option>
                <option value="easeOut">Ease Out (Decelerate Smoothly)</option>
                <option value="easeInOut">Ease In Out (Natural Motion)</option>
              </select>

              <div style="margin-top: 14px;">
                <div class="toggle-row">
                  <label class="toggle-label" for="autoCenter">
                    <span>Auto Center Position</span>
                  </label>
                  <label class="toggle-switch">
                    <input type="checkbox" id="autoCenter" checked>
                    <span class="slider"></span>
                  </label>
                </div>
              </div>
            </div>
          </div>

          <div class="field-grid" id="manualPos" hidden>
            <div class="field">
              <label class="field-label" for="posX">Manual X (px)</label>
              <input type="number" id="posX" value="100">
            </div>
            <div class="field">
              <label class="field-label" for="posY">Manual Y (px)</label>
              <input type="number" id="posY" value="100">
            </div>
          </div>
        </div>

        <!-- Action Buttons Deck -->
        <div class="actions">
          <button type="button" id="btnPreview" class="btn secondary">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><polygon points="5 3 19 12 5 21 5 3"/></svg>
            <span>Play Preview</span>
          </button>
          <button type="button" id="btnReset" class="btn ghost">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="1 4 1 10 7 10"/><path d="M3.51 15a9 9 0 1 0 2.13-9.36L1 10"/></svg>
            <span>Reset</span>
          </button>
          <button type="submit" id="btnExport" class="btn primary"<?= $ffmpegOk ? '' : ' disabled' ?>>
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
            <span>EXPORT TRANSPARENT MP4</span>
          </button>
        </div>
      </form>

      <!-- Right Column: Studio Monitor & Output Center -->
      <aside class="preview-panel">
        
        <!-- Studio Monitor Frame -->
        <div class="monitor-frame">
          <div class="monitor-topbar">
            <div class="monitor-title">
              <span class="rec-dot"></span>
              <span>Live Monitor</span>
            </div>
            <div class="monitor-controls">
              <!-- Background mode selector -->
              <button type="button" class="monitor-btn active" id="btnBgDark" title="Dark checkerboard background">Dark Alpha</button>
              <button type="button" class="monitor-btn" id="btnBgLight" title="Light checkerboard background">Light Alpha</button>
              <button type="button" class="monitor-btn" id="btnBgBlack" title="Solid black background">Black</button>
              <button type="button" class="monitor-btn" id="btnBgWhite" title="Solid white background">White</button>
              <!-- Safe guide toggle -->
              <button type="button" class="monitor-btn" id="btnToggleGuide" title="Toggle framing safe area grid">Grid</button>
            </div>
          </div>

          <!-- Stage Wrap -->
          <div id="stageWrap" class="stage-wrap checker-dark">
            <div id="stage" class="stage">
              <div id="stageGuide" class="stage-guide-overlay">
                <div class="crosshair-h"></div>
                <div class="crosshair-v"></div>
              </div>
              <div id="previewNum" class="preview-num">1</div>
            </div>
          </div>

          <div class="monitor-footer">
            <span class="meta-badge" id="previewMeta">1920 × 1080 · 24 FPS · 5s</span>
            <span class="meta-badge" style="color: var(--accent-cyan);">PNG / RGBA Alpha</span>
          </div>
        </div>

        <!-- Export Progress Card -->
        <div id="exportBox" class="export-card" hidden>
          <div class="export-header">
            <div class="export-status-wrap">
              <div class="export-spinner" id="exportSpinner"></div>
              <span id="exportStatus" class="status">Encoding Video…</span>
            </div>
            <span id="exportPct" class="export-pct-badge">0%</span>
          </div>
          
          <div class="bar">
            <div id="barFill" class="bar-fill"></div>
          </div>

          <div class="export-details">
            <span id="exportFrame">Frame: 0 / 0</span>
            <span>Ultra Fast Multi-Thread</span>
          </div>

          <div class="btn-download-wrap">
            <a id="btnDownload" class="btn primary" href="#" hidden download>
              <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
              <span>Download Transparent MP4</span>
            </a>
          </div>
        </div>

        <!-- Pro Creator Tips Accordion -->
        <details class="help-card">
          <summary>
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
            <span>Transparency &amp; Editor Import Guide</span>
          </summary>
          <ul>
            <li><strong>Transparent MP4 Export:</strong> Encoded using the <strong>PNG codec with 32-bit RGBA</strong> in an MP4 container. The background has 0% opacity.</li>
            <li><strong>CapCut &amp; DaVinci Resolve:</strong> Drop the exported <code>.mp4</code> directly onto an upper video track over your footage. The transparent alpha displays instantly without chroma keying.</li>
            <li><strong>Adobe Premiere Pro:</strong> Drag to your sequence timeline. If alpha isn't detected by default, right-click the clip &rarr; <em>Modify</em> &rarr; <em>Interpret Footage</em> &rarr; ensure <em>Alpha Channel</em> is set to Premultiplied or Straight.</li>
            <li><strong>Adding Custom Fonts:</strong> Drop any <code>.ttf</code> or <code>.otf</code> into the project's <code>fonts/</code> folder to instantly make them available in the typography selector.</li>
          </ul>
        </details>
      </aside>
    </div>
  </div>

  <script>
    window.WEB_FONTS = <?= json_encode(array_keys($webFonts)) ?>;
    window.RX_PAGE_NAME = 'number-counter';
  </script>
  <script src="script.js"></script>
  <script src="tracker.js" async></script>
</body>
</html>
