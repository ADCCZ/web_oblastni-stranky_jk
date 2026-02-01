# CLAUDE.md - Jizni Kriz Pathfinder Web

Tento soubor slouzi jako "single source of truth" pro vyvoj webu oblasti "Jizni Kriz" Klubu Pathfinder.

---

## 1. Project Overview

### Cil projektu

Vytvorit moderni, responzivni webove stranky pro oblast "Jizni Kriz" Klubu Pathfinder. Web slouzi jako informacni portal pro vedouci, rodice, deti a verejnost.

### Cilova skupina

| Role | Potreby |
|------|---------|
| **Vedouci** | Sprava akci, galerii, uzivatelu, administrace obsahu |
| **Rodice** | Prehled akci, registrace deti, kontakty, aktuality |
| **Deti/Clenove** | Informace o schuzkach, nadchazejici akce, fotky |
| **Verejnost** | Zakladni info o organizaci, kontakt, moznost pripojit se |

### Technology Stack

```
Backend:      PHP 8.3+ | Nette Framework 3.2+
Frontend:     Latte Templates | Tailwind CSS 3.4+
Build:        Vite 5.x | PostCSS
Database:     MySQL 8.0 / MariaDB 10.6+
Dependencies: Composer | NPM
API:          JSON REST API (pro budouci mobilni aplikaci)
```

### Klicove principy

- **Mobile-first**: Vsechny komponenty navrhovany primarne pro mobilni zarizeni
- **API-first**: Kazda funkcionalita dostupna pres REST API
- **Modularita**: Oddelene moduly pro snadnou udrzbu a rozsireni
- **Pristupnost**: WCAG 2.1 AA standardy
- **Vykon**: Lazy loading, optimalizovane obrazky, caching

---

## 2. Design System & UI Guidelines

### 2.1 Barevna paleta

Paleta vychazi z oficialnich barev Klubu Pathfinder s durazem na prirodni tony.

```css
/* Primary Colors */
--color-primary:        #0075b5;  /* Pathfinder Blue - hlavni akcni barva */
--color-primary-light:  #0fa6db;  /* Light Blue - hover stavy */
--color-primary-dark:   #005a8c;  /* Dark Blue - aktivni stavy */

/* Accent Colors */
--color-accent:         #ffd600;  /* Pathfinder Gold/Yellow - CTA, highlights */
--color-accent-hover:   #e6c200;  /* Darker Gold */

/* Nature Colors (pro sekce, kategorie) */
--color-forest:         #009043;  /* Forest Green - outdoor aktivity */
--color-forest-light:   #78ba63;  /* Light Green - success stavy */
--color-earth:          #764c24;  /* Earth Brown - taborove akce */
--color-earth-light:    #a67c52;  /* Light Brown */

/* Neutral Colors */
--color-gray-50:        #f9fafb;  /* Pozadi stranky */
--color-gray-100:       #f3f4f6;  /* Karty, sekce */
--color-gray-200:       #e5e7eb;  /* Bordery */
--color-gray-400:       #9ca3af;  /* Placeholder text */
--color-gray-600:       #4b5563;  /* Secondary text */
--color-gray-800:       #1f2937;  /* Primary text */
--color-gray-900:       #111827;  /* Headings */

/* Semantic Colors */
--color-success:        #10b981;  /* Uspech */
--color-warning:        #f59e0b;  /* Varovani */
--color-error:          #ef4444;  /* Chyba */
--color-info:           #3b82f6;  /* Informace */
```

### 2.2 Tailwind Configuration

```javascript
// tailwind.config.js
export default {
  content: [
    './app/Presenters/templates/**/*.latte',
    './app/Components/**/*.latte',
    './www/js/**/*.js',
  ],
  theme: {
    extend: {
      colors: {
        primary: {
          DEFAULT: '#0075b5',
          light: '#0fa6db',
          dark: '#005a8c',
        },
        accent: {
          DEFAULT: '#ffd600',
          hover: '#e6c200',
        },
        forest: {
          DEFAULT: '#009043',
          light: '#78ba63',
        },
        earth: {
          DEFAULT: '#764c24',
          light: '#a67c52',
        },
      },
      fontFamily: {
        sans: ['Inter', 'system-ui', 'sans-serif'],
        display: ['Montserrat', 'system-ui', 'sans-serif'],
      },
      fontSize: {
        'display-xl': ['3.5rem', { lineHeight: '1.1', fontWeight: '700' }],
        'display-lg': ['2.5rem', { lineHeight: '1.2', fontWeight: '700' }],
        'display-md': ['2rem', { lineHeight: '1.3', fontWeight: '600' }],
      },
      spacing: {
        '18': '4.5rem',
        '88': '22rem',
        '128': '32rem',
      },
      borderRadius: {
        'xl': '1rem',
        '2xl': '1.5rem',
      },
      boxShadow: {
        'card': '0 4px 6px -1px rgb(0 0 0 / 0.1), 0 2px 4px -2px rgb(0 0 0 / 0.1)',
        'card-hover': '0 10px 15px -3px rgb(0 0 0 / 0.1), 0 4px 6px -4px rgb(0 0 0 / 0.1)',
      },
    },
  },
  plugins: [
    require('@tailwindcss/forms'),
    require('@tailwindcss/typography'),
  ],
}
```

### 2.3 Typografie

| Element | Font | Size | Weight | Usage |
|---------|------|------|--------|-------|
| H1 | Montserrat | 3.5rem / 56px | 700 | Hero titulky |
| H2 | Montserrat | 2.5rem / 40px | 700 | Sekce nadpisy |
| H3 | Montserrat | 2rem / 32px | 600 | Podsekce |
| H4 | Inter | 1.5rem / 24px | 600 | Karty, moduly |
| Body | Inter | 1rem / 16px | 400 | Bezny text |
| Small | Inter | 0.875rem / 14px | 400 | Metadata, captions |

### 2.4 Komponenty - Design Tokens

**Buttons:**
```html
<!-- Primary Button -->
<button class="bg-primary hover:bg-primary-dark text-white font-semibold
               px-6 py-3 rounded-xl transition-colors duration-200">
  Prihlasit se na akci
</button>

<!-- Secondary Button -->
<button class="bg-white border-2 border-primary text-primary
               hover:bg-primary hover:text-white font-semibold
               px-6 py-3 rounded-xl transition-colors duration-200">
  Zjistit vice
</button>

<!-- Accent Button (CTA) -->
<button class="bg-accent hover:bg-accent-hover text-gray-900 font-bold
               px-8 py-4 rounded-xl shadow-card hover:shadow-card-hover
               transition-all duration-200">
  Registrovat se
</button>
```

**Cards:**
```html
<article class="bg-white rounded-2xl shadow-card hover:shadow-card-hover
                transition-shadow duration-300 overflow-hidden">
  <img class="w-full h-48 object-cover" src="..." alt="...">
  <div class="p-6">
    <span class="text-sm font-medium text-forest">Vikendovka</span>
    <h3 class="text-xl font-semibold text-gray-900 mt-2">Nazev akce</h3>
    <p class="text-gray-600 mt-3">Popis akce...</p>
  </div>
</article>
```

---

## 3. Database Schema

### 3.1 Entity Relationship Diagram (ERD)

```
+-------------------+       +-------------------+       +-------------------+
|      users        |       |      events       |       |   registrations   |
+-------------------+       +-------------------+       +-------------------+
| PK id             |       | PK id             |       | PK id             |
| email (unique)    |       | title             |       | FK user_id        |
| password_hash     |       | slug (unique)     |       | FK event_id       |
| first_name        |       | description       |       | status            |
| last_name         |       | content (TEXT)    |       | note              |
| role              |<---+  | location          |       | created_at        |
| phone             |    |  | start_date        |       | updated_at        |
| avatar_path       |    |  | end_date          |       +-------------------+
| is_active         |    |  | registration_from |              |
| created_at        |    |  | registration_to   |              |
| updated_at        |    |  | capacity          |              |
+-------------------+    |  | price             |              |
        |                |  | FK created_by ----|--+           |
        |                |  | FK category_id    |  |           |
        +----------------|--| is_published      |  |           |
                         |  | created_at        |  |           |
                         |  | updated_at        |  |           |
                         |  +-------------------+  |           |
                         |          |              |           |
                         |          v              |           |
                         |  +-------------------+  |           |
                         |  | event_categories  |  |           |
                         |  +-------------------+  |           |
                         |  | PK id             |  |           |
                         |  | name              |  |           |
                         |  | slug              |  |           |
                         |  | color             |  |           |
                         |  | icon              |  |           |
                         |  +-------------------+  |           |
                         |                         |           |
+-------------------+    |  +-------------------+  |           |
|     galleries     |    |  |       news        |  |           |
+-------------------+    |  +-------------------+  |           |
| PK id             |    |  | PK id             |  |           |
| title             |    |  | title             |  |           |
| slug (unique)     |    +--| slug (unique)     |  |           |
| description       |       | perex             |  |           |
| FK event_id (null)|       | content (TEXT)    |  |           |
| FK created_by     |-------| FK created_by ----|--+           |
| is_published      |       | is_published      |              |
| created_at        |       | published_at      |              |
| updated_at        |       | created_at        |              |
+-------------------+       | updated_at        |              |
        |                   +-------------------+              |
        v                                                      |
+-------------------+       +-------------------+              |
|   gallery_images  |       |     contacts      |              |
+-------------------+       +-------------------+              |
| PK id             |       | PK id             |              |
| FK gallery_id     |       | name              |              |
| filename          |       | role_title        |              |
| original_name     |       | email             |              |
| alt_text          |       | phone             |              |
| sort_order        |       | photo_path        |              |
| created_at        |       | sort_order        |              |
+-------------------+       +-------------------+              |
                                                               |
+-------------------+       +-------------------+              |
|      pages        |       |   audit_logs      |              |
+-------------------+       +-------------------+              |
| PK id             |       | PK id             |              |
| title             |       | FK user_id -------|--+-----------+
| slug (unique)     |       | action            |
| content (TEXT)    |       | entity_type       |
| meta_title        |       | entity_id         |
| meta_description  |       | old_values (JSON) |
| is_published      |       | new_values (JSON) |
| sort_order        |       | ip_address        |
| created_at        |       | user_agent        |
| updated_at        |       | created_at        |
+-------------------+       +-------------------+
```

### 3.2 SQL Schema

```sql
-- Users table
CREATE TABLE users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    role ENUM('admin', 'leader', 'member') NOT NULL DEFAULT 'member',
    phone VARCHAR(20) NULL,
    avatar_path VARCHAR(255) NULL,
    is_active BOOLEAN NOT NULL DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_role (role),
    INDEX idx_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Event categories
CREATE TABLE event_categories (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    slug VARCHAR(100) NOT NULL UNIQUE,
    color VARCHAR(7) NOT NULL DEFAULT '#0075b5',
    icon VARCHAR(50) NULL,

    INDEX idx_slug (slug)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Events table
CREATE TABLE events (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    slug VARCHAR(255) NOT NULL UNIQUE,
    description VARCHAR(500) NULL,
    content TEXT NULL,
    location VARCHAR(255) NULL,
    start_date DATETIME NOT NULL,
    end_date DATETIME NULL,
    registration_from DATETIME NULL,
    registration_to DATETIME NULL,
    capacity INT UNSIGNED NULL,
    price DECIMAL(10, 2) NULL,
    category_id INT UNSIGNED NULL,
    created_by INT UNSIGNED NOT NULL,
    is_published BOOLEAN NOT NULL DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (category_id) REFERENCES event_categories(id) ON DELETE SET NULL,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE,

    INDEX idx_slug (slug),
    INDEX idx_dates (start_date, end_date),
    INDEX idx_published (is_published),
    INDEX idx_category (category_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Event registrations
CREATE TABLE registrations (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
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

-- News/Articles
CREATE TABLE news (
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

-- Galleries
CREATE TABLE galleries (
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

-- Gallery images
CREATE TABLE gallery_images (
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

-- Static pages
CREATE TABLE pages (
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

-- Contact persons
CREATE TABLE contacts (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(200) NOT NULL,
    role_title VARCHAR(200) NULL,
    email VARCHAR(255) NULL,
    phone VARCHAR(20) NULL,
    photo_path VARCHAR(255) NULL,
    sort_order INT UNSIGNED NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Audit log for admin actions
CREATE TABLE audit_logs (
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

-- Default data
INSERT INTO event_categories (name, slug, color, icon) VALUES
    ('Schuzka', 'schuzka', '#0075b5', 'calendar'),
    ('Vikendovka', 'vikendovka', '#009043', 'tent'),
    ('Tabor', 'tabor', '#764c24', 'campfire'),
    ('Soutez', 'soutez', '#ffd600', 'trophy'),
    ('Celorepublikova akce', 'celorepublikova-akce', '#0fa6db', 'globe');
```

---

## 4. Architecture & Directory Structure

### 4.1 Nette Project Structure

```
jizni-kriz/
|
|-- app/                          # Application code
|   |-- Bootstrap.php             # Application bootstrap
|   |-- Router/
|   |   |-- RouterFactory.php     # Route definitions
|   |
|   |-- Presenters/               # Web presenters (controllers)
|   |   |-- BasePresenter.php     # Abstract base presenter
|   |   |-- HomepagePresenter.php
|   |   |-- EventPresenter.php
|   |   |-- NewsPresenter.php
|   |   |-- GalleryPresenter.php
|   |   |-- PagePresenter.php
|   |   |-- ContactPresenter.php
|   |   |-- Error4xxPresenter.php
|   |   |-- ErrorPresenter.php
|   |   |-- templates/
|   |   |   |-- @layout.latte     # Main layout
|   |   |   |-- Homepage/
|   |   |   |-- Event/
|   |   |   |-- ...
|   |
|   |-- AdminModule/              # Admin module (namespace App\AdminModule)
|   |   |-- Presenters/
|   |   |   |-- BasePresenter.php
|   |   |   |-- DashboardPresenter.php
|   |   |   |-- EventPresenter.php
|   |   |   |-- NewsPresenter.php
|   |   |   |-- GalleryPresenter.php
|   |   |   |-- UserPresenter.php
|   |   |   |-- PagePresenter.php
|   |   |   |-- templates/
|   |   |       |-- @layout.latte # Admin layout
|   |   |       |-- Dashboard/
|   |   |       |-- ...
|   |
|   |-- ApiModule/                # REST API module (namespace App\ApiModule)
|   |   |-- Presenters/
|   |   |   |-- BasePresenter.php # JSON responses, CORS
|   |   |   |-- EventPresenter.php
|   |   |   |-- NewsPresenter.php
|   |   |   |-- AuthPresenter.php
|   |   |   |-- RegistrationPresenter.php
|   |
|   |-- Model/                    # Business logic layer
|   |   |-- Entity/               # Entity classes (DTOs)
|   |   |   |-- User.php
|   |   |   |-- Event.php
|   |   |   |-- Registration.php
|   |   |   |-- ...
|   |   |
|   |   |-- Repository/           # Database access layer
|   |   |   |-- BaseRepository.php
|   |   |   |-- UserRepository.php
|   |   |   |-- EventRepository.php
|   |   |   |-- RegistrationRepository.php
|   |   |   |-- ...
|   |   |
|   |   |-- Service/              # Business services
|   |   |   |-- AuthService.php
|   |   |   |-- EventService.php
|   |   |   |-- RegistrationService.php
|   |   |   |-- ImageService.php
|   |   |   |-- MailService.php
|   |   |   |-- SlugService.php
|   |   |   |-- ...
|   |   |
|   |   |-- Facade/               # Facade pattern for complex operations
|   |       |-- EventFacade.php
|   |       |-- RegistrationFacade.php
|   |
|   |-- Components/               # Reusable UI components
|   |   |-- EventCard/
|   |   |   |-- EventCard.php
|   |   |   |-- EventCard.latte
|   |   |-- EventCalendar/
|   |   |   |-- EventCalendar.php
|   |   |   |-- EventCalendar.latte
|   |   |-- RegistrationForm/
|   |   |   |-- RegistrationForm.php
|   |   |   |-- RegistrationForm.latte
|   |   |-- Pagination/
|   |   |-- FlashMessages/
|   |
|   |-- Forms/                    # Form factories
|   |   |-- FormFactory.php       # Base form factory with CSRF
|   |   |-- SignInFormFactory.php
|   |   |-- SignUpFormFactory.php
|   |   |-- EventFormFactory.php
|   |   |-- ...
|   |
|   |-- Security/                 # Auth & authorization
|   |   |-- Authenticator.php
|   |   |-- Authorizator.php
|   |   |-- UserIdentity.php
|   |
|   |-- Utils/                    # Utility classes
|       |-- Validators.php
|       |-- Helpers.php
|
|-- bin/                          # CLI scripts
|   |-- console                   # Symfony Console entry
|
|-- config/                       # Configuration
|   |-- common.neon               # Shared config
|   |-- local.neon                # Local overrides (gitignored)
|   |-- local.neon.example        # Template for local config
|   |-- services.neon             # DI services registration
|
|-- log/                          # Log files (gitignored)
|-- temp/                         # Cache & sessions (gitignored)
|
|-- migrations/                   # Database migrations
|   |-- 001_initial_schema.sql
|   |-- 002_seed_data.sql
|
|-- resources/                    # Frontend source files
|   |-- css/
|   |   |-- app.css               # Main CSS (Tailwind imports)
|   |   |-- components/           # Component-specific styles
|   |-- js/
|   |   |-- app.js                # Main JS entry
|   |   |-- components/
|   |       |-- calendar.js
|   |       |-- gallery.js
|   |       |-- forms.js
|   |-- images/                   # Source images (for optimization)
|
|-- www/                          # Public document root
|   |-- index.php                 # Application entry point
|   |-- .htaccess
|   |-- assets/                   # Compiled assets (gitignored in dev)
|   |   |-- css/
|   |   |-- js/
|   |   |-- images/
|   |-- uploads/                  # User uploads
|       |-- avatars/
|       |-- galleries/
|       |-- events/
|
|-- tests/                        # Tests
|   |-- Unit/
|   |-- Integration/
|   |-- bootstrap.php
|
|-- vendor/                       # Composer dependencies (gitignored)
|-- node_modules/                 # NPM dependencies (gitignored)
|
|-- .gitignore
|-- .env                          # Environment variables (gitignored)
|-- .env.example
|-- composer.json
|-- package.json
|-- vite.config.js
|-- tailwind.config.js
|-- postcss.config.js
|-- claude.md                     # This file
|-- README.md
```

### 4.2 Nette Configuration Pattern

**config/common.neon:**
```neon
parameters:
    appName: 'Jizni Kriz Pathfinder'
    uploadDir: %wwwDir%/uploads
    imageMaxSize: 5MB

application:
    errorPresenter: Error
    mapping:
        *: App\*Module\Presenters\*Presenter
        Api: App\ApiModule\Presenters\*Presenter

session:
    expiration: 14 days

database:
    dsn: 'mysql:host=127.0.0.1;dbname=jizni_kriz'
    user: %env.DB_USER%
    password: %env.DB_PASSWORD%
    options:
        lazy: yes

services:
    - App\Router\RouterFactory::createRouter

    # Security
    security.authenticator: App\Security\Authenticator
    security.authorizator: App\Security\Authorizator

    # Repositories
    - App\Model\Repository\UserRepository
    - App\Model\Repository\EventRepository
    - App\Model\Repository\RegistrationRepository
    - App\Model\Repository\NewsRepository
    - App\Model\Repository\GalleryRepository
    - App\Model\Repository\PageRepository

    # Services
    - App\Model\Service\AuthService
    - App\Model\Service\EventService
    - App\Model\Service\RegistrationService
    - App\Model\Service\MailService
    imageService:
        class: App\Model\Service\ImageService
        arguments:
            uploadDir: %uploadDir%
            maxSize: %imageMaxSize%

    # Facades
    - App\Model\Facade\EventFacade
    - App\Model\Facade\RegistrationFacade

    # Forms
    - App\Forms\FormFactory
    - App\Forms\SignInFormFactory
    - App\Forms\SignUpFormFactory
    - App\Forms\EventFormFactory
```

**config/services.neon:**
```neon
services:
    routerFactory:
        class: App\Router\RouterFactory

    latte.latteFactory:
        setup:
            - addFilter(dateCs, [App\Utils\Helpers, dateCzech])
            - addFilter(truncate, [Nette\Utils\Strings, truncate])
```

### 4.3 Router Configuration

```php
<?php
// app/Router/RouterFactory.php

declare(strict_types=1);

namespace App\Router;

use Nette;
use Nette\Application\Routers\RouteList;

final class RouterFactory
{
    use Nette\StaticClass;

    public static function createRouter(): RouteList
    {
        $router = new RouteList;

        // API module routes
        $router->withModule('Api')
            ->addRoute('api/v1/events[/<id \d+>]', 'Event:default')
            ->addRoute('api/v1/news[/<id \d+>]', 'News:default')
            ->addRoute('api/v1/auth/<action>', 'Auth:default')
            ->addRoute('api/v1/registrations', 'Registration:default');

        // Admin module routes
        $router->withModule('Admin')
            ->addRoute('admin/<presenter>/<action>[/<id>]', 'Dashboard:default');

        // Frontend routes
        $router->addRoute('akce/<slug>', 'Event:detail');
        $router->addRoute('akce', 'Event:default');
        $router->addRoute('novinky/<slug>', 'News:detail');
        $router->addRoute('novinky', 'News:default');
        $router->addRoute('galerie/<slug>', 'Gallery:detail');
        $router->addRoute('galerie', 'Gallery:default');
        $router->addRoute('kalendar', 'Event:calendar');
        $router->addRoute('kontakt', 'Contact:default');
        $router->addRoute('o-nas', 'Page:about');
        $router->addRoute('<slug>', 'Page:detail');
        $router->addRoute('<presenter>/<action>[/<id>]', 'Homepage:default');

        return $router;
    }
}
```

### 4.4 Base Presenter Pattern

```php
<?php
// app/Presenters/BasePresenter.php

declare(strict_types=1);

namespace App\Presenters;

use Nette;
use Nette\Application\UI\Presenter;

abstract class BasePresenter extends Presenter
{
    /** @persistent */
    public string $locale = 'cs';

    protected function startup(): void
    {
        parent::startup();
    }

    protected function beforeRender(): void
    {
        parent::beforeRender();

        $this->template->appName = $this->context->parameters['appName'];
        $this->template->user = $this->getUser();
    }

    /**
     * Creates flash message component with Tailwind styling
     */
    protected function flashMessage(string $message, string $type = 'info'): \stdClass
    {
        $flash = parent::flashMessage($message, $type);

        // Map to Tailwind color classes
        $colors = [
            'success' => 'bg-green-100 text-green-800 border-green-200',
            'error' => 'bg-red-100 text-red-800 border-red-200',
            'warning' => 'bg-yellow-100 text-yellow-800 border-yellow-200',
            'info' => 'bg-blue-100 text-blue-800 border-blue-200',
        ];

        $flash->classes = $colors[$type] ?? $colors['info'];

        return $flash;
    }
}
```

### 4.5 API Module Base Presenter

```php
<?php
// app/ApiModule/Presenters/BasePresenter.php

declare(strict_types=1);

namespace App\ApiModule\Presenters;

use Nette\Application\UI\Presenter;
use Nette\Application\Responses\JsonResponse;

abstract class BasePresenter extends Presenter
{
    protected function startup(): void
    {
        parent::startup();

        // CORS headers
        $this->getHttpResponse()->setHeader('Access-Control-Allow-Origin', '*');
        $this->getHttpResponse()->setHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS');
        $this->getHttpResponse()->setHeader('Access-Control-Allow-Headers', 'Content-Type, Authorization');
        $this->getHttpResponse()->setHeader('Content-Type', 'application/json');

        // Handle preflight
        if ($this->getHttpRequest()->getMethod() === 'OPTIONS') {
            $this->terminate();
        }
    }

    protected function sendSuccess(mixed $data = null, int $code = 200): void
    {
        $this->getHttpResponse()->setCode($code);
        $this->sendJson([
            'success' => true,
            'data' => $data,
        ]);
    }

    protected function sendError(string $message, int $code = 400, array $errors = []): void
    {
        $this->getHttpResponse()->setCode($code);
        $this->sendJson([
            'success' => false,
            'message' => $message,
            'errors' => $errors,
        ]);
    }

    protected function requireAuth(): void
    {
        if (!$this->getUser()->isLoggedIn()) {
            $this->sendError('Unauthorized', 401);
        }
    }
}
```

---

## 5. Feature Specification

### 5.1 Authentication System

**Registration Flow:**
1. User fills registration form (email, password, name, phone)
2. Password validated (min 8 chars, complexity)
3. Email uniqueness check
4. Password hashed using `password_hash()` with BCRYPT
5. User created with `role = 'member'`, `is_active = true`
6. Confirmation email sent (optional)
7. Auto-login after registration

**Login Flow:**
1. User submits email + password
2. User fetched by email
3. `password_verify()` check
4. Check `is_active` status
5. Create user identity, start session
6. Redirect to intended URL or homepage

**Roles & Permissions:**
```php
// app/Security/Authorizator.php
$acl->addRole('member');
$acl->addRole('leader', 'member');
$acl->addRole('admin', 'leader');

$acl->addResource('event');
$acl->addResource('gallery');
$acl->addResource('news');
$acl->addResource('user');

// Member can read and register
$acl->allow('member', 'event', ['view', 'register']);
$acl->allow('member', 'gallery', 'view');
$acl->allow('member', 'news', 'view');

// Leader can manage events, galleries, news
$acl->allow('leader', 'event', ['view', 'create', 'edit']);
$acl->allow('leader', 'gallery', ['view', 'create', 'edit']);
$acl->allow('leader', 'news', ['view', 'create', 'edit']);

// Admin has full access
$acl->allow('admin');
```

### 5.2 Event Management

**Event Entity Fields:**
- title, slug, description (perex), content (full HTML)
- location (text or coordinates for map)
- start_date, end_date
- registration_from, registration_to (registration window)
- capacity (null = unlimited)
- price (null = free)
- category_id (vikendovka, tabor, soutez, etc.)
- is_published (draft / published)

**Event Listing (Public):**
- Filter by: category, date range, upcoming/past
- Sort by: date (default), title
- Pagination: 12 items per page
- Card view with image, title, date, category badge

**Event Detail (Public):**
- Full content with images
- Registration form (if open and user logged in)
- Current registration count / capacity
- Related gallery (if exists)
- Share buttons

**Event Admin:**
- CRUD operations
- Rich text editor for content (CKEditor or TipTap)
- Image upload for cover
- Preview before publish
- Duplicate event feature

### 5.3 Event Registration System

**Registration Flow:**
```
User views event
    |
    v
Check: registration_from <= now <= registration_to?
    |-- No --> Show "Registration not open" / "Registration closed"
    |
    v
Check: User logged in?
    |-- No --> Show login/register prompt
    |
    v
Check: Already registered?
    |-- Yes --> Show "You are registered" + status
    |
    v
Check: capacity reached?
    |-- Yes --> Option to join waitlist
    |
    v
Show registration form
    |
    v
Submit registration
    |
    v
Create registration (status: pending)
    |
    v
Send confirmation email to user
Send notification to event organizer
    |
    v
Leader confirms registration
    |
    v
Update status to "confirmed"
Send confirmation to user
```

**Registration Statuses:**
- `pending` - ceka na potvrzeni vedoucim
- `confirmed` - potvrzeno
- `cancelled` - zruseno (uzivatel nebo admin)
- `waitlist` - na cekacce (pokud plna kapacita)

**API Endpoints for Mobile:**
```
POST   /api/v1/registrations          Create registration
GET    /api/v1/registrations          List user's registrations
DELETE /api/v1/registrations/{id}     Cancel registration
```

### 5.4 Interactive Calendar

**Technology:** FullCalendar.js (or custom implementation)

**Features:**
- Month/week/day views
- Category color coding
- Click to view event detail
- Mobile swipe navigation
- Today highlight
- Loading indicator

**Data Format:**
```json
{
  "events": [
    {
      "id": 1,
      "title": "Vikendovka",
      "start": "2024-03-15T17:00:00",
      "end": "2024-03-17T14:00:00",
      "url": "/akce/vikendovka-brezen",
      "color": "#009043",
      "category": "vikendovka"
    }
  ]
}
```

**Latte Component:**
```latte
{* app/Components/EventCalendar/EventCalendar.latte *}
<div id="event-calendar"
     class="bg-white rounded-2xl shadow-card p-4 md:p-6"
     data-events-url="{plink Api:Event:default}">
</div>

<script type="module">
import { Calendar } from '@fullcalendar/core';
import dayGridPlugin from '@fullcalendar/daygrid';
// ... initialization
</script>
```

### 5.5 Photo Gallery

**Features:**
- Album-based organization
- Linked to events (optional)
- Lightbox viewer (GLightbox or similar)
- Lazy loading
- Thumbnail generation (multiple sizes)
- Drag & drop upload (admin)
- Bulk upload support
- Alt text for accessibility

**Image Processing:**
```php
// app/Model/Service/ImageService.php
class ImageService
{
    private const SIZES = [
        'thumb' => [300, 200],    // Gallery grid
        'medium' => [800, 600],   // Lightbox preview
        'large' => [1920, 1080],  // Full resolution
    ];

    public function processUpload(FileUpload $file, string $directory): array
    {
        $filename = $this->generateUniqueFilename($file);

        foreach (self::SIZES as $size => [$width, $height]) {
            $image = Image::fromFile($file->getTemporaryFile());
            $image->resize($width, $height, Image::SHRINK_ONLY);
            $image->save("{$directory}/{$size}/{$filename}");
        }

        return [
            'filename' => $filename,
            'original_name' => $file->getName(),
        ];
    }
}
```

### 5.6 News / Articles

**Features:**
- Rich text content (HTML)
- Featured image
- Perex (excerpt) for listings
- Publish scheduling (published_at)
- Author attribution
- Social sharing

### 5.7 REST API Specification

**Base URL:** `/api/v1`

**Authentication:** Bearer token (JWT)
```
Authorization: Bearer <token>
```

**Endpoints:**

| Method | Endpoint | Description | Auth |
|--------|----------|-------------|------|
| POST | /auth/login | Login, returns JWT | No |
| POST | /auth/register | Register new user | No |
| POST | /auth/refresh | Refresh token | Yes |
| GET | /events | List events | No |
| GET | /events/{id} | Event detail | No |
| GET | /events/calendar | Events for calendar | No |
| GET | /news | List news | No |
| GET | /news/{id} | News detail | No |
| GET | /registrations | User's registrations | Yes |
| POST | /registrations | Create registration | Yes |
| DELETE | /registrations/{id} | Cancel registration | Yes |
| GET | /profile | Current user profile | Yes |
| PUT | /profile | Update profile | Yes |

**Response Format:**
```json
{
  "success": true,
  "data": { ... },
  "meta": {
    "page": 1,
    "per_page": 20,
    "total": 45
  }
}
```

**Error Format:**
```json
{
  "success": false,
  "message": "Validation failed",
  "errors": {
    "email": ["Email is required"],
    "password": ["Password must be at least 8 characters"]
  }
}
```

---

## 6. Development Roadmap

### Phase 0: Project Setup (Day 1-2)

- [ ] Initialize Git repository
- [ ] Create Nette project via Composer
- [ ] Configure database connection
- [ ] Setup Vite + Tailwind build pipeline
- [ ] Configure development environment (.env)
- [ ] Create initial directory structure
- [ ] Setup local.neon configuration

```bash
# Commands
composer create-project nette/web-project jizni-kriz
cd jizni-kriz
npm init -y
npm install -D vite tailwindcss postcss autoprefixer @tailwindcss/forms @tailwindcss/typography
npx tailwindcss init -p
```

### Phase 1: Database & Core Models (Day 3-5)

- [ ] Create database and run migrations
- [ ] Implement BaseRepository
- [ ] Create User entity and repository
- [ ] Create Event entity and repository
- [ ] Create EventCategory entity and repository
- [ ] Create Registration entity and repository
- [ ] Write basic model tests

### Phase 2: Authentication (Day 6-8)

- [ ] Implement Authenticator
- [ ] Implement Authorizator with roles
- [ ] Create SignInFormFactory
- [ ] Create SignUpFormFactory
- [ ] Build login/register presenters
- [ ] Create auth templates
- [ ] Add password reset flow (optional)

### Phase 3: Public Frontend (Day 9-14)

- [ ] Design and build base layout
- [ ] Create responsive navigation
- [ ] Build Homepage (hero, upcoming events, latest news)
- [ ] Build Event listing page
- [ ] Build Event detail page
- [ ] Build News listing and detail
- [ ] Build Contact page
- [ ] Build static Page presenter
- [ ] Implement Calendar component

### Phase 4: Registration System (Day 15-18)

- [ ] Create RegistrationFormFactory
- [ ] Build registration flow in Event presenter
- [ ] Implement capacity checking
- [ ] Add waitlist logic
- [ ] Send confirmation emails
- [ ] Build "My registrations" page

### Phase 5: Admin Panel (Day 19-25)

- [ ] Create Admin module structure
- [ ] Build admin layout with sidebar
- [ ] Dashboard with stats
- [ ] Event CRUD with form
- [ ] News CRUD with rich editor
- [ ] User management
- [ ] Registration management
- [ ] Gallery management with upload
- [ ] Page management

### Phase 6: Gallery System (Day 26-28)

- [ ] Implement ImageService
- [ ] Build gallery CRUD
- [ ] Implement drag & drop upload
- [ ] Build public gallery views
- [ ] Integrate lightbox

### Phase 7: REST API (Day 29-32)

- [ ] Create API module structure
- [ ] Implement JWT authentication
- [ ] Build Event endpoints
- [ ] Build News endpoints
- [ ] Build Registration endpoints
- [ ] Build Auth endpoints
- [ ] API documentation (OpenAPI/Swagger)

### Phase 8: Polish & Optimization (Day 33-35)

- [ ] SEO meta tags
- [ ] Open Graph / social sharing
- [ ] Image optimization pipeline
- [ ] Caching strategy
- [ ] Error pages (404, 500)
- [ ] Accessibility audit
- [ ] Performance testing
- [ ] Security audit

### Phase 9: Deployment (Day 36-37)

- [ ] Production environment setup
- [ ] CI/CD pipeline (GitHub Actions)
- [ ] SSL certificate
- [ ] Backup strategy
- [ ] Monitoring setup
- [ ] Documentation

---

## 7. Development Commands

```bash
# Start development server
php -S localhost:8000 -t www

# Build assets (development)
npm run dev

# Build assets (production)
npm run build

# Watch mode for Tailwind
npm run watch

# Clear cache
rm -rf temp/cache/*

# Run tests
vendor/bin/tester tests/

# Database migrations
php bin/console migrations:migrate

# Generate entity from table
php bin/console orm:generate-entity users
```

---

## 8. Code Conventions

### PHP

- **PSR-12** coding standard
- **Strict types** in all files: `declare(strict_types=1);`
- **Type hints** for all parameters and return types
- **Final classes** by default (open for extension only when needed)
- **Readonly properties** where applicable (PHP 8.2+)

### Naming Conventions

| Element | Convention | Example |
|---------|------------|---------|
| Classes | PascalCase | `EventService` |
| Methods | camelCase | `findUpcoming()` |
| Variables | camelCase | `$eventList` |
| Constants | UPPER_SNAKE | `MAX_UPLOAD_SIZE` |
| DB tables | snake_case | `event_categories` |
| DB columns | snake_case | `created_at` |
| Routes | kebab-case | `/akce/vikendovka` |
| CSS classes | kebab-case | `.event-card` |

### Git Commit Messages

Format: `type(scope): description`

Types:
- `feat` - new feature
- `fix` - bug fix
- `docs` - documentation
- `style` - formatting, no code change
- `refactor` - code restructuring
- `test` - adding tests
- `chore` - maintenance

Examples:
```
feat(events): add registration capacity check
fix(auth): resolve session expiration issue
docs(api): update endpoint documentation
```

---

## 9. Security Checklist

- [ ] HTTPS only in production
- [ ] CSRF tokens on all forms (Nette handles automatically)
- [ ] XSS protection via Latte auto-escaping
- [ ] SQL injection prevention via Nette Database Explorer
- [ ] Password hashing with BCRYPT (cost 12+)
- [ ] Rate limiting on login/API endpoints
- [ ] Secure session configuration
- [ ] Input validation on all user data
- [ ] File upload validation (type, size, content)
- [ ] Sanitize file names on upload
- [ ] Content Security Policy headers
- [ ] CORS configuration for API

---

## 10. Useful Resources

- [Nette Documentation](https://doc.nette.org/)
- [Latte Templates](https://latte.nette.org/)
- [Nette Database Explorer](https://doc.nette.org/database/explorer)
- [Tailwind CSS Documentation](https://tailwindcss.com/docs)
- [Vite Documentation](https://vitejs.dev/)
- [FullCalendar](https://fullcalendar.io/)
- [GLightbox](https://biati-digital.github.io/glightbox/)

---

*Last updated: 2026-02-01*
*Version: 1.0.0*
