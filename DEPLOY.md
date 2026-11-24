# Deploying CashIko

Laravel 13 + Inertia 2 + Vue (VILT). It runs as a long-lived PHP process, which
is why it is hosted on Render rather than a serverless platform: the app needs a
writable filesystem, a queue worker when reminders ship, and a scheduler for
trial expiry.

| Piece | Where |
| :--- | :--- |
| App | Render web service (Docker, PHP 8.3-FPM + nginx) |
| Database | Supabase Postgres — session pooler, port 5432 |
| Uploads (product photos, payment QR) | Supabase Storage, private bucket, S3 protocol |
| Sessions / cache | Postgres (`sessions`, `cache` tables) |
| Queue | `sync` — the app dispatches no jobs today |
| Marketing site | Vercel (`CashIko-Web`) — separate project |

## Files and why they exist

| File | Purpose |
| :--- | :--- |
| `Dockerfile` | Two stages: Node builds the Vite bundle, PHP-FPM + nginx runs the app with production dependencies only |
| `.dockerignore` | Keeps `.env`, `vendor`, `node_modules`, local SQLite and logs out of the image |
| `render.yaml` | Blueprint: web service, health check, pre-deploy migration, environment variables |
| `.env.production.example` | The full variable list, with secrets marked |
| `config/filesystems.php` | The `private` disk switches to S3 when `PRIVATE_DISK_DRIVER=s3`; controllers are unchanged |
| `bootstrap/app.php` | `trustProxies(at: '*')` so HTTPS behind Render's proxy produces correct URLs and secure cookies |

## One-time setup

1. **Supabase** — create a project, then:
   - a private bucket named `cashiko-private` (Storage → New bucket, public **off**);
   - S3 access keys (Storage → S3 access keys);
   - the session-pooler connection string: **Connect** button in the project top
     bar → **Session pooler**. If only Direct and Transaction appear, take the
     Transaction URI and change `:6543` → `:5432`. Copy the host — the pooler
     index (`aws-0`, `aws-1`) is not derived from the region.
2. **Render** — New → Blueprint → select this repo. Render reads `render.yaml`
   and prompts for every `sync: false` value. Paste from
   `.env.production.example`.
3. **Migrations** — pre-deploy commands are a paid-plan feature, so on the free
   plan run them once from your machine:

   ```powershell
   $env:DB_CONNECTION="pgsql"; $env:DB_URL="postgresql://postgres.<ref>:<pw>@aws-0-<region>.pooler.supabase.com:5432/postgres"
   $env:DB_SSLMODE="require"
   php -d extension=pdo_pgsql artisan migrate --force
   ```

   Re-run it whenever a migration is added. Switch `plan` to `starter` and
   uncomment `preDeployCommand` in `render.yaml` to automate this.
4. **First boot** — open the Render URL, register an account, and complete
   onboarding. The first request after 15 idle minutes takes ~1 minute while
   the free instance wakes.

## Free plan: what you actually get

| | Free ($0) | Starter ($7) |
| :--- | :--- | :--- |
| RAM / CPU | 512 MB / 0.1 shared | 512 MB / 0.5 |
| Idle behaviour | Spins down after 15 min, ~1 min wake | Always on |
| Pre-deploy migrations | ✗ (run manually) | ✓ |
| Background worker / cron | ✗ | ✓ |
| Persistent disk | ✗ (not needed — uploads go to Supabase) | $0.25/GB |
| SSH / one-off jobs | ✗ | ✓ |
| Instance hours | 750/month | unlimited |
| Restarts | Render may restart at any time | — |

Render's own docs say the free tier is "for testing out a new technology,
working on a hobby project, or previewing Render's developer experience" and
"do not use them for production applications." That is accurate: a POS app that
takes a minute to wake is not a POS app. Use free to validate the whole path,
then upgrade — it is one line in `render.yaml`.

## Local Docker build (optional)

```powershell
docker build -t cashiko .
docker run --rm -p 8080:8080 --env-file .env cashiko
```

Without Docker locally, the Render build log is the verification: a failed
Composer install, Vite build or `package:discover` shows up there before the
service goes live.

## Recovering a locked account

Render defines no `MAIL_*` variables, so `config/mail.php` falls back to
`MAIL_MAILER=log`: `/forgot-password` accepts the request and writes the reset
mail into the container log, where nobody will read it. Until SMTP is wired up
(Resend or Brevo free tier), unlock an account from the shell:

1. Render dashboard → the `cashiko` service → **Shell**.
2. Run:

   ```bash
   php artisan cashiko:reset-password you@example.com
   ```

3. Type the new password twice at the hidden prompts (8 characters minimum).
   The command hashes it, rotates `remember_token` so other devices are signed
   out, and prints the account it updated.

The command refuses unknown addresses, short passwords and mismatched
confirmations, and reads the password from a prompt rather than an argument, so
it never lands in shell history or the Render event log. Existing passwords are
bcrypt hashes in `users.password` — one-way, so they can be replaced but never
read back.

## Before real traffic

- **PHP 8.3 leaves security support in December 2026.** Bump the base image to
  `serversideup/php:8.4-fpm-nginx` and run `php artisan test` on PHP 8.4 first.
- **`APP_URL` must match the real domain**, or signed photo URLs and redirects
  point at the wrong host.
- **The `private` bucket must stay private.** `PhotoController` serves files
  through a signed route; a public bucket would expose every product photo.
- **Enable the worker and cron blocks** in `render.yaml` before shipping
  reminders or trial expiry. `QUEUE_CONNECTION=sync` runs jobs inside the
  request, so a slow reminder send becomes a slow page.
- **Add a database backup plan.** Supabase free tier keeps daily backups for
  seven days; confirm that is enough before charging for the product.

## Why not Vercel

Tried and rejected. Vercel's filesystem is read-only and per-instance, there is
no queue worker or scheduler, requests cap at 60 s, uploads at 4.5 MB, and the
community PHP runtime is archived upstream. Hobby is also non-commercial only,
and CashIko charges money. Render costs about the same ($7/mo Starter) and has
none of those constraints.
