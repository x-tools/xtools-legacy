<?php 

class PagesBase{
	
	public static function getUserData( $dbr, $username ){
		$query = "
			SELECT user_name, user_id 
			FROM user 
			WHERE user_name = '$username';
		";
		
		$result = $dbr->query( $query );
		$userdata = $result[0];
		
		return $userdata;
	}
	

	public static function getCreatedPages( $dbr, $user_id, $lang, $wiki, $namespace, $redirects ){
		
		$namespaceCondition = ($namespace == "all") ? "" : " and page_namespace = '".intval($namespace)."' ";
		$redirectCondition = "";
		if ( $redirects == "onlyredirects" ){ $redirectCondition = " and page_is_redirect = '1' "; }
		if ( $redirects == "noredirects" ){ $redirectCondition = " and page_is_redirect = '0' "; }
		
		$query = "
			SELECT DISTINCT page_namespace, page_title, page_is_redirect, page_id, UNIX_TIMESTAMP(rev_timestamp) as timestamp
			FROM page
			JOIN revision_userindex on page_id = rev_page
			WHERE rev_user = '$user_id' AND rev_parent_id = '0'  $namespaceCondition  $redirectCondition
			ORDER BY page_namespace ASC, rev_timestamp DESC;
		";
		
		$items = $dbr->query( $query );
		$items = $items->endArray;
		
		$nsnames = self::getNamespaceNames( $lang, $wiki );
		
		$result = new stdClass(
				$filter 	 = null,
				$namespaces  = null,
				$list 		 = null
			);
		$currentNamespace = "";
		$currentNumber = 0;

		foreach ( $items as $i => $item ){
			$pageurl  = urlencode( $item["page_title"] );
			$page 	  = str_replace("_", " ", $item["page_title"]);
			$date 	  = date("Y-m-d", $item["timestamp"]);
			$ns 	  = $item["page_namespace"];
			$prefix   = ( $nsnames[$ns] != "Mainspace" ) ? $nsnames[$ns].":" : ""; 
			$redirect = ( $item["page_is_redirect"] == 1 ) ? "(redirect)" : "";
			

			//create a new header if namespace changes
			if( $ns != $currentNamespace){
				
				$result->list .= "<tr ><td colspan=4 ><h3 id=$ns >".$nsnames[$ns]."</h3></td></tr>";
				$result->namespaces[$ns]["name"] = $nsnames[$ns];

				$currentNamespace = $ns;
				$currentNumber = 0;
			}

			$result->namespaces[$ns]["num"]  += 1;
			if ($redirect != "") { $result->namespaces[$ns]["redir"]  += 1; }
			$currentNumber++;

			$result->list .= "
				<tr>
					<td>$currentNumber.</td>
					<td><a href=\"//$lang.$wiki.org/wiki/$prefix$pageurl?redirect=no\">$page</a> <small> $redirect</small></td>
					<td style='font-size:95%' >$date</td>
				</tr> 
			 ";
		}
	
		$result->filterns = $namespace;
		$result->filterredir = $redirects;
		$result->total = count($items);
		unset($items, $nsnames);

		//make serialized list for graphics
		foreach ( $result->namespaces as $ns ){
			$result->listns .= "|".$ns["name"];
			$result->listnum .= ",".intval((intval($ns["num"])/intval($result->total))*100);
		}
		$result->listns = urlencode( substr($result->listns, 1) );
		$result->listnum = urlencode( substr($result->listnum, 1) );

		return $result;
	}
	
	static function getNamespaceNames( $lang, $wiki ) {
		$http = new HTTP();
		$namespaces = $http->get( "http://$lang.$wiki.org/w/api.php?action=query&meta=siteinfo&siprop=namespaces&format=php" );
		$namespaces = unserialize( $namespaces );
		$namespaces = $namespaces['query']['namespaces'];
		 
		unset( $namespaces[-2] );
		unset( $namespaces[-1] );
	
		$namespaces[0]['*'] = "Mainspace";
		 
		$namespacenames = array();
		foreach ($namespaces as $value => $ns) {
			$namespacenames[$value] = $ns['*'];
		}
#print_r($namespacenames);die;
		return $namespacenames;
	}
}
