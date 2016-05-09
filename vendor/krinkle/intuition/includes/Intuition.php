<?php
/**
 * Main class.
 *
 * @copyright 2011-2015 See AUTHORS.txt
 * @license CC-BY 3.0 <https://creativecommons.org/licenses/by/3.0/>
 * @package intuition
 */

// Protect against invalid entry
if ( !defined( 'INTUITION' ) ) {
	echo "This file is not a valid entry point\n";
	exit;
}

/**
 * This file contains the main class which the individual tools will
 * creating an instance of to use and configure their i18n.
 */
class Intuition {
	// Message file cache. Does not contain local overrides. See $messageBlob.
	protected static $messageCache = array();

	// Fallback cache. Stored as an array of language codes with their fallback as value.
	protected static $fallbackCache = null;

	public $localBaseDir;

	public $version = '0.2.3';

	// One of 'in-tool' or 'dashboard'
	public $mode = null;

	// Address to the public html, must end in a slash.
	public $dashboardHome = '//tools.wmflabs.org/intuition/';

	// Constructor options
	protected $currentDomain;

	protected $currentLanguage;

	protected $suppressfatal;

	protected $suppressnotice;

	protected $suppressbrackets;

	protected $stayalive;

	protected $useRequestParam;

	// Changing this will invalidate all cookies
	protected $cookieNames = array(
		'userlang' => 'TsIntuition_userlang',
		'track-expire' => 'TsIntuition_expiry'
	);

	// Changing this will break existing permalinks
	protected $paramNames = array( 'userlang' => 'userlang' );

	// In-class message storage.
	// Format: $store['domain']['lang']['message-key'] = 'Raw message value';
	protected $messageBlob = array();

	// Which domains and languages have been loaded.
	// $loadedDomains['general']['en'] = true;
	protected $loadedDomains = array();

	// Based on MediaWiki 1.26alpha ()
	// These codes are mapped to their replacements before loading.
	// Associated language files may still exist, but will not be used.
	protected $deprecatedLangCodes = array(
		'als' => 'gsw',
		'bat-smg' => 'sgs',
		'be-x-old' => 'be-tarask',
		'bh' => 'bho',
		'fiu-vro' => 'vro',
		'no' => 'nb',
		'qqq' => 'en',
		'qqx' => 'en',
		'roa-rup' => 'rup',
		'simple' => 'en',
		'zh-classical' => 'lzh',
		'zh-min-nan' => 'nan',
		'zh-yue' => 'yue',
	);

	// Language names are stored as an array of language codes
	// with their native name as value
	// Such as as for Spanish: langNames['es'] = 'Español';
	protected $langNames = null;

	protected $availableLanguages = null;

	protected $domainInfos = array();

	// These variable names will be extracted from the message files
	protected $includeVariables = array( 'messages', 'url' );

	// Redirect address and status
	protected $redirectTo = null;

	// Instance of MessagesFunctions
	protected $messagesFunctions = null;

	public static function clearCache() {
		self::$messageCache = array();
		self::$fallbackCache = null;
	}

	/**
	 * Initialize class
	 *
	 * Pass a string (domain) or array (options)
	 *
	 * Options:
	 *
	 * - lang
	 * - domain
	 * - globalfunctions
	 * - suppressfatal
	 * - suppressnotice
	 * - suppressbrackets
	 * - stayalive
	 * - param
	 */
	public function __construct( $options = array() ) {
		$this->localBaseDir = dirname( __DIR__ );

		if ( is_string( $options ) ) {
			$options = array( 'domain' => $options );
		}

		$defaultOptions = array(
			'domain' => 'general',
			'lang' => null,
			'globalfunctions' => true,
			'suppressfatal' => false,
			'suppressnotice' => true,
			'suppressbrackets' => false,
			'stayalive' => false,
			'param' => true,
			'mode' => 'in-tool',
		);
		$options = array_merge( $defaultOptions, $options );

		$this->mode = $options['mode'];

		// The domain of your tool can be set here.
		// Otherwise defaults to 'general'. See also documentation of msg()
		// First character is case-insensitive
		if ( isset( $options['domain'] ) ) {
			$this->setDomain( $options['domain'] );
		}

		// Allow a tool to disable the loading of global functions,
		// in case they have a _() and/or _e() already.
		if ( $options['globalfunctions'] === true ) {
			require_once $this->localBaseDir . '/includes/Functions.php';
		}

		// Allow a tool to suppress fatals, which hide php fatal errors.
		$this->suppressfatal = $options['suppressfatal'];

		// Allow a tool to suppress notices, which hide php notices.
		$this->suppressnotice = $options['suppressnotice'];

		// Allow a tool to suppress brackets, msg() will return "Messagekey" instead of "[messagekey]"
		// if this is true.
		$this->suppressbrackets = $options['suppressbrackets'];

		// Allow a tool to prevent exiting/dieing on fatal errors.
		$this->stayalive = $options['stayalive'];

		// Choose language based on a cookie. However it can be manually overriden for permalinks
		// through a request parameter. By default this is 'userlang'. If you need this parameter
		// for something else you can disable this system here. To avoid inconsistencies between
		// tools a custom parameter name will not be supported. It's either on or off.
		$this->setUseRequestParam( $options['param'] );

		// A tool may override the automatic initiation with cookies and paramters
		// (ie. during development). Note you can also override it for individual msg calls,
		// by passing the language code as third argument to msg().
		// If options['lang'] is a non-empty string, initLangSelect will use it,
		// instead of it's own routine.

		// Initialize language choise
		$this->initLangSelect( $options['lang'] );

		$this->initHook( $this );
	}

	public function initHook( Intuition $intuition ) {
		// Version date (default to this file modification time)
		// Can be overwritten in LocalConfig (ie. from svn info)

		if ( function_exists( 'intuitionHookInit' ) ) {
			intuitionHookInit( $intuition );
		} elseif ( function_exists( 'TsIntuition_inithook' ) ) {
			TsIntuition_inithook( $intuition );
		}
	}

	/* Get/Set variables
	 * ------------------------------------------------- */

	public function getLang() {
		return $this->currentLanguage;
	}

	/**
	 * Set the current language which will be used when requesting messages etc.
	 * @param string $lang Language code. If not a valid string, the setting
	 *  will remain unchanged and false is returned.
	 * @return bool
	 */
	public function setLang( $lang ) {
		if ( IntuitionUtil::nonEmptyStr( $lang ) ) {
			$this->currentLanguage = $this->normalizeLang( $lang );
			return true;
		}
		return false;
	}

	/**
	 * Get an array of common locale values for setlocale().
	 * @param string $lang [optional] Pass a language code. Defaults to current language.
	 * @return array
	 */
	public function getLocale( $lang = null, $utf8 = true ) {
		$suffixes = $utf8 ? array( '.UTF-8', '.UTF-8', '.utf8' ) : array( '' );
		$normal = isset( $lang ) ? $lang : $this->getLang();
		$normalUC = strtoupper( $normal );

		// Get 'foo' from 'foo-bar'
		$parts = explode( '-', $normal );
		$short = $parts[0];
		$shortUC = strtoupper( $short );

		$versions = array(
			// foo-br or en
			$normal,
			// FOO-BR or EN
			$normalUC,
			// foo_FOO or en_EN
			$short . '_' . $shortUC,
			// foo or en
			$short,
			// FOO or EN
			$shortUC,
		);
		$return = array();
		foreach ( $versions as $version ) {
			foreach ( $suffixes as $suffix ) {
				$return[] = $version . $suffix;
			}
		}
		return array_values( array_unique( $return ) );
	}

	/**
	 * Return the currently selected text domain.
	 * @return string
	 */
	public function getDomain() {
		return $this->currentDomain;
	}

	/**
	 * Set the current domain which will be used when requesting messages etc.
	 * @return true
	 */
	public function setDomain( $domain ) {
		$this->currentDomain = $this->normalizeDomain( $domain );
		return true;
	}

	/**
	 * Cookie names may change over time, don't depend on them.
	 * Each cookie-name has an alias (eg. 'userlang' instead of 'pref_userlang')
	 * Use getCookieName() if you only need a single value.
	 *
	 * @return array An array of aliases as keys and actual cookienames as values
	 */
	public function getCookieNames() {
		return $this->cookieNames;
	}

	public function getCookieName( $name ) {
		return $this->cookieNames[$name];
	}

	/**
	 * Parameter names may change over time, don't depend on them.
	 * Each paramter-name has an alias (so far the same as the actual value)
	 * Use getParamName() if you only need a single value.
	 *
	 * @return array An array of aliases as keys and actual parameter names as values.
	 */
	public function getParamNames() {
		return $this->paramNames;
	}

	public function getParamName( $name ) {
		return $this->paramNames[$name];
	}

	/**
	 * Whether or not the userlang-parameter is used to determine the
	 * userlanguage during initialization.
	 *
	 * @return bool
	 */
	public function getUseRequestParam() {
		return $this->useRequestParam;
	}

	/**
	 * Overwrite the setting to use or ignore the userlang-parameter.
	 * Note that it's likely the language intitialization/detection has already
	 * been ran. Call refreshLang() if you want it to re-check the cookies,
	 * parameters, overwrites etc.
	 *
	 * @param bool $bool True if you want it to use the parameter, false otherwise.
	 * @return bool False if the passed argument wasn't a boolean, true otherwise.
	 */
	public function setUseRequestParam( $bool ) {
		if ( is_bool( $bool ) ) {
			$this->useRequestParam = $bool;
			return true;
		}
		return false;
	}

	/**
	 * Get an instance of MessagesFunctions.
	 * @return object Instance of MessagesFunction
	 */
	protected function getMessagesFunctions() {
		if ( $this->messagesFunctions == null ) {
			require_once $this->localBaseDir . '/language/MessagesFunctions.php';
			$this->messagesFunctions = MessagesFunctions::getInstance( $this->localBaseDir, $this );
		}
		return $this->messagesFunctions;
	}

	/* Message functions
	 * ------------------------------------------------- */

	protected function normalizeDomain( $domain ) {
		return strtolower( $domain );
	}

	protected function normalizeLang( $lang ) {
		$lang = strtolower( str_replace( '_', '-', $lang ) );
		if ( isset( $this->deprecatedLangCodes[$lang] ) ) {
			return $this->deprecatedLangCodes[$lang];
		}
		return $lang;
	}

	/**
	 * Get a message from the message blob.
	 *
	 * @param string $key Message key to retrieve a message for.
	 * @param string|array $options [optional] A domain name or an array with one or more
	 *  of the following options:
	 *  - domain: overrides the currently selected domain, and if needed loads it from disk
	 *  - lang: overrides the currently selected language
	 *  - variables: numerical array to do variable replacements ($1> var[0], $2> var[1], etc.)
	 *  - raw-variables: boolean to determine whether the variables should be escaped as well
	 *  - parsemag: boolean to determine whether the message sould be tranformed
	 *              using magic phrases (PLURAL, etc.)
	 *  - escape: Optionally the return can be escaped. By default this takes place after variable
	 *            replacement. Set 'raw-variables' to true if you just want the raw message
	 *            to be escaped and have escaped the variables already.
	 *  - * 'plain'
	 *  - * 'html' (<>"& escaped)
	 *  - * 'htmlspecialchars' (alias of 'html')
	 *  - * 'htmlentities' (foreign/UTF-8 chars converted as well)
	 *
	 * @param mixed $fail [optional] Value if the message doesn't exist. Defaults to null.
	 */
	public function msg( $key = 0, $options = array(), $fail = null ) {
		if ( !IntuitionUtil::nonEmptyStr( $key ) ) {
			// Invalid message key
			return $this->bracketMsg( $key, $fail );
		}

		$defaultOptions = array(
			'domain' => $this->getDomain(),
			'lang' => $this->getLang(),
			'variables' => array(),
			'raw-variables' => false,
			'escape' => 'plain',
			'parsemag' => true,
			'externallinks' => false,
			// Set to a wiki article path for converting
			'wikilinks' => false,
		);

		// If $options was a domain string, convert it now.
		if ( IntuitionUtil::nonEmptyStr( $options ) ) {
			$options = array( 'domain' => $options );
		}

		// If $options is still not an array, ignore it and use default
		// Otherwise merge the options with the defaults.
		if ( !is_array( $options ) ) {
			$options = $defaultOptions;
		} else {
			$options = array_merge( $defaultOptions, $options );
		}

		// Normalise key. First character is case-insensitive.
		$key = lcfirst( $key );

		$msg = $this->rawMsg( $options['domain'], $options['lang'], $key );
		if ( $msg === null ) {
			$this->errTrigger(
				"Message \"$key\" for lang \"{$options['lang']}\" in domain \"{$options['domain']}\" not found",
				__METHOD__,
				E_NOTICE
			);
			return $this->bracketMsg( $key, $fail );
		}

		// Now that we've got the message, apply any post processing
		$escapeDone = false;

		// If using raw variables, escape message before replacement
		if ( $options['raw-variables'] === true ) {
			$msg = IntuitionUtil::strEscape( $msg, $options['escape'] );
			$escapeDone = true;
		}

		// Replace variables
		foreach ( $options['variables'] as $i => $val ) {
			$n = $i + 1;
			$msg = str_replace( "\$$n", $val, $msg );
		}

		if ( $options['parsemag'] === true ) {
			$msg = $this->getMessagesFunctions()->parse( $msg, $options['lang'] );
		}

		// If not using raw vars, escape the message now (after variable replacement).
		if ( !$escapeDone ) {
			$escapeDone = true;
			$msg = IntuitionUtil::strEscape( $msg, $options['escape'] );
		}

		if ( is_string( $options['wikilinks'] ) ) {
			$msg = IntuitionUtil::parseWikiLinks( $msg, $options['wikilinks'] );
		}

		if ( $options['externallinks'] ) {
			$msg = IntuitionUtil::parseExternalLinks( $msg );
		}

		return $msg;
	}

	/**
	 * Get a raw message. (Handles language fallback.)
	 *
	 * @param string $domain
	 * @param string $lang
	 * @param string $key
	 * @return string|null
	 */
	public function rawMsg( $domain, $lang, $key ) {
		$domain = $this->normalizeDomain( $domain );
		$lang = $this->normalizeLang( $lang );

		$lang = $this->getLangForMsg( $domain, $lang, $key );
		return $this->accessBlob( $domain, $lang, $key );
	}

	/**
	 * Access message blob directly. (Does not handle fallback.)
	 *
	 * @param string $domain
	 * @param string $lang
	 * @param string $key
	 * @return string|null
	 */
	protected function accessBlob( $domain, $lang, $key ) {
		if ( !isset( $this->messageBlob[$domain][$lang][$key] ) ) {
			return null;
		}
		return $this->messageBlob[$domain][$lang][$key];
	}

	/**
	 * Don't show [brackets] when suppressing errors.
	 * In that case there could be message files missing and invalid language codes chosen.
	 * Just return a somewhat readable string.
	 * We use square brackets for simplicity sake, using inequality brackets (< >) may cause
	 * conflicts with HTML when used wrong.
	 *
	 * @param string $key Name of the key to be used
	 * @param mixed $fail [optional] Custom failure return
	 */
	public function bracketMsg( $key, $fail = null ) {
		if ( $fail !== null ) {
			return $fail;
		}
		if ( $this->suppressbrackets ) {
			// Keyname
			return ucfirst( $key );
		}
		// [keyname]
		return "[$key]";
	}

	/**
	 * Check is a message exists at all.
	 * If this returns false it means msg() would return "[message-key]"
	 * Parameters the same as msg(), except $fail which is overwritten.
	 *
	 * @return bool
	 */
	public function msgExists( $key = 0, $options = array() ) {
		// Use the $fail option of msg()
		if ( $this->msg( $key, $options, /* $fail = */ false ) === false ) {
			return false;
		}
		return true;
	}

	/**
	 * Add or overwrites a message in the blob.
	 *
	 * This function is public so tools can use it while testing their tools
	 * and don't need a message to exist in translatewiki.net yet, but don't want to see [msgkey]
	 * either. See also addMsgs() for registering multiple messages.
	 *
	 * @param string $key
	 * @param string $message
	 * @param string $domain [optional] Defaults to current domain
	 * @param string $lang [optional] Defaults to current language
	 */
	public function setMsg( $key, $message, $domain = null, $lang = null ) {
		$domain = IntuitionUtil::nonEmptyStr( $domain )
			? $this->normalizeDomain( $domain )
			: $this->getDomain();
		$lang = IntuitionUtil::nonEmptyStr( $lang )
			? $this->normalizeLang( $lang )
			: $this->getLang();

		$this->messageBlob[$domain][$lang][$key] = $message;
	}

	/**
	 * Set multiple messages in the blob.
	 *
	 * @param string $messagesByKey
	 * @param string $domain [optional] Defaults to current domain
	 * @param string $lang [optional] Defaults to current language
	 */
	public function setMsgs( $messagesByKey, $domain = null, $lang = null ) {
		foreach ( $messagesByKey as $key => $message ) {
			$this->setMsg( $key, $message, $domain, $lang );
		}
	}

	/**
	 * Register a custom domain.
	 *
	 * @param string $domain Name of domain
	 * @param array $dir Path to messages directory
	 * @param array $info [optional] Domain info
	 * @return array
	 */
	public function registerDomain( $domain, $dir, $info = array() ) {
		$info['dir'] = $dir;
		$this->domainInfos[ $this->normalizeDomain( $domain ) ] = $info;
	}

	/**
	 * Get all known message keys for a domain.
	 *
	 * If the domain is not loaded, this returns an empty list.
	 * This assumes "en" is the source language containing all keys.
	 *
	 * @param string $domain
	 * @return array
	 */
	public function listMsgs( $domain ) {
		$domain = $this->normalizeDomain( $domain );
		$this->ensureLoaded( $domain, 'en' );
		// Ignore load failure to allow listing of messages that
		// were manually registered (in case there are any).
		if ( !isset( $this->messageBlob[$domain]['en'] ) ) {
			return array();
		}
		return array_keys( $this->messageBlob[$domain]['en'] );
	}

	/* Lang functions
	 * ------------------------------------------------- */

	/**
	 * Get fallback chain for a given language.
	 *
	 * @param string $lang Language code
	 * @return string[] List of one or more language codes
	 */
	public function getLangFallbacks( $lang ) {
		if ( self::$fallbackCache === null ) {
			// Lazy-initialize
			self::$fallbackCache = $this->fetchLangFallbacks();
		}

		$lang = $this->normalizeLang( $lang );
		return isset( self::$fallbackCache[$lang] ) ? self::$fallbackCache[$lang] : array( 'en' );
	}

	/**
	 * @return array
	 */
	protected function fetchLangFallbacks() {
		$file = $this->localBaseDir . '/language/fallbacks.json';
		if ( !is_file( $file ) || !is_readable( $file )  ) {
			$this->errTrigger( 'Unable to open fallbacks.json', __METHOD__, E_NOTICE, __FILE__, __LINE__ );
			return array();
		}

		$fallbacks = json_decode( file_get_contents( $file ), true );
		if ( !$fallbacks ) {
			$this->errTrigger( 'Unable to parse fallbacks.json', __METHOD__, E_NOTICE, __FILE__, __LINE__ );
			return array();
		}

		foreach ( $fallbacks as &$fallback ) {
			// Expand string values to arrays
			$fallback = (array)$fallback;
			// Add English to the end of the fallback chain
			$fallback[] = 'en';
		}

		return $fallbacks;
	}

	/**
	 * Return the language name in the native language.
	 * @param string $lang [optional] Language code
	 * @return string
	 */
	public function getLangName( $lang = false ) {
		$lang = $lang ? $this->normalizeLang( $lang ) : $this->getLang();
		$langNames = $this->getLangNames();
		return isset( $langNames[$lang] ) ? $langNames[$lang] : '';
	}

	/**
	 * Return all known languages.
	 * @return array
	 */
	protected function getLangNames() {
		// Lazy-load and cache
		if ( $this->langNames === null ) {
			$path = $this->localBaseDir . '/language/mw-classes/Names.php';
			if ( !is_file( $path ) || !is_readable( $path ) ) {
				$this->errTrigger( 'Names.php is missing', __METHOD__, E_NOTICE, __FILE__, __LINE__ );
				$this->langNames = array();
				return array();
			}

			// Load it
			$coreLanguageNames = array();
			include $path;
			$this->langNames = $coreLanguageNames;
		}

		return $this->langNames;
	}

	/**
	 * Return all languages available in at least one domain.
	 * @return array Language names keyed by language code
	 */
	public function getAvailableLangs() {
		if ( !$this->availableLanguages ) {
			$this->availableLanguages = require $this->localBaseDir . '/language/langlist.php';
		}
		return $this->availableLanguages;
	}

	/**
	 * Generate a list of all languages available in at least one domain.
	 * @return array Language names keyed by language code
	 */
	public function generateLanguageList() {
		$messageFiles = glob( $this->localBaseDir . '/language/messages/*/*.json' );

		$languages = array_map(
			function ( $filename ) {
				return basename( $filename, '.json' );
			},
			$messageFiles
		);
		$languages = array_values( array_unique( $languages ) );

		$availableLanguages = array();
		foreach ( $languages as $lang ) {
			$availableLanguages[$lang] = $this->getLangName( $lang );
		}
		ksort( $availableLanguages );

		return $availableLanguages;
	}

	/* Domain functions
	 * ------------------------------------------------- */

	/**
	 * Get the language that can be used for a message in a domain.
	 *
	 * If possible, returns the preferred lang right away, otherwise it looks
	 * for a suitable falback
	 *
	 * @param string $domain Normalised domain
	 * @param string $lang Normalised language code of preferred language
	 * @param string $key Key of message
	 * @return string
	 */
	protected function getLangForMsg( $domain, $lang, $key ) {
		$this->ensureLoaded( $domain, $lang );
		if ( isset( $this->messageBlob[$domain][$lang][$key] ) ) {
			return $lang;
		}

		// Check fallbacks
		$fallbacks = $this->getLangFallbacks( $lang );
		foreach ( $fallbacks as $fallbackLang ) {
			$this->ensureLoaded( $domain, $fallbackLang );
			if ( isset( $this->messageBlob[$domain][$fallbackLang][$key] ) ) {
				return $fallbackLang;
			}
		}

		return 'en';
	}

	/**
	 * Ensure a domain's language is loaded.
	 *
	 * @param string $domain Name of the domain
	 * @param string $lang Language code
	 * @return bool
	 */
	protected function ensureLoaded( $domain, $lang ) {
		if ( isset( $this->loadedDomains[ $domain ][ $lang ] ) ) {
			// Already tried
			return $this->loadedDomains[ $domain ][ $lang ];
		}

		// Validate input and protect against path traversal
		if ( !IntuitionUtil::nonEmptyStrs( $domain, $lang ) ||
			strcspn( $domain, ":/\\\000" ) !== strlen( $domain ) ||
			strcspn( $lang, ":/\\\000" ) !== strlen( $lang )
		) {
			$this->errTrigger( 'Illegal domain or lang', __METHOD__, E_NOTICE );
			return false;
		}

		$this->loadedDomains[ $domain ][ $lang ] = false;

		if ( !isset( self::$messageCache[ $domain ][ $lang ] ) ) {
			$domainInfo = $this->getDomainInfo( $domain );
			if ( !isset( $domainInfo['dir'] ) ) {
				return false;
			}
			$file = $domainInfo['dir'] . "/$lang.json";
			$loaded = $this->loadMessageFile( $domain, $lang, $file );
			if ( !$loaded ) {
				return false;
			}
		} else {
			$this->setMsgs( self::$messageCache[ $domain ][ $lang ], $domain, $lang );
		}

		$this->loadedDomains[ $domain ][ $lang ] = true;
		return true;
	}

	/**
	 * @param string $domain Normalised domain
	 * @param string $lang Normalised language code
	 * @param string $filePath
	 * @return bool
	 */
	public function loadMessageFile( $domain, $lang, $file ) {
		if ( !is_file( $file ) || !is_readable( $file ) ) {
			$this->errTrigger( "Unable to open message file \"$file\"",
				__METHOD__, E_NOTICE, __FILE__, __LINE__ );
			return false;
		}

		$messages = json_decode( file_get_contents( $file ), /* assoc = */ true );
		if ( !is_array( $messages ) ) {
			return false;
		}
		unset( $messages['@metadata'] );

		self::$messageCache[ $domain ][ $lang ] = $messages;
		$this->setMsgs( $messages, $domain, $lang );
		return true;
	}

	/**
	 * Get information about a domain (if any).
	 *
	 * @param string $domain Name of the domain
	 * @return array|bool
	 */
	public function getDomainInfo( $domain ) {
		$domain = $this->normalizeDomain( $domain );
		// Check cache and custom-registered domains
		if ( !isset( $this->domainInfos[ $domain ] ) ) {
			// Default to local domain
			$dir = $this->localBaseDir . '/language/messages/' . $domain;
			if ( !is_dir( $dir ) ) {
				// Domain does not exist
				return false;
			}
			$this->domainInfos[ $domain ] = array(
				'dir' => $dir,
			);
		}
		return $this->domainInfos[ $domain ];
	}

	/**
	 * @param string $domain
	 * @return bool
	 */
	protected function isLocalDomain( $domain ) {
		$domain = $this->normalizeDomain( $domain );
		return is_dir( $this->localBaseDir . '/language/messages/' . $domain );
	}

	/* Cookie functions
	 * ------------------------------------------------- */

	/**
	 * Set a cookie.
	 *
	 * @param $name string Canonical name of the cookie.
	 * @param $val string Value to be set.
	 * @param $lifetime int Lifetime in seconds from now (defaults to 30 days)
	 *
	 * @return bool
	 */
	public function setCookie( $key, $val, $lifetime = 2592000, $track = TSINT_COOKIE_TRACK ) {
			// Validate cookie name
			$name = $this->getCookieName( $key );
			if ( !$name ) {
				return false;
			}

			$val = strval( $val );
			$lifetime = intval( $lifetime );
			$expire = time() + $lifetime;

			// Set a 30-day domain-wide cookie
			setcookie( $name, $val, $expire, '/' );

			// In order to keep track of the expiration date, we set another cookie
			if ( $track === TSINT_COOKIE_TRACK ) {
				$this->setExpiryTrackerCookie( $lifetime );
			}

			return true;
	}

	/**
	 * Browsers don't send the expiration date of cookies with the request
	 * In order to keep track of the expiration date, we set an additional cookie.
	 */
	protected function setExpiryTrackerCookie( $lifetime ) {
		$val = time() + $lifetime;
		$this->setCookie( 'track-expire', $val, $lifetime, TSINT_COOKIE_NOTRACK );
		return true;
	}

	/**
	 * Renew all cookies
	 */
	public function renewCookies( $lifetime = 2592000 /* 30 days */ ) {
		foreach ( $this->getCookieNames() as $key => $name ) {
			if ( $key === 'track-expire' ) {
				continue;
			}
			$this->setCookie( $key, $_COOKIE[$name], $lifetime, TSINT_COOKIE_NOTRACK );
		}
		$this->setExpiryTrackerCookie( $lifetime );
		return true;
	}

	/**
	 * Delete all cookies.
	 * It's recommended to redirectTo() directly after this.
	 * @return bool
	 */
	public function wipeCookies() {
		foreach ( $this->getCookieNames() as $key => $name ) {
			$this->setCookie( $key, '', -3600, TSINT_COOKIE_NOTRACK );
			unset( $_COOKIE[$name] );
		}
		return true;
	}

	/**
	 * Get expiration timestamp.
	 * @return int Unix timestamp of expiration date or 0 if not available.
	 */
	public function getCookieExpiration() {
		$name = $this->getCookieName( 'track-expire' );
		$expire = isset( $_COOKIE[$name] ) ? intval( $_COOKIE[$name] ) : 0;
		return $expire;
	}

	/**
	 * Get expected lifetime left in seconds.
	 * Returns 0 if expired or unavailable.
	 * @return int
	 */
	public function getCookieLifetime() {
		$expire = $this->getCookieExpiration();
		$lifetime = $expire - time();
		// If already expired (-xxx), return 0
		return $lifetime < 0 ? 0 : $lifetime;
	}

	public function hasCookies() {
		return isset( $_COOKIE[ $this->getCookieName( 'userlang' ) ] );
	}

	/* Magic words
	 * ------------------------------------------------- */

	/**
	 * @todo FIXME: Implement in language/MessagesFunctions.php.
	 * @codeCoverageIgnore
	 */
	public function gender( $male, $female, $neutral ) {
		// Depends on getGender() which doesn't exist yet
		throw new BadMethodCallException( 'Not supported yet!' );
	}

	/**
	 * Can be founded in language/MessagesFunctions.php.
	 *
	 * @see MessagesFunctions::parse
	 * @see MessagesFunctions::plural
	 * @deprecated
	 * @codeCoverageIgnore
	 */
	public function plural( $count, $forms ) {
		throw new BadMethodCallException(
			"Use msg() with \"parse\" option to support PLURAL!"
		);
	}

	/* Output promo and dashboard backlinks
	 * ------------------------------------------------- */

	/**
	 * Show a link that explains that this tool has been localized via Intuition and that they
	 * can change the language by setting their preference in the dashboard.
	 * Or (if they've done so already) that they can manage their settings there
	 */
	public function dashboardBacklink() {
		if ( $this->hasCookies() ) {
			$text = $this->msg( 'bl-mysettings', 'tsintuition' );
		} else {
			$text = $this->msg( 'bl-mysettings-new', 'tsintuition' );
		}
		return IntuitionUtil::tag(
			$text,
			'a',
			array(
				'class' => 'int-dashboardbacklink',
				'href' => $this->getDashboardReturnToUrl(),
				'title' => $this->msg( 'bl-changelanguage', 'tsintuition' ),
			)
		);
	}

	/**
	 * Show a promobox on the bottom of your tool.
	 *
	 * @param int $imgSize [optional] Defaults to 28px.
	 *  Set to 0 to omit the image from the promobox.
	 * @param bool|string|null $helpTranslateDomain [optional] Provide a link to translatewiki.net
	 *  where this tool can be translated.
	 * - TSINT_HELP_CURRENT (null): Link to translations for the current domain.
	 * - TSINT_HELP_ALL (true): Link to translations for all domains.
	 * - TSINT_HELP_NONE (false): Disable this link.
	 * - string value: Link to translations for the specified domain.
	 * @return string The HTML for the promo box.
	 */
	public function getPromoBox( $imgSize = 28, $helpTranslateDomain = TSINT_HELP_CURRENT ) {
		// Logo
		if ( is_int( $imgSize ) && $imgSize > 0 ) {
			$src = '//upload.wikimedia.org/wikipedia/commons/thumb/a/a4/Tool_labs_logo.svg/'
				. '/' . $imgSize . 'px-Tool_labs_logo.svg.png';
			$src_2x = '//upload.wikimedia.org/wikipedia/commons/thumb/a/a4/Tool_labs_logo.svg/'
				. '/' . ( $imgSize * 2 ) . 'px-Tool_labs_logo.svg.png';
			$img = IntuitionUtil::tag( '', 'img', array(
				'src' => $src,
				'srcset' => "$src 1x, $src_2x 2x",
				'width' => $imgSize,
				'height' => $imgSize,
				'alt' => '',
				'title' => '',
				'class' => 'int-logo',
			) );
		} else {
			$img = '';
		}

		// Promo message
		$promoMsgOpts = array(
			'domain' => 'tsintuition',
			'escape' => 'html',
			'raw-variables' => true,
			'variables' => array(
				'<a href="//translatewiki.net/">translatewiki.net</a>',
				'<a href="' . $this->dashboardHome . '">Intuition</a>'
			),
		);
		$poweredHtml = $this->msg( 'bl-promo', $promoMsgOpts );

		// "Help translation" link
		$translateGroup = null;
		if ( $helpTranslateDomain === TSINT_HELP_ALL ) {
			$translateGroup = 'tsint-0-all';
			$twLinkText = $this->msg( 'help-translate-all', 'tsintuition' );

		} elseif ( $helpTranslateDomain === TSINT_HELP_CURRENT ) {
			$domain = $this->getDomain();
			$translateGroup = $this->isLocalDomain( $domain ) ? "tsint-{$domain}" : "int-{$domain}";
			$twLinkText = $this->msg( 'help-translate-tool', 'tsintuition' );

		} elseif ( $helpTranslateDomain !== TSINT_HELP_NONE ) {
			// Custom domain
			$domain = $this->normalizeDomain( $helpTranslateDomain );
			$translateGroup = $this->isLocalDomain( $domain ) ? "tsint-{$domain}" : "int-{$domain}";
			$twLinkText = $this->msg( 'help-translate-tool', 'tsintuition' );
		}

		$helpTranslateLink = '';

		if ( $translateGroup ) {
			// https://translatewiki.net/w/i.php?language=nl&title=Special:Translate&group=tsint-0-all
			$twParams = array(
				'title' => 'Special:Translate',
				'language' => $this->getLang(),
				'group' => $translateGroup,
			);
			$twParams = http_build_query( $twParams );
			$helpTranslateLink = '<small>(' . IntuitionUtil::tag( $twLinkText, 'a', array(
				'href' => "https://translatewiki.net/w/i.php?$twParams",
				'title' => $this->msg( 'help-translate-tooltip', 'tsintuition' )
			) ) . ')</small>';
		}

		// Build output
		return
			'<div class="int-promobox"><p><a href="' .
			htmlspecialchars( $this->getDashboardReturnToUrl() )
			. "\">$img</a> "
			. "$poweredHtml {$this->dashboardBacklink()} $helpTranslateLink</p></div>";
	}

	/**
	 * Show a typical "powered by .." footer line.
	 * Same as getPromoBox() but without the image.
	 * @return string HTML
	 */
	public function getFooterLine( $helpTranslateDomain = TSINT_HELP_CURRENT ) {
		return $this->getPromoBox( 'no-image', $helpTranslateDomain );
	}

	/**
	 * Build a permalink to the dashboard with a returnto query
	 * to return to the current page. To be used in other tools.
	 *
	 * @example:
	 *  Location: //tools.wmflabs.org/example/JohnDoe.php?wiki=loremwiki_p
	 *  HTML:
	 *  '<p>Change the settings <a href="' . $I18N->getDashboardReturnToUrl() . '">here</a>';
	 *
	 * @return string URL
	 */
	public function getDashboardReturnToUrl() {
		$p = array(
			'returnto' => $_SERVER['SCRIPT_NAME'],
			'returntoquery' => http_build_query( $_GET ),
		);
		$p = http_build_query( $p );
		return "{$this->dashboardHome}?$p#tab-settingsform";
	}

	/* Redirect functions
	 * ------------------------------------------------- */

	/**
	 * Redirect or refresh to url. Pass null to undo redirection.
	 *
	 * @param string $url
	 * @param int $code [optional] Defaults to 302
	 * @return bool false on failure.
	 */
	public function redirectTo( $url = 0, $code = 302 ) {
		if ( $url === null ) {
			$this->redirectTo = null;
			return true;
		}
		if ( !is_string( $url ) || !is_int( $code ) ) {
			return false;
		}
		$this->redirectTo = array( $url, $code );
		return true;
	}

	public function doRedirect() {
		if ( !is_array( $this->redirectTo ) ) {
			return false;
		}
		header( 'Content-Type: text/html; charset=utf-8' );
		header( 'Location: ' . $this->redirectTo[0], true, $this->redirectTo[1] );
		exit;
	}

	public function isRedirecting() {
		return is_array( $this->redirectTo );
	}

	/* Other functions
	 * ------------------------------------------------- */

	public function parentheses( /* $this->msg( [arguments] ) */ ) {
		$msg = call_user_func_array(
			array( $this, 'msg' ),
			func_get_args()
		);
		return $this->parensWrap( $msg );
	}

	/**
	 * @param string $content Text or HTML to be wrapped in parentheses.
	 * @param string $escape Any valid format for IntuitionUtil::strEscape.
	 */
	public function parensWrap( $content, $escape = 'plain' ) {
		return $this->msg(
			'parentheses',
			array(
				'domain' => 'general',
				'raw-variables' => true,
				'variables' => array( IntuitionUtil::strEscape( $content, $escape ) ),
			)
		);
	}

	/**
	 * Get a localized date. Pass a format, time or both.
	 * Defaults to the current timestamp in the language's default date format.
	 *
	 * @param string $format Date format compatible with strftime()
	 * @param mixed $timestamp Timestamp (seconds since unix epoch) or string (ie. "2011-12-31")
	 * @param string $lang Language code. Defaults to current langauge (through getLocale() )
	 * @return string
	 */
	public function dateFormatted( $first = null, $second = null, $lang = null ) {
		// One argument or less
		if ( $second === null ) {
			// No arguments
			if ( $first === null ) {
				$format = $this->msg( 'dateformat', 'general' );
				$timestamp = time();

			// Timestamp only
			} elseif ( is_int( $first ) ) {
				$format = $this->msg( 'dateformat', 'general' );
				$timestamp = $first;

			// Date string only
			} elseif ( strtotime( $first ) ) {
				$format = $this->msg( 'dateformat', 'general' );
				$timestamp = strtotime( $first );

			// Format only
			} else {
				$format = $first;
				$timestamp = time();
			}

		// Two arguments
		} else {
			$format = $first;
			$timestamp = is_int( $second ) ? $second : strtotime( $second );
		}

		// Save current setlocale
		$saved = setlocale( LC_ALL, 0 );

		// Overwrite for current language
		setlocale( LC_ALL, $this->getLocale( $lang ) );
		$return = strftime( $format, $timestamp );

		// Reset back to what it was
		setlocale( LC_ALL, $saved );

		return $return;

	}

	/**
	 * Check language choice tree in the following order:
	 * - First: Construct override
	 * - Second: Parameter override
	 * - Third: Saved cookie
	 * - Fourth: Preferences from Accept-Language header
	 * - Fifth: A language which is a prefix for one of the
	 * Accept-Language preferences.
	 * - Sixth: English (default stays)
	 *
	 * @return true
	 */
	protected function initLangSelect( $option ) {
		$set = false;

		if ( isset( $option ) && !empty( $option ) ) {
			$set = $this->setLang( $option );
		}

		if ( !$set && $this->getUseRequestParam() === true &&
			isset( $_GET[ $this->paramNames['userlang'] ] )
		) {
			$set = $this->setLang( $_GET[ $this->paramNames['userlang'] ] );
		}

		if ( !$set && isset( $_COOKIE[ $this->cookieNames['userlang'] ] ) ) {
			$set = $this->setLang( $_COOKIE[ $this->cookieNames['userlang'] ] );
		}

		if ( !$set ) {
			$acceptableLanguages = IntuitionUtil::getAcceptableLanguages();
			$availableLanguages = $this->getAvailableLangs();
			foreach ( $acceptableLanguages as $acceptLang => $qVal ) {
				if ( $acceptLang === '*' ) {
					/**
					 * We pick the first available language which is not in $acceptableLanguages.
					 * The special * range matches every tag not matched by any other range.
					 * Other language codes in $acceptableLanguages will either have a lower q-value,
					 * or be missing from availableLanguages.
					 * The order will be the one in the i18n file: en, af, ar...
					 */
					foreach ( $availableLanguages as $langCode => $langName ) {
						if ( !isset( $acceptableLanguages[$langCode] ) ) {
							$n = strstr( $langCode, '-' );
							// Assumption: We won't have translations for languages with more than
							// 1 dashe on the language tag
							if ( $n !== false &&
								!isset( $acceptableLanguages[ substr( $langCode, 0, $n - 1 ) ] )
							) {
								// if we have non-Chinese translations, zh-hans should not be
								// picked for "fr,*;q=0.3,zh;q=0.1".
								continue;
							}

							$set = $this->setLang( $langCode );
							break;
						}
					}
					if ( $set ) {
						break;
					}
				} elseif ( isset( $availableLanguages[$acceptLang] ) ) {
					$set = $this->setLang( $acceptLang );
					break;
				}
			}
		}

		/* From this point on, we are choosing amongst languages with a $qVal of 0 */

		if ( !$set ) {
			/**
			 * Some browsers show (apparently by default) only a tag,
			 * such as "ru-RU", "fr-FR" or "es-mx".
			 * This is broken behavior! The browser should be providing
			 * appropriate guidance.
			 * Providing only a full tag is doing a disservice.
			 * See RFC 2616 section 1.4 - http://tools.ietf.org/html/rfc2616#page-105
			 */
			foreach ( $acceptableLanguages as $acceptLang => $qVal ) {
				if ( !$qVal ) {
					continue;
				}

				while ( ( $n = strstr( $acceptLang, '-' ) ) !== false ) {
					$acceptLang = substr( $acceptLang, 0, $n - 1 );

					if ( isset( $availableLanguages[$acceptLang] ) ) {
						$set = $this->setLang( $acceptLang );
						break 2;
					}
				}
			}
		}

		if ( !$set ) {
			$set = $this->setLang( 'en' );
		}

		return $set;
	}

	/**
	 * Take a list of strings and build a locale-friendly comma-separated
	 * list, using the local comma-separator message.
	 * The last two strings are chained with an "and".
	 *
	 * @param array $l
	 * @return string
	 */
	function listToText( $l ) {
		$s = '';
		$m = count( $l ) - 1;
		if ( $m == 1 ) {
			$s = $l[0] . $this->msg( 'and', array( 'domain' => 'general' ) ) .
				$this->msg( 'word-separator', array( 'domain' => 'general' ) ) .
				$l[1];
		} else {
			for ( $i = $m; $i >= 0; $i-- ) {
				if ( $i == $m ) {
					$s = $l[$i];
				} elseif ( $i == $m - 1 ) {
					$s = $l[$i] . $this->msg( 'and', array( 'domain' => 'general' ) ) .
						$this->msg( 'word-separator', array( 'domain' => 'general' ) ) .
						$s;
				} else {
					$s = $l[$i] .
						$this->msg( 'comma-separator', array( 'domain' => 'general' ) ) .
						$s;
				}
			}
		}
		return str_replace( '&#32;', ' ', $s );
	}

	/**
	 * Redo language init
	 *
	 * Use this when you've changed the cookies and don't want to refresh
	 * for it to be applied.
	 *
	 * @return true
	 */
	public function refreshLang() {
		$this->initLangSelect();
		return true;
	}

	protected function errMsg( $msg, $context ) {
		return "[$context] $msg";
	}

	// Custom version of trigger_error() that can be passed a custom filename and line number
	public function errTrigger( $msg, $context, $level = E_WARNING, $file = '', $line = '' ) {
		$die = false;
		$error = false;
		$notice = false;
		switch ( $level ) {
			// Fatal
			case E_ERROR:
			case E_CORE_ERROR:
			case E_COMPILE_ERROR:
			case E_USER_ERROR:
			case E_RECOVERABLE_ERROR:
				$code = 'Fatal error';
				$error = true;
				$die = true;
				break;

			// Warning
			case E_WARNING:
			case E_CORE_WARNING:
			case E_COMPILE_WARNING:
			case E_USER_WARNING:
				$code = 'Warning';
				$error = true;
				break;

			// Notice
			case E_NOTICE:
				$code = 'Notice';
				$notice = true;
				break;

			// Unknown
			default:
				$code = 'Unknown error';
		}

		if ( $error && $this->suppressfatal ) {
			return;
		}
		if ( $notice && $this->suppressnotice ) {
			return;
		}

		if ( PHP_SAPI === 'cli' ) {
			echo "$code: " . $this->errMsg( $msg, $context ) .
				( $file ? " in $file" : '' ) .
				( $line ? " on line $line" : '' ) .
				'.';
		} else {
			echo "<b>$code: </b>" . htmlspecialchars( $this->errMsg( $msg, $context ) ) .
				( $file ? " in <b>$file</b>" : '' ) .
				( $line ? " on line <b>$line</b>" : '' ) .
				'.<br/>';
		}

		if ( $die && !$this->stayalive ) {
			die;
		}
	}

	/**
	 * Whether the language is right-to-left
	 *
	 * @param string $lang Language code to get the property from,
	 *  current language if missing
	 * @return bool
	 */
	public function isRtl( $lang = null ) {
		static $rtlLanguages = null;

		$lang = $lang ? $this->normalizeLang( $lang ) : $this->getLang();

		if ( $rtlLanguages === null ) {
			$file = $this->localBaseDir . '/language/rtl.json';
			$rtlLanguages = json_decode( file_get_contents( $file ), true );
		}

		return in_array( $lang, $rtlLanguages );
	}

	/**
	 * Return the correct HTML 'dir' attribute value for this language.
	 * @return string
	 */
	public function getDir( $lang = null ) {
		return $this->isRtl( $lang ) ? 'rtl' : 'ltr';
	}

	/**
	 * Return the fallback language for a given language.
	 *
	 * @deprecated since 0.2.0: Use #getLangFallbacks instead
	 * @param string $lang Language code
	 * @return string Language code
	 * @codeCoverageIgnore
	 */
	public function getLangFallback( $lang ) {
		$fallbacks = $this->getLangFallbacks( $lang );
		return $fallbacks[0];
	}

	/**
	 * Get a list of registered text domains.
	 *
	 * @deprecated since 0.2.0: Obsolete. To get available languages, use getAvailableLangs()
	 * @return array
	 * @codeCoverageIgnore
	 */
	public function getAllRegisteredDomains() {
		return array();
	}

	/**
	 * @deprecated since 0.2.0: Use #ensureLoaded instead.
	 * @param string $domain
	 * @return bool|string Normalised domain name or boolean false
	 * @codeCoverageIgnore
	 */
	public function loadTextdomain( $domain ) {
		$domain = $this->normalizeDomain( $domain );
		if ( !$this->ensureLoaded( $domain, $this->getLang() ) ) {
			return false;
		}
		return $domain;
	}

	/**
	 * @deprecated since 0.2.0: Use #loadMessageFile instead.
	 * @param string $filePath
	 * @param string $domain
	 * @return bool
	 * @codeCoverageIgnore
	 */
	public function loadTextdomainFromFile( $filePath, $domain ) {
		return false;
	}
}
