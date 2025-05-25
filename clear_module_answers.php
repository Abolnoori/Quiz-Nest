<?php
require_once 'config.php';

if (!isset($_SESSION['user'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

// دریافت داده‌های ارسالی
$data = json_decode(file_get_contents('php://input'), true);
$module_id = $data['module_id'] ?? null;
$user_id = $_SESSION['user']['id'];

if (!$module_id) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Module ID is required']);
    exit;
}

try {
    // حذف همه پاسخ‌های کاربر برای این پودمان
    $stmt = $pdo->prepare("DELETE FROM answers WHERE user_id = ? AND module_id = ?");
    $stmt->execute([$user_id, $module_id]);
    
    header('Content-Type: application/json');
    echo json_encode(['success' => true]);
    
} catch (Exception $e) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
} 