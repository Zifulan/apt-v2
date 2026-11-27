<?php
/**
 * Yearly archives of statements (Archives page, Agenda and Archive page).
 * Server-rendered: each year is a plain link (?y=2024), so a year can be
 * bookmarked, shared and indexed, and works without JavaScript. The newest
 * year is shown by default. ("year" itself is reserved by WordPress.)
 */
$apt_years = apt_archive_years();
if ( ! $apt_years ) {
	echo '<p class="apt-empty">' . esc_html__( 'No statements yet.', 'akseki-vi' ) . '</p>';
	return;
}
$apt_req  = isset( $_GET['y'] ) ? absint( wp_unslash( $_GET['y'] ) ) : 0; // phpcs:ignore WordPress.Security.NonceVerification -- read-only filter
$apt_year = in_array( $apt_req, $apt_years, true ) ? $apt_req : $apt_years[0];
$apt_base = get_permalink();

// The year's statements, newest first, grouped by month.
$apt_q = new WP_Query( array(
	'cat'                 => get_term_children( 7, 'category' ),
	'post_status'         => 'publish',
	'posts_per_page'      => -1,
	'orderby'             => 'date',
	'order'               => 'DESC',
	'ignore_sticky_posts' => true,
	'no_found_rows'       => true,
	'date_query'          => array( array( 'year' => $apt_year ) ),
) );
$apt_months = array();
foreach ( $apt_q->posts as $p ) {
	$apt_months[ get_the_date( 'F', $p ) ][] = $p->ID;
}
?>
<section class="apt-archive" aria-labelledby="apt-archive-year">
	<nav class="apt-year-nav" aria-label="<?php esc_attr_e( 'Years', 'akseki-vi' ); ?>">
		<ul>
			<?php foreach ( $apt_years as $y ) : ?>
			<li><a href="<?php echo esc_url( add_query_arg( 'y', $y, $apt_base ) ); ?>"<?php echo $y === $apt_year ? ' aria-current="page"' : ''; ?>><?php echo (int) $y; ?></a></li>
			<?php endforeach; ?>
		</ul>
	</nav>

	<h2 id="apt-archive-year" class="apt-archive-year"><?php echo (int) $apt_year; ?></h2>
	<?php if ( $apt_months ) : ?>
		<?php foreach ( $apt_months as $month => $ids ) : ?>
		<section class="apt-archive-month">
			<h3 class="apt-archive-month-name"><?php echo esc_html( $month . ' ' . $apt_year ); ?>
				<span class="apt-archive-month-count"><?php echo esc_html( sprintf( /* translators: %d: count */ _n( '%d statement', '%d statements', count( $ids ), 'akseki-vi' ), count( $ids ) ) ); ?></span></h3>
			<ul class="list-group ministerial-listing">
				<?php foreach ( $ids as $id ) {
					echo apt_statement_row_html( $id, 'h4' ); // phpcs:ignore WordPress.Security.EscapeOutput -- built and escaped in the function.
				} ?>
			</ul>
		</section>
		<?php endforeach; ?>
	<?php else : ?>
	<p class="apt-empty"><?php esc_html_e( 'No statements for this year.', 'akseki-vi' ); ?></p>
	<?php endif; ?>
</section>
