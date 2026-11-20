<?php
// One agreement / other document (apt_document_row_html() in functions.php).
// "Other documents" lists only documents filed directly under it.
$apt_slugs = wp_list_pluck( get_the_category(), 'slug' );
if ( 'other-documents' === $args['curcatslug'] && ! in_array( 'other-documents', $apt_slugs, true ) ) {
	return;
}
echo apt_document_row_html( get_the_ID() ); // phpcs:ignore WordPress.Security.EscapeOutput -- built and escaped in the function.
