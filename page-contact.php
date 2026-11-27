<?php
/**
 * The Contact page (WordPress uses this file for the page with the slug
 * "contact"). The details come from apt_secretariat_contact(), shared with
 * the footer, and are edited under Appearance → Home page content → Contact. The page's own content in the
 * editor (the old map and address) is not shown.
 *
 * @package apt-v2
 */

get_header();
$apt_contact = apt_secretariat_contact();
$apt_icon    = function ( $path ) {
	return '<span class="apt-contact-ico" aria-hidden="true"><svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" focusable="false">' . $path . '</svg></span>';
};
?>

	<main id="main" class="site-main site-single">
		<div class="container">
			<?php while ( have_posts() ) : the_post(); ?>
			<div class="row akseki-detail-style1">
				<div class="col-md-12">
					<article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
						<?php get_template_part( 'template-parts/article-header' ); ?>
						<div class="entry-content">
							<div class="apt-contact">
								<div class="apt-contact-card">
									<h2><?php echo esc_html( $apt_contact['name'] ); ?></h2>
									<ul class="apt-contact-rows">
										<li>
											<?php echo $apt_icon( '<path d="M12 21s-7-6.2-7-11.5A7 7 0 0 1 19 9.5C19 14.8 12 21 12 21z"/><circle cx="12" cy="9.5" r="2.5"/>' ); // phpcs:ignore WordPress.Security.EscapeOutput -- fixed markup ?>
											<div><span class="apt-contact-label"><?php esc_html_e( 'Address', 'akseki-vi' ); ?></span>
												<address><?php echo implode( '<br>', array_map( 'esc_html', $apt_contact['address'] ) ); // phpcs:ignore WordPress.Security.EscapeOutput -- escaped ?></address>
											</div>
										</li>
										<li>
											<?php echo $apt_icon( '<path d="M5 4h4l2 5-2.5 1.5a11 11 0 0 0 5 5L15 13l5 2v4a2 2 0 0 1-2 2A16 16 0 0 1 3 6a2 2 0 0 1 2-2"/>' ); // phpcs:ignore WordPress.Security.EscapeOutput -- fixed markup ?>
											<div><span class="apt-contact-label"><?php esc_html_e( 'Phone', 'akseki-vi' ); ?></span>
												<?php foreach ( $apt_contact['phones'] as $i => $ph ) : ?>
													<?php echo $i ? '<br>' : ''; ?><a href="tel:<?php echo esc_attr( $ph[1] ); ?>"><?php echo esc_html( $ph[0] ); ?></a><?php if ( '' !== $ph[2] ) : ?>, <a href="tel:<?php echo esc_attr( $ph[3] ); ?>"><?php echo esc_html( $ph[2] ); ?></a><?php endif; ?>
												<?php endforeach; ?>
											</div>
										</li>
										<li>
											<?php echo $apt_icon( '<rect x="3" y="5" width="18" height="14" rx="2"/><path d="m3 7 9 6 9-6"/>' ); // phpcs:ignore WordPress.Security.EscapeOutput -- fixed markup ?>
											<div><span class="apt-contact-label"><?php esc_html_e( 'Email', 'akseki-vi' ); ?></span>
												<a href="mailto:<?php echo esc_attr( $apt_contact['email'] ); ?>"><?php echo esc_html( $apt_contact['email'] ); ?></a>
											</div>
										</li>
									</ul>
								</div>
								<div class="apt-contact-map">
									<div class="apt-contact-frame">
										<iframe src="<?php echo esc_url( $apt_contact['map'] ); ?>" title="<?php echo esc_attr( sprintf( /* translators: 1: name, 2: first line of the address */ __( 'Map: %1$s, %2$s', 'akseki-vi' ), $apt_contact['name'], isset( $apt_contact['address'][0] ) ? $apt_contact['address'][0] : '' ) ); ?>" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
									</div>
									<a class="apt-pill apt-ext apt-contact-route" href="<?php echo esc_url( $apt_contact['route'] ); ?>" target="_blank" rel="noopener"><?php esc_html_e( 'Get directions', 'akseki-vi' ); ?><span class="screen-reader-text"> <?php esc_html_e( '(opens Google Maps in a new tab)', 'akseki-vi' ); ?></span></a>
								</div>
							</div>
						</div>
					</article>
				</div>
			</div>
			<?php endwhile; ?>
		</div>
	</main><!-- #main -->

<?php
get_footer();
