<?php
require_once 'CONFIG/CONFIG.PHP';
require_once 'INCLUDES/AUTH.PHP';

requirePermission('irrigation');

$lang = getUserLanguage();
$ethiopian_date = getCurrentEthiopianDate();

$sql = "SELECT * FROM irrigation_fields ORDER BY created_at DESC";
$stmt = $db->query($sql);
$fields = $stmt->fetchAll(PDO::FETCH_ASSOC);

$sql = "SELECT COALESCE(SUM(size_hectares), 0) as total FROM irrigation_fields WHERE status != 'fallow'";
$stmt = $db->query($sql);
$totalHectares = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_field'])) {
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $message = 'Invalid security token';
        $messageType = 'error';
    } else {
        $field_code = sanitizeInput($_POST['field_code']);
        $field_name = sanitizeInput($_POST['field_name']);
        $size = floatval($_POST['size_hectares']);
        $crop_type = sanitizeInput($_POST['crop_type']);
        $planting_date = sanitizeInput($_POST['planting_date']);
        $method = sanitizeInput($_POST['irrigation_method']);
        
        try {
            $sql = "INSERT INTO irrigation_fields (field_code, field_name, size_hectares, crop_type, planting_date, irrigation_method, status, created_by) 
                    VALUES (:code, :name, :size, :crop, :pdate, :method, 'planted', :uid)";
            $stmt = $db->prepare($sql);
            $stmt->execute([
                ':code' => $field_code, ':name' => $field_name, ':size' => $size,
                ':crop' => $crop_type, ':pdate' => $planting_date, ':method' => $method,
                ':uid' => $_SESSION['user_id']
            ]);
            
            $message = 'Field added successfully!';
            $messageType = 'success';
            header('Location: IRRIGATION.PHP?msg=' . urlencode($message) . '&type=' . $messageType);
            exit();
        } catch (Exception $e) {
            $message = 'Error: ' . $e->getMessage();
            $messageType = 'error';
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_water'])) {
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $message = 'Invalid security token';
        $messageType = 'error';
    } else {
        $field_id = intval($_POST['field_id']);
        $water_amount = floatval($_POST['water_amount']);
        $hours = floatval($_POST['usage_hours']);
        $cost = floatval($_POST['pump_cost']);
        
        try {
            $sql = "INSERT INTO water_usage (field_id, water_amount_cubic, usage_hours, pump_electricity_cost, ethiopian_date, usage_date, operator_id) 
                    VALUES (:fid, :amount, :hours, :cost, :edate, CURDATE(), :uid)";
            $stmt = $db->prepare($sql);
            $stmt->execute([
                ':fid' => $field_id, ':amount' => $water_amount, ':hours' => $hours,
                ':cost' => $cost, ':edate' => $ethiopian_date, ':uid' => $_SESSION['user_id']
            ]);
            
            $message = 'Water usage recorded!';
            $messageType = 'success';
            header('Location: IRRIGATION.PHP?msg=' . urlencode($message) . '&type=' . $messageType);
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
    <title>Irrigation - <?php echo APP_NAME; ?></title>
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
        .field-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 20px; }
        .field-card {
            background: var(--light); border-radius: 15px; padding: 20px;
            border: 1px solid #eee; transition: all 0.3s;
        }
        .field-card:hover { transform: translateY(-3px); box-shadow: 0 8px 25px rgba(0,0,0,0.1); }
        .field-card h4 { margin-bottom: 10px; color: var(--primary); }
        .field-card .status-badge {
            display: inline-block; padding: 4px 12px; border-radius: 15px;
            font-size: 12px; font-weight: 500;
        }
        .status-preparation { background: #fff3cd; color: #856404; }
        .status-planted { background: #cce5ff; color: #004085; }
        .status-growing { background: #d4edda; color: #155724; }
        .status-harvesting { background: #ffeaa7; color: #6c5b00; }
        .status-harvested { background: #e2e3e5; color: #383d41; }
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
                <li><a href="IRRIGATION.PHP" class="active"><i class="fas fa-water"></i> Irrigation</a></li>
                <li><a href="LOGOUT.PHP"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>
        </aside>

        <main class="main-content">
            <div class="top-bar">
                <div class="top-bar-left">
                    <button class="hamburger" onclick="toggleSidebar()"><i class="fas fa-bars"></i></button>
                    <h2>Irrigation Management</h2>
                    <div class="ethiopian-date"><i class="fas fa-calendar-alt"></i> <?php echo $ethiopian_date; ?></div>
                </div>
                <div><strong><?php echo $_SESSION['full_name']; ?></strong></div>
            </div>

            <?php if ($message): ?>
            <div class="message <?php echo $messageType; ?>"><?php echo htmlspecialchars($message); ?></div>
            <?php endif; ?>

            <div class="summary-cards">
                <div class="summary-card">
                    <h3><?php echo count($fields); ?></h3>
                    <p>Active Fields</p>
                </div>
                <div class="summary-card">
                    <h3><?php echo number_format($totalHectares, 1); ?> ha</h3>
                    <p>Total Area</p>
                </div>
            </div>

            <div class="section-card">
                <div class="section-header"><h3>Add New Field</h3></div>
                <form method="POST" action="">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                    <input type="hidden" name="add_field" value="1">
                    <div class="form-grid">
                        <div class="form-group">
                            <label>Field Code</label>
                            <input type="text" name="field_code" required placeholder="e.g., FLD-001">
                        </div>
                        <div class="form-group">
                            <label>Field Name</label>
                            <input type="text" name="field_name" required placeholder="Field name">
                        </div>
                        <div class="form-group">
                            <label>Size (Hectares)</label>
                            <input type="number" name="size_hectares" step="0.01" required>
                        </div>
                        <div class="form-group">
                            <label>Crop Type</label>
                            <input type="text" name="crop_type" required placeholder="e.g., Tomato">
                        </div>
                        <div class="form-group">
                            <label>Planting Date</label>
                            <input type="date" name="planting_date" required>
                        </div>
                        <div class="form-group">
                            <label>Irrigation Method</label>
                            <select name="irrigation_method" required>
                                <option value="">Select</option>
                                <option value="drip">Drip</option>
                                <option value="sprinkler">Sprinkler</option>
                                <option value="flood">Flood</option>
                                <option value="furrow">Furrow</option>
                                <option value="pivot">Pivot</option>
                            </select>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-plus"></i> Add Field</button>
                </form>
            </div>

            <div class="section-card">
                <div class="section-header"><h3>Record Water Usage</h3></div>
                <form method="POST" action="">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                    <input type="hidden" name="add_water" value="1">
                    <div class="form-grid">
                        <div class="form-group">
                            <label>Field</label>
                            <select name="field_id" required>
                                <option value="">Select Field</option>
                                <?php foreach($fields as $field): ?>
                                <option value="<?php echo $field['id']; ?>"><?php echo htmlspecialchars($field['field_code'] . ' - ' . $field['field_name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Water Amount (Cubic Meters)</label>
                            <input type="number" name="water_amount" step="0.01" required>
                        </div>
                        <div class="form-group">
                            <label>Usage Hours</label>
                            <input type="number" name="usage_hours" step="0.1" required>
                        </div>
                        <div class="form-group">
                            <label>Electricity Cost (ETB)</label>
                            <input type="number" name="pump_cost" step="0.01" required>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Record Usage</button>
                </form>
            </div>

            <div class="section-card">
                <div class="section-header"><h3>Irrigation Fields</h3></div>
                <div class="field-grid">
                    <?php foreach($fields as $field): ?>
                    <div class="field-card">
                        <h4><i class="fas fa-seedling"></i> <?php echo htmlspecialchars($field['field_code']); ?></h4>
                        <p><?php echo htmlspecialchars($field['field_name']); ?></p>
                        <p>Crop: <strong><?php echo htmlspecialchars($field['crop_type']); ?></strong></p>
                        <p>Size: <?php echo number_format($field['size_hectares'], 1); ?> hectares</p>
                        <p>Method: <strong><?php echo $field['irrigation_method']; ?></strong></p>
                        <p>Planted: <?php echo $field['planting_date']; ?></p>
                        <p>
                            Status: 
                            <span class="status-badge status-<?php echo $field['status']; ?>">
                                <?php echo $field['status']; ?>
                            </span>
                        </p>
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