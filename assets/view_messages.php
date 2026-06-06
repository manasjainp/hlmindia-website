<?php
// view_messages.php - Admin Panel

session_start();

// Enable error reporting for debugging
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(0);

// Set Timezone to India Standard Time (matches log dates)
date_default_timezone_set('Asia/Kolkata');

// --- AUTHENTICATION ---
$admin_password = "hlmmanas!@#2026";

// Handle Logout
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: view_messages.php");
    exit;
}

// Handle Login Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['password'])) {
    $logFile = __DIR__ . '/security.log';
    $max_attempts = 3;
    $lockout_time = 15 * 60; // 15 minutes in seconds
    $ip = $_SERVER['HTTP_CF_CONNECTING_IP'] ?? $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
    $agent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
    $current_time = time();

    $attempts_count = 0;

    // Parse security.log backwards for recent attempts by this IP
    if (file_exists($logFile)) {
        $lines = file($logFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if ($lines) {
            for ($i = count($lines) - 1; $i >= 0; $i--) {
                $parts = explode(' | ', $lines[$i]);
                if (count($parts) >= 3) {
                    $log_date = trim($parts[0]);
                    $log_ip = trim($parts[1]);
                    $log_action = trim($parts[2]);

                    if ($log_ip === $ip) {
                        $log_time = strtotime($log_date);
                        if ($current_time - $log_time > $lockout_time) {
                            break; // Stop checking if we are past the 15-minute window
                        }
                        if ($log_action === 'SUCCESSFUL_LOGIN') {
                            break; // Reset count on successful login
                        } elseif ($log_action === 'FAILED_LOGIN') {
                            $attempts_count++;
                        }
                    }
                }
            }
        }
    }

    if ($attempts_count >= $max_attempts) {
        $login_error = "Too many failed attempts. Please try again after 15 minutes.";
        $date = date('Y-m-d H:i:s');
        file_put_contents($logFile, "$date | $ip | LOCKOUT_BLOCK | $agent" . PHP_EOL, FILE_APPEND | LOCK_EX);
    } else {
        if ($_POST['password'] === $admin_password) {
            $date = date('Y-m-d H:i:s');
            file_put_contents($logFile, "$date | $ip | SUCCESSFUL_LOGIN | $agent" . PHP_EOL, FILE_APPEND | LOCK_EX);
            
            session_regenerate_id(true); // Prevent Session Fixation
            $_SESSION['is_admin'] = true;
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32)); // Generate CSRF Token
            header("Location: view_messages.php"); // Redirect to clear POST data
            exit;
        } else {
            $date = date('Y-m-d H:i:s');
            file_put_contents($logFile, "$date | $ip | FAILED_LOGIN | $agent" . PHP_EOL, FILE_APPEND | LOCK_EX);
            
            $attempts_count++;
            $remaining = $max_attempts - $attempts_count;
            if ($remaining > 0) {
                $login_error = "Incorrect password. {$remaining} attempts remaining.";
            } else {
                $login_error = "Too many failed attempts. Please try again after 15 minutes.";
            }
        }
    }
}

// Require Login
if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head><title>Admin Login</title><style>body{font-family:'Segoe UI',sans-serif;background:#f3f4f6;display:flex;justify-content:center;align-items:center;height:100vh;margin:0}.login-card{background:white;padding:2rem;border-radius:8px;box-shadow:0 4px 6px rgba(0,0,0,0.1);width:100%;max-width:320px;text-align:center}h2{color:#091a3d;margin-top:0}input{width:100%;padding:10px;margin:10px 0;border:1px solid #ddd;border-radius:4px;box-sizing:border-box}button{width:100%;padding:10px;background:#091a3d;color:white;border:none;border-radius:4px;cursor:pointer;font-weight:600}button:hover{background:#0f355f}.error{color:#ef4444;font-size:14px;margin-bottom:10px}</style></head>
    <body>
        <div class="login-card">
            <h2>Admin Login</h2>
            <?php if(isset($login_error)) echo '<div class="error">'.$login_error.'</div>'; ?>
            <form method="POST"><input type="password" name="password" placeholder="Enter Password" required autofocus><button type="submit">Login</button></form>
        </div>
    </body>
    </html>
    <?php
    exit;
}

$jsonFile = __DIR__ . '/contact_submissions.json';

// CSRF Check Function
function check_csrf() {
    if (!isset($_POST['csrf_token']) || !isset($_SESSION['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die("Security Error: Invalid CSRF Token. Please refresh the page and try again.");
    }
}

// Handle Delete Request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    check_csrf();
    if (file_exists($jsonFile)) {
        $submissions = json_decode(file_get_contents($jsonFile), true);
        if (is_array($submissions)) {
            $filtered = array_filter($submissions, function($item) {
                return $item['id'] !== $_POST['delete_id'];
            });
            file_put_contents($jsonFile, json_encode(array_values($filtered), JSON_PRETTY_PRINT | LOCK_EX));
        }
    }
    header('Location: view_messages.php');
    exit;
}

// Handle Clear Log Request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['clear_log'])) {
    check_csrf();
    $logFile = __DIR__ . '/security.log';
    if (file_exists($logFile)) {
        file_put_contents($logFile, '');
    }
    header('Location: view_messages.php');
    exit;
}

// Handle Toggle Status Request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_id'])) {
    check_csrf();
    if (file_exists($jsonFile)) {
        $submissions = json_decode(file_get_contents($jsonFile), true);
        if (is_array($submissions)) {
            foreach ($submissions as &$item) {
                if ($item['id'] === $_POST['toggle_id']) {
                    // Toggle between New and Read
                    $item['status'] = (isset($item['status']) && $item['status'] === 'Read') ? 'New' : 'Read';
                    break;
                }
            }
            file_put_contents($jsonFile, json_encode($submissions, JSON_PRETTY_PRINT | LOCK_EX));
        }
    }
    header('Location: view_messages.php');
    exit;
}

// Handle Export CSV Request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['export_csv'])) {
    check_csrf();
    if (file_exists($jsonFile)) {
        $submissions = json_decode(file_get_contents($jsonFile), true);
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="messages_' . date('Y-m-d') . '.csv"');
        $output = fopen('php://output', 'w');
        fputcsv($output, ['Date', 'Name', 'Email', 'Phone', 'Company', 'Subject', 'Message', 'Status']);
        foreach ($submissions as $row) {
            fputcsv($output, [$row['date'], $row['name'], $row['email'], $row['phone'], $row['company'] ?? '', $row['subject'], $row['message'], $row['status'] ?? 'New']);
        }
        fclose($output);
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel - Hertz Logic Machinery</title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; padding: 20px; background-color: #f3f4f6; color: #1f2937; }
        .container { max-width: 1200px; margin: 0 auto; background: white; padding: 30px; border-radius: 10px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
        .header { display: flex; justify-content: space-between; align-items: center; border-bottom: 2px solid #e5e7eb; padding-bottom: 20px; margin-bottom: 20px; }
        h1 { color: #091a3d; margin: 0; font-size: 24px; }
        .refresh-btn { background-color: #091a3d; color: white; text-decoration: none; padding: 10px 20px; border-radius: 5px; font-weight: 600; transition: background 0.3s; }
        .refresh-btn:hover { background-color: #0f355f; }
        .logout-btn { color: #ef4444; text-decoration: none; font-size: 14px; margin-left: 15px; font-weight: 600; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; font-size: 14px; }
        th, td { padding: 12px 15px; text-align: left; border-bottom: 1px solid #e5e7eb; }
        th { background-color: #f9fafb; color: #374151; font-weight: 600; text-transform: uppercase; font-size: 12px; letter-spacing: 0.5px; }
        tr:hover { background-color: #f8fafc; }
        .btn-delete { background-color: #ef4444; color: white; border: none; padding: 6px 12px; border-radius: 4px; cursor: pointer; font-size: 12px; transition: background 0.2s; }
        .btn-delete:hover { background-color: #dc2626; }
        .no-data { text-align: center; padding: 40px; color: #6b7280; font-style: italic; }
        .status-bar { margin-bottom: 15px; font-size: 14px; color: #4b5563; }
        .status-badge { padding: 4px 8px; border-radius: 12px; font-size: 11px; font-weight: bold; text-transform: uppercase; display: inline-block; }
        .status-new { background-color: #d1fae5; color: #065f46; }
        .status-read { background-color: #f3f4f6; color: #6b7280; }
        .security-section { margin-top: 0; border-top: none; padding-top: 0; }
        .log-box { background: #1f2937; color: #10b981; padding: 15px; border-radius: 6px; font-family: monospace; height: 200px; overflow-y: auto; white-space: pre-wrap; font-size: 13px; }
        
        /* Tab Styles */
        .tab-nav { display: flex; border-bottom: 1px solid #e5e7eb; margin-bottom: 20px; }
        .tab-btn { padding: 12px 24px; background: none; border: none; border-bottom: 3px solid transparent; cursor: pointer; font-size: 15px; font-weight: 600; color: #6b7280; transition: all 0.2s; }
        .tab-btn:hover { color: #091a3d; background-color: #f9fafb; }
        .tab-btn.active { color: #091a3d; border-bottom-color: #091a3d; }
        .tab-content { display: none; }
        .tab-content.active { display: block; animation: fadeIn 0.3s ease; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(5px); } to { opacity: 1; transform: translateY(0); } }
        
        /* Pagination Styles */
        .pagination { margin-top: 20px; display: flex; justify-content: center; gap: 5px; }
        .page-btn { padding: 8px 12px; border: 1px solid #ddd; background: white; text-decoration: none; color: #374151; border-radius: 4px; transition: all 0.2s; }
        .page-btn:hover { background-color: #f3f4f6; }
        .page-btn.active { background-color: #091a3d; color: white; border-color: #091a3d; }
        
        /* Scroll to Top Button */
        #scrollTopBtn {
            display: none;
            position: fixed;
            bottom: 20px;
            right: 30px;
            z-index: 99;
            font-size: 20px;
            border: none;
            outline: none;
            background-color: #091a3d;
            color: white;
            cursor: pointer;
            padding: 10px 15px;
            border-radius: 50%;
            box-shadow: 0 2px 10px rgba(0,0,0,0.2);
            transition: background-color 0.3s, transform 0.3s;
        }
        #scrollTopBtn:hover {
            background-color: #f5b21a;
            transform: translateY(-3px);
        }
    </style>
    <script>
        function switchTab(tabName) {
            // Hide all tabs
            document.querySelectorAll('.tab-content').forEach(el => el.classList.remove('active'));
            document.querySelectorAll('.tab-btn').forEach(el => el.classList.remove('active'));
            
            // Show selected tab
            document.getElementById('view-' + tabName).classList.add('active');
            document.getElementById('btn-' + tabName).classList.add('active');
            
            // Toggle Export Button visibility
            const exportForm = document.getElementById('export-form');
            if (exportForm) {
                exportForm.style.display = (tabName === 'submissions') ? 'inline' : 'none';
            }
            
            // Save preference so it stays on the same tab after reload
            localStorage.setItem('admin_active_tab', tabName);
        }

        // Scroll to top functionality
        window.onscroll = function() {
            const btn = document.getElementById('scrollTopBtn');
            if (btn) {
                if (window.pageYOffset > 300) {
                    btn.style.display = "block";
                } else {
                    btn.style.display = "none";
                }
            }
        };

        function scrollToTop() {
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }

        document.addEventListener('DOMContentLoaded', function() {
            const savedTab = localStorage.getItem('admin_active_tab') || 'submissions';
            switchTab(savedTab);
        });
    </script>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Admin Panel</h1>
            <div>
                <form id="export-form" method="POST" style="display:inline;">
                    <input type="hidden" name="export_csv" value="true">
                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                    <button type="submit" class="refresh-btn" style="background-color: #059669; margin-right: 10px;">Export CSV</button>
                </form>
                <a href="view_messages.php" class="refresh-btn">Refresh Data</a>
                <a href="?logout=true" class="logout-btn">Logout</a>
            </div>
        </div>

        <!-- Tab Navigation -->
        <div class="tab-nav">
            <button id="btn-submissions" class="tab-btn" onclick="switchTab('submissions')">Form Submissions</button>
            <button id="btn-security" class="tab-btn" onclick="switchTab('security')">Security Access Logs</button>
        </div>

        <!-- Tab 1: Form Submissions -->
        <div id="view-submissions" class="tab-content">
            
            <?php
            if (file_exists($jsonFile)) {
                $submissions = json_decode(file_get_contents($jsonFile), true);
                
                if (!empty($submissions) && is_array($submissions)) {
                    // Pagination Logic
                    $limit = 10;
                    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
                    if ($page < 1) $page = 1;
                    $total_submissions = count($submissions);
                    $total_pages = ceil($total_submissions / $limit);
                    if ($page > $total_pages && $total_pages > 0) $page = $total_pages;
                    $offset = ($page - 1) * $limit;
                    $current_submissions = array_slice($submissions, $offset, $limit);

                    echo '<table>';
                    echo '<thead>
                            <tr>
                                <th>Date</th>
                                <th>Status</th>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Phone</th>
                                <th>Company</th>
                                <th>Subject</th>
                                <th>Message</th>
                                <th>Action</th>
                            </tr>
                          </thead>';
                    echo '<tbody>';
                    foreach ($current_submissions as $row) {
                        $status = $row['status'] ?? 'New';
                        $statusClass = ($status === 'Read') ? 'status-read' : 'status-new';
                        
                        echo '<tr>';
                        echo '<td>' . htmlspecialchars($row['date']) . '</td>';
                        echo '<td><span class="status-badge ' . $statusClass . '">' . htmlspecialchars($status) . '</span></td>';
                        echo '<td>' . htmlspecialchars($row['name']) . '</td>';
                        echo '<td><a href="mailto:' . htmlspecialchars($row['email']) . '">' . htmlspecialchars($row['email']) . '</a></td>';
                        echo '<td>' . htmlspecialchars($row['phone']) . '</td>';
                        echo '<td>' . htmlspecialchars($row['company'] ?? '') . '</td>';
                        echo '<td>' . htmlspecialchars($row['subject']) . '</td>';
                        echo '<td>' . nl2br(htmlspecialchars(substr($row['message'], 0, 100))) . (strlen($row['message']) > 100 ? '...' : '') . '</td>';
                        echo '<td>
                                <form method="POST" style="display:inline-block; margin-bottom:5px;">
                                    <input type="hidden" name="toggle_id" value="' . htmlspecialchars($row['id']) . '">
                                    <input type="hidden" name="csrf_token" value="' . $_SESSION['csrf_token'] . '">
                                    <button type="submit" class="btn-delete" style="background-color: #4b5563;">' . ($status === 'New' ? 'Mark Read' : 'Mark New') . '</button>
                                </form>
                                <form method="POST" onsubmit="return confirm(\'Are you sure you want to delete this message?\');">
                                    <input type="hidden" name="delete_id" value="' . htmlspecialchars($row['id']) . '">
                                    <input type="hidden" name="csrf_token" value="' . $_SESSION['csrf_token'] . '">
                                    <button type="submit" class="btn-delete">Delete</button>
                                </form>
                              </td>';
                        echo '</tr>';
                    }
                    echo '</tbody></table>';

                    // Pagination Controls
                    if ($total_pages > 1) {
                        echo '<div class="pagination">';
                        
                        if ($page > 1) {
                            echo '<a href="?page='.($page-1).'" class="page-btn">&laquo; Prev</a>';
                        }

                        for ($i = 1; $i <= $total_pages; $i++) {
                            $active = ($i == $page) ? 'active' : '';
                            echo '<a href="?page='.$i.'" class="page-btn '.$active.'">'.$i.'</a>';
                        }

                        if ($page < $total_pages) {
                            echo '<a href="?page='.($page+1).'" class="page-btn">Next &raquo;</a>';
                        }
                        echo '</div>';
                    }
                } else {
                    echo '<div class="no-data">File exists but is empty.</div>';
                }
            } else {
                echo '<div class="no-data">No submissions found yet.<br>Submit a form on the Contact page to generate the file.</div>';
            }
            ?>
        </div>

        <!-- Security Logs Section -->
        <div id="view-security" class="tab-content">
            <div class="security-section">
                <div class="header" style="border:none; margin-bottom: 10px; padding-bottom: 0;">
                    <h2 style="font-size: 20px; color: #091a3d; margin: 0;">Security Access Logs</h2>
                    <form method="POST" onsubmit="return confirm('Clear all security logs?');">
                        <input type="hidden" name="clear_log" value="true">
                        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                        <button type="submit" class="btn-delete" style="background-color: #4b5563;">Clear Logs</button>
                    </form>
                </div>
                <div class="log-box">
    <?php
    $logFile = __DIR__ . '/security.log';
    if (file_exists($logFile) && filesize($logFile) > 0) {
        echo htmlspecialchars(file_get_contents($logFile));
    } else {
        echo "No security incidents recorded.";
    }
    ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Scroll to Top Button -->
    <button id="scrollTopBtn" onclick="scrollToTop()" title="Go to top">↑</button>
</body>
</html>