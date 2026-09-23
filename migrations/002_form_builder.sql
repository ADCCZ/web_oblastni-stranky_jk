-- =====================================================
-- Form Builder & Registration System
-- Version: 2.0.0
-- =====================================================

-- Sablony formularu (znovupouzitelne sady poli)
CREATE TABLE IF NOT EXISTS form_templates (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    description VARCHAR(500) NULL,
    created_by INT UNSIGNED NOT NULL,
    is_active BOOLEAN NOT NULL DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_active (is_active),
    INDEX idx_created_by (created_by)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Pole formulare
CREATE TABLE IF NOT EXISTS form_fields (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    template_id INT UNSIGNED NOT NULL,
    field_type ENUM('text', 'textarea', 'number', 'select', 'radio', 'checkbox', 'date', 'info', 'image', 'youtube') NOT NULL,
    label VARCHAR(255) NOT NULL,
    placeholder VARCHAR(255) NULL,
    is_required BOOLEAN NOT NULL DEFAULT FALSE,
    options JSON NULL,
    conditions JSON NULL,
    sort_order INT UNSIGNED NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (template_id) REFERENCES form_templates(id) ON DELETE CASCADE,
    INDEX idx_template_order (template_id, sort_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Odpovedi na registracni formular
CREATE TABLE IF NOT EXISTS registration_responses (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    registration_id INT UNSIGNED NOT NULL,
    field_id INT UNSIGNED NOT NULL,
    value TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (registration_id) REFERENCES registrations(id) ON DELETE CASCADE,
    FOREIGN KEY (field_id) REFERENCES form_fields(id) ON DELETE CASCADE,
    UNIQUE KEY unique_response (registration_id, field_id),
    INDEX idx_registration (registration_id),
    INDEX idx_field (field_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Rozsireni tabulky events o typ registrace a vazbu na sablonu
ALTER TABLE events
    ADD COLUMN registration_type ENUM('none', 'external', 'internal') NOT NULL DEFAULT 'none' AFTER form_button_text,
    ADD COLUMN form_template_id INT UNSIGNED NULL AFTER registration_type,
    ADD CONSTRAINT fk_event_form_template FOREIGN KEY (form_template_id) REFERENCES form_templates(id) ON DELETE SET NULL;

-- Migrace dat: existujici eventy s form_url dostanou registration_type = 'external'
UPDATE events SET registration_type = 'external' WHERE form_url IS NOT NULL AND form_url != '';

-- Zakladni udaje primo v registraci (editovatelne, predvyplnene z profilu)
ALTER TABLE registrations
    ADD COLUMN first_name VARCHAR(100) NULL AFTER user_id,
    ADD COLUMN last_name VARCHAR(100) NULL AFTER first_name,
    ADD COLUMN email VARCHAR(255) NULL AFTER last_name,
    ADD COLUMN phone VARCHAR(20) NULL AFTER email;

-- Migrace dat: vyplnit z users tabulky pro existujici registrace
UPDATE registrations r JOIN users u ON r.user_id = u.id
SET r.first_name = u.first_name, r.last_name = u.last_name, r.email = u.email, r.phone = u.phone
WHERE r.first_name IS NULL;
