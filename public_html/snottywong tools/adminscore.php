<?php
$_REQUEST['db'] = 'enwiki_p'; // To do: to choose
/*require_once( '/data/project/xtools/stats.php' );
require_once( '/data/project/xtools/GlobalFunctions.php' );
require_once( '/data/project/xtools/public_html/phptemp/PHPtemp.php' );
require_once( '/data/project/xtools/public_html/phptemp/Language.php' );
require_once( '/data/project/xtools/public_html/sitenotice.php' );*/
if (empty($_REQUEST['account']) OR $_REQUEST['account'] == '') { 
	print '
	<form action="adminscore.php" method="post">
		<label>Account: </label><input type="login" size="50" name="account"><br/><br/>
		<label>Account age</label><input type="checkbox" name="Account age" value="Yes" checked /><br/>
		<label>Edit count</label><input type="checkbox" name="Edit count" value="Yes" checked /><br/>
		<label>User page</label><input type="checkbox" name="User page" value="Yes" checked /><br/>
		<label>Patrols</label><input type="checkbox" name="Patrols" value="Yes" checked /><br/>
		<label>Block count</label><input type="checkbox" name="Block count" value="Yes" /><br/>
		<label>WP:AFD</label><input type="checkbox" name="WP:AFD" value="Yes" /><br/>
		<label>Recent activity</label><input type="checkbox" name="Recent activity" value="Yes" /><br/>
		<label>WP:RPP</label><input type="checkbox" name="WP:RPP" value="Yes" /><br/>
		<label>Edit summaries</label><input type="checkbox" name="Edit summaries" value="Yes" /><br/>
		<label>Namespaces</label><input type="checkbox" name="Namespaces" value="Yes" /><br/>
		<label>Articles created</label><input type="checkbox" name="Articles created" value="Yes" /><br/>
		<label>WP:AIV</label><input type="checkbox" name="WP:AIV" value="Yes" /><br/><br/>
		<input type="submit" name="formSubmit" value="Submit" />
	</form>';
} else {
	var_dump($_REQUEST);exit;
	if (substr($_SERVER['TMP'], 0, 3) == 'C:/') {
		require_once('E:/www/database.inc');
		$host = 'localhost';
	} else {
		#require_once( '/data/project/xtools/database.inc');
		require_once( '/data/project/jackbot/database.inc');
		$host = 'enwiki.labsdb';
	}
	$mysqli = new mysqli($host, $toolserver_username, $toolserver_password, $_REQUEST['db']);
	if ($mysqli->connect_errno) { echo "MySQL connection error: (" . $mysqli->connect_errno . ") " . $mysqli->connect_error; }
	
	// MULTIPLIERS (to review)
	$EDITCOUNT = 1.25; 		# 0 if = 10 000
	$ACCOUNTAGE = 1.25; 	# 0 if = 365 jours
	$BLOCKCOUNT = 1.4; 		# 0 if = 10
	$USERRIGHTS = 0.75; 	# 0 if = 
	$USERPAGE = 0.1; 		# 0 if = 
	$EDITSUMMARIES = 0.8; 	# 0 if = 
	$ACTIVITY = 0.9; 		# 0 if = 
	$NAMESPACES = 1.0; 		# 0 if = 
	$ARTICLESCREATED = 1.4; # 0 if = 
	$AIVRPPAFD = 1.15; 		# 0 if = 
	$PATROL_MULTIPLIER = 1; # 0 if = 
	
	// https://en.wikipedia.org/wiki/User_talk:JackPotte#Benchmarking
	$queries = array(
		1 => array('Account age', "SELECT user_registration FROM user WHERE user_name='".$_REQUEST['account']."';", 'date'),
		2 => array('Edit count', "SELECT user_editcount FROM user WHERE user_name='".$_REQUEST['account']."';", '-100+0.01*'),
		3 => array('User page', "SELECT page_len FROM page WHERE page_namespace=2 AND page_title='".$_REQUEST['account']."';", '-100+0.1*'),
		4 => array('Patrols', "SELECT COUNT(*) FROM logging WHERE log_type='patrol' and log_action='patrol' and log_namespace=0 and log_deleted=0 and log_user_text='".$_REQUEST['account']."';", '-100+0.01*'),
		5 => array('Block count', "SELECT COUNT(*) FROM logging WHERE log_type=\"block\" AND log_action=\"block\" AND log_namespace=2 AND log_deleted=0 AND log_title='".$_REQUEST['account']."';", '100-10*'),
		6 => array('WP:AFD', "SELECT COUNT(*) FROM revision WHERE rev_page like 'Articles for deletion/%' and rev_page not like 'Articles_for_deletion/Log/%' and rev_user_text='".$_REQUEST['account']."';",'1*'),
		7 => array('Recent activity', "SELECT COUNT(*) FROM revision WHERE rev_user_text='".$_REQUEST['account']."' AND rev_timestamp > (now()-INTERVAL 730 day) and rev_timestamp < now();",'1*'),
		8 => array('WP:RPP', "SELECT COUNT(*) FROM revision WHERE rev_page like 'Administrator intervention against vandalism%' and rev_user_text='".$_REQUEST['account']."';",'1*'),
		9 => array('Edit summaries', "SELECT COUNT(*) FROM revision JOIN page ON rev_page=page_id WHERE page_namespace=0 AND rev_user_text='".$_REQUEST['account']."' AND rev_comment='';",'1*'),
		10 => array('Namespaces', "SELECT count(*) FROM revision JOIN page ON rev_page=page_id WHERE rev_user_text='".$_REQUEST['account']."' AND page_namespace=0;",'1*'),
		11 => array('Articles created', "SELECT DISTINCT page_id FROM page JOIN revision ON page_id=rev_page WHERE rev_user_text='".$_REQUEST['account']."' and page_namespace=0 AND page_is_redirect=0;",'1*'),
		12 => array('WP:AIV', "SELECT COUNT(*) FROM revision WHERE rev_page like 'Requests_for_page_protection%' and rev_user_text='".$_REQUEST['account']."';",'1*')
	);
	print "<H1>Admin eligibility score for <a href='https://en.wikipedia.org/wiki/User:".$_REQUEST['account']."'>".$_REQUEST['account']."</a></H1>";
	print '<table border="1px;">';
	print '<th>Label</th><th>Value</th><th>Points</th>';
	$score = 0;
	$now = new DateTime();
	for ($l = 1; $l <= sizeof($queries); $l++) {
		print '<tr>';
			print '<td>'.$queries[$l][0].'</td>';
			$value = get_score($queries[$l][1]);
			if ($queries[$l][2] == 'date') {
				$date = new DateTime($value);
				$diff = $date->diff($now);
				$formula = 365*$diff->format("%y")+30*$diff->format("%m")+$diff->format("%d");
				print '<td>'.$formula.' days</td>';
				$formula = $formula-365;
			} else {
				print '<td>'.$value.'</td>';
				$formula = calculate_string($queries[$l][2].$value);
			}
			$formula = max(min($formula, 100), -100);
			print '<td align=right><font color='.get_color($formula).'>'.$formula.'</font></td>';
			$score += $formula;
		print '</tr>';
	}
	print '<tr><td><b><big>Total score</big></b></td><td colspan=2 align=right><b><big>'.$score.'</big></b></td></tr>';
	$mysqli->close();
	print '</table>';
}

function get_score($query) {
	global $mysqli;
	$res = $mysqli->query($query);
	if ($res) {
		$res = $res->fetch_assoc();
		if ($res) {
			$res = current($res);
		} else {
			$res = 0;
		}
	} else {
		$res = 0;
	}
	return $res;
}

function get_color($number) {
	if ($number < 0) {
		return 'red';
	} elseif ($number > 0) {
		return 'green';
	}
}

function calculate_string( $mathString )    {
    $mathString = trim($mathString);     // trim white spaces
    $mathString = preg_replace('[^0-9\+-\*\/\(\) ]', '', $mathString);    // remove any non-numbers chars; exception for math operators
    $compute = create_function("", "return (" . $mathString . ");" );
    return 0 + $compute();
}
?>
