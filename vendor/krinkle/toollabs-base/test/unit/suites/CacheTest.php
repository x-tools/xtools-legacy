<?php
use org\bovigo\vfs\vfsStream;

class CacheTest extends CacheTestCase {
	protected static $root;

	public static function setUpBeforeClass() {
		self::$root = vfsStream::setup( 'test/cache' );
	}

	protected function setUp() {
		parent::setUp();

		$this->memoryCache1 = new MemoryCacheStore();
		$this->memoryCache2 = new MemoryCacheStore();
		$this->memoryCache3 = new MemoryCacheStore();
		$this->fileCache = new FileCacheStore(array(
			'dir' => vfsStream::url( 'test/cache' )
		));

		$this->cache = new Cache( array(
			$this->memoryCache1,
			$this->memoryCache2,
			$this->memoryCache3,
			$this->fileCache
		) );
	}

	public function testMultiWrite() {
		$key = sha1(mt_rand());
		$this->cache->set( $key, 'value' );

		$this->assertEquals( 'value', $this->memoryCache1->get( $key ) );
		$this->assertEquals( 'value', $this->memoryCache2->get( $key ) );
		$this->assertEquals( 'value', $this->memoryCache3->get( $key ) );

		$this->memoryCache3->delete( $key );
		$this->assertEquals( 'value', $this->cache->get( $key ) );

		$this->memoryCache2->delete( $key );
		$this->assertEquals( 'value', $this->cache->get( $key ) );

		$this->memoryCache1->delete( $key );
		$this->assertEquals( 'value', $this->cache->get( $key ) );

		// The value remains available as long as any of the caches has it.
		$this->fileCache->delete( $key );
		$this->assertFalse( $this->cache->get( $key ) );
	}

	public function testHarvesting() {
		$this->cache->enableHarvest();

		$key = sha1(mt_rand());
		$this->cache->set( $key, 'value' );

		$this->memoryCache1->delete( $key );
		$this->memoryCache2->delete( $key );
		$this->memoryCache3->delete( $key );
		$this->assertFalse( $this->memoryCache1->get( $key ) );
		$this->assertFalse( $this->memoryCache2->get( $key ) );
		$this->assertFalse( $this->memoryCache3->get( $key ) );
		$this->assertEquals( 'value', $this->fileCache->get( $key ) );

		// Falls back to FileCacheStore
		$this->assertEquals( 'value', $this->cache->get( $key ) );

		// Value was harvested into the frontend cache
		$this->assertEquals( 'value', $this->memoryCache1->get( $key ) );
		$this->assertFalse( $this->memoryCache2->get( $key ) );
		$this->assertFalse( $this->memoryCache3->get( $key ) );
		$this->assertEquals( 'value', $this->fileCache->get( $key ) );
	}
}
