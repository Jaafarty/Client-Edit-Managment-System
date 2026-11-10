# Client Edit Management System

- `backend/` contains Laravel, database migrations, routes, and PHP tests.
- `frontend/` contains the React pages, components, styles, and Vite configuration.

Laravel and React are connected through Inertia. Laravel serves the application at [localhost:8000](http://localhost:8000), and Vite serves the frontend assets during development.

`backend/routes/web.php` maps URLs to controller methods. `backend/app/Http/Controllers/HomeController.php` handles `/` and renders `frontend/resources/js/pages/home.tsx`.

## Local development

From the project root, run the following in PowerShell. Use `composer setup` for a fresh clone.

```powershell
cd backend
composer setup
composer run dev
```

To run the servers in separate terminals, run `php artisan serve` in `backend/` and `npm.cmd run dev` in `frontend/`.

## MySQL

The local configuration points to the `clientedit` database on `127.0.0.1:3306`. Create that database and set `DB_USERNAME` and `DB_PASSWORD` in `backend/.env` to match your MySQL account. Laravel uses files for local sessions and cache.

Add your own migrations under `backend/database/migrations`, then run `php artisan migrate` from `backend/`.

## Admin sign-in

From `backend/`, apply the migrations and create your admin account.

```powershell
php artisan migrate
php artisan admin:create
```

Enter your admin email and a password of at least 12 characters. The command prompts for the password twice and stores its hash. Sign in at [localhost:8000/login](http://localhost:8000/login).
