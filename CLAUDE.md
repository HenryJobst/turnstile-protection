# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Projekt

WordPress-Plugin: **Turnstile Registration Protection** – schützt die WordPress-Benutzerregistrierung mit Cloudflare Turnstile (Bot-Schutz). Version 1.1.0, Lizenz GPL v2+.

## Build

```bash
bash build.sh
```

Erstellt `turnstile-protection-1.1.0.zip` mit `turnstile-protection.php` und `README.md` für die WordPress-Plugin-Installation.

## Architektur

Das Plugin besteht aus einer einzigen Datei (`turnstile-protection.php`). Die Klasse `Turnstile_Protection` (Singleton) kapselt alle Hooks, Einstellungen und Verifizierungslogik.

### WordPress-Hook-Registrierung (Einstiegspunkte)

| Hook | Methode | Zweck |
|------|---------|-------|
| `plugins_loaded` | `load_textdomain` | Lädt Übersetzungen aus `/languages` |
| `admin_menu` | `add_admin_menu` | Settings-Seite unter Einstellungen → Turnstile Schutz |
| `admin_init` | `register_settings` | WordPress Settings API: Felder `turnstile_protection_site_key` + `_secret_key` |
| `login_enqueue_scripts` | `enqueue_script` | Lädt Cloudflare Turnstile JS (nur auf `login`/`register` Seiten) |
| `register_form` | `render_widget` | Fügt `<div class="cf-turnstile">` in das Registrierungsformular ein |
| `login_form` | `render_widget` | Fügt `<div class="cf-turnstile">` in das Login-Formular ein |
| `lostpassword_form` | `render_widget` | Fügt `<div class="cf-turnstile">` in das Passwort-vergessen-Formular ein |
| `registration_errors` (Filter) | `verify_registration` | Serverseitige Verifizierung bei Registrierung |
| `authenticate` (Filter, Prio 20) | `verify_login` | Serverseitige Verifizierung beim Login; überspringt XML-RPC, REST API, WP-CLI |
| `lostpassword_post` | `verify_lostpassword` | Serverseitige Verifizierung beim Passwort-zurücksetzen |
| `admin_notices` | `activation_notice` | Zeigt Konfigurations-Warnung nach Plugin-Aktivierung |

### Konfiguration

Keys werden über `get_option`/`register_setting` in der WordPress-Datenbank gespeichert:
- `turnstile_protection_site_key` – öffentlicher Key (Site Key)
- `turnstile_protection_secret_key` – geheimer Key (Secret Key)

Beide Funktionen prüfen zuerst, ob Keys gesetzt sind, bevor sie aktiv werden.

### Verifizierungsablauf

`verify_token()` (private Methode, geteilt von Registration und Login) wird für die Verifizierung genutzt:
1. Prüft ob `$_POST['cf-turnstile-response']` vorhanden ist
2. POST-Request an `https://challenges.cloudflare.com/turnstile/v0/siteverify`
3. Fügt `WP_Error` hinzu bei Fehler – Registrierung wird nur fortgesetzt wenn keine Fehler

**Zentrale Methoden:** `is_configured()` prüft ob beide Keys gesetzt sind; `verify_token()` enthält die gemeinsame Verifizierungslogik.

## Voraussetzungen

- WordPress 5.0+
- PHP 7.4+
- Deployment: `turnstile-protection.php` nach `/wp-content/plugins/` kopieren
