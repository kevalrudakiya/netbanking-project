-- ============================================================
--  NETBANKING DATABASE SCHEMA (V2 Optimized)
--  Run this file once to set up the full database from scratch
-- ============================================================

CREATE DATABASE IF NOT EXISTS netbanking
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE netbanking;

-- ============================================================
--  TABLE: users
-- ============================================================
CREATE TABLE IF NOT EXISTS users (
    user_id     INT AUTO_INCREMENT PRIMARY KEY,
    full_name   VARCHAR(100)        NOT NULL,
    email       VARCHAR(150)        NOT NULL UNIQUE,
    phone       VARCHAR(15)         NOT NULL UNIQUE,
    username    VARCHAR(50)         NOT NULL UNIQUE,
    password    VARCHAR(255)        NOT NULL,           -- bcrypt hash
    status      ENUM('active','blocked') DEFAULT 'active',
    last_login  TIMESTAMP           NULL DEFAULT NULL,
    created_at  TIMESTAMP           DEFAULT CURRENT_TIMESTAMP,
    updated_at  TIMESTAMP           DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
--  TABLE: accounts
-- ============================================================
CREATE TABLE IF NOT EXISTS accounts (
    account_id      INT AUTO_INCREMENT PRIMARY KEY,
    user_id         INT             NOT NULL,
    account_number  VARCHAR(20)     NOT NULL UNIQUE,
    balance         DECIMAL(15,2)   NOT NULL DEFAULT 0.00,
    pin             VARCHAR(255)    NOT NULL,           -- bcrypt hash
    status          ENUM('active','frozen','closed') DEFAULT 'active',
    account_type    ENUM('savings','checking','current') DEFAULT 'savings',
    created_at      TIMESTAMP       DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP       DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    INDEX idx_accounts_user_status (user_id, status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
--  TABLE: transactions
-- ============================================================
CREATE TABLE IF NOT EXISTS transactions (
    transaction_id     INT AUTO_INCREMENT PRIMARY KEY,
    account_id         INT             NOT NULL,
    type               ENUM('deposit','withdraw','transfer_in','transfer_out') NOT NULL,
    amount             DECIMAL(15,2)   NOT NULL,
    balance_after      DECIMAL(15,2)   NOT NULL,
    reference_no       VARCHAR(30)     NOT NULL UNIQUE,   -- e.g. TXN20240623XXXX
    note               VARCHAR(255)    DEFAULT NULL,
    related_account_id INT             DEFAULT NULL,
    created_at         TIMESTAMP       DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (account_id) REFERENCES accounts(account_id) ON DELETE CASCADE,
    FOREIGN KEY (related_account_id) REFERENCES accounts(account_id) ON DELETE SET NULL,
    INDEX idx_txn_acc_date (account_id, created_at DESC),
    INDEX idx_txn_acc_type (account_id, type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
--  TABLE: admins
-- ============================================================
CREATE TABLE IF NOT EXISTS admins (
    admin_id    INT AUTO_INCREMENT PRIMARY KEY,
    username    VARCHAR(50)     NOT NULL UNIQUE,
    password    VARCHAR(255)    NOT NULL,              -- bcrypt hash
    full_name   VARCHAR(100)    NOT NULL,
    role        ENUM('superadmin','admin') DEFAULT 'admin',
    last_login  TIMESTAMP       NULL DEFAULT NULL,
    created_at  TIMESTAMP       DEFAULT CURRENT_TIMESTAMP,
    updated_at  TIMESTAMP       DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
--  TABLE: login_attempts  (brute-force protection)
-- ============================================================
CREATE TABLE IF NOT EXISTS login_attempts (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    username     VARCHAR(50)     NOT NULL,
    ip_address   VARCHAR(45)     NOT NULL,
    attempted_at TIMESTAMP       DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_login_attempt_lookup (username, ip_address, attempted_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
--  TABLE: password_reset_requests
-- ============================================================
CREATE TABLE IF NOT EXISTS password_reset_requests (
    request_id      INT AUTO_INCREMENT PRIMARY KEY,
    user_id         INT             NOT NULL,
    account_number  VARCHAR(20)     NOT NULL,
    phone           VARCHAR(15)     NOT NULL,
    status          ENUM('pending','approved','rejected') DEFAULT 'pending',
    processed_by    INT             DEFAULT NULL,
    processed_at    TIMESTAMP       NULL DEFAULT NULL,
    created_at      TIMESTAMP       DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (processed_by) REFERENCES admins(admin_id) ON DELETE SET NULL,
    INDEX idx_reset_user_status (user_id, status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
--  TABLE: audit_logs (Security & Admin Activity Tracking)
-- ============================================================
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

-- ============================================================
--  TABLE: beneficiaries (Saved Transfer Recipients)
-- ============================================================
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

-- ============================================================
--  TABLE: notifications (User Alert System)
-- ============================================================
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

-- ============================================================
--  DEFAULT SUPER ADMIN  (password: Admin@123)
--  Change the password immediately after first login!
-- ============================================================
INSERT IGNORE INTO admins (username, password, full_name, role)
VALUES (
    'superadmin',
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', -- Admin@123
    'Super Admin',
    'superadmin'
);
