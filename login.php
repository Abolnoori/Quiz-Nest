<?php
require_once 'config.php';

// اگر کاربر قبلاً لاگین کرده است، به صفحه اصلی منتقل شود
if (isset($_SESSION['user'])) {
    header('Location: index.php');
    exit;
}

$error = '';

// پردازش فرم ورود
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'login') {
        $username = $_POST['username'];
        $password = $_POST['password'];
        
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
            $error = 'نام کاربری یا رمز عبور اشتباه است';
        }
    } 
    // پردازش فرم ثبت‌نام
    elseif ($_POST['action'] === 'register') {
        $fullname = $_POST['fullname'];
        $username = $_POST['username'];
        $password = $_POST['password'];
        $confirmPassword = $_POST['confirmPassword'];
        
        if ($password !== $confirmPassword) {
            $error = 'رمز عبور و تکرار آن یکسان نیستند';
        } else {
            // بررسی تکراری نبودن نام کاربری
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = ?");
            $stmt->execute([$username]);
            if ($stmt->fetchColumn() > 0) {
                $error = 'این نام کاربری قبلاً استفاده شده است';
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
                    $error = 'خطا در ثبت‌نام. لطفاً دوباره تلاش کنید';
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
    <link href="https://cdn.jsdelivr.net/npm/vazirmatn@33.0.3/Vazirmatn-font-face.css" rel="stylesheet">
    <link href="styles.css" rel="stylesheet">
    <style>
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
            margin-bottom: 1rem;
            text-align: center;
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
                
                <?php if ($error): ?>
                    <div class="alert alert-danger text-center mb-4"><?php echo $error; ?></div>
                <?php endif; ?>
                
                <div class="card auth-card">
                    <div class="auth-tabs">
                        <ul class="nav nav-pills nav-fill mb-0" role="tablist">
                            <li class="nav-item">
                                <button class="nav-link active" data-bs-toggle="pill" data-bs-target="#login-tab">
                                    ورود
                                </button>
                            </li>
                            <li class="nav-item">
                                <button class="nav-link" data-bs-toggle="pill" data-bs-target="#register-tab">
                                    ثبت‌نام
                                </button>
                            </li>
                        </ul>
                    </div>
                    
                    <div class="tab-content">
                        <!-- فرم ورود -->
                        <div class="tab-pane fade show active" id="login-tab">
                            <div class="auth-form">
                                <form method="POST">
                                    <input type="hidden" name="action" value="login">
                                    <div class="mb-3">
                                        <label class="form-label">نام کاربری</label>
                                        <input type="text" class="form-control" name="username" required>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">رمز عبور</label>
                                        <input type="password" class="form-control" name="password" required>
                                    </div>
                                    <button type="submit" class="btn btn-primary w-100 btn-auth">ورود</button>
                                </form>
                            </div>
                        </div>
                        
                        <!-- فرم ثبت‌نام -->
                        <div class="tab-pane fade" id="register-tab">
                            <div class="auth-form">
                                <form method="POST">
                                    <input type="hidden" name="action" value="register">
                                    <div class="mb-3">
                                        <label class="form-label">نام و نام خانوادگی</label>
                                        <input type="text" class="form-control" name="fullname" required>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">نام کاربری</label>
                                        <input type="text" class="form-control" name="username" required>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">رمز عبور</label>
                                        <input type="password" class="form-control" name="password" required>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">تکرار رمز عبور</label>
                                        <input type="password" class="form-control" name="confirmPassword" required>
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