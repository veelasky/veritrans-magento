<?php
class Veelasky_Veritrans_Block_Redirect extends Mage_Core_Block_Template {
	
	/**
	 * Set Default Block Template
	 * 
	 * @see Mage_Core_Block_Template::_construct()
	 */
	protected function _construct() {
		$this->setTemplate('veritrans/redirect.phtml');	
	}
	
	/**
	 * Get Token Browser Encryption Key
	 * 
	 * @return string
	 */
	public function getTokenBrowser() {
		return Mage::getSingleton('core/session')->getTokenBrowser();
	}
	
	/**
	 * Get Merchant Id
	 * 
	 * @return string
	 */
	public function getMerchantId() {
		return Mage::getSingleton('veritrans/payment')->getMerchantId();
	}
	
	/**
	 * Get Veritrans Quote Id
	 * 
	 * @return string
	 */
	public function getOrderId() {
		return Mage::getSingleton('core/session')->getVeritransQuoteId();
	}
	
	/**
	 * Get Veritrans gateway url
	 * 
	 * @return string
	 */
	public function getVeritransGatewayUrl() {
		return Mage::getSingleton('veritrans/payment')->getPaymentUrl();
	}
}