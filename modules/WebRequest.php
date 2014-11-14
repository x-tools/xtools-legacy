<?php
/**
 * Deal with importing all those nasssty globals and things
 */

# Copyright (C) 2003 Brion Vibber <brion@pobox.com>
# http://www.mediawiki.org/
#
# This program is free software; you can redistribute it and/or modify
# it under the terms of the GNU General Public License as published by
# the Free Software Foundation; either version 2 of the License, or
# (at your option) any later version.
#
# This program is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
# GNU General Public License for more details.
#
# You should have received a copy of the GNU General Public License along
# with this program; if not, write to the Free Software Foundation, Inc.,
# 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.
# http://www.gnu.org/copyleft/gpl.html


/**
 * The WebRequest class encapsulates getting at data passed in the
 * URL or via a POSTed form, handling remove of "magic quotes" slashes,
 * stripping illegal input characters and normalizing Unicode sequences.
 *
 * Usually this is used via a global singleton, $wgRequest. You should
 * not create a second WebRequest object; make a FauxRequest object if
 * you want to pass arbitrary data to some function in place of the web
 * input.
 *
 * @ingroup HTTP
 */
class WebRequest {
	protected $data;
	protected $headers = array();

	public function __construct() {

		// POST overrides GET data
		// We don't use $_REQUEST here to avoid interference from cookies...
		$this->data = $_POST + $_GET;
	}

	/**
	 * Recursively normalizes UTF-8 strings in the given array.
	 *
	 * @param $data string or array
	 * @return cleaned-up version of the given
	 * @private
	 */
	function normalizeUnicode( $data ) {
		if( is_array( $data ) ) {
			foreach( $data as $key => $val ) {
				$data[$key] = $this->normalizeUnicode( $val );
			}
		} else {
			$data = $this->normalize( $data );
		}
		return $data;
	}
	
	function normalize( $s ) {
		return $s;
#		return UtfNormal::cleanUp( $s );
	}
	
	static function normalize_static( $s ) {
		return $s;
#		return UtfNormal::cleanUp( $s );
	}

	/**
	 * Fetch a value from the given array or return $default if it's not set.
	 *
	 * @param $arr Array
	 * @param $name String
	 * @param $default Mixed
	 * @return mixed
	 */
	private function getGPCVal( $arr, $name, $default ) {
		# PHP is so nice to not touch input data, except sometimes:
		# http://us2.php.net/variables.external#language.variables.external.dot-in-names
		# Work around PHP *feature* to avoid *bugs* elsewhere.
		$name = strtr( $name, '.', '_' );
		if( isset( $arr[$name] ) ) {
			$data = $arr[$name];
			if( isset( $_GET[$name] ) && !is_array( $data ) ) {
				$data = $this->checkTitleEncoding( $data );
			}
			$data = $this->normalizeUnicode( $data );
			return $data;
		} else {
#			taint( $default ); 
			return $default;
		}
	}
	
	function checkTitleEncoding( $s ) {
		$ishigh = preg_match( '/[\x80-\xff]/', $s );
		if ( !$ishigh ) {
			return $s;
		}

		$isutf8 = preg_match( '/^([\x00-\x7f]|[\xc0-\xdf][\x80-\xbf]|' .
			'[\xe0-\xef][\x80-\xbf]{2}|[\xf0-\xf7][\x80-\xbf]{3})+$/', $s );

		if ( $isutf8 ) {
			return $s;
		}
		return $s;//fallback, has problems.
		//return $this->iconv( $this->fallback8bitEncoding(), 'utf-8', $s );
	} 
	
	function iconv( $in, $out, $string ) {
		$text = @iconv( $in, $out . '//IGNORE', $string );
		return $text; 
	}
	
	public function getSafeVal( $callback ) {
	
		if( !method_exists( $this, $callback ) ) {
			$args = func_get_args();
			$callback = 'getVal';
		}
		else {
			$args = func_get_args();
			array_shift( $args );
		}
		
		return htmlspecialchars( call_user_func_array( array( $this, $callback ), $args ) );
	}

	/**
	 * Fetch a scalar from the input or return $default if it's not set.
	 * Returns a string. Arrays are discarded. Useful for
	 * non-freeform text inputs (e.g. predefined internal text keys
	 * selected by a drop-down menu). For freeform input, see getText().
	 *
	 * @param $name String
	 * @param $default String: optional default (or NULL)
	 * @return String
	 */
	public function getVal( $name, $default = null ) {
		$val = $this->getGPCVal( $this->data, $name, $default );
		if( is_array( $val ) ) {
			$val = $default;
		}
		if( is_null( $val ) ) {
			return $val;
		} else {
			return (string)$val;
		}
	}

	/**
	 * Set an aribtrary value into our get/post data.
	 *
	 * @param $key String: key name to use
	 * @param $value Mixed: value to set
	 * @return Mixed: old value if one was present, null otherwise
	 */
	public function setVal( $key, $value ) {
		$ret = isset( $this->data[$key] ) ? $this->data[$key] : null;
		$this->data[$key] = $value;
		return $ret;
	}

	/**
	 * Fetch an array from the input or return $default if it's not set.
	 * If source was scalar, will return an array with a single element.
	 * If no source and no default, returns NULL.
	 *
	 * @param $name String
	 * @param $default Array: optional default (or NULL)
	 * @return Array
	 */
	public function getArray( $name, $default = null ) {
		$val = $this->getGPCVal( $this->data, $name, $default );
		if( is_null( $val ) ) {
			return null;
		} else {
			return (array)$val;
		}
	}

	/**
	 * Fetch an array of integers, or return $default if it's not set.
	 * If source was scalar, will return an array with a single element.
	 * If no source and no default, returns NULL.
	 * If an array is returned, contents are guaranteed to be integers.
	 *
	 * @param $name String
	 * @param $default Array: option default (or NULL)
	 * @return Array of ints
	 */
	public function getIntArray( $name, $default = null ) {
		$val = $this->getArray( $name, $default );
		if( is_array( $val ) ) {
			$val = array_map( 'intval', $val );
		}
		return $val;
	}

	/**
	 * Fetch an integer value from the input or return $default if not set.
	 * Guaranteed to return an integer; non-numeric input will typically
	 * return 0.
	 *
	 * @param $name String
	 * @param $default Integer
	 * @return Integer
	 */
	public function getInt( $name, $default = 0 ) {
		return intval( $this->getVal( $name, $default ) );
	}

	/**
	 * Fetch an integer value from the input or return null if empty.
	 * Guaranteed to return an integer or null; non-numeric input will
	 * typically return null.
	 *
	 * @param $name String
	 * @return Integer
	 */
	public function getIntOrNull( $name ) {
		$val = $this->getVal( $name );
		return is_numeric( $val )
			? intval( $val )
			: null;
	}

	/**
	 * Fetch a boolean value from the input or return $default if not set.
	 * Guaranteed to return true or false, with normal PHP semantics for
	 * boolean interpretation of strings.
	 *
	 * @param $name String
	 * @param $default Boolean
	 * @return Boolean
	 */
	public function getBool( $name, $default = false ) {
		$val = $this->getVal( $name, $default );
		return !empty( $val ) ? true : false;
	}

	/**
	 * Return true if the named value is set in the input, whatever that
	 * value is (even "0"). Return false if the named value is not set.
	 * Example use is checking for the presence of check boxes in forms.
	 *
	 * @param $name String
	 * @return Boolean
	 */
	public function getCheck( $name ) {
		# Checkboxes and buttons are only present when clicked
		# Presence connotes truth, abscense false
		$val = $this->getVal( $name, null );
		return isset( $val );
	}

	/**
	 * Fetch a text string from the given array or return $default if it's not
	 * set. \r is stripped from the text, and with some language modules there
	 * is an input transliteration applied. This should generally be used for
	 * form <textarea> and <input> fields. Used for user-supplied freeform text
	 * input (for which input transformations may be required - e.g. Esperanto
	 * x-coding).
	 *
	 * @param $name String
	 * @param $default String: optional
	 * @return String
	 */
	public function getText( $name, $default = '' ) {
		$val = $this->getVal( $name, $default );
		return str_replace( "\r\n", "\n", $val );
	}

	/**
	 * Extracts the given named values into an array.
	 * If no arguments are given, returns all input values.
	 * No transformation is performed on the values.
	 */
	public function getValues() {
		$names = func_get_args();
		if ( count( $names ) == 0 ) {
			$names = array_keys( $this->data );
		}
		elseif( is_array( $names[0] ) ) {
			$names = $names[0];
		}

		$retVal = array();
		foreach ( $names as $name ) {
			$value = $this->getVal( $name );
			if ( !is_null( $value ) ) {
				$retVal[$name] = $value;
			}
		}
		return $retVal;
	}

	/**
	 * Returns true if the present request was reached by a POST operation,
	 * false otherwise (GET, HEAD, or command-line).
	 *
	 * Note that values retrieved by the object may come from the
	 * GET URL etc even on a POST request.
	 *
	 * @return Boolean
	 */
	public function wasPosted() {
		return $_SERVER['REQUEST_METHOD'] == 'POST';
	}

	/**
	 * Returns true if there is a session cookie set.
	 * This does not necessarily mean that the user is logged in!
	 *
	 * If you want to check for an open session, use session_id()
	 * instead; that will also tell you if the session was opened
	 * during the current request (in which case the cookie will
	 * be sent back to the client at the end of the script run).
	 *
	 * @return Boolean
	 */
	public function checkSessionCookie() {
		return isset( $_COOKIE[session_name()] );
	}

	/**
	 * Get a cookie from the $_COOKIE jar
	 *
	 * @param $key String: the name of the cookie
	 * @param $default Mixed: what to return if the value isn't found
	 * @param $prefix String: a prefix to use for the cookie name, if not $wgCookiePrefix
	 * @return Mixed: cookie value or $default if the cookie not set
	 */
	public function getCookie( $key, $default = null, $prefix = '' ) {
		return $this->getGPCVal( $_COOKIE, $prefix . $key , $default );
	}

	/**
	 * Return the path portion of the request URI.
	 *
	 * @return String
	 */
	public function getRequestURL() {
		if( isset( $_SERVER['REQUEST_URI']) && strlen($_SERVER['REQUEST_URI']) ) {
			$base = $_SERVER['REQUEST_URI'];
		} elseif( isset( $_SERVER['SCRIPT_NAME'] ) ) {
			// Probably IIS; doesn't set REQUEST_URI
			$base = $_SERVER['SCRIPT_NAME'];
			if( isset( $_SERVER['QUERY_STRING'] ) && $_SERVER['QUERY_STRING'] != '' ) {
				$base .= '?' . $_SERVER['QUERY_STRING'];
			}
		} else {
			// This shouldn't happen!
			throw new Exception( "Web server doesn't provide either " .
				"REQUEST_URI or SCRIPT_NAME. Report details of your " .
				"web server configuration to http://bugzilla.wikimedia.org/" );
		}
		// User-agents should not send a fragment with the URI, but
		// if they do, and the web server passes it on to us, we
		// need to strip it or we get false-positive redirect loops
		// or weird output URLs
		$hash = strpos( $base, '#' );
		if( $hash !== false ) {
			$base = substr( $base, 0, $hash );
		}
		if( $base{0} == '/' ) {
			return $base;
		} else {
			// We may get paths with a host prepended; strip it.
			return preg_replace( '!^[^:]+://[^/]+/!', '/', $base );
		}
	}

	/**
	 * Return the request URI with the canonical service and hostname.
	 *
	 * @return String
	 */
	public function getFullRequestURL() {
		return $this->getServer() . $this->getRequestURL();
	}
	
	function getIP() {
		return @$_SERVER['REMOTE_ADDR'];
	}
	
	function getServer() {
		/** URL of the server. It will be automatically built including https mode */
		$wgServer = '';
		 
		if( isset( $_SERVER['SERVER_NAME'] ) ) {
			$wgServerName = $_SERVER['SERVER_NAME'];
		} elseif( isset( $_SERVER['HOSTNAME'] ) ) {
			$wgServerName = $_SERVER['HOSTNAME'];
		} elseif( isset( $_SERVER['HTTP_HOST'] ) ) {
			$wgServerName = $_SERVER['HTTP_HOST'];
		} elseif( isset( $_SERVER['SERVER_ADDR'] ) ) {
			$wgServerName = $_SERVER['SERVER_ADDR'];
		} else {
			$wgServerName = 'localhost';
		}
		 
		# check if server use https:
		$wgProto = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') ? 'https' : 'http';
		 
		$wgServer = $wgProto.'://' . $wgServerName;
		# If the port is a non-standard one, add it to the URL
		if(    isset( $_SERVER['SERVER_PORT'] )
		    && !strpos( $wgServerName, ':' )
		    && (    ( $wgProto == 'http' && $_SERVER['SERVER_PORT'] != 80 )
			 || ( $wgProto == 'https' && $_SERVER['SERVER_PORT'] != 443 ) ) ) {
		 
			$wgServer .= ":" . $_SERVER['SERVER_PORT'];
		}
		return $wgServer;
	}

	/**
	 * Get a request header, or false if it isn't set
	 * @param $name String: case-insensitive header name
	 */
	public function getHeader( $name ) {
		$name = strtoupper( $name );
		if ( function_exists( 'apache_request_headers' ) ) {
			if ( !$this->headers ) {
				foreach ( apache_request_headers() as $tempName => $tempValue ) {
					$this->headers[ strtoupper( $tempName ) ] = $tempValue;
				}
			}
			if ( isset( $this->headers[$name] ) ) {
				return $this->headers[$name];
			} else {
				return false;
			}
		} else {
			$name = 'HTTP_' . str_replace( '-', '_', $name );
			if ( $name === 'HTTP_CONTENT_LENGTH' && !isset( $_SERVER[$name] ) ) {
				$name = 'CONTENT_LENGTH';
			}
			if ( isset( $_SERVER[$name] ) ) {
				return $_SERVER[$name];
			} else {
				return false;
			}
		}
	}

	/**
	 * Get data from $_SESSION
	 *
	 * @param $key String: name of key in $_SESSION
	 * @return Mixed
	 */
	public function getSessionData( $key ) {
		if( !isset( $_SESSION[$key] ) )
			return null;
		return $_SESSION[$key];
	}

	/**
	 * Set session data
	 *
	 * @param $key String: name of key in $_SESSION
	 * @param $data Mixed
	 */
	public function setSessionData( $key, $data ) {
		$_SESSION[$key] = $data;
	}

	/**
	 * Returns true if the PATH_INFO ends with an extension other than a script
	 * extension. This could confuse IE for scripts that send arbitrary data which
	 * is not HTML but may be detected as such.
	 *
	 * Various past attempts to use the URL to make this check have generally
	 * run up against the fact that CGI does not provide a standard method to
	 * determine the URL. PATH_INFO may be mangled (e.g. if cgi.fix_pathinfo=0),
	 * but only by prefixing it with the script name and maybe some other stuff,
	 * the extension is not mangled. So this should be a reasonably portable
	 * way to perform this security check.
	 */
	public function isPathInfoBad() {
		if ( !isset( $_SERVER['PATH_INFO'] ) ) {
			return false;
		}
		$pi = $_SERVER['PATH_INFO'];
		$dotPos = strrpos( $pi, '.' );
		if ( $dotPos === false ) {
			return false;
		}
		$ext = substr( $pi, $dotPos );
		return !in_array( $ext, array( '.php', '.php5' ) );
	}
	
	/**
	 * Parse the Accept-Language header sent by the client into an array
	 * @return array( languageCode => q-value ) sorted by q-value in descending order
	 */
	public function getAcceptLang() {
		// Modified version of code found at http://www.thefutureoftheweb.com/blog/use-accept-language-header
		if ( !isset( $_SERVER['HTTP_ACCEPT_LANGUAGE'] ) ) {
			return array();
		}
		
		// Break up string into pieces (languages and q factors)
		$lang_parse = null;
		preg_match_all( '/([a-z]{1,8}(-[a-z]{1,8})?)\s*(;\s*q\s*=\s*(1|0(\.[0-9]+))?)?/i',
			$_SERVER['HTTP_ACCEPT_LANGUAGE'], $lang_parse );
		
		if ( !count( $lang_parse[1] ) ) {
			return array();
		}
		// Create a list like "en" => 0.8
		$langs = array_combine( $lang_parse[1], $lang_parse[4] );
		// Set default q factor to 1
		foreach ( $langs as $lang => $val ) {
			if ( $val === '' ) {
				$langs[$lang] = 1;
			}
		}
		// Sort list
		arsort( $langs, SORT_NUMERIC );
		return $langs;
	}
}
