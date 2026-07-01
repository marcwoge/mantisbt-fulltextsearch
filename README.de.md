# FullTextSearch — MantisBT-Plugin

[🇬🇧 English](README.md) · **🇩🇪 Deutsch**

[![Lizenz: GPL-2.0](https://img.shields.io/badge/Lizenz-GPL--2.0-blue.svg)](LICENSE)
[![MantisBT: ≥2.25.0](https://img.shields.io/badge/MantisBT-%E2%89%A52.25.0-brightgreen.svg)](https://www.mantisbt.org/)
![Status: Aktiv](https://img.shields.io/badge/Status-Aktiv-success.svg)

Ein MantisBT-2.x-Plugin, das eine echte **Volltextsuche über alle Ticketfelder**
hinzufügt — Zusammenfassung, Beschreibung, Schritte zur Reproduktion, zusätzliche
Informationen, Werte benutzerdefinierter Felder und Notizen — in einer einzigen
Abfrage, mit hervorgehobenem Trefferkontext und unter Beachtung der
Zugriffsrechte.

---

## Beschreibung

Die in MantisBT eingebaute Suche stößt schnell an Grenzen, sobald man Begriffe
sucht, die nicht nur in der Zusammenfassung, sondern auch in der Beschreibung, in
den Schritten zur Reproduktion, in benutzerdefinierten Feldern oder in den Notizen
(Bugnotes) eines Tickets vorkommen. **FullTextSearch** schließt diese Lücke: Eine
einzige Suchanfrage durchsucht alle genannten Quellen gleichzeitig und liefert
deduplizierte Treffer mit hervorgehobenem Kontext.

Das Plugin legt **keine** eigenen Datenbanktabellen an und verändert das Schema
nicht. Es liest ausschließlich die bestehenden MantisBT-Tabellen und respektiert
dabei konsequent die vorhandenen Zugriffsrechte: Es werden nur Tickets angezeigt,
die der angemeldete Benutzer ohnehin sehen darf, und private Notizen werden — im
Ergebnis wie auch in der Suche selbst — ausgeschlossen, wenn die Berechtigung
fehlt.

## Funktionen

- Durchsucht in einer Anfrage: Zusammenfassung, Beschreibung, Schritte zur
  Reproduktion, zusätzliche Informationen, Werte benutzerdefinierter Felder sowie
  Notizen.
- Standardmäßig case-insensitive `LIKE`-Suche — funktioniert ohne FULLTEXT-Index
  und damit auf jeder unterstützten Datenbank.
- Mehrere Wörter werden mit **UND** verknüpft; exakte **Wortgruppen** über
  doppelte Anführungszeichen: `"Login Fehler"`.
- Filter nach Projekt (oder „Alle Projekte"), nach Status (Mehrfachauswahl) und
  nach durchsuchten Feldern (per Checkbox).
- `<mark>`-Hervorhebung und ein ausklappbares Kontext-Snippet je Feld (max. 120
  Zeichen).
- Ergebnisse per GET-Parameter teil- und als Lesezeichen speicherbar;
  Paginierung 25/50/100.
- Vollständig zweisprachig (Deutsch / Englisch) über MantisBT-Sprachdateien.

## Voraussetzungen

- MantisBT **2.25.0** oder neuer
- PHP **7.4+** (kompatibel bis einschließlich PHP 8.x)
- Keine externen Abhängigkeiten, kein Composer, kein npm

## Installation

1. Das Verzeichnis [`FullTextSearch/`](FullTextSearch) dieses Repositories in das
   `plugins/`-Verzeichnis Ihrer MantisBT-Installation kopieren, sodass der Pfad
   `plugins/FullTextSearch/FullTextSearch.php` entsteht.
   ```bash
   git clone https://github.com/marcwoge/mantisbt-fulltextsearch.git
   cp -r mantisbt-fulltextsearch/FullTextSearch /pfad/zu/mantisbt/plugins/
   ```
2. Sicherstellen, dass das Plugin-Verzeichnis exakt `FullTextSearch` heißt.
3. Als Administrator in MantisBT anmelden.
4. Zu **Verwalten → Plugins verwalten** navigieren und neben *Full-Text Search*
   auf **Installieren** klicken.
5. Prüfen, ob der Menüpunkt **„Volltextsuche"** in der Hauptnavigation
   (Seitenleiste) erscheint.

## Verwendung

1. In der Seitenleiste auf **„Volltextsuche"** klicken.
2. Einen oder mehrere Suchbegriffe eingeben. Für eine exakte Wortgruppe doppelte
   Anführungszeichen verwenden, z. B. `"Zeitüberschreitung beim Login"`.
3. Optional Projekt, Status und die zu durchsuchenden Felder einschränken sowie
   die Anzahl der Ergebnisse pro Seite wählen.
4. Auf **Suchen** klicken. Die Treffer erscheinen unterhalb des Formulars; je
   Zeile lässt sich der Trefferkontext aufklappen.

## Lokal ausprobieren (Docker)

Dieses Repository enthält eine sofort lauffähige MantisBT-Sandbox (MantisBT +
MariaDB + Mailpit) mit bereits eingebundenem Plugin.

```bash
docker compose up -d --build
# MantisBT : http://localhost:8989   (Installer einmalig abschließen)
# Mailpit  : http://localhost:8025
```

Den einmaligen Installer abschließen, mit `administrator` / `root` anmelden und
das Plugin unter **Verwalten → Plugins verwalten** installieren. Die komplette
Anleitung steht in [`docker/README.md`](docker/README.md).

## Bekannte Einschränkungen

- Die `LIKE`-basierte Suche nutzt **keine** Datenbank-Indizes. Auf sehr großen
  Installationen (> 50.000 Tickets) kann die Suche dadurch langsam werden; eine
  Migration auf einen FULLTEXT-Index ist als zukünftige Option denkbar.
- Suchen über **alle Projekte** können auf sehr großen Datenbanken ohne weitere
  Query-Optimierung träge sein.
- In Version 1.0.0 gibt es **keine Relevanz-Sortierung**; Treffer werden nach dem
  Zeitpunkt der letzten Aktualisierung sortiert.
- Bei einer Suche über *alle Projekte* werden private Notizen nur dann durchsucht,
  wenn der Benutzer die Berechtigungsschwelle für private Notizen in *jedem*
  zugänglichen Projekt erfüllt. Diese konservative Regel verhindert, dass eine
  private Notiz ein Ticket als Treffer verrät.

## Beitragen

Beiträge sind willkommen — bitte Issues und Pull Requests einreichen. Beim Code
bitte den vorhandenen Stil einhalten (MantisBT-Konventionen, PHP-7.4-kompatibel,
keine externen Abhängigkeiten). Eine manuelle
[Test-Checkliste](FullTextSearch/TESTING.md) liegt bei.

## Lizenz

Veröffentlicht unter **GPL-2.0-only** — kompatibel mit dem MantisBT-Kern. Den
vollständigen Text finden Sie in der Datei [LICENSE](LICENSE).
