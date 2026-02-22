# Turnstile Registration Protection

WordPress-Plugin zum Schutz der Benutzerregistrierung mit Cloudflare Turnstile.

## Funktionen

- ✅ Cloudflare Turnstile Integration im Registrierungsformular
- ✅ Serverseitige Verifizierung der Turnstile-Challenge
- ✅ Einfache Konfiguration über WordPress-Admin
- ✅ Deutsche Fehlermeldungen
- ✅ Keine Captcha-Bilder lösen nötig
- ✅ Kostenlos und unbegrenzt nutzbar

## Voraussetzungen

- WordPress 5.0 oder höher
- PHP 7.4 oder höher
- Cloudflare-Account (kostenlos)

## Installation

### 1. Plugin installieren

Kopieren Sie die Datei `turnstile-protection.php` in das Verzeichnis:

```
/wp-content/plugins/
```

### 2. Plugin aktivieren

Gehen Sie zu **Plugins** in WordPress-Admin und aktivieren Sie *Turnstile Registration Protection*.

### 3. Cloudflare Turnstile einrichten

1. Melden Sie sich bei [Cloudflare](https://dash.cloudflare.com) an
2. Navigieren Sie zu **Turnstile** im linken Menü
3. Klicken Sie auf **Add site**
4. Füllen Sie das Formular aus:
   - **Site name:** Ihr Website-Name
   - **Hostname:** Ihre Domain (z.B. `example.com`)
   - **Widget Mode:** Managed
5. Klicken Sie auf **Create**

Sie erhalten nun zwei Keys:
- **Site Key** (öffentlich)
- **Secret Key** (geheim)

### 4. Keys in WordPress konfigurieren

1. Gehen Sie zu **Einstellungen → Turnstile Schutz**
2. Tragen Sie den **Site Key** ein
3. Tragen Sie den **Secret Key** ein
4. Klicken Sie auf **Änderungen speichern**

## Funktionsweise

1. Besucher öffnet die Registrierungsseite
2. Turnstile-Widget wird automatisch geladen
3. Besucher löst die Challenge (meist unsichtbar)
4. Bei Registrierung wird die Antwort serverseitig verifiziert
5. Nur bei erfolgreicher Verifizierung wird der Benutzer erstellt

## Fehlerbehebung

### Widget wird nicht angezeigt

- Prüfen Sie, ob Site Key und Secret Key korrekt eingetragen sind
- Prüfen Sie die Browser-Konsole auf JavaScript-Fehler
- Stellen Sie sicher, dass keine Ad-Blocker das Skript blockieren

### Verifizierung schlägt fehl

- Prüfen Sie, ob der Secret Key korrekt ist
- Stellen Sie sicher, dass der Hostname in Cloudflare mit Ihrer Domain übereinstimmt
- Prüfen Sie, ob Ihr Server ausgehende HTTPS-Anfragen zulässt

## DSGVO-Hinweis

Cloudflare Turnstile ist DSGVO-konform. Weitere Informationen:
https://www.cloudflare.com/de-de/privacypolicy/

## Support

Bei Problemen oder Fragen erstellen Sie bitte ein Issue im Repository.

## Lizenz

GPL v2 oder später
