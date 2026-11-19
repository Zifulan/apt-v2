<?php
/**
 * Home: "Cooperation in Practice" — shared APT mechanisms.
 *
 * Wording reviewed for neutrality: no superlatives, no country-by-country
 * contributions, no causal claims. Sources (checked November 2026):
 * - CMIM US$240 billion; Rapid Financing Facility in force 11 Nov 2026:
 *   https://amro-asia.org/about-amro/amro-and-the-cmim/ ,
 *   https://www.mof.go.jp/english/policy/international_policy/financial_cooperation_in_asia/cmi/20261110211148.html
 * - CMIM Agreement in force 24 March 2010 (US$120 billion, doubled in 2014): amro-asia.org.
 * - APTERR Agreement signed 7 October 2011, in force 12 July 2012 (year shown):
 *   https://cil.nus.edu.sg/databasecil/2011-asean-plus-three-emergency-rice-reserve-agreement/ ,
 *   https://www.mofa.go.jp/mofaj/press/release/24/7/0710_05.html
 * - AMRO established 2011, international organisation 2016: amro-asia.org (same page).
 * - APTERR 787,000 tonnes earmarked; Typhoon Haiyan 2013: asean.org APTERR factsheet,
 *   https://www.asean.org/wp-content/uploads/images/2015/October/outreach-document/Edited%20APTERR-2.pdf
 * - ABMI August 2003, "Asian savings for Asian investment": Japan MOF,
 *   https://www.mof.go.jp/english/policy/international_policy/financial_cooperation_in_asia/index.html
 */
$apt_mechanisms = array(
	array(
		'name' => 'Chiang Mai Initiative Multilateralisation (CMIM)',
		'meta' => 'Financial stability &middot; since 2010',
		'text' => 'A regional financial safety net of <strong>US$240 billion</strong>. Participants commit to support one another through currency swaps if a member faces balance-of-payments or short-term liquidity needs. A Rapid Financing Facility for urgent needs took effect in 2026.',
		'url'  => '/category/statements-and-declarations/working-expert-group/economic-cooperations-working-expert-group/wg-cmim/',
	),
	array(
		'name' => 'ASEAN+3 Macroeconomic Research Office (AMRO)',
		'meta' => 'Economic surveillance &middot; since 2011',
		'text' => 'Monitors and analyses regional economies and supports the CMIM, contributing to the economic and financial resilience of the region. AMRO became an international organisation in 2016.',
		'url'  => '/category/statements-and-declarations/working-expert-group/economic-cooperations-working-expert-group/amro-working-committee/',
	),
	array(
		'name' => 'ASEAN Plus Three Emergency Rice Reserve (APTERR)',
		'meta' => 'Food security &middot; since 2012',
		'text' => '<strong>787,000 tonnes</strong> of rice earmarked by APT countries to respond to emergencies such as natural disasters. Among other uses, rice from the reserve was distributed in the Philippines after Typhoon Haiyan (Yolanda) in 2013.',
		'url'  => '/category/statements-and-declarations/director-general-level/economic-cooperations-director-general-level/apterr/',
	),
	array(
		'name' => 'Asian Bond Markets Initiative (ABMI)',
		'meta' => 'Capital markets &middot; since 2003',
		'text' => 'Supports the development of local-currency bond markets in the region, enabling better use of Asian savings for Asian investment.',
		'url'  => '/chairmans-press-releaseon-the-asian-bond-markets-initiative/',
	),
);
?>?>
<section class="apt-section apt-coop-section" id="front-cooperation" aria-labelledby="front-cooperation-title">
	<div class="container">
		<div class="apt-section-head">
			<div>
				<h2 class="apt-section-title" id="front-cooperation-title">Cooperation in Practice</h2>
				<p class="apt-section-lede apt-coop-lede">The ASEAN Plus Three process began in December 1997, against the backdrop of the Asian financial crisis. Since then, APT countries have developed shared mechanisms that support financial stability and food security in the region.</p>
			</div>
			<a class="apt-pill apt-pill--light apt-arrow" href="<?php echo esc_url( home_url( '/about-apt/areas-of-cooperation/' ) ); ?>">Areas of Cooperation</a>
		</div>

		<ul class="apt-coop-list">
			<?php foreach ( $apt_mechanisms as $m ) : ?>
			<li class="apt-coop-item">
				<h3 class="apt-coop-name"><a href="<?php echo esc_url( home_url( $m['url'] ) ); ?>"><?php echo esc_html( $m['name'] ); ?></a></h3>
				<p class="apt-coop-meta"><?php echo wp_kses_post( $m['meta'] ); ?></p>
				<p class="apt-coop-text"><?php echo wp_kses_post( $m['text'] ); ?></p>
			</li>
			<?php endforeach; ?>
		</ul>

		<p class="apt-stats-source apt-coop-source">Sources: the APT pages linked above; <a href="https://amro-asia.org/about-amro/amro-and-the-cmim/">AMRO</a> (CMIM and AMRO); <a href="https://www.asean.org/wp-content/uploads/images/2015/October/outreach-document/Edited%20APTERR-2.pdf">ASEAN Secretariat</a> (APTERR reserve and its use in 2013); <a href="https://cil.nus.edu.sg/databasecil/2011-asean-plus-three-emergency-rice-reserve-agreement/">NUS Centre for International Law</a> (APTERR entry into force); <a href="https://www.mof.go.jp/english/policy/international_policy/financial_cooperation_in_asia/index.html">Ministry of Finance, Japan</a> (ABMI).</p>
	</div>
</section>
