<?php

require_once WFP_PLUGIN_DIR . '/includes/l10n.php';
require_once WFP_PLUGIN_DIR . '/includes/capabilities.php';
require_once WFP_PLUGIN_DIR . '/includes/functions.php';
require_once WFP_PLUGIN_DIR . '/includes/formatting.php';
require_once WFP_PLUGIN_DIR . '/includes/pipe.php';
require_once WFP_PLUGIN_DIR . '/includes/form-tag.php';
require_once WFP_PLUGIN_DIR . '/includes/form-tags-manager.php';
require_once WFP_PLUGIN_DIR . '/includes/shortcodes.php';
require_once WFP_PLUGIN_DIR . '/includes/swv/swv.php';
require_once WFP_PLUGIN_DIR . '/includes/wing-form-functions.php';
require_once WFP_PLUGIN_DIR . '/includes/wing-form-template.php';
require_once WFP_PLUGIN_DIR . '/includes/wing-form.php';
require_once WFP_PLUGIN_DIR . '/includes/mail.php';
require_once WFP_PLUGIN_DIR . '/includes/special-mail-tags.php';
require_once WFP_PLUGIN_DIR . '/includes/file.php';
require_once WFP_PLUGIN_DIR . '/includes/validation-functions.php';
require_once WFP_PLUGIN_DIR . '/includes/validation.php';
require_once WFP_PLUGIN_DIR . '/includes/submission.php';
require_once WFP_PLUGIN_DIR . '/includes/upgrade.php';
require_once WFP_PLUGIN_DIR . '/includes/integration.php';
require_once WFP_PLUGIN_DIR . '/includes/config-validator.php';
require_once WFP_PLUGIN_DIR . '/includes/rest-api.php';
require_once WFP_PLUGIN_DIR . '/includes/block-editor/block-editor.php';

if ( is_admin() ) {
	require_once WFP_PLUGIN_DIR . '/admin/admin.php';
} else {
	require_once WFP_PLUGIN_DIR . '/includes/controller.php';
}


class WFP {

	/**
	 * Loads modules from the modules directory.
	 */
	public static function load_modules() {
		self::load_module( 'acceptance' );
		self::load_module( 'akismet' );
		self::load_module( 'checkbox' );
		self::load_module( 'constant-wing' );
		self::load_module( 'count' );
		self::load_module( 'date' );
		self::load_module( 'disallowed-list' );
		self::load_module( 'doi-helper' );
		self::load_module( 'file' );
		self::load_module( 'flamingo' );
		self::load_module( 'hidden' );
		self::load_module( 'listo' );
		self::load_module( 'number' );
		self::load_module( 'quiz' );
		self::load_module( 'really-simple-captcha' );
		self::load_module( 'recaptcha' );
		self::load_module( 'response' );
		self::load_module( 'select' );
		self::load_module( 'sendinblue' );
		self::load_module( 'stripe' );
		self::load_module( 'submit' );
		self::load_module( 'text' );
		self::load_module( 'textarea' );
	}


	/**
	 * Loads the specified module.
	 *
	 * @param string $mod Name of module.
	 * @return bool True on success, false on failure.
	 */
	protected static function load_module( $mod ) {
		return false
			|| wfp_include_module_file( $mod . '/' . $mod . '.php' )
			|| wfp_include_module_file( $mod . '.php' );
	}


	/**
	 * Retrieves a named entry from the option array of Wing Forms.
	 *
	 * @param string $name Array item key.
	 * @param mixed $default_value Optional. Default value to return if the entry
	 *                             does not exist. Default false.
	 * @return mixed Array value tied to the $name key. If nothing found,
	 *               the $default_value value will be returned.
	 */
	public static function get_option( $name, $default_value = false ) {
		$option = get_option( 'wfp' );

		if ( false === $option ) {
			return $default_value;
		}

		if ( isset( $option[$name] ) ) {
			return $option[$name];
		} else {
			return $default_value;
		}
	}


	/**
	 * Update an entry value on the option array of Wing Forms.
	 *
	 * @param string $name Array item key.
	 * @param mixed $value Option value.
	 */
	public static function update_option( $name, $value ) {
		$option = get_option( 'wfp' );
		$option = ( false === $option ) ? array() : (array) $option;
		$option = array_merge( $option, array( $name => $value ) );
		update_option( 'wfp', $option );
	}
}


add_action( 'plugins_loaded', 'wfp', 10, 0 );

/**
 * Loads modules and registers WordPress shortcodes.
 */
function wfp() {
	WFP::load_modules();

	add_shortcode( 'wing-forms', 'wfp_wing_form_tag_func' );
	add_shortcode( 'wing-form', 'wfp_wing_form_tag_func' );
}


add_action( 'init', 'wfp_init', 10, 0 );

/**
 * Registers post types for wing forms.
 */
function wfp_init() {
	wfp_get_request_uri();
	wfp_register_post_types();

	do_action( 'wfp_init' );
}


add_action( 'admin_init', 'wfp_upgrade', 10, 0 );

/**
 * Upgrades option data when necessary.
 */
function wfp_upgrade() {
	$old_ver = WFP::get_option( 'version', '0' );
	$new_ver = WFP_VERSION;

	if ( $old_ver == $new_ver ) {
		return;
	}

	do_action( 'wfp_upgrade', $new_ver, $old_ver );

	WFP::update_option( 'version', $new_ver );
}


add_action( 'activate_' . WFP_PLUGIN_BASENAME, 'wfp_install', 10, 0 );

/**
 * Callback tied to plugin activation action hook. Attempts to create
 * initial user dataset.
 */
function wfp_install() {
	if ( $opt = get_option( 'wfp' ) ) {
		return;
	}

	wfp_register_post_types();
	wfp_upgrade();

	if ( get_posts( array( 'post_type' => 'wfp_wing_form' ) ) ) {
		return;
	}

	$wing_form = WFP_WingForm::get_template(
		array(
			'title' =>
				/* translators: title of your first wing form. %d: number fixed to '1' */
				sprintf( __( 'Wing form %d', 'wing-forms' ), 1 ),
		)
	);

	$wing_form->save();

	WFP::update_option( 'bulk_validate',
		array(
			'timestamp' => time(),
			'version' => WFP_VERSION,
			'count_valid' => 1,
			'count_invalid' => 0,
		)
	);
}
