<?php

class WFP_SWV_MaxNumberRule extends WFP_SWV_Rule {

	const rule_name = 'maxnumber';

	public function matches( $context ) {
		if ( false === parent::matches( $context ) ) {
			return false;
		}

		if ( empty( $context['text'] ) ) {
			return false;
		}

		return true;
	}

	public function validate( $context ) {
		$field = $this->get_property( 'field' );
		$field_input4 = sanitize_text_field($_POST[$field]);
		$input = isset( $field_input4 ) ? $field_input4 : '';
		$input = wfp_array_flatten( $input );
		$input = wfp_exclude_blank( $input );

		$threshold = $this->get_property( 'threshold' );

		if ( ! wfp_is_number( $threshold ) ) {
			return true;
		}

		foreach ( $input as $i ) {
			if ( wfp_is_number( $i ) and (float) $threshold < (float) $i ) {
				return new WP_Error( 'wfp_invalid_maxnumber',
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
