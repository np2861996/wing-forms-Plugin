<?php

add_filter( 'map_meta_cap', 'wfp_map_meta_cap', 10, 4 );

function wfp_map_meta_cap( $caps, $cap, $user_id, $args ) {
	$meta_caps = array(
		'wfp_edit_wing_form' => WFP_ADMIN_READ_WRITE_CAPABILITY,
		'wfp_edit_wing_forms' => WFP_ADMIN_READ_WRITE_CAPABILITY,
		'wfp_read_wing_form' => WFP_ADMIN_READ_CAPABILITY,
		'wfp_read_wing_forms' => WFP_ADMIN_READ_CAPABILITY,
		'wfp_delete_wing_form' => WFP_ADMIN_READ_WRITE_CAPABILITY,
		'wfp_delete_wing_forms' => WFP_ADMIN_READ_WRITE_CAPABILITY,
		'wfp_manage_integration' => 'manage_options',
		'wfp_submit' => 'read',
	);

	$meta_caps = apply_filters( 'wfp_map_meta_cap', $meta_caps );

	$caps = array_diff( $caps, array_keys( $meta_caps ) );

	if ( isset( $meta_caps[$cap] ) ) {
		$caps[] = $meta_caps[$cap];
	}

	return $caps;
}
