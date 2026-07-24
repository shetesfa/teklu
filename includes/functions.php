<?php
function generateCSRFToken() {
    if (!isset($_SESSION[CSRF_TOKEN_NAME])) {
        $_SESSION[CSRF_TOKEN_NAME] = bin2hex(random_bytes(32));
    }
    return $_SESSION[CSRF_TOKEN_NAME];
}

function validateCSRFToken($token) {
    return isset($_SESSION[CSRF_TOKEN_NAME]) && hash_equals($_SESSION[CSRF_TOKEN_NAME], $token);
}

function sanitizeInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $data;
}

function formatCurrency($amount) {
    return number_format($amount, 2) . ' ETB';
}

function getCurrentEthiopianDate() {
    $gregorian = new DateTime();
    $year = $gregorian->format('Y') - 7;
    $month = $gregorian->format('m');
    $day = $gregorian->format('d');
    if ($gregorian->format('m') <= 4 || ($gregorian->format('m') == 4 && $gregorian->format('d') < 15)) {
        $year -= 1;
    }
    return sprintf('%04d-%02d-%02d', $year, $month, $day);
}

function getUserLanguage() {
    return $_SESSION['language'] ?? 'am';
}

function translate($key, $lang = 'en') {
    $translations = [
        'en' => [
            'dashboard' => 'Dashboard',
            'finance' => 'Finance',
            'animals' => 'Animals',
            'milk' => 'Milk Production',
            'feed' => 'Feed Management',
            'poultry' => 'Poultry',
            'fish' => 'Fish Farming',
            'irrigation' => 'Irrigation',
            'reports' => 'Reports',
            'settings' => 'Settings',
            'logout' => 'Logout',
        ],
        'am' => [
            'dashboard' => 'ዳሽቦርድ',
            'finance' => 'ፋይናንስ',
            'animals' => 'እንስሳት',
            'milk' => 'የወተት ምርት',
            'feed' => 'የመኖ አስተዳደር',
            'poultry' => 'ዶሮ',
            'fish' => 'አሳ',
            'irrigation' => 'መስኖ',
            'reports' => 'ሪፖርቶች',
            'settings' => 'ቅንብሮች',
            'logout' => 'ውጣ',
        ],
        'or' => [
            'dashboard' => 'Daashboordii',
            'finance' => 'Faayinaansii',
            'animals' => 'Beellada',
            'milk' => 'Oomisha Aannanii',
            'feed' => 'Bulchiinsa Nyaataa',
            'poultry' => 'Lukkuu',
            'fish' => 'Qurxummii',
            'irrigation' => 'Jallisii',
            'reports' => 'Gabaasota',
            'settings' => 'Sajoo',
            'logout' => 'Ba\'i',
        ]
    ];
    return $translations[$lang][$key] ?? $key;
}

function checkPermission($required_permission) {
    if (!isset($_SESSION['user_id'])) {
        return false;
    }
    $permissions = json_decode($_SESSION['permissions'] ?? '{}', true);
    if (isset($permissions['all']) && $permissions['all'] === true) {
        return true;
    }
    return isset($permissions[$required_permission]) && $permissions[$required_permission] === true;
}

function isLoggedIn() {
    return isset($_SESSION['user_id']) && isset($_SESSION['last_activity']) 
           && (time() - $_SESSION['last_activity'] < SESSION_LIFETIME);
}

function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: LOGIN.PHP');
        exit();
    }
    $_SESSION['last_activity'] = time();
}

function requirePermission($permission) {
    requireLogin();
    if (!checkPermission($permission)) {
        header('Location: 403.PHP');
        exit();
    }
}

function logAudit($db, $user_id, $action, $table, $record_id, $old_values = null, $new_values = null) {
    $sql = "INSERT INTO audit_logs (user_id, action_type, table_name, record_id, old_values, new_values, ip_address, ethiopian_date) 
            VALUES (:user_id, :action, :table_name, :record_id, :old_values, :new_values, :ip, :eth_date)";
    $stmt = $db->prepare($sql);
    $stmt->execute([
        ':user_id' => $user_id,
        ':action' => $action,
        ':table_name' => $table,
        ':record_id' => $record_id,
        ':old_values' => $old_values ? json_encode($old_values) : null,
        ':new_values' => $new_values ? json_encode($new_values) : null,
        ':ip' => $_SERVER['REMOTE_ADDR'],
        ':eth_date' => getCurrentEthiopianDate()
    ]);
}

function sendNotification($db, $user_id, $type, $title, $message, $link = null) {
    $sql = "INSERT INTO notifications (user_id, notification_type, title, title_am, message, message_am, link, ethiopian_date) 
            VALUES (:user_id, :type, :title, :title_am, :message, :message_am, :link, :eth_date)";
    $stmt = $db->prepare($sql);
    $stmt->execute([
        ':user_id' => $user_id,
        ':type' => $type,
        ':title' => $title,
        ':title_am' => $title,
        ':message' => $message,
        ':message_am' => $message,
        ':link' => $link,
        ':eth_date' => getCurrentEthiopianDate()
    ]);
}
?>