<?php
DEFINE('STARTTIME', microtime(True) );
DEFINE('STARTMEM', memory_get_usage(true) );

DEFINE('PEACHY_BASE_SYS_DIR', '/data/project/xtools' );
DEFINE('XTOOLS_BASE_SYS_DIR', '/data/project/xtools' );
DEFINE('XTOOLS_BASE_SYS_DIR_DB', '/data/project/xtools' );
DEFINE('XTOOLS_BASE_SYS_DIR_SESSION', '/data/project/xtools' );
DEFINE('XTOOLS_BASE_WEB_DIR', 'tools.wmflabs.org/xtools' );
DEFINE('XTOOLS_I18_TEXTFILE', '/data/project/xtools/modules/Xtools.i18n.php'); 
DEFINE('XTOOLS_REDIS_FLUSH_TOKEN', 'x000006');
DEFINE('XTOOLS_LONG_QUEUE_COUNT', 'longQueueCount');
DEFINE('XTOOLS_DATABASE_TMP', 's51187__xtools_tmp');
DEFINE('XTOOLS_LONG_QUEUE_LIMIT', 6 );

set_include_path( get_include_path() . PATH_SEPARATOR . XTOOLS_BASE_SYS_DIR . '/modules' . PATH_SEPARATOR . XTOOLS_BASE_SYS_DIR . '/public_html');

$perflog = new Perflog();

// Incudes
   require_once('/data/project/intuition/src/Intuition/ToolStart.php');
   require_once('WebRequest.php');
#  require_once( PEACHY_BASE_SYS_DIR . '/Peachy/Init.php' );
   require_once('OAuth.php');
   require_once('Forms.php');

/**
 * Main class for all xtools subtools
 * Requires the following Peachy classes (or equivalent):
 * HTTP, WebRequest, Wiki
 * @author Hedonil
 *
 */
class WebTool {
   
   public $moreheader;
   public $moreScript;

   public $sitenotice;
   public $toolnotice;
   public $alert;
   public $error;
   public $replag;
   
   public $toolConfigTitle;
   public $toolTitle;
   public $toolDesc;    //description
   
   public $content;
   
   public $sourcecode;
   public $bugreport;
   public $executed;
   public $memused;
   
   public $i18Langs;
   public $uselang;
   public $translateMsg;
   public $langLinks;
   public $langPromoLinks;
   public $statusLink;
   
   public $loggedIn;
   public $loggedInUsername;
   public $OAuthObject;
   
   public $wikiInfo;
   public $userInfo;
   public $namespaces = array();
   public $metap = array();
   
   public $curlChannel = null;
   private $curlUserAgent = 'Xtools/2.0 (https://tools.wmflabs.org/xtools/; Hedonil/Cyberpower678)';
   
   private $numberFormatter;
   private $dateFormatter;
   private $dateFormatterDate;
   private $dateFormatterTime;
   private $mOutput;
   public $debug;
   public $active = array("ec"=>"", "articleinfo"=>"", "pages"=>"", "topedits"=>"", "autoedits"=>"", "blame"=>"", "rangecontribs"=>"", "autoblock"=>"", "rfa"=>"", "rfap"=>"", "bash"=>"", "sc"=>"" , "adminstats" =>"");
   public $viewStyle = "old";
   
   public $linkOauthHelp = '<a class="alert-link" href="//www.mediawiki.org/wiki/Help:OAuth" >Help:OAuth</a>';
   
   function __construct( $viewtitle = null, $configtitle = null, $options = array() ) {
      global $wgRequest, $dbr, $dbrtools, $I18N, $redis;
      
      //temporary
      if ( !$configtitle ){
         $configtitle = $viewtitle;
      }
      
      //Init redis caching support
      $redis = $this->initRedis();
      
      $this->checkSpider();
      
      //Start session
      session_save_path(XTOOLS_BASE_SYS_DIR_SESSION.'/tmp/session');
      ini_set('session.gc_probability', 1);
      session_cache_limiter("public");
      session_cache_expire(30);
      $lifetime = 15552000;
      $path = preg_replace('/^(\/.*\/).*/', '\1', dirname($_SERVER['SCRIPT_NAME']) );
      session_name( 'xtools' );
      session_set_cookie_params ( $lifetime, $path, ".tools.wmflabs.org"); 
      if ( !@session_start() ) {
          session_regenerate_id();
          session_start();
      }
      

      //Init webRequest object
      $wgRequest = new WebRequest();
      
      /*if( XTOOLS_BASE_SYS_DIR != XTOOLS_BASE_SYS_DIR_DB ) {
          $_SESSION['forcesessionretrieval'] = true;
      } else {
          $_SESSION['forcesessionretrieval'] = false;
      }
      if( $_SESSION['forcesessionretrieval'] === TRUE && !isset( $_GET['h'] ) && !isset( $_GET['hash'] ) ) {
          header( "Location: https://".XTOOLS_BASE_WEB_DIR."/oauthredirector.php?action=getsession&returnto=".$wgRequest->getFullRequestURL() );
          die();
      }
      if( isset( $_GET['hash'] ) ) {
          if( $tmp = unserialize(base64_decode( $_GET['hash'] )) !== FALSE ) $_SESSION = $tmp;
          session_write_close();
          unset( $_GET['hash'] );
          header( "Location: {$wgRequest->getServer()}".$_SERVER['SCRIPT_NAME']."?".implode("&",$_GET)."&h" );
          die();
      }*/
      
      //Init permanent connection to tools-db
      $dbrtools = $this->loadDatabase(null, null, "tools");
      
      //Init I18n language support
      $I18N = $this->initI18N();
      $this->checkLocale();
      $I18N->setLang( $this->uselang );
      $this->generateLangLinks();
      //Init I18 specific number/date formatter
      $this->initFormatterAndStrings();
      
      //get db metatdata
      $this->getMetapData();
      
      $this->toolConfigTitle = $configtitle;
      $this->toolTitle = $I18N->msg( 'tool_'.$this->toolConfigTitle );
      $this->toolDesc  = $I18N->msg( 'tool_'.$this->toolConfigTitle.'_desc' );
      $this->active[ $this->toolConfigTitle ] = "active";

      
      mb_internal_encoding("utf-8");
      
      $this->sitenotice = '
            &#9733;&nbsp; Try: <a class="alert-link" href="//meta.wikimedia.org/wiki/User:Hedonil/XTools" >XTools gadget</a>. It\'s fast. Enjoy!&nbsp;&bull;&nbsp;
            &nbsp;&bull;&nbsp;#Featured: <a class="alert-link" href="https://tools.wmflabs.org/directory/?view=web" >Directory NG</a>  &#9733;
         '; 
      $this->alert = '';
      #Now with cross-wiki notifications <sup style=color:green; font-style:italic">beta</sup>
      #$xnotice["adminstats"] = 'Please note: Default behaviour has changed. To autorun with default of 100 days, please set both parameters: <i>project</i> and <i>begin</i>. Eg.: ?project=enwiki<b>&begin=default</b>';
      $this->toolnotice = isset( $xnotice[$configtitle] ) ? $xnotice[$configtitle] : '';
      
      $this->getWikiInfo();
      $this->OAuthObject = new OAuth2(( !empty($this->getWikiInfo()->domain) ? "https://".$this->getWikiInfo()->domain."/w/api.php" : "https://www.mediawiki.org/w/api.php") );
      $this->checkLoginMessagesStatus();
      
      
      
      if ( !$this->wikiInfo->error ){
         $_SESSION[ "wikiinfo_". $this->toolConfigTitle ] = $this->wikiInfo;
         $_SESSION[ "wikiinfo_last"] = $this->wikiInfo;
      }
   }
   
   function initFormatterAndStrings(){
      global $I18N, $wgRequest;
      
      $this->numberFormatter   = new NumberFormatter( $this->uselang, NumberFormatter::DECIMAL);
      $this->dateFormatter     = new IntlDateFormatter( $this->uselang, IntlDateFormatter::MEDIUM, IntlDateFormatter::MEDIUM, "UTC", IntlDateFormatter::GREGORIAN);
      $this->dateFormatterDate = new IntlDateFormatter( $this->uselang, IntlDateFormatter::MEDIUM, IntlDateFormatter::NONE, "UTC", IntlDateFormatter::GREGORIAN);
      $this->dateFormatterTime = new IntlDateFormatter( $this->uselang, IntlDateFormatter::NONE, IntlDateFormatter::SHORT, "UTC", IntlDateFormatter::GREGORIAN);
   
      $this->sourcecode = '<a href="//github.com/x-Tools/xtools/" >'.$I18N->msg('source').'</a> &bull; ';
      $this->bugreport = '<a href="//github.com/x-Tools/xtools/issues" >'.$I18N->msg('bugs').'</a> &bull; ';
      
      
      if ( $_SERVER["HTTP_USER_AGENT"] == "TestDebug Hedo" && $wgRequest->getVal( 'debug' ) == "1" ){
         $_SESSION["debug"] = true;
      }
      if ( $_SERVER["HTTP_USER_AGENT"] != "TestDebug Hedo" || $wgRequest->getVal( 'debug' ) == "0" ){
         $_SESSION["debug"] = false;
      }
      $this->debug = $wgRequest->getSessionData( 'debug' );
      
      
      if ( $wgRequest->getVal( 'style' ) == "new" ){
         $_SESSION["viewStyle"] = 'new';
      }
      if ( $wgRequest->getVal( 'style' ) == "old" ){
         $_SESSION["viewStyle"] = 'old';
      }
      $this->viewStyle = isset($_SESSION["viewStyle"]) ? $wgRequest->getSessionData( 'viewStyle' ) : "new" ;
      
   }
   
   function getWikiInfo( $lang=null, $wiki=null, $project=null, $setwiki=true ) {
      global $wgRequest, $perflog, $redis;
      
      $imgbase = "//tools.wmflabs.org/xtools/static/images/flags/png/";
      
      $icon = new stdClass();
         $icon->watchers = "//upload.wikimedia.org/wikipedia/commons/6/68/Eye_open_font_awesome.svg";
         $icon->lang = "//upload.wikimedia.org/wikipedia/commons/b/bd/Gray_flag_waving.png";
         $icon->featured = "//upload.wikimedia.org/wikipedia/commons/e/e7/Cscr-featured.svg";
         $icon->project = "//upload.wikimedia.org/wikipedia/commons/8/81/Wikimedia-logo.svg";
         $icon->wikibooks = "//upload.wikimedia.org/wikipedia/commons/f/fa/Wikibooks-logo.svg";
         $icon->wiktionary = "//upload.wikimedia.org/wikipedia/commons/e/ef/Wikitionary.svg";
         $icon->wikiquote = "//upload.wikimedia.org/wikipedia/commons/f/fa/Wikiquote-logo.svg";
         $icon->wikipedia = "//upload.wikimedia.org/wikipedia/commons/8/80/Wikipedia-logo-v2.svg";
         $icon->wikinews = "//upload.wikimedia.org/wikipedia/commons/2/24/Wikinews-logo.svg";
         $icon->wikivoyage = "//upload.wikimedia.org/wikipedia/commons/8/8a/Wikivoyage-logo.svg";
         $icon->wikisource = "//upload.wikimedia.org/wikipedia/commons/4/4c/Wikisource-logo.svg";
         $icon->wikiversity = "//upload.wikimedia.org/wikipedia/commons/9/91/Wikiversity-logo.svg";
         $icon->mobile = "//upload.wikimedia.org/wikipedia/commons/7/73/Cell_phone_icon.svg";
         $icon->foundation = "//upload.wikimedia.org/wikipedia/commons/c/c4/Wikimedia_Foundation_RGB_logo_with_text.svg";
         $icon->mediawiki = "//upload.wikimedia.org/wikipedia/commons/3/3d/Mediawiki-logo.png";
         $icon->metawiki = "//upload.wikimedia.org/wikipedia/commons/7/75/Wikimedia_Community_Logo.svg";
         $icon->wikidatawiki = "//upload.wikimedia.org/wikipedia/commons/f/ff/Wikidata-logo.svg";
         $icon->commonswiki = "//upload.wikimedia.org/wikipedia/commons/4/4a/Commons-logo.svg";
         $icon->blank = "";
      
      $obj = new stdClass();
         $obj->lang = null;
         $obj->wiki = null;
         $obj->domain = null;
         $obj->database = null;
         $obj->error = false;
         $obj->direction = 'ltr';
         $obj->imglang = Null;
         $obj->imgwiki = Null;
         $obj->rlm = "";
         
      if ( (!$lang || !$wiki) && !$project ){
         $lang = strtolower( $wgRequest->getVal( 'lang') );
         $wiki = strtolower( $wgRequest->getVal( 'wiki') );
         $project = strtolower( $wgRequest->getVal( 'project') );
      }
      if ( (!$lang || !$wiki) && !$project ){
         //compatibility wikixxxx
         $lang = strtolower( $wgRequest->getVal( 'wikilang', $lang) );
         $wiki = strtolower( $wgRequest->getVal( 'wikifam', $project) );
      }
      if ( (!$lang || !$wiki) && !$project ){
         $obj->error = 'no wiki specified';
         if ($setwiki) { $this->wikiInfo = $obj; }
         return $obj;
      }

      $combo = ( $project ) ? $project : $lang.$wiki;
      $combo = str_replace( array('http:', 'https:', '//', '/', '.', 'org'), array('','','','','',''), $combo );

      if ( strpos( $project, '.org' ) !== false ) {
         $domain = str_replace( array('http:', 'https:', '/'), array('','',''), $project );
      } elseif ( preg_match( '/(?:wiki$|wikipedia)/', $combo , $matches ) ){
         $domain = str_replace( $matches[0], '', $combo ) . '.wikipedia.org';
      } elseif ( preg_match( '/(?:wikisource|wikibooks|wiktionary|wikinews|wikiquote|wikiversity|wikivoyage)/', $combo , $matches )  ){
         $domain = str_replace( $matches[0], '', $combo ) . '.' . $matches[0] . '.org';
      } elseif ( preg_match( '/wikidata/', $combo) ) {
         $domain = 'www.wikidata.org';
      } elseif ( preg_match( '/commons/', $combo) ) {
         $domain = 'commons.wikimedia.org';
      } elseif ( preg_match( '/mediawiki/', $combo) ) {
         $domain = 'www.mediawiki.org';
      } elseif ( preg_match( '/meta/', $combo) ) {
         $domain = 'meta.wikimedia.org';
      } elseif ( preg_match( '/foundation/', $combo) ) {
         $domain = 'wikimediafoundation.org';
      } elseif ( preg_match( '/outreach/', $combo) ) {
         $domain = 'outreach.wikimedia.org';
      } elseif ( preg_match( '/incubator/', $combo) ) {
         $domain = 'incubator.wikimedia.org';
      }

      $ttl = 604800;
      $hash = "xtoolsWikiInfo".$domain.XTOOLS_REDIS_FLUSH_TOKEN;
      $res = $redis->get($hash);
      if ( $res === false ) {
         $data = array(
            'action' => 'query',
            'meta' => 'siteinfo',
            'format' => 'json',
         );
         $res = json_decode( $this->gethttp( "https://$domain/w/api.php?" . http_build_query( $data ) ) )->query->general;
         if ( is_object( $res ) ) {
            $redis->setex( $hash, $ttl, serialize( $res ) );
         }
      } else {
         $res = unserialize( $res );
      }
      $obj->lang = substr( $res->servername, 0, strcspn( $res->servername, '.' ) );
      $obj->domain = $res->servername;
      $obj->database = $res->wikiid;
      $obj->imgwiki = $res->favicon;
      $obj->imglang = $imgbase.$obj->lang.".png";
      preg_match( '/([^.]+)\.org$/', $domain, $matches );
      $obj->wiki = $matches[1];
      if ( isset( $obj->rtl ) ) {
         $obj->rlm = "&rlm;";
         $obj->direction = 'rtl';
      }

      if ($setwiki) { $this->wikiInfo = $obj; }
      
      $this->getNamespaces( $obj->domain );
      
      return $obj;
   }
   
   public function getNamespaces( $domain, $retval=false ){
      global $redis, $I18N, $perflog;
      
      if ( !$domain ){ return; }
      $tmpNamespaces = array();
      
      $ttl = 604800;
      $hash = "xtoolsNamespaces".$domain.XTOOLS_REDIS_FLUSH_TOKEN;
      $lc = $redis->get($hash);
      
      if ($lc === false){
         
	 $data = array(
	    'action' => 'query',
	    'meta' => 'allmessages',
	    'ammessages' => 'blanknamespace',
	    'uselang' => 'content',
	    'format' => 'json',
	 );

	 $res = json_decode( $this->gethttp( "https://$domain/w/api.php?" . http_build_query( $data ) ) )->query->allmessages;
	 $ns0 = $res[0]->{'*'};

         $data = array(
            'action' => 'query',
            'meta' => 'siteinfo',
            'format' => 'json',
            'siprop' => 'namespaces',
         );

         $res = json_decode( $this->gethttp( "https://$domain/w/api.php?" . http_build_query( $data ) ) )->query->namespaces;

         foreach( $res as $id => $ns ) {
            $nsname = $ns->{'*'} == '' ? $ns0 : $ns->{'*'};
            $tmpNamespaces['ids'][$nsname] = $id;
            $tmpNamespaces['names'][$id] =  $nsname;
         }
         
         if ( is_object( $res ) ){
            $redis->setex( $hash, $ttl, serialize( $tmpNamespaces ) );
            $perflog->stack[] = "NS domain: $domain - stored redis";
         }
         unset($res, $http );
      }
      else{
         $redis->expire( $hash, $ttl );
         $tmpNamespaces = unserialize( $lc );
         unset( $lc );
         #$perflog->stack[] = "NS domain: $domain - from redis";
      }
      
      if( $retval ){
         return $tmpNamespaces;
      }
      else{
         $this->namespaces = $tmpNamespaces;
         unset($tmpNamespaces);
         return ;
      }
      
   }
   
   private function getMetapData(){
      global $redis, $perflog;
      
      $ttl = 604800;
      $hash = "xtoolsMetapData"."004".XTOOLS_REDIS_FLUSH_TOKEN;
      $lc = $redis->get($hash);
      
      if ($lc === false){
            
         $dbr = $this->loadDatabase("en", "wikipedia");
         $res = $dbr->query("
                  SELECT dbname, url, slice, family, lang
                  FROM meta_p.wiki
               ");
            
         foreach( $res as $i => $row ) {
            $this->metap[ $row["dbname"] ] = array(
                  "domain" => str_replace("https://", "", $row["url"] ),
                  "slice" => $row["slice"],
                  "family" => $row["family"],
                  "lang" => $row["lang"]
               );
         }
         $dbr->close();
            
         if ( count($this->metap) > 0  ){
            $redis->setex( $hash, $ttl, serialize( $this->metap ) );
         }
         unset($res, $dbr );
      }
      else{
         $this->metap = unserialize( $lc );
         unset( $lc );
         //$perflog->add('getMetapRedis', 0, $this->metap);
      }
      
   }
   
   function getUserInfo( $lang=null, $wiki=null, $username=null, $dbcheck=true){
      global $wgRequest, $perflog;
      
      $ui = new stdClass();
         $ui->user = null;
         $ui->userUrl = null;
         $ui->userDb = null;
         $ui->isIP = false;
         $ui->userid = null;
         $ui->editcount = 0;
      
      if ( !$username ){
         //check both variants user & name
         $username = $wgRequest->getVal('user');
         $username = $wgRequest->getVal('name', $username );
      }
      if ( !$username ){
         return $ui;
      }
      
      if ( !$lang || !$wiki ){
         $wi = $this->wikiInfo;
            $lang = $wi->lang;
            $wiki = $wi->wiki;
      }
      if ( !$lang || !$wiki ){
         $this->toDie( 'nowiki', "wt::getUserInfo");
         //return $ui;
      }

      if ( !(@inet_pton( $username ) === false) ){
         $ui->isIP = true;
         $ui->user = strtoupper($username);
         $ui->userDb = $ui->user;
         $ui->userUrl = $ui->user;
      }
      else{
         $username = str_replace( "_", " ", ucfirst( trim ( urldecode(  $username ) ) ) );
         $ui->user = $username;
         $ui->userUrl = rawurlencode( $username );
      }
      
      if ( !$dbcheck ){ return $ui; }
      
      
      if ( $ui->isIP  ){
         $ui->isIP = true;
         $ui->userid = 0;
      }
      else{
         
         $dbr2 = $this->loadDatabase( $lang, $wiki );
         
         $ui->userDb = $dbr2->strencode( $username );
         $query = "
               SELECT user_id, user_editcount
               FROM user
               WHERE user_name = '$ui->userDb';
            ";
         
         $result = $dbr2->query( $query );
         $dbr2->close();
         
         if( isset($result[0]["user_id"]) ){
            $ui->userid = (int)$result[0]["user_id"];
            $ui->editcount = (int)$result[0]["user_editcount"];
         }
         else{
            $this->toDie( 'nosuchuser', $username." (wt::getUserInfo)");
         }
      }
      
      $this->userInfo = $ui;
      $perflog->add('ui_check', 0, $ui );
      return $ui;
   }
   
   function checkLoginMessagesStatus(){
      global $wgRequest, $I18N, $perflog;
      
      $this->statusLink['logo'] = '<a href="//'.XTOOLS_BASE_WEB_DIR.'" >X!\'s Tools</a>';
      $this->statusLink['loginout'] = '<span class="login" ><a href="https://'.XTOOLS_BASE_WEB_DIR.'/oauthredirector.php?action=login&callto='.(!empty($this->getWikiInfo()->domain) ? "https://".$this->getWikiInfo()->domain."/w/api.php" : "https://www.mediawiki.org/w/api.php").'&returnto='.$wgRequest->getFullRequestURL().'" >'.$I18N->msg('login').'</a></span>';
      $this->statusLink['agentconfig'] = '<a title="XAgent configuration" href="//'.XTOOLS_BASE_WEB_DIR.'/agent/config.php"><img style="vertical-align:baseline" src="//'.XTOOLS_BASE_WEB_DIR.'/static/images/Blue_Fedora_hat_12.png" />{$linktext}</a>&nbsp;&nbsp;';
      $this->statusLink['echo'] = '<a title="Your echo notifications from 800+ wikis" href="//'.XTOOLS_BASE_WEB_DIR.'/echo/"><img style="vertical-align:bottom;padding-right:4px;" src="//'.XTOOLS_BASE_WEB_DIR.'/static/images/Echo_Icon_18.png" />XEcho</a>&nbsp;&nbsp;';
      $this->loggedIn = false;
      $this->loggedInUsername = null;
      
      if ( $this->OAuthObject->isAuthorized() ){
         
         if ( $this->OAuthObject->getUsername() ){
            $this->loggedIn = true;
            $this->statusLink['loginout'] = '<span class="login" ><span style="margin-right:10px;">'.$this->OAuthObject->getUsername().'</span><a href="https://'.XTOOLS_BASE_WEB_DIR.'/oauthredirector.php?action=logout&callto='.(!empty($this->getWikiInfo()->domain) ? "https://".$this->getWikiInfo()->domain."/w/api.php" : "https://www.mediawiki.org/w/api.php").'&returnto='.$wgRequest->getFullRequestURL().'" >'.$I18N->msg('logout').'</a></span>';
            $this->loggedInUsername = $this->OAuthObject->getUsername();
         }
         else{
            $this->loggedInUsername = $this->OAuthObject->getUsername();
            
         }
         $_SESSION["cacheCtrl"] = "nocacheOnce";
      }
      
      //&& $this->loggedInUsername == "Hedonil"
      $this->statusLink['message'] = '';
      if ( $this->loggedIn && $this->toolConfigTitle != "echo" ){
         require_once( 'Agent.php');
         $this->statusLink['message'] = getCrossWikiMessage( $this );
      }
   }
   

   
   function checkLocale(){
      global $wgRequest, $I18N;

      $this->uselang = "en";
      
      //1. check browser lang
      if ( $bl = array_keys( $wgRequest->getAcceptLang() ) ){
         $bl = explode("-", $bl[0]);
         if ( $bl[0] == "no") { $bl[0] = "nb"; }
         if ( array_key_exists( $bl[0], $this->i18Langs ) ) {
            $this->uselang = $bl[0];
         }
      }
      
      //2. check session
      $sl = $wgRequest->getSessionData( "uselang" );
      if ($sl) {
         $this->uselang = $sl;
      }
      
      //3. check uselang setting
      $ul = $wgRequest->getVal( "uselang" );
      if ( array_key_exists( $ul, $this->i18Langs ) ) {
         $wgRequest->setSessionData( "uselang", $ul);
         $this->uselang = $ul;
      }
      
   }
   
   function setLimits( $mb = 512, $time = 30 ) {
      ini_set("memory_limit", $mb . 'M' );
      set_time_limit ( $time );
   }
   
//    public function loadPeachy( $lang, $wiki ) {
//       global $pgVerbose;
      
//       $pgVerbose = array();
      
//       $wi = $this->getWikiInfo( $lang, $wiki );

//       return Peachy::newWiki( null, null, null, "https://$wi->domain/w/api.php" );
//    }
   
   function loadDBCredentials(){
      global $dbUser, $dbPwd;
      
      try{
         $inifile = XTOOLS_BASE_SYS_DIR_DB . "/replica.my.cnf";
         $iniVal = parse_ini_file($inifile);
         $dbUser = $iniVal["user"];
         $dbPwd  = $iniVal["password"];
         unset($iniVal);
      }
      catch (Exception $e){
         ;
      }
   }
   
   public function loadDatabase( $lang, $wiki, $dbnameIn=null) {
      global $perflog, $dbUser, $dbPwd;
      
      $this->loadDBCredentials();
      $checkReplag = true;
      
      if ($dbnameIn == "tools"){
         $server = "p:".$dbnameIn.".labsdb";
         $dbname = "";
         $checkReplag = false;
      }
      elseif (in_array( $dbnameIn, array("s1","s2","s3","s4","s5","s6","s7") ) ){
         $server = $dbnameIn.".labsdb";
         $dbname = "";
         $checkReplag = false;
      }
//       elseif ( in_array( $dbnameIn, array("wikidatawiki") ) ){
//          $server = "p:s2.labsdb";
//          $dbname = $dbnameIn."_p";
//       }
      elseif ($dbnameIn){
         $server = "c2.labsdb"; //$dbnameIn.".labsdb";
         $dbname = $dbnameIn."_p";
      }
      else{
         $wi = $this->getWikiInfo( $lang, $wiki);
         $server = $wi->database.".labsdb";
         $dbname = $wi->database."_p";
      }
      
//       if (($lang == "en" && $wiki == "wikipedia") || $dbnameIn == "enwiki"   ){
//          $server = "dewiki.labsdb";
//          $dbname = "enwiki_p";
//       }
      
      try { 
         $dbr = new Database2( $server, $dbUser, $dbPwd, $dbname );
         if ( $checkReplag ) { $this->checkReplag( $dbr ); }

         #$perflog->add( 'db' , 0, $dbr );
         
         return $dbr;
      } 
      catch( DBError $e ) {
         #$perflog->add('mysqlerror', 0, $e->getMessage() );
         #$this->toDie( 'mysqlerror', $e->getMessage() );
         return $e->getMessage();
      }
   }
   
   function checkReplag( $dbr ) {
      global $I18N;

      $res = $dbr->query("
            SELECT ( UNIX_TIMESTAMP() - UNIX_TIMESTAMP(rc_timestamp) ) AS replag
            FROM recentchanges
            ORDER BY rc_timestamp DESC
            LIMIT 1
         ");
      
      $secs = floor($res[0]['replag']);

      if(  $secs > 120  ){
         $timeMin = (int)($secs / 60);
         $msgMin = $timeMin.' '.$I18N->msg( 'minutes', array("variables" => array($timeMin)));
         
         $this->replag = $I18N->msg( 'highreplag', array("variables" => array($msgMin))); 
      }
   }
   
   function initI18N(){
      global $redis;
      
      $ttl = 86400;
      $hash = "xtoolsI18N_".XTOOLS_REDIS_FLUSH_TOKEN.'35' ;
      
      $lc = $redis->get( $hash );
      if ( $lc === false ) {
         
         $initOpts = array(
               'suppressfatal' => true,
               'stayalive' => true,
               //'domain' => 'xtools',
            );
         $I18N = new Intuition( $initOpts );
         $I18N->loadTextdomainFromFile( XTOOLS_I18_TEXTFILE, 'xtools');
         $I18N->setDomain('xtools');
         $redis->setex( $hash, $ttl, serialize($I18N) );
      }
      else {
         $I18N = unserialize($lc);
         unset($lc);
      }
      
      //temp messages
      
      $this->i18Langs = $I18N->getAvailableLangs('xtools');
      
      return $I18N;
   }
   
   function checkSpider(){
      global $redis, $perflog;
      
      $lang    = isset($_REQUEST['lang']) ? $_REQUEST['lang'] : null;
      $wiki    = isset($_REQUEST['wiki']) ? $_REQUEST['wiki'] : null;
      $wikifam = isset($_REQUEST['wikifam']) ? $_REQUEST['wikifam'] : null;
      $project = isset($_REQUEST['project']) ? $_REQUEST['project'] : null;
      $uagent  = isset($_SERVER["HTTP_USER_AGENT"]) ? $_SERVER["HTTP_USER_AGENT"] : null;
      $reqUri  = isset($_SERVER["REQUEST_URI"]) ? $_SERVER["REQUEST_URI"] : null;
      $article = isset($_REQUEST['article']) ? $_REQUEST['article'] : null;
      $page    = isset($_REQUEST['page']) ? $_REQUEST['page'] : $article;
      $user    = isset($_REQUEST['user']) ? $_REQUEST['user'] : null;
      
      $argv = split('&', $_SERVER['QUERY_STRING']);
      
      $reqStr =  'xtspidercheck003' . $reqUri . $uagent; 
      $reqMin = (int)$redis->incr( $reqStr );
      
      if (
         $reqMin > 10 ||
         in_array( $wikifam, array(".wikibooks.org" ) ) ||
         in_array( $article, array("Digital_journalism", "Talk:Monte_Vista_High_School_(Danville,_California)","Template:WPRe" ) ) || 
         in_array( $user, array( "112.198.79.177", "2.91.35.107", "Carpodacus","Ambroix") ) ||
         in_array( $page, array( "Category:AppleScript_Programming","AppleScript_Programming/Mail_alert") ) ||
         in_array( $uagent, array("Java/1.6.0_34","Java/1.7.0_51", "-","Python-urllib/2.7", "Magic Browser2", "Magic Browsers","Magic Browser") ) ||
         count($argv) > 15 ||
         ( $uagent == "Mozilla/5.02" && $lang == "th" )
       ){
         $redis->expire( $reqStr , 1800 );
         header("HTTP/1.1 403 Forbidden");
         echo "Forbidden: checkSpider. (timeout 10 min) .Please inform the tool maintainer if this isn't correct.";
         file_put_contents('/data/project/xtools/spider.log', gethostname()."\t$reqUri\t$uagent\n", FILE_APPEND );
         $this->__destruct();
      }
      else{
         $redis->expire( $reqStr , 60 );
      }
      #file_put_contents('/data/project/xtools/uagent.log', gethostname()."\t$lang\t$uagent\n", FILE_APPEND );
      
      /**
       * It's a game that sucks, dude !
       */
      
      #$perflog->add('checkSpider', 0, $argv );
   }
   
   /**
    * Generates a list of languages that aren't currently selected
    * @return void
    */
   function generateLangLinks() {
   
      global $I18N;
      
      $langLinks = array();
      foreach( $this->i18Langs as $langCode => $langName ) {
         
         $url = "//tools.wmflabs.org".$_SERVER['REQUEST_URI'];
   
         if( strpos( $url, 'uselang') > 0 ) {
            $url = preg_replace( '/(uselang=[a-z]*)/', 'uselang='.$langCode, $url );
#           $url = $url . "&uselang=".$langCode;
         }
         elseif( strpos( $url, '?') > 0 ) {
            $url = $url . "&uselang=".$langCode;
         }
         else {
            $url = $url . "?uselang=".$langCode;
         }
         
         if( $langCode == $this->uselang ) {
            $langLinks[] = '<span title="'.$langName.'" >'.$langCode.'</span> ';
         }
         else{
            $langLinks[] = '<a style="display:inline-block;padding: 0px; text-align:center" href="'.$url.'" title="'.$langName.'" >'.$langCode.'</a> ';
         }
      }
      
      $rlm ="";
      if ( in_array( $this->uselang, array("he","fa","ar") ) ){
         $rlm = "&rlm;";
      }
      
      $this->translateMsg = '
            <span  style="margin-right:5px" >('.$rlm.'<a class="alert-link" href="//translatewiki.net/w/i.php?title=Special:MessageGroupStats&language=en&group=tsint-xtools&setlang='.$this->uselang.'" >'.$I18N->msg('translatelink').'</a>)'.$rlm.'</span>
            ';
      
      $this->langLinks = $this->translateMsg . '<span>'. $I18N->msg('select_language' ).': </span>' . implode('&middot;&nbsp;', $langLinks );
      
      $this->langPromoLinks = $I18N->msg('bl-promo', array( "domain" => "tsintuition", "variables" => array(
            "<a href='//translatewiki.net/' >translatewiki.net</a>",
            "<a href='//tools.wmflabs.org/intuition/#tab-about' >Intuition</a>"
         )));
   }
   

   
   /**
    * Checks dates: Input format YYYY-MM-DD or YYYY-MM or YYYY
    * @param string $date
    * @return string
    */
   function checkDate ( $date ){
      if ( !$date) return null;
      
      $len = strlen($date);
      switch ($len) {
         case 10:
            $year = substr($date,0,4);
            $mon  = substr($date,5,2);
            $day  = substr($date,8,2);          
            break;
         case 7:
            $year = substr($date,0,4);
            $mon  = substr($date,5,2);
            $day  = "01";           
            break;
         case 4:
            $year = substr($date,0,4);
            $mon  = "01";
            $day  = "01";
            break;
         
         default: 
            ;
      }
      // check format month,day,year
      if ( checkdate( $mon, $day, $year ) ){
         $res = "$year-$mon-$day";
      }
      else{
         $res = 'error';
      }
      
      return $res;
   }
   
   public function toDie( $msgStr , $var=null ) {
      global $I18N;
      
      $msgStr = ( $msgStr == 'nosuchuser' || $msgStr == 'nosuchpage' ) ? 'noexist' : $msgStr;
      
      if( is_string($var) ){ $var = array($var); }

      $msg = $I18N->msg( $msgStr , array("variables" => $var) );
      $this->error = htmlspecialchars( $msg );
      $this->showPage();
   }
   
   public function numFmt( $number, $decimal = 0, $noZero = false ) {
      if ( !is_null($number) && !is_numeric($number) && !$noZero ){
         return $number;
      }
      if ( intval($number) == 0 && $noZero ){
         return null;
      }
      $this->numberFormatter->setAttribute(NumberFormatter::MAX_FRACTION_DIGITS, $decimal);
      
      return $this->numberFormatter->format($number);
   }
   
   public function dateFmt( $date, $iso=false ){
      if (!$date) {return null; }
      
      if ( $iso ){
         return date('Y-m-d, H:i', strtotime( $date ) );
      }
      $datetime = new DateTime($date);
      
      if ( !is_object( $this->dateFormatterDate ) ) {
           $this->initFormatterAndStrings();
      }
      $mDate = $this->dateFormatterDate->format( $datetime );
      $mTime = $this->dateFormatterTime->format( $datetime );
      
      return $mDate.", ".$mTime; //$this->dateFormatter->format($datetime);
   }
   
   public function datediff( $then, $now=null ){
      global $I18N, $perflog;
      
      $ret = new stdClass();
         $ret->difftxt = '> 30 '.$I18N->msg('days', array("variables" => array(2)));
         $ret->diffcolor = "silver";
      
      if (!$then) {return $ret; }
      $now = ($now) ? $now : new DateTime();
         
      $diff = $now->diff($then);
      #$difftxt = $diff->format('%R%d %h %i');
      
      if( $diff->y == 0 && $diff->m != 0) {
         $ret->difftxt = '+'. $diff->m.' '.$I18N->msg('months', array("variables" => array( $diff->m )) );
         $ret->diffcolor = 'gray';
      }
      elseif( $diff->d != 0) { 
         $ret->difftxt = '+'.$diff->d.' '.$I18N->msg('days', array("variables" => array( $diff->d )) ); 
         $ret->diffcolor = 'gray';
      }
      elseif( $diff->d == 0 && $diff->h != 0) { 
         $ret->difftxt = '+'. $diff->h.' '.$I18N->msg('hours', array("variables" => array( $diff->h )) ); 
         $ret->diffcolor = 'darkgreen'; 
      }
      elseif( $diff->h == 0 && ( $diff->i != 0 || $diff->s != 0 ) ) { 
         $ret->difftxt = '+'. $diff->i.' '.$I18N->msg('minutes', array("variables" => array( $diff->i )) ); 
         $ret->diffcolor= 'green'; 
      }
      
      #$perflog->add('datediff', 0, $diff);
      return $ret;
   }
   
   /**
    * simple url get routine
    * @param string $url
    * @param integer $timeout in sec
    */
   public function gethttp($url, $timeout=90 ){
      
      if ( !$this->curlChannel ){
         $ch = $this->curlChannel = curl_init();
         curl_setopt($ch, CURLOPT_USERAGENT, $this->curlUserAgent );
         curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
#        curl_setopt($ch, CURLOPT_TIMEOUT, 3);
      }

      $ch = $this->curlChannel;
      curl_setopt($ch, CURLOPT_URL, $url);
#     curl_setopt($ch, CURLOPT_CONNECTTIMEOUT , $timeout);
      
      return curl_exec( $ch );
   }

   static function usort( $a, $b, $key){
      global $perflog;
      $perflog->stack[] = array($a, $b, $key);
      $al = $a[$key];
      $bl = $b[$key];
      
      if ($al == $bl) {
         return 0;
      }

      return ($al > $bl) ? +1 : -1;
   }
   
   static function in_string( $needle, $haystack ) {
      return strpos( $haystack, $needle ) !== false;
   }
   
   function initRedis(){
      
      $redis = new Redis();
      if ($redis->connect('tools-redis', 6379)){
         try {
            $redis->info("server");
         }
         catch (Exception $e){
            $redis = new RedisFake();
         }
      }
      else {
         $redis = new RedisFake();
      }

      return $redis;
   }
   
   /**
    * Loads Intuition I18N object. Replaces {#these#} with the messages.
    * @param object $I18N - defined in class I18N
    * @param string $texdomain Intuition registered textdomain eg. 'xtools', default already set in object
    * @param string $currLang language to translate to, default set in object
    * @return void
    */
   function translate_i18n( $textdomain=null, $currLang=null ) {
      global $I18N;
      
      $textdomain = ( is_null($textdomain) ) ? $I18N->getDomain() : $textdomain;
      $currLang = ( is_null($currLang) ) ? $I18N->getLang() : $currLang;
   
      $i18KeyArr = $I18N->listMsgs( $textdomain );
      $i18opt = array(
            "domain" => $textdomain,
            "lang" => $currLang,
            "variables" => array(1),
            "parsemag" => true,
         );
      $i18optPl = array(
            "domain" => $textdomain,
            "lang" => $currLang,
            "variables" => array(2),
            "parsemag" => true,
         );

      foreach( $i18KeyArr as $i => $i18Key ) {
         $this->content = str_ireplace( 
               array('{#'.$i18Key.'##}','{#'.$i18Key.'#}'), 
               array( $I18N->msg($i18Key, $i18optPl), $I18N->msg($i18Key, $i18opt ) ), 
               $this->content 
            );
      }
   }
   
   /**
    * Get the Templates from static Form class
    * @return void
    */
   function getPageTemplate( $type ){
      global $wgRequest, $perflog;

      $this->content = xForms::getTemplate( $this->viewStyle, $type, $this->toolConfigTitle );
      
      $defobj = new stdClass();
         $defobj->lang = "en";
         $defobj->wiki = "wikipedia";
         $defobj->domain = "en.wikipedia.org";
      
      $wikilast = $wgRequest->getSessionData( "wikiinfo_last" );
      $wikitool = $wgRequest->getSessionData( "wikiinfo_" . $this->toolConfigTitle );

      //1. check current input
      if ( !$this->wikiInfo->error ){
         $defobj = $this->wikiInfo;
      }
      //2. check last setting for this tool
      elseif ( $wikitool ){
         $defobj = $wikitool;
      }
      //3. check last setting overall
      elseif ( $wikilast ){
         $defobj = $wikilast;
      }
      //4. check uselang
      elseif ( $this->uselang != "en" ){
         $defobj->lang = $this->uselang;
         $defobj->domain = $this->uselang . '.wikipedia.org';
      }
      
      $this->assign( "lang", $defobj->lang );
      $this->assign( "wiki", $defobj->wiki );
      $this->assign( "project", $defobj->domain );
      
      #$perflog->stack[] = 'pagetamplate';
      #$perflog->stack[] = $defobj;
   }
   
   /**
    * Replaces {$something$} with some string. Also parses the isset function
    * @param string $name Variable to change
    * @param string $value What to change it to.
    * @return void
    */
   function assign( $name, $value ) {
      if( is_array( $value ) ) { file_put_contents('/data/project/xtools/sp_error', $this->toolTitle."\n$name\n".json_encode($value)."\n\n", FILE_APPEND ); }
      $this->content = str_replace( '{$'.$name.'$}', $value, $this->content );
      $this->content = str_replace( '{$'.$name.'}', $value, $this->content );
   
      $this->content = str_ireplace( '{&isset: '.$name.' &}', '', $this->content );
   }
   
   /**
    * Finishes script, outputs the things, unsets the objects & vars
    * @return void
    */
   public function showPage( $nocache=false ){
      global $I18N, $wgRequest, $perflog;
      
      //Assign defaults (set it to '' if not specified
      $this->assign( 'defaultPage', '' );
      $this->assign( 'defaultUser', '' );
      $this->assign( 'defaultBegin', '' );
      
      $this->assign( "lang", $this->wikiInfo->lang );
      $this->assign( "wiki", $this->wikiInfo->wiki );
      $this->assign( 'project', $this->wikiInfo->domain );
      $this->assign( 'toolTitle', $this->toolTitle );
      $this->assign( 'toolDesc', $this->toolDesc );
      
      $this->translate_i18n();
      
      $exectime = $this->numFmt( (microtime(true) - STARTTIME),2 );
      $this->executed = $I18N->msg( 'executed', array( "variables" => array($exectime) ) );
      
      $mem = $this->numFmt( (memory_get_usage(true) - STARTMEM) /1024/1024, 2);
      $peak = $this->numFmt( (memory_get_peak_usage( true ) /1024/1024) , 2);
      $this->memused = $I18N->msg( 'memory', array( "variables" => array($mem)) )." (Peak: $peak)";
      
      //config menu & status
      $this->statusLink = '
            <ul class="list-inline">
            <li>'.$this->statusLink['logo'].'</li>
            <li>'.$this->statusLink['loginout'].'</li>
            <li>'.str_replace('{$linktext}', ' XAgent', $this->statusLink['agentconfig']).'</li>
            <li>'.$this->statusLink['message'].'</li>
            <li>'.$this->statusLink['echo'].'</li>
            </ul>
         ';
      
      
      $wt = &$this;
      
      //headers
      header('content-type: text/html; charset=utf-8');
      
      $cacheCtrl = $wgRequest->getSessionData('cacheCtrl'); 
      
      if ( $cacheCtrl == "nocache" || $cacheCtrl == "nocacheOnce" || $nocache ){
         header("Cache-Control: no-cache, must-revalidate");
         header("Expires: Sat, 26 Jul 1997 05:00:00 GMT");
         header("Pragma: no-cache");
      }
      if ( $cacheCtrl == "nocacheOnce"){
         unset( $_SESSION["cacheCtrl"] );
      }
      
      //template
      $tmpl = 'main_new.php';
      if ( $this->viewStyle == "old" ){
         $tmpl = 'main.php';
      }
      include "templates/$tmpl";
   
      if( $this->debug ) 
         $perflog->getOutput();
      
      $this->__destruct();
   }
   
   function __destruct(){
      global $dbr, $dbrtools, $redis, $wgRequest, $site, $perflog;
      
      if ( isset($dbr) ){ $dbr->close(); }
      if ( isset($redis) ){ $redis->close(); }
      if ( isset($this->curlChannel) ) { curl_close($this->curlChannel); unset($this->curlChannel); }
      if ( isset($dbrtools) ){ $dbrtools->close(); }
      unset( $dbr, $dbrtools, $redis, $wgRequest, $site, $perflog );
      exit(0);
   }

}

class Database2{
   
   public $dbo;
   public $dbotype;
   
   private $server;
   private $database;
   
   function getCurrentDatabase(){
      return $this->database;
   }
   
   function __construct( $server, $dbUser, $dbPassword, $database, $persistant=false ){
      
      $p = ($persistant) ? "p:" : "";
      
      $this->dbo = new mysqli( $server, $dbUser, $dbPassword, $database);
      $this->dbo->set_charset("utf8");
      $this->dbotype = 'custom';
      $this->server = $server;
      $this->database = $database;
   }
   
   function query( $queryString ) {
      
      $retArr = null;
      if (!$queryString ) return $retArr;

      if ( $result = $this->dbo->query( $queryString ) ){
         
         if ( is_object($result) ){
            while( $row = $result->fetch_assoc() ){
               $retArr[] = $row;          
            }
            $result->close();
         }
      }

      return $retArr;
   }
   
   function close(){
      $this->dbo->close();
   }
   function strencode( $String ){
      return $this->dbo->real_escape_string( $String );
   }
   
   function multiquery( $queries ){
      global $wt, $redis, $perflog;
      $mqstart = microtime(true);
      
      $reqToken = hash('md5', serialize($queries) );
      $request = array(
            "defaultServer" => $this->server,  
            "defaultDatabase" => $this->database, 
            "queries" => $queries
         );
      $redis->setex( $reqToken, 100, json_encode($request) );
      
#     $cmd = "REQUEST_METHOD=GET SERVER_NAME=Blue SERVER_PORT=19999 SERVER_PROTOCOL=tcp QUERY_STRING=reqToken=eea02eea8fd65742e692f4c36fe7c3c7 cgi-fcgi -bind -connect tools-webgrid-tomcat:19999";   
      
      $output = $wt->gethttp("https://tools.wmflabs.org/xtools/multiquery.fcgi?reqToken=$reqToken");
      
      $perflog->add('mq2-cmd', (microtime(true)- $mqstart), array("cmd" => 'query', "output" => $output ) );
      
      //retry once
//       if( strlen($output) != 32 ){
//          $perflog->stack[] = $wt->curlChannel;
//          $perflog->stack[] = curl_getinfo( $wt->curlChannel );
//          $wt->toDie('error::mq');
//       }
      
      
      $mqResponse = json_decode( $redis->get( $reqToken . "_response" ) );
      
      $error = false;
      foreach ($mqResponse as $i => $response){
         
         if ( $queries[$i]["type"] == "api"){
            $result[] = json_decode( $redis->get( $response->token ), false );
         }
         else {
            $result[] = json_decode( $redis->get( $response->token ), true );
         }
         
         $perflog->add('queries', 0, array(
               "query" => substr($queries[$i]["query"],0,1500), 
               "response" => $mqResponse[$i],
               //"result" => $result[$i] 
            ));
         if ( $mqResponse[$i]->duration == "error" ) {$error = true ;}
      }
      
      if ($error){
         $wt->toDie('error::mq');
      }
         
      #$perflog->add('mq2-result',0, $result) ;
      return $result;
   }
   
   function multiquery3( $queries ){
      global $redis, $perflog, $wt;
      $mqstart = microtime(true);
      
      $reqToken = hash('md5', serialize($queries) );
      $request = array(
            "defaultServer" => $this->server,
            "defaultDatabase" => $this->database,
            "queries" => $queries
      );
      $redis->setex( $reqToken, 100, json_encode($request) );
      $perflog->add('reqToken', 0, $reqToken );
      
         
      if ($redis){
         $sqlapi = "http://tools-webproxy/tools-info/sqlapi/api.fcgi?";
         
         //Get the separate queries
         foreach ( $queries as $i => $query ){
            
            if( $query["type"] == "db" ) {
               
               $server = $this->server;
               $database = $this->database;
               
               if ( $query["src"] != "this" ){
                  $server = $query["src"].".labsdb";
                  $database = $query["src"]."_p";
               }
                  
               $data = array(
                     "server" => $server,
                     "database" => $database,
                     "query" => $query["query"],
                  );
               $request[] = $sqlapi.http_build_query($data);
            }
            
            if( $query["type"] == "api" ){
               $request[] = $query["query"];
            }
         }
         
         $apiresults = $this->multicurl($request);
         
         
         //Get the results from redis
         $error = false;
         foreach ($apiresults as $i=> $apiresult ){
            
            $obj = json_decode( $apiresult, false);
            
            if ( $queries[$i]["type"] == "api"){
               $result[$i] = $obj;
               
               $perflog->add('mq_api: '.count($obj), microtime(true)-$mqstart );
            }
            elseif ( !is_object($obj) || $obj->length == 0 ) {
               $start = microtime(true);
               
               $result[$i] = $this->query( $queries[$i]["query"] );
                
               $perflog->add('dbr_local: ', (microtime(true) - $start) );
            }
            else{
               $result[$i] = json_decode( $redis->get( $obj->token ), true );
               
               $xlen = isset($obj->len) ? $obj->len : 0;
               $perflog->add('sql_api: '.$xlen, $obj->exectime, $queries[$i]["query"] );
            }
         }
         
         return $result;
         
      }
      
   }
   
   function multicurl( $urlArr, $method="GET", $request=null ){

      global $version;
      $res  = null;
      $err  = null;
   
      if ( !is_array($urlArr) ) { $urlArr = array($urlArr); }
   
      //create multiple cUrl handler
      $mh = curl_multi_init();
   
      foreach ( $urlArr as $i => $url ) {
         $ch[$i] = curl_init();
         curl_setopt($ch[$i], CURLOPT_USERAGENT, 'Xtools/2.0 (https://tools.wmflabs.org/xtools/; Hedonil/Cyberpower678)');
         curl_setopt($ch[$i], CURLOPT_RETURNTRANSFER, true);
         curl_setopt($ch[$i], CURLOPT_URL, $url);
         curl_setopt($ch[$i], CURLOPT_CONNECTTIMEOUT ,2);
         curl_setopt($ch[$i], CURLOPT_TIMEOUT, 2);
         //echo curl_getinfo($handle, CURLINFO_HTTP_CODE);
   
         if ( $method == "POST") {
            curl_setopt( $ch[$i], CURLOPT_POST, true );
            curl_setopt( $ch[$i], CURLOPT_POSTFIELDS, http_build_query( $request ) );
            //curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, false );
         }
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
      foreach ($urlArr as $i => $url ) {
         $res[$i] = curl_multi_getcontent($ch[$i]);
         curl_close($ch[$i]);
      }
   
      #  $err = curl_error($mh);
      curl_multi_close($mh);
      $mh = null;
   
      if (count($res) == 1) { $res = $res[0]; }
   

      return $res;
   }
}

class Perflog {
   public $stack = array();

   function add( $modul, $time, $info=null ){
      array_push( $this->stack, array("modul" => $modul, "time" => $time, "info" => $info ));
   }
   function getOutput(){
      echo "<br style='clear:both' /><br /><div class='container'><pre>"; print_r($this->stack); echo "</pre></div>";
   }
}
   
/**
 * dummy class, if redis is not availabe
 * @author Hedonil
 */
class RedisFake{
   function get(){
      return false;
   }
   function set(){
      return false;
   }
   function setex(){
      return false;
   }
   function expire(){
      return false;
   }
   function close(){
      return false;
   }
}
   
   
