<?php
/**
 * Home: "Cooperation in Practice" — shared APT mechanisms.
 *
 * Every description below is quoted word for word from an ASEAN Secretariat
 * or official APT document. Do not paraphrase; if a source changes, replace
 * the quote. Sources (checked 19 November 2026):
 * - CMIM: "About CMIM" page on this site, /about-cmim/ (first sentence).
 * - AMRO: "About AMRO" page on this site, /about-amro-working-committee/ (Mission).
 * - APTERR: ASEAN Secretariat, "Overview of ASEAN Plus Three Cooperation"
 *   (as of 14 November 2025), para 13,
 *   https://aseanplusthree.asean.org/wp-content/uploads/2026/01/Overview-of-APT-Cooperation-as-of-14-November-2025.pdf
 *   and ASEAN Secretariat factsheet "ASEAN Food Security: Ensuring the Supply of Rice in the Region",
 *   https://www.asean.org/wp-content/uploads/images/2015/October/outreach-document/Edited%20APTERR-2.pdf
 * - ABMI: Chairman's Press Release on the Asian Bond Markets Initiative,
 *   /chairmans-press-releaseon-the-asian-bond-markets-initiative/ (first sentence).
 * - Section intro: Overview of ASEAN Plus Three Cooperation, para 3.
 */
$apt_mechanisms = array(
	array(
		'name' => 'Chiang Mai Initiative Multilateralisation (CMIM)',
		'text' => 'With a size of <strong>US$240 billion</strong>, the CMIM is a multilateral currency swap arrangement among ASEAN+3 members, established under the agreement signed by the ASEAN+3 Finance Ministers and Central Bank Governors that came into effect on March 24, 2010.',
		'url'  => '/about-cmim/',
		'label' => 'About the CMIM',
		'ext'  => 'https://amro-asia.org/about-amro/amro-and-the-cmim/',
		'ext_label' => 'CMIM on amro-asia.org',
	),
	array(
		'name' => 'ASEAN+3 Macroeconomic Research Office (AMRO)',
		'text' => 'Mission: To contribute to the macroeconomic and financial resilience and stability of the region through surveillance, supporting regional financing arrangements, providing technical assistance, serving as a regional knowledge hub and facilitating ASEAN+3 financial cooperation.',
		'url'  => '/about-amro-working-committee/',
		'label' => 'About AMRO',
		'ext'  => 'https://amro-asia.org/',
		'ext_label' => 'amro-asia.org',
	),
	array(
		'name' => 'ASEAN Plus Three Emergency Rice Reserve (APTERR)',
		'text' => 'APTERR aims to enhance food security, alleviate poverty, and eradicate malnutrition among members without disrupting normal trade. At the initial stage of operation, the total earmarked quantity of rice reserve contributed by the ASEAN Plus Three Countries amounts to <strong>787,000 MT</strong>.',
		'url'  => '/category/statements-and-declarations/director-general-level/economic-cooperations-director-general-level/apterr/',
		'label' => 'APTERR documents',
		'ext'  => 'https://www.apterr.org/',
		'ext_label' => 'apterr.org',
	),
	array(
		'name' => 'Asian Bond Markets Initiative (ABMI)',
		'text' => 'The Asian Bond Markets Initiative (ABMI) aims to develop efficient and liquid bond markets in Asia, which would enable better utilization of Asian savings for Asian investments.',
		'url'  => '/chairmans-press-releaseon-the-asian-bond-markets-initiative/',
		'label' => 'ABMI press release',
		'ext'  => 'https://asianbondsonline.adb.org/abmi.php',
		'ext_label' => 'AsianBondsOnline (ADB)',
	),
);
?>
<section class="apt-section apt-coop-section" id="front-cooperation" aria-labelledby="front-cooperation-title">
	<div class="container">
		<div class="apt-section-head">
			<div>
				<h2 class="apt-section-title" id="front-cooperation-title">Cooperation in Practice</h2>
				<p class="apt-section-lede apt-coop-lede">The APT framework has become a cornerstone of East Asian regionalism, fostering cooperation in political-security, economic, and socio-cultural areas. The APT process has also developed into a full-fledged cooperation framework with 59 mechanisms coordinating various initiatives.</p>
			</div>
			<a class="apt-pill apt-pill--light apt-arrow" href="<?php echo esc_url( home_url( '/about-apt/areas-of-cooperation/' ) ); ?>">Areas of Cooperation</a>
		</div>

		<ul class="apt-coop-list">
			<?php foreach ( $apt_mechanisms as $m ) : ?>
			<li class="apt-coop-item">
				<h3 class="apt-coop-name"><?php echo esc_html( $m['name'] ); ?></h3>
				<p class="apt-coop-text"><?php echo wp_kses_post( $m['text'] ); ?></p>
				<p class="apt-coop-links">
					<a class="apt-arrow" href="<?php echo esc_url( home_url( $m['url'] ) ); ?>"><?php echo esc_html( $m['label'] ); ?></a>
					<a class="apt-ext" href="<?php echo esc_url( $m['ext'] ); ?>" rel="external"><?php echo esc_html( $m['ext_label'] ); ?><span class="sr-only"> (external site)</span></a>
				</p>
			</li>
			<?php endforeach; ?>
		</ul>

		<p class="apt-stats-source apt-coop-source">Descriptions are quoted from the APT pages linked above, the ASEAN Secretariat&rsquo;s <a href="https://aseanplusthree.asean.org/wp-content/uploads/2026/01/Overview-of-APT-Cooperation-as-of-14-November-2025.pdf">Overview of ASEAN Plus Three Cooperation</a> (as of 14 November 2025) and its <a href="https://www.asean.org/wp-content/uploads/images/2015/October/outreach-document/Edited%20APTERR-2.pdf">ASEAN Food Security factsheet</a>.</p>
	</div>
</section>
