<?php
require_once 'config.php';

// بررسی وضعیت ورود کاربر
if (!isset($_SESSION['user'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'کاربر وارد نشده است']);
    exit;
}

$user = $_SESSION['user'];
$data = json_decode(file_get_contents('php://input'), true);

if (!$data || !isset($data['subject_id']) || !isset($data['modules'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'داده‌های نامعتبر']);
    exit;
}

try {
    $pdo->beginTransaction();

    // بررسی دسترسی کاربر به کتاب مورد نظر
    $stmt = $pdo->prepare("SELECT id FROM subjects WHERE id = ? AND user_id = ?");
    $stmt->execute([$data['subject_id'], $user['id']]);
    if (!$stmt->fetch()) {
        throw new Exception('دسترسی غیرمجاز');
    }

    // به‌روزرسانی اطلاعات پودمان‌ها
    $updateStmt = $pdo->prepare("UPDATE modules SET name = ?, questions_count = ? WHERE id = ? AND subject_id = ?");
    
    foreach ($data['modules'] as $module) {
        $updateStmt->execute([
            $module['name'],
            $module['questions'],
            $module['id'],
            $data['subject_id']
        ]);
    }

    $pdo->commit();
    header('Content-Type: application/json');
    echo json_encode(['success' => true]);

} catch (Exception $e) {
    $pdo->rollBack();
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?> 