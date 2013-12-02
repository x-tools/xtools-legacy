<?php

include('/data/project/xtools/public_html/common/header.php');
include('/data/project/xtools/wikibot.classes.php');

$http = new http;

echo '
<div id="content">
<table class="cont_table" style="width:100%;">
<tr>
<td class="cont_td" style="width:75%;">
<h2 class="table">Is Admin checker</h2>';

function printForm() {
   global $http;
   
   echo 'Check if a user is in a certain user group<br /><br /><form action="index.php" method="get">Username: <input type="text" name="user" /><br />Group: <select name="group">';
   
   $groups = $http->get('https://en.wikipedia.org/w/api.php?action=query&meta=siteinfo&siprop=usergroups&format=php', false);
   $groups = unserialize($groups);
   $groups = $groups['query']['usergroups'];
   foreach ($groups as $group) {
      
      if ($group['name'] == '*' || $group['name'] == 'user' || $group['name'] == 'autoconfirmed') { continue; }
      
      if ($group['name'] == 'sysop') { 
         echo '<option value="sysop" selected="selected">Admin</option>\n';
      }
      else {
         echo '<option value="'.$group['name'].'">'.$group['name'].'</option>';
      }
   }
            
   echo '</select><br />';    
   echo '<input type="submit" />
   </form>
   </td>';
   include('/data/project/xtools/public_html/common/footer.php');
   die();
}

if (!isset($_GET['user'])) {
   printForm();
}

$user = mysql_escape_string($_GET['user']);
$groups = array();

require_once('/data/project/xtools/database.inc');
mysql_connect("enwiki.labsdb",$toolserver_username,$toolserver_password);
@mysql_select_db("enwiki_p") or print mysql_error();

$query = "SELECT ug_user, ug_group FROM user AS A
 JOIN user_groups AS B ON ug_user = user_id
 WHERE user_name = '$user';";
$result = mysql_query($query);
if(!$result) Die("ERROR: No result returned.");
while ($row = mysql_fetch_assoc($result)) {

   $groups[] = $row['ug_group'];
}

switch ($_GET['group']) {
case 'admin':
case '':
    $toCheck = 'sysop';
    break;
default:
    $toCheck = $_GET['group'];
}

if (in_array($toCheck, $groups)) {
   echo '<big><font color="green">'.$_GET['user']." is in the $toCheck group.</font></div>";
}
else {
   echo '<big><font color="red">'.$_GET['user']." is not in the $toCheck group.</font></div>";
}

include('/data/project/xtools/public_html/common/footer.php');

?>
