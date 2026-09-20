<?php
/**
 * rxwithcode Admin Analytics Dashboard
 * Monitor website traffic: Daily, Monthly, and All-Time stats.
 */

session_start();
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/analytics_db.php';

// Handle Logout
if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    unset($_SESSION['rx_admin_logged_in']);
    session_destroy();
    header('Location: admin.php');
    exit;
}

// Handle Export CSV
if (isset($_GET['action']) && $_GET['action'] === 'export_csv') {
    if (empty($_SESSION['rx_admin_logged_in'])) {
        http_response_code(403);
        die('Unauthorized');
    }
    
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="rxwithcode_analytics_' . date('Y-m-d') . '.csv"');
    $out = fopen('php://output', 'w');
    fputcsv($out, ['ID', 'Page', 'Device', 'Referrer', 'Date', 'Month', 'Timestamp']);
    
    $pdo = AnalyticsDB::getPDO();
    $stmt = $pdo->query("SELECT id, page, device, referrer, visit_date, visit_month, created_at FROM page_views ORDER BY id DESC LIMIT 5000");
    while ($row = $stmt->fetch(PDO::FETCH_NUM)) {
        fputcsv($out, $row);
    }
    fclose($out);
    exit;
}

// Handle Purge Storage Action
if (isset($_GET['action']) && $_GET['action'] === 'purge_storage') {
    if (empty($_SESSION['rx_admin_logged_in'])) {
        http_response_code(403);
        die('Unauthorized');
    }
    $deleted = purgeStorageFiles();
    header('Location: admin.php?purged=' . $deleted);
    exit;
}

// Handle Login
$loginError = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    $user = trim($_POST['username'] ?? '');
    $pass = trim($_POST['password'] ?? '');

    // Rate limiting
    $attempts = $_SESSION['login_attempts'] ?? 0;
    $lockoutTime = $_SESSION['lockout_until'] ?? 0;

    if ($lockoutTime > time()) {
        $minutesLeft = ceil(($lockoutTime - time()) / 60);
        $loginError = "Too many failed attempts. Locked out for {$minutesLeft} more minute(s).";
    } else {
        if (hash_equals($adminUsername, $user) && hash_equals($adminPassword, $pass)) {
            $_SESSION['rx_admin_logged_in'] = true;
            $_SESSION['login_attempts'] = 0;
            unset($_SESSION['lockout_until']);
            header('Location: admin.php');
            exit;
        } else {
            $attempts++;
            $_SESSION['login_attempts'] = $attempts;
            if ($attempts >= 5) {
                $_SESSION['lockout_until'] = time() + (15 * 60); // 15 mins
                $loginError = "Too many failed attempts. Account locked for 15 minutes.";
            } else {
                $rem = 5 - $attempts;
                $loginError = "Invalid username or password. ({$rem} attempts remaining)";
            }
        }
    }
}

$isLoggedIn = !empty($_SESSION['rx_admin_logged_in']);
if ($isLoggedIn) {
    $stats = AnalyticsDB::getStats();
    $outputStorage = getDirSize($outputDir);
    $tempStorage = getDirSize($tempDir);
    $totalStorageMB = round($outputStorage['mb'] + $tempStorage['mb'], 2);
    $totalFiles = $outputStorage['count'] + $tempStorage['count'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Analytics Dashboard • rxwithcode</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;600&display=swap" rel="stylesheet">
  <style>
    :root {
      --bg: #090e17;
      --panel: #111927;
      --panel-border: rgba(255, 255, 255, 0.08);
      --card-bg: #162033;
      --text: #f0f4f8;
      --text-muted: #8fa0b5;
      --primary: #3b82f6;
      --primary-glow: rgba(59, 130, 246, 0.25);
      --success: #10b981;
      --warning: #f59e0b;
      --accent: #8b5cf6;
      --radius: 14px;
    }

    * { box-sizing: border-box; margin: 0; padding: 0; }
    body {
      background-color: var(--bg);
      color: var(--text);
      font-family: 'Plus Jakarta Sans', sans-serif;
      min-height: 100vh;
      line-height: 1.5;
    }

    a { color: var(--primary); text-decoration: none; }
    a:hover { text-decoration: underline; }

    /* Login Screen */
    .login-wrapper {
      display: flex;
      align-items: center;
      justify-content: center;
      min-height: 100vh;
      padding: 24px;
      background: radial-gradient(circle at top, #1e293b 0%, #090e17 70%);
    }

    .login-card {
      background: var(--panel);
      border: 1px solid var(--panel-border);
      border-radius: var(--radius);
      padding: 40px 32px;
      width: 100%;
      max-width: 400px;
      box-shadow: 0 20px 40px rgba(0,0,0,0.5);
    }

    .login-brand {
      display: flex;
      align-items: center;
      gap: 12px;
      margin-bottom: 24px;
    }

    .brand-mark {
      width: 36px;
      height: 36px;
      border-radius: 10px;
      background: linear-gradient(135deg, #3b82f6, #06b6d4);
      display: flex;
      align-items: center;
      justify-content: center;
      font-weight: 800;
      color: #fff;
    }

    .login-title { font-size: 1.4rem; font-weight: 700; }
    .login-sub { color: var(--text-muted); font-size: 0.88rem; margin-bottom: 24px; }

    .form-group { margin-bottom: 18px; }
    .form-group label { display: block; font-size: 0.84rem; font-weight: 600; margin-bottom: 6px; color: var(--text-muted); }
    .form-control {
      width: 100%;
      padding: 12px 14px;
      background: #0d131f;
      border: 1px solid var(--panel-border);
      border-radius: 8px;
      color: #fff;
      font-size: 0.95rem;
      outline: none;
      transition: border-color 0.2s;
    }
    .form-control:focus { border-color: var(--primary); box-shadow: 0 0 0 3px var(--primary-glow); }

    .btn-login {
      width: 100%;
      padding: 12px;
      background: var(--primary);
      border: none;
      border-radius: 8px;
      color: #fff;
      font-weight: 700;
      font-size: 0.95rem;
      cursor: pointer;
      margin-top: 10px;
      transition: opacity 0.2s;
    }
    .btn-login:hover { opacity: 0.9; }

    .alert-error {
      background: rgba(239, 68, 68, 0.15);
      border: 1px solid rgba(239, 68, 68, 0.4);
      color: #fca5a5;
      padding: 12px;
      border-radius: 8px;
      font-size: 0.85rem;
      margin-bottom: 18px;
    }

    /* Dashboard Shell */
    .dashboard-shell {
      max-width: 1200px;
      margin: 0 auto;
      padding: 28px 20px 60px;
    }

    /* Top Nav */
    .dash-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding-bottom: 24px;
      border-bottom: 1px solid var(--panel-border);
      margin-bottom: 28px;
      flex-wrap: wrap;
      gap: 16px;
    }

    .dash-brand {
      display: flex;
      align-items: center;
      gap: 12px;
    }

    .dash-brand h1 { font-size: 1.25rem; font-weight: 800; }
    .status-badge {
      display: inline-flex;
      align-items: center;
      gap: 6px;
      padding: 4px 10px;
      border-radius: 20px;
      font-size: 0.75rem;
      font-weight: 600;
      background: rgba(16, 185, 129, 0.15);
      color: #34d399;
      border: 1px solid rgba(16, 185, 129, 0.3);
    }
    .status-dot { width: 8px; height: 8px; border-radius: 50%; background: #34d399; animation: pulse 2s infinite; }

    @keyframes pulse {
      0%, 100% { opacity: 1; transform: scale(1); }
      50% { opacity: 0.5; transform: scale(1.1); }
    }

    .dash-actions {
      display: flex;
      gap: 12px;
      align-items: center;
    }

    .btn-action {
      padding: 8px 14px;
      background: var(--card-bg);
      border: 1px solid var(--panel-border);
      border-radius: 8px;
      color: var(--text);
      font-size: 0.84rem;
      font-weight: 600;
      cursor: pointer;
      display: inline-flex;
      align-items: center;
      gap: 6px;
      text-decoration: none;
    }
    .btn-action:hover { background: #1e2c47; text-decoration: none; }
    .btn-action.btn-danger { color: #f87171; border-color: rgba(239, 68, 68, 0.3); }

    /* Core Metrics Cards */
    .metrics-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
      gap: 20px;
      margin-bottom: 28px;
    }

    .metric-card {
      background: var(--panel);
      border: 1px solid var(--panel-border);
      border-radius: var(--radius);
      padding: 24px;
      position: relative;
      overflow: hidden;
    }

    .metric-card::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      right: 0;
      height: 3px;
    }
    .metric-card.card-today::before { background: linear-gradient(90deg, #3b82f6, #60a5fa); }
    .metric-card.card-month::before { background: linear-gradient(90deg, #10b981, #34d399); }
    .metric-card.card-total::before { background: linear-gradient(90deg, #8b5cf6, #c084fc); }

    .metric-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 12px;
    }
    .metric-label { font-size: 0.85rem; font-weight: 600; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.05em; }
    .metric-icon { font-size: 1.2rem; }

    .metric-number {
      font-size: 2.3rem;
      font-weight: 800;
      line-height: 1.1;
      margin-bottom: 6px;
      font-family: 'JetBrains Mono', monospace;
    }

    .metric-sub {
      font-size: 0.82rem;
      color: var(--text-muted);
      display: flex;
      gap: 8px;
      align-items: center;
    }
    .badge-unique {
      background: rgba(255, 255, 255, 0.08);
      padding: 2px 8px;
      border-radius: 6px;
      color: #93c5fd;
      font-weight: 600;
    }

    /* Notice Banner */
    .bot-notice {
      background: rgba(59, 130, 246, 0.08);
      border: 1px solid rgba(59, 130, 246, 0.25);
      border-radius: 10px;
      padding: 12px 16px;
      font-size: 0.84rem;
      color: #93c5fd;
      margin-bottom: 28px;
      display: flex;
      align-items: center;
      gap: 10px;
    }

    /* Chart Section */
    .section-card {
      background: var(--panel);
      border: 1px solid var(--panel-border);
      border-radius: var(--radius);
      padding: 24px;
      margin-bottom: 28px;
    }

    .section-title {
      font-size: 1.05rem;
      font-weight: 700;
      margin-bottom: 18px;
      display: flex;
      align-items: center;
      justify-content: space-between;
    }

    /* 14 Day Bar Chart */
    .chart-container {
      display: flex;
      align-items: flex-end;
      gap: 8px;
      height: 180px;
      padding-top: 24px;
      border-bottom: 1px solid var(--panel-border);
      padding-bottom: 8px;
    }

    .chart-col {
      flex: 1;
      display: flex;
      flex-direction: column;
      align-items: center;
      height: 100%;
      justify-content: flex-end;
      position: relative;
    }

    .chart-bar-wrap {
      width: 100%;
      display: flex;
      align-items: flex-end;
      justify-content: center;
      height: 140px;
    }

    .chart-bar {
      width: 80%;
      max-width: 28px;
      background: linear-gradient(180deg, #3b82f6 0%, #1d4ed8 100%);
      border-radius: 4px 4px 0 0;
      transition: height 0.3s ease, background 0.2s;
      min-height: 4px;
      position: relative;
    }

    .chart-bar:hover {
      background: linear-gradient(180deg, #60a5fa 0%, #2563eb 100%);
    }

    .chart-bar-val {
      position: absolute;
      top: -20px;
      left: 50%;
      transform: translateX(-50%);
      font-size: 0.72rem;
      font-weight: 600;
      color: var(--text-muted);
      font-family: 'JetBrains Mono', monospace;
    }

    .chart-date {
      margin-top: 8px;
      font-size: 0.72rem;
      color: var(--text-muted);
      white-space: nowrap;
    }

    /* 2 Columns Section */
    .two-cols {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
      gap: 20px;
      margin-bottom: 28px;
    }

    .breakdown-table {
      width: 100%;
      border-collapse: collapse;
      font-size: 0.88rem;
    }

    .breakdown-table th {
      text-align: left;
      padding: 8px 10px;
      color: var(--text-muted);
      font-size: 0.78rem;
      font-weight: 600;
      text-transform: uppercase;
      border-bottom: 1px solid var(--panel-border);
    }

    .breakdown-table td {
      padding: 10px;
      border-bottom: 1px solid rgba(255, 255, 255, 0.04);
    }

    .breakdown-bar-bg {
      background: rgba(255, 255, 255, 0.05);
      height: 6px;
      border-radius: 3px;
      overflow: hidden;
      margin-top: 4px;
    }

    .breakdown-bar-fill {
      height: 100%;
      background: var(--primary);
      border-radius: 3px;
    }

    .pill-page {
      display: inline-block;
      padding: 2px 8px;
      border-radius: 4px;
      font-family: 'JetBrains Mono', monospace;
      font-size: 0.78rem;
      background: rgba(59, 130, 246, 0.15);
      color: #93c5fd;
    }

    .pill-device {
      display: inline-block;
      padding: 2px 8px;
      border-radius: 4px;
      font-size: 0.78rem;
      background: rgba(16, 185, 129, 0.15);
      color: #6ee7b7;
    }

    /* Responsive */
    @media (max-width: 640px) {
      .dash-header { flex-direction: column; align-items: flex-start; }
      .chart-container { gap: 4px; }
      .chart-date { font-size: 0.62rem; }
    }
  </style>
</head>
<body>

<?php if (!$isLoggedIn): ?>
  <!-- Login Screen -->
  <div class="login-wrapper">
    <div class="login-card">
      <div class="login-brand">
        <div class="brand-mark">rx</div>
        <div>
          <div class="login-title">Admin Login</div>
          <div class="login-sub">Visitor Analytics Portal</div>
        </div>
      </div>

      <?php if (!empty($loginError)): ?>
        <div class="alert-error"><?= htmlspecialchars($loginError) ?></div>
      <?php endif; ?>

      <form method="POST" action="admin.php">
        <div class="form-group">
          <label for="username">Username</label>
          <input type="text" id="username" name="username" class="form-control" required autofocus placeholder="admin">
        </div>
        <div class="form-group">
          <label for="password">Password</label>
          <input type="password" id="password" name="password" class="form-control" required placeholder="••••••••">
        </div>
        <button type="submit" name="login" class="btn-login">Sign In →</button>
      </form>
    </div>
  </div>

<?php else: ?>
  <!-- Admin Dashboard -->
  <div class="dashboard-shell">
    
    <!-- Top Bar -->
    <header class="dash-header">
      <div class="dash-brand">
        <div class="brand-mark">rx</div>
        <div>
          <h1>Website Visitor Analytics</h1>
          <div class="status-badge">
            <span class="status-dot"></span>
            <span>Live Monitoring Active</span>
          </div>
        </div>
      </div>
      <div class="dash-actions">
        <a href="index.php" class="btn-action" target="_blank">🎬 Number Counter</a>
        <a href="home.php" class="btn-action" target="_blank">🏠 Homepage</a>
        <a href="admin.php?action=export_csv" class="btn-action">📥 Export CSV</a>
        <a href="admin.php?action=purge_storage" onclick="return confirm('Purge all generated video output and temp files from server disk now?')" class="btn-action" style="color: #fbbf24; border-color: rgba(245, 158, 11, 0.4);" title="Clean Output and Temp folders">🧹 Purge Storage (<?= $totalStorageMB ?> MB)</a>
        <a href="admin.php?action=logout" class="btn-action btn-danger">Log Out</a>
      </div>
    </header>

    <?php if (isset($_GET['purged'])): ?>
      <div class="bot-notice" style="background: rgba(16, 185, 129, 0.12); border-color: rgba(16, 185, 129, 0.4); color: #6ee7b7; margin-bottom: 18px;">
        <span>✅</span>
        <span><strong>Storage Cleaned:</strong> Successfully purged <?= (int)$_GET['purged'] ?> temporary and video file(s) from server disk. Current disk footprint is now <?= $totalStorageMB ?> MB.</span>
      </div>
    <?php endif; ?>

    <!-- Uptime Filter Indicator -->
    <div class="bot-notice">
      <span>🛡️</span>
      <span><strong>Bot &amp; Uptime Protected:</strong> UptimeRobot pings and automated search bots are automatically ignored. Only real visitors executing JavaScript are counted.</span>
    </div>


    <!-- 3 Main Metrics Cards -->
    <div class="metrics-grid">
      <!-- Daily (Today) -->
      <div class="metric-card card-today">
        <div class="metric-header">
          <span class="metric-label">Today's Visits (Daily)</span>
          <span class="metric-icon">📅</span>
        </div>
        <div class="metric-number"><?= number_format($stats['today']['total']) ?></div>
        <div class="metric-sub">
          <span class="badge-unique"><?= number_format($stats['today']['unique']) ?> Unique</span>
          <span>human visitors today</span>
        </div>
      </div>

      <!-- Monthly (This Month) -->
      <div class="metric-card card-month">
        <div class="metric-header">
          <span class="metric-label">This Month (<?= date('F Y') ?>)</span>
          <span class="metric-icon">🗓️</span>
        </div>
        <div class="metric-number"><?= number_format($stats['month']['total']) ?></div>
        <div class="metric-sub">
          <span class="badge-unique"><?= number_format($stats['month']['unique']) ?> Unique</span>
          <span>visitors this month</span>
        </div>
      </div>

      <!-- Total (All-Time) -->
      <div class="metric-card card-total">
        <div class="metric-header">
          <span class="metric-label">All-Time Visits (Total)</span>
          <span class="metric-icon">🌐</span>
        </div>
        <div class="metric-number"><?= number_format($stats['all_time']['total']) ?></div>
        <div class="metric-sub">
          <span class="badge-unique"><?= number_format($stats['all_time']['unique']) ?> Unique</span>
          <span>lifetime visitors</span>
        </div>
      </div>

      <!-- Render Storage Card -->
      <div class="metric-card" style="position: relative;">
        <div style="position: absolute; top: 0; left: 0; right: 0; height: 3px; background: linear-gradient(90deg, #f59e0b, #fbbf24);"></div>
        <div class="metric-header">
          <span class="metric-label">Render Storage</span>
          <span class="metric-icon">💾</span>
        </div>
        <div class="metric-number" style="color: <?= $totalStorageMB > 100 ? '#ef4444' : '#f59e0b' ?>;">
          <?= $totalStorageMB ?> <span style="font-size: 1rem; font-weight: normal; color: var(--text-muted);">MB</span>
        </div>
        <div class="metric-sub" style="font-size: 0.78rem; display: flex; flex-direction: column; align-items: flex-start; gap: 2px;">
          <span style="color: var(--text-muted);">Output: <?= $outputStorage['count'] ?> / 10 MP4s (<?= $outputStorage['mb'] ?> MB)</span>
          <span style="color: #34d399; font-weight: 600;">✓ FIFO Queue: Keeps latest 10 files</span>
        </div>
      </div>
    </div>

    <!-- 14-Day Visual Trend Bar Chart -->
    <div class="section-card">
      <div class="section-title">
        <span>📈 14-Day Traffic Trend</span>
        <span style="font-size:0.8rem; font-weight:normal; color:var(--text-muted);">Daily Pageviews</span>
      </div>

      <?php
        $maxDaily = 1;
        foreach ($stats['daily_trend'] as $day) {
            if ($day['total'] > $maxDaily) $maxDaily = $day['total'];
        }
      ?>

      <div class="chart-container">
        <?php foreach ($stats['daily_trend'] as $day): ?>
          <?php 
            $pct = max(3, round(($day['total'] / $maxDaily) * 100)); 
          ?>
          <div class="chart-col">
            <div class="chart-bar-wrap">
              <div class="chart-bar" style="height: <?= $pct ?>%;">
                <?php if ($day['total'] > 0): ?>
                  <span class="chart-bar-val"><?= $day['total'] ?></span>
                <?php endif; ?>
              </div>
            </div>
            <div class="chart-date"><?= htmlspecialchars($day['label']) ?></div>
          </div>
        <?php endforeach; ?>
      </div>
    </div>

    <!-- Two Columns: Tool Breakdown & Device Breakdown -->
    <div class="two-cols">
      <!-- Visits by Tool / Page -->
      <div class="section-card" style="margin-bottom:0;">
        <div class="section-title">📄 Visits by Tool / Page</div>
        <table class="breakdown-table">
          <thead>
            <tr>
              <th>Page / Tool</th>
              <th style="text-align:right;">Views</th>
              <th style="text-align:right;">Unique</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($stats['pages'])): ?>
              <tr><td colspan="3" style="color:var(--text-muted); text-align:center; padding:20px;">No page data recorded yet.</td></tr>
            <?php else: ?>
              <?php foreach ($stats['pages'] as $p): ?>
                <tr>
                  <td>
                    <span class="pill-page"><?= htmlspecialchars($p['page']) ?></span>
                    <?php 
                      $pctPage = $stats['all_time']['total'] > 0 ? round(($p['total'] / $stats['all_time']['total']) * 100) : 0; 
                    ?>
                    <div class="breakdown-bar-bg">
                      <div class="breakdown-bar-fill" style="width: <?= $pctPage ?>%;"></div>
                    </div>
                  </td>
                  <td style="text-align:right; font-weight:700; font-family:'JetBrains Mono',monospace;"><?= number_format($p['total']) ?></td>
                  <td style="text-align:right; color:var(--text-muted); font-family:'JetBrains Mono',monospace;"><?= number_format($p['unique_v']) ?></td>
                </tr>
              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>

      <!-- Device Breakdown -->
      <div class="section-card" style="margin-bottom:0;">
        <div class="section-title">📱 Device Breakdown</div>
        <table class="breakdown-table">
          <thead>
            <tr>
              <th>Device</th>
              <th style="text-align:right;">Share</th>
              <th style="text-align:right;">Total Views</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($stats['devices'])): ?>
              <tr><td colspan="3" style="color:var(--text-muted); text-align:center; padding:20px;">No device data yet.</td></tr>
            <?php else: ?>
              <?php foreach ($stats['devices'] as $d): ?>
                <?php 
                  $pctDev = $stats['all_time']['total'] > 0 ? round(($d['total'] / $stats['all_time']['total']) * 100) : 0; 
                ?>
                <tr>
                  <td>
                    <span class="pill-device"><?= htmlspecialchars($d['device']) ?></span>
                    <div class="breakdown-bar-bg">
                      <div class="breakdown-bar-fill" style="width: <?= $pctDev ?>%; background:#10b981;"></div>
                    </div>
                  </td>
                  <td style="text-align:right; color:var(--text-muted);"><?= $pctDev ?>%</td>
                  <td style="text-align:right; font-weight:700; font-family:'JetBrains Mono',monospace;"><?= number_format($d['total']) ?></td>
                </tr>
              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Recent Activity Log -->
    <div class="section-card" style="margin-top:20px;">
      <div class="section-title">🕒 Recent Visitor Activity (Last 25 Views)</div>
      <table class="breakdown-table">
        <thead>
          <tr>
            <th>Time</th>
            <th>Page / Tool</th>
            <th>Device</th>
            <th>Referrer</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($stats['recent'])): ?>
            <tr><td colspan="4" style="color:var(--text-muted); text-align:center; padding:20px;">No visitor logs yet. Once visitors arrive, they will appear here.</td></tr>
          <?php else: ?>
            <?php foreach ($stats['recent'] as $log): ?>
              <tr>
                <td style="color:var(--text-muted); font-size:0.8rem; font-family:'JetBrains Mono',monospace; white-space:nowrap;">
                  <?= htmlspecialchars($log['created_at']) ?>
                </td>
                <td><span class="pill-page"><?= htmlspecialchars($log['page']) ?></span></td>
                <td><span class="pill-device"><?= htmlspecialchars($log['device']) ?></span></td>
                <td style="color:var(--text-muted);"><?= htmlspecialchars($log['referrer']) ?></td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>

  </div>
<?php endif; ?>

</body>
</html>

