<?php
require_once 'CONFIG/CONFIG.PHP';
require_once 'INCLUDES/AUTH.PHP';

requirePermission('fish');

$lang = getUserLanguage();
$ethiopian_date = getCurrentEthiopianDate();

$sql = "SELECT * FROM fish_ponds ORDER BY created_at DESC";
$stmt = $db->query($sql);
$ponds = $stmt->fetchAll(PDO::FETCH_ASSOC);

$sql = "SELECT COALESCE(SUM(current_count), 0) as total_fish FROM fish_ponds WHERE status = 'active'";
$stmt = $db->query($sql);
$totalFish = $stmt->fetch(PDO::FETCH_ASSOC)['total_fish'];

$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_pond'])) {
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $message = 'Invalid security token';
        $messageType = 'error';
    } else {
        $pond_code = sanitizeInput($_POST['pond_code']);
        $pond_name = sanitizeInput($_POST['pond_name']);
        $size = floatval($_POST['size_sqm']);
        $water_source = sanitizeInput($_POST['water_source']);
        $species = sanitizeInput($_POST['fish_species']);
        $count = intval($_POST['initial_count']);
        
        try {
            $sql = "INSERT INTO fish_ponds (pond_code, pond_name, size_sqm, water_source, fish_species, initial_count, current_count, stocking_date, created_by) 
                    VALUES (:code, :name, :size, :water, :species, :count, :count, CURDATE(), :uid)";
            $stmt = $db->prepare($sql);
            $stmt->execute([
                ':code' => $pond_code, ':name' => $pond_name, ':size' => $size,
                ':water' => $water_source, ':species' => $species, ':count' => $count,
                ':uid' => $_SESSION['user_id']
            ]);
            
            $message = 'Pond added successfully!';
            $messageType = 'success';
            header('Location: FISH_FARMING.PHP?msg=' . urlencode($message) . '&type=' . $messageType);
            exit();
        } catch (Exception $e) {
            $message = 'Error: ' . $e->getMessage();
            $messageType = 'error';
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_feed'])) {
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $message = 'Invalid security token';
        $messageType = 'error';
    } else {
        $pond_id = intval($_POST['pond_id']);
        $feed_type = sanitizeInput($_POST['feed_type']);
        $quantity = floatval($_POST['quantity_kg']);
        $feeding_time = sanitizeInput($_POST['feeding_time']);
        $cost = floatval($_POST['cost_per_kg']);
        
        try {
            $sql = "INSERT INTO fish_feed_records (pond_id, feed_type, quantity_kg, feeding_time, cost_per_kg, ethiopian_date, feed_date, fed_by) 
                    VALUES (:pid, :ftype, :qty, :ftime, :cost, :edate, CURDATE(), :uid)";
            $stmt = $db->prepare($sql);
            $stmt->execute([
                ':pid' => $pond_id, ':ftype' => $feed_type, ':qty' => $quantity,
                ':ftime' => $feeding_time, ':cost' => $cost, ':edate' => $ethiopian_date,
                ':uid' => $_SESSION['user_id']
            ]);
            
            $message = 'Feed record added successfully!';
            $messageType = 'success';
            header('Location: FISH_FARMING.PHP?msg=' . urlencode($message) . '&type=' . $messageType);
            exit();
        } catch (Exception $e) {
            $message = 'Error: ' . $e->getMessage();
            $messageType = 'error';
        }
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
    <title>Fish Farming - <?php echo APP_NAME; ?></title>
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
        .summary-cards { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin-bottom: 25px; }
        .summary-card {
            background: var(--glass-bg); backdrop-filter: blur(10px); border-radius: 15px;
            padding: 25px; box-shadow: var(--glass-shadow); text-align: center;
            border: 1px solid rgba(255,255,255,0.2);
        }
        .summary-card h3 { font-size: 32px; color: var(--info); margin-bottom: 5px; }
        .summary-card p { font-size: 14px; color: #666; }
        .section-card {
            background: var(--glass-bg); backdrop-filter: blur(10px); border-radius: 20px;
            padding: 25px; box-shadow: var(--glass-shadow); border: 1px solid rgba(255,255,255,0.2);
            margin-bottom: 25px;
        }
        .section-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
        .form-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: 500; font-size: 13px; }
        .form-group input, .form-group select {
            width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 8px;
            font-size: 14px; font-family: inherit;
        }
        .form-group input:focus, .form-group select:focus {
            outline: none; border-color: #667eea; box-shadow: 0 0 0 3px rgba(102,126,234,0.1);
        }
        .btn { padding: 10px 25px; border: none; border-radius: 25px; cursor: pointer; font-weight: 500; transition: all 0.3s; font-size: 14px; }
        .btn-primary { background: linear-gradient(135deg, #667eea, #764ba2); color: white; }
        .btn:hover { transform: translateY(-2px); box-shadow: 0 5px 15px rgba(0,0,0,0.2); }
        .message { padding: 12px 20px; border-radius: 10px; margin-bottom: 20px; font-weight: 500; }
        .message.success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .message.error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .pond-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 20px; }
        .pond-card {
            background: var(--light); border-radius: 15px; padding: 20px;
            border: 1px solid #eee; transition: all 0.3s;
        }
        .pond-card:hover { transform: translateY(-3px); box-shadow: 0 8px 25px rgba(0,0,0,0.1); }
        .pond-card h4 { margin-bottom: 10px; color: var(--primary); }
        .pond-card .stats { display: flex; justify-content: space-between; margin-top: 15px; }
        .pond-card .stat { text-align: center; }
        .pond-card .stat strong { display: block; font-size: 18px; color: var(--info); }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #eee; font-size: 13px; }
        th { background: #f8f9fa; font-weight: 600; color: var(--primary); }
        tr:hover { background: #f8f9fa; }
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
                <li><a href="FISH_FARMING.PHP" class="active"><i class="fas fa-fish"></i> Fish Farming</a></li>
                <li><a href="LOGOUT.PHP"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>
        </aside>

        <main class="main-content">
            <div class="top-bar">
                <div class="top-bar-left">
                    <button class="hamburger" onclick="toggleSidebar()"><i class="fas fa-bars"></i></button>
                    <h2>Fish Farming Management</h2>
                    <div class="ethiopian-date"><i class="fas fa-calendar-alt"></i> <?php echo $ethiopian_date; ?></div>
                </div>
                <div><strong><?php echo $_SESSION['full_name']; ?></strong></div>
            </div>

            <?php if ($message): ?>
            <div class="message <?php echo $messageType; ?>"><?php echo htmlspecialchars($message); ?></div>
            <?php endif; ?>

            <div class="summary-cards">
                <div class="summary-card">
                    <h3><?php echo count($ponds); ?></h3>
                    <p>Active Ponds</p>
                </div>
                <div class="summary-card">
                    <h3><?php echo number_format($totalFish); ?></h3>
                    <p>Total Fish</p>
                </div>
            </div>

            <div class="section-card">
                <div class="section-header"><h3>Add New Pond</h3></div>
                <form method="POST" action="">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                    <input type="hidden" name="add_pond" value="1">
                    <div class="form-grid">
                        <div class="form-group">
                            <label>Pond Code</label>
                            <input type="text" name="pond_code" required placeholder="e.g., POND-001">
                        </div>
                        <div class="form-group">
                            <label>Pond Name</label>
                            <input type="text" name="pond_name" required placeholder="Pond name">
                        </div>
                        <div class="form-group">
                            <label>Size (sqm)</label>
                            <input type="number" name="size_sqm" step="0.01" required>
                        </div>
                        <div class="form-group">
                            <label>Water Source</label>
                            <input type="text" name="water_source" required placeholder="e.g., River">
                        </div>
                        <div class="form-group">
                            <label>Fish Species</label>
                            <input type="text" name="fish_species" required placeholder="e.g., Nile Tilapia">
                        </div>
                        <div class="form-group">
                            <label>Initial Count</label>
                            <input type="number" name="initial_count" required min="1">
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-plus"></i> Add Pond</button>
                </form>
            </div>

            <div class="section-card">
                <div class="section-header"><h3>Record Feed</h3></div>
                <form method="POST" action="">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                    <input type="hidden" name="add_feed" value="1">
                    <div class="form-grid">
                        <div class="form-group">
                            <label>Pond</label>
                            <select name="pond_id" required>
                                <option value="">Select Pond</option>
                                <?php foreach($ponds as $pond): ?>
                                <option value="<?php echo $pond['id']; ?>"><?php echo htmlspecialchars($pond['pond_code'] . ' - ' . $pond['pond_name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Feed Type</label>
                            <input type="text" name="feed_type" required placeholder="Feed type">
                        </div>
                        <div class="form-group">
                            <label>Quantity (KG)</label>
                            <input type="number" name="quantity_kg" step="0.01" required>
                        </div>
                        <div class="form-group">
                            <label>Feeding Time</label>
                            <select name="feeding_time" required>
                                <option value="morning">Morning</option>
                                <option value="afternoon">Afternoon</option>
                                <option value="evening">Evening</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Cost per KG (ETB)</label>
                            <input type="number" name="cost_per_kg" step="0.01" required>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Record Feed</button>
                </form>
            </div>

            <div class="section-card">
                <div class="section-header"><h3>Fish Ponds</h3></div>
                <div class="pond-grid">
                    <?php foreach($ponds as $pond): ?>
                    <div class="pond-card">
                        <h4><i class="fas fa-water"></i> <?php echo htmlspecialchars($pond['pond_code']); ?></h4>
                        <p><?php echo htmlspecialchars($pond['pond_name']); ?></p>
                        <p>Species: <strong><?php echo htmlspecialchars($pond['fish_species']); ?></strong></p>
                        <p>Size: <?php echo number_format($pond['size_sqm'], 1); ?> sqm</p>
                        <div class="stats">
                            <div class="stat">
                                <strong><?php echo $pond['current_count']; ?></strong>
                                <small>Current</small>
                            </div>
                            <div class="stat">
                                <strong><?php echo $pond['initial_count']; ?></strong>
                                <small>Initial</small>
                            </div>
                            <div class="stat">
                                <strong><?php echo number_format($pond['average_weight_g'], 1); ?>g</strong>
                                <small>Avg Weight</small>
                            </div>
                            <div class="stat">
                                <strong><?php echo $pond['water_quality_status']; ?></strong>
                                <small>Water</small>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </main>
    </div>

    <script>
        function toggleSidebar() {
            document.getElementById('sidebar').classList.toggle('open');
        }
    </script>
</body>
</html>