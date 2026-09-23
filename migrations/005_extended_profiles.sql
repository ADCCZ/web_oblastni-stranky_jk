-- =====================================================
-- Extended Profiles & Children System
-- Version: 5.0.0
-- =====================================================

-- Rozsireni tabulky users o osobni udaje, adresu a zdravotni informace
ALTER TABLE users
    ADD COLUMN street VARCHAR(200) NULL AFTER avatar_path,
    ADD COLUMN city VARCHAR(100) NULL AFTER street,
    ADD COLUMN zip VARCHAR(10) NULL AFTER city,
    ADD COLUMN birth_date DATE NULL AFTER zip,
    ADD COLUMN is_pathfinder_member BOOLEAN NOT NULL DEFAULT FALSE AFTER birth_date,
    ADD COLUMN is_vegetarian BOOLEAN NOT NULL DEFAULT FALSE AFTER is_pathfinder_member,
    ADD COLUMN dietary_notes TEXT NULL AFTER is_vegetarian,
    ADD COLUMN allergies TEXT NULL AFTER dietary_notes,
    ADD COLUMN health_notes TEXT NULL AFTER allergies,
    ADD COLUMN medications TEXT NULL AFTER health_notes,
    ADD COLUMN can_swim BOOLEAN NULL AFTER medications,
    ADD COLUMN clothing_size VARCHAR(10) NULL AFTER can_swim;

-- Deti (bez vlastniho uctu, vazane na rodice)
CREATE TABLE IF NOT EXISTS children (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    parent_id INT UNSIGNED NOT NULL,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    birth_date DATE NULL,
    phone VARCHAR(20) NULL,
    email VARCHAR(255) NULL,
    -- Adresa
    street VARCHAR(200) NULL,
    city VARCHAR(100) NULL,
    zip VARCHAR(10) NULL,
    -- Osobni
    is_pathfinder_member BOOLEAN NOT NULL DEFAULT FALSE,
    -- Zdravotni / stravovani
    is_vegetarian BOOLEAN NOT NULL DEFAULT FALSE,
    dietary_notes TEXT NULL,
    allergies TEXT NULL,
    health_notes TEXT NULL,
    medications TEXT NULL,
    can_swim BOOLEAN NULL,
    clothing_size VARCHAR(10) NULL,
    -- Meta
    is_active BOOLEAN NOT NULL DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (parent_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_parent (parent_id),
    INDEX idx_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- System key pro profilova pole v sablonach
ALTER TABLE form_fields ADD COLUMN system_key VARCHAR(50) NULL AFTER conditions;

-- Zakladni udaje primo v registraci (ne pres user JOIN)
ALTER TABLE registrations
    ADD COLUMN is_pathfinder_member BOOLEAN NULL AFTER phone,
    ADD COLUMN street VARCHAR(200) NULL AFTER is_pathfinder_member,
    ADD COLUMN city VARCHAR(100) NULL AFTER street,
    ADD COLUMN zip VARCHAR(10) NULL AFTER city,
    ADD COLUMN birth_date DATE NULL AFTER zip;
