# Ajaia Docs

A small document workspace built with Laravel 13, Vue 3, and SQLite. It supports persistent rich-text documents, plain-text/Markdown imports, and a simple owner/collaborator sharing model.

## Local setup

Requirements: PHP 8.3+, Composer, and Node.js 20+.

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
npm install
npm run build
php artisan serve
```

On Windows Command Prompt, use `copy .env.example .env` instead of `cp`.

Open `http://127.0.0.1:8000`. Use the user selector in the header to switch between the three seeded accounts and demonstrate sharing.

## Development mode

Run the Laravel server and Vite development server in separate terminals:

```bash
php artisan serve
npm run dev
```

To reset demo data, run `php artisan migrate:fresh --seed`.

## Features and constraints

- Create, rename, edit, save, and reopen rich-text documents.
- Bold, italic, underline, headings, and ordered/unordered lists.
- Import `.txt` or `.md` files up to 2 MB. Imports preserve the source as safe plain text; Markdown syntax is not rendered.
- Owners can grant edit access to another seeded user by email.
- SQLite persists HTML content and collaborator relationships across refreshes.
- Mock user switching intentionally replaces authentication for this scoped demo.

## Tests

```bash
php artisan test
npm run build
```

The feature test covers the key sharing path, collaborator editing, and rejection of unshared users.

## Supporting notes

- [Architecture](ARCHITECTURE.md)
- [AI workflow](AI_WORKFLOW.md)
- [Submission inventory](SUBMISSION.md)

## Deployment

The repository is ready for any PHP 8.3 host (Laravel Cloud, Railway, Render, or a VPS): set `APP_KEY`, `APP_URL`, `APP_ENV=production`, `APP_DEBUG=false`, and a persistent database, then run `php artisan migrate --force` and `npm run build` during deployment. Ensure `storage/` and `bootstrap/cache/` are writable.

This checkout does not include hosting credentials or a public deployment URL. A live deployment therefore needs the reviewer's chosen host connected to this repository; SQLite should be placed on a persistent volume, or `DB_CONNECTION` changed to Postgres.

# Work-thru Video

https://drive.google.com/file/d/1L_EFus31Ql-0XdBN4bEh-PDRk2NUsf9K/view?usp=sharing
