<?php
require_once 'CONFIG/CONFIG.PHP';

$error = '';
$success = '';
$step = 1;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid security token';
    } else {
        $action = $_POST['action'] ?? '';
        
        if ($action === 'send_code') {
            $email = sanitizeInput($_POST['email'] ?? '');
            $sql = "SELECT id, username FROM users WHERE email = :email AND is_active = TRUE LIMIT 1";
            $stmt = $db->prepare($sql);
            $stmt->execute([':email' => $email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($user) {
                $token = bin2hex(random_bytes(32));
                $expires = date('Y-m-d H:i:s', time() + 3600);
                $sql = "INSERT INTO password_resets (user_id, token, expires_at) VALUES (:uid, :token, :expires)";
                $stmt = $db->prepare($sql);
                $stmt->execute([':uid' => $user['id'], ':token' => $token, ':expires' => $expires]);
                
                $success = "Reset code: <strong>$token</strong> (Demo - in production this would be emailed)";
                $step = 2;
                $_SESSION['reset_user_id'] = $user['id'];
                $_SESSION['reset_token'] = $token;
            } else {
                $error = 'Email not found';
            }
        } elseif ($action === 'verify_code') {
            $code = sanitizeInput($_POST['code'] ?? '');
            if ($code === $_SESSION['reset_token']) {
                $step = 3;
            } else {
                $error = 'Invalid reset code';
            }
        } elseif ($action === 'reset_password') {
            $password = $_POST['password'] ?? '';
            $confirm = $_POST['confirm_password'] ?? '';
            
            if (strlen($password) < 6) {
                $error = 'Password must be at least 6 characters';
            } elseif ($password !== $confirm) {
                $error = 'Passwords do not match';
            } else {
                $hashed = password_hash($password, PASSWORD_BCRYPT);
                $sql = "UPDATE users SET password = :password, force_password_change = FALSE WHERE id = :id";
                $stmt = $db->prepare($sql);
                
                if ($stmt->execute([':password' => $hashed, ':id' => $_SESSION['reset_user_id']])) {
                    $sql = "UPDATE password_resets SET used = TRUE WHERE token = :token";
                    $stmt = $db->prepare($sql);
                    $stmt->execute([':token' => $_SESSION['reset_token']]);
                    unset($_SESSION['reset_user_id'], $_SESSION['reset_token']);
                    $success = 'Password reset successfully! <a href="LOGIN.PHP">Login here</a>';
                    $step = 1;
                } else {
                    $error = 'Failed to reset password';
                }
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
    <title>Forgot Password - ተክሉ ጌታቸው</title>
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
        }
        .card-header {
            text-align: center;
            margin-bottom: 30px;
        }
        .card-header i {
            font-size: 50px;
            color: #f39c12;
            margin-bottom: 15px;
        }
        .card-header h2 {
            color: #2c3e50;
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
        .back-link {
            text-align: center;
            margin-top: 20px;
        }
        .back-link a {
            color: #667eea;
            text-decoration: none;
        }
        .back-link a:hover {
            text-decoration: underline;
        }
        .step-indicator {
            display: flex;
            justify-content: center;
            margin-bottom: 30px;
        }
        .step {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            background: #e0e0e0;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 10px;
            font-weight: bold;
            color: white;
        }
        .step.active {
            background: #667eea;
        }
        .step.completed {
            background: #27ae60;
        }
    </style>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="container">
        <div class="card">
            <div class="card-header">
                <i class="fas fa-unlock-alt"></i>
                <h2>Forgot Password</h2>
                <p>Step <?php echo $step; ?> of 3</p>
            </div>

            <div class="step-indicator">
                <div class="step <?php echo $step >= 1 ? 'active' : ''; ?> <?php echo $step > 1 ? 'completed' : ''; ?>">1</div>
                <div class="step <?php echo $step >= 2 ? 'active' : ''; ?> <?php echo $step > 2 ? 'completed' : ''; ?>">2</div>
                <div class="step <?php echo $step >= 3 ? 'active' : ''; ?>">3</div>
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

            <?php if ($step === 1): ?>
            <form method="POST" action="">
                <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                <input type="hidden" name="action" value="send_code">
                <div class="form-group">
                    <label class="form-label" for="email">
                        <i class="fas fa-envelope"></i> Email Address
                    </label>
                    <input type="email" id="email" name="email" class="form-input" 
                           placeholder="Enter your email" required>
                </div>
                <button type="submit" class="btn-primary">
                    <i class="fas fa-paper-plane"></i> Send Reset Code
                </button>
            </form>
            <?php elseif ($step === 2): ?>
            <form method="POST" action="">
                <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                <input type="hidden" name="action" value="verify_code">
                <div class="form-group">
                    <label class="form-label" for="code">
                        <i class="fas fa-key"></i> Reset Code
                    </label>
                    <input type="text" id="code" name="code" class="form-input" 
                           placeholder="Enter reset code" required>
                </div>
                <button type="submit" class="btn-primary">
                    <i class="fas fa-check"></i> Verify Code
                </button>
            </form>
            <?php elseif ($step === 3): ?>
            <form method="POST" action="">
                <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                <input type="hidden" name="action" value="reset_password">
                <div class="form-group">
                    <label class="form-label" for="password">
                        <i class="fas fa-lock"></i> New Password
                    </label>
                    <input type="password" id="password" name="password" class="form-input" 
                           placeholder="Enter new password" required minlength="6">
                </div>
                <div class="form-group">
                    <label class="form-label" for="confirm_password">
                        <i class="fas fa-check-circle"></i> Confirm Password
                    </label>
                    <input type="password" id="confirm_password" name="confirm_password" class="form-input" 
                           placeholder="Confirm new password" required>
                </div>
                <button type="submit" class="btn-primary">
                    <i class="fas fa-save"></i> Reset Password
                </button>
            </form>
            <?php endif; ?>

            <div class="back-link">
                <a href="LOGIN.PHP">
                    <i class="fas fa-arrow-left"></i> Back to Login
                </a>
            </div>
        </div>
    </div>
</body>
</html>