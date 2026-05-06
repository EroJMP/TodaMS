# TodaMS

TodaMS is a role-based transportation management system starter project built with PHP, HTML, CSS, and JavaScript.

## Local Setup (XAMPP + phpMyAdmin)
1. Copy this project to `C:\xampp\htdocs\TodaMS`.
2. Start **Apache** and **MySQL** in XAMPP Control Panel.
3. Open `http://localhost/phpmyadmin` (database will now be auto-created as `todams` on first app run).
4. Create .env file then Copy `.env.example` to `.env` and adjust values if needed.
5. Open `http://localhost/TodaMS/public`.

## Database Bootstrap (Automatic)
- On app startup, TodaMS now:
  - Creates the `todams` database if missing
  - Creates required tables run sql query from `database/schema.sql`
  - Seeds default role accounts in `users`
  - Imports initial driver/member records from `public/assets/data/OTODA Member List.xlsm` into `members` (one-time, if members table is empty)
  - Auto-creates missing `driver` user accounts from imported members
    - Username pattern: normalized member name (with numeric suffix if needed)
    - Default password pattern: `driver####` (member id, zero-padded)
  - Seeds default TODA fee policies (`fee_rules`) and penalty policies (`penalty_rules`)
  - Auto-generates penalty billing in `payments` when violations are approved and matched to penalty rules
  - Auto-generates recurring fee billing (monthly dues, terminal fee, yearly membership renewal) for active drivers
  - Supports Treasurer-triggered activity contribution billing for all active drivers

## Demo Accounts
- `admin / admin123`
- `vp / vp123`
- `secretary / secretary123`
- `treasurer / treasurer123`
- `compliance / compliance123`
- `driver / driver1`
-  other driver (`if their fullname format is like Kent O. Layko the username and password will be kent.o.layko`)

## Note for presentation
- Drop your database and create a database again to restart data which is ready for testing/presentation
- Do not 
