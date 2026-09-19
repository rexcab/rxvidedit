(() => {
  const $ = (id) => document.getElementById(id);

  const RES = {
    landscape: [
      { v: '1920x1080', t: '1080p (1920 × 1080)' },
      { v: '1280x720', t: '720p (1280 × 720)' },
      { v: '640x360', t: '360p (640 × 360)' },
    ],
    portrait: [
      { v: '1080x1920', t: '1080p (1080 × 1920)' },
      { v: '720x1280', t: '720p (720 × 1280)' },
      { v: '360x640', t: '360p (360 × 640)' },
    ],
    square: [
      { v: '1080x1080', t: '1080p (1080 × 1080)' },
      { v: '720x720', t: '720p (720 × 720)' },
      { v: '360x360', t: '360p (360 × 360)' },
    ],
  };

  const fontMap = {
    Arial: 'Arial, sans-serif',
    'Arial Bold': 'Arial, sans-serif',
    'Arial Black': '"Arial Black", Arial, sans-serif',
    Calibri: 'Calibri, sans-serif',
    'Calibri Bold': 'Calibri, sans-serif',
    'Segoe UI': '"Segoe UI", sans-serif',
    'Segoe UI Bold': '"Segoe UI", sans-serif',
    Tahoma: 'Tahoma, sans-serif',
    'Tahoma Bold': 'Tahoma, sans-serif',
    Verdana: 'Verdana, sans-serif',
    'Verdana Bold': 'Verdana, sans-serif',
    Georgia: 'Georgia, serif',
    'Georgia Bold': 'Georgia, serif',
    'Times New Roman': '"Times New Roman", serif',
    Impact: 'Impact, sans-serif',
    'Comic Sans MS': '"Comic Sans MS", sans-serif',
    Consolas: 'Consolas, monospace',
  };

  const webFonts = new Set(window.WEB_FONTS || []);
  
  // Expanded modern creator color palettes
  const solidColors = [
    '#ffffff', '#f8fafc', '#000000', '#ef4444', '#f97316', 
    '#facc15', '#10b981', '#06b6d4', '#3b82f6', '#6366f1', 
    '#a855f7', '#ec4899', '#f43f5e', '#14b8a6', '#fbbf24'
  ];

  const gradientColors = [
    ['#ff2d55', '#7c3aed'], // Cyberpunk Neon
    ['#ef4444', '#f97316'], // Flare Red-Orange
    ['#facc15', '#22c55e'], // Lime Gold
    ['#06b6d4', '#3b82f6'], // Electric Cyan-Blue
    ['#8b5cf6', '#ec4899'], // Violet Neon Pink
    ['#10b981', '#06b6d4'], // Aurora Emerald
    ['#fbbf24', '#d97706'], // Luxury Gold
    ['#f43f5e', '#fb7185'], // Rose Glow
    ['#ffffff', '#94a3b8'], // Platinum Chrome
    ['#1e293b', '#0f172a'], // Stealth Dark
  ];

  function cssFontFamily(name) {
    if (webFonts.has(name)) {
      return JSON.stringify(name) + ', sans-serif';
    }
    return fontMap[name] || JSON.stringify(name) + ', sans-serif';
  }

  let previewTimer = null;
  let pollTimer = null;
  let exporting = false;
  const END_HOLD_SECONDS = 2;

  function parseNum(str) {
    const n = Number(String(str).trim().replace(/,/g, ''));
    return Number.isFinite(n) ? n : NaN;
  }

  function hexToRgb(hex) {
    const value = String(hex).replace('#', '');
    const full = value.length === 3 ? value.split('').map((part) => part + part).join('') : value;
    return [parseInt(full.slice(0, 2), 16), parseInt(full.slice(2, 4), 16), parseInt(full.slice(4, 6), 16)];
  }

  function decimalsOf(a, b) {
    const d = (s) => {
      const t = String(s).trim().replace(/,/g, '');
      if (!t.includes('.')) return 0;
      return (t.split('.')[1] || '').replace(/0+$/, '').length || (t.split('.')[1] || '').length;
    };
    return Math.min(6, Math.max(d(a), d(b)));
  }

  function formatNum(value, decimals, commas) {
    if (decimals > 0) {
      const fixed = value.toFixed(decimals);
      if (!commas) return fixed;
      const [i, f] = fixed.split('.');
      return Number(i).toLocaleString('en-US') + '.' + f;
    }
    const n = Math.round(value);
    return commas ? n.toLocaleString('en-US') : String(n);
  }

  function ease(t, type) {
    t = Math.max(0, Math.min(1, t));
    switch (type) {
      case 'easeIn': return t * t;
      case 'easeOut': return t * (2 - t);
      case 'easeInOut': return t < 0.5 ? 2 * t * t : -1 + (4 - 2 * t) * t;
      default: return t;
    }
  }

  function syncResolutionOptions() {
    const ori = $('orientation').value;
    const sel = $('resolution');
    const list = RES[ori] || RES.landscape;
    const prevVal = sel.value;
    sel.innerHTML = list.map((o) => `<option value="${o.v}">${o.t}</option>`).join('');
    // Keep selection if exists, else pick default
    const exists = list.some((o) => o.v === prevVal);
    sel.value = exists ? prevVal : list[list.length - 1].v;

    // Sync Aspect Ratio Pills
    document.querySelectorAll('#aspectPills .pill-btn').forEach((btn) => {
      btn.classList.toggle('active', btn.dataset.orientation === ori);
    });

    updateStage();
  }

  function getSize() {
    const [w, h] = $('resolution').value.split('x').map(Number);
    return { width: w, height: h };
  }

  function updateStage() {
    const { width, height } = getSize();
    $('stage').style.aspectRatio = `${width} / ${height}`;
    applyPreviewStyle();
    $('previewMeta').textContent =
      `${width} × ${height} · ${$('fps').value} FPS · ${$('duration').value}s`;
  }

  function applyPreviewStyle() {
    const el = $('previewNum');
    const size = Number($('fontSize').value) || 70;
    const stage = $('stage');
    const { width } = getSize();
    const scale = stage.clientWidth / width;
    const fontName = $('font').value;
    const cssFont = cssFontFamily(fontName);
    const isWebFont = webFonts.has(fontName);
    const bold = !isWebFont && /bold|black/i.test(fontName) ? '700' : '400';
    const italic = !isWebFont && /italic/i.test(fontName) ? 'italic' : 'normal';
    const letterSpacing = Number($('letterSpacing').value) || 0;

    el.style.fontFamily = cssFont;
    el.style.fontWeight = bold;
    el.style.fontStyle = italic;
    el.style.fontSize = Math.max(10, size * scale) + 'px';
    el.style.letterSpacing = (letterSpacing * scale) + 'px';
    
    const gradientOn = document.querySelector('[data-color-mode="gradient"].active');
    if (gradientOn) {
      el.style.color = 'transparent';
      el.style.background = `linear-gradient(90deg, ${$('gradientStart').value}, ${$('gradientEnd').value})`;
      el.style.backgroundClip = 'text';
      el.style.webkitBackgroundClip = 'text';
    } else {
      el.style.color = $('textColor').value;
      el.style.background = 'none';
      el.style.backgroundClip = 'border-box';
      el.style.webkitBackgroundClip = 'border-box';
    }

    const depth = $('depthEnabled').checked ? Math.max(1, Number($('depth').value) || 1) : 0;
    const depthSigns = {
      'down-right': [1, 1], 'down-left': [-1, 1],
      'up-right': [1, -1], 'up-left': [-1, -1],
    };
    const [dx, dy] = depthSigns[$('depthAngle').value] || depthSigns['down-right'];
    const shadows = [];
    for (let i = depth; i > 0; i--) {
      shadows.push(`${dx * i * scale}px ${dy * i * scale}px 0 ${$('depthColor').value}`);
    }

    if ($('glowEnabled').checked) {
      const glowDistance = Math.max(1, Number($('glowDistance').value) || 1) * scale;
      const glowStrength = Math.max(1, Math.min(100, Number($('glowStrength').value) || 1)) / 100;
      shadows.push(`0 0 ${glowDistance}px rgba(${hexToRgb($('glowColor').value)}, ${glowStrength})`);
    }
    el.style.textShadow = shadows.join(', ');

    if ($('borderEnabled').checked && Number($('borderWidth').value) > 0) {
      const bw = Math.max(1, Number($('borderWidth').value) * scale);
      el.style.webkitTextStroke = `${bw}px ${$('borderColor').value}`;
      el.style.paintOrder = 'stroke fill';
    } else {
      el.style.webkitTextStroke = '0';
    }

    const auto = $('autoCenter').checked;
    $('manualPos').hidden = auto;
    el.style.top = 'auto';
    el.style.left = 'auto';
    el.style.right = 'auto';
    el.style.bottom = 'auto';

    if (auto) {
      const pad = '12px';
      const align = $('align').value;
      const rules = {
        'top-left':      { top: pad, left: pad, transform: 'none' },
        'top-center':    { top: pad, left: '50%', transform: 'translateX(-50%)' },
        'top-right':     { top: pad, right: pad, transform: 'none' },
        'center-left':   { top: '50%', left: pad, transform: 'translateY(-50%)' },
        center:          { top: '50%', left: '50%', transform: 'translate(-50%, -50%)' },
        'center-right':  { top: '50%', right: pad, transform: 'translateY(-50%)' },
        'bottom-left':   { bottom: pad, left: pad, transform: 'none' },
        'bottom-center': { bottom: pad, left: '50%', transform: 'translateX(-50%)' },
        'bottom-right':  { bottom: pad, right: pad, transform: 'none' },
      };
      const r = rules[align] || rules.center;
      Object.assign(el.style, {
        top: r.top || 'auto',
        left: r.left || 'auto',
        right: r.right || 'auto',
        bottom: r.bottom || 'auto',
        transform: r.transform,
      });

      // Sync 3x3 alignment grid buttons
      document.querySelectorAll('#alignGrid .align-btn').forEach((btn) => {
        btn.classList.toggle('active', btn.dataset.align === align);
      });
    } else {
      const x = Number($('posX').value) || 0;
      const y = Number($('posY').value) || 0;
      el.style.transform = 'none';
      el.style.left = (x * scale) + 'px';
      el.style.top = (y * scale) + 'px';
    }

    const start = $('start').value;
    const end = $('end').value;
    const dec = decimalsOf(start, end);
    const n = parseNum(start);
    if (Number.isFinite(n)) {
      el.textContent = ($('prefix').value || '') + formatNum(n, dec, $('commas').checked) + ($('suffix').value || '');
    }

    // Update companion value badges if present
    syncValueBadges();
  }

  function syncValueBadges() {
    if ($('durationVal')) $('durationVal').textContent = `${$('duration').value}s`;
    if ($('fontSizeVal')) $('fontSizeVal').textContent = `${$('fontSize').value}px`;
    if ($('letterSpacingVal')) $('letterSpacingVal').textContent = `${$('letterSpacing').value}px`;
    if ($('borderWidthVal')) $('borderWidthVal').textContent = `${$('borderWidth').value}px`;
    if ($('depthVal')) $('depthVal').textContent = `${$('depth').value}px`;
    if ($('glowDistanceVal')) $('glowDistanceVal').textContent = `${$('glowDistance').value}px`;
    if ($('glowStrengthVal')) $('glowStrengthVal').textContent = `${$('glowStrength').value}%`;
  }

  function stopPreview() {
    if (previewTimer) {
      cancelAnimationFrame(previewTimer);
      previewTimer = null;
    }
  }

  function playPreview() {
    stopPreview();
    const start = parseNum($('start').value);
    const end = parseNum($('end').value);
    const duration = Number($('duration').value);
    if (!Number.isFinite(start) || !Number.isFinite(end)) {
      alert('Please enter valid numbers.');
      return;
    }
    if (!(duration > 0)) {
      alert('Duration must be greater than 0.');
      return;
    }
    const fps = Number($('fps').value) || 24;
    const countDuration = duration;
    const total = Math.max(1, Math.round(countDuration * fps));
    const totalDuration = countDuration + END_HOLD_SECONDS;
    const dec = decimalsOf($('start').value, $('end').value);
    const easing = $('easing').value;
    const t0 = performance.now();
    const { width, height } = getSize();

    function frame(now) {
      const elapsed = (now - t0) / 1000;
      const p = Math.min(1, elapsed / countDuration);
      const idx = Math.min(total - 1, Math.floor(p * (total - 1)));
      const prog = elapsed < countDuration && total > 1 ? idx / (total - 1) : 1;
      const v = start + (end - start) * ease(prog, easing);
      $('previewNum').textContent =
        ($('prefix').value || '') + formatNum(v, dec, $('commas').checked) + ($('suffix').value || '');
      $('previewMeta').textContent = `Preview ${Math.min(elapsed, totalDuration).toFixed(1)}s / ${totalDuration.toFixed(1)}s · ${width} × ${height} · ${fps} FPS`;
      if (elapsed < totalDuration) {
        previewTimer = requestAnimationFrame(frame);
      } else {
        previewTimer = null;
        updateStage();
      }
    }
    previewTimer = requestAnimationFrame(frame);
  }

  function collectSettings(jobId) {
    const { width, height } = getSize();
    return {
      jobId,
      start: String($('start').value).trim().replace(/,/g, ''),
      end: String($('end').value).trim().replace(/,/g, ''),
      duration: Number($('duration').value),
      fps: Number($('fps').value),
      width,
      height,
      font: $('font').value,
      fontSize: Number($('fontSize').value),
      letterSpacing: Number($('letterSpacing').value) || 0,
      textColor: $('textColor').value,
      colorMode: document.querySelector('[data-color-mode="gradient"].active') ? 'gradient' : 'solid',
      gradientStart: $('gradientStart').value,
      gradientEnd: $('gradientEnd').value,
      borderEnabled: $('borderEnabled').checked,
      borderColor: $('borderColor').value,
      borderWidth: Number($('borderWidth').value),
      depthEnabled: $('depthEnabled').checked,
      depth: Number($('depth').value) || 0,
      depthAngle: $('depthAngle').value,
      depthColor: $('depthColor').value,
      glowEnabled: $('glowEnabled').checked,
      glowDistance: Number($('glowDistance').value) || 0,
      glowStrength: Number($('glowStrength').value) || 0,
      glowColor: $('glowColor').value,
      prefix: $('prefix').value,
      suffix: $('suffix').value,
      commas: $('commas').checked,
      easing: $('easing').value,
      align: $('align').value,
      autoCenter: $('autoCenter').checked,
      posX: Number($('posX').value) || 0,
      posY: Number($('posY').value) || 0,
    };
  }

  function setDownloadState(enabled, href = '#') {
    const link = $('btnDownload');
    link.hidden = !enabled;
    link.href = enabled ? href : '#';
    link.tabIndex = enabled ? 0 : -1;
    link.classList.toggle('disabled', !enabled);
    link.setAttribute('aria-disabled', String(!enabled));
    link.dataset.enabled = String(enabled);
  }

  function setProgressUI(p) {
    $('exportBox').hidden = false;
    const pct = Math.max(0, Math.min(100, p.percent || 0));
    $('barFill').style.width = pct + '%';
    $('exportPct').textContent = pct + '%';
    if (p.totalFrames) {
      $('exportFrame').textContent = `Frame: ${p.frame || 0} / ${p.totalFrames}`;
    }
    const spinner = $('exportSpinner');
    if (p.status === 'done') {
      if (spinner) spinner.style.display = 'none';
      $('exportStatus').textContent = 'Export Completed! (100%)';
      setDownloadState(true, p.download || '#');
    } else if (p.status === 'error') {
      if (spinner) spinner.style.display = 'none';
      setDownloadState(false);
      $('exportStatus').textContent = p.error || p.message || 'Export failed.';
    } else {
      if (spinner) spinner.style.display = 'block';
      setDownloadState(false);
      $('exportStatus').textContent = `Encoding MP4… ${pct}%`;
    }
  }

  async function exportMp4(e) {
    e.preventDefault();
    if (exporting) return;

    const start = parseNum($('start').value);
    const end = parseNum($('end').value);
    const duration = Number($('duration').value);
    if (!Number.isFinite(start) || !Number.isFinite(end)) {
      alert('Please enter valid numbers.');
      return;
    }
    if (!(duration > 0)) {
      alert('Duration must be greater than 0.');
      return;
    }

    exporting = true;
    $('btnExport').disabled = true;
    setDownloadState(false);
    setProgressUI({
      status: 'rendering',
      percent: 0,
      frame: 0,
      totalFrames: Math.round(duration * Number($('fps').value)) + (Number($('fps').value) * END_HOLD_SECONDS)
    });

    const jobId = 'job_' + Date.now().toString(36) + '_' + Math.random().toString(36).slice(2, 8);
    const settings = collectSettings(jobId);

    if (pollTimer) clearInterval(pollTimer);
    pollTimer = setInterval(async () => {
      try {
        const r = await fetch('progress.php?job=' + encodeURIComponent(jobId) + '&_=' + Date.now());
        const p = await r.json();
        setProgressUI(p);
        if (p.status === 'done' || p.status === 'error') {
          clearInterval(pollTimer);
          pollTimer = null;
        }
      } catch (_) { /* ignore poll errors */ }
    }, 400);

    try {
      const res = await fetch('export.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(settings),
      });
      const data = await res.json();
      if (!data.ok) {
        setProgressUI({ status: 'error', percent: 0, error: data.error || 'Export failed.' });
      } else {
        setProgressUI({
          status: 'done',
          percent: 100,
          frame: data.totalFrames,
          totalFrames: data.totalFrames,
          download: data.download,
        });
      }
    } catch (err) {
      setProgressUI({ status: 'error', percent: 0, error: 'Export failed. Check the FFmpeg configuration.' });
    } finally {
      if (pollTimer) {
        clearInterval(pollTimer);
        pollTimer = null;
      }
      exporting = false;
      $('btnExport').disabled = false;
    }
  }

  function resetForm() {
    stopPreview();
    $('form').reset();
    $('fps').value = '24';
    $('orientation').value = 'landscape';
    $('borderEnabled').checked = true;
    $('commas').checked = true;
    $('autoCenter').checked = true;
    $('align').value = 'center';
    $('easing').value = 'linear';
    $('fontSize').value = '70';
    if ($('fontSizeRange')) $('fontSizeRange').value = '70';
    $('letterSpacing').value = '0';
    if ($('letterSpacingRange')) $('letterSpacingRange').value = '0';
    $('start').value = '1';
    $('end').value = '1000';
    $('duration').value = '5';
    if ($('durationRange')) $('durationRange').value = '5';
    $('borderWidth').value = '4';
    if ($('borderWidthRange')) $('borderWidthRange').value = '4';
    $('textColor').value = '#ffffff';
    $('textColorHex').value = '#ffffff';
    $('gradientStart').value = '#ff2d55';
    $('gradientEnd').value = '#7c3aed';
    $('borderColor').value = '#000000';
    $('depthEnabled').checked = false;
    $('depth').value = '8';
    if ($('depthRange')) $('depthRange').value = '8';
    $('depthAngle').value = 'down-right';
    $('depthColor').value = '#111827';
    $('glowEnabled').checked = false;
    $('glowDistance').value = '18';
    if ($('glowDistanceRange')) $('glowDistanceRange').value = '18';
    $('glowStrength').value = '65';
    if ($('glowStrengthRange')) $('glowStrengthRange').value = '65';
    $('glowColor').value = '#38bdf8';
    $('prefix').value = '';
    $('suffix').value = '';
    $('exportBox').hidden = true;
    setDownloadState(false);
    setColorMode('solid');
    syncResolutionOptions();
  }

  // Color Mode Tabs
  function setColorMode(mode) {
    document.querySelectorAll('[data-color-mode]').forEach((tab) => tab.classList.toggle('active', tab.dataset.colorMode === mode));
    $('solidColors').hidden = mode !== 'solid';
    $('gradientColors').hidden = mode !== 'gradient';
    applyPreviewStyle();
  }

  // Build Swatches
  solidColors.forEach((color) => {
    const swatch = document.createElement('button');
    swatch.type = 'button';
    swatch.className = 'swatch';
    swatch.style.background = color;
    swatch.title = color;
    swatch.addEventListener('click', () => {
      $('textColor').value = color;
      $('textColorHex').value = color;
      setColorMode('solid');
    });
    $('solidSwatches').appendChild(swatch);
  });

  gradientColors.forEach(([start, end]) => {
    const swatch = document.createElement('button');
    swatch.type = 'button';
    swatch.className = 'gradient-swatch';
    swatch.style.background = `linear-gradient(90deg, ${start}, ${end})`;
    swatch.title = `${start} → ${end}`;
    swatch.addEventListener('click', () => {
      $('gradientStart').value = start;
      $('gradientEnd').value = end;
      setColorMode('gradient');
    });
    $('gradientSwatches').appendChild(swatch);
  });

  // Range Slider & Numeric Input Bidirectional Sync Helper
  function bindSliderInput(rangeId, numId) {
    const range = $(rangeId);
    const num = $(numId);
    if (!range || !num) return;
    range.addEventListener('input', () => {
      num.value = range.value;
      applyPreviewStyle();
    });
    num.addEventListener('input', () => {
      range.value = num.value;
      applyPreviewStyle();
    });
  }

  bindSliderInput('durationRange', 'duration');
  bindSliderInput('fontSizeRange', 'fontSize');
  bindSliderInput('letterSpacingRange', 'letterSpacing');
  bindSliderInput('borderWidthRange', 'borderWidth');
  bindSliderInput('depthRange', 'depth');
  bindSliderInput('glowDistanceRange', 'glowDistance');
  bindSliderInput('glowStrengthRange', 'glowStrength');

  // Aspect Ratio Pill Selector Sync
  document.querySelectorAll('#aspectPills .pill-btn').forEach((btn) => {
    btn.addEventListener('click', () => {
      const ori = btn.dataset.orientation;
      $('orientation').value = ori;
      syncResolutionOptions();
    });
  });

  // 3x3 Alignment Matrix Sync
  document.querySelectorAll('#alignGrid .align-btn').forEach((btn) => {
    btn.addEventListener('click', () => {
      $('align').value = btn.dataset.align;
      applyPreviewStyle();
    });
  });

  // Quick Presets
  document.querySelectorAll('.preset-chip').forEach((chip) => {
    chip.addEventListener('click', () => {
      const p = chip.dataset.preset;
      if (p === 'money') {
        $('start').value = '0';
        $('end').value = '1000';
        $('prefix').value = '$';
        $('suffix').value = '';
        $('commas').checked = true;
        $('duration').value = '5';
        if ($('durationRange')) $('durationRange').value = '5';
        $('easing').value = 'easeOut';
      } else if (p === 'countdown') {
        $('start').value = '10';
        $('end').value = '0';
        $('prefix').value = '';
        $('suffix').value = '';
        $('commas').checked = false;
        $('duration').value = '10';
        if ($('durationRange')) $('durationRange').value = '10';
        $('easing').value = 'linear';
      } else if (p === 'subs') {
        $('start').value = '0';
        $('end').value = '100000';
        $('prefix').value = '';
        $('suffix').value = ' Subs';
        $('commas').checked = true;
        $('duration').value = '8';
        if ($('durationRange')) $('durationRange').value = '8';
        $('easing').value = 'easeOut';
      } else if (p === 'percent') {
        $('start').value = '0';
        $('end').value = '100';
        $('prefix').value = '';
        $('suffix').value = '%';
        $('commas').checked = false;
        $('duration').value = '4';
        if ($('durationRange')) $('durationRange').value = '4';
        $('easing').value = 'easeInOut';
      }
      applyPreviewStyle();
    });
  });

  // Quick Symbol Chips
  document.querySelectorAll('.chip-btn').forEach((btn) => {
    btn.addEventListener('click', () => {
      const target = $(btn.dataset.target);
      if (target) {
        target.value = btn.dataset.char;
        applyPreviewStyle();
      }
    });
  });

  // Stage Viewport Background Mode Switcher
  const stageWrap = $('stageWrap');
  const bgBtns = [
    { btn: $('btnBgDark'), cls: 'checker-dark' },
    { btn: $('btnBgLight'), cls: 'checker-light' },
    { btn: $('btnBgBlack'), cls: 'bg-black' },
    { btn: $('btnBgWhite'), cls: 'bg-white' },
  ];

  bgBtns.forEach(({ btn, cls }) => {
    if (!btn) return;
    btn.addEventListener('click', () => {
      bgBtns.forEach((b) => {
        if (b.btn) b.btn.classList.remove('active');
        if (stageWrap) stageWrap.classList.remove(b.cls);
      });
      btn.classList.add('active');
      if (stageWrap) stageWrap.classList.add(cls);
    });
  });

  // Safe-Zone Guide Toggle
  const btnToggleGuide = $('btnToggleGuide');
  const stageGuide = $('stageGuide');
  if (btnToggleGuide && stageGuide) {
    btnToggleGuide.addEventListener('click', () => {
      stageGuide.classList.toggle('visible');
      btnToggleGuide.classList.toggle('active');
    });
  }

  // Core Events
  document.querySelectorAll('[data-color-mode]').forEach((tab) => {
    tab.addEventListener('click', () => setColorMode(tab.dataset.colorMode));
  });

  $('textColor').addEventListener('input', () => {
    $('textColorHex').value = $('textColor').value;
    applyPreviewStyle();
  });

  $('textColorHex').addEventListener('change', () => {
    if (/^#[0-9a-f]{6}$/i.test($('textColorHex').value)) {
      $('textColor').value = $('textColorHex').value;
    }
    applyPreviewStyle();
  });

  $('gradientStart').addEventListener('input', applyPreviewStyle);
  $('gradientEnd').addEventListener('input', applyPreviewStyle);
  $('orientation').addEventListener('change', syncResolutionOptions);
  $('resolution').addEventListener('change', updateStage);

  [
    'font', 'fontSize', 'letterSpacing', 'textColor', 'borderColor', 'borderWidth',
    'borderEnabled', 'depthEnabled', 'depth', 'depthAngle', 'depthColor',
    'glowEnabled', 'glowDistance', 'glowStrength', 'glowColor',
    'prefix', 'suffix', 'commas', 'align', 'autoCenter', 'posX', 'posY',
    'start', 'end', 'fps', 'duration'
  ].forEach((id) => {
    const el = $(id);
    if (el) el.addEventListener('input', applyPreviewStyle);
  });

  $('autoCenter').addEventListener('change', applyPreviewStyle);
  $('borderEnabled').addEventListener('change', applyPreviewStyle);

  $('btnPreview').addEventListener('click', playPreview);
  $('btnReset').addEventListener('click', resetForm);
  $('btnDownload').addEventListener('click', (e) => {
    if ($('btnDownload').dataset.enabled !== 'true') e.preventDefault();
  });
  $('form').addEventListener('submit', exportMp4);
  window.addEventListener('resize', applyPreviewStyle);

  // Initial Sync
  syncResolutionOptions();
})();
