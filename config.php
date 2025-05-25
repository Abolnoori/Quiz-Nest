<?php
$host = 'localhost';
$dbname = 'quiz_system';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// ایجاد جداول مورد نیاز اگر وجود نداشته باشند
$queries = [
    // جدول کاربران
    "CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(50) UNIQUE NOT NULL,
        password VARCHAR(255) NOT NULL,
        fullname VARCHAR(100) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )",
    
    // جدول کتاب‌ها
    "CREATE TABLE IF NOT EXISTS subjects (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT,
        name VARCHAR(100) NOT NULL,
        grade VARCHAR(50) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )",
    
    // جدول پودمان‌ها
    "CREATE TABLE IF NOT EXISTS modules (
        id INT AUTO_INCREMENT PRIMARY KEY,
        subject_id INT,
        name VARCHAR(100) NOT NULL,
        questions_count INT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE CASCADE
    )",
    
    // جدول پاسخ‌ها
    "CREATE TABLE IF NOT EXISTS answers (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT,
        module_id INT,
        question_number INT NOT NULL,
        answer INT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (module_id) REFERENCES modules(id) ON DELETE CASCADE
    )"
];

foreach ($queries as $query) {
    try {
        $pdo->exec($query);
    } catch(PDOException $e) {
        die("Error creating tables: " . $e->getMessage());
    }
}

// اضافه کردن ستون questions_count به جدول subjects اگر وجود نداشته باشد
try {
    // بررسی وجود ستون
    $stmt = $pdo->query("SHOW COLUMNS FROM subjects LIKE 'questions_count'");
    if ($stmt->rowCount() == 0) {
        // اضافه کردن ستون
        $pdo->exec("ALTER TABLE subjects ADD COLUMN questions_count INT DEFAULT 20");
        
        // به‌روزرسانی رکوردهای موجود
        $pdo->exec("UPDATE subjects SET questions_count = 20 WHERE questions_count IS NULL");
    }
} catch(PDOException $e) {
    die("Error adding questions_count column: " . $e->getMessage());
}

session_start(); 