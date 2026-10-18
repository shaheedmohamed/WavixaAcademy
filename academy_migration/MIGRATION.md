# Wavixa Academy — Migration Package

Contents:
- `project.tar.gz`  — full project source (Laravel `backend/` + Vite `frontend/`, vendor included, node_modules excluded)
- `database/academy_db.sql.gz` — MySQL dump of `wavixa_academy`
- `deploy/nginx-academy.conf` — nginx vhost used on the old server
- `deploy/academy-queue.service` — systemd queue worker unit
- `deploy/crontab.txt` — cron entry for Laravel scheduler

## Stack requirements
- PHP 8.5 + php-fpm (cli NTS used on old server), Composer
- MySQL/MariaDB, Redis, Nginx, Certbot (or any SSL)
- Node.js only if rebuilding the frontend (`dist` build already inside `backend/public`)

## Setup on the new server
1. Extract `project.tar.gz` into the web root, e.g. `/www/wwwroot/academy.wavixa.net`
   (keep the `academy.wavixa.net/` top-level folder or rename — update paths accordingly).
2. Create MySQL DB + user:
   CREATE DATABASE wavixa_academy CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   CREATE USER 'wavixa_academy'@'127.0.0.1' IDENTIFIED BY '<new-password>';
   GRANT ALL ON wavixa_academy.* TO 'wavixa_academy'@'127.0.0.1';
   gunzip < database/academy_db.sql.gz | mysql -u wavixa_academy -p wavixa_academy
3. Edit `backend/.env`: DB credentials (if changed), APP_URL / SESSION_DOMAIN /
   SANCTUM_STATEFUL_DOMAINS / APP_FRONTEND_URL if the domain changes.
4. `chown -R www-data:www-data backend/storage backend/public` (or the web user).
5. Copy `deploy/academy-queue.service` to `/etc/systemd/system/`, fix paths if the
   install dir differs, then `systemctl daemon-reload && systemctl enable --now academy-queue`.
6. Add the cron line from `deploy/crontab.txt` (fix path if needed).
7. Install `deploy/nginx-academy.conf` as the site vhost; update `root`, `alias`,
   `server_name`, and `fastcgi_pass` socket to match the new server, then issue SSL.
8. `cd backend && php artisan config:cache && php artisan storage:link` (if `public/storage` missing).

Done — the site should work exactly as on the old server.
