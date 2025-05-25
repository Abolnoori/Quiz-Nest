<?php
require_once 'config.php';

// بررسی وضعیت ورود کاربر
if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit;
}

$user = $_SESSION['user'];

// دریافت لیست کتاب‌های کاربر
$stmt = $pdo->prepare("SELECT * FROM subjects WHERE user_id = ?");
$stmt->execute([$user['id']]);
$subjects = $stmt->fetchAll(PDO::FETCH_ASSOC);

// دریافت اطلاعات پودمان‌ها
$modules = [];
if (!empty($subjects)) {
    $stmt = $pdo->prepare("SELECT * FROM modules WHERE subject_id = ?");
    foreach ($subjects as $subject) {
        $stmt->execute([$subject['id']]);
        $modules[$subject['id']] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}

// دریافت پاسخ‌های کاربر
$stmt = $pdo->prepare("SELECT * FROM answers WHERE user_id = ?");
$stmt->execute([$user['id']]);
$answers = $stmt->fetchAll(PDO::FETCH_ASSOC);

// تبدیل پاسخ‌ها به فرمت مناسب
$userAnswers = [];
foreach ($answers as $answer) {
    $key = "module_{$answer['module_id']}_{$answer['question_number']}";
    $userAnswers[$key] = $answer['answer'];
}
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>سامانه ثبت پاسخ آزمون</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/vazirmatn@33.0.3/Vazirmatn-font-face.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="styles.css" rel="stylesheet">
    <style>
        body {
            font-family: "Vazirmatn", system-ui, -apple-system, sans-serif;
            background-color: #f8f9fa;
            min-height: 100vh;
        }
        .navbar {
            background-color: #fff;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
        }
        .book-btn {
            margin-bottom: 1rem;
            padding: 1.5rem;
            text-align: right;
            transition: all 0.3s ease;
        }
        .book-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        .module-btn {
            margin-bottom: 0.5rem;
        }
        .answer-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 1.5rem;
            margin-top: 1rem;
        }
        .answer-card {
            background: #fff;
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
            transition: all 0.3s ease;
            position: relative;
        }
        .answer-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }
        .question-number {
            font-size: 1.1rem;
            font-weight: 600;
            color: #2196F3;
            margin-bottom: 1rem;
        }
        .answer-options {
            display: flex;
            flex-direction: column;
            gap: 0.8rem;
        }
        @media (max-width: 768px) {
            .answer-options {
                display: grid;
                grid-template-columns: repeat(2, 1fr);
                gap: 0.8rem;
            }
            .answer-card {
                padding: 1.2rem;
            }
            .answer-option label {
                padding: 0.6rem;
                font-size: 0.9rem;
            }
            .answer-option label:before {
                width: 16px;
                height: 16px;
                margin-left: 8px;
            }
            .question-number {
                font-size: 1rem;
                margin-bottom: 0.8rem;
            }
        }
        .answer-option {
            position: relative;
            padding: 0;
            margin: 0;
        }
        .answer-option input[type="radio"] {
            display: none;
        }
        .answer-option label {
            display: flex;
            align-items: center;
            padding: 0.8rem 1rem;
            border: 2px solid #e9ecef;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.2s ease;
            margin: 0;
        }
        .answer-option label:before {
            content: '';
            width: 20px;
            height: 20px;
            border: 2px solid #dee2e6;
            border-radius: 50%;
            margin-left: 12px;
            transition: all 0.2s ease;
        }
        .answer-option input[type="radio"]:checked + label {
            border-color: #2196F3;
            background-color: #E3F2FD;
        }
        .answer-option input[type="radio"]:checked + label:before {
            border-color: #2196F3;
            background-color: #2196F3;
            box-shadow: inset 0 0 0 4px #fff;
        }
        .answer-option label:hover {
            border-color: #2196F3;
        }
        .btn-logout {
            background-color: #dc3545;
            color: white;
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 8px;
            transition: all 0.3s ease;
        }
        .btn-logout:hover {
            background-color: #c82333;
            transform: translateY(-1px);
        }
        .welcome-message {
            font-size: 0.9rem;
            color: #6c757d;
        }
        .section {
            display: none;
        }
        .section.active {
            display: block;
        }
        #emptyState {
            text-align: center;
            padding: 3rem;
            background: #fff;
            border-radius: 15px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            margin-top: 2rem;
        }
        #emptyState i {
            font-size: 3rem;
            color: #6c757d;
            margin-bottom: 1rem;
        }
        .user-menu-btn {
            color: #333;
            text-decoration: none;
            display: flex;
            align-items: center;
            padding: 0.5rem 1rem;
            border: 1px solid #ddd;
            border-radius: 8px;
            background: white;
        }
        
        .user-menu-btn:hover, .user-menu-btn:focus {
            background: #f8f9fa;
            color: #333;
            text-decoration: none;
        }
        
        .user-menu-btn i {
            font-size: 1.5rem;
        }
        
        .dropdown-item {
            padding: 0.7rem 1rem;
            display: flex;
            align-items: center;
        }
        
        .dropdown-item:hover {
            background-color: #f8f9fa;
        }
        
        .dropdown-item i {
            width: 20px;
            text-align: center;
        }

        .floating-timer-btn {
            position: fixed;
            bottom: 20px;
            left: 20px;
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: #2196F3;
            color: white;
            border: none;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            z-index: 1000;
            transition: all 0.3s ease;
        }

        .floating-timer-btn:hover {
            transform: scale(1.1);
            background: #1976D2;
        }

        .floating-timer-btn i {
            font-size: 1.5rem;
        }

        .timer-display {
            position: fixed;
            bottom: 80px;
            left: 20px;
            background: rgba(33, 150, 243, 0.9);
            color: white;
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 0.9rem;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
            z-index: 1000;
            display: none;
            transition: all 0.3s ease;
        }

        .timer-display.warning {
            background: rgba(244, 67, 54, 0.9);
            animation: pulse 1s infinite;
        }

        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.05); }
            100% { transform: scale(1); }
        }

        .timer-display.active {
            display: block;
        }

        /* استایل نوتیفیکیشن */
        .custom-notification {
            position: fixed;
            top: 20px;
            left: 50%;
            transform: translateX(-50%);
            background: #dc3545;
            color: white;
            padding: 15px 25px;
            border-radius: 10px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            z-index: 1100;
            display: none;
            text-align: center;
            animation: slideDown 0.5s ease-out;
        }

        .custom-notification i {
            margin-left: 8px;
            font-size: 1.2rem;
        }

        @keyframes slideDown {
            from { transform: translate(-50%, -100%); }
            to { transform: translate(-50%, 0); }
        }

        .timer-modal .time-input {
            font-size: 2rem;
            width: 100%;
            text-align: center;
            border: 2px solid #e9ecef;
            border-radius: 8px;
            padding: 1rem;
            margin-bottom: 1rem;
        }

        .clear-answer-btn {
            position: absolute;
            top: 0.8rem;
            left: 0.8rem;
            background: none;
            border: none;
            color: #adb5bd;
            padding: 5px;
            cursor: pointer;
            opacity: 0;
            transition: all 0.2s ease;
            font-size: 0.9rem;
        }

        .answer-card:hover .clear-answer-btn {
            opacity: 0.6;
        }

        .clear-answer-btn:hover {
            opacity: 1;
            color: #dc3545;
            transform: scale(1.1);
        }

        .answered-count {
            color: #28a745;
            font-size: 0.9rem;
            font-weight: 500;
        }

        .clear-module-btn {
            color: #6c757d;
            border: none;
            background: none;
            padding: 0.5rem;
            border-radius: 4px;
            display: inline-flex;
            align-items: center;
            gap: 0.3rem;
            font-size: 0.9rem;
            transition: all 0.2s ease;
        }

        .clear-module-btn:hover {
            color: #dc3545;
            background: rgba(220, 53, 69, 0.1);
        }

        .loader-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(255, 255, 255, 0.9);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 9999;
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s ease;
        }

        .loader-overlay.active {
            opacity: 1;
            visibility: visible;
        }

        .loader {
            width: 50px;
            height: 50px;
            border: 3px solid #f3f3f3;
            border-top: 3px solid #2196F3;
            border-radius: 50%;
            animation: spin 1s linear infinite, scale 0.3s ease;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        @keyframes scale {
            0% { transform: scale(0.5); opacity: 0; }
            100% { transform: scale(1); opacity: 1; }
        }

        .section {
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .section.active {
            opacity: 1;
        }

        /* استایل برای مودال مدیریت کتاب‌ها */
        #manageSubjectsModal .modal-content {
            border-radius: 15px;
            border: none;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        }

        #manageSubjectsModal .modal-header {
            background-color: #f8f9fa;
            border-bottom: 1px solid #eee;
            border-radius: 15px 15px 0 0;
            padding: 1.5rem;
        }

        #manageSubjectsModal .modal-body {
            padding: 1.5rem;
        }

        #manageSubjectsModal .table {
            margin-bottom: 0;
            border-collapse: separate;
            border-spacing: 0 8px;
        }

        #manageSubjectsModal .table th {
            font-weight: 600;
            color: #495057;
            border: none;
            padding: 1rem;
            background-color: #f8f9fa;
        }

        #manageSubjectsModal .table td {
            vertical-align: middle;
            padding: 1rem;
            border: none;
            background-color: white;
        }

        #manageSubjectsModal .table tr {
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
            border-radius: 8px;
            transition: all 0.2s ease;
        }

        #manageSubjectsModal .table tr:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        #manageSubjectsModal .form-control {
            border: 1px solid #e9ecef;
            border-radius: 6px;
            padding: 0.5rem;
            transition: all 0.2s ease;
        }

        #manageSubjectsModal .form-control:focus {
            border-color: #2196F3;
            box-shadow: 0 0 0 3px rgba(33, 150, 243, 0.1);
        }

        #manageSubjectsModal .btn-group {
            gap: 0.5rem;
        }

        #manageSubjectsModal .btn {
            padding: 0.5rem 1rem;
            border-radius: 6px;
            transition: all 0.2s ease;
        }

        #manageSubjectsModal .btn-outline-primary {
            border-color: #2196F3;
            color: #2196F3;
        }

        #manageSubjectsModal .btn-outline-primary:hover {
            background-color: #2196F3;
            color: white;
            transform: translateY(-1px);
        }

        #manageSubjectsModal .btn-outline-danger {
            border-color: #dc3545;
            color: #dc3545;
        }

        #manageSubjectsModal .btn-outline-danger:hover {
            background-color: #dc3545;
            color: white;
            transform: translateY(-1px);
        }

        /* استایل‌های جدید برای مدیریت کتاب‌ها */
        #manageSubjectsModal .list-group-item {
            transition: all 0.2s ease;
            border-left: 4px solid transparent;
        }

        #manageSubjectsModal .list-group-item:hover {
            border-left-color: #2196F3;
            background-color: #f8f9fa;
        }

        #manageSubjectsModal .list-group-item h6 {
            color: #333;
            font-weight: 600;
        }

        #manageSubjectsModal .btn-outline-primary {
            border-width: 2px;
        }

        #manageSubjectsModal .btn-outline-primary:hover {
            transform: translateY(-1px);
        }

        #editForm {
            animation: slideIn 0.3s ease;
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
    </style>
</head>
<body>
    <!-- لودر -->
    <div class="loader-overlay" id="loaderOverlay">
        <div class="loader"></div>
    </div>

    <!-- نوار بالای صفحه -->
    <nav class="navbar navbar-expand-lg navbar-light mb-4">
        <div class="container">
            <a class="navbar-brand" href="#">سامانه ثبت پاسخ آزمون</a>
            <div class="dropdown">
                <button class="btn btn-link dropdown-toggle user-menu-btn" type="button" id="userMenuButton" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="fas fa-user-circle"></i>
                    <span class="ms-2"><?php echo htmlspecialchars($user['fullname']); ?></span>
                </button>
                <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userMenuButton">
                    <li>
                        <a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#changePasswordModal">
                            <i class="fas fa-key me-2"></i>
                            تغییر رمز عبور
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item text-danger" href="#" data-bs-toggle="modal" data-bs-target="#deleteAccountModal">
                            <i class="fas fa-user-times me-2"></i>
                            حذف حساب کاربری
                        </a>
                    </li>
                    <li><hr class="dropdown-divider"></li>
                    <li>
                <form action="logout.php" method="POST" class="m-0">
                            <button type="submit" class="dropdown-item text-danger">
                                <i class="fas fa-sign-out-alt me-2"></i>
                        خروج
                    </button>
                </form>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- تایمر شناور -->
    <button class="floating-timer-btn" onclick="showTimerModal()">
        <i style="margin: 0px;" class="fas fa-clock"></i>
    </button>
    <div class="timer-display" id="timerDisplay">00:00</div>

    <!-- اضافه کردن نوتیفیکیشن -->
    <div class="custom-notification" id="timerNotification">
        <i class="fas fa-hourglass-end"></i>
        آخ آخ! تایمت تموم شد
    </div>

    <div class="container">
        <!-- بخش اصلی -->
        <div id="subjectSection" class="section active">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h4>کتاب‌های من</h4>
                <div class="d-flex gap-2">
                    <button class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#manageSubjectsModal">
                        <i class="fas fa-cog"></i>
                        مدیریت کتاب‌ها
                    </button>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addSubjectModal">
                    <i class="fas fa-plus me-1"></i>
                    افزودن کتاب جدید
                </button>
                </div>
            </div>

            <?php if (empty($subjects)): ?>
            <div id="emptyState">
                <i class="fas fa-book mb-3"></i>
                <h5>هنوز کتابی اضافه نکرده‌اید</h5>
                <p class="text-muted">برای شروع، روی دکمه "افزودن کتاب جدید" کلیک کنید</p>
            </div>
            <?php else: ?>
            <div id="subjectList">
                <?php foreach ($subjects as $subject): ?>
                <div class="position-relative">
                    <button class="btn btn-lg btn-outline-primary book-btn w-100" onclick="showModules(<?php echo $subject['id']; ?>)">
                        <div class="d-flex align-items-center justify-content-between">
                            <span><?php echo htmlspecialchars($subject['name']); ?></span>
                            <small class="text-muted"><?php echo htmlspecialchars($subject['grade']); ?></small>
                        </div>
                    </button>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>

        <!-- بخش پودمان‌ها -->
        <div id="moduleSection" class="section">
            <div class="d-flex align-items-center mb-4">
                <button style="font-size: 20px; margin: 0px 16px;  padding: 8px 18px;" class="btn btn-outline-primary me-3" onclick="backToSubjects()">
                    <i style="margin: 0px;"  class="fas fa-arrow-right"></i>
                </button>
                <h4 class="m-0" id="selectedSubject"></h4>
            </div>
            <div id="moduleList" class="row"></div>
        </div>

        <!-- بخش پاسخ‌ها -->
        <div id="answerSection" class="section">
            <div class="d-flex align-items-center mb-4">
                <button style="font-size: 20px; margin: 0px 16px;  padding: 8px 18px;" class="btn btn-outline-primary me-3" onclick="backToModules()">
                    <i style="margin: 0px;" class="fas fa-arrow-right"></i>
                </button>
                <h4 class="m-0" id="selectedModule"></h4>
                <button style="margin-right: 10px;" class="clear-module-btn ms-auto" onclick="confirmClearModule()">
                    <i class="fas fa-broom"></i>
                    پاک کردن همه
                </button>
            </div>
            <div id="questionContainer" class="answer-grid"></div>
            <div style="margin-bottom: 50px;" class="d-flex justify-content-between mt-4">
                <button class="btn btn-outline-primary" onclick="prevPage()">صفحه قبل</button>
                <button class="btn btn-outline-primary" onclick="nextPage()">صفحه بعد</button>
            </div>
        </div>
    </div>

    <!-- مودال افزودن کتاب -->
    <div class="modal fade" id="addSubjectModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">افزودن کتاب جدید</h5>
                    <button type="button" class="btn-close ms-0 me-auto" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="addSubjectForm" action="add_subject.php" method="POST">
                        <div class="mb-3">
                            <label class="form-label">نام کتاب</label>
                            <input type="text" class="form-control" name="name" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">پایه تحصیلی</label>
                            <input type="text" class="form-control" name="grade" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">تعداد سوال هر پودمان</label>
                            <input type="number" class="form-control" name="questions_count" min="1" required>
                        </div>
                        <button type="submit" class="btn btn-primary w-100">افزودن کتاب</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal تغییر رمز عبور -->
    <div class="modal fade" id="changePasswordModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">تغییر رمز عبور</h5>
                    <button type="button" class="btn-close ms-0 me-auto" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="changePasswordForm" action="change_password.php" method="POST">
                        <div class="mb-3">
                            <label class="form-label">رمز عبور فعلی</label>
                            <input type="password" class="form-control" name="current_password" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">رمز عبور جدید</label>
                            <input type="password" class="form-control" name="new_password" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">تکرار رمز عبور جدید</label>
                            <input type="password" class="form-control" name="confirm_password" required>
                        </div>
                        <button type="submit" class="btn btn-primary w-100">تغییر رمز عبور</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal حذف حساب کاربری -->
    <div class="modal fade" id="deleteAccountModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">حذف حساب کاربری</h5>
                    <button type="button" class="btn-close ms-0 me-auto" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        هشدار: این عملیات غیرقابل بازگشت است!
                    </div>
                    <p>آیا از حذف حساب کاربری خود اطمینان دارید؟</p>
                    <form id="deleteAccountForm" action="delete_account.php" method="POST">
                        <div class="mb-3">
                            <label class="form-label">برای تایید، رمز عبور خود را وارد کنید</label>
                            <input type="password" class="form-control" name="password" required>
                        </div>
                        <button type="submit" class="btn btn-danger w-100">حذف حساب کاربری</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- مودال تنظیم تایمر -->
    <div class="modal fade" id="timerModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">تنظیم تایمر</h5>
                    <button type="button" class="btn-close ms-0 me-auto" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body timer-modal">
                    <input style="font-size: 22px;" type="number" class="time-input" id="timerInput" placeholder="زمان به دقیقه" min="1" max="180">
                    <button class="btn btn-primary w-100" onclick="startTimer()">شروع تایمر</button>
                </div>
            </div>
        </div>
    </div>

    <!-- مودال مدیریت کتاب‌ها -->
    <div class="modal fade" id="manageSubjectsModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-book me-2"></i>
                        مدیریت کتاب‌ها
                    </h5>
                    <button type="button" class="btn-close ms-0 me-auto" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <?php if (empty($subjects)): ?>
                        <div class="text-center p-5">
                            <i class="fas fa-book mb-4" style="font-size: 3rem; color: #6c757d;"></i>
                            <h5 class="mb-3">هنوز کتابی اضافه نکرده‌اید</h5>
                            <p class="text-muted mb-4">برای شروع، روی دکمه "افزودن کتاب جدید" کلیک کنید</p>
                            <button class="btn btn-primary" data-bs-dismiss="modal" data-bs-toggle="modal" data-bs-target="#addSubjectModal">
                                <i class="fas fa-plus me-2"></i>
                                افزودن کتاب جدید
                            </button>
                        </div>
                    <?php else: ?>
                        <!-- لیست کتاب‌ها -->
                        <div id="subjectsList">
                            <div class="list-group">
                                <?php foreach ($subjects as $subject): ?>
                                    <div class="list-group-item list-group-item-action d-flex justify-content-between align-items-center p-3">
                                        <div>
                                            <h6 class="mb-1"><?php echo htmlspecialchars($subject['name']); ?></h6>
                                            <small class="text-muted">پایه <?php echo htmlspecialchars($subject['grade']); ?></small>
                                        </div>
                                        <button class="btn btn-outline-primary btn-sm" onclick="showEditForm(<?php echo $subject['id']; ?>)">
                                            <i class="fas fa-edit"></i>
                                            ویرایش
                                        </button>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <!-- فرم ویرایش کتاب -->
                        <div id="editForm" style="display: none;">
                            <div style="display: flex; flex-direction: row-reverse; justify-content: space-between;" class="mb-3  align-items-center">
                                <button style="margin: 0px 0px 0px 16px;" class="btn btn-outline-secondary btn-sm me-3" onclick="showSubjectsList()">
                                    <i class="fas fa-arrow-right"></i>
                                    بازگشت به لیست
                                </button>
                                <h6 class="m-0" id="editFormTitle">ویرایش کتاب</h6>
                            </div>
                            <form id="subjectEditForm" class="mt-4">
                                <input type="hidden" id="edit_subject_id">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label">نام کتاب</label>
                                        <input type="text" class="form-control" id="edit_name" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">پایه تحصیلی</label>
                                        <input type="text" class="form-control" id="edit_grade" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">تعداد سوال هر پودمان</label>
                                        <input type="number" class="form-control" id="edit_questions_count" 
                                               min="1" max="100" required>
                                    </div>
                                </div>
                                <div class="d-flex justify-content-between mt-4">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save me-2"></i>
                                        ذخیره تغییرات
                                    </button>
                                    <button type="button" class="btn btn-danger" onclick="confirmDelete()">
                                        <i class="fas fa-trash me-2"></i>
                                        حذف کتاب
                                    </button>
                                </div>
                            </form>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">بستن</button>
                    <button type="button" class="btn btn-primary" data-bs-dismiss="modal" data-bs-toggle="modal" data-bs-target="#addSubjectModal">
                        <i class="fas fa-plus me-2"></i>
                        افزودن کتاب جدید
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // متغیرهای سراسری
        let currentSubject = null;
        let currentModule = null;
        let currentPage = 0;
        let subjects = <?php echo json_encode($subjects); ?>;
        let modules = <?php echo json_encode($modules); ?>;
        let userAnswers = <?php echo json_encode($userAnswers); ?>;
        let isLoading = false;

        // متغیرهای تایمر
        let timerInterval;
        let remainingTime = 0;
        let timerActive = false;

        // نمایش/مخفی کردن لودر
        function showLoader() {
            isLoading = true;
            document.getElementById('loaderOverlay').classList.add('active');
        }

        function hideLoader() {
            isLoading = false;
            document.getElementById('loaderOverlay').classList.remove('active');
        }

        // بازیابی وضعیت از localStorage
        function restoreState() {
            const savedState = localStorage.getItem('quizState');
            if (!savedState) {
                return;
            }

            try {
                const state = JSON.parse(savedState);
                if (!state.subjectId || !state.moduleId) {
                    return;
                }

                currentPage = state.page || 0;
                const subject = subjects.find(s => s.id == state.subjectId);
                if (!subject) {
                    return;
                }

                currentSubject = subject;
                showModules(subject.id, false);

                const module = modules[subject.id]?.find(m => m.id == state.moduleId);
                if (module) {
                    currentModule = module;
                    showQuestions(module.id, false);
                }
            } catch (error) {
                console.error('Error restoring state:', error);
            }
        }

        // ذخیره وضعیت در localStorage
        function saveState() {
            const state = {
                subjectId: currentSubject?.id,
                moduleId: currentModule?.id,
                page: currentPage
            };
            localStorage.setItem('quizState', JSON.stringify(state));
        }

        // نمایش پودمان‌های یک کتاب
        function showModules(subjectId, saveHistory = true) {
            currentSubject = subjects.find(s => s.id == subjectId);
            document.getElementById('selectedSubject').textContent = currentSubject.name;
            
            const moduleList = document.getElementById('moduleList');
            moduleList.innerHTML = modules[subjectId].map(module => {
                // Count answered questions for this module
                const answeredCount = Object.keys(userAnswers).filter(key => 
                    key.startsWith(`module_${module.id}_`) && userAnswers[key]
                ).length;
                
                return `
                    <div class="position-relative">
                        <button class="btn btn-lg btn-outline-primary book-btn w-100" 
                                onclick="showQuestions(${module.id})">
                            <div class="d-flex align-items-center justify-content-between">
                                <div>
                                    <span>${module.name}</span>
                                    <span class="answered-count me-2">${answeredCount} / ${module.questions_count}</span>
                                </div>
                                <small class="text-muted">${module.questions_count} سوال</small>
                            </div>
                        </button>
                    </div>
                `;
            }).join('');

            showSection('moduleSection');
            
            if (saveHistory) {
                currentModule = null;
                currentPage = 0;
                saveState();
            }
        }

        // نمایش سوالات یک پودمان
        function showQuestions(moduleId, saveHistory = true) {
            currentModule = modules[currentSubject.id].find(m => m.id == moduleId);
            document.getElementById('selectedModule').textContent = currentModule.name;
            
            const container = document.getElementById('questionContainer');
            const questionsPerPage = 15;
            const startIndex = currentPage * questionsPerPage;
            const endIndex = startIndex + questionsPerPage;
            
            let html = '';
            for(let i = startIndex + 1; i <= Math.min(endIndex, currentModule.questions_count); i++) {
                const answerKey = `module_${moduleId}_${i}`;
                const selectedAnswer = userAnswers[answerKey] || '';
                
                html += `
                    <div class="answer-card">
                        <button class="clear-answer-btn" onclick="clearAnswer(event, ${moduleId}, ${i})" title="پاک کردن پاسخ">
                            <i class="fas fa-times"></i>
                        </button>
                        <div class="question-number">سوال ${i}</div>
                        <div class="answer-options">
                            ${[1, 2, 3, 4].map(option => `
                                <div class="answer-option">
                                    <input type="radio" 
                                           id="q${i}_${option}" 
                                           name="q${i}" 
                                           value="${option}"
                                           ${selectedAnswer == option ? 'checked' : ''}
                                           onchange="saveAnswer(${moduleId}, ${i}, ${option})">
                                    <label for="q${i}_${option}">گزینه ${option}</label>
                                </div>
                            `).join('')}
                        </div>
                    </div>
                `;
            }
            
            container.innerHTML = html;
            
            const totalPages = Math.ceil(currentModule.questions_count / questionsPerPage);
            document.querySelector('button[onclick="prevPage()"]').style.visibility = 
                currentPage > 0 ? 'visible' : 'hidden';
            document.querySelector('button[onclick="nextPage()"]').style.visibility = 
                currentPage < totalPages - 1 ? 'visible' : 'hidden';
            
            showSection('answerSection');
            
            if (saveHistory) {
                saveState();
            }
        }

        // ذخیره پاسخ
        function saveAnswer(moduleId, questionNumber, answer) {
            fetch('save_answer.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    module_id: moduleId,
                    question_number: questionNumber,
                    answer: answer
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const key = `module_${moduleId}_${questionNumber}`;
                    userAnswers[key] = answer;
                }
            })
            .catch(error => console.error('Error:', error));
        }

        // نمایش بخش مورد نظر
        function showSection(sectionId) {
            document.querySelectorAll('.section').forEach(section => {
                section.classList.remove('active');
            });
            document.getElementById(sectionId).classList.add('active');
        }

        // بازگشت به صفحه کتاب‌ها
        function backToSubjects() {
            showSection('subjectSection');
            currentSubject = null;
            currentModule = null;
            currentPage = 0;
            saveState();
        }

        // بازگشت به صفحه پودمان‌ها
        function backToModules() {
            showSection('moduleSection');
            currentModule = null;
            currentPage = 0;
            saveState();
        }

        // صفحه قبلی
        function prevPage() {
            if (currentPage > 0) {
                currentPage--;
                showQuestions(currentModule.id);
            }
        }

        // صفحه بعدی
        function nextPage() {
            if ((currentPage + 1) * 6 < currentModule.questions_count) {
                currentPage++;
                showQuestions(currentModule.id);
            }
        }

        // نمایش مودال تایمر
        function showTimerModal() {
            if (!timerActive) {
                new bootstrap.Modal(document.getElementById('timerModal')).show();
            } else {
                stopTimer();
            }
        }

        // شروع تایمر
        function startTimer() {
            const minutes = parseInt(document.getElementById('timerInput').value);
            if (isNaN(minutes) || minutes <= 0 || minutes > 180) {
                alert('لطفاً یک عدد بین 1 تا 180 وارد کنید.');
                return;
            }

            remainingTime = minutes * 60;
            timerActive = true;
            const timerDisplay = document.getElementById('timerDisplay');
            timerDisplay.classList.add('active');
            timerDisplay.classList.remove('warning');
            document.querySelector('.floating-timer-btn i').className = 'fas fa-stop';
            
            // بستن مودال
            bootstrap.Modal.getInstance(document.getElementById('timerModal')).hide();
            
            // شروع تایمر
            updateTimerDisplay();
            timerInterval = setInterval(() => {
                remainingTime--;
                updateTimerDisplay();
                
                // بررسی زمان کمتر از یک دقیقه
                if (remainingTime <= 60) {
                    timerDisplay.classList.add('warning');
                }
                
                if (remainingTime <= 0) {
                    stopTimer();
                    showTimerNotification();
                }
            }, 1000);
        }

        // توقف تایمر
        function stopTimer() {
            clearInterval(timerInterval);
            timerActive = false;
            document.getElementById('timerDisplay').classList.remove('active');
            document.querySelector('.floating-timer-btn i').className = 'fas fa-clock';
        }

        // بروزرسانی نمایش تایمر
        function updateTimerDisplay() {
            const minutes = Math.floor(remainingTime / 60);
            const seconds = remainingTime % 60;
            document.getElementById('timerDisplay').textContent = 
                `${String(minutes).padStart(2, '0')}:${String(seconds).padStart(2, '0')}`;
        }

        // نمایش نوتیفیکیشن
        function showTimerNotification() {
            const notification = document.getElementById('timerNotification');
            notification.style.display = 'block';
            
            // پخش صدای اعلان
            const audio = new Audio('data:audio/wav;base64,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qJrx4KOQjahZxWbcZgztj2c49nKmkId44S71j0c8eV9yDK6uPRzx5X18eDvjvQ6yKo9ZSS6l//8elePK/Lf//IInrOF/FvDoADYAGBMGb7FtErm5MXMlmPAJQVgWta7Zx2go+8xJ0UiCb8LHHdftWyLJE0QIAIsI+UbXu67dZMjmgDGCGl1H+vpF4NSDckSIkk7Vd+sxEhBQMRU8j/12UIRhzSaUdQ+rQU5kGeFxm+hb1oh6pWWmv3uvmReDl0UnvtapVaIzo1jZbf/pD6ElLqSX+rUmOQNpJFa/r+sa4e/pBlAABoAAAAA3CUgShLdGIxsY7AUABPRrgCABdDuQ5GC7DqPQCgbbJUAoRSUj+NIEig0YfyWUho1VBBBA//uQZB4ABZx5zfMakeAAAAmwAAAAF5F3P0w9GtAAACfAAAAAwLhMDmAYWMgVEG1U0FIGCBgXBXAtfMH10000EEEEEECUBYln03TTTdNBDZopopYvrTTdNa325mImNg3TTPV9q3pmY0xoO6bv3r00y+IDGid/9aaaZTGMuj9mpu9Mpio1dXrr5HERTZSmqU36A3CumzN/9Robv/Xx4v9ijkSRSNLQhAWumap82WRSBUqXStV/YcS+XVLnSS+WLDroqArFkMEsAS+eWmrUzrO0oEmE40RlMZ5+ODIkAyKAGUwZ3mVKmcamcJnMW26MRPgUw6j+LkhyHGVGYjSUUKNpuJUQoOIAyDvEyG8S5yfK6dhZc0Tx1KI/gviKL6qvvFs1+bWtaz58uUNnryq6kt5RzOCkPWlVqVX2a/EEBUdU1KrXLf40GoiiFXK///qpoiDXrOgqDR38JB0bw7SoL+ZB9o1RCkQjQ2CBYZKd/+VJxZRRZlqSkKiws0WFxUyCwsKiMy7hUVFhIaCrNQsKkTIsLivwKKigsj8XYlwt/WKi2N4d//uQRCSAAjURNIHpMZBGYiaQPSYyAAABLAAAAAAAACWAAAAApUF/Mg+0aohSIRobBAsMlO//Kk4soosy1JSFRYWaLC4qZBYWFRGZdwqKiwkNBVmoWFSJkWFxX4FFRQWR+LsS4W/rFRb/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////VEFHAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAU291bmRib3kuZGUAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAMjAwNGh0dHA6Ly93d3cuc291bmRib3kuZGUAAAAAAAAAACU=');
            audio.play();
            
            // حذف نوتیفیکیشن بعد از 5 ثانیه
            setTimeout(() => {
                notification.style.display = 'none';
            }, 5000);
        }

        // پاک کردن پاسخ یک سوال
        function clearAnswer(event, moduleId, questionNumber) {
            event.preventDefault(); // جلوگیری از رفرش صفحه
            const key = `module_${moduleId}_${questionNumber}`;
            
            // پاک کردن از پایگاه داده
            fetch('save_answer.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    module_id: moduleId,
                    question_number: questionNumber,
                    answer: null // ارسال null برای پاک کردن پاسخ
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // پاک کردن از حافظه
                    delete userAnswers[key];
                    // بروزرسانی رادیو باتن‌ها
                    const inputs = document.querySelectorAll(`input[name="q${questionNumber}"]`);
                    inputs.forEach(input => input.checked = false);
                }
            })
            .catch(error => console.error('Error:', error));
        }

        // تایید و پاک کردن همه پاسخ‌های یک پودمان
        function confirmClearModule() {
            if (confirm('آیا از پاک کردن همه پاسخ‌های این پودمان مطمئن هستید؟')) {
                const moduleId = currentModule.id;
                
                // پاک کردن از پایگاه داده
                fetch('clear_module_answers.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        module_id: moduleId
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // پاک کردن از حافظه
                        Object.keys(userAnswers).forEach(key => {
                            if (key.startsWith(`module_${moduleId}_`)) {
                                delete userAnswers[key];
                            }
                        });
                        // بروزرسانی نمایش
                        showQuestions(moduleId);
                    }
                })
                .catch(error => console.error('Error:', error));
            }
        }

        // اضافه کردن توابع جدید برای مدیریت کتاب‌ها
        let currentSubjectId = null;

        function showEditForm(subjectId) {
            currentSubjectId = subjectId;
            const subject = subjects.find(s => s.id == subjectId);
            
            if (subject) {
                document.getElementById('edit_subject_id').value = subject.id;
                document.getElementById('edit_name').value = subject.name;
                document.getElementById('edit_grade').value = subject.grade;
                document.getElementById('edit_questions_count').value = subject.questions_count;
                document.getElementById('editFormTitle').textContent = `ویرایش کتاب: ${subject.name}`;
                
                document.getElementById('subjectsList').style.display = 'none';
                document.getElementById('editForm').style.display = 'block';
            }
        }

        function showSubjectsList() {
            document.getElementById('subjectsList').style.display = 'block';
            document.getElementById('editForm').style.display = 'none';
            currentSubjectId = null;
        }

        // به‌روزرسانی تابع ذخیره تغییرات
        document.getElementById('subjectEditForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            if (!currentSubjectId) return;

            const name = document.getElementById('edit_name').value.trim();
            const grade = document.getElementById('edit_grade').value.trim();
            const questions = parseInt(document.getElementById('edit_questions_count').value);

            if (!name || !grade || isNaN(questions) || questions < 1 || questions > 100) {
                alert('لطفاً همه فیلدها را به درستی پر کنید');
                return;
            }

            showLoader();
            const formData = new FormData();
            formData.append('subject_id', currentSubjectId);
            formData.append('name', name);
            formData.append('grade', grade);
            formData.append('questions_count', questions);

            fetch('edit_subject.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                hideLoader();
                if (data.success) {
                    showNotification('تغییرات با موفقیت ذخیره شد', 'success');
                    setTimeout(() => location.reload(), 1500);
                } else {
                    showNotification(data.message || 'خطا در ذخیره تغییرات', 'error');
                }
            })
            .catch(error => {
                hideLoader();
                showNotification('خطا در ارتباط با سرور', 'error');
            });
        });

        function confirmDelete() {
            if (!currentSubjectId) return;
            
            const subject = subjects.find(s => s.id == currentSubjectId);
            if (!subject) return;

            const modal = document.createElement('div');
            modal.className = 'modal fade';
            modal.innerHTML = `
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">
                                <i class="fas fa-exclamation-triangle text-danger me-2"></i>
                                حذف کتاب
                            </h5>
                            <button type="button" class="btn-close ms-0 me-auto" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <p>آیا از حذف کتاب "${subject.name}" اطمینان دارید؟</p>
                            <div class="alert alert-warning">
                                <i class="fas fa-info-circle me-2"></i>
                                این عملیات غیرقابل بازگشت است و تمام پودمان‌ها و پاسخ‌های مرتبط با این کتاب حذف خواهند شد.
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">انصراف</button>
                            <button type="button" class="btn btn-danger" onclick="deleteSubject(${subject.id})" data-bs-dismiss="modal">
                                <i class="fas fa-trash me-2"></i>
                                حذف کتاب
                            </button>
                        </div>
                    </div>
                </div>
            `;
            document.body.appendChild(modal);
            const bsModal = new bootstrap.Modal(modal);
            bsModal.show();
            modal.addEventListener('hidden.bs.modal', () => modal.remove());
        }

        function showNotification(message, type = 'success') {
            const notification = document.createElement('div');
            notification.className = `alert alert-${type} position-fixed top-0 start-50 translate-middle-x mt-3`;
            notification.style.zIndex = '9999';
            notification.innerHTML = `
                <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-circle'} me-2"></i>
                ${message}
            `;
            document.body.appendChild(notification);
            setTimeout(() => notification.remove(), 3000);
        }

        // به‌روزرسانی تابع حذف کتاب
        function deleteSubject(subjectId) {
            showLoader();
            fetch('delete_subject.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ subject_id: subjectId })
            })
            .then(response => response.json())
            .then(data => {
                hideLoader();
                if (data.success) {
                    showNotification('کتاب با موفقیت حذف شد', 'success');
                    setTimeout(() => location.reload(), 1500);
                } else {
                    showNotification(data.message || 'خطا در حذف کتاب', 'error');
                }
            })
            .catch(error => {
                hideLoader();
                showNotification('خطا در ارتباط با سرور', 'error');
            });
        }

        // اجرای اولیه
        document.addEventListener('DOMContentLoaded', restoreState);
    </script>
</body>
</html>