<section class="modern-about-section apt-section" id="front-about">
	<div class="container">
		<div class="apt-about-grid">
			<div class="apt-about-intro">
				<h2 class="apt-section-title"><span class="text-nowrap">ASEAN Plus Three</span> at a Glance</h2>
				<div class="about-card-body">
						<p class="about-card-text apt-section-lede">ASEAN Plus Three (APT) consist of ASEAN Member States and the People&rsquo;s Republic of China, Japan and the Republic of Korea. The APT Cooperation process began in December 1997 and since than has evolve as the main vehicle to promote East Asian Cooperation towards the long-term goal of building an East Asian Community, with ASEAN as the driving force. The APT has become one of the most comprehensive cooperation frameworks in the region, and APT cooperation continue to be broadened and deepened in a wide range of areas, including political-security, trade and investment, finance, energy, tourism, agriculture and forestry, environment, education, health, culture and arts, etc, among others. The APT also supports the implementation of the ASEAN Community Vision 2045.</p>
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
					 * Every label below is quoted word for word from its source; do not
					 * paraphrase. Update when new editions come out.
					 * - Trade and FDI: ASEAN Secretariat, "Overview of ASEAN Plus Three
					 *   Cooperation" (as of 14 November 2025), para 8.
					 * - Population, GDP, visitor arrivals: ASEANstats, ASEAN Statistical
					 *   Highlights 2025 (2024 data), https://www.aseanstats.org/publication/
					 * The intro paragraph is the site's own About page text (/about-apt/).
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
							<div class="stat-value">US$44.4 billion</div>
							<div class="stat-desc">FDI from the Plus Three countries <em>(preliminary ASEAN statistics for 2024)</em></div>
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
							<div class="stat-value">US$1.2 trillion</div>
							<div class="stat-desc">ASEAN and the Plus Three countries: two-way merchandise trade <em>(preliminary ASEAN statistics for 2024)</em></div>
						</div>
					</div>

					<div class="stat-card stat-card-detail">
						<div class="stat-icon">
							<svg width="32" height="32" viewBox="0 0 24 24" fill="none" aria-hidden="true">
								<path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2M9 11a4 4 0 100-8 4 4 0 000 8zM23 21v-2a4 4 0 00-3-3.87M16 3.13a4 4 0 010 7.75" stroke="currentColor" stroke-width="2"/>
							</svg>
						</div>
						<div class="stat-content">
							<div class="stat-value">2,277.5 million</div>
							<div class="stat-desc">ASEAN+3 population <em>(ASEAN+3 population as % world population: 28.1%)</em></div>
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
							<div class="stat-value">US$28.6 trillion</div>
							<div class="stat-desc">ASEAN+3 GDP <em>(ASEAN+3 GDP as % of world GDP (Nominal): 25.9%)</em></div>
						</div>
					</div>

					<div class="stat-card stat-card-detail stat-card-wide">
						<div class="stat-icon">
							<svg width="32" height="32" viewBox="0 0 24 24" fill="none" aria-hidden="true">
								<path d="M17.8 19.2 16 11l3.5-3.5C21 6 21.5 4 21 3c-1-.5-3 0-4.5 1.5L13 8 4.8 6.2c-.5-.1-.9.1-1.1.5l-.3.5c-.2.5-.1 1 .3 1.3L9 12l-2 3H4l-1 1 3 2 2 3 1-1v-3l3-2 3.5 5.3c.3.4.8.5 1.3.3l.5-.2c.4-.3.6-.7.5-1.2z" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/>
							</svg>
						</div>
						<div class="stat-content">
							<div class="stat-value">26.7%</div>
							<div class="stat-desc">Share of visitor arrivals from +3 countries <em>(ASEAN, 2024)</em></div>
						</div>
					</div>

					<p class="apt-stats-source">Sources: ASEAN Secretariat, <a href="https://aseanplusthree.asean.org/wp-content/uploads/2026/01/Overview-of-APT-Cooperation-as-of-14-November-2025.pdf">Overview of ASEAN Plus Three Cooperation</a> (as of 14 November 2025), for trade and FDI; ASEANstats, <a href="https://www.aseanstats.org/publication/ash2025/">ASEAN Statistical Highlights 2025</a>, for population, GDP and visitor arrivals.</p>
				</div>
			</div>
		</div>
	</div>
</section>

