<?php
/**
 * The Akismet integration module
 *
 * @link https://akismet.com/development/api/
 */


add_filter( 'wfp_spam', 'wfp_akismet', 10, 2 );

function wfp_akismet( $spam, $submission ) {
	if ( $spam ) {
		return $spam;
	}

	if ( ! wfp_akismet_is_available() ) {
		return false;
	}

	if ( ! $params = wfp_akismet_submitted_params() ) {
		return false;
	}

	$c = array();

	$c['comment_author'] = $params['author'];
	$c['comment_author_email'] = $params['author_email'];
	$c['comment_author_url'] = $params['author_url'];
	$c['comment_content'] = $params['content'];

	$c['blog'] = get_option( 'home' );
	$c['blog_lang'] = get_locale();
	$c['blog_charset'] = get_option( 'blog_charset' );
	$c['user_ip'] = sanitize_text_field($_SERVER['REMOTE_ADDR']);
	$c['user_agent'] = sanitize_text_field($_SERVER['HTTP_USER_AGENT']);
	$c['referrer'] = sanitize_text_field($_SERVER['HTTP_REFERER']);
	$c['comment_type'] = 'wing-form';

	$datetime = date_create_immutable(
		'@' . $submission->get_meta( 'timestamp' )
	);

	if ( $datetime ) {
		$c['comment_date_gmt'] = $datetime->format( DATE_ATOM );
	}

	if ( $permalink = get_permalink() ) {
		$c['permalink'] = $permalink;
	}

	$ignore = array( 'HTTP_COOKIE', 'HTTP_COOKIE2', 'PHP_AUTH_PW' );

	foreach ( $_SERVER as $key => $value ) {
		if ( ! in_array( $key, (array) $ignore ) ) {
			$c["$key"] = $value;
		}
	}

	$c = apply_filters( 'wfp_akismet_parameters', $c );

	if ( wfp_akismet_comment_check( $c ) ) {
		$spam = true;

		$submission->add_spam_log( array(
			'agent' => 'akismet',
			'reason' => __( "Akismet returns a spam response.", 'wing-forms' ),
		) );
	} else {
		$spam = false;
	}

	return $spam;
}


/**
 * Returns true if Akismet is active and has a valid API key.
 */
function wfp_akismet_is_available() {
	if ( is_callable( array( 'Akismet', 'get_api_key' ) ) ) {
		return (bool) Akismet::get_api_key();
	}

	return false;
}


/**
 * Returns an array of parameters based on the current form submission.
 * Returns false if Akismet is not active on the wing form.
 */
function wfp_akismet_submitted_params() {
	$akismet_tags = array_filter(
		wfp_scan_form_tags(),
		function ( $tag ) {
			$akismet_option = $tag->get_option( 'akismet',
				'(author|author_email|author_url)',
				true
			);

			return (bool) $akismet_option;
		}
	);

	if ( ! $akismet_tags ) { // Akismet is not active on this wing form.
		return false;
	}

	$params = array(
		'author' => '',
		'author_email' => '',
		'author_url' => '',
		'content' => '',
	);

	foreach ( (array) $_POST as $key => $val ) {
		if ( '_wfp' == substr( $key, 0, 6 )
		or '_wpnonce' == $key ) {
			continue;
		}

		$vals = array_filter(
			wfp_array_flatten( $val ),
			function ( $val ) {
				return '' !== trim( $val );
			}
		);

		if ( empty( $vals ) ) {
			continue;
		}

		if ( $tags = wfp_scan_form_tags( array( 'name' => $key ) ) ) {
			$tag = $tags[0];

			$akismet_option = $tag->get_option( 'akismet',
				'(author|author_email|author_url)',
				true
			);

			if ( 'author' === $akismet_option ) {
				$params['author'] = sprintf(
					'%s %s',
					$params['author'],
					implode( ' ', $vals )
				);

				continue;
			}

			if ( 'author_email' === $akismet_option
			and '' === $params['author_email'] ) {
				$params['author_email'] = $vals[0];
				continue;
			}

			if ( 'author_url' === $akismet_option
			and '' === $params['author_url'] ) {
				$params['author_url'] = $vals[0];
				continue;
			}

			$vals = array_filter(
				$vals,
				function ( $val ) use ( $tag ) {
					if ( wfp_form_tag_supports( $tag->type, 'selectable-values' )
					and in_array( $val, $tag->labels ) ) {
						return false;
					} else {
						return true;
					}
				}
			);
		}

		if ( $vals ) {
			$params['content'] .= "\n\n" . implode( ', ', $vals );
		}
	}

	$params = array_map( 'trim', $params );

	return $params;
}


/**
 * Sends data to Akismet.
 *
 * @param array $comment Submission and environment data.
 * @return bool True if Akismet called it spam, or false otherwise.
 */
function wfp_akismet_comment_check( $comment ) {
	$spam = false;
	$query_string = wfp_build_query( $comment );

	if ( is_callable( array( 'Akismet', 'http_post' ) ) ) {
		$response = Akismet::http_post( $query_string, 'comment-check' );
	} else {
		return $spam;
	}

	if ( 'true' == $response[1] ) {
		$spam = true;
	}

	if ( $submission = WFP_Submission::get_instance() ) {
		$submission->akismet = array( 'comment' => $comment, 'spam' => $spam );
	}

	return apply_filters( 'wfp_akismet_comment_check', $spam, $comment );
}


add_filter( 'wfp_posted_data', 'wfp_akismet_posted_data', 10, 1 );

/**
 * Removes Akismet-related properties from the posted data.
 *
 * This does not affect the $_POST variable itself.
 *
 * @link https://plugins.trac.wordpress.org/browser/akismet/tags/5.0/_inc/akismet-frontend.js
 */
function wfp_akismet_posted_data( $posted_data ) {
	if ( wfp_akismet_is_available() ) {
		$posted_data = array_diff_key(
			$posted_data,
			array(
				'ak_bib' => '',
				'ak_bfs' => '',
				'ak_bkpc' => '',
				'ak_bkp' => '',
				'ak_bmc' => '',
				'ak_bmcc' => '',
				'ak_bmk' => '',
				'ak_bck' => '',
				'ak_bmmc' => '',
				'ak_btmc' => '',
				'ak_bsc' => '',
				'ak_bte' => '',
				'ak_btec' => '',
				'ak_bmm' => '',
			)
		);
	}

	return $posted_data;
}
