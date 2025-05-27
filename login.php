<?php
require_once 'config.php';

// اگر کاربر قبلاً لاگین کرده است، به صفحه اصلی منتقل شود
if (isset($_SESSION['user'])) {
    header('Location: index.php');
    exit;
}

$errors = [];

// پردازش فرم ورود
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'login') {
        $username = $_POST['username'];
        $password = $_POST['password'];
        
        // اعتبارسنجی نام کاربری
        if (empty($username)) {
            $errors['login_username'] = 'لطفاً نام کاربری را وارد کنید';
        }
        
        // اعتبارسنجی رمز عبور
        if (empty($password)) {
            $errors['login_password'] = 'لطفاً رمز عبور را وارد کنید';
        }
        
        if (empty($errors)) {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch();
        
        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user'] = [
                'id' => $user['id'],
                'username' => $user['username'],
                'fullname' => $user['fullname']
            ];
            header('Location: index.php');
            exit;
        } else {
                $errors['login_general'] = 'نام کاربری یا رمز عبور اشتباه است';
            }
        }
    } 
    // پردازش فرم ثبت‌نام
    elseif ($_POST['action'] === 'register') {
        $fullname = $_POST['fullname'];
        $username = $_POST['username'];
        $password = $_POST['password'];
        $confirmPassword = $_POST['confirmPassword'];
        
        // اعتبارسنجی نام و نام خانوادگی
        if (empty($fullname)) {
            $errors['register_fullname'] = 'لطفاً نام و نام خانوادگی را وارد کنید';
        }
        
        // اعتبارسنجی نام کاربری
        if (empty($username)) {
            $errors['register_username'] = 'لطفاً نام کاربری را وارد کنید';
        } elseif (strlen($username) < 4) {
            $errors['register_username'] = 'نام کاربری باید حداقل 4 کاراکتر باشد';
        }
        
        // اعتبارسنجی رمز عبور
        if (empty($password)) {
            $errors['register_password'] = 'لطفاً رمز عبور را وارد کنید';
        } elseif (strlen($password) < 6) {
            $errors['register_password'] = 'رمز عبور باید حداقل 6 کاراکتر باشد';
        }
        
        // اعتبارسنجی تکرار رمز عبور
        if (empty($confirmPassword)) {
            $errors['register_confirmPassword'] = 'لطفاً تکرار رمز عبور را وارد کنید';
        } elseif ($password !== $confirmPassword) {
            $errors['register_confirmPassword'] = 'رمز عبور و تکرار آن یکسان نیستند';
        }
        
        if (empty($errors)) {
            // بررسی تکراری نبودن نام کاربری
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = ?");
            $stmt->execute([$username]);
            if ($stmt->fetchColumn() > 0) {
                $errors['register_username'] = 'این نام کاربری قبلاً استفاده شده است';
            } else {
                // ثبت کاربر جدید
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("INSERT INTO users (username, password, fullname) VALUES (?, ?, ?)");
                if ($stmt->execute([$username, $hashedPassword, $fullname])) {
                    $userId = $pdo->lastInsertId();
                    $_SESSION['user'] = [
                        'id' => $userId,
                        'username' => $username,
                        'fullname' => $fullname
                    ];
                    header('Location: index.php');
                    exit;
                } else {
                    $errors['register_general'] = 'خطا در ثبت‌نام. لطفاً دوباره تلاش کنید';
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ورود به سامانه آزمون</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="styles.css" rel="stylesheet">
    <style>
        @font-face {
            font-family: 'Vazirmatn';
            src: url('fonts/Vazirmatn-Thin.ttf') format('truetype');
            font-weight: 100;
            font-style: normal;
            font-display: swap;
        }
        
        @font-face {
            font-family: 'Vazirmatn';
            src: url('fonts/Vazirmatn-ExtraLight.ttf') format('truetype');
            font-weight: 200;
            font-style: normal;
            font-display: swap;
        }
        
        @font-face {
            font-family: 'Vazirmatn';
            src: url('fonts/Vazirmatn-Light.ttf') format('truetype');
            font-weight: 300;
            font-style: normal;
            font-display: swap;
        }
        
        @font-face {
            font-family: 'Vazirmatn';
            src: url('fonts/Vazirmatn-Regular.ttf') format('truetype');
            font-weight: 400;
            font-style: normal;
            font-display: swap;
        }
        
        @font-face {
            font-family: 'Vazirmatn';
            src: url('fonts/Vazirmatn-Medium.ttf') format('truetype');
            font-weight: 500;
            font-style: normal;
            font-display: swap;
        }
        
        @font-face {
            font-family: 'Vazirmatn';
            src: url('fonts/Vazirmatn-SemiBold.ttf') format('truetype');
            font-weight: 600;
            font-style: normal;
            font-display: swap;
        }
        
        @font-face {
            font-family: 'Vazirmatn';
            src: url('fonts/Vazirmatn-Bold.ttf') format('truetype');
            font-weight: 700;
            font-style: normal;
            font-display: swap;
        }
        
        @font-face {
            font-family: 'Vazirmatn';
            src: url('fonts/Vazirmatn-ExtraBold.ttf') format('truetype');
            font-weight: 800;
            font-style: normal;
            font-display: swap;
        }
        
        @font-face {
            font-family: 'Vazirmatn';
            src: url('fonts/Vazirmatn-Black.ttf') format('truetype');
            font-weight: 900;
            font-style: normal;
            font-display: swap;
        }

        body {
            font-family: "Vazirmatn", system-ui, -apple-system, sans-serif;
            background-color: #f8f9fa;
            min-height: 100vh;
            display: flex;
            align-items: center;
        }
        .auth-card {
            border-radius: 15px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            border: none;
            overflow: hidden;
        }
        .auth-tabs {
            background-color: #f8f9fa;
            padding: 1rem;
        }
        .auth-tabs .nav-link {
            color: #6c757d;
            border: none;
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            transition: all 0.3s ease;
        }
        .auth-tabs .nav-link.active {
            color: #0d6efd;
            background-color: #fff;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
        }
        .auth-form {
            padding: 2rem;
        }
        .form-control {
            padding: 0.75rem 1rem;
            border-radius: 8px;
        }
        .btn-auth {
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            font-weight: 500;
        }
        .error-message {
            color: #dc3545;
            font-size: 0.875rem;
            margin-top: 0.25rem;
            display: block;
        }
        .form-control.is-invalid {
            border-color: #dc3545;
        }
        .form-control.is-invalid:focus {
            border-color: #dc3545;
            box-shadow: 0 0 0 0.25rem rgba(220, 53, 69, 0.25);
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-6 col-lg-5">
                <div class="text-center mb-4">
                    <h2>سامانه پاسخ‌دون</h2>
                    <p class="text-muted">برای ادامه وارد حساب کاربری خود شوید</p>
                </div>
                
                <div class="card auth-card">
                    <div class="auth-tabs">
                        <ul class="nav nav-pills nav-fill mb-0" role="tablist">
                            <li class="nav-item">
                                <button class="nav-link <?php echo (!isset($_POST['action']) || $_POST['action'] === 'login') ? 'active' : ''; ?>" data-bs-toggle="pill" data-bs-target="#login-tab">
                                    ورود
                                </button>
                            </li>
                            <li class="nav-item">
                                <button class="nav-link <?php echo (isset($_POST['action']) && $_POST['action'] === 'register') ? 'active' : ''; ?>" data-bs-toggle="pill" data-bs-target="#register-tab">
                                    ثبت‌نام
                                </button>
                            </li>
                        </ul>
                    </div>
                    
                    <div class="tab-content">
                        <!-- فرم ورود -->
                        <div class="tab-pane fade <?php echo (!isset($_POST['action']) || $_POST['action'] === 'login') ? 'show active' : ''; ?>" id="login-tab">
                            <div class="auth-form">
                                <form method="POST" novalidate>
                                    <input type="hidden" name="action" value="login">
                                    <div class="mb-3">
                                        <label class="form-label">نام کاربری</label>
                                        <input type="text" class="form-control <?php echo isset($errors['login_username']) ? 'is-invalid' : ''; ?>" 
                                               name="username" value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>" required>
                                        <?php if (isset($errors['login_username'])): ?>
                                            <div class="error-message"><?php echo $errors['login_username']; ?></div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">رمز عبور</label>
                                        <input type="password" class="form-control <?php echo isset($errors['login_password']) || isset($errors['login_general']) ? 'is-invalid' : ''; ?>" 
                                               name="password" required>
                                        <?php if (isset($errors['login_password'])): ?>
                                            <div class="error-message"><?php echo $errors['login_password']; ?></div>
                                        <?php elseif (isset($errors['login_general'])): ?>
                                            <div class="error-message"><?php echo $errors['login_general']; ?></div>
                                        <?php endif; ?>
                                    </div>
                                    <button type="submit" class="btn btn-primary w-100 btn-auth">ورود</button>
                                </form>
                            </div>
                        </div>
                        
                        <!-- فرم ثبت‌نام -->
                        <div class="tab-pane fade <?php echo (isset($_POST['action']) && $_POST['action'] === 'register') ? 'show active' : ''; ?>" id="register-tab">
                            <div class="auth-form">
                                <?php if (isset($errors['register_general'])): ?>
                                    <div class="alert alert-danger text-center mb-4"><?php echo $errors['register_general']; ?></div>
                                <?php endif; ?>
                                <form method="POST" novalidate>
                                    <input type="hidden" name="action" value="register">
                                    <div class="mb-3">
                                        <label class="form-label">نام و نام خانوادگی</label>
                                        <input type="text" class="form-control <?php echo isset($errors['register_fullname']) ? 'is-invalid' : ''; ?>" 
                                               name="fullname" value="<?php echo isset($_POST['fullname']) ? htmlspecialchars($_POST['fullname']) : ''; ?>" required>
                                        <?php if (isset($errors['register_fullname'])): ?>
                                            <div class="error-message"><?php echo $errors['register_fullname']; ?></div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">نام کاربری</label>
                                        <input type="text" class="form-control <?php echo isset($errors['register_username']) ? 'is-invalid' : ''; ?>" 
                                               name="username" value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>" required>
                                        <?php if (isset($errors['register_username'])): ?>
                                            <div class="error-message"><?php echo $errors['register_username']; ?></div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">رمز عبور</label>
                                        <input type="password" class="form-control <?php echo isset($errors['register_password']) ? 'is-invalid' : ''; ?>" 
                                               name="password" required>
                                        <?php if (isset($errors['register_password'])): ?>
                                            <div class="error-message"><?php echo $errors['register_password']; ?></div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">تکرار رمز عبور</label>
                                        <input type="password" class="form-control <?php echo isset($errors['register_confirmPassword']) ? 'is-invalid' : ''; ?>" 
                                               name="confirmPassword" required>
                                        <?php if (isset($errors['register_confirmPassword'])): ?>
                                            <div class="error-message"><?php echo $errors['register_confirmPassword']; ?></div>
                                        <?php endif; ?>
                                    </div>
                                    <button type="submit" class="btn btn-primary w-100 btn-auth">ثبت‌نام</button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 