<?php
require_once 'config.php';

// بررسی وضعیت ورود کاربر
if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'];
    $grade = $_POST['grade'];
    $questionsCounts = $_POST['questions_count'];
    $userId = $_SESSION['user']['id'];
    
    // اعتبارسنجی تعداد سوالات
    if (!is_array($questionsCounts) || count($questionsCounts) !== 5) {
        die("خطا: تعداد سوالات نامعتبر است");
    }
    
    foreach ($questionsCounts as $count) {
        if (!is_numeric($count) || $count < 1 || $count > 100) {
            die("خطا: تعداد سوالات باید بین 1 تا 100 باشد");
        }
    }
    
    try {
        // شروع تراکنش
        $pdo->beginTransaction();
        
        // افزودن کتاب جدید
        $stmt = $pdo->prepare("INSERT INTO subjects (user_id, name, grade) VALUES (?, ?, ?)");
        $stmt->execute([$userId, $name, $grade]);
        $subjectId = $pdo->lastInsertId();
        
        // افزودن پودمان‌ها
        $stmt = $pdo->prepare("INSERT INTO modules (subject_id, name, questions_count) VALUES (?, ?, ?)");
        for ($i = 0; $i < 5; $i++) {
            $stmt->execute([$subjectId, "پودمان " . ($i + 1), $questionsCounts[$i]]);
        }
        
        // تایید تراکنش
        $pdo->commit();
        
        header('Location: index.php');
        exit;
        
    } catch (PDOException $e) {
        // برگرداندن تراکنش در صورت خطا
        $pdo->rollBack();
        die("خطا در افزودن کتاب: " . $e->getMessage());
    }
}

// در صورت درخواست GET، انتقال به صفحه اصلی
header('Location: index.php');
exit; 