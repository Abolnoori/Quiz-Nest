<?php
require_once 'config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

try {
    $userId = $_SESSION['user']['id'];
    
    // دریافت کتاب‌ها
    $stmt = $pdo->prepare("SELECT * FROM subjects WHERE user_id = ?");
    $stmt->execute([$userId]);
    $subjects = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // دریافت پودمان‌ها
    $modules = [];
    if (!empty($subjects)) {
        $stmt = $pdo->prepare("SELECT * FROM modules WHERE subject_id = ?");
        foreach ($subjects as $subject) {
            $stmt->execute([$subject['id']]);
            $modules[$subject['id']] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
    }
    
    // دریافت پاسخ‌ها
    $stmt = $pdo->prepare("SELECT * FROM answers WHERE user_id = ?");
    $stmt->execute([$userId]);
    $answers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // تبدیل پاسخ‌ها به فرمت مناسب
    $userAnswers = [];
    foreach ($answers as $answer) {
        $key = "module_{$answer['module_id']}_{$answer['question_number']}";
        $userAnswers[$key] = $answer['answer'];
    }
    
    echo json_encode([
        'subjects' => $subjects,
        'modules' => $modules,
        'userAnswers' => $userAnswers
    ]);
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error']);
}
?> 