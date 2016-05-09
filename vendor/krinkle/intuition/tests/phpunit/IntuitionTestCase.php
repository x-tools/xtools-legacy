<?php

class IntuitionTestCase extends PHPUnit_Framework_TestCase {

	protected $i18n;

	protected $live = array();

	protected function setUp() {
		parent::setUp();

		$this->i18n = new Intuition( 'general' );
		$this->live['SERVER'] = $_SERVER;
	}

	protected function tearDown() {
		$_SERVER = $this->live['SERVER'];
		unset( $this->i18n );

		parent::tearDown();
	}
}
