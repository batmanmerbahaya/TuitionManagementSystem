# Anjung Ilmu TMS

Tuition management system for Pusat Tuisyen Anjung Ilmu Pendeta, built with **Laravel 13 (PHP 8.5) and
MySQL 8** as a standard Laravel MVC app:

| Part | Where |
|------|-------|
| Models + database queries | `app/Models/*.php` |
| Controllers | `app/Http/Controllers/**`, named routes in `routes/web.php` |
| Pages | `resources/views/**/*.blade.php` (shared top bar: `resources/views/partials/header.blade.php`) |
| Role checks | `app/Http/Middleware/EnsureRole.php`, `EnsureSuperAdmin.php`, `EnsureActiveAccount.php` |
| Database schema | `database/migrations/` |
| Settings (database, email, centre details) | `.env`, `config/tms.php` |
| Styles and images | `public/css`, `public/images` |

## Run it

Requirements: PHP 8.3+ with `pdo_mysql`, Composer, MySQL 8.

```powershell
cd tms-laravel
composer install
copy .env.example .env
php artisan key:generate

# 1. Database + a MySQL account for the app (once, as MySQL root). Pick your own password.
mysql -u root -p -e "CREATE DATABASE tms CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci; CREATE DATABASE tms_test CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci; CREATE USER 'tms_app'@'localhost' IDENTIFIED BY 'choose-a-password'; GRANT ALL ON tms.* TO 'tms_app'@'localhost'; GRANT ALL ON tms_test.* TO 'tms_app'@'localhost';"

# 2. In .env: DB_USERNAME=tms_app and DB_PASSWORD=choose-a-password

# 3. Create the tables + the first superadmin (add --class=DemoSeeder for sample data)
php artisan migrate --seed
php artisan db:seed --class=DemoSeeder      # optional

# 4. Start the server + the monthly-billing scheduler
.\serve.bat
```

Open http://127.0.0.1:8000 and log in:

| Role        | Email                     | Password    |
|-------------|---------------------------|-------------|
| Superadmin  | admin@anjungilmu.com      | Admin@123   |
| Teacher*    | teacher1@anjungilmu.com   | Password1   |
| Student F4* | student4@anjungilmu.com   | Password1   |
| Student F5* | student5@anjungilmu.com   | Password1   |

\* only after `php artisan db:seed --class=DemoSeeder`. Change the admin password after the first login.

To start again from an empty database: `php artisan migrate:fresh --seed`.

## Pages

| URL | Page |
|-----|------|
| `/` | Landing page |
| `/login`, `/signup` | Log in, student sign-up |
| `/forgot-password` → `/verify-otp` → `/reset-password` | Password reset with an emailed code |
| `/profile`, `/profile/edit`, `/profile/password` | Own profile, edit profile, change password |
| `/admin/dashboard` | Admin dashboard (counts, revenue chart, timetable) |
| `/admin/admins` | Admin list (superadmin only) |
| `/admin/teachers`, `/admin/students` | Teacher and student lists, add / edit / delete |
| `/admin/subjects`, `/admin/packages/create` | Subjects and packages |
| `/admin/payments` | Payment verification and monthly bills |
| `/teacher/dashboard`, `/teacher/students`, `/teacher/subjects` | Teacher pages |
| `/student/dashboard`, `/student/subjects`, `/student/payments` | Student pages |
| `/student/payments/checkout` | Pay selected bills and upload proof |
| `/receipts/{transaction or payment id}` | Receipt |

`php artisan route:list` shows all of them.

## Features

**Registration and seats.** Students register for single subjects or packages of their level. Seats are
held as soon as a student registers (packages included), full subjects/packages cannot be taken, and a
one-time RM15 registration fee is added to a student's first registration. Unpaid registrations can be
dropped by the student; paid ones only by an admin.

**Monthly billing.** Every bill belongs to a billing month (`payments.billingperiod`, e.g. `2026-11`).
Registering creates this month's bill (plus the one-off registration fee); each following month a new
tuition bill is created at the subject's/package's current fee for every active registration.
- Automatically: `serve.bat` starts the scheduler, which runs `php artisan tms:bill-month` every hour.
- By hand: **Payment → Monthly Bills → Generate Bills** (this month or next month), or
  `php artisan tms:bill-month 2026-12`.
- Nobody is ever billed twice for the same month (also enforced by a unique key in the database).
- Dropping a registration deletes its unpaid bills; a paid bill for the current month becomes CANCELLED;
  months already paid before stay APPROVED, so past revenue stays correct.
- The revenue chart groups by month and year.

**Payments.** Students pay several bills at once and upload one proof (photo or PDF); admins approve or
reject the whole transaction. Receipts and proofs are only visible to admins and the owning student.

**Admin tools.**
- The stat cards on the Admin, Teacher and Student lists filter the list below them.
- Edit a student (name, email, phone, **level**) or a teacher from their lists. The level can only change
  once the student's current registrations are dropped, because those subjects belong to the old level.
- Superadmins can promote/demote admins from the Admin list. The change applies on that admin's next click.

**Timetable rules.**
- Students cannot register two classes at the same time (individual subjects, packages, or both).
- A package cannot contain two classes at the same time.
- Classes must be between 08:00 and 22:00 (`TMS_DAY_START` / `TMS_DAY_END`).
- Admins cannot set a subject's slots below the number of students enrolled, cannot change the subjects
  of a package that has students, and cannot move a class onto an enrolled student's other class.
- Dashboards show a class in every 2-hour slot it covers and every class in a slot (hover a badge for the
  full name and time).

**Safety.**
- Passwords are stored with bcrypt. Delete / drop / logout are POST requests with a CSRF token.
- Reset password only works after the emailed code has been verified.
- A user whose account is deleted is logged out on their next click.
- The last superadmin cannot be deleted or demoted, and you cannot remove or demote yourself from the list.
- Login locks for 5 minutes after 5 wrong passwords; forgot-password allows 3 codes per 15 minutes,
  30 seconds between resends, and 5 guesses per code.
- Registrations and anything that creates IDs run one at a time (MySQL named lock), so two people
  clicking at the same moment cannot take the same last seat or get the same ID.
- Every form (sign up, add/edit users, profile) is validated on the server too: name, email, Malaysian
  mobile number, password strength.
- A deleted account's email can be used again for a new account.
- Profile photos and payment proofs are stored in the database (LONGBLOB) and served by
  `/users/{id}/photo` and `/payments/{id}/proof`.

**Centre details.** Receipts and the payment page read `TMS_*` settings in `.env` (address, phone, email,
bank, account, DuitNow QR text, registration fee). See `config/tms.php`.

## Tests

```powershell
php artisan test
```

58 tests covering login/sign-up, password reset, registration rules, monthly billing, admin tools,
subject/package rules and every page. They run on the separate `tms_test` database (it is wiped on every
run); the test setup refuses to run on any database whose name does not end in `_test`.
If you ever run `php artisan config:cache`, run `php artisan config:clear` before testing.

## Before putting it online

- In `.env`: `APP_ENV=production`, `APP_DEBUG=false` (otherwise error pages show code and settings),
  `APP_URL=https://your-domain`.
- Serve it over HTTPS with Apache or Nginx pointing at the `public` folder; set `upload_max_filesize=16M`
  and `post_max_size=20M` in `php.ini`.
- Add a cron job / Windows scheduled task: `php artisan schedule:run` every minute (monthly bills).
- Run `php artisan config:cache route:cache view:cache` after each deploy.
- Set the Gmail app password and the DuitNow QR in `.env` (see Notes).
- Use a long random database password.
- Back up the `tms` database regularly (`mysqldump -u tms_app -p tms > backup.sql`).

## Notes

- **Email:** put the centre's Gmail address and a Google app password in `.env` (`MAIL_USERNAME`,
  `MAIL_PASSWORD`, `MAIL_FROM_ADDRESS`). Never commit them - `.env` is ignored by git.
  Set `MAIL_MAILER=log` to write OTP emails to `storage/logs/laravel.log` while testing offline.
- **Uploads:** `serve.bat` allows 16 MB. If you deploy behind Apache/Nginx, raise `upload_max_filesize`
  and `post_max_size` in `php.ini` instead.
- **Payment QR:** set `TMS_DUITNOW_QR` in `.env` to the text of the centre's own DuitNow QR (scan it
  with any QR reader app), or leave it empty to show only the bank details.
