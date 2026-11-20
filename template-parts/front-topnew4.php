<?php
	// Front-page hero: posts tagged "front". First post = feature image,
	// the next three = "Highlights" row.
	$ftn_query = new WP_Query( [
		'tag'            => 'front',
		'posts_per_page' => 4,
	] );

	if ( ! function_exists( 'a_get_the_post_thumbnail_url' ) ) {
		function a_get_the_post_thumbnail_url( $size = 'large' ) {
			$th = get_the_post_thumbnail_url( get_the_ID(), $size );
			return $th !== false ? $th : wp_get_attachment_image_url( 1007, $size );
		}
	}

	$ftn_cats = array_merge(
		akseki_get_ministrial_categories(),
		[ akseki_get_dg_categories(), akseki_get_wg_categories(), akseki_get_som_categories(),
		  akseki_get_othlev_categories(), 'summit-level-statements', 'cpr3' ]
	);

	$ftn_feature = null;
	$ftn_highlights = [];

	if ( $ftn_query->have_posts() ) :
		while ( $ftn_query->have_posts() ) : $ftn_query->the_post();
			$ftn_ID    = get_the_ID();
			$ftn_title = strip_tags( get_the_title() );

			// Meeting posts get their short name (e.g. "26th AMAF+3"), others keep the title.
			$aa = wp_list_pluck( get_the_category(), 'slug' );
			if ( array_intersect( $aa, $ftn_cats ) ) {
				$ftn_SM = akseki_get_SM( $ftn_ID );
				$ftn_short = empty( $ftn_SM ) ? akseki_get_shortname( $ftn_title, $ftn_ID, 7 ) : $ftn_SM;
			} else {
				$ftn_short = $ftn_title;
			}

			$thumb_id = get_post_thumbnail_id( $ftn_ID );
			$thumb    = wp_get_attachment_image_src( $thumb_id ? $thumb_id : 1007, 'large' );
			$item = [
				'short' => $ftn_short,
				'title' => $ftn_title,
				'link'  => get_the_permalink(),
				'meta'  => akseki_get_the_meta( $ftn_ID ),
				'img'   => a_get_the_post_thumbnail_url( 'large' ),
				'w'     => $thumb ? (int) $thumb[1] : 0,
				'h'     => $thumb ? (int) $thumb[2] : 0,
			];

			if ( null === $ftn_feature ) {
				$ftn_feature = $item;
			} else {
				$ftn_highlights[] = $item;
			}
		endwhile;
	endif;
	wp_reset_postdata();
?>
<section class="apt-hero" aria-labelledby="apt-hero-title">
	<div class="apt-hero-main">
		<div class="apt-hero-intro">
			<h1 class="apt-hero-title" id="apt-hero-title">ASEAN <span>Plus Three</span></h1>
			<?php
			/*
			 * Flag line-up: ASEAN Member States in alphabetical order with the
			 * ASEAN flag on the right (ASEAN Flag Guidelines, para 16), then
			 * China, Japan and the Republic of Korea. Official names are in alt/title only.
			 * Flags: flag-icons (MIT, img/flags/1x1/LICENSE-flag-icons); ASEAN
			 * flag: img/flags/asean.svg.
			 */
			$apt_flag_dir    = get_stylesheet_directory_uri() . '/img/flags/';
			$apt_flag_groups = array(
				'ASEAN'      => array(
					'bn'    => 'Brunei Darussalam',
					'kh'    => 'Kingdom of Cambodia',
					'id'    => 'Republic of Indonesia',
					'la'    => "Lao People's Democratic Republic",
					'my'    => 'Malaysia',
					'mm'    => 'Republic of the Union of Myanmar',
					'ph'    => 'Republic of the Philippines',
					'sg'    => 'Republic of Singapore',
					'th'    => 'Kingdom of Thailand',
					'tl'    => 'Democratic Republic of Timor-Leste',
					'vn'    => 'Socialist Republic of Viet Nam',
					'asean' => 'Association of Southeast Asian Nations (ASEAN)',
				),
				'Plus Three' => array(
					'cn' => "People's Republic of China",
					'jp' => 'Japan',
					'kr' => 'Republic of Korea',
				),
			);
			?>
			<div class="apt-hero-flags" role="group" aria-label="<?php esc_attr_e( 'ASEAN Member States, ASEAN, and China, Japan and the Republic of Korea', 'akseki-vi' ); ?>">
				<?php foreach ( $apt_flag_groups as $apt_group => $apt_flags ) : ?>
				<ul class="apt-flag-group">
					<?php foreach ( $apt_flags as $apt_code => $apt_name ) : ?>
					<li<?php echo 'asean' === $apt_code ? ' class="is-asean"' : ''; ?>><img src="<?php echo esc_url( $apt_flag_dir . ( 'asean' === $apt_code ? 'asean.svg' : '1x1/' . $apt_code . '.svg' ) ); ?>" alt="<?php echo esc_attr( $apt_name ); ?>" title="<?php echo esc_attr( $apt_name ); ?>" width="26" height="26" decoding="async"></li>
					<?php endforeach; ?>
				</ul>
				<?php endforeach; ?>
			</div>
		</div>

		<?php if ( $ftn_feature ) : ?>
		<a class="apt-hero-feature" href="<?php echo esc_url( $ftn_feature['link'] ); ?>">
			<?php /* LCP image: high priority, never lazy; intrinsic size prevents layout shift */ ?>
			<img src="<?php echo esc_url( $ftn_feature['img'] ); ?>" alt=""<?php if ( $ftn_feature['w'] && $ftn_feature['h'] ) : ?> width="<?php echo (int) $ftn_feature['w']; ?>" height="<?php echo (int) $ftn_feature['h']; ?>"<?php endif; ?> fetchpriority="high">
			<span class="apt-hero-feature-label">Latest</span>
			<span class="apt-hero-feature-cap">
				<span>
					<span class="apt-hero-feature-title"><?php echo esc_html( $ftn_feature['title'] ); ?></span>
				</span>
				<span class="apt-arrow" aria-hidden="true"></span>
			</span>
		</a>
		<?php endif; ?>
	</div>

	<div class="apt-hero-bottom">
		<?php if ( $ftn_highlights ) : ?>
		<h2 class="apt-highlights-title">APT Highlights</h2>
		<div class="apt-highlights">
			<?php foreach ( $ftn_highlights as $hl ) : ?>
			<a class="apt-highlight" href="<?php echo esc_url( $hl['link'] ); ?>" title="<?php echo esc_attr( $hl['title'] ); ?>">
				<span>
					<span class="apt-highlight-title"><?php echo wp_kses_post( $hl['short'] ); ?></span>
					<span class="apt-highlight-meta"><?php echo $hl['meta']; ?></span>
				</span>
				<span class="apt-arrow" aria-hidden="true"></span>
			</a>
			<?php endforeach; ?>
		</div>
		<?php endif; ?>

		<nav class="apt-quicklinks" aria-label="Quick links">
			<span class="apt-quicklinks-label">Quick links:</span>
			<a class="apt-arrow" href="#front-news">Latest News</a>
			<a class="apt-arrow" href="#front-about">About ASEAN Plus Three</a>
			<a class="apt-arrow" href="#front-resources">Key Documents</a>
			<a class="apt-arrow" href="#front-events">Upcoming Agenda</a>
		</nav>
	</div>
</section>
