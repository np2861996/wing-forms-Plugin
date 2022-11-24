<?php

add_action(
	'wp_enqueue_scripts',
	function () {
		$assets = array();
		$asset_file = wfp_plugin_path( 'includes/swv/js/index.asset.php' );

		if ( file_exists( $asset_file ) ) {
			$assets = include( $asset_file );
		}

		$assets = wp_parse_args( $assets, array(
			'src' => wfp_plugin_url( 'includes/swv/js/index.js' ),
			'dependencies' => array(),
			'version' => WFP_VERSION,
			'in_footer' => true,
		) );

		wp_register_script( 'swv',
			$assets['src'],
			$assets['dependencies'],
			$assets['version'],
			$assets['in_footer']
		);
	},
	10, 0
);
