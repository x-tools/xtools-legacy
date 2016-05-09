<?php
/**
 * @author Timo Tijhof, 2015
 * @license Public domain
 * @package toollabs-base
 * @since v0.5.0
 */

class Wiki {
	const NS_MAIN = 0;
	const NS_TALK = 1;
	const NS_USER = 2;
	const NS_USER_TALK = 3;
	const NS_PROJECT = 4;
	const NS_PROJECT_TALK = 5;
	const NS_FILE = 6;
	const NS_FILE_TALK = 7;
	const NS_MEDIAWIKI = 8;
	const NS_MEDIAWIKI_TALK = 9;
	const NS_TEMPLATE = 10;
	const NS_TEMPLATE_TALK = 11;
	const NS_CATEGORY = 14;
	const NS_CATEGORY_TALK = 15;

	protected static $instances = array();

	protected $dbname;

	protected static $siprops = array(
		'general',
		'namespaces',
	);

	/**
	 * @param string $dbname
	 * @return Wiki
	 */
	public static function byDbname( $dbname ) {
		if ( !isset( self::$instances[ $dbname ] ) ) {
			self::$instances[ $dbname ] = new static( $dbname );
		}
		return self::$instances[ $dbname ];
	}

	/**
	 * Based on mediawiki-core's wfUrlencode()
	 *
	 * @param string $pageName
	 * @return string
	 */
	protected static function urlencodePage( $pageName ) {
		static $needle = null;

		if ( $needle === null ) {
			$needle = array( '%3B', '%40', '%24', '%21', '%2A', '%28', '%29', '%2C', '%2F', '%3A' );
		}

		return str_ireplace(
			$needle,
			array( ';', '@', '$', '!', '*', '(', ')', ',', '/', ':' ),
			urlencode( str_replace( ' ', '_', $pageName ) )
		);
	}

	/**
	 * @param string $dbname
	 */
	protected function __construct( $dbname ) {
		$this->dbname = $dbname;
	}

	/**
	 * @return string
	 */
	public function getDbname() {
		return $this->dbname;
	}

	/**
	 * @return array
	 */
	public function getWikiInfo() {
		return LabsDB::getDbInfo( $this->dbname );
	}

	/**
	 * @return object|bool
	 */
	protected function fetchSiteInfo() {
		$section = new KfLogSection( __METHOD__ );

		$wikiInfo = $this->getWikiInfo();
		$data = kfApiRequest( $wikiInfo['url'], array(
			'meta' => 'siteinfo',
			'siprop' => implode( '|', self::$siprops ),
		) );
		foreach ( self::$siprops as $siprop ) {
			if ( !isset( $data->query->$siprop ) ) {
				return false;
			}
		}
		return $data->query;
	}

	/**
	 * @param string $prop See self::$siprops
	 * @return array
	 * @throws Exception If property is not supported
	 * @throws Exception If fetching failed
	 */
	public function getSiteInfo( $prop ) {
		global $kgCache;

		$key = kfCacheKey( 'base', 'mwapi', $this->dbname, 'siteinfo', $prop );
		$value = $kgCache->get( $key );
		if ( $value === false ) {
			if ( !in_array( $prop, self::$siprops ) ) {
				throw new Exception( 'Unsupported property "' . $prop . '"' );
			}
			$values = $this->fetchSiteInfo();
			if ( $values !== false ) {
				foreach ( self::$siprops as $siprop ) {
					$kgCache->set(
						kfCacheKey( 'base', 'mwapi', $this->dbname, 'siteinfo', $siprop  ),
						$values->$siprop,
						3600
					);
				}
				$value = $values->$prop;
			} else {
				// Don't try again within this request nor for the next few
				$value = null;
				$kgCache->set( $key, $value, 60 * 5 );
			}
		}
		if ( $value === null ) {
			throw new Exception( 'Fetch siteinfo failed' );
		}
		return $value;
	}

	/**
	 * @return array
	 */
	public function getNamespaces() {
		static $namespaces = null;
		if ( $namespaces === null ) {
			$namespaces = array();
			$data = $this->getSiteInfo( 'namespaces' );
			foreach ( $data as $i => &$ns ) {
				$namespaces[ $ns->id ] = $ns->{"*"};
			}
		}

		return $namespaces;
	}

	/**
	 * @param int $namespaceId
	 * @param string $pageTitle
	 * @return string
	 */
	public function getPageName( $namespaceId, $pageTitle ) {
		if ( $namespaceId === Wiki::NS_MAIN ) {
			return $pageTitle;
		}
		$namespaces = $this->getNamespaces();
		if ( !isset( $namespaces[ $namespaceId ] ) ) {
			throw new Exception( "Unknown namespace id: $namespaceId" );
		}

		return $namespaces[ $namespaceId ] . ":$pageTitle";
	}

	/**
	 * @param string $pageName
	 * @param array $query [optional]
	 * @return string URI
	 */
	public function getPageUrl( $pageName, Array $query = null ) {
		static $general = null;
		if ( $general === null ) {
			$general = $this->getSiteInfo( 'general' );
		}

		if ( $query ) {
			return $general->server . $general->script .
				'?title=' . self::urlencodePage( $pageName ) .
				'&' . http_build_query( $query );
		}

		return $general->server . str_replace( '$1', self::urlencodePage( $pageName ), $general->articlepath );
	}

	/**
	 * @param array $query
	 * @return string URI
	 */
	public function getUrl( Array $query ) {
		static $general = null;
		if ( $general === null ) {
			$general = $this->getSiteInfo( 'general' );
		}

		return $general->server . $general->script . '?' . http_build_query( $query );
	}

}
