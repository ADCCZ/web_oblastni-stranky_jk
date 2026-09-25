-- =====================================================
-- Jizni Kriz Pathfinder - Database Schema
-- Consolidated schema (was previously split across 001-008,
-- squashed into a single file once the project outgrew the
-- original XAMPP-era migration history).
-- =====================================================

-- Vytvoreni databaze (spust pouze pokud databaze neexistuje)
-- CREATE DATABASE IF NOT EXISTS pathfinder_jk CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
-- USE pathfinder_jk;

-- =====================================================
-- TABULKY
-- =====================================================

-- Uzivatele
CREATE TABLE IF NOT EXISTS users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NULL,
    google_id VARCHAR(255) NULL UNIQUE,
    facebook_id VARCHAR(255) NULL UNIQUE,
    discord_id VARCHAR(255) NULL UNIQUE,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    nickname VARCHAR(50) NULL,
    role ENUM('admin', 'leader', 'parent', 'member') NOT NULL DEFAULT 'member',
    phone VARCHAR(20) NULL,
    avatar_path VARCHAR(255) NULL,
    newsletter BOOLEAN NOT NULL DEFAULT FALSE,
    street VARCHAR(200) NULL,
    city VARCHAR(100) NULL,
    zip VARCHAR(10) NULL,
    birth_date DATE NULL,
    is_pathfinder_member BOOLEAN NOT NULL DEFAULT FALSE,
    is_vegetarian BOOLEAN NOT NULL DEFAULT FALSE,
    dietary_notes TEXT NULL,
    allergies TEXT NULL,
    health_notes TEXT NULL,
    medications TEXT NULL,
    can_swim BOOLEAN NULL,
    clothing_size VARCHAR(10) NULL,
    is_active BOOLEAN NOT NULL DEFAULT TRUE,
    two_factor_type ENUM('none', 'email', 'totp', 'sms') NOT NULL DEFAULT 'none',
    two_factor_secret VARCHAR(255) NULL,
    two_factor_verified BOOLEAN NOT NULL DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_role (role),
    INDEX idx_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Kategorie akci
CREATE TABLE IF NOT EXISTS event_categories (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    slug VARCHAR(100) NOT NULL UNIQUE,
    color VARCHAR(7) NOT NULL DEFAULT '#0075b5',
    icon VARCHAR(50) NULL,

    INDEX idx_slug (slug)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Sablony registracnich formularu (form builder)
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

-- Jednotliva pole sablony registracniho formulare
CREATE TABLE IF NOT EXISTS form_fields (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    template_id INT UNSIGNED NOT NULL,
    field_type ENUM('text', 'textarea', 'number', 'select', 'radio', 'checkbox', 'date', 'info', 'image', 'youtube') NOT NULL,
    label VARCHAR(255) NOT NULL,
    placeholder VARCHAR(255) NULL,
    is_required BOOLEAN NOT NULL DEFAULT FALSE,
    options JSON NULL,
    conditions JSON NULL,
    system_key VARCHAR(50) NULL,
    sort_order INT UNSIGNED NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (template_id) REFERENCES form_templates(id) ON DELETE CASCADE,

    INDEX idx_template_order (template_id, sort_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Akce
CREATE TABLE IF NOT EXISTS events (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    slug VARCHAR(255) NOT NULL UNIQUE,
    description VARCHAR(500) NULL,
    content TEXT NULL,
    image_path VARCHAR(500) NULL,
    location VARCHAR(255) NULL,
    location_url VARCHAR(500) NULL,
    start_date DATETIME NOT NULL,
    end_date DATETIME NULL,
    registration_to DATETIME NULL,
    capacity INT UNSIGNED NULL,
    price DECIMAL(10, 2) NULL,
    category_id INT UNSIGNED NULL,
    created_by INT UNSIGNED NOT NULL,
    is_published BOOLEAN NOT NULL DEFAULT FALSE,
    availability ENUM('all', 'leaders') NOT NULL DEFAULT 'all',
    form_url VARCHAR(500) NULL,
    form_button_text VARCHAR(100) NULL,
    registration_type ENUM('none', 'external', 'internal') NOT NULL DEFAULT 'none',
    form_template_id INT UNSIGNED NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (category_id) REFERENCES event_categories(id) ON DELETE SET NULL,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (form_template_id) REFERENCES form_templates(id) ON DELETE SET NULL,

    INDEX idx_slug (slug),
    INDEX idx_dates (start_date, end_date),
    INDEX idx_published (is_published),
    INDEX idx_category (category_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Registrace na akce
CREATE TABLE IF NOT EXISTS registrations (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    first_name VARCHAR(100) NULL,
    last_name VARCHAR(100) NULL,
    email VARCHAR(255) NULL,
    phone VARCHAR(20) NULL,
    is_pathfinder_member BOOLEAN NULL,
    street VARCHAR(200) NULL,
    city VARCHAR(100) NULL,
    zip VARCHAR(10) NULL,
    birth_date DATE NULL,
    event_id INT UNSIGNED NOT NULL,
    status ENUM('pending', 'confirmed', 'cancelled', 'waitlist') NOT NULL DEFAULT 'pending',
    note TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE,

    UNIQUE KEY unique_registration (user_id, event_id),
    INDEX idx_status (status),
    INDEX idx_event (event_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Odpovedi na jednotliva pole registracniho formulare
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

-- Deti rodicu (pro registraci deti na akce)
CREATE TABLE IF NOT EXISTS children (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    parent_id INT UNSIGNED NOT NULL,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    birth_date DATE NULL,
    phone VARCHAR(20) NULL,
    email VARCHAR(255) NULL,
    street VARCHAR(200) NULL,
    city VARCHAR(100) NULL,
    zip VARCHAR(10) NULL,
    is_pathfinder_member BOOLEAN NOT NULL DEFAULT FALSE,
    is_vegetarian BOOLEAN NOT NULL DEFAULT FALSE,
    dietary_notes TEXT NULL,
    allergies TEXT NULL,
    health_notes TEXT NULL,
    medications TEXT NULL,
    can_swim BOOLEAN NULL,
    clothing_size VARCHAR(10) NULL,
    is_active BOOLEAN NOT NULL DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (parent_id) REFERENCES users(id) ON DELETE CASCADE,

    INDEX idx_parent (parent_id),
    INDEX idx_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Kody pro reset hesla
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

-- Kody pro dvoufaktorove overeni (email/SMS varianta; TOTP pouziva users.two_factor_secret)
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

-- Novinky / Clanky
CREATE TABLE IF NOT EXISTS news (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    slug VARCHAR(255) NOT NULL UNIQUE,
    perex VARCHAR(500) NULL,
    content TEXT NOT NULL,
    created_by INT UNSIGNED NOT NULL,
    is_published BOOLEAN NOT NULL DEFAULT FALSE,
    published_at DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE,

    INDEX idx_slug (slug),
    INDEX idx_published (is_published, published_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Galerie
CREATE TABLE IF NOT EXISTS galleries (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    slug VARCHAR(255) NOT NULL UNIQUE,
    description TEXT NULL,
    event_id INT UNSIGNED NULL,
    created_by INT UNSIGNED NOT NULL,
    is_published BOOLEAN NOT NULL DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE SET NULL,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE,

    INDEX idx_slug (slug),
    INDEX idx_event (event_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Obrazky v galerii
CREATE TABLE IF NOT EXISTS gallery_images (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    gallery_id INT UNSIGNED NOT NULL,
    filename VARCHAR(255) NOT NULL,
    original_name VARCHAR(255) NOT NULL,
    alt_text VARCHAR(255) NULL,
    sort_order INT UNSIGNED NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (gallery_id) REFERENCES galleries(id) ON DELETE CASCADE,

    INDEX idx_gallery_order (gallery_id, sort_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Staticke stranky
CREATE TABLE IF NOT EXISTS pages (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    slug VARCHAR(255) NOT NULL UNIQUE,
    content TEXT NOT NULL,
    meta_title VARCHAR(255) NULL,
    meta_description VARCHAR(500) NULL,
    is_published BOOLEAN NOT NULL DEFAULT FALSE,
    sort_order INT UNSIGNED NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_slug (slug)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Kontaktni osoby
CREATE TABLE IF NOT EXISTS contacts (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(200) NOT NULL,
    role_title VARCHAR(200) NULL,
    email VARCHAR(255) NULL,
    phone VARCHAR(20) NULL,
    photo_path VARCHAR(255) NULL,
    sort_order INT UNSIGNED NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Audit log pro admin akce
CREATE TABLE IF NOT EXISTS audit_logs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NULL,
    action VARCHAR(50) NOT NULL,
    entity_type VARCHAR(50) NOT NULL,
    entity_id INT UNSIGNED NULL,
    old_values JSON NULL,
    new_values JSON NULL,
    ip_address VARCHAR(45) NULL,
    user_agent VARCHAR(500) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,

    INDEX idx_entity (entity_type, entity_id),
    INDEX idx_user (user_id),
    INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- DOPLNENI CHYBEJICICH SLOUPCU (pro databaze zalozene pred timto souborem,
-- kde uz tabulka "users" existuje bez nekterych sloupcu). Na cerstve
-- databazi selze s "duplicate column" a bin/migrate.php to bezpecne preskoci.
-- =====================================================
ALTER TABLE users ADD COLUMN newsletter BOOLEAN NOT NULL DEFAULT FALSE AFTER avatar_path;

-- =====================================================
-- VYCHOZI DATA
-- Vsechny INSERTy nize jsou idempotentni (ON DUPLICATE KEY UPDATE),
-- takze je bezpecne spustit tento soubor opakovane (bin/migrate.php
-- to ostatne dela pri kazdem behu, protoze si nepamatuje, ktere
-- migrace uz aplikoval).
-- =====================================================

-- Kategorie akci
INSERT INTO event_categories (name, slug, color, icon) VALUES
    ('Víkendovka', 'vikendovka', '#009043', 'tent'),
    ('Tábor', 'tabor', '#764c24', 'campfire'),
    ('Akce pro Čechy', 'akce-pro-cechy', '#0fa6db', 'globe'),
    ('Unijní Akce', 'unijni-akce', '#8b5cf6', 'globe')
ON DUPLICATE KEY UPDATE name = VALUES(name), color = VALUES(color), icon = VALUES(icon);

-- Admin uzivatel (heslo: admin123 - ZMEN SI HO PO PRVNIM PRIHLASENI!)
-- Hash vygenerovan pres password_hash('admin123', PASSWORD_BCRYPT, ['cost' => 12]).
-- ON DUPLICATE KEY UPDATE je zamerne no-op (email = email): pokud uz admin
-- existuje (napr. si zmenil heslo), tenhle seed mu ho pri opakovanem behu
-- migrace nesmi prepsat zpatky na admin123.
INSERT INTO users (email, password_hash, first_name, last_name, role, is_active) VALUES
    ('admin@jiznirkiz.cz', '$2y$12$TRkadJF3R.7PY1GXGTPJ3ehnmL29NgTX1Ez1vO4btq5pm/tgfOfYO', 'Admin', 'Pathfinder', 'admin', TRUE)
ON DUPLICATE KEY UPDATE email = email;

-- Ukazkova stranka "O nas"
INSERT INTO pages (title, slug, content, is_published, sort_order) VALUES
    ('O nas', 'o-nas', '<p>Vitejte na strankach oblasti Jizni Kriz Klubu Pathfinder.</p>', TRUE, 1)
ON DUPLICATE KEY UPDATE slug = slug;

-- Testovaci ucty pro rucni testovani ruznych roli (heslo pro vsechny: test1234)
-- Hash vygenerovan pres password_hash('test1234', PASSWORD_BCRYPT, ['cost' => 12]).
-- POZOR: pouze pro lokalni/dev pouziti, nespoustet na produkci (Railway).
INSERT INTO users (email, password_hash, first_name, last_name, role, is_active) VALUES
    ('vedouci@test.cz', '$2y$12$hKJn.cSenBI0V7V2oIklwuj9JvNRJxnN6e7BcWA8iavOVZTOkfCqG', 'Test', 'Vedouci', 'leader', TRUE),
    ('rodic@test.cz', '$2y$12$hKJn.cSenBI0V7V2oIklwuj9JvNRJxnN6e7BcWA8iavOVZTOkfCqG', 'Test', 'Rodic', 'parent', TRUE),
    ('clen@test.cz', '$2y$12$hKJn.cSenBI0V7V2oIklwuj9JvNRJxnN6e7BcWA8iavOVZTOkfCqG', 'Test', 'Clen', 'member', TRUE)
ON DUPLICATE KEY UPDATE role = VALUES(role), is_active = VALUES(is_active);

-- =====================================================
-- HOTOVO
-- =====================================================
