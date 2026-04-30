# BRACU Student Freelance Marketplace

A PHP + MySQL implementation of a BRAC University freelance marketplace where each user can act as both:
- Client (post gigs, track statuses, mark done)
- Freelancer (browse listed gigs, accept work)

The app includes e-wallet transfer logic where credit moves from client to freelancer when a pending gig is marked done.

## Features Implemented

1. Secure login with BRACU ID and password hash verification
2. Dual-role dashboard with quick access to client and freelancer tools
3. Profile page with wallet credit view
4. Gig request form with description, deadline, credit amount, and category
5. Gig tracking board with statuses: listed, pending, done
6. Marketplace page for freelancers with search and category filter
7. Accept-gig workflow with mail notification trigger/log
8. Mark-as-done workflow with transactional wallet deduction/addition
9. CSRF protection for all state-changing actions
10. Login throttling and temporary lockout after repeated failures
11. Secure session defaults and HTTP security headers
12. Apache access hardening for sensitive directories
13. Admin-only console to promote/demote admin users
14. Full profile editor (photo, name, phone, address, bio)
15. Login mode selector (Hiring or Working)
16. Marketplace now shows all listed gigs (including your posted gigs as non-accept actions)

## Project Structure

- `index.php`: Login
- `dashboard.php`: Dual-role landing page
- `profile.php`: User profile and wallet
- `assets/uploads/avatars/`: Uploaded profile pictures
- `admin/manage_admins.php`: Admin management (grant/revoke admin role)
- `client/create_gig.php`: Client gig request form
- `client/my_gigs.php`: Client status board and done trigger
- `freelancer/marketplace.php`: Available gig list and accept flow
- `freelancer/my_work.php`: Accepted gigs tracker
- `includes/`: Config, DB, auth, helper, wallet and mail logic
- `database/schema.sql`: Tables and constraints
- `database/sample_data.sql`: Optional demo users
- `logs/mail.log`: Email logs for local/dev fallback

## Database Setup

### Connection Status

Database connection has been verified from the application layer.

Verified result:
- PDO test query (SELECT 1): db-connected

Current default DB config in `includes/config.php`:
- Host: 127.0.0.1
- Port: 3306
- Database: bracu_freelance_marketplace
- Username: root
- Password: (empty by default in local XAMPP)

1. Create database and tables:

```sql
SOURCE database/schema.sql;
```

2. Optional demo data:

```sql
SOURCE database/sample_data.sql;
```

3. If your database was created before admin role support, run migration:

```sql
SOURCE database/migration_add_admin_role.sql;
```

4. If your database was created before profile/mode support, run migration:

```sql
SOURCE database/migration_add_profile_and_mode.sql;
```

5. If you want Analytics, Indexing, and Community features, run migration:

```sql
SOURCE database/migration_add_analytics_indexing_community.sql;
```

Demo user password after seeding: `password`

If you want new users, generate password hash with PHP:

```bash
php -r "echo password_hash('your_password', PASSWORD_DEFAULT), PHP_EOL;"
```

Then insert into `User` table.

## How To Use In XAMPP

1. Open XAMPP Control Panel.
2. Start Apache.
3. Start MySQL.
4. Open phpMyAdmin and import SQL files in this order:
	- database/schema.sql
	- database/sample_data.sql
	- database/migration_add_admin_role.sql (only if DB was created before admin role)
	- database/migration_add_profile_and_mode.sql (only if DB was created before profile/mode fields)
	- database/migration_add_analytics_indexing_community.sql (for Analytics, Indexing, Community)
5. Keep the project folder inside htdocs, for example:
	- C:\xampp\htdocs\CSE370 - Group Project
6. Open the app in browser:
	- http://localhost/CSE370%20-%20Group%20Project/index.php
7. Login using a seeded user (password: password), or your own inserted account.
8. On login, choose mode:
	- Hiring (Post jobs)
	- Working (Apply to jobs)
9. Go to Profile page to add/edit:
	- Profile picture
	- Full name
	- Phone number
	- Address
	- Bio
	- Default login mode

Optional local alternative:
- Run npm run db:start
- Run npm run dev
- Open http://127.0.0.1:8000

## Local Run

1. Put this project in your PHP server root (XAMPP htdocs or similar).
2. Update DB credentials in `includes/config.php` if needed.
3. Start Apache + MySQL.
4. Open:

```
http://localhost/<your-project-folder>/index.php
```

### Optional NPM Shortcut (This Machine)

You can run the built-in PHP server through npm:

```bash
npm run dev
```

Start MySQL first (if not already running):

```bash
npm run db:start
```

This uses `C:\xampp\php\php.exe` and serves the app at:

```
http://127.0.0.1:8000
```

## Environment Configuration

Configuration now supports environment variables. Use [.env.example](.env.example) as a reference.

Key variables:
- `APP_ENV` (`production` or `development`)
- `APP_DEBUG` (`0` or `1`)
- `DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER`, `DB_PASS`
- `LOGIN_MAX_ATTEMPTS`, `LOGIN_LOCKOUT_SECONDS`

For production, keep:
- `APP_ENV=production`
- `APP_DEBUG=0`

## Production Checklist

1. Use HTTPS and a real domain (not IP-only local access).
2. Set strong database credentials and remove default root/no-password access.
3. Set environment variables for DB and app settings on the server.
4. Keep `APP_DEBUG=0` in production.
5. Ensure Apache honors the `.htaccess` files in [includes/.htaccess](includes/.htaccess), [database/.htaccess](database/.htaccess), and [logs/.htaccess](logs/.htaccess).
6. Rotate demo accounts and seed credentials before launch.
7. Set up real SMTP for `mail()` replacement (PHPMailer/SMTP recommended).
8. Schedule database backups and monitor error logs.

## Notes

- Email notification uses PHP `mail()` and always writes a log entry to `logs/mail.log`.
- Wallet transfer uses a DB transaction to avoid partial updates.
- A user cannot accept their own gig.
- A gig can only have one active freelancer assignment.
- POST actions are protected with CSRF tokens.
- Repeated failed login attempts trigger temporary lockout.
- Admin navigation link appears only for users with `is_admin = 1`.
