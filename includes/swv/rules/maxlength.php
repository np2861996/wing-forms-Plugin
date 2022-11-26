<?php

class WFP_SWV_MaxLengthRule extends WFP_SWV_Rule {

	const rule_name = 'maxlength';

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
		$sinput_field3 = sanitize_text_field($_POST[$field]);
		$input = isset( $sinput_field3 ) ? $sinput_field3 : '';
		$input = wfp_array_flatten( $input );
		$input = wfp_exclude_blank( $input );

		if ( empty( $input ) ) {
			return true;
		}

		$total = 0;

		foreach ( $input as $i ) {
			$total += wfp_count_code_units( $i );
		}

		$threshold = (int) $this->get_property( 'threshold' );

		if ( $total <= $threshold ) {
			return true;
		} else {
			return new WP_Error( 'wfp_invalid_maxlength',
				$this->get_property( 'error' )
			);
		}
	}

	public function to_array() {
		return array( 'rule' => self::rule_name ) + (array) $this->properties;
	}
}
