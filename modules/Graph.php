<?php
define('PCHART_BASE_PATH', '/data/project/wikiviewstats/wvs_modules/pchart');
define('PCHART_FONTS_PATH', '/data/project/wikiviewstats/public_html/fonts');

include(PCHART_BASE_PATH."/pData.class.php");
include(PCHART_BASE_PATH."/pDraw.class.php");
include(PCHART_BASE_PATH."/pImage.class.php");
include(PCHART_BASE_PATH."/pPie.class.php");
include(PCHART_BASE_PATH."/pBubble.class.php");




class xGraph{
	
	private $font_color       = '#0044CC';
	private $background_color = '#DDFFFF';
	private $axis_color       = '#0066CC';
	private $grid_color       = '#3366CC';
	
	
	static function makeMiniPie( $data, $labels, $colors, $lang="en" ){
		global $wt;
		
		$font = PCHART_FONTS_PATH."/LiberationSans-Regular.ttf";
		if ( in_array( $wt->uselang, array("zh","ja","jp","ko") ) ){ $font = PCHART_FONTS_PATH."/wqy-microhei.ttf"; }
		if ( in_array( $wt->uselang, array("he","bn","vi","fa","ar","th", "ta","ka","hi","hy","ml") ) ) { $font = PCHART_FONTS_PATH."/unifont.ttf"; }
		
		$MyData = new pData();
		$MyData->addPoints( $data,"ScoreA");
		$MyData->setSerieDescription("ScoreA","Application A");
		
		$MyData->addPoints( $labels ,"Labels");
		$MyData->setAbscissa("Labels");
		
		$myPicture = new pImage(350,100,$MyData,TRUE);
		$myPicture->setFontProperties(array("FontName"=> $font,"FontSize"=>9));
		#		$myPicture->drawText( 100, 10, $title, array("Align"=>"TEXT_ALIGN_MIDDLE_MIDDLE", "R"=>0,"G"=>0,"B"=>0));
		
		$PieChart = new pPie($myPicture,$MyData);
		foreach ( $colors as $i => $color){
			$PieChart->setSliceColor( $i, self::hex2rgb($color, 200, true) );
		}
		
		$PieChart->draw2DPie(80,50,array("Radius" => 42, "SecondPass"=>true, "DrawLabels"=>false,"Border"=>TRUE));
		
		$myPicture->setFontProperties(array("FontName"=> $font,"FontSize"=>9,"R"=>34,"G"=>34,"B"=>34));
		$PieChart->drawPieLegend(150,30,array("Style"=>LEGEND_NOBORDER,"BoxSize"=>12, "Mode"=>LEGEND_VERTICAL));
		
		ob_start();
			imagepng($myPicture->Picture);
			$imgdata = ob_get_contents();
		ob_end_clean();
		$rimg =  "data:image/png;base64,".base64_encode($imgdata);
		
		return $rimg;
	}
	
	static function makePieGoogle( $data, $title = NULL ){
	
		$ff = array_values($data);
		$sum = array_sum( $ff );
		
		foreach( array_values($data) as $value ){
			$pctdata[] = number_format( ($value / $sum)*100 , 1);
			$labels[] = "bla";
		}
		
		foreach( array_keys($data) as $nsid ){
			$colors[] = str_replace("#", "", self::GetColorList( $nsid ));
		} 
		
		
		$MyData = new pData();
		$MyData->addPoints( $pctdata,"ScoreA");
		$MyData->setSerieDescription("ScoreA","Application A");
		
		$MyData->addPoints( $labels ,"Labels");
		$MyData->setAbscissa("Labels");
		
		$myPicture = new pImage(250,250,$MyData,TRUE);
		$myPicture->setFontProperties(array("FontName"=> PCHART_FONTS_PATH."/LiberationSans-Regular.ttf","FontSize"=>9));
#		$myPicture->drawText( 100, 10, $title, array("Align"=>"TEXT_ALIGN_MIDDLE_MIDDLE", "R"=>0,"G"=>0,"B"=>0));
		
		$PieChart = new pPie($myPicture,$MyData);
		foreach ( $colors as $i => $color){
			$PieChart->setSliceColor( $i, self::hex2rgb($color, 200, true) );
		}
		
		$PieChart->draw2DPie(125,125,array("Radius" => 120, "SecondPass"=>true, "DrawLabels"=>false,"Border"=>TRUE));
		
		$myPicture->setFontProperties(array("FontName"=>PCHART_FONTS_PATH."/LiberationSans-Regular.ttf","FontSize"=>10,"R"=>34,"G"=>34,"B"=>34));
#		$PieChart->drawPieLegend(320,55,array("Style"=>LEGEND_NOBORDER,"BoxSize"=>12, "Mode"=>LEGEND_VERTICAL));
		
		ob_start();
			imagepng($myPicture->Picture);
			$imgdata = ob_get_contents();
		ob_end_clean();
		$rimg =  "data:image/png;base64,".base64_encode($imgdata);
		
		return $rimg;
	}
	
	
	static function makeBarPageViews( $title, $data, $lang ){
		global $wt;
		
		if ( !isset($data->series->s0->Data) ){ return null; }
		
		$font = PCHART_FONTS_PATH."/LiberationSans-Regular.ttf";
		if ( in_array( $wt->uselang, array("zh","ja","jp","ko") ) ){ $font = PCHART_FONTS_PATH."/wqy-microhei.ttf"; }
		if ( in_array( $wt->uselang, array("he","bn","vi","fa","ar","th", "ta","ka","hi","hy","ml") ) ) { $font = PCHART_FONTS_PATH."/unifont.ttf"; }
		
		$MyData = new pData();
		$MyData->addPoints( $data->series->s0->Data ,"ScoreA");
		$MyData->setSerieDescription("ScoreA","Application A");
		$MyData->Data["Series"]["ScoreA"]["Color"] = self::hex2rgb( "#BCD2EE", 200, true );//"rgba(82,168,236,0.8)";
		
		$MyData->addPoints( $data->series->Time->Data ,"Labels");
		$MyData->setAbscissa("Labels");
		
		$myPicture = new pImage(1000,300,$MyData,TRUE);
		$myPicture->setFontProperties(array("FontName"=> $font,"FontSize"=>9));
		$myPicture->drawText( 420, 20, $title, array( "R"=>0,"G"=>0,"B"=>0));
		
		$myPicture->setGraphArea(50,30,950,270);
		$myPicture->setFontProperties(array("FontName"=> $font,"FontSize"=>8));
		
		$scaleSettings = array("AxisR"=>134,"AxisG"=>134,"AxisB"=>134,"AxisAplha"=>40,"DrawSubTicks"=>TRUE,"CycleBackground"=>TRUE, "LabelRotation"=>0, "LabelSkip"=>0, "Mode"=>SCALE_MODE_START0) ;
		$myPicture->drawScale($scaleSettings);
		
		$settings = array("Surrounding"=>-30,"InnerSurrounding"=>10,"RecordImageMap"=>false, "DisplayValues"=>false);
		$myPicture->drawBarChart($settings);
		
		
		
		ob_start();
			imagepng( $myPicture->Picture );
			$imgdata = ob_get_contents();
		ob_end_clean();
		$rimg =  "data:image/png;base64,".base64_encode($imgdata);
		
		return $rimg;
		
	}
	
	static function makePieTopEditors( $title, $total, &$data, $lang ){
		global $wt, $I18N;
		
		$font = PCHART_FONTS_PATH."/LiberationSans-Regular.ttf";
		if ( in_array( $wt->uselang, array("zh","ja","jp","ko") ) ){ $font = PCHART_FONTS_PATH."/wqy-microhei.ttf"; }
		if ( in_array( $wt->uselang, array("he","bn","vi","fa","ar","th", "ta","ka","hi","hy","ml") ) ) { $font = PCHART_FONTS_PATH."/unifont.ttf"; }
		
		$i =0;
		foreach ($data as $user => $details){
			$val = number_format( ($details["all"] / $total)*100,1);
			$users[] = $user." &middot; $val%";
			$values[] = $val;
			$colors[] = str_replace("#", "", self::GetColorList( $i ));
			$i++;
			if ($i == 9) break;
		}
		$users[] = $I18N->msg( "others" );
		$colors[] = str_replace("#", "", self::GetColorList( 100 ));
		$values[] = 100-array_sum($values);
		
		
		$MyData = new pData();
		$MyData->addPoints( $values,"ScoreA");
		$MyData->setSerieDescription("ScoreA","Application A");
		
		$MyData->addPoints( $users ,"Labels");
		$MyData->setAbscissa("Labels");
		
		$myPicture = new pImage(500,250,$MyData,TRUE);
		$myPicture->setFontProperties(array("FontName"=> $font,"FontSize"=>9));
		$myPicture->drawText( 150, 16, $title, array( "R"=>0,"G"=>0,"B"=>0));
		
		$PieChart = new pPie($myPicture,$MyData);
		foreach ( $colors as $i => $color){
			$PieChart->setSliceColor( $i, self::hex2rgb($color, 200, true) );
		}
		
		$PieChart->draw2DPie(150,140,array("Radius" => 100, "SecondPass"=>true, "DrawLabels"=>false,"Border"=>TRUE));
		
		$myPicture->setFontProperties(array("FontName" => $font,"FontSize"=>9,"R"=>34,"G"=>34,"B"=>34));
		$PieChart->drawPieLegend(320,55,array("Style"=>LEGEND_NOBORDER,"BoxSize"=>12, "Mode"=>LEGEND_VERTICAL));
		
		ob_start();
			imagepng($myPicture->Picture);
			$imgdata = ob_get_contents();
		ob_end_clean();
		$rimg =  "data:image/png;base64,".base64_encode($imgdata);
		
		return $rimg;
	}
	
	function hex2rgb($hex, $opacity, $asArray=false) {
		$hex = str_replace("#", "", $hex);
	
		if(strlen($hex) == 3) {
			$r = hexdec(substr($hex,0,1).substr($hex,0,1));
			$g = hexdec(substr($hex,1,1).substr($hex,1,1));
			$b = hexdec(substr($hex,2,1).substr($hex,2,1));
		} else {
			$r = hexdec(substr($hex,0,2));
			$g = hexdec(substr($hex,2,2));
			$b = hexdec(substr($hex,4,2));
		}
	
		if ( $asArray ){
			return array("R"=>$r, "G"=>$g, "B"=>$b, "Alpha"=>$opacity);
		}
		else{
			return "rgba(".$r.",".$g.",".$b.",".$opacity.")";
		}
	}
	
	
	static function makeTimecardBubble( $data, $lang="en" ){
		global $wt;
		
		$font = PCHART_FONTS_PATH."/LiberationSans-Regular.ttf";
		if ( in_array( $wt->uselang, array("zh","ja","jp","ko") ) ){ $font = PCHART_FONTS_PATH."/wqy-microhei.ttf"; }
		if ( in_array( $wt->uselang, array("he","bn","vi","fa","ar","th", "ta","ka","hi","hy","ml") ) ) { $font = PCHART_FONTS_PATH."/unifont.ttf"; }
		
		$MyData = new pData();
			
		ksort($data);
		
		//get max value
		$max = 0;
		foreach ($data as $i => $vals ){
			if ( max($vals) > $max ){ $max = max($vals); }
		}

		//loop through days of week
		for ( $i=0; $i <= 6; $i++ ){
			
			@ksort( $data[$i] );
			$seriesData = array();
			$seriesWeight = array();
			//Loop through timerange 0=0-3h, 1=4-7h...
			for ( $u=0; $u <= 5; $u++ ){
				$seriesData[] = $i*100;
				$seriesWeight[] = ($max) ? intval( ($data[$i][$u] / $max)*100 ) : 0;
			}

			$MyData->addPoints( $seriesData, "data".$i );
			$MyData->addPoints( $seriesWeight, "weight".$i );
		}
		
		$MyData->addPoints(array("0-4h","4-8h","8-12h","12-16h","16-20h","20-24h"),"xtime");
		$MyData->setAbscissa("xtime");
		$MyData->setAxisDisplay(0,AXIS_FORMAT_CUSTOM, "YAxisFormatDay");
		
		$myPicture = new pImage(900,280,$MyData, true);
		$myPicture->setGraphArea(90,10,850,240);
		$myPicture->setFontProperties(array("FontName" => $font,"FontSize"=>10,"R"=>34,"G"=>34,"B"=>34));
		
		$scaleSettings = array("GridR"=>200,"GridG"=>200,"GridB"=>200,"DrawSubTicks"=>TRUE,"CycleBackground"=>false);
		$myPicture->drawScale($scaleSettings);
		
		$myBubbleChart = new pBubble($myPicture,$MyData);
		
		$bubbleDataSeries = array("data0","data1","data2","data3","data4","data5","data6");
		$bubbleWeightSeries = array("weight0","weight1","weight2","weight3","weight4","weight5","weight6");
		
#		$myBubbleChart->bubbleScale( $bubbleDataSeries, $bubbleWeightSeries );
		$myBubbleChart->drawBubbleChart($bubbleDataSeries,$bubbleWeightSeries, array( "ForceAlpha"=>true));
		
#		
		
		
		
#		$myPicture->setFontProperties(array("FontName"=>PCHART_FONTS_PATH."/arial.ttf","FontSize"=>11,"R"=>34,"G"=>34,"B"=>34));
#		$myPicture->drawLegend(900,55,array("Style"=>LEGEND_NOBORDER,"BoxWidth"=>10,"BoxHeight"=>10, "Mode"=>LEGEND_VERTICAL));
		
		ob_start();
		 	imagepng( $myPicture->Picture );
			$imgdata = ob_get_contents();
		ob_end_clean();
		$rimg =  "data:image/png;base64,".base64_encode($imgdata);
		
		return $rimg;
		
	}
	
	static function makeChartArticle( $type, $data, $events, $colors, $lang="en" ){
		global $wt, $I18N;
		
		$font = PCHART_FONTS_PATH."/LiberationSans-Regular.ttf";
		if ( in_array( $wt->uselang, array("zh","ja","jp","ko") ) ){ $font = PCHART_FONTS_PATH."/wqy-microhei.ttf"; }
		if ( in_array( $wt->uselang, array("he","bn","vi","fa","ar","th", "ta","ka","hi","hy","ml") ) ) { $font = PCHART_FONTS_PATH."/unifont.ttf"; }
		
		$maxsizeTotal = 0;
		$maxeditTotal = 0;
		$u = 0;
		foreach( $data as $year => $values){
			$years[] = $year; 
			$all[] = $values["all"];
			$minor[] = $values["minor"];
			$anon[] = $values["anon"];
			
			$maxsize=0;
			//get the max size of each month
			foreach ( $values["months"] as $i => $mdetails ){
				if( $mdetails["size"] > $maxsize ){ $maxsize = $mdetails["size"]; }  
			}
			$tmpssize[] = ($maxsize) ? $maxsize : $tmpssize[ $u-1 ];
			
			$linemarker[] = 1;
			$eventmarker[] =  isset($events[ $year ]["protect"]) ? 10 + $events[ $year ]["protect"] * 3 : 0 ;
			$u++;
		}
		
		
		$msgAll = $I18N->msg('all');
		$msgMinor = $I18N->msg('minor');
		$msgPagesize = $I18N->msg('pagesize');
		
		
		$MyData = new pData();
		$MyData->addPoints( $all,"all");
		$MyData->addPoints( $minor,"minor");
		$MyData->addPoints( $anon,"anon");
		$MyData->addPoints( $tmpssize,"size");
		$MyData->addPoints( $eventmarker,"protect");
		
		$MyData->setSerieOnAxis("all",0 );
		$MyData->setSerieOnAxis("minor",0 );
		$MyData->setSerieOnAxis("anon",0 );
		$MyData->setSerieOnAxis("size",1 );
		$MyData->setSerieOnAxis("protect",1 );
		$MyData->setAxisPosition(1,AXIS_POSITION_RIGHT);
		$MyData->setAxisName(0, "Edits");
		$MyData->setAxisName(1, "Size (kb)");
		
		
		$MyData->Data["Series"]["all"]["Color"] = self::hex2rgb( $colors["all"], 200, true );
		$MyData->Data["Series"]["minor"]["Color"] = self::hex2rgb( $colors["minor"], 200, true );
		$MyData->Data["Series"]["anon"]["Color"] = self::hex2rgb( $colors["anon"], 200, true );
		$MyData->Data["Series"]["size"]["Color"] = self::hex2rgb( $colors["size"], 200, true );
		$MyData->Data["Series"]["protect"]["Color"] = self::hex2rgb( $colors["protect"], 200, true );
		
		$MyData->addPoints( $years ,"Labels");
		$MyData->setAbscissa("Labels");
		
		$myPicture = new pImage(1000,300,$MyData,TRUE);
		$myPicture->setFontProperties(array("FontName" => $font,"FontSize"=>12,"R"=>34,"G"=>34,"B"=>34));
		
		$myPicture->setGraphArea(50,30,820,270);
		$myPicture->setFontProperties(array("FontName" => $font,"FontSize"=>8));
		
		$scaleSettings = array("AxisR"=>134,"AxisG"=>134,"AxisB"=>134,"AxisAplha"=>40,"DrawSubTicks"=>TRUE,"CycleBackground"=>false, "LabelRotation"=>0, "LabelSkip"=>0, "Mode"=>SCALE_MODE_START0) ;
		$myPicture->drawScale($scaleSettings);
		
		$settings = array("Surrounding"=>-30,"InnerSurrounding"=>10,"RecordImageMap"=>false, "DisplayValues"=>false);
		$MyData->setSerieDrawable('size', false);
		$MyData->setSerieDrawable('protect', false);
		$myPicture->drawBarChart($settings);
		
		$MyData->setSerieDrawable('size', true);
		$MyData->setSerieDrawable('all', false);
		$MyData->setSerieDrawable('minor', false);
		$MyData->setSerieDrawable('anon', false);
		$MyData->setSerieDrawable('protect', false);
		
		
		
		$myPicture->drawLineChart();
		
		$MyData->setSerieDrawable('protect', true);
		$MyData->setSerieDrawable('size', true);
		
		
		
		
		
		$myBubbleChart = new pBubble($myPicture,$MyData);
				
		$bubbleDataSeries = array("size");
		$bubbleWeightSeries = array("protect");
		$myBubbleChart->bubbleScale( $bubbleDataSeries, $bubbleWeightSeries );
		
		
		
		$myBubbleChart->drawBubbleChart($bubbleDataSeries,$bubbleWeightSeries, array( "ForceAlpha"=>true));
		
		$myPicture->setFontProperties(array("FontName" => $font,"FontSize"=>11,"R"=>34,"G"=>34,"B"=>34));
		$MyData->setSerieDrawable('size', true);
		$MyData->setSerieDrawable('all', true);
		$MyData->setSerieDrawable('minor', true);
		$MyData->setSerieDrawable('anon', true);
		$MyData->setSerieDrawable('protect', true);
		$myPicture->drawLegend(900,55,array("Style"=>LEGEND_NOBORDER,"BoxWidth"=>10,"BoxHeight"=>10, "Mode"=>LEGEND_VERTICAL));
		
		ob_start();
			imagepng( $myPicture->Picture );
			$imgdata = ob_get_contents();
		ob_end_clean();
		$rimg =  "data:image/png;base64,".base64_encode($imgdata);
		
		return $rimg;
	
	}

		
	/**
	 * Legend for for edit counter namespace edits
	 * @param unknown $data
	 * @param unknown $namespaces
	 * @return string
	 */
	static function makeLegendTable( &$data, &$namespaces ){
		global $wt;
		
		$sum = array_sum( $data );
		$i = 0;
		$legendNS = '<table class="table-striped xt-table">';
		foreach ( $data as $nsid => $count ){

			$color = self::GetColorList($nsid);
			$legendNS .= '
			<tr>
			<td><span style="display:inline-block; border-radius:2px; height:14px; width:14px; margin-right:5px; background-color:'.$color.' "></span></td>
			<td>'.$namespaces["names"][$nsid].'</td>
			<td style="text-align:right; padding-left:15px;">'.$wt->numFmt($count).'</td>
			<td style="text-align:right; padding-left:10px;">'.$wt->numFmt( ($count/$sum)*100,1).'%</td>
			</tr>';
			
			$i++;
		}
		$legendNS .= "</table>";
		
		return $legendNS;
	}
	
	
	static function makeHorizontalBar( $type, $monthTotals, $width = 500, &$wgNamespaces ) {
		global $wt;

		if ($type == "year"){
			
			$tmp = array();
			foreach( $monthTotals as $month => $edits ) {
				$year = substr( $month, 0, 4);
				foreach( $monthTotals[$month] as $nsid => $count ) {
					if( !isset($tmp[$year][$nsid]) ){
						$tmp[$year][$nsid] = 0;
					}
					$tmp[$year][$nsid] += $count;
				}
			}
			$monthTotals = $tmp;
		}
		
		$month_total_edits = array();
		foreach( $monthTotals as $month => $edits ) {
			$month_total_edits[$month] = ($edits == array()) ? 0 : array_sum($edits);
		}
	
		$max_width = max( $month_total_edits );
	
		$pixels = array();
		foreach( $monthTotals as $month => $nsdata ) {
			if( count( $nsdata ) == 0 ) $pixels[$month] = array();
			
			foreach( $nsdata as $nsid => $count ) {
				$pixels[$month][$nsid] = ceil(($width * $count) / $max_width);
			}
		}
		
		$msg = '<table class="months table-condensed xt-table">';
		$imsg = '<table>';
	
		foreach( $pixels as $month => $namespace_counts ) {
			$msg .= '<tr>';
			$imsg .= '<tr class="months">';
			$monthshow = ( strlen($month) == 6 ) ? substr($month, 0, 4).'-'.substr($month, 4, 2) : $month;
			
			$mtem = $month_total_edits[$month];	
			if( $mtem != "0" ) {
				$msg .= '<td title="'.htmlentities( self::getMonthPopup( $monthTotals[$month], $month, $wgNamespaces ), ENT_QUOTES, 'UTF-8').'" class="date">'.$monthshow.'</td>
						 <td style="text-align:right; padding-right:5px;" >'. $wt->numFmt( $mtem ) .'</td>';
				$imsg .= '<td class="date" >'.$monthshow.'</td><td>'.$month_total_edits[$month].'</td>';
			}
			else {
				$msg .= '<td class="date" >'.$monthshow.'</td><td>'. $wt->numFmt( $mtem ) .'</td>';
				$imsg .= '<td class="date" >'.$monthshow.'</td><td>'. $wt->numFmt( $mtem ) .'</td>';
			}
				
			ksort( $namespace_counts );
				
			$msg .= "<td>";
			$imsg .= "<td>";
				
			if( $month_total_edits[$month] != "0" ) {
				$msg .= '<div class="outer_bar" title="'.htmlentities( self::getMonthPopup( $monthTotals[$month], $month, $wgNamespaces ), ENT_QUOTES, 'UTF-8').'" >';
				$imsg .= '<div class="outer_bar">';
			}
				
			foreach( $namespace_counts as $namespace_id => $pixel ) {
				$msg .= '<div class="bar" style="border-left:' . $pixel . 'px solid ' . self::GetColorList($namespace_id) . '" >';
				$imsg .= '<div class="bar" style="border-left:' . $pixel . 'px solid ' . self::GetColorList($namespace_id) . '" >';
			}
				
			$msg .= str_repeat( "</div>", count( $namespace_counts ) );
			$imsg .= str_repeat( "</div>", count( $namespace_counts ) );
				
			if( $month_total_edits[$month] != "0" ) {
				$msg .= "</div>";
				$imsg .= "</div>";
			}
				
			$msg .= "</td></tr>";
			$imsg .= "</td></tr>";
		}
	
		$msg .= "</table>";
		$imsg .= "</table>";

		return $msg;
#		if( $this->miPhone === true ) { return $imsg; }
#		else { return $msg; }
	}
	
	function getMonthPopup( $monthtotalsM, $month, &$wgNamespaces ) {
		
		ksort($monthtotalsM);
		$out = '';
		foreach( $monthtotalsM as $ns_id => $count ) {
			$sum = number_format( ( ( $count / array_sum( $monthtotalsM ) ) * 100 ), 2 );
			$out .= $wgNamespaces['names'][$ns_id] . ": $count edits ($sum%) \n";
		}
		return $month."\n".$out;
	
	}
	
	static function GetColorList( $num = false ) {
		$colors = array(
				0 => '#Cc0000',#'#FF005A', #red '#FF5555',
				1 => '#F7b7b7',
	
				2 => '#5c8d20',#'#008800', #green'#55FF55',
				3 => '#85eD82',
	
				4 => '#2E97E0', #blue
				5 => '#B9E3F9',
	
				6 => '#e1711d',  #orange
				7 => '#ffc04c',
	
				8 => '#FDFF98', #yellow
	
				9 => '#5555FF',
				10 => '#55FFFF',
	
				11 => '#0000C0',  #
				12 => '#008800',  # green
				13 => '#00C0C0',
				14 => '#FFAFAF',	# rosé
				15 => '#808080',	# gray
				16 => '#00C000',
				17 => '#404040',
				18 => '#C0C000',	# green
				19 => '#C000C0',
				
				100 => '#75A3D1',	# blue
				101 => '#A679D2',	# purple
				102 => '#660000',
				103 => '#000066',
				104 => '#FAFFAF',	# caramel
				105 => '#408345',
				106 => '#5c8d20',
				107 => '#e1711d',	# red
				108 => '#94ef2b',	# light green
				109 => '#756a4a',	# brown
				110 => '#6f1dab',
				111 => '#301e30',
				112 => '#5c9d96',
				113 => '#a8cd8c',	# earth green
				114 => '#f2b3f1',	# light purple
				115 => '#9b5828',
				118 => '#99FFFF',
				119 => '#99BBFF',
				120 => '#FF99FF',
				121 => '#CCFFFF',
				122 => '#CCFF00',
				123 => '#CCFFCC',
				200 => '#33FF00',
				201 => '#669900',
				202 => '#666666',
				203 => '#999999',
				204 => '#FFFFCC',
				205 => '#FF00CC',
				206 => '#FFFF00',
				207 => '#FFCC00',
				208 => '#FF0000',
				209 => '#FF6600',
				446 => '#06DCFB',
				447 => '#892EE4',
				460 => '#99FF66',
				461 => '#99CC66',	# green
				470 => '#CCCC33',	# ocker
				471 => '#CCFF33',
				480 => '#6699FF',
				481 => '#66FFFF',
				490 => '#995500',
				491 => '#998800',
				710 => '#FFCECE',
				711 => '#FFC8F2',
				828 => '#F7DE00',
				829 => '#BABA21',
				866 => '#FFFFFF',
				867 => '#FFCCFF',
				1198 => '#FF34B3',
				1199 => '#8B1C62',
	
				'#61a9f3',#blue
				'#f381b9',#pink
				'#61E3A9',
	
				'#D56DE2',
				'#85eD82',
				'#F7b7b7',
				'#CFDF49',
				'#88d8f2',
				'#07AF7B',#green
				'#B9E3F9',
				'#FFF3AD',
				'#EF606A',#red
				'#EC8833',
				'#FFF100',
				'#87C9A5',
				'#FFFB11',
				'#005EBC',
				'#9AEB67',
				'#FF4A26',
				'#FDFF98',
				'#6B7EFF',
				'#BCE02E',
				'#E0642E',
				'#E0D62E',
	
				'#02927F',
				'#FF005A',
				'#61a9f3', #blue' #FFFF55',
		);
	
				if( $num === false ) {
			return $colors;
	}
	else{
		return $colors[$num];
	}
	
	
	}

	static function oldGoolgestuff(){
		// 		$chartbase = "//chart.googleapis.com/chart?";
		// 		$chdata = array(
		// 				'cht' => 'bvg',
		// 				'chs' => '1000x200',
		// 				"chf" => "bg,s,00000000",
		// 				'chco' => $colors["all"].','.$colors["anon"].','.$colors["minor"].','.$colors["size"].','.$colors["protect"],
		// 				'chd' => 't3:'.implode(',', $all).'|'.implode(',', $anon).'|'.implode(',', $minor).'|'.implode(',', $size).'|'.implode(',', $dummyline),
		// 				'chdl' => "$msgAll|IPs|$msgMinor|$msgPagesize$msgProtect",
		// 				'chdlp'=> 'r|l',
		// 				'chds' => 'a',
		// 				'chbh' => '10,1,15',
		// 				'chxt' => 'y,y,x,r,r',
		// 				'chxl' => '1:||Edits||2:|'.implode('|', $years).'|4:||Size (kb)|',
		// 				'chxr' => '0,0,'.$maxeditTotal.'|3,0,'.$maxsizeTotal,
		// 				'chm' => "D,737373,3,0,1,1|o,737373,3,-1,4.0|".implode("|", $eventmarker),
		
		// 		$chartbase = "//chart.googleapis.com/chart?";
		// 		$chdata = array(
		// 				'cht' => 'bvg',
		// 				"chtt" => $charttitle,
		// 				'chs' => '1000x250',
		// 				"chf" => "bg,s,00000000",
		// 				'chco' => $gcolor1,
		// 				'chd' => 't1:'.implode(",", $ai->pageviews->series->s0->Data),
		// 				'chds' => 'a',
		// 				'chbh' => '10,1,5',
		// 				'chxt' => 'y,y,x',
		// 				'chxl' => '1:||Hits||2:|'.implode('|', $ai->pageviews->series->Time->Data),
		// 				//			'chxr' => '0,0,'.$maxeditTotal.'|3,0,'.$maxsizeTotal,
		// 		);
		
		// 		$chartbase = "//chart.googleapis.com/chart?";
		// 		$chdata = array(
		// 				"cht" => "p",
		// 				"chs" => "250x250",
		// 				"chp" => '-1.55',
		// 				"chf" => "bg,s,00000000",
		// 				"chd" => "t:".implode(",", $pctdata),
		// 				"chl" => "", //implode("|", array_keys($data)),
		// 				"chco" => implode("|", $colors)
		
		// 			);
	}
	
}

function YAxisFormatDay($value) {
	global $wt;
	
	$fmt = new IntlDateFormatter( $wt->uselang, IntlDateFormatter::FULL, IntlDateFormatter::FULL, 'UTC',IntlDateFormatter::GREGORIAN, 'EEEE');
	
	$ret = null;
	switch ($value) {
		case 0:
			$ret = 7; //"Sunday";
			break;
		case 100:
			$ret = 1; //"Monday";
			break;
		case 200:
			$ret = 2; //"Tuesday";
			break;
		case 300:
			$ret = 3; //"Wednesday";
			break;
		case 400:
			$ret = 4; //"Thursday";
			break;
		case 500:
			$ret = 5; //"Friday";
			break;
		case 600:
			$ret = 6; //"Saturday";
			break;

		default:
			return null;
			break;
	}
	
	return  $fmt->format( new DateTime("1980-09-0$ret 00:00:01") );

}

	
	
