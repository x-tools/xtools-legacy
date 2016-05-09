<?php
/**
 * Caching classes
 *
 * Inspired by php-UniversalCache <https://github.com/c9s/php-UniversalCache>
 * Inspired by ObjectCache and BagOStuff <https://github.com/wikimedia/mediawiki-core>
 *
 * @author Timo Tijhof, 2015
 * @license Public domain
 * @package toollabs-base
 * @since v0.5.0
 */

class Cache implements CacheInterface {
	protected $frontend;
	protected $stores;

	/**
	 * @param CacheInterface[] $stores
	 */
	public function __construct( Array $stores ) {
		$this->stores = $stores;

		foreach ( $stores as $i => $store ) {
			kfLog( "Registered " . get_class( $store ) );
		}
	}

	/**
	 * Enable harvest behaviour for the first cache store.
	 *
	 * The store marked as "harvester" will receive set() commands
	 * when a multi-store get() results a miss from this one,
	 * that way it will be populated for the next request.
	 *
	 * Typically this is an instance of MemoryCacheStore.
	 *
	 * Example configuration:
	 *
	 *     $tmpCache = new MemoryCacheStore();
	 *     $redisCache = new RedisCacheStore( .. );
	 *     $cache = new Cache( array( $tmpCache, $redisCache ) );
	 *
	 * When a value is stored, it will be in both. Within that
	 * request it will be retreived from memory only without having
	 * to hit Redis.
	 *
	 * On subsequent requests, though, it would always fallback to
	 * Redis. Even if it is called multiple times within the
	 * subsequent request, it never comes back in memory store.
	 *
	 *     $cache->enableHarvest();
	 *
	 * Enabling harvest behaviour will automatically hold on to the
	 * value retrieved from Redis, in memory, within the current request.
	 *
	 * NB: When a value is harvested, the default expiry will be used (this
	 * information can generally not be covered from an existing store).
	 * This is generally not an issue as memory stores just expire at the end
	 * of the request.
	 */
	public function enableHarvest() {
		$this->frontend = $this->stores[0];
	}

	public function addStore( CacheInterface $store ) {
		$this->stores[] = $store;

		kfLog( "Registered " . get_class( $store ) );
	}

	/**
	 * @param string $key
	 * @return mixed|bool
	 */
	public function get( $key ) {
		foreach ( $this->stores as $store ) {
			$data = $store->get( $key );
			if ( $data !== false ) {
				kfLog( "Cache hit for '$key' in " . get_class( $store ) );
				// If we have a frontend and this wasn't from there,
				// be sure to populate it.
				if ( $this->frontend && $store !== $this->frontend ) {
					$this->frontend->set( $key, $data );
				}
				return $data;
			}
		}
		kfLog( "Cache miss for '$key'" );
		return false;
	}

	/**
	 * @param string $key
	 * @param mixed $data
	 * @param int $ttl
	 * @return bool
	 */
	public function set( $key, $data, $ttl = 0 ) {
		foreach ( $this->stores as $store ) {
			if ( !$store->set( $key, $data, $ttl ) ) {
				kfLog( "Failed to store value for '$key' in " . get_class( $store ) );
			}
		}
		return true;
	}

	/**
	 * @param string $key
	 */
	public function delete( $key ) {
		foreach ( $this->stores as $store ) {
			$store->delete( $key );
		}
	}
}

interface CacheInterface {
	/**
	 * @param string $key
	 * @return mixed|bool Retreived data or boolean false
	 */
	public function get( $key );

	/**
	 * @param string $key
	 * @param mixed $data
	 * @param int $ttl In seconds from now, 0 for indefinitely
	 * @return bool
	 */
	public function set( $key, $data, $ttl = 0 );

	/**
	 * @param string $key
	 * @return bool
	 */
	public function delete( $key );
}

abstract class CacheStoreBase implements CacheInterface {
	/**
	 * @param int $ttl
	 * @return int Timestamp in seconds
	 */
	protected function convertExpiry( $ttl ) {
		if ( $ttl !== 0 ) {
			return time() + $ttl;
		}
		return $ttl;
	}

	protected function encode( $data ) {
		if ( is_int( $data ) ) {
			return $data;
		}
		return serialize( $data );
	}

	protected function decode( $data ) {
		if ( is_int( $data ) || ctype_digit( $data ) ) {
			return (int)$data;
		}
		return unserialize( $data );
	}
}

/**
 * Inspired by php-MemoryCache <https://github.com/c9s/php-UniversalCache>
 * Inspired by HashBagOStuff <https://github.com/wikimedia/mediawiki-core>
 */
class MemoryCacheStore extends CacheStoreBase {
	/** @var array */
	protected $store = array();

	/**
	 * @return bool
	 */
	protected function expire( $key ) {
		$expiryTime = $this->store[ $key ][ 1 ];

		if ( $expiryTime === 0 || $expiryTime > time() ) {
			return false;
		}

		$this->delete( $key );
		return true;
	}

	/**
	 * @param string $key
	 * @return mixed|bool
	 */
	public function get( $key ) {
		if ( !isset( $this->store[ $key ] ) ) {
			return false;
		}

		if ( $this->expire( $key ) ) {
			return false;
		}

		return $this->store[ $key ][ 0 ];
	}

	/**
	 * @param string $key
	 * @param mixed $data
	 * @param int $ttl
	 * @return bool
	 */
	public function set( $key, $data, $ttl = 0 ) {
		$this->store[ $key ] = array(
			$data,
			$this->convertExpiry( $ttl )
		);
		return true;
	}

	/**
	 * @param string $key
	 * @return bool
	 */
	public function delete( $key ) {
		if ( isset( $this->store[ $key ] ) ) {
			unset( $this->store[ $key ] );
			return true;
		}
		return false;
	}
}

class FileCacheStore extends CacheStoreBase {
	/** @var string */
	protected $dir;

	/**
	 * Configuration:
	 * - string dir
	 */
	public function __construct( Array $config ) {
		if ( !isset( $config['dir'] ) ) {
			throw new Exception( 'Cache directory not specified.' );
		}

		if ( !is_dir( $config['dir'] ) || !is_writable( $config['dir'] ) ) {
			throw new Exception( 'Cache directory not found or not writable.' );
		}

		$this->dir = $config['dir'];
	}

	protected function getFilepath( $key ) {
		return $this->dir . DIRECTORY_SEPARATOR . sha1( $key ) . '.json';
	}

	/** @return array|bool */
	protected function read( $key ) {
		$fp = $this->getFilepath( $key );
		if ( !is_readable( $fp ) ) {
			return false;
		}
		return json_decode( file_get_contents( $fp ), /* assoc = */ true );
	}

	/** @return bool */
	protected function write( $key, Array $store ) {
		$fp = $this->getFilepath( $key );
		return file_put_contents( $fp, json_encode( $store ) ) !== false;
	}

	/**
	 * @return bool
	 */
	protected function expire( $store, $key ) {
		if ( $store['expiryTime'] === 0 || $store['expiryTime'] > time() ) {
			return false;
		}

		$this->delete( $key );
		return true;
	}

	/**
	 * @param string $key
	 * @return mixed|bool
	 */
	public function get( $key ) {
		$store = $this->read( $key );
		if ( !$store ) {
			return false;
		}

		if ( $this->expire( $store, $key ) ) {
			return false;
		}

		return $this->decode( $store['value'] );
	}

	/**
	 * @param string $key
	 * @param mixed $data
	 * @param int $ttl
	 * @return bool
	 */
	public function set( $key, $data, $ttl = 0 ) {
		return $this->write( $key, array(
			'value' => $this->encode( $data ),
			'expiryTime' => $this->convertExpiry( $ttl ),
		) );
	}

	/**
	 * @param string $key
	 */
	public function delete( $key ) {
		$fp = $this->getFilepath( $key );
		if ( file_exists( $fp ) ) {
			unlink( $fp );
		}
	}

}

class RedisCacheStore extends CacheStoreBase {
	/** @var Redis */
	protected $client;

	/** @var string */
	protected $prefix;

	protected static $defaults = array(
		'port' => 6379,
		'timeout' => 2,
		'prefix' => '',
	);

	protected static $presets = array(
		'toollabs' => array(
			'host' => 'tools-redis',
			'port' => 6379,
		),
	);

	/**
	 * Configuration:
	 * - string host
	 * - int port
	 * - float timeout Value in seconds (0 for unlimited)
	 * - string prefix
	 */
	public function __construct( Array $config ) {
		if ( !class_exists( 'Redis' ) ) {
			throw new Exception( 'Redis class not loaded' );
		}

		if ( isset( $config['preset'] ) ) {
			if ( !isset( self::$presets[ $config['preset'] ] ) ) {
				throw new Exception( "Unknown Redis preset '{$config['preset']}'" );
			}
			$config = array_merge( self::$presets[ $config['preset'] ], $config );
			if ( $config['preset'] === 'toollabs'
				&& ( !isset( $config['prefix'] ) || strlen( $config['prefix'] ) < 10 )
			) {
				throw new Exception( 'Redis prefix is required in Tool Labs.' );
			}
		}

		if ( !isset( $config['host'] ) ) {
			throw new Exception( 'Redis host not specified.' );
		}

		$config += self::$defaults;

		$client = new Redis();
		$client->connect( $config['host'], $config['port'], $config['timeout'] );

		$this->client = $client;
		$this->prefix = $config['prefix'];
	}

	/**
	 * @param string $key
	 * @return mixed
	 */
	public function get( $key ) {
		$encdata = $this->client->get( $this->prefix . $key );
		if ( $encdata === false ) {
			return false;
		}
		return $this->decode( $encdata );
	}

	/**
	 * @param string $key
	 * @param int|string $data
	 * @param int $ttl
	 * @return bool
	 */
	public function set( $key, $data, $ttl = 0 ) {
		$encdata = $this->encode( $data );
		if ( $ttl === 0 ) {
			return $this->client->set( $this->prefix . $key, $encdata );
		}
		return $this->client->setex( $this->prefix . $key, $ttl, $encdata );

	}

	/**
	 * @param string $key
	 * @return bool
	 */
	public function delete( $key ) {
		// Redis::delete returns number of keys deleted
		return $this->client->delete( $this->prefix . $key ) === 1;
	}
}
