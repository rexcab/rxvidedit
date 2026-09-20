<?php
/**
 * Lightweight Keep-Alive Ping Endpoint
 * Designed specifically for UptimeRobot / cron-job.org
 * Returns immediate 200 OK without running analytics or database queries.
 */
http_response_code(200);
header('Content-Type: text/plain; charset=utf-8');
header('Cache-Control: no-cache, no-store, must-revalidate');
echo "OK";

