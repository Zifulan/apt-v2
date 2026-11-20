<?php
/**
 * Photo gallery (Gallery pages): statement photos, newest first, 24 per page.
 * Each photo links to its statement; the caption gives the meeting, place and
 * date. Native lazy loading; image dimensions are set so nothing jumps.
 */
$apt_paged = max( 1, (int) get_query_var( 'paged' ), (int) get_query_var( 'page' ) );
$apt_gq    = new WP_Query( array(
	'cat'                 => get_term_children( 7, 'category' ),
	'post_status'         => 'publish',
	'posts_per_page'      => 24,
	'paged'               => $apt_paged,
	'ignore_sticky_posts' => true,
	'meta_query'          => array( array( 'key' => '_thumbnail_id', 'compare' => 'EXISTS' ) ), // phpcs:ignore WordPress.DB.SlowDBQuery
) );
?>
<header class="apt-page-head">
	<h1><?php esc_html_e( 'Photo Gallery', 'akseki-vi' ); ?></h1>
</header>
<?php if ( $apt_gq->have_posts() ) : ?>
<ul class="apt-gallery">
	<?php
	while ( $apt_gq->have_posts() ) :
		$apt_gq->the_post();
		$id    = get_the_ID();
		$sm    = akseki_get_sm( $id );
		$name  = $sm ? $sm : akseki_get_shortname( wp_strip_all_tags( get_the_title() ), $id, 7 );
		$thumb = get_the_post_thumbnail( $id, 'medium_large', array( 'loading' => 'lazy', 'decoding' => 'async', 'alt' => '' ) );
		?>
	<li>
		<figure class="apt-gallery-item">
			<a href="<?php the_permalink(); ?>" class="apt-gallery-link"><?php echo $thumb; // phpcs:ignore WordPress.Security.EscapeOutput ?><span class="screen-reader-text"><?php echo esc_html( wp_strip_all_tags( get_the_title() ) ); ?></span></a>
			<figcaption>
				<span class="apt-gallery-name"><?php echo $name; // phpcs:ignore WordPress.Security.EscapeOutput -- short name may contain <sup> ?></span>
				<span class="akseki-meta"><?php echo akseki_get_the_meta( $id ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span>
			</figcaption>
		</figure>
	</li>
	<?php endwhile; ?>
</ul>
	<?php
	$apt_links = paginate_links( array(
		'current'   => $apt_paged,
		'total'     => $apt_gq->max_num_pages,
		'mid_size'  => 2,
		'prev_text' => __( 'Previous', 'akseki-vi' ),
		'next_text' => __( 'Next', 'akseki-vi' ),
	) );
	if ( $apt_links ) {
		echo '<nav class="navigation pagination" aria-label="' . esc_attr__( 'Gallery pages', 'akseki-vi' ) . '"><div class="nav-links">' . $apt_links . '</div></nav>'; // phpcs:ignore WordPress.Security.EscapeOutput
	}
	wp_reset_postdata();
	?>
<?php else : ?>
<p class="apt-empty"><?php esc_html_e( 'No photos yet.', 'akseki-vi' ); ?></p>
<?php endif; ?>
