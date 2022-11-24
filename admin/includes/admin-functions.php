<?php

function wfp_current_action() {
	if ( isset( $_REQUEST['action'] ) and -1 != $_REQUEST['action'] ) {
		return $_REQUEST['action'];
	}

	if ( isset( $_REQUEST['action2'] ) and -1 != $_REQUEST['action2'] ) {
		return $_REQUEST['action2'];
	}

	return false;
}

function wfp_admin_has_edit_cap() {
	return current_user_can( 'wfp_edit_wing_forms' );
}

function wfp_add_tag_generator( $name, $title, $elm_id, $callback, $options = array() ) {
	$tag_generator = WFP_TagGenerator::get_instance();
	return $tag_generator->add( $name, $title, $callback, $options );
}
