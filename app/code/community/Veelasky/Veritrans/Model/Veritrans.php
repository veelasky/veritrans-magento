<?php
/**
 * Veritrans Data Model
 *
 * @package		Magento
 * @author		veelasky <riefky.alhuraibi@gmail.com>
 * @version		0.1.0
 *
 * @see			https://github.com/veritrans/
 */
class Veelasky_Veritrans_Model_Veritrans extends Mage_Core_Model_Abstract {
	
	public function _construct() {
		parent::_construct();
		$this->_init('veritrans/veritrans');
	}
}