# APT Mail

Sends the website's email through Gmail, for when the hosting's own mail
(cPanel) does not deliver. Settings are under **Settings > Email**.

## What it does

- **All email the site sends goes through Gmail** (`smtp.gmail.com`, port 587,
  TLS), from the Gmail account set on Settings > Email (default
  `apt.analytic@gmail.com`), with the sender name "ASEAN Plus Three". That
  covers the theme's new-post notifications, password resets, and WordPress
  update, backup (UpdraftPlus) and security emails.
- **The App Password is only in `wp-config.php`**, never in the database or on
  screen. Without it the plugin does nothing and WordPress sends mail the usual
  way.
- **New-post notifications:** the theme emails a list of people when a post is
  first published. The list can be edited on Settings > Email (empty = the
  list in the theme). A post is only marked as notified if the email went out;
  posts of the last 60 days whose notification failed are listed with a
  **Send now** button. **Send test notification** sends a post's notification
  to yourself only.
- **Failures are not silent:** administrators see a notice with the time,
  subject and error of the last failed email until an email succeeds. Only that
  is kept; no email contents are stored.

## Setting it up

1. In the Gmail account: turn on **2-Step Verification** (Google Account >
   Security), then create an **App Password** at
   <https://myaccount.google.com/apppasswords> (name it e.g. "ASEAN Plus Three
   website").
2. In `wp-config.php`, above `/* That's all, stop editing! */`:
   ```php
   define( 'APT_SMTP_PASSWORD', 'xxxx xxxx xxxx xxxx' );
   ```
3. Upload `apt-mail.zip` (Plugins > Add New > Upload Plugin) and activate it.
   Deactivate WP Mail SMTP if it is still active: both would change how mail
   is sent.
4. **Settings > Email > Send test email.** Check the inbox and spam folder.

## If the test fails

- *"Could not authenticate"*: the App Password is wrong or was revoked, or
  2-Step Verification was turned off. Create a new App Password.
- *"connect() failed"* or a timeout: the host blocks outgoing SMTP. Ask the
  host to allow port 587 to smtp.gmail.com, or use the WP Mail SMTP plugin's
  Gmail API mailer instead (it sends over HTTPS).

Gmail allows about 500 emails a day from one account.
