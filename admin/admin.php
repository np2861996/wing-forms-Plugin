<?php

require_once WFP_PLUGIN_DIR . '/admin/includes/admin-functions.php';
require_once WFP_PLUGIN_DIR . '/admin/includes/help-tabs.php';
require_once WFP_PLUGIN_DIR . '/admin/includes/tag-generator.php';
require_once WFP_PLUGIN_DIR . '/admin/includes/welcome-panel.php';
require_once WFP_PLUGIN_DIR . '/admin/includes/config-validator.php';


add_action(
	'admin_init',
	function () {
		do_action( 'wfp_admin_init' );
	},
	10, 0
);


add_action(
	'admin_menu',
	'wfp_admin_menu',
	9, 0
);

function wfp_admin_menu() {
	do_action( 'wfp_admin_menu' );

	add_menu_page(
		__( 'Wing Forms', 'wing-forms' ),
		__( 'Wing', 'wing-forms' )
			. wfp_admin_menu_change_notice(),
		'wfp_read_wing_forms',
		'wfp',
		'wfp_admin_management_page',
		'dashicons-email',
		30
	);

	$edit = add_submenu_page( 'wfp',
		__( 'Edit Wing Form', 'wing-forms' ),
		__( 'Wing Forms', 'wing-forms' )
			. wfp_admin_menu_change_notice( 'wfp' ),
		'wfp_read_wing_forms',
		'wfp',
		'wfp_admin_management_page'
	);

	add_action( 'load-' . $edit, 'wfp_load_wing_form_admin', 10, 0 );

	$addnew = add_submenu_page( 'wfp',
		__( 'Add New Wing Form', 'wing-forms' ),
		__( 'Add New', 'wing-forms' )
			. wfp_admin_menu_change_notice( 'wfp-new' ),
		'wfp_edit_wing_forms',
		'wfp-new',
		'wfp_admin_add_new_page'
	);

	add_action( 'load-' . $addnew, 'wfp_load_wing_form_admin', 10, 0 );

	$integration = WFP_Integration::get_instance();

	if ( $integration->service_exists() ) {
		$integration = add_submenu_page( 'wfp',
			__( 'Integration with External API', 'wing-forms' ),
			__( 'Integration', 'wing-forms' )
				. wfp_admin_menu_change_notice( 'wfp-integration' ),
			'wfp_manage_integration',
			'wfp-integration',
			'wfp_admin_integration_page'
		);

		add_action( 'load-' . $integration, 'wfp_load_integration_page', 10, 0 );
	}
}


function wfp_admin_menu_change_notice( $menu_slug = '' ) {
	$counts = apply_filters( 'wfp_admin_menu_change_notice',
		array(
			'wfp' => 0,
			'wfp-new' => 0,
			'wfp-integration' => 0,
		)
	);

	if ( empty( $menu_slug ) ) {
		$count = absint( array_sum( $counts ) );
	} elseif ( isset( $counts[$menu_slug] ) ) {
		$count = absint( $counts[$menu_slug] );
	} else {
		$count = 0;
	}

	if ( $count ) {
		return sprintf(
			' <span class="update-plugins %1$d"><span class="plugin-count">%2$s</span></span>',
			$count,
			esc_html( number_format_i18n( $count ) )
		);
	}

	return '';
}


add_action(
	'admin_enqueue_scripts',
	'wfp_admin_enqueue_scripts',
	10, 1
);

function wfp_admin_enqueue_scripts( $hook_suffix ) {
	if ( false === strpos( $hook_suffix, 'wfp' ) ) {
		return;
	}

	wp_enqueue_style( 'wing-forms-admin',
		wfp_plugin_url( 'admin/css/styles.css' ),
		array(), WFP_VERSION, 'all'
	);

	if ( wfp_is_rtl() ) {
		wp_enqueue_style( 'wing-forms-admin-rtl',
			wfp_plugin_url( 'admin/css/styles-rtl.css' ),
			array(), WFP_VERSION, 'all'
		);
	}

	wp_enqueue_script( 'wfp-admin',
		wfp_plugin_url( 'admin/js/scripts.js' ),
		array( 'jquery', 'jquery-ui-tabs' ),
		WFP_VERSION, true
	);

	if(isset($_GET['active-tab']))
	{
		$showactivetab = sanitize_text_field($_GET['active-tab']);
	}

	$args = array(
		'apiSettings' => array(
			'root' => esc_url_raw( rest_url( 'wing-forms/v1' ) ),
			'namespace' => 'wing-forms/v1',
			'nonce' => ( wp_installing() && ! is_multisite() )
				? '' : wp_create_nonce( 'wp_rest' ),
		),
		'pluginUrl' => wfp_plugin_url(),
		'saveAlert' => __(
			"The changes you made will be lost if you navigate away from this page.",
			'wing-forms' ),
		'activeTab' => isset( $_GET['active-tab'] )
			? (int) $showactivetab : 0,
		'configValidator' => array(
			'errors' => array(),
			'howToCorrect' => __( "How to resolve?", 'wing-forms' ),
			'oneError' => __( '1 configuration error detected', 'wing-forms' ),
			'manyErrors' => __( '%d configuration errors detected', 'wing-forms' ),
			'oneErrorInTab' => __( '1 configuration error detected in this tab panel', 'wing-forms' ),
			'manyErrorsInTab' => __( '%d configuration errors detected in this tab panel', 'wing-forms' ),
			'docUrl' => WFP_ConfigValidator::get_doc_link(),
			/* translators: screen reader text */
			'iconAlt' => __( '(configuration error)', 'wing-forms' ),
		),
	);

	if ( $post = wfp_get_current_wing_form()
	and current_user_can( 'wfp_edit_wing_form', $post->id() )
	and wfp_validate_configuration() ) {
		$config_validator = new WFP_ConfigValidator( $post );
		$config_validator->restore();
		$args['configValidator']['errors'] =
			$config_validator->collect_error_messages();
	}

	wp_localize_script( 'wfp-admin', 'wfp', $args );

	add_thickbox();

	wp_enqueue_script( 'wfp-admin-taggenerator',
		wfp_plugin_url( 'admin/js/tag-generator.js' ),
		array( 'jquery', 'thickbox', 'wfp-admin' ),
		WFP_VERSION,
		true
	);
}


add_filter(
	'set_screen_option_wfp_wing_forms_per_page',
	function ( $result, $option, $value ) {
		$wfp_screens = array(
			'wfp_wing_forms_per_page',
		);

		if ( in_array( $option, $wfp_screens ) ) {
			$result = $value;
		}

		return $result;
	},
	10, 3
);


function wfp_load_wing_form_admin() {
	global $plugin_page;

	$action = wfp_current_action();

	$sgetpage = sanitize_text_field($_GET['page']);

	do_action( 'wfp_admin_load',
		isset( $sgetpage ) ? trim( $sgetpage ) : '',
		$action
	);

	if ( 'save' == $action ) {
		$spost_ID = sanitize_text_field($_POST['post_ID']);
		$id = isset( $spost_ID ) ? $spost_ID : '-1';
		check_admin_referer( 'wfp-save-wing-form_' . $id );

		if ( ! current_user_can( 'wfp_edit_wing_form', $id ) ) {
			wp_die(
				__( "You are not allowed to edit this item.", 'wing-forms' )
			);
		}

		$args = sanitize_text_field($_REQUEST);
		$args['id'] = $id;

		$spost_title = sanitize_text_field($_POST['post_title']);
		$args['title'] = isset( $spost_title )
			? $spost_title : null;

		$swfplocale = sanitize_text_field($_POST['wfp-locale']);
		$args['locale'] = isset( $swfplocale )
			? $swfplocale : null;

		$swfpform = sanitize_text_field($_POST['wfp-form']);
		$args['form'] = isset( $swfpform )
			? $swfpform : '';

		$swfpmail = sanitize_text_field($_POST['wfp-mail']);
		$args['mail'] = isset( $swfpmail )
			? $swfpmail : array();

		$swfpmail2 = sanitize_text_field($_POST['wfp-mail-2']);
		$args['mail_2'] = isset( $swfpmail2 )
			? $swfpmail2 : array();

		$swfpmessages = sanitize_text_field($_POST['wfp-messages']);
		$args['messages'] = isset( $swfpmessages )
			? $swfpmessages : array();

		$swfpadditionalsettings = sanitize_text_field($_POST['wfp-additional-settings']);
		$args['additional_settings'] = isset( $swfpadditionalsettings )
			? $swfpadditionalsettings : '';

		$wing_form = wfp_save_wing_form( $args );

		if ( $wing_form and wfp_validate_configuration() ) {
			$config_validator = new WFP_ConfigValidator( $wing_form );
			$config_validator->validate();
			$config_validator->save();
		}

		$sactivetab = sanitize_text_field($_POST['active-tab']);

		$query = array(
			'post' => $wing_form ? $wing_form->id() : 0,
			'active-tab' => isset( $sactivetab )
				? (int) $sactivetab : 0,
		);

		if ( ! $wing_form ) {
			$query['message'] = 'failed';
		} elseif ( -1 == $id ) {
			$query['message'] = 'created';
		} else {
			$query['message'] = 'saved';
		}

		$redirect_to = add_query_arg( $query, menu_page_url( 'wfp', false ) );
		wp_safe_redirect( $redirect_to );
		exit();
	}

	$spost_ID = sanitize_text_field(['post_ID']);

	if ( 'copy' == $action ) {
		$id = empty( $spost_ID )
			? absint( $_REQUEST['post'] )
			: absint( $spost_ID );

		check_admin_referer( 'wfp-copy-wing-form_' . $id );

		if ( ! current_user_can( 'wfp_edit_wing_form', $id ) ) {
			wp_die(
				__( "You are not allowed to edit this item.", 'wing-forms' )
			);
		}

		$query = array();

		if ( $wing_form = wfp_wing_form( $id ) ) {
			$new_wing_form = $wing_form->copy();
			$new_wing_form->save();

			$query['post'] = $new_wing_form->id();
			$query['message'] = 'created';
		}

		$redirect_to = add_query_arg( $query, menu_page_url( 'wfp', false ) );

		wp_safe_redirect( $redirect_to );
		exit();
	}

	if ( 'delete' == $action ) {
		if ( ! empty( $spost_ID ) ) {
			check_admin_referer( 'wfp-delete-wing-form_' . $spost_ID );
		} elseif ( ! is_array( $_REQUEST['post'] ) ) {
			check_admin_referer( 'wfp-delete-wing-form_' . $_REQUEST['post'] );
		} else {
			check_admin_referer( 'bulk-posts' );
		}

		$posts = empty( $spost_ID )
			? (array) $_REQUEST['post']
			: (array) $spost_ID;

		$deleted = 0;

		foreach ( $posts as $post ) {
			$post = WFP_WingForm::get_instance( $post );

			if ( empty( $post ) ) {
				continue;
			}

			if ( ! current_user_can( 'wfp_delete_wing_form', $post->id() ) ) {
				wp_die(
					__( "You are not allowed to delete this item.", 'wing-forms' )
				);
			}

			if ( ! $post->delete() ) {
				wp_die( __( "Error in deleting.", 'wing-forms' ) );
			}

			$deleted += 1;
		}

		$query = array();

		if ( ! empty( $deleted ) ) {
			$query['message'] = 'deleted';
		}

		$redirect_to = add_query_arg( $query, menu_page_url( 'wfp', false ) );

		wp_safe_redirect( $redirect_to );
		exit();
	}

	$post = null;

	if ( 'wfp-new' == $plugin_page ) {
		if(isset($_GET['locale']))
		{
			$slocale = sanitize_text_field($_GET['locale']);
		}
		
		$post = WFP_WingForm::get_template( array(
			'locale' => isset( $slocale ) ? $slocale : null,
		) );
	} elseif ( ! empty( $_GET['post'] ) ) {
		$post = WFP_WingForm::get_instance( $_GET['post'] );
	}

	$current_screen = get_current_screen();

	$help_tabs = new WFP_Help_Tabs( $current_screen );

	if ( $post
	and current_user_can( 'wfp_edit_wing_form', $post->id() ) ) {
		$help_tabs->set_help_tabs( 'edit' );
	} else {
		$help_tabs->set_help_tabs( 'list' );

		if ( ! class_exists( 'WFP_Wing_Form_List_Table' ) ) {
			require_once WFP_PLUGIN_DIR . '/admin/includes/class-wing-forms-list-table.php';
		}

		add_filter(
			'manage_' . $current_screen->id . '_columns',
			array( 'WFP_Wing_Form_List_Table', 'define_columns' ),
			10, 0
		);

		add_screen_option( 'per_page', array(
			'default' => 20,
			'option' => 'wfp_wing_forms_per_page',
		) );
	}
}


function wfp_admin_management_page() {
	if ( $post = wfp_get_current_wing_form() ) {
		$post_id = $post->initial() ? -1 : $post->id();

		require_once WFP_PLUGIN_DIR . '/admin/includes/editor.php';
		require_once WFP_PLUGIN_DIR . '/admin/edit-wing-form.php';
		return;
	}

	if ( 'validate' == wfp_current_action()
	and wfp_validate_configuration()
	and current_user_can( 'wfp_edit_wing_forms' ) ) { 
		wfp_admin_bulk_validate_page();
		return;
	}

	$list_table = new WFP_Wing_Form_List_Table();
	$list_table->prepare_items();

?>
<div class="wrap" id="wfp-wing-form-list-table">

<h1 class="wp-heading-inline"><?php
	echo esc_html( __( 'Wing Forms', 'wing-forms' ) );
?></h1>

<?php
	if ( current_user_can( 'wfp_edit_wing_forms' ) ) {
		echo wfp_link(
			menu_page_url( 'wfp-new', false ),
			__( 'Add New', 'wing-forms' ),
			array( 'class' => 'page-title-action' )
		);
	}

	if ( ! empty( $_REQUEST['s'] ) ) {
		echo sprintf(
			'<span class="subtitle">'
			/* translators: %s: search keywords */
			. __( 'Search results for &#8220;%s&#8221;', 'wing-forms' )
			. '</span>',
			esc_html( $_REQUEST['s'] )
		);
	}
?>

<hr class="wp-header-end">

<?php
	do_action( 'wfp_admin_warnings',
		'wfp', wfp_current_action(), null
	);

	wfp_welcome_panel();

	do_action( 'wfp_admin_notices',
		'wfp', wfp_current_action(), null
	);
?>

<form method="get" action="">
	<input type="hidden" name="page" value="<?php echo esc_attr( $_REQUEST['page'] ); ?>" />
	<?php $list_table->search_box( __( 'Search Wing Forms', 'wing-forms' ), 'wfp-wing' ); ?>
	<?php $list_table->display(); ?>
</form>

</div>
<?php
}


function wfp_admin_add_new_page() {
	$post = wfp_get_current_wing_form();

	if ( ! $post ) {
		$post = WFP_WingForm::get_template();
	}

	$post_id = -1;

	require_once WFP_PLUGIN_DIR . '/admin/includes/editor.php';
	require_once WFP_PLUGIN_DIR . '/admin/edit-wing-form.php';
}


function wfp_load_integration_page() {

	$spage = sanitize_text_field($_GET['page']);

	do_action( 'wfp_admin_load',
		isset( $_GET['page'] ) ? trim( $spage ) : '',
		wfp_current_action()
	);

	$integration = WFP_Integration::get_instance();

	if ( isset( $_REQUEST['service'] )
	and $integration->service_exists( $_REQUEST['service'] ) ) {
		$service = $integration->get_service( $_REQUEST['service'] );
		$service->load( wfp_current_action() );
	}

	$help_tabs = new WFP_Help_Tabs( get_current_screen() );
	$help_tabs->set_help_tabs( 'integration' );
}


function wfp_admin_integration_page() {
	$integration = WFP_Integration::get_instance();

	$service = isset( $_REQUEST['service'] )
		? $integration->get_service( $_REQUEST['service'] )
		: null;

?>
<div class="wrap" id="wfp-integration">

<h1><?php echo esc_html( __( 'Integration with External API', 'wing-forms' ) ); ?></h1>

<p><?php
	echo sprintf(
		/* translators: %s: link labeled 'Integration with external APIs' */
		esc_html( __( "You can expand the possibilities of your wing forms by integrating them with external services. For details, see %s.", 'wing-forms' ) ),
		wfp_link(
			__( 'https://github.com/np2861996/wing-forms-Plugin', 'wing-forms' ),
			__( 'Integration with external APIs', 'wing-forms' )
		)
	);
?></p>

<?php
	do_action( 'wfp_admin_warnings',
		'wfp-integration', wfp_current_action(), $service
	);

	do_action( 'wfp_admin_notices',
		'wfp-integration', wfp_current_action(), $service
	);

	if ( $service ) {
		$message = isset( $_REQUEST['message'] ) ? sanitize_text_field($_REQUEST['message']) : '';
		$service->admin_notice( $message );

		$sservice =  sanitize_text_field($_REQUEST['service']);

		$integration->list_services( array(
			'include' => $sservice,
		) );
	} else {
		$integration->list_services();
	}
?>

</div>
<?php
}


add_action( 'wfp_admin_notices', 'wfp_admin_updated_message', 10, 3 );

function wfp_admin_updated_message( $page, $action, $object ) {
	if ( ! in_array( $page, array( 'wfp', 'wfp-new' ) ) ) {
		return;
	}

	if ( empty( $_REQUEST['message'] ) ) {
		return;
	}

	if ( 'created' == $_REQUEST['message'] ) {
		$updated_message = __( "Wing form created.", 'wing-forms' );
	} elseif ( 'saved' == $_REQUEST['message'] ) {
		$updated_message = __( "Wing form saved.", 'wing-forms' );
	} elseif ( 'deleted' == $_REQUEST['message'] ) {
		$updated_message = __( "Wing form deleted.", 'wing-forms' );
	}

	if ( ! empty( $updated_message ) ) {
		echo sprintf(
			'<div id="message" class="notice notice-success"><p>%s</p></div>',
			esc_html( $updated_message )
		);

		return;
	}

	if ( 'failed' == $_REQUEST['message'] ) {
		$updated_message =
			__( "There was an error saving the wing form.", 'wing-forms' );

		echo sprintf(
			'<div id="message" class="notice notice-error"><p>%s</p></div>',
			esc_html( $updated_message )
		);

		return;
	}

	if ( 'validated' == $_REQUEST['message'] ) {
		$bulk_validate = WFP::get_option( 'bulk_validate', array() );
		$count_invalid = isset( $bulk_validate['count_invalid'] )
			? absint( $bulk_validate['count_invalid'] ) : 0;

		if ( $count_invalid ) {
			$updated_message = sprintf(
				_n(
					/* translators: %s: number of wing forms */
					"Configuration validation completed. %s invalid wing form was found.",
					"Configuration validation completed. %s invalid wing forms were found.",
					$count_invalid, 'wing-forms'
				),
				number_format_i18n( $count_invalid )
			);

			echo sprintf(
				'<div id="message" class="notice notice-warning"><p>%s</p></div>',
				esc_html( $updated_message )
			);
		} else {
			$updated_message = __( "Configuration validation completed. No invalid wing form was found.", 'wing-forms' );

			echo sprintf(
				'<div id="message" class="notice notice-success"><p>%s</p></div>',
				esc_html( $updated_message )
			);
		}

		return;
	}
}


add_filter( 'plugin_action_links', 'wfp_plugin_action_links', 10, 2 );

function wfp_plugin_action_links( $links, $file ) {
	if ( $file != WFP_PLUGIN_BASENAME ) {
		return $links;
	}

	if ( ! current_user_can( 'wfp_read_wing_forms' ) ) {
		return $links;
	}

	$settings_link = wfp_link(
		menu_page_url( 'wfp', false ),
		__( 'Settings', 'wing-forms' )
	);

	array_unshift( $links, $settings_link );

	return $links;
}


add_action( 'wfp_admin_warnings', 'wfp_old_wp_version_error', 10, 3 );

function wfp_old_wp_version_error( $page, $action, $object ) {
	$wp_version = get_bloginfo( 'version' );

	if ( ! version_compare( $wp_version, WFP_REQUIRED_WP_VERSION, '<' ) ) {
		return;
	}

?>
<div class="notice notice-warning">
<p><?php
	echo sprintf(
		/* translators: 1: version of Wing Forms, 2: version of WordPress, 3: URL */
		__( '<strong>Wing Forms %1$s requires WordPress %2$s or higher.</strong> Please <a href="%3$s">update WordPress</a> first.', 'wing-forms' ),
		WFP_VERSION,
		WFP_REQUIRED_WP_VERSION,
		admin_url( 'update-core.php' )
	);
?></p>
</div>
<?php
}


add_action( 'wfp_admin_warnings', 'wfp_not_allowed_to_edit', 10, 3 );

function wfp_not_allowed_to_edit( $page, $action, $object ) {
	if ( $object instanceof WFP_WingForm ) {
		$wing_form = $object;
	} else {
		return;
	}

	if ( current_user_can( 'wfp_edit_wing_form', $wing_form->id() ) ) {
		return;
	}

	$message = __( "You are not allowed to edit this wing form.", 'wing-forms' );

	echo sprintf(
		'<div class="notice notice-warning"><p>%s</p></div>',
		esc_html( $message )
	);
}
