<?php

class WFP_SWV_MaxFileSizeRule extends WFP_SWV_Rule {

	const rule_name = 'maxfilesize';

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
		$input = isset( $_FILES[$field]['size'] ) ? sanitize_text_field($_FILES[$field]['size']) : '';
		$input = wfp_array_flatten( $input );
		$input = wfp_exclude_blank( $input );

		if ( empty( $input ) ) {
			return true;
		}

		$threshold = $this->get_property( 'threshold' );

		if ( $threshold < array_sum( $input ) ) {
			return new WP_Error( 'wfp_invalid_maxfilesize',
				$this->get_property( 'error' )
			);
		}

		return true;
	}

	public function to_array() {
		return array( 'rule' => self::rule_name ) + (array) $this->properties;
	}
}
