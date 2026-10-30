# PASS: institutional ID autofill, email and license registration

Both the React website and Expo mobile app read the institutional ID first, verify the institutional inbox, then read the driver’s license. The ID autofill follow-up corrects the earlier form that required typed names and birth dates. It does not reset existing accounts, vehicle plates, RFID assignments, hardware configurations, or gate history.

## Registration

1. Choose College, Employee or SHS, enter the institutional email and take/upload the front of the DYCI institutional ID. OCR starts automatically. First name, surname, printed middle initial, suffix and student/employee number or LRN are displayed read-only. No name, birth date or ID number can be typed over the result. Blurry/uncertain school-ID results require a new photo. The signed result is bound to the exact file, category and 20-minute expiry and is checked again by the server before starting the draft. It is not an authenticated school-roster record.
2. Complete only information that was not confidently read from the card: own contact number, department/office or SHS grade/section. A printed department is filled only when its exact configured name/code is recognized; a course is never guessed into a department. An emergency contact is not used as the applicant’s contact number. Verify the code delivered to that exact `@dyci.edu.ph` inbox. Gmail, lookalike domains and subdomains are rejected. Inbox control is the affiliation check; the selected category only chooses the card format and regular-user profile. Employee signup never grants parking-staff or administrator permissions.
3. Take or choose a JPG/PNG photo of the license front. Reading starts automatically. The complete printed name, birth date, license number and labeled expiration date must each be extracted confidently without conflicting readings. The fields cannot be edited. Image resolution/sharpness and existing LTO document-layout checks also apply. Printed names and dates need at least 80% field confidence. The license number needs either 80% confidence or the existing corroboration policy across whole-card, broad-region and color-region reads; repeated segmentation of the same preparation is not a vote. Low confidence, blur and ambiguous fields require a retake; confidently mismatched first/surname, printed suffix or middle initial, and expired licenses block registration. The ID may contain only a middle initial; the complete middle name is read from the license and checked against that initial. Birth date is read from the labeled license field, must be in the past, and is never requested as a manual input. A DOB absent from the institutional ID is not described as independently cross-verified. An unavailable reading service remains unapproved and retryable.
4. Review the read-only details, choose a password, consent and create the account. The server rechecks expiry, both private ID/license photo integrity checks, email/ID/license uniqueness and the current session before atomically creating the active account, profile, driver and automatically checked license. College, Employee and SHS use the same policy. Account activation does not automatically approve a vehicle or assign RFID.

No generative AI, new paid API or external license-upload service is added. The existing server-side Tesseract engine reads the image. Passing is an OCR/document-consistency result, not official LTO authenticity verification, a live issuer-status lookup or proof against a forged document. There is no claim of 100% OCR accuracy. Test using the actual card format and phone camera before a demonstration or deployment; uncertain images intentionally cannot proceed.

## Continue on another device

Choose **Continue on another device** during the license step. Scan the QR or share its temporary link to your own phone. The phone opens a small browser capture page; installing the app on the second device is unnecessary. The original registration polls for the photo result, then enables Review after the check passes. Only the original device can create the account or choose the password.

The photo key is scoped to one registration, kept in the URL fragment rather than a query string, removed from the browser address after opening, stored hashed on the server, and invalidated after a passed photo check, cancellation, replacement, or 10-minute expiry. Retakes can use the same unexpired link until a successful check. The one-hour registration draft is stored encrypted; a password is never part of it. Closing/reloading the original registration loses its in-memory owner key: start a fresh registration rather than sharing an account token. Expired sessions cannot approve an account.

For local testing, the phone and laptop must reach the same LAN. Run Laravel on `0.0.0.0:8000`, and open the web app using the laptop Wi-Fi IP and Vite port (normally `http://<laptop-ip>:8444/register`), **not** `localhost`. The website capture link uses that same origin and the existing `/api` proxy. The native link uses the existing `EXPO_PUBLIC_API_URL` and directly opens Laravel's capture page. No frontend-origin env setting is required for this flow. Production capture links must use the deployed HTTPS origin and accessible API routes.

## Installation and service checks

For the original email/license update, run `Update-PassRegistration.cjs`. After that update has been installed, the matching ID-first follow-up is `Update-PassIdAutofill.cjs`, run from PASS-STAGING or PASS-BE-LARAVEL using Node. It verifies reviewed source fingerprints before writing, backs up only the changed source files, checks PHP syntax, installs the matching web/backend/native code and, for the original installer only, runs the additive registration-session migration. The ID autofill follow-up uses the existing session table and adds no migration or npm dependency. It never runs `migrate:fresh`, deletes existing user/vehicle/license records, changes `.env`, runs an npm dependency upgrade, stops the hardware bridge, commits or pushes Git.

The installer has a read-only `--check` mode. A source fingerprint mismatch stops the install; send the exact stop message for a matching patch. Do not bypass this guard by replacing unrelated newer files.

Restart the backend and Expo after installation; refresh the website. Existing dependencies already include the image picker, QR renderer and upload adapter used here. No package.json or lockfile changes are required.

From PASS-BE-LARAVEL:

```powershell
php artisan registration:check
```

This checks the registration table, encryption, PHP image support, Tesseract/English language availability and whether a delivering mail transport is configured. It does **not** send mail or verify SMTP credentials. A real code request to a DYCI test inbox is still required. If OCR needs setup, point the existing `TESSERACT_PATH` setting to `tesseract.exe`; enable PHP GD/fileinfo and install Tesseract's English language data. Do not paste email-provider credentials or APP_KEY into chat. Configure application/PHP request timeouts to allow the bounded OCR check (up to roughly 90 seconds). The flow does not require a queue worker; production PHP workers should support concurrent requests.

Automatic expiry is enforced on every API operation. Cleanup is separate: `registration:prune` removes expired drafts and temporary photos while retaining committed license images and institutional ID images owned by user profiles. Cancelling a draft removes both temporary ID and license photos. It is scheduled hourly in Laravel. Configure the normal Laravel scheduler for deployment; during local prototype work you can run this command manually after testing:

```powershell
php artisan registration:prune
```

Do not publicly link `storage/app/private`. The existing protected license-document endpoint continues to serve committed documents to their owner or authorized staff. Photo captures are not embedded in audit records.

## Validation

The accompanying tests cover real OTP state transitions with fake mail delivery, exact-domain restrictions, hashed owner/capture keys, encrypted drafts, replay and expiry controls, conflicting/low-confidence OCR, name/birth-date mismatches, duplicate credentials, rollback, private file retention, automatic SHS/Employee activation and preservation of separate RFID permissions. The ID follow-up also covers signed school-ID receipts, file/category/expiry binding, read-only identity fields, full-middle-name/initial matching, optional printed profile fields and retention of the institutional ID. Legacy school-ID signup assertions are replaced by the new registration-session tests; remaining ID-reader/profile compatibility tests are retained.

Development validation also exercises real Tesseract with synthetic text images, web DOM interaction flows, the standalone phone page and Expo Android export. Synthetic images and mocked-mail tests do not establish actual institutional email delivery or recognition accuracy on every real license photo.
