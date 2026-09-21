-- ============================================================
-- NETBANKING DATABASE MIGRATION V2 (Idempotent)
-- Professional Optimizations & Additive Enhancements
-- 100% Backward Compatible with Existing Codebase
-- ============================================================

USE netbanking;

-- ------------------------------------------------------------
-- 1. PERFORMANCE INDEXES
-- ------------------------------------------------------------

-- Indexes on transactions
SET @dbname = DATABASE();
SET @tablename = "transactions";
SET @indexname = "idx_txn_acc_date";
SET @cmd = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS 
     WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND INDEX_NAME = @indexname) > 0,
    "SELECT 1",
    "ALTER TABLE transactions ADD INDEX idx_txn_acc_date (account_id, created_at DESC);"
));
PREPARE stmt FROM @cmd; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @indexname = "idx_txn_acc_type";
SET @cmd = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS 
     WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND INDEX_NAME = @indexname) > 0,
    "SELECT 1",
    "ALTER TABLE transactions ADD INDEX idx_txn_acc_type (account_id, type);"
));
PREPARE stmt FROM @cmd; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Index on login_attempts
SET @tablename = "login_attempts";
SET @indexname = "idx_login_attempt_lookup";
SET @cmd = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS 
     WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND INDEX_NAME = @indexname) > 0,
    "SELECT 1",
    "ALTER TABLE login_attempts ADD INDEX idx_login_attempt_lookup (username, ip_address, attempted_at);"
));
PREPARE stmt FROM @cmd; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Index on password_reset_requests
SET @tablename = "password_reset_requests";
SET @indexname = "idx_reset_user_status";
SET @cmd = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS 
     WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND INDEX_NAME = @indexname) > 0,
    "SELECT 1",
    "ALTER TABLE password_reset_requests ADD INDEX idx_reset_user_status (user_id, status);"
));
PREPARE stmt FROM @cmd; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Index on accounts
SET @tablename = "accounts";
SET @indexname = "idx_accounts_user_status";
SET @cmd = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS 
     WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND INDEX_NAME = @indexname) > 0,
    "SELECT 1",
    "ALTER TABLE accounts ADD INDEX idx_accounts_user_status (user_id, status);"
));
PREPARE stmt FROM @cmd; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- ------------------------------------------------------------
-- 2. ENHANCE EXISTING TABLES (ADDITIVE & COMPATIBLE)
-- ------------------------------------------------------------

-- Add audit timestamps & last login to users
SET @tablename = "users";
SET @colname = "last_login";
SET @cmd = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
     WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND COLUMN_NAME = @colname) > 0,
    "SELECT 1",
    "ALTER TABLE users ADD COLUMN last_login TIMESTAMP NULL AFTER status;"
));
PREPARE stmt FROM @cmd; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @colname = "updated_at";
SET @cmd = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
     WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND COLUMN_NAME = @colname) > 0,
    "SELECT 1",
    "ALTER TABLE users ADD COLUMN updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER created_at;"
));
PREPARE stmt FROM @cmd; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Add account type and timestamp to accounts
SET @tablename = "accounts";
SET @colname = "account_type";
SET @cmd = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
     WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND COLUMN_NAME = @colname) > 0,
    "SELECT 1",
    "ALTER TABLE accounts ADD COLUMN account_type ENUM('savings', 'checking', 'current') DEFAULT 'savings' AFTER status;"
));
PREPARE stmt FROM @cmd; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @colname = "updated_at";
SET @cmd = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
     WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND COLUMN_NAME = @colname) > 0,
    "SELECT 1",
    "ALTER TABLE accounts ADD COLUMN updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER created_at;"
));
PREPARE stmt FROM @cmd; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Link counter-party accounts for transactions
SET @tablename = "transactions";
SET @colname = "related_account_id";
SET @cmd = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
     WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND COLUMN_NAME = @colname) > 0,
    "SELECT 1",
    "ALTER TABLE transactions ADD COLUMN related_account_id INT NULL AFTER note, ADD CONSTRAINT fk_txn_related_account FOREIGN KEY (related_account_id) REFERENCES accounts(account_id) ON DELETE SET NULL;"
));
PREPARE stmt FROM @cmd; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Add audit fields to admins
SET @tablename = "admins";
SET @colname = "last_login";
SET @cmd = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
     WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND COLUMN_NAME = @colname) > 0,
    "SELECT 1",
    "ALTER TABLE admins ADD COLUMN last_login TIMESTAMP NULL AFTER role;"
));
PREPARE stmt FROM @cmd; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @colname = "updated_at";
SET @cmd = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
     WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND COLUMN_NAME = @colname) > 0,
    "SELECT 1",
    "ALTER TABLE admins ADD COLUMN updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER created_at;"
));
PREPARE stmt FROM @cmd; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- ------------------------------------------------------------
-- 3. NEW TABLES
-- ------------------------------------------------------------

-- Table: audit_logs (Security & Admin Activity Tracking)
CREATE TABLE IF NOT EXISTS audit_logs (
    log_id          INT AUTO_INCREMENT PRIMARY KEY,
    actor_type      ENUM('user', 'admin', 'system') NOT NULL,
    actor_id        INT DEFAULT NULL,
    action          VARCHAR(100) NOT NULL,
    details         TEXT DEFAULT NULL,
    ip_address      VARCHAR(45) NOT NULL,
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_audit_actor (actor_type, actor_id),
    INDEX idx_audit_action (action),
    INDEX idx_audit_created (created_at DESC)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: beneficiaries (Saved Transfer Recipients)
CREATE TABLE IF NOT EXISTS beneficiaries (
    beneficiary_id   INT AUTO_INCREMENT PRIMARY KEY,
    user_id          INT NOT NULL,
    account_number   VARCHAR(20) NOT NULL,
    beneficiary_name VARCHAR(100) NOT NULL,
    nickname         VARCHAR(50) DEFAULT NULL,
    created_at       TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    UNIQUE KEY uk_user_beneficiary (user_id, account_number),
    INDEX idx_beneficiary_user (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: notifications (User Alert System)
CREATE TABLE IF NOT EXISTS notifications (
    notification_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id         INT NOT NULL,
    title           VARCHAR(150) NOT NULL,
    message         TEXT NOT NULL,
    type            ENUM('info', 'success', 'warning', 'danger') DEFAULT 'info',
    is_read         TINYINT(1) DEFAULT 0,
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    INDEX idx_notif_user_read (user_id, is_read, created_at DESC)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
