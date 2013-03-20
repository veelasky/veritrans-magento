<?php
class Veelasky_Veritrans_Block_Response extends Mage_Core_Block_Template {
	
	/**
	 * Class Constructor
	 * 
	 * @see Mage_Core_Block_Template::_construct()
	 */
	protected function _construct() {
		parent::_construct();
		$this->setTemplate('veritrans/unauthorized.phtml');
	}
}