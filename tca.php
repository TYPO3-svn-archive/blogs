<?php
if (!defined ('TYPO3_MODE')) 	die ('Access denied.');

$TCA['tx_blogs_categories'] = array (
	'ctrl' => $TCA['tx_blogs_categories']['ctrl'],
	'interface' => array (
		'showRecordFieldList' => 'hidden,title'
	),
	'feInterface' => $TCA['tx_blogs_categories']['feInterface'],
	'columns' => array (
		'hidden' => array (		
			'exclude' => 1,
			'label'   => 'LLL:EXT:lang/locallang_general.xml:LGL.hidden',
			'config'  => array (
				'type'    => 'check',
				'default' => '0'
			)
		),
		'title' => array (		
			'exclude' => 0,		
			'label' => 'LLL:EXT:blogs/locallang_db.xml:tx_blogs_categories.title',		
			'config' => array (
				'type' => 'input',	
				'size' => '30',	
				'eval' => 'required,trim',
			)
		),
	),
	'types' => array (
		'0' => array('showitem' => 'hidden;;1;;1-1-1, title;;;;2-2-2')
	),
	'palettes' => array (
		'1' => array('showitem' => '')
	)
);



$TCA['tx_blogs_items'] = array (
	'ctrl' => $TCA['tx_blogs_items']['ctrl'],
	'interface' => array (
		'showRecordFieldList' => 'hidden,title,author,author_email,category,teaser,bodytext,tags'
	),
	'feInterface' => $TCA['tx_blogs_items']['feInterface'],
	'columns' => array (
		'hidden' => array (		
			'exclude' => 1,
			'label'   => 'LLL:EXT:lang/locallang_general.xml:LGL.hidden',
			'config'  => array (
				'type'    => 'check',
				'default' => '0'
			)
		),
		'title' => array (		
			'exclude' => 0,		
			'label' => 'LLL:EXT:blogs/locallang_db.xml:tx_blogs_items.title',		
			'config' => array (
				'type' => 'input',	
				'size' => '30',	
				'eval' => 'required,trim',
			)
		),
		'author' => array (		
			'exclude' => 0,		
			'label' => 'LLL:EXT:blogs/locallang_db.xml:tx_blogs_items.author',		
			'config' => array (
				'type' => 'input',	
				'size' => '30',	
				'eval' => 'required,trim',
			)
		),
		'author_email' => array (		
			'exclude' => 0,		
			'label' => 'LLL:EXT:blogs/locallang_db.xml:tx_blogs_items.author_email',		
			'config' => array (
				'type' => 'input',	
				'size' => '30',	
				'eval' => 'required,trim',
			)
		),
		'category' => array (		
			'exclude' => 0,		
			'label' => 'LLL:EXT:blogs/locallang_db.xml:tx_blogs_items.category',		
			'config' => array (
				'type' => 'select',	
				'foreign_table' => 'tx_blogs_categories',	
				'foreign_table_where' => 'ORDER BY tx_blogs_categories.uid',	
				'size' => 1,	
				'minitems' => 0,
				'maxitems' => 1,
			)
		),
		'teaser' => array (		
			'exclude' => 0,		
			'label' => 'LLL:EXT:blogs/locallang_db.xml:tx_blogs_items.teaser',		
			'config' => array (
				'type' => 'text',
				'cols' => '30',
				'rows' => '5',
				'wizards' => array(
					'_PADDING' => 2,
					'RTE' => array(
						'notNewRecords' => 1,
						'RTEonly'       => 1,
						'type'          => 'script',
						'title'         => 'Full screen Rich Text Editing|Formatteret redigering i hele vinduet',
						'icon'          => 'wizard_rte2.gif',
						'script'        => 'wizard_rte.php',
					),
				),
			)
		),
		'bodytext' => array (		
			'exclude' => 0,		
			'label' => 'LLL:EXT:blogs/locallang_db.xml:tx_blogs_items.bodytext',		
			'config' => array (
				'type' => 'text',
				'cols' => '30',
				'rows' => '5',
				'wizards' => array(
					'_PADDING' => 2,
					'RTE' => array(
						'notNewRecords' => 1,
						'RTEonly'       => 1,
						'type'          => 'script',
						'title'         => 'Full screen Rich Text Editing|Formatteret redigering i hele vinduet',
						'icon'          => 'wizard_rte2.gif',
						'script'        => 'wizard_rte.php',
					),
				),
			)
		),
		'tags' => array (		
			'exclude' => 0,		
			'label' => 'LLL:EXT:blogs/locallang_db.xml:tx_blogs_items.tags',		
			'config' => array (
				'type' => 'input',	
				'size' => '30',	
				'eval' => 'required,trim',
			)
		),
	),
	'types' => array (
		'0' => array('showitem' => 'hidden;;1;;1-1-1, title;;;;2-2-2, author;;;;3-3-3, author_email, category, teaser;;;richtext[], bodytext;;;richtext[]:rte_transform[mode=ts], tags')
	),
	'palettes' => array (
		'1' => array('showitem' => '')
	)
);



$TCA['tx_blogs_comments'] = array (
	'ctrl' => $TCA['tx_blogs_comments']['ctrl'],
	'interface' => array (
		'showRecordFieldList' => 'hidden,item_id,name,email,url,bodytext'
	),
	'feInterface' => $TCA['tx_blogs_comments']['feInterface'],
	'columns' => array (
		'hidden' => array (		
			'exclude' => 1,
			'label'   => 'LLL:EXT:lang/locallang_general.xml:LGL.hidden',
			'config'  => array (
				'type'    => 'check',
				'default' => '0'
			)
		),
		'item_id' => array (		
			'exclude' => 0,		
			'label' => 'LLL:EXT:blogs/locallang_db.xml:tx_blogs_comments.item_id',		
			'config' => array (
				'type' => 'select',	
				'foreign_table' => 'tx_blogs_items',	
				'foreign_table_where' => 'ORDER BY tx_blogs_items.uid',	
				'size' => 1,	
				'minitems' => 0,
				'maxitems' => 1,
			)
		),
		'name' => array (		
			'exclude' => 0,		
			'label' => 'LLL:EXT:blogs/locallang_db.xml:tx_blogs_comments.name',		
			'config' => array (
				'type' => 'input',	
				'size' => '30',	
				'eval' => 'trim',
			)
		),
		'email' => array (		
			'exclude' => 0,		
			'label' => 'LLL:EXT:blogs/locallang_db.xml:tx_blogs_comments.email',		
			'config' => array (
				'type' => 'input',	
				'size' => '30',	
				'eval' => 'trim',
			)
		),
		'url' => array (		
			'exclude' => 0,		
			'label' => 'LLL:EXT:blogs/locallang_db.xml:tx_blogs_comments.url',		
			'config' => array (
				'type' => 'input',	
				'size' => '30',	
				'eval' => 'trim',
			)
		),
		'bodytext' => array (		
			'exclude' => 0,		
			'label' => 'LLL:EXT:blogs/locallang_db.xml:tx_blogs_comments.bodytext',		
			'config' => array (
				'type' => 'text',
				'cols' => '30',
				'rows' => '5',
				'wizards' => array(
					'_PADDING' => 2,
					'RTE' => array(
						'notNewRecords' => 1,
						'RTEonly'       => 1,
						'type'          => 'script',
						'title'         => 'Full screen Rich Text Editing|Formatteret redigering i hele vinduet',
						'icon'          => 'wizard_rte2.gif',
						'script'        => 'wizard_rte.php',
					),
				),
			)
		),
	),
	'types' => array (
		'0' => array('showitem' => 'hidden;;1;;1-1-1, item_id, name, email, url, bodytext;;;richtext[]:rte_transform[mode=ts]')
	),
	'palettes' => array (
		'1' => array('showitem' => '')
	)
);
?>