<?php

class WFP_SWV_URLRule extends WFP_SWV_Rule {

	const rule_name = 'url';

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
		$field7 = sanitize_text_field($_POST[$field]);
		$input = isset( $field7 ) ? $field7 : '';
		$input = wfp_array_flatten( $input );
		$input = wfp_exclude_blank( $input );

		foreach ( $input as $i ) {
			if ( ! wfp_is_url( $i ) ) {
				return new WP_Error( 'wfp_invalid_url',
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
