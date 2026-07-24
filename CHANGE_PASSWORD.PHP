<?php
require_once 'CONFIG/CONFIG.PHP';
require_once 'INCLUDES/AUTH.PHP';

requireLogin();

if (!$_SESSION['force_password_change']) {
    header('Location: DASHBOARD.PHP');
    exit();
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid security token';
    } else {
        $new_password = $_POST['new_password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';
        
        if (strlen($new_password) < 6) {
            $error = 'Password must be at least 6 characters';
        } elseif ($new_password !== $confirm_password) {
            $error = 'Passwords do not match';
        } elseif ($new_password === '123') {
            $error = 'Cannot use default password';
        } else {
            $hashed_password = password_hash($new_password, PASSWORD_BCRYPT);
            $sql = "UPDATE users SET password = :password, force_password_change = FALSE WHERE id = :id";
            $stmt = $db->prepare($sql);
            
            if ($stmt->execute([':password' => $hashed_password, ':id' => $_SESSION['user_id']])) {
                $_SESSION['force_password_change'] = false;
                logAudit($db, $_SESSION['user_id'], 'update', 'users', $_SESSION['user_id'], null, ['password_changed' => true]);
                $success = 'Password changed successfully! Redirecting...';
                header('Refresh: 2; URL=DASHBOARD.PHP');
            } else {
                $error = 'Failed to update password';
            }
        }
    }
}

$csrf_token = generateCSRFToken();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Change Password - ተክሉ ጌታቸው</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .container {
            width: 100%;
            max-width: 450px;
        }
        .card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        .card-header {
            text-align: center;
            margin-bottom: 30px;
        }
        .card-header i {
            font-size: 50px;
            color: #e74c3c;
            margin-bottom: 15px;
        }
        .card-header h2 {
            color: #2c3e50;
            margin-bottom: 10px;
        }
        .card-header p {
            color: #7f8c8d;
            font-size: 14px;
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: #2c3e50;
        }
        .form-input {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            font-size: 16px;
            transition: all 0.3s ease;
        }
        .form-input:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        .password-strength {
            margin-top: 8px;
            height: 5px;
            background: #e0e0e0;
            border-radius: 5px;
            overflow: hidden;
        }
        .strength-bar {
            height: 100%;
            width: 0;
            transition: all 0.3s;
            border-radius: 5px;
        }
        .strength-weak { background: #e74c3c; width: 25%; }
        .strength-fair { background: #f39c12; width: 50%; }
        .strength-good { background: #3498db; width: 75%; }
        .strength-strong { background: #27ae60; width: 100%; }
        .btn-primary {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 30px rgba(102, 126, 234, 0.4);
        }
        .error-message {
            background: #fce4e4;
            color: #c0392b;
            padding: 12px 15px;
            border-radius: 10px;
            margin-bottom: 20px;
            border-left: 4px solid #e74c3c;
        }
        .success-message {
            background: #e8f8f5;
            color: #27ae60;
            padding: 12px 15px;
            border-radius: 10px;
            margin-bottom: 20px;
            border-left: 4px solid #27ae60;
        }
        .requirements {
            margin-top: 15px;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 10px;
            font-size: 13px;
        }
        .requirements ul {
            list-style: none;
            padding: 0;
        }
        .requirements li {
            margin-bottom: 8px;
            color: #7f8c8d;
        }
        .requirements li i {
            margin-right: 8px;
            color: #e74c3c;
        }
        .requirements li.valid i {
            color: #27ae60;
        }
    </style>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="container">
        <div class="card">
            <div class="card-header">
                <i class="fas fa-lock"></i>
                <h2>Change Password</h2>
                <p>First time login. Please change your password.</p>
            </div>

            <?php if ($error): ?>
            <div class="error-message">
                <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
            </div>
            <?php endif; ?>

            <?php if ($success): ?>
            <div class="success-message">
                <i class="fas fa-check-circle"></i> <?php echo $success; ?>
            </div>
            <?php endif; ?>

            <form method="POST" action="" id="changePasswordForm">
                <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                
                <div class="form-group">
                    <label class="form-label" for="new_password">
                        <i class="fas fa-key"></i> New Password
                    </label>
                    <input type="password" id="new_password" name="new_password" class="form-input" 
                           placeholder="Enter new password" required onkeyup="checkPasswordStrength()">
                    <div class="password-strength">
                        <div class="strength-bar" id="strengthBar"></div>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label" for="confirm_password">
                        <i class="fas fa-check-circle"></i> Confirm Password
                    </label>
                    <input type="password" id="confirm_password" name="confirm_password" class="form-input" 
                           placeholder="Confirm new password" required onkeyup="checkPasswordMatch()">
                    <small id="matchMessage" style="color: #e74c3c; display: none;">
                        Passwords do not match
                    </small>
                </div>

                <button type="submit" class="btn-primary">
                    <i class="fas fa-save"></i> Change Password
                </button>
            </form>

            <div class="requirements">
                <strong>Password Requirements:</strong>
                <ul>
                    <li id="req-length"><i class="fas fa-times-circle"></i> At least 6 characters</li>
                    <li id="req-uppercase"><i class="fas fa-times-circle"></i> At least one uppercase letter</li>
                    <li id="req-number"><i class="fas fa-times-circle"></i> At least one number</li>
                    <li id="req-special"><i class="fas fa-times-circle"></i> At least one special character</li>
                </ul>
            </div>
        </div>
    </div>

    <script>
        function checkPasswordStrength() {
            var password = document.getElementById('new_password').value;
            var strengthBar = document.getElementById('strengthBar');
            var strength = 0;

            var hasLength = password.length >= 6;
            var hasUpper = /[A-Z]/.test(password);
            var hasNumber = /[0-9]/.test(password);
            var hasSpecial = /[!@#$%^&*(),.?":{}|<>]/.test(password);

            updateRequirement('req-length', hasLength);
            updateRequirement('req-uppercase', hasUpper);
            updateRequirement('req-number', hasNumber);
            updateRequirement('req-special', hasSpecial);

            if (hasLength) strength++;
            if (hasUpper) strength++;
            if (hasNumber) strength++;
            if (hasSpecial) strength++;

            strengthBar.className = 'strength-bar';
            if (strength === 1) strengthBar.classList.add('strength-weak');
            else if (strength === 2) strengthBar.classList.add('strength-fair');
            else if (strength === 3) strengthBar.classList.add('strength-good');
            else if (strength === 4) strengthBar.classList.add('strength-strong');
        }

        function updateRequirement(id, valid) {
            var element = document.getElementById(id);
            if (valid) {
                element.classList.add('valid');
                element.querySelector('i').classList.remove('fa-times-circle');
                element.querySelector('i').classList.add('fa-check-circle');
            } else {
                element.classList.remove('valid');
                element.querySelector('i').classList.remove('fa-check-circle');
                element.querySelector('i').classList.add('fa-times-circle');
            }
        }

        function checkPasswordMatch() {
            var password = document.getElementById('new_password').value;
            var confirm = document.getElementById('confirm_password').value;
            var message = document.getElementById('matchMessage');
            
            if (confirm.length > 0 && password !== confirm) {
                message.style.display = 'block';
            } else {
                message.style.display = 'none';
            }
        }

        if (window.history.replaceState) {
            window.history.replaceState(null, null, window.location.href);
        }
    </script>
</body>
</html>