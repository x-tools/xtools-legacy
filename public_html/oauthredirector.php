<?php
    DEFINE('XTOOLS_BASE_SYS_DIR', '/data/project/xtools' );
    DEFINE('XTOOLS_BASE_SYS_DIR_DB', '/data/project/xtools' );
    DEFINE('XTOOLS_BASE_SYS_DIR_SESSION', '/data/project/xtools' );
    require_once ( '/data/project/xtools/modules/OAuth.php' );
    if( isset( $_GET['action'] ) ) {
        start_session();
        if( $_GET['action'] == "login" ) {
            if( isset( $_GET['callto'] ) ) $_SESSION['callto'] = $_GET['callto'];
            else $_SESSION['callto'] = "https://www.mediawiki.org/w/api.php";
            if( isset( $_GET['returnto'] ) ) $_SESSION['returnto'] = $_GET['returnto'];
            else $_SESSION['returnto'] = "https://tools.wmflabs.org/xtools-articleinfo/index.php";
            $OAuth = new OAuth2( $_SESSION['callto'] );
            
            if( !$OAuth->Authorize() ) {
                session_write_close();
                die("Failed to initiate Authorization!<br>Returned error:<br>".$OAuth->getError());
            } else {
                session_write_close();
                die();
            }
        }
        if( $_GET['action'] == "logout" ) {
            if( isset( $_GET['callto'] ) ) $_SESSION['callto'] = $_GET['callto'];
            else $_SESSION['callto'] = "https://www.mediawiki.org/w/api.php";
            if( isset( $_GET['returnto'] ) ) $_SESSION['returnto'] = $_GET['returnto'];
            else $_SESSION['returnto'] = "https://tools.wmflabs.org/xtools-articleinfo/index.php";
            $OAuth = new OAuth2( $_SESSION['callto'] );
            
            $OAuth->logout();
            
            $returnstring = base64_encode( serialize( $_SESSION ) );
            header( "Location: {$_SESSION['returnto']}"/*.(strpos($_SESSION['returnto'], "?") === FALSE ? "?" : "&")."hash=$returnstring"*/ );
            unset( $_SESSION['returnto'], $_SESSION['callto'] );
            session_write_close();
            die();
        }
        if( $_GET['action'] == "getsession" ) {
            $returnstring = base64_encode( serialize( $_SESSION ) );
            header( "Location: {$_GET['returnto']}"/*.(strpos($_GET['returnto'], "?") === FALSE ? "?" : "&")."hash=$returnstring"*/ );
        }
    }
    
    //If we have a callback, it probably means the user executed the authorize function, so let's finish authorization by getting the access token.
    if ( isset( $_GET['oauth_verifier'] ) && $_GET['oauth_verifier'] ) {
        start_session();
        $OAuth = new OAuth2( $_SESSION['callto'] );
        $returnstring = base64_encode( serialize( $_SESSION ) );
        if( $OAuth->isAuthorized() ) {
            header( "Location: {$_SESSION['returnto']}"/*.(strpos($_SESSION['returnto'], "?") === FALSE ? "?" : "&")."hash=$returnstring"*/ );
            unset( $_SESSION['returnto'], $_SESSION['callto'] );
            session_write_close();
            die();
        } else {
            session_write_close();
            die("Failed to complete Authorization!<br>Returned error:<br>".$OAuth->getError());
        }
    }
    
    die("This is a redirector OAuth handling script.  It is not meant to be called directly." );
    
    function start_session() {
        session_save_path(XTOOLS_BASE_SYS_DIR_SESSION.'/tmp/session');
        ini_set('session.gc_probability', 1);
        session_cache_limiter("public");
        session_cache_expire(30);
        $lifetime = 15552000;
        $path = preg_replace('/^(\/.*\/).*/', '\1', dirname($_SERVER['SCRIPT_NAME']) );
        session_name( 'xtools' );
        session_set_cookie_params ( $lifetime, $path, ".tools.wmflabs.org"); 
        session_start();
    }
?>
