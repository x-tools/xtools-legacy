<?php

/**
 * Written in 2013 by Brad Jorsch
 *
 * To the extent possible under law, the author(s) have dedicated all copyright 
 * and related and neighboring rights to this software to the public domain 
 * worldwide. This software is distributed without any warranty. 
 *
 * See <http://creativecommons.org/publicdomain/zero/1.0/> for a copy of the 
 * CC0 Public Domain Dedication.
 * 
 * Rewritten/Modified 2014 Hedonil
 */
 
$oauth_cnf = "oauth.cnf";
$inifile = XTOOLS_BASE_SYS_DIR."/$oauth_cnf";

$mwOAuthUrl = 'https://meta.wikimedia.org/w/index.php?title=Special:OAuth';
$mwApiUrl = 'https://meta.wikimedia.org/w/api.php';
#$mwOAuthUrl = 'https://www.mediawiki.org/wiki/Special:OAuth';
#$mwApiUrl = 'https://www.mediawiki.org/w/api.php';


/* Set this to the interwiki prefix for the OAuth central wiki. */
$mwOAuthIW = 'mw';



class OAuth{
	
	private static function init(){
		global $inifile;
		
		// Read the ini file
		$ini = parse_ini_file( $inifile );
		if ( $ini === false ) {
			header( "HTTP/1.1 501 Internal Server Error" );
			echo 'The ini file could not be read';
			exit(0);
		}
		
		if ( !isset( $ini['consumer'] ) ||
		!isset( $ini['consumerKey'] ) ||
		!isset( $ini['consumerSecret'] )
		) {
			header( "HTTP/1.1 501 Internal Server Error" );
			echo 'Required configuration directives not found in ini file';
			exit(0);
		}
		
		$_SESSION['userAgent'] = $ini['consumer'];
		$_SESSION['consumerKey'] = $ini['consumerKey'];
		$_SESSION['consumerSecret'] = $ini['consumerSecret'];
		
	}
	
	public static function getSessionKey( $keyname, $default=null ){
		 return isset($_SESSION[$keyname]) ? $_SESSION[$keyname] : $default;
	}
	
	
	/**
	 * Utility function to sign a request
	 *
	 * Note this doesn't properly handle the case where a parameter is set both in
	 * the query string in $url and in $params, or non-scalar values in $params.
	 *
	 * @param string $method Generally "GET" or "POST"
	 * @param string $url URL string
	 * @param array $params Extra parameters for the Authorization header or post
	 * 	data (if application/x-www-form-urlencoded).
	 * @return string Signature
	 */
	private static function signRequest( $method, $url, $params = array() ) {
		
		$consumerSecret = self::getSessionKey('consumerSecret'); 
		$tokenSecret = self::getSessionKey('tokenSecret');
	
		$parts = parse_url( $url );
	
		// We need to normalize the endpoint URL
		$scheme = isset( $parts['scheme'] ) ? $parts['scheme'] : 'http';
		$host = isset( $parts['host'] ) ? $parts['host'] : '';
		$port = isset( $parts['port'] ) ? $parts['port'] : ( $scheme == 'https' ? '443' : '80' );
		$path = isset( $parts['path'] ) ? $parts['path'] : '';
		if ( ( $scheme == 'https' && $port != '443' ) ||
		( $scheme == 'http' && $port != '80' )
		) {
			// Only include the port if it's not the default
			$host = "$host:$port";
		}
	
		// Also the parameters
		$pairs = array();
		parse_str( isset( $parts['query'] ) ? $parts['query'] : '', $query );
		$query += $params;
		unset( $query['oauth_signature'] );
		if ( $query ) {
			$query = array_combine(
					// rawurlencode follows RFC 3986 since PHP 5.3
					array_map( 'rawurlencode', array_keys( $query ) ),
					array_map( 'rawurlencode', array_values( $query ) )
			);
			ksort( $query, SORT_STRING );
			foreach ( $query as $k => $v ) {
				$pairs[] = "$k=$v";
			}
		}
	
		$toSign = rawurlencode( strtoupper( $method ) ) . '&' .
				rawurlencode( "$scheme://$host$path" ) . '&' .
				rawurlencode( join( '&', $pairs ) );
		$key = rawurlencode( $consumerSecret ) . '&' . rawurlencode( $tokenSecret );
	
		$sign=base64_encode( hash_hmac( 'sha1', $toSign, $key, true ) );
	
		return $sign;
	}
	
	
	/**
	 * Request authorization
	 * @return void
	 */
	public static function doAuthorizationRedirect($wt) {
		global $mwOAuthUrl, $mwApiUrl, $perflog;
		
		$_SESSION['tokenSecret'] = null;
		$_SESSION['tokenKey'] = null;
		
		self::init();
		
		$userAgent = self::getSessionKey('userAgent');
		$consumerKey = self::getSessionKey('consumerKey');
	
		// First, we need to fetch a request token.
		// The request is signed with an empty token secret and no token key.
		$url = $mwOAuthUrl . '/initiate';
		$url .= strpos( $url, '?' ) ? '&' : '?';
		$url .= http_build_query( array(
				'format' => 'json',
	
				// OAuth information
				'oauth_callback' => 'oob', // Must be "oob" for MWOAuth
				'oauth_consumer_key' => $consumerKey,
				'oauth_version' => '1.0',
				'oauth_nonce' => md5( microtime() . mt_rand() ),
				'oauth_timestamp' => time(),
	
				// We're using secret key signatures here.
				'oauth_signature_method' => 'HMAC-SHA1',
		) );
		$signature = self::signRequest( 'GET', $url );
		$url .= "&oauth_signature=" . urlencode( $signature );
		$ch = curl_init();
		curl_setopt( $ch, CURLOPT_URL, $url );
		//curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, false );
		curl_setopt( $ch, CURLOPT_USERAGENT, $userAgent );
		curl_setopt( $ch, CURLOPT_HEADER, 0 );
		curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1 );
		
		$data = curl_exec( $ch );
		
		#$perflog->stack[] = $data;
		#$perflog->stack[] = $mwApiUrl . "?action=query&meta=userinfo&format=json";
		#$perflog->stack[] = $wt->gethttp($mwApiUrl . "?action=query&meta=userinfo&format=json");
		
		if ( !$data ) {
			header( "HTTP/1.1 501 Internal Server Error" );
			echo "<p>data - There was an error communicating with the wiki for app authorization. Please try again.</p>\n";
			echo '<!-- Curl error: ' . htmlspecialchars( curl_error( $ch ) ) . ' -->';
			
			exit(0);
		}
		curl_close( $ch );
	
		$token = json_decode( $data );
		
		if ( is_object( $token ) && isset( $token->error ) ) {
			#header( "HTTP/1.1 501 Internal Server Error" );
			$wt->error = "object There was an error communicating with the wiki for app authorization. Please try again.";
			#echo '<!-- Error retrieving token: ' . htmlspecialchars( $token->error ) . ' -->';
			$wt->showPage();
			exit(0);
		}
		if ( !is_object( $token ) || !isset( $token->key ) || !isset( $token->secret ) ) {
			#header( "HTTP/1.1 501 Internal Server Error" );
			echo "<p>secret -There was an error communicating with the wiki for app authorization. Please try again.</p>\n";
			echo '<!-- Invalid response from token request -->';
			exit(0);
		}
		
	
		// Now we have the request token, we need to save it for later.
		$_SESSION['tokenKey'] = $token->key;
		$_SESSION['tokenSecret'] = $token->secret;
	
		
		// Then we send the user off to authorize
		$url = $mwOAuthUrl . '/authorize';
		$url .= strpos( $url, '?' ) ? '&' : '?';
		$url .= http_build_query( array(
				'oauth_token' => $token->key,
				'oauth_consumer_key' => $consumerKey,
		) );
		header( "Location: $url" );
		echo 'Please see <a href="' . htmlspecialchars( $url ) . '">' . htmlspecialchars( $url ) . '</a>';
	}
	
	
	/**
	 * Handle a callback to fetch the _final_ access token
	 * @return void
	 */
	public static function fetchAccessToken( $oauth_verifier, $oauth_token ) {
		global $mwOAuthUrl;
		
		$userAgent = self::getSessionKey('userAgent');
		$consumerKey = self::getSessionKey('consumerKey');
		$tokenKey = $oauth_token; 
		#$gTokenSecret;

		$url = $mwOAuthUrl . '/token';
		$url .= strpos( $url, '?' ) ? '&' : '?';
		$url .= http_build_query( array(
				'format' => 'json',
				'oauth_verifier' => $oauth_verifier,
	
				// OAuth information
				'oauth_consumer_key' => $consumerKey,
				'oauth_token' => $tokenKey,
				'oauth_version' => '1.0',
				'oauth_nonce' => md5( microtime() . mt_rand() ),
				'oauth_timestamp' => time(),
	
				// We're using secret key signatures here.
				'oauth_signature_method' => 'HMAC-SHA1',
		) );
		$signature = self::signRequest( 'GET', $url );
		$url .= "&oauth_signature=" . urlencode( $signature );
		$ch = curl_init();
		curl_setopt( $ch, CURLOPT_URL, $url );
		//curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, false );
		curl_setopt( $ch, CURLOPT_USERAGENT, $userAgent );
		curl_setopt( $ch, CURLOPT_HEADER, 0 );
		curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1 );
		
		$data = curl_exec( $ch );
		
		if ( !$data ) {
			header( "HTTP/1.1 501 Internal Server Error" );
			echo "<p>There was an error communicating with the wiki for app authorization. Please try again.</p>\n";
			echo '<!-- Curl error: ' . htmlspecialchars( curl_error( $ch ) ) . ' -->';
			exit(0);
		}
		curl_close( $ch );
		
		$token = json_decode( $data );
		
		if ( is_object( $token ) && isset( $token->error ) ) {
			header( "HTTP/1.1 501 Internal Server Error" );
			echo "<p>There was an error communicating with the wiki for app authorization. Please try again.</p>\n";
			echo '<!-- Error retrieving token: ' . htmlspecialchars( $token->error ) . ' -->';
			exit(0);
		}
		if ( !is_object( $token ) || !isset( $token->key ) || !isset( $token->secret ) ) {
			header( "HTTP/1.1 501 Internal Server Error" );
			echo "<p>There was an error communicating with the wiki for app authorization. Please try again.</p>\n";
			echo '<!-- Invalid response from token request -->';
			exit(0);
		}
	
		//Save the access token

		$_SESSION['tokenKey'] = $token->key;
		$_SESSION['tokenSecret'] = $token->secret;

	}
	
	
	/**
	 * Send an API query with OAuth authorization
	 *
	 * @param array $post Post data
	 * @param object $ch Curl handle
	 * @return array API results
	 */
	static function doApiQuery( $apiUrl, $post, &$ch = null ) {
		global $mwApiUrl;
		
		$apiUrl = ( $apiUrl ) ? $apiUrl : $mwApiUrl;
		
		$userAgent = self::getSessionKey('userAgent');
		$consumerKey = self::getSessionKey('consumerKey');
		$tokenKey = self::getSessionKey('tokenKey');
	
		$headerArr = array(
				// OAuth information
				'oauth_consumer_key' => $consumerKey,
				'oauth_token' => $tokenKey,
				'oauth_version' => '1.0',
				'oauth_nonce' => md5( microtime() . mt_rand() ),
				'oauth_timestamp' => time(),
	
				// We're using secret key signatures here.
				'oauth_signature_method' => 'HMAC-SHA1',
		);
		$signature = self::signRequest( 'POST', $apiUrl, $post + $headerArr );
		$headerArr['oauth_signature'] = $signature;
	
		$header = array();
		foreach ( $headerArr as $k => $v ) {
			$header[] = rawurlencode( $k ) . '="' . rawurlencode( $v ) . '"';
		}
		$header = 'Authorization: OAuth ' . join( ', ', $header );
	
		if ( !$ch ) {
			$ch = curl_init();
		}
		curl_setopt( $ch, CURLOPT_POST, true );
		curl_setopt( $ch, CURLOPT_URL, $apiUrl );
		curl_setopt( $ch, CURLOPT_POSTFIELDS, http_build_query( $post ) );
		curl_setopt( $ch, CURLOPT_HTTPHEADER, array( $header ) );
		//curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, false );
		curl_setopt( $ch, CURLOPT_USERAGENT, $userAgent );
		curl_setopt( $ch, CURLOPT_HEADER, 0 );
		curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1 );
		
		$data = curl_exec( $ch );
		
		if ( !$data ) {
			return null; // "<p>1. There was an error communicating with the wiki when performing the apiQuery. Please try again.</p>\n";
		}
		$ret = json_decode( $data );
		if ( !$data ) {
			return  'JSON decode failed: ' . htmlspecialchars( $data );
		}

		return $ret;
	}
	
	
	
static function doApiMultiQuery( &$apiRequestArr ) {
	global $perflog;
	$ret = null;
	
	#$perflog->add("OArq", 0, $apiRequestArr );
	
	$userAgent = self::getSessionKey('userAgent');
	$consumerKey = self::getSessionKey('consumerKey');
	$tokenKey = self::getSessionKey('tokenKey');

	//create multiple cUrl handler
	$mh = curl_multi_init();
	
	foreach ( $apiRequestArr as $i => $apiRequest ){
		$headerArr = array(
				// OAuth information
				'oauth_consumer_key' => $consumerKey,
				'oauth_token' => $tokenKey,
				'oauth_version' => '1.0',
				'oauth_nonce' => md5( microtime() . mt_rand() ),
				'oauth_timestamp' => time(),
	
				// We're using secret key signatures here.
				'oauth_signature_method' => 'HMAC-SHA1',
		);
		$signature = self::signRequest( 'POST', $apiRequest["apiUrl"], $apiRequest["data"] + $headerArr );
		$headerArr['oauth_signature'] = $signature;
	
		$header = array();
		foreach ( $headerArr as $k => $v ) {
			$header[] = rawurlencode( $k ) . '="' . rawurlencode( $v ) . '"';
		}
		$header = 'Authorization: OAuth ' . join( ', ', $header );
	

		$ch[$i] = curl_init();

		curl_setopt( $ch[$i], CURLOPT_POST, true );
		curl_setopt( $ch[$i], CURLOPT_URL, $apiRequest["apiUrl"] );
		curl_setopt( $ch[$i], CURLOPT_POSTFIELDS, http_build_query( $apiRequest["data"] ) );
		curl_setopt( $ch[$i], CURLOPT_HTTPHEADER, array( $header ) );
		//curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, false );
		curl_setopt( $ch[$i], CURLOPT_USERAGENT, $userAgent );
		curl_setopt( $ch[$i], CURLOPT_HEADER, 0 );
		curl_setopt( $ch[$i], CURLOPT_RETURNTRANSFER, 1 );
		
		curl_multi_add_handle($mh, $ch[$i]);
	}
	
	$active = null;
	// execute the handles
	do {
	    $mrc = curl_multi_exec($mh, $active);
	} while ($mrc == CURLM_CALL_MULTI_PERFORM);
	
	//check for results and execute until everything is done
	while ($active && $mrc == CURLM_OK) {
	    if (curl_multi_select($mh) == -1) {
	    	usleep(50);
	    }
	    
        do {
            $mrc = curl_multi_exec($mh, $active);
        } while ($mrc == CURLM_CALL_MULTI_PERFORM);
	  
	}

	//fetch the results
	foreach ($apiRequestArr as $i => $apiRequest) {
		$wiki =  $apiRequestArr[$i]["wiki"];
		$res = curl_multi_getcontent($ch[$i]);
		$ret[$wiki] = json_decode( $res );
		curl_close($ch[$i]);
	}
	
	curl_multi_close($mh);
	$mh = null;

	return $ret;
}

public static function getUserInfo( $apiUrl=null ) {
	
	$ch = null;

	// First fetch the username
	$res = self::doApiQuery( $apiUrl, array(
			'format' => 'json',
			'action' => 'query',
			'meta' => 'userinfo',
	), $ch );
	
	return $res;
}

	
// does not work with OAuthApi
// public static function login($apiUrl, $username){
// 	global $mwApiUrl;

// 	$ch = null;

// 	$res = self::doApiQuery( $apiUrl, array(
// 			'format' => 'json',
// 			'action' => 'login',
// 	), $ch );

// 	return $res;
// }

//logout does not work with OAuth API (Anomie)
public static function logout(){
	global $mwApiUrl;

	$ch = null;

	$res = self::doApiQuery( $mwApiUrl, array(
			'format' => 'json',
			'action' => 'logout',
	), $ch );

	return $res;
}

/**
 * Get watchlist of the user
 * input array with (apiUrl, continue)
 * @return array raw watchlist
 */
public static function getWatchlist( $wlRequestArr ) {
	global $log; if($log){ $start = microtime(true);}
	$apiRequestArr = null;
	
	//error if input is not an array
	if ( !is_array($wlRequestArr) ) {
		#echo "not an array"; 
		return null; 
	}

	foreach ( $wlRequestArr as $wlRequest ){
		$post = array(
				'format'  => 'json',
				'action'  => 'query',
				'list' 	  => 'watchlistraw',
				'wrprop'  => 'changed',
				'wrlimit' => '500',
			);
	
		if ( isset($wlRequest["continue"]) && strval($wlRequest["continue"]) != "" ){
			$post['wrcontinue'] = $wlRequest["continue"];
		}
		$apiRequestArr[] = array("wiki" => $wlRequest["wiki"], "apiUrl" => $wlRequest["apiUrl"], "post" => $post);
	}
	
	
	$res = self::doApiMultiQuery( $apiRequestArr );
	
	if($log){ wvs::write_log('OAuth::getWatchlist', '', microtime(true) - $start);}
	return $res;
}

public static function getNotifications( $wlRequestArr ) {
	global $log; if($log){ $start = microtime(true);}
	$apiRequestArr = null;

	//error if input is not an array
	if ( !is_array($wlRequestArr) ) {
		#echo "not an array";
		return null;
	}

	foreach ( $wlRequestArr as $wlRequest ){
		$post = array(
				'format'  => 'json',
				'action'  => 'query',
				'meta' 	  => 'notifications',
				'notprop'  => 'count',
				'notlimit' => '50',
		);

		if ( isset($wlRequest["continue"]) && strval($wlRequest["continue"]) != "" ){
			$post['wrcontinue'] = $wlRequest["continue"];
		}
		$apiRequestArr[] = array("wiki" => $wlRequest["wiki"], "apiUrl" => $wlRequest["apiUrl"], "post" => $post);
	}


	$res = self::doApiMultiQuery( $apiRequestArr );

	if($log){ wvs::write_log('OAuth::getNotifications', '', microtime(true) - $start);}
	return $res;
}

}

class OAuth2 {
    
    protected $consumerKey = "";
    
    protected $consumerSecret = "";
    
    protected $requestToken = "";
    
    protected $requestTokenSecret = "";
    
    protected $accessToken = "";
    
    protected $accessTokenSecret = "";
    
    protected $identifiedUsername = "";
    
    protected $identifiedUserid = "";
    
    protected $signature = "";
    
    protected $OAuthSuccess = false;
    
    protected $OAuthURL = "";
    
    protected $apiURL = "";
    
    protected $OAuthErrorMessage = null;
    
    public $profile = "default";
    
    
    public function __construct( $api = "", $profile = "default" ) {
        
        global $inifile;

        // Read the ini file
        $ini = parse_ini_file( $inifile );
        if ( $ini === false ) {
            header( "HTTP/1.1 501 Internal Server Error" );
            echo 'The ini file could not be read';
            exit(0);
        }
        
        if ( !isset( $ini['consumer'] ) ||
        !isset( $ini['consumerKey'] ) ||
        !isset( $ini['consumerSecret'] )
        ) {
            header( "HTTP/1.1 501 Internal Server Error" );
            echo 'Required configuration directives not found in ini file';
            exit(0);
        }
        
        $_SESSION['userAgent'] = $ini['consumer'];
        $this->consumerKey = $ini['consumerKey'];
        $this->consumerSecret = $ini['consumerSecret'];
        $this->OAuthURL = str_replace( "/w/api.php", "/w/index.php?title=Special:OAuth", $api );
        $this->apiURL = $api;
        $this->profile = $profile;
        
        if ( isset( $_SESSION[$this->profile.'requesttokenKey'] ) ) {
            $this->requestToken = $_SESSION[$this->profile.'requesttokenKey'];
            $this->requestTokenSecret = $_SESSION[$this->profile.'requesttokenSecret'];
        }
        if( isset( $_SESSION['loggedInUsername'] ) ) {
            if ( isset( $_SESSION[$this->profile.'accesstokenKey'] ) ) {
                $this->accessToken = $_SESSION[$this->profile.'accesstokenKey'];
                $this->accessTokenSecret = $_SESSION[$this->profile.'accesstokenSecret']; 
            }
        }
        
        //If we have a callback, it probably means the user executed the authorize function, so let's finish authorization by getting the access token.
        if ( isset( $_GET['oauth_verifier'] ) && $_GET['oauth_verifier'] ) {
            if( !$this->getAccessToken() ) return;
        }
        
        if( $this->accessToken != "" && $this->accessTokenSecret != "" ) {
            if( $this->identify() ) $this->OAuthSuccess = true;
        }
    }
    
    public function Authorize() {
        
        //reqeust a request token
        if( !$this->getRequestToken() ) {
            return false;
        }
        // Then we send the user off to authorize
        $url = $this->OAuthURL . '/authorize';
        $url .= strpos( $url, '?' ) ? '&' : '?';
        $url .= http_build_query( array(
            'oauth_token' => $this->requestToken,
            'oauth_consumer_key' => $this->consumerKey,
        ) );
        header( "Location: $url" );
        return true;
    }
    
    public function getRequestToken() {
        $this->requestTokenSecret = '';
        $url = $this->OAuthURL . '/initiate';
        $url .= strpos( $url, '?' ) ? '&' : '?';
        $url .= http_build_query( array(
                                        'format' => 'json',
                                        
                                        // OAuth information
                                        'oauth_callback' => 'oob', // Must be "oob" for MWOAuth
                                        'oauth_consumer_key' => $this->consumerKey,
                                        'oauth_version' => '1.0',
                                        'oauth_nonce' => md5( microtime() . mt_rand() ),
                                        'oauth_timestamp' => time(),

                                        // We're using secret key signatures here.
                                        'oauth_signature_method' => 'HMAC-SHA1',
                                    ) );
        $signature = $this->generateSignature( 'GET', $url );
        $url .= "&oauth_signature=" . urlencode( $signature );
        $ch = curl_init();
        curl_setopt( $ch, CURLOPT_URL, $url );
        curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, false );
        curl_setopt( $ch, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT'] );
        curl_setopt( $ch, CURLOPT_HEADER, 0 );
        curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1 );
        $data = curl_exec( $ch );
        if ( !$data ) {
            $this->OAuthErrorMessage = 'Curl error: ' . htmlspecialchars( curl_error( $ch ) );
            return false;
        }
        curl_close( $ch );
        $token = json_decode( $data );
        if ( is_object( $token ) && isset( $token->error ) ) {
            $this->OAuthErrorMessage = 'Error retrieving token: ' . htmlspecialchars( $token->error );
            $this->killOAuthSession();
            return false;
        }
        if ( !is_object( $token ) || !isset( $token->key ) || !isset( $token->secret ) ) {
            $this->OAuthErrorMessage = 'Invalid response from token request\n<br>'.$data;
            return false;
        }

        // Now we have the request token, we need to save it for later.
        $_SESSION[$this->profile.'requesttokenKey'] = ($this->requestToken = $token->key);
        $_SESSION[$this->profile.'requesttokenSecret'] = ($this->requestTokenSecret = $token->secret);
        
        return true;
    }
    
    public function getAccessToken() {
        $this->accessTokenSecret = '';
        $url = $this->OAuthURL . '/token';
        $url .= strpos( $url, '?' ) ? '&' : '?';
        $url .= http_build_query( array(
                                        'format' => 'json',
                                        'oauth_verifier' => $_GET['oauth_verifier'],

                                        // OAuth information
                                        'oauth_consumer_key' => $this->consumerKey,
                                        'oauth_token' => $this->requestToken,
                                        'oauth_version' => '1.0',
                                        'oauth_nonce' => md5( microtime() . mt_rand() ),
                                        'oauth_timestamp' => time(),

                                        // We're using secret key signatures here.
                                        'oauth_signature_method' => 'HMAC-SHA1',
                                    ) );
        $signature = $this->generateSignature( 'GET', $url );
        $url .= "&oauth_signature=" . urlencode( $signature );
        $ch = curl_init();
        curl_setopt( $ch, CURLOPT_URL, $url );
        curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, false );
        curl_setopt( $ch, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT'] );
        curl_setopt( $ch, CURLOPT_HEADER, 0 );
        curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1 );
        $data = curl_exec( $ch );
        
        if ( !$data ) {
            $this->OAuthErrorMessage = 'Curl error: ' . htmlspecialchars( curl_error( $ch ) );
            return false;
        }
        curl_close( $ch );
        $token = json_decode( $data );
        if ( is_object( $token ) && isset( $token->error ) ) {
            $this->OAuthErrorMessage = 'Error retrieving token: ' . htmlspecialchars( $token->error );
            $this->killOAuthSession();
            return false;
        }
        if ( !is_object( $token ) || !isset( $token->key ) || !isset( $token->secret ) ) {
            $this->OAuthErrorMessage = 'Invalid response from token request';
            return false;
        }

        // Save the access token
        $_SESSION[$this->profile.'accesstokenKey'] = $this->accessToken = $token->key;
        $_SESSION[$this->profile.'accesstokenSecret'] = $this->accessTokenSecret = $token->secret;
        
        return true;
    }
    
    private function generateSignature( $method, $url, $params = array() ) {
        $parts = parse_url( $url );

        // We need to normalize the endpoint URL
        $scheme = isset( $parts['scheme'] ) ? $parts['scheme'] : 'http';
        $host = isset( $parts['host'] ) ? $parts['host'] : '';
        $port = isset( $parts['port'] ) ? $parts['port'] : ( $scheme == 'https' ? '443' : '80' );
        $path = isset( $parts['path'] ) ? $parts['path'] : '';
        if ( ( $scheme == 'https' && $port != '443' ) ||
            ( $scheme == 'http' && $port != '80' ) 
        ) {
            // Only include the port if it's not the default
            $host = "$host:$port";
        }

        // Also the parameters
        $pairs = array();
        parse_str( isset( $parts['query'] ) ? $parts['query'] : '', $query );
        $query += $params;
        unset( $query['oauth_signature'] );
        if ( $query ) {
            $query = array_combine(
                // rawurlencode follows RFC 3986 since PHP 5.3
                array_map( 'rawurlencode', array_keys( $query ) ),
                array_map( 'rawurlencode', array_values( $query ) )
            );
            ksort( $query, SORT_STRING );
            foreach ( $query as $k => $v ) {
                $pairs[] = "$k=$v";
            }
        }

        $toSign = rawurlencode( strtoupper( $method ) ) . '&' .
            rawurlencode( "$scheme://$host$path" ) . '&' .
            rawurlencode( join( '&', $pairs ) );
        $key = rawurlencode( $this->consumerSecret ) . '&' . rawurlencode( ( $this->accessTokenSecret != "" ? $this->accessTokenSecret : $this->requestTokenSecret ) );
        return base64_encode( hash_hmac( 'sha1', $toSign, $key, true ) );
    }
    
    public function identify() {
        $url = $this->OAuthURL . '/identify';

        $headerArr = array(
                            // OAuth information
                            'oauth_consumer_key' => $this->consumerKey,
                            'oauth_token' => $this->accessToken,
                            'oauth_version' => '1.0',
                            'oauth_nonce' => md5( microtime() . mt_rand() ),
                            'oauth_timestamp' => time(),

                            // We're using secret key signatures here.
                            'oauth_signature_method' => 'HMAC-SHA1',
                        );
        $signature = $this->generateSignature( 'GET', $url, $headerArr );
        $headerArr['oauth_signature'] = $signature;

        $header = array();
        foreach ( $headerArr as $k => $v ) {
            $header[] = rawurlencode( $k ) . '="' . rawurlencode( $v ) . '"';
        }
        $header = 'Authorization: OAuth ' . join( ', ', $header );

        $ch = curl_init();
        curl_setopt( $ch, CURLOPT_URL, $url );
        curl_setopt( $ch, CURLOPT_HTTPHEADER, array( $header ) );
        curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, false );
        curl_setopt( $ch, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT'] );
        curl_setopt( $ch, CURLOPT_HEADER, 0 );
        curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1 );
        $data = curl_exec( $ch );
        if ( !$data ) {
            $this->OAuthErrorMessage = 'Curl error: ' . htmlspecialchars( curl_error( $ch ) );
            return false;
        }
        $err = json_decode( $data );
        if ( is_object( $err ) && isset( $err->error ) && $err->error === 'mwoauthdatastore-access-token-not-found' ) {
            // We're not authorized!
            $this->OAuthErrorMessage = "Missing authorization or authorization failed";
            $this->killOAuthSession();
            return false;
        }

        // There are three fields in the response
        $fields = explode( '.', $data );
        if ( count( $fields ) !== 3 ) {
            $this->OAuthErrorMessage = 'Invalid identify response: ' . htmlspecialchars( $data );
            $this->killOAuthSession();
            return false;
        }

        // Validate the header. MWOAuth always returns alg "HS256".
        $header = base64_decode( strtr( $fields[0], '-_', '+/' ), true );
        if ( $header !== false ) {
            $header = json_decode( $header );
        }
        if ( !is_object( $header ) || $header->typ !== 'JWT' || $header->alg !== 'HS256' ) {
            $this->OAuthErrorMessage = 'Invalid header in identify response: ' . htmlspecialchars( $data );
            $this->killOAuthSession();
            return false;
        }

        // Verify the signature
        $sig = base64_decode( strtr( $fields[2], '-_', '+/' ), true );
        $check = hash_hmac( 'sha256', $fields[0] . '.' . $fields[1], $this->consumerSecret, true );
        if ( $sig !== $check ) {
            $this->OAuthErrorMessage = 'JWT signature validation failed: ' . htmlspecialchars( $data );
            $this->killOAuthSession();
            return false;
        }

        // Decode the payload
        $payload = base64_decode( strtr( $fields[1], '-_', '+/' ), true );
        if ( $payload !== false ) {
            $payload = json_decode( $payload );
        }
        if ( !is_object( $payload ) ) {
            $this->OAuthErrorMessage = 'Invalid payload in identify response: ' . htmlspecialchars( $data );
            $this->killOAuthSession();
            return false;
        }
        
        $this->identifiedUsername = $payload->username;
        $_SESSION['loggedInUsername'] = $payload->username;

        return true;
    }
    
    public function edit( $page, $text, $summary = "", $minor = false, $bot = true, $pend = "", $create = "never", $newsection = false, $sectiontitle = null ) {

        $ch = null;

        // First fetch the username
        $res = $this->doApiQuery( array(
            'format' => 'json',
            'action' => 'query',
            'meta' => 'userinfo',
            'prop' => 'info',
            'titles' => $page
        ), $ch );
        
        if( !$res ) return false;

        if ( isset( $res->error->code ) && $res->error->code === 'mwoauth-invalid-authorization' ) {
            // We're not authorized!
            $this->OAuthErrorMessage = "Invalid Authorization";
            $this->killOAuthSession();
            return false;
        }

        if ( !isset( $res->query->userinfo ) ) {
            $this->OAuthErrorMessage = 'Bad API response: <pre>' . htmlspecialchars( var_export( $res, 1 ) ) . '</pre>';
            return false;
        }
        if ( isset( $res->query->userinfo->anon ) ) {
            $this->OAuthErrorMessage = 'Not logged in. (How did that happen?)';
            $this->killOAuthSession();
            return false;
        }
        
        foreach( $res->query->pages as $tpage ) {
            if( isset( $tpage->touched ) ) $touched = $tpage->touched;
            else $touched = "";
        }
        
        $this->identifiedUserid = $res->query->userinfo->id;

        // Next fetch the edit token
        $res = $this->doApiQuery( array(
            'format' => 'json',
            'action' => 'tokens',
            'type' => 'edit',
        ), $ch );
        
        if( !$res ) return false;
        
        if ( !isset( $res->tokens->edittoken ) ) {
            $this->OAuthErrorMessage = 'Bad API response: <pre>' . htmlspecialchars( var_export( $res, 1 ) ) . '</pre>';
            return false;
        }
        $token = $res->tokens->edittoken;
        
        $editarray = array( 'format' => 'json',
                            'action' => 'edit',
                            'title' => $page,
                            'token' => $token,
                            'text' => $text,
                            'md5' => md5( $text ),
                            'summary' => $summary,
                            'basetimestamp' => $touched );
        if( $newsection ) {
            $editarray['section'] = 'new';
            $editarray['sectiontitle'] = $sectiontitle;
        } else {
            if( $pend == "pre" ) {
                $editarray['prependtext'] = $text;
            } elseif( $pend == "ap" ) {
                $editarray['appendtext'] = $text;
            }
        }
        
        if( $bot ) $editarray['bot'] = 'yes';
        
        if( $minor ) $editarray['minor'] = 'yes';
        else $editarray['notminor'] = 'yes';
        
        if( $create == "never" ) $editarray['nocreate'] = 'yes';
        elseif( $create == "only" ) $editarray['createonly'] = 'yes';
        elseif( $create == "recreate" ) $editarray['recreate'] = 'yes';

        // Now perform the edit
        $res = $this->doApiQuery( $editarray, $ch );
        
        if( !$res ) return false;
        
        if( isset( $res->edit ) && $res->edit->result == "Success" ) return true;
        else {
            $this->OAuthErrorMessage = "Edit failure: ".json_encode( $res->error );
            return false;
        }
        
    }
    
    private function doApiQuery( $post, &$ch = null ) {

        $headerArr = array(
            // OAuth information
            'oauth_consumer_key' => $this->consumerKey,
            'oauth_token' => $this->accessToken,
            'oauth_version' => '1.0',
            'oauth_nonce' => md5( microtime() . mt_rand() ),
            'oauth_timestamp' => time(),

            // We're using secret key signatures here.
            'oauth_signature_method' => 'HMAC-SHA1',
        );
        $signature = $this->generateSignature( 'POST', $this->apiURL, $post + $headerArr );
        $headerArr['oauth_signature'] = $signature;

        $header = array();
        foreach ( $headerArr as $k => $v ) {
            $header[] = rawurlencode( $k ) . '="' . rawurlencode( $v ) . '"';
        }
        $header = 'Authorization: OAuth ' . join( ', ', $header );

        if ( !$ch ) {
            $ch = curl_init();
        }
        curl_setopt( $ch, CURLOPT_POST, true );
        curl_setopt( $ch, CURLOPT_URL, $this->apiURL );
        curl_setopt( $ch, CURLOPT_POSTFIELDS, http_build_query( $post ) );
        curl_setopt( $ch, CURLOPT_HTTPHEADER, array( $header ) );
        curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, false );
        curl_setopt( $ch, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT'] );
        curl_setopt( $ch, CURLOPT_HEADER, 0 );
        curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1 );
        $data = curl_exec( $ch );
        if ( !$data ) {
            $this->OAuthErrorMessage = 'Curl error: ' . htmlspecialchars( curl_error( $ch ) );
            return false;
        }
        $ret = json_decode( $data );
        if ( $ret === null ) {
            $this->OAuthErrorMessage = 'Unparsable API response: <pre>' . htmlspecialchars( $data ) . '</pre>';
            return false;
        }
        return $ret;
    }
    
    public function doApiMultiQuery( &$apiRequestArr ) {
        $ret = null;

        //create multiple cUrl handler
        $mh = curl_multi_init();
        
        foreach ( $apiRequestArr as $i => $apiRequest ){
            $headerArr = array(
                    // OAuth information
                    'oauth_consumer_key' => $this->consumerKey,
                    'oauth_token' => $this->accessToken,
                    'oauth_version' => '1.0',
                    'oauth_nonce' => md5( microtime() . mt_rand() ),
                    'oauth_timestamp' => time(),
        
                    // We're using secret key signatures here.
                    'oauth_signature_method' => 'HMAC-SHA1',
            );
            $signature = $this->generateSignature( 'POST', $apiRequest["apiUrl"], $apiRequest["data"] + $headerArr );
            $headerArr['oauth_signature'] = $signature;
        
            $header = array();
            foreach ( $headerArr as $k => $v ) {
                $header[] = rawurlencode( $k ) . '="' . rawurlencode( $v ) . '"';
            }
            $header = 'Authorization: OAuth ' . join( ', ', $header );
        

            $ch[$i] = curl_init();

            curl_setopt( $ch[$i], CURLOPT_POST, true );
            curl_setopt( $ch[$i], CURLOPT_URL, $apiRequest["apiUrl"] );
            curl_setopt( $ch[$i], CURLOPT_POSTFIELDS, http_build_query( $apiRequest["data"] ) );
            curl_setopt( $ch[$i], CURLOPT_HTTPHEADER, array( $header ) );
            //curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, false );
            curl_setopt( $ch[$i], CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT'] );
            curl_setopt( $ch[$i], CURLOPT_HEADER, 0 );
            curl_setopt( $ch[$i], CURLOPT_RETURNTRANSFER, 1 );
            
            curl_multi_add_handle($mh, $ch[$i]);
        }
        
        $active = null;
        // execute the handles
        do {
            $mrc = curl_multi_exec($mh, $active);
        } while ($mrc == CURLM_CALL_MULTI_PERFORM);
        
        //check for results and execute until everything is done
        while ($active && $mrc == CURLM_OK) {
            if (curl_multi_select($mh) == -1) {
                usleep(50);
            }
            
            do {
                $mrc = curl_multi_exec($mh, $active);
            } while ($mrc == CURLM_CALL_MULTI_PERFORM);
          
        }

        //fetch the results
        foreach ($apiRequestArr as $i => $apiRequest) {
            $wiki =  $apiRequestArr[$i]["wiki"];
            $res = curl_multi_getcontent($ch[$i]);
            $ret[$wiki] = json_decode( $res );
            curl_close($ch[$i]);
        }
        
        curl_multi_close($mh);
        $mh = null;

        return $ret;
    }
    
    private function killOAuthSession() {
        if( isset( $_SESSION['loggedInUsername'] ) ) unset( $_SESSION['loggedInUsername'] );
        $this->accessToken = "";
        $this->accessTokenSecret = "";
        $this->requestToken = "";
        $this->requestTokenSecret = "";
        $this->identifiedUserid = "";
        $this->identifiedUsername = "";
        $this->signature = "";
        $this->OAuthSuccess = false;
    }
    
    public function getUsername() {
        return $this->identifiedUsername;
    }
    
    public function getUserID() {
        return $this->identifiedUserid;
    }
    
    public function isAuthorized() {
        return $this->OAuthSuccess;
    }
    
    public function getError() {
        return $this->OAuthErrorMessage;
    }
    
    public function logout() {
        $this->killOAuthSession();
    }
}
?>
