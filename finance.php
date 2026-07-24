<?php
require_once 'CONFIG/CONFIG.PHP';
require_once 'INCLUDES/AUTH.PHP';

requirePermission('finance');

$lang = getUserLanguage();
$ethiopian_date = getCurrentEthiopianDate();

// Get bank accounts
$sql = "SELECT * FROM bank_accounts WHERE is_active = 1 ORDER BY bank_name";
$stmt = $db->query($sql);
$bank_accounts = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get total bank balance
$sql = "SELECT COALESCE(SUM(balance), 0) as total FROM bank_accounts WHERE is_active = 1";
$stmt = $db->query($sql);
$totalBankBalance = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

// Get cash balance
$sql = "SELECT current_balance FROM cash_register LIMIT 1";
$stmt = $db->query($sql);
$cashBalance = $stmt->fetch(PDO::FETCH_ASSOC)['current_balance'];

// Get recent transactions
$sql = "SELECT t.*, tc.category_name_am, u.full_name 
        FROM transactions t 
        LEFT JOIN transaction_categories tc ON t.category_id = tc.id 
        LEFT JOIN users u ON t.created_by = u.id 
        ORDER BY t.created_at DESC LIMIT 10";
$stmt = $db->query($sql);
$recent_transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get transaction categories
$sql = "SELECT * FROM transaction_categories WHERE is_active = 1 ORDER BY category_type, category_name";
$stmt = $db->query($sql);
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Handle new transaction
$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_transaction'])) {
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $message = 'Invalid security token';
        $messageType = 'error';
    } else {
        $type = sanitizeInput($_POST['transaction_type']);
        $amount = floatval($_POST['amount']);
        $category_id = intval($_POST['category_id'] ?? 0);
        $description = sanitizeInput($_POST['description'] ?? '');
        $bank_account_id = intval($_POST['bank_account_id'] ?? 0);
        
        $transaction_code = 'TXN-' . date('Ymd') . '-' . rand(1000, 9999);
        
        try {
            $db->beginTransaction();
            
            // Insert transaction
            $sql = "INSERT INTO transactions (transaction_code, transaction_type, bank_account_id, category_id, amount, description, ethiopian_date, gregorian_date, created_by) 
                    VALUES (:code, :type, :bank_id, :cat_id, :amount, :desc, :eth_date, CURDATE(), :uid)";
            $stmt = $db->prepare($sql);
            $stmt->execute([
                ':code' => $transaction_code,
                ':type' => $type,
                ':bank_id' => $bank_account_id ?: null,
                ':cat_id' => $category_id ?: null,
                ':amount' => $amount,
                ':desc' => $description,
                ':eth_date' => $ethiopian_date,
                ':uid' => $_SESSION['user_id']
            ]);
            
            // Update bank balance if bank account selected
            if ($bank_account_id > 0) {
                if ($type === 'bank_deposit') {
                    $sql = "UPDATE bank_accounts SET balance = balance + :amount WHERE id = :id";
                } elseif ($type === 'bank_withdrawal') {
                    $sql = "UPDATE bank_accounts SET balance = balance - :amount WHERE id = :id";
                }
                if (isset($sql)) {
                    $stmt = $db->prepare($sql);
                    $stmt->execute([':amount' => $amount, ':id' => $bank_account_id]);
                }
            }
            
            // Update cash register
            if ($type === 'income' || $type === 'bank_withdrawal') {
                $sql = "UPDATE cash_register SET current_balance = current_balance + :amount";
            } elseif ($type === 'expense' || $type === 'bank_deposit') {
                $sql = "UPDATE cash_register SET current_balance = current_balance - :amount";
            }
            if (isset($sql)) {
                $stmt = $db->prepare($sql);
                $stmt->execute([':amount' => $amount]);
            }
            
            $db->commit();
            logAudit($db, $_SESSION['user_id'], 'create', 'transactions', $db->lastInsertId(), null, ['amount' => $amount, 'type' => $type]);
            
            $message = 'Transaction recorded successfully!';
            $messageType = 'success';
            
            // Refresh data
            header('Location: FINANCE.PHP?msg=' . urlencode($message) . '&type=' . $messageType);
            exit();
        } catch (Exception $e) {
            $db->rollBack();
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
    <title>Finance - <?php echo APP_NAME; ?></title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        :root {
            --primary: #2c3e50;
            --secondary: #3498db;
            --success: #27ae60;
            --danger: #e74c3c;
            --warning: #f39c12;
            --info: #2980b9;
            --light: #ecf0f1;
            --dark: #2c3e50;
            --white: #ffffff;
            --glass-bg: rgba(255, 255, 255, 0.95);
            --glass-shadow: 0 8px 32px 0 rgba(31, 38, 135, 0.15);
        }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            color: var(--dark);
        }
        .app-container {
            display: flex;
            min-height: 100vh;
        }
        .sidebar {
            width: 280px;
            background: var(--glass-bg);
            backdrop-filter: blur(10px);
            border-right: 1px solid rgba(255,255,255,0.2);
            padding: 20px;
            position: fixed;
            height: 100vh;
            overflow-y: auto;
            z-index: 1000;
            box-shadow: var(--glass-shadow);
        }
        .sidebar-logo {
            text-align: center;
            padding: 20px 0;
            border-bottom: 2px solid rgba(255,255,255,0.2);
            margin-bottom: 20px;
        }
        .sidebar-logo i {
            font-size: 40px;
            color: var(--success);
        }
        .sidebar-logo h2 {
            font-size: 16px;
            color: var(--primary);
            margin-top: 10px;
        }
        .sidebar-menu {
            list-style: none;
        }
        .sidebar-menu li {
            margin-bottom: 5px;
        }
        .sidebar-menu a {
            display: flex;
            align-items: center;
            padding: 12px 15px;
            color: var(--dark);
            text-decoration: none;
            border-radius: 10px;
            transition: all 0.3s;
            font-weight: 500;
            font-size: 14px;
        }
        .sidebar-menu a:hover,
        .sidebar-menu a.active {
            background: linear-gradient(135deg, var(--secondary), var(--info));
            color: white;
            transform: translateX(5px);
        }
        .sidebar-menu a i {
            margin-right: 10px;
            width: 20px;
            text-align: center;
        }
        .main-content {
            flex: 1;
            margin-left: 280px;
            padding: 20px;
        }
        .top-bar {
            background: var(--glass-bg);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 15px 25px;
            margin-bottom: 25px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: var(--glass-shadow);
            flex-wrap: wrap;
            gap: 15px;
        }
        .top-bar-left {
            display: flex;
            align-items: center;
            gap: 20px;
        }
        .hamburger {
            display: none;
            font-size: 24px;
            cursor: pointer;
            background: none;
            border: none;
            color: var(--dark);
        }
        .ethiopian-date {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            padding: 8px 15px;
            border-radius: 25px;
            font-size: 14px;
        }
        .finance-summary {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 25px;
        }
        .summary-card {
            background: var(--glass-bg);
            backdrop-filter: blur(10px);
            border-radius: 15px;
            padding: 25px;
            box-shadow: var(--glass-shadow);
            border: 1px solid rgba(255,255,255,0.2);
            text-align: center;
        }
        .summary-card h3 {
            font-size: 14px;
            color: #666;
            margin-bottom: 10px;
            text-transform: uppercase;
        }
        .summary-card .amount {
            font-size: 28px;
            font-weight: bold;
        }
        .summary-card .amount.blue { color: var(--info); }
        .summary-card .amount.green { color: var(--success); }
        .summary-card .amount.red { color: var(--danger); }
        .section-card {
            background: var(--glass-bg);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 25px;
            box-shadow: var(--glass-shadow);
            border: 1px solid rgba(255,255,255,0.2);
            margin-bottom: 25px;
        }
        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
        }
        .form-group {
            margin-bottom: 15px;
        }
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 500;
            font-size: 13px;
        }
        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 14px;
            font-family: inherit;
        }
        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102,126,234,0.1);
        }
        .btn {
            padding: 10px 25px;
            border: none;
            border-radius: 25px;
            cursor: pointer;
            font-weight: 500;
            transition: all 0.3s;
            font-size: 14px;
        }
        .btn-primary {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
        }
        .btn-success {
            background: linear-gradient(135deg, var(--success), #2ecc71);
            color: white;
        }
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }
        .message {
            padding: 12px 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            font-weight: 500;
        }
        .message.success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .message.error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #eee;
            font-size: 14px;
        }
        th {
            background: #f8f9fa;
            font-weight: 600;
            color: var(--primary);
        }
        tr:hover {
            background: #f8f9fa;
        }
        .badge {
            padding: 4px 12px;
            border-radius: 15px;
            font-size: 12px;
            font-weight: 500;
        }
        .badge-income {
            background: #d4edda;
            color: #155724;
        }
        .badge-expense {
            background: #f8d7da;
            color: #721c24;
        }
        .badge-deposit {
            background: #cce5ff;
            color: #004085;
        }
        .badge-withdrawal {
            background: #fff3cd;
            color: #856404;
        }
        .bank-list {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }
        .bank-card {
            background: var(--light);
            padding: 20px;
            border-radius: 15px;
            border: 1px solid #eee;
        }
        .bank-card h4 {
            margin-bottom: 10px;
            color: var(--primary);
        }
        .bank-card .balance {
            font-size: 24px;
            font-weight: bold;
            color: var(--info);
        }
        @media (max-width: 768px) {
            .sidebar {
                width: 0;
                padding: 0;
                overflow: hidden;
            }
            .sidebar.open {
                width: 280px;
                padding: 20px;
            }
            .main-content {
                margin-left: 0;
            }
            .hamburger {
                display: block;
            }
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
                <li><a href="FINANCE.PHP" class="active"><i class="fas fa-money-bill"></i> Finance</a></li>
                <li><a href="ANIMALS.PHP"><i class="fas fa-cow"></i> Animals</a></li>
                <li><a href="POULTRY.PHP"><i class="fas fa-kiwi-bird"></i> Poultry</a></li>
                <li><a href="FISH_FARMING.PHP"><i class="fas fa-fish"></i> Fish</a></li>
                <li><a href="IRRIGATION.PHP"><i class="fas fa-water"></i> Irrigation</a></li>
                <li><a href="REPORTS.PHP"><i class="fas fa-chart-bar"></i> Reports</a></li>
                <li><a href="LOGOUT.PHP"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>
        </aside>

        <main class="main-content">
            <div class="top-bar">
                <div class="top-bar-left">
                    <button class="hamburger" onclick="toggleSidebar()">
                        <i class="fas fa-bars"></i>
                    </button>
                    <h2>Finance Management</h2>
                    <div class="ethiopian-date">
                        <i class="fas fa-calendar-alt"></i> <?php echo $ethiopian_date; ?>
                    </div>
                </div>
                <div>
                    <strong><?php echo $_SESSION['full_name']; ?></strong>
                </div>
            </div>

            <?php if ($message): ?>
            <div class="message <?php echo $messageType; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
            <?php endif; ?>

            <div class="finance-summary">
                <div class="summary-card">
                    <h3>Total Bank Balance</h3>
                    <div class="amount blue"><?php echo formatCurrency($totalBankBalance); ?></div>
                </div>
                <div class="summary-card">
                    <h3>Cash in Hand</h3>
                    <div class="amount green"><?php echo formatCurrency($cashBalance); ?></div>
                </div>
                <div class="summary-card">
                    <h3>Total Available</h3>
                    <div class="amount red"><?php echo formatCurrency($totalBankBalance + $cashBalance); ?></div>
                </div>
            </div>

            <div class="bank-list">
                <?php foreach($bank_accounts as $account): ?>
                <div class="bank-card">
                    <h4><i class="fas fa-university"></i> <?php echo htmlspecialchars($account['bank_name']); ?></h4>
                    <p><?php echo htmlspecialchars($account['account_number']); ?></p>
                    <p class="balance"><?php echo formatCurrency($account['balance']); ?></p>
                    <small><?php echo $account['account_type']; ?></small>
                </div>
                <?php endforeach; ?>
            </div>

            <div class="section-card">
                <div class="section-header">
                    <h3>New Transaction</h3>
                </div>
                <form method="POST" action="">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                    <input type="hidden" name="add_transaction" value="1">
                    
                    <div class="form-grid">
                        <div class="form-group">
                            <label>Transaction Type</label>
                            <select name="transaction_type" required>
                                <option value="">Select Type</option>
                                <option value="income">Income</option>
                                <option value="expense">Expense</option>
                                <option value="bank_deposit">Bank Deposit</option>
                                <option value="bank_withdrawal">Bank Withdrawal</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Amount (ETB)</label>
                            <input type="number" name="amount" step="0.01" required placeholder="Enter amount">
                        </div>
                        <div class="form-group">
                            <label>Category</label>
                            <select name="category_id">
                                <option value="">Select Category</option>
                                <?php foreach($categories as $cat): ?>
                                <option value="<?php echo $cat['id']; ?>">
                                    <?php echo htmlspecialchars($cat['category_name_am']); ?> (<?php echo $cat['category_type']; ?>)
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Bank Account</label>
                            <select name="bank_account_id">
                                <option value="">Cash Only</option>
                                <?php foreach($bank_accounts as $account): ?>
                                <option value="<?php echo $account['id']; ?>">
                                    <?php echo htmlspecialchars($account['bank_name']); ?> - <?php echo $account['account_number']; ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Description</label>
                        <textarea name="description" rows="2" placeholder="Enter description"></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Record Transaction
                    </button>
                </form>
            </div>

            <div class="section-card">
                <div class="section-header">
                    <h3>Recent Transactions</h3>
                </div>
                <div style="overflow-x: auto;">
                    <table>
                        <thead>
                            <tr>
                                <th>Code</th>
                                <th>Type</th>
                                <th>Category</th>
                                <th>Amount</th>
                                <th>Description</th>
                                <th>Date</th>
                                <th>By</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($recent_transactions as $txn): ?>
                            <tr>
                                <td><?php echo $txn['transaction_code']; ?></td>
                                <td>
                                    <span class="badge badge-<?php echo $txn['transaction_type']; ?>">
                                        <?php echo $txn['transaction_type']; ?>
                                    </span>
                                </td>
                                <td><?php echo htmlspecialchars($txn['category_name_am'] ?? '-'); ?></td>
                                <td><strong><?php echo formatCurrency($txn['amount']); ?></strong></td>
                                <td><?php echo htmlspecialchars($txn['description']); ?></td>
                                <td><?php echo $txn['ethiopian_date']; ?></td>
                                <td><?php echo htmlspecialchars($txn['full_name']); ?></td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if(count($recent_transactions) == 0): ?>
                            <tr>
                                <td colspan="7" style="text-align: center;">No transactions found</td>
                            </tr>
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