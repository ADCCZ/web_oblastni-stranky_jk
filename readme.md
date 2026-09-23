# Pathfinder JK

Webová prezentace a informační portál pro oblast "Jižní Kříž" Klubu Pathfinder
(křesťanská skautská organizace). Web slouží k prezentaci akcí, novinek,
fotogalerií a registraci účastníků na akce.

## Tech stack

- PHP 8.2+ s frameworkem [Nette](https://nette.org) 3.2+
- MySQL/MariaDB (Nette Database Explorer)
- Latte 3.1+ (šablonovací engine)
- Tailwind CSS 3.4+ (build přes Vite)
- Vanilla JavaScript

## Role uživatelů

- **admin** — plný přístup, správa uživatelů, akcí, registrací
- **leader** — správa akcí a obsahu
- **member** — prohlížení, registrace na akce

## Struktura projektu

```
app/
├── Presentation/       # Presentery (controllery) + šablony
│   ├── Home/            Úvodní stránka
│   ├── Sign/             Přihlášení / registrace
│   ├── Event/            Akce
│   ├── Registration/     Registrace na akce
│   ├── Profile/          Profil uživatele, děti
│   ├── Admin/            Administrace
│   ├── FormTemplate/      Form builder pro registrační formuláře
│   ├── Gallery/          Fotogalerie
│   ├── Password/         Reset hesla
│   └── Error/            Chybové stránky
├── Model/Repository/    Přístup k datům
├── Forms/               Factory pro formuláře
├── Security/             Autentizace + OAuth
├── Services/            Mail, TOTP, export do Excelu
└── Core/                Router

config/                  Konfigurace Nette (common.neon, services.neon, local.neon)
migrations/              SQL migrace databázového schématu
www/                     Veřejný document root (jediný vstupní bod: index.php)
assets/                  Zdrojové CSS/JS pro build
```

## Spuštění projektu

### Požadavky

- PHP 8.2+
- MySQL/MariaDB
- Composer
- Node.js + npm (pro build CSS)

### Instalace

```bash
composer install
npm install
```

### Konfigurace databáze

1. Vytvořit databázi `pathfinder_jk`
2. Spustit migrace ze složky `migrations/` v číselném pořadí
3. Nastavit `config/local.neon` (soubor je v `.gitignore`, je nutné ho vytvořit lokálně):

```neon
database:
    dsn: 'mysql:host=127.0.0.1;dbname=pathfinder_jk'
    user: root
    password: ''
```

### Build CSS

```bash
npm run dev    # vývoj, watch mode
npm run build  # produkční build (minifikovaný)
```

### Spuštění serveru

Přes XAMPP (Apache):

```
http://localhost/web_oblastni-stranky_jk/www/
```

Nebo přes PHP built-in server:

```bash
php -S localhost:8000 -t www
```

## Bezpečnost

- XSS ochrana: automatický escaping v Latte
- SQL injection ochrana: prepared statements přes Nette Database
- CSRF ochrana: Nette Forms token
- Hashování hesel: bcrypt (cost 12)
- Session: Nette session framework
