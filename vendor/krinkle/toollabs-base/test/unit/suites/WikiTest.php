<?php
class WikiTest extends PHPUnit_Framework_TestCase {

	public function testGetInstance() {
		$wiki1 = WikiMock::byDbname( 'foo' );
		$wiki2 = WikiMock::byDbname( 'foo' );
		$wiki3 = WikiMock::byDbname( 'bar' );

		$this->assertTrue( $wiki1 === $wiki2, 'foo' );
		$this->assertFalse( $wiki2 === $wiki3, 'bar');
	}

	/**
	 * @covers Wiki::getPageUrl
	 */
	public function testGetPageUrl() {
		$wiki = WikiMock::byDbname( 'foo' );

		$this->assertEquals( '//foo.example.org/wiki/Example_Page',
			$wiki->getPageUrl( 'Example Page' )
		);
		$this->assertEquals( '//foo.example.org/w/index.php?title=Example_Page&action=edit',
			$wiki->getPageUrl( 'Example Page', array( 'action' => 'edit' ) )
		);
	}

	/**
	 * @covers Wiki::getUrl
	 */
	public function testGetUrl() {
		$wiki = WikiMock::byDbname( 'foo' );

		$this->assertEquals( '//foo.example.org/w/index.php?curid=123&action=delete',
			$wiki->getUrl( array( 'curid' => 123, 'action' => 'delete' ) )
		);
	}

	public function testGetNamespaces() {
		$wiki = WikiMock::byDbname( 'foo' );
		$namespaces = $wiki->getNamespaces();
		$this->assertEquals( array(
			'-1' => 'Special',
			'0' => '',
			'1' => 'Talk',
			'2' => 'User',
		), $namespaces );

		$this->assertEquals( 'User', $namespaces[ Wiki::NS_USER ] );
	}

	public function testGetPageName() {
		$wiki = WikiMock::byDbname( 'foo' );
		$this->assertEquals( 'Sandbox', $wiki->getPageName( Wiki::NS_MAIN, 'Sandbox' ) );
		$this->assertEquals( 'Talk:Sandbox', $wiki->getPageName( Wiki::NS_TALK, 'Sandbox' ) );
		$this->assertEquals( 'User:Sandbox', $wiki->getPageName( Wiki::NS_USER, 'Sandbox' ) );
	}
}

class WikiMock extends Wiki {

	public function getWikiInfo() {
		return array(
			'dbname' => $this->dbname,
			'lang' => 'en',
			'family' => 'test',
			'url' => 'http://' . $this->dbname . '.example.org',
			'slice' => $this->dbname . '.testdb',
		);
	}

	protected function fetchSiteInfo() {
		return (object) array(
			'general' => (object) array(
				'articlepath' => '/wiki/$1',
				'scriptpath' => '/w',
				'script' => '/w/index.php',
				'server' => '//' . $this->dbname . '.example.org',
				'servername' => $this->dbname . '.example.org',
			),
			'namespaces' => (object) array(
				'-1' => (object) array(
					'id' => -1,
					'case' => 'first-letter',
					'*' => 'Special',
					'canonical' => 'Special',
				),
				'0' => (object) array(
					'id' => 0,
					'case' => 'first-letter',
					'*' => '',
					'subpages' => '',
					'content' => '',
				),
				'1' => (object) array(
					'id' => 1,
					'case' => 'first-letter',
					'*' => 'Talk',
					'subpages' => '',
					'canonical' => 'Talk',
				),
				'2' => (object) array(
					'id' => 2,
					'case' => 'first-letter',
					'*' => 'User',
					'subpages' => '',
					'canonical' => 'User',
				),
			),
		);
	}
}
