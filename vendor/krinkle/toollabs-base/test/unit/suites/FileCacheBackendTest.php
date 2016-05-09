<?php
use org\bovigo\vfs\vfsStream;

class FileCacheStoreTest extends CacheTestCase {
	protected static $root;

	public static function setUpBeforeClass() {
		self::$root = vfsStream::setup( 'test/cache' );
	}

	protected function setUp() {
		parent::setUp();

		$this->cache = new FileCacheStore(array(
			'dir' => vfsStream::url( 'test/cache' )
		));
	}
}
