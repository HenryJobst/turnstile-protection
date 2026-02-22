# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Projekt

WordPress-Plugin: **Turnstile Registration Protection** – schützt die WordPress-Benutzerregistrierung mit Cloudflare Turnstile (Bot-Schutz). Version 1.0.0, Lizenz GPL v2+.

## Build

```bash
bash build.sh
```

Erstellt `turnstile-protection-1.0.0.zip` mit `turnstile-protection.php` und `README.md` für die WordPress-Plugin-Installation.

## Architektur

Das Plugin besteht aus einer einzigen Datei (`turnstile-protection.php`) ohne Klassen – reines prozedurales PHP mit WordPress-Hooks.

### WordPress-Hook-Registrierung (Einstiegspunkte)

| Hook | Funktion | Zweck |
|------|----------|-------|
| `admin_menu` | `turnstile_protection_add_admin_menu` | Settings-Seite unter Einstellungen → Turnstile Schutz |
| `admin_init` | `turnstile_protection_register_settings` | WordPress Settings API: Felder `turnstile_protection_site_key` + `_secret_key` |
| `login_enqueue_scripts` | `turnstile_protection_enqueue_script` | Lädt Cloudflare Turnstile JS (`challenges.cloudflare.com`) |
| `register_form` | `turnstile_protection_add_field` | Fügt `<div class="cf-turnstile">` in das Registrierungsformular ein |
| `registration_errors` (Filter) | `turnstile_protection_verify` | Serverseitige Verifizierung via `wp_remote_post` zur Cloudflare API |

### Konfiguration

Keys werden über `get_option`/`register_setting` in der WordPress-Datenbank gespeichert:
- `turnstile_protection_site_key` – öffentlicher Key (Site Key)
- `turnstile_protection_secret_key` – geheimer Key (Secret Key)

Beide Funktionen prüfen zuerst, ob Keys gesetzt sind, bevor sie aktiv werden.

### Verifizierungsablauf

`turnstile_protection_verify` wird als `registration_errors`-Filter aufgerufen:
1. Prüft ob Secret Key konfiguriert ist
2. Prüft ob `$_POST['cf-turnstile-response']` vorhanden ist
3. POST-Request an `https://challenges.cloudflare.com/turnstile/v0/siteverify`
4. Fügt `WP_Error` hinzu bei Fehler – Registrierung wird nur fortgesetzt wenn keine Fehler

## Voraussetzungen

- WordPress 5.0+
- PHP 7.4+
- Deployment: `turnstile-protection.php` nach `/wp-content/plugins/` kopieren
