<?php
/**
 * @author Timo Tijhof, 2015
 * @license Public domain
 * @package toollabs-base
 * @since v0.5.0
 */

class LabsDB {
	/**
	 * @var array PDO objects keyed by hostname
	 */
	protected static $dbConnections = array();

	/**
	 * @var array
	 */
	protected static $dbInfos;

	/**
	 * @var array
	 */
	protected static $wikiInfos;

	/**
	 * Get a database connection by hostname
	 *
	 * Returns a previously established connection or initiates a new one.
	 *
	 * @return PDO
	 * @throws If connection failed
	 */
	public static function getConnection( $hostname, $dbname ) {
		if ( isset( self::$dbConnections[ $hostname ] ) ) {
			$conn = self::$dbConnections[ $hostname ];
		} else {
			$section = new kfLogSection( __METHOD__ );
			try {
				$conn = new LoggedPDO(
					'mysql:host=' . $hostname . ';dbname=' . $dbname . '_p;charset=utf8',
					kfDbUsername(),
					kfDbPassword()
				);
			    $conn->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );
			} catch ( Exception $e ) {
				throw new Exception( "Connection to '$hostname' failed: " . $e->getMessage() );
			}
			self::$dbConnections[ $hostname ] = $conn;
			return $conn;
		}

		// Re-used connection, switch database first.
		self::selectDB( $conn, $dbname );

		return $conn;
	}

	/**
	 * @return PDO
	 */
	public static function getMetaDB() {
		static $metaServer;
		// meta_p is replicated on all shards, any of these is fine
		static $servers = array(
			's1.labsdb',
			's2.labsdb',
			's3.labsdb',
			's4.labsdb',
			's5.labsdb',
			's6.labsdb',
			's7.labsdb',
		);

		// See if we have a connection to any of the shards already
		if ( !$metaServer ) {
			foreach ( $servers as $server ) {
				if ( isset( self::$dbConnections[ $server ] ) ) {
					$metaServer = $server;
					break;
				}
			}
		}

		// Fallback to making a new connection to s7
		if ( !$metaServer ) {
			$metaServer = 's7.labsdb';
		}

		return self::getConnection( $metaServer, 'meta' );
	}

	/**
	 * Get a database connection by dbname.
	 *
	 * Usage:
	 *
	 *     $conn = LabsDB::getDB( 'aawiki' );
	 *     $rows = LabsDB::query( $conn, 'SELECT * WHERE name = "str"' );
	 *
	 *     $conn = LabsDB::getDB( 'aawiki' );
	 *     $rows = LabsDB::query( $conn, 'SELECT * WHERE name = :name',
	 *         array( ':name' => "string" )
	 *     );
	 *
	 *     $conn = LabsDB::getDB( 'aawiki' );
	 *     $m = $conn->prepare( 'SELECT * WHERE total = :total' );
	 *     $m->bindParam( ':total', $total, PDO::PARAM_INT );
	 *     $m->execute();
	 *     $rows = $m->fetchAll( PDO::FETCH_ASSOC );
	 *
	 * @return PDO
	 * @throws If dbname could not be found
	 */
	public static function getDB( $dbname ) {
		if ( $dbname === 'meta' ) {
			return self::getMetaDB();
		}

		$wikiInfo = self::getDbInfo( $dbname );
		if ( !$wikiInfo['slice'] ) {
			throw new Exception( "Incomplete database information for '$dbname'" );
		}

		return self::getConnection( $wikiInfo['slice'], $dbname );
	}

	/**
	 * @param PDO $conn
	 */
	public static function selectDB( $conn, $dbname ) {
        $stmt = $conn->prepare( 'USE `' . $dbname . '_p`;' );
        $stmt->execute();
        unset( $stmt );
	}

	/**
	 *
	 * @param PDO $conn
	 * @param string $db Database name
	 * @param string $sql SQL query (with placeholders)
	 * @param array $bindings Bindings of type PDO::PARAM_STR. Use prepare() if you
	 *  need different types or if you want to execute multiple times.
	 * @return array Rows
	 */
	public static function query( $conn, $sql, $bindings = null ) {
		$section = new kfLogSection( __METHOD__ );

		if ( $bindings ) {
			$m = $conn->prepare( $sql );
			$m->execute( $bindings );
		} else {
			$m = $conn->query( $sql );
			$m->execute();
		}
		return $m->fetchAll( PDO::FETCH_ASSOC );
	}

	/**
	 * @return array
	 */
	protected static function fetchAllDbInfos() {
		$rows = self::query( self::getMetaDB(),
			'SELECT dbname, family, url, slice
			FROM wiki
			WHERE is_closed = 0
			ORDER BY url ASC'
		);

		$dbInfos = array();
		foreach ( $rows as &$row ) {
			$dbInfos[ $row['dbname'] ] = $row;
		}

		return $dbInfos;
	}

	/**
	 * Get information for all (replicated) databases.
	 *
	 * See https://wikitech.wikimedia.org/wiki/Nova_Resource:Tools/Help#Metadata_database
	 *
	 * @return array
	 */
	public static function getAllDbInfos() {
		if ( !isset( self::$dbInfos ) ) {
			global $kgCache;
			$key = kfCacheKey( 'base', 'labsdb', 'meta', 'dbinfos' );
			$value = $kgCache->get( $key );
			if ( $value === false ) {
				$value = self::fetchAllDbInfos();
				$kgCache->set( $key, $value, 3600 * 24 );
			}
			self::$dbInfos = $value;
		}

		return self::$dbInfos;
	}

	/**
	 * @param string $dbname
	 * @return Array
	 */
	public static function getDbInfo( $dbname ) {
		$dbInfos = self::getAllDbInfos();

		if ( !isset( $dbInfos[ $dbname ] ) ) {
			throw new Exception( "Unable to find '$dbname'" );
		}

		return $dbInfos[ $dbname ];
	}

	/**
	 * Like getAllDbInfos, but without databases that aren't wikis.
	 *
	 * Because meta_p.wiki also contains dbname='centralauth' we need to
	 * filter out non-wikis. Do so by removing rows with NULL values for url
	 * (see wmbug.com/65789). Could simply be done in SQL, but we want to
	 * cache all db infos, so do here instead.
	 *
	 * @return Array
	 */
	public static function getAllWikiInfos() {
		if ( !isset( self::$wikiInfos ) ) {
			$wikiInfos = self::getAllDbInfos();
			foreach ( $wikiInfos as $dbname => &$wikiInfo ) {
				if ( !$wikiInfo['url'] ) {
					unset( $wikiInfos[ $dbname ] );
				}
			}

			self::$wikiInfos = $wikiInfos;
		}

		return self::$wikiInfos;
	}

	public static function purgeConnections() {
		// PDO doesn't have an explicit close method.
		// Just dereference them.
		self::$dbConnections = array();
	}
}

class LoggedPDO extends PDO {
	public function __construct( $dsn, $username = null, $password = null ) {
		parent::__construct( $dsn, $username, $password );
	}

	public function prepare( $statement, $driver_options = null ) {
		kfLog( self::generalizeSQL( "query-prepare: $statement" ) );
		return parent::prepare( $statement );
	}

	public function query( $statement ) {
		kfLog( self::generalizeSQL( "query: $statement" ) );
		return parent::query( $statement );
	}

	/**
	 * Remove most variables from an SQL query and replace them with X or N markers.
	 *
	 * Based on Database.php of mediawik-core 1.24-alpha
	 *
	 * @param string $sql
	 * @return string
	 */
	protected static function generalizeSQL( $sql ) {
		// This does the same as the regexp below would do, but in such a way
		// as to avoid crashing php on some large strings.
		# $sql = preg_replace( "/'([^\\\\']|\\\\.)*'|\"([^\\\\\"]|\\\\.)*\"/", "'X'", $sql );

		$sql = str_replace( "\\\\", '', $sql );
		$sql = str_replace( "\\'", '', $sql );
		$sql = str_replace( "\\\"", '', $sql );
		$sql = preg_replace( "/'.*'/s", "'X'", $sql );
		$sql = preg_replace( '/".*"/s', "'X'", $sql );

		// All newlines, tabs, etc replaced by single space
		$sql = preg_replace( '/\s+/', ' ', $sql );

		// All numbers => N
		$sql = preg_replace( '/-?\d+(,-?\d+)+/s', 'N,...,N', $sql );
		$sql = preg_replace( '/-?\d+/s', 'N', $sql );

		return $sql;
	}
}
