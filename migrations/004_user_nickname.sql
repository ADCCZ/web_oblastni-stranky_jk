-- =====================================================
-- User Nickname Column
-- Version: 1.0.0
-- =====================================================

-- Pridani sloupce pro prezdivku
ALTER TABLE users
    ADD COLUMN nickname VARCHAR(50) NULL AFTER last_name;

-- =====================================================
-- HOTOVO
-- =====================================================
