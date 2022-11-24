<?php

/**
 * Validates uploaded files and moves them to the temporary directory.
 *
 * @param array $file An item of `$_FILES`.
 * @param string|array $args Optional. Arguments to control behavior.
 * @return array|WP_Error Array of file paths, or WP_Error if validation fails.
 */
function wfp_unship_uploaded_file( $file, $args = '' ) {
	$args = wp_parse_args( $args, array(
		'required' => false,
		'filetypes' => '',
		'limit' => MB_IN_BYTES,
	) );

	foreach ( array( 'name', 'size', 'tmp_name', 'error' ) as $key ) {
		if ( ! isset( $file[$key] ) ) {
			$file[$key] = array();
		}
	}

	$names = wfp_array_flatten( $file['name'] );
	$sizes = wfp_array_flatten( $file['size'] );
	$tmp_names = wfp_array_flatten( $file['tmp_name'] );
	$errors = wfp_array_flatten( $file['error'] );

	foreach ( $errors as $error ) {
		if ( ! empty( $error ) and UPLOAD_ERR_NO_FILE !== $error ) {
			return new WP_Error( 'wfp_upload_failed_php_error',
				wfp_get_message( 'upload_failed_php_error' )
			);
		}
	}

	if ( isset( $args['schema'] ) and isset( $args['name'] ) ) {
		$result = $args['schema']->validate( array(
			'file' => true,
			'field' => $args['name'],
		) );

		if ( is_wp_error( $result ) ) {
			return $result;
		}
	}

	// Move uploaded file to tmp dir
	$uploads_dir = wfp_upload_tmp_dir();
	$uploads_dir = wfp_maybe_add_random_dir( $uploads_dir );

	$uploaded_files = array();

	foreach ( $names as $key => $name ) {
		$tmp_name = $tmp_names[$key];

		if ( empty( $tmp_name ) or ! is_uploaded_file( $tmp_name ) ) {
			continue;
		}

		$filename = $name;
		$filename = wfp_canonicalize( $filename, array( 'strto' => 'as-is' ) );
		$filename = wfp_antiscript_file_name( $filename );

		$filename = apply_filters( 'wfp_upload_file_name',
			$filename, $name, $args
		);

		$filename = wp_unique_filename( $uploads_dir, $filename );
		$new_file = path_join( $uploads_dir, $filename );

		if ( false === @move_uploaded_file( $tmp_name, $new_file ) ) {
			return new WP_Error( 'wfp_upload_failed',
				wfp_get_message( 'upload_failed' )
			);
		}

		// Make sure the uploaded file is only readable for the owner process
		chmod( $new_file, 0400 );

		$uploaded_files[] = $new_file;
	}

	return $uploaded_files;
}


add_filter(
	'wfp_messages',
	'wfp_file_messages',
	10, 1
);

/**
 * A wfp_messages filter callback that adds messages for
 * file-uploading fields.
 */
function wfp_file_messages( $messages ) {
	return array_merge( $messages, array(
		'upload_failed' => array(
			'description' => __( "Uploading a file fails for any reason", 'wing-forms' ),
			'default' => __( "There was an unknown error uploading the file.", 'wing-forms' ),
		),

		'upload_file_type_invalid' => array(
			'description' => __( "Uploaded file is not allowed for file type", 'wing-forms' ),
			'default' => __( "You are not allowed to upload files of this type.", 'wing-forms' ),
		),

		'upload_file_too_large' => array(
			'description' => __( "Uploaded file is too large", 'wing-forms' ),
			'default' => __( "The uploaded file is too large.", 'wing-forms' ),
		),

		'upload_failed_php_error' => array(
			'description' => __( "Uploading a file fails for PHP error", 'wing-forms' ),
			'default' => __( "There was an error uploading the file.", 'wing-forms' ),
		),
	) );
}


add_filter(
	'wfp_form_enctype',
	'wfp_file_form_enctype_filter',
	10, 1
);

/**
 * A wfp_form_enctype filter callback that sets the enctype attribute
 * to multipart/form-data if the form has file-uploading fields.
 */
function wfp_file_form_enctype_filter( $enctype ) {
	$multipart = (bool) wfp_scan_form_tags( array(
		'feature' => 'file-uploading',
	) );

	if ( $multipart ) {
		$enctype = 'multipart/form-data';
	}

	return $enctype;
}


/**
 * Converts a MIME type string to an array of corresponding file extensions.
 *
 * @param string $mime MIME type.
 *                     Wildcard (*) is available for the subtype part.
 * @return array Corresponding file extensions.
 */
function wfp_convert_mime_to_ext( $mime ) {
	static $mime_types = array();

	$mime_types = wp_get_mime_types();

	$results = array();

	if ( preg_match( '%^([a-z]+)/([*]|[a-z0-9.+-]+)$%i', $mime, $matches ) ) {
		foreach ( $mime_types as $key => $val ) {
			if ( '*' === $matches[2] and str_starts_with( $val, $matches[1] . '/' )
		 	or $val === $matches[0] ) {
				$results = array_merge( $results, explode( '|', $key ) );
			}
		}
	}

	$results = array_unique( $results );
	$results = array_filter( $results );
	$results = array_values( $results );

	return $results;
}


/**
 * Returns a formatted list of acceptable filetypes.
 *
 * @param string|array $types Optional. Array of filetypes.
 * @param string $format Optional. Pre-defined format designator.
 * @return string Formatted list of acceptable filetypes.
 */
function wfp_acceptable_filetypes( $types = 'default', $format = 'regex' ) {
	if ( 'default' === $types or empty( $types ) ) {
		$types = array(
			'audio/*',
			'video/*',
			'image/*',
		);
	} else {
		$types = array_map(
			function ( $type ) {
				if ( is_string( $type ) ) {
					return preg_split( '/[\s|,]+/', strtolower( $type ) );
				}
			},
			(array) $types
		);

		$types = wfp_array_flatten( $types );
		$types = array_filter( array_unique( $types ) );
	}

	if ( 'attr' === $format or 'attribute' === $format ) {
		$types = array_map(
			function ( $type ) {
				if ( false === strpos( $type, '/' ) ) {
					return sprintf( '.%s', trim( $type, '.' ) );
				} elseif ( preg_match( '%^([a-z]+)/[*]$%i', $type, $matches ) ) {
					if ( in_array( $matches[1], array( 'audio', 'video', 'image' ) ) ) {
						return $type;
					} else {
						return '';
					}
				} elseif ( wfp_convert_mime_to_ext( $type ) ) {
					return $type;
				}
			},
			$types
		);

		$types = array_filter( $types );

		return implode( ',', $types );

	} elseif ( 'regex' === $format ) {
		$types = array_map(
			function ( $type ) {
				if ( false === strpos( $type, '/' ) ) {
					return preg_quote( trim( $type, '.' ) );
				} elseif ( $type = wfp_convert_mime_to_ext( $type ) ) {
					return $type;
				}
			},
			$types
		);

		$types = wfp_array_flatten( $types );
		$types = array_filter( array_unique( $types ) );

		return implode( '|', $types );
	}

	return '';
}


add_action(
	'wfp_init',
	'wfp_init_uploads',
	10, 0
);

/**
 * Initializes the temporary directory for uploaded files.
 */
function wfp_init_uploads() {
	$dir = wfp_upload_tmp_dir();

	if ( is_dir( $dir ) and is_writable( $dir ) ) {
		$htaccess_file = path_join( $dir, '.htaccess' );

		if ( ! file_exists( $htaccess_file )
		and $handle = @fopen( $htaccess_file, 'w' ) ) {
			fwrite( $handle, "Deny from all\n" );
			fclose( $handle );
		}
	}
}


/**
 * Creates a child directory with a randomly generated name.
 *
 * @param string $dir The parent directory path.
 * @return string The child directory path if created, otherwise the parent.
 */
function wfp_maybe_add_random_dir( $dir ) {
	do {
		$rand_max = mt_getrandmax();
		$rand = zeroise( mt_rand( 0, $rand_max ), strlen( $rand_max ) );
		$dir_new = path_join( $dir, $rand );
	} while ( file_exists( $dir_new ) );

	if ( wp_mkdir_p( $dir_new ) ) {
		return $dir_new;
	}

	return $dir;
}


/**
 * Returns the directory path for uploaded files.
 *
 * @return string Directory path.
 */
function wfp_upload_tmp_dir() {
	if ( defined( 'WFP_UPLOADS_TMP_DIR' ) ) {
		$dir = path_join( WP_CONTENT_DIR, WFP_UPLOADS_TMP_DIR );
		wp_mkdir_p( $dir );

		if ( wfp_is_file_path_in_content_dir( $dir ) ) {
			return $dir;
		}
	}

	$dir = path_join( wfp_upload_dir( 'dir' ), 'wfp_uploads' );
	wp_mkdir_p( $dir );
	return $dir;
}


add_action(
	'shutdown',
	'wfp_cleanup_upload_files',
	20, 0
);

/**
 * Cleans up files in the temporary directory for uploaded files.
 *
 * @param int $seconds Files older than this are removed. Default 60.
 * @param int $max Maximum number of files to be removed in a function call.
 *                 Default 100.
 */
function wfp_cleanup_upload_files( $seconds = 60, $max = 100 ) {
	$dir = trailingslashit( wfp_upload_tmp_dir() );

	if ( ! is_dir( $dir )
	or ! is_readable( $dir )
	or ! wp_is_writable( $dir ) ) {
		return;
	}

	$seconds = absint( $seconds );
	$max = absint( $max );
	$count = 0;

	if ( $handle = opendir( $dir ) ) {
		while ( false !== ( $file = readdir( $handle ) ) ) {
			if ( '.' == $file
			or '..' == $file
			or '.htaccess' == $file ) {
				continue;
			}

			$mtime = @filemtime( path_join( $dir, $file ) );

			if ( $mtime and time() < $mtime + $seconds ) { // less than $seconds old
				continue;
			}

			wfp_rmdir_p( path_join( $dir, $file ) );
			$count += 1;

			if ( $max <= $count ) {
				break;
			}
		}

		closedir( $handle );
	}
}


add_action(
	'wfp_admin_warnings',
	'wfp_file_display_warning_message',
	10, 3
);

/**
 * Displays warning messages about file-uploading fields.
 */
function wfp_file_display_warning_message( $page, $action, $object ) {
	if ( $object instanceof WFP_WingForm ) {
		$wing_form = $object;
	} else {
		return;
	}

	$has_tags = (bool) $wing_form->scan_form_tags( array(
		'feature' => 'file-uploading',
	) );

	if ( ! $has_tags ) {
		return;
	}

	$uploads_dir = wfp_upload_tmp_dir();

	if ( ! is_dir( $uploads_dir ) or ! wp_is_writable( $uploads_dir ) ) {
		$message = sprintf(
			/* translators: %s: the path of the temporary folder */
			__( 'This wing form has file uploading fields, but the temporary folder for the files (%s) does not exist or is not writable. You can create the folder or change its permission manually.', 'wing-forms' ),
			$uploads_dir
		);

		echo sprintf(
			'<div class="notice notice-warning"><p>%s</p></div>',
			esc_html( $message )
		);
	}
}
