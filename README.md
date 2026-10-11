# PHP Login System with Google Sign-In

A simple PHP + MySQL authentication system with username/password registration and login, plus "Continue with Google" using OAuth 2.0. Built with plain PHP, PDO and cURL, with no external libraries.

## Features

- User registration with hashed passwords (`password_hash`)
- Login with username and password (`password_verify`)
- Sign up / log in with a Google account (OAuth 2.0)
- Existing accounts are linked automatically when the Google email matches
- Session-based authentication with session ID regeneration on login
- CSRF protection on the OAuth flow using a `state` parameter
- Prepared statements (PDO) to prevent SQL injection
- Output escaping with `htmlspecialchars` to prevent XSS

## Project Structure

```
classtask/
├── config.php            # Google OAuth credentials (do NOT commit)
├── db.php                # PDO database connection
├── register.php          # Sign-up page
├── login.php             # Login page
├── home.php              # Protected page (requires login)
├── logout.php            # Destroys the session
├── google-login.php      # Redirects the user to Google
└── google-callback.php   # Handles Google's response
```

## Requirements

- PHP 7.4 or higher (with the `pdo_mysql` and `curl` extensions enabled)
- MySQL or MariaDB
- XAMPP, WAMP, or any Apache/PHP stack
- A Google Cloud project with an OAuth 2.0 Client ID

## Installation

### 1. Copy the project

Place the project folder in your web root, for example:

```
C:\xampp\htdocs\classtask
```

### 2. Create the database

Create a database named `task` and run:

```sql
CREATE TABLE accounts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(255) NULL,
    google_id VARCHAR(50) NULL UNIQUE
);
```

If you already have an `accounts` table, add the Google columns instead:

```sql
ALTER TABLE accounts
  ADD COLUMN google_id VARCHAR(50) NULL UNIQUE,
  ADD COLUMN email VARCHAR(255) NULL;

ALTER TABLE accounts MODIFY password VARCHAR(255) NOT NULL;
```

> The `password` column must be at least `VARCHAR(255)`. A shorter column truncates the hash and causes "invalid password" errors.

### 3. Configure the database connection

Edit `db.php` if your MySQL settings differ:

```php
$pdo = new PDO(
    'mysql:host=localhost;dbname=task;charset=utf8mb4',
    'root',
    ''
);
```

### 4. Set up Google OAuth

1. Go to the [Google Cloud Console](https://console.cloud.google.com/) and open **APIs & Services → Credentials**.
2. Create an **OAuth client ID** with application type **Web application**.
3. Under **Authorized redirect URIs**, add:
   ```
   http://localhost/classtask/google-callback.php
   ```
4. Open **OAuth consent screen**. If the app is in *Testing* mode, add your Gmail address under **Test users**.
5. Copy the **Client ID** and **Client Secret**.

### 5. Create `config.php`

```php
<?php
define('GOOGLE_CLIENT_ID', 'YOUR_CLIENT_ID.apps.googleusercontent.com');
define('GOOGLE_CLIENT_SECRET', 'YOUR_CLIENT_SECRET');
define('GOOGLE_REDIRECT_URI', 'http://localhost/classtask/google-callback.php');
```

The redirect URI here must match the one in Google Console exactly.

### 6. Run

Start Apache and MySQL in XAMPP, then open:

```
http://localhost/classtask/register.php
```

## How the Google Login Works

1. The user clicks **Continue with Google** and is sent to `google-login.php`.
2. A random `state` value is stored in the session, and the user is redirected to Google.
3. After approval, Google redirects to `google-callback.php` with a `code`.
4. The callback verifies `state`, exchanges the `code` for an access token, and fetches the user's profile.
5. The user is found by `google_id`, linked by `email`, or created as a new account.
6. A session is started and the user is redirected to `home.php`.

Accounts created through Google get a random, unusable password hash, so they can only sign in through Google.

## Troubleshooting

| Error | Cause and fix |
|-------|---------------|
| **Invalid username or password** for valid accounts | The `password` column is too short, or the account was inserted manually with a plain-text password. Use `VARCHAR(255)` and register through `register.php`. |
| **401: invalid_client** | The Client ID in `config.php` is wrong, missing, or still the placeholder. |
| **redirect_uri_mismatch** | The redirect URI in Google Console doesn't exactly match `GOOGLE_REDIRECT_URI`. Check the folder name, `http` vs `https`, the port, and trailing slashes. |
| **Access blocked: app not verified** | Add your Gmail as a Test user on the OAuth consent screen. |
| **cURL error: SSL certificate problem** | Download `cacert.pem` from https://curl.se/ca/cacert.pem, set `curl.cainfo` in `php.ini` to its path, then restart Apache. |
| **Column not found: google_id / email** | Run the `ALTER TABLE` command from step 2. |

Changes to redirect URIs in Google Console can take a few minutes to apply.

## Security Notes

- Never commit `config.php`. It contains your client secret. Add it to `.gitignore`:
  ```
  config.php
  ```
- Use HTTPS in production, and add the HTTPS redirect URI in Google Console.
- Change the default MySQL `root` user with an empty password before deploying.
- Publish the OAuth consent screen when you want users outside your test list to sign in.

## License

This project is for learning purposes. Use and modify it freely.
