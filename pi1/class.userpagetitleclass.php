<?php

class user_pagetitle_class {

	/**
	 * Receives the page title as param, checks if there's blog vars set, if so, change title to put the category or blog item title in the title tag
	 * 
	 * @param	string		The current page title
	 * @return	string		The page title
	 */
	public function changetitle($content) {
		$getVar = t3lib_div::_GET('tx_blogs_pi1');

			// Check if get var exists
		if(isset($getVar) && is_array($getVar)) {
			
				// Check for a blog item
			if(array_key_exists('itemid', $getVar)) {
				$itemId = $this->findTitle();
				$title = &$itemId[0]['title'];
				$title = $title.': My Blog';		
			}

				// Check for blog category
			if(array_key_exists('categoryid', $getVar)) {	
				$catId = $this->findCategory();
				$title = &$catId[0]['title'];
				$title = $title.': My Blog';				
			}	
		}

			// Check if title is set
		if(isset($title) && !empty($title)) {
			return $title;
		} else {
			return $content;
		}
	}
	
	/**
	 * Executes the query to find the item title
	 * 
	 * @return	array		The resultset
	 */
	public function findTitle() {
		$arr = t3lib_div::_GP('tx_blogs_pi1');
		return $GLOBALS['TYPO3_DB']->exec_SELECTgetRows(
			'title',
			'tx_blogs_items',
			'uid = '.(int) $arr['itemid'] . $this->cObj->enableFields('tx_blogs_items')
		);
	}

	/**
	 * Executes the query to find the category title
	 * 
	 * @return	array		The resultset
	 */	
	public function findCategory() {
		$arr = t3lib_div::_GP('tx_blogs_pi1');
		return $GLOBALS['TYPO3_DB']->exec_SELECTgetRows(
			'title',
			'tx_blogs_categories',
			'uid = '.(int) $arr['categoryid'] . $this->cObj->enableFields('tx_blogs_categories')
			
		);
	}
}
?>
