<?php
require_once 'CONFIG/CONFIG.PHP';
require_once 'INCLUDES/AUTH.PHP';

requirePermission('settings');

$lang = getUserLanguage();
$ethiopian_date = getCurrentEthiopianDate();

$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_settings'])) {
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $message = 'Invalid security token';
        $messageType = 'error';
    } else {
        $app_name = sanitizeInput($_POST['app_name']);
        $language = sanitizeInput($_POST['default_language']);
        
        $sql = "INSERT INTO system_settings (setting_key, setting_value, setting_type, updated_by) 
                VALUES ('app_name', :name, 'text', :uid)
                ON DUPLICATE KEY UPDATE setting_value = :name2, updated_by = :uid2";
        $stmt = $db->prepare($sql);
        $stmt->execute([':name' => $app_name, ':uid' => $_SESSION['user_id'], ':name2' => $app_name, ':uid2' => $_SESSION['user_id']]);
        
        $message = 'Settings saved successfully!';
        $messageType = 'success';
    }
}

if (isset($_GET['msg'])) {
    $message = $_GET['msg'];
    $messageType = $_GET['type'] ?? 'info';
}
?>
<!DOCTYPE html>
<html lang="<?php echo $lang; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings - <?php echo APP_NAME; ?></title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        :root {
            --primary: #2c3e50; --secondary: #3498db; --success: #27ae60;
            --danger: #e74c3c; --warning: #f39c12; --info: #2980b9;
            --light: #ecf0f1; --dark: #2c3e50; --glass-bg: rgba(255, 255, 255, 0.95);
            --glass-shadow: 0 8px 32px 0 rgba(31, 38, 135, 0.15);
        }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh; color: var(--dark);
        }
        .app-container { display: flex; min-height: 100vh; }
        .sidebar {
            width: 280px; background: var(--glass-bg); backdrop-filter: blur(10px);
            border-right: 1px solid rgba(255,255,255,0.2); padding: 20px;
            position: fixed; height: 100vh; overflow-y: auto; z-index: 1000;
            box-shadow: var(--glass-shadow);
        }
        .sidebar-logo { text-align: center; padding: 20px 0; border-bottom: 2px solid rgba(255,255,255,0.2); margin-bottom: 20px; }
        .sidebar-logo i { font-size: 40px; color: var(--success); }
        .sidebar-logo h2 { font-size: 16px; color: var(--primary); margin-top: 10px; }
        .sidebar-menu { list-style: none; }
        .sidebar-menu li { margin-bottom: 5px; }
        .sidebar-menu a {
            display: flex; align-items: center; padding: 12px 15px; color: var(--dark);
            text-decoration: none; border-radius: 10px; transition: all 0.3s;
            font-weight: 500; font-size: 14px;
        }
        .sidebar-menu a:hover, .sidebar-menu a.active {
            background: linear-gradient(135deg, var(--secondary), var(--info));
            color: white; transform: translateX(5px);
        }
        .sidebar-menu a i { margin-right: 10px; width: 20px; text-align: center; }
        .main-content { flex: 1; margin-left: 280px; padding: 20px; }
        .top-bar {
            background: var(--glass-bg); backdrop-filter: blur(10px); border-radius: 20px;
            padding: 15px 25px; margin-bottom: 25px; display: flex;
            justify-content: space-between; align-items: center; box-shadow: var(--glass-shadow);
            flex-wrap: wrap; gap: 15px;
        }
        .hamburger { display: none; font-size: 24px; cursor: pointer; background: none; border: none; color: var(--dark); }
        .ethiopian-date {
            background: linear-gradient(135deg, #667eea, #764ba2); color: white;
            padding: 8px 15px; border-radius: 25px; font-size: 14px;
        }
        .section-card {
            background: var(--glass-bg); backdrop-filter: blur(10px); border-radius: 20px;
            padding: 25px; box-shadow: var(--glass-shadow); border: 1px solid rgba(255,255,255,0.2);
            margin-bottom: 25px;
        }
        .section-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; margin-bottom: 8px; font-weight: 500; color: var(--primary); }
        .form-group input, .form-group select, .form-group textarea {
            width: 100%; padding: 12px; border: 2px solid #e0e0e0; border-radius: 10px;
            font-size: 14px; font-family: inherit; transition: all 0.3s;
        }
        .form-group input:focus, .form-group select:focus, .form-group textarea:focus {
            outline: none; border-color: #667eea; box-shadow: 0 0 0 3px rgba(102,126,234,0.1);
        }
        .btn { padding: 12px 30px; border: none; border-radius: 25px; cursor: pointer; font-weight: 600; transition: all 0.3s; font-size: 14px; }
        .btn-primary { background: linear-gradient(135deg, #667eea, #764ba2); color: white; }
        .btn-danger { background: linear-gradient(135deg, var(--danger), #c0392b); color: white; }
        .btn:hover { transform: translateY(-2px); box-shadow: 0 5px 15px rgba(0,0,0,0.2); }
        .message { padding: 12px 20px; border-radius: 10px; margin-bottom: 20px; font-weight: 500; }
        .message.success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .message.error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .settings-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 25px; }
        @media (max-width: 768px) {
            .sidebar { width: 0; padding: 0; overflow: hidden; }
            .sidebar.open { width: 280px; padding: 20px; }
            .main-content { margin-left: 0; }
            .hamburger { display: block; }
        }
    </style>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="app-container">
        <aside class="sidebar" id="sidebar">
            <div class="sidebar-logo">
                <i class="fas fa-leaf"></i>
                <h2><?php echo APP_NAME; ?></h2>
            </div>
            <ul class="sidebar-menu">
                <li><a href="DASHBOARD.PHP"><i class="fas fa-home"></i> Dashboard</a></li>
                <li><a href="SETTINGS.PHP" class="active"><i class="fas fa-cog"></i> Settings</a></li>
                <li><a href="LOGOUT.PHP"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>
        </aside>

        <main class="main-content">
            <div class="top-bar">
                <div class="top-bar-left">
                    <button class="hamburger" onclick="toggleSidebar()"><i class="fas fa-bars"></i></button>
                    <h2>Settings</h2>
                    <div class="ethiopian-date"><i class="fas fa-calendar-alt"></i> <?php echo $ethiopian_date; ?></div>
                </div>
                <div><strong><?php echo $_SESSION['full_name']; ?></strong></div>
            </div>

            <?php if ($message): ?>
            <div class="message <?php echo $messageType; ?>"><?php echo htmlspecialchars($message); ?></div>
            <?php endif; ?>

            <div class="settings-grid">
                <div class="section-card">
                    <div class="section-header"><h3>General Settings</h3></div>
                    <form method="POST" action="">
                        <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                        <input type="hidden" name="save_settings" value="1">
                        
                        <div class="form-group">
                            <label>Application Name</label>
                            <input type="text" name="app_name" value="<?php echo APP_NAME; ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Default Language</label>
                            <select name="default_language">
                                <option value="am">አማርኛ</option>
                                <option value="en">English</option>
                                <option value="or">Afaan Oromo</option>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save Settings</button>
                    </form>
                </div>

                <div class="section-card">
                    <div class="section-header"><h3>System Information</h3></div>
                    <table style="width: 100%;">
                        <tr><td><strong>Version:</strong></td><td><?php echo APP_VERSION; ?></td></tr>
                        <tr><td><strong>PHP Version:</strong></td><td><?php echo phpversion(); ?></td></tr>
                        <tr><td><strong>Database:</strong></td><td>MySQL</td></tr>
                        <tr><td><strong>Server:</strong></td><td><?php echo $_SERVER['SERVER_SOFTWARE']; ?></td></tr>
                        <tr><td><strong>Ethiopian Date:</strong></td><td><?php echo $ethiopian_date; ?></td></tr>
                    </table>
                </div>

                <div class="section-card">
                    <div class="section-header"><h3>Backup & Restore</h3></div>
                    <div style="display: flex; gap: 15px; flex-wrap: wrap;">
                        <button class="btn btn-primary" onclick="backupDatabase()">
                            <i class="fas fa-database"></i> Backup Database
                        </button>
                        <button class="btn btn-danger" onclick="confirmReset()">
                            <i class="fas fa-exclamation-triangle"></i> Reset System
                        </button>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script>
        function toggleSidebar() {
            document.getElementById('sidebar').classList.toggle('open');
        }

        function backupDatabase() {
            if (confirm('Are you sure you want to backup the database?')) {
                alert('Database backup initiated! This may take a few moments.');
            }
        }

        function confirmReset() {
            if (confirm('WARNING: This will reset all data! Are you absolutely sure?')) {
                var confirmAgain = prompt('Type "DELETE" to confirm system reset:');
                if (confirmAgain === 'DELETE') {
                    alert('System reset initiated!');
                }
            }
        }
    </script>
</body>
</html>