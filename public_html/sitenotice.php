<?php

class siteNotice {
	private $status_content;
	
	function __construct() {
		$this->status_content = "Deleted edits are now available.";//file_get_contents('/var/www/sitenotice');
		//$this->status_content = "Foobar";
	}
	
	public function checkSiteNoticeHtml() {
		if (!empty($this->status_content)) {
			    return htmlentities($this->status_content);
		}
		return null;
	}
	
	public function checkSiteNoticeRaw() {
		if (!empty($this->status_content)) {
			    return $this->status_content;
		}
		return null;
	}
}

?>
