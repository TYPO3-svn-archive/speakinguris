<?php
/***************************************************************
*  Copyright notice
*  
*  (c) 2003 Jan Roehrich (jan@roehrich.info)
*  All rights reserved
*
*  This script is part of the Typo3 project. The Typo3 project is 
*  free software; you can redistribute it and/or modify
*  it under the terms of the GNU General Public License as published by
*  the Free Software Foundation; either version 2 of the License, or
*  (at your option) any later version.
* 
*  The GNU General Public License can be found at
*  http://www.gnu.org/copyleft/gpl.html.
*  A copy is found in the textfile GPL.txt and important notices to the license 
*  from the author is found in LICENSE.txt distributed with these scripts.
*
* 
*  This script is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*
*  This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/

class ux_tslib_fe extends tslib_fe {
	var $speakingEnabled;
	var $baseURL;
	var $langMap = Array();
	var $langMapFlipped = Array();
	var $allwaysAnalyseDomains; //not implemented yet
	
	function ux_tslib_fe($TYPO3_CONF_VARS, $id, $type, $no_cache="", $cHash="", $jumpurl="",$MP="",$RDCT="")	{
		parent::tslib_fe($TYPO3_CONF_VARS, $id, $type, $no_cache, $cHash, $jumpurl, $MP, $RDCT);
		
		//Load configurable values
		$this->speakingEnabled = ($GLOBALS["TYPO3_CONF_VARS"]["FE"]["speakingURIs"]["enable"] == "1");
		$this->baseURL = $GLOBALS["TYPO3_CONF_VARS"]["FE"]["speakingURIs"]["baseURI"];
		$this->langIdentMethod = $GLOBALS["TYPO3_CONF_VARS"]["FE"]["speakingURIs"]["langIdentMethod"];
		if(!strlen($this->langIdentMethod) > 0) $this->langIdentMethod = 'none';		// use 'filesuffix' as default
		$langMapString = $GLOBALS["TYPO3_CONF_VARS"]["FE"]["speakingURIs"]["langMapString"];
		$allwaysAnalyseDomains = ($GLOBALS["TYPO3_CONF_VARS"]["FE"]["speakingURIs"]["allwaysAnalyseDomains"] == "1");
		//Explode the language mapping string
		if(strlen($langMapString) > 3) {
			$langMapTmp = explode('|', $langMapString);
			foreach($langMapTmp as $item) {
				$item_tmp = explode(':', $item);
				$this->langMap[$item_tmp[0]] = $item_tmp[1];
			}
		} 
		$this->langMapFlipped = array_flip($this->langMap);
		
		
	}
	

     /**
	 * Build a speaking URL, like Products/Product%201/Features/
	 * Note the URL is not prepended with a slash, this is done by config.absRefPrefix
	 * There is also a function $this->sys_page->getPathFromRootline, but that one can only be used for a visual
	 * indication of the path in the backend, not for a real url.
	 * Note also that the for-loop starts with 1 so the first page is stripped off. This is (in most cases) the
	 * root of the website (which is 'handled' domainname).
	 */
	function getSpeakingUrlFromRootline($rl) {
		$paths = array();
	  	$c = count($rl);
		for ($i = 1; $i < $c; $i++) {
		  	$paths[$i] = rawurlencode($rl[$i]["title"]);
		}
		return implode('/',$paths).(count($paths)?"/":"");
	}
	
	/** 
 	 * make simulation filename
 	 */
	function makeSimulFileName($inTitle,$page,$type,$addParams="",$no_cache="")	{
	  	$path = $this->getSpeakingUrlFromRootline($this->sys_page->getRootline($page));
			
		switch($type) {
		  	case 0:
			  	$filename = "index"; 
			  	break;
		  	case 1:
			  	$filename = "page"; 
			  	break;
		  	case 2:
			  	$filename = "menu"; 
			  	break;
		  	case 3:
			  	$filename = "top"; 
			  	break;
			default:
			  	$filename = "index";
			  	break;
		}
		
		$lang = $this->langMapFlipped[$this->config['config']['sys_language_uid']];
		if($this->langIdentMethod == 'pathprefix') {
			return $lang . '/' . $path . $filename;
		} else {
			return $path.$filename;
		}
	}
		
	function checkAlternativeIdMethods()	{
		$requestHost = $GLOBALS["HTTP_SERVER_VARS"]["HTTP_HOST"];
		$requestURI = $GLOBALS["HTTP_SERVER_VARS"]["REQUEST_URI"];
		$useDefault = ereg("index\.php\?id=.*", $requestURI);
		
		//look if speaking URLs are enabled	
		if (!$useDefault && $this->speakingEnabled) { 

			// speaking URLs are enabled
			// Get the URL and strip leading and trailing slashes off
			$url = trim($requestURI, "/");


			//$url = trim($GLOBALS["HTTP_SERVER_VARS"]["SCRIPT_NAME"],'/');
			//Cut leading part of URI (until $baseURL)
			$url = substr($url, strlen($this->baseURL)-1);
			//These are the initial settings
		  	$lang_code = 0;
		  	$path = array();
			$filename = "";
			// Split the URL in a path and filename
			if (strlen($url)!=0) {
			  	// Split the url into pieces
			  	if(strpos($url, '/')) {
			  		$path = explode("/", $url);
			  	} else {
			  		$path = Array(0 => $url);
			  	}

					
				// Url-decode every piece again
				foreach ($path as $key => $elem) { 
					$path[$key] = rawurldecode($elem);
				}
					
				$filename = $path[count($path)-1]; 
				// Assume the last part is a filename
				if (ereg('.*\.html.*', $filename)) { 
					//remove last part of path array because it is a filename an has nothing to do with the document location
					array_pop($path);
				}				  	
				switch($this->langIdentMethod) {
					case 'filesuffix':
						//filesuffix is used (index.html.xx) where 'xx' is the language code defined in ISO 639
			  			if(ereg('.*\.html\...', $filename)) {
			  				//Filename has language code attached -> get it!
			  				$lang = ereg_replace('.*\.html\.(..)', '\\1', $filename);
			  				$filename = ereg_replace('(.*)\...', '\\1', $filename);
			  				$langCode = $this->langMap[$lang];
			  			}
	  				  	$GLOBALS["HTTP_GET_VARS"]['L']= $langCode;			  	
				  		break;
				  	case 'pathprefix':
				  		//pathprefix is used: /xx/path/index.html where 'xx' is the language code defined in ISO 639
				  		//assume the first element in $path is this prefix
				  		$prefix = $path[0];
				  		//check if $prefix is in $langMapString
				  		$langCodeTmp = $this->langMap[$prefix];
				  		//check if it is realy this language code (back annotation)
				  		if($this->langMapFlipped[$langCodeTmp] == $prefix) {				  			
				  			// the disired language is available
				  			// remove first element from $path and set $langCode
				  			$langCode = $langCodeTmp;
				  			$path = array_reverse($path);
				  			array_pop($path);
				  			$path = array_reverse($path);
				  		}
	  				  	$GLOBALS["HTTP_GET_VARS"]['L']= $langCode;			  	
				  		break;				  						  			
				}
			}

			$this->id = $this->determineIDFromPath($path, $requestHost);
			$this->type = $this->determineTypeFromFile($filename);
			
		} else {
			//speaking URLS are disabled -> use standard functionality
			$this->id = t3lib_div::GPvar("id");
		}
	}
		
		function determineTypeFromFile($filename) {
			switch($filename) {
				case 'page.html':
				  	return 1;
				case 'menu.html':
				  	return 2;
				case 'top.html':
				  	return 3;
				case 'index.php':
				case 'index.htm':
				case 'index.html':
				case 'default.html':
				case '':
				default:
				  	return 0;
			}
		}

		/**
		 * Traverses the $elements array from 0 to the last element and matches each entry with the title of pages. 
		 * If the given path is not found, -1 is returned.
		 * Else the page id of the page belonging to the path is supplied.
		 */
		function determineIDFromPath($elements, $requestHost)  {


			// first of all determine if there is only one root-page
			$query = "SELECT uid, pid, doktype, mount_pid FROM pages WHERE pid = 0 AND deleted = 0 AND hidden = 0";
			$ref = mysql(TYPO3_db, $query);
			$num = mysql_num_rows($ref);
			if($num == 1) {
				// There is only one root-page
				$result = mysql_fetch_array($ref);
			} else if($num == 0) {
				// No root Page -> give error 404
				return -1;
			} else {
				// More than one root-pages ->analyse the "Domain" entries
				$query = "SELECT uid, pid, domainName FROM sys_domain WHERE domainName = '" . $requestHost . "' AND hidden = 0";
				$domain_ref = mysql(TYPO3_db, $query);
				$domain_num = mysql_num_rows($domain_ref);
				if($domain_num == 1) {
					// one domain entry -> get correct parent page
					$domain_result = mysql_fetch_array($domain_ref);
					$query = "SELECT uid, pid, doktype, mount_pid FROM pages WHERE uid = " . $domain_result["pid"] . " AND deleted = 0 AND hidden = 0";
					$ref = mysql(TYPO3_db, $query);
					echo mysql_error();
					$num = mysql_num_rows($ref);
					$result = mysql_fetch_array($ref);
				} else {
					// none or more than one domain entry -> error
					return -1;
				}
			}

		  	if (count($elements)==0) return $result["uid"]; // if no path specified load default page for this domain

			$query = "SELECT uid, pid, title, doktype, mount_pid FROM pages WHERE title = '" . $elements[0] . "' AND pid = " . $result["uid"] . "  AND deleted = 0 AND hidden = 0";
			

			$ref = mysql(TYPO3_db, $query);
			echo mysql_error();
			$num = mysql_num_rows($ref);
			if($num == 0) {
				//A page with the given title is not availiable in this subtree -> give error 404
				return -1;
			}
			$result = mysql_fetch_array($ref);
		  	
		  	for($i = 1; $i < count($elements); $i++) {		  		
			  	$query = "SELECT uid, pid, title, doktype, mount_pid FROM pages WHERE title = '" . $elements[$i] . "' AND pid = " . $result["uid"] . " AND deleted = 0 AND hidden = 0";
			  	$ref = mysql(TYPO3_db, $query);
			  	$num = mysql_num_rows($ref);
			  	if($num == 0) {
			  		//A page with the given title is not availiable in this subtree -> give error 404
			  		return -1;
			  	}
			  	$result = mysql_fetch_array($ref);
			  	
			  	if($result["doktype"] == 7) {
			  		//it is a page mount
			  		$query = "SELECT uid, pid, title, doktype, mount_pid FROM pages WHERE uid = " . $result["mount_pid"];
  				  	$ref = mysql(TYPO3_db, $query);
				  	$result = mysql_fetch_array($ref);
			  	}
		  	}
		  	return $result["uid"];  	
		}
	}
?>
