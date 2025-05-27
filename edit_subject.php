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
$subject_id = $_POST['subject_id'] ?? null;
$name = $_POST['name'] ?? null;
$grade = $_POST['grade'] ?? null;
$questions_counts = $_POST['questions_count'] ?? null;

// اعتبارسنجی داده‌ها
if (!$subject_id || !$name || !$grade || !$questions_counts) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'فیلدهای ضروری را پر کنید']);
    exit;
}

// اعتبارسنجی تعداد سوالات
if (!is_array($questions_counts) || count($questions_counts) !== 5) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'تعداد سوالات نامعتبر است']);
    exit;
}

foreach ($questions_counts as $count) {
    if (!is_numeric($count) || $count < 1 || $count > 200) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'تعداد سوالات باید بین 1 تا 200 باشد']);
        exit;
    }
}

try {
    // بررسی مالکیت کتاب
    $stmt = $pdo->prepare("SELECT user_id FROM subjects WHERE id = ?");
    $stmt->execute([$subject_id]);
    $subject = $stmt->fetch();

    if (!$subject || $subject['user_id'] != $user['id']) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'شما اجازه ویرایش این کتاب را ندارید']);
        exit;
    }

    // شروع تراکنش
    $pdo->beginTransaction();

    // بروزرسانی اطلاعات کتاب
    $stmt = $pdo->prepare("UPDATE subjects SET name = ?, grade = ? WHERE id = ?");
    $stmt->execute([$name, $grade, $subject_id]);

    // دریافت پودمان‌های کتاب
    $stmt = $pdo->prepare("SELECT id FROM modules WHERE subject_id = ? ORDER BY id");
    $stmt->execute([$subject_id]);
    $modules = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // بروزرسانی تعداد سوالات هر پودمان
    $updateStmt = $pdo->prepare("UPDATE modules SET questions_count = ? WHERE id = ?");
    foreach ($modules as $index => $module) {
        if (isset($questions_counts[$index])) {
            $updateStmt->execute([$questions_counts[$index], $module['id']]);
        }
    }

    // تایید تراکنش
    $pdo->commit();

    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'message' => 'تغییرات با موفقیت ذخیره شد']);

} catch (PDOException $e) {
    // برگرداندن تراکنش در صورت خطا
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'خطا در ذخیره تغییرات: ' . $e->getMessage()]);
} 