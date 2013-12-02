<?php

/*
Soxred93's Edit Counter
Copyright (C) 2010 Soxred93

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program.  If not, see <//www.gnu.org/licenses/>.
*/

class Functions {
	
	function getFullUrl() {
		global $_SERVER;
		return '//' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
	}
	
	function getNamespaces( $db_name ) {
		global $http;
		
		//The toolserver database is incomplete, falling back to API
		/*global $tdbr;
		
		$res = Database :: mysql2array( $tdbr->select(
			'namespacename',
			array(
				'ns_id',
				'ns_name',
			),
			array(
				array(
					'dbname',
					'=',
					$db_name
				),
				array(
					'ns_type',
					'=',
					'primary'
				),
				array(
					'ns_id',
					'>',
					-1
				),
			),
			array(
				'ORDER BY' => 'ns_id ASC'
			)
		));*/
		
		$res = $http->getnamespaces();
		return $res;
	}
	
	function calcTimes( $time ) {
		return array(
			'time' => number_format(microtime( 1 ) - $time, 2, '.', ''),
			'memory' => number_format((memory_get_usage() / (1024 * 1024)), 2, '.', '')
		);
	}
	
	function pre( $array ) {
		echo "<pre>";
		print_r( $array );
		echo "</pre>";
	}
	
	function toDie( $msg ) {
		global $content;
		$content->assign( "error", $msg );
		$this->assignContent();
	}
	
	function assignContent() {
		global $phptemp, $content, $lang, $langlinks;
		$phptemp->assign( "content", $content->display( true ) );
		$phptemp->assign( "curlang", $lang );
		$phptemp->assign( "langlinks", $langlinks );
		$phptemp->display(); 
		die();
	}
	//THIS NEEDS UPDATING AS SOON AS LABS IS READY
	function getDBInfo( $lang, $wiki ) {
		/*global $dbr;
		
		$res = Database :: mysql2array( $dbr->select(
			'wiki',
			array(
				'dbname',
				'server',
			),
			array(
				array(
					'domain',
					'=',
					"$lang.$wiki.org"
				)
			)
		));
	 
		if( !count( $res ) ) {
			return array( 'error' => 'nowiki' );
		}
		 */	
		if( $wiki == 'wikipedia' || $wiki == 'wikimedia' ) $wiki = "wiki";
		$res['server'] = $lang.$wiki.".labsdb";
		$res['dbname'] = $lang.$wiki."_p";
		
		if ($wiki == 'wikidata') {
    $res['dbname'] = 'wikidatawiki_p';
    $res['server'] = 'wikidatawiki.labsdb';
		}

		return $res;
	}
	
	function getReplag() {
		global $dbr;
		
		$res = Database :: mysql2array( $dbr->select(
			'recentchanges_userindex',
			'UNIX_TIMESTAMP() - UNIX_TIMESTAMP(rc_timestamp) as replag',
			null,
			array( 
				'ORDER BY' => 'rc_timestamp DESC',
				'LIMIT' => 1
			)
		));
		
		$seconds = floor($res[0]['replag']);
		$text = $this->formatReplag($seconds);
	    
	    return array($seconds,$text);

	}
	
	function formatReplag($secs) {
		global $phptemp;
		$second = 1;
		$minute = $second * 60;
		$hour = $minute * 60;
		$day = $hour * 24;
		$week = $day * 7;
		
		$r = array();
		if ($secs > $week) {
			$r[] = floor($secs/$week) . ' ' . $phptemp->getConf( 'w' );
			$secs %= $week;
		}
		if ($secs > $day) {
			$r[] = floor($secs/$day) . ' ' . $phptemp->getConf( 'd' );
			$secs %= $day;
		}
		if ($secs > $hour) {
			$r[] = floor($secs/$hour) . ' ' . $phptemp->getConf( 'h' );
			$secs %= $hour;
		}
		if ($secs > $minute) {
			$r[] = floor($secs/$minute) . ' ' . $phptemp->getConf( 'm' );
			$secs %= $week;
		}
		if ($secs > $second) {
			$r[] = floor(($secs/$second)/100) . ' ' . $phptemp->getConf( 's' );
		}
		
		$r = implode( ', ', $r );
		return $r;
	}
	
}
