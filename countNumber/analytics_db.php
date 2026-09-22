<?php
/**
 * Analytics Database Handler
 * Uses Supabase (PostgreSQL) when DATABASE_URL env var is set.
 * Falls back to local SQLite for local development.
 */

date_default_timezone_set('Asia/Manila');

class AnalyticsDB {
    private static ?PDO $pdo = null;
    private static string $driver = 'sqlite';

    public static function getPDO(): PDO {
        if (self::$pdo !== null) {
            return self::$pdo;
        }

        $dbUrl = getenv('DATABASE_URL') ?: '';

        if (!empty($dbUrl)) {
            // ── Supabase / PostgreSQL ──────────────────────────────────────
            self::$driver = 'pgsql';

            $parts  = parse_url($dbUrl);
            $host   = $parts['host']               ?? 'localhost';
            $port   = $parts['port']               ?? 5432;
            $dbname = ltrim($parts['path'] ?? '/postgres', '/');
            $user   = urldecode($parts['user']     ?? 'postgres');
            $pass   = urldecode($parts['pass']     ?? '');

            $dsn = "pgsql:host={$host};port={$port};dbname={$dbname};sslmode=require";

            self::$pdo = new PDO($dsn, $user, $pass, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]);

        } else {
            // ── Local SQLite fallback (dev only) ───────────────────────────
            self::$driver = 'sqlite';

            $dbDir = __DIR__ . '/data';
            if (!is_dir($dbDir)) {
                @mkdir($dbDir, 0777, true);
            }
            $dbFile = $dbDir . '/analytics.db';

            self::$pdo = new PDO('sqlite:' . $dbFile, null, null, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_TIMEOUT            => 5,
            ]);
        }

        self::initSchema();
        return self::$pdo;
    }

    private static function initSchema(): void {
        $pdo = self::$pdo;

        if (self::$driver === 'pgsql') {
            $pdo->exec("
                CREATE TABLE IF NOT EXISTS page_views (
                    id          BIGSERIAL PRIMARY KEY,
                    page        TEXT NOT NULL,
                    visitor_hash TEXT NOT NULL,
                    device      TEXT NOT NULL DEFAULT 'Desktop',
                    referrer    TEXT NOT NULL DEFAULT 'Direct',
                    visit_date  TEXT NOT NULL,
                    visit_month TEXT NOT NULL,
                    created_at  TIMESTAMP DEFAULT NOW()
                );
                CREATE INDEX IF NOT EXISTS idx_visit_date     ON page_views(visit_date);
                CREATE INDEX IF NOT EXISTS idx_visit_month    ON page_views(visit_month);
                CREATE INDEX IF NOT EXISTS idx_visitor_hash   ON page_views(visitor_hash);
                CREATE INDEX IF NOT EXISTS idx_page           ON page_views(page);
            ");
        } else {
            $pdo->exec("
                CREATE TABLE IF NOT EXISTS page_views (
                    id           INTEGER PRIMARY KEY AUTOINCREMENT,
                    page         TEXT NOT NULL,
                    visitor_hash TEXT NOT NULL,
                    device       TEXT NOT NULL DEFAULT 'Desktop',
                    referrer     TEXT NOT NULL DEFAULT 'Direct',
                    visit_date   TEXT NOT NULL,
                    visit_month  TEXT NOT NULL,
                    created_at   DATETIME DEFAULT CURRENT_TIMESTAMP
                );
                CREATE INDEX IF NOT EXISTS idx_visit_date   ON page_views(visit_date);
                CREATE INDEX IF NOT EXISTS idx_visit_month  ON page_views(visit_month);
                CREATE INDEX IF NOT EXISTS idx_visitor_hash ON page_views(visitor_hash);
                CREATE INDEX IF NOT EXISTS idx_page         ON page_views(page);
            ");
        }
    }

    /**
     * Record a new human page view.
     */
    public static function recordVisit(string $page, string $visitorHash, string $device, string $referrer): bool {
        try {
            $pdo       = self::getPDO();
            $today     = date('Y-m-d');
            $thisMonth = date('Y-m');
            // Always store UTC timestamp so formatPhtTime() can convert correctly
            $utcNow    = gmdate('Y-m-d H:i:s');

            $stmt = $pdo->prepare("
                INSERT INTO page_views (page, visitor_hash, device, referrer, visit_date, visit_month, created_at)
                VALUES (:page, :hash, :device, :referrer, :date, :month, :created_at)
            ");

            return $stmt->execute([
                ':page'       => substr(trim($page), 0, 50) ?: 'unknown',
                ':hash'       => $visitorHash,
                ':device'     => in_array($device, ['Desktop', 'Mobile', 'Tablet'], true) ? $device : 'Desktop',
                ':referrer'   => substr(trim($referrer), 0, 100) ?: 'Direct',
                ':date'       => $today,
                ':month'      => $thisMonth,
                ':created_at' => $utcNow,
            ]);
        } catch (\Exception $e) {
            error_log('AnalyticsDB Error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Retrieve aggregated statistics for the admin dashboard.
     */
    public static function getStats(): array {
        $pdo       = self::getPDO();
        $today     = date('Y-m-d');
        $thisMonth = date('Y-m');

        // Today
        $stmt = $pdo->prepare('SELECT COUNT(*) as total, COUNT(DISTINCT visitor_hash) as unique_v FROM page_views WHERE visit_date = :d');
        $stmt->execute([':d' => $today]);
        $todayStats = $stmt->fetch() ?: ['total' => 0, 'unique_v' => 0];

        // This Month
        $stmt = $pdo->prepare('SELECT COUNT(*) as total, COUNT(DISTINCT visitor_hash) as unique_v FROM page_views WHERE visit_month = :m');
        $stmt->execute([':m' => $thisMonth]);
        $monthStats = $stmt->fetch() ?: ['total' => 0, 'unique_v' => 0];

        // All Time Total
        $totalStmt    = $pdo->query('SELECT COUNT(*) as total, COUNT(DISTINCT visitor_hash) as unique_v FROM page_views');
        $allTimeStats = $totalStmt->fetch() ?: ['total' => 0, 'unique_v' => 0];

        // Breakdown by Page
        $pageStmt = $pdo->query('SELECT page, COUNT(*) as total, COUNT(DISTINCT visitor_hash) as unique_v FROM page_views GROUP BY page ORDER BY total DESC LIMIT 10');
        $pages    = $pageStmt->fetchAll();

        // Breakdown by Device
        $deviceStmt = $pdo->query('SELECT device, COUNT(*) as total FROM page_views GROUP BY device ORDER BY total DESC');
        $devices    = $deviceStmt->fetchAll();

        // Top Referrers
        $refStmt  = $pdo->query('SELECT referrer, COUNT(*) as total FROM page_views GROUP BY referrer ORDER BY total DESC LIMIT 8');
        $referrers = $refStmt->fetchAll();

        // Last 14 Days Trend (Manila Timezone)
        $startDate = date('Y-m-d', strtotime('-13 days'));
        $trendStmt = $pdo->prepare('
            SELECT visit_date, COUNT(*) as total, COUNT(DISTINCT visitor_hash) as unique_v
            FROM page_views
            WHERE visit_date >= :startDate
            GROUP BY visit_date
            ORDER BY visit_date ASC
        ');
        $trendStmt->execute([':startDate' => $startDate]);
        $trendRaw = $trendStmt->fetchAll();

        // Fill in missing days so chart always has full 14 continuous days
        $trendMap = [];
        foreach ($trendRaw as $row) {
            $trendMap[$row['visit_date']] = $row;
        }

        $dailyTrend = [];
        for ($i = 13; $i >= 0; $i--) {
            $dateKey      = date('Y-m-d', strtotime("-$i days"));
            $dailyTrend[] = [
                'date'     => $dateKey,
                'label'    => date('M d', strtotime($dateKey)),
                'total'    => isset($trendMap[$dateKey]) ? (int)$trendMap[$dateKey]['total'] : 0,
                'unique_v' => isset($trendMap[$dateKey]) ? (int)$trendMap[$dateKey]['unique_v'] : 0,
            ];
        }

        // Recent 25 Pageviews
        $recentStmt = $pdo->query('
            SELECT page, device, referrer, created_at
            FROM page_views
            ORDER BY id DESC
            LIMIT 25
        ');
        $recent = $recentStmt->fetchAll();

        return [
            'today'       => ['total' => (int)$todayStats['total'],    'unique' => (int)$todayStats['unique_v']],
            'month'       => ['total' => (int)$monthStats['total'],    'unique' => (int)$monthStats['unique_v']],
            'all_time'    => ['total' => (int)$allTimeStats['total'],  'unique' => (int)$allTimeStats['unique_v']],
            'pages'       => $pages,
            'devices'     => $devices,
            'referrers'   => $referrers,
            'daily_trend' => $dailyTrend,
            'recent'      => $recent,
        ];
    }
}
