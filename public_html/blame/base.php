<?php 

class BlameBase{
	
	public static function getBlameResult( $wiki, $article, $nofollowredir, $text ){
		
		try {
			$site = Peachy::newWiki( null, null, null, 'http://'.$wiki.'/w/api.php' );
		} catch (Exception $e) {
			return null;
		}
		
		$pageClass = $site->initPage( $article, null, !$nofollowredir );

		$title = $pageClass->get_title();
		$history = $pageClass->history( null, 'older', true );
		
		$revs = array();
		foreach( $history as $id => $rev ) {
			if( ( $id + 1 ) == count( $history ) ) {
				if( in_string( $text, $rev['*'] , true ) ) $revs[] = self::parseRev( $rev, $wiki, $title );
			}
			else {
				if( in_string( $text, $rev['*'], true ) && !in_string( $text, $history[$id+1]['*'], true ) ) $revs[] = self::parseRev( $$rev, $wiki, $title );
			}
			unset( $rev );//Saves memory
		}
		
		return $revs;
	}
	
	static function parseRev( $rev, $wiki, $title ) {
	   
	   $title = htmlspecialchars($title);
	   $urltitle = urlencode($title);
		
	   $timestamp = $rev['timestamp'];
	    $date = date('M d, Y H:i:s', strtotime( $timestamp ) );
	   
	   $list = '(<a href="//'.$wiki.'/w/index.php?title='.$urltitle.'&amp;diff=prev&amp;oldid='.urlencode($rev['revid']).'" title="'.$title.'">diff</a>) ';
	
	   $list .= '(<a href="//'.$wiki.'/w/index.php?title='.$urltitle.'&amp;action=history" title="'.$title.'">hist</a>) . . ';
	   
	   if( isset( $rev['minor'] ) ) {
	      $list .= '<span class="minor">m</span>  ';
	   }
	   
	   $list .= '<a href="//'.$wiki.'/wiki/'.$urltitle.'" title="'.$title.'">'.$title.'</a>‎; ';
	   
	   $list .= $date . ' . . ';
	   
	   $list .= '<a href="//'.$wiki.'/wiki/User:'.$rev['user'].'" title="User:'.$rev['user'].'">'.$rev['user'].'</a> ';
	   
	   $list .= '(<a href="//'.$wiki.'/wiki/User_talk:'.$rev['user'].'" title="User talk:'.$rev['user'].'">talk</a>) ';
	   
	   if( isset( $rev['comment'] ) ) $list .= '('.$rev['comment'].')';
	   
	   $list .= "<hr />\n</li>\n";
	   
	   return $list;
	}
}
