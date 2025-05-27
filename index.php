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
        .book-btn .book-name {
            max-width: 200px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            display: block;
        }
        .book-btn:hover .book-name.overflow {
            animation: scroll-text 8s linear infinite;
        }
        @keyframes scroll-text {
            0% { transform: translateX(0%); }
            45% { transform: translateX(calc(-100% + 200px)); }
            55% { transform: translateX(calc(-100% + 200px)); }
            100% { transform: translateX(0%); }
        }
        .module-btn {
            margin-bottom: 0.5rem;
        }
        .module-name {
            max-width: 180px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            display: block;
        }
        .module-name.overflow {
            animation: scroll-text 8s linear infinite;
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

        /* در بخش مدیریت کتاب‌ها */
        #manageSubjectsModal .modiriat-t {
            max-width: 250px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            display: block;
        }
        #manageSubjectsModal .list-group-item:hover .modiriat-t.overflow {
            animation: scroll-text 8s linear infinite;
        }
        .char-counter {
            font-size: 0.8rem;
            color: #6c757d;
            text-align: left;
            margin-top: 0.25rem;
        }
        .char-counter.limit-near {
            color: #ffc107;
        }
        .char-counter.limit-reached {
            color: #dc3545;
        }

        /* استایل‌های جدید برای تایمر آیفون */
        .timer-picker {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 10px;
            padding: 20px 0;
            background: #f8f9fa;
            border-radius: 12px;
            margin-bottom: 1rem;
        }

        .time-column {
            display: flex;
            flex-direction: column;
            align-items: center;
            position: relative;
            height: 120px;
            overflow: hidden;
        }

        .time-column:not(:last-child)::after {
            content: ':';
            position: absolute;
            right: -10px;
            top: 50%;
            transform: translateY(-50%);
            font-size: 24px;
            font-weight: bold;
            color: #333;
        }

        .time-scroll {
            display: flex;
            flex-direction: column;
            transition: transform 0.3s;
            cursor: pointer;
        }

        .time-item {
            height: 40px;
            width: 60px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            color: #333;
            font-weight: 500;
            user-select: none;
        }

        .time-item.selected {
            font-size: 28px;
            font-weight: bold;
            color: #2196F3;
        }

        .time-column-overlay {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            pointer-events: none;
            background: linear-gradient(to bottom,
                rgba(248, 249, 250, 0.9) 0%,
                rgba(248, 249, 250, 0) 30%,
                rgba(248, 249, 250, 0) 70%,
                rgba(248, 249, 250, 0.9) 100%
            );
        }

        .time-column-highlight {
            position: absolute;
            top: 50%;
            left: 0;
            right: 0;
            height: 40px;
            transform: translateY(-50%);
            background: rgba(33, 150, 243, 0.1);
            border-top: 1px solid rgba(33, 150, 243, 0.2);
            border-bottom: 1px solid rgba(33, 150, 243, 0.2);
            pointer-events: none;
        }

        /* استایل‌های جدید برای تایمر ساده */
        .timer-inputs {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 15px;
            margin-bottom: 1.5rem;
            direction: ltr;
        }

        .timer-input-group {
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .timer-input-group label {
            font-size: 0.9rem;
            color: #666;
            margin-bottom: 5px;
        }

        .timer-input-group input {
            width: 80px;
            height: 60px;
            font-size: 28px;
            text-align: center;
            border: 2px solid #e9ecef;
            border-radius: 12px;
            padding: 0;
            -moz-appearance: textfield;
            background: #f8f9fa;
        }

        .timer-input-group input::-webkit-outer-spin-button,
        .timer-input-group input::-webkit-inner-spin-button {
            -webkit-appearance: none;
            margin: 0;
        }

        .timer-separator {
            font-size: 32px;
            font-weight: bold;
            color: #333;
            margin-top: 25px;
        }

        /* استایل‌های نوار پیشرفت */
        .progress-line {
            width: 100%;
            height: 4px;
            background: #f0f0f0;
            border-radius: 4px;
            margin-top: 12px;
            overflow: hidden;
            position: relative;
        }

        .progress-line .progress-fill {
            position: absolute;
            top: 0;
            left: 0;
            height: 100%;
            border-radius: 4px;
            width: 0;
            transition: width 0.8s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .progress-fill.low {
            background: #dc3545;  /* قرمز */
        }

        .progress-fill.medium {
            background: #ffc107;  /* زرد */
        }

        .progress-fill.high {
            background: #28a745;  /* سبز */
        }

        .progress-text {
            font-size: 0.85rem;
            color: #6c757d;
            margin-top: 10px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .progress-status {
            font-weight: 500;
            color: #495057;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .progress-status i {
            font-size: 0.9rem;
        }

        .progress-percent {
            font-size: 0.8rem;
            opacity: 0.7;
        }

        .book-btn:hover .progress-text {
            color: #2196F3;
        }

        .book-btn:hover .progress-status i {
            transform: scale(1.2);
        }

        .progress-status i {
            transition: transform 0.2s ease;
        }

        .progress-status.complete i { color: #28a745; }
        .progress-status.good i { color: #17a2b8; }
        .progress-status.half i { color: #ffc107; }
        .progress-status.start i { color: #dc3545; }
        .progress-status.none i { color: #6c757d; }

        /* استایل‌های جدید برای بخش آمار */
        .stat-card {
            background: #fff;
            border-radius: 15px;
            padding: 1.5rem;
            box-shadow: 0 2px 6px rgba(0,0,0,0.08);
            height: 100%;
            transition: transform 0.2s;
        }

        .stat-card:hover {
            transform: translateY(-5px);
        }

        .stat-card-content {
            display: flex;
            align-items: center;
            gap: 1.5rem;
        }

        .stat-icon {
            width: 60px;
            height: 60px;
            border-radius: 12px;
            background: rgba(33, 150, 243, 0.1);
            color: #2196F3;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
        }

        .stat-icon.purple {
            background: rgba(156, 39, 176, 0.1);
            color: #9C27B0;
        }

        .stat-icon.green {
            background: rgba(76, 175, 80, 0.1);
            color: #4CAF50;
        }

        .stat-icon.orange {
            background: rgba(255, 152, 0, 0.1);
            color: #FF9800;
        }

        .stat-info h3 {
            font-size: 1.8rem;
            margin: 0;
            font-weight: 600;
            color: #333;
        }

        .stat-info p {
            margin: 0;
            color: #666;
            font-size: 0.9rem;
        }

        .stats-chart-card {
            background: #fff;
            border-radius: 15px;
            padding: 1.5rem;
            box-shadow: 0 2px 6px rgba(0,0,0,0.08);
        }

        .stats-chart-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }

        .stats-chart-header h5 {
            margin: 0;
            font-weight: 600;
            color: #333;
        }

        .chart-legend {
            display: flex;
            gap: 1rem;
        }

        .legend-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.9rem;
            color: #666;
        }

        .legend-dot {
            width: 10px;
            height: 10px;
            border-radius: 50%;
        }

        .legend-dot.blue {
            background: #2196F3;
        }

        .stats-chart-body {
            position: relative;
            padding: 1rem 0;
        }

        .donut-center-text {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            text-align: center;
        }

        .donut-center-text h3 {
            margin: 0;
            font-size: 1.8rem;
            font-weight: 600;
            color: #333;
        }

        .donut-center-text p {
            margin: 0;
            font-size: 0.9rem;
            color: #666;
        }

        .stats-table-card {
            background: #fff;
            border-radius: 15px;
            padding: 1.5rem;
            box-shadow: 0 2px 6px rgba(0,0,0,0.08);
        }

        .stats-table-header {
            margin-bottom: 1.5rem;
        }

        .stats-table-header h5 {
            margin: 0;
            font-weight: 600;
            color: #333;
        }

        .stats-table {
            margin: 0;
        }

        .stats-table th {
            font-weight: 500;
            color: #666;
            border-bottom: 2px solid #eee;
            padding: 1rem;
        }

        .stats-table td {
            padding: 1rem;
            vertical-align: middle;
            border-bottom: 1px solid #eee;
        }

        .stats-table .book-name {
            font-weight: 500;
            color: #333;
        }

        .progress-wrapper {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .progress {
            flex: 1;
            height: 6px;
            background: #eee;
            border-radius: 3px;
            overflow: hidden;
        }

        .progress-bar {
            background: #2196F3;
            border-radius: 3px;
        }

        .progress-text {
            min-width: 45px;
            font-size: 0.9rem;
            color: #666;
        }

        @media (max-width: 768px) {
            .stat-card {
                padding: 1rem;
            }

            .stat-icon {
                width: 50px;
                height: 50px;
                font-size: 1.2rem;
            }

            .stat-info h3 {
                font-size: 1.4rem;
            }

            .stats-chart-card, .stats-table-card {
                padding: 1rem;
            }

            .stats-table th, .stats-table td {
                padding: 0.75rem;
            }
        }
    </style>

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
    <!-- لودر -->
    <div class="loader-overlay" id="loaderOverlay">
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
        <!-- نوار تب‌ها -->
        <ul class="nav nav-tabs mb-4" id="mainTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="answers-tab" data-bs-toggle="tab" data-bs-target="#answers-content" type="button" role="tab">
                    <i class="fas fa-book"></i>
                    کتاب‌های من
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="stats-tab" data-bs-toggle="tab" data-bs-target="#stats-content" type="button" role="tab">
                    <i class="fas fa-chart-bar"></i>
                    آمار
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="profile-tab" data-bs-toggle="tab" data-bs-target="#profile-content" type="button" role="tab">
                    <i class="fas fa-user"></i>
                    پنل کاربری
                </button>
            </li>
        </ul>

        <!-- محتوای تب‌ها -->
        <div class="tab-content" id="mainTabsContent">
            <!-- تب پاسخ‌دون -->
            <div class="tab-pane fade show active" id="answers-content" role="tabpanel">
        <!-- بخش اصلی -->
        <div id="subjectSection" class="section active">
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
            <div style="margin-bottom: 50px;" class="safebaed d-flex justify-content-between mt-4">
                <button class="btn btn-outline-primary" onclick="prevPage()">صفحه قبل</button>
                <button class="btn btn-outline-primary" onclick="nextPage()">صفحه بعد</button>
                    </div>
                </div>
            </div>

            <!-- تب آمار -->
            <div class="tab-pane fade" id="stats-content" role="tabpanel">
                <?php 
                    // محاسبه کل سوالات و پاسخ‌های داده شده
                    $totalQuestions = 0;
                    $answeredQuestions = 0;
                    
                    foreach ($subjects as $subject) {
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
                    }
                    
                    $progressPercent = $totalQuestions > 0 ? round(($answeredQuestions / $totalQuestions) * 100) : 0;
                ?>
                <div class="stats-header mb-4">
                    <h4>داشبورد آماری</h4>
                    <p class="text-muted">خلاصه عملکرد شما</p>
                </div>
                
                <!-- کارت‌های آمار -->
                <div class="stats-cards">
                    <div class="stat-card" data-aos="fade-up" data-aos-delay="100">
                        <div class="stat-value">
                            <span class="counter"><?php echo count($subjects); ?></span>
                            <div class="stat-label">کتاب</div>
                        </div>
                        <div class="stat-icon">
                            <i style="margin: 0px;" class="fas fa-book"></i>
                        </div>
                        <div class="stat-trend positive">
                            <i class="fas fa-arrow-up"></i>
                            <span>جدید</span>
                        </div>
                    </div>

                    <div class="stat-card" data-aos="fade-up" data-aos-delay="200">
                        <div class="stat-value">
                            <span class="counter"><?php echo $totalQuestions; ?></span>
                            <div class="stat-label">سوال</div>
                        </div>
                        <div class="stat-icon purple">
                            <i style="margin: 0px;" class="fas fa-tasks"></i>
                        </div>
                        <div class="stat-trend positive">
                            <i class="fas fa-arrow-up"></i>
                            <span>کل</span>
                        </div>
                    </div>

                    <div class="stat-card" data-aos="fade-up" data-aos-delay="300">
                        <div class="stat-value">
                            <span class="counter"><?php $darsadzade = $answeredQuestions; echo $answeredQuestions;?></span>
                            <div class="stat-label">پاسخ</div>
                        </div>
                        <div class="stat-icon green">
                            <i style="margin: 0px;" class="fas fa-check-circle"></i>
                        </div>
                        <div class="stat-trend positive">
                            <i class="fas fa-arrow-up"></i>
                            <span>تکمیل شده</span>
                        </div>
                    </div>

                    <div class="stat-card" data-aos="fade-up" data-aos-delay="400">
                        <div class="stat-value">
                            <span class="counter"><?php echo $progressPercent; ?></span>%
                            <div class="stat-label">پیشرفت کلی</div>
                        </div>
                        <div class="stat-icon orange">
                            <i style="margin: 0px;" class="fas fa-chart-pie"></i>
                        </div>
                        <div class="stat-trend <?php echo $progressPercent > 50 ? 'positive' : ''; ?>">
                            <i class="fas fa-<?php echo $progressPercent > 50 ? 'arrow-up' : 'arrow-right'; ?>"></i>
                            <span>پیشرفت</span>
                        </div>
                    </div>
                </div>

                <!-- نمودارها -->
                <div class="nemodar stats-charts">
                    <div class="chart-container" data-aos="fade-up" data-aos-delay="100">
                        <div class="chart-header">
                            <h5>پیشرفت روزانه</h5>
                            <div class="chart-actions">

                            </div>
                        </div>
                        <div class="nemodar-ckaty chart-body">
                            <canvas id="dailyProgressChart"></canvas>
                        </div>
                    </div>

                    <div class="chart-container" data-aos="fade-up" data-aos-delay="200">
                        <div class="chart-header">
                            <h5>توزیع پیشرفت</h5>
                            <div class="chart-legend">
                                <div class="legend-item">
                                    <span class="legend-dot completed"></span>
                                    تکمیل شده
                                </div>
                                <div class="legend-item">
                                    <span class="legend-dot remaining"></span>
                                    باقی‌مانده
                                </div>
                            </div>
                        </div>
                        <div class="chart-body donut-chart-container">
                            <canvas id="overallProgressChart"></canvas>
                            <div class="donut-center">
                                <div class="donut-value counter"><?php echo $progressPercent; ?>%</div>
                                <div class="donut-label">تکمیل شده</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- جدول عملکرد -->
                <div class="performance-table" data-aos="fade-up" data-aos-delay="300">
                    <div class="amalkard table-header">
                        <h5>عملکرد به تفکیک کتاب</h5>
                        <div class="table-actions">
                            <button class="btn btn-sm btn-light">
                                <i class="fas fa-download"></i>
                                دانلود گزارش
                            </button>
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>نام کتاب</th>
                                    <th>تعداد سوالات</th>
                                    <th>پاسخ داده شده</th>
                                    <th>درصد پیشرفت</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($subjects as $index => $subject): ?>
                                    <?php
                                        $subjectQuestions = 0;
                                        $answeredQuestions = 0;
                                        foreach ($modules[$subject['id']] as $module) {
                                            $subjectQuestions += $module['questions_count'];
                                            foreach ($userAnswers as $key => $value) {
                                                if (strpos($key, "module_{$module['id']}_") === 0) {
                                                    $answeredQuestions++;
                                                }
                                            }
                                        }
                                        $progress = $subjectQuestions > 0 ? 
                                            round(($answeredQuestions / $subjectQuestions) * 100) : 0;
                                    ?>
                                    <tr class="animate-row" style="animation-delay: <?php echo $index * 0.1; ?>s">
                                        <td>
                                            <div class="book-info">
                                                <div class="book-icon">
                                                    <i class="fas fa-book"></i>
                                                </div>
                                                <div class="book-details">
                                                    <div class="book-name"><?php echo htmlspecialchars($subject['name']); ?></div>
                                                    <div class="book-grade"><?php echo htmlspecialchars($subject['grade']); ?></div>
                                                </div>
                                            </div>
                                        </td>
                                        <td><span class="counter"><?php echo $subjectQuestions; ?></span></td>
                                        <td><span class="counter"><?php echo $answeredQuestions; ?></span></td>
                                        <td>
                                            <div class="progress-wrapper">
                                                <div class="progress">
                                                    <div class="progress-bar progress-animate" 
                                                         style="width: <?php echo $progress; ?>%">
                                                    </div>
                                                </div>
                                                <span class="progress-text">
                                                    <span class="counter"><?php echo $progress; ?></span>%
                                                </span>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <style>
                /* استایل‌های جدید برای بخش آمار */
                .stats-header {
                    text-align: right;
                    padding: 1rem 0;
                }

                .stats-header h4 {
                    margin: 0;
                    font-weight: 600;
                    color: #2c3e50;
                }

                .stats-header p {
                    margin: 0.5rem 0 0;
                    font-size: 0.9rem;
                }

                .stats-cards {
                    display: grid;
                    grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
                    gap: 1.5rem;
                    margin-bottom: 2rem;
                }

                .stat-card {
                    background: #fff;
                    border-radius: 16px;
                    padding: 1.5rem;
                    position: relative;
                    overflow: hidden;
                    display: flex;
                    flex-direction: column;
                    gap: 1rem;
                    box-shadow: 0 4px 20px rgba(0,0,0,0.05);
                    transition: all 0.3s ease;
                }

                .stat-card:hover {
                    transform: translateY(-5px);
                    box-shadow: 0 8px 25px rgba(0,0,0,0.1);
                }

                .stat-value {
                    font-size: 2rem;
                    font-weight: 600;
                    color: #2c3e50;
                    line-height: 1;
                    display: flex;
                    align-items: baseline;
                    gap: 0.25rem;
                }

                .stat-label {
                    font-size: 0.9rem;
                    color: #7f8c8d;
                    margin-top: 0.25rem;
                }

                .stat-icon {
                    position: absolute;
                    left: 1.5rem;
                    top: 1.5rem;
                    width: 48px;
                    height: 48px;
                    border-radius: 12px;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    font-size: 1.5rem;
                    background: rgba(33, 150, 243, 0.1);
                    color: #2196F3;
                    transition: all 0.3s ease;
                }

                .stat-icon.purple {
                    background: rgba(156, 39, 176, 0.1);
                    color: #9C27B0;
                }

                .stat-icon.green {
                    background: rgba(76, 175, 80, 0.1);
                    color: #4CAF50;
                }

                .stat-icon.orange {
                    background: rgba(255, 152, 0, 0.1);
                    color: #FF9800;
                }

                .stat-trend {
                    display: flex;
                    align-items: center;
                    gap: 0.5rem;
                    font-size: 0.9rem;
                    padding: 0.5rem 1rem;
                    border-radius: 8px;
                    background: rgba(76, 175, 80, 0.1);
                    color: #4CAF50;
                    width: fit-content;
                }

                .stat-trend.negative {
                    background: rgba(244, 67, 54, 0.1);
                    color: #F44336;
                }

                .stats-charts {
                    display: grid;
                    grid-template-columns: 2fr 1fr;
                    gap: 1.5rem;
                    margin-bottom: 2rem;
                }

                .chart-container {
                    background: #fff;
                    border-radius: 16px;
                    padding: 1.5rem;
                    box-shadow: 0 4px 20px rgba(0,0,0,0.05);
                }

                .chart-header {
                    display: flex;
                    justify-content: space-between;
                    align-items: center;
                    margin-bottom: 1.5rem;
                }

                .chart-header h5 {
                    margin: 0;
                    font-weight: 600;
                    color: #2c3e50;
                }

                .chart-actions {
                    display: flex;
                    gap: 0.5rem;
                }

                .chart-actions .btn {
                    padding: 0.25rem 1rem;
                    border-radius: 8px;
                    font-size: 0.9rem;
                }

                .chart-actions .btn.active {
                    background: #2196F3;
                    color: #fff;
                }

                .chart-legend {
                    display: flex;
                    gap: 1rem;
                }

                .legend-item {
                    display: flex;
                    align-items: center;
                    gap: 0.5rem;
                    font-size: 0.9rem;
                    color: #7f8c8d;
                }

                .legend-dot {
                    width: 10px;
                    height: 10px;
                    border-radius: 50%;
                }

                .legend-dot.completed {
                    background: #4CAF50;
                }

                .legend-dot.remaining {
                    background: #ecf0f1;
                }

                .donut-chart-container {
                    position: relative;
                    padding-top: 1rem;
                }

                .donut-center {
                    position: absolute;
                    top: 50%;
                    left: 50%;
                    transform: translate(-50%, -50%);
                    text-align: center;
                }

                .donut-value {
                    font-size: 2rem;
                    font-weight: 600;
                    color: #2c3e50;
                    line-height: 1;
                }

                .donut-label {
                    font-size: 0.9rem;
                    color: #7f8c8d;
                    margin-top: 0.25rem;
                }

                .performance-table {
                    background: #fff;
                    border-radius: 16px;
                    padding: 1.5rem;
                    box-shadow: 0 4px 20px rgba(0,0,0,0.05);
                }

                .table-header {
                    display: flex;
                    justify-content: space-between;
                    align-items: center;
                    margin-bottom: 1.5rem;
                }

                .table-header h5 {
                    margin: 0;
                    font-weight: 600;
                    color: #2c3e50;
                }

                .table {
                    margin: 0;
                }

                .table th {
                    font-weight: 500;
                    color: #7f8c8d;
                    border: none;
                    padding: 1rem;
                }

                .table td {
                    padding: 1rem;
                    vertical-align: middle;
                    border: none;
                }

                .book-info {
                    display: flex;
                    align-items: center;
                    gap: 1rem;
                }

                .book-icon {
                    width: 40px;
                    height: 40px;
                    border-radius: 10px;
                    background: rgba(33, 150, 243, 0.1);
                    color: #2196F3;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                }

                .book-details {
                    display: flex;
                    flex-direction: column;
                }

                .book-name {
                    font-weight: 500;
                    color: #2c3e50;
                }

                .book-grade {
                    font-size: 0.9rem;
                    color: #7f8c8d;
                }

                .progress-wrapper {
                    display: flex;
                    align-items: center;
                    gap: 1rem;
                }

                .progress {
                    flex: 1;
                    height: 6px;
                    background: #ecf0f1;
                    border-radius: 3px;
                    overflow: hidden;
                }

                .progress-bar {
                    background: #2196F3;
                    border-radius: 3px;
                    position: relative;
                    overflow: hidden;
                }

                .progress-animate {
                    animation: progressAnimation 1.5s ease-out forwards;
                }

                .progress-bar::after {
                    content: '';
                    position: absolute;
                    top: 0;
                    left: 0;
                    width: 100%;
                    height: 100%;
                    background: linear-gradient(90deg, 
                        transparent, 
                        rgba(255,255,255,0.3), 
                        transparent);
                    animation: progressShine 2s infinite;
                }

                .progress-text {
                    min-width: 45px;
                    font-size: 0.9rem;
                    color: #2c3e50;
                    font-weight: 500;
                }

                @keyframes progressAnimation {
                    from { width: 0; }
                }

                @keyframes progressShine {
                    0% { transform: translateX(-100%); }
                    100% { transform: translateX(100%); }
                }

                /* موبایل */
                @media (max-width: 768px) {
                    .stats-cards {
                        grid-template-columns: 1fr;
                    }

                    .stats-charts {
                        grid-template-columns: 1fr;
                    }

                    .stat-card {
                        padding: 1.25rem;
                    }

                    .stat-value {
                        font-size: 1.75rem;
                    }

                    .stat-icon {
                        width: 40px;
                        height: 40px;
                        font-size: 1.25rem;
                    }

                    .chart-container {
                        padding: 1.25rem;
                    }

                    .donut-value {
                        font-size: 1.5rem;
                    }

                    .table-responsive {
                        margin: 0 -1.25rem;
                        padding: 0 1.25rem;
                        overflow-x: auto;
                    }

                    .book-info {
                        min-width: 200px;
                    }

                    .progress-wrapper {
                        min-width: 150px;
                    }
                }
            </style>

            <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
            <script>
                // نمودار پیشرفت روزانه
                <?php
                // محاسبه آمار پاسخ‌های روزانه
                $userId = $_SESSION['user']['id'];
                $weekDays = ['شنبه', 'یکشنبه', 'دوشنبه', 'سه‌شنبه', 'چهارشنبه', 'پنج‌شنبه', 'جمعه'];
                $dailyStats = array_fill(0, 7, 0);

                // محاسبه تاریخ شنبه (اول هفته)
                $saturday = new DateTime();
                while ($saturday->format('w') != 6) { // 6 = شنبه
                    $saturday->modify('-1 day');
                }
                $saturday->setTime(0, 0, 0);

                // دریافت آمار هفته جاری
                $stmt = $pdo->prepare("
                    SELECT 
                        DATE(created_at) as answer_date,
                        COUNT(*) as answer_count
                    FROM answers 
                    WHERE user_id = ? 
                    AND created_at >= ?
                    AND created_at <= CURRENT_TIMESTAMP
                    GROUP BY DATE(created_at)
                ");

                $stmt->execute([
                    $userId, 
                    $saturday->format('Y-m-d 00:00:00')
                ]);

                $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

                // پر کردن آرایه آمار روزانه
                foreach ($results as $row) {
                    $answerDate = new DateTime($row['answer_date']);
                    $daysDiff = $saturday->diff($answerDate)->days;
                    if ($daysDiff >= 0 && $daysDiff < 7) {
                        $dailyStats[$daysDiff] = (int)$row['answer_count'];
                    }
                }

                // برای دیباگ
                error_log('Daily Stats: ' . print_r($dailyStats, true));
                error_log('Start Date: ' . $saturday->format('Y-m-d'));
                error_log('Results: ' . print_r($results, true));
                ?>
                const dailyProgressCtx = document.getElementById('dailyProgressChart').getContext('2d');
                new Chart(dailyProgressCtx, {
                    type: 'bar',
                    data: {
                        labels: <?php echo json_encode($weekDays); ?>,
                        datasets: [{
                            label: 'تعداد پاسخ‌ها',
                            data: <?php echo json_encode($dailyStats); ?>,
                            backgroundColor: '#2196F3',
                            borderRadius: 8,
                            maxBarThickness: 40
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                display: false
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                grid: {
                                    display: true,
                                    color: '#f8f9fa'
                                },
                                ticks: {
                                    font: {
                                        family: 'Vazirmatn'
                                    }
                                }
                            },
                            x: {
                                grid: {
                                    display: false
                                },
                                ticks: {
                                    font: {
                                        family: 'Vazirmatn'
                                    }
                                }
                            }
                        }
                    }
                });

            
                // نمودار پیشرفت کلی
                const overallProgressCtx = document.getElementById('overallProgressChart').getContext('2d');
                new Chart(overallProgressCtx, {
                    type: 'doughnut',
                    data: {
                        labels: ['تکمیل شده', 'باقی‌مانده'],
                        datasets: [{
                            data: [<?php echo $darsadzade; ?>, <?php echo $totalQuestions - $darsadzade; ?>],
                            backgroundColor: ['#4CAF50', '#ecf0f1'],
                            borderWidth: 0,
                            borderRadius: 5
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        cutout: '75%',
                        plugins: {
                            legend: {
                                display: false
                            },
                            tooltip: {
                                callbacks: {
                                    label: function(context) {
                                        const value = context.raw;
                                        const total = <?php echo $totalQuestions; ?>;
                                        const percentage = Math.round((value / total) * 100);
                                        return `${value} سوال (${percentage}%)`;
                                    }
                                }
                            }
                        },
                        animation: {
                            animateRotate: true,
                            animateScale: true
                        }
                    }
                });

                // راه‌اندازی AOS
                AOS.init({
                    duration: 800,
                    once: true,
                    offset: 50
                });

                // راه‌اندازی Counter-Up
                jQuery(document).ready(function($) {
                    $('.counter').counterUp({
                        delay: 10,
                        time: 1000
                    });
                });
            </script>

            <!-- تب کتاب‌های من -->
            <div class="tab-pane fade" id="books-content" role="tabpanel">
                <!-- محتوای تب کتاب‌های من -->
            </div>

            <!-- تب پنل کاربری -->
            <div class="tab-pane fade" id="profile-content" role="tabpanel">
               <div class="text-center py-5">
                        <i class="fas fa-user fa-3x text-muted mb-3"></i>
                        <h5 class="paneltext text-muted">بخش پنل کاربری به زودی اضافه خواهد شد</h5>
                    </div>
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

            // اجرای انیمیشن نوار پیشرفت بعد از رندر شدن المان‌ها
            setTimeout(animateProgressBars, 100);
            checkTextOverflow();
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
            restoreState();
            checkTextOverflow();
            restoreTimerState(); // بازیابی وضعیت تایمر در لود صفحه
            animateProgressBars();
        });

        // اضافه کردن فراخوانی تابع بررسی overflow بعد از باز شدن مودال مدیریت
        document.getElementById('manageSubjectsModal').addEventListener('shown.bs.modal', function() {
            checkTextOverflow();
        });

        // تابع جدید برای اعمال انیمیشن نوار پیشرفت
        function animateProgressBars() {
            const progressBars = document.querySelectorAll('.progress-fill');
            progressBars.forEach(bar => {
                // ابتدا عرض را صفر می‌کنیم
                bar.style.width = '0';
                
                // کمی تاخیر برای اجرای انیمیشن
                setTimeout(() => {
                    bar.style.width = bar.getAttribute('data-progress') + '%';
                }, 50);
            });
        }
    </script>
</body>
</html>