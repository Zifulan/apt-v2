<?php
/**
 * Home: "Latest from Each Pillar".
 *
 * The newest statement in each pillar, quoted word for word and cited.
 * The quote is the statement's Excerpt (when it appears word for word in the
 * statement) or its opening sentences; see apt_statement_quote() in
 * functions.php. Pillar headings follow the ASEC "Overview of ASEAN Plus
 * Three Cooperation".
 */
$apt_pillar_quotes = apt_pillar_quotes();
if ( ! $apt_pillar_quotes ) {
	return;
}
?>
<section class="apt-section apt-pillars-section" id="front-pillars" aria-labelledby="front-pillars-title">
	<div class="container">
		<div class="apt-section-head">
			<div>
				<h2 class="apt-section-title" id="front-pillars-title">Latest from Each Pillar</h2>
				<p class="apt-section-lede">The most recent statement under each pillar, quoted from the statement itself.</p>
			</div>
			<a class="apt-pill apt-pill--light apt-arrow" href="<?php echo esc_url( home_url( '/category/statements-and-declarations/' ) ); ?>">All statements</a>
		</div>

		<ul class="apt-pillar-list">
			<?php foreach ( $apt_pillar_quotes as $key => $item ) : ?>
			<li class="apt-pillar-item pillar-<?php echo esc_attr( $key ); ?>">
				<h3 class="apt-pillar-name"><?php echo esc_html( $item['pillar'] ); ?></h3>
				<figure class="apt-pillar-quote">
					<blockquote cite="<?php echo esc_url( $item['url'] ); ?>">
						<p>&ldquo;<?php echo esc_html( $item['quote'] ); ?>&rdquo;</p>
					</blockquote>
					<figcaption>
						<cite><a href="<?php echo esc_url( $item['url'] ); ?>"><?php echo esc_html( $item['title'] ); ?></a></cite>,
						<?php echo esc_html( $item['date'] ); ?>
					</figcaption>
				</figure>
				<?php if ( $item['more'] ) : ?>
				<a class="apt-arrow apt-pillar-more" href="<?php echo esc_url( $item['more'] ); ?>">More statements<span class="sr-only"> on <?php echo esc_html( $item['pillar'] ); ?></span></a>
				<?php endif; ?>
			</li>
			<?php endforeach; ?>
		</ul>
	</div>
</section>
