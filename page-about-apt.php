<?php
/**
 * About ASEAN Plus Three (page slug about-apt): the ASEAN Secretariat's
 * description (verbatim), links to History and Areas of Cooperation, and the
 * flag line-up in protocol order (apt_flag_lineup_html()).
 */
get_header();
?>
	<main id="main" class="site-main">
		<div class="container" id="avi-content">
			<header class="apt-page-head">
				<h1><?php esc_html_e( 'About ASEAN Plus Three', 'akseki-vi' ); ?></h1>
			</header>

			<div class="apt-about">
				<div class="apt-about-text">
					<p class="apt-about-lede">ASEAN Plus Three (APT) consists of the 11 ASEAN Member States and the People&rsquo;s Republic of China, Japan and the Republic of Korea. The APT Cooperation process began in December 1997 and since than has evolve as the main vehicle to promote East Asian Cooperation towards the long-term goal of building an East Asian Community, with ASEAN as the driving force. The APT has become one of the most comprehensive cooperation frameworks in the region, and APT cooperation continue to be broadened and deepened in a wide range of areas, including political-security, trade and investment, finance, energy, tourism, agriculture and forestry, environment, education, health, culture and arts, etc, among others. The APT also supports the implementation of the ASEAN Community Vision 2045.</p>
					<p class="apt-about-source">Source: ASEAN Secretariat</p>
					<div class="apt-about-links">
						<a class="apt-pill apt-arrow" href="<?php echo esc_url( home_url( '/about-apt/history/' ) ); ?>"><?php esc_html_e( 'History', 'akseki-vi' ); ?></a>
						<a class="apt-pill apt-arrow" href="<?php echo esc_url( home_url( '/about-apt/areas-of-cooperation/' ) ); ?>"><?php esc_html_e( 'Areas of Cooperation', 'akseki-vi' ); ?></a>
					</div>
				</div>
				<?php echo apt_flag_lineup_html( 'apt-about-flags' ); // phpcs:ignore WordPress.Security.EscapeOutput -- escaped inside ?>
			</div>
		</div>
	</main>
<?php
get_footer();
