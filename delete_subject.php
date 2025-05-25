<?php
require_once 'config.php';

// بررسی وضعیت ورود کاربر
if (!isset($_SESSION['user'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$user = $_SESSION['user'];

// دریافت داده‌های ارسالی
$data = json_decode(file_get_contents('php://input'), true);
$subject_id = $data['subject_id'] ?? null;

if (!$subject_id) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Missing subject ID']);
    exit;
}

try {
    // بررسی مالکیت کتاب
    $stmt = $pdo->prepare("SELECT user_id FROM subjects WHERE id = ?");
    $stmt->execute([$subject_id]);
    $subject = $stmt->fetch();

    if (!$subject || $subject['user_id'] != $user['id']) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        exit;
    }

    // شروع تراکنش
    $pdo->beginTransaction();

    // حذف پاسخ‌های مربوط به پودمان‌های این کتاب
    $stmt = $pdo->prepare("DELETE answers FROM answers 
                          INNER JOIN modules ON answers.module_id = modules.id 
                          WHERE modules.subject_id = ?");
    $stmt->execute([$subject_id]);

    // حذف پودمان‌های کتاب
    $stmt = $pdo->prepare("DELETE FROM modules WHERE subject_id = ?");
    $stmt->execute([$subject_id]);

    // حذف کتاب
    $stmt = $pdo->prepare("DELETE FROM subjects WHERE id = ?");
    $stmt->execute([$subject_id]);

    // تایید تراکنش
    $pdo->commit();

    header('Content-Type: application/json');
    echo json_encode(['success' => true]);
} catch (PDOException $e) {
    // برگرداندن تراکنش در صورت خطا
    $pdo->rollBack();
    
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
} 