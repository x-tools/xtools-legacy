<?php
/**
 * RfA Analysis Library, modified for use with Peachy
 * Copyright (C) 2006 Tangotango (tangotango.wp _at_ gmail _dot_ com)
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 * 
 * Modified for German Adminkandidaturen 
 * (c) Hedonil 2014
 * 
 */


/**
 * An RFA object contains the parsed information for an RFA
 */
class RFAde {

	protected $username = false;
	protected $enddate = false;
	protected $support = array();
	protected $oppose = array();
	protected $neutral = array();
	protected $duplicates = array();
	protected $lasterror = '';

	/**
	 * Analyzes an RFA. Returns TRUE on success, FALSE on failure
	 * @param Wiki $wiki Peachy Wiki Class
	 * @param $page	Peachy Page class
	 * @param null $rawwikitext - raw page text (for use without Peachy)
	 */
	public function __construct( Wiki $wiki, $page, $rawwikitext = null ) {
		if( is_null( $rawwikitext ) ){
			$pageObj = $wiki->initPage( $page );
			$rawwikitext = $pageObj->get_text();
		}


#var_export($rawwikitext);
		//Get the page sections & section names
		$sections = array();
		preg_match_all(	"/"	. "^(\=[=]?\s*[^\/\n]*\s*\=[\=]?)\s*$" . "/m" , $rawwikitext, $sections);
		
		$splitterRegex = array();
		foreach ( $sections[1] as $sectionName ){
			$sectionClear = trim( str_replace("=", "", $sectionName) );
			$sectionName = trim( $sectionName );
			
			if ( preg_match("/^Pro/", $sectionClear ) > 0 ) 
				$sectionType["support"] = $sectionName ;
			
			if ( preg_match("/^(?:Kontra|Contra)/", $sectionClear ) > 0 )
				$sectionType["oppose"] = $sectionName ;
			
			if ( preg_match("/^Enthaltung[en]*/", $sectionClear ) > 0 )
				$sectionType["neutral"] = $sectionName ;
			
			if ( preg_match("/^Kommentare/", $sectionClear ) > 0 )
				$sectionType["comments"] = $sectionName ;

			$splitterRegex[] .= "(" .preg_quote($sectionName). ")";
				
		}
#print_r($sections);die;

		$split = preg_split(
			"/"
			. "(?:" . implode( "|", $splitterRegex ) . ")"
			. "/"
			, $rawwikitext, -1, PREG_SPLIT_DELIM_CAPTURE);
			
#print_r($split);die;
		
		$header = array_shift( $split );

		//=== Deal with the header ===//
		$header = str_ireplace( array( '<nowiki>', '</nowiki>' ), '', $header );

		if( preg_match( "/===\s*\[\[User:(.*?)\|.*?\]\]\s*===/", $header, $matches ) ) {
			$this->username = $matches[1];
		} elseif( preg_match( "/===\s*\[\[.*?\|(.*?)\]\]\s*===/", $header, $matches ) ) {
			$this->username = $matches[1];
		}

		$header = str_replace( array( '[[', ']]' ), '', $header );

		if( preg_match( "/(?: endet am) '*(.*) Uhr/i", $header, $matches ) ) {
			$this->enddate = $matches[1];
		}
		//=== End header stuff ===//

		//Now parse through each non-header section, figuring out what they are
		//Nothing expected = 0, Support = 1, Oppose = 2, Neutral = 3
		$nextsection = 0;
#print_r($sectionType);
#print_r($split);die;
		foreach( $split as $i => $splut ){
			$splut = trim( $splut );
			
			if( empty( $splut ) ) {
				continue;
			}

			if( strcasecmp( $splut, $sectionType["support"] ) == 0 ) {
				$support = $split[ $i + 1 ];
			} 
			elseif( strcasecmp( $splut, $sectionType["oppose"] ) == 0 ) {
				$oppose = $split[ $i + 1 ];
			} 
			elseif( strcasecmp( $splut, $sectionType["neutral"] ) == 0 ) {
				$neutral = $split[ $i + 1 ];
			} 

		}

		if( !isset( $support ) ) {
			$this->lasterror = "Support section not found";
			return false;
		}
		if( !isset( $oppose ) ) {
			$this->lasterror = "Oppose section not found";
			return false;
		}
		if( !isset( $neutral ) ) {
			$this->lasterror = "Neutral section not found";
			return false;
		}

#print_r($support);
#print_r($oppose);
#print_r($neutral);die;


		$this->support = $this->analyzeSection( $support );
		$this->oppose = $this->analyzeSection( $oppose );
		$this->neutral = $this->analyzeSection( $neutral );
		
		//Merge all votes in one array and sort:
		$m = array();
		foreach( $this->support as $s ){
			if( isset( $s['name'] ) ) $m[] = $s['name'];
		}
		foreach( $this->oppose as $o ){
			if( isset( $o['name'] ) ) $m[] = $o['name'];
		}
		foreach( $this->neutral as $n ){
			if( isset( $n['name'] ) ) $m[] = $n['name'];
		}
		sort( $m );
		//Find duplicates:
		for( $i = 0; $i < count( $m ); $i++ ){
			if( $i != count( $m ) - 1 ) {
				if( $m[$i] == $m[$i + 1] ) {
					$this->duplicates[] = $m[$i];
				}
			}
		}

		return true;
	}

	public function get_username() {
		return $this->username;
	}

	public function get_enddate() {
		return $this->enddate;
	}

	public function get_support() {
		return $this->support;
	}

	public function get_oppose() {
		return $this->oppose;
	}

	public function get_neutral() {
		return $this->neutral;
	}

	public function get_duplicates() {
		return $this->duplicates;
	}

	public function get_lasterror() {
		return $this->lasterror;
	}

	/**
	 * Attempts to find a signature in $input using the default regex. Returns matches.
	 * @param $input
	 * @param $matches
	 *
	 * @return int
	 */
	protected function findSig( $input, &$matches ) {
		//Supports User: and User talk: wikilinks, {{fullurl}}, unsubsted {{unsigned}}, unsubsted {{unsigned2}}, anything that looks like a custom sig template
		return preg_match_all(
			"/"
			. "\[\[[Uu]ser(?:[\s_][Tt]alk)?\:([^\]\|\/]*)(?:\|[^\]]*)?\]\]" //1: Normal [[User:XX]] and [[User talk:XX]]
			. "|\[\[[Bb]enutzer[in]*(?:[\s_][Dd]iskussion)?\:([^\]\|\/]*)(?:\|[^\]]*)?\]\]" //1: Normal [[User:XX]] and [[User talk:XX]]
			. "|\{\{(?:[Ff]ullurl\:[Bb]enutzer(?:[\s_][Dd]iskussion)?\:|[Uu]nsigned\|)([^\}\|]*)(?:|[\|\}]*)?\}\}" //2: {{fullurl}} and {{unsigned}} templates
			. "|(?:\{\{)[Bb]enutzer(?:[\s_][Dd]iskussion)?\:([^\}\/\|]*)" //3: {{User:XX/sig}} templates
			. "|(?:\[\[)[Bb]enutzer\:([^\]\/\|]*)\/[Ss]ig[\|\]]" //5: [[User:XX/sig]] links (compromise measure)
			. "|\[\[[Bb][Dd]\:([^\]\|\/]*)(?:\|[^\]]*)?\]\]"
			. "/"
			, $input, $matches, PREG_OFFSET_CAPTURE
		);
	}

	/**
	 * Attempts to find a signature in $input using a different regex. Returns matches.
	 * @param $input
	 * @param $matches
	 *
	 * @return int
	 */
	protected function findSigAlt( $input, &$matches ) {
		return preg_match_all(
			"/"
			. "\[\[[Bb]enutzer(?:[\s_][Dd]iskussion)?\:([^\]\/\|]*)" //5: "[[User:XX/PageAboutMe" links (notice no end tag)
			. "|\[\[[Ss]pezial\:[Bb]eiträge\/([^\|\]]*)"
			. "|\{\{[Uu]nsigned2\|[^\|]*\|([^\}]*)\}\}" //4: {{unsigned2|Date|XX}} templates
			. "/"
			, $input, $matches, PREG_OFFSET_CAPTURE
		);
	}

	/**
	 * Attempts to find a signature in $input. Returns the name of the user, false on failure.
	 * @param $input
	 * @param $iffy
	 *
	 * @return bool|string false if not found Signature, or the Signature if it is found
	 */
	protected function findSigInLine( $input, &$iffy ) {
		$iffy = 0;

		$parsee_array = explode( "\n", $input );
#print_r($parsee_array);
		for( $n = 0; $n < count( $parsee_array ); $n++ ){ //This for will terminate when a sig is found.
			$parsee = $parsee_array[$n];
#print_r($parsee."\n");
			//Okay, let's try and remove "copied from above" messages. If the line has more than one timestamp, we'll disregard anything after the first.
			//Note: we're ignoring people who use custom timestamps - if these peoples' votes are moved, the mover's name will show up as having voted.

			//If more than one timestamp is found in the first portion of the vote:
			$tsmatches = array();
			$dummymatches = array();
			
			if( preg_match_all( 
					"/" 
					. "[0-2][0-9]\:[0-5][0-9],\s[1-3]?[0-9]\.\s(?:Jan|Feb|Mär|Apr|Mai|Jun|Jul|Aug|Sep|Okt|Nov|Dez)\.\s\d{4}\s\(CE[S]?T\)"
					. "/"
					, $parsee, $tsmatches, PREG_OFFSET_CAPTURE ) > 0 ) {
#print_r($parsee."\n");
				//Go through each timestamp-section, looking for a signature
				foreach( $tsmatches[0] as $minisection ){
					$temp = substr( $parsee, 0, $minisection[1] );
#print_r($temp);
					//If a signature is found, stop and use it as voter
					if( $this->findSig( $temp, $dummymatches ) != 0 ) { //KNOWN ISSUE: Write description later
						$parsee = $temp;
						break;
					}
				}
			}

#print_r($parsee."\n");
			
			//Start the main signature-finding:
			$matches = array();
			if( $this->findSig( $parsee, $matches ) == 0 ) {
				//Okay, signature not found. Let's try the backup regex
				if( $this->findSigAlt( $parsee, $matches ) == 0 ) {
					//Signature was not found in this iteration of the main loop :(
					continue; //Go on to next newline (may be iffy)
				} 
				else {
					$merged = array_merge( $matches[1], $matches[2] );
				}
			} 
			else {
				//Merge the match arrays:
				$merged = array_merge(  $matches[5], $matches[1], $matches[3], $matches[2], $matches[4], $matches[6] );
			}
		
			//Remove blank values and arrays of the form ('',-1):
			foreach( $merged as $key => $value ){
				if( is_array( $value ) && ( $value[0] == '' ) && ( $value[1] == -1 ) ) {
					unset( $merged[$key] );
				} 
				elseif( $value == "" ) {
					unset( $merged[$key] );
				}
			}

			//Let's find out the real signature
			$keys = array();
			$values = array();
			
			foreach( $merged as $mergee ){
				$keys[] = $mergee[0];
				$values[] = $mergee[1];
			}
			
			//Now sort:
			array_multisort( $values, SORT_DESC, SORT_NUMERIC, $keys );
			//Now we should have the most relevant match (i.e., the sig) at the top of $keys
			$i = 0;
			$foundsig = '';
			while( $foundsig == '' ){
				$foundsig = trim( $keys[$i++] );
				if( $i >= count( $keys ) ) break; //If we can only find blank usernames in the sig, catch overflow
				//Also fires when the first sig is also the last sig, so not an error
			}

			//Set iffy flag (level 1) if went beyond first line
			if( $n > 0 ) {
				$iffy = 1;
			}
			return $foundsig;
		}

		return false;
	}

	/**
	 * Analyzes an RFA section. Returns an array of parsed signatures on success. Undefined behaviour on failure.
	 * @param string $input
	 *
	 * @return array
	 */
	private function analyzeSection( $input ) {
		//Remove trailing sharp, if any
		$input = preg_replace( '/#\s*$/', '', $input );

		//Old preg_split regex: "/(^|\n)\s*\#[^\#\:\*]/"
		$parsed = preg_split( "/(^|\n)\#/", $input );
		//Shift off first empty element:
		array_shift( $parsed );

		foreach( $parsed as &$parsee ){ //Foreach line
			//If the line is empty for some reason, ignore
			$parsee = trim( $parsee );
			if( empty( $parsee ) ) continue;

			//If the line has been indented (disabled), or is a comment, ignore
			if( ( $parsee[0] == ':' ) || ( $parsee[0] == '*' ) || ( $parsee[0] == '#' ) ) {

				$parsee = '///###///';
				continue;
			}; //struck-out vote or comment

			$parsedsig = $this->findSigInLine( $parsee, $iffy ); //Find signature
			$orgsig = $parsee;
			$parsee = array();
			$parsee['context'] = $orgsig;
			
			if( $parsedsig === false ) {
				$parsee['error'] = 'Signature not found';
			} 
			else {
				$parsee['name'] = $parsedsig;
			}
			
			if( @$iffy == 1 ) {
				$parsee['iffy'] = '1';
			}
			
		} //Foreach line

		if( ( count( $parsed ) == 1 ) && ( @trim( $parsed[0]['name'] ) == '' ) ) { //filters out placeholder sharp sign used in empty sections
			$parsed = array();
		}

		//Delete struck-out keys "continued" in foreach
		foreach( $parsed as $key => $value ){
			if( $value == '///###///' ) {
				unset( $parsed[$key] );
			}
		}

		return $parsed;
	}

}
