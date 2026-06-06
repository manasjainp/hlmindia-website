<?php
// 403.php - Log unauthorized access attempts

// Set Timezone to India Standard Time
date_default_timezone_set('Asia/Kolkata');

$logFile = __DIR__ . '/assets/security.log';
// Use Cloudflare IP if available, otherwise fall back to remote address
$ip = $_SERVER['HTTP_CF_CONNECTING_IP'] ?? $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
$page = $_SERVER['REQUEST_URI'] ?? 'Unknown';
$date = date('Y-m-d H:i:s');
$agent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';

// Format: Date | IP | File | User Agent
$entry = "$date | $ip | $page | $agent" . PHP_EOL;

// Append to log file
file_put_contents($logFile, $entry, FILE_APPEND);

// Show standard error to the user
http_response_code(403);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Access Denied</title>
    <style>
        body { font-family: 'Segoe UI', sans-serif; text-align: center; padding: 50px; color: #333; background: #f3f4f6; }
        h1 { color: #ef4444; font-size: 48px; margin-bottom: 10px; }
        p { font-size: 18px; color: #4b5563; }
        .ip { font-family: monospace; background: #e5e7eb; padding: 4px 8px; border-radius: 4px; }
    </style>
</head>
<body>
    <h1>403 Forbidden</h1>
    <p>You do not have permission to access this resource.</p>
</body>
</html>