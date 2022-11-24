<?php

add_action( 'init', 'wfp_init_block_editor_assets', 10, 0 );

function wfp_init_block_editor_assets() {
	$assets = array();

	$asset_file = wfp_plugin_path(
		'includes/block-editor/index.asset.php'
	);

	if ( file_exists( $asset_file ) ) {
		$assets = include( $asset_file );
	}

	$assets = wp_parse_args( $assets, array(
		'src' => wfp_plugin_url( 'includes/block-editor/index.js' ),
		'dependencies' => array(
			'wp-api-fetch',
			'wp-components',
			'wp-compose',
			'wp-blocks',
			'wp-element',
			'wp-i18n',
		),
		'version' => WFP_VERSION,
	) );

	wp_register_script(
		'wing-forms-block-editor',
		$assets['src'],
		$assets['dependencies'],
		$assets['version']
	);

	wp_set_script_translations(
		'wing-forms-block-editor',
		'wing-forms'
	);

	register_block_type(
		'wing-forms/wing-form-selector',
		array(
			'editor_script' => 'wing-forms-block-editor',
		)
	);

	$wing_forms = array_map(
		function ( $wing_form ) {
			return array(
				'id' => $wing_form->id(),
				'slug' => $wing_form->name(),
				'title' => $wing_form->title(),
				'locale' => $wing_form->locale(),
			);
		},
		WFP_WingForm::find( array(
			'posts_per_page' => 20,
		) )
	);

	wp_add_inline_script(
		'wing-forms-block-editor',
		sprintf(
			'window.wfp = {wingForms:%s};',
			json_encode( $wing_forms )
		),
		'before'
	);

}
