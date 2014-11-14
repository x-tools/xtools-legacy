<?php 

class RangeContribs{
	
	private $items = array(
				"cidr" => array(),
				"user" => array(),
				"ip" => array(),
				"error" => array(),
				"sqlconds" => array(),
				"matchingIPs" => array(),
				"byrange" => array(),
			);
	
	private $contribs = array(
			"data" => array(), 
			"byname" => array(), 
			"bytime" => array() 
		);
	
	function getContribs(){
		return $this->contribs;
	}
	function getItems(){
		return $this->items;
	}

	
	function __construct( &$dbr, &$wt, $input, $begin=null, $end=null, $limit=20, $namespace=null ){
		
		$namespace = ($namespace == "all" ) ? null : intval($namespace); 
		
		$this->checkType( $input );
		$this->fetchMatchingIPs( $dbr, $begin, $end, $namespace );
		$this->fetchIPInformation( $wt );
		$this->fetchContribs( $dbr, $limit, $begin, $end, $namespace );
	}
	
	
	/**
	 * Check input types: cidr, ip's, names or mixed and determine dql conditions
	 * @param string $input textfield
	 * @return void
	 */
	private function checkType( $input ){
		
		$this->items["sqlconds"]["like"] = array();
		$this->items["sqlconds"]["equal"] = array();
		
		$lines = str_replace( "|", "\n", $input );
		$lines = explode( "\n", $lines );
		
		foreach ($lines as $line ){
			
			if ( !$line ) 
				continue;
			
			$tmp = trim(preg_replace('/[^\d|\.|\/]/','', $line));
			if( preg_match( '/^\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}\/\d{1,2}$/', $tmp ) === 1 ) {
				$this->items["cidr"][$tmp] = array();
			}
			elseif( preg_match( '/^\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}$/', $tmp ) === 1 ) {
				$this->items["ip"][$tmp] = 0 ;
			}
			elseif( preg_match( '/[\/]/', $line ) === 1 ) {
				$this->items["error"][$line] = 0 ; 
			}
			else{
				$this->items["user"][$line] = 0 ;
			}
		}
		
		foreach ( $this->items["cidr"] as $item => $crap ){
			$cidr_info = $this->calcCIDR( $item );
			$this->items["cidr"][$item]["cidrinfo"] = $cidr_info;
			$this->items["sqlconds"]["like"][] = $this->findMatch( $cidr_info['begin'], $cidr_info['end'] );
		}
		
		foreach ( $this->items["user"] as $item => $crap){
			$this->items["sqlconds"]["equal"][] = $item;
		}
		
		//autogroup single ips -- work on it
#		$cidr_info = $this->calcRange( array_keys( $this->items["ip"] ) );
#		
#		//if the range is too small (1-2) or too big >4096 ip's, we assume it's just a bunch of single ip's (and no range)
#		if ( $cidr_info["count"] > 2 && $cidr_info["count"] <= 4096 ){
#
#			$this->items["ip"]["cidrinfo"] = $cidr_info;
#			$this->sqlconds["like"][] = $this->findMatch( $cidr_info['begin'], $cidr_info['end'] );
#		}
#		else {
			foreach ( $this->items["ip"] as $item => $crap){
				$this->items["sqlconds"]["equal"][] = $item;
			}	
#		}
		

#print_r($this->items);
#print_r($this->sqlconds);
	}
	
	/**
	 * Construct query from sql conditions in sqlconds
	 */
	private function fetchMatchingIPs( &$dbr, $begin, $end, $namespace ){
		
		$period = ($begin) ? " AND rev_timestamp > '".str_replace("-", "", $begin)."'" : " AND rev_timestamp > 1 ";
		$period .= ($end) ? " AND rev_timestamp < '".str_replace("-", "", $end)."'" : "" ;
		$ns = ($namespace === null ) ?  "" : " AND page_namespace = '$namespace' ";
		
		$i = 0;
		foreach ($this->items["sqlconds"]["like"] as $item ){
			$like[] = " rev_user_text LIKE '".$dbr->strencode($item)."%' ";
			$i++;
		}
		if( $i>0 ){ 
			$conds[] = " (( ".implode(" OR ", $like).") AND rev_user = 0 ) "; 
		}
		
		
		$i = 0;
		foreach ($this->items["sqlconds"]["equal"] as $item ){
			$in[] = " '".$dbr->strencode($item)."' ";
			$i++; 
		}
		if( $i > 0) {
			$conds[] = "rev_user_text IN (".implode(",", $in).") ";
		}
		
		$conditions = implode( " OR " , $conds );
		
		$query = "
			SELECT rev_user_text, count(rev_user_text) as sum
			FROM revision_userindex
			JOIN page ON page_id = rev_page
			WHERE $conditions $period $ns
			Group by rev_user_text
			Order by INET_ATON(rev_user_text)
		";
		
		$this->items["matchingIPs"] = $dbr->query( $query );

#print_r($query);
#print_r($this->items);

	}
	
	
	private function fetchContribs( $dbr, $limit, $begin, $end, $namespace ){
		
		if ( count( $this->items["matchingIPs"] ) == 0 )
			return; 
		
		$period = ($begin) ? " AND rev_timestamp > '".str_replace("-", "", $begin)."'" : " AND rev_timestamp > 1 ";
		$period .= ($end) ? " AND rev_timestamp < '".str_replace("-", "", $end)."'" : "" ;
		$ns = ($namespace === null) ?  "" : " AND page_namespace = '$namespace' ";
	
		foreach ( $this->items["matchingIPs"] as $i => $matchingIP ){
				
			$ip = $matchingIP["rev_user_text"];
			$sum = $matchingIP["sum"];
				
			$query[] = "
					(SELECT '$sum' as sum, b.page_title, b.rev_id, b.rev_user_text, b.rev_timestamp, 
							b.rev_minor_edit, b.rev_comment, b.page_namespace
					From(
						SELECT page_title, rev_id, rev_user_text, rev_timestamp, rev_minor_edit, rev_comment, page_namespace
						FROM revision_userindex
						JOIN page ON page_id = rev_page
						WHERE rev_user_text = '$ip' $period $ns
						ORDER BY rev_user_text ASC, rev_timestamp DESC
						LIMIT $limit
					) as b)
			";
		}
	
		$this->contribs["data"] = $dbr->query( implode(" UNION ", $query ) );
		
		//building indeces
		foreach ( $this->contribs["data"] as $i => $row ){
			$this->contribs["byname"][$i] = $row["rev_user_text"].$row["rev_timestamp"];
			$this->contribs["bytime"][$i] = $row["rev_timestamp"] ;
		}
		
		arsort( $this->contribs["byname"] );
		arsort( $this->contribs["bytime"] );
		
	}
	

	
	
	/**
	 * Get some ripe information about the IP 
	 */
	private function fetchIPInformation( &$wt ){
		
		$this->items["byrange"] = array();
		$ranges = array();
		$i = 0;
		
		foreach ( $this->items["matchingIPs"] as $e => $matchingIP ){
			
			$ip = $matchingIP["rev_user_text"];
			$count = $matchingIP["sum"];
			$ipval = ip2long( $ip );
			
			if ( long2ip( $ipval ) != $ip ) {
				$this->items["byrange"]["user"]["list"][$ip] = $count;
				continue ; 
			}
			
			$match = false;
			foreach ( $ranges as $range ){
				if ( $ipval >= $range->minval && $ipval <= $range->maxval  ) {
					
					$this->items["byrange"][$range->inetnum]["list"][$ip] = $count;
					
					$match = true;
					break;
				}
			}
			
			if ( $match ) { continue; }
			
			$apiUrl = 'http://rest.db.ripe.net/search.json?query-string='.trim($ip).'&flags=no-irt&flags=no-referenced&flags=resource';
			$result = json_decode( $wt->gethttp( $apiUrl ) );
			$ripeAttributes = $result->objects->object[0]->attributes->attribute ;
			$i++;
			
			$ranges[$i] = new stdClass();
			foreach ( $ripeAttributes as $u => $attribute ){
				
				if ($attribute->name == "inetnum") { 
					
					$tmpRange = explode(" - ", $attribute->value );
					
					$ranges[$i]->inetnum  = $attribute->value; 
					$ranges[$i]->min 	= $tmpRange[0];
					$ranges[$i]->minval = ip2long($tmpRange[0]);
					$ranges[$i]->max 	= $tmpRange[1];
					$ranges[$i]->maxval = ip2long($tmpRange[1]);
				}
				if ($attribute->name == "netname") { $ranges[$i]->netname = $attribute->value; }
				if ($attribute->name == "descr")   { $ranges[$i]->descr   = $attribute->value; }
				if ($attribute->name == "country") { $ranges[$i]->country = $attribute->value; }
			}

			if( strval($ranges[$i]->country) == "" ){
				$apiUrl = "http://api.hostip.info/get_json.php?ip=".$ip;
				$result = json_decode( $wt->gethttp( $apiUrl ) );
				$ranges[$i]->country = $result->country_code;
			}
			
			$this->items["byrange"][ $ranges[$i]->inetnum ]["rangeinfo"] = $ranges[$i];
			$this->items["byrange"][ $ranges[$i]->inetnum ]["list"][$ip] = $count;
			
		}
#print_r($ranges);
#print_r($this->items);
		
		//Loop again and assign values to the ip's

		foreach ( $this->matchingIPs as $u => $matchingIP ){
			
			$ip = $matchingIP["rev_user_text"];
			$ipval = ip2long($ip);
			
			foreach ( $ranges as $range ){
				if ( $ipval >= $range->minval && $ipval <= $range->maxval  ) {
					$this->matchingIPs[$u]["inetnum"] = $range->inetnum;
					$this->matchingIPs[$u]["netname"] = $range->netname;
					$this->matchingIPs[$u]["descr"] = $range->descr;
					$this->matchingIPs[$u]["country"] = $range->country;
					
					break;
				}
			}
		}
	}

	
	public function calcCIDR( $cidr ) {

		$cidr = explode("/", $cidr);
	
		$cidr_base = $cidr[0];
		$cidr_range = $cidr[1];

		$cidr_base_bin = self::addZero( decbin( ip2long( $cidr_base ) ) );
#		$cidr_base_bin = self::ip2bin( $cidr_base );
	
		$cidr_shortened = substr( $cidr_base_bin, 0, $cidr_range );
#		$cidr_shortened = substr( implode( '', $cidr_base_bin ), 0, $cidr_range );
		$cidr_difference = 32 - $cidr_range;
	
		$cidr_begin = $cidr_shortened . str_repeat( '0', $cidr_difference );
		$cidr_end = $cidr_shortened . str_repeat( '1', $cidr_difference );
	
		$ip_begin = long2ip( bindec( self::removeZero( $cidr_begin ) ) );
		$ip_end = long2ip( bindec( self::removeZero( $cidr_end ) ) );
		$ip_count = bindec( $cidr_end ) - bindec( $cidr_begin ) + 1;
		 
		return array( 
				'begin' => $ip_begin, 
				'end' => $ip_end, 
				'count' => $ip_count, 
				'shortened' => $cidr_shortened, 
				'suffix' => $cidr_range 
			);
	}
	
	public function calcRange( $iparray ) {

		$iparray = array_unique($iparray);
		$iparray = array_map("ip2long",$iparray);
		sort($iparray);
		$iparray = array_map("long2ip",$iparray);
		 
		$ip_begin = $iparray[0];
		$ip_end = $iparray[ count($iparray) - 1 ];
		 
		$ip_begin_bin = self::addZero( decbin( ip2long( $ip_begin ) ) );
		$ip_end_bin = self::addZero( decbin( ip2long( $ip_end ) ) );
		 
		$ip_shortened = self::findMatch( $ip_begin_bin, $ip_end_bin );
		$cidr_range = strlen( $ip_shortened );
		$cidr_difference = 32 - $cidr_range;
		 
		$cidr_begin = $ip_shortened . str_repeat( '0', $cidr_difference );
		$cidr_end = $ip_shortened . str_repeat( '1', $cidr_difference );
		 
		$ip_count = bindec( $cidr_end ) - bindec( $cidr_begin ) + 1;
	
		return array( 
				'begin' => $ip_begin, 
				'end' => $ip_end, 
				'count' => $ip_count, 
				'shortened' => $ip_shortened, 
				'suffix' => $cidr_range 
			);
	}
	
	public function findMatch( $ip1, $ip2 ) {
		$ip1 = str_split( $ip1, 1 );
		$ip2 = str_split( $ip2, 1 );
			
		$match = null;
		foreach ( $ip1 as $val => $char ) {
			if( $char != $ip2[$val] ) break;
	
			$match .= $char;
		}
			
		return $match;
	}
	
	
	// from ipcalc, maybe outdated
// 	function calcRange2( $iparray ) {
// 		print_r($iparray);
// 		$iparray = array_unique($iparray);
// 		$iparray = array_map("ip2long",$iparray[0]);
// 		sort($iparray);
// 		$iparray = array_map("long2ip",$iparray);
	
// 		$ip_begin = $iparray[0];
// 		$ip_end = $iparray[ count($iparray) - 1 ];
	
// 		$ip_begin_bin = self::ip2bin( $ip_begin );
// 		$ip_end_bin = self::ip2bin( $ip_end );
	
// 		$ip_shortened = self::findMatch( implode( '', $ip_begin_bin ), implode( '', $ip_end_bin ) );
// 		$cidr_range = strlen( $ip_shortened );
// 		$cidr_difference = 32 - $cidr_range;
	
// 		$cidr_begin = $ip_shortened . str_repeat( '0', $cidr_difference );
// 		$cidr_end = $ip_shortened . str_repeat( '1', $cidr_difference );
	
// 		$ip_count = bindec( $cidr_end ) - bindec( $cidr_begin ) + 1;
	
// 		$ips = array();
// 		foreach( $iparray as $ip ) {
// 			$ips[] = array(
// 					'ip' => $ip,
// 					'bin' => implode( '.', self::ip2bin( $ip ) ),
// 					'rdns' => gethostbyaddr( $ip ),
// 					'long' => ip2long( $ip ),
// 					'hex' => implode( '.', self::ip2hex( $ip ) ),
// 					'octal' => implode( '.', self::ip2oct( $ip ) ),
// 					'radians' => implode( '/', self::ip2rad( $ip ) ),
// 					'base64' => implode( '.', self::ip264( $ip ) ),
// 					'alpha' => implode( '.', self::ip2alpha( $ip ) ),
// 			);
// 		}
	
// 		usort( $ips, array( 'IPCalc', 'ipsort' ) );
	
// 		$tmp = self::calcCIDR( $ip_begin . '/' . $cidr_range );
	
// 		return array(
// 				'begin' => $tmp['begin'],
// 				'end' => $tmp['end'],
// 				'count' => $tmp['count'],
// 				'suffix' => $cidr_range,
// 				'ips' => $ips
// 		);
// 	}
	
	public function addZero ( $string ) {
		$count = 32 - strlen( $string );
		for( $i = $count; $i>0; $i-- ) {
			$string = "0" . $string;
		}
		return $string;
	}
	
	function addZero2( $string, $len = 32 ) {
		$count = $len - strlen( $string );
		for( $i = $count; $i > 0; $i-- ) {
			$string = "0" . $string;
		}
		return $string;
	}
	
	public function removeZero ( $string ) {
		$string = str_split( $string, 1 );
		foreach( $string as $val => $strchar ) {
			if( $strchar == 1 ) break;
	
			unset( $string[$val] );
		}
		 
		$string = implode( "", $string );
		return $string;
	}
	
	

	
	
	function ipsort( $ip1, $ip2 ) {
		return strnatcmp( sprintf('%u', $ip1['long'] ), sprintf('%u', $ip2['long'] ) );
	}
	
	function ip2bin( $ip ) {
		$tmp = explode( '.', $ip );
	
		foreach( $tmp as $key => $val ) {
			$tmp[$key] = self::addZero( decbin( $val ), 8 ) ;
		}
	
		return $tmp;
	}
	
	function ip2hex( $ip ) {
		$tmp = explode( '.', $ip );
	
		foreach( $tmp as $key => $val ) {
			$tmp[$key] = self::addZero( dechex( $val ), 2 );
		}
	
		return $tmp;
	}
	
	function ip2oct( $ip ) {
		$tmp = explode( '.', $ip );
	
		foreach( $tmp as $key => $val ) {
			$tmp[$key] = self::addZero( decoct( $val ), 3 );
		}
	
		return $tmp;
	}
	
	function ip2rad( $ip ) {
		$tmp = explode( '.', $ip );
	
		foreach( $tmp as $key => $val ) {
			$tmp[$key] = deg2rad( $val );
		}
	
		return $tmp;
	}
	
	function ip264( $ip ) {
		$tmp = explode( '.', $ip );
	
		foreach( $tmp as $key => $val ) {
			$tmp[$key] = base64_encode( $val );
		}
	
		return $tmp;
	}
	
	function ip2alpha( $ip ) {
		$tmp = explode( '.', $ip );
	
		foreach( $tmp as $key => $val ) {
			$tmp[$key] = str_replace( array( 1, 2, 3, 4, 5, 6, 7, 8, 9, 0 ), array( 'A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J' ), $val );
		}
	
		return $tmp;
	}

//unfinished code
	function ipCalculator(){
		//Start the calculation
		if( $type == 'range' ) {
			$cidr_info = $base->calcCIDR( $cidr );
		}
		elseif( $type == 'list' ) {
			#preg_match_all( '/((((25[0-5]|2[0-4][0-9])|([0-1]?[0-9]?[0-9]))\.){3}((25[0-5]|2[0-4][0-9])|([0-1]?[0-9]?[0-9])){1})/', $cidr, $m );
		
			$m = array("142.10.14.28","148.69.145.3");
			$cidr_info = $base->calcRange( $m );
			print_r($cidr_info);
			$ips = array();
		
			foreach( $cidr_info['ips'] as $ip ) {
				$tmp = "<h3>{$ip['ip']}</h3>";
		
				$tmp .= "<ul>";/*'bin' => implode( '.', self::ip2bin( $ip ) ),
				'rdns' => gethostbyaddr( $ip ),
				'long' => ip2long( $ip ),
				'hex' => implode( '.', self::ip2hex( $ip ) ),
				'octal' => implode( '.', self::ip2oct( $ip ) ),
				'radians' => implode( '.', self::ip2rad( $ip ) ),
				'base64'*/
		
				$tmp .= "<li>Reverse DNS: {$ip['rdns']}</li>";
				$tmp .= "<li>Network address: {$ip['long']}</li>";
				$tmp .= "<li>Binary: {$ip['bin']}</li>";
		
				if( isset( $_GET['fun'] ) ) {
					$tmp .= "<li>Hexadecimal: {$ip['hex']}</li>";
					$tmp .= "<li>Octal: {$ip['octal']}</li>";
					$tmp .= "<li>Radians: {$ip['radians']}</li>";
					$tmp .= "<li>Base 64: {$ip['base64']}</li>";
					$tmp .= "<li>Letters: {$ip['alpha']}</li>";
				}
				$tmp .= "<li>More info: " .
						"<a href=\"//ws.arin.net/whois/?queryinput={$ip['ip']}\">WHOIS</a> · " .
						"<a href=\"//toolserver.org/~luxo/contributions/contributions.php?user={$ip['ip']}\">Global Contribs</a> · " .
						"<a href=\"//www.robtex.com/rbls/{$ip['ip']}.html\">RBLs</a> · " .
						"<a href=\"//www.dnsstuff.com/tools/tracert.ch?ip={$ip['ip']}\">Traceroute</a> · " .
						"<a href=\"//www.infosniper.net/index.php?ip_address={$ip['ip']}\">Geolocate</a> · " .
						"<a href=\"//toolserver.org/~overlordq/scripts/checktor.fcgi?ip={$ip['ip']}\">TOR</a> · " .
						"<a href=\"//www.google.com/search?hl=en&q={$ip['ip']}\">Google</a> · "
						."</li>";
		
				$tmp .= "</ul>";
		
				$list = $tmp;
			}
		
		}
		else {
			$wt->error = 'Invalid type selected.' ;
		}
	}
}