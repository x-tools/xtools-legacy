<?php

class IPCalc {

	static function addZero( $string, $len = 32 ) {
		$count = $len - strlen( $string );	
		for( $i = $count; $i > 0; $i-- ) {
			$string = "0" . $string;
		}
		return $string;
	}
	
	static function removeZero( $string ) {
		$string = str_split( $string, 1 );
		foreach( $string as $val => $strchar ) {
			if( $strchar == 1 ) break;
			
			unset( $string[$val] );
		}
		
		$string = implode( "", $string );
		return $string;
	}
	
	static function calcCIDR( $cidr ) {
		$cidr = explode('/', $cidr);
	
		$cidr_base = $cidr[0];
		$cidr_range = $cidr[1];
	
		$cidr_base_bin = self::ip2bin( $cidr_base );
	
		$cidr_shortened = substr( implode( '', $cidr_base_bin ), 0, $cidr_range );
		$cidr_difference = 32 - $cidr_range;
	
		$cidr_begin = $cidr_shortened . str_repeat( '0', $cidr_difference );
		$cidr_end = $cidr_shortened . str_repeat( '1', $cidr_difference );
	
		$ip_begin = long2ip( bindec( self::removeZero( $cidr_begin ) ) );
		$ip_end = long2ip( bindec( self::removeZero( $cidr_end ) ) );
		$ip_count = bindec( $cidr_end ) - bindec( $cidr_begin ) + 1;
		
		return array( 'begin' => $ip_begin, 'end' => $ip_end, 'count' => $ip_count );
	}
	
	static function calcRange( $iparray ) {
		//print_r($iparray);
		$iparray = array_unique($iparray);
		$iparray = array_map("ip2long",$iparray[0]);
		sort($iparray);
		$iparray = array_map("long2ip",$iparray);
		
		$ip_begin = $iparray[0];
		$ip_end = $iparray[ count($iparray) - 1 ];
		
		$ip_begin_bin = self::ip2bin( $ip_begin );
		$ip_end_bin = self::ip2bin( $ip_end );
		
		$ip_shortened = self::findMatch( implode( '', $ip_begin_bin ), implode( '', $ip_end_bin ) );
		$cidr_range = strlen( $ip_shortened );
		$cidr_difference = 32 - $cidr_range;
		
		$cidr_begin = $ip_shortened . str_repeat( '0', $cidr_difference );
		$cidr_end = $ip_shortened . str_repeat( '1', $cidr_difference );
		
		$ip_count = bindec( $cidr_end ) - bindec( $cidr_begin ) + 1;
		
		$ips = array();
		foreach( $iparray as $ip ) {
			$ips[] = array(
				'ip' => $ip,
				'bin' => implode( '.', self::ip2bin( $ip ) ),
				'rdns' => gethostbyaddr( $ip ),
				'long' => ip2long( $ip ),
				'hex' => implode( '.', self::ip2hex( $ip ) ),
				'octal' => implode( '.', self::ip2oct( $ip ) ),
				'radians' => implode( '/', self::ip2rad( $ip ) ),
				'base64' => implode( '.', self::ip264( $ip ) ),
				'alpha' => implode( '.', self::ip2alpha( $ip ) ),
			);
		}
		
		usort( $ips, array( 'IPCalc', 'ipsort' ) );
		
		$tmp = self::calcCIDR( $ip_begin . '/' . $cidr_range );
		
		return array( 'begin' => $tmp['begin'], 'end' => $tmp['end'], 'count' => $tmp['count'], 'suffix' => $cidr_range, 'ips' => $ips );
	}
	
	static function ipsort( $ip1, $ip2 ) {
		return strnatcmp( sprintf('%u', $ip1['long'] ), sprintf('%u', $ip2['long'] ) );
	}
	
	static function ip2bin( $ip ) {
		$tmp = explode( '.', $ip );
		
		foreach( $tmp as $key => $val ) {
			$tmp[$key] = self::addZero( decbin( $val ), 8 ) ;
		}
		
		return $tmp;
	}
	
	static function ip2hex( $ip ) {
		$tmp = explode( '.', $ip );
		
		foreach( $tmp as $key => $val ) {
			$tmp[$key] = self::addZero( dechex( $val ), 2 );
		}
		
		return $tmp;
	}
	
	static function ip2oct( $ip ) {
		$tmp = explode( '.', $ip );
		
		foreach( $tmp as $key => $val ) {
			$tmp[$key] = self::addZero( decoct( $val ), 3 );
		}
		
		return $tmp;
	}
	
	static function ip2rad( $ip ) {
		$tmp = explode( '.', $ip );
		
		foreach( $tmp as $key => $val ) {
			$tmp[$key] = deg2rad( $val );
		}
		
		return $tmp;
	}
	
	static function ip264( $ip ) {
		$tmp = explode( '.', $ip );
		
		foreach( $tmp as $key => $val ) {
			$tmp[$key] = base64_encode( $val );
		}
		
		return $tmp;
	}
	
	static function ip2alpha( $ip ) {
		$tmp = explode( '.', $ip );
		
		foreach( $tmp as $key => $val ) {
			$tmp[$key] = str_replace( array( 1, 2, 3, 4, 5, 6, 7, 8, 9, 0 ), array( 'A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J' ), $val );
		}
		
		return $tmp;
	}
	
	static function findMatch( $ip1, $ip2 ) {
		$ip1 = str_split( $ip1, 1 );
		$ip2 = str_split( $ip2, 1 );
		
		$match = null;
		foreach ( $ip1 as $val => $char ) {
			if( $char != $ip2[$val] ) break;
			
			$match .= $char;
		}
		
		return $match;
	}
	
}