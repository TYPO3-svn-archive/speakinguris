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

class ux_tslib_menu extends tslib_menu {
}

class ux_tslib_gmenu extends tslib_gmenu {
	var $speakingURLsEnabled;								//true if SpeakingURIs are enabled in this website 
	var $baseURL;													//the leading URL-Part in front of the dynamic content (must be same as config.absRefPref)
	var $langIdentMethod;										// method of language encoding in the URLs
	var $langMap = Array();									// A map containing ISO 639 codes with references to Typo3 language numbers 
	var $langMapFlipped = Array();							// Same Array, but flipped
	
	/**
	 * Same as tslib_gmenu::start() it also loads the configuration values of the SpeakingURIs feature.
	 */
	function start($tmpl,$sys_page,$id,$conf,$menuNumber)	{
		parent::start($tmpl, $sys_page, $id, $conf, $menuNumber);
		$this->speakingURLsEnabled = ($GLOBALS["TYPO3_CONF_VARS"]["FE"]["speakingURLs"]["enable"] == "1");
		$this->baseURL = $GLOBALS["TYPO3_CONF_VARS"]["FE"]["speakingURLs"]["baseURL"];
		$this->langIdentMethod = $GLOBALS["TYPO3_CONF_VARS"]["FE"]["speakingURLs"]["langIdentMethod"];
		if(!strlen($this->langIdentMethod) > 0) $this->langIdentMethod = 'none';		// use 'filesuffix' as default
		$langMapString = $GLOBALS["TYPO3_CONF_VARS"]["FE"]["speakingURLs"]["langMapString"];
		$this->langMap = Array();
		
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
	 * This function is called before a link in tslib_menu::makeMenu is created.
	 * It extrancs the HREF string and replaces the ISO 639 language code by the value given in special.value.
	 * If special.text is given, the link will not be named as title, but as special.text.
	 */
	function extProc_beforeLinking($key)	{
		if(($this->conf["special"] == "lang") && $this->speakingURLsEnabled && ($GLOBALS["TSFE"]->config["config"]["simulateStaticDocuments"] == "1")) {
			$href = $this->I["linkHREF"]["HREF"];
			$host = ereg_replace("(.*://[^/]*)/.*", "\\1", $href);
			$base = $this->baseURL;
			$dyn = ereg_replace(".*://.*" . $this->baseURL . "(.*)", "\\1", $href);
			
			if($this->langIdentMethod == "pathprefix") {		
				// Pathprefix is configured -> change first 2 characters of "$dyn"
				$replaceLangCode = $this->langMapFlipped[$this->conf["special."]["value"]];
				$actualLangCode = substr($dyn, 0, 2);
				if($this->langMap[$actualLangCode] == $GLOBALS["TSFE"]->config["config"]["sys_language_uid"]) {
					//if first 2 chars of $dyn are the language code -> replace it
					$page = substr($dyn, 2);	
				} else {
					//if not -> default language without language code
					$page = $dyn;
				}
				$this->I["linkHREF"]["HREF"] =   $host . $base . $replaceLangCode . $page;
			} else if($this->langIdentMethod == "filesuffix") {
				// not implemented yet
			}
		} else {
			//no speaking URLs -> just append "L=x" in the URL
			//not implemented yet
		}
	}

	function makeMenu()	{
		if ($this->id)	{
			$temp = array();
			$altSortFieldValue = trim($this->mconf["alternativeSortingField"]);
			$altSortField = $altSortFieldValue ? $altSortFieldValue : "sorting";
			if ($this->menuNumber==1 && $this->conf["special"])	{
				$value = $this->conf["special."]["value"];

				switch($this->conf["special"])	{
					case "userdefined":
						$temp = $this->includeMakeMenu($this->conf["special."],$altSortField);
					break;
					case "userfunction":
						$temp = $this->parent_cObj->callUserFunction(
							$this->conf["special."]["userFunc"], 
							array_merge($this->conf["special."],array("_altSortField"=>$altSortField)),		// Candidate for t3lib_div::array_merge() if integer-keys will some day make trouble...
							""
						);
						if (!is_array($temp))	$temp=array();
					break;
					case "directory":
						if ($value=="") {
							$value=$GLOBALS["TSFE"]->page["uid"];
						}
						$items=t3lib_div::intExplode(",",$value);
						reset($items);
						while(list(,$id)=each($items))	{
							$idPage = $GLOBALS["TSFE"]->sys_page->getRawRecord("pages",$id);
							if (is_array($idPage) && $GLOBALS["TYPO3_CONF_VARS"]["FE"]["enable_mount_pids"] && $idPage["mount_pid"]>0)	{
								$MP=$idPage["mount_pid"]."-".$idPage["uid"];
								$id=$idPage["mount_pid"];
							} else $MP=0;
							
							$query = $GLOBALS["TSFE"]->cObj->getQuery("pages",Array("pidInList"=>$id,"orderBy"=>$altSortField));
							$res = mysql(TYPO3_db, $query);
							while ($row = mysql_fetch_assoc($res))	{
								$temp[$row["uid"]]=$GLOBALS["TSFE"]->sys_page->getPageOverlay($row);
								$temp[$row["uid"]]["_MP_PARAM"]=$MP;
							}
						}
					break;
					case "lang":
						$value=$GLOBALS["TSFE"]->page["uid"];
						$loadDB = t3lib_div::makeInstance("FE_loadDBGroup");
						$loadDB->start($value, "pages");
						$loadDB->additionalWhere["pages"]=tslib_cObj::enableFields("pages");
						$loadDB->getFromDB();
	
						reset($loadDB->itemArray);
						$data = $loadDB->results;
	
						if(list(,$val)=each($loadDB->itemArray))	{
							$row = $data[$val["table"]][$val["id"]];
							if ($row)	{
								$page = $GLOBALS["TSFE"]->sys_page->getPageOverlay($row);
								if($this->conf["special."]["text"]) {
									$page["title"] = $this->conf["special."]["text"];
								}
								$temp[] = $page;
							}
						}
					break;
					case "list":
						if ($value=="") {
							$value=$this->id;
						}
						$loadDB = t3lib_div::makeInstance("FE_loadDBGroup");
						$loadDB->start($value, "pages");
						$loadDB->additionalWhere["pages"]=tslib_cObj::enableFields("pages");
						$loadDB->getFromDB();
	
						reset($loadDB->itemArray);
						$data = $loadDB->results;
	
						while(list(,$val)=each($loadDB->itemArray))	{
							$row = $data[$val["table"]][$val["id"]];
							if ($row)	{
								$temp[]=$GLOBALS["TSFE"]->sys_page->getPageOverlay($row);
							}
						}
					break;
					case "updated":
						if ($value=="") {
							$value=$GLOBALS["TSFE"]->page[uid];
						}
						$items=t3lib_div::intExplode(",",$value);
						if (t3lib_div::testInt($this->conf["special."]["depth"]))	{
							$depth = t3lib_div::intInRange($this->conf["special."]["depth"],1,20);		// Tree depth
						} else {
							$depth=20;
						}				
						$limit = t3lib_div::intInRange($this->conf["special."]["limit"],0,100);	// max number of items
						$maxAge = intval(tslib_cObj::calc($this->conf["special."]["maxAge"]));
						if (!$limit)	$limit=10;
						$mode = $this->conf["special."]["mode"];	// *"auto", "manual", "tstamp"
							// Get id's
						$id_list_arr = Array();
						reset($items);
						while(list(,$id)=each($items))	{
							$bA = t3lib_div::intInRange($this->conf["special."]["beginAtLevel"],0,100);
							$id_list_arr[]=tslib_cObj::getTreeList($id,$depth-1+$bA,$bA-1).($bA?0:$id);
						}
						$id_list = implode($id_list_arr, ",");
							// Get sortField (mode)
						switch($mode)	{
							case "starttime":
								$sortField = "starttime";
							break;						
							case "lastUpdated":
							case "manual":
								$sortField = "lastUpdated";
							break;
							case "tstamp":
								$sortField = "tstamp";
							break;
							case "crdate":
								$sortField = "crdate";
							break;						
							default:
								$sortField = "SYS_LASTCHANGED";
							break;
						}
							// Get 
						$extraWhere = " AND pages.doktype NOT IN (5,6)";
	
						if ($this->conf["special."]["excludeNoSearchPages"]) {
							$extraWhere.= " AND pages.no_search=0";
						}
						if ($maxAge>0)	{
							$extraWhere.=" AND ".$sortField.">".($GLOBALS["SIM_EXEC_TIME"]-$maxAge);
						}
	
						$query = $GLOBALS["TSFE"]->cObj->getQuery("pages",Array("pidInList"=>"0", "uidInList"=>$id_list, "where"=>$sortField.">=0".$extraWhere, "orderBy"=>($altSortFieldValue ? $altSortFieldValue : $sortField." desc"),"max"=>$limit));
						$res = mysql(TYPO3_db, $query);
						while ($row = mysql_fetch_assoc($res))	{
							$temp[$row["uid"]]=$GLOBALS["TSFE"]->sys_page->getPageOverlay($row);
						}
					break;
					case "keywords":
						list($value)=t3lib_div::intExplode(",",$value);
						if (!$value) {
							$value=$GLOBALS["TSFE"]->page["uid"];
						}
						if ($this->conf["special."]["setKeywords"] || $this->conf["special."]["setKeywords."]) {
							$kw = $this->parent_cObj->stdWrap($this->conf["special."]["setKeywords"], $this->conf["special."]["setKeywords."]);
	 					} else {
		 					$value_rec=$this->sys_page->getPage($value);	// The page record of the "value".

							$kfieldSrc = $this->conf["special."]["keywordsField."]["sourceField"] ? $this->conf["special."]["keywordsField."]["sourceField"] : "keywords";
							$kw = trim(tslib_cObj::keywords($value_rec[$kfieldSrc]));		// keywords.
	 					}

						$mode = $this->conf["special."]["mode"];	// *"auto", "manual", "tstamp"
						switch($mode)	{
							case "starttime":
								$sortField = "starttime";
							break;						
							case "lastUpdated":
							case "manual":
								$sortField = "lastUpdated";
							break;
							case "tstamp":
								$sortField = "tstamp";
							break;
							case "crdate":
								$sortField = "crdate";
							break;						
							default:
								$sortField = "SYS_LASTCHANGED";
							break;
						}

							// depth, limit, extra where
						if (t3lib_div::testInt($this->conf["special."]["depth"]))	{
							$depth = t3lib_div::intInRange($this->conf["special."]["depth"],0,20);		// Tree depth
						} else {
							$depth=20;
						}				
						$limit = t3lib_div::intInRange($this->conf["special."]["limit"],0,100);	// max number of items
						$extraWhere = " AND pages.uid!=".$value." AND pages.doktype NOT IN (5,6)";
						if ($this->conf["special."]["excludeNoSearchPages"]) {
							$extraWhere.= " AND pages.no_search=0";
						}
							// start point
						$eLevel = tslib_cObj::getKey (intval($this->conf["special."]["entryLevel"]),$this->tmpl->rootLine);
						$startUid = intval($this->tmpl->rootLine[$eLevel][uid]);

							// which field is for keywords
						$kfield = "keywords";
						if ( $this->conf["special."]["keywordsField"] ) {
							list($kfield) = explode(" ",trim ($this->conf["special."]["keywordsField"]));
						}	
					
							// If there are keywords and the startuid is present.
	//					debug($kw);
						if ($kw && $startUid)	{
							$bA = t3lib_div::intInRange($this->conf["special."]["beginAtLevel"],0,100);
							$id_list=tslib_cObj::getTreeList($startUid,$depth-1+$bA,$bA-1).($bA?0:$startUid);

							$kwArr = explode(",",$kw);
							reset($kwArr);
							while(list(,$word)=each($kwArr))	{
								$word = trim($word);
								if ($word)	{
									$keyWordsWhereArr[]=$kfield." LIKE '%".addslashes($word)."%'";
								}
							}
							$query = $GLOBALS["TSFE"]->cObj->getQuery("pages",Array("pidInList"=>"0", "uidInList"=>$id_list, "where"=>"(".implode($keyWordsWhereArr," OR ").")".$extraWhere, "orderBy"=>($altSortFieldValue ? $altSortFieldValue : $sortField." desc"),"max"=>$limit));
							$res = mysql(TYPO3_db, $query);
							while ($row = mysql_fetch_assoc($res))	{
								$temp[$row["uid"]]=$GLOBALS["TSFE"]->sys_page->getPageOverlay($row);
							}
						}
					break;
					case "rootline":
						$begin_end = explode("|",$this->conf["special."]["range"]);
						if (!t3lib_div::testInt($begin_end[0]))	{intval($begin_end[0]);}
						if (!t3lib_div::testInt($begin_end[1]))	{$begin_end[1]=-1;}
	
						$beginKey = tslib_cObj::getKey ($begin_end[0],$this->tmpl->rootLine);
						$endKey = tslib_cObj::getKey ($begin_end[1],$this->tmpl->rootLine);
						if ($endKey<$beginKey)	{$endKey=$beginKey;}
						
						reset($this->tmpl->rootLine);
						while(list($k_rl,$v_rl)=each($this->tmpl->rootLine))	{
							if ($k_rl>=$beginKey && $k_rl<=$endKey)	{
								$temp_key=$k_rl;
								$temp[$temp_key]=$this->sys_page->getPage($v_rl["uid"]);
								if (count($temp[$temp_key]))	{
									if (!$temp[$temp_key]["target"])	{	// If there are no specific target for the page, put the level specific target on.
										$temp[$temp_key]["target"] = $this->conf["special."]["targets."][$k_rl];
									}
								} else unset($temp[$temp_key]);
							}
						}
					break;
					case "browse":
						list($value)=t3lib_div::intExplode(",",$value);
						if (!$value) {
							$value=$GLOBALS["TSFE"]->page[uid];
						}
						if ($value!=$this->tmpl->rootLine[0][uid])	{	// Will not work out of rootline
		 					$recArr=array();
		 					$value_rec=$this->sys_page->getPage($value);	// The page record of the "value".
		 					if ($value_rec["pid"])	{	// "up" page cannot be outside rootline
		 						$recArr["up"]=$this->sys_page->getPage($value_rec["pid"]);	// The page record of "up".
		 					}
		 					if ($recArr["up"][pid] && $value_rec["pid"]!=$this->tmpl->rootLine[0][uid])	{	// If the "up" item was NOT level 0 in rootline...
		 						$recArr["index"]=$this->sys_page->getPage($recArr["up"][pid]);	// The page record of "index".
		 					}
		 				
		 						// prev / next is found
		 					$prevnext_menu = $this->sys_page->getMenu($value_rec["pid"],"*",$altSortField);
		 					$lastKey=0;
		 					$nextActive=0;
		 					reset($prevnext_menu);
		 					while(list($k_b,$v_b)=each($prevnext_menu))	{
		 						if ($nextActive)	{
		 							$recArr["next"]=$v_b;
		 							$nextActive=0;
								}
		 						if ($v_b["uid"]==$value)	{
		 							if ($lastKey)	{
		 								$recArr["prev"]=$prevnext_menu[$lastKey];
		 							}
		 							$nextActive=1;
								}
		 						$lastKey=$k_b;
		 					}
		 					reset($prevnext_menu);
							$recArr["first"]=pos($prevnext_menu);
							end($prevnext_menu);
							$recArr["last"]=pos($prevnext_menu);
	
		 						// prevsection / nextsection is found
							if (is_array($recArr["index"]))	{	// You can only do this, if there is a valid page two levels up!
			 					$prevnextsection_menu = $this->sys_page->getMenu($recArr["index"]["uid"],"*",$altSortField);
			 					$lastKey=0;
			 					$nextActive=0;
			 					reset($prevnextsection_menu);
			 					while(list($k_b,$v_b)=each($prevnextsection_menu))	{
			 						if ($nextActive)	{
										$sectionRec_temp = $this->sys_page->getMenu($v_b["uid"],"*",$altSortField);
										if (count($sectionRec_temp))	{
											reset($sectionRec_temp);
				 							$recArr["nextsection"]=pos($sectionRec_temp);
											end ($sectionRec_temp);
				 							$recArr["nextsection_last"]=pos($sectionRec_temp);
				 							$nextActive=0;
										}
									}
			 						if ($v_b["uid"]==$value_rec["pid"])	{
			 							if ($lastKey)	{
											$sectionRec_temp = $this->sys_page->getMenu($prevnextsection_menu[$lastKey][uid],"*",$altSortField);
											if (count($sectionRec_temp))	{
												reset($sectionRec_temp);
					 							$recArr["prevsection"]=pos($sectionRec_temp);
												end ($sectionRec_temp);
					 							$recArr["prevsection_last"]=pos($sectionRec_temp);
											}
			 							}
			 							$nextActive=1;
									}
			 						$lastKey=$k_b;
			 					}
							}
							if ($this->conf["special."]["items."]["prevnextToSection"])	{
								if (!is_array($recArr["prev"]) && is_array($recArr["prevsection_last"]))	{
									$recArr["prev"]=$recArr["prevsection_last"];
								}
								if (!is_array($recArr["next"]) && is_array($recArr["nextsection"]))	{
									$recArr["next"]=$recArr["nextsection"];
								}
							}
							
		 					$items = explode("|",$this->conf["special."]["items"]);
							$c=0;
		 					while(list($k_b,$v_b)=each($items))	{
		 						$v_b=strtolower(trim($v_b));
								if (intval($this->conf["special."][$v_b."."]["uid"]))	{
									$recArr[$v_b] = $this->sys_page->getPage(intval($this->conf["special."][$v_b."."]["uid"]));	// fetches the page in case of a hardcoded pid in template
								}
		 						if (is_array($recArr[$v_b]))	{
		 							$temp[$c]=$recArr[$v_b];
									if ($this->conf["special."][$v_b."."]["target"])	{
										$temp[$c]["target"]=$this->conf["special."][$v_b."."]["target"];
									}
									if (is_array($this->conf["special."][$v_b."."]["fields."]))	{
										reset($this->conf["special."][$v_b."."]["fields."]);
										while(list($fk,$val)=each($this->conf["special."][$v_b."."]["fields."]))	{
											$temp[$c][$fk]=$val;
										}
									}
									$c++;
								}
		 					}
						}
					break;
				}
			} elseif ($this->mconf["sectionIndex"]) {
				if ($GLOBALS["TSFE"]->sys_language_uid && count($GLOBALS["TSFE"]->sys_page->getPageOverlay($this->id)))	{
					$sys_language_uid = intval($GLOBALS["TSFE"]->sys_language_uid);
				} else $sys_language_uid=0;
				
				$selectSetup = Array(
					"pidInList"=>$this->id,
					"orderBy"=>$altSortField,
					"where" => "colPos=0 AND sys_language_uid=".$sys_language_uid,
					"andWhere" => "sectionIndex!=0"
					);
				switch($this->mconf["sectionIndex."]["type"])	{
					case "all":
						unset($selectSetup["andWhere"]);
					break;				
					case "header":
						$selectSetup["andWhere"]="header_layout!=100 AND header!=''";
					break;
				}
				$basePageRow=$this->sys_page->getPage($this->id);
				if (is_array($basePageRow))	{
					$query = $GLOBALS["TSFE"]->cObj->getQuery("tt_content",	$selectSetup);
					$res = mysql(TYPO3_db, $query);
					while ($row = mysql_fetch_assoc($res))	{
						$temp[$row["uid"]]=$basePageRow;
						$temp[$row["uid"]]["title"]=$row["header"];
						$temp[$row["uid"]]["subtitle"]=$row["subheader"];
						$temp[$row["uid"]]["starttime"]=$row["starttime"];
						$temp[$row["uid"]]["endtime"]=$row["endtime"];
						$temp[$row["uid"]]["fe_group"]=$row["fe_group"];
						$temp[$row["uid"]]["media"]=$row["media"];

						$temp[$row["uid"]]["header_layout"]=$row["header_layout"];
						$temp[$row["uid"]]["bodytext"]=$row["bodytext"];
						$temp[$row["uid"]]["image"]=$row["image"];

						$temp[$row["uid"]]["sectionIndex_uid"]=$row["uid"];
					}
				}
			} else {
				$temp = $this->sys_page->getMenu($this->id,"*",$altSortField);		// gets the menu
			}
			
			$this->menuArr = Array();
			reset($temp);
			$c=0;
			$c_b=0;

			$minItems=intval($this->conf["minItems"]);
			$maxItems=intval($this->conf["maxItems"]);
			$begin= tslib_cObj::calc($this->conf["begin"]);

			$banUidArray=array();
			if (trim($this->conf["excludeUidList"]))	{
				$banUidArray = t3lib_div::intExplode(",", $this->conf["excludeUidList"]);
			}
			
			while(list(,$data)=each($temp))	{
				$uid=$data["uid"];
				$spacer = (t3lib_div::inList($this->spacerIDList,$data["doktype"])?1:0);		// if item is a spacer, $spacer is set
				if ($this->mconf["SPC"] || !$spacer)	{	// If the spacer-function is not enabled, spacers will not enter the $menuArr
					if (!t3lib_div::inList("5,6",$data["doktype"]) && !t3lib_div::inArray($banUidArray,$uid))	{		// Page may not be "not_in_menu" or "Backend User Section" + not in banned uid's
						$c_b++;
						if ($begin<=$c_b)	{		// If the beginning item has been reached.
							$this->menuArr[$c]=$data;
							$this->menuArr[$c]["isSpacer"]=$spacer;
							$c++;
							if ($maxItems && $c>=$maxItems)	{
								break;
							}
						}
					}
				}
			}
			if ($minItems)	{
				while($c<$minItems)	{
					$this->menuArr[$c] = Array(
						"title" => "...",
						"uid" => $GLOBALS["TSFE"]->id
					);
					$c++;
				}
			}
				// Setting number of menu items
			$GLOBALS["TSFE"]->register["count_menuItems"] = count($this->menuArr);
				//	Passing the menuArr through a user defined function:
			if ($this->mconf["itemArrayProcFunc"])	{
				if (!is_array($this->parentMenuArr)) {$this->parentMenuArr=array();}
				$this->menuArr = $this->userProcess("itemArrayProcFunc",$this->menuArr);
			}
			$this->hash = md5(serialize($this->menuArr).serialize($this->mconf).serialize($this->tmpl->rootLine));

			$serData = $this->sys_page->getHash($this->hash, 60*60*24);
			if (!$serData)	{
				$this->generate();
				$this->sys_page->storeHash($this->hash, serialize($this->result),"MENUDATA");
			} else {
				$this->result=unserialize($serData);
			}
		}
	}
}
?>
