<?php
require_once 'config.php';

if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    $user_id = $_SESSION['user']['id'];
    
    // بررسی خالی نبودن فیلدها
    if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
        $_SESSION['error'] = 'لطفاً تمام فیلدها را پر کنید.';
        header('Location: index.php');
        exit;
    }
    
    // بررسی یکسان بودن رمز جدید و تکرار آن
    if ($new_password !== $confirm_password) {
        $_SESSION['error'] = 'رمز عبور جدید و تکرار آن یکسان نیستند.';
        header('Location: index.php');
        exit;
    }
    
    // بررسی صحت رمز عبور فعلی
    $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();
    
    if (!$user || !password_verify($current_password, $user['password'])) {
        $_SESSION['error'] = 'رمز عبور فعلی اشتباه است.';
        header('Location: index.php');
        exit;
    }
    
    // بروزرسانی رمز عبور
    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
    
    if ($stmt->execute([$hashed_password, $user_id])) {
        $_SESSION['success'] = 'رمز عبور با موفقیت تغییر کرد.';
    } else {
        $_SESSION['error'] = 'خطا در تغییر رمز عبور.';
    }
}

header('Location: index.php');
exit; 