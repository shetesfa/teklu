<?php
require_once 'CONFIG/CONFIG.PHP';
require_once 'INCLUDES/AUTH.PHP';

requirePermission('poultry');

$lang = getUserLanguage();
$ethiopian_date = getCurrentEthiopianDate();

// Get batches
$sql = "SELECT id, batch_code, breed FROM poultry_batches WHERE status = 'active' ORDER BY batch_code";
$stmt = $db->query($sql);
$batches = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get egg production records
$sql = "SELECT ep.*, pb.batch_code, pb.breed 
        FROM egg_production ep 
        JOIN poultry_batches pb ON ep.batch_id = pb.id 
        ORDER BY ep.collection_date DESC, ep.created_at DESC LIMIT 50";
$stmt = $db->query($sql);
$egg_records = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get today's total
$sql = "SELECT COALESCE(SUM(good_eggs), 0) as total FROM egg_production WHERE collection_date = CURDATE()";
$stmt = $db->query($sql);
$today_total = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

// Get monthly total
$sql = "SELECT COALESCE(SUM(good_eggs), 0) as total FROM egg_production 
        WHERE MONTH(collection_date) = MONTH(CURDATE()) AND YEAR(collection_date) = YEAR(CURDATE())";
$stmt = $db->query($sql);
$monthly_total = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_eggs'])) {
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $message = 'Invalid security token';
        $messageType = 'error';
    } else {
        $batch_id = intval($_POST['batch_id']);
        $total_eggs = intval($_POST['total_eggs']);
        $broken_eggs = intval($_POST['broken_eggs']);
        
        try {
            $sql = "INSERT INTO egg_production (batch_id, total_eggs, broken_eggs, ethiopian_date, collection_date, collected_by) 
                    VALUES (:bid, :total, :broken, :edate, CURDATE(), :uid)";
            $stmt = $db->prepare($sql);
            $stmt->execute([
                ':bid' => $batch_id, ':total' => $total_eggs, ':broken' => $broken_eggs,
                ':edate' => $ethiopian_date, ':uid' => $_SESSION['user_id']
            ]);
            
            $message = 'Egg record added successfully!';
            $messageType = 'success';
            header('Location: EGG_PRODUCTION.PHP?msg=' . urlencode($message) . '&type=' . $messageType);
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
    <title>Egg Production - <?php echo APP_NAME; ?></title>
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
                <li><a href="POULTRY.PHP"><i class="fas fa-kiwi-bird"></i> Poultry</a></li>
                <li><a href="EGG_PRODUCTION.PHP" class="active"><i class="fas fa-egg"></i> Egg Production</a></li>
                <li><a href="LOGOUT.PHP"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>
        </aside>

        <main class="main-content">
            <div class="top-bar">
                <div class="top-bar-left">
                    <button class="hamburger" onclick="toggleSidebar()"><i class="fas fa-bars"></i></button>
                    <h2>Egg Production</h2>
                    <div class="ethiopian-date"><i class="fas fa-calendar-alt"></i> <?php echo $ethiopian_date; ?></div>
                </div>
                <div><strong><?php echo $_SESSION['full_name']; ?></strong></div>
            </div>

            <?php if ($message): ?>
            <div class="message <?php echo $messageType; ?>"><?php echo htmlspecialchars($message); ?></div>
            <?php endif; ?>

            <div class="summary-cards">
                <div class="summary-card">
                    <h3><?php echo number_format($today_total); ?></h3>
                    <p>Today's Good Eggs</p>
                </div>
                <div class="summary-card">
                    <h3><?php echo number_format($monthly_total); ?></h3>
                    <p>Monthly Eggs</p>
                </div>
            </div>

            <div class="section-card">
                <div class="section-header"><h3>Record Egg Collection</h3></div>
                <form method="POST" action="">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                    <input type="hidden" name="add_eggs" value="1">
                    <div class="form-grid">
                        <div class="form-group">
                            <label>Batch</label>
                            <select name="batch_id" required>
                                <option value="">Select Batch</option>
                                <?php foreach($batches as $batch): ?>
                                <option value="<?php echo $batch['id']; ?>"><?php echo htmlspecialchars($batch['batch_code'] . ' - ' . $batch['breed']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Total Eggs Collected</label>
                            <input type="number" name="total_eggs" required min="0">
                        </div>
                        <div class="form-group">
                            <label>Broken Eggs</label>
                            <input type="number" name="broken_eggs" value="0" min="0">
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Record</button>
                </form>
            </div>

            <div class="section-card">
                <div class="section-header"><h3>Egg Production History</h3></div>
                <div style="overflow-x: auto;">
                    <table>
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Batch</th>
                                <th>Total</th>
                                <th>Broken</th>
                                <th>Good</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($egg_records as $record): ?>
                            <tr>
                                <td><?php echo $record['collection_date']; ?></td>
                                <td><?php echo htmlspecialchars($record['batch_code']); ?></td>
                                <td><?php echo $record['total_eggs']; ?></td>
                                <td style="color: var(--danger);"><?php echo $record['broken_eggs']; ?></td>
                                <td><strong style="color: var(--success);"><?php echo $record['good_eggs']; ?></strong></td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if(count($egg_records) == 0): ?>
                            <tr><td colspan="5" style="text-align: center;">No records found</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
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