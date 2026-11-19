<?php
// Shown when a list (search, posts page, category archive) has no posts.
?>
<div class="apt-notfound" id="notfound">
	<?php if ( is_search() ) : ?>
		<h2><?php printf( esc_html__( 'No results for “%s”', 'akseki-vi' ), esc_html( get_search_query() ) ); ?></h2>
		<p><?php esc_html_e( 'Check the spelling or try fewer or different keywords, such as a meeting name (AMAF+3) or a city.', 'akseki-vi' ); ?></p>
	<?php else : ?>
		<h2><?php esc_html_e( 'Nothing here yet', 'akseki-vi' ); ?></h2>
		<p><?php esc_html_e( 'There are no published items in this section.', 'akseki-vi' ); ?></p>
	<?php endif; ?>
	<button type="button" class="apt-pill apt-pill--dark" commandfor="apt-search" command="show-modal"><?php esc_html_e( 'Search the site', 'akseki-vi' ); ?></button>
</div>
