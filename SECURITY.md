# Security Policy

## Supported versions

Only the latest release (and the current default branch) receives security fixes.

## Reporting a vulnerability

Please **do not** open a public issue for security problems.
Report them privately via GitHub's private vulnerability reporting
("Security" tab -> "Report a vulnerability") or, if that is not available,
contact the maintainer [@marcwoge](https://github.com/marcwoge) directly.

Please include affected version, steps to reproduce and the impact you expect.
You will get an answer within 14 days. Once a fix is available, the advisory
will be published together with the release notes.

## Scope notes

- The Docker setup in this repository is a **local test sandbox** only. Its ports
  are bound to `127.0.0.1`; credentials come from a local, git-ignored `.env`.
- Run the plugin on a supported MantisBT release (currently **2.28.4 or newer**,
  which fixes CVE-2026-47156) and a PHP version with security support.
