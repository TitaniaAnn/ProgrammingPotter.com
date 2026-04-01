---
description: "Use when editing admin pages, admin CRUD flows, or admin access control. Covers login guard placement, flash messages, and shared admin layout patterns."
name: "Admin Pages"
applyTo: "public/admin/**/*.php"
---
# Admin Pages Guidelines

- Call Auth::requireLogin() near the top of each admin page controller before page logic.
- Reuse existing flash helpers from includes/bootstrap.php: flash() and getFlash(). Do not introduce ad-hoc session keys for user feedback.
- Keep page controllers procedural and minimal, aligned with existing admin files in public/admin.
- Use parameterized DB access through Database helper methods; never concatenate untrusted input into SQL.
- Keep output escaped by default with e() unless raw HTML is explicitly intended.
- Reuse shared admin partials and assets where available, including public/admin/partials/sidebar.php and public/admin/partials/topbar.php.
- Follow existing upload constraints/constants from config/config.php and includes/ImageUpload.php when admin actions include file uploads.
- For auth behavior and source-of-truth implementation details, refer to includes/Auth.php.
