# StudentHub Practicals 7–14

## Local setup

1. Start Apache and MySQL in XAMPP.
2. Import [`schema.sql`](./schema.sql) in phpMyAdmin or run it with the MySQL command line. It creates the `studenthub` database and all tables. If the database already contains the earlier Practical 3–6 tables, run [`migrate-existing.sql`](./migrate-existing.sql) once afterward to add the new user/event fields without losing existing records.
3. If needed, set `STUDENTHUB_DB_HOST`, `STUDENTHUB_DB_NAME`, `STUDENTHUB_DB_USER`, and `STUDENTHUB_DB_PASSWORD`. Defaults are `localhost`, `studenthub`, `root`, and an empty password for a standard XAMPP installation.
4. Open the project through Apache (for example `http://localhost/WDF%20PRACTICALS%20-%20Copy/`); do not open the HTML files as `file://` URLs.
5. Register an account, then promote it to administrator in MySQL:

   ```sql
   UPDATE studenthub.users SET role = 'admin' WHERE email = 'your-email@example.com';
   ```

6. Log in again. Admin accounts go to `admin.php`; students go to `profile.php`.

The web server must be able to write to `data/` for contact JSON submissions and to `uploads/event-posters/` for event poster uploads. Contact records are stored in `data/contact_submissions.json`; the included Apache rule prevents direct web access to that file.

## ER model

- **users** 1—many **registrations** many—1 **events**
- **students** stores the administrator-managed student directory independently from login accounts.
- **users** 1—many **remember_tokens**; tokens are selector/validator credentials and only validator hashes are stored.
- **users** 1—many **audit_logs** as the acting administrator.

`registrations` has a unique `(user_id, event_id)` pair and foreign keys to both parent records. Events and registration history are removed consistently when their parent is deleted.

## Practical feature map

- **7:** `contact.html` → `php/contact.php` validates form values and appends JSON records with an exclusive file lock.
- **8:** `schema.sql` and `php/config.php` provide the schema, PDO connection, and MySQLi connection.
- Event attendees can register from the public event list; registrations are unique per student and event.
- **9:** `register.html` → `php/register.php` validates fields, enforces unique email/Student ID, and stores `password_hash()` output using a MySQLi prepared statement.
- **10:** `php/login.php`, `php/logout.php`, `php/auth.php`, and `profile.php` provide password verification, regenerated sessions, role-aware redirects, a 30-minute idle timeout, and optional 30-day remember-me tokens.
- **11:** `students.php` and `php/students_api.php` implement admin-only MySQLi CRUD, search, course filtering, and feedback messages.
- **12:** `events_admin.php` and `php/events_api.php` implement admin-only event CRUD and JPEG/PNG/WebP poster validation (2 MB maximum).
- **13:** `js/management.js` uses Fetch for asynchronous student and event CRUD; the public `event.html` list loads from the JSON event API.
- **14:** `admin.php` provides admin-only counts, recent registrations, dynamic administrator navigation, and recent audit records.

All management API mutations require an authenticated administrator and a session CSRF token. A registered student is not an administrator unless their role is explicitly promoted.
