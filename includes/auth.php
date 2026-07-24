<?php
require_once __DIR__ . '/../CONFIG/CONFIG.PHP';

function loginUser($db, $username, $password, $ip) {
    $sql = "SELECT u.id, u.username, u.password, u.full_name, u.role_id, u.force_password_change, 
                   u.is_active, u.login_attempts, u.locked_until, u.language_preference, r.permissions 
            FROM users u 
            JOIN roles r ON u.role_id = r.id 
            WHERE u.username = :username LIMIT 1";
    $stmt = $db->prepare($sql);
    $stmt->execute([':username' => $username]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        return ['success' => false, 'message' => 'Invalid username or password'];
    }
    
    if ($user['locked_until'] && strtotime($user['locked_until']) > time()) {
        return ['success' => false, 'message' => 'Account locked. Try again later.'];
    }
    
    if (!$user['is_active']) {
        return ['success' => false, 'message' => 'Account deactivated.'];
    }
    
    // Verify password
    if (!password_verify($password, $user['password'])) {
        $attempts = $user['login_attempts'] + 1;
        $locked = null;
        if ($attempts >= MAX_LOGIN_ATTEMPTS) {
            $locked = date('Y-m-d H:i:s', time() + LOCKOUT_TIME);
        }
        $db->prepare("UPDATE users SET login_attempts = :a, locked_until = :l WHERE id = :id")
           ->execute([':a' => $attempts, ':l' => $locked, ':id' => $user['id']]);
        return ['success' => false, 'message' => 'Invalid username or password'];
    }
    
    // Login successful
    $db->prepare("UPDATE users SET login_attempts = 0, locked_until = NULL, last_login = NOW() WHERE id = :id")
       ->execute([':id' => $user['id']]);
    
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['username'] = $user['username'];
    $_SESSION['full_name'] = $user['full_name'];
    $_SESSION['role_id'] = $user['role_id'];
    $_SESSION['permissions'] = $user['permissions'];
    $_SESSION['force_password_change'] = $user['force_password_change'];
    $_SESSION['last_activity'] = time();
    $_SESSION['language'] = $user['language_preference'] ?? 'am';
    
    $db->prepare("INSERT INTO login_history (user_id, login_time, ip_address, user_agent, status, ethiopian_date) 
                  VALUES (:uid, NOW(), :ip, :agent, 'success', :eth)")
       ->execute([':uid' => $user['id'], ':ip' => $ip, ':agent' => $_SERVER['HTTP_USER_AGENT'], ':eth' => getCurrentEthiopianDate()]);
    
    return ['success' => true, 'force_password_change' => $user['force_password_change'], 'user_id' => $user['id']];
}

function logoutUser($db) {
    if (isset($_SESSION['user_id'])) {
        $db->prepare("INSERT INTO login_history (user_id, login_time, ip_address, status, ethiopian_date) 
                      VALUES (:uid, NOW(), :ip, 'logout', :eth)")
           ->execute([':uid' => $_SESSION['user_id'], ':ip' => $_SERVER['REMOTE_ADDR'], ':eth' => getCurrentEthiopianDate()]);
    }
    session_destroy();
    header('Location: LOGIN.PHP');
    exit();
}
?>