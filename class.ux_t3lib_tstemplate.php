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

class ux_t3lib_tstemplate extends t3lib_tstemplate {
	function linkData($page,$oTarget,$no_cache,$script,$overrideArray="",$addParams="",$typeOverride="")	{
		$LD = parent::linkData($page, $oTarget, $no_cache, $script, $overrideArray, $addParams, $typeOverride);
		
		//determine which domain is nearest to the page
		$domain = $GLOBALS["TSFE"]->sys_page->getDomainOfPage($page);
		//and add the domain as prefix (config.absRefPrefix mustn't contain the hostname!!!!!!)
		$LD["totalURL"] = "http://" . $domain[0]["domainName"] . $LD["totalURL"];
		return $LD;
	}
}
?>