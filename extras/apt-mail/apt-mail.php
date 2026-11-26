<?php
/**
 * Plugin Name:       APT Mail
 * Description:       Sends the site's email through Gmail (SMTP with an App Password), with a test button, editable new-post notification recipients, and a warning when email fails (Settings > Email).
 * Version:           1.0.1
 * Requires at least: 5.9
 * Requires PHP:      7.4
 * Author:            ASEAN Plus Three website
 * License:           GPL-2.0-or-later
 * Text Domain:       apt-mail
 *
 * - All email from WordPress (new-post notifications, password resets,
 *   update, backup and security emails) goes out through smtp.gmail.com as
 *   the Gmail account set on Settings > Email.
 * - The App Password is read only from wp-config.php:
 *       define( 'APT_SMTP_PASSWORD', 'xxxxxxxxxxxxxxxx' );
 *   It is never stored in the database or shown. Without it the plugin does
 *   nothing and WordPress sends mail as usual.
 * - New-post notifications (sent by the theme when a post is first
 *   published): recipients are edited on Settings > Email; recent posts whose
 *   notification failed are listed there with a "Send now" button.
 * - Only the time, subject and error of the last failed email are kept (no
 *   email contents). Administrators see a notice until an email succeeds.
 */

defined( 'ABSPATH' ) || exit;

define( 'APT_MAIL_OPTION', 'apt_mail_settings' );
define( 'APT_MAIL_LAST_ERROR', 'apt_mail_last_error' );
define( 'APT_MAIL_LAST_OK', 'apt_mail_last_ok' );

/** Settings, with Gmail's defaults. */
function apt_mail_settings() {
	$s = get_option( APT_MAIL_OPTION, array() );
	return wp_parse_args( is_array( $s ) ? $s : array(), array(
		'user'       => 'apt.analytic@gmail.com',
		'from_name'  => 'ASEAN Plus Three',
		'host'       => 'smtp.gmail.com',
		'port'       => 587,
		'secure'     => 'tls',
		'recipients' => '',
	) );
}

/** Whether the App Password is set in wp-config.php. */
function apt_mail_ready() {
	return defined( 'APT_SMTP_PASSWORD' ) && '' !== trim( (string) APT_SMTP_PASSWORD );
}

/* ---------- Send through Gmail ---------- */

add_action( 'phpmailer_init', function ( $mailer ) {
	if ( ! apt_mail_ready() ) {
		return;
	}
	$s = apt_mail_settings();
	$mailer->isSMTP();
	$mailer->Host        = $s['host'];
	$mailer->Port        = (int) $s['port'];
	$mailer->SMTPSecure  = in_array( $s['secure'], array( 'tls', 'ssl' ), true ) ? $s['secure'] : 'tls';
	$mailer->SMTPAutoTLS = true;
	$mailer->SMTPAuth    = true;
	$mailer->Username    = $s['user'];
	$mailer->Password    = str_replace( ' ', '', (string) APT_SMTP_PASSWORD ); // Google shows it in groups of four
	$mailer->Timeout     = 20;
	// Gmail only sends as the signed-in account: always use it as the sender.
	$mailer->setFrom( $s['user'], $s['from_name'], false );
	$mailer->Sender = $s['user'];
} );

/* ---------- Remember failures, warn administrators ---------- */

add_action( 'wp_mail_failed', function ( $error ) {
	$data = $error->get_error_data();
	update_option( APT_MAIL_LAST_ERROR, array(
		'time'    => time(),
		'message' => $error->get_error_message(),
		'subject' => is_array( $data ) && isset( $data['subject'] ) ? (string) $data['subject'] : '',
	), false );
} );

// A successful email clears the warning.
add_action( 'wp_mail_succeeded', function () {
	update_option( APT_MAIL_LAST_OK, time(), false );
	delete_option( APT_MAIL_LAST_ERROR );
} );

/** The last failure, if no email has succeeded since. */
function apt_mail_open_error() {
	$err = get_option( APT_MAIL_LAST_ERROR );
	return ( is_array( $err ) && ! empty( $err['time'] ) ) ? $err : null;
}

/** A plain-language hint for an SMTP error. */
function apt_mail_hint( $message ) {
	$m = strtolower( (string) $message );
	if ( false !== strpos( $m, 'authenticate' ) || false !== strpos( $m, 'username and password' ) ) {
		return __( 'Gmail refused the login: check the App Password in wp-config.php and that 2-Step Verification is still on for the account.', 'apt-mail' );
	}
	if ( false !== strpos( $m, 'connect' ) || false !== strpos( $m, 'timed out' ) || false !== strpos( $m, 'timeout' ) ) {
		return __( 'The server could not reach smtp.gmail.com. The host is probably blocking outgoing SMTP; ask the host to allow port 587, or use the Gmail API (WP Mail SMTP plugin) instead.', 'apt-mail' );
	}
	return '';
}

add_action( 'admin_notices', function () {
	if ( ! current_user_can( 'manage_options' ) || ! apt_mail_ready() ) {
		return;
	}
	$err = apt_mail_open_error();
	if ( ! $err ) {
		return;
	}
	$hint = apt_mail_hint( $err['message'] );
	echo '<div class="notice notice-error"><p><strong>' . esc_html__( 'The site could not send an email.', 'apt-mail' ) . '</strong> '
		. esc_html( sprintf( /* translators: 1: date and time, 2: subject */ __( 'Last failure: %1$s, "%2$s".', 'apt-mail' ), wp_date( 'j F Y, H:i', $err['time'] ), $err['subject'] ) )
		. ' <code>' . esc_html( $err['message'] ) . '</code> ' . esc_html( $hint )
		. ' <a href="' . esc_url( admin_url( 'options-general.php?page=apt-mail' ) ) . '">' . esc_html__( 'Settings > Email', 'apt-mail' ) . '</a></p></div>';
} );

/* ---------- New-post notification recipients ---------- */

/** Recipients from Settings > Email, one per line (empty: the theme's own list). */
function apt_mail_recipients() {
	$list = preg_split( '/[\s,;]+/', (string) apt_mail_settings()['recipients'] );
	return array_values( array_unique( array_filter( array_map( 'sanitize_email', $list ), 'is_email' ) ) );
}

add_filter( 'apt_publish_notify_recipients', function ( $default ) {
	$mine = apt_mail_recipients();
	return $mine ? $mine : $default;
} );

/** Send (or re-send) the theme's new-post notification for one post. */
function apt_mail_send_notification( $post_id, $only_to = '' ) {
	if ( ! function_exists( 'notifyauthor' ) ) {
		return new WP_Error( 'apt_mail_no_theme', __( 'The theme\'s notification function is not available.', 'apt-mail' ) );
	}
	$was_sent = get_post_meta( $post_id, '_notification_sent', true );
	delete_post_meta( $post_id, '_notification_sent' );
	$only = function () use ( $only_to ) {
		return array( $only_to );
	};
	if ( $only_to ) {
		add_filter( 'apt_publish_notify_recipients', $only, 99 );
	}
	notifyauthor( $post_id );
	if ( $only_to ) {
		remove_filter( 'apt_publish_notify_recipients', $only, 99 );
		// A test to one person doesn't count as the real notification.
		$was_sent ? update_post_meta( $post_id, '_notification_sent', $was_sent ) : delete_post_meta( $post_id, '_notification_sent' );
	}
	return true;
}

/* ---------- Settings > Email ---------- */

add_action( 'admin_menu', function () {
	add_options_page( __( 'Email', 'apt-mail' ), __( 'Email', 'apt-mail' ), 'manage_options', 'apt-mail', 'apt_mail_page' );
} );

add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), function ( $links ) {
	array_unshift( $links, '<a href="' . esc_url( admin_url( 'options-general.php?page=apt-mail' ) ) . '">' . esc_html__( 'Settings', 'apt-mail' ) . '</a>' );
	return $links;
} );

function apt_mail_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	$notice = '';
	$s      = apt_mail_settings();

	// Save settings.
	if ( isset( $_POST['apt_mail_save'] ) && check_admin_referer( 'apt_mail_save' ) ) {
		$secure = sanitize_key( wp_unslash( $_POST['secure'] ?? 'tls' ) );
		$s      = array(
			'user'       => sanitize_email( wp_unslash( $_POST['user'] ?? '' ) ),
			'from_name'  => sanitize_text_field( wp_unslash( $_POST['from_name'] ?? '' ) ),
			'host'       => sanitize_text_field( wp_unslash( $_POST['host'] ?? '' ) ),
			'port'       => absint( $_POST['port'] ?? 587 ),
			'secure'     => in_array( $secure, array( 'tls', 'ssl' ), true ) ? $secure : 'tls',
			'recipients' => sanitize_textarea_field( wp_unslash( $_POST['recipients'] ?? '' ) ),
		);
		update_option( APT_MAIL_OPTION, $s );
		$s      = apt_mail_settings();
		$notice = '<div class="notice notice-success"><p>' . esc_html__( 'Settings saved.', 'apt-mail' ) . '</p></div>';
	}

	// Test email.
	if ( isset( $_POST['apt_mail_test'] ) && check_admin_referer( 'apt_mail_test' ) ) {
		$to     = sanitize_email( wp_unslash( $_POST['test_to'] ?? '' ) );
		$failed = '';
		$catch  = function ( $e ) use ( &$failed ) {
			$failed = $e->get_error_message();
		};
		add_action( 'wp_mail_failed', $catch );
		$ok = is_email( $to ) && wp_mail( $to, __( 'Test email from the ASEAN Plus Three website', 'apt-mail' ), __( 'This is a test from Settings > Email. If you can read it, the site can send email.', 'apt-mail' ) );
		remove_action( 'wp_mail_failed', $catch );
		$notice = $ok
			? '<div class="notice notice-success"><p>' . esc_html( sprintf( /* translators: %s: address */ __( 'Sent to %s. Check the inbox and the spam folder.', 'apt-mail' ), $to ) ) . '</p></div>'
			: '<div class="notice notice-error"><p><strong>' . esc_html__( 'Not sent.', 'apt-mail' ) . '</strong> <code>' . esc_html( $failed ? $failed : __( 'Invalid address.', 'apt-mail' ) ) . '</code> ' . esc_html( apt_mail_hint( $failed ) ) . '</p></div>';
	}

	// Send a post's notification (to everyone, or to me as a test).
	if ( isset( $_POST['apt_mail_notify'] ) && check_admin_referer( 'apt_mail_notify' ) ) {
		$post_id = absint( $_POST['post_id'] ?? 0 );
		$is_test = isset( $_POST['test_notify_to'] );
		$only    = $is_test ? sanitize_email( wp_unslash( $_POST['test_notify_to'] ) ) : '';
		$post    = get_post( $post_id );
		if ( $is_test && ! is_email( $only ) ) {
			// Never fall back to the real recipients for a test.
			$notice = '<div class="notice notice-error"><p>' . esc_html__( 'Enter a valid email address for the test notification. Nothing was sent.', 'apt-mail' ) . '</p></div>';
		} elseif ( $post && 'post' === $post->post_type && 'publish' === $post->post_status ) {
			$failed = '';
			$catch  = function ( $e ) use ( &$failed ) {
				$failed = $e->get_error_message();
			};
			add_action( 'wp_mail_failed', $catch );
			apt_mail_send_notification( $post_id, $only );
			remove_action( 'wp_mail_failed', $catch );
			$ok = '' === $failed;
			$notice     = $ok
				? '<div class="notice notice-success"><p>' . esc_html( $only
					? sprintf( /* translators: %s: address */ __( 'Test notification sent to %s.', 'apt-mail' ), $only )
					: __( 'Notification sent to the recipients.', 'apt-mail' ) ) . '</p></div>'
				: '<div class="notice notice-error"><p>' . esc_html__( 'The notification could not be sent:', 'apt-mail' ) . ' <code>' . esc_html( $failed ) . '</code> ' . esc_html( apt_mail_hint( $failed ) ) . '</p></div>';
		}
	}

	$recent  = get_posts( array( 'post_type' => 'post', 'post_status' => 'publish', 'numberposts' => 20, 'orderby' => 'date', 'order' => 'DESC' ) );
	$missing = array_filter( get_posts( array(
		'post_type'   => 'post',
		'post_status' => 'publish',
		'numberposts' => 50,
		'date_query'  => array( array( 'after' => '60 days ago' ) ),
	) ), function ( $p ) {
		return ! get_post_meta( $p->ID, '_notification_sent', true );
	} );
	$err = apt_mail_open_error();
	?>
	<div class="wrap">
		<h1><?php esc_html_e( 'Email', 'apt-mail' ); ?></h1>
		<?php echo $notice; // phpcs:ignore WordPress.Security.EscapeOutput -- built from escaped parts ?>

		<h2><?php esc_html_e( 'Status', 'apt-mail' ); ?></h2>
		<table class="widefat striped" style="max-width:900px"><tbody>
			<tr><th style="width:240px"><?php esc_html_e( 'App Password', 'apt-mail' ); ?></th><td>
				<?php if ( apt_mail_ready() ) : ?>
					<span style="color:#00a32a;font-weight:600"><?php esc_html_e( 'Found in wp-config.php. Email is sent through Gmail.', 'apt-mail' ); ?></span>
				<?php else : ?>
					<span style="color:#b32d2e;font-weight:600"><?php esc_html_e( 'Not set. Email is sent the usual WordPress way.', 'apt-mail' ); ?></span><br>
					<?php esc_html_e( 'Add this line to wp-config.php, above "That\'s all, stop editing!", with the App Password created for the Gmail account:', 'apt-mail' ); ?><br>
					<code>define( 'APT_SMTP_PASSWORD', 'xxxx xxxx xxxx xxxx' );</code>
				<?php endif; ?>
			</td></tr>
			<tr><th><?php esc_html_e( 'Last failure', 'apt-mail' ); ?></th><td>
				<?php if ( $err ) : ?>
					<span style="color:#b32d2e;font-weight:600"><?php echo esc_html( wp_date( 'j F Y, H:i', $err['time'] ) ); ?></span>: <?php echo esc_html( $err['subject'] ); ?> <code><?php echo esc_html( $err['message'] ); ?></code> <?php echo esc_html( apt_mail_hint( $err['message'] ) ); ?>
				<?php else : ?>
					<?php esc_html_e( 'None since the last successful email.', 'apt-mail' ); ?>
				<?php endif; ?>
			</td></tr>
		</tbody></table>

		<h2><?php esc_html_e( 'Send a test email', 'apt-mail' ); ?></h2>
		<form method="post"><?php wp_nonce_field( 'apt_mail_test' ); ?>
			<p><input type="email" name="test_to" class="regular-text" value="<?php echo esc_attr( wp_get_current_user()->user_email ); ?>" required>
			<button class="button button-primary" name="apt_mail_test" value="1"><?php esc_html_e( 'Send test email', 'apt-mail' ); ?></button></p>
		</form>

		<form method="post"><?php wp_nonce_field( 'apt_mail_save' ); ?>
			<h2><?php esc_html_e( 'New post notifications', 'apt-mail' ); ?></h2>
			<p><?php esc_html_e( 'When a post is first published, the theme emails these addresses. One address per line. Leave empty to use the list in the theme.', 'apt-mail' ); ?></p>
			<textarea name="recipients" rows="6" class="large-text code" style="max-width:500px" placeholder="fifi@asean.org&#10;pham.thu@asean.org&#10;andi.rozin@asean.org&#10;fatahillah.rachman@asean.org"><?php echo esc_textarea( $s['recipients'] ); ?></textarea>

			<h2><?php esc_html_e( 'Gmail account', 'apt-mail' ); ?></h2>
			<table class="form-table" role="presentation"><tbody>
				<tr><th><label for="apt-user"><?php esc_html_e( 'Gmail address', 'apt-mail' ); ?></label></th><td><input type="email" id="apt-user" name="user" class="regular-text" value="<?php echo esc_attr( $s['user'] ); ?>"><p class="description"><?php esc_html_e( 'Emails are sent from this address. The App Password must belong to it.', 'apt-mail' ); ?></p></td></tr>
				<tr><th><label for="apt-from"><?php esc_html_e( 'Sender name', 'apt-mail' ); ?></label></th><td><input type="text" id="apt-from" name="from_name" class="regular-text" value="<?php echo esc_attr( $s['from_name'] ); ?>"></td></tr>
				<tr><th><?php esc_html_e( 'Server', 'apt-mail' ); ?></th><td>
					<input type="text" name="host" value="<?php echo esc_attr( $s['host'] ); ?>" class="regular-text" aria-label="<?php esc_attr_e( 'SMTP host', 'apt-mail' ); ?>">
					<input type="number" name="port" value="<?php echo (int) $s['port']; ?>" style="width:6em" aria-label="<?php esc_attr_e( 'Port', 'apt-mail' ); ?>">
					<select name="secure" aria-label="<?php esc_attr_e( 'Encryption', 'apt-mail' ); ?>"><option value="tls" <?php selected( $s['secure'], 'tls' ); ?>>TLS</option><option value="ssl" <?php selected( $s['secure'], 'ssl' ); ?>>SSL</option></select>
					<p class="description"><?php esc_html_e( 'Gmail: smtp.gmail.com, 587, TLS. Change only if told to.', 'apt-mail' ); ?></p>
				</td></tr>
			</tbody></table>
			<p><button class="button button-primary" name="apt_mail_save" value="1"><?php esc_html_e( 'Save settings', 'apt-mail' ); ?></button></p>
		</form>

		<h2><?php esc_html_e( 'Send a post\'s notification', 'apt-mail' ); ?></h2>
		<?php if ( $missing ) : ?>
			<p><?php esc_html_e( 'Posts of the last 60 days whose notification was never sent (for example while email was not working):', 'apt-mail' ); ?></p>
			<table class="widefat striped" style="max-width:900px"><tbody>
			<?php foreach ( $missing as $p ) : ?>
				<tr><td><a href="<?php echo esc_url( get_permalink( $p ) ); ?>"><?php echo esc_html( get_the_title( $p ) ); ?></a> <span class="description">(<?php echo esc_html( get_the_date( 'j F Y', $p ) ); ?>)</span></td>
				<td style="width:140px"><form method="post"><?php wp_nonce_field( 'apt_mail_notify' ); ?><input type="hidden" name="post_id" value="<?php echo (int) $p->ID; ?>"><button class="button" name="apt_mail_notify" value="1"><?php esc_html_e( 'Send now', 'apt-mail' ); ?></button></form></td></tr>
			<?php endforeach; ?>
			</tbody></table>
		<?php else : ?>
			<p><?php esc_html_e( 'Every post of the last 60 days has had its notification sent.', 'apt-mail' ); ?></p>
		<?php endif; ?>
		<form method="post" style="margin-top:16px"><?php wp_nonce_field( 'apt_mail_notify' ); ?>
			<p><label for="apt-notify-post"><?php esc_html_e( 'See what the recipients get: send the notification for', 'apt-mail' ); ?></label>
			<select id="apt-notify-post" name="post_id">
				<?php foreach ( $recent as $p ) : ?><option value="<?php echo (int) $p->ID; ?>"><?php echo esc_html( wp_trim_words( get_the_title( $p ), 12 ) ); ?></option><?php endforeach; ?>
			</select>
			<label for="apt-notify-to"><?php esc_html_e( 'only to', 'apt-mail' ); ?></label>
			<input type="email" id="apt-notify-to" name="test_notify_to" class="regular-text" value="<?php echo esc_attr( wp_get_current_user()->user_email ); ?>" required>
			<button class="button" name="apt_mail_notify" value="1"><?php esc_html_e( 'Send test notification', 'apt-mail' ); ?></button></p>
		</form>
	</div>
	<?php
}
