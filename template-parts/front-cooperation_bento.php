<?php
/**
 * Home: "Cooperation in Practice" — shared APT mechanisms.
 *
 * The intro, cards and source line are edited under Appearance → Home page
 * content → Cooperation cards (inc/home-content.php); the original text is
 * in apt_home_content_defaults().
 *
 * Each card says what the mechanism is, quoted word for word from a current
 * official source (sentences that are not next to each other in the source
 * are joined with "…"). No dated figures that go stale (the 2015 APTERR
 * rice quantity was removed). Do not paraphrase; if a source changes,
 * replace the quote. Sources (checked 26 November 2026):
 * - CMIM: "About CMIM" page on this site, /about-cmim/ (first sentence).
 * - AMRO: AMRO, "Who we are", https://www.amro-asia.org/about-amro/who-we-are/
 *   (first sentence).
 * - ABMI: ASEAN Secretariat, "Finance Cooperation (ASEAN+3)",
 *   https://asean.org/our-communities/economic-community/finance-integration/finance-cooperation-asean-3/
 *   (ABMI entry, first sentence); the quoted ABFMI phrase is from the Joint
 *   Statement of the 29th ASEAN+3 Finance Ministers' and Central Bank
 *   Governors' Meeting, Samarkand, 3 May 2026, para 14, on this
 *   site: /wp-content/uploads/2026/05/20260502-Joint-Statement_29th-AFMGM3_final.pdf
 *   The ABMI is to become the Asian Bond and Financial Markets Initiative
 *   (ABFMI) under the next roadmap (endorsement expected at the AFMGM+3 in
 *   2027): rename the card then.
 * - APTERR: ASEAN Secretariat, "Overview of ASEAN Plus Three Cooperation"
 *   (as of 14 November 2025), para 13,
 *   https://aseanplusthree.asean.org/wp-content/uploads/2026/01/Overview-of-APT-Cooperation-as-of-14-November-2025.pdf
 * - Section intro: Overview of ASEAN Plus Three Cooperation, para 3.
 */
$apt_coop       = apt_home_content( 'coop' ); // Appearance → Home page content → Cooperation cards
$apt_mechanisms = $apt_coop['cards'];
?>
<section class="apt-section apt-coop-section" id="front-cooperation" aria-labelledby="front-cooperation-title">
	<div class="container">
		<div class="apt-section-head">
			<div>
				<h2 class="apt-section-title" id="front-cooperation-title">Cooperation in Practice</h2>
				<p class="apt-section-lede apt-coop-lede"><?php echo wp_kses_post( $apt_coop['intro'] ); ?></p>
			</div>
			<a class="apt-pill apt-pill--light apt-arrow" href="<?php echo esc_url( home_url( '/overview-of-asean-plus-three-cooperation/' ) ); ?>">Read the full Overview</a>
		</div>

		<ul class="apt-coop-list">
			<?php foreach ( $apt_mechanisms as $m ) : ?>
			<li class="apt-coop-item">
				<h3 class="apt-coop-name"><?php echo esc_html( $m['name'] ); ?></h3>
				<p class="apt-coop-text"><?php echo wp_kses_post( $m['text'] ); ?></p>
				<p class="apt-coop-links">
					<?php if ( '' !== $m['url'] && '' !== $m['label'] ) : ?><a class="apt-arrow" href="<?php echo esc_url( apt_hc_link( $m['url'] ) ); ?>"><?php echo esc_html( $m['label'] ); ?></a><?php endif; ?>
					<?php if ( '' !== $m['ext'] && '' !== $m['ext_label'] ) : ?><a class="apt-ext" href="<?php echo esc_url( $m['ext'] ); ?>" rel="external"><?php echo esc_html( $m['ext_label'] ); ?><span class="sr-only"> (external site)</span></a><?php endif; ?>
				</p>
			</li>
			<?php endforeach; ?>
		</ul>

		<p class="apt-stats-source apt-coop-source"><?php echo wp_kses_post( $apt_coop['sources'] ); ?></p>
	</div>
</section>
