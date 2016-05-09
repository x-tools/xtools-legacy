<?php
class MemoryCacheStoreTest extends CacheTestCase {

	protected function setUp() {
		parent::setUp();
		$this->cache = new MemoryCacheStore();
	}

	public function testPersistanceGet() {
		// Not kept, memory does not persist
		$this->assertFalse( $this->cache->get( 'keep' ) );
	}
}
