<?php

class WFP_SWV_MinLengthRule extends WFP_SWV_Rule {

	const rule_name = 'minlength';

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
		$sfield_5 = sanitize_text_field($_POST[$field]);
		$input = isset( $sfield_5 ) ? $sfield_5 : '';
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

		if ( $threshold <= $total ) {
			return true;
		} else {
			return new WP_Error( 'wfp_invalid_minlength',
				$this->get_property( 'error' )
			);
		}
	}

	public function to_array() {
		return array( 'rule' => self::rule_name ) + (array) $this->properties;
	}
}
