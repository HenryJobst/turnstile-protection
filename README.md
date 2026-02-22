# Turnstile Registration Protection

WordPress plugin to protect registration, login and password reset with Cloudflare Turnstile.

## Features

- ✅ Cloudflare Turnstile integration for registration, login and lost password forms
- ✅ Server-side verification of the Turnstile challenge
- ✅ Simple configuration via WordPress admin
- ✅ Multilingual (English/German) with i18n support
- ✅ Fail-open on login during network errors (no lockout on Cloudflare outage)
- ✅ Bypass for Application Passwords, XML-RPC, REST API and WP-CLI
- ✅ No captcha images to solve
- ✅ Free and unlimited usage

## Requirements

- WordPress 5.0 or higher
- PHP 7.4 or higher
- Cloudflare account (free)

## Installation

### Option A: ZIP upload (recommended)

1. Download the latest `turnstile-protection-x.x.x.zip` from the [Releases](../../releases) page
2. In WordPress admin go to **Plugins → Add New → Upload Plugin**
3. Select the ZIP file and click **Install Now**
4. Click **Activate Plugin**

### Option B: Manual installation

1. Download or clone this repository
2. Copy the **entire** `turnstile-protection/` folder (including `languages/`) into:

```
/wp-content/plugins/turnstile-protection/
```

The folder structure should look like this:

```
wp-content/plugins/turnstile-protection/
├── turnstile-protection.php
├── languages/
│   ├── turnstile-protection-de_DE.mo
│   ├── turnstile-protection-de_DE.po
│   └── turnstile-protection.pot
└── README.md
```

3. Go to **Plugins** in WordPress admin and activate *Turnstile Registration Protection*

### 3. Set up Cloudflare Turnstile

1. Sign in to [Cloudflare](https://dash.cloudflare.com)
2. Navigate to **Turnstile** in the left menu
3. Click **Add site**
4. Fill in the form:
   - **Site name:** Your website name
   - **Hostname:** Your domain (e.g. `example.com`)
   - **Widget Mode:** Managed
5. Click **Create**

You will receive two keys:
- **Site Key** (public)
- **Secret Key** (secret)

### 4. Configure keys in WordPress

1. Go to **Settings → Turnstile Protection**
2. Enter the **Site Key**
3. Enter the **Secret Key**
4. Click **Save Changes**

## How it works

1. Visitor opens the registration, login or lost password page
2. The Turnstile widget loads automatically
3. Visitor completes the challenge (usually invisible)
4. On form submission the response is verified server-side
5. The action is only executed upon successful verification

### Error behavior

- **Registration & Lost Password:** Fail-closed — blocked on network errors or missing tokens
- **Login:** Fail-open on network errors — prevents admin lockout during Cloudflare outages. Missing or invalid tokens are still blocked.

## Troubleshooting

### Widget not showing

- Verify that Site Key and Secret Key are entered correctly
- Check the browser console for JavaScript errors
- Make sure no ad blockers are blocking the script

### Verification fails

- Verify that the Secret Key is correct
- Ensure the hostname in Cloudflare matches your domain
- Check that your server allows outgoing HTTPS requests

## GDPR notice

Cloudflare Turnstile is GDPR compliant. More information:
https://www.cloudflare.com/privacypolicy/

## License

MIT License — see [LICENSE](LICENSE)

---

# Turnstile Registration Protection (Deutsch)

WordPress-Plugin zum Schutz der Benutzerregistrierung mit Cloudflare Turnstile.

## Funktionen

- ✅ Cloudflare Turnstile Integration in Registrierung, Login und Passwort-vergessen
- ✅ Serverseitige Verifizierung der Turnstile-Challenge
- ✅ Einfache Konfiguration über WordPress-Admin
- ✅ Mehrsprachig (Deutsch/Englisch) mit i18n-Support
- ✅ Fail-open beim Login bei Netzwerkfehlern (kein Lockout bei Cloudflare-Ausfall)
- ✅ Bypass für Application Passwords, XML-RPC, REST API und WP-CLI
- ✅ Keine Captcha-Bilder lösen nötig
- ✅ Kostenlos und unbegrenzt nutzbar

## Voraussetzungen

- WordPress 5.0 oder höher
- PHP 7.4 oder höher
- Cloudflare-Account (kostenlos)

## Installation

### Option A: ZIP-Upload (empfohlen)

1. Laden Sie die neueste `turnstile-protection-x.x.x.zip` von der [Releases](../../releases)-Seite herunter
2. Gehen Sie in WordPress-Admin zu **Plugins → Installieren → Plugin hochladen**
3. Wählen Sie die ZIP-Datei aus und klicken Sie auf **Jetzt installieren**
4. Klicken Sie auf **Plugin aktivieren**

### Option B: Manuelle Installation

1. Laden Sie dieses Repository herunter oder klonen Sie es
2. Kopieren Sie den **gesamten** Ordner `turnstile-protection/` (inkl. `languages/`) nach:

```
/wp-content/plugins/turnstile-protection/
```

Die Ordnerstruktur sollte so aussehen:

```
wp-content/plugins/turnstile-protection/
├── turnstile-protection.php
├── languages/
│   ├── turnstile-protection-de_DE.mo
│   ├── turnstile-protection-de_DE.po
│   └── turnstile-protection.pot
└── README.md
```

3. Gehen Sie zu **Plugins** in WordPress-Admin und aktivieren Sie *Turnstile Registration Protection*

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

1. Besucher öffnet die Registrierungs-, Login- oder Passwort-vergessen-Seite
2. Turnstile-Widget wird automatisch geladen
3. Besucher löst die Challenge (meist unsichtbar)
4. Bei Absenden des Formulars wird die Antwort serverseitig verifiziert
5. Nur bei erfolgreicher Verifizierung wird die Aktion ausgeführt

### Fehlerverhalten

- **Registrierung & Passwort-vergessen:** Fail-closed — bei Netzwerkfehlern oder fehlendem Token wird die Aktion blockiert
- **Login:** Fail-open bei Netzwerkfehlern — verhindert Admin-Lockout bei Cloudflare-Ausfall. Fehlende oder ungültige Tokens werden weiterhin blockiert

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

## Lizenz

MIT License — siehe [LICENSE](LICENSE)

## Support

Bei Problemen oder Fragen erstellen Sie bitte ein Issue im Repository.
