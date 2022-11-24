<?php

add_filter( 'wfp_spam', 'wfp_disallowed_list', 10, 2 );

function wfp_disallowed_list( $spam, $submission ) {
	if ( $spam ) {
		return $spam;
	}

	$target = wfp_array_flatten( $submission->get_posted_data() );
	$target[] = $submission->get_meta( 'remote_ip' );
	$target[] = $submission->get_meta( 'user_agent' );
	$target = implode( "\n", $target );

	$word = wfp_check_disallowed_list( $target );

	$word = wfp_apply_filters_deprecated(
		'wfp_submission_is_blacklisted',
		array( $word, $submission ),
		'5.3',
		'wfp_submission_has_disallowed_words'
	);

	$word = apply_filters(
		'wfp_submission_has_disallowed_words',
		$word,
		$submission
	);

	if ( $word ) {
		if ( is_bool( $word ) ) {
			$reason = __( "Disallowed words are used.", 'wing-forms' );
		} else {
			$reason = sprintf(
				__( "Disallowed words (%s) are used.", 'wing-forms' ),
				implode( ', ', (array) $word )
			);
		}

		$submission->add_spam_log( array(
			'agent' => 'disallowed_list',
			'reason' => $reason,
		) );
	}

	$spam = (bool) $word;

	return $spam;
}

function wfp_check_disallowed_list( $target ) {
	$mod_keys = get_option( 'disallowed_keys' );

	if ( is_scalar( $mod_keys ) ) {
		$mod_keys = trim( $mod_keys );
	} else {
		$mod_keys = '';
	}

	if ( '' === $mod_keys ) {
		return false;
	}

	foreach ( explode( "\n", $mod_keys ) as $word ) {
		$word = trim( $word );
		$length = strlen( $word );

		if ( $length < 2 or 256 < $length ) {
			continue;
		}

		$pattern = sprintf( '#%s#i', preg_quote( $word, '#' ) );

		if ( preg_match( $pattern, $target ) ) {
			return $word;
		}
	}

	return false;
}

function wfp_blacklist_check( $target ) {
	wfp_deprecated_function(
		__FUNCTION__,
		'5.3',
		'wfp_check_disallowed_list'
	);

	return wfp_check_disallowed_list( $target );
}
