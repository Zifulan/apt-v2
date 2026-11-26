<?php
/**
 * Home: "Cooperation in Practice" — shared APT mechanisms.
 *
 * Each card says what the mechanism is, quoted word for word from a current
 * official source (sentences that are not next to each other in the source
 * are joined with "…"). No dated figures that go stale (the 2015 APTERR
 * rice quantity was removed). Do not paraphrase; if a source changes,
 * replace the quote. Sources (checked 26 November 2026):
 * - CMIM: AMRO, "AMRO and the CMIM",
 *   https://amro-asia.org/about-amro/amro-and-the-cmim/ (first and last
 *   sentences of the introduction).
 * - AMRO: AMRO, "Who we are", https://www.amro-asia.org/about-amro/who-we-are/
 *   (first sentence).
 * - ABMI: Joint Statement of the 29th ASEAN+3 Finance Ministers' and Central
 *   Bank Governors' Meeting, Samarkand, 3 May 2026, paras 13 and 14,
 *   https://asianbondsonline.adb.org/documents/asean3-fm-cbg-js/20260503.pdf
 *   The ABMI is to become the Asian Bond and Financial Markets Initiative
 *   (ABFMI) under the next roadmap (endorsement expected at the AFMGM+3 in
 *   2027): rename the card then.
 * - APTERR: ASEAN Secretariat, "Overview of ASEAN Plus Three Cooperation"
 *   (as of 14 November 2025), para 13,
 *   https://aseanplusthree.asean.org/wp-content/uploads/2026/01/Overview-of-APT-Cooperation-as-of-14-November-2025.pdf
 * - Section intro: Overview of ASEAN Plus Three Cooperation, para 3.
 */
$apt_mechanisms = array(
	array(
		'name' => 'Chiang Mai Initiative Multilateralisation (CMIM)',
		'text' => 'The Chiang Mai Initiative Multilateralisation (CMIM) serves as the ASEAN+3 region’s financial safety net – a multilateral currency swap arrangement under which participating economies commit to support one another in times of need. … Collectively, members commit <strong>US$240 billion</strong> to support one another when needed.',
		'url'  => '/about-cmim/',
		'label' => 'About the CMIM',
		'ext'  => 'https://amro-asia.org/about-amro/amro-and-the-cmim/',
		'ext_label' => 'CMIM on amro-asia.org',
	),
	array(
		'name' => 'ASEAN+3 Macroeconomic Research Office (AMRO)',
		'text' => 'Established in 2011 by ASEAN+3 Finance Ministers, the ASEAN+3 Macroeconomic Research Office (AMRO) is an international organization that aims to contribute to the macroeconomic and financial resilience and stability in the ASEAN+3 region.',
		'url'  => '/about-amro-working-committee/',
		'label' => 'About AMRO',
		'ext'  => 'https://amro-asia.org/',
		'ext_label' => 'amro-asia.org',
	),
	array(
		'name' => 'ASEAN Plus Three Emergency Rice Reserve (APTERR)',
		'text' => 'A key element of APT food security cooperation is the ASEAN Plus Three Emergency Rice Reserve (APTERR), established under the APTERR Agreement signed in October 2011 in Jakarta. APTERR aims to enhance food security, alleviate poverty, and eradicate malnutrition among members without disrupting normal trade.',
		'url'  => '/category/statements-and-declarations/director-general-level/economic-cooperations-director-general-level/apterr/',
		'label' => 'APTERR documents',
		'ext'  => 'https://www.apterr.org/',
		'ext_label' => 'apterr.org',
	),
	array(
		'name' => 'Asian Bond Markets Initiative (ABMI)',
		'text' => 'At their 29th Meeting on 3 May 2026, the ASEAN+3 Finance Ministers and Central Bank Governors stated: “We acknowledge the progress that the ABMI has achieved since the Asian Financial Crisis and appreciate the significant developments made in the local currency bond markets. … To mark a new stage of regional financial cooperation in ASEAN+3, we agreed to evolve the ABMI into the Asian Bond and Financial Markets Initiative (ABFMI) under the next roadmap, while local currency bond market development remains the anchor of the initiative.”',
		'url'  => '/chairmans-press-releaseon-the-asian-bond-markets-initiative/',
		'label' => 'ABMI press release',
		'ext'  => 'https://asianbondsonline.adb.org/documents/asean3-fm-cbg-js/20260503.pdf',
		'ext_label' => '2026 Joint Statement (PDF)',
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
			<a class="apt-pill apt-pill--light apt-arrow" href="<?php echo esc_url( home_url( '/overview-of-asean-plus-three-cooperation/' ) ); ?>">Read the full Overview</a>
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

		<p class="apt-stats-source apt-coop-source">Descriptions are quoted from AMRO&rsquo;s <a href="https://amro-asia.org/about-amro/amro-and-the-cmim/">AMRO and the CMIM</a> and <a href="https://www.amro-asia.org/about-amro/who-we-are/">Who we are</a> pages, the <a href="https://asianbondsonline.adb.org/documents/asean3-fm-cbg-js/20260503.pdf">Joint Statement of the 29th ASEAN+3 Finance Ministers&rsquo; and Central Bank Governors&rsquo; Meeting</a> (3 May 2026) and the ASEAN Secretariat&rsquo;s <a href="https://aseanplusthree.asean.org/wp-content/uploads/2026/01/Overview-of-APT-Cooperation-as-of-14-November-2025.pdf">Overview of ASEAN Plus Three Cooperation</a> (as of 14 November 2025).</p>
	</div>
</section>
