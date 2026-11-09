# Client Edit Management System

- `backend/` contains Laravel, database migrations, routes, and PHP tests.
- `frontend/` contains the React pages, components, styles, and Vite configuration.

Laravel and React are connected through Inertia. Laravel serves the application at [localhost:8000](http://localhost:8000), and Vite serves the frontend assets during development.

## Local development

From the project root, run the following in PowerShell. Use `composer setup` for a fresh clone.

```powershell
cd backend
composer setup
composer run dev
```

To run the servers in separate terminals, run `php artisan serve` in `backend/` and `npm.cmd run dev` in `frontend/`. Run migrations with `php artisan migrate` in `backend/`.

Laravel reads the database settings from `backend/.env`. Fresh installs use SQLite by default. To use MySQL, create a local database, set the `DB_*` values in `backend/.env`, and run the migrations.
