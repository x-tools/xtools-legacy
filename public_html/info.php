<?php

#phpinfo();

//Requires
require_once( 'WebTool.php' );

$wt = new WebTool('test');
$wt->loadDBCredentials();

echo "Hallo";
#print_r($wt->metap);
foreach ( $wt->metap as $db => $row ){
	$slice = $row["slice"];
	if ($db == "centralauth"  ) { continue; }

#	if ( in_array( $db, array("bgwiki","afwiki","abwiki","acewiki","amwiki", "angwiki") ) || $db == "commonswiki" ) { continue; }
 
	$queries[$slice][] =
	"
			(SELECT rev_timestamp, '$db' as wiki, page_namespace, page_title, rev_id
			FROM ${db}_p.revision_userindex
			JOIN ${db}_p.page on page_id = rev_page
			where  rev_user_text = 'Hedonil' AND rev_timestamp > '20140628210110'
			Limit 10)
	";
}
#	print_r($queries);
	
foreach ( $queries as $slice => $slarr ){
		$stime = microtime(true);
			$dbr = new Database2( $slice, $dbUser, $dbPwd, '');
		$dur = number_format( microtime(true) - $stime, 3);

		$query = implode(" UNION ", $slarr);
		echo $query;
		$res = $dbr->query( $query );
		$anz = count($res);
		
		echo "$slice --- $db --- $anz --- $dur  <br/>\n";
		
		$dbr->close();	
}

