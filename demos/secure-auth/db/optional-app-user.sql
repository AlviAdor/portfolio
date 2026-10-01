-- Optional hardening: a dedicated database user for this app, instead of root.
-- Worth doing before a real deployment. Not needed to get the demo running --
-- db/setup.sql + root (the default) is enough for local development.
--
-- Run this via the command line, not phpMyAdmin's import -- CREATE USER and
-- GRANT need server-admin privileges that phpMyAdmin's import doesn't always
-- carry, even when you're logged into phpMyAdmin as root:
--   /Applications/XAMPP/xamppfiles/bin/mysql -u root < db/optional-app-user.sql
--
-- Then tell the app to use it instead of root -- set these wherever your
-- server reads environment variables (see README.md "Getting it running"):
--   SAD_DB_USER=sad_app
--   SAD_DB_PASS=replace-with-a-strong-password
-- (set the same strong password in the SQL below before running it.)

CREATE USER IF NOT EXISTS 'sad_app'@'localhost' IDENTIFIED BY 'replace-with-a-strong-password';
GRANT SELECT, INSERT, UPDATE ON secure_auth_demo.* TO 'sad_app'@'localhost';
FLUSH PRIVILEGES;
