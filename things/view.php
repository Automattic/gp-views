<?php

class GP_Views_View {
	var $name;
	var $terms = array();
	var $priorities = array();

	function __construct( $data ){
		foreach ( $data as $key => $value ) {
			if ( property_exists( $this, $key ) ) {
				$this->$key = $value;
			}
		}

	}

	function validate() {
		if ( empty( $this->terms ) || ! $this->name ) {
			throw new Exception( 'Missing property');
		}
	}
}