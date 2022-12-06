<?php

abstract class WFP_WelcomePanelColumn {

	abstract protected function icon();
	abstract protected function title();
	abstract protected function content();

	public function print_content() {
		$icon = sprintf(
			'<span class="dashicons dashicons-%s" aria-hidden="true"></span>',
			esc_attr( $this->icon() )
		);

		$title = sprintf( 
			'<h3>%1$s %2$s</h3>',
			$icon,
			$this->title()
		);

		$content = $this->content();

		if ( is_array( $content ) ) {
			$content = implode( "\n\n", $content );
		}

		$content = wp_kses_post( $content );
		$content = wptexturize( $content );
		$content = convert_chars( $content );
		$content = wpautop( $content );

		echo "\n";
		echo '<div class="welcome-panel-column">';
		echo wp_kses_post($title);
		echo wp_kses_post($content);
		echo '</div>';
	}
}


class WFP_WelcomePanelColumn_AntiSpam extends WFP_WelcomePanelColumn {

	protected function icon() {
		return 'shield';
	}

	protected function title() {
		return esc_html(
			__( "Getting spammed? You have protection.", 'wing-forms' )
		);
	}

	protected function content() {
		return array(
			esc_html( __( "Spammers target everything; your wing forms are not an exception. Before you get spammed, protect your wing forms with the powerful anti-spam features Wing Forms provides.", 'wing-forms' ) ),
			sprintf(
				/* translators: links labeled 1: 'Akismet', 2: 'reCAPTCHA', 3: 'disallowed list' */
				esc_html( __( 'Wing Forms supports spam-filtering with %1$s. Intelligent %2$s blocks annoying spambots. Plus, using %3$s, you can block messages containing specified keywords or those sent from specified IP addresses.', 'wing-forms' ) ),
				wfp_link(
					__( 'https://github.com/np2861996/wing-forms-Plugin', 'wing-forms' ),
					__( 'Akismet', 'wing-forms' )
				),
				wfp_link(
					__( 'https://github.com/np2861996/wing-forms-Plugin', 'wing-forms' ),
					__( 'reCAPTCHA', 'wing-forms' )
				),
				wfp_link(
					__( 'https://github.com/np2861996/wing-forms-Plugin', 'wing-forms' ),
					__( 'disallowed list', 'wing-forms' )
				)
			),
		);
	}
}


class WFP_WelcomePanelColumn_Donation extends WFP_WelcomePanelColumn {

	protected function icon() {
		return 'megaphone';
	}

	protected function title() {
		return esc_html(
			__( "Wing Forms needs your support.", 'wing-forms' )
		);
	}

	protected function content() {
		return array(
			esc_html( __( "It is hard to continue development and support for this plugin without contributions from users like you.", 'wing-forms' ) ),
			sprintf(
				/* translators: %s: link labeled 'making a donation' */
				esc_html( __( 'If you enjoy using Wing Forms and find it useful, please consider %s.', 'wing-forms' ) ),
				wfp_link(
					__( 'https://github.com/np2861996/wing-forms-Plugin', 'wing-forms' ),
					__( 'making a donation', 'wing-forms' )
				)
			),
			esc_html( __( "Your donation will help encourage and support the plugin&#8217;s continued development and better user support.", 'wing-forms' ) ),
		);
	}
}


class WFP_WelcomePanelColumn_Flamingo extends WFP_WelcomePanelColumn {

	protected function icon() {
		return 'editor-help';
	}

	protected function title() {
		return esc_html(
			__( "Before you cry over spilt mail&#8230;", 'wing-forms' )
		);
	}

	protected function content() {
		return array(
			esc_html( __( "Wing Forms does not store submitted messages anywhere. Therefore, you may lose important messages forever if your mail server has issues or you make a mistake in mail configuration.", 'wing-forms' ) ),
			sprintf(
				/* translators: %s: link labeled 'Flamingo' */
				esc_html( __( 'Install a message storage plugin before this happens to you. %s saves all messages through wing forms into the database. Flamingo is a free WordPress plugin created by the same author as Wing Forms.', 'wing-forms' ) ),
				wfp_link(
					__( 'https://github.com/np2861996/wing-forms-Plugin', 'wing-forms' ),
					__( 'Flamingo', 'wing-forms' )
				)
			),
		);
	}
}


class WFP_WelcomePanelColumn_Integration extends WFP_WelcomePanelColumn {

	protected function icon() {
		return 'superhero-alt';
	}

	protected function title() {
		return esc_html(
			__( "You have strong allies to back you up.", 'wing-forms' )
		);
	}

	protected function content() {
		return array(
			sprintf(
				/* translators: 1: link labeled 'Sendinblue', 2: link labeled 'Constant Wing' */
				esc_html( __( 'Your wing forms will become more powerful and versatile by integrating them with external APIs. With CRM and email marketing services, you can build your own wing lists (%1$s and %2$s).', 'wing-forms' ) ),
				wfp_link(
					__( 'https://github.com/np2861996/wing-forms-Plugin', 'wing-forms' ),
					__( 'Sendinblue', 'wing-forms' )
				),
				wfp_link(
					__( 'https://github.com/np2861996/wing-forms-Plugin', 'wing-forms' ),
					__( 'Constant Wing', 'wing-forms' )
				)
			),
			sprintf(
				/* translators: 1: link labeled 'reCAPTCHA', 2: link labeled 'Stripe' */
				esc_html( __( 'With help from cloud-based machine learning, anti-spam services will protect your forms (%1$s). Even payment services are natively supported (%2$s).', 'wing-forms' ) ),
				wfp_link(
					__( 'https://github.com/np2861996/wing-forms-Plugin', 'wing-forms' ),
					__( 'reCAPTCHA', 'wing-forms' )
				),
				wfp_link(
					__( 'https://github.com/np2861996/wing-forms-Plugin', 'wing-forms' ),
					__( 'Stripe', 'wing-forms' )
				)
			),
		);
	}
}


function wfp_welcome_panel() {
	$columns = array();

	$flamingo_is_active = defined( 'FLAMINGO_VERSION' );

	$sendinblue_is_active = false;

	if ( class_exists( 'WFP_Sendinblue' )
	and $sendinblue = WFP_Sendinblue::get_instance() ) {
		$sendinblue_is_active = $sendinblue->is_active();
	}

	if ( $flamingo_is_active and $sendinblue_is_active ) {
		$columns[] = new WFP_WelcomePanelColumn_AntiSpam();
		$columns[] = new WFP_WelcomePanelColumn_Donation();
	} elseif ( $flamingo_is_active ) {
		$columns[] = new WFP_WelcomePanelColumn_Integration();
		$columns[] = new WFP_WelcomePanelColumn_AntiSpam();
	} elseif ( $sendinblue_is_active ) {
		$columns[] = new WFP_WelcomePanelColumn_Flamingo();
		$columns[] = new WFP_WelcomePanelColumn_AntiSpam();
	} else {
		$columns[] = new WFP_WelcomePanelColumn_Flamingo();
		$columns[] = new WFP_WelcomePanelColumn_Integration();
	}

	$classes = 'wfp-welcome-panel';

	$vers = (array) get_user_meta( get_current_user_id(),
		'wfp_hide_welcome_panel_on', true
	);

	if ( wfp_version_grep( wfp_version( 'only_major=1' ), $vers ) ) {
		$classes .= ' hidden';
	}

?>
<div id="wfp-welcome-panel" class="<?php echo esc_attr( $classes ); ?>">
	<?php wp_nonce_field( 'wfp-welcome-panel-nonce', 'welcomepanelnonce', false ); ?>
	<a class="welcome-panel-close" href="<?php echo esc_url( menu_page_url( 'wfp', false ) ); ?>"><?php echo esc_html( __( 'Dismiss', 'wing-forms' ) ); ?></a>

	<div class="welcome-panel-content">
		<div class="welcome-panel-column-container">
<?php

	foreach ( $columns as $column ) {
		$column->print_content();
	}

?>
		</div>
	</div>
</div>
<?php
}


add_action(
	'wp_ajax_wfp-update-welcome-panel',
	'wfp_admin_ajax_welcome_panel',
	10, 0
);

function wfp_admin_ajax_welcome_panel() {
	check_ajax_referer( 'wfp-welcome-panel-nonce', 'welcomepanelnonce' );

	$vers = get_user_meta( get_current_user_id(),
		'wfp_hide_welcome_panel_on', true
	);

	if ( empty( $vers ) or ! is_array( $vers ) ) {
		$vers = array();
	}

	$svisible = sanitize_text_field($_POST['visible']);

	if ( empty( $svisible ) ) {
		$vers[] = wfp_version( 'only_major=1' );
	} else {
		$vers = array_diff( $vers, array( wfp_version( 'only_major=1' ) ) );
	}

	$vers = array_unique( $vers );

	update_user_meta( get_current_user_id(),
		'wfp_hide_welcome_panel_on', $vers
	);

	wp_die( 1 );
}


add_filter(
	'screen_settings',
	'wfp_welcome_panel_screen_settings',
	10, 2
);

function wfp_welcome_panel_screen_settings( $screen_settings, $screen ) {

	if ( 'toplevel_page_wfp' !== $screen->id ) {
		return $screen_settings;
	}

	$vers = (array) get_user_meta( get_current_user_id(),
		'wfp_hide_welcome_panel_on', true
	);

	$checkbox_id = 'wfp-welcome-panel-show';
	$checked = ! in_array( wfp_version( 'only_major=1' ), $vers );

	$checkbox = sprintf(
		'<input %s />',
		wfp_format_atts( array(
			'id' => $checkbox_id,
			'type' => 'checkbox',
			'checked' => $checked ? 'checked' : null,
		) )
	);

	$screen_settings .= sprintf( '
<fieldset class="wfp-welcome-panel-options">
<legend>%1$s</legend>
<label for="%2$s">%3$s %4$s</label>
</fieldset>',
 		esc_html( __( 'Welcome panel', 'wing-forms' ) ),
		esc_attr( $checkbox_id ),
		$checkbox,
		esc_html( __( 'Show welcome panel', 'wing-forms' ) )
	);

	return $screen_settings;
}
