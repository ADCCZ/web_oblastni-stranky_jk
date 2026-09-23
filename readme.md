# Pathfinder JK

Webová prezentace a informační portál pro oblast "Jižní Kříž" Klubu Pathfinder
(křesťanská skautská organizace). Web slouží k prezentaci akcí, novinek,
fotogalerií a registraci účastníků na akce.

## Tech stack

- PHP 8.2+ s frameworkem [Nette](https://nette.org) 3.2+
- MySQL/MariaDB (Nette Database Explorer)
- Latte 3.1+ (šablonovací engine)
- Tailwind CSS 3.4+ (build přes CLI)
- Vanilla JavaScript
- Docker + Docker Compose (lokální vývoj i nasazení, XAMPP už není potřeba)

## Role uživatelů

- **admin** — plný přístup, správa uživatelů, akcí, registrací
- **leader** — správa akcí a obsahu
- **member** — prohlížení, registrace na akce

## Struktura projektu

```text
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

bin/migrate.php          Skript pro spuštění SQL migrací
config/                  Konfigurace Nette (common.neon, services.neon, local.neon)
docker/                  Apache vhost + entrypoint skript pro produkční image
migrations/              SQL migrace databázového schématu
www/                     Veřejný document root (jediný vstupní bod: index.php)
assets/                  Zdrojové CSS/JS pro build
Dockerfile               Produkční PHP+Apache image (používá i Railway)
docker-compose.yml       Lokální vývojové prostředí (app + MySQL + Adminer)
```

## Spuštění projektu (Docker Compose)

Jediný požadavek je nainstalovaný [Docker Desktop](https://www.docker.com/products/docker-desktop/).
Nic dalšího (PHP, MySQL, Node, Composer) není potřeba mít nainstalované přímo v systému.

### 1. Nastavení proměnných prostředí

```bash
cp .env.example .env
```

Výchozí hodnoty v `.env.example` fungují rovnou pro lokální vývoj (databáze v Dockeru).
Pokud chceš posílat maily nebo používat OAuth přihlášení (Google/Facebook/Discord),
doplň si vlastní údaje do `.env` (viz komentáře v souboru, kde je získat).

### 2. Spuštění

```bash
docker compose up -d --build
```

Tím se spustí:

| Služba     | Popis                                    | URL                         |
|------------|-------------------------------------------|------------------------------|
| `app`      | PHP 8.2 + Apache (web aplikace)           | http://localhost:8000       |
| `db`       | MySQL 8.0                                 | localhost:3307 (z hostitele) |
| `adminer`  | webové UI pro správu databáze             | http://localhost:8080       |
| `css`      | sleduje `assets/` a přebuilduje Tailwind  | -                            |

### 3. Databázové migrace (jen při prvním spuštění / po přidání nové migrace)

```bash
docker compose exec app php bin/migrate.php
```

Skript spustí všechny `.sql` soubory ze složky `migrations/` v abecedním pořadí.
Je bezpečné ho spustit opakovaně (přeskočí už existující tabulky/sloupce).

### Užitečné příkazy

```bash
docker compose logs -f app      # log PHP/Apache
docker compose exec app bash    # shell v PHP kontejneru
docker compose down             # zastavit vše
docker compose down -v          # zastavit a smazat i data databáze
```

V Admineru (http://localhost:8080) se přihlásíš přes:
- System: MySQL
- Server: `db`
- Uživatel/heslo/databáze: podle `.env` (výchozí `pathfinder` / `pathfinder` / `pathfinder_jk`)

### Bez Dockeru (nedoporučeno, jen pro nouzi)

Pokud opravdu nechceš Docker, potřebuješ lokálně PHP 8.2+, MySQL/MariaDB, Composer a Node.js:

```bash
composer install
npm install && npm run build
```

Nastav proměnné prostředí (`DB_HOST`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`, ...) v systému
nebo je vlož napevno do `config/local.neon` (viz komentář v souboru), vytvoř databázi a spusť
migrace (`php bin/migrate.php`), pak:

```bash
php -S localhost:8000 -t www
```

## Nasazení na Railway (produkce)

Aplikace se nasazuje jako Docker kontejner na [Railway](https://railway.app) — běžný
hosting typu Vercel/Netlify PHP nepodporuje (žádný oficiální PHP runtime, žádná
spravovaná MySQL databáze, žádný persistentní disk pro session/cache/upload souborů),
proto Vercel pro tuto aplikaci nefunguje. Railway naproti tomu spustí `Dockerfile`
z repozitáře přímo tak, jak je, včetně spravované MySQL databáze.

### 1. Vytvoření projektu

1. Na [railway.app](https://railway.app) → **New Project** → **Deploy from GitHub repo**
   → vyber tento repozitář.
2. Railway automaticky najde `Dockerfile` v rootu a `railway.json` (viz níže) a podle
   něj aplikaci sestaví a spustí. Přiřadí i veřejnou HTTPS doménu (`*.up.railway.app`).

### 2. Přidání databáze

1. V projektu klikni **+ New** → **Database** → **Add MySQL**.
2. Otevři záložku **Variables** u MySQL služby a zkopíruj si názvy proměnných, které
   Railway vygenerovalo (typicky `MYSQLHOST`, `MYSQLPORT`, `MYSQLDATABASE`, `MYSQLUSER`,
   `MYSQLPASSWORD` — názvy si over přímo v UI, Railway je čas od času mění).

### 3. Proměnné prostředí webové služby

V záložce **Variables** u webové (app) služby nastav (hodnoty napravo odkazují na
MySQL službu podle jejího jména v projektu, typicky `MySQL`):

```env
DB_HOST=${{MySQL.MYSQLHOST}}
DB_PORT=${{MySQL.MYSQLPORT}}
DB_DATABASE=${{MySQL.MYSQLDATABASE}}
DB_USERNAME=${{MySQL.MYSQLUSER}}
DB_PASSWORD=${{MySQL.MYSQLPASSWORD}}
```

Volitelně přidej i mail/OAuth proměnné ze stejné sady jako v `.env.example`
(`MAIL_SMTP`, `MAIL_HOST`, ..., `OAUTH_GOOGLE_ID`, ...), pokud je chceš v produkci používat.

`PORT` nastavuje Railway automaticky — Apache uvnitř kontejneru se na něj sám přepne
(`docker/entrypoint.sh`), není potřeba nic nastavovat ručně.

### 4. Spuštění migrací

Přes [Railway CLI](https://docs.railway.com/guides/cli):

```bash
railway login
railway link            # vyber tento projekt
railway run php bin/migrate.php
```

(Příkaz se spustí lokálně, ale s proměnnými prostředí z Railway, takže se připojí
rovnou na produkční databázi.)

### 5. Persistentní úložiště pro nahrané soubory

Kontejner na Railway má efemérní disk — cokoliv se nahraje do `www/uploads`
(galerie, avatary), po redeployi zmizí. V nastavení webové služby přidej
**Volume** připojený na `/var/www/html/www/uploads`, aby nahrané soubory přežily
redeploy.

## Bezpečnost

- XSS ochrana: automatický escaping v Latte
- SQL injection ochrana: prepared statements přes Nette Database
- CSRF ochrana: Nette Forms token
- Hashování hesel: bcrypt (cost 12)
- Session: Nette session framework
- Žádné tajné údaje (DB heslo, SMTP, OAuth klíče) nejsou v repozitáři — čtou se
  z proměnných prostředí (`.env` lokálně, proměnné nastavené v Railway dashboardu
  v produkci). Viz `.env.example` a `app/Bootstrap.php`.
