<?php
/**
 * Common functions globally available
 *
 * @author Timo Tijhof, 2015
 * @license Public domain
 * @package toollabs-base
 * @since v0.1.0
 */

/**
 * Logging
 * -------------------------------------------------
 */

class kfLogSection {
	protected $name;
	protected $conf;
	/**
	 * Begin section for a function and return an object that ends the section
	 * when the object is destroyed. As long as the object is not specifically
	 * inked to other objects, it will automatically be destroyed when the function
	 * returns (without having to worry about early returns and exceptions).
	 */
	public function __construct( $name ) {
		global $kgConf;
		$kgConf->startLogSection( $name );
		$this->name = $name;
		$this->conf = $kgConf;
	}
	function __destruct() {
		$this->conf->endLogSection( $this->name );
	}
}

function kfLog( $msg ) {
	global $kgConf;
	return $kgConf->writeDebugLog( $msg );
}

/**
 * @param bool $echo One of KR_LOG_ECHO, KR_LOG_RETURN
 * @param int $mode One of KR_FLUSH_CLEARTEXT, KR_FLUSH_HTMLPRE
 */
function kfLogFlush( $echo = KR_LOG_ECHO, $mode = KR_FLUSH_HTMLPRE ) {
	global $kgConf;

	$output = $kgConf->getDebugLog();

	$kgConf->clearDebugLog();

	switch ( $mode ) {
		case KR_FLUSH_HTMLPRE:
			$output = '<pre>' . htmlspecialchars( $output ) . '</pre>';
			break;
		case KR_FLUSH_CLEARTEXT:
			// Nothing
			break;
		default:
			// Nothing

	}

	// Echo or return
	if ( $echo === KR_LOG_ECHO ) {
		echo $output;
		return true;
	} else {
		return $output;
	}
}

/**
 * String utilities
 * -------------------------------------------------
 */

function kfEscapeRE( $str ) {
	return preg_quote( $str, KR_REGEX_DELIMITER );
}

function kfStrLastReplace( $search, $replace, $subject ) {
	return substr_replace( $subject, $replace, strrpos( $subject, $search ), strlen( $search ) );
}

/**
 * Database
 * -------------------------------------------------
 */

function kfDbUsername() {
	global $kgConf;
	return $kgConf->getDbUsername();
}

function kfDbPassword() {
	global $kgConf;
	return $kgConf->getDbPassword();
}

/**
 * Cache
 * -------------------------------------------------
 */

function kfCacheKey() {
	$args = func_get_args();
	$key = 'kf:' . implode( ':', $args );
	return str_replace( ' ', '_', $key );
}

/**
 * HTML templates
 * -------------------------------------------------
 */

/**
 * @param string $text
 * @param string $type One of success, info, warning, or danger.
 * @return string Html
 */
function kfAlertText( $type, $text ) {
	$class = 'alert';
	$class .= $type ? ' alert-' . $type : ' alert-default';
	return Html::element( 'div', array( 'class' => $class ), $text );
}

function kfAlertHtml( $type, $html ) {
	$class = 'alert';
	$class .= $type ? ' alert-' . $type : ' alert-default';
	return Html::rawElement( 'div', array( 'class' => $class ), $html );
}

function kfGetAllWikiOptionHtml( $options = array() ) {
	new kfLogSection( __FUNCTION__ );

	// Options
	$defaultOptions = array(
		'group' => true,
		'current' => null,
		'exclude' => array(),
	);
	$options = $options + $defaultOptions;

	$wikiInfos = LabsDB::getAllWikiInfos();
	$optionsHtml = '';
	$optionsHtmlGroups = array();
	foreach ( $wikiInfos as $wikiInfo ) {
		if ( in_array( $wikiInfo['dbname'], $options['exclude'] ) ) {
			continue;
		}
		$hostname = parse_url( $wikiInfo['url'], PHP_URL_HOST );
		if ( !$hostname ) {
			kfLog( "Unable to parse hostname of {$wikiInfo['dbname']}: '{$wikiInfo['url']}'" );
			continue;
		}
		$optionHtml = Html::element( 'option', array(
			'value' => $wikiInfo['dbname'],
			'selected' => $wikiInfo['dbname'] === $options['current'],
			'data-url' => $hostname
		), $hostname );
		if ( $options['group'] ) {
			if ( !isset( $optionsHtmlGroups[ $wikiInfo['family'] ] ) ) {
				$optionsHtmlGroups[ $wikiInfo['family'] ] = '';
			}
			$optionsHtmlGroups[ $wikiInfo['family'] ] .= $optionHtml;
		} else {
			$optionsHtml .= $optionHtml;
		}

	}

	if ( $options['group'] ) {
		foreach ( $optionsHtmlGroups as $family => $groupHtml ) {
			$optionsHtml .=
				Html::openElement( 'optgroup', array( 'label' => $family ) )
				. $groupHtml
				. '</optgroup>';

		}
	}

	return $optionsHtml;
}

/**
 * API Builder
 * -------------------------------------------------
 */

/**
 * Sanatize callback
 *
 * @param string $str
 * @return string
 */
function kfSanatizeJsCallback( $str ) {
	// Valid: foo.bar_Baz["quux"]['01']
	return preg_replace( "/[^a-zA-Z0-9_\.\]\[\'\"]/", '', $str );
}

/**
 * Build API response
 *
 * @param string $specialFormat If $format is set to this format this function will not output
 *  anything and return true. This can be used for a GUI front-end.
 */
function kfApiExport( $data = array( 'krApiExport' => 'Example' ), $format = 'dump', $callback = null, $specialFormat = null ) {

	if ( $specialFormat !== null && $format === $specialFormat ) {
		return true;
	}

	switch ( $format ) {
		case 'php':
			header( 'Content-Type: application/vnd.php.serialized; charset=utf-8', /* replace = */ true );
			echo serialize( $data );
			die;
			break;

		case 'json':
		case 'jsonp':

			// Serve as AJAX object object or JSONP callback
			if ( $callback === null ) {
				header( 'Content-Type: application/json; charset=utf-8', /* replace = */ true );
				echo json_encode( $data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES );
				die;
			} else {
				header( 'Content-Type: text/javascript; charset=utf-8', /* replace = */ true );

				// Sanatize callback
				$callback = kfSanatizeJsCallback( $callback );
				echo $callback . '(' . json_encode( $data ) .')';
				die;
			}
			break;

		case 'dump':

			// No text/plain due to IE7 mime-type sniff bug causing html parsing
			header( 'Content-Type: text/text; charset=utf-8', /* replace = */ true );
			var_dump( $data );
			die;
			break;

		default:
			// HTTP 400 Bad Request
			http_response_code( 400 );
			header( 'Content-Type: text/plain; charset=utf-8', /* replace = */ true );
			echo 'Invalid format.';
			exit;
	}

}

function kfApiFormats() {
	return array(
		'json' => array(
			'params' => array(
				'format' => 'json',
			),
			'label' => 'JSON'
		),
		'jsonp' => array(
			'params' => array(
				'format' => 'jsonp',
				'callback' => 'example',
			),
			'label' => 'JSON-P'
		),
		'php' => array(
			'params' => array(
				'format' => 'php',
			),
			'label' => 'Serialized PHP'
		),
		'dump' => array(
			'params' => array(
				'format' => 'dump',
			),
			'label' => 'Dump'
		),
	);
}

/**
 * Version control
 * -------------------------------------------------
 */

/**
 * @param array $options (optional):
 * - string dir: Full path to where the git repository is.
 *    By default it will assume the current directory is already the git repository.
 * - string checkout: Will be checked out and reset to its HEAD. Otherwise stays in
 *    the current branch and resets to its head.
 * - unlock: Whether or not it should ensure there is no lock.
 *
 * @return bool|string Boolean false on failure, or a string
 * with the output of the commands.
 */
function kfGitCleanReset( $options = array() ) {
	$orgPath = __DIR__;

	if ( isset( $options['dir'] ) ) {
		if ( !is_dir( $options['dir'] ) ) {
			return false;
		}

		// Navigate to the repo so we can execute the git commands
		chdir( $options['dir'] );
	}

	$out = '';
	$cmds = array();
	if ( isset( $options['unlock'] ) && $options['unlock'] ) {
		$cmds[] = 'rm -f .git/index.lock';
	}
	$cmds[] = 'git clean -q -d -x -f';
	$cmds[] = 'git reset -q --hard';
	if ( isset( $options['checkout'] ) ) {
		$cmds[] = 'git checkout -q -f ' . kfEscapeShellArg( $options['checkout'] );
	}

	foreach ( $cmds as $cmd ) {
		$out .= "$ $cmd\n";
		$out .= kfShellExec( $cmd ) . "\n";
	}

	// Go back to the original dir if we changed it
	if ( isset( $options['dir'] ) ) {
		chdir( $orgPath );
	}

	return $out;
}

/**
 * Shell
 * -------------------------------------------------
 */

function kfShellExec( $cmd ) {
	$retval = null;

	ob_start();
	passthru( $cmd, $retval );
	$output = ob_get_contents();
	ob_end_clean();

	if ( $retval != 0 ) {
		return "Command failed:\n$cmd\nReturn: $retval $output";
	}

	return $output;
}

function kfEscapeShellArg() {
	$args = func_get_args();
	$args = array_map( 'escapeshellarg', $args );
	return implode( ' ', $args );
}

/**
 * @source php.net/filesize#100097
 */
function kfFormatBytes( $size, $precision = 2 ) {
	$units = array( ' B', ' KB', ' MB', ' GB', ' TB' );
	for ( $i = 0; $size >= 1024 && $i < 4; $i++ ) {
		$size /= 1024;
	}
	return round( $size, 2 ) . $units[$i];
}

/**
 * HTTP
 * -------------------------------------------------
 */

/**
 * Get data from MediaWiki API.
 * *
 * @param string $url Base url for wiki (from LabsDb::getDbInfo).
 * @param array $params Query parameters for MediaWiki API
 * @return object|bool Data from the API response, or boolean false
 */
function kfApiRequest( $url, $params ) {
	$section = new kfLogSection( __METHOD__ );

	$params['format'] = 'json';
	if ( !isset( $params['action'] ) ) {
		$params['action'] = 'query';
	}

	$apiUrl = "$url/w/api.php?" . http_build_query( $params );
	kfLog( "request: GET $apiUrl" );
	$response = HttpRequest::get( $apiUrl );
	if ( !$response ) {
		return false;
	}

	$data = json_decode( $response );
	if ( !is_object( $data ) || isset( $data->error ) ) {
		return false;
	}

	return $data;
}

// php.net/http_response_code
if ( !function_exists( 'http_response_code' ) ) {
	function http_response_code( $code = null ) {

		if ( $code !== null ) {
			switch ( $code ) {
				case 100: $text = 'Continue'; break;
				case 101: $text = 'Switching Protocols'; break;
				case 200: $text = 'OK'; break;
				case 201: $text = 'Created'; break;
				case 202: $text = 'Accepted'; break;
				case 203: $text = 'Non-Authoritative Information'; break;
				case 204: $text = 'No Content'; break;
				case 205: $text = 'Reset Content'; break;
				case 206: $text = 'Partial Content'; break;
				case 300: $text = 'Multiple Choices'; break;
				case 301: $text = 'Moved Permanently'; break;
				case 302: $text = 'Moved Temporarily'; break;
				case 303: $text = 'See Other'; break;
				case 304: $text = 'Not Modified'; break;
				case 305: $text = 'Use Proxy'; break;
				case 400: $text = 'Bad Request'; break;
				case 401: $text = 'Unauthorized'; break;
				case 402: $text = 'Payment Required'; break;
				case 403: $text = 'Forbidden'; break;
				case 404: $text = 'Not Found'; break;
				case 405: $text = 'Method Not Allowed'; break;
				case 406: $text = 'Not Acceptable'; break;
				case 407: $text = 'Proxy Authentication Required'; break;
				case 408: $text = 'Request Time-out'; break;
				case 409: $text = 'Conflict'; break;
				case 410: $text = 'Gone'; break;
				case 411: $text = 'Length Required'; break;
				case 412: $text = 'Precondition Failed'; break;
				case 413: $text = 'Request Entity Too Large'; break;
				case 414: $text = 'Request-URI Too Large'; break;
				case 415: $text = 'Unsupported Media Type'; break;
				case 500: $text = 'Internal Server Error'; break;
				case 501: $text = 'Not Implemented'; break;
				case 502: $text = 'Bad Gateway'; break;
				case 503: $text = 'Service Unavailable'; break;
				case 504: $text = 'Gateway Time-out'; break;
				case 505: $text = 'HTTP Version not supported'; break;
				default:
					$code = 500;
					$text = 'Unknown-Http-Status-Code';
				break;
			}

			$protocol = ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' );

			header( $protocol . ' ' . $code . ' ' . $text );

			$GLOBALS['http_response_code'] = $code;

		} else {
			$code = ( isset( $GLOBALS['http_response_code'] ) ? $GLOBALS['http_response_code'] : 200 );
		}

		return $code;
	}
}
