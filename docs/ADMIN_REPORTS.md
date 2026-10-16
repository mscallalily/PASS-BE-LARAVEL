# Admin Reports & Analytics

Reports are restricted to active administrators. The sidebar exposes Overview plus eight categories: Entry & Exit, Occupancy, RFID & Devices, Users, Vehicles & Drivers, Visitors & QR, Security & Incidents, and System Activity & Audit.

## Data and dates

- All UI/export dates use Asia/Manila; database timestamps remain UTC. Date ranges include the complete final campus day and support 1–366 days per query.
- The current campus snapshot is explicitly independent of historical/search filters. It shares ParkingStats with operations, includes maintenance, and reports whether active stays reconcile with slot assignments.
- Entry/exit rows are transactions with an entry OR exit in the period. Entry charts count only non-cancelled entries inside the period. Exit metrics count completed/force-closed exits inside the period. Duration uses actual valid entry/exit timestamps of those completed stays; no completed sample produces an unavailable average.
- Historical occupancy reconstructs concurrent vehicles from overlapping valid stay intervals, including carry-in stays. Active stays without exits continue through the current time; invalid completed stays without exits are excluded and flagged in Security. Hourly buckets are used for periods up to two days, otherwise daily buckets. Average vehicles is time-weighted; peaks are interval peaks, not sums of entries. Existing historical capacity/maintenance snapshots and physical slot sensors are unavailable. Current area capacity is labeled as a reference only.
- Users are a registration-date cohort with current status and requirement indicators. Rejected accounts use the existing deactivated enum. Vehicles are authorization-request-date cohorts (or registration date if no authorization), with current primary-driver/latest-license indicators. Multiple authorization records can refer to one vehicle; distinct vehicle totals do not count it twice.
- Visitors are selected by scheduled visit start; actual QR-linked stay timestamps are shown separately. Used/revoked/expired pass states are derived from stored records. QR exports use internal references, not usable pass codes.
- RFID offers scans, retained assignment history, and current reader/gate inventory. Dates do not apply to current reader inventory. Stored reader status and last heartbeat are not live connectivity tests; connection interruption/error history is unavailable.
- Security combines denied/manual access events, recorded incidents, and structural transaction conflicts. Active stays are not labeled missing exits just because they have not ended. No automatic override or repair is performed by reports.
- Audit shows only recorded actions. Existing vehicle/RFID/visitor/slot audit history is retained. New registrations, successful login/logout, account approval/rejection and General Settings edits now append audit rows. Historical actions that were never recorded cannot be backfilled.

## Filters and exports

The server applies date, search, category-specific status, user/vehicle type, method and area filters before summaries/charts/pagination. UI changes require Apply filters; exports remain disabled while draft filters differ from the displayed report. Paging never narrows the summary/export dataset. CSV, Excel 2003 XML (not XLSX), and print-friendly HTML include title, period, filters, generation time, operator, summary and all matching rows. Print opens the browser print dialog for printing or Save as PDF. Export requests over 50,000 rows fail explicitly and ask for narrower filters. No 500-row history truncation remains.

CSV formula prefixes are neutralized; Excel cells are literal strings; HTML is escaped. Passwords, token hashes/secrets, full license numbers and document/image paths are excluded or redacted. Report responses and exports are private/no-store. CSV/Excel/print export requires the same admin authorization as viewing.

## Validation

- Added AdminReportsTest: permissions, all categories/datasets, 515-row cross-page exports, Manila midnight boundary and duration, carry-in occupancy integral, filtering/validation, security conflicts, safe exports, and new audit actions.
- Local QA: PHP 8.3 / MariaDB 10.11; owner environment uses PHP 8.2 / MySQL and should run its own full suite after applying.
- Existing System Settings policy previews and logo upload remain outside this change. No database migration or new application dependency is required.
