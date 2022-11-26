<?php

class WFP_SWV_RequiredRule extends WFP_SWV_Rule {

	const rule_name = 'required';

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
		$s_input6 = sanitize_text_field($_POST[$field]);
		$input = isset( $s_input6 ) ? $s_input6 : '';

		$input = wfp_array_flatten( $input );
		$input = wfp_exclude_blank( $input );

		if ( empty( $input ) ) {
			return new WP_Error( 'wfp_invalid_required',
				$this->get_property( 'error' )
			);
		}

		return true;
	}

	public function to_array() {
		return array( 'rule' => self::rule_name ) + (array) $this->properties;
	}
}
