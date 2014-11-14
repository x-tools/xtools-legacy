<?php

//Requires
	require_once( '/data/project/xtools/modules/WebTool.php' );

//Load WebTool class
	$wt = new WebTool( 'sc' );
	$wt->setLimits();
	$wt->getPageTemplate( "form" );

	$ui = $wt->getUserInfo();
		$user = $ui->user;
	
	$wi = $wt->wikiInfo;
		$lang = $wi->lang;
		$wiki = $wi->wiki;
	
//Show form if &article parameter is not set (or empty)
	if( !$user ) {
		$wt->showPage();
	}
	
	$dbr = $wt->loadDatabase( $lang, $wiki );
	$obj = getEditCounts($dbr, $user);

	$wt->content = '
		<br />
		<table class="leantable table-condensed xt-table" >
			<tr><td>{#username#}: </td><td><a href="//'.$lang.'.'.$wiki.'.org/wiki/User:'.$user.'" >'.$user.'</a></td></tr>
			<tr><td>{#userid#}: </td><td>'.$obj->id.'</td></tr>
			<tr><td>{#groups#}: </td><td>'.$obj->groups.'</td></tr>
			<tr><td>{#deleted_edits#}: </td><td>'.$wt->numFmt($obj->deleted).'</td></tr>
			<tr><td>{#live#}: </td><td>'.$wt->numFmt($obj->live).'</td></tr>
			<tr><td>{#total#}: </td><td><b>'.$wt->numFmt($obj->live + $obj->deleted).'</b></td></tr>
		</table>
	';
	
$wt->showPage();


function getEditCounts( $dbr, $user ) {
	
	$name = $dbr->strencode($user);
	$query = " 
			SELECT 'id' as source, user_id as value FROM user WHERE user_name = '$name' 
			UNION
			SELECT 'arch'as source, COUNT(*) AS value FROM archive_userindex WHERE ar_user_text = '$name'
			UNION
			SELECT 'rev' as source, COUNT(*) AS value FROM revision_userindex WHERE rev_user_text = '$name'
			UNION
			SELECT 'groups' as source, ug_group as value FROM user_groups JOIN user on user_id = ug_user WHERE user_name = '$name'
		";
	$res = $dbr->query($query);
	
	$obj = new stdClass();
	foreach ( $res as $row ){
		if ($row["source"] == "id" ){ $obj->id = $row["value"]; }
		if ($row["source"] == "arch" ){ $obj->deleted = $row["value"]; }
		if ($row["source"] == "rev" ){ $obj->live = $row["value"]; }
		if ($row["source"] == "groups" ){ $obj->groups .= $row["value"]." "; }
	}
	
	return $obj;

}

/**************************************** templates ****************************************
 *
*/
function getPageTemplate( $type ){

	$templateForm = '..old..';
	
	if( $type == "form" ) { return $templateForm; }
	if( $type == "result" ) { return $templateResult; }
}