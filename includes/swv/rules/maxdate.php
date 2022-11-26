<?php

class WFP_SWV_MaxDateRule extends WFP_SWV_Rule {

	const rule_name = 'maxdate';

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
		$sinput_field2 = sanitize_text_field($_POST[$field]);
		$input = isset( $sinput_field2 ) ? $sinput_field2 : '';
		$input = wfp_array_flatten( $input );
		$input = wfp_exclude_blank( $input );

		$threshold = $this->get_property( 'threshold' );

		if ( ! wfp_is_date( $threshold ) ) {
			return true;
		}

		foreach ( $input as $i ) {
			if ( wfp_is_date( $i ) and $threshold < $i ) {
				return new WP_Error( 'wfp_invalid_maxdate',
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
