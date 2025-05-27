-- اضافه کردن ستون updated_at به جدول answers
ALTER TABLE answers ADD COLUMN updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;

-- بروزرسانی رکوردهای موجود
UPDATE answers SET updated_at = created_at WHERE updated_at IS NULL; 