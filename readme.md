# SQLite Visualizer

A tiny single-file PHP web app to upload and interact with a local SQLite database file (`database.sqlite`). It provides a simple UI to:

> Warning: This tool is intended for local development and debugging only. It is not designed or secured for production environments.

- Upload a SQLite database file via an HTML file input.
- List all tables found in the uploaded database.
- Run SQL queries (SELECT queries return rows; non-SELECT queries run and report affected rows).

This project is intentionally minimal. It was created as a development/debugging tool and is NOT hardened for production use. Read the Security Considerations section carefully before running this on any network-accessible host.

## Files

- `index.php` — single entry point that handles file upload, SQLite connection, SQL execution, and rendering.

Note: the SQLite file is created on the server at runtime after you upload a database through the web UI. The file is intentionally not tracked by git (it should be listed in `.gitignore`) and therefore is not part of the repository. On disk the app will create `database.sqlite` in the project folder when you upload a file.

## How it works (high-level)

1. If no `database.sqlite` exists in the project folder, the page shows a file upload form. Submitting a file stores it on disk as `database.sqlite`.
2. Once a database file exists, the app opens it using PDO with the sqlite driver and lists tables from `sqlite_master`.
3. Clicking a table shows all rows from that table. A simple SQL editor lets you submit SQL statements:
	- If the SQL starts with `SELECT` it executes a query and displays the returned rows.
	- Otherwise it executes the statement with `PDO::exec()` and reports the number of affected rows.
4. A `?option=refresh` link deletes `database.sqlite` so you can upload another file.

## Setup (Windows + XAMPP)

1. Install XAMPP and ensure Apache/PHP are running.
2. Place the project folder under your XAMPP document root (example: `C:\xampp\htdocs\sqlite-visualizer`).
3. Make sure the Apache process user can read/write files in the project folder.
4. Open a browser and navigate to `http://localhost/sqlite-visualizer/index.php`.
5. Use the file input to upload a `.sqlite` / `.db` file. The app will create `database.sqlite` in the same folder.

## Usage notes

- The app uses PHP's PDO with the sqlite driver; no extra PHP extensions should be required beyond the bundled SQLite PDO extension provided by most PHP distributions.
- The SQL editor accepts free-form SQL. It performs a naive check for `SELECT` by testing the start of the submitted string; this is simplistic.
- Large results are rendered in a plain HTML table — pagination is not implemented.

## Security considerations (read this)

This application has very little security. It should only be used in a trusted, isolated development environment. Do NOT expose this to untrusted networks. Key issues include:

- Arbitrary SQL execution: The page accepts and executes arbitrary SQL statements from any visitor who can access the app. This allows data exfiltration, data modification, schema changes, and arbitrary SQLite-specific commands.
- Remote code exposure via uploaded files: The uploaded file is written to `database.sqlite` in the document root. If an attacker can upload files with other extensions or exploit path handling, they could attempt to drop or replace files. The app does not validate file type beyond assuming the uploaded file is a SQLite DB.
- No authentication or access control: Anyone who can reach the URL can upload databases and run SQL.
- Cross-Site Scripting (XSS) risks: The app echoes database content and error messages directly into the page without output escaping. Malicious data inside the database could contain HTML/JS and will be rendered into the page.
- Insecure error handling: Exceptions are displayed to the user. Error messages may expose filesystem paths, SQL queries, or other sensitive details.
- File permissions and cleanup: Uploaded files are stored in the project directory with no expiry. Sensitive data may remain on disk indefinitely.
- SQL command detection is naive: The app uses a simple string check for `SELECT` to decide whether to call `query()` or `exec()`. This is bypassable (e.g., leading whitespace, comments, or different casing) and could lead to unexpected behavior.

Recommended mitigations if you plan to use this beyond a short-lived local experiment:

- Run only on localhost or an isolated network. Do not expose to the public internet.
- Add authentication (password or basic auth) to restrict who can reach the page.
- Only accept uploads while authenticated and validate uploaded files (MIME type, extension, and ideally attempt to open with SQLite in a sandboxed process before moving to the webroot).
- Never store uploaded files in the webroot — use a storage directory outside the document root and restrict access.
- Escape all output (HTML-encode cell values and error messages) to prevent XSS.
- Remove or sanitize error output in production; log errors to a file outside the webroot.
- Implement CSRF protection for the SQL execution endpoint and the file upload form.
- Use prepared statements or explicit permissions for operations that depend on user input; avoid letting arbitrary SQL run if possible.
- Implement size limits on uploads and on query result sets (and ideally add pagination).
- Create a retention/cleanup policy for uploaded databases; delete temporary files after use.

## Contract (inputs / outputs)

- Inputs: file upload (multipart/form-data input named `path`), optional query string parameters `table`, `option=refresh`, and posted SQL in `sql-editor`.
- Outputs: an HTML page that lists tables, renders table contents, or results of ad-hoc SQL. The uploaded file becomes `database.sqlite` in the project folder.

## Limitations and known issues

- The SQL detection for SELECT is naive and simplistic.
- File upload error handling is minimal; missing checks may lead to warnings or server errors.
- No CSRF tokens, no authentication, no input/output sanitization.

## Suggested hardening steps

A small, incremental set of improvements to harden the app includes:

1. Add HTML-escaping for all output and sanitize error messages.
2. Add simple HTTP Basic auth to restrict access.
3. Move uploads out of the webroot and add file type checks.
4. Replace the naive SQL detection with a safer execution flow (e.g., always use prepared statements, or limit allowed statements).

---

## Authorship & accuracy

This README was created with assistance from an AI language model (OpenAI GPT-4). While it aims to be helpful and accurate, it may contain mistakes, omissions, or recommendations that do not fit your exact environment. Verify any instructions or security recommendations before applying them in production.

The application source code itself was written by the repository owner and contributor; this README only documents and explains the app and security considerations and was assisted by the AI.
