<?php
/**
 * News page: the latest statements and activities, newest first, as a list
 * grouped by month, 12 per page, with filters by pillar, level and year
 * (inc/news-page.php). The home page keeps its card carousel.
 */
get_header();

$paged        = max( 1, (int) get_query_var( 'paged' ), (int) get_query_var( 'page' ) );
$filters      = apt_news_filters();
$latest_query = new WP_Query( apt_news_query_args( $filters, $paged ) );
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

			<?php echo apt_news_filters_html( $filters ); // phpcs:ignore WordPress.Security.EscapeOutput -- escaped inside ?>

			<?php if ( $latest_query->have_posts() ) : ?>
				<?php
				$month = '';
				while ( $latest_query->have_posts() ) {
					$latest_query->the_post();
					$this_month = get_the_date( 'Y-m' );
					if ( $this_month !== $month ) {
						echo ( $month ? '</ul>' : '' ) . '<h2 class="nw-month"><time datetime="' . esc_attr( $this_month ) . '">' . esc_html( get_the_date( 'F Y' ) ) . '</time></h2><ul class="nw-list">';
						$month = $this_month;
					}
					echo '<li>' . apt_news_row_html() . '</li>'; // phpcs:ignore WordPress.Security.EscapeOutput -- built and escaped in apt_news_row_html().
				}
				echo '</ul>';
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
			<?php elseif ( array_filter( $filters ) ) : ?>
				<p class="nw-empty"><?php esc_html_e( 'No statements match these filters.', 'akseki-vi' ); ?> <a href="<?php the_permalink(); ?>"><?php esc_html_e( 'Clear filters', 'akseki-vi' ); ?></a></p>
			<?php else : ?>
				<p><?php esc_html_e( 'No news yet.', 'akseki-vi' ); ?></p>
			<?php endif; ?>
		</div>
	</main>
<?php
get_footer();
