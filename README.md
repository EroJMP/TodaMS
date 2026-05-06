# TodaMS

TodaMS is a role-based transportation management system starter project built with PHP, HTML, CSS, and JavaScript.

## Local Setup (XAMPP + phpMyAdmin)
1. Copy this project to `C:\xampp\htdocs\TodaMS`.
2. Start **Apache** and **MySQL** in XAMPP Control Panel.
3. Create a database in `http://localhost/phpmyadmin` named `todams`.
4. Copy `.env.example` to `.env` and adjust values if needed.
5. Open `http://localhost/TodaMS/public`.

## Phase 1 Included
- Project folder structure and base routing
- Auth pages and session-based login starter
- Role matrix configuration
- Base layout, CSS, and shared JS

## Phase 2 Included
- **Members:** Secretary encoding + VP approve/reject flow
- **Violations:** Driver submit -> Secretary encode -> Compliance validate -> VP approve/reject
- **Payments:** Treasurer creates billing and verifies payment status
- **Notifications:** Role-based workflow notifications

## Demo Accounts
- `admin / admin123`
- `vp / vp123`
- `secretary / secretary123`
- `treasurer / treasurer123`
- `compliance / compliance123`
- `driver / driver123`
