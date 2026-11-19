<!doctype html>
<html <?php language_attributes(); ?>>
	<head>
        <?php if ( 'production' === wp_get_environment_type() ) : // staging copies: define( 'WP_ENVIRONMENT_TYPE', 'staging' ) to skip analytics ?>
        <script async src="https://www.googletagmanager.com/gtag/js?id=G-V2E02382TY"></script>
        <script>
          window.dataLayer = window.dataLayer || [];
          function gtag(){dataLayer.push(arguments);}
          gtag('js', new Date());
          gtag('config', 'G-V2E02382TY');
        </script>
        <?php endif; ?>
        
		<meta charset="<?php bloginfo( 'charset' ); ?>"/>
		<meta name="viewport" content="width=device-width, initial-scale=1">
			<link rel="profile" href="http://gmpg.org/xfn/11"/>
			
			<?php wp_head(); ?>
			<title>
			<?php 
			if(is_front_page()){
				echo 'ASEAN Plus Three';
			}else{
				echo 'APT ';
				echo wp_title();
			} 
			?>
			</title>
			
		</head>
		<body <?php body_class(); ?>>
		<?php wp_body_open(); ?>
			<a class="screen-reader-text apt-skip" href="#main"><?php esc_html_e( 'Skip to content', 'akseki-vi' ); ?></a>
			<div id="wrapper">

				<header class="apt-header">
					<a class="apt-brand" href="<?php echo esc_url( home_url( '/' ) ); ?>" aria-label="ASEAN Plus Three - Home">
						<?php echo load_logo_svg(); ?>
						<span class="apt-brand-text" aria-hidden="true"><span>ASEAN</span><span>Plus</span><span>Three</span></span>
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
					<div class="apt-menu-scroller">
						<div class="apt-menu-sheet">
							<nav class="apt-menu-panel" aria-label="<?php esc_attr_e( 'Main', 'akseki-vi' ); ?>">
								<div class="apt-menu-top">
									<button type="button" class="apt-menu-close" aria-label="<?php esc_attr_e( 'Close menu', 'akseki-vi' ); ?>" autofocus>
										<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" aria-hidden="true"><path d="M6 6l12 12M18 6 6 18"/></svg>
									</button>
								</div>
								<?php
								wp_nav_menu( array(
									'theme_location' => 'primary',
									'depth'          => 3,
									'container'      => false,
									'menu_class'     => 'apt-menu-list',
									'menu_id'        => 'apt-menu-list',
									'fallback_cb'    => false,
									'apt_mega'       => true,
								) );
								?>
							</nav>
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
	<?php get_template_part( 'template-parts/search-form', null, array( 'id' => 'apt-search', 'autofocus' => true ) ); ?>
</dialog>
