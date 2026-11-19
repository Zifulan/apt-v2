<?php
/**
 * One search result: short meeting name, title, excerpt, place and date,
 * categories (pillar categories carry their colour dot).
 */
$apt_title = get_the_title();
$apt_short = akseki_get_shortname( $apt_title, get_the_ID() );
?>
<article id="post-<?php the_ID(); ?>" <?php post_class( 'apt-result' ); ?>>
	<?php if ( has_post_thumbnail() ) : ?>
	<div class="apt-result-thumb"><?php the_post_thumbnail( 'thumbnail', array( 'alt' => '' ) ); ?></div>
	<?php endif; ?>
	<div class="apt-result-body">
		<?php if ( $apt_short && wp_strip_all_tags( $apt_short ) !== wp_strip_all_tags( $apt_title ) ) : ?>
		<p class="apt-result-short"><?php echo wp_kses_post( $apt_short ); ?></p>
		<?php endif; ?>
		<h2 class="apt-result-title"><a href="<?php the_permalink(); ?>"><?php echo esc_html( wp_strip_all_tags( $apt_title ) ); ?></a></h2>
		<div class="apt-result-excerpt"><?php the_excerpt(); ?></div>
		<p class="apt-result-meta akseki-meta"><?php echo akseki_get_the_meta( get_the_ID() ); ?></p>
		<?php $apt_cats = get_the_category(); ?>
		<?php if ( $apt_cats ) : ?>
		<ul class="apt-result-cats" aria-label="<?php esc_attr_e( 'Categories', 'akseki-vi' ); ?>">
			<?php foreach ( $apt_cats as $apt_cat ) : ?>
				<?php $apt_pkey = apt_pillar_key( $apt_cat->name ); ?>
			<li><a class="apt-tag <?php echo 'other' !== $apt_pkey ? 'pillar-' . esc_attr( $apt_pkey ) : 'apt-tag--plain'; ?>" href="<?php echo esc_url( get_category_link( $apt_cat->term_id ) ); ?>"><?php echo esc_html( $apt_cat->name ); ?></a></li>
			<?php endforeach; ?>
		</ul>
		<?php endif; ?>
	</div>
</article>
