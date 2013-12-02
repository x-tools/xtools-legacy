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
along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/

class Graph {
	private $mColors;
	private $mNames;
	private $mMonthly;
	private $mGross;
	private $miPhone;
	
	function __construct( $data, $iphone = false ) {
		$this->mColors = $data['colors'];
		$this->mNames = $data['names'];
		$this->mMonthly = $data['monthly'];
		$this->mGross = $data['gross'];
		$this->miPhone = $iphone;
		
		ksort( $this->mMonthly );
	}
	
	function pie( $title = '', $bgcolor = "00000000" ) {
		global $phptemp;
		
		$url = "http://chart.apis.google.com/chart?cht=p3&chd=t:";
		
		$pcts = $this->getPercentages( $this->mGross );
		
		$url .= implode( ',', $pcts );
		
		$url .= "&chs=600x300&chl=" . implode( '|', $pcts );
		
		$tmp = array();
		foreach( $this->mNames as $val => $name ) {
			if( isset( $pcts[$val] ) ) $tmp[] = $name;
		}
		
		$url .= "&chdl=" . implode( '|', $tmp );
		
		$tmp = array();
		foreach( $this->mColors as $val => $color ) {
			if( isset( $pcts[$val] ) ) $tmp[] = $color;
		}
		
		$url .= "&chco=" . implode( '|', $tmp );
		
		if( $title != '' ) $url .= "&chtt=".urlencode($title);	
		
		$url .= "&chf=bg,s,$bgcolor";	
		
		return "<img src=\"".str_replace('&', '&amp;',$url)."\" alt=\"". $phptemp->getConf( 'graphalt' ) ."\" />";
	}
	
	function horizontalBarForYear( $data, $width = 500 ) {
		

		$month_total_edits = array();
		foreach( $data as $year => $tmp ) {
			$month_total_edits[$year] = $tmp['all'];
		}
		
		$max_width = max( $month_total_edits );
		
		$pixels = array();
		foreach( $data as $year => $tmp ) {
			if( $tmp['all'] == 0 ) $pixels[$year] = array();
			
			foreach( $nsdata as $nsid => $count ) {
				$pixels[$month][$nsid] = ceil(($width * $count) / $max_width);
			}
		}
		
		$msg = "<table class=\"months\">\n";
		
		$imsg = "<table>";
		
		foreach( $pixels as $month => $namespace_counts ) {
			$msg .= "<tr>\n";
			$imsg .= "<tr class=\"months\">\n";
			
			if( $month_total_edits[$month] != "0" ) {
				$msg .= "<td onmouseover=\"popup('".htmlentities($this->getMonthPopup( $month ), ENT_QUOTES, 'UTF-8')."')\" onmouseout=\"popout()\" class=\"date\">$month</td><td>".$month_total_edits[$month]."</td>\n";
			    $imsg .= "<td class=\"date\">$month</td><td>{$month_total_edits[$month]}</td>";
			}
			else {
				$msg .= "<td class=\"date\">$month</td><td>".$month_total_edits[$month]."</td>\n";
			    $imsg .= "<td class=\"date\">$month</td><td>{$month_total_edits[$month]}</td>";
			}
			
			ksort( $namespace_counts );
			
			$msg .= "<td>";
			$imsg .= "<td>";
			
			if( $month_total_edits[$month] != "0" ) {
				$msg .= "<div class=\"outer_bar\" onmouseover=\"popup('".htmlentities($this->getMonthPopup( $month ), ENT_QUOTES, 'UTF-8')."')\" onmouseout=\"popout()\">";
			    $imsg .= "<div class=\"outer_bar\">";
			}
			
			foreach( $namespace_counts as $namespace_id => $pixel ) {
				$msg .= "<div class=\"bar\" style=\"border-left:" . $pixel . "px solid #" . $this->mColors[$namespace_id] . "\">";
			    $imsg .= "<div class=\"bar\" style=\"border-left:" . $pixel . "px solid #" . $this->mColors[$namespace_id] . "\">";
			}
			
			$msg .= str_repeat( "</div>", count( $namespace_counts ) );
			$imsg .= str_repeat( "</div>", count( $namespace_counts ) );
			
			if( $month_total_edits[$month] != "0" ) {
				$msg .= "</div>";
				$imsg .= "</div>";
			}
			
			$msg .= "</td></tr>\n";
            $imsg .= "</td></tr>\n";
		}
		
		$msg .= "</table>";
		$imsg .= "</table>";
		
		if( $this->miPhone === true ) { return $imsg; }
		else { return $msg; }
	}
	
	function horizontalBar( $width = 500 ) {
		
		if( $this->miPhone ) $width = 150;
		
		$this->fillMonthList();
		
		$month_total_edits = array();
		foreach( $this->mMonthly as $month => $edits ) {
			$month_total_edits[$month] = ($edits == array()) ? 0 : array_sum($edits);
		}
		
		$max_width = max( $month_total_edits );
		
		$pixels = array();
		foreach( $this->mMonthly as $month => $nsdata ) {
			if( count( $nsdata ) == 0 ) $pixels[$month] = array();
			foreach( $nsdata as $nsid => $count ) {
				$pixels[$month][$nsid] = ceil(($width * $count) / $max_width);
			}
		}
		
		$msg = "<table class=\"months\">\n";
		
		$imsg = "<table>";
		
		foreach( $pixels as $month => $namespace_counts ) {
			$msg .= "<tr>\n";
			$imsg .= "<tr class=\"months\">\n";
			
			if( $month_total_edits[$month] != "0" ) {
				$msg .= "<td onmouseover=\"popup('".htmlentities($this->getMonthPopup( $month ), ENT_QUOTES, 'UTF-8')."')\" onmouseout=\"popout()\" class=\"date\">$month</td><td>".$month_total_edits[$month]."</td>\n";
			    $imsg .= "<td class=\"date\">$month</td><td>{$month_total_edits[$month]}</td>";
			}
			else {
				$msg .= "<td class=\"date\">$month</td><td>".$month_total_edits[$month]."</td>\n";
			    $imsg .= "<td class=\"date\">$month</td><td>{$month_total_edits[$month]}</td>";
			}
			
			ksort( $namespace_counts );
			
			$msg .= "<td>";
			$imsg .= "<td>";
			
			if( $month_total_edits[$month] != "0" ) {
				$msg .= "<div class=\"outer_bar\" onmouseover=\"popup('".htmlentities($this->getMonthPopup( $month ), ENT_QUOTES, 'UTF-8')."')\" onmouseout=\"popout()\">";
			    $imsg .= "<div class=\"outer_bar\">";
			}
			
			foreach( $namespace_counts as $namespace_id => $pixel ) {
				$msg .= "<div class=\"bar\" style=\"border-left:" . $pixel . "px solid #" . $this->mColors[$namespace_id] . "\">";
			    $imsg .= "<div class=\"bar\" style=\"border-left:" . $pixel . "px solid #" . $this->mColors[$namespace_id] . "\">";
			}
			
			$msg .= str_repeat( "</div>", count( $namespace_counts ) );
			$imsg .= str_repeat( "</div>", count( $namespace_counts ) );
			
			if( $month_total_edits[$month] != "0" ) {
				$msg .= "</div>";
				$imsg .= "</div>";
			}
			
			$msg .= "</td></tr>\n";
            $imsg .= "</td></tr>\n";
		}
		
		$msg .= "</table>";
		$imsg .= "</table>";
		
		if( $this->miPhone === true ) { return $imsg; }
		else { return $msg; }
	}
	
	function fillMonthList() {
		$new_monthlist = array();
		$last_monthkey = null;
		
		foreach( $this->mMonthly as $month => $null ) {
			$str = explode( '/', $month );
			$str = strtotime( $str[0] . "-" . $str[1] . "-01" );
			if( !isset( $first_month ) ) $first_month = $str;
			$last_month = $str;
		}
		
		for( $date = $first_month; $date <= $last_month; $date += 10*24*60*60 ) {
			$monthkey = date( 'Y/m', $date );

			if( $monthkey != $last_monthkey ) {
				$new_monthlist[] = $monthkey;
				$last_monthkey = $monthkey;
			}
		}
		
		$monthkey = date( 'Y/m', str_replace( '/', '', $last_month ) );
		
		if( $monthkey != $last_monthkey ) {
			$new_monthlist[] = $monthkey;
			$last_monthkey = $monthkey;
		}
		
		foreach( $new_monthlist as $month ) {
			if( !isset( $this->mMonthly[$month] ) ) {
				$this->mMonthly[$month] = array();
			}
		}
		
		ksort( $this->mMonthly );

	}
	
	function legend( $table = '', $tr = '', $td = '') {
		$pcts = $this->getPercentages( $this->mGross, false );
		
		$ret = "<table $table>\n";
		foreach( $this->mNames as $id => $namespace ) {
			if( !$this->mGross[$id] ) continue;
			$ret .= "<tr $tr><td $td style=\"border: 1px solid #000;background:#{$this->mColors[$id]};\" >" . 
				$namespace . 
				"</td><td $td>".$this->mGross[$id]."</td><td $td>" . 
				$pcts[$id] . "%</td></tr>\n";
		}
		$ret .= "</table>";
		
		return $ret;
	}
	
	function getMonthPopup( $month ) {
		global $wgNamespaces, $phptemp;
		
		$out = "<table>";
		
		$e = 0;
		foreach( $this->mMonthly[$month] as $ns_id => $count ) {
			$sum = number_format( ( ( $count / array_sum( $this->mMonthly[$month] ) ) * 100 ), 2 );
			if( $count == 0 ) continue;
			if( $e == 1 ) {
				$out .= "<tr style=\"background-color: #dde\">";
				$e = 0;
			}
			else {
				$out .= "<tr>";
				$e = 1;
			}
			$out .= "<td>" . $wgNamespaces['names'][$ns_id] . "</td><td style=\"border-left: 1px solid #aaa;border-right: 1px solid #aaa;\">$count " . $phptemp->getConf( 'edits' ) . "</td><td>$sum%</td>";
			$out .= "</tr>";
			//echo $phptemp->getConf( 'edits' ) . "<br />";
		}
		
		$out .= "</table>";
		return $out;

	}
	
	function getPercentages( $data, $hidesmall = true ) {
		$i = 0;
		foreach( $data as $v => $m ) {
			$i += $m;
		}
		
		
		foreach( $data as $v => $n ) {
			$data[$v] = number_format( ( $n / $i ) * 100, 2 );
			if( round( number_format( ( $n / $i ) * 100, 2 ) ) < 0.5 && $hidesmall ) {
				unset( $data[$v] );
			}
		}
		
		return $data;
	}
}
