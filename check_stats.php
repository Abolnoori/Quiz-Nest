<?php
require_once 'config.php';

// برای تست، یک کاربر ثابت را بررسی می‌کنیم
$userId = 1; // یا هر ID دیگری که می‌دانید وجود دارد

echo "Checking database tables...\n\n";

try {
    // بررسی جدول subjects
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM subjects WHERE user_id = ?");
    $stmt->execute([$userId]);
    echo "Total subjects: " . $stmt->fetchColumn() . "\n";

    // بررسی جدول modules
    $stmt = $pdo->prepare("
        SELECT COUNT(*) 
        FROM modules m 
        JOIN subjects s ON s.id = m.subject_id 
        WHERE s.user_id = ?
    ");
    $stmt->execute([$userId]);
    echo "Total modules: " . $stmt->fetchColumn() . "\n";

    // بررسی جدول answers
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM answers WHERE user_id = ?");
    $stmt->execute([$userId]);
    echo "Total answers: " . $stmt->fetchColumn() . "\n";

    // بررسی ستون updated_at
    $stmt = $pdo->prepare("SHOW COLUMNS FROM answers LIKE 'updated_at'");
    $stmt->execute();
    echo "Updated_at column exists: " . ($stmt->rowCount() > 0 ? "Yes" : "No") . "\n";

    // نمایش ساختار جدول answers
    echo "\nTable structure for answers:\n";
    $stmt = $pdo->query("DESCRIBE answers");
    while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo $row['Field'] . " - " . $row['Type'] . "\n";
    }

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?> 