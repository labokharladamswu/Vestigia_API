# Vestigia PHP API

PHP API for the Vestigia Android app, backed by **Supabase** (Postgres + Storage).
Runs locally in **Docker** and deploys to **Vercel**. Config comes from environment variables / `.env`.

```
api/        endpoints (login.php, items_list.php, ...)  -> served at /api/<file>.php
lib/        shared code: env.php, db.php, auth_helper.php, storage.php (not public)
docker/     Apache + PHP config for the container
database/   schema.sql to run once in Supabase
vercel.json Vercel PHP runtime config
```

## 1. Supabase setup (once)

1. Create a project at supabase.com.
2. **SQL Editor** -> paste `database/schema.sql` -> Run.
3. **Storage** -> New bucket -> name `vestigia-uploads` -> toggle **Public bucket** on.
4. **Connect** button (top bar) -> copy the **Transaction pooler** details (host, port 6543, user `postgres.<ref>`).
5. **Project Settings -> API Keys** -> copy the `service_role` / secret key.

## 2. Run with Docker

```bash
cp .env.example .env      # then fill in the values from step 1
docker compose up --build
curl http://localhost:8080/api/health.php
```

A healthy response looks like `{"success":true,...,"pdo_pgsql":true,"database":true}`.

## 3. Push to GitHub

```bash
git init && git add . && git commit -m "Vestigia PHP API"
git branch -M main
git remote add origin https://github.com/<you>/vestigia-api.git
git push -u origin main
```

`.env` is git-ignored. Only `.env.example` (placeholders) is committed.

## 4. Deploy to Vercel

1. vercel.com -> **Add New -> Project** -> import the GitHub repo (Framework Preset: **Other**).
2. Before deploying, add these **Environment Variables** (same names as `.env`):
   `DB_HOST, DB_PORT, DB_NAME, DB_USER, DB_PASS, DB_SSLMODE, SUPABASE_URL, SUPABASE_SERVICE_KEY, SUPABASE_BUCKET`
3. Deploy, then open `https://<your-app>.vercel.app/api/health.php`.

Every push to `main` redeploys automatically.

## 5. Point the Android app at it

In `ApiConfig.java`:

```java
// Vercel
public static final String BASE_URL = "https://<your-app>.vercel.app/api/";
// Android emulator -> Docker on your PC (also add 10.0.2.2 to network_security_config.xml)
// public static final String BASE_URL = "http://10.0.2.2:8080/api/";
// Real phone on same Wi-Fi -> Docker on your PC (add the PC's LAN IP to network_security_config.xml)
// public static final String BASE_URL = "http://192.168.x.x:8080/api/";
```

## What changed vs. the original `php-api`

- `db.php`: hardcoded `localhost/root` credentials replaced by env vars; SSL + pooler-safe PDO options.
- Uploads: no more writing to `uploads/` (Vercel's disk is read-only). Images go to the Supabase Storage bucket; the DB stores the object name and API responses return full public URLs (`image_url`, `photo_url`, and new `proof_photo_url` on pending claims).
- Added `api/health.php`.
- Existing rows with `uploads/xyz.jpg` still resolve if you upload `xyz.jpg` into the bucket.

## Limits & troubleshooting

- Vercel request bodies are capped at ~4.5 MB, so images are limited to 4 MB.
- `Database connection failed` -> check `DB_USER` is `postgres.<project-ref>` (not just `postgres`), the password, and set `APP_DEBUG=true` temporarily for details.
- `could not find driver` / `"pdo_pgsql": false` on Vercel -> the PHP runtime lacks the extension; see the notes in the chat reply.
- Image upload returns 500 -> bucket name mismatch, bucket missing, or wrong `SUPABASE_SERVICE_KEY`.
