<?php
/**
 * News page: the latest statements and activities, newest first, as the same
 * cards the home page carousel uses (apt_news_card_html), 12 per page.
 */
get_header();

$paged        = max( 1, (int) get_query_var( 'paged' ), (int) get_query_var( 'page' ) );
$latest_query = new WP_Query( array(
	'posts_per_page' => 12,
	'paged'          => $paged,
	'post_type'      => 'post',
	'post_status'    => 'publish',
	'order'          => 'DESC',
) );
?>
	<main id="main" class="site-main news-listing">
		<div class="container" id="avi-content">
			<header class="apt-page-head">
				<h1><?php esc_html_e( 'APT Latest News & Activities', 'akseki-vi' ); ?></h1>
				<?php if ( $latest_query->max_num_pages > 1 ) : ?>
				<p><?php
					/* translators: 1: current page, 2: number of pages */
					printf( esc_html__( 'Page %1$s of %2$s', 'akseki-vi' ), esc_html( number_format_i18n( $paged ) ), esc_html( number_format_i18n( $latest_query->max_num_pages ) ) );
				?></p>
				<?php endif; ?>
			</header>

			<?php if ( $latest_query->have_posts() ) : ?>
			<ul class="apt-news-grid">
				<?php
				while ( $latest_query->have_posts() ) {
					$latest_query->the_post();
					echo '<li>' . apt_news_card_html( 'h2' ) . '</li>'; // phpcs:ignore WordPress.Security.EscapeOutput -- built and escaped in apt_news_card_html().
				}
				?>
			</ul>
			<?php
			$links = paginate_links( array(
				'current'   => $paged,
				'total'     => $latest_query->max_num_pages,
				'mid_size'  => 2,
				'prev_text' => __( 'Previous', 'akseki-vi' ),
				'next_text' => __( 'Next', 'akseki-vi' ),
			) );
			if ( $links ) {
				echo '<nav class="navigation pagination" aria-label="' . esc_attr__( 'News pages', 'akseki-vi' ) . '"><div class="nav-links">' . $links . '</div></nav>'; // phpcs:ignore WordPress.Security.EscapeOutput
			}
			wp_reset_postdata();
			?>
			<?php else : ?>
			<p><?php esc_html_e( 'No news yet.', 'akseki-vi' ); ?></p>
			<?php endif; ?>
		</div>
	</main>
<?php
get_footer();
