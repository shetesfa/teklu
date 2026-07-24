<?php
require_once 'CONFIG/CONFIG.PHP';
require_once 'INCLUDES/AUTH.PHP';

requireLogin();

$lang = getUserLanguage();
$ethiopian_date = getCurrentEthiopianDate();
$search_results = [];
$query = '';

if (isset($_GET['q']) && !empty($_GET['q'])) {
    $query = sanitizeInput($_GET['q']);
    $search_term = '%' . $query . '%';
    
    // Search animals
    $sql = "SELECT 'Animal' as type, animal_id as code, name, breed, status FROM animals 
            WHERE animal_id LIKE :q OR name LIKE :q2 OR breed LIKE :q3 LIMIT 5";
    $stmt = $db->prepare($sql);
    $stmt->execute([':q' => $search_term, ':q2' => $search_term, ':q3' => $search_term]);
    $search_results = array_merge($search_results, $stmt->fetchAll(PDO::FETCH_ASSOC));
    
    // Search transactions
    $sql = "SELECT 'Transaction' as type, transaction_code as code, description as name, amount, status FROM transactions 
            WHERE transaction_code LIKE :q OR description LIKE :q2 LIMIT 5";
    $stmt = $db->prepare($sql);
    $stmt->execute([':q' => $search_term, ':q2' => $search_term]);
    $search_results = array_merge($search_results, $stmt->fetchAll(PDO::FETCH_ASSOC));
    
    // Search users
    $sql = "SELECT 'User' as type, username as code, full_name as name, email, '' as status FROM users 
            WHERE username LIKE :q OR full_name LIKE :q2 OR email LIKE :q3 LIMIT 5";
    $stmt = $db->prepare($sql);
    $stmt->execute([':q' => $search_term, ':q2' => $search_term, ':q3' => $search_term]);
    $search_results = array_merge($search_results, $stmt->fetchAll(PDO::FETCH_ASSOC));
}
?>
<!DOCTYPE html>
<html lang="<?php echo $lang; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Search - <?php echo APP_NAME; ?></title>
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
        .sidebar-menu a:hover {
            background: linear-gradient(135deg, var(--secondary), var(--info));
            color: white; transform: translateX(5px);
        }
        .sidebar-menu a i { margin-right: 10px; width: 20px; text-align: center; }
        .main-content { flex: 1; margin-left: 280px; padding: 20px; }
        .search-container {
            max-width: 800px; margin: 0 auto;
        }
        .search-box {
            background: var(--glass-bg); backdrop-filter: blur(10px); border-radius: 50px;
            padding: 10px 20px; box-shadow: var(--glass-shadow); display: flex;
            align-items: center; margin-bottom: 30px;
        }
        .search-box input {
            flex: 1; border: none; padding: 15px; font-size: 18px;
            background: transparent; outline: none;
        }
        .search-box button {
            background: linear-gradient(135deg, #667eea, #764ba2); color: white;
            border: none; padding: 15px 30px; border-radius: 50px; cursor: pointer;
            font-size: 16px; font-weight: 500;
        }
        .result-card {
            background: var(--glass-bg); backdrop-filter: blur(10px); border-radius: 15px;
            padding: 20px; box-shadow: var(--glass-shadow); border: 1px solid rgba(255,255,255,0.2);
            margin-bottom: 15px; transition: all 0.3s;
        }
        .result-card:hover { transform: translateX(5px); }
        .result-type {
            display: inline-block; padding: 4px 12px; border-radius: 15px;
            font-size: 12px; font-weight: 500; margin-bottom: 10px;
        }
        .type-Animal { background: #cce5ff; color: #004085; }
        .type-Transaction { background: #d4edda; color: #155724; }
        .type-User { background: #fff3cd; color: #856404; }
        .no-results {
            text-align: center; padding: 40px; color: #666;
        }
        .hamburger { display: none; }
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
                <li><a href="LOGOUT.PHP"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>
        </aside>

        <main class="main-content">
            <button class="hamburger" onclick="toggleSidebar()" style="font-size: 24px; cursor: pointer; background: none; border: none; margin-bottom: 20px;">
                <i class="fas fa-bars"></i>
            </button>

            <div class="search-container">
                <form method="GET" action="SEARCH.PHP">
                    <div class="search-box">
                        <i class="fas fa-search" style="font-size: 20px; color: #666; margin-right: 15px;"></i>
                        <input type="text" name="q" value="<?php echo htmlspecialchars($query); ?>" 
                               placeholder="Search animals, transactions, users..." autofocus>
                        <button type="submit"><i class="fas fa-search"></i> Search</button>
                    </div>
                </form>

                <?php if ($query): ?>
                <h3 style="margin-bottom: 20px;">Results for: "<?php echo htmlspecialchars($query); ?>" (<?php echo count($search_results); ?> found)</h3>
                
                <?php if (count($search_results) > 0): ?>
                    <?php foreach($search_results as $result): ?>
                    <div class="result-card">
                        <span class="result-type type-<?php echo $result['type']; ?>"><?php echo $result['type']; ?></span>
                        <h4><?php echo htmlspecialchars($result['code']); ?></h4>
                        <p><?php echo htmlspecialchars($result['name']); ?></p>
                        <?php if (isset($result['breed'])): ?>
                        <small>Breed: <?php echo htmlspecialchars($result['breed']); ?></small>
                        <?php endif; ?>
                        <?php if (isset($result['amount'])): ?>
                        <small>Amount: <?php echo formatCurrency($result['amount']); ?></small>
                        <?php endif; ?>
                        <?php if ($result['status']): ?>
                        <small>Status: <?php echo $result['status']; ?></small>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                <div class="no-results">
                    <i class="fas fa-search" style="font-size: 48px; margin-bottom: 15px; color: #ccc;"></i>
                    <p>No results found for "<?php echo htmlspecialchars($query); ?>"</p>
                </div>
                <?php endif; ?>
                <?php endif; ?>
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