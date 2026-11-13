# Due It Now

A web-based task manager where each user registers, logs in and manages their own tasks, with priorities, statuses, due times and a dark mode.

**Live site:** https://due-it-now.onrender.com

**Repository:** https://github.com/andreabuhangin/due-it-now

## Features

- Registration and login with password-protected accounts and a session
- Sign in with Google (on the live site, for Google accounts added as test users)
- Create, view, update and delete your own tasks
- Priority and status on every task
- Optional due time on each task
- Google Calendar sync for tasks
- Installable as a Progressive Web App (PWA)
- Light and dark themes, remembered in the browser with localStorage (Web Storage)

## Technology stack

| Area | Technology |
| --- | --- |
| Frontend | HTML, CSS, JavaScript |
| Backend | PHP (API endpoints in `source-code/api/`) |
| Database | MySQL, queried with prepared statements |
| Tools | XAMPP (local development), Git and GitHub |

## Security

- **Prepared statements:** every query uses `prepare()` and `bind_param()`, which prevents SQL injection.
- **Task ownership:** every read, update and delete in `tasks.php` includes `user_id = ?`, so users can only touch their own tasks.
- **Login protection:** `password_verify` and `session_regenerate_id(true)` are used, and the error message does not reveal whether the email or the password was wrong.
- **Server-side validation:** the title is required, and priority, status and time are checked.
- **Deployment:** database credentials are read from environment variables, not stored in the code.

## Run it locally

1. Install [XAMPP](https://www.apachefriends.org/) and start **Apache** and **MySQL**.
2. Copy the contents of the `source-code/` folder into `C:\xampp\htdocs\due_it_now`.
3. Open phpMyAdmin (`http://localhost/phpmyadmin`), click **Import**, and choose `database/dueitnow.sql`. The file creates the `dueitnow` database and its `users` and `tasks` tables, so you do not need to create the database first.
4. No database settings need changing on a default XAMPP install. `source-code/config/db.php` reads `DB_HOST`, `DB_USER`, `DB_PASS`, `DB_NAME` and `DB_PORT` from environment variables and falls back to `localhost`, `root`, an empty password and the `dueitnow` database. Set the environment variables, or edit those defaults, if your setup is different.
5. Open `http://localhost/due_it_now` in your browser.

**Google sign-in:** `source-code/config/google.php` reads `GOOGLE_CLIENT_ID`, `GOOGLE_CLIENT_SECRET` and `APP_URL` from environment variables, or from a local `config/google.local.php` file that is not included in this submission or in GitHub. Without those credentials, Google sign-in will not work when the project runs locally. Registering and logging in with an email and password works without them.

On the live site, Google sign-in works for Google accounts that have been added as test users in the project's Google OAuth settings. The instructor's Google account has been added so it can be tested there.

### Test account

The database contains test data only, no real accounts. Log in with:

- Email: `andreabuhangin16@gmail.com`
- Password: `buhanginandrea16`

## Project structure

```
FINAL_PROJECT_BuhanginChasityAndreaRainD_202412671/
├── database/        # dueitnow.sql (database export)
├── diagrams/        # architecture, ERD, flowchart, use-case diagram
├── documentation/   # final project documentation (PDF)
├── source-code/
│   ├── api/         # PHP API endpoints
│   ├── assets/      # CSS, JavaScript and images
│   ├── config/      # db.php (database connection)
│   ├── index.php, register.php, dashboard.php
│   └── ...          # other app files
└── README.md
```

## Known limitations and future work

- No CSRF tokens and no limit on login attempts.
- Update and delete do not check whether a row changed, so the API can reply "Task updated" for a task ID that is not the user's. No data is changed, because of the `user_id` check, but the message is misleading.

## Documentation

The full project documentation is the PDF in the `documentation/` folder.

## Author

Chasity Andrea Rain D. Buhangin · BSIT 2-4 · Prof. John Ray M. Tenio
