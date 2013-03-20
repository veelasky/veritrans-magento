<?php
/**
 * Veritrans Payment Collection
 *
 * @package		Magento
 * @author		veelasky <riefky.alhuraibi@gmail.com>
 * @version		0.1.0
 *
 * @see			https://github.com/veritrans/
 */

class Veelasky_Veritrans_Model_Mysql4_Veritrans_Collection extends Mage_Core_Model_Mysql4_Collection_Abstract {
	
	protected function _construct() {
		$this->_init('veritrans/veritrans');
	}
}