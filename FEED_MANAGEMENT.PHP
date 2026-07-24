<?php
require_once 'CONFIG/CONFIG.PHP';
require_once 'INCLUDES/AUTH.PHP';

requirePermission('livestock');

$lang = getUserLanguage();
$ethiopian_date = getCurrentEthiopianDate();

// Get feed inventory
$sql = "SELECT * FROM feed_inventory WHERE is_active = 1 ORDER BY feed_name";
$stmt = $db->query($sql);
$feed_inventory = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get today's feed distribution
$sql = "SELECT fr.*, fi.feed_name, fi.feed_name_am, a.animal_id, a.name as animal_name 
        FROM feed_records fr 
        JOIN feed_inventory fi ON fr.feed_inventory_id = fi.id 
        LEFT JOIN animals a ON fr.animal_id = a.id 
        WHERE fr.distribution_date = CURDATE() 
        ORDER BY fr.created_at DESC";
$stmt = $db->query($sql);
$today_records = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get today's total feed
$sql = "SELECT COALESCE(SUM(quantity_kg), 0) as total FROM feed_records WHERE distribution_date = CURDATE()";
$stmt = $db->query($sql);
$today_total = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

// Get active animals for individual feed
$sql = "SELECT id, animal_id, name FROM animals WHERE status = 'active' ORDER BY animal_id";
$stmt = $db->query($sql);
$active_animals = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get feed type counts
$sql = "SELECT feed_type, SUM(quantity_kg) as total_kg, COUNT(*) as count 
        FROM feed_inventory WHERE is_active = 1 GROUP BY feed_type";
$stmt = $db->query($sql);
$feed_types = $stmt->fetchAll(PDO::FETCH_ASSOC);

$message = '';
$messageType = '';

// Handle add feed inventory
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_inventory'])) {
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $message = 'Invalid security token';
        $messageType = 'error';
    } else {
        $feed_name = sanitizeInput($_POST['feed_name']);
        $feed_type = sanitizeInput($_POST['feed_type']);
        $quantity = floatval($_POST['quantity_kg']);
        $unit_price = floatval($_POST['unit_price']);
        $location = sanitizeInput($_POST['storage_location']);
        $supplier = sanitizeInput($_POST['supplier']);
        
        try {
            $sql = "INSERT INTO feed_inventory (feed_name, feed_type, quantity_kg, unit_price, storage_location, supplier, purchase_date) 
                    VALUES (:name, :type, :qty, :price, :loc, :sup, CURDATE())";
            $stmt = $db->prepare($sql);
            $stmt->execute([
                ':name' => $feed_name, ':type' => $feed_type, ':qty' => $quantity,
                ':price' => $unit_price, ':loc' => $location, ':sup' => $supplier
            ]);
            
            $message = 'Feed inventory added successfully!';
            $messageType = 'success';
            header('Location: FEED_MANAGEMENT.PHP?msg=' . urlencode($message) . '&type=' . $messageType);
            exit();
        } catch (Exception $e) {
            $message = 'Error: ' . $e->getMessage();
            $messageType = 'error';
        }
    }
}

// Handle distribute feed
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['distribute_feed'])) {
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $message = 'Invalid security token';
        $messageType = 'error';
    } else {
        $feed_id = intval($_POST['feed_inventory_id']);
        $animal_id = !empty($_POST['animal_id']) ? intval($_POST['animal_id']) : null;
        $quantity = floatval($_POST['quantity_kg']);
        $dist_type = sanitizeInput($_POST['distribution_type']);
        
        try {
            $db->beginTransaction();
            
            // Insert feed record
            $sql = "INSERT INTO feed_records (feed_inventory_id, animal_id, quantity_kg, distribution_type, ethiopian_date, distribution_date, distributed_by) 
                    VALUES (:fid, :aid, :qty, :dtype, :edate, CURDATE(), :uid)";
            $stmt = $db->prepare($sql);
            $stmt->execute([
                ':fid' => $feed_id, ':aid' => $animal_id, ':qty' => $quantity,
                ':dtype' => $dist_type, ':edate' => $ethiopian_date, ':uid' => $_SESSION['user_id']
            ]);
            
            // Update inventory
            $sql = "UPDATE feed_inventory SET quantity_kg = quantity_kg - :qty WHERE id = :id";
            $stmt = $db->prepare($sql);
            $stmt->execute([':qty' => $quantity, ':id' => $feed_id]);
            
            $db->commit();
            
            $message = 'Feed distributed successfully!';
            $messageType = 'success';
            header('Location: FEED_MANAGEMENT.PHP?msg=' . urlencode($message) . '&type=' . $messageType);
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
    <title>Feed Management - <?php echo APP_NAME; ?></title>
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
        .btn-success { background: linear-gradient(135deg, var(--success), #2ecc71); color: white; }
        .btn:hover { transform: translateY(-2px); box-shadow: 0 5px 15px rgba(0,0,0,0.2); }
        .message { padding: 12px 20px; border-radius: 10px; margin-bottom: 20px; font-weight: 500; }
        .message.success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .message.error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #eee; font-size: 13px; }
        th { background: #f8f9fa; font-weight: 600; color: var(--primary); }
        tr:hover { background: #f8f9fa; }
        .badge {
            display: inline-block; padding: 4px 12px; border-radius: 15px;
            font-size: 12px; font-weight: 500;
        }
        .badge-hay { background: #d4edda; color: #155724; }
        .badge-silage { background: #fff3cd; color: #856404; }
        .badge-concentrate { background: #cce5ff; color: #004085; }
        .badge-mineral { background: #f8d7da; color: #721c24; }
        .badge-supplement { background: #e8daef; color: #6c3483; }
        .low-stock { background: #ffeaea !important; }
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
                <li><a href="MILK_PRODUCTION.PHP"><i class="fas fa-flask"></i> Milk</a></li>
                <li><a href="FEED_MANAGEMENT.PHP" class="active"><i class="fas fa-seedling"></i> Feed</a></li>
                <li><a href="LOGOUT.PHP"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>
        </aside>

        <main class="main-content">
            <div class="top-bar">
                <div class="top-bar-left">
                    <button class="hamburger" onclick="toggleSidebar()"><i class="fas fa-bars"></i></button>
                    <h2>Feed Management</h2>
                    <div class="ethiopian-date"><i class="fas fa-calendar-alt"></i> <?php echo $ethiopian_date; ?></div>
                </div>
                <div><strong><?php echo $_SESSION['full_name']; ?></strong></div>
            </div>

            <?php if ($message): ?>
            <div class="message <?php echo $messageType; ?>"><?php echo htmlspecialchars($message); ?></div>
            <?php endif; ?>

            <div class="summary-cards">
                <div class="summary-card">
                    <h3><?php echo number_format($today_total, 1); ?> KG</h3>
                    <p>Today's Feed Distributed</p>
                </div>
                <div class="summary-card">
                    <h3><?php echo count($feed_inventory); ?></h3>
                    <p>Feed Types Available</p>
                </div>
                <div class="summary-card">
                    <h3><?php echo number_format(array_sum(array_column($feed_inventory, 'quantity_kg')), 1); ?> KG</h3>
                    <p>Total Feed Stock</p>
                </div>
            </div>

            <div class="section-card">
                <div class="section-header"><h3>Add Feed Inventory</h3></div>
                <form method="POST" action="">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                    <input type="hidden" name="add_inventory" value="1">
                    
                    <div class="form-grid">
                        <div class="form-group">
                            <label>Feed Name</label>
                            <input type="text" name="feed_name" required placeholder="Feed name">
                        </div>
                        <div class="form-group">
                            <label>Feed Type</label>
                            <select name="feed_type" required>
                                <option value="">Select Type</option>
                                <option value="hay">Hay</option>
                                <option value="silage">Silage</option>
                                <option value="concentrate">Concentrate</option>
                                <option value="mineral">Mineral</option>
                                <option value="supplement">Supplement</option>
                                <option value="other">Other</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Quantity (KG)</label>
                            <input type="number" name="quantity_kg" step="0.01" required>
                        </div>
                        <div class="form-group">
                            <label>Unit Price (ETB/KG)</label>
                            <input type="number" name="unit_price" step="0.01" required>
                        </div>
                        <div class="form-group">
                            <label>Storage Location</label>
                            <input type="text" name="storage_location" placeholder="e.g., Warehouse A">
                        </div>
                        <div class="form-group">
                            <label>Supplier</label>
                            <input type="text" name="supplier" placeholder="Supplier name">
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-plus"></i> Add Feed</button>
                </form>
            </div>

            <div class="section-card">
                <div class="section-header"><h3>Distribute Feed</h3></div>
                <form method="POST" action="">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                    <input type="hidden" name="distribute_feed" value="1">
                    
                    <div class="form-grid">
                        <div class="form-group">
                            <label>Feed Type</label>
                            <select name="feed_inventory_id" required>
                                <option value="">Select Feed</option>
                                <?php foreach($feed_inventory as $feed): ?>
                                <option value="<?php echo $feed['id']; ?>">
                                    <?php echo htmlspecialchars($feed['feed_name']); ?> (<?php echo number_format($feed['quantity_kg'], 1); ?> KG available)
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Distribution Type</label>
                            <select name="distribution_type" required onchange="toggleAnimalSelect(this.value)">
                                <option value="">Select</option>
                                <option value="individual">Individual Animal</option>
                                <option value="group">Group</option>
                                <option value="all">All Animals</option>
                            </select>
                        </div>
                        <div class="form-group" id="animalSelectGroup" style="display:none;">
                            <label>Animal</label>
                            <select name="animal_id">
                                <option value="">Select Animal</option>
                                <?php foreach($active_animals as $animal): ?>
                                <option value="<?php echo $animal['id']; ?>">
                                    <?php echo htmlspecialchars($animal['animal_id'] . ' - ' . $animal['name']); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Quantity (KG)</label>
                            <input type="number" name="quantity_kg" step="0.01" required>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-success"><i class="fas fa-share"></i> Distribute Feed</button>
                </form>
            </div>

            <div class="section-card">
                <div class="section-header"><h3>Feed Inventory Stock</h3></div>
                <div style="overflow-x: auto;">
                    <table>
                        <thead>
                            <tr>
                                <th>Feed Name</th>
                                <th>Type</th>
                                <th>Quantity (KG)</th>
                                <th>Unit Price</th>
                                <th>Total Value</th>
                                <th>Location</th>
                                <th>Supplier</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($feed_inventory as $feed): ?>
                            <tr class="<?php echo $feed['quantity_kg'] <= $feed['minimum_threshold'] ? 'low-stock' : ''; ?>">
                                <td><strong><?php echo htmlspecialchars($feed['feed_name']); ?></strong></td>
                                <td><span class="badge badge-<?php echo $feed['feed_type']; ?>"><?php echo $feed['feed_type']; ?></span></td>
                                <td><?php echo number_format($feed['quantity_kg'], 1); ?></td>
                                <td><?php echo formatCurrency($feed['unit_price']); ?></td>
                                <td><strong><?php echo formatCurrency($feed['total_value']); ?></strong></td>
                                <td><?php echo htmlspecialchars($feed['storage_location'] ?? '-'); ?></td>
                                <td><?php echo htmlspecialchars($feed['supplier'] ?? '-'); ?></td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if(count($feed_inventory) == 0): ?>
                            <tr><td colspan="7" style="text-align: center;">No feed in inventory</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="section-card">
                <div class="section-header"><h3>Today's Feed Distribution</h3></div>
                <div style="overflow-x: auto;">
                    <table>
                        <thead>
                            <tr>
                                <th>Feed Type</th>
                                <th>Animal</th>
                                <th>Quantity (KG)</th>
                                <th>Distribution</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($today_records as $record): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($record['feed_name']); ?></td>
                                <td><?php echo $record['animal_id'] ? htmlspecialchars($record['animal_id'] . ' - ' . $record['animal_name']) : 'All/Group'; ?></td>
                                <td><strong><?php echo number_format($record['quantity_kg'], 1); ?></strong></td>
                                <td><?php echo $record['distribution_type']; ?></td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if(count($today_records) == 0): ?>
                            <tr><td colspan="4" style="text-align: center;">No feed distributed today</td></tr>
                            <?php endif; ?>
                        </tbody>
                        <?php if(count($today_records) > 0): ?>
                        <tfoot>
                            <tr style="font-weight: bold; background: #f8f9fa;">
                                <td colspan="2">Total</td>
                                <td><?php echo number_format($today_total, 1); ?> KG</td>
                                <td></td>
                            </tr>
                        </tfoot>
                        <?php endif; ?>
                    </table>
                </div>
            </div>
        </main>
    </div>

    <script>
        function toggleSidebar() {
            document.getElementById('sidebar').classList.toggle('open');
        }
        
        function toggleAnimalSelect(value) {
            var group = document.getElementById('animalSelectGroup');
            if (value === 'individual') {
                group.style.display = 'block';
            } else {
                group.style.display = 'none';
            }
        }
    </script>
</body>
</html>