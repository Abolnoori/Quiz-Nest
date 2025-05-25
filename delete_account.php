<?php
require_once 'config.php';

if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = $_POST['password'];
    $user_id = $_SESSION['user']['id'];
    
    // بررسی خالی نبودن رمز عبور
    if (empty($password)) {
        $_SESSION['error'] = 'لطفاً رمز عبور خود را وارد کنید.';
        header('Location: index.php');
        exit;
    }
    
    // بررسی صحت رمز عبور
    $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();
    
    if (!$user || !password_verify($password, $user['password'])) {
        $_SESSION['error'] = 'رمز عبور اشتباه است.';
        header('Location: index.php');
        exit;
    }
    
    try {
        // شروع تراکنش
        $pdo->beginTransaction();
        
        // حذف پاسخ‌های کاربر
        $stmt = $pdo->prepare("DELETE FROM answers WHERE user_id = ?");
        $stmt->execute([$user_id]);
        
        // حذف پودمان‌های مربوط به کتاب‌های کاربر
        $stmt = $pdo->prepare("DELETE modules FROM modules 
                              INNER JOIN subjects ON modules.subject_id = subjects.id 
                              WHERE subjects.user_id = ?");
        $stmt->execute([$user_id]);
        
        // حذف کتاب‌های کاربر
        $stmt = $pdo->prepare("DELETE FROM subjects WHERE user_id = ?");
        $stmt->execute([$user_id]);
        
        // حذف حساب کاربری
        $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        
        // پایان تراکنش
        $pdo->commit();
        
        // خروج از سیستم
        session_destroy();
        header('Location: login.php?message=' . urlencode('حساب کاربری شما با موفقیت حذف شد.'));
        exit;
        
    } catch (Exception $e) {
        // برگرداندن تغییرات در صورت بروز خطا
        $pdo->rollBack();
        $_SESSION['error'] = 'خطا در حذف حساب کاربری.';
        header('Location: index.php');
        exit;
    }
}

header('Location: index.php');
exit; 