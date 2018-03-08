<?php

class GP_Views_View {
	var $name;
	var $public;
	var $terms = array();
	var $priorities = array();
	var $screenshot;
	var $rank;

	function __construct( $data ){
		foreach ( $data as $key => $value ) {
			if ( property_exists( $this, $key ) ) {
				$this->$key = $value;
			}
		}

	}

	function validate() {
		if ( empty( $this->terms ) || ! $this->name ) {
			throw new Exception( 'Missing property' );
		}
	}
}
