<?php
$TYPO3_CONF_VARS[TYPO3_MODE]["XCLASS"]["tslib/class.tslib_fe.php"] = t3lib_extMgm::extPath($_EXTKEY)."class.ux_tslib_fe.php";
$TYPO3_CONF_VARS[TYPO3_MODE]["XCLASS"]["tslib/class.tslib_menu.php"] = t3lib_extMgm::extPath($_EXTKEY)."class.ux_tslib_menu.php";
$TYPO3_CONF_VARS[TYPO3_MODE]["XCLASS"]["t3lib/class.t3lib_tstemplate.php"] = t3lib_extMgm::extPath($_EXTKEY)."class.ux_t3lib_tstemplate.php";
$TYPO3_CONF_VARS[TYPO3_MODE]["XCLASS"]["t3lib/class.t3lib_page.php"] = t3lib_extMgm::extPath($_EXTKEY)."class.ux_t3lib_page.php";
$TYPO3_CONF_VARS["FE"]["speakingURIs"]["enable"] = '1';
$TYPO3_CONF_VARS["FE"]["speakingURIs"]["baseURI"] = '/';
$TYPO3_CONF_VARS["FE"]["speakingURIs"]["langMapString"] = 'de:0|en:1';
$TYPO3_CONF_VARS["FE"]["speakingURIs"]["langIdentMethod"] = 'none';
$TYPO3_CONF_VARS["FE"]["speakingURIs"]["allwaysAnalyseDomains"] = '0'; //reserved - not yet implemented
?>
