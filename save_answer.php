<?php
require_once 'config.php';

// بررسی وضعیت ورود کاربر
if (!isset($_SESSION['user'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

// دریافت داده‌های ارسالی
$data = json_decode(file_get_contents('php://input'), true);

if (!$data || !isset($data['module_id']) || !isset($data['question_number'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Invalid data']);
    exit;
}

$moduleId = (int)$data['module_id'];
$questionNumber = (int)$data['question_number'];
$answer = isset($data['answer']) ? (int)$data['answer'] : null;
$userId = $_SESSION['user']['id'];

try {
    if ($answer === null) {
        // حذف پاسخ
        $stmt = $pdo->prepare("DELETE FROM answers WHERE user_id = ? AND module_id = ? AND question_number = ?");
        $stmt->execute([$userId, $moduleId, $questionNumber]);
    } else {
        // بررسی وجود پاسخ قبلی
        $stmt = $pdo->prepare("SELECT id FROM answers WHERE user_id = ? AND module_id = ? AND question_number = ?");
        $stmt->execute([$userId, $moduleId, $questionNumber]);
        $existingAnswer = $stmt->fetch();
        
        if ($existingAnswer) {
            // بروزرسانی پاسخ موجود
            $stmt = $pdo->prepare("UPDATE answers SET answer = ? WHERE id = ?");
            $stmt->execute([$answer, $existingAnswer['id']]);
        } else {
            // افزودن پاسخ جدید
            $stmt = $pdo->prepare("INSERT INTO answers (user_id, module_id, question_number, answer) VALUES (?, ?, ?, ?)");
            $stmt->execute([$userId, $moduleId, $questionNumber, $answer]);
        }
    }
    
    header('Content-Type: application/json');
    echo json_encode(['success' => true]);
    
} catch (PDOException $e) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
} 