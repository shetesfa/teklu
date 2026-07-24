<?php
require_once 'CONFIG/CONFIG.PHP';
require_once 'INCLUDES/AUTH.PHP';

requirePermission('reports');

$lang = getUserLanguage();
$ethiopian_date = getCurrentEthiopianDate();

// Get summary data
$sql = "SELECT 
    (SELECT COALESCE(SUM(balance), 0) FROM bank_accounts WHERE is_active = 1) as bank_balance,
    (SELECT current_balance FROM cash_register LIMIT 1) as cash_balance,
    (SELECT COUNT(*) FROM animals WHERE status = 'active') as active_animals,
    (SELECT COALESCE(SUM(current_count), 0) FROM poultry_batches WHERE status = 'active') as poultry_count,
    (SELECT COALESCE(SUM(current_count), 0) FROM fish_ponds WHERE status = 'active') as fish_count,
    (SELECT COUNT(*) FROM irrigation_fields WHERE status NOT IN ('fallow', 'harvested')) as active_fields";
$stmt = $db->query($sql);
$summary = $stmt->fetch(PDO::FETCH_ASSOC);

// Monthly income
$sql = "SELECT COALESCE(SUM(amount), 0) as total FROM transactions 
        WHERE transaction_type = 'income' AND MONTH(gregorian_date) = MONTH(CURDATE()) 
        AND YEAR(gregorian_date) = YEAR(CURDATE())";
$stmt = $db->query($sql);
$monthly_income = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

// Monthly expense
$sql = "SELECT COALESCE(SUM(amount), 0) as total FROM transactions 
        WHERE transaction_type = 'expense' AND MONTH(gregorian_date) = MONTH(CURDATE()) 
        AND YEAR(gregorian_date) = YEAR(CURDATE())";
$stmt = $db->query($sql);
$monthly_expense = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

// Milk monthly
$sql = "SELECT COALESCE(SUM(total_milk), 0) as total FROM milk_records 
        WHERE MONTH(record_date) = MONTH(CURDATE()) AND YEAR(record_date) = YEAR(CURDATE())";
$stmt = $db->query($sql);
$monthly_milk = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
?>
<!DOCTYPE html>
<html lang="<?php echo $lang; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports - <?php echo APP_NAME; ?></title>
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
        .report-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 25px; margin-bottom: 25px; }
        .report-card {
            background: var(--glass-bg); backdrop-filter: blur(10px); border-radius: 20px;
            padding: 30px; box-shadow: var(--glass-shadow); border: 1px solid rgba(255,255,255,0.2);
            text-align: center; transition: all 0.3s; cursor: pointer;
        }
        .report-card:hover { transform: translateY(-5px); box-shadow: 0 15px 40px rgba(31, 38, 135, 0.25); }
        .report-icon { font-size: 48px; margin-bottom: 15px; }
        .report-value { font-size: 36px; font-weight: bold; margin-bottom: 10px; }
        .report-label { font-size: 16px; color: #666; text-transform: uppercase; }
        .report-card.blue .report-value { color: var(--info); }
        .report-card.green .report-value { color: var(--success); }
        .report-card.orange .report-value { color: var(--warning); }
        .report-card.purple .report-value { color: #667eea; }
        .report-card.red .report-value { color: var(--danger); }
        .section-card {
            background: var(--glass-bg); backdrop-filter: blur(10px); border-radius: 20px;
            padding: 25px; box-shadow: var(--glass-shadow); border: 1px solid rgba(255,255,255,0.2);
            margin-bottom: 25px;
        }
        .section-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
        .btn { padding: 10px 25px; border: none; border-radius: 25px; cursor: pointer; font-weight: 500; transition: all 0.3s; font-size: 14px; }
        .btn-primary { background: linear-gradient(135deg, #667eea, #764ba2); color: white; }
        .btn-success { background: linear-gradient(135deg, var(--success), #2ecc71); color: white; }
        .btn-warning { background: linear-gradient(135deg, var(--warning), #e67e22); color: white; }
        .btn:hover { transform: translateY(-2px); box-shadow: 0 5px 15px rgba(0,0,0,0.2); }
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
                <li><a href="FINANCE.PHP"><i class="fas fa-money-bill"></i> Finance</a></li>
                <li><a href="ANIMALS.PHP"><i class="fas fa-cow"></i> Animals</a></li>
                <li><a href="REPORTS.PHP" class="active"><i class="fas fa-chart-bar"></i> Reports</a></li>
                <li><a href="LOGOUT.PHP"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>
        </aside>

        <main class="main-content">
            <div class="top-bar">
                <div class="top-bar-left">
                    <button class="hamburger" onclick="toggleSidebar()"><i class="fas fa-bars"></i></button>
                    <h2>Reports & Analytics</h2>
                    <div class="ethiopian-date"><i class="fas fa-calendar-alt"></i> <?php echo $ethiopian_date; ?></div>
                </div>
                <div><strong><?php echo $_SESSION['full_name']; ?></strong></div>
            </div>

            <div class="report-grid">
                <div class="report-card blue">
                    <div class="report-icon"><i class="fas fa-university"></i></div>
                    <div class="report-value"><?php echo formatCurrency($summary['bank_balance']); ?></div>
                    <div class="report-label">Total Bank Balance</div>
                </div>
                <div class="report-card green">
                    <div class="report-icon"><i class="fas fa-money-bill"></i></div>
                    <div class="report-value"><?php echo formatCurrency($summary['cash_balance']); ?></div>
                    <div class="report-label">Cash in Hand</div>
                </div>
                <div class="report-card orange">
                    <div class="report-icon"><i class="fas fa-chart-line"></i></div>
                    <div class="report-value"><?php echo formatCurrency($monthly_income); ?></div>
                    <div class="report-label">Monthly Income</div>
                </div>
                <div class="report-card red">
                    <div class="report-icon"><i class="fas fa-chart-pie"></i></div>
                    <div class="report-value"><?php echo formatCurrency($monthly_expense); ?></div>
                    <div class="report-label">Monthly Expense</div>
                </div>
                <div class="report-card purple">
                    <div class="report-icon"><i class="fas fa-flask"></i></div>
                    <div class="report-value"><?php echo number_format($monthly_milk, 1); ?> L</div>
                    <div class="report-label">Monthly Milk Production</div>
                </div>
                <div class="report-card blue">
                    <div class="report-icon"><i class="fas fa-cow"></i></div>
                    <div class="report-value"><?php echo $summary['active_animals']; ?></div>
                    <div class="report-label">Active Animals</div>
                </div>
            </div>

            <div class="section-card">
                <div class="section-header">
                    <h3>Generate Reports</h3>
                </div>
                <div style="display: flex; gap: 15px; flex-wrap: wrap;">
                    <button class="btn btn-primary" onclick="generateReport('daily')">
                        <i class="fas fa-file"></i> Daily Report
                    </button>
                    <button class="btn btn-success" onclick="generateReport('monthly')">
                        <i class="fas fa-file-alt"></i> Monthly Report
                    </button>
                    <button class="btn btn-warning" onclick="generateReport('annual')">
                        <i class="fas fa-file-pdf"></i> Annual Report
                    </button>
                    <button class="btn btn-primary" onclick="window.print()">
                        <i class="fas fa-print"></i> Print Report
                    </button>
                </div>
            </div>

            <div class="section-card">
                <div class="section-header"><h3>Quick Summary</h3></div>
                <table style="width: 100%;">
                    <tr>
                        <td><strong>Total Bank Balance:</strong></td>
                        <td><?php echo formatCurrency($summary['bank_balance']); ?></td>
                    </tr>
                    <tr>
                        <td><strong>Cash in Hand:</strong></td>
                        <td><?php echo formatCurrency($summary['cash_balance']); ?></td>
                    </tr>
                    <tr>
                        <td><strong>Total Available:</strong></td>
                        <td><strong><?php echo formatCurrency($summary['bank_balance'] + $summary['cash_balance']); ?></strong></td>
                    </tr>
                    <tr>
                        <td><strong>Monthly Profit/Loss:</strong></td>
                        <td style="color: <?php echo ($monthly_income - $monthly_expense) >= 0 ? 'var(--success)' : 'var(--danger)'; ?>;">
                            <strong><?php echo formatCurrency($monthly_income - $monthly_expense); ?></strong>
                        </td>
                    </tr>
                    <tr>
                        <td><strong>Active Animals:</strong></td>
                        <td><?php echo $summary['active_animals']; ?></td>
                    </tr>
                    <tr>
                        <td><strong>Poultry Birds:</strong></td>
                        <td><?php echo number_format($summary['poultry_count']); ?></td>
                    </tr>
                    <tr>
                        <td><strong>Fish Stock:</strong></td>
                        <td><?php echo number_format($summary['fish_count']); ?></td>
                    </tr>
                    <tr>
                        <td><strong>Active Fields:</strong></td>
                        <td><?php echo $summary['active_fields']; ?></td>
                    </tr>
                    <tr>
                        <td><strong>Monthly Milk:</strong></td>
                        <td><?php echo number_format($monthly_milk, 1); ?> Liters</td>
                    </tr>
                </table>
            </div>
        </main>
    </div>

    <script>
        function toggleSidebar() {
            document.getElementById('sidebar').classList.toggle('open');
        }

        function generateReport(type) {
            var reportType = type;
            var message = 'Generating ' + reportType + ' report...\n\n';
            
            if (reportType === 'daily') {
                message += 'Daily Report - <?php echo $ethiopian_date; ?>\n';
                message += 'Bank Balance: <?php echo formatCurrency($summary['bank_balance']); ?>\n';
                message += 'Cash: <?php echo formatCurrency($summary['cash_balance']); ?>\n';
                message += 'Active Animals: <?php echo $summary['active_animals']; ?>\n';
            } else if (reportType === 'monthly') {
                message += 'Monthly Report\n';
                message += 'Income: <?php echo formatCurrency($monthly_income); ?>\n';
                message += 'Expense: <?php echo formatCurrency($monthly_expense); ?>\n';
                message += 'Profit: <?php echo formatCurrency($monthly_income - $monthly_expense); ?>\n';
                message += 'Milk: <?php echo number_format($monthly_milk, 1); ?> L\n';
            } else {
                message += 'Annual Report\n';
                message += 'This report will be generated as PDF\n';
            }
            
            alert(message);
        }
    </script>
</body>
</html>