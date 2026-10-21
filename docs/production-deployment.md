# PASS deployment profile — 20 October 2026

This is a source cleanup and deployment configuration, not a claim that a public launch has passed acceptance. It targets the exact frontend/backend ZIP exports supplied on 20 October. Hardware and the native mobile application remain separate work.

## Hosting and mail decision

Try Railway Free first for a small demo. It currently includes $1 of credit/month and limits each service to 512 MB RAM and 1 vCPU. That is not a guarantee that Laravel, MySQL and OCR fit, stay online all month, or handle simultaneous uploads. Measure memory, OCR latency and credit consumption before deciding whether to upgrade. Hobby can also run this stack and includes $5 of usage for $5/month, with extra usage billed. Pro is not required for OCR.

Railway disables outbound SMTP on Free, Trial and Hobby. Use an HTTPS transactional mail API. The uploaded backend already includes the Resend Laravel transport and SDK. Its free transactional plan allows 3,000 emails/month and 100/day, but sending to real users requires a verified domain with DNS access. Its test domain is limited to the account owner's address.

Brevo offers 300 free sends/day and can replace a verified Gmail sender with its own compliant sender address. It requires a new Laravel API transport; setting `MAIL_MAILER=brevo` alone WILL NOT work in this package. No Brevo account, sender, API key or delivery has been configured by this cleanup. Choose the provider before deployment. Sender verification/account activation and an actual inbox test remain necessary.

Official references checked 20 October 2026:
- https://docs.railway.com/networking/outbound-networking
- https://docs.railway.com/pricing/plans
- https://resend.com/pricing
- https://resend.com/docs/api-reference/errors
- https://help.brevo.com/hc/en-us/articles/208580669
- https://help.brevo.com/hc/en-us/articles/14925263522578

## Backend on Railway

Use the PHP Railpack builder. `railway.json` starts `bash deploy/start.sh` and health-checks `/up`. Remove any old custom NPM build command from the backend service. The unused Laravel Vite/Node scaffold has been removed; React builds separately on Vercel.

`railpack.json` installs `tesseract-ocr`, English language data and `poppler-utils`. The latter extracts existing PDF text; this does not claim OCR support for every scanned/image-only PDF. `php.ini` disables displayed PHP errors and permits the application's document upload sizes.

Configure Railway variables privately, never in frontend `VITE_*` variables or Git:

| Variable | Value |
|---|---|
| APP_ENV | production |
| APP_DEBUG | false |
| APP_KEY | Preserve the existing valid key; generate once only for a new installation |
| APP_NAME | PASS |
| APP_URL | Your actual HTTPS Railway backend origin, without /api |
| FRONTEND_URL | Your actual HTTPS Vercel/custom frontend origin |
| DB_CONNECTION | mysql |
| DB_HOST / DB_PORT / DB_DATABASE / DB_USERNAME / DB_PASSWORD | Reference your Railway MySQL service variables |
| CACHE_STORE | file |
| SESSION_DRIVER | file |
| SESSION_SECURE_COOKIE | true |
| QUEUE_CONNECTION | sync |
| LOG_CHANNEL | stderr |
| LOG_LEVEL | warning |
| PASS_SIMULATOR_ENABLED | false |
| TESSERACT_PATH | tesseract |
| PDFTOTEXT_PATH | pdftotext |
| RAILPACK_PHP_EXTENSIONS | gd,exif,pdo_mysql,mbstring,fileinfo,openssl |
| MAIL_MAILER | resend (only after choosing/configuring Resend) |
| RESEND_KEY | Private Resend API key |
| MAIL_FROM_ADDRESS | Sender using your verified domain |
| MAIL_FROM_NAME | PASS |

The initial single-service profile requires sync jobs. Do not switch to database/Redis queues until a working queue worker is deployed and monitored, or license checks can remain in processing. Sync OCR also consumes HTTP worker time; shared-campus load has not been certified.

Attach a persistent Railway volume at **`/app/storage`** before the first production start. Railway supplies `RAILWAY_VOLUME_MOUNT_PATH` automatically; do not fake that variable to bypass the check. Configure backups for BOTH MySQL and private documents, and verify a restore. An ordinary container filesystem is not durable across redeploys.

On startup the script creates required writable directories, clears stale configuration, checks the production configuration, runs normal pending migrations, seeds only empty reference tables, then starts Railpack's generated FrankenPHP server. It never runs `migrate:fresh`, general `DatabaseSeeder`, demo account seeders or test fixtures. Review/back up the existing database before the deployment. Existing table contents, slots and assignments are left unchanged by `ProductionReferenceSeeder`.

A first administrator can be bootstrapped with the existing interactive `php artisan pass:create-admin your-real-email ADM-001`; choose its password privately in the terminal. Do not use public demo credentials. Invite subsequent Admin/Parking Staff accounts through the UI so email activation and mandatory password change apply.

Useful checks in the deployed container:

```sh
php artisan pass:check-production --railway
tesseract --version
tesseract --list-langs
pdftotext -v
php artisan migrate:status
```

A passed configuration check does not prove inbox delivery, API routing, actual OCR accuracy or load capacity. It deliberately prints setting names, not credential values.

Railpack references:
- https://railpack.com/languages/php/
- https://docs.railway.com/config-as-code/reference
- https://docs.railway.com/volumes

## Frontend on Vercel

Use Node 22.12+ (or the existing Node 24 toolchain), `npm ci`, `npm run build`, output `dist`, framework Vite. Use the matching cleanup frontend revision.

`vercel.json` keeps `/api/:path*` before the SPA fallback, targeting the source's existing `https://projectsystem-production.up.railway.app`. Confirm this is YOUR current backend domain before deploying. If different, change this destination explicitly. Vite's development proxy has no effect on Vercel.

The local Vite proxy defaults to `http://127.0.0.1:8000`. If local Laravel uses another port, set `PASS_API_PROXY_TARGET=http://127.0.0.1:8001` in your frontend `.env.local`. Keep your local Windows Tesseract path in the BACKEND `.env`; Railway uses the Linux binary name instead.

HTML/protected pages use no-store responses. Hashed assets remain cacheable. The 3D renderer and vehicle recognition worker are still large; 3D is loaded on demand and signed-in read-only maps default to the SVG plan. This does not pretend a physical slot sensor is connected.

## Owner checks after import

1. Stop the intended Vite server, import both source patches, then restart from these project directories. The Add User button must read **Invite Admin / Staff**; the legacy required-password handler has been removed.
2. Frontend: `npm run test:http`, `npx tsc --noEmit`, `npm run build`.
3. Backend: `php artisan config:clear`, `php artisan route:clear`. Run `php artisan test` ONLY with the already configured separate `_testing` database, its normal migrations and required reference seeds. Do not repoint tests at live data.
4. Verify Settings load/save/cancel/reload, mobile sidebar, student/guest plan and 3D, persisted RFID assignments, and invitation activation/password setup.
5. On the DEPLOYED pair, verify sign-in/refresh/logout, registration/email codes, reset-link origin and one-time reset, both invited roles, document OCR/uploads, vehicle selection, guest schedule/approval/QR, and report/export permissions. Do not use real gate hardware for these software checks.
6. Restart/redeploy the backend; verify existing documents remain private and readable only by the owner and authorized roles.

## Remaining launch gates

- Real mail provider/sender, domain or sender rewriting choice, quota and inbox delivery remain unverified.
- Actual matching Railway/Vercel builds, authenticated smoke tests, durable storage/backup restore and OCR performance remain unverified.
- Shared-campus IP limits and expected concurrent HTTP load need staging measurements; parallel service tests are not throughput certification.
- OCR text matching is not an official school-affiliation or LTO authenticity verification. Confirm the school's accepted approval policy and SHS document rules.
- The supplied registration UI still mentions Terms/Privacy without published school-approved documents. Supply the approved documents/URLs before collecting real user IDs publicly; this package does not invent an approved policy.
- Broader active-account lifecycle/identity correction and rejected-ID resubmission acceptance remains open in the audit tracker. Do not describe those unverified flows as completed.
- Real Android/iOS browser/device acceptance and physical RFID/servo/sensors remain separate. The simulator remains development-only and production-disabled.
