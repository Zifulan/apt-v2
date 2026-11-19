<section class="modern-about-section apt-section" id="front-about">
	<div class="container">
		<div class="apt-about-grid">
			<div class="apt-about-intro">
				<h2 class="apt-section-title">ASEAN+3 at a Glance</h2>
				<div class="about-card-body">
						<p class="about-card-text apt-section-lede">ASEAN Plus Three (APT) consist of ASEAN Member States and the People's Republic of China, Japan and the Republic of Korea. The APT Cooperation process began in December 1997 and since than has evolve as the main vehicle to promote East Asian Cooperation towards the long-term goal of building an East Asian Community, with ASEAN as the driving force. The APT has become one of the most comprehensive cooperation frameworks in the region, and APT cooperation continue to be broadened and deepened in a wide range of areas, including political-security, trade and investment, finance, energy, tourism, agriculture and forestry, environment, education, health, culture and arts, etc, among others. The APT also supports the implementation of the ASEAN Community Vision 2045.</p>
						<div class="about-links">
							<a href="<?php echo esc_url( home_url( '/about-apt/history/' ) ); ?>" class="apt-pill apt-pill--light apt-arrow">History</a>
							<a href="<?php echo esc_url( home_url( '/about-apt/areas-of-cooperation/' ) ); ?>" class="apt-pill apt-pill--light apt-arrow">Areas of Cooperation</a>
						</div>
				</div>
			</div>

			<div class="apt-about-stats">
				<?php
				$years = floor((strtotime("today")-strtotime("1997-12-15"))/31556952);
				$min2 = akseki_get_ministrial_categories();
				$totmincount = 0;
				foreach($min2 as $min){			
					$catObj = get_category_by_slug($min); 
					$catcount = $catObj->category_count;
					$totmincount += $catcount;
				}
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
						<div class="stat-label">Ministerial Level Activities</div>
					</div>
					
					<?php
					/*
					 * ASEANstats figures (2024 data). Update when new editions come out:
					 * ASEAN Statistical Highlights (ASH) and ASEAN Key Figures (AKF),
					 * https://www.aseanstats.org/publication/
					 *
					 * Trade with China, Japan and the Republic of Korea is not published as one total.
					 * It is worked out from ASH2025: exports/imports shares by partner
					 * x total exports (1,952.9) / imports (1,891.4), with the missing
					 * side from each published trade balance. ~US$1,219B = 31.7% of
					 * total goods trade (3,844.3).
					 *
					 * Visitors from China, Japan and the Republic of Korea: ASH2025 total arrivals (127.1M)
					 * x share from +3 countries (26.7%).
					 */
					?>
					<div class="stat-card stat-card-detail">
						<div class="stat-icon">
							<svg width="32" height="32" viewBox="0 0 24 24" fill="none" aria-hidden="true">
								<path d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z" stroke="currentColor" stroke-width="2"/>
								<path d="M9 10h6M9 14h6" stroke="currentColor" stroke-width="2"/>
							</svg>
						</div>
						<div class="stat-content">
							<div class="stat-value">US$ 230.8 billion</div>
							<div class="stat-desc">Foreign direct investment into ASEAN in 2024 <em>(double the US$ 115.4 billion of 2015)</em></div>
						</div>
					</div>

					<div class="stat-card stat-card-detail">
						<div class="stat-icon">
							<svg width="32" height="32" viewBox="0 0 24 24" fill="none" aria-hidden="true">
								<path d="M3 3h18v18H3z" stroke="currentColor" stroke-width="2"/>
								<path d="M8 8h8M8 12h8M8 16h4" stroke="currentColor" stroke-width="2"/>
							</svg>
						</div>
						<div class="stat-content">
							<div class="stat-value">US$ 1.22 trillion</div>
							<div class="stat-desc">ASEAN trade in goods with China, Japan and the Republic of Korea in 2024 <em>(almost a third of all ASEAN trade)</em></div>
						</div>
					</div>

					<div class="stat-card stat-card-detail">
						<div class="stat-icon">
							<svg width="32" height="32" viewBox="0 0 24 24" fill="none" aria-hidden="true">
								<path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2M9 11a4 4 0 100-8 4 4 0 000 8zM23 21v-2a4 4 0 00-3-3.87M16 3.13a4 4 0 010 7.75" stroke="currentColor" stroke-width="2"/>
							</svg>
						</div>
						<div class="stat-content">
							<div class="stat-value">2.28 billion</div>
							<div class="stat-desc">People in ASEAN+3 <em>(28.1% of the world&rsquo;s population)</em></div>
						</div>
					</div>

					<div class="stat-card stat-card-detail">
						<div class="stat-icon">
							<svg width="32" height="32" viewBox="0 0 24 24" fill="none" aria-hidden="true">
								<circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="2"/>
								<path d="M12 6v6l4 4" stroke="currentColor" stroke-width="2"/>
							</svg>
						</div>
						<div class="stat-content">
							<div class="stat-value">US$ 28.6 trillion</div>
							<div class="stat-desc">Combined GDP of ASEAN+3 <em>(about a quarter of the world economy, 25.9%)</em></div>
						</div>
					</div>

					<div class="stat-card stat-card-detail stat-card-wide">
						<div class="stat-icon">
							<svg width="32" height="32" viewBox="0 0 24 24" fill="none" aria-hidden="true">
								<path d="M17.8 19.2 16 11l3.5-3.5C21 6 21.5 4 21 3c-1-.5-3 0-4.5 1.5L13 8 4.8 6.2c-.5-.1-.9.1-1.1.5l-.3.5c-.2.5-.1 1 .3 1.3L9 12l-2 3H4l-1 1 3 2 2 3 1-1v-3l3-2 3.5 5.3c.3.4.8.5 1.3.3l.5-.2c.4-.3.6-.7.5-1.2z" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/>
							</svg>
						</div>
						<div class="stat-content">
							<div class="stat-value">33.9 million</div>
							<div class="stat-desc">Visitors to ASEAN from China, Japan and the Republic of Korea in 2024 <em>(about 1 in 4 of all 127.1 million visitors)</em></div>
						</div>
					</div>

					<p class="apt-stats-source">2024 figures. Source: ASEANstats, <a href="https://www.aseanstats.org/publication/ash2025/">ASEAN Statistical Highlights 2025</a> and <a href="https://www.aseanstats.org/publication/akf2025/">ASEAN Key Figures 2025</a>. Trade and visitor totals for China, Japan and the Republic of Korea are worked out from the published shares.</p>
				</div>
			</div>
		</div>
	</div>
</section>

