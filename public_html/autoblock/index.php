<?php

# ############################# #
# Autoblock finder              #
# Nakon, 2010                   #
# ############################# #

$cnt = file_get_contents( "ab.txt" );
$cnt++;
file_put_contents ( "ab.txt", $cnt );

if ( $_GET['u'] ) {

   # begin database connection
   //include('/home/nakon/includes/database.php');
   //dbconnect('enwiki-p');

   require_once( '/data/project/xtools/database.inc' );
   mysql_connect( 'enwiki.labsdb',$toolserver_username,$toolserver_password );
   @mysql_select_db('enwiki_p') or print mysql_error();

   $q = 'SELECT ipb_id, ipb_by_text, ipb_expiry, ipb_user FROM ipblocks WHERE ipb_auto = 1 AND ipb_reason LIKE "%' . mysql_real_escape_string( $_GET['u'] ) . '%"';
   $result = mysql_query( $q );
   while ( $out = mysql_fetch_array( $result ) ) {
      $autoblockList .= '<li><strong>#' . $out['ipb_id'] . '</strong> - blocked by <a href="//en.wikipedia.org/wiki/User:' . htmlspecialchars( $out['ipb_by_text'] ) . '">' . htmlspecialchars( $out['ipb_by_text'] ) . '</a> :: <a href="//en.wikipedia.org/w/index.php?title=Special:BlockList&action=unblock&id=' . $out['ipb_id'] . '">Lift block</a><br />';
   }

   $html = '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "//www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="//www.w3.org/1999/xhtml" lang="en" xml:lang="en">
<head>
   <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
   <title>autoblock finder &middot; results</title>
</head>
<body style="font-family:georgia;padding:0;margin:0;">
   <div id="mainContainer" style="width:1000px;margin-left:auto;margin-right:auto;overflow:auto;">
      <div id="header" style="width:1000px;border-bottom:2px solid grey;">
         <span style="font-size:16pt;">autoblock finder &mdash; results</span>
      </div>
      <div id="leftBox" style="float:left;padding:5px;background-color:#BBB;width:990px;clear:both;margin-top:15px;">
         <h2 style="margin:0;margin-bottom:15px;border-bottom:2px solid black;">Autoblocks for ' . htmlspecialchars( $_GET['u'] ) . '</h2>
         ' . ( !$autoblockList ? 'No autoblocks found' : $autoblockList ) . '
         <hr />
         <a href="index.php">Check another account</a>
      </div>
   </div>
   <div id="footerContainer" style="width:1000px;padding:5px;margin-top:10px;margin-left:auto;margin-right:auto;border:1px solid black;background-color:#DDD;overflow:auto;">
      <div id="footerText" style="float:left;">
         <p style="margin-top:8px;margin-bottom:0;">Powered by toolserver.  Last updated ' . date( "m/d/Y", filemtime( 'autoblockfinder.php' ) ) . '.</p>
      </div>
      <div id="toolserverBug" style="float:right;">
         <a href="//toolserver.org"><img src="//toolserver.org/images/wikimedia-toolserver-button.png" style="border:none;" alt="powered by toolserver" /></a>
      </div>
   </div>
<!-- ' . $cnt . ' -->
</body>
</html>';

   echo $html;
   

} else {

   $html = '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "//www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="//www.w3.org/1999/xhtml" lang="en" xml:lang="en">
<head>
   <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
   <title>autoblock finder</title>
</head>
<body style="font-family:georgia;padding:0;margin:0;">
   <div id="mainContainer" style="width:1000px;margin-left:auto;margin-right:auto;overflow:auto;">
      <div id="header" style="width:1000px;border-bottom:2px solid grey;">
         <span style="font-size:16pt;">autoblock finder</span>
      </div>
      <div id="leftBox" style="float:left;padding:5px;background-color:#BBB;width:990px;clear:both;margin-top:15px;">
         <form action="index.php" method="GET">
         <strong>User name</strong> <input type="textbox" name="u" /><br />
         <input type="submit" value="Check autoblocks for user" />
         </form>
      </div>
   </div>
   <div id="footerContainer" style="width:1000px;padding:5px;margin-top:10px;margin-left:auto;margin-right:auto;border:1px solid black;background-color:#DDD;overflow:auto;">
      <div id="footerText" style="float:left;">
         <p style="margin-top:8px;margin-bottom:0;">Powered by labs.  Last updated ' . date( "m/d/Y", filemtime( 'index.php' ) ) . '.</p>
      </div>
      <div id="toolserverBug" style="float:right;">
         <a href="//toolserver.org"><img src="//toolserver.org/images/wikimedia-toolserver-button.png" style="border:none;" alt="powered by toolserver" /></a>
      </div>
   </div>
<!-- ' . $cnt . ' -->
</body>
</html>';

   echo $html;
}
