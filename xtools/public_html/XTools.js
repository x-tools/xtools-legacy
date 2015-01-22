var xtoolsagent = {
  
  defaultCfg : {
		'status': 'enabled',
    	'mode' : 'verbose',
  	},
  	
  enable : function () {
	  var cfg = xtoolsagent.defaultCfg;
	  cfg.status = 'enabled';
	  cfg.reenable = 1;
	  localStorage.setItem('xtoolscfg', JSON.stringify( cfg) );
	  $('#xtools').html('<span></span>');
	  $( xtoolsagent.execute );
  },
  
  getText : function(){
//	  regex = [
//	        /ist eine .* Band aus Arkansas/,
//	        /Mann/
//	     ];
//	  zz = $('#content').text();
//	  ww = $('#content').html();
//	  
//	  $.each( regex, function( index, value ) {
//		  //alert( index + ": " + value );
//		  match = zz.match(value);
//		  if(match){
//			 $('#content').html( ww.replace(match, '<span style="background-color:yellow" >' + match + '</span>') );
//			  //$('#content').text(zz);
//		  }
//		});
	  
	  
	  return ;
  },

  execute : function () {
  	if (mw.config.get('wgArticleId') === 0) return; // no deleted articles, no special pages
  	if (mw.config.get('wgCurRevisionId') != mw.config.get('wgRevisionId')) return; // only current revision
  	
  	var cfg = {};
  	try{
  	    cfg = JSON.parse( localStorage.getItem('xtoolscfg') );
  	    var test = cfg.mode;
  	} catch (e) {
  	    cfg = xtoolsagent.defaultCfg;
  	}
  	
  	if ( cfg && (cfg.mode == 'silent' || cfg.status == 'disabled') ){
  	    silentpos = ($("#pt-notifications").length === 0) ? "#pt-login" : "#pt-notifications" ;
  	    $('<li id="xtools"></li>').insertAfter( silentpos );
  	    if ( cfg.status == 'disabled' ){
  	    	$('#xtools').html('<span title="Click to enable XAgent" style="cursor:pointer;font-weigth:bold;" onclick="xtoolsagent.enable();" >X∅</span>');
  	    	return;
  	    }
  		
  	} else{
  	    $('<div id="xtools" style="font-size:85%" >&nbsp;</div>').insertBefore("#contentSub");
  	}

  	ff = xtoolsagent.getText();
  	
  	var reenable = cfg.hasOwnProperty('reenable') ? "&reenable=1" : "";
  			
  	importScriptURI("//tools.wmflabs.org/xtools/api.php?pageid=" 
  			+  mw.config.get('wgArticleId') 
  			+ "&db=" + mw.config.get('wgDBname') 
  			+ "&nsid=" + mw.config.get('wgNamespaceNumber') 
  			+ "&pagetitle=" + mw.config.get('wgPageName') 
  			+ "&wditemid=" + mw.config.get('wgWikibaseItemId') 
  			+ "&uselang=" + mw.config.get('wgContentLanguage') 
  			+ "&mode=" + cfg.mode
  			+ reenable
  			+ "&test=test"
  		);

  },
  	resultloaded : function( resp ) {
  		var res = JSON.parse(resp.resp);
	  	$("#xtools").html( res.html );
	  	
	  	if ( res.cfg ) {
		    localStorage.setItem('xtoolscfg', res.cfg);
		}
	  	return;
  	}
 
};
if ( (mw.config.get('wgAction') == "view") ) $( xtoolsagent.execute );
