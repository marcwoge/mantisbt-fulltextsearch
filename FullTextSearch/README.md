# FullTextSearch — MantisBT Plugin

![License: GPL-2.0](https://img.shields.io/badge/License-GPL--2.0-blue.svg)
![MantisBT: ≥2.25.0](https://img.shields.io/badge/MantisBT-%E2%89%A52.25.0-brightgreen.svg)
![Status: Active](https://img.shields.io/badge/Status-Active-success.svg)

---

## 🇩🇪 Deutsch

### Beschreibung

**FullTextSearch** erweitert MantisBT 2.x um eine echte Volltextsuche über sämtliche
inhaltlichen Felder eines Tickets. Die in MantisBT eingebaute Suche stößt schnell an
Grenzen, sobald man Begriffe sucht, die nicht nur in der Zusammenfassung, sondern auch
in der Beschreibung, in den Schritten zur Reproduktion, in benutzerdefinierten Feldern
oder in den Notizen (Bugnotes) eines Tickets vorkommen. Genau diese Lücke schließt
dieses Plugin: Eine einzige Suchanfrage durchsucht alle genannten Quellen gleichzeitig
und liefert deduplizierte Treffer mit hervorgehobenem Kontext.

Das Plugin legt **keine** eigenen Datenbanktabellen an und verändert das Schema nicht.
Es liest ausschließlich die bestehenden MantisBT-Tabellen und respektiert dabei
konsequent die vorhandenen Zugriffsrechte: Es werden nur Tickets angezeigt, die der
angemeldete Benutzer ohnehin sehen darf, und private Notizen erscheinen nur bei
ausreichender Berechtigung.

### Funktionen

- Durchsucht in einer Anfrage: Zusammenfassung, Beschreibung, Schritte zur
  Reproduktion, zusätzliche Informationen, Werte benutzerdefinierter Felder sowie
  Notizen (Bugnotes).
- Standardmäßig case-insensitive `LIKE`-Suche — funktioniert ohne FULLTEXT-Index und
  damit auf jeder unterstützten Datenbank.
- Mehrere Wörter werden mit **UND** verknüpft: Jeder Begriff muss irgendwo im Ticket
  vorkommen.
- **Phrasensuche** mit doppelten Anführungszeichen: `"exakte Wortgruppe"`.
- Filter nach Projekt (oder „Alle Projekte"), nach Status (Mehrfachauswahl) und nach
  durchsuchten Feldern (per Checkbox an-/abschaltbar).
- Treffer werden mit `<mark>`-Hervorhebung und einem ausklappbaren Kontext-Snippet
  (max. 120 Zeichen je Feld) dargestellt.
- Ergebnisse sind über GET-Parameter teilbar und als Lesezeichen speicherbar.
- Paginierung mit 25 / 50 / 100 Ergebnissen pro Seite.
- Vollständig zweisprachig (Deutsch / Englisch) über MantisBT-Sprachdateien.

### Voraussetzungen

- MantisBT **2.25.0** oder neuer
- PHP **7.4+** (kompatibel bis einschließlich PHP 8.x)
- Keine externen Abhängigkeiten, kein Composer, kein npm

### Installation

1. Das Repository in das Verzeichnis `plugins/` Ihrer MantisBT-Installation klonen
   oder die Dateien dorthin herunterladen.
2. Das Plugin-Verzeichnis exakt in `FullTextSearch` umbenennen, falls es anders heißt
   (z. B. `mantisbt-fulltextsearch` → `FullTextSearch`).
3. Als Administrator in MantisBT anmelden.
4. Zu **Verwalten → Plugins verwalten** navigieren und neben *Full-Text Search* auf
   **Installieren** klicken.
5. Prüfen, ob der Menüpunkt **„Volltextsuche"** in der Hauptnavigation erscheint.

### Konfiguration (falls zutreffend)

Das Plugin benötigt keine Konfiguration. Nach der Installation ist es sofort
einsatzbereit. Sichtbarkeit und Trefferumfang ergeben sich automatisch aus den
bestehenden MantisBT-Zugriffsrechten und der aktuellen Projektauswahl.

### Verwendung

1. In der Hauptnavigation auf **„Volltextsuche"** klicken.
2. Einen oder mehrere Suchbegriffe eingeben. Für eine exakte Wortgruppe doppelte
   Anführungszeichen verwenden, z. B. `"Zeitüberschreitung beim Login"`.
3. Optional Projekt, Status und die zu durchsuchenden Felder einschränken sowie die
   Anzahl der Ergebnisse pro Seite wählen.
4. Auf **Suchen** klicken. Die Treffer erscheinen unterhalb des Formulars; je Treffer
   lässt sich der Trefferkontext aufklappen.

### Bekannte Einschränkungen

- Die `LIKE`-basierte Suche nutzt **keine** Datenbank-Indizes. Auf sehr großen
  Installationen (> 50.000 Tickets) kann die Suche dadurch langsam werden; eine
  Migration auf einen FULLTEXT-Index ist als zukünftige Option denkbar.
- Suchen über **alle Projekte** können auf sehr großen Datenbanken ohne weitere
  Query-Optimierung träge sein.
- In Version 1.0.0 gibt es **keine Relevanz-Sortierung**; Treffer werden nach dem
  Zeitpunkt der letzten Aktualisierung sortiert.
- Bei einer Suche über **alle Projekte** werden private Notizen nur dann
  durchsucht, wenn der Benutzer die Berechtigungsschwelle für private Notizen in
  *jedem* zugänglichen Projekt erfüllt. Diese bewusst konservative Regel
  verhindert, dass eine private Notiz ein Ticket als Treffer „verrät"; sie kann
  bei projektspezifisch abweichenden Schwellen dazu führen, dass einzelne
  sichtbare private Notizen nicht gefunden werden.

### Beitragen

Beiträge sind willkommen. Bitte Issues und Pull Requests im GitHub-Repository
`mantisbt-fulltextsearch` einreichen. Halten Sie sich beim Code an den vorhandenen
Stil (MantisBT-Konventionen, PHP 7.4-kompatibel, keine externen Abhängigkeiten).

### Lizenz

Veröffentlicht unter der **GPL-2.0-only** — kompatibel mit dem MantisBT-Kern. Den
vollständigen Lizenztext finden Sie in der Datei [LICENSE](LICENSE).

---

## 🇬🇧 English

### Description

**FullTextSearch** extends MantisBT 2.x with a genuine full-text search across every
content-bearing field of an issue. MantisBT's built-in search quickly reaches its
limits as soon as you look for terms that appear not only in the summary but also in
the description, the steps to reproduce, custom field values or an issue's bugnotes.
This plugin closes exactly that gap: a single query searches all of those sources at
once and returns deduplicated matches together with highlighted context.

The plugin creates **no** tables of its own and does not alter the schema. It reads the
existing MantisBT tables only, and it consistently respects the existing access
controls: it returns only issues the logged-in user is allowed to see, and private
bugnotes appear only when the user has sufficient permission.

### Features

- Searches, in one query: summary, description, steps to reproduce, additional
  information, custom field values and bugnotes.
- Case-insensitive `LIKE` search by default — works without a FULLTEXT index and
  therefore on every supported database.
- Multiple words are combined with **AND**: each term must appear somewhere in the
  issue.
- **Phrase search** using double quotes: `"exact phrase"`.
- Filter by project (or "All Projects"), by status (multi-select) and by the fields to
  search (toggle each with a checkbox).
- Matches are shown with `<mark>` highlighting and a collapsible context snippet
  (max. 120 characters per field).
- Results are shareable and bookmarkable via GET parameters.
- Pagination with 25 / 50 / 100 results per page.
- Fully bilingual (German / English) through MantisBT language files.

### Requirements

- MantisBT **2.25.0** or newer
- PHP **7.4+** (compatible up to and including PHP 8.x)
- No external dependencies, no Composer, no npm

### Installation

1. Clone the repository into the `plugins/` directory of your MantisBT installation,
   or download the files there.
2. Rename the plugin directory to exactly `FullTextSearch` if it has a different name
   (e.g. `mantisbt-fulltextsearch` → `FullTextSearch`).
3. Log in to MantisBT as an administrator.
4. Go to **Manage → Manage Plugins** and click **Install** next to *Full-Text Search*.
5. Verify that the **"Full-Text Search"** menu item appears in the main navigation bar.

### Configuration (if applicable)

The plugin requires no configuration. It is ready to use immediately after
installation. Visibility and the scope of matches follow automatically from the
existing MantisBT access controls and the currently selected project.

### Usage

1. Click **"Full-Text Search"** in the main navigation bar.
2. Enter one or more search terms. For an exact phrase use double quotes, e.g.
   `"timeout during login"`.
3. Optionally restrict the project, status and the fields to search, and choose the
   number of results per page.
4. Click **Search**. Matches appear below the form; expand the match context per row
   to see where each term was found.

### Known Limitations

- The `LIKE`-based search does **not** use database indexes. On large installations
  (> 50,000 issues) this can make searches slow; migrating to a FULLTEXT index is a
  conceivable future option.
- Searches across **all projects** may be slow on very large databases without further
  query optimization.
- Version 1.0.0 has **no relevance ranking**; results are ordered by last-updated
  timestamp.
- In an **All Projects** search, private bugnotes are only searched when the user
  meets the private-bugnote access threshold in *every* accessible project. This
  deliberately conservative rule prevents a private note from revealing an issue
  as a match; with per-project threshold overrides it may cause some otherwise
  visible private notes not to be found.

### Contributing

Contributions are welcome. Please file issues and pull requests in the GitHub
repository `mantisbt-fulltextsearch`. When contributing code, follow the existing style
(MantisBT conventions, PHP 7.4-compatible, no external dependencies).

### License

Released under **GPL-2.0-only** — compatible with the MantisBT core. See the
[LICENSE](LICENSE) file for the full license text.
