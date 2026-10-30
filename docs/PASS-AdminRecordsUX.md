# PASS admin audit and records experience

This update changes how existing activity and retained revisions are presented. It does not migrate, update, delete, or relabel stored audit rows. It does not push or deploy the repositories.

## Audit Logs

- Ordinary action names such as `login`, `rfid_assigned`, and `account_auto_approved` are Completed. Submissions and unfamiliar action names are Recorded by default. A completed administrative rejection, suspension, or archive is a successful action, not a failed system operation; the actual decision remains visible in the activity and field changes.
- Access-denied events, actual failed actions, and explicit operational issues retain distinct results. These results describe the event. Priority review separately identifies repeated patterns or integrity problems using the existing backend rules.
- Priority review is outside the Refresh button. Empty severity levels no longer appear as colored warning cards. Needs review and Reviewed have separate tabs. Reviewing an alert preserves evidence and does not automatically resolve its condition.
- Search, module, result and campus-date filters apply on the server across all pages. Result counts follow the current search/module/date range. A date-to-only search works. Actor names are shown where a current profile exists; the originally recorded actor code stays available.
- Details show changed fields and their previous/updated values. Audit IDs, raw action codes, device and IP references remain available in an expandable section. CSV export explicitly exports the current page and guards spreadsheet formula prefixes.
- This package is matched to the uploaded PASS-Admin-Latest.zip. It preserves the current Philippine-time helpers: export filenames use the campus calendar date, and retained timestamps (including nested values and validity timestamps) display in Philippine time. Calendar-only dates stay unchanged. Existing datetime helpers and hardware files are not replaced.

## Records & History

- Accounts, Vehicles and Change history have local navigation. Accounts and Vehicles start with active records and offer an explicit Archived view with matching counts. Existing APIs keep their previous default when no state is supplied.
- Archive/restore still requires a reason. Existing server permissions, inside-vehicle restrictions, administrator protection and fresh-approval/revoked-credential rules remain enforced.
- Change history starts with business records and omits installation snapshots. Both are display filters: All retained records and Include initial snapshots reveal the retained data. Direct revision links remain visible even when their revision is a baseline or a system record.
- Search uses a fixed allowlist of name/email/plate/account/slot fields, rather than searching hidden credentials or raw document paths. Before/after comparisons are readable field tables. Retained documents stay behind their existing admin-only download endpoint; filesystem paths and credentials are excluded from detail payloads.

## Apply

From the PASS-STAGING parent, backend or frontend folder run:

    node "$env:USERPROFILE\Downloads\Update-PassAdminRecords.cjs"

The updater checks expected source versions and PHP syntax before writing. It saves original files and a hash manifest in PASS-Admin-Backups. Unknown local changes stop the update before source files are written. `--check` is a read-only preflight. Running the update again is safe.

Refresh the local development browser with Ctrl+Shift+R. If serving built assets rather than Vite development, build the frontend through the existing deployment process. No database migration is required for this update; the previously installed record-history tables/endpoints are prerequisites.

## Validation

- New AuditExperienceTest plus existing AuditPriorityTest and RecordRetentionTest: 22 tests, 188 assertions on an isolated MySQL-compatible test database.
- Frontend presentation tests: 6 passed, including Philippine midnight and nested timestamp cases. Production build and targeted TypeScript checks passed against the uploaded current source.
- Installer checks cover preflight, existing source drift, backups, idempotence, Windows line endings and rollback.
- A live browser layout pass could not run in the editing environment. Check the installed desktop/mobile layout, record detail dialogs and priority-review controls locally before deployment.
