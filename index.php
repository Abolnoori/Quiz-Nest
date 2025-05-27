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
    <title>سامانه پاسخ‌دون</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/vazirmatn@33.0.3/Vazirmatn-font-face.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="styles.css" rel="stylesheet">


    <?php
    // تابع کمکی برای تولید متن و کلاس وضعیت
    function getProgressStatus($percent) {
        if ($percent == 100) {
            return ['<i class="fas fa-check-circle"></i> تموم شد!', 'complete'];
        } elseif ($percent >= 60) {
            return ['<i class="fas fa-fire"></i> عالی پیش میری', 'good'];
        } elseif ($percent >= 30) {
            return ['<i class="fas fa-running"></i> تو راهی', 'half'];
        } elseif ($percent > 0) {
            return ['<i class="fas fa-hourglass-start"></i> تازه اولشه', 'start'];
        } else {
            return ['<i class="fas fa-book"></i> منتظر شروع', 'none'];
        }
    }
    ?>
</head>
<body>
    <!-- لودر صفحه -->
    <div id="pageLoader" class="page-loader active">
        <div class="loader"></div>
    </div>

    <!-- لودر برای عملیات‌های خاص -->
    <div id="loaderOverlay" class="loader-overlay">
        <div class="loader"></div>
    </div>

    <!-- نوار بالای صفحه -->
    <nav class="navbar navbar-expand-lg navbar-light mb-4">
        <div class="container">
            <a class="navbar-brand" href="#">سامانه پاسخ‌دون</a>
            <div class="dropdown">
                <button class="btn btn-link dropdown-toggle user-menu-btn" type="button" id="userMenuButton" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="fas fa-user-circle"></i>
                    <span><?php echo htmlspecialchars($user['fullname']); ?></span>
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
    <div class="ackack custom-notification" id="timerNotification">
        <i  class=" fas fa-hourglass-end"></i>
      آخ! تایمت تموم شد
    </div>

    <div class="container">
        <!-- بخش اصلی -->
        <div id="subjectSection" class="section active">
            <!-- نوار تب‌ها -->
            <ul class="nav nav-tabs mb-4" id="mainTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="books-tab" data-bs-toggle="tab" data-bs-target="#books-content" type="button" role="tab" aria-controls="books-content" aria-selected="true">
                        <i class="fas fa-book me-1"></i>
                        کتاب‌های من
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="stats-tab" data-bs-toggle="tab" data-bs-target="#stats-content" type="button" role="tab" aria-controls="stats-content" aria-selected="false">
                        <i class="fas fa-chart-bar me-1"></i>
                        آمار
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="profile-tab" data-bs-toggle="tab" data-bs-target="#profile-content" type="button" role="tab" aria-controls="profile-content" aria-selected="false">
                        <i class="fas fa-user me-1"></i>
                        پنل کاربری
                    </button>
                </li>
            </ul>

            <!-- محتوای تب‌ها -->
            <div class="tab-content" id="mainTabsContent">
                <!-- تب کتاب‌های من -->
                <div class="tab-pane fade show active" id="books-content" role="tabpanel" aria-labelledby="books-tab">
                    <div class="ketab-div d-flex justify-content-between align-items-center mb-4">
                        <h4 class="title-hed">کتاب‌های من</h4>
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
                                <div class="d-flex flex-column w-100">
                                    <div class="d-flex align-items-center justify-content-between">
                                        <span class="book-name"><?php echo htmlspecialchars($subject['name']); ?></span>
                                        <small class="text-muted"><?php echo htmlspecialchars($subject['grade']); ?></small>
                                    </div>
                                    <?php
                                        // محاسبه درصد پیشرفت برای هر کتاب
                                        $totalQuestions = 0;
                                        $answeredQuestions = 0;
                                        if (isset($modules[$subject['id']])) {
                                            foreach ($modules[$subject['id']] as $module) {
                                                $totalQuestions += $module['questions_count'];
                                                foreach ($userAnswers as $key => $value) {
                                                    if (strpos($key, "module_{$module['id']}_") === 0) {
                                                        $answeredQuestions++;
                                                    }
                                                }
                                            }
                                        }
                                        $progressPercent = $totalQuestions > 0 ? round(($answeredQuestions / $totalQuestions) * 100) : 0;
                                        $progressClass = $progressPercent < 30 ? 'low' : ($progressPercent < 60 ? 'medium' : 'high');
                                        
                                        // متن وضعیت پیشرفت
                                        $progressStatus = getProgressStatus($progressPercent);
                                    ?>
                                    <div class="progress-line">
                                        <div class="progress-fill <?php echo $progressClass; ?>" 
                                             data-progress="<?php echo $progressPercent; ?>"></div>
                                    </div>
                                    <div class="progress-text">
                                        <span class="progress-status <?php echo $progressStatus[1]; ?>"><?php echo $progressStatus[0]; ?></span>
                                        <span class="progress-percent"><?php echo $progressPercent; ?>%</span>
                                    </div>
                                </div>
                            </button>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- تب آمار -->
                <div class="tab-pane fade" id="stats-content" role="tabpanel" aria-labelledby="stats-tab">
                    <div class="row g-4">
                        <!-- کارت آمار کلی -->
                        <div class="col-md-6">
                            <div class="card h-100">
                                <div class="card-body">
                                    <h5 class="card-title mb-5">
                                        <i class="fas fa-chart-pie text-primary me-2"></i>
                                        آمار کلی
                                    </h5>
                                    <div class="stats-grid">
                                        <div class="stat-item">
                                            <div class="stat-icon">
                                                <i class="fas fa-book"></i>
                                            </div>
                                            <div class="stat-info">
                                                <span class="stat-value" id="totalSubjects">-</span>
                                                <span class="stat-label">کتاب</span>
                                            </div>
                                        </div>
                                        <div class="stat-item">
                                            <div class="stat-icon">
                                                <i class="fas fa-layer-group"></i>
                                            </div>
                                            <div class="stat-info">
                                                <span class="stat-value" id="totalModules">-</span>
                                                <span class="stat-label">پودمان</span>
                                            </div>
                                        </div>
                                        <div class="stat-item">
                                            <div class="stat-icon">
                                                <i class="fas fa-question-circle"></i>
                                            </div>
                                            <div class="stat-info">
                                                <span class="stat-value" id="totalQuestions">-</span>
                                                <span class="stat-label">سوال</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- کارت پیشرفت -->
                        <div class="col-md-6">
                            <div class="card h-100">
                                <div class="card-body">
                                    <h5 class="card-title mb-5">
                                        <i class="fas fa-tasks text-primary me-2"></i>
                                        پیشرفت کلی
                                    </h5>
                                    <div class="progress-stats">
                                        <div class="circular-progress" id="circularProgress">
                                            <div class="progress-value">
                                                <span id="progressPercent">-</span>%
                                            </div>
                                        </div>
                                        <div class="progress-details">
                                            <div class="detail-item">
                                            <div>
                                                <i class="fas fa-check-circle text-success"></i>
                                                <span>پاسخ داده شده: </span>
                                            </div>
                                                <strong id="totalAnswers">-</strong>
                                            </div>
                                            <div class="detail-item">
                                            <div>
                                                <i class="fas fa-clock text-info"></i>
                                                <span>آخرین فعالیت: </span>
                                            </div>
                                                <strong id="lastActivity">-</strong>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- محل نمایش آمار تفصیلی -->
                    <div id="detailedStats"></div>
                </div>

                <!-- تب پنل کاربری -->
                <div class="tab-pane fade" id="profile-content" role="tabpanel" aria-labelledby="profile-tab">
                    <div class="text-center py-5">
                        <i class="fas fa-user fa-3x text-muted mb-3"></i>
                        <h5 class="paneltext text-muted">بخش پنل کاربری به زودی اضافه خواهد شد</h5>
                    </div>
                </div>
            </div>
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
                <button style="font-size: 20px; margin: 0px 16px; padding: 8px 18px;" class="btn btn-outline-primary me-3" onclick="backToModules()">
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
                            <input type="text" class="form-control book-name-input" name="name" 
                                   maxlength="26" required 
                                   oninput="updateCharCounter(this)">
                            <div class="char-counter">0 / 26</div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">پایه تحصیلی</label>
                            <input type="text" class="form-control grade-input" name="grade" 
                                   maxlength="16" required 
                                   oninput="updateCharCounter(this)">
                            <div class="char-counter">0 / 16</div>
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
                    <div class="timer-inputs">
                        <div class="timer-input-group">
                            <label>ساعت</label>
                            <input type="number" id="hoursInput" min="0" max="3" value="0">
                        </div>
                        <div class="timer-separator">:</div>
                        <div class="timer-input-group">
                            <label>دقیقه</label>
                            <input type="number" id="minutesInput" min="0" max="59" value="0">
                        </div>
                    </div>
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
                                        <div class="modiriat">
                                            <h6 class="modiriat-t mb-1"><?php echo htmlspecialchars($subject['name']); ?></h6>
                                            <small class="text-muted">پایه <?php echo htmlspecialchars($subject['grade']); ?></small>
                                        </div>
                                        <button  class="virayesh btn btn-outline-primary btn-sm" onclick="showEditForm(<?php echo $subject['id']; ?>)">
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
                                <button style="margin: 0px 0px 0px 16px;" class="bazgashd btn btn-outline-secondary btn-sm me-3" onclick="showSubjectsList()">
                                    <i class="bazogashd fas fa-arrow-right"></i>
                                    بازگشت  
                                </button>
                                <h6 class="m-0" id="editFormTitle">ویرایش کتاب</h6>
                            </div>
                            <form id="subjectEditForm" class="mt-4">
                                <input type="hidden" id="edit_subject_id">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label">نام کتاب</label>
                                        <input type="text" class="form-control book-name-input" id="edit_name" 
                                               maxlength="26" required 
                                               oninput="updateCharCounter(this)">
                                        <div class="char-counter">0 / 26</div>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">پایه تحصیلی</label>
                                        <input type="text" class="form-control grade-input" id="edit_grade" 
                                               maxlength="16" required 
                                               oninput="updateCharCounter(this)">
                                        <div class="char-counter">0 / 16</div>
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
                    <button type="button" class="bastan btn btn-secondary" data-bs-dismiss="modal">بستن</button>
                    <button type="button" class="btn btn-primary" data-bs-dismiss="modal" data-bs-toggle="modal" data-bs-target="#addSubjectModal">
                        <i class="fas fa-plus me-2"></i>
                        افزودن کتاب جدید
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="editModulesModal" tabindex="-1" aria-labelledby="editModulesModalLabel" aria-hidden="false">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editModulesModalLabel">ویرایش پودمان‌ها</h5>
                    <button type="button" class="btn-close ms-0 me-auto" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div id="moduleEditForm">
                        <!-- Form will be dynamically populated -->
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">انصراف</button>
                    <button type="button" class="btn btn-primary" onclick="saveModuleChanges()">ذخیره تغییرات</button>
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
        let selectedHours = 0;
        let selectedMinutes = 0;

        // نمایش/مخفی کردن لودر
        function showLoader() {
            document.getElementById('loaderOverlay').classList.add('active');
        }

        function hideLoader() {
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
                if (!state.subjectId) {
                    return;
                }

                currentPage = state.page || 0;
                const subject = subjects.find(s => s.id == state.subjectId);
                if (!subject) {
                    return;
                }

                currentSubject = subject;
                
                // نمایش بخش پودمان‌ها
                if (state.view === 'modules') {
                    showModules(subject.id, false);
                }
                
                // نمایش بخش سوالات
                if (state.view === 'questions' && state.moduleId) {
                    showModules(subject.id, false);
                    const module = modules[subject.id]?.find(m => m.id == state.moduleId);
                    if (module) {
                        currentModule = module;
                        showQuestions(module.id, false);
                    }
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
                page: currentPage,
                view: currentModule ? 'questions' : (currentSubject ? 'modules' : 'main')
            };
            localStorage.setItem('quizState', JSON.stringify(state));
        }

        // نمایش پودمان‌های یک کتاب
        function showModules(subjectId, saveHistory = true) {
            currentSubject = subjects.find(s => s.id == subjectId);
            document.getElementById('selectedSubject').textContent = currentSubject.name;
            
            const moduleList = document.getElementById('moduleList');
            moduleList.innerHTML = modules[subjectId].map(module => {
                const answeredCount = Object.keys(userAnswers).filter(key => 
                    key.startsWith(`module_${module.id}_`) && userAnswers[key]
                ).length;
                
                const progressPercent = Math.round((answeredCount / module.questions_count) * 100);
                const progressClass = progressPercent < 30 ? 'low' : (progressPercent < 60 ? 'medium' : 'high');
                
                // تعیین وضعیت پیشرفت
                let statusIcon, statusText, statusClass;
                if (progressPercent == 100) {
                    statusIcon = 'check-circle';
                    statusText = 'تموم شد!';
                    statusClass = 'complete';
                } else if (progressPercent >= 60) {
                    statusIcon = 'fire';
                    statusText = 'عالی پیش میری';
                    statusClass = 'good';
                } else if (progressPercent >= 30) {
                    statusIcon = 'running';
                    statusText = 'تو راهی';
                    statusClass = 'half';
                } else if (progressPercent > 0) {
                    statusIcon = 'hourglass-start';
                    statusText = 'تازه اولشه';
                    statusClass = 'start';
                } else {
                    statusIcon = 'book';
                    statusText = 'منتظر شروع';
                    statusClass = 'none';
                }
                
                return `
                    <div class="position-relative">
                        <button class="btn btn-lg btn-outline-primary book-btn w-100" 
                                onclick="showQuestions(${module.id})">
                            <div class="d-flex flex-column w-100">
                                <div class="d-flex align-items-center justify-content-between">
                                    <div>
                                        <span class="module-name">${module.name}</span>
                                    </div>
                                    <small class="text-muted">${module.questions_count} سوال</small>
                                </div>
                                <div class="progress-line">
                                    <div class="progress-fill ${progressClass}" 
                                         data-progress="${progressPercent}"></div>
                                </div>
                                <div class="progress-text">
                                    <span class="progress-status ${statusClass}">
                                        <i class="fas fa-${statusIcon}"></i>
                                        ${statusText}
                                    </span>
                                    <span class="progress-percent">${progressPercent}%</span>
                                </div>
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

            // اجرای انیمیشن با تاخیر برای اطمینان از رندر شدن المان‌ها
            setTimeout(() => {
                animateProgressBars();
                checkTextOverflow();
            }, 100);
        }

        // بررسی overflow برای متن‌های طولانی
        function checkTextOverflow() {
            // بررسی نام کتاب‌ها
            document.querySelectorAll('.book-name').forEach(element => {
                if (element.scrollWidth > element.clientWidth) {
                    element.classList.add('overflow');
                }
            });

            // بررسی نام پودمان‌ها
            document.querySelectorAll('.module-name').forEach(element => {
                if (element.scrollWidth > element.clientWidth) {
                    element.classList.add('overflow');
                }
            });

            // بررسی نام‌ها در مودال مدیریت
            document.querySelectorAll('.modiriat-t').forEach(element => {
                if (element.scrollWidth > element.clientWidth) {
                    element.classList.add('overflow');
                }
            });
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
                section.style.display = 'none';
                section.classList.remove('active');
            });
            const targetSection = document.getElementById(sectionId);
            targetSection.style.display = 'block';
            setTimeout(() => targetSection.classList.add('active'), 50);
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
                window.scrollTo({ top: 0, behavior: 'smooth' });
            }
        }

        // صفحه بعدی
        function nextPage() {
            if ((currentPage + 1) * 6 < currentModule.questions_count) {
                currentPage++;
                showQuestions(currentModule.id);
                window.scrollTo({ top: 0, behavior: 'smooth' });
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

        // ذخیره وضعیت تایمر
        function saveTimerState() {
            if (timerActive && remainingTime > 0) {
                const timerState = {
                    remainingTime,
                    timerActive,
                    startTime: Date.now()
                };
                localStorage.setItem('timerState', JSON.stringify(timerState));
            } else {
                localStorage.removeItem('timerState');
            }
        }

        // بازیابی وضعیت تایمر
        function restoreTimerState() {
            const savedState = localStorage.getItem('timerState');
            if (!savedState) return;

            try {
                const state = JSON.parse(savedState);
                if (state.timerActive) {
                    const elapsedTime = Math.floor((Date.now() - state.startTime) / 1000);
                    remainingTime = Math.max(0, state.remainingTime - elapsedTime);
                    
                    if (remainingTime > 0) {
                        timerActive = true;
                        const timerDisplay = document.getElementById('timerDisplay');
                        timerDisplay.classList.add('active');
                        document.querySelector('.floating-timer-btn i').className = 'fas fa-stop';
                        updateTimerDisplay();
                        startTimerInterval();
                    } else {
                        localStorage.removeItem('timerState');
                    }
                }
            } catch (error) {
                console.error('Error restoring timer state:', error);
                localStorage.removeItem('timerState');
            }
        }

        // راه‌اندازی تایمر پیکر
        function initializeTimerPicker() {
            const hourScroll = document.querySelector('#hourPicker .time-scroll');
            const minuteScroll = document.querySelector('#minutePicker .time-scroll');
            
            // ساخت آیتم‌های ساعت (0-3)
            for (let i = 0; i <= 3; i++) {
                const item = document.createElement('div');
                item.className = 'time-item' + (i === 0 ? ' selected' : '');
                item.textContent = i.toString().padStart(2, '0');
                item.onclick = () => selectTime(hourScroll, i, 'hour');
                hourScroll.appendChild(item);
            }
            
            // ساخت آیتم‌های دقیقه (0-59)
            for (let i = 0; i <= 59; i++) {
                const item = document.createElement('div');
                item.className = 'time-item' + (i === 0 ? ' selected' : '');
                item.textContent = i.toString().padStart(2, '0');
                item.onclick = () => selectTime(minuteScroll, i, 'minute');
                minuteScroll.appendChild(item);
            }
        }

        // انتخاب زمان
        function selectTime(scrollElement, value, type) {
            const items = scrollElement.children;
            for (let item of items) {
                item.classList.remove('selected');
            }
            items[value].classList.add('selected');
            
            if (type === 'hour') {
                selectedHours = value;
            } else {
                selectedMinutes = value;
            }
        }

        // شروع تایمر
        function startTimer() {
            const hours = parseInt(document.getElementById('hoursInput').value) || 0;
            const minutes = parseInt(document.getElementById('minutesInput').value) || 0;
            const totalMinutes = (hours * 60) + minutes;

            if (totalMinutes <= 0 || totalMinutes > 180) {
                alert('لطفاً زمان معتبری بین 1 تا 180 دقیقه انتخاب کنید');
                return;
            }

            remainingTime = totalMinutes * 60;
            timerActive = true;
            const timerDisplay = document.getElementById('timerDisplay');
            timerDisplay.classList.add('active');
            timerDisplay.classList.remove('warning');
            document.querySelector('.floating-timer-btn i').className = 'fas fa-stop';
            
            // بستن مودال
            bootstrap.Modal.getInstance(document.getElementById('timerModal')).hide();
            
            // شروع تایمر
            updateTimerDisplay();
            startTimerInterval();
            saveTimerState();
        }

        // محدود کردن ورودی‌ها
        document.getElementById('hoursInput').addEventListener('input', function() {
            if (this.value > 3) this.value = 3;
            if (this.value < 0) this.value = 0;
        });

        document.getElementById('minutesInput').addEventListener('input', function() {
            if (this.value > 59) this.value = 59;
            if (this.value < 0) this.value = 0;
        });

        // شروع اینتروال تایمر
        function startTimerInterval() {
            clearInterval(timerInterval);
            timerInterval = setInterval(() => {
                remainingTime--;
                updateTimerDisplay();
                
                if (remainingTime <= 60) {
                    document.getElementById('timerDisplay').classList.add('warning');
                }
                
                if (remainingTime <= 0) {
                    stopTimer();
                    showTimerNotification();
                } else {
                    saveTimerState();
                }
            }, 1000);
        }

        // توقف تایمر
        function stopTimer() {
            clearInterval(timerInterval);
            timerActive = false;
            remainingTime = 0;
            document.getElementById('timerDisplay').classList.remove('active', 'warning');
            document.querySelector('.floating-timer-btn i').className = 'fas fa-clock';
            localStorage.removeItem('timerState');
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
            const audio = new Audio('data:audio/wav;base64,//uQRAAAAWMSLwUIYAAsYkXgoQwAEaYLWfkWgAI0wWs/ItAAAGDgYtAgAyN+QWaAAihwMWm4G8QQRDiMcCBcH3Cc+CDv/7xA4Tvh9Rz/y8QADBwMWgQAZG/ILNAARQ4GLTcDeIIIhxGOBAuD7hOfBB3/94gcJ3w+o5/5eIAIAAAVwWgQAVQ2ORaIQwEMAJiDg95G4nQL7mQVWI6GwRcfsZAcsKkJvxgxEjzFUgfHoSQ9Qq7KNwqHwuB13MA4a1q/DmBrHgPcmjiGoh//EwC5nGPEmS4RcfkVKOhJf+WOgoxJclFz3kgn//dBA+ya1GhurNn8zb//9NNutNuhz31f////9vt///z+IdAEAAAK4LQIAKobHItEIYCGAExBwe8jcToF9zIKrEdDYIuP2MgOWFSE34wYiR5iqQPj0JIeoVdlG4VD4XA67mAcNa1fhzA1jwHuTRxDUQ//iYBczjHiTJcIuPyKlHQkv/LHQUYkuSi57yQT//uggfZNajQ3Vmz+Zt//+mm3Wm3Q576v////+32///5/EOgAAADVghQAAAAA//uQZAUAB1WI0PZugAAAAAoQwAAAEk3nRd2qAAAAACiDgAAAAAAABCqEEQRLCgwpBGMlJkIz8jKhGvj4k6jzRnqasNKIeoh5gI7BJaC1A1AoNBjJgbyApVS4IDlZgDU5WUAxEKDNmmALHzZp0Fkz1FMTmGFl1FMEyodIavcCAUHDWrKAIA4aa2oCgILEBupZgHvAhEBcZ6joQBxS76AgccrFlczBvKLC0QI2cBoCFvfTDAo7eoOQInqDPBtvrDEZBNYN5xwNwxQRfw8ZQ5wQVLvO8OYU+mHvFLlDh05Mdg7BT6YrRPpCBznMB2r//xKJjyyOh+cImr2/4doscwD6neZjuZR4AgAABYAAAABy1xcdQtxYBYYZdifkUDgzzXaXn98Z0oi9ILU5mBjFANmRwlVJ3/6jYDAmxaiDG3/6xjQQCCKkRb/6kg/wW+kSJ5//rLobkLSiKmqP/0ikJuDaSaSf/6JiLYLEYnW/+kXg1WRVJL/9EmQ1YZIsv/6Qzwy5qk7/+tEU0nkls3/zIUMPKNX/6yZLf+kFgAfgGyLFAUwY//uQZAUABcd5UiNPVXAAAApAAAAAE0VZQKw9ISAAACgAAAAAVQIygIElVrFkBS+Jhi+EAuu+lKAkYUEIsmEAEoMeDmCETMvfSHTGkF5RWH7kz/ESHWPAq/kcCRhqBtMdokPdM7vil7RG98A2sc7zO6ZvTdM7pmOUAZTnJW+NXxqmd41dqJ6mLTXxrPpnV8avaIf5SvL7pndPvPpndJR9Kuu8fePvuiuhorgWjp7Mf/PRjxcFCPDkW31srioCExivv9lcwKEaHsf/7ow2Fl1T/9RkXgEhYElAoCLFtMArxwivDJJ+bR1HTKJdlEoTELCIqgEwVGSQ+hIm0NbK8WXcTEI0UPoa2NbG4y2K00JEWbZavJXkYaqo9CRHS55FcZTjKEk3NKoCYUnSQ0rWxrZbFKbKIhOKPZe1cJKzZSaQrIyULHDZmV5K4xySsDRKWOruanGtjLJXFEmwaIbDLX0hIPBUQPVFVkQkDoUNfSoDgQGKPekoxeGzA4DUvnn4bxzcZrtJyipKfPNy5w+9lnXwgqsiyHNeSVpemw4bWb9psYeq//uQZBoABQt4yMVxYAIAAAkQoAAAHvYpL5m6AAgAACXDAAAAD59jblTirQe9upFsmZbpMudy7Lz1X1DYsxOOSWpfPqNX2WqktK0DMvuGwlbNj44TleLPQ+Gsfb+GOWOKJoIrWb3cIMeeON6lz2umTqMXV8Mj30yWPpjoSa9ujK8SyeJP5y5mOW1D6hvLepeveEAEDo0mgCRClOEgANv3B9a6fikgUSu/DmAMATrGx7nng5p5iimPNZsfQLYB2sDLIkzRKZOHGAaUyDcpFBSLG9MCQALgAIgQs2YunOszLSAyQYPVC2YdGGeHD2dTdJk1pAHGAWDjnkcLKFymS3RQZTInzySoBwMG0QueC3gMsCEYxUqlrcxK6k1LQQcsmyYeQPdC2YfuGPASCBkcVMQQqpVJshui1tkXQJQV0OXGAZMXSOEEBRirXbVRQW7ugq7IM7rPWSZyDlM3IuNEkxzCOJ0ny2ThNkyRai1b6ev//3dzNGzNb//4uAvHT5sURcZCFcuKLhOFs8mLAAEAt4UWAAIABAAAAAB4qbHo0tIjVkUU//uQZAwABfSFz3ZqQAAAAAngwAAAE1HjMp2qAAAAACZDgAAAD5UkTE1UgZEUExqYynN1qZvqIOREEFmBcJQkwdxiFtw0qEOkGYfRDifBui9MQg4QAHAqWtAWHoCxu1Yf4VfWLPIM2mHDFsbQEVGwyqQoQcwnfHeIkNt9YnkiaS1oizycqJrx4KOQjahZxWbcZgztj2c49nKmkId44S71j0c8eV9yDK6uPRzx5X18eDvjvQ6yKo9ZSS6l//8elePK/Lf//IInrOF/FvDoADYAGBMGb7FtErm5MXMlmPAJQVgWta7Zx2go+8xJ0UiCb8LHHdftWyLJE0QIAIsI+UbXu67dZMjmgDGCGl1H+vpF4NSDckSIkk7Vd+sxEhBQMRU8j/12UIRhzSaUdQ+rQU5kGeFxm+hb1oh6pWWmv3uvmReDl0UnvtapVaIzo1jZbf/pD6ElLqSX+rUmOQNpJFa/r+sa4e/pBlAABoAAAAA3CUgShLdGIxsY7AUABPRrgCABdDuQ5GC7DqPQCgbbJUAoRSUj+NIEig0YfyWUho1VBBBA//uQZB4ABZx5zfMakeAAAAmwAAAAF5F3P0w9GtAAACfAAAAAwLhMDmAYWMgVEG1U0FIGCBgXBXAtfMH10000EEEEEECUBYln03TTTdNBDZopopYvrTTdNa325mImNg3TTPV9q3pmY0xoO6bv3r00y+IDGid/9aaaZTGMuj9mpu9Mpio1dXrr5HERTZSmqU36A3CumzN/9Robv/Xx4v9ijkSRSNLQhAWumap82WRSBUqXStV/YcS+XVLnSS+WLDroqArFkMEsAS+eWmrUzrO0oEmE40RlMZ5+ODIkAyKAGUwZ3mVKmcamcJnMW26MRPgUw6j+LkhyHGVGYjSUUKNpuJUQoOIAyDvEyG8S5yfK6dhZc0Tx1KI/gviKL6qvvFs1+bWtaz58uUNnryq6kt5RzOCkPWlVqVX2a/EEBUdU1KrXLf40GoiiFXK///qpoiDXrOgqDR38JB0bw7SoL+ZB9o1RCkQjQ2CBYZKd/+VJxZRRZlqSkKiws0WFxUyCwsKiMy7hUVFhIaCrNQsKkTIsLivwKKigsj8XYlwt/WKi2N4d//uQRCSAAjURNIHpMZBGYiaQPSYyAAABLAAAAAAAACWAAAAApUF/Mg+0aohSIRobBAsMlO//Kk4soosy1JSFRYWaLC4qZBYWFRGZdwqKiwkNBVmoWFSJkWFxX4FFRQWR+LsS4W/rFRb/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////VEFHAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAU291bmRib3kuZGUAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAMjAwNGh0dHA6Ly93d3cuc291bmRib3kuZGUAAAAAAAAAACU=');
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
                const nameInput = document.getElementById('edit_name');
                nameInput.value = subject.name;
                updateCharCounter(nameInput); // بروزرسانی شمارنده برای نام فعلی
                const gradeInput = document.getElementById('edit_grade');
                gradeInput.value = subject.grade;
                updateCharCounter(gradeInput); // بروزرسانی شمارنده برای پایه فعلی
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
                    showNotification('تغییرات  ذخیره شد', 'success');
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

        // تابع بروزرسانی شمارنده کاراکترها
        function updateCharCounter(input) {
            const counter = input.nextElementSibling;
            const currentLength = input.value.length;
            const maxLength = input.maxLength;
            counter.textContent = `${currentLength} / ${maxLength}`;

            // تغییر رنگ شمارنده بر اساس تعداد کاراکترها
            counter.classList.remove('limit-near', 'limit-reached');
            if (currentLength >= maxLength) {
                counter.classList.add('limit-reached');
            } else if (currentLength >= maxLength * 0.8) {
                counter.classList.add('limit-near');
            }
        }

        // اضافه کردن اعتبارسنجی به فرم افزودن کتاب
        document.getElementById('addSubjectForm').addEventListener('submit', function(e) {
            const nameInput = this.querySelector('input[name="name"]');
            const gradeInput = this.querySelector('input[name="grade"]');
            
            if (nameInput.value.length > 26) {
                e.preventDefault();
                alert('نام کتاب نمی‌تواند بیشتر از 26 حرف باشد');
                return;
            }
            
            if (gradeInput.value.length > 16) {
                e.preventDefault();
                alert('پایه تحصیلی نمی‌تواند بیشتر از 16 حرف باشد');
                return;
            }
        });

        // اجرای اولیه
        document.addEventListener('DOMContentLoaded', function() {
            // نمایش لودر در هنگام رفرش صفحه
            const pageLoader = document.getElementById('pageLoader');
            
            // اجرای توابع اصلی به جز انیمیشن‌ها
            restoreState();
            checkTextOverflow();
            restoreTimerState();

            // مخفی کردن لودر و سپس اجرای انیمیشن‌ها
            setTimeout(() => {
                pageLoader.classList.remove('active');
                setTimeout(() => {
                    animateProgressBars();
                }, 300);
            }, 500);
            
            // بازیابی تب فعال از localStorage
            const activeTab = localStorage.getItem('activeTab');
            if (activeTab) {
                const tab = document.querySelector(activeTab);
                if (tab) {
                    const bsTab = new bootstrap.Tab(tab);
                    bsTab.show();
                }
            }
        });

        // نمایش لودر قبل از رفرش صفحه
        window.addEventListener('beforeunload', function() {
            document.getElementById('pageLoader').classList.add('active');
        });

        // اضافه کردن فراخوانی تابع بررسی overflow بعد از باز شدن مودال مدیریت
        document.getElementById('manageSubjectsModal').addEventListener('shown.bs.modal', function() {
            checkTextOverflow();
        });

        // تابع انیمیشن نوار پیشرفت - بازنویسی شده
        function animateProgressBars() {
            const progressBars = document.querySelectorAll('.progress-fill');
            progressBars.forEach(bar => {
                // ابتدا عرض را صفر می‌کنیم
                bar.style.width = '0';
                
                // کمی تاخیر برای اجرای انیمیشن
                setTimeout(() => {
                    bar.style.width = bar.getAttribute('data-progress') + '%';
                }, 100);
            });
        }

        // تابع بروزرسانی آمار
        function updateStats() {
            // نمایش وضعیت در حال بارگیری
            document.querySelectorAll('.stat-value, #progressPercent, #totalAnswers, #lastActivity').forEach(el => {
                el.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
            });

            fetch('stats.php?get_stats')
                .then(response => response.json())
                .then(data => {
                    // بروزرسانی آمار کلی با انیمیشن
                    animateNumber('totalSubjects', data.subject_stats.total_subjects);
                    animateNumber('totalModules', data.subject_stats.total_modules);
                    animateNumber('totalQuestions', data.subject_stats.total_questions);
                    
                    // بروزرسانی آمار پاسخ‌ها
                    animateNumber('totalAnswers', data.answer_stats.total_answers);
                    
                    // بروزرسانی درصد پیشرفت با انیمیشن
                    const progress = data.answer_stats.progress_percent;
                    animateProgress(progress);
                    
                    // بروزرسانی آخرین فعالیت
                    const lastActivity = data.answer_stats.last_activity ? 
                        new Date(data.answer_stats.last_activity).toLocaleDateString('fa-IR') :
                        'بدون فعالیت';
                    document.getElementById('lastActivity').textContent = lastActivity;

                    // بروزرسانی آمار جدید
                    updateDetailedStats(data.detailed_stats);

                    // مخفی کردن لودر
                    document.getElementById('pageLoader').classList.remove('active');
                })
                .catch(error => {
                    console.error('Error fetching stats:', error);
                    document.querySelectorAll('.stat-value, #progressPercent, #totalAnswers, #lastActivity').forEach(el => {
                        el.innerHTML = '<i class="fas fa-exclamation-circle text-danger"></i>';
                    });
                    // مخفی کردن لودر در صورت خطا
                    document.getElementById('pageLoader').classList.remove('active');
                });
        }

        // تابع انیمیشن اعداد
        function animateNumber(elementId, finalNumber) {
            const element = document.getElementById(elementId);
            const duration = 1000; // مدت زمان انیمیشن به میلی‌ثانیه
            const start = parseInt(element.textContent) || 0;
            const increment = (finalNumber - start) / (duration / 16);
            let current = start;
            
            const animate = () => {
                current += increment;
                if ((increment >= 0 && current >= finalNumber) || 
                    (increment < 0 && current <= finalNumber)) {
                    element.textContent = finalNumber;
                } else {
                    element.textContent = Math.round(current);
                    requestAnimationFrame(animate);
                }
            };
            
            animate();
        }

        // تابع انیمیشن دایره پیشرفت
        function animateProgress(targetProgress) {
            const progressElement = document.getElementById('progressPercent');
            const circleElement = document.querySelector('.circular-progress');
            const duration = 1000;
            const start = parseInt(progressElement.textContent) || 0;
            const increment = (targetProgress - start) / (duration / 16);
            let current = start;
            
            const animate = () => {
                current += increment;
                if ((increment >= 0 && current >= targetProgress) || 
                    (increment < 0 && current <= targetProgress)) {
                    progressElement.textContent = targetProgress;
                    circleElement.style.setProperty('--progress', `${targetProgress * 3.6}deg`);
                } else {
                    progressElement.textContent = Math.round(current);
                    circleElement.style.setProperty('--progress', `${current * 3.6}deg`);
                    requestAnimationFrame(animate);
                }
            };
            
            animate();
        }

        // اضافه کردن استایل‌های جدید
        const newStyles = `
            .circular-progress {
                background: conic-gradient(var(--progress-color, #2196F3) var(--progress), #f0f0f0 0deg);
                transition: background 0.3s ease;
            }

            .stat-value i.fa-spinner {
                font-size: 1rem;
                color: #2196F3;
            }

            .stat-value i.fa-exclamation-circle {
                font-size: 1rem;
                color: #dc3545;
            }

            @keyframes fadeIn {
                from { opacity: 0; transform: translateY(10px); }
                to { opacity: 1; transform: translateY(0); }
            }

            .stats-grid .stat-item {
                animation: fadeIn 0.5s ease forwards;
            }

            .stats-grid .stat-item:nth-child(1) { animation-delay: 0.1s; }
            .stats-grid .stat-item:nth-child(2) { animation-delay: 0.2s; }
            .stats-grid .stat-item:nth-child(3) { animation-delay: 0.3s; }
        `;

        // اضافه کردن استایل‌ها به صفحه
        const styleSheet = document.createElement("style");
        styleSheet.textContent = newStyles;
        document.head.appendChild(styleSheet);

        // بروزرسانی آمار هنگام باز شدن تب
        document.getElementById('stats-tab').addEventListener('shown.bs.tab', updateStats);

        // بروزرسانی اولیه آمار
        document.addEventListener('DOMContentLoaded', () => {
            if (document.querySelector('#stats-content.active')) {
                updateStats();
            }
        });

        // تابع رفرش تب کتاب‌ها
        function refreshBooksTab() {
            fetch('get_books.php')
                .then(response => response.json())
                .then(data => {
                    subjects = data.subjects;
                    modules = data.modules;
                    userAnswers = data.userAnswers;
                    
                    // بروزرسانی لیست کتاب‌ها
                    const subjectList = document.getElementById('subjectList');
                    if (subjectList) {
                        if (subjects.length === 0) {
                            subjectList.innerHTML = `
                                <div id="emptyState">
                                    <i class="fas fa-book mb-3"></i>
                                    <h5>هنوز کتابی اضافه نکرده‌اید</h5>
                                    <p class="text-muted">برای شروع، روی دکمه "افزودن کتاب جدید" کلیک کنید</p>
                                </div>
                            `;
                        } else {
                            subjectList.innerHTML = subjects.map(subject => {
                                // محاسبه درصد پیشرفت
                                const totalQuestions = modules[subject.id]?.reduce((sum, module) => 
                                    sum + module.questions_count, 0) || 0;
                                const answeredQuestions = Object.keys(userAnswers).filter(key => 
                                    key.startsWith(`module_`) && modules[subject.id]?.some(m => 
                                        key.startsWith(`module_${m.id}_`)
                                    )
                                ).length;
                                const progressPercent = totalQuestions > 0 ? 
                                    Math.round((answeredQuestions / totalQuestions) * 100) : 0;
                                const progressClass = progressPercent < 30 ? 'low' : 
                                    (progressPercent < 60 ? 'medium' : 'high');
                                
                                // تعیین وضعیت پیشرفت
                                let statusIcon, statusText, statusClass;
                                if (progressPercent == 100) {
                                    statusIcon = 'check-circle';
                                    statusText = 'تموم شد!';
                                    statusClass = 'complete';
                                } else if (progressPercent >= 60) {
                                    statusIcon = 'fire';
                                    statusText = 'عالی پیش میری';
                                    statusClass = 'good';
                                } else if (progressPercent >= 30) {
                                    statusIcon = 'running';
                                    statusText = 'تو راهی';
                                    statusClass = 'half';
                                } else if (progressPercent > 0) {
                                    statusIcon = 'hourglass-start';
                                    statusText = 'تازه اولشه';
                                    statusClass = 'start';
                                } else {
                                    statusIcon = 'book';
                                    statusText = 'منتظر شروع';
                                    statusClass = 'none';
                                }

                                return `
                                    <div class="position-relative">
                                        <button class="btn btn-lg btn-outline-primary book-btn w-100" 
                                                onclick="showModules(${subject.id})">
                                            <div class="d-flex flex-column w-100">
                                                <div class="d-flex align-items-center justify-content-between">
                                                    <span class="book-name">${subject.name}</span>
                                                    <small class="text-muted">${subject.grade}</small>
                                                </div>
                                                <div class="progress-line">
                                                    <div class="progress-fill ${progressClass}" 
                                                         data-progress="${progressPercent}"></div>
                                                </div>
                                                <div class="progress-text">
                                                    <span class="progress-status ${statusClass}">
                                                        <i class="fas fa-${statusIcon}"></i>
                                                        ${statusText}
                                                    </span>
                                                    <span class="progress-percent">${progressPercent}%</span>
                                                </div>
                                            </div>
                                        </button>
                                    </div>
                                `;
                            }).join('');
                        }
                        animateProgressBars();
                        checkTextOverflow();
                    }
                })
                .catch(error => console.error('Error refreshing books:', error));
        }

        // تابع رفرش تب آمار
        function refreshStatsTab() {
            updateStats();
        }

        // تابع رفرش تب پنل کاربری
        function refreshProfileTab() {
            // در حال حاضر محتوای خاصی ندارد
            console.log('Profile tab refreshed');
        }

        // تابع جدید برای نمایش آمار تفصیلی
        function updateDetailedStats(stats) {
            const detailedStatsHtml = `
                <div class="row g-4 mt-4">
                    <!-- کارت عملکرد روزانه -->
                    <div class="col-md-6">
                        <div class="card h-100">
                            <div class="card-body">
                                <h5 class="card-title mb-5">
                                    <i class="fas fa-calendar-check text-primary me-2"></i>
                                    عملکرد روزانه
                                </h5>
                                <div class="d-flex align-items-center mb-3">
                                    <div class="flex-grow-1">
                                        <h6 class="mb-1">امروز</h6>
                                        <small class="text-muted">${stats?.today_answers || 0} پاسخ</small>
                                    </div>
                                    <div class="progress flex-grow-1" style="height: 8px;">
                                        <div class="progress-bar" role="progressbar" 
                                             style="width: ${stats?.today_progress || 0}%" 
                                             aria-valuenow="${stats?.today_progress || 0}" 
                                             aria-valuemin="0" 
                                             aria-valuemax="100"></div>
                                    </div>
                                </div>
                                <div class="d-flex align-items-center">
                                    <div class="flex-grow-1">
                                        <h6 class="mb-1">دیروز</h6>
                                        <small class="text-muted">${stats?.yesterday_answers || 0} پاسخ</small>
                                    </div>
                                    <div class="progress flex-grow-1" style="height: 8px;">
                                        <div class="progress-bar bg-info" role="progressbar" 
                                             style="width: ${stats?.yesterday_progress || 0}%" 
                                             aria-valuenow="${stats?.yesterday_progress || 0}" 
                                             aria-valuemin="0" 
                                             aria-valuemax="100"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- کارت بهترین عملکرد -->
                    <div class="col-md-6">
                        <div class="card h-100">
                            <div class="card-body">
                                <h5 class="card-title mb-5">
                                    <i class="fas fa-trophy text-warning me-2"></i>
                                    بهترین عملکرد
                                </h5>
                                <div class="best-performance">
                                    <div style="margin-bottom: 20px;" class="d-flex align-items-center">
                                        <i class="fas fa-star text-warning me-2"></i>
                                        <div>
                                            <h6 class="mb-1">بیشترین پاسخ در یک روز</h6>
                                            <small class="text-muted">${stats?.max_answers_day || '---'}</small>
                                        </div>
                                        <h4 class="me-auto mb-0 text-primary">${stats?.max_answers || 0}</h4>
                                    </div>
                                    <div class="d-flex align-items-center">
                                        <i class="fas fa-fire text-danger me-2"></i>
                                        <div>
                                            <h6 class="mb-1">روزهای متوالی فعالیت</h6>
                                            <small class="text-muted">رکورد شخصی شما</small>
                                        </div>
                                        <h4 class="me-auto mb-0 text-primary">${stats?.streak_days || 0}</h4>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- کارت نمودار پیشرفت هفتگی -->
                    <div class="col-12">
                        <div class="card">
                            <div class="card-body">
                                <h5 class="card-title mb-5">
                                    <i class="fas fa-chart-line text-success me-2"></i>
                                    پیشرفت هفتگی
                                </h5>
                                <div class="weekly-progress">
                                    ${generateWeeklyProgressBars(stats?.weekly_progress || {})}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            `;

            // اضافه کردن آمار تفصیلی به صفحه
            const detailedStatsContainer = document.getElementById('detailedStats');
            if (detailedStatsContainer) {
                detailedStatsContainer.innerHTML = detailedStatsHtml;
            }
        }

        // تابع کمکی برای تولید نمودارهای پیشرفت هفتگی
        function generateWeeklyProgressBars(weeklyData) {
            const days = ['شنبه', 'یکشنبه', 'دوشنبه', 'سه‌شنبه', 'چهارشنبه', 'پنج‌شنبه', 'جمعه'];
            return days.map(day => {
                const progress = weeklyData[day]?.progress || 0;
                const answers = weeklyData[day]?.answers || 0;
                return `
                    <div class="d-flex align-items-center mb-3">
                        <div class="day-label" style="width: 60px;">
                            <h6 class="mb-1">${day}</h6>
                            <small class="text-muted">${answers} پاسخ</small>
                        </div>
                        <div class="progress flex-grow-1" style="height: 8px;">
                            <div class="progress-bar bg-success" role="progressbar" 
                                 style="width: ${progress}%" 
                                 aria-valuenow="${progress}" 
                                 aria-valuemin="0" 
                                 aria-valuemax="100"></div>
                        </div>
                        <div class="me-2">
                            <small class="text-muted">${progress}%</small>
                        </div>
                    </div>
                `;
            }).join('');
        }
    </script>
</body>
</html>
</html>