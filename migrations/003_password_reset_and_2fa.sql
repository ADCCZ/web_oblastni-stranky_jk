-- =====================================================
-- Password Reset & Two-Factor Authentication
-- Version: 1.0.0
-- =====================================================

-- Tabulka pro reset hesla
CREATE TABLE IF NOT EXISTS password_resets (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    code VARCHAR(6) NOT NULL,
    expires_at DATETIME NOT NULL,
    used_at DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_code (user_id, code),
    INDEX idx_expires (expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Pridani 2FA sloupcu do users
ALTER TABLE users
    ADD COLUMN two_factor_type ENUM('none', 'email', 'totp', 'sms') NOT NULL DEFAULT 'none' AFTER is_active,
    ADD COLUMN two_factor_secret VARCHAR(255) NULL AFTER two_factor_type,
    ADD COLUMN two_factor_verified BOOLEAN NOT NULL DEFAULT FALSE AFTER two_factor_secret;

-- Tabulka pro 2FA kody (pro email a SMS)
CREATE TABLE IF NOT EXISTS two_factor_codes (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    code VARCHAR(6) NOT NULL,
    type ENUM('email', 'sms') NOT NULL,
    expires_at DATETIME NOT NULL,
    used_at DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_code (user_id, code),
    INDEX idx_expires (expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- HOTOVO
-- =====================================================
