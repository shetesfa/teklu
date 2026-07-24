<?php
session_start();
require_once 'CONFIG/CONFIG.PHP';

if (isset($_SESSION['user_id']) && isset($_SESSION['last_activity']) 
    && (time() - $_SESSION['last_activity'] < SESSION_LIFETIME)) {
    header('Location: DASHBOARD.PHP');
    exit();
}
header('Location: LOGIN.PHP');
exit();
?>