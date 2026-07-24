<?php
require_once 'CONFIG/CONFIG.PHP';
require_once 'INCLUDES/AUTH.PHP';

requirePermission('livestock');

$lang = getUserLanguage();
$ethiopian_date = getCurrentEthiopianDate();

// Get today's milk records
$sql = "SELECT mr.*, a.animal_id, a.name as animal_name 
        FROM milk_records mr 
        JOIN animals a ON mr.animal_id = a.id 
        WHERE mr.record_date = CURDATE() 
        ORDER BY mr.created_at DESC";
$stmt = $db->query($sql);
$today_records = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get today's total
$sql = "SELECT COALESCE(SUM(total_milk), 0) as total FROM milk_records WHERE record_date = CURDATE()";
$stmt = $db->query($sql);
$today_total = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

// Get monthly data
$sql = "SELECT record_date, SUM(total_milk) as daily_total 
        FROM milk_records 
        WHERE MONTH(record_date) = MONTH(CURDATE()) AND YEAR(record_date) = YEAR(CURDATE())
        GROUP BY record_date 
        ORDER BY record_date";
$stmt = $db->query($sql);
$monthly_data = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get active female animals
$sql = "SELECT id, animal_id, name FROM animals WHERE gender = 'female' AND status IN ('active', 'pregnant') ORDER BY animal_id";
$stmt = $db->query($sql);
$active_females = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Handle add milk record
$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_milk'])) {
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $message = 'Invalid security token';
        $messageType = 'error';
    } else {
        $animal_id = intval($_POST['animal_id']);
        $morning = floatval($_POST['morning_milk']);
        $evening = floatval($_POST['evening_milk']);
        
        // CALCULATE total_milk before insert
        $total_milk = $morning + $evening;
        
        try {
            $sql = "INSERT INTO milk_records (animal_id, morning_milk, evening_milk, total_milk, ethiopian_date, record_date, recorded_by) 
                    VALUES (:aid, :morning, :evening, :total, :edate, CURDATE(), :uid)";
            $stmt = $db->prepare($sql);
            $stmt->execute([
                ':aid' => $animal_id,
                ':morning' => $morning,
                ':evening' => $evening,
                ':total' => $total_milk,
                ':edate' => $ethiopian_date,
                ':uid' => $_SESSION['user_id']
            ]);
            
            logAudit($db, $_SESSION['user_id'], 'create', 'milk_records', $db->lastInsertId(), null, ['animal_id' => $animal_id, 'total_milk' => $total_milk]);
            
            $message = 'Milk record added successfully! Total: ' . number_format($total_milk, 1) . ' Liters';
            $messageType = 'success';
            header('Location: MILK_PRODUCTION.PHP?msg=' . urlencode($message) . '&type=' . $messageType);
            exit();
        } catch (Exception $e) {
            if ($e->getCode() == 23000) {
                $message = 'Record already exists for this animal today';
            } else {
                $message = 'Error: ' . $e->getMessage();
            }
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
    <title>Milk Production - <?php echo APP_NAME; ?></title>
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
        .form-row { display: flex; gap: 15px; flex-wrap: wrap; align-items: flex-end; }
        .form-group { margin-bottom: 15px; flex: 1; min-width: 150px; }
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
        .formula-info {
            background: #f0f8ff; padding: 10px 15px; border-radius: 10px; margin-bottom: 15px;
            font-size: 13px; color: var(--info); border-left: 4px solid var(--info);
        }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #eee; font-size: 13px; }
        th { background: #f8f9fa; font-weight: 600; color: var(--primary); }
        tr:hover { background: #f8f9fa; }
        .highlight { background: #fff3cd; font-weight: bold; }
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
                <li><a href="ANIMALS.PHP"><i class="fas fa-cow"></i> Animals</a></li>
                <li><a href="MILK_PRODUCTION.PHP" class="active"><i class="fas fa-flask"></i> Milk Production</a></li>
                <li><a href="FEED_MANAGEMENT.PHP"><i class="fas fa-seedling"></i> Feed</a></li>
                <li><a href="LOGOUT.PHP"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>
        </aside>

        <main class="main-content">
            <div class="top-bar">
                <div class="top-bar-left">
                    <button class="hamburger" onclick="toggleSidebar()"><i class="fas fa-bars"></i></button>
                    <h2>Milk Production</h2>
                    <div class="ethiopian-date"><i class="fas fa-calendar-alt"></i> <?php echo $ethiopian_date; ?></div>
                </div>
                <div><strong><?php echo $_SESSION['full_name']; ?></strong></div>
            </div>

            <?php if ($message): ?>
            <div class="message <?php echo $messageType; ?>"><?php echo htmlspecialchars($message); ?></div>
            <?php endif; ?>

            <div class="summary-cards">
                <div class="summary-card">
                    <h3><?php echo number_format($today_total, 1); ?> L</h3>
                    <p>Today's Total Milk</p>
                </div>
                <div class="summary-card">
                    <h3><?php echo count($today_records); ?></h3>
                    <p>Cows Milked Today</p>
                </div>
                <div class="summary-card">
                    <h3><?php echo count($active_females); ?></h3>
                    <p>Active Female Cows</p>
                </div>
            </div>

            <div class="section-card">
                <div class="section-header"><h3>Record Milk Production</h3></div>
                <div class="formula-info">
                    <i class="fas fa-calculator"></i> 
                    <strong>Formula:</strong> Total Milk = Morning Milk + Evening Milk (calculated automatically)
                </div>
                <form method="POST" action="" id="milkForm" onsubmit="return calculateTotal()">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                    <input type="hidden" name="add_milk" value="1">
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label>Animal</label>
                            <select name="animal_id" required>
                                <option value="">Select Cow</option>
                                <?php foreach($active_females as $cow): ?>
                                <option value="<?php echo $cow['id']; ?>">
                                    <?php echo htmlspecialchars($cow['animal_id'] . ' - ' . $cow['name']); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Morning Milk (Liters)</label>
                            <input type="number" id="morning_milk" name="morning_milk" step="0.01" min="0" value="0" required onchange="calculateTotal()" onkeyup="calculateTotal()">
                        </div>
                        <div class="form-group">
                            <label>Evening Milk (Liters)</label>
                            <input type="number" id="evening_milk" name="evening_milk" step="0.01" min="0" value="0" required onchange="calculateTotal()" onkeyup="calculateTotal()">
                        </div>
                        <div class="form-group">
                            <label>Total Milk (Liters) - Auto Calculated</label>
                            <input type="number" id="total_milk_display" step="0.01" readonly 
                                   style="background: #f0f8ff; font-weight: bold; font-size: 18px; color: var(--info);">
                        </div>
                        <div class="form-group">
                            <button type="submit" class="btn btn-primary" style="width: 100%;">
                                <i class="fas fa-save"></i> Record Milk
                            </button>
                        </div>
                    </div>
                </form>
            </div>

            <div class="section-card">
                <div class="section-header"><h3>Today's Records</h3></div>
                <div style="overflow-x: auto;">
                    <table>
                        <thead>
                            <tr>
                                <th>Animal ID</th>
                                <th>Name</th>
                                <th>Morning (L)</th>
                                <th>Evening (L)</th>
                                <th>Total (L)</th>
                                <th>Quality</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($today_records as $record): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($record['animal_id']); ?></strong></td>
                                <td><?php echo htmlspecialchars($record['animal_name']); ?></td>
                                <td><?php echo number_format($record['morning_milk'], 1); ?></td>
                                <td><?php echo number_format($record['evening_milk'], 1); ?></td>
                                <td class="highlight"><?php echo number_format($record['total_milk'], 1); ?></td>
                                <td><span class="badge" style="background: #d4edda; color: #155724; padding: 4px 10px; border-radius: 12px;"><?php echo $record['milk_quality']; ?></span></td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if(count($today_records) == 0): ?>
                            <tr><td colspan="6" style="text-align: center;">No records for today</td></tr>
                            <?php endif; ?>
                        </tbody>
                        <?php if(count($today_records) > 0): ?>
                        <tfoot>
                            <tr style="font-weight: bold; background: #f8f9fa;">
                                <td colspan="4">Total</td>
                                <td colspan="2"><?php echo number_format($today_total, 1); ?> Liters</td>
                            </tr>
                        </tfoot>
                        <?php endif; ?>
                    </table>
                </div>
            </div>

            <div class="section-card">
                <div class="section-header"><h3>Monthly Production Chart</h3></div>
                <div style="width: 100%; min-height: 300px;" id="milkChart">
                    <?php if(count($monthly_data) > 0): ?>
                    <div style="display: flex; align-items: flex-end; height: 250px; gap: 3px; padding: 20px; overflow-x: auto;">
                        <?php 
                        $maxMilk = max(array_column($monthly_data, 'daily_total'));
                        foreach($monthly_data as $day): 
                            $height = $maxMilk > 0 ? ($day['daily_total'] / $maxMilk) * 100 : 0;
                        ?>
                        <div style="flex: 1; min-width: 25px; display: flex; flex-direction: column; align-items: center;">
                            <div style="width: 100%; background: linear-gradient(135deg, #667eea, #764ba2); 
                                        height: <?php echo $height; ?>%; border-radius: 4px 4px 0 0; 
                                        min-height: 2px; position: relative;"
                                 title="<?php echo $day['record_date']; ?>: <?php echo number_format($day['daily_total'], 1); ?> L">
                            </div>
                            <small style="font-size: 9px; margin-top: 5px; transform: rotate(-45deg); white-space: nowrap;">
                                <?php echo date('d', strtotime($day['record_date'])); ?>
                            </small>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php else: ?>
                    <p style="text-align: center; padding: 40px; color: #666;">No data for this month</p>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>

    <script>
        function toggleSidebar() {
            document.getElementById('sidebar').classList.toggle('open');
        }

        function calculateTotal() {
            var morning = parseFloat(document.getElementById('morning_milk').value) || 0;
            var evening = parseFloat(document.getElementById('evening_milk').value) || 0;
            var total = morning + evening;
            document.getElementById('total_milk_display').value = total.toFixed(2);
            return true;
        }

        // Calculate on page load
        window.onload = function() {
            calculateTotal();
        };
    </script>
</body>
</html>