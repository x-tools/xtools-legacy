<?php
echo "<!--";
require_once( '/data/project/xtools/Peachy/Init.php' );
echo "-->";
function newWebTool( $toolname = null, $smarty_name = null, $dont = array() ) {
   global $wt, $pgHTTP;
   print_r($GLOBALS);
   $wt = new WebTool( $toolname, $smarty_name, $dont );
}

class WebTool {

   function __construct( $toolname = null, $smarty_name = null, $dont = array() ) {
      global $wgRequest, $wtConfigTitle, $phptemp, $content, $time;
      
      $time = microtime( 1 );
      
      $wtConfigTitle = $smarty_name;
      
      mb_internal_encoding("utf-8"); 
      header('content-type: text/html; charset: utf-8'); 

      error_reporting(E_ALL|E_STRICT);
      ini_set("display_errors", 1);
      
      if( !in_array( 'showonlyerrors', $dont ) ) { 
         error_reporting(E_ERROR);
      }
      
      if( !in_array( 'smarty', $dont ) ) {
         require_once( "/data/project/xtools/public_html/Smarty/languages.class.php" );
         require_once( "/data/project/xtools/public_html/Smarty/Smarty.class.php" );
         self::initSmarty( $smarty_name );
      }
      
      if( !in_array( 'sitenotice', $dont ) ) {
         require_once( '/data/project/xtools/public_html/sitenotice.php' );
         self::checkSitenotice();
      }
      
      if( is_null( $wgRequest ) ) $wgRequest = new WebRequest();
      
      if( !in_array( 'getwikiinfo', $dont ) ) self::getWikiInfo();
      
      self::setDBVars();
      
      if( !in_array( 'peachy', $dont ) ) {
         self::loadPeachy();
      }
      
      if( !in_array( 'database', $dont ) ) {
         self::loadDatabase();
         
         if( !in_array( 'replag', $dont ) ) {
            $replag = self::getReplag();
   
            if ($replag[0] > 120) {
               $content->assign( 'replag', $phptemp->get_config_vars( 'highreplag', $replag[1] ) );
            }
         }
      }
      
      if( !in_array( 'addstat', $dont ) ) {
				require_once( '/data/project/xtools/stats.php' );
				addStatV3( $toolname );
      }
      
      
   }
   
   static function loadForApi( $toolname, $showonlyerrors = true, $db = true ) {
      
      mb_internal_encoding("utf-8"); 
      header('content-type: text/html; charset: utf-8'); 

      error_reporting(E_ALL|E_STRICT);
      ini_set("display_errors", 1);
      
      if( $showonlyerrors ) { 
         error_reporting(E_ERROR);
      }
      
      define( 'WEBTOOL_API_TRUE', 1 );
      
      if( !$db ) return;
      
      self::setDBVars();
      self::loadDatabase();
      
			require_once( '/data/project/xtools/stats.php' );
			addStatV3( $toolname );
      
      self::loadPeachy();
      
   }
   
   static function setMemLimit( $mb = 512 ) {
      ini_set("memory_limit", $mb . 'M' );
   }
   
   static function loadPeachy() {
      global $url, $pgVerbose, $site;
      
      $pgVerbose = array();
      /*$pgHooks['StartLogin'][] = 'fixlogin';
      function fixlogin( &$config ) {
         $config['httpecho'] = true;
      }*/
			echo "<!--";
      $site = Peachy::newWiki( null, null, null, 'http://'.$url.'/w/api.php' );
			echo "-->";
	 }
   
   static function initSmarty( $config_title ) {
      global $phptemp, $content, $language, $curlang, $langlinks;
      
      $backtrace = debug_backtrace();
      if( !isset( $backtrace[0]['file'] ) ) {
         self::toDie( "File backtrace not found." );
      }
      
      $langs = glob( dirname( $backtrace[1]['file'] ) . '/configs/*.conf' );
      $langs = array_merge( $langs, glob( dirname( $backtrace[1]['file'] ) . '/../configs/*.conf' ) );
      
      foreach( $langs as $k => $newlang ) {
         $langs[$k] = str_replace( array( dirname( $backtrace[1]['file'] ) . '/configs/', dirname( $backtrace[1]['file'] ) . '/../configs/', '.conf' ), '', $newlang );
         if( $langs[$k] == "qqq" ) unset( $langs[$k] );
      }
      
      $langs = array_unique( $langs );

      $language = new Language( $langs );
      $curlang = $language->getLang();
      
      $langlinks = $language->generateLangLinks();
      
      self::addSmartyObj( $phptemp, $config_title );
      self::addSmartyObj( $content, $config_title );
      
      $phptemp->assign( "curlang", $curlang );
      $phptemp->assign( "langlinks", $langlinks );
   }
   
   static function addSmartyObj( &$object, $config ) {
      global $curlang;
      
      $object = new Smarty();
      $object->config_load( '../../configs/en.conf', 'main' );
      $object->config_load( 'en.conf', $config );
      
      if( is_file( '../configs/' . $curlang . '.conf' ) ) $object->config_load( '../../configs/' . $curlang . '.conf', 'main' );
      if( is_file( 'configs/' . $curlang . '.conf' ) ) $object->config_load( $curlang . '.conf', $config );
      
   }
   
   static function checkSitenotice() {
      global $content;
      
      $siteNoticeClass = new siteNotice;
      $sitenotice = $siteNoticeClass->checkSiteNoticeRaw();
      if( $sitenotice ) {
         $content->assign( "alert", $sitenotice );
      }
   }
   
   static function setDBVars() {
      global $toolserver_username, $toolserver_password;

      $toolserver_mycnf = parse_ini_file("/data/project/xtools/replica.my.cnf");
      $toolserver_username = $toolserver_mycnf['user'];
      $toolserver_password = $toolserver_mycnf['password'];
      unset($toolserver_mycnf);
   }
   
   
   static function loadDatabase( $api = false ) {
      global $lang, $wiki, $url, $phptemp, $dbr, $toolserver_username, $toolserver_password;
      
      self::setDBVars();
      
      try {
         /*$dbr = new Database( 
            'sql-toolserver', 
            $toolserver_username, 
            $toolserver_password, 
            'toolserver'
         );
         
         $res = $dbr->select(
            'wiki',
            array( 'dbname', 'server', ),
            array( 'domain' => "$lang.$wiki.org" )
         );
            
         if( !count( $res ) ) {
            if( !$api ) self::toDie( $phptemp->get_config_vars( 'nowiki', $url ) );
            return array( 'error' => 'nowiki', 'info' => $url );
         }*/
         if( $wiki = 'wikipedia' || $wiki = 'wikimedia' ) $wiki = "wiki";
				 $res['server'] = $lang.$wiki.".labsdb";
				 $res['dbname'] = $lang.$wiki."_p";
				
				 if ($wiki == "wikidata") {
					$res['dbname'] = 'wikidatawiki_p';
					$res['server'] = 'wikidatawiki.labsdb';
				}
         $dbr = new Database( 
            $res['server'], 
            $toolserver_username, 
            $toolserver_password, 
            $res['dbname']
         );
      } catch( DBError $e ) {
         if( !$api ) self::toDie( $phptemp->get_config_vars( 'mysqlerror', $e->getMessage() ) );
         return array( 'error' => 'mysqlerror', 'info' => $e->getMessage() );
      }
   }
   
   static function getWikiInfo() {
      global $wgRequest, $lang, $wiki, $url;
      
      $wiki = $wgRequest->getSafeVal( 'wiki', 'wikipedia' );
      $lang = $wgRequest->getSafeVal( 'lang', 'en' );
      $url = $lang.'.'.$wiki.'.org';
   }
   
   static function toDie( $msg ) {
      global $content;
      $content->assign( "error", $msg );
      self::assignContent();
   }
   
   static function toDieMsg( $msg ) {
      global $phptemp;
      
      $text = call_user_func_array( array( $phptemp, 'get_config_vars' ), func_get_args() );
      self::toDie( $text );
   }
   
   static function pre( $array ) {
      echo "<pre>";
      print_r( $array );
      echo "</pre>";
   }
   
   static function assignContent() {
      global $phptemp, $content, $wtConfigTitle, $wtSource, $wtTranslate, $wgRequest;
      
      if( $wgRequest->getBool( 'uselang' ) ) {
         $content->assign( "uselang", $wgRequest->getSafeVal( 'uselang' ) );
      }
      
      
      $phptemp->assign( "content", $content->fetch( $wtConfigTitle . '.tpl' ) );
      
      if( !is_null( $wtSource ) ) {
         $phptemp->assign( "source2", $wtSource );
      }
      
      if( (bool) $wtTranslate ) {
         $phptemp->assign( "translate", "//tools.wmflabs.org/xtools/translate/index.php" );
      }
      
      $phptemp->display( '../../templates/mainSmarty.tpl' ); 
      die();
   }
   
   static function getTimeString( $secs ) {
      $r = implode( ', ', self::getTimeArray( $secs ) );
      return $r;
   }
   
   static function getTimeArray( $secs ) {
      global $phptemp;
      
      if( is_null( $phptemp ) ) {
         $phptemp = new FauxPHPTemp;
      }
      
      if( !$secs ) return array( '0 ' .  $phptemp->get_config_vars( 's' ) );
      
      $second = 1;
      $minute = $second * 60;
      $hour = $minute * 60;
      $day = $hour * 24;
      $week = $day * 7;
      $month = $day * ( 365 / 12 );
      
      $r = array();
      if ($secs > $month) {
         $count = 0;
         for( $i = $month; $i <= $secs; $i += $month ) {
            $count++;
         }
         
         $r[] = $count . ' ' . $phptemp->get_config_vars( 'mo' );
         $secs -= $month * $count;
      }
      if ($secs > $week) {
         $count = 0;
         for( $i = $week; $i <= $secs; $i += $week ) {
            $count++;
         }
         
         $r[] = $count . ' ' . $phptemp->get_config_vars( 'w' );
         $secs -= $week * $count;
      }
      if ($secs > $day) {
         $count = 0;
         for( $i = $day; $i <= $secs; $i += $day ) {
            $count++;
         }
         
         $r[] = $count . ' ' . $phptemp->get_config_vars( 'd' );
         $secs -= $day * $count;
      }
      if ($secs > $hour) {
         $count = 0;
         for( $i = $hour; $i <= $secs; $i += $hour ) {
            $count++;
         }
         
         $r[] = $count . ' ' . $phptemp->get_config_vars( 'h' );
         $secs -= $hour * $count;
      }
      if ($secs > $minute) {
         $count = 0;
         for( $i = $minute; $i <= $secs; $i += $minute ) {
            $count++;
         }
         
         $r[] = $count . ' ' . $phptemp->get_config_vars( 'm' );
         $secs -= $minute * $count;
      }
      if ($secs) {
         $r[] = $secs . ' ' . $phptemp->get_config_vars( 's' );
      }
      
      return $r;
   }
   
   static function getReplag( $conn = null ) {
      
      
      if( is_null( $conn ) ) {
         global $dbr;
         $conn = &$dbr;
      }
      
      $res = $conn->select(
         'recentchanges',
         'UNIX_TIMESTAMP() - UNIX_TIMESTAMP(rc_timestamp) AS replag',
         null,
         array( 
            'ORDER BY' => 'rc_timestamp DESC',
            'LIMIT' => 1
         )
      );
      
      $seconds = floor( $res[0]['replag'] );
      $text = self::getTimeString( $seconds );
      
      return array( $seconds, $text );
      
   }
   
   static function finishScript() {
      global $time, $phptemp;
      
      $exectime = number_format(microtime( 1 ) - $time, 2, '.', '');
      $phptemp->assign( "excecutedtime", $phptemp->get_config_vars( 'executed', $exectime ) );
      self::assignContent();
   }
   
   static function prettyTitle( $s, $capital = false ) {
      $name = trim( str_replace( array('&#39;','%20'), array('\'',' '), $s ) );
      $name = urldecode($name);
      $name = str_replace('_', ' ', $name);
      $name = str_replace('/', '', $name);
      
      if( $capital ) $name = ucfirst( $name );
      
      return $name;
   }
   
   static function isIP( $name ) {
      return (bool) ( long2ip(ip2long($name)) == $name );
   }
   
   static function number_format( $number, $decimal = 2 ) {
      global $phptemp;
      
      if( method_exists( $phptemp, 'get_config_vars' ) ) {
         return number_format( $number, $decimal, $phptemp->get_config_vars( 'decimal_separator' ), $phptemp->get_config_vars( 'thousands_separator' ) );
      }
      else {
         return number_format( $number, $decimal );
      }
   }
   
}

class FauxPHPTemp {
   function get_config_vars( $var ) {
      return $var;
   }
}
