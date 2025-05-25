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
$questions_count = isset($_POST['questions_count']) ? (int)$_POST['questions_count'] : null;

// اعتبارسنجی داده‌ها
if (!$subject_id || !$name || !$grade) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'فیلدهای ضروری را پر کنید']);
    exit;
}

if ($questions_count === null || $questions_count < 1 || $questions_count > 100) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'تعداد سوالات باید بین 1 تا 100 باشد']);
    exit;
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
    $stmt = $pdo->prepare("UPDATE subjects SET name = ?, grade = ?, questions_count = ? WHERE id = ?");
    $stmt->execute([$name, $grade, $questions_count, $subject_id]);

    // بروزرسانی تعداد سوالات پودمان‌ها
    $stmt = $pdo->prepare("UPDATE modules SET questions_count = ? WHERE subject_id = ?");
    $stmt->execute([$questions_count, $subject_id]);

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