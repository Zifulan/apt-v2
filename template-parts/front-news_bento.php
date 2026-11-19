<section class="modern-news-section apt-section" id="front-news">
	<div class="container">
		<div class="apt-news-layout">
		<div class="apt-news-intro">
			<h2 class="apt-section-title">Latest News &amp; Activities</h2>
			<p class="apt-section-lede">Statements and outcomes from ASEAN Plus Three meetings.</p>
			<a class="apt-pill apt-pill--light apt-arrow" href="<?php echo esc_url( get_category_link( 7 ) ); ?>">All news</a>
		</div>
		<div class="apt-carousel" role="region" aria-roledescription="carousel" aria-label="Latest news and activities">
			<div class="apt-carousel-controls">
				<button type="button" class="apt-carousel-btn" data-direction="previous" aria-label="Previous slide">
					<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M19 12H5M11 6l-6 6 6 6"/></svg>
				</button>
				<button type="button" class="apt-carousel-btn" data-direction="next" aria-label="Next slide">
					<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
				</button>
			</div>
			<div class="apt-carousel-track" tabindex="0" role="group" aria-label="Slides">
			<?php
			$descats = get_categories(array('child_of' => 7));
			$descats_arr = wp_list_pluck($descats, 'slug');
			$fpn_cats = akseki_get_ministrial_categories();
			array_push($fpn_cats,'summit-level-statements','news');
			$latest_args = [
				'post_type' => 'post',
				'post_status' => 'publish',
				'order'=>'DESC',
				'posts_per_page' => 12,
				'category_name'=> implode( ',', $descats_arr )
			];										
			$latest_query = new WP_Query( $latest_args ); 
			
			if ( $latest_query->have_posts() ) :
				$key = 1;
				$total = (int) $latest_query->post_count;
				while ( $latest_query->have_posts() ) : $latest_query->the_post(); 
					$card = apt_news_card_html( 'h3' );
					$short = akseki_get_shortname( get_the_title(), get_the_ID(), 0 );
					$latest_card = '
						<div class="apt-slide" role="group" aria-roledescription="slide" aria-label="'.$key.' of '.$total.'" data-marker-name="'.esc_attr('Go to slide '.$key.': '.wp_strip_all_tags($short)).'">
							'.$card.'
						</div>';
					echo($latest_card);
					++$key;
				endwhile;
			endif;
			wp_reset_postdata();
			?>
			</div>
			<p class="screen-reader-text apt-carousel-status" role="status" aria-live="polite"></p>
			<nav class="apt-carousel-markers" aria-label="Choose a slide"></nav>
		</div>
		</div>
	</div>
</section>
