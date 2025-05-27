<?php
require_once 'config.php';

// بررسی وضعیت ورود کاربر
if (!isset($_SESSION['user'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$user = $_SESSION['user'];
$subject_id = $_GET['subject_id'] ?? null;

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

    // دریافت اطلاعات پودمان‌ها
    $stmt = $pdo->prepare("SELECT id, name, questions_count FROM modules WHERE subject_id = ? ORDER BY id");
    $stmt->execute([$subject_id]);
    $modules = $stmt->fetchAll(PDO::FETCH_ASSOC);

    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'modules' => $modules]);

} catch (PDOException $e) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
} 