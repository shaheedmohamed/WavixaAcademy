# Wavixa Academy — New Server Setup

Laravel + Vue e-learning app at `/var/www/WavixaAcademy`, own DB
(`wavixa_academy`), own subdomain `academy.wavixa.net`.
Independent of the main Wavixa app (it only calls `wavixa.net/api` for
WhatsApp notifications via `WAVIXA_API_*`).

## Production topology

| Piece | Source dir | Runs as | Path |
|---|---|---|---|
| Academy Laravel API | `backend/` | php8.5-fpm via nginx | `academy.wavixa.net/api/*`, `/sanctum/*` |
| Academy SPA | `frontend/` build → `backend/public/` | static | `academy.wavixa.net/*` |
| Queue worker | `backend/` | `academy-queue.service` (redis) | — |
| Protected videos | `backend/storage/app/private/videos/` | nginx `internal` X-Accel | `/protected-videos/` |
| `academy_migration/` | migration artifacts (deploy scripts) | — | — |

## 1. System packages

Same stack as the main app (see `/var/www/Wavixa/SERVER_SETUP.md`):
nginx, MySQL 8, redis, php8.5-fpm + extensions, Node 24, Composer, certbot,
ffmpeg (video processing).

## 2. Deploy code

```bash
cd /var/www && git clone git@github.com:shaheedmohamed/WavixaAcademy.git WavixaAcademy
```

## 3. MySQL

```sql
CREATE DATABASE wavixa_academy CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'wavixa_academy'@'127.0.0.1' IDENTIFIED BY '7574e3bdd6738618685265ac9b233a0c';
CREATE USER 'wavixa_academy'@'localhost' IDENTIFIED BY '7574e3bdd6738618685265ac9b233a0c';
GRANT ALL ON wavixa_academy.* TO 'wavixa_academy'@'127.0.0.1';
GRANT ALL ON wavixa_academy.* TO 'wavixa_academy'@'localhost';
FLUSH PRIVILEGES;
```

## 4. Laravel backend

```bash
cd /var/www/WavixaAcademy/backend
composer install --no-dev --optimize-autoloader
cp .env.example .env            # real production values already inside
php artisan migrate --force
php artisan storage:link
php artisan config:cache
chown -R www-data:www-data storage bootstrap/cache public
```

Note: queue + cache run on **Redis** (`QUEUE_CONNECTION=redis`,
`CACHE_STORE=redis`, DBs 1/2 — do not collide with the main app's DB 0).
Sessions are encrypted (`SESSION_ENCRYPT=true`) and scoped to
`academy.wavixa.net`.

## 5. Frontend

```bash
cd /var/www/WavixaAcademy/frontend
npm ci
cp .env.example .env.production   # VITE_API_BASE_URL=/api
npm run build
cp -r dist/* /var/www/WavixaAcademy/backend/public/
chown -R www-data:www-data /var/www/WavixaAcademy/backend/public
```

## 6. systemd — academy-queue.service

```ini
[Unit]
Description=Wavixa Academy Queue Worker
After=network.target mysql.service redis-server.service

[Service]
User=www-data
Group=www-data
Restart=always
RestartSec=5
ExecStart=/usr/bin/php /var/www/WavixaAcademy/backend/artisan queue:work redis --sleep=3 --tries=3 --max-time=3600 --timeout=120
WorkingDirectory=/var/www/WavixaAcademy/backend

[Install]
WantedBy=multi-user.target
```

```bash
systemctl daemon-reload && systemctl enable --now academy-queue
```

## 7. Crontab (root)

```cron
* * * * * cd /var/www/WavixaAcademy/backend && php artisan schedule:run >> /dev/null 2>&1
```

## 8. Nginx — `/etc/nginx/sites-available/academy.wavixa.net.conf`

```nginx
server {
    server_name academy.wavixa.net;

    root /var/www/WavixaAcademy/backend/public;
    index index.html index.php;

    client_max_body_size 4096m;
    charset utf-8;

    location / {
        try_files $uri $uri/ /index.html;
    }

    # Internal-only delivery of HLS video files — PHP authorizes each
    # request via X-Accel-Redirect (CourseShield session check).
    location /protected-videos/ {
        internal;
        alias /var/www/WavixaAcademy/backend/storage/app/private/videos/;
    }

    # Laravel API + Sanctum CSRF endpoint
    location ~ ^/(api|sanctum)(/|$) {
        fastcgi_read_timeout 600s;
        try_files $uri $uri/ /index.php$is_args$args;
    }

    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/run/php/php8.5-fpm.sock;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }

    listen 80;
    listen [::]:80;
}
```

SSL: `certbot --nginx -d academy.wavixa.net` (after DNS points here).

## 9. Things NOT in git — copy manually or redo

- `backend/storage/app/` user uploads + `storage/app/private/videos/` (~105 MB)
  — rsync from the old server (CourseShield needs the HLS files for
  `protected-videos` delivery).
- `academy_migration/database/academy_db.sql.gz` — DB dump kept out of the
  repo; import into `wavixa_academy` if doing a data migration.
- Mux / Google Drive env vars are **empty** — fill them in `.env` if video
  upload/streaming via Mux is needed on the new box.

## 10. Verify

```bash
systemctl status academy-queue php8.5-fpm nginx mysql redis-server
curl -s https://academy.wavixa.net/api/ -o /dev/null -w '%{http_code}\n'
```
