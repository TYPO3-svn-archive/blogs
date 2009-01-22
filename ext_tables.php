<?php
if (!defined ('TYPO3_MODE')) {
	die ('Access denied.');
}

t3lib_extMgm::addStaticFile($_EXTKEY,'static/blogs/', 'Blogs');
t3lib_extMgm::allowTableOnStandardPages('tx_blogs_categories');

$TCA['tx_blogs_categories'] = array (
	'ctrl' => array (
		'title'     => 'LLL:EXT:blogs/locallang_db.xml:tx_blogs_categories',		
		'label'     => 'title',	
		'tstamp'    => 'tstamp',
		'crdate'    => 'crdate',
		'cruser_id' => 'cruser_id',
		'default_sortby' => 'ORDER BY title',	
		'delete' => 'deleted',	
		'enablecolumns' => array (		
			'disabled' => 'hidden',
		),
		'dynamicConfigFile' => t3lib_extMgm::extPath($_EXTKEY).'tca.php',
		'iconfile'          => t3lib_extMgm::extRelPath($_EXTKEY).'icon_tx_blogs_categories.gif',
	),
);


t3lib_extMgm::allowTableOnStandardPages('tx_blogs_items');

$TCA['tx_blogs_items'] = array (
	'ctrl' => array (
		'title'     => 'LLL:EXT:blogs/locallang_db.xml:tx_blogs_items',		
		'label'     => 'title',	
		'tstamp'    => 'tstamp',
		'crdate'    => 'crdate',
		'cruser_id' => 'cruser_id',
		'default_sortby' => 'ORDER BY crdate DESC',	
		'delete' => 'deleted',	
		'enablecolumns' => array (		
			'disabled' => 'hidden',
		),
		'dynamicConfigFile' => t3lib_extMgm::extPath($_EXTKEY).'tca.php',
		'iconfile'          => t3lib_extMgm::extRelPath($_EXTKEY).'icon_tx_blogs_items.gif',
	),
);


t3lib_extMgm::allowTableOnStandardPages('tx_blogs_comments');

$TCA['tx_blogs_comments'] = array (
	'ctrl' => array (
		'title'     => 'LLL:EXT:blogs/locallang_db.xml:tx_blogs_comments',		
		'label'     => 'bodytext',	
		'tstamp'    => 'tstamp',
		'crdate'    => 'crdate',
		'cruser_id' => 'cruser_id',
		'default_sortby' => 'ORDER BY crdate DESC',	
		'delete' => 'deleted',	
		'enablecolumns' => array (		
			'disabled' => 'hidden',
		),
		'dynamicConfigFile' => t3lib_extMgm::extPath($_EXTKEY).'tca.php',
		'iconfile'          => t3lib_extMgm::extRelPath($_EXTKEY).'icon_tx_blogs_comments.gif',
	),
);


t3lib_div::loadTCA('tt_content');
$TCA['tt_content']['types']['list']['subtypes_excludelist'][$_EXTKEY.'_pi1']='layout,select_key';
// Flexform
$TCA["tt_content"]["types"]["list"]["subtypes_addlist"][$_EXTKEY."_pi1"] = 'pi_flexform';
t3lib_extMgm::addPiFlexFormValue($_EXTKEY.'_pi1', 'FILE:EXT:'.$_EXTKEY.'/flexform_ds_pi1_settings.xml');


t3lib_extMgm::addPlugin(array(
	'LLL:EXT:blogs/locallang_db.xml:tt_content.list_type_pi1',
	$_EXTKEY . '_pi1',
	t3lib_extMgm::extRelPath($_EXTKEY) . 'ext_icon.gif'
),'list_type');
?>