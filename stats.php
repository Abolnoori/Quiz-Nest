<?php
require_once 'config.php';
require_once 'jdate.php';

function getSubjectStats($userId) {
    global $pdo;
    
    // تعداد کل کتاب‌ها
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM subjects WHERE user_id = ?");
    $stmt->execute([$userId]);
    $totalSubjects = $stmt->fetchColumn();
    
    // تعداد کل پودمان‌ها
    $stmt = $pdo->prepare("
        SELECT COUNT(*) 
        FROM modules m 
        LEFT JOIN subjects s ON s.id = m.subject_id 
        WHERE s.user_id = ?
    ");
    $stmt->execute([$userId]);
    $totalModules = $stmt->fetchColumn();
    
    // تعداد کل سوالات
    $stmt = $pdo->prepare("
        SELECT COALESCE(SUM(m.questions_count), 0) as total
        FROM modules m 
        LEFT JOIN subjects s ON s.id = m.subject_id 
        WHERE s.user_id = ?
    ");
    $stmt->execute([$userId]);
    $totalQuestions = $stmt->fetchColumn();
    
    return [
        'total_subjects' => (int)$totalSubjects,
        'total_modules' => (int)$totalModules,
        'total_questions' => (int)$totalQuestions
    ];
}

function getAnswerStats($userId) {
    global $pdo;
    
    // تعداد کل پاسخ‌های داده شده
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM answers WHERE user_id = ? AND answer IS NOT NULL");
    $stmt->execute([$userId]);
    $totalAnswers = $stmt->fetchColumn();
    
    // درصد پیشرفت کلی
    $progressPercent = 0;
    $stmt = $pdo->prepare("
        SELECT COALESCE(SUM(m.questions_count), 0) as total_questions
        FROM modules m 
        LEFT JOIN subjects s ON s.id = m.subject_id 
        WHERE s.user_id = ?
    ");
    $stmt->execute([$userId]);
    $totalQuestions = $stmt->fetchColumn();
    
    if ($totalQuestions > 0) {
        $progressPercent = round(($totalAnswers / $totalQuestions) * 100);
    }
    
    // آخرین فعالیت
    $stmt = $pdo->prepare("
        SELECT updated_at
        FROM answers 
        WHERE user_id = ? AND answer IS NOT NULL
        ORDER BY updated_at DESC
        LIMIT 1
    ");
    $stmt->execute([$userId]);
    $lastActivity = $stmt->fetchColumn();
    
    return [
        'total_answers' => (int)$totalAnswers,
        'progress_percent' => (int)$progressPercent,
        'last_activity' => $lastActivity
    ];
}

function getDetailedStats($userId) {
    global $pdo;
    
    $today = date('Y-m-d');
    $yesterday = date('Y-m-d', strtotime('-1 day'));
    
    // آمار امروز
    $stmt = $pdo->prepare("
        SELECT COUNT(*) 
        FROM answers 
        WHERE user_id = ? AND DATE(updated_at) = ? AND answer IS NOT NULL
    ");
    $stmt->execute([$userId, $today]);
    $todayAnswers = $stmt->fetchColumn();
    
    // آمار دیروز
    $stmt->execute([$userId, $yesterday]);
    $yesterdayAnswers = $stmt->fetchColumn();
    
    // بیشترین پاسخ در یک روز
    $stmt = $pdo->prepare("
        SELECT DATE(updated_at) as answer_date, COUNT(*) as answer_count
        FROM answers 
        WHERE user_id = ? AND answer IS NOT NULL
        GROUP BY DATE(updated_at)
        ORDER BY answer_count DESC
        LIMIT 1
    ");
    $stmt->execute([$userId]);
    $maxAnswersData = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // روزهای متوالی فعالیت
    $stmt = $pdo->prepare("
        SELECT DISTINCT DATE(updated_at) as activity_date
        FROM answers 
        WHERE user_id = ? AND answer IS NOT NULL
        ORDER BY activity_date DESC
    ");
    $stmt->execute([$userId]);
    $activityDates = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    $streakDays = 0;
    $currentDate = strtotime('today');
    foreach ($activityDates as $date) {
        $activityDate = strtotime($date);
        if ($currentDate - strtotime($date) == $streakDays * 86400) {
            $streakDays++;
        } else {
            break;
        }
    }
    
    // آمار هفتگی
    $weeklyProgress = [];
    $weekStart = strtotime('last saturday');
    for ($i = 0; $i < 7; $i++) {
        $date = date('Y-m-d', strtotime("+$i days", $weekStart));
        
        $stmt = $pdo->prepare("
            SELECT COUNT(*) 
            FROM answers 
            WHERE user_id = ? AND DATE(updated_at) = ? AND answer IS NOT NULL
        ");
        $stmt->execute([$userId, $date]);
        $dayAnswers = $stmt->fetchColumn();
        
        // محاسبه درصد پیشرفت روزانه (فرض می‌کنیم هدف روزانه 20 پاسخ است)
        $dayProgress = min(100, round(($dayAnswers / 20) * 100));
        
        $weeklyProgress[jdate('l', strtotime($date))] = [
            'answers' => $dayAnswers,
            'progress' => $dayProgress
        ];
    }
    
    return [
        'today_answers' => $todayAnswers,
        'today_progress' => min(100, round(($todayAnswers / 20) * 100)),
        'yesterday_answers' => $yesterdayAnswers,
        'yesterday_progress' => min(100, round(($yesterdayAnswers / 20) * 100)),
        'max_answers' => $maxAnswersData['answer_count'] ?? 0,
        'max_answers_day' => $maxAnswersData ? jdate('Y/m/d', strtotime($maxAnswersData['answer_date'])) : null,
        'streak_days' => $streakDays,
        'weekly_progress' => $weeklyProgress
    ];
}

// اگر درخواست AJAX باشد
if (isset($_GET['get_stats'])) {
    header('Content-Type: application/json');
    if (!isset($_SESSION['user'])) {
        http_response_code(401);
        echo json_encode(['error' => 'Unauthorized']);
        exit;
    }
    
    $userId = $_SESSION['user']['id'];
    $subjectStats = getSubjectStats($userId);
    $answerStats = getAnswerStats($userId);
    $detailedStats = getDetailedStats($userId);
    
    echo json_encode([
        'subject_stats' => $subjectStats,
        'answer_stats' => $answerStats,
        'detailed_stats' => $detailedStats
    ]);
    exit;
}
?> 