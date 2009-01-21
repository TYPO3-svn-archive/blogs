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
		$this->pi_initPIflexForm();	// load flexform	

		// check configuration
		$this->blogStorage = $this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'BlogStorage');
		if(!isset($this->blogStorage)) return $this->pi_getLL('no_storage_selected');
		
		if(!isset($this->conf['templateFile'])) return $this->pi_getLL('no_template_selected');
		$this->templateFile = $this->cObj->fileResource($this->conf['templateFile']);
	
		$content = $this->whatToShow();

		return $this->pi_wrapInBaseClass($content);
	}
	
	/**
	 * Decides what to show
	 * 
	 * @return	The content that is displayed on the website
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
				$content = $this->pi_getLL('no_type_selected');
		}
		
		return $content;
	}
	
	/**
	 * Listview function
	 * 
	 * @return	Listview content
	 */
	protected function listView() {
		// check configuration
		$singleView = $this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'SingleView');;
		if(!isset($singleView)) return $this->pi_getLL('no_singleview_page_selected');
		
		$filter = '';
		
		// check if we need to filter on categories
		if(isset($this->piVars['categoryid']) && is_numeric($this->piVars['categoryid'])) $filter = ' AND tx_blogs_items.category = '.$this ->piVars['categoryid'];
		
		// check if we need to filter on tags
		if(isset($this->piVars['tag'])) $filter = ' AND tags LIKE \'%'.$this->piVars['tag'].'%\'';
	
		// check if we need to filter on dates
		if(isset($this->piVars['year']) && isset($this->piVars['month']) && is_numeric($this->piVars['year']) && is_numeric($this->piVars['month'])) {
			$crdateMin = mktime(0, 0, 0, $this->piVars['month'], 0, $this->piVars['year']);
			$crdateMax = mktime(0, 0, 0, $this->piVars['month'] + 1, 0, $this->piVars['year']);
			
			$filter = ' AND tx_blogs_items.crdate < '.$crdateMax.' AND tx_blogs_items.crdate >= '.$crdateMin;
		}
		
		// fetch all latest items
		$results = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows(
			'tx_blogs_items.uid, tx_blogs_items.title, tx_blogs_items.author, tx_blogs_items.author_email, tx_blogs_items.teaser, 
			tx_blogs_items.tags, tx_blogs_items.crdate, tx_blogs_categories.uid AS category_id, tx_blogs_categories.title AS category_title',
			'tx_blogs_items, tx_blogs_categories',
			'tx_blogs_items.category = tx_blogs_categories.uid' . $this->cObj->enableFields('tx_blogs_items') . $filter,
			'',
			'crdate DESC',
			5
		);

		if(count($results)) {
			$template = $this->cObj->getSubpart($this->templateFile, '###LISTVIEW###');
			
			// item subpart
			$items = $this->cObj->getSubpart($template, '###ITEM_SUBPART###');
			$subpartArray = array('###ITEM_SUBPART###' => '');
		
			// loop through results
			foreach($results as $result) {
				// find how many comments were posted for this item
				$comments = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows(
					'count(*) as count',
					'tx_blogs_comments',
					'item_id = '.$result['uid'] . $this->cObj->enableFields('tx_blogs_comments')
				);
			
				// recreate markerarray and fill it
				$markerArray = array(
					'###TITLE###' => $result['title'],
					'###COMMENTS###' => ($comments[0]['count'] == 1) ? str_replace('%s', $comments[0]['count'], $this->pi_getLL('comments_link_single')) : str_replace('%s', $comments[0]['count'], $this->pi_getLL('comments_link')),
					'###AUTHOR_EMAIL###' => $result['author_email'],
					'###CATEGORY_LINK###' => $this->pi_getPageLink($this->conf['listViewPage'], '', array($this->prefixId => array('categoryid' => $result['category_id']))),
					'###CATEGORY###' => $result['category_title'],
					'###COMMENTS_LINK###' => $this->pi_getPageLink($singleView, '', array($this->prefixId => array('itemid' => $result['uid']))).'#comments', 
					'###AUTHOR###' => $result['author'], 
					'###DATE###' => str_replace('%s', date('j F Y', $result['crdate']), $this->pi_getLL('posted_header')),
					'###TEASER###' => $result['teaser'],
					'###READ_MORE_LABEL###' => $this->pi_getLL('read_more_label'),
					'###READ_MORE_LINK###' => $this->pi_getPageLink($singleView, '', array($this->prefixId => array('itemid' => $result['uid'])))
				);
 				$subpartArray['###ITEM_SUBPART###'] .= $this->cObj->substituteMarkerArrayCached($items, $markerArray);
			}
			
			$markerArray = array();
			$content = $this->cObj->substituteMarkerArrayCached($template, $markerArray, $subpartArray);
		} else {
			return $this->pi_getLL('no_listview_items_found');
		}

		return $content;
	}
	
	/**
	 * Singleview function
	 * 
	 * @return	Singleview page
	 */
	protected function singleView() {
		if(!isset($this->piVars['itemid']) || !is_numeric($this->piVars['itemid'])) return 'item not found';
		
		// check if the comment form was submit
		if($_SERVER['REQUEST_METHOD'] == 'POST') {
			// to do: validation
			$insertFields = array(
				'name' => $this->piVars['name'],
				'pid' => $this->blogStorage,
				'email' => $this->piVars['email'],
				'url' => $this->piVars['url'],
				'bodytext' => $this->piVars['bodytext'],
				'item_id' => $this->piVars['id'],
				'crdate' => time()
			);
			$GLOBALS['TYPO3_DB']->exec_INSERTquery(
				'tx_blogs_comments',
				$insertFields
			);
		}
	
		// select item
		$result = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows(
			'tx_blogs_items.uid, tx_blogs_items.title, tx_blogs_items.author, tx_blogs_items.author_email, 
			tx_blogs_items.tags, tx_blogs_items.crdate, tx_blogs_items.bodytext, tx_blogs_categories.uid AS category_id, tx_blogs_categories.title AS category_title',
			'tx_blogs_items, tx_blogs_categories',
			'tx_blogs_items.uid = '.(int) $this->piVars['itemid'] .' AND tx_blogs_items.category = tx_blogs_categories.uid '.$this->cObj->enableFields('tx_blogs_items')
		);

		// check results
		if(count($result)) {
			$template = $this->cObj->getSubpart($this->templateFile, '###SINGLEVIEW###');
			
			// comment query
			$results = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows(
				'name, url, crdate, bodytext',
				'tx_blogs_comments',
				'item_id = '.(int) $this->piVars['itemid'] . $this->cObj->enableFields('tx_blogs_comments'),
				'',
				'crdate DESC'
			);
		
			// check for comments
			if(count($results)) {
				$comments = $this->cObj->getSubpart($template, '###COMMENT_SUBPART###');
				$subpartArray = array('###COMMENT_SUBPART###' => '');
				
				// odd or even colours
				$oddEven = 'even';
				
				// loop through comments and add them to subpart
				foreach($results as $comment) {
					($oddEven == 'even') ? $oddEven = 'odd' : $oddEven = 'even';
					$markerArray = array(
						'###NAME###' => htmlspecialchars($comment['name']),
						'###URL###' => htmlspecialchars($comment['url']),
						'###COMMENT_DATE###' => date('j F Y @ G:i', $comment['crdate']),
						'###BODYTEXT###' => htmlspecialchars($comment['bodytext']),
						'###ODD_EVEN###' => $oddEven
					);
					
					$subpartArray['###COMMENT_SUBPART###'] .= $this->cObj->substituteMarkerArrayCached($comments, $markerArray);
				}
			} else {
				// no comments, empty subpart
				$subpartArray['###COMMENT_SUBPART###'] = '';
			}
			
			// now fill the item markerarray
			$markerArray = array(
				'###TITLE###' => $result[0]['title'],
				'###AUTHOR_EMAIL###' => $result[0]['author_emai l'],
				'###AUTHOR###' => $result[0]['author'],
				'###DATE###' => str_replace('%s', date('j F Y', $result[0]['crdate']), $this->pi_getLL('posted_header')),
				'###CATEGORY_LINK###' => $this->pi_getPageLink($this->conf['listViewPage'], '', array($this->prefixId => array('categoryid' => $result[0]['category_id']))),
				'###CATEGORY###' => $result[0]['category_title'],
				'###COMMENTS_LINK###' => '#comments',		
				'###COMMENTS###' => (count($results) == 1) ? str_replace('%s', count($results), $this->pi_getLL('comments_link_single')) : str_replace('%s', count($results), $this->pi_getLL('comments_link')),
				'###COMMENTS_HEADER###' => (count($results) == 1) ? str_replace('%s', count($results), $this->pi_getLL('comments_header_single')) : str_replace('%s', count($results), $this->pi_getLL('comments_header')),
				'###TAGS###' => str_replace('%s', $result[0]['tags'], $this->pi_getLL('tags')),
				'###BODYTEXT###' => $result[0]['bodytext'],
				'###FORM_ACTION###' => $this->pi_getPageLink($GLOBALS['TSFE']->id, '', array($this->prefixId => array('itemid' => $this->piVars['itemid']))),
				'###ID###' => $this->piVars['itemid'],
				'###NAME_LABEL###' => $this->pi_getLL('name_label'),
				'###EMAIL_LABEL###' => $this->pi_getLL('email_label'),
				'###URL_LABEL###' => $this->pi_getLL('url_label'),
				'###SUBMIT_COMMENT###' => $this->pi_getLL('submit_comment_label')
			);
			
			// substitute the whole thing
			$content = $this->cObj->substituteMarkerArrayCached($template, $markerArray, $subpartArray);
			
			// set meta tags
			if(!empty($result[0]['tags'])) {
				$GLOBALS['TSFE']->additionalHeaderData[] = '<meta name="keywords" content="'.$result[0]['tags'].'" />';
			}
		} else {
			$content = $this->pi_getLL('item_not_found');
		}
		
		return $content;
	}
	
	/**
	 * Archive function
	 *  
	 * @return	Archive view
	 */
	public function archiveView() {
		$template = $this->cObj->getSubpart($this->templateFile, '###ARCHIVEVIEW###');
	
		// dateview
			
		// select the crdate of all items
		$results = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows(
			'crdate',
			'tx_blogs_items',
			'1 = 1 '.$this->cObj->enableFields('tx_blogs_items'),
			'',
			'crdate DESC'
		);

		$datesArray = array();
		
		// loop through all of them and store the month/year
		foreach($results as $result) {
			$year = date('Y', $result['crdate']);
			$month = date('F', $result['crdate']);
			$monthNo = date('n', $result['crdate']);
			
			if(!is_array($datesArray[$year][$monthNo])) $datesArray[$year][$monthNo] = array();
			
			if(array_key_exists($month, $datesArray[$year][$monthNo])) {
				$datesArray[$year][$monthNo][$month]++;	// increment amount if key already exists
			} else {
				$datesArray[$year][$monthNo][$month] = 1;	
			}
		}

		// subpart
		$categories = $this->cObj->getSubpart($template, '###DATEVIEW_SUBPART###');
		$subpartArray = array('###DATEVIEW_SUBPART###' => '');

		// loop through dates
		foreach($datesArray as $year => $monthArray) {
			foreach($monthArray as $monthNo => $monthArray) {
				foreach($monthArray as $month => $count) {		
					$markerArray = array(
						'###CATEGORY_LINK###' => $this->pi_getPageLink($this->conf['listViewPage'], '', array($this->prefixId => array('year' => $year, 'month' => $monthNo))),
						'###DATE###' => $month.' '.$year,
						'###POST_COUNT###' => $count
					);
					
					$subpartArray['###DATEVIEW_SUBPART###'] .= $this->cObj->substituteMarkerArrayCached($categories, $markerArray);
				}
			}
		}
		
		// category view 
		
		// select all categories
		$results = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows(
			'uid, title',
			'tx_blogs_categories',
			'1 = 1 '.$this->cObj->enableFields('tx_blogs_categories'),
			'',
			'title ASC'
		);

		// check for results
		if(count($results)) {
			// subpart
			$categories = $this->cObj->getSubpart($template, '###CATEGORY_SUBPART###');
			$subpartArray['###CATEGORY_SUBPART###'] = '';
			
			// loop through results and build the list
			foreach($results as $result) {
				$count = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows(
					'count(*) as count',
					'tx_blogs_items',
					'category = '.$result['uid'] . $this->cObj->enableFields('tx_blogs_items')
				);
				
				$markerArray = array(
					'###CATEGORY_LINK###' => $this->pi_getPageLink($this->conf['listViewPage'], '', array($this->prefixId => array('categoryid' => $result['uid']))),
					'###CATEGORY_TITLE###' => $result['title'],
					'###POST_COUNT###' => $count[0]['count']
				);
				
				$subpartArray['###CATEGORY_SUBPART###'] .= $this->cObj->substituteMarkerArrayCached($categories, $markerArray);
			}
			
			$markerArray = array(
				'###CATEGORY_HEADER###' => $this->pi_getLL('category_header'),
				'###ARCHIVE_HEADER###' => $this->pi_getLL('archive_header')
			);
			
			// substitute everything
			$content .= $this->cObj->substituteMarkerArrayCached($template, $markerArray, $subpartArray);
		}
		
		return $content;
	}
	
	public function tagcloudView() {
		// select all tags
		$results = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows(
			'tags',
			'tx_blogs_items',
			'1 = 1 '.$this->cObj->enableFields('tx_blogs_items')
		);
	
		// check for results
		if(count($results)) {
			$template = $this->cObj->getSubpart($this->templateFile, '###TAGCLOUDVIEW###');
			
			$tagArray = array();	// init
			
			// loop through results
			foreach($results as $result) {
				// create array out of tags
				$tags = explode(',', $result['tags']);
				// loop through these tags and add them to $tagArray, if they exist we increment the count
				foreach($tags as $tag) {
					if(array_key_exists(strtolower(trim($tag)), $tagArray)) {
						$tagArray[strtolower(trim($tag))]++;
					} else {
						$tagArray[strtolower(trim($tag))] = 1;
					}
				}
			}

			arsort($tagArray);	// sort it

			// min and max font sizes from typoscript, if not set use default  values
			$minFontSize = (isset($this->conf['tagCloud.']['minFontSize'])) ? $this->conf['tagCloud.']['minFontSize'] : 8;
			$maxFontSize = (isset($this->conf['tagCloud.']['maxFontSize'])) ? $this->conf['tagCloud.']['maxFontSize'] : 18;
	
			$difference = ($maxFontSize +1) - ($minFontSize + 1); 	// determine difference

			$highestOccurance = max($tagArray);	// find highest occurance
			
			$tagArray = array_slice($tagArray, 0, $this->conf['tagCloud.']['maxTags'], true);	// only select the amount specified by maxTags in typoscript
		
			// loop through tag array and create final aray
			foreach($tagArray as $tag => $occurance) {
				$newTagArray[$tag] = $minFontSize + round(($occurance / $highestOccurance) * $difference, 0);
			}

			// shuffle array while preserving keys
			$tags = array();	// new array
			$keys = array_keys($newTagArray);	// select keys
			shuffle($keys);	// shuffle them
			foreach($keys as $key) {	
				$tags[$key] = $newTagArray[$key];	// assign
				unset($newTagArray[$key]);	// unset
			}
		
			// now loop through final tag array and create the content
			foreach($tags as $tag => $fontSize) {
				$markerArray['###TAGS###'] .= "\n".'<span style="font-size: '.$fontSize.'px"><a href="'.$this->pi_getPageLink($this->conf['listViewPage'], '', array($this->prefixId => array('tag' => $tag))).'" >'.$tag.'</a></span>'."\n";
			}
		} else {
			$markerArray['###TAGS###'] = $this->pi_getLL('no_tags_found');
		}
		
		$content = $this->cObj->substituteMarkerArrayCached($template, $markerArray);
		
		return $content;
	}
	
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/blogs/pi1/class.tx_blogs_pi1.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/blogs/pi1/class.tx_blogs_pi1.php']);
}

?>