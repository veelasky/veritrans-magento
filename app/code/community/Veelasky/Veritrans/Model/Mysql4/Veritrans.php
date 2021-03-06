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

Class Veelasky_Veritrans_Model_Mysql4_Veritrans extends Mage_Core_Model_Mysql4_Abstract {
	
	protected function _construct() {
		$this->_init('veritrans/veritrans', 'veritrans_id');
	}
}