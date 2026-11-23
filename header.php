<!doctype html>
<html <?php language_attributes(); ?>>
	<head>
        <?php if ( 'production' === wp_get_environment_type() ) : // staging copies: define( 'WP_ENVIRONMENT_TYPE', 'staging' ) to skip analytics ?>
        <script>
          window.dataLayer = window.dataLayer || [];
          function gtag(){dataLayer.push(arguments);}
          gtag('js', new Date());
          gtag('config', 'G-V2E02382TY');
          /* Analytics loads after the page: on the first interaction, or 3 s
             after load, so it does not compete with rendering on phones.
             Calls made before then wait in dataLayer and are sent once it loads. */
          (function () {
            var done = false;
            function load() {
              if (done) { return; }
              done = true;
              var s = document.createElement('script');
              s.async = true;
              s.src = 'https://www.googletagmanager.com/gtag/js?id=G-V2E02382TY';
              document.head.appendChild(s);
            }
            ['pointerdown', 'keydown', 'scroll', 'touchstart'].forEach(function (e) {
              window.addEventListener(e, load, { once: true, passive: true });
            });
            window.addEventListener('load', function () { setTimeout(load, 3000); });
          })();
        </script>
        <?php endif; ?>
        
		<meta charset="<?php bloginfo( 'charset' ); ?>"/>
		<meta name="viewport" content="width=device-width, initial-scale=1">
			<link rel="profile" href="http://gmpg.org/xfn/11"/>
			
			<?php wp_head(); ?>
			<?php // <title> is printed by WordPress / Yoast SEO (title-tag support in functions.php). ?>
			
		</head>
		<body <?php body_class(); ?>>
		<?php wp_body_open(); ?>
			<a class="screen-reader-text apt-skip" href="#main"><?php esc_html_e( 'Skip to content', 'akseki-vi' ); ?></a>
			<div id="wrapper">

				<header class="apt-header">
					<a class="apt-brand" href="<?php echo esc_url( home_url( '/' ) ); ?>" aria-label="ASEAN Plus Three - Home">
						<?php echo load_logo_svg(); ?>
						<span class="apt-brand-text" aria-hidden="true"><span>ASEAN</span> <span>Plus</span> <span>Three</span></span>
					</a>
					<div class="apt-header-actions">
						<button type="button" class="apt-header-btn" commandfor="apt-search" command="show-modal" aria-label="<?php esc_attr_e( 'Search', 'akseki-vi' ); ?>">
							<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
						</button>
						<button type="button" class="apt-header-btn apt-menu-toggle" aria-controls="apt-menu" aria-expanded="false" aria-label="<?php esc_attr_e( 'Menu', 'akseki-vi' ); ?>">
							<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" aria-hidden="true"><path d="M4 7h16M4 12h16M4 17h16"/></svg>
						</button>
					</div>
				</header>

				<dialog class="apt-menu" id="apt-menu" aria-label="<?php esc_attr_e( 'Menu', 'akseki-vi' ); ?>">
					<div class="apt-menu-inner">
						<div class="apt-menu-top">
							<a class="apt-menu-brand" href="<?php echo esc_url( home_url( '/' ) ); ?>" aria-label="ASEAN Plus Three - Home">
								<?php echo load_logo_svg(); // phpcs:ignore WordPress.Security.EscapeOutput -- theme SVG ?>
								<span aria-hidden="true">ASEAN Plus Three</span>
							</a>
							<button type="button" class="apt-menu-close" aria-label="<?php esc_attr_e( 'Close menu', 'akseki-vi' ); ?>" autofocus>
								<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" aria-hidden="true"><path d="M6 6l12 12M18 6 6 18"/></svg>
							</button>
						</div>
						<nav class="apt-menu-nav" aria-label="<?php esc_attr_e( 'Main', 'akseki-vi' ); ?>">
							<?php echo apt_site_menu_html(); // phpcs:ignore WordPress.Security.EscapeOutput -- built and escaped in the function ?>
						</nav>
						<div class="apt-menu-foot">
							<form class="apt-menu-search" role="search" method="get" action="<?php echo esc_url( home_url( '/' ) ); ?>">
								<label class="screen-reader-text" for="apt-menu-q"><?php esc_html_e( 'Search the site', 'akseki-vi' ); ?></label>
								<input type="search" id="apt-menu-q" name="s" placeholder="<?php esc_attr_e( 'Search the site', 'akseki-vi' ); ?>" enterkeyhint="search" autocomplete="off">
								<button type="submit"><?php esc_html_e( 'Search', 'akseki-vi' ); ?></button>
							</form>
							<?php echo apt_site_menu_latest_html(); // phpcs:ignore WordPress.Security.EscapeOutput -- built and escaped in the function ?>
						</div>
					</div>
				</dialog>

<!-- Search: native modal dialog. Opens from any button with
     commandfor="apt-search" command="show-modal"; closes with Esc, the close
     button or a click outside (closedby="any"). Fallbacks in apt-nav.js. -->
<dialog id="apt-search" class="apt-search" closedby="any" aria-labelledby="apt-search-title">
	<div class="apt-search-head">
		<h2 class="apt-search-title" id="apt-search-title"><?php esc_html_e( 'Search the site', 'akseki-vi' ); ?></h2>
		<button type="button" class="apt-search-close" commandfor="apt-search" command="close" aria-label="<?php esc_attr_e( 'Close search', 'akseki-vi' ); ?>">
			<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M6 6l12 12M18 6L6 18"/></svg>
		</button>
	</div>
	<?php get_template_part( 'template-parts/search-form', null, array( 'id' => 'apt-search', 'autofocus' => true, 'lazy_cats' => true ) ); ?>
</dialog>
