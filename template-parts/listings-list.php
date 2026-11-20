<?php
// One statement row on a meeting page (see apt_statement_row_html() in functions.php).
echo apt_statement_row_html( get_the_ID() ); // phpcs:ignore WordPress.Security.EscapeOutput -- built and escaped in the function.
