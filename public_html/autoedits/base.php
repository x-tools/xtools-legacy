<?php

class AutoEditsBase {


   public static $AEBTypes = array(
      'Twinkle' => array( 'type' => 'LIKE', 'query' => '%WP:TW%', 'shortcut' => 'WP:TW' ),
      'AutoWikiBrowser' => array( 'type' => 'RLIKE', 'query' => '.*(AutoWikiBrowser|AWB).*', 'shortcut' => 'WP:AWB' ),
      'Friendly' => array( 'type' => 'LIKE', 'query' => '%WP:FRIENDLY%', 'shortcut' => 'WP:FRIENDLY' ),
      'FurMe' => array( 'type' => 'RLIKE', 'query' => '.*(User:AWeenieMan/furme|FurMe).*', 'shortcut' => 'WP:FURME' ),
      'Popups' => array( 'type' => 'LIKE', 'query' => '%Wikipedia:Tools/Navigation_popups%', 'shortcut' => 'Wikipedia:Tools/Navigation_popups' ),
      'MWT' => array( 'type' => 'LIKE', 'query' => '%User:MichaelBillington/MWT%', 'shortcut' => 'User:MichaelBillington/MWT' ),
      'Huggle' => array( 'type' => 'LIKE', 'query' => '%[[WP:HG|HG]]%', 'shortcut' => 'WP:HG' ),
      'NPWatcher' => array( 'type' => 'LIKE', 'query' => '%WP:NPW%', 'shortcut' => 'WP:NPW' ),
      'Amelvand' => array( 'type' => 'LIKE', 'query' => 'Reverted % edit% by % (%) to last revision by %', 'shortcut' => 'User:Gracenotes/amelvand.js' ),
      'Igloo' => array( 'type' => 'RLIKE', 'query' => '.*(User:Ale_jrb/Scripts/igloo|GLOO).*', 'shortcut' => 'WP:IGL' ),
      'HotCat' => array( 'type' => 'LIKE', 'query' => '%(using [[WP:HOTCAT|HotCat]])%', 'shortcut' => 'WP:HOTCAT' ),
      'STiki' => array( 'type' => 'LIKE', 'query' => '%STiki%', 'shortcut' => 'WP:STiki' ),
      'Dazzle!' => array( 'type' => 'LIKE', 'query' => '%Dazzle!%', 'shortcut' => 'WP:Dazzle!' ),
      'Articles For Creation tool' => array( 'type' => 'LIKE', 'query' => '%([[WP:AFCH|AFCH]])%', 'shortcut' => 'WP:AFCH' ),
   );

   public static function getMatchingEdits( $username, $begin, $end, $count, $api = false ) {
      global $dbr, $phptemp;

      $timeconds = array( 'rev_user_text' => $username );

      if( $begin ) {
         $timeconds[] = 'UNIX_TIMESTAMP(rev_timestamp) > ' . $dbr->strencode( strtotime( $begin ) );
      }
      if( $end ) {
         $timeconds[] = 'UNIX_TIMESTAMP(rev_timestamp) < ' . $dbr->strencode( strtotime( $end ) );
      }

      $contribs = $urls = array();

      foreach( AutoEditsBase::$AEBTypes as $name => $check ) {
         $conds = $timeconds; $conds[] = 'rev_comment ' . $check['type'] . ' \'' . $check['query'] . '\'';

         try {
            $res = $dbr->select(
               array( 'revision' ),
               array( 'COUNT(*) AS count' ),
               $conds
            );
            $contribs[$name] = $res[0]['count'];
         } catch( Exception $e ) {
            if( $api ) return array( 'error' => 'dberror', 'info' => $e->getMessage() );
            WebTool::toDieMsg( 'mysqlerror', $e->getMessage() );
         }
         $urls[$name] = $check['shortcut'];
      }

      $formattedpct = WebTool::number_format( ( ( $count ? array_sum( $contribs ) / $count : 0 ) *100 ), 2);
      if( $api ) {
         return array( 'counts' => $contribs, 'total' => array_sum( $contribs ), 'editcount' => $count, 'pct' => $formattedpct );
      }
      else {
         return array( 'counts' => $contribs, 'total' => WebTool::number_format( array_sum( $contribs ), 0 ), 'editcount' => WebTool::number_format( $count, 0 ), 'pct' => $formattedpct, 'urls' => $urls );
      }

   }

}  
