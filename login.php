<?php
require_once 'CONFIG/CONFIG.PHP';
require_once 'INCLUDES/AUTH.PHP';

if (isLoggedIn()) {
    if ($_SESSION['force_password_change']) {
        header('Location: CHANGE_PASSWORD.PHP');
    } else {
        header('Location: DASHBOARD.PHP');
    }
    exit();
}

$error = '';
$username = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid security token';
    } else {
        $username = sanitizeInput($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        
        if (empty($username) || empty($password)) {
            $error = 'Please enter username and password';
        } else {
            $result = loginUser($db, $username, $password, $_SERVER['REMOTE_ADDR']);
            if ($result['success']) {
                header('Location: ' . ($result['force_password_change'] ? 'CHANGE_PASSWORD.PHP' : 'DASHBOARD.PHP'));
                exit();
            } else {
                $error = $result['message'];
            }
        }
    }
}

$csrf_token = generateCSRFToken();
?>
<!DOCTYPE html>
<html lang="am">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - ተክሉ ጌታቸው የእንስሳት ተዋጽኦ</title>
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
        .login-container {
            width: 100%;
            max-width: 450px;
        }
        .login-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        .logo-section {
            text-align: center;
            margin-bottom: 30px;
        }
        .logo-icon {
            font-size: 50px;
            color: #27ae60;
            margin-bottom: 15px;
        }
        .logo-title {
            font-size: 24px;
            font-weight: bold;
            color: #2c3e50;
            margin-bottom: 5px;
        }
        .logo-subtitle {
            font-size: 14px;
            color: #7f8c8d;
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: #2c3e50;
            font-size: 14px;
        }
        .form-input {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            font-size: 16px;
            transition: all 0.3s ease;
            background: white;
        }
        .form-input:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        .password-wrapper {
            position: relative;
        }
        .toggle-password {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: #95a5a6;
            background: none;
            border: none;
            font-size: 18px;
        }
        .login-btn {
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
            margin-top: 10px;
        }
        .login-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 30px rgba(102, 126, 234, 0.4);
        }
        .login-btn:active {
            transform: translateY(0);
        }
        .forgot-password {
            text-align: center;
            margin-top: 20px;
        }
        .forgot-password a {
            color: #667eea;
            text-decoration: none;
            font-size: 14px;
            transition: color 0.3s;
        }
        .forgot-password a:hover {
            color: #764ba2;
            text-decoration: underline;
        }
        .error-message {
            background: #fce4e4;
            color: #c0392b;
            padding: 12px 15px;
            border-radius: 10px;
            margin-bottom: 20px;
            font-size: 14px;
            border-left: 4px solid #e74c3c;
            display: <?php echo $error ? 'block' : 'none'; ?>;
        }
        .demo-info {
            margin-top: 20px;
            padding: 15px;
            background: #f0f8ff;
            border-radius: 10px;
            font-size: 13px;
            color: #2c3e50;
        }
        .demo-info strong {
            color: #667eea;
        }
        .language-selector {
            text-align: center;
            margin-bottom: 20px;
        }
        .language-selector select {
            padding: 8px 15px;
            border-radius: 25px;
            border: 1px solid #e0e0e0;
            background: white;
            cursor: pointer;
            font-size: 14px;
        }
        @media (max-width: 480px) {
            .login-card {
                padding: 25px;
            }
            .logo-title {
                font-size: 20px;
            }
        }
    </style>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="login-container">
        <div class="login-card">
            <div class="logo-section">
                <div class="logo-icon">
                    <i class="fas fa-leaf"></i>
                </div>
                <div class="logo-title">ተክሉ ጌታቸው</div>
                <div class="logo-subtitle">የእንስሳት ተዋጽኦ አስተዳደር ሲስተም</div>
            </div>

            <div class="language-selector">
                <select id="loginLanguage" onchange="changeLoginLanguage(this.value)">
                    <option value="am">አማርኛ</option>
                    <option value="en">English</option>
                    <option value="or">Afaan Oromo</option>
                </select>
            </div>

            <div class="error-message" id="errorMessage">
                <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
            </div>

            <form method="POST" action="" id="loginForm">
                <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                
                <div class="form-group">
                    <label class="form-label" for="username">
                        <i class="fas fa-user"></i> Username / የተጠቃሚ ስም
                    </label>
                    <input type="text" id="username" name="username" class="form-input" 
                           value="<?php echo htmlspecialchars($username); ?>"
                           placeholder="Enter username" required autocomplete="username">
                </div>

                <div class="form-group">
                    <label class="form-label" for="password">
                        <i class="fas fa-lock"></i> Password / የይለፍ ቃል
                    </label>
                    <div class="password-wrapper">
                        <input type="password" id="password" name="password" class="form-input" 
                               placeholder="Enter password" required autocomplete="current-password">
                        <button type="button" class="toggle-password" onclick="togglePassword()">
                            <i class="fas fa-eye" id="toggleIcon"></i>
                        </button>
                    </div>
                </div>

                <div class="form-group">
                    <label style="display: flex; align-items: center; font-size: 14px; color: #7f8c8d;">
                        <input type="checkbox" id="rememberMe" name="remember_me" style="margin-right: 10px;">
                        Remember me / አስታውሰኝ
                    </label>
                </div>

                <button type="submit" class="login-btn">
                    <i class="fas fa-sign-in-alt"></i> Login / ግባ
                </button>
            </form>

            <div class="forgot-password">
                <a href="FORGOT_PASSWORD.PHP">
                    <i class="fas fa-question-circle"></i> Forgot Password? / የይለፍ ቃል ረሳሁ?
                </a>
            </div>

            <div class="demo-info">
                <strong>Demo Credentials:</strong><br>
                Owner: username: <strong>owner</strong> | pass: <strong>123</strong><br>
                Doctor: username: <strong>doctor1</strong> | pass: <strong>123</strong><br>
                <small style="color: #e74c3c;">Default password for all: <strong>123</strong></small>
            </div>
        </div>
    </div>

    <script>
        function togglePassword() {
            var passwordInput = document.getElementById('password');
            var toggleIcon = document.getElementById('toggleIcon');
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                toggleIcon.classList.remove('fa-eye');
                toggleIcon.classList.add('fa-eye-slash');
            } else {
                passwordInput.type = 'password';
                toggleIcon.classList.remove('fa-eye-slash');
                toggleIcon.classList.add('fa-eye');
            }
        }

        function changeLoginLanguage(lang) {
            localStorage.setItem('language', lang);
            if (lang === 'am') {
                document.querySelector('.logo-title').textContent = 'ተክሉ ጌታቸው';
                document.querySelector('.logo-subtitle').textContent = 'የእንስሳት ተዋጽኦ አስተዳደር ሲስተም';
            } else if (lang === 'en') {
                document.querySelector('.logo-title').textContent = 'Teklu Getachew';
                document.querySelector('.logo-subtitle').textContent = 'Livestock Products Management System';
            } else if (lang === 'or') {
                document.querySelector('.logo-title').textContent = 'Teklu Getachew';
                document.querySelector('.logo-subtitle').textContent = 'Sirna Bulchiinsa Oomisha Beelladaa';
            }
        }

        var savedLang = localStorage.getItem('language') || 'am';
        document.getElementById('loginLanguage').value = savedLang;
        changeLoginLanguage(savedLang);

        if (window.history.replaceState) {
            window.history.replaceState(null, null, window.location.href);
        }

        var errorMsg = document.getElementById('errorMessage');
        if (errorMsg.style.display !== 'none') {
            setTimeout(function() {
                errorMsg.style.transition = 'opacity 0.5s';
                errorMsg.style.opacity = '0';
                setTimeout(function() {
                    errorMsg.style.display = 'none';
                }, 500);
            }, 5000);
        }

        document.getElementById('loginForm').addEventListener('submit', function(e) {
            var btn = this.querySelector('.login-btn');
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Logging in...';
            btn.disabled = true;
        });
    </script>
</body>
</html>