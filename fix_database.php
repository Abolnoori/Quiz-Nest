<?php
require_once 'config.php';

try {
    echo "Fixing database structure...\n";
    
    // اضافه کردن ستون updated_at
    $pdo->exec("ALTER TABLE answers ADD COLUMN updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP");
    echo "Added updated_at column\n";
    
    // بروزرسانی رکوردهای موجود
    $pdo->exec("UPDATE answers SET updated_at = created_at WHERE updated_at IS NULL");
    echo "Updated existing records\n";
    
    echo "\nDatabase structure updated successfully!\n";
    
    // بررسی داده‌ها
    $userId = $_SESSION['user']['id'] ?? 1;
    
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM subjects WHERE user_id = ?");
    $stmt->execute([$userId]);
    echo "\nCurrent stats for user $userId:\n";
    echo "Total subjects: " . $stmt->fetchColumn() . "\n";
    
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM answers WHERE user_id = ?");
    $stmt->execute([$userId]);
    echo "Total answers: " . $stmt->fetchColumn() . "\n";
    
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
    
    if (strpos($e->getMessage(), "Duplicate column name") !== false) {
        echo "Column already exists, continuing...\n";
    }
} 