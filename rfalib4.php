<?php
error_reporting(E_ALL);
ini_set('display_errors', '1');


/*
RfA Analysis Library 2.05
This version breaks compatibility with the 1.x series
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

define('TSREGEX',"[0-2][0-9]\:[0-5][0-9], [1-3]?[0-9] (?:January|February|March|April|May|June|July|August|September|October|November|December) \d{4} \(UTC\)");
function rfa_getver() { return '3.00'; }

/*
 * An RFA object contains the parsed information for an RFA
 */
class RFA {
    var $username = False;
    var $enddate  = False;
    var $support  = array();
    var $oppose   = array();
    var $neutral  = array();
    var $duplicates = array();
    var $lasterror = '';

    function reset() {
        $username = False;
        $enddate  = False;
        $support  = array();
        $oppose   = array();
        $neutral  = array();
        $duplicates = array();
        $lasterror = '';
    }

    /*
     * Attempts to find a signature in $input using the default regex. Returns matches.
     */
    function findsig($input, &$matches) {
        //Supports User: and User talk: wikilinks, {{fullurl}}, unsubsted {{unsigned}}, unsubsted {{unsigned2}}, anything that looks like a custom sig template
        return preg_match_all(
         "/\[\[[Uu]ser(?:[\s_][Tt]alk)?\:([^\]\|\/]*)(?:\|[^\]]*)?\]\]"                                     //1: Normal [[User:XX]] and [[User talk:XX]]
        ."|\{\{(?:[Ff]ullurl\:[Uu]ser(?:[\s_][Tt]alk)?\:|[Uu]nsigned\|)([^\}\|]*)(?:|[\|\}]*)?\}\}"    //2: {{fullurl}} and {{unsigned}} templates
        ."|(?:\{\{)[Uu]ser(?:[\s_][Tt]alk)?\:([^\}\/\|]*)"                                                  //3: {{User:XX/sig}} templates
        ."|\{\{[Uu]nsigned2\|[^\|]*\|([^\}]*)\}\}"                                                                //4: {{unsigned2|Date|XX}} templates
        ."|(?:\[\[)[Uu]ser\:([^\]\/\|]*)\/[Ss]ig[\|\]]/"                                                        //5: [[User:XX/sig]] links (compromise measure)
        ,$input,$matches,PREG_OFFSET_CAPTURE);
    }

    /*
     * Attempts to find a signature in $input using a different regex. Returns matches.
     */
    function findsig2($input, &$matches) {
        return preg_match_all(
         "/\[\[[Uu]ser(?:[\s_][Tt]alk)?\:([^\]\/\|]*)"        //5: "[[User:XX/PageAboutMe" links (notice no end tag)
        ."|\[\[[Ss]pecial\:[Cc]ontributions\/([^\|\]]*)/"
        ,$input,$matches,PREG_OFFSET_CAPTURE);
    }

    /*
     * Attempts to find a signature in $input. Returns the name of the user, false on failure.
     */
    function findsiginline($input, &$iffy) {
        $iffy = 0;

        $parsee_array = explode("\n",$input);
        for ($n = 0; $n < count($parsee_array); $n++) { //This for will terminate when a sig is found.
            $parsee = $parsee_array[$n];
            //Okay, let's try and remove "copied from above" messages. If the line has more than one timestamp, we'll disregard anything after the first.
            //Note: we're ignoring people who use custom timestamps - if these peoples' votes are moved, the mover's name will show up as having voted.

            //If more than one timestamp is found in the first portion of the vote:
            $tsmatches = array();
            $dummymatches = array();
            if (preg_match_all('/'.TSREGEX.'/',$parsee,$tsmatches,PREG_OFFSET_CAPTURE) > 1) {
                //Go through each timestamp-section, looking for a signature
                foreach ($tsmatches[0] as $minisection) {
                    $temp = substr($parsee,0,$minisection[1]);
                    //If a signature is found, stop and use it as voter
                    if ($this->findsig($temp,$dummymatches) != 0) {            //KNOWN ISSUE: Write description later
                        $parsee = $temp;
                        break;
                    }
                }
            }

            //Start the main signature-finding:
            $matches = array();
            if ($this->findsig($parsee,$matches) == 0) {
                //Okay, signature not found. Let's try the backup regex
                if ($this->findsig2($parsee,$matches) == 0) {
                    //Signature was not found in this iteration of the main loop :(
                    continue; //Go on to next newline (may be iffy)
                } else {
                    $merged = array_merge($matches[1],$matches[2]);
                }
            } else {
                //Merge the match arrays:
                $merged = array_merge($matches[5],$matches[1],$matches[3],$matches[2],$matches[4]);
            }
            //Remove blank values and arrays of the form ('',-1):
            foreach($merged as $key => $value) {
                if (is_array($value) && ($value[0] == '') && ($value[1] == -1)) {
                    unset($merged[$key]);
                } elseif ($value == "") {
                    unset($merged[$key]);
                }
            }

            //Let's find out the real signature
            $keys = array();
            $values = array();
            foreach ($merged as $mergee) {
                $keys[] = $mergee[0];
                $values[] = $mergee[1];
            }
            //Now sort:
            array_multisort($values, SORT_DESC, SORT_NUMERIC, $keys);
            //Now we should have the most relevant match (i.e., the sig) at the top of $keys
            $i = 0; $foundsig = '';
            while ($foundsig == '') {
                $foundsig = trim($keys[$i++]);
                if ($i == count($keys)) break;    //If we can only find blank usernames in the sig, catch overflow
                                                //Also fires when the first sig is also the last sig, so not an error
            }

            //Set iffy flag (level 1) if went beyond first line
            if ($n > 0) {
                $iffy = 1;
            }
            return $foundsig; //Found!
        }

        return False; //Signature not found
    }

    /*
     * Analyzes an RFA section. Returns an array of parsed signatures on success. Undefined behaviour on failure.
     */
    function analyze_section($input) {
        //Remove trailing sharp, if any
        $input = preg_replace('/#\s*$/','',$input);

        //Old preg_split regex: "/(^|\n)\s*\#[^\#\:\*]/"
        $parsed = preg_split("/(^|\n)\#/",$input);
        //Shift off first empty element:
        array_shift($parsed);

        foreach ($parsed as &$parsee) { //Foreach line
            //If the line is empty for some reason, ignore
            $parsee = trim($parsee);
            if (empty($parsee)) continue;

            //If the line has been indented (disabled), or is a comment, ignore
            if (($parsee[0] == ':') || ($parsee[0] == '*') || ($parsee[0] == '#')) { $parsee = '///###///'; continue; }; //struck-out vote or comment

            $parsedsig = $this->findsiginline($parsee, $iffy); //Find signature
            $orgsig = $parsee;
            $parsee = array();
            $parsee['context'] = $orgsig;
            if ($parsedsig === False) {
                $parsee['error'] = 'Signature not found';
            } else {
                $parsee['name'] = $parsedsig;
            }
            if (@$iffy == 1) {
                $parsee['iffy'] = '1';
            }
        } //Foreach line

        if ( (count($parsed) == 1) && (@trim($parsed[0]['name']) == '') ) { //filters out placeholder sharp sign used in empty sections
            $parsed = array();
        }

        //Delete struck-out keys "continued" in foreach
        foreach($parsed as $key => $value) {
            if($value == '///###///') {
                unset($parsed[$key]);
            }
        }

        return $parsed;
    }

    /*
     * Analyzes an RFA. Returns TRUE on success, FALSE on failure
     */
    function analyze($rawwikitext) {
        $split  = preg_split("/^(?:(?:'''|(?:<includeonly><noin<\/includeonly><includeonly>clude><\/includeonly>)?={4,5}(?:<includeonly><\/noin<\/includeonly><includeonly>clude><\/includeonly>''')?)"
        ."\s*?(Support|Oppose|Neutral|Comments)\s*?(?:'''|(?:'''<includeonly><noin<\/includeonly><includeonly>clude><\/includeonly>)?={4,5}(?:<includeonly><\/noin<\/includeonly><includeonly>clude><\/includeonly>)?)|;\s*(Support|Oppose|Neutral|Comments))\s*(?:<br>|<br \/>)?\s*$/im"
         ,$rawwikitext, -1, PREG_SPLIT_DELIM_CAPTURE);

        $header = array_shift($split);

        //=== Deal with the header ===//
        $header = str_ireplace(array('<nowiki>','</nowiki>'),'',$header);

        if (preg_match("/===\s*\[\[User:(.*?)\|.*?\]\]\s*===/",$header,$matches)) {
            $this->username = $matches[1];
        } elseif (preg_match("/===\s*\[\[.*?\|(.*?)\]\]\s*===/",$header,$matches)) {
            $this->username = $matches[1];
        }

        $header = str_replace(array('[[',']]'),'',$header);

        if (preg_match("/end(?:ing|ed)?(?: no earlier than)? (.*?) \(UTC\)/i",$header,$matches)) {
            $this->enddate = $matches[1];
        }
        //=== End header stuff ===//

        //Now parse through each non-header section, figuring out what they are
        //Nothing expected = 0, Support = 1, Oppose = 2, Neutral = 3
        $nextsection = 0;

        foreach ($split as $splut) {
            $splut = trim($splut);
            if (empty($splut)) {
                continue;
            }

            if (strcasecmp($splut,'Support') == 0) {
                $nextsection = 1;
            } elseif (strcasecmp($splut,'Oppose') == 0) {
                $nextsection = 2;
            } elseif (strcasecmp($splut,'Neutral') == 0) {
                $nextsection = 3;
            } else {
                switch ($nextsection) {
                    case 1:
                        $support = $splut;
                        break;
                    case 2:
                        $oppose = $splut;
                        break;
                    case 3:
                        $neutral = $splut;
                        break;
                }
                $nextsection = 0;
            }
        }

        if (!isset($support)) { $this->lasterror = "Support section not found"; return false; }
        if (!isset($oppose))  { $this->lasterror = "Oppose section not found";  return false; }
        if (!isset($neutral)) { $this->lasterror = "Neutral section not found"; return false; }

        $this->support = $this->analyze_section($support);
        $this->oppose  = $this->analyze_section($oppose);
        $this->neutral = $this->analyze_section($neutral);

        //Merge all votes in one array and sort:
        $m = array();
        foreach ($this->support as $s) { if (isset($s['name'])) $m[] = $s['name']; }
        foreach ($this->oppose  as $o) { if (isset($o['name'])) $m[] = $o['name']; }
        foreach ($this->neutral as $n) { if (isset($n['name'])) $m[] = $n['name']; }
        sort($m);
        //Find duplicates:
        for ($i = 0; $i < count($m); $i++) {
            if ($i != count($m)-1) {
                if ($m[$i] == $m[$i+1]) {
                    $this->duplicates[] = $m[$i];
                }
            }
        }

        return True;
    }
}
?>