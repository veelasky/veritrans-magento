<?php

class Veelasky_Veritrans_Block_Form extends Mage_Payment_Block_Form {
	
	/**
	 * Class Constructor
	 *
	 * @see Mage_Core_Block_Template::_construct()
	 */
	protected function _construct() {
		parent::_construct();
		$this->setTemplate('veritrans/form.phtml');
	}
}