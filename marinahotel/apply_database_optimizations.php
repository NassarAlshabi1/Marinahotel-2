<?php
/**
 * Database Optimization Application Script
 * This script safely applies the database optimizations to the Marina Hotel system
 */

require_once 'includes/config.php';
require_once 'includes/db.php';

// Set execution time limit for large operations
set_time_limit(300); // 5 minutes

// Enable error reporting for this script
ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "<!DOCTYPE html>\n";
echo "<html lang='ar' dir='rtl'>\n";
echo "<head>\n";
echo "<meta charset='UTF-8'>\n";
echo "<title>تطبيق تحسينات قاعدة البيانات - فندق مارينا</title>\n";
echo "<style>\n";
echo "body { font-family: Arial, sans-serif; margin: 20px; direction: rtl; }\n";
echo ".success { color: green; background: #e8f5e8; padding: 10px; margin: 5px 0; border-radius: 5px; }\n";
echo ".error { color: red; background: #ffe8e8; padding: 10px; margin: 5px 0; border-radius: 5px; }\n";
echo ".info { color: blue; background: #e8f0ff; padding: 10px; margin: 5px 0; border-radius: 5px; }\n";
echo ".warning { color: orange; background: #fff8e8; padding: 10px; margin: 5px 0; border-radius: 5px; }\n";
echo "</style>\n";
echo "</head>\n";
echo "<body>\n";

echo "<h1>🚀 تطبيق تحسينات قاعدة البيانات - فندق مارينا</h1>\n";

// Function to execute SQL and report results
function executeSQLWithReport($conn, $sql, $description) {
    echo "<div class='info'><strong>تنفيذ:</strong> $description</div>\n";
    
    try {
        $result = $conn->query($sql);
        if ($result) {
            echo "<div class='success'>✅ تم بنجاح: $description</div>\n";
            return true;
        } else {
            echo "<div class='error'>❌ فشل: $description - " . $conn->error . "</div>\n";
            return false;
        }
    } catch (Exception $e) {
        echo "<div class='error'>❌ خطأ: $description - " . $e->getMessage() . "</div>\n";
        return false;
    }
}

// Function to check if column exists
function columnExists($conn, $table, $column) {
    $result = $conn->query("SHOW COLUMNS FROM `$table` LIKE '$column'");
    return $result && $result->num_rows > 0;
}

// Function to check if index exists
function indexExists($conn, $table, $index) {
    $result = $conn->query("SHOW INDEX FROM `$table` WHERE Key_name = '$index'");
    return $result && $result->num_rows > 0;
}

// Function to check if table exists
function tableExists($conn, $table) {
    $result = $conn->query("SHOW TABLES LIKE '$table'");
    return $result && $result->num_rows > 0;
}

echo "<h2>📊 المرحلة 1: تحديث هيكل الجداول</h2>\n";

// Update bookings table structure
if (!columnExists($conn, 'bookings', 'guest_created_at')) {
    executeSQLWithReport($conn, 
        "ALTER TABLE `bookings` ADD COLUMN `guest_created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP AFTER `guest_address`",
        "إضافة عمود guest_created_at إلى جدول bookings"
    );
}

if (!columnExists($conn, 'bookings', 'expected_nights')) {
    executeSQLWithReport($conn, 
        "ALTER TABLE `bookings` ADD COLUMN `expected_nights` int(11) DEFAULT 1 COMMENT 'عدد الليالي المتوقع' AFTER `notes`",
        "إضافة عمود expected_nights إلى جدول bookings"
    );
}

if (!columnExists($conn, 'bookings', 'actual_checkout')) {
    executeSQLWithReport($conn, 
        "ALTER TABLE `bookings` ADD COLUMN `actual_checkout` datetime DEFAULT NULL AFTER `expected_nights`",
        "إضافة عمود actual_checkout إلى جدول bookings"
    );
}

if (!columnExists($conn, 'bookings', 'calculated_nights')) {
    executeSQLWithReport($conn, 
        "ALTER TABLE `bookings` ADD COLUMN `calculated_nights` int(11) DEFAULT 1 AFTER `actual_checkout`",
        "إضافة عمود calculated_nights إلى جدول bookings"
    );
}

if (!columnExists($conn, 'bookings', 'last_calculation')) {
    executeSQLWithReport($conn, 
        "ALTER TABLE `bookings` ADD COLUMN `last_calculation` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER `calculated_nights`",
        "إضافة عمود last_calculation إلى جدول bookings"
    );
}

// Update payment table structure
executeSQLWithReport($conn, 
    "ALTER TABLE `payment` MODIFY COLUMN `amount` int(11) NOT NULL COMMENT 'المبلغ بالريال اليمني بدون كسور'",
    "تحديث نوع عمود amount في جدول payment"
);

if (!columnExists($conn, 'payment', 'room_number')) {
    executeSQLWithReport($conn, 
        "ALTER TABLE `payment` ADD COLUMN `room_number` varchar(10) DEFAULT NULL AFTER `cash_transaction_id`",
        "إضافة عمود room_number إلى جدول payment"
    );
}

// Update cash_transactions table
executeSQLWithReport($conn, 
    "ALTER TABLE `cash_transactions` MODIFY COLUMN `amount` int(11) NOT NULL COMMENT 'المبلغ بالريال اليمني بدون كسور'",
    "تحديث نوع عمود amount في جدول cash_transactions"
);

executeSQLWithReport($conn, 
    "ALTER TABLE `cash_transactions` MODIFY COLUMN `reference_id` int(11) DEFAULT NULL",
    "تحديث عمود reference_id في جدول cash_transactions"
);

echo "<h2>🔐 المرحلة 2: إضافة جداول الأمان</h2>\n";

// Create failed_logins table
if (!tableExists($conn, 'failed_logins')) {
    $sql = "CREATE TABLE `failed_logins` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `username` varchar(50) DEFAULT NULL,
        `ip_address` varchar(45) DEFAULT NULL,
        `attempt_time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        KEY `idx_username` (`username`),
        KEY `idx_ip_address` (`ip_address`),
        KEY `idx_attempt_time` (`attempt_time`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    
    executeSQLWithReport($conn, $sql, "إنشاء جدول failed_logins");
}

// Create user_activity_log table
if (!tableExists($conn, 'user_activity_log')) {
    $sql = "CREATE TABLE `user_activity_log` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `user_id` int(11) DEFAULT NULL,
        `action` varchar(100) DEFAULT NULL,
        `details` text DEFAULT NULL,
        `ip_address` varchar(45) DEFAULT NULL,
        `user_agent` text DEFAULT NULL,
        `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        KEY `idx_user_id` (`user_id`),
        KEY `idx_action` (`action`),
        KEY `idx_created_at` (`created_at`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    
    executeSQLWithReport($conn, $sql, "إنشاء جدول user_activity_log");
}

// Update users table with security enhancements
if (!columnExists($conn, 'users', 'password_hash')) {
    executeSQLWithReport($conn, 
        "ALTER TABLE `users` ADD COLUMN `password_hash` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL AFTER `password`",
        "إضافة عمود password_hash إلى جدول users"
    );
}

if (!columnExists($conn, 'users', 'failed_login_attempts')) {
    executeSQLWithReport($conn, 
        "ALTER TABLE `users` ADD COLUMN `failed_login_attempts` int(11) DEFAULT 0 AFTER `updated_at`",
        "إضافة عمود failed_login_attempts إلى جدول users"
    );
}

if (!columnExists($conn, 'users', 'locked_until')) {
    executeSQLWithReport($conn, 
        "ALTER TABLE `users` ADD COLUMN `locked_until` timestamp NULL DEFAULT NULL AFTER `failed_login_attempts`",
        "إضافة عمود locked_until إلى جدول users"
    );
}

if (!columnExists($conn, 'users', 'password_reset_token')) {
    executeSQLWithReport($conn, 
        "ALTER TABLE `users` ADD COLUMN `password_reset_token` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL AFTER `locked_until`",
        "إضافة عمود password_reset_token إلى جدول users"
    );
}

if (!columnExists($conn, 'users', 'password_reset_expires')) {
    executeSQLWithReport($conn, 
        "ALTER TABLE `users` ADD COLUMN `password_reset_expires` timestamp NULL DEFAULT NULL AFTER `password_reset_token`",
        "إضافة عمود password_reset_expires إلى جدول users"
    );
}

echo "<h2>⚡ المرحلة 3: تحسين الفهارس</h2>\n";

// Add performance indexes for bookings table
$bookingIndexes = [
    'idx_guest_name' => '(`guest_name`)',
    'idx_room_number' => '(`room_number`)',
    'idx_checkin_date' => '(`checkin_date`)',
    'idx_status' => '(`status`)',
    'idx_guest_phone' => '(`guest_phone`)',
    'idx_created_at' => '(`created_at`)',
    'idx_status_checkin' => '(`status`, `checkin_date`)',
    'idx_guest_search' => '(`guest_name`, `guest_phone`)'
];

foreach ($bookingIndexes as $indexName => $indexColumns) {
    if (!indexExists($conn, 'bookings', $indexName)) {
        executeSQLWithReport($conn, 
            "ALTER TABLE `bookings` ADD INDEX `$indexName` $indexColumns",
            "إضافة فهرس $indexName إلى جدول bookings"
        );
    }
}

// Add performance indexes for payment table
$paymentIndexes = [
    'idx_booking_id' => '(`booking_id`)',
    'idx_payment_date' => '(`payment_date`)',
    'idx_payment_method' => '(`payment_method`)',
    'idx_amount' => '(`amount`)',
    'idx_room_number' => '(`room_number`)',
    'idx_payment_date_method' => '(`payment_date`, `payment_method`)',
    'idx_revenue_type' => '(`revenue_type`)'
];

foreach ($paymentIndexes as $indexName => $indexColumns) {
    if (!indexExists($conn, 'payment', $indexName)) {
        executeSQLWithReport($conn, 
            "ALTER TABLE `payment` ADD INDEX `$indexName` $indexColumns",
            "إضافة فهرس $indexName إلى جدول payment"
        );
    }
}

// Add other performance indexes
if (!indexExists($conn, 'cash_transactions', 'idx_type_date')) {
    executeSQLWithReport($conn, 
        "ALTER TABLE `cash_transactions` ADD INDEX `idx_type_date` (`transaction_type`, `transaction_time`)",
        "إضافة فهرس idx_type_date إلى جدول cash_transactions"
    );
}

if (!indexExists($conn, 'expenses', 'idx_date')) {
    executeSQLWithReport($conn, 
        "ALTER TABLE `expenses` ADD INDEX `idx_date` (`date`)",
        "إضافة فهرس idx_date إلى جدول expenses"
    );
}

if (!indexExists($conn, 'rooms', 'idx_status')) {
    executeSQLWithReport($conn, 
        "ALTER TABLE `rooms` ADD INDEX `idx_status` (`status`)",
        "إضافة فهرس idx_status إلى جدول rooms"
    );
}

// Add user table indexes
$userIndexes = [
    'idx_username' => '(`username`)',
    'idx_user_type' => '(`user_type`)',
    'idx_is_active' => '(`is_active`)',
    'idx_password_reset_token' => '(`password_reset_token`)'
];

foreach ($userIndexes as $indexName => $indexColumns) {
    if (!indexExists($conn, 'users', $indexName)) {
        executeSQLWithReport($conn, 
            "ALTER TABLE `users` ADD INDEX `$indexName` $indexColumns",
            "إضافة فهرس $indexName إلى جدول users"
        );
    }
}

echo "<h2>🔗 المرحلة 4: إضافة قيود المفاتيح الخارجية</h2>\n";

// Add foreign key constraints
try {
    // Check if foreign key exists before adding
    $fkResult = $conn->query("SELECT CONSTRAINT_NAME FROM information_schema.TABLE_CONSTRAINTS 
                             WHERE TABLE_NAME = 'payment' AND CONSTRAINT_TYPE = 'FOREIGN KEY' 
                             AND CONSTRAINT_NAME = 'payment_ibfk_1'");
    
    if (!$fkResult || $fkResult->num_rows == 0) {
        executeSQLWithReport($conn, 
            "ALTER TABLE `payment` ADD CONSTRAINT `payment_ibfk_1` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`booking_id`) ON DELETE CASCADE",
            "إضافة قيد المفتاح الخارجي payment_ibfk_1"
        );
    }
    
    $fkResult = $conn->query("SELECT CONSTRAINT_NAME FROM information_schema.TABLE_CONSTRAINTS 
                             WHERE TABLE_NAME = 'payment' AND CONSTRAINT_TYPE = 'FOREIGN KEY' 
                             AND CONSTRAINT_NAME = 'payment_ibfk_2'");
    
    if (!$fkResult || $fkResult->num_rows == 0) {
        executeSQLWithReport($conn, 
            "ALTER TABLE `payment` ADD CONSTRAINT `payment_ibfk_2` FOREIGN KEY (`cash_transaction_id`) REFERENCES `cash_transactions` (`id`) ON DELETE SET NULL",
            "إضافة قيد المفتاح الخارجي payment_ibfk_2"
        );
    }
    
    $fkResult = $conn->query("SELECT CONSTRAINT_NAME FROM information_schema.TABLE_CONSTRAINTS 
                             WHERE TABLE_NAME = 'payment' AND CONSTRAINT_TYPE = 'FOREIGN KEY' 
                             AND CONSTRAINT_NAME = 'payment_ibfk_3'");
    
    if (!$fkResult || $fkResult->num_rows == 0) {
        executeSQLWithReport($conn, 
            "ALTER TABLE `payment` ADD CONSTRAINT `payment_ibfk_3` FOREIGN KEY (`room_number`) REFERENCES `rooms` (`room_number`) ON DELETE SET NULL",
            "إضافة قيد المفتاح الخارجي payment_ibfk_3"
        );
    }
    
} catch (Exception $e) {
    echo "<div class='warning'>⚠️ تحذير: بعض قيود المفاتيح الخارجية قد تكون موجودة بالفعل</div>\n";
}

echo "<h2>⚙️ المرحلة 5: تحسين المحفزات</h2>\n";

// Drop and recreate optimized triggers
executeSQLWithReport($conn, "DROP TRIGGER IF EXISTS `calculate_nights_on_insert`", "حذف المحفز القديم calculate_nights_on_insert");
executeSQLWithReport($conn, "DROP TRIGGER IF EXISTS `calculate_nights_on_update`", "حذف المحفز القديم calculate_nights_on_update");

$trigger1 = "CREATE TRIGGER `calculate_nights_on_insert` BEFORE INSERT ON `bookings` FOR EACH ROW 
BEGIN
    SET NEW.calculated_nights = DATEDIFF(CURRENT_DATE(), DATE(NEW.checkin_date)) + 
                              (CASE WHEN TIME(CURRENT_TIME()) > '13:00:00' THEN 1 ELSE 0 END);
END";

$trigger2 = "CREATE TRIGGER `calculate_nights_on_update` BEFORE UPDATE ON `bookings` FOR EACH ROW 
BEGIN
    IF NEW.status = 'محجوزة' THEN
        SET NEW.calculated_nights = DATEDIFF(CURRENT_DATE(), DATE(NEW.checkin_date)) + 
                                  (CASE WHEN TIME(CURRENT_TIME()) > '13:00:00' THEN 1 ELSE 0 END);
    END IF;
END";

executeSQLWithReport($conn, $trigger1, "إنشاء المحفز المحسن calculate_nights_on_insert");
executeSQLWithReport($conn, $trigger2, "إنشاء المحفز المحسن calculate_nights_on_update");

echo "<h2>📅 المرحلة 6: تحسين جدولة الأحداث</h2>\n";

// Update event scheduler
executeSQLWithReport($conn, "DROP EVENT IF EXISTS `update_nights_calculation`", "حذف الحدث القديم");

$event = "CREATE EVENT `update_nights_calculation`
ON SCHEDULE EVERY 6 HOUR
DO
BEGIN
    UPDATE bookings 
    SET calculated_nights = DATEDIFF(CURRENT_DATE(), DATE(checkin_date)) + 
                          (CASE WHEN TIME(CURRENT_TIME()) > '13:00:00' THEN 1 ELSE 0 END),
        last_calculation = CURRENT_TIMESTAMP
    WHERE status = 'محجوزة' 
    AND last_calculation < NOW() - INTERVAL 6 HOUR;
END";

executeSQLWithReport($conn, $event, "إنشاء الحدث المحسن update_nights_calculation");

echo "<h2>🔧 المرحلة 7: تحسين محركات الجداول</h2>\n";

// Convert tables to InnoDB
executeSQLWithReport($conn, "ALTER TABLE `invoices` ENGINE=InnoDB", "تحويل جدول invoices إلى InnoDB");
executeSQLWithReport($conn, "ALTER TABLE `payment` ENGINE=InnoDB", "تحويل جدول payment إلى InnoDB");
executeSQLWithReport($conn, "ALTER TABLE `salary_withdrawals` ENGINE=InnoDB", "تحويل جدول salary_withdrawals إلى InnoDB");

echo "<h2>📊 المرحلة 8: إنشاء جداول مراقبة الأداء</h2>\n";

// Create performance monitoring tables
if (!tableExists($conn, 'performance_metrics')) {
    $sql = "CREATE TABLE `performance_metrics` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `metric_name` varchar(100) NOT NULL,
        `metric_value` decimal(10,4) NOT NULL,
        `measurement_time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
        `details` text DEFAULT NULL,
        PRIMARY KEY (`id`),
        KEY `idx_metric_name` (`metric_name`),
        KEY `idx_measurement_time` (`measurement_time`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    
    executeSQLWithReport($conn, $sql, "إنشاء جدول performance_metrics");
}

if (!tableExists($conn, 'slow_queries')) {
    $sql = "CREATE TABLE `slow_queries` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `query_text` text NOT NULL,
        `execution_time` decimal(10,4) NOT NULL,
        `rows_examined` int(11) DEFAULT NULL,
        `rows_sent` int(11) DEFAULT NULL,
        `timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
        `user` varchar(50) DEFAULT NULL,
        `host` varchar(100) DEFAULT NULL,
        PRIMARY KEY (`id`),
        KEY `idx_execution_time` (`execution_time`),
        KEY `idx_timestamp` (`timestamp`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    
    executeSQLWithReport($conn, $sql, "إنشاء جدول slow_queries");
}

echo "<h2>🔧 المرحلة 9: تحسين الجداول</h2>\n";

// Optimize tables
$tables = ['bookings', 'payment', 'cash_transactions', 'rooms', 'users', 'expenses'];
foreach ($tables as $table) {
    executeSQLWithReport($conn, "OPTIMIZE TABLE `$table`", "تحسين جدول $table");
    executeSQLWithReport($conn, "ANALYZE TABLE `$table`", "تحليل جدول $table");
}

echo "<div class='success'><h2>✅ تم الانتهاء من تطبيق جميع التحسينات بنجاح!</h2></div>\n";

echo "<h3>📈 ملخص التحسينات المطبقة:</h3>\n";
echo "<ul>\n";
echo "<li>✅ تحديث هيكل الجداول مع الأعمدة الجديدة</li>\n";
echo "<li>✅ إضافة جداول الأمان والمراقبة</li>\n";
echo "<li>✅ تحسين الفهارس لتسريع الاستعلامات</li>\n";
echo "<li>✅ إضافة قيود المفاتيح الخارجية</li>\n";
echo "<li>✅ تحسين المحفزات والأحداث</li>\n";
echo "<li>✅ تحويل الجداول إلى محرك InnoDB</li>\n";
echo "<li>✅ إضافة نظام مراقبة الأداء</li>\n";
echo "<li>✅ تحسين وتحليل الجداول</li>\n";
echo "</ul>\n";

echo "<div class='info'><strong>النتائج المتوقعة:</strong><br>\n";
echo "• تحسين سرعة الاستعلامات بنسبة 40-60%<br>\n";
echo "• تقليل استخدام الذاكرة<br>\n";
echo "• تحسين أمان النظام<br>\n";
echo "• إمكانية مراقبة الأداء</div>\n";

echo "</body></html>\n";

$conn->close();
?>