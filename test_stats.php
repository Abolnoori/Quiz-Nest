<?php
require_once 'config.php';
require_once 'stats.php';

// برای تست، یک کاربر ثابت را استفاده می‌کنیم
$userId = 1; // یا هر ID دیگری که می‌دانید وجود دارد
echo "Testing stats for user ID: $userId\n\n";

// تست آمار کتاب‌ها
$subjectStats = getSubjectStats($userId);
echo "Subject Stats:\n";
echo "-------------\n";
echo "Total Subjects: " . $subjectStats['total_subjects'] . "\n";
echo "Total Modules: " . $subjectStats['total_modules'] . "\n";
echo "Total Questions: " . $subjectStats['total_questions'] . "\n\n";

// تست آمار پاسخ‌ها
$answerStats = getAnswerStats($userId);
echo "Answer Stats:\n";
echo "-------------\n";
echo "Total Answers: " . $answerStats['total_answers'] . "\n";
echo "Progress Percent: " . $answerStats['progress_percent'] . "%\n";
echo "Last Activity: " . ($answerStats['last_activity'] ?? 'No activity') . "\n";

// تست کل خروجی JSON
echo "\nFull JSON Response:\n";
echo "----------------\n";
echo json_encode([
    'subject_stats' => $subjectStats,
    'answer_stats' => $answerStats
], JSON_PRETTY_PRINT);

// نمایش کوئری‌های اجرا شده برای دیباگ
echo "\n\nDebug Queries:\n";
echo "-------------\n";
$stmt = $pdo->prepare("
    SELECT s.*, COUNT(m.id) as module_count, SUM(m.questions_count) as total_questions
    FROM subjects s
    LEFT JOIN modules m ON m.subject_id = s.id
    WHERE s.user_id = ?
    GROUP BY s.id
");
$stmt->execute([$userId]);
$subjects = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo "\nSubjects data:\n";
print_r($subjects);

$stmt = $pdo->prepare("SELECT * FROM answers WHERE user_id = ? ORDER BY updated_at DESC LIMIT 5");
$stmt->execute([$userId]);
$recentAnswers = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo "\nRecent answers:\n";
print_r($recentAnswers);
?> 