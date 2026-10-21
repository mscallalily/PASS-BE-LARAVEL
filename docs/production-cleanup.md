# Current-source production cleanup

The 20 October uploaded source was used directly, including the owner's existing authentication/audit changes. No old AuthContext replacement or regex installer was applied.

Fixed:
- Removed the unused manual-password Add User handler. The visible invitation flow remains the only frontend account-creation form.
- Closed the legacy `/api/admin/users` backend creation bypass. Both creation routes require generated credentials, email activation, password setup and the same invitation throttle.
- Removed preview-only System Settings sections and the unfinished logo endpoint. General information still validates, saves, cancels and restores from the database. The roles matrix is labelled as a read-only reference.
- Removed unused mock/native-preview pages and replaced mobile-app marketing with actual browser access.
- Replaced the temporary-state RFID request demo with the owner's stored RFID assignment status. Admin assignment and access checks remain real APIs. RFID hardware is still deferred.
- Removed production Figma/Vite replay injection and debug dashboard logging; added explicit Vercel API-first routing and private-page headers.
- Defaulted read-only parking maps to the SVG plan and deferred the 3D renderer. Original proposed geometry, inventory, measurements, entry/exit pathways and sensor caveats are unchanged.
- Prepared Railpack OCR packages, PHP upload/error settings, durable-storage checks, a safe Laravel startup, and reference-only initialization. No demo users are seeded by this deployment profile.

Validation and its limits are recorded in `production-cleanup-evidence.json`. Deployment variables, mail provider choice, owner import checks and remaining public-launch gates are in `production-deployment.md`.

This patch does not configure an external account, send a real email, move production data, push GitHub commits or deploy a site. It does not claim that OCR proves official affiliation/license authenticity, that physical slot sensors are connected, or that the free hosting allowance is enough for production load.
