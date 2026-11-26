				<footer class="apt-footer">
					<div class="container">
						<div class="apt-footer-top">
							<a class="apt-brand apt-footer-brand" href="<?php echo esc_url( home_url( '/' ) ); ?>" aria-label="ASEAN Plus Three - Home">
								<?php echo str_replace( 'id="logo"', 'id="logo-footer"', load_logo_svg() ); ?>
								<span class="apt-brand-text" aria-hidden="true"><span>ASEAN</span> <span>Plus</span> <span>Three</span></span>
							</a>
							<ul class="apt-social" aria-label="ASEAN Secretariat on social media">
								<li><a href="https://facebook.com/aseansecretariat" target="_blank" rel="noopener" aria-label="Facebook"><svg class="apt-social-icon" viewBox="0 0 24 24" width="20" height="20" fill="currentColor" aria-hidden="true" focusable="false"><path d="M13.5 22v-8h2.7l.4-3.2h-3.1V8.8c0-.9.3-1.5 1.6-1.5h1.7V4.4c-.3 0-1.3-.1-2.5-.1-2.5 0-4.1 1.5-4.1 4.2v2.3H7.4V14h2.8v8z"/></svg></a></li>
								<li><a href="https://www.instagram.com/asean/" target="_blank" rel="noopener" aria-label="Instagram"><svg class="apt-social-icon" viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true" focusable="false"><rect x="3" y="3" width="18" height="18" rx="5"/><circle cx="12" cy="12" r="4"/><circle cx="17.5" cy="6.5" r="0.6" fill="currentColor"/></svg></a></li>
								<li><a href="https://twitter.com/ASEAN" target="_blank" rel="noopener" aria-label="X (Twitter)"><svg class="apt-social-icon" viewBox="-2 -2 28 28" width="20" height="20" fill="currentColor" aria-hidden="true" focusable="false"><path d="M18.9 1.15h3.68l-8.04 9.19L24 22.85h-7.41l-5.8-7.58-6.64 7.58H.47l8.6-9.83L0 1.15h7.59l5.25 6.93zm-1.29 19.5h2.04L6.49 3.24H4.3z"/></svg></a></li>
								<li><a href="https://www.youtube.com/user/theaseansecretariat" target="_blank" rel="noopener" aria-label="YouTube"><svg class="apt-social-icon" viewBox="0 0 24 24" width="20" height="20" fill="currentColor" aria-hidden="true" focusable="false"><path fill-rule="evenodd" d="M23 7.2a3 3 0 0 0-2.1-2.1C19 4.6 12 4.6 12 4.6s-7 0-8.9.5A3 3 0 0 0 1 7.2 31 31 0 0 0 .5 12a31 31 0 0 0 .5 4.8 3 3 0 0 0 2.1 2.1c1.9.5 8.9.5 8.9.5s7 0 8.9-.5a3 3 0 0 0 2.1-2.1 31 31 0 0 0 .5-4.8 31 31 0 0 0-.5-4.8zM9.8 15.2V8.8l5.6 3.2z"/></svg></a></li>
								<li><a href="https://www.linkedin.com/company/the-asean-secretariat" target="_blank" rel="noopener" aria-label="LinkedIn"><svg class="apt-social-icon" viewBox="0 0 24 24" width="20" height="20" fill="currentColor" aria-hidden="true" focusable="false"><path d="M5 3.5a2.5 2.5 0 1 1 0 5 2.5 2.5 0 0 1 0-5zM3 9.5h4V21H3zm6.5 0h3.8v1.6h.1c.5-1 1.8-2 3.7-2 4 0 4.7 2.6 4.7 6V21h-4v-5.1c0-1.2 0-2.8-1.7-2.8s-2 1.3-2 2.7V21h-4z"/></svg></a></li>
							</ul>
						</div>

						<div class="apt-footer-cols">
							<?php
							// Same menu as the header panel, top level + first level only.
							wp_nav_menu( array(
								'theme_location' => 'primary',
								'depth'          => 2,
								'container'      => false,
								'menu_class'     => 'apt-footer-menu',
								'menu_id'        => 'apt-footer-menu',
								'fallback_cb'    => false,
								'apt_footer'     => true,
							) );
							?>
							<div>
								<?php $apt_contact = apt_secretariat_contact(); ?>
								<h2 class="apt-footer-heading"><?php echo esc_html( $apt_contact['name'] ); ?></h2>
								<address class="apt-footer-address">
									<?php foreach ( $apt_contact['address'] as $line ) : ?><?php echo esc_html( $line ); ?><br><?php endforeach; ?>
									<?php foreach ( $apt_contact['phones'] as $ph ) : ?><?php echo esc_html( $ph[0] . ', ' . $ph[2] ); ?><br><?php endforeach; ?>
									<a href="mailto:<?php echo esc_attr( $apt_contact['email'] ); ?>"><?php echo esc_html( $apt_contact['email'] ); ?></a>
								</address>
							</div>
							<div>
								<h2 class="apt-footer-heading">Links</h2>
								<ul>
									<li><a class="apt-ext" href="https://asean.org/" target="_blank" rel="noopener">ASEAN Website</a></li>
									<li><a class="apt-ext" href="https://asean2026.gov.ph/" target="_blank" rel="noopener">ASEAN Philippines 2026</a></li>
									<li><a class="apt-ext" href="https://www.eastasiabusiness.org/" target="_blank" rel="noopener">East Asia Business Council</a></li>
								</ul>
							</div>
						</div>

						<div class="apt-footer-bottom">
							<span>ASEAN Plus Three Cooperation</span>
							<span>&copy; <?php echo esc_html( date( 'Y' ) ); ?> ASEAN Plus Three. All rights reserved.</span>
						</div>
					</div>
				</footer>
			</div><!-- #wrapper -->

<?php wp_footer(); ?>
</body>
</html>
