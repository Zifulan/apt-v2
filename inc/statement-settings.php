<?php
/**
 * "Statement settings": a box on the post edit screen for the special tags
 * the theme reads, so editors don't have to know their names:
 *
 *   SM_<text>      short name (headings, lists, cards)
 *   SC_month       upcoming meeting: show month and year only
 *   SC_year        upcoming meeting: show the year only
 *   SC_nodate      upcoming meeting: date to be decided
 *   hasnocontent   listed without a link (no page of its own)
 *   hyperlinked    the page sends visitors to the first "source" link
 *   noshorttitle   always the full title
 *   marker         summit marker: the summit's statements are grouped under it
 *
 * The box saves the same tags, so everything that reads them works as before,
 * and typing the tags in the Tags box still works. The box remembers what it
 * showed when the post was opened and only changes the settings that were
 * changed in it, so a tag added the old way is not overwritten.
 *
 * @package apt-v2
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** On/off settings: tag => label. */
function apt_statement_flags() {
	return array(
		'hasnocontent' => __( 'No page of its own (listed without a link)', 'akseki-vi' ),
		'hyperlinked'  => __( 'Send visitors to its source link instead of showing the page', 'akseki-vi' ),
		'noshorttitle' => __( 'Always show the full title', 'akseki-vi' ),
		'marker'       => __( 'Summit marker: group this summit\'s statements under this post', 'akseki-vi' ),
	);
}

/** Date display for upcoming meetings: tag => label ('' = full date). */
function apt_statement_date_modes() {
	return array(
		''          => __( 'Full date', 'akseki-vi' ),
		'SC_month'  => __( 'Month and year only', 'akseki-vi' ),
		'SC_year'   => __( 'Year only', 'akseki-vi' ),
		'SC_nodate' => __( 'To be decided (no date)', 'akseki-vi' ),
	);
}

/** A post's settings, read from its tags. */
function apt_statement_settings( $post_id ) {
	$names    = wp_get_post_tags( $post_id, array( 'fields' => 'names' ) );
	$settings = array( 'short' => '', 'date' => '', 'flags' => array() );
	foreach ( (array) $names as $name ) {
		if ( 0 === strpos( $name, 'SM_' ) && strlen( $name ) > 3 ) {
			$settings['short'] = substr( $name, 3 );
		} elseif ( isset( apt_statement_date_modes()[ $name ] ) && '' !== $name ) {
			$settings['date'] = $name;
		} elseif ( isset( apt_statement_flags()[ $name ] ) ) {
			$settings['flags'][] = $name;
		}
	}
	sort( $settings['flags'] );
	return $settings;
}

add_action( 'add_meta_boxes_post', function () {
	add_meta_box( 'apt-statement-settings', __( 'Statement settings', 'akseki-vi' ), 'apt_statement_settings_box', 'post', 'side', 'high' );
} );

function apt_statement_settings_box( $post ) {
	$s = apt_statement_settings( $post->ID );
	wp_nonce_field( 'apt_statement_settings', 'apt_statement_settings_nonce' );
	// What the box showed, to save only what is changed in it.
	echo '<input type="hidden" name="apt_ss_was" value="' . esc_attr( wp_json_encode( $s ) ) . '">';

	$auto = trim( wp_strip_all_tags( html_entity_decode( akseki_get_shortname( $post->post_title, $post->ID, 7 ), ENT_QUOTES, 'UTF-8' ) ) );
	?>
	<p style="margin-top:0">
		<label for="apt-ss-short"><strong><?php esc_html_e( 'Short name', 'akseki-vi' ); ?></strong></label><br>
		<input type="text" id="apt-ss-short" name="apt_ss_short" value="<?php echo esc_attr( $s['short'] ); ?>" class="widefat" placeholder="<?php esc_attr_e( 'e.g. 11th APT JSO', 'akseki-vi' ); ?>">
		<span class="description">
			<?php esc_html_e( 'Used in headings, lists and cards. Leave empty for the automatic one', 'akseki-vi' ); ?><?php echo $auto ? ': ' . esc_html( $auto ) : ''; ?>.
		</span>
	</p>
	<p>
		<label for="apt-ss-date"><strong><?php esc_html_e( 'Date of an upcoming meeting', 'akseki-vi' ); ?></strong></label><br>
		<select id="apt-ss-date" name="apt_ss_date" class="widefat">
			<?php foreach ( apt_statement_date_modes() as $tag => $label ) : ?>
				<option value="<?php echo esc_attr( $tag ); ?>" <?php selected( $s['date'], $tag ); ?>><?php echo esc_html( $label ); ?></option>
			<?php endforeach; ?>
		</select>
		<span class="description"><?php esc_html_e( 'How the date is shown in the agenda while the meeting is upcoming.', 'akseki-vi' ); ?></span>
	</p>
	<fieldset>
		<legend class="screen-reader-text"><?php esc_html_e( 'Options', 'akseki-vi' ); ?></legend>
		<?php foreach ( apt_statement_flags() as $tag => $label ) : ?>
			<p style="margin:6px 0"><label>
				<input type="checkbox" name="apt_ss_flags[]" value="<?php echo esc_attr( $tag ); ?>" <?php checked( in_array( $tag, $s['flags'], true ) ); ?>>
				<?php echo esc_html( $label ); ?>
			</label></p>
		<?php endforeach; ?>
	</fieldset>
	<p class="description" style="margin-bottom:0"><?php esc_html_e( 'These save the same special tags as before (SM_…, SC_…, hasnocontent, hyperlinked, noshorttitle, marker), so typing them in Tags still works.', 'akseki-vi' ); ?></p>
	<?php
}

/** Apply the box on save: only the settings changed in it. */
function apt_save_statement_settings( $post_id ) {
	if ( ! isset( $_POST['apt_statement_settings_nonce'] )
		|| ! wp_verify_nonce( sanitize_key( wp_unslash( $_POST['apt_statement_settings_nonce'] ) ), 'apt_statement_settings' )
		|| ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE )
		|| wp_is_post_revision( $post_id )
		|| ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}
	$was = json_decode( wp_unslash( isset( $_POST['apt_ss_was'] ) ? $_POST['apt_ss_was'] : '' ), true ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput -- decoded and compared only
	if ( ! is_array( $was ) ) {
		return;
	}
	$was += array( 'short' => '', 'date' => '', 'flags' => array() );
	$new = array(
		'short' => trim( sanitize_text_field( wp_unslash( isset( $_POST['apt_ss_short'] ) ? $_POST['apt_ss_short'] : '' ) ) ),
		'date'  => sanitize_text_field( wp_unslash( isset( $_POST['apt_ss_date'] ) ? $_POST['apt_ss_date'] : '' ) ),
		'flags' => array_map( 'sanitize_text_field', (array) wp_unslash( isset( $_POST['apt_ss_flags'] ) ? $_POST['apt_ss_flags'] : array() ) ),
	);
	if ( ! isset( apt_statement_date_modes()[ $new['date'] ] ) ) {
		$new['date'] = $was['date'];
	}

	$names   = (array) wp_get_post_tags( $post_id, array( 'fields' => 'names' ) );
	$changed = false;
	if ( $new['short'] !== $was['short'] ) {
		$names = array_filter( $names, function ( $n ) { return 0 !== strpos( $n, 'SM_' ); } );
		if ( '' !== $new['short'] ) {
			$names[] = 'SM_' . $new['short'];
		}
		$changed = true;
	}
	if ( $new['date'] !== $was['date'] ) {
		$names = array_filter( $names, function ( $n ) { return 0 !== strpos( $n, 'SC_' ); } );
		if ( '' !== $new['date'] ) {
			$names[] = $new['date'];
		}
		$changed = true;
	}
	foreach ( array_keys( apt_statement_flags() ) as $flag ) {
		$on_now = in_array( $flag, $new['flags'], true );
		if ( $on_now !== in_array( $flag, (array) $was['flags'], true ) ) {
			$names   = array_diff( $names, array( $flag ) );
			$names   = $on_now ? array_merge( $names, array( $flag ) ) : $names;
			$changed = true;
		}
	}
	if ( $changed ) {
		wp_set_post_tags( $post_id, array_values( array_unique( $names ) ), false );
	}
}
add_action( 'save_post_post', 'apt_save_statement_settings' );
