-- Database Performance Optimization Script for Marina Hotel
-- This script implements the optimizations discussed in the conversation
-- Run this script to upgrade the existing database structure

-- ========================================
-- PHASE 1: DATABASE STRUCTURE IMPROVEMENTS
-- ========================================

-- Update the database schema to match the optimized version from the conversation
-- First, let's update the existing tables with the new structure

-- Update bookings table structure
ALTER TABLE `bookings` 
ADD COLUMN IF NOT EXISTS `guest_created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP AFTER `guest_address`,
ADD COLUMN IF NOT EXISTS `expected_nights` int(11) DEFAULT 1 COMMENT 'عدد الليالي المتوقع' AFTER `notes`,
ADD COLUMN IF NOT EXISTS `actual_checkout` datetime DEFAULT NULL AFTER `expected_nights`,
ADD COLUMN IF NOT EXISTS `calculated_nights` int(11) DEFAULT 1 AFTER `actual_checkout`,
ADD COLUMN IF NOT EXISTS `last_calculation` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER `calculated_nights`;

-- Update payment table structure to match the optimized schema
ALTER TABLE `payment` 
MODIFY COLUMN `amount` int(11) NOT NULL COMMENT 'المبلغ بالريال اليمني بدون كسور',
ADD COLUMN IF NOT EXISTS `room_number` varchar(10) DEFAULT NULL AFTER `cash_transaction_id`;

-- Update cash_transactions table
ALTER TABLE `cash_transactions`
MODIFY COLUMN `amount` int(11) NOT NULL COMMENT 'المبلغ بالريال اليمني بدون كسور',
MODIFY COLUMN `reference_id` int(11) DEFAULT NULL;

-- Add missing tables from the optimized schema
CREATE TABLE IF NOT EXISTS `failed_logins` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `attempt_time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_username` (`username`),
  KEY `idx_ip_address` (`ip_address`),
  KEY `idx_attempt_time` (`attempt_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `user_activity_log` (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Update users table with security enhancements
ALTER TABLE `users`
ADD COLUMN IF NOT EXISTS `password_hash` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL AFTER `password`,
ADD COLUMN IF NOT EXISTS `failed_login_attempts` int(11) DEFAULT 0 AFTER `updated_at`,
ADD COLUMN IF NOT EXISTS `locked_until` timestamp NULL DEFAULT NULL AFTER `failed_login_attempts`,
ADD COLUMN IF NOT EXISTS `password_reset_token` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL AFTER `locked_until`,
ADD COLUMN IF NOT EXISTS `password_reset_expires` timestamp NULL DEFAULT NULL AFTER `password_reset_token`;

-- Add salary withdrawals table enhancements
ALTER TABLE `salary_withdrawals`
ADD COLUMN IF NOT EXISTS `withdrawal_type` varchar(50) DEFAULT 'cash' AFTER `created_at`;

-- ========================================
-- PHASE 2: INDEX OPTIMIZATIONS
-- ========================================

-- Add performance indexes for bookings table
ALTER TABLE `bookings`
ADD INDEX IF NOT EXISTS `idx_guest_name` (`guest_name`),
ADD INDEX IF NOT EXISTS `idx_room_number` (`room_number`),
ADD INDEX IF NOT EXISTS `idx_checkin_date` (`checkin_date`),
ADD INDEX IF NOT EXISTS `idx_status` (`status`),
ADD INDEX IF NOT EXISTS `idx_guest_phone` (`guest_phone`),
ADD INDEX IF NOT EXISTS `idx_created_at` (`created_at`),
ADD INDEX IF NOT EXISTS `idx_status_checkin` (`status`, `checkin_date`),
ADD INDEX IF NOT EXISTS `idx_guest_search` (`guest_name`, `guest_phone`);

-- Add performance indexes for payment table
ALTER TABLE `payment`
ADD INDEX IF NOT EXISTS `idx_booking_id` (`booking_id`),
ADD INDEX IF NOT EXISTS `idx_payment_date` (`payment_date`),
ADD INDEX IF NOT EXISTS `idx_payment_method` (`payment_method`),
ADD INDEX IF NOT EXISTS `idx_amount` (`amount`),
ADD INDEX IF NOT EXISTS `idx_room_number` (`room_number`),
ADD INDEX IF NOT EXISTS `idx_payment_date_method` (`payment_date`, `payment_method`),
ADD INDEX IF NOT EXISTS `idx_revenue_type` (`revenue_type`);

-- Add performance indexes for cash_transactions table
ALTER TABLE `cash_transactions`
ADD INDEX IF NOT EXISTS `idx_type_date` (`transaction_type`, `transaction_time`);

-- Add performance indexes for expenses table
ALTER TABLE `expenses`
ADD INDEX IF NOT EXISTS `idx_date` (`date`);

-- Add performance indexes for rooms table
ALTER TABLE `rooms`
ADD INDEX IF NOT EXISTS `idx_status` (`status`);

-- Add performance indexes for users table
ALTER TABLE `users`
ADD INDEX IF NOT EXISTS `idx_username` (`username`),
ADD INDEX IF NOT EXISTS `idx_user_type` (`user_type`),
ADD INDEX IF NOT EXISTS `idx_is_active` (`is_active`),
ADD INDEX IF NOT EXISTS `idx_password_reset_token` (`password_reset_token`);

-- ========================================
-- PHASE 3: FOREIGN KEY CONSTRAINTS
-- ========================================

-- Add proper foreign key constraints
ALTER TABLE `payment`
ADD CONSTRAINT IF NOT EXISTS `payment_ibfk_1` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`booking_id`) ON DELETE CASCADE,
ADD CONSTRAINT IF NOT EXISTS `payment_ibfk_2` FOREIGN KEY (`cash_transaction_id`) REFERENCES `cash_transactions` (`id`) ON DELETE SET NULL,
ADD CONSTRAINT IF NOT EXISTS `payment_ibfk_3` FOREIGN KEY (`room_number`) REFERENCES `rooms` (`room_number`) ON DELETE SET NULL;

-- Update cash_transactions foreign key
ALTER TABLE `cash_transactions`
ADD CONSTRAINT IF NOT EXISTS `cash_transactions_ibfk_2` FOREIGN KEY (`reference_id`) REFERENCES `payment` (`payment_id`) ON DELETE SET NULL;

-- ========================================
-- PHASE 4: TRIGGER OPTIMIZATIONS
-- ========================================

-- Drop existing triggers to recreate them with optimizations
DROP TRIGGER IF EXISTS `calculate_nights_on_insert`;
DROP TRIGGER IF EXISTS `calculate_nights_on_update`;

-- Create optimized triggers
DELIMITER $$
CREATE TRIGGER `calculate_nights_on_insert` BEFORE INSERT ON `bookings` FOR EACH ROW 
BEGIN
    SET NEW.calculated_nights = DATEDIFF(CURRENT_DATE(), DATE(NEW.checkin_date)) + 
                              (CASE WHEN TIME(CURRENT_TIME()) > '13:00:00' THEN 1 ELSE 0 END);
END$$

CREATE TRIGGER `calculate_nights_on_update` BEFORE UPDATE ON `bookings` FOR EACH ROW 
BEGIN
    IF NEW.status = 'محجوزة' THEN
        SET NEW.calculated_nights = DATEDIFF(CURRENT_DATE(), DATE(NEW.checkin_date)) + 
                                  (CASE WHEN TIME(CURRENT_TIME()) > '13:00:00' THEN 1 ELSE 0 END);
    END IF;
END$$

-- Add expense logging trigger
CREATE TRIGGER IF NOT EXISTS `after_expense_insert` AFTER INSERT ON `expenses` FOR EACH ROW
BEGIN
    INSERT INTO expense_logs (expense_id, action, details, created_at)
    VALUES (NEW.id, 'create', CONCAT('تم إضافة مصروف: ', NEW.description), NOW());
END$$

CREATE TRIGGER IF NOT EXISTS `after_expense_update` AFTER UPDATE ON `expenses` FOR EACH ROW
BEGIN
    INSERT INTO expense_logs (expense_id, action, details, created_at)
    VALUES (NEW.id, 'update', CONCAT('تم تعديل المصروف: النوع=', NEW.expense_type, ', المبلغ=', NEW.amount), NOW());
END$$

-- Add salary withdrawal logging trigger
CREATE TRIGGER IF NOT EXISTS `after_salary_withdrawal` AFTER INSERT ON `salary_withdrawals` FOR EACH ROW
BEGIN
    INSERT INTO expense_logs (expense_id, action, details, created_at)
    VALUES (NEW.id, 'salary_withdrawal', CONCAT('تم سحب راتب للموظف ID: ', NEW.employee_id, ' - المبلغ: ', NEW.amount), NOW());
END$$

DELIMITER ;

-- ========================================
-- PHASE 5: EVENT SCHEDULER OPTIMIZATION
-- ========================================

-- Drop existing event and create optimized version
DROP EVENT IF EXISTS `update_nights_calculation`;

DELIMITER $$
CREATE EVENT `update_nights_calculation`
ON SCHEDULE EVERY 6 HOUR
DO
BEGIN
    UPDATE bookings 
    SET calculated_nights = DATEDIFF(CURRENT_DATE(), DATE(checkin_date)) + 
                          (CASE WHEN TIME(CURRENT_TIME()) > '13:00:00' THEN 1 ELSE 0 END),
        last_calculation = CURRENT_TIMESTAMP
    WHERE status = 'محجوزة' 
    AND last_calculation < NOW() - INTERVAL 6 HOUR;
END$$
DELIMITER ;

-- ========================================
-- PHASE 6: TABLE ENGINE OPTIMIZATIONS
-- ========================================

-- Convert MyISAM tables to InnoDB for better performance and ACID compliance
ALTER TABLE `invoices` ENGINE=InnoDB;
ALTER TABLE `payment` ENGINE=InnoDB;
ALTER TABLE `salary_withdrawals` ENGINE=InnoDB;

-- ========================================
-- PHASE 7: PERFORMANCE MONITORING SETUP
-- ========================================

-- Create performance monitoring table
CREATE TABLE IF NOT EXISTS `performance_metrics` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `metric_name` varchar(100) NOT NULL,
  `metric_value` decimal(10,4) NOT NULL,
  `measurement_time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `details` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_metric_name` (`metric_name`),
  KEY `idx_measurement_time` (`measurement_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create slow query log table
CREATE TABLE IF NOT EXISTS `slow_queries` (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ========================================
-- PHASE 8: DATA CLEANUP AND OPTIMIZATION
-- ========================================

-- Optimize table structures
OPTIMIZE TABLE `bookings`;
OPTIMIZE TABLE `payment`;
OPTIMIZE TABLE `cash_transactions`;
OPTIMIZE TABLE `rooms`;
OPTIMIZE TABLE `users`;
OPTIMIZE TABLE `expenses`;

-- Update table statistics
ANALYZE TABLE `bookings`;
ANALYZE TABLE `payment`;
ANALYZE TABLE `cash_transactions`;
ANALYZE TABLE `rooms`;
ANALYZE TABLE `users`;
ANALYZE TABLE `expenses`;

-- ========================================
-- COMPLETION MESSAGE
-- ========================================

SELECT 'Database optimization completed successfully!' as Status;