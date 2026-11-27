<section class="modern-about-section apt-section" id="front-about">
	<div class="container">
		<div class="apt-about-grid">
			<div class="apt-about-intro">
				<h2 class="apt-section-title">About <span class="text-nowrap">ASEAN Plus Three</span></h2>
				<div class="about-card-body">
						<?php $apt_about = apt_home_content( 'about' ); // Appearance → Home page content ?>
						<p class="about-card-text apt-section-lede"><?php echo wp_kses_post( $apt_about['text'] ); ?></p>
						<div class="about-links">
							<a href="<?php echo esc_url( home_url( '/about-apt/history/' ) ); ?>" class="apt-pill apt-pill--light apt-arrow">History</a>
							<a href="<?php echo esc_url( home_url( '/about-apt/areas-of-cooperation/' ) ); ?>" class="apt-pill apt-pill--light apt-arrow">Areas of Cooperation</a>
						</div>
				</div>
			</div>

			<div class="apt-about-stats">
				<?php
				$years = floor((strtotime("today")-strtotime("1997-12-15"))/31556952);
				$totmincount = apt_get_ministerial_meeting_count(); // meetings, not posts
				?>
				
				<div class="stats-grid">
					<div class="stat-card stat-card-blue">
						<div class="stat-number"><?php echo $years; ?></div>
						<div class="stat-label">Years of Cooperation</div>
					</div>
					
					<div class="stat-card stat-card-yellow">
						<?php $summits = apt_get_summit_count(); ?>
						<div class="stat-number"><?php echo esc_html( $summits ? $summits : '&mdash;' ); ?></div>
						<div class="stat-label">Summit Level Activities</div>
					</div>
					
					<div class="stat-card stat-card-red">
						<div class="stat-number"><?php echo $totmincount; ?></div>
						<div class="stat-label">Ministerial Level Meetings</div>
					</div>
					
					<?php
					// Figures: Appearance → Home page content → Figures (inc/home-content.php).
					$apt_figures = apt_home_content( 'figures' );
					$apt_icons   = apt_hc_icons();
					foreach ( $apt_figures as $i => $f ) :
						$icon = isset( $apt_icons[ $f['icon'] ] ) ? $apt_icons[ $f['icon'] ][1] : $apt_icons['investment'][1];
						$wide = ( count( $apt_figures ) % 2 ) && $i === count( $apt_figures ) - 1; // an odd last card fills the row
						?>
					<div class="stat-card stat-card-detail<?php echo $wide ? ' stat-card-wide' : ''; ?>">
						<div class="stat-icon">
							<svg width="32" height="32" viewBox="0 0 24 24" fill="none" aria-hidden="true"><?php echo $icon; // phpcs:ignore WordPress.Security.EscapeOutput -- fixed markup ?></svg>
						</div>
						<div class="stat-content">
							<div class="stat-value"><?php echo esc_html( $f['value'] ); ?></div>
							<div class="stat-desc"><?php echo wp_kses_post( $f['text'] ); ?><?php echo '' !== $f['note'] ? ' <em>(' . esc_html( $f['note'] ) . ')</em>' : ''; ?></div>
							<?php if ( '' !== $f['source'] ) : ?>
							<p class="stat-source"><?php esc_html_e( 'Source:', 'akseki-vi' ); ?> <?php echo $f['source_url'] ? '<a href="' . esc_url( apt_hc_link( $f['source_url'] ) ) . '">' . esc_html( $f['source'] ) . '</a>' : esc_html( $f['source'] ); ?></p>
							<?php endif; ?>
						</div>
					</div>
					<?php endforeach; ?>
				</div>
			</div>
		</div>
	</div>
</section>

