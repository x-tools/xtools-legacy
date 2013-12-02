<?php
error_reporting(E_ALL);
ini_set('display_errors', '1');

$version = '1.51';


/*
RfA Analysis
Now supports RFALib 2.0 and higher
Copyright (C) 2006 Tangotango (tangotango.wp _at_ gmail _dot_ com)

This program is free software; you can redistribute it and/or
modify it under the terms of the GNU General Public License
as published by the Free Software Foundation; either version 2
of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
*/

include( '/data/project/xtools/public_html/common/header.php' );
//include( '/data/project/xtools/wikibot.classes.php' );
echo "<!--";
require_once('/data/project/xtools/Peachy/Init.php');
echo"-->";
include( '/data/project/xtools/rfalib4.php');

echo "<!--";
$site = Peachy::newWiki( null, null, null, 'http://en.wikipedia.org/w/api.php' );
echo "-->";

$wiki = new HTTP;

$wikipedia = "//en.wikipedia.org/wiki/";

function print_h_l($var,$searchlist) {
    if (empty($var)) {
        echo "<ul><li>No items in list</li></ul>";
    }
    echo "<ol>";
    foreach ($var as $vr) {
        $iffy = False;

        if (isset($vr['iffy'])) {
            $iffy = $vr['iffy'];
        }
        if (isset($vr['error'])) {
            $text = "<strong>Error parsing signature:</strong> <em>".htmlspecialchars($vr['context'])."</em>";
        } else {
            $text = $vr['name'];
        }

        if (isset($vr['name']) && in_array($vr['name'],$searchlist)) {
            if ($iffy == 1)
                echo "<li class=\"dup iffy1\">{$text}</li>\n";
            else
                echo "<li class=\"dup\">{$text}</li>\n";
        } else {
            if ($iffy == 1)
                echo "<li class=\"iffy1\">{$text}</li>\n";
            else
                echo "<li>{$text}</li>\n";
        }
    }
    echo "</ol>";
}

function bailout($message) {
    echo "<h3>Fatal Error</h3>";
    echo "<p>$message</p>";
    include( '/data/project/xtools/public_html/common/footer.php' );
    exit;
}

//BEGIN ==
echo '<div id="content">
   <table class="cont_table" style="width:100%;">
   <tr>
   <td class="cont_td" style="width:75%;">
   <h2 class="table">RfA Analysis</h2>';
?>
<h1>RfA Analysis</h1>
<p>This tool identifies duplicate voters in a <a href="//en.wikipedia.org/wiki/Wikipedia:Requests_for_adminship">Request for adminship</a> on the English Wikipedia. This tool can also analyze Requests for bureaucratship pages.</p>
<?php
if (isset($_GET['p'])) {
    $targetpage = str_replace(' ','_',$_GET['p']);
    $targetpage = explode('?',$_GET['p']);
    $getpage = $targetpage[0];
    echo "<h2>Voters for <a href=\"//en.wikipedia.org/wiki/{$getpage}\">{$getpage}</a></h2>";

    //$buffer = file_get_contents('input.txt');
    //$buffer = $wpq->getpage($getpage);
    //$buffer = $wiki->get($wikipedia . $getpage);
		echo "<!--";
    $mypage = initPage($getpage);
    $buffer = $mypage->get_text();
		echo "-->";

    if (($buffer === False) or (trim($buffer) == '')) {
        bailout("Failed to load \"$getpage\" from server");
    }

    if (preg_match("/#redirect:?\s*?\[\[\s*?(.*?)\s*?\]\]/i",$buffer,$match)) {
        bailout("Page redirects to {$match[1]}<br /><a href=\"{$_SERVER['PHP_SELF']}?p=".urlencode($match[1])."\">Click here to analyze it</a>");
    }

    //Create an RFA object
    $myRFA = new RFA();


    $result = $myRFA->analyze($buffer);
    if ($result !== TRUE) {
        //bailout($myRFA->lasterror);
    }

    $enddate = $myRFA->enddate;

    $tally = count($myRFA->support).'/'.count($myRFA->oppose).'/'.count($myRFA->neutral);

    $totalVotes = count($myRFA->support) + count($myRFA->oppose);
    if( $totalVotes != 0 ) {
      $tally .= ", " . number_format( ( count($myRFA->support) / $totalVotes ) * 100, 2 ) . "%";
    }

    echo '<a href="//en.wikipedia.org/wiki/User:'.$myRFA->username.'">'.$myRFA->username.'</a>\'s RfA ('.$tally.'); End date: '.$enddate.'<br />';

    echo 'Found <strong>'.count($myRFA->duplicates).'</strong> duplicate votes (highlighted in <span class="dup">red</span>).'
    .' Votes the tool is unsure about are <span class="iffy1">italicized</span>.';

    echo "<h3>Support</h3>";
    print_h_l($myRFA->support,$myRFA->duplicates);
    echo "<h3>Oppose</h3>";
    print_h_l($myRFA->oppose,$myRFA->duplicates);
    echo "<h3>Neutral</h3>";
    print_h_l($myRFA->neutral,$myRFA->duplicates);
}
?>
<h2>Analyze</h2>
<form method="get" action="<?php echo $_SERVER['PHP_SELF']; ?>">
<strong>RfA page:</strong>&nbsp;
<input type="text" name="p" size="50" value="<?php echo (isset($_GET['p'])) ? $_GET['p'] : "Wikipedia:Requests for adminship/Name of user" ?>" />
<input type="submit" value="Analyze" />
</form>
<?php

include( '/data/project/xtools/public_html/common/footer.php' );

?>
