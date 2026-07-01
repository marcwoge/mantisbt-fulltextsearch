# FullTextSearch — MantisBT Plugin

**🇬🇧 English** · [🇩🇪 Deutsch](README.de.md)

[![License: GPL-2.0](https://img.shields.io/badge/License-GPL--2.0-blue.svg)](LICENSE)
[![MantisBT: ≥2.25.0](https://img.shields.io/badge/MantisBT-%E2%89%A52.25.0-brightgreen.svg)](https://www.mantisbt.org/)
![Status: Active](https://img.shields.io/badge/Status-Active-success.svg)

A MantisBT 2.x plugin that adds a genuine **full-text search across all issue
fields** — summary, description, steps to reproduce, additional information,
custom field values and bugnotes — in a single query, with highlighted match
context and access-control awareness.

---

## Description

MantisBT's built-in search quickly reaches its limits as soon as you look for
terms that appear not only in the summary but also in the description, the steps
to reproduce, custom field values or an issue's bugnotes. **FullTextSearch**
closes that gap: a single query searches all of those sources at once and returns
deduplicated matches together with highlighted context.

The plugin creates **no** tables of its own and does not alter the schema. It
reads the existing MantisBT tables only, and it consistently respects the
existing access controls: it returns only issues the logged-in user is allowed to
see, and private bugnotes are excluded — both from the results and from the
search itself — when the user lacks permission.

## Features

- Searches, in one query: summary, description, steps to reproduce, additional
  information, custom field values and bugnotes.
- Case-insensitive `LIKE` search by default — works without a FULLTEXT index and
  therefore on every supported database.
- Multiple words are combined with **AND**; exact **phrases** use double quotes:
  `"login error"`.
- Filter by project (or "All Projects"), by status (multi-select) and by the
  fields to search (toggle each with a checkbox).
- `<mark>` highlighting and a collapsible per-field match snippet (≤120 chars).
- Results are shareable/bookmarkable via GET parameters; pagination 25/50/100.
- Fully bilingual (German / English) through MantisBT language files.

## Requirements

- MantisBT **2.25.0** or newer
- PHP **7.4+** (compatible up to and including PHP 8.x)
- No external dependencies, no Composer, no npm

## Installation

1. Copy the [`FullTextSearch/`](FullTextSearch) directory of this repository into
   your MantisBT installation's `plugins/` directory, so that the path becomes
   `plugins/FullTextSearch/FullTextSearch.php`.
   ```bash
   git clone https://github.com/marcwoge/mantisbt-fulltextsearch.git
   cp -r mantisbt-fulltextsearch/FullTextSearch /path/to/mantisbt/plugins/
   ```
2. Make sure the plugin directory is named exactly `FullTextSearch`.
3. Log in to MantisBT as an administrator.
4. Go to **Manage → Manage Plugins** and click **Install** next to
   *Full-Text Search*.
5. Verify that the **"Full-Text Search"** item appears in the main navigation
   (sidebar).

## Usage

1. Click **"Full-Text Search"** in the sidebar.
2. Enter one or more search terms. For an exact phrase use double quotes, e.g.
   `"timeout during login"`.
3. Optionally restrict the project, status and the fields to search, and choose
   the number of results per page.
4. Click **Search**. Matches appear below the form; expand *Matches* per row to
   see where each term was found.

## Try it locally (Docker)

This repository ships a ready-to-run MantisBT sandbox (MantisBT + MariaDB +
Mailpit) with the plugin already mounted.

```bash
docker compose up -d --build
# MantisBT : http://localhost:8989   (finish the installer once)
# Mailpit  : http://localhost:8025
```

Finish the one-time installer, log in as `administrator` / `root`, then install
the plugin under **Manage → Manage Plugins**. See [`docker/README.md`](docker/README.md)
for the full walkthrough.

## Known Limitations

- The `LIKE`-based search does **not** use database indexes. On large
  installations (> 50,000 issues) this can make searches slow; migrating to a
  FULLTEXT index is a conceivable future option.
- Searches across **all projects** may be slow on very large databases without
  further query optimization.
- Version 1.0.0 has **no relevance ranking**; results are ordered by last-updated
  timestamp.
- In an *All Projects* search, private bugnotes are only searched when the user
  meets the private-bugnote access threshold in *every* accessible project. This
  conservative rule prevents a private note from revealing an issue as a match.

## Contributing

Contributions are welcome — please file issues and pull requests. When
contributing code, follow the existing style (MantisBT conventions, PHP
7.4-compatible, no external dependencies). A manual
[testing checklist](FullTextSearch/TESTING.md) is included.

## License

Released under **GPL-2.0-only** — compatible with the MantisBT core. See
[LICENSE](LICENSE) for the full text.
