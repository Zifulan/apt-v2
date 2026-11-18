# Content changes for the Contact page

These live in WordPress (database), not in the theme. The theme already
styles everything below (`assets/css/apt-design.css`: "Contact page" and
"Contact Form 7").

## 1. Contact page: map, address and links

Edit **Pages → Contact**, replace the current content with a **Custom HTML**
block containing:

```html
<div class="apt-contact">
  <iframe title="Map: ASEAN Secretariat, 70A Jalan Sisingamangaraja, Jakarta" src="https://maps.google.com/maps?q=asean%20secretariat%20jakarta&amp;t=m&amp;z=15&amp;output=embed&amp;iwloc=near" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
  <address>
    <strong>The ASEAN Secretariat</strong>
    70A Jalan Sisingamangaraja<br>
    Jakarta 12110, Indonesia<br>
    Tel: <a href="tel:+62217262991">+62 21 7262991</a>, <a href="tel:+62217243372">+62 21 7243372</a><br>
    (+62 21) 7398234, 7243504<br>
    Email: <a href="mailto:public@asean.org">public@asean.org</a>
  </address>
</div>
```

- The map now has a `title` (screen readers announce what it is) and loads lazily.
- Phone numbers and email are tap-to-call / tap-to-email links.
- The second number line is left as plain text: confirm whether 7398234 and
  7243504 are fax numbers (as asean.org has listed them). If they are phone
  numbers, wrap them like the first line, e.g.
  `<a href="tel:+62217398234">+62 21 7398234</a>`.

## 2. Contact form (Contact Form 7)

1. **Plugins → Add New**, install and activate **Contact Form 7**.
2. **Contact → Add New**, title "Contact", and use this form template:

```text
<label> Your name
    [text* your-name autocomplete:name] </label>

<label> Your email
    [email* your-email autocomplete:email] </label>

<label> Subject
    [text your-subject] </label>

<label> Your message
    [textarea* your-message maxlength:2000] </label>

[acceptance consent] I agree that my details are used to reply to this message. [/acceptance]

[submit "Send message"]
```

3. **Mail tab:**
   - **To:** the mailbox that should receive messages (the team that answers
     public enquiries).
   - **From:** an address on the site's own domain, e.g.
     `APT website <wordpress@aseanplusthree.asean.org>`; a gmail/asean.org
     address in From is often rejected as spoofing.
   - **Additional headers:** `Reply-To: [your-name] <[your-email]>` so
     "Reply" goes to the sender.
4. **Spam protection:** Contact → Integration → reCAPTCHA (v3 needs its own
   v3 site/secret keys; the keys used by the login form are v2), or Akismet.
5. Save, copy the shortcode (`[contact-form-7 id="…" title="Contact"]`), and
   add it to the Contact page in a **Shortcode** block below the Custom HTML
   block, with a **Heading** block "Send us a message" above it.
6. Send a test message and check it arrives (and isn't in spam).

## 3. Pages that should not be public

Unpublish (**Quick Edit → Status: Private**, or move to Trash):

- **Testing Page** (`/testing-page/`): has the old `[akseki_limited_access]`
  form, whose handler never logs anyone in.
- ~~`/login_ujv5g/`~~ done: it exposed the renamed login address.

Nothing on the site links to either page (checked all 835 pages and posts).
