# AGENTS.md

This file provides guidance to WARP (warp.dev) when working with code in this repository.

## Project snapshot
- Stack: plain PHP + MySQL (PDO), server-rendered views, vanilla JS on top of NiceAdmin assets.
- Entrypoint: `index.php` (front controller); URL rewriting is handled by `.htaccess`.
- DB connection/runtime constants are in `config/db.php` and `config/env.php`.
- There is no root `composer.json` or app-level `package.json`; vendor assets are committed under `assets/vendor/`.

## Common development commands
Run commands from repository root.

### Database setup / updates
```bash
mysql -u root -p -e "CREATE DATABASE IF NOT EXISTS diemdanh_literature CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
mysql -u root -p diemdanh_literature < database/diemdanh_literature.sql
```

Incremental SQL patches in this repo (apply as needed):
```bash
mysql -u root -p diemdanh_literature < database/add_accuracy_score.sql
mysql -u root -p diemdanh_literature < database/add_tracking_columns.sql
mysql -u root -p diemdanh_literature < database/update_comment_table.sql
mysql -u root -p diemdanh_literature < database/update_peer_evaluation.sql
```

### Run locally
- App base URL is configured as `http://literaturever2.test` in `config/env.php`.
- Typical local run mode is Apache/Nginx via local dev stack (rewrite rules in `.htaccess` route all requests to `index.php?route=...`).

### Validation (no formal test runner in repo)
No PHPUnit/Pest/PHPStan/ESLint config exists at project root. Use syntax checks + focused manual verification:

Single-file PHP syntax check:
```bash
php -l api/manage_tasks.php
```

Syntax check all first-party PHP files:
```bash
Get-ChildItem -Recurse -File -Include *.php | ForEach-Object { php -l $_.FullName }
```

Useful targeted scripts for local data/debug workflows:
```bash
php test_seed.php
php assign_all.php
php teacher_seed.php
php seed_evaluations.php
```

## Architecture and code structure
## 1) Request routing and page composition
- `index.php` maps `route` strings to concrete files in `views/` and `api/`, then enforces auth per route config.
- `.htaccess` rewrites non-static paths to `index.php?route=<path>`.
- Most pages include shared layout pieces from `views/templates/` (`header.php`, role-based `sidebar*.php`, `vendors.php`, `footer.php`).

## 2) API layer pattern
- API handlers live in `api/*.php` and are action-based (usually `$_POST['action']` switch blocks).
- Common pattern: start output buffer, enforce JSON header, validate login/role, then return JSON (`success`, `message`, payload).
- Business logic should stay in helper modules under `config/helper_*.php`; APIs mostly orchestrate auth/validation/response shape.

## 3) Domain model (big picture)
Core hierarchy:
- `class` / `class_member` / `group` / `group_member`
- `topic` (belongs to class)
- `phase` (belongs to topic, references `main_phase`)
- `task` (belongs to phase)
- `deliver` (assignment target: class/group/user for a task)
- `response` + `content` (student submission data, structured content blocks)
- `comment` (threaded feedback on content/general response)

Evaluation subsystem:
- Criteria: `nhom_tc` (criteria groups), `tieu_chi` (criteria items), `task_evaluate` (task → criteria group binding)
- Review assignment: `deliver_evaluate` (which response a deliver reviews)
- Scoring: `evaluate`, `detail_evaluate`, `score`, `score_detail`, `total_score`, `result_evaluate`
- Peer-evaluation phase identity comes from `main_phase.phase_code = 'PHASE_DANHGIANGUOIHOC'` (see docs and evaluate APIs/views).

Reflect/support subsystem:
- `reflect_ticket`, `reflect_detail`, `reflect_type` power support/feedback flow.

## 4) Frontend behavior and i18n
- JS is loaded from `views/templates/vendors.php`.
- Main UI behaviors: `assets/js/main.js`.
- Translation system:
  - dictionary in `assets/js/translations.js`
  - runtime switcher in `assets/js/language-switcher.js`
  - text keys attached via `data-lang-key` in templates/views.

## 5) Important implementation conventions
- Session usage is mixed across files: both `$_SESSION['user']` and top-level `$_SESSION['user_id']`/`$_SESSION['role']` are used. Preserve compatibility when refactoring auth/session code.
- Task file content is persisted under `uploads/tasks/` and referenced by relative path in DB (see `api/manage_tasks.php` + `config/helper_task.php`).
- Permission checks are class/topic aware and frequently rely on `isTeacherInTopicClass(...)` or `getRoleInClass(...)`.
- `docs/` contains feature-level operational notes for Admin, language switching, and peer evaluation; treat these as intent docs for current behavior.
- Some docs mention `database/migrations/...`, but SQL files currently live directly in `database/`.
