<?php
require_once 'CONFIG/CONFIG.PHP';
require_once 'INCLUDES/AUTH.PHP';

requireLogin();

$user_id = $_SESSION['user_id'];
$role_id = $_SESSION['role_id'];
$lang = getUserLanguage();
$ethiopian_date = getCurrentEthiopianDate();

$stats = [];

// Only show cards based on role permissions
if (checkPermission('irrigation')) {
    $sql = "SELECT 
        COALESCE((SELECT SUM(total_sales) FROM harvest_records WHERE DATE(harvest_date) = CURDATE()), 0) as today_income,
        COALESCE((SELECT SUM(total_cost) FROM fertilizer_records WHERE DATE(application_date) = CURDATE()), 0) as today_expense,
        COALESCE((SELECT SUM(total_sales) FROM harvest_records WHERE MONTH(harvest_date) = MONTH(CURDATE()) AND YEAR(harvest_date) = YEAR(CURDATE())), 0) as monthly_income,
        COALESCE((SELECT SUM(total_cost) FROM fertilizer_records WHERE MONTH(application_date) = MONTH(CURDATE()) AND YEAR(application_date) = YEAR(CURDATE())), 0) as monthly_expense
    FROM DUAL";
    $stmt = $db->query($sql);
    $stats['irrigation'] = $stmt->fetch(PDO::FETCH_ASSOC);
}

if (checkPermission('poultry')) {
    $sql = "SELECT 
        COALESCE((SELECT SUM(total_amount) FROM poultry_sales WHERE DATE(sale_date) = CURDATE()), 0) as today_income,
        COALESCE((SELECT SUM(total_cost) FROM poultry_feed_records WHERE DATE(feed_date) = CURDATE()), 0) as today_expense,
        COALESCE((SELECT SUM(total_amount) FROM poultry_sales WHERE MONTH(sale_date) = MONTH(CURDATE()) AND YEAR(sale_date) = YEAR(CURDATE())), 0) as monthly_income,
        COALESCE((SELECT SUM(total_cost) FROM poultry_feed_records WHERE MONTH(feed_date) = MONTH(CURDATE()) AND YEAR(feed_date) = YEAR(CURDATE())), 0) as monthly_expense
    FROM DUAL";
    $stmt = $db->query($sql);
    $stats['poultry'] = $stmt->fetch(PDO::FETCH_ASSOC);
}

if (checkPermission('livestock')) {
    $sql = "SELECT 
        COALESCE((SELECT SUM(total_amount) FROM milk_sales WHERE DATE(sale_date) = CURDATE()), 0) as today_income,
        COALESCE((SELECT SUM(fr.quantity_kg * fi.unit_price) FROM feed_records fr JOIN feed_inventory fi ON fr.feed_inventory_id = fi.id WHERE DATE(fr.distribution_date) = CURDATE()), 0) as today_expense,
        COALESCE((SELECT SUM(total_amount) FROM milk_sales WHERE MONTH(sale_date) = MONTH(CURDATE()) AND YEAR(sale_date) = YEAR(CURDATE())), 0) as monthly_income,
        COALESCE((SELECT SUM(fr.quantity_kg * fi.unit_price) FROM feed_records fr JOIN feed_inventory fi ON fr.feed_inventory_id = fi.id WHERE MONTH(fr.distribution_date) = MONTH(CURDATE()) AND YEAR(fr.distribution_date) = YEAR(CURDATE())), 0) as monthly_expense
    FROM DUAL";
    $stmt = $db->query($sql);
    $stats['cattle'] = $stmt->fetch(PDO::FETCH_ASSOC);
}

if (checkPermission('fish')) {
    $sql = "SELECT 
        COALESCE((SELECT SUM(total_sales) FROM fish_harvest WHERE DATE(harvest_date) = CURDATE()), 0) as today_income,
        COALESCE((SELECT SUM(total_cost) FROM fish_feed_records WHERE DATE(feed_date) = CURDATE()), 0) as today_expense,
        COALESCE((SELECT SUM(total_sales) FROM fish_harvest WHERE MONTH(harvest_date) = MONTH(CURDATE()) AND YEAR(harvest_date) = YEAR(CURDATE())), 0) as monthly_income,
        COALESCE((SELECT SUM(total_cost) FROM fish_feed_records WHERE MONTH(feed_date) = MONTH(CURDATE()) AND YEAR(feed_date) = YEAR(CURDATE())), 0) as monthly_expense
    FROM DUAL";
    $stmt = $db->query($sql);
    $stats['fish'] = $stmt->fetch(PDO::FETCH_ASSOC);
}

if (checkPermission('finance') || $role_id == 1 || $role_id == 2) {
    $sql = "SELECT 
        COALESCE((SELECT SUM(amount) FROM transactions WHERE transaction_type = 'income' AND DATE(gregorian_date) = CURDATE()), 0) as today_income,
        COALESCE((SELECT SUM(amount) FROM transactions WHERE transaction_type = 'expense' AND DATE(gregorian_date) = CURDATE()), 0) as today_expense,
        COALESCE((SELECT SUM(amount) FROM transactions WHERE transaction_type = 'income' AND MONTH(gregorian_date) = MONTH(CURDATE()) AND YEAR(gregorian_date) = YEAR(CURDATE())), 0) as monthly_income,
        COALESCE((SELECT SUM(amount) FROM transactions WHERE transaction_type = 'expense' AND MONTH(gregorian_date) = MONTH(CURDATE()) AND YEAR(gregorian_date) = YEAR(CURDATE())), 0) as monthly_expense
    FROM DUAL";
    $stmt = $db->query($sql);
    $stats['general'] = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Get alerts only for this user
$sql = "SELECT * FROM notifications WHERE user_id = :uid AND is_read = 0 ORDER BY created_at DESC LIMIT 5";
$stmt = $db->prepare($sql);
$stmt->execute([':uid' => $user_id]);
$alerts = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Role-specific quick stats
$quickStats = [];

if (checkPermission('livestock')) {
    $sql = "SELECT COUNT(*) as total, SUM(CASE WHEN status='active' THEN 1 ELSE 0 END) as active FROM animals";
    $stmt = $db->query($sql);
    $quickStats['animals'] = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $sql = "SELECT COALESCE(SUM(total_milk), 0) as total FROM milk_records WHERE record_date = CURDATE()";
    $stmt = $db->query($sql);
    $quickStats['milk'] = $stmt->fetch(PDO::FETCH_ASSOC);
}

if (checkPermission('poultry')) {
    $sql = "SELECT COALESCE(SUM(current_count), 0) as total FROM poultry_batches WHERE status = 'active'";
    $stmt = $db->query($sql);
    $quickStats['poultry'] = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $sql = "SELECT COALESCE(SUM(good_eggs), 0) as total FROM egg_production WHERE collection_date = CURDATE()";
    $stmt = $db->query($sql);
    $quickStats['eggs'] = $stmt->fetch(PDO::FETCH_ASSOC);
}

if (checkPermission('fish')) {
    $sql = "SELECT COALESCE(SUM(current_count), 0) as total FROM fish_ponds WHERE status = 'active'";
    $stmt = $db->query($sql);
    $quickStats['fish'] = $stmt->fetch(PDO::FETCH_ASSOC);
}

if (checkPermission('irrigation')) {
    $sql = "SELECT COUNT(*) as total FROM irrigation_fields WHERE status NOT IN ('fallow', 'harvested')";
    $stmt = $db->query($sql);
    $quickStats['fields'] = $stmt->fetch(PDO::FETCH_ASSOC);
}

if (checkPermission('finance') || $role_id <= 3) {
    $sql = "SELECT COALESCE(SUM(balance), 0) as total FROM bank_accounts WHERE is_active = 1";
    $stmt = $db->query($sql);
    $quickStats['bank'] = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $sql = "SELECT current_balance FROM cash_register LIMIT 1";
    $stmt = $db->query($sql);
    $quickStats['cash'] = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Get user role name
$sql = "SELECT role_name, role_name_am FROM roles WHERE id = :rid";
$stmt = $db->prepare($sql);
$stmt->execute([':rid' => $role_id]);
$roleInfo = $stmt->fetch(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="<?php echo $lang; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - <?php echo APP_NAME; ?></title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        :root {
            --primary: #2c3e50; --secondary: #3498db; --success: #27ae60;
            --danger: #e74c3c; --warning: #f39c12; --info: #2980b9;
            --light: #ecf0f1; --dark: #2c3e50;
            --glass-bg: rgba(255, 255, 255, 0.95);
            --glass-shadow: 0 8px 32px 0 rgba(31, 38, 135, 0.15);
            --card-radius: 20px;
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
        .sidebar-logo h2 { font-size: 14px; color: var(--primary); margin-top: 10px; }
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
            background: var(--glass-bg); backdrop-filter: blur(10px); border-radius: var(--card-radius);
            padding: 15px 25px; margin-bottom: 25px; display: flex;
            justify-content: space-between; align-items: center; box-shadow: var(--glass-shadow);
            flex-wrap: wrap; gap: 15px;
        }
        .top-bar-left { display: flex; align-items: center; gap: 15px; }
        .hamburger { display: none; font-size: 24px; cursor: pointer; background: none; border: none; color: var(--dark); }
        .ethiopian-date {
            background: linear-gradient(135deg, #667eea, #764ba2); color: white;
            padding: 8px 15px; border-radius: 25px; font-size: 13px;
        }
        .user-info {
            display: flex; align-items: center; gap: 10px;
        }
        .user-avatar {
            width: 40px; height: 40px; border-radius: 50%;
            background: linear-gradient(135deg, var(--secondary), var(--info));
            display: flex; align-items: center; justify-content: center;
            color: white; font-weight: bold;
        }
        .role-badge {
            padding: 5px 15px; border-radius: 25px; font-size: 12px;
            background: linear-gradient(135deg, #667eea, #764ba2); color: white;
        }
        .welcome-text {
            background: var(--glass-bg); backdrop-filter: blur(10px); border-radius: var(--card-radius);
            padding: 25px; box-shadow: var(--glass-shadow); margin-bottom: 25px;
            border: 1px solid rgba(255,255,255,0.2);
        }
        .welcome-text h2 { color: var(--primary); margin-bottom: 5px; }
        .welcome-text p { color: #666; }
        .quick-stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 15px; margin-bottom: 25px; }
        .stat-card {
            background: var(--glass-bg); backdrop-filter: blur(10px); border-radius: 15px;
            padding: 20px; box-shadow: var(--glass-shadow); display: flex; align-items: center; gap: 15px;
            border: 1px solid rgba(255,255,255,0.2);
        }
        .stat-icon {
            width: 50px; height: 50px; border-radius: 12px; display: flex;
            align-items: center; justify-content: center; font-size: 22px; color: white;
        }
        .stat-icon.blue { background: linear-gradient(135deg, #667eea, #764ba2); }
        .stat-icon.green { background: linear-gradient(135deg, #43e97b, #38f9d7); }
        .stat-icon.orange { background: linear-gradient(135deg, #f093fb, #f5576c); }
        .stat-icon.red { background: linear-gradient(135deg, #fa709a, #fee140); }
        .stat-icon.purple { background: linear-gradient(135deg, #a18cd1, #fbc2eb); }
        .stat-info h4 { font-size: 20px; color: var(--dark); }
        .stat-info p { font-size: 12px; color: #666; }
        .dashboard-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(350px, 1fr)); gap: 25px; margin-bottom: 25px; }
        .dashboard-card {
            background: var(--glass-bg); backdrop-filter: blur(10px); border-radius: var(--card-radius);
            padding: 25px; box-shadow: var(--glass-shadow); border: 1px solid rgba(255,255,255,0.2);
            transition: all 0.3s; cursor: pointer;
        }
        .dashboard-card:hover { transform: translateY(-5px); box-shadow: 0 15px 40px rgba(31, 38, 135, 0.25); }
        .card-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
        .card-icon {
            width: 60px; height: 60px; border-radius: 15px; display: flex;
            align-items: center; justify-content: center; font-size: 28px; color: white;
        }
        .card-icon.irrigation { background: linear-gradient(135deg, #667eea, #764ba2); }
        .card-icon.poultry { background: linear-gradient(135deg, #f093fb, #f5576c); }
        .card-icon.cattle { background: linear-gradient(135deg, #4facfe, #00f2fe); }
        .card-icon.fish { background: linear-gradient(135deg, #43e97b, #38f9d7); }
        .card-icon.general { background: linear-gradient(135deg, #fa709a, #fee140); }
        .card-title { font-size: 18px; font-weight: 600; color: var(--dark); }
        .card-stats { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
        .stat-item { padding: 10px; background: var(--light); border-radius: 10px; text-align: center; }
        .stat-label { font-size: 10px; color: #666; margin-bottom: 5px; text-transform: uppercase; }
        .stat-value { font-size: 18px; font-weight: bold; }
        .stat-value.income { color: var(--success); }
        .stat-value.expense { color: var(--danger); }
        .profit-loss {
            grid-column: 1 / -1; padding: 15px; border-radius: 10px;
            text-align: center; font-size: 16px; font-weight: bold; color: white;
        }
        .profit-loss.positive { background: linear-gradient(135deg, var(--success), #2ecc71); }
        .profit-loss.negative { background: linear-gradient(135deg, var(--danger), #c0392b); }
        .alerts-section {
            background: var(--glass-bg); backdrop-filter: blur(10px); border-radius: var(--card-radius);
            padding: 25px; box-shadow: var(--glass-shadow); border: 1px solid rgba(255,255,255,0.2);
        }
        .alerts-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
        .btn { padding: 8px 20px; border: none; border-radius: 25px; cursor: pointer; font-weight: 500; transition: all 0.3s; font-size: 13px; }
        .btn-sm { padding: 6px 15px; font-size: 12px; }
        .btn-primary { background: linear-gradient(135deg, #667eea, #764ba2); color: white; }
        .btn:hover { transform: translateY(-2px); box-shadow: 0 5px 15px rgba(0,0,0,0.2); }
        .alert-item {
            display: flex; align-items: center; padding: 12px; margin-bottom: 8px;
            background: var(--light); border-radius: 10px; border-left: 4px solid var(--warning);
            gap: 12px;
        }
        .alert-icon { font-size: 20px; }
        .alert-content { flex: 1; }
        .alert-title { font-weight: 600; font-size: 13px; color: var(--dark); }
        .alert-time { font-size: 11px; color: #999; }
        @media (max-width: 768px) {
            .sidebar { width: 0; padding: 0; overflow: hidden; }
            .sidebar.open { width: 280px; padding: 20px; }
            .main-content { margin-left: 0; }
            .hamburger { display: block; }
            .dashboard-grid { grid-template-columns: 1fr; }
            .quick-stats { grid-template-columns: 1fr 1fr; }
        }
    </style>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="app-container">
        <!-- Sidebar -->
        <aside class="sidebar" id="sidebar">
            <div class="sidebar-logo">
                <i class="fas fa-leaf"></i>
                <h2><?php echo APP_NAME; ?></h2>
            </div>
            <ul class="sidebar-menu">
                <li><a href="DASHBOARD.PHP" class="active"><i class="fas fa-home"></i> Dashboard</a></li>
                
                <?php if(checkPermission('finance') || $role_id <= 2): ?>
                <li><a href="FINANCE.PHP"><i class="fas fa-money-bill"></i> Finance</a></li>
                <?php endif; ?>
                
                <?php if(checkPermission('livestock') || $role_id <= 3): ?>
                <li><a href="ANIMALS.PHP"><i class="fas fa-cow"></i> Animals</a></li>
                <li><a href="MILK_PRODUCTION.PHP"><i class="fas fa-flask"></i> Milk</a></li>
                <li><a href="FEED_MANAGEMENT.PHP"><i class="fas fa-seedling"></i> Feed</a></li>
                <?php endif; ?>
                
                <?php if(checkPermission('poultry') || $role_id <= 3): ?>
                <li><a href="POULTRY.PHP"><i class="fas fa-kiwi-bird"></i> Poultry</a></li>
                <li><a href="EGG_PRODUCTION.PHP"><i class="fas fa-egg"></i> Eggs</a></li>
                <?php endif; ?>
                
                <?php if(checkPermission('fish') || $role_id <= 3): ?>
                <li><a href="FISH_FARMING.PHP"><i class="fas fa-fish"></i> Fish</a></li>
                <?php endif; ?>
                
                <?php if(checkPermission('irrigation') || $role_id <= 3): ?>
                <li><a href="IRRIGATION.PHP"><i class="fas fa-water"></i> Irrigation</a></li>
                <?php endif; ?>
                
                <?php if(checkPermission('medicine') || $role_id <= 3): ?>
                <li><a href="DOCTOR_MODULE.PHP"><i class="fas fa-stethoscope"></i> Doctor</a></li>
                <?php endif; ?>
                
                <?php if(checkPermission('reports') || $role_id <= 3): ?>
                <li><a href="REPORTS.PHP"><i class="fas fa-chart-bar"></i> Reports</a></li>
                <?php endif; ?>
                
                <?php if($role_id <= 2): ?>
                <li><a href="SETTINGS.PHP"><i class="fas fa-cog"></i> Settings</a></li>
                <li><a href="USERS.PHP"><i class="fas fa-users"></i> Users</a></li>
                <?php endif; ?>
                
                <li><a href="LOGOUT.PHP"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <!-- Top Bar -->
            <div class="top-bar">
                <div class="top-bar-left">
                    <button class="hamburger" onclick="toggleSidebar()"><i class="fas fa-bars"></i></button>
                    <h2>Dashboard</h2>
                    <div class="ethiopian-date"><i class="fas fa-calendar-alt"></i> <?php echo $ethiopian_date; ?></div>
                </div>
                <div class="user-info">
                    <span class="role-badge"><?php echo htmlspecialchars($roleInfo['role_name_am'] ?? $roleInfo['role_name']); ?></span>
                    <div class="user-avatar"><?php echo substr($_SESSION['full_name'], 0, 1); ?></div>
                    <div>
                        <strong><?php echo $_SESSION['full_name']; ?></strong>
                        <br><small style="font-size:11px;"><?php echo $_SESSION['username']; ?></small>
                    </div>
                </div>
            </div>

            <!-- Welcome Message -->
            <div class="welcome-text">
                <h2>እንኳን ደህና መጡ / Welcome, <?php echo $_SESSION['full_name']; ?></h2>
                <p>Role: <?php echo htmlspecialchars($roleInfo['role_name_am'] ?? $roleInfo['role_name']); ?> | Ethiopian Date: <?php echo $ethiopian_date; ?></p>
            </div>

            <!-- Quick Stats (Role-Specific) -->
            <div class="quick-stats">
                <?php if(isset($quickStats['animals'])): ?>
                <div class="stat-card">
                    <div class="stat-icon blue"><i class="fas fa-cow"></i></div>
                    <div class="stat-info">
                        <h4><?php echo $quickStats['animals']['active'] ?? 0; ?>/<?php echo $quickStats['animals']['total'] ?? 0; ?></h4>
                        <p>Active Animals</p>
                    </div>
                </div>
                <?php endif; ?>
                
                <?php if(isset($quickStats['milk'])): ?>
                <div class="stat-card">
                    <div class="stat-icon green"><i class="fas fa-flask"></i></div>
                    <div class="stat-info">
                        <h4><?php echo number_format($quickStats['milk']['total'] ?? 0, 1); ?> L</h4>
                        <p>Today's Milk</p>
                    </div>
                </div>
                <?php endif; ?>
                
                <?php if(isset($quickStats['poultry'])): ?>
                <div class="stat-card">
                    <div class="stat-icon orange"><i class="fas fa-kiwi-bird"></i></div>
                    <div class="stat-info">
                        <h4><?php echo number_format($quickStats['poultry']['total'] ?? 0); ?></h4>
                        <p>Poultry Birds</p>
                    </div>
                </div>
                <?php endif; ?>
                
                <?php if(isset($quickStats['eggs'])): ?>
                <div class="stat-card">
                    <div class="stat-icon red"><i class="fas fa-egg"></i></div>
                    <div class="stat-info">
                        <h4><?php echo number_format($quickStats['eggs']['total'] ?? 0); ?></h4>
                        <p>Today's Eggs</p>
                    </div>
                </div>
                <?php endif; ?>
                
                <?php if(isset($quickStats['fish'])): ?>
                <div class="stat-card">
                    <div class="stat-icon purple"><i class="fas fa-fish"></i></div>
                    <div class="stat-info">
                        <h4><?php echo number_format($quickStats['fish']['total'] ?? 0); ?></h4>
                        <p>Fish Stock</p>
                    </div>
                </div>
                <?php endif; ?>
                
                <?php if(isset($quickStats['fields'])): ?>
                <div class="stat-card">
                    <div class="stat-icon blue"><i class="fas fa-water"></i></div>
                    <div class="stat-info">
                        <h4><?php echo $quickStats['fields']['total'] ?? 0; ?></h4>
                        <p>Active Fields</p>
                    </div>
                </div>
                <?php endif; ?>
                
                <?php if(isset($quickStats['bank'])): ?>
                <div class="stat-card">
                    <div class="stat-icon green"><i class="fas fa-university"></i></div>
                    <div class="stat-info">
                        <h4><?php echo formatCurrency($quickStats['bank']['total'] ?? 0); ?></h4>
                        <p>Bank Balance</p>
                    </div>
                </div>
                <?php endif; ?>
                
                <?php if(isset($quickStats['cash'])): ?>
                <div class="stat-card">
                    <div class="stat-icon red"><i class="fas fa-money-bill"></i></div>
                    <div class="stat-info">
                        <h4><?php echo formatCurrency($quickStats['cash']['current_balance'] ?? 0); ?></h4>
                        <p>Cash in Hand</p>
                    </div>
                </div>
                <?php endif; ?>
            </div>

            <!-- Dashboard Cards - Only show permitted modules -->
            <div class="dashboard-grid">
                <?php if(isset($stats['irrigation'])): ?>
                <div class="dashboard-card" onclick="window.location='IRRIGATION.PHP'">
                    <div class="card-header">
                        <div class="card-title">መስኖ / Irrigation</div>
                        <div class="card-icon irrigation"><i class="fas fa-water"></i></div>
                    </div>
                    <div class="card-stats">
                        <div class="stat-item">
                            <div class="stat-label">ዛሬ ገቢ</div>
                            <div class="stat-value income"><?php echo formatCurrency($stats['irrigation']['today_income'] ?? 0); ?></div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-label">ዛሬ ወጪ</div>
                            <div class="stat-value expense"><?php echo formatCurrency($stats['irrigation']['today_expense'] ?? 0); ?></div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-label">ወርሃዊ ገቢ</div>
                            <div class="stat-value income"><?php echo formatCurrency($stats['irrigation']['monthly_income'] ?? 0); ?></div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-label">ወርሃዊ ወጪ</div>
                            <div class="stat-value expense"><?php echo formatCurrency($stats['irrigation']['monthly_expense'] ?? 0); ?></div>
                        </div>
                        <?php 
                        $profit = ($stats['irrigation']['monthly_income'] ?? 0) - ($stats['irrigation']['monthly_expense'] ?? 0);
                        $class = $profit >= 0 ? 'positive' : 'negative';
                        ?>
                        <div class="profit-loss <?php echo $class; ?>">
                            <?php echo ($profit >= 0 ? 'ትርፍ: ' : 'ኪሳራ: ') . formatCurrency(abs($profit)); ?>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <?php if(isset($stats['poultry'])): ?>
                <div class="dashboard-card" onclick="window.location='POULTRY.PHP'">
                    <div class="card-header">
                        <div class="card-title">ዶሮ / Poultry</div>
                        <div class="card-icon poultry"><i class="fas fa-kiwi-bird"></i></div>
                    </div>
                    <div class="card-stats">
                        <div class="stat-item">
                            <div class="stat-label">ዛሬ ገቢ</div>
                            <div class="stat-value income"><?php echo formatCurrency($stats['poultry']['today_income'] ?? 0); ?></div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-label">ዛሬ ወጪ</div>
                            <div class="stat-value expense"><?php echo formatCurrency($stats['poultry']['today_expense'] ?? 0); ?></div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-label">ወርሃዊ ገቢ</div>
                            <div class="stat-value income"><?php echo formatCurrency($stats['poultry']['monthly_income'] ?? 0); ?></div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-label">ወርሃዊ ወጪ</div>
                            <div class="stat-value expense"><?php echo formatCurrency($stats['poultry']['monthly_expense'] ?? 0); ?></div>
                        </div>
                        <?php 
                        $profit = ($stats['poultry']['monthly_income'] ?? 0) - ($stats['poultry']['monthly_expense'] ?? 0);
                        $class = $profit >= 0 ? 'positive' : 'negative';
                        ?>
                        <div class="profit-loss <?php echo $class; ?>">
                            <?php echo ($profit >= 0 ? 'ትርፍ: ' : 'ኪሳራ: ') . formatCurrency(abs($profit)); ?>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <?php if(isset($stats['cattle'])): ?>
                <div class="dashboard-card" onclick="window.location='ANIMALS.PHP'">
                    <div class="card-header">
                        <div class="card-title">ከብት / Cattle</div>
                        <div class="card-icon cattle"><i class="fas fa-cow"></i></div>
                    </div>
                    <div class="card-stats">
                        <div class="stat-item">
                            <div class="stat-label">ዛሬ ገቢ</div>
                            <div class="stat-value income"><?php echo formatCurrency($stats['cattle']['today_income'] ?? 0); ?></div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-label">ዛሬ ወጪ</div>
                            <div class="stat-value expense"><?php echo formatCurrency($stats['cattle']['today_expense'] ?? 0); ?></div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-label">ወርሃዊ ገቢ</div>
                            <div class="stat-value income"><?php echo formatCurrency($stats['cattle']['monthly_income'] ?? 0); ?></div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-label">ወርሃዊ ወጪ</div>
                            <div class="stat-value expense"><?php echo formatCurrency($stats['cattle']['monthly_expense'] ?? 0); ?></div>
                        </div>
                        <?php 
                        $profit = ($stats['cattle']['monthly_income'] ?? 0) - ($stats['cattle']['monthly_expense'] ?? 0);
                        $class = $profit >= 0 ? 'positive' : 'negative';
                        ?>
                        <div class="profit-loss <?php echo $class; ?>">
                            <?php echo ($profit >= 0 ? 'ትርፍ: ' : 'ኪሳራ: ') . formatCurrency(abs($profit)); ?>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <?php if(isset($stats['fish'])): ?>
                <div class="dashboard-card" onclick="window.location='FISH_FARMING.PHP'">
                    <div class="card-header">
                        <div class="card-title">አሳ / Fish</div>
                        <div class="card-icon fish"><i class="fas fa-fish"></i></div>
                    </div>
                    <div class="card-stats">
                        <div class="stat-item">
                            <div class="stat-label">ዛሬ ገቢ</div>
                            <div class="stat-value income"><?php echo formatCurrency($stats['fish']['today_income'] ?? 0); ?></div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-label">ዛሬ ወጪ</div>
                            <div class="stat-value expense"><?php echo formatCurrency($stats['fish']['today_expense'] ?? 0); ?></div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-label">ወርሃዊ ገቢ</div>
                            <div class="stat-value income"><?php echo formatCurrency($stats['fish']['monthly_income'] ?? 0); ?></div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-label">ወርሃዊ ወጪ</div>
                            <div class="stat-value expense"><?php echo formatCurrency($stats['fish']['monthly_expense'] ?? 0); ?></div>
                        </div>
                        <?php 
                        $profit = ($stats['fish']['monthly_income'] ?? 0) - ($stats['fish']['monthly_expense'] ?? 0);
                        $class = $profit >= 0 ? 'positive' : 'negative';
                        ?>
                        <div class="profit-loss <?php echo $class; ?>">
                            <?php echo ($profit >= 0 ? 'ትርፍ: ' : 'ኪሳራ: ') . formatCurrency(abs($profit)); ?>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <?php if(isset($stats['general'])): ?>
                <div class="dashboard-card" onclick="window.location='FINANCE.PHP'">
                    <div class="card-header">
                        <div class="card-title">የተለያዩ ገቢና ወጪ</div>
                        <div class="card-icon general"><i class="fas fa-chart-pie"></i></div>
                    </div>
                    <div class="card-stats">
                        <div class="stat-item">
                            <div class="stat-label">ዛሬ ገቢ</div>
                            <div class="stat-value income"><?php echo formatCurrency($stats['general']['today_income'] ?? 0); ?></div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-label">ዛሬ ወጪ</div>
                            <div class="stat-value expense"><?php echo formatCurrency($stats['general']['today_expense'] ?? 0); ?></div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-label">ወርሃዊ ገቢ</div>
                            <div class="stat-value income"><?php echo formatCurrency($stats['general']['monthly_income'] ?? 0); ?></div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-label">ወርሃዊ ወጪ</div>
                            <div class="stat-value expense"><?php echo formatCurrency($stats['general']['monthly_expense'] ?? 0); ?></div>
                        </div>
                        <?php 
                        $profit = ($stats['general']['monthly_income'] ?? 0) - ($stats['general']['monthly_expense'] ?? 0);
                        $class = $profit >= 0 ? 'positive' : 'negative';
                        ?>
                        <div class="profit-loss <?php echo $class; ?>">
                            <?php echo ($profit >= 0 ? 'ትርፍ: ' : 'ኪሳራ: ') . formatCurrency(abs($profit)); ?>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>

            <!-- Alerts -->
            <?php if(count($alerts) > 0): ?>
            <div class="alerts-section">
                <div class="alerts-header">
                    <h3>ማስጠንቀቂያዎች / Alerts</h3>
                    <button class="btn btn-sm btn-primary" onclick="markAllRead()">Mark All Read</button>
                </div>
                <?php foreach($alerts as $alert): ?>
                <div class="alert-item">
                    <div class="alert-icon">
                        <i class="fas fa-bell" style="color: var(--warning);"></i>
                    </div>
                    <div class="alert-content">
                        <div class="alert-title"><?php echo htmlspecialchars($alert['title_am'] ?? $alert['title']); ?></div>
                        <div class="alert-time"><?php echo $alert['ethiopian_date'] ?? ''; ?></div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </main>
    </div>

    <script>
        function toggleSidebar() {
            document.getElementById('sidebar').classList.toggle('open');
        }
        
        function markAllRead() {
            fetch('API/MARK_NOTIFICATIONS_READ.PHP', {method: 'POST'})
            .then(function(r) { return r.json(); })
            .then(function(d) { if (d.success) location.reload(); });
        }

        setInterval(function() { location.reload(); }, 300000);
    </script>
</body>
</html>