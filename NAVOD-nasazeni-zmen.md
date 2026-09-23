# Nasazení změn z review – Pathfinder JK

Postup pro XAMPP na Windows. Repo: `ADCCZ/web_oblastni-stranky_jk`, projekt v `pathfinder-jk/`.
Počítej s cca hodinou včetně proklikání.

Potřebné soubory (všechny tři patche i zipy jsou z tohoto chatu):

| Fáze | Patch | Zip (záloha, stejný obsah) |
|---|---|---|
| 1 – blokery, bezpečnost, veřejné stránky | `0001-fix-blockers-security_-_event-news-page-presenters.patch` | `pathfinder-jk_changed-files.zip` |
| 2 – admin, registrace, galerie, navbar | `0001-phase-2_admin-registrations-gallery_-_navbar-dropdow.patch` | `pathfinder-jk_phase2_changed-files.zip` |
| 3 – děti, e-maily, platby | `0001-phase-3_children-registrations_-_emails-payments.patch` | `pathfinder-jk_phase3_changed-files.zip` |

---

## 1. Aplikovat patche

Git Bash v kořeni repa (`web_oblastni-stranky_jk`, ne v `pathfinder-jk`):

```bash
git checkout main
git pull
git checkout -b feature/claude-review

git am --3way 0001-fix-blockers-security_-_event-news-page-presenters.patch
git am --3way 0001-phase-2_admin-registrations-gallery_-_navbar-dropdow.patch
git am --3way 0001-phase-3_children-registrations_-_emails-payments.patch
```

Patche jsou dělané nad commitem `20d8090` (`phase-1_users-auth`).

**Když `git am` ohlásí konflikt:**

```bash
git am --abort
```

a místo patchů rozbal tři zipy v pořadí 1 → 2 → 3 tak, aby jejich obsah přepsal soubory v `pathfinder-jk/`. Pak:

```bash
git add -A
git commit -m "review_&_phases-1-3"
```

---

## 2. Závislosti

```bash
cd pathfinder-jk
composer install        # přidá bacon/bacon-qr-code (QR kódy pro 2FA a platby)
npm install
npm run build           # NUTNÉ – nové šablony používají Tailwind třídy, které ve starém www/css/style.css nejsou
```

V `C:\xampp\php\php.ini` zkontroluj, že je odkomentované:

```ini
extension=gd
```

(potřebuje ho galerie – zmenšování fotek a náhledy; v XAMPPu bývá zapnuté). Po změně restartovat Apache.

---

## 3. Konfigurace

```bash
cp config/local.neon.example config/local.neon
```

V `config/local.neon` vyplň:

```neon
parameters:
	cronToken: 'sem-dlouhy-nahodny-retezec'     # chrání /cron/cleanup
	oauth:                                       # můžeš nechat prázdné, sociální login pak jen nepůjde
		google: { clientId: '', clientSecret: '' }
		facebook: { clientId: '', clientSecret: '' }
		discord: { clientId: '', clientSecret: '' }

database:
	dsn: 'mysql:host=localhost;dbname=pathfinder_jk;charset=utf8mb4'
	user: root
	password: ''

mail:
	smtp: no          # vývoj: e-maily se nepošlou, chyba jde do log/, web běží dál
```

Pro reálné testování e-mailů (potvrzení přihlášek) použij Mailtrap (zdarma) a do `mail:` dej jeho údaje:

```neon
mail:
	smtp: yes
	host: sandbox.smtp.mailtrap.io
	port: 2525
	username: '...'
	password: '...'
```

---

## 4. Databáze

phpMyAdmin → databáze `pathfinder_jk` → Import, nebo z konzole. **Pořadí je důležité:**

```
migrations/001_initial_schema.sql
migrations/002_oauth_columns.sql
migrations/003_password_reset_and_2fa.sql
migrations/004_user_nickname.sql
migrations/005_newsletter_and_code_attempts.sql      ← nové
migrations/006_children_and_payments.sql             ← nové
```

Pokud už máš DB s 001–004, spusť jen 005 a 006.

Migrace 006 ruší a znovu vytváří unikátní index na tabulce `registrations`. Když tam máš testovací data, projde, ale udělej si před tím export (phpMyAdmin → Export).

Co migrace přidávají:

- 005: `users.newsletter` (bez toho padala registrace), `attempts` u 2FA/reset kódů, tabulka `newsletter_subscribers`
- 006: tabulka `children`, `registrations.child_id / is_paid / paid_at`, tabulky `login_attempts` a `settings`

---

## 5. První admin

1. Zaregistruj se přes web: `/sign/up`
2. V phpMyAdmin:

```sql
UPDATE users SET role = 'admin' WHERE email = 'tvuj@email.cz';
```

3. Odhlásit a znovu přihlásit (role se načítá do session při loginu).

Role: `member` (přihlašuje sebe a děti) → `leader` (správa obsahu a přihlášek) → `admin` (vše + uživatelé, mazání, nastavení).

---

## 6. Cache a spuštění

```bat
rmdir /s /q temp\cache
```

(v Git Bash: `rm -rf temp/cache/*`). Vždy po změně `config/*.neon` nebo přidání presenteru.

Otevři: `http://localhost/web_oblastni-stranky_jk/pathfinder-jk/www/`

Složka `www/uploads/` musí být zapisovatelná (na Windows je).

---

## 7. Kontrolní průchod

Každý krok testuje jednu část; když něco selže, víš přesně kde.

| # | Co udělat | Co má být vidět |
|---|---|---|
| 1 | Otevřít homepage | Načte se, navbar má rozbalovací AKCE a O NÁS; při zúžení okna pod 1024 px hamburger a akordeon |
| 2 | `/admin` → Stránky | Žluté upozornění s tlačítky „Vytvořit Oddíly / Historie / O nás / Odkazy“ – vytvoř aspoň jednu |
| 3 | Kliknout v navbaru na vytvořenou stránku | Zobrazí obsah; nevytvořené ukazují „Připravujeme“, ne 404 |
| 4 | Admin → Akce → Nová akce | Vyplň cenu, **kapacitu 1**, přihlašování od dneška do za týden, publikovat |
| 5 | Profil → Moje děti → Přidat dítě | Dítě v seznamu s věkem |
| 6 | Detail akce → přihlásit sebe | Zelený box „odesláno, čeká na potvrzení“ |
| 7 | Tamtéž → přihlásit dítě | Musí skončit jako **náhradník** (kapacita 1 je plná) |
| 8 | Admin → Akce → číslo ve sloupci Přihlášky | Seznam přihlášek, tlačítka Potvrdit / Náhradník / Zrušit, Export CSV |
| 9 | Potvrdit svou přihlášku | Stav `confirmed`; s Mailtrapem dorazí e-mail |
| 10 | Admin → Nastavení → vyplnit IBAN (`CZ` + 22 číslic) | Uložit |
| 11 | Detail akce | U potvrzené přihlášky QR kód a VS – zkus načíst bankovní aplikací (nic neplať, jen ověř, že se předvyplní částka) |
| 12 | Admin → přihlášky → „nezaplaceno“ | Přepne na „zaplaceno“, v Profilu → Moje přihlášky je ✅ |
| 13 | Profil → Zabezpečení → 2FA přes aplikaci | QR kód je inline SVG (v DevTools žádný request na cizí doménu), Google Authenticator ho načte |
| 14 | Admin → Galerie → nová galerie → nahrát 2–3 fotky | Náhledy v adminu, `/galerie` ukáže obálku, detail otevře lightbox |
| 15 | `/akce.ics` | Stáhne se kalendář; v telefonu jde přidat jako odběr |
| 16 | 11× špatné heslo na `/sign/in` | Od 11. pokusu hláška o blokaci na 15 minut |
| 17 | `/cron/cleanup?token=<cronToken>` | Odpověď `OK {...}`; se špatným tokenem 403 |

---

## 8. Když něco spadne

Tracy zobrazí červenou stránku s výpisem chyby. Stejný výpis se ukládá do `log/exception-*.html` – ten mi pošli.

Nejpravděpodobnější místa, která jsem nemohl ověřit bez běžící DB:

1. `{plink $item['link'], $item['args'] ?? []}` v `app/Components/Navbar/Navbar.latte` – proměnná destinace odkazu
2. `$r->ref('children', 'child_id')` v přihláškách – vyžaduje, aby migrace 006 opravdu vytvořila cizí klíč `fk_reg_child`
3. `image-set()` u CTA pozadí na starším Safari – kosmetické, jen se nezobrazí pozadí

Rychlé opravy bez čekání na mě:

- **„Missing service of type …“** → `rm -rf temp/cache/*`
- **„Column not found“** → chybí migrace 005 nebo 006
- **Bílá stránka bez Tracy** → v `app/Bootstrap.php` Tracy běží jen v debug módu; na localhostu by měl být zapnutý automaticky
- **Rozbitý vzhled nových stránek** → nebyl spuštěn `npm run build`

---

## 9. Až to běží

```bash
git push -u origin feature/claude-review
```

Merge přes pull request na GitHubu, ať je v historii vidět, co je z review.

**Na produkci navíc:**

- `config/local.neon` s reálným SMTP a silným `cronToken`
- cron denně: `curl -s "https://tvoje-domena.cz/cron/cleanup?token=…"`
- `/admin/settings`: IBAN oblasti + e-mail vedoucích pro notifikace o přihláškách
- v `app/Bootstrap.php` **nezapínat** debug mód pro cizí IP (Tracy by ukazovala vnitřnosti aplikace)
- Apache musí mířit na `pathfinder-jk/www/`, ne na kořen projektu (jinak jsou `config/` a `log/` veřejně čitelné)

---

## Co zbývá (fáze 4, až tohle poběží)

- WYSIWYG editor v adminu (obsah je zatím HTML v textarea)
- Nette Tester: `RegistrationRepository::register()`, `PaymentService::spdString()`, `TotpService`
- obrázek k akci, dokumenty ke stažení (propozice, bezinfekčnost)
- připomínka e-mailem 3 dny před akcí (cron)
- double opt-in newsletteru a rozesílání
- oddíly jako data s mapou místo CMS stránky
