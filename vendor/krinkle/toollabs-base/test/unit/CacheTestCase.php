<?php
abstract class CacheTestCase extends PHPUnit_Framework_TestCase {

	public static function provideValues() {
		return array(
			array( 'bool0', false ),
			array( 'bool1', true ),
			array( 'num0', 0 ),
			array( 'num1', 1 ),
			array( 'num3', 123 ),
			array( 'str', '123' ),
			array( 'arr', array( 'num' => 123, 'str' => '123' ) ),
			array( 'obj', (object)array( 'num' => 123, 'str' => '123' ) ),
		);
	}

	/**
	 * @dataProvider provideValues
	 */
	public function testValues( $key, $value ) {
		$this->assertFalse( $this->cache->get( $key ) );

		$this->cache->set( $key, $value );
		$this->assertEquals( $value, $this->cache->get( $key ) );

		$this->cache->delete( $key, $value );
		$this->assertFalse( $this->cache->get( $key ) );
	}

	public function testPersistanceSet() {
		$this->assertTrue( $this->cache->set( 'keep', 'remember' ) );
	}

	public function testPersistanceGet() {
		// Verify that, when given a new instance of the cache store,
		// the values are still retrievable.
		$this->assertEquals( 'remember', $this->cache->get( 'keep' ) );
	}
}
