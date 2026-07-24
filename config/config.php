<?php
session_start();

define('DB_HOST', 'localhost');
define('DB_NAME', 'teklu_getachew_erp');
define('DB_USER', 'root');
define('DB_PASS', '');
define('APP_NAME', 'ተክሉ ጌታቸው የእንስሳት ተዋጽኦ');
define('APP_NAME_EN', 'Teklu Getachew Livestock ERP');
define('APP_VERSION', '1.0.0');
define('BASE_URL', 'http://localhost/teklu-erp/');
define('CSRF_TOKEN_NAME', 'csrf_token');
define('SESSION_LIFETIME', 3600);
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOCKOUT_TIME', 900);
define('MAX_UPLOAD_SIZE', 10485760);
define('UPLOAD_DIR', __DIR__ . '/../ASSETS/UPLOADS/');

$ethiopian_months = [
    1 => 'መስከረም', 2 => 'ጥቅምት', 3 => 'ኅዳር', 
    4 => 'ታኅሣሥ', 5 => 'ጥር', 6 => 'የካቲት',
    7 => 'መጋቢት', 8 => 'ሚያዝያ', 9 => 'ግንቦት',
    10 => 'ሰኔ', 11 => 'ሐምሌ', 12 => 'ነሐሴ', 13 => 'ጳጉሜ'
];

require_once 'DATABASE.PHP';
$database = new Database();
$db = $database->getConnection();

require_once __DIR__ . '/../INCLUDES/FUNCTIONS.PHP';
?>