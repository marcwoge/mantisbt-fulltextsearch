# Lokale MantisBT-Testumgebung

Komplett lauffähige MantisBT-Instanz zum Ausprobieren des **FullTextSearch**
Plugins – inklusive Datenbank und einem Mail-Catcher, der alle ausgehenden
E-Mails abfängt (es wird also nichts wirklich verschickt).

| Dienst | URL | Zugang |
| --- | --- | --- |
| MantisBT | http://localhost:8989 | `administrator` / `root` |
| Mailpit (E-Mails ansehen) | http://localhost:8025 | – |
| MariaDB | intern `db:3306` | `mantisbt` / `mantisbt` |

> Reines Test-Setup. Die Zugangsdaten/Salt in `docker-compose.yml` und
> `config_inc.php` sind absichtlich simpel – **nicht in Produktion verwenden.**

## Starten

Aus dem Projekt-Stammverzeichnis (eine Ebene über `docker/`):

```bash
docker compose up -d --build
```

Beim ersten Start wird das MantisBT-Image gebaut (lädt das offizielle Release
herunter). Danach **einmalig** den Installer abschließen:

1. http://localhost:8989/admin/install.php öffnen.
2. Die Datenbankfelder sind bereits aus `docker/config_inc.php` vorbefüllt
   (Host `db`, DB `bugtracker`, Benutzer `mantisbt`/`mantisbt`).
3. Auf **„Install/Upgrade Database"** klicken → die Tabellen werden angelegt.
4. Fertig. Anmelden mit `administrator` / `root`.

## Plugin installieren

1. In MantisBT: **Manage → Manage Plugins**.
2. Bei *Full-Text Search* auf **Install** klicken.
3. Der Menüpunkt **„Volltextsuche"** erscheint anschließend in der
   Hauptnavigation (Seitenleiste).

Das Plugin ist bereits in den Container gemountet
(`./FullTextSearch` → `/var/www/html/plugins/FullTextSearch`). Änderungen an den
Plugin-Dateien wirken sofort, ohne Neubau.

## Volltextsuche testen

1. Ein paar Tickets anlegen (mit aussagekräftiger Zusammenfassung,
   Beschreibung, ggf. benutzerdefinierten Feldern und Notizen).
2. In der Seitenleiste auf **„Volltextsuche"** klicken.
3. Einen Suchbegriff eingeben. Für eine exakte Wortgruppe doppelte
   Anführungszeichen verwenden, z. B. `"Login Fehler"`. Mehrere Wörter werden
   mit UND verknüpft.
4. Über die Checkboxen *Zusammenfassung / Beschreibung / Benutzerdefinierte
   Felder / Notizen* den Suchbereich eingrenzen und das Ergebnis prüfen.

Der ausgehende Mail-Catcher **Mailpit** (http://localhost:8025) bleibt für
allgemeine MantisBT-Benachrichtigungen verfügbar; das Plugin selbst versendet
keine E-Mails.

## Häufige Befehle

```bash
# Logs ansehen
docker compose logs -f mantis

# In den MantisBT-Container
docker compose exec mantis bash

# Stoppen (Daten bleiben erhalten)
docker compose down

# Stoppen und ALLES löschen (DB-Daten zurücksetzen)
docker compose down -v
```

## Hinweis zu Windows / Git Bash

Wird `docker compose exec` aus Git Bash mit einem absoluten Container-Pfad
aufgerufen, wandelt Git Bash den Pfad evtl. um. Dann der Variante mit
`MSYS_NO_PATHCONV=1` davor verwenden, z. B.:

```bash
MSYS_NO_PATHCONV=1 docker compose exec mantis \
  php /var/www/html/plugins/Reminder/cron/reminder_cron.php force-digest
```
