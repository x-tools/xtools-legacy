<?php
/**
 * Configuration
 *
 * @author Timo Tijhof, 2015
 * @license Public domain
 * @package toollabs-base
 * @since v0.1.0
 */

class GlobalConfig {

	/**
	 * Set from LocalConfig
	 */
	public $remoteBase = './base';
	public $userAgent = 'BaseTool/0.3.0 (https://github.com/Krinkle/toollabs-base)';
	public $cookiePrefix = '';

	// Set by BaseTool
	public $I18N = null;

	protected $logSectionStack = array(
		'(init)'
	);

	protected $confInitiated = false;
	protected $debugMode = null;
	protected $runlog = '';
	protected $runlogFlushCount = 0;
	protected $dbUsername;
	protected $dbPassword;

	/**
	 * Initiated certain configuration variables
	 * that depend on other factors (ie. environment, request parameters etc.)
	 *
	 * @return Boolean: True if initiation request was execututed (ie. first call), false on later ones
	 */
	public function initConfig() {
		if ( $this->confInitiated ) {
			return false;
		}

		global $kgReq;
		date_default_timezone_set( 'UTC' );

		// User agent (required to get data from wmf domains)
		ini_set( 'user_agent', $this->userAgent );

		// Allow request parameter to toggle debug mode
		if ( $kgReq->hasKey( 'debug' ) ) {
			$isDebug = $kgReq->getFuzzyBool( 'debug' );

			// Set cookie to remember it in the next request.
			// Also makes it easier to debug by not having to append it to every
			// individual request.
			$kgReq->setCookie( 'debug', $isDebug ? '1' : null );
		} else {
			$isDebug = $kgReq->getCookie( 'debug' ) === '1';
		}

		$this->debugMode = $isDebug;

		$this->confInitiated = true;

		return true;
	}

	/**
	 * Get remote base
	 *
	 * @return string Remote base path complete from the protocol:// without trailing slash
	 */
	public function getRemoteBase() { return $this->remoteBase; }

	/**
	 * Get cookie prefix
	 *
	 * @return string
	 */
	public function getCookiePrefix() { return $this->cookiePrefix; }

	/**
	 * Get path to home directory
	 *
	 * @return string Home directory of tool user account (eg. /home/username or /data/project/mytool)
	 */
	public function getLocalHome() {
		$info = posix_getpwuid(posix_geteuid());
		return $info['dir'];
	}

	/**
	 * Wether debug mode is enabled
	 *
	 * @return bool
	 */
	public function isDebugMode() { return $this->debugMode; }

	/**
	 * Return the run log of everything that happened so far
	 */
	public function getDebugLog() { return $this->runlog; }

	/**
	 * Write one or more lines to the debug log
	 */
	public function writeDebugLog( $val ) {
		if ( $this->debugMode === false ) {
			return;
		}

		$this->runlog .= $this->getLogSection() . '> '
			. $val
			. "\n";
	}

	/**
	 * Clear the run log
	 */
	public function clearDebugLog() {
		$this->runlog = '';
	}

	public function getLogSection() {
		return end( $this->logSectionStack );
	}

	public function startLogSection( $name ) {
		$this->logSectionStack[] = $name;
	}

	public function endLogSection( $name ) {
		$item = array_pop( $this->logSectionStack );
		if ( $item !== $name ) {
			kfLog( "Log section mismatch (in: $item, out: $name)" );
		}
	}

	protected function fetchDbCredentials() {
		// Read and cache in-class
		$file = $this->getLocalHome() . '/replica.my.cnf';
		if ( !is_readable( $file ) || !is_file( $file ) ) {
			throw new Exception( 'Failed to fetch credentials from replica.my.cnf' );
		}
		$cnf = parse_ini_file( $file );
		if ( !$cnf || !$cnf['user'] || !$cnf['password'] ) {
			throw new Exception( 'Failed to fetch credentials from replica.my.cnf' );
		}
		$this->dbUsername = $cnf['user'];
		$this->dbPassword = $cnf['password'];
	}

	/**
	 * Get the database username
	 */
	public function getDbUsername() {
		$this->fetchDbCredentials();
		return $this->dbUsername;
	}

	/**
	 * Get the database password
	 */
	public function getDbPassword() {
		$this->fetchDbCredentials();
		return $this->dbPassword;
	}

}
