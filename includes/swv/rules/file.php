<?php

class WFP_SWV_FileRule extends WFP_SWV_Rule {

	const rule_name = 'file';

	public function matches( $context ) {
		if ( false === parent::matches( $context ) ) {
			return false;
		}

		if ( empty( $context['file'] ) ) {
			return false;
		}

		return true;
	}

	public function validate( $context ) {
		$field = $this->get_property( 'field' );
		$sfilefield = sanitize_text_field($_FILES[$field]['name']);
		$input = isset( $sfilefield ) ? $sfilefield : '';
		$input = wfp_array_flatten( $input );
		$input = wfp_exclude_blank( $input );

		$acceptable_filetypes = array();

		foreach ( (array) $this->get_property( 'accept' ) as $accept ) {
			if ( false === strpos( $accept, '/' ) ) {
				$acceptable_filetypes[] = strtolower( $accept );
			} else {
				foreach ( wfp_convert_mime_to_ext( $accept ) as $ext ) {
					$acceptable_filetypes[] = sprintf(
						'.%s',
						strtolower( trim( $ext, ' .' ) )
					);
				}
			}
		}

		$acceptable_filetypes = array_unique( $acceptable_filetypes );

		foreach ( $input as $i ) {
			$last_period_pos = strrpos( $i, '.' );

			if ( false === $last_period_pos ) { // no period
				return new WP_Error( 'wfp_invalid_file',
					$this->get_property( 'error' )
				);
			}

			$suffix = strtolower( substr( $i, $last_period_pos ) );

			if ( ! in_array( $suffix, $acceptable_filetypes, true ) ) {
				return new WP_Error( 'wfp_invalid_file',
					$this->get_property( 'error' )
				);
			}
		}

		return true;
	}

	public function to_array() {
		return array( 'rule' => self::rule_name ) + (array) $this->properties;
	}
}
