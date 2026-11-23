# Mise

Recipe costing and stock variance for independent restaurants.
**Price change → re-cost every affected dish → margin alert**, plus **weekly stock count → variance report**.

Built with Laravel 13, Breeze (Blade), Tailwind and MySQL. The full project plan, rules and tickets are in [`CLAUDE.md`](CLAUDE.md).

## What you need (Windows)

| Tool | Check | Version |
| --- | --- | --- |
| PHP | `php -v` | 8.3 or newer (XAMPP's PHP 8.2 is too old: install from [php.new](https://php.new)) |
| Composer | `composer --version` | 2.x |
| Node | `node -v` | 22 or newer |
| Git | `git --version` | any |
| MySQL | XAMPP Control Panel | XAMPP's MySQL is fine |

`where php` must show the new PHP first, not `C:\xampp\php\php.exe`.

## First-time setup

1. Open the **XAMPP Control Panel** and click **Start** next to **MySQL**.
2. Create the database: open http://localhost/phpmyadmin (start **Apache** too for this), click **New**, type `mise` and click **Create**.
   Or, in Command Prompt: `C:\xampp\mysql\bin\mysql -u root -e "CREATE DATABASE mise"`
3. Get the code and install everything:

```bash
git clone https://github.com/rayhanakheireddine2003/mise.git
cd mise
composer install
npm install
copy .env.example .env
php artisan key:generate
php artisan migrate --seed
npm run build
```

The `.env` file already points to XAMPP's MySQL (`root`, no password, database `mise`).

## Every day

1. Start **MySQL** in the XAMPP Control Panel.
2. Terminal 1: `php artisan serve`
3. Terminal 2: `npm run dev`
4. Open http://localhost:8000

After every `git pull`: `composer install`, `npm install`, `php artisan migrate`.

To rebuild the database with fresh demo data: `php artisan migrate:fresh --seed`

## Tests

```bash
php artisan test
```

Tests use a temporary in-memory database, so they never touch your MySQL data. On GitHub, CI runs them twice: on SQLite and on a real MySQL server. Both must be green before a PR is merged.

## Team workflow

- Never push to `main`. One branch per ticket: `feat/MISE-<n>-<short-name>`, then open a pull request.
- One reviewer, CI green, then merge.
- Never commit `.env`, `vendor/` or `node_modules/`.
