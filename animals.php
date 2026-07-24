<?php
require_once 'CONFIG/CONFIG.PHP';
require_once 'INCLUDES/AUTH.PHP';

requirePermission('livestock');

$lang = getUserLanguage();
$ethiopian_date = getCurrentEthiopianDate();

// Get all animals
$sql = "SELECT * FROM animals ORDER BY created_at DESC";
$stmt = $db->query($sql);
$animals = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get animal statistics
$sql = "SELECT 
    COUNT(*) as total,
    SUM(CASE WHEN status='active' THEN 1 ELSE 0 END) as active,
    SUM(CASE WHEN status='sick' THEN 1 ELSE 0 END) as sick,
    SUM(CASE WHEN status='pregnant' THEN 1 ELSE 0 END) as pregnant,
    SUM(CASE WHEN status='sold' THEN 1 ELSE 0 END) as sold,
    SUM(CASE WHEN status='dead' THEN 1 ELSE 0 END) as dead,
    SUM(CASE WHEN gender='female' THEN 1 ELSE 0 END) as female,
    SUM(CASE WHEN gender='male' THEN 1 ELSE 0 END) as male
FROM animals";
$stmt = $db->query($sql);
$animalStats = $stmt->fetch(PDO::FETCH_ASSOC);

// Handle add animal
$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_animal'])) {
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $message = 'Invalid security token';
        $messageType = 'error';
    } else {
        $animal_id = sanitizeInput($_POST['animal_id']);
        $name = sanitizeInput($_POST['name']);
        $breed = sanitizeInput($_POST['breed']);
        $gender = sanitizeInput($_POST['gender']);
        $birth_date = sanitizeInput($_POST['birth_date']);
        $weight = floatval($_POST['weight']);
        $color = sanitizeInput($_POST['color']);
        $purchase_price = floatval($_POST['purchase_price']);
        
        try {
            $sql = "INSERT INTO animals (animal_id, name, breed, gender, birth_date, ethiopian_birth_date, weight, color, purchase_price, status, created_by) 
                    VALUES (:aid, :name, :breed, :gender, :bdate, :edate, :weight, :color, :price, 'active', :uid)";
            $stmt = $db->prepare($sql);
            $stmt->execute([
                ':aid' => $animal_id,
                ':name' => $name,
                ':breed' => $breed,
                ':gender' => $gender,
                ':bdate' => $birth_date,
                ':edate' => $ethiopian_date,
                ':weight' => $weight,
                ':color' => $color,
                ':price' => $purchase_price,
                ':uid' => $_SESSION['user_id']
            ]);
            
            logAudit($db, $_SESSION['user_id'], 'create', 'animals', $db->lastInsertId(), null, ['animal_id' => $animal_id, 'name' => $name]);
            
            $message = 'Animal added successfully!';
            $messageType = 'success';
            header('Location: ANIMALS.PHP?msg=' . urlencode($message) . '&type=' . $messageType);
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
    <title>Animals - <?php echo APP_NAME; ?></title>
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
        .sidebar-logo i { font-size: 40px; color: var(--success); }
        .sidebar-logo h2 { font-size: 16px; color: var(--primary); margin-top: 10px; }
        .sidebar-menu { list-style: none; }
        .sidebar-menu li { margin-bottom: 5px; }
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
        .sidebar-menu a i { margin-right: 10px; width: 20px; text-align: center; }
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
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
            gap: 15px;
            margin-bottom: 25px;
        }
        .stat-card {
            background: var(--glass-bg);
            backdrop-filter: blur(10px);
            border-radius: 15px;
            padding: 20px;
            box-shadow: var(--glass-shadow);
            text-align: center;
            border: 1px solid rgba(255,255,255,0.2);
        }
        .stat-card h3 {
            font-size: 28px;
            margin-bottom: 5px;
        }
        .stat-card p {
            font-size: 12px;
            color: #666;
            text-transform: uppercase;
        }
        .stat-card.active h3 { color: var(--success); }
        .stat-card.sick h3 { color: var(--warning); }
        .stat-card.pregnant h3 { color: var(--info); }
        .stat-card.sold h3 { color: var(--secondary); }
        .stat-card.dead h3 { color: var(--danger); }
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
        .form-group select {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 14px;
            font-family: inherit;
        }
        .form-group input:focus,
        .form-group select:focus {
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
        .message.success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .message.error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .animal-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
        }
        .animal-card {
            background: var(--light);
            border-radius: 15px;
            padding: 20px;
            border: 1px solid #eee;
            transition: all 0.3s;
            cursor: pointer;
        }
        .animal-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }
        .animal-card .animal-id {
            font-size: 12px;
            color: #666;
            margin-bottom: 10px;
        }
        .animal-card .animal-name {
            font-size: 18px;
            font-weight: bold;
            margin-bottom: 5px;
        }
        .animal-card .animal-breed {
            font-size: 13px;
            color: #666;
            margin-bottom: 10px;
        }
        .status-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 15px;
            font-size: 12px;
            font-weight: 500;
        }
        .status-active { background: #d4edda; color: #155724; }
        .status-sick { background: #fff3cd; color: #856404; }
        .status-pregnant { background: #cce5ff; color: #004085; }
        .status-sold { background: #e2e3e5; color: #383d41; }
        .status-dead { background: #f8d7da; color: #721c24; }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #eee;
            font-size: 13px;
        }
        th {
            background: #f8f9fa;
            font-weight: 600;
            color: var(--primary);
        }
        tr:hover { background: #f8f9fa; }
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
                <li><a href="ANIMALS.PHP" class="active"><i class="fas fa-cow"></i> Animals</a></li>
                <li><a href="MILK_PRODUCTION.PHP"><i class="fas fa-flask"></i> Milk</a></li>
                <li><a href="FEED_MANAGEMENT.PHP"><i class="fas fa-seedling"></i> Feed</a></li>
                <li><a href="PREGNANCY_TRACKING.PHP"><i class="fas fa-baby"></i> Pregnancy</a></li>
                <li><a href="DEATH_RECORDS.PHP"><i class="fas fa-skull"></i> Death Records</a></li>
                <li><a href="LOGOUT.PHP"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>
        </aside>

        <main class="main-content">
            <div class="top-bar">
                <div class="top-bar-left">
                    <button class="hamburger" onclick="toggleSidebar()">
                        <i class="fas fa-bars"></i>
                    </button>
                    <h2>Animal Management</h2>
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

            <div class="stats-grid">
                <div class="stat-card active">
                    <h3><?php echo $animalStats['active']; ?></h3>
                    <p>Active</p>
                </div>
                <div class="stat-card sick">
                    <h3><?php echo $animalStats['sick']; ?></h3>
                    <p>Sick</p>
                </div>
                <div class="stat-card pregnant">
                    <h3><?php echo $animalStats['pregnant']; ?></h3>
                    <p>Pregnant</p>
                </div>
                <div class="stat-card sold">
                    <h3><?php echo $animalStats['sold']; ?></h3>
                    <p>Sold</p>
                </div>
                <div class="stat-card dead">
                    <h3><?php echo $animalStats['dead']; ?></h3>
                    <p>Dead</p>
                </div>
                <div class="stat-card">
                    <h3><?php echo $animalStats['total']; ?></h3>
                    <p>Total</p>
                </div>
            </div>

            <div class="section-card">
                <div class="section-header">
                    <h3>Add New Animal</h3>
                </div>
                <form method="POST" action="">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                    <input type="hidden" name="add_animal" value="1">
                    
                    <div class="form-grid">
                        <div class="form-group">
                            <label>Animal ID</label>
                            <input type="text" name="animal_id" required placeholder="e.g., COW-001">
                        </div>
                        <div class="form-group">
                            <label>Name</label>
                            <input type="text" name="name" required placeholder="Animal name">
                        </div>
                        <div class="form-group">
                            <label>Breed</label>
                            <input type="text" name="breed" required placeholder="e.g., Holstein Friesian">
                        </div>
                        <div class="form-group">
                            <label>Gender</label>
                            <select name="gender" required>
                                <option value="">Select</option>
                                <option value="female">Female</option>
                                <option value="male">Male</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Birth Date</label>
                            <input type="date" name="birth_date" required>
                        </div>
                        <div class="form-group">
                            <label>Weight (KG)</label>
                            <input type="number" name="weight" step="0.01" required>
                        </div>
                        <div class="form-group">
                            <label>Color</label>
                            <input type="text" name="color" placeholder="e.g., Black & White">
                        </div>
                        <div class="form-group">
                            <label>Purchase Price (ETB)</label>
                            <input type="number" name="purchase_price" step="0.01" required>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Add Animal
                    </button>
                </form>
            </div>

            <div class="section-card">
                <div class="section-header">
                    <h3>All Animals (<?php echo $animalStats['total']; ?>)</h3>
                    <input type="text" id="searchInput" placeholder="Search animals..." 
                           style="padding: 8px 15px; border-radius: 25px; border: 1px solid #ddd; width: 250px;"
                           onkeyup="searchAnimals()">
                </div>
                <div style="overflow-x: auto;">
                    <table id="animalsTable">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Name</th>
                                <th>Breed</th>
                                <th>Gender</th>
                                <th>Birth Date</th>
                                <th>Age (Months)</th>
                                <th>Weight (KG)</th>
                                <th>Status</th>
                                <th>Value</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($animals as $animal): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($animal['animal_id']); ?></strong></td>
                                <td><?php echo htmlspecialchars($animal['name']); ?></td>
                                <td><?php echo htmlspecialchars($animal['breed']); ?></td>
                                <td><?php echo $animal['gender']; ?></td>
                                <td><?php echo $animal['birth_date']; ?></td>
                                <td><?php echo $animal['age_months']; ?></td>
                                <td><?php echo number_format($animal['weight'], 1); ?></td>
                                <td>
                                    <span class="status-badge status-<?php echo $animal['status']; ?>">
                                        <?php echo $animal['status']; ?>
                                    </span>
                                </td>
                                <td><?php echo formatCurrency($animal['current_value']); ?></td>
                            </tr>
                            <?php endforeach; ?>
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

        function searchAnimals() {
            var input = document.getElementById('searchInput');
            var filter = input.value.toUpperCase();
            var table = document.getElementById('animalsTable');
            var tr = table.getElementsByTagName('tr');

            for (var i = 1; i < tr.length; i++) {
                var td = tr[i].getElementsByTagName('td');
                var found = false;
                for (var j = 0; j < td.length; j++) {
                    if (td[j]) {
                        var txtValue = td[j].textContent || td[j].innerText;
                        if (txtValue.toUpperCase().indexOf(filter) > -1) {
                            found = true;
                            break;
                        }
                    }
                }
                tr[i].style.display = found ? '' : 'none';
            }
        }
    </script>
</body>
</html>