# Content changes for the Contact page

These live in WordPress (database), not in the theme. The theme already
styles everything below (`assets/css/apt-design.css`, "Contact page").

## 1. Contact page: map, address and email

Edit **Pages → Contact**, replace the current content with a **Custom HTML**
block containing:

```html
<div class="apt-contact">
  <iframe title="Map: ASEAN Secretariat, 70A Jalan Sisingamangaraja, Jakarta" src="https://maps.google.com/maps?q=asean%20secretariat%20jakarta&amp;t=m&amp;z=15&amp;output=embed&amp;iwloc=near" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
  <div>
    <address>
      <strong>The ASEAN Secretariat</strong>
      70A Jalan Sisingamangaraja<br>
      Jakarta 12110, Indonesia<br>
      Tel: <a href="tel:+62217262991">+62 21 7262991</a>, <a href="tel:+62217243372">+62 21 7243372</a><br>
      (+62 21) 7398234, 7243504<br>
      Email: <a href="mailto:public@asean.org">public@asean.org</a>
    </address>
    <div class="apt-contact-cta">
      <p>For enquiries about ASEAN Plus Three cooperation, email the ASEAN Secretariat.</p>
      <a class="apt-pill apt-arrow" href="mailto:public@asean.org">Email public@asean.org</a>
    </div>
  </div>
</div>
```

- The map has a `title` (screen readers announce what it is) and loads lazily.
- Phone numbers and the email address are tap-to-call / tap-to-email links,
  and the button opens a new email to public@asean.org.
- The second number line is left as plain text: confirm whether 7398234 and
  7243504 are fax numbers (as asean.org has listed them). If they are phone
  numbers, wrap them like the first line, e.g.
  `<a href="tel:+62217398234">+62 21 7398234</a>`.

## 2. Pages that should not be public

Unpublish (**Quick Edit → Status: Private**, or move to Trash):

- **Testing Page** (`/testing-page/`): has the old `[akseki_limited_access]`
  form, whose handler never logs anyone in.
- ~~`/login_ujv5g/`~~ done: it exposed the renamed login address.

Nothing on the site links to either page (checked all 835 pages and posts).
