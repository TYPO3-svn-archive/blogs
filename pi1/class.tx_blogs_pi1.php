<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2009 J. Paardekooper <jesper_paardekooper@hotmail.com>
*  All rights reserved
*
*  This script is part of the TYPO3 project. The TYPO3 project is
*  free software; you can redistribute it and/or modify
*  it under the terms of the GNU General Public License as published by
*  the Free Software Foundation; either version 2 of the License, or
*  (at your option) any later version.
*
*  The GNU General Public License can be found at
*  http://www.gnu.org/copyleft/gpl.html.
*
*  This script is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*
*  This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/
/**
 * [CLASS/FUNCTION INDEX of SCRIPT]
 *
 * Hint: use extdeveval to insert/update function index above.
 */

require_once(PATH_tslib.'class.tslib_pibase.php');


/**
 * Plugin 'Blogs' for the 'blogs' extension.
 *
 * @author	J. Paardekooper <jesper_paardekooper@hotmail.com>
 * @package	TYPO3
 * @subpackage	tx_blogs
 */
class tx_blogs_pi1 extends tslib_pibase {
	var $prefixId      = 'tx_blogs_pi1';		// Same as class name
	var $scriptRelPath = 'pi1/class.tx_blogs_pi1.php';	// Path to this script relative to the extension dir.
	var $extKey        = 'blogs';	// The extension key.
	var $pi_checkCHash = true;
	
	/**
	 * The main method of the PlugIn
	 *
	 * @param	string		$content: The PlugIn content
	 * @param	array		$conf: The PlugIn configuration
	 * @return	The content that is displayed on the website
	 */
	function main($content, $conf) {
		$this->conf = $conf;
		$this->pi_setPiVarDefaults();
		$this->pi_loadLL();
		
			// Get Flexform
		$this->pi_initPIflexForm();	

			// Check configuration, we need at least a storage container specified
		$this->blogStorage = $this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'BlogStorage');
		if(empty($this->blogStorage)) return $this->pi_getLL('no_storage_selected');
		
			// .. and a Template
		if(!isset($this->conf['templateFile'])) return $this->pi_getLL('no_static_template');
		$this->templateFile = $this->cObj->fileResource($this->conf['templateFile']);
			
			// Set listview page, if not set in TypoScript, use current
		(empty($this->conf['listViewPage'])) ? $this->listViewPage = $GLOBALS['TSFE']->id : $this->listViewPage = $this->conf['listViewPage'];

			// Get content
		$content = $this->whatToShow();

		return $this->pi_wrapInBaseClass($content);
	}
	
	/**
	 * Decides what to show by importing values set in Flexform
	 * 
	 * @return	string		The content that is displayed on the website
	 */
	protected function whatToShow() {
		$whatToShow = $this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'WhatToShow');	
		switch($whatToShow) {
			case('single');
				$content = $this->singleView();
			break;
			case('list');
				$content = $this->listView();
			break;
			case('archive');
				$content = $this->archiveView();
			break;
			case('tagcloud');
				$content = $this->tagcloudView();
			break;
			default:
					// Show error message
				$content = $this->pi_getLL('no_type_selected');
		}
		
		return $content;
	}
	
	/**
	 * This method returns the listview. Can filter on tags, categories and date. Default shows latest items,
	 * amount specified in TypoScript/Flexform (to do)
	 * 
	 * @return	string		The listview
	 */
	protected function listView() {
			// Check configuration, we need to know what the singleview page will be
		$singleView = $this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'SingleView');;
		if(!isset($singleView)) return $this->pi_getLL('no_singleview_page_selected');
		
			// Initialise
		$filter = '';
		
			// Category filter
		if(isset($this->piVars['categoryid']) && is_numeric($this->piVars['categoryid'])) $filter = ' AND tx_blogs_items.category = '.$this ->piVars['categoryid'];
		
			// Tag filter
		if(isset($this->piVars['tag'])) $filter = ' AND tags LIKE \'%'.mysql_real_escape_string($this->piVars['tag']).'%\'';
	
			// Date filter
		if(isset($this->piVars['year']) && isset($this->piVars['month']) && is_numeric($this->piVars['year']) && is_numeric($this->piVars['month'])) {
			$crdateMin = mktime(0, 0, 0, $this->piVars['month'], 0, $this->piVars['year']);
			$crdateMax = mktime(0, 0, 0, $this->piVars['month'] + 1, 0, $this->piVars['year']);
			
			$filter = ' AND tx_blogs_items.crdate < '.$crdateMax.' AND tx_blogs_items.crdate >= '.$crdateMin;
		}
		
			// Execute the query
		$results = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows(
			'tx_blogs_items.uid, tx_blogs_items.title, tx_blogs_items.author, tx_blogs_items.author_email, tx_blogs_items.teaser, 
			tx_blogs_items.tags, tx_blogs_items.crdate, tx_blogs_categories.uid AS category_id, tx_blogs_categories.title AS category_title',
			'tx_blogs_items, tx_blogs_categories',
			'tx_blogs_items.category = tx_blogs_categories.uid AND tx_blogs_items.pid = '.$this->blogStorage.' AND tx_blogs_categories.pid = '.$this->blogStorage .  
			$this->cObj->enableFields('tx_blogs_items') . $filter,
			'',
			'crdate DESC',
			$this->conf['resultsPerPage']
		);

			// Result check
		if(count($results)) {
				// Set template
			$template = $this->cObj->getSubpart($this->templateFile, '###LISTVIEW###');
			
				// Item subpart
			$items = $this->cObj->getSubpart($template, '###ITEM_SUBPART###');
			$subpartArray = array('###ITEM_SUBPART###' => '');
		
				// Loop through resultset
			foreach($results as $result) {
					// Find out how many comments were posted for this entry
				$comments = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows(
					'count(*) as count',
					'tx_blogs_comments',
					'item_id = '.$result['uid'] . $this->cObj->enableFields('tx_blogs_comments')
				);
			
					// Create a fresh markerArray and fill it
				$markerArray = array(
					'###TITLE###' => $result['title'],
					'###COMMENTS###' => ($comments[0]['count'] == 1) ? str_replace('%s', $comments[0]['count'], $this->pi_getLL('comments_link_single')) : str_replace('%s', $comments[0]['count'], $this->pi_getLL('comments_link')),
					'###AUTHOR_EMAIL###' => $result['author_email'],
					'###CATEGORY_LINK###' => $this->pi_getPageLink($this->listViewPage, '', array($this->prefixId => array('categoryid' => $result['category_id']))),
					'###CATEGORY###' => $result['category_title'],
					'###COMMENTS_LINK###' => $this->pi_getPageLink($singleView, '', array($this->prefixId => array('itemid' => $result['uid']))).'#comments', 
					'###AUTHOR###' => $result['author'], 
					'###DATE###' => str_replace('%s', date($this->conf['listView.']['dateFormat'], $result['crdate']), $this->pi_getLL('posted_header')),
					'###TEASER###' => $result['teaser'],
					'###READ_MORE_LABEL###' => $this->pi_getLL('read_more_label'),
					'###READ_MORE_LINK###' => $this->pi_getPageLink($singleView, '', array($this->prefixId => array('itemid' => $result['uid'])))
				);
					// Substitute it
 				$subpartArray['###ITEM_SUBPART###'] .= $this->cObj->substituteMarkerArrayCached($items, $markerArray);
			}
			
				/// To do: pagebrowser
			$markerArray = array('###PAGE_BROWSER###' => '');
			
				// Check if RSS feed is enabled
			if($this->conf['listView.']['includeRSSFeed']) {
				// TO DO
			}
				// Substitute the whole thing
			$content = $this->cObj->substituteMarkerArrayCached($template, $markerArray, $subpartArray);
		} else {
				// No items were found
			return $this->pi_getLL('no_listview_items_found');
		}
	

		return $content;
	}
	
	/**
	 * This method prints the singleview. Includes a commentform (featuring Captcha support if sr_freecap is installed)
	 * 
	 * @return	string		The singleview page
	 */
	protected function singleView() {
			// Check GET vars
		if(!isset($this->piVars['itemid']) || !is_numeric($this->piVars['itemid'])) return 'item not found';

			// Check if sr_freecap is loaded
		if(t3lib_extMgm::isLoaded('sr_freecap')) {
				// It is, include it
			require_once(t3lib_extMgm::extPath('sr_freecap').'pi2/class.tx_srfreecap_pi2.php');
				// Instantiate
			$this->freeCap = t3lib_div::makeInstance('tx_srfreecap_pi2');
		}				
	
			// Init error
		$error = '';
			
			// Comment form handler
		if($_SERVER['REQUEST_METHOD'] == 'POST') {
				// If sr_freecap is installed, we check if the value matched the image
			if(is_object($this->freeCap) && !$this->freeCap->checkWord($this->piVars['captcha_response'])) $error = $this->pi_getLL('wrong_captcha');

				// Check for empty fields
			if(empty($this->piVars['name']) || empty($this->piVars['email']) || empty($this->piVars['url']) || empty($this->piVars['bodytext'])) 
				$error = $this->pi_getLL('empty_fields');
				
				// Email check
			if(!t3lib_div::validEmail($this->piVars['email'])) $error = $this->pi_getLL('invalid_email');	
				
				// Error check
			if(!empty($error)) { 
					// Build insert array
				$insertFields = array(
					'name' => $this->piVars['name'],
					'pid' => $this->blogStorage,
					'email' => $this->piVars['email'],
					'url' => $this->piVars['url'],
					'bodytext' => $this->piVars['bodytext'],
					'item_id' => $this->piVars['id'],
					'crdate' => time()
				);
				
					// Insert it
				$GLOBALS['TYPO3_DB']->exec_INSERTquery(
					'tx_blogs_comments',
					$insertFields
				);	
			}
		}
	
			// Select the specified item
		$result = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows(
			'tx_blogs_items.uid, tx_blogs_items.title, tx_blogs_items.author, tx_blogs_items.author_email, 
			tx_blogs_items.tags, tx_blogs_items.crdate, tx_blogs_items.bodytext, tx_blogs_categories.uid AS category_id, tx_blogs_categories.title AS category_title',
			'tx_blogs_items, tx_blogs_categories',
			'tx_blogs_items.uid = '.(int) $this->piVars['itemid'] .' AND tx_blogs_items.category = tx_blogs_categories.uid AND tx_blogs_items.pid = '.$this->blogStorage .' AND
			tx_blogs_categories.pid = '.$this->blogStorage . $this->cObj->enableFields('tx_blogs_items')
		);

			// Result check
		if(count($result)) {
				// Get template
			$template = $this->cObj->getSubpart($this->templateFile, '###SINGLEVIEW###');
			
				// Get all comments
			$results = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows(
				'name, url, crdate, bodytext',
				'tx_blogs_comments',
				'item_id = '.(int) $this->piVars['itemid'].' AND pid = '.$this->blogStorage . $this->cObj->enableFields('tx_blogs_comments'),
				'',
				'crdate DESC'
			);
		
				// Result check for comments
			if(count($results)) {
					// Comment subpart
				$comments = $this->cObj->getSubpart($template, '###COMMENT_SUBPART###');
				$subpartArray = array('###COMMENT_SUBPART###' => '');
				
					// Odd or even classname for the wrap
				$oddEven = 'even';
				
					// Loop through comments and add them to the subpart
				foreach($results as $comment) {
						// Odd or even?
					($oddEven == 'even') ? $oddEven = 'odd' : $oddEven = 'even';
						// Fill a fresh markerArray
					$markerArray = array(
						'###NAME###' => htmlspecialchars($comment['name']),
						'###URL###' => htmlspecialchars($comment['url']),
						'###COMMENT_DATE###' => date($this->conf['singleView.']['comments.']['dateFormat'], $comment['crdate']),
						'###BODYTEXT###' => htmlspecialchars($comment['bodytext']),
						'###ODD_EVEN###' => $oddEven
					);
						// Substitute to subpart
					$subpartArray['###COMMENT_SUBPART###'] .= $this->cObj->substituteMarkerArrayCached($comments, $markerArray);
				}
			} else {
					// No comments found, leave comment subpart empty
				$subpartArray['###COMMENT_SUBPART###'] = '';
			}
			
				// Init error subpart
			 $subpartArray['###ERROR_SUBPART###'] = '';
			 
				// Check if there was a form error
			if(!empty($error)) {
					// Get ERROR subpart
				$errorPart = $this->cObj->getSubpart($template, '###ERROR_SUBPART###');
					// Fill array marker
				$markerArray = array('###ERROR###' => $error);
				
					// Substitute to subpart
				$subpartArray['###ERROR_SUBPART###'] = $this->cObj->substituteMarkerArrayCached($errorPart, $markerArray);
			}
		
				// Create array out of the comma seperated tags
			$tags = explode(',', $result[0]['tags']);

				// Loop through tags and create a clickable link which points to the listview and filters on that tag
			foreach($tags as $key => &$tag) {
				$tags[$key] = '<a href="'.$this->pi_getPageLink($this->listViewPage, '', array($this->prefixId => array('tag' => trim($tag)))).'">'.trim($tag).'</a>';
			}

				// Now fill the markerArray with the item data
			$markerArray = array(
				'###TITLE###' => $result[0]['title'],
				'###AUTHOR_EMAIL###' => $result[0]['author_email'],
				'###AUTHOR###' => $result[0]['author'],
				'###DATE###' => str_replace('%s', date($this->conf['singleView.']['dateFormat'], $result[0]['crdate']), $this->pi_getLL('posted_header')),
				'###CATEGORY_LINK###' => $this->pi_getPageLink($this->listViewPage, '', array($this->prefixId => array('categoryid' => $result[0]['category_id']))),
				'###CATEGORY###' => $result[0]['category_title'],
				'###COMMENTS_LINK###' => '#comments',		
				'###COMMENTS###' => (count($results) == 1) ? str_replace('%s', count($results), $this->pi_getLL('comments_link_single')) : str_replace('%s', count($results), $this->pi_getLL('comments_link')),
				'###COMMENTS_HEADER###' => (count($results) == 1) ? str_replace('%s', count($results), $this->pi_getLL('comments_header_single')) : str_replace('%s', count($results), $this->pi_getLL('comments_header')),
				'###TAGS###' => str_replace('%s', implode(', ', $tags), $this->pi_getLL('tags')),
				'###BODYTEXT###' => $result[0]['bodytext'],
				'###FORM_ACTION###' => $this->pi_getPageLink($GLOBALS['TSFE']->id, '', array($this->prefixId => array('itemid' => $this->piVars['itemid']))),
				'###ID###' => $this->piVars['itemid'],
				'###NAME_LABEL###' => $this->pi_getLL('name_label'),
				'###EMAIL_LABEL###' => $this->pi_getLL('email_label'),
				'###URL_LABEL###' => $this->pi_getLL('url_label'),
				'###NAME_VALUE###' => (isset($this->piVars['name'])) ? htmlspecialchars($this->piVars['name']) : '',
				'###EMAIL_VALUE###' => (isset($this->piVars['email'])) ? htmlspecialchars($this->piVars['email']) : '',
				'###URL_VALUE###' => (isset($this->piVars['url'])) ? htmlspecialchars($this->piVars['url']) : '',
				'###BODYTEXT_VALUE###' => (isset($this->piVars['bodytext'])) ? htmlspecialchars($this->piVars['bodytext']) : '',
				'###SUBMIT_COMMENT###' => $this->pi_getLL('submit_comment_label')
			);
			
				// Show captcha fields in the commentform if sr_freecap is installed
			if(is_object($this->freeCap)) {
				$markerArray = array_merge($markerArray, $this->freeCap->makeCaptcha());
			} else {
					// Otherwise, leave this empty
				$subpartArray['###CAPTCHA_INSERT###'] = '';
			}
				
				// Substitute the whole thing
			$content = $this->cObj->substituteMarkerArrayCached($template, $markerArray, $subpartArray);
			
				// Set our tags as META keywords to improve SEO
			if(!empty($result[0]['tags'])) {
				$GLOBALS['TSFE']->additionalHeaderData[] = '<meta name="keywords" content="'.$result[0]['tags'].'" />';
			}
		} else {
				// Item wasn't found
			return $this->pi_getLL('item_not_found');
		}
		
		return $content;
	}
	
	/**
	 * This method creates the archive view
	 *  
	 * @return	string		The archive content
	 */
	public function archiveView() {
			// Template
		$template = $this->cObj->getSubpart($this->templateFile, '###ARCHIVEVIEW###');
			
			// Select the crdate of all items
		$results = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows(
			'crdate',
			'tx_blogs_items',
			'pid = '.$this->blogStorage . $this->cObj->enableFields('tx_blogs_items'),
			'',
			'crdate DESC'
		);
			// Initialise array
		$datesArray = array();
		
			// Loop through all crdate's and convert the timestamp to year, month and month number. Save these values seperatly in $datesArray
		foreach($results as $result) {
			$year = date('Y', $result['crdate']);
			$month = date('F', $result['crdate']);
			$monthNo = date('n', $result['crdate']);
			
			if(!is_array($datesArray[$year][$monthNo])) $datesArray[$year][$monthNo] = array();
			
			if(array_key_exists($month, $datesArray[$year][$monthNo])) {
					// Increment amount if key already exists
				$datesArray[$year][$monthNo][$month]++;	
			} else {
				$datesArray[$year][$monthNo][$month] = 1;	
			}
		}

			// Get dateview subpart
		$categories = $this->cObj->getSubpart($template, '###DATEVIEW_SUBPART###');
		$subpartArray = array('###DATEVIEW_SUBPART###' => '');

			// Loop through dates
		foreach($datesArray as $year => $monthArray) {
			foreach($monthArray as $monthNo => $monthArray) {
				foreach($monthArray as $month => $count) {
						// Fill markerArray		
					$markerArray = array(
						'###CATEGORY_LINK###' => $this->pi_getPageLink($this->listViewPage, '', array($this->prefixId => array('year' => $year, 'month' => $monthNo))),
						'###DATE###' => $month.' '.$year,
						'###POST_COUNT###' => $count
					);
						// Substitute to subpart
					$subpartArray['###DATEVIEW_SUBPART###'] .= $this->cObj->substituteMarkerArrayCached($categories, $markerArray);
				}
			}
		}
		
			// Now we want to select all categories
		$results = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows(
			'uid, title',
			'tx_blogs_categories',
			'pid = '.$this->blogStorage . $this->cObj->enableFields('tx_blogs_categories'),
			'',
			'title ASC'
		);

			// Result check
		if(count($results)) {
				// Get subpart
			$categories = $this->cObj->getSubpart($template, '###CATEGORY_SUBPART###');
			$subpartArray['###CATEGORY_SUBPART###'] = '';
			
				// Loop through results
			foreach($results as $result) {
					// Find the amount of items posted in this category
				$count = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows(
					'count(*) as count',
					'tx_blogs_items',
					'category = '.$result['uid'].' AND pid = '.$this->blogStorage . $this->cObj->enableFields('tx_blogs_items')
				);
					// Fill a fresh markerArray
				$markerArray = array(
					'###CATEGORY_LINK###' => $this->pi_getPageLink($this->listViewPage, '', array($this->prefixId => array('categoryid' => $result['uid']))),
					'###CATEGORY_TITLE###' => $result['title'],
					'###POST_COUNT###' => $count[0]['count']
				);
					// Substitute to subpart
				$subpartArray['###CATEGORY_SUBPART###'] .= $this->cObj->substituteMarkerArrayCached($categories, $markerArray);
			}
				// Fill static stuff
			$markerArray = array(
				'###CATEGORY_HEADER###' => $this->pi_getLL('category_header'),
				'###ARCHIVE_HEADER###' => $this->pi_getLL('archive_header')
			);
			
				// Substitute everything
			$content .= $this->cObj->substituteMarkerArrayCached($template, $markerArray, $subpartArray);
		}
		
		return $content;
	}
	
	/**
	 * This method creates a tagcloud
	 * 
	 * @return	string		The tagcloud
	 */
	public function tagcloudView() {
			// Select all tags
		$results = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows(
			'tags',
			'tx_blogs_items',
			'pid = '.$this->blogStorage . $this->cObj->enableFields('tx_blogs_items')
		);
	
			// Result check
		if(count($results)) {
				// Template
			$template = $this->cObj->getSubpart($this->templateFile, '###TAGCLOUDVIEW###');
			
				// Initialise
			$tagArray = array();
			
				// Loop through results
			foreach($results as $result) {
					// Create array out of the tags
				$tags = explode(',', $result['tags']);
					// Loop through these tags and add them to $tagArray, if they exist we increment the count
				foreach($tags as $tag) {
					if(array_key_exists(strtolower(trim($tag)), $tagArray)) {
						$tagArray[strtolower(trim($tag))]++;
					} else {
						$tagArray[strtolower(trim($tag))] = 1;
					}
				}
			}
				// Sort
			arsort($tagArray);	

				// Min and max font sizes from TypoScript, if not set use default values
			$minFontSize = (isset($this->conf['tagCloud.']['minFontSize'])) ? $this->conf['tagCloud.']['minFontSize'] : 8;
			$maxFontSize = (isset($this->conf['tagCloud.']['maxFontSize'])) ? $this->conf['tagCloud.']['maxFontSize'] : 18;
				// Determine difference
			$difference = ($maxFontSize + 1) - ($minFontSize + 1); 	
				// Find highest occurance
			$highestOccurance = max($tagArray);	
				// only select the amount specified by maxTags in TypoScript
			$tagArray = array_slice($tagArray, 0, $this->conf['tagCloud.']['maxTags'], true);	
		
				// Loop through tag array and create final aray
			foreach($tagArray as $tag => $occurance) {
				$newTagArray[$tag] = $minFontSize + round(($occurance / $highestOccurance) * $difference, 0);
			}

				// Shuffle array while preserving keys
			$tags = array();
			$keys = array_keys($newTagArray);	
			shuffle($keys);	
			foreach($keys as $key) {	
				$tags[$key] = $newTagArray[$key];	
				unset($newTagArray[$key]);	
			}
		
				// Now loop through final tag array and create the content
			foreach($tags as $tag => $fontSize) {
				$markerArray['###TAGS###'] .= "\n".'<span style="font-size: '.$fontSize.'px"><a href="'.$this->pi_getPageLink($this->listViewPage, '', array($this->prefixId => array('tag' => $tag))).'" >'.$tag.'</a></span>'."\n";
			}
		} else {
				// No results
			$markerArray['###TAGS###'] = $this->pi_getLL('no_tags_found');
		}
			// Substitute whole thing
		$content = $this->cObj->substituteMarkerArrayCached($template, $markerArray);
		
		return $content;
	}
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/blogs/pi1/class.tx_blogs_pi1.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/blogs/pi1/class.tx_blogs_pi1.php']);
}

?>