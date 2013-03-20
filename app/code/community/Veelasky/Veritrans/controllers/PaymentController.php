<?php
/**
 * Veritrans Payment Controller
 * ====================================================
 * 
 * Veritrans Notification Post Data:
 * 	- <postalcode>
 * 	- <mStatus>
 * 	- <phone>
 * 	- <shippingPhone>
 * 	- <mErrMsg>
 * 	- <email>
 * 	- <address>
 * 	- <name>
 * 	- <vResultCode>
 * 	- <shippingAddress>
 * 	- <orderId>
 * 	- <shippingPostalcode>
 * 	- <shippingName>
 * 	- <TOKEN_MERCHANT>
 * 	
 * @package		Magento
 * @author		veelasky <riefky.alhuraibi@gmail.com>
 * @version		0.1.0
 * 
 * @see			https://github.com/veritrans/
 */

class Veelasky_Veritrans_PaymentController extends Mage_Core_Controller_Front_Action {
	
	/**
	 * Process Action - Redirect User to veritrans payment gateway
	 * 
	 * <veritrans_payment_process>
	 * 
	 * @return 	void;
	 * @see 	<package>/<themes>/layout/veritrans.xml
	 */
	public function processAction() {
		$session = Mage::getSingleton('core/session');
		
		if ($session->getTokenBrowser() == null) {
			$this->_redirect('veritrans/payment/unauthorized');
		} else {
			$this->loadLayout();
			$this->getLayout()->getBlock('head')->setTitle($this->__('Veritrans Payment Gateway'));
			$this->renderLayout();
		}
	}
	
	/**
	 * Success Action - Customer successfully paid their order.
	 *
	 * <veritrans_payment_success>
	 *
	 * @return 	void;
	 * @see 	<package>/<themes>/layout/veritrans.xml
	 */
	public function successAction() {
		$this->loadLayout();
		$this->getLayout()->getBlock('head')->setTitle($this->__('Veritrans Payment Gateway'));
		$this->renderLayout();
	}
	
	/**
	 * Cancel Action - Customer cancelled their order.
	 *
	 * <veritrans_payment_cancel>
	 *
	 * @return 	void;
	 * @see 	<package>/<themes>/layout/veritrans.xml
	 */
	public function cancelAction() {
		$this->loadLayout();
		$this->getLayout()->getBlock('head')->setTitle($this->__('Veritrans Payment Gateway'));
		$this->renderLayout();
	}
	
	/**
	 * Error Action
	 *
	 * <veritrans_payment_error>
	 *
	 * @return 	void;
	 * @see 	<package>/<themes>/layout/veritrans.xml
	 */
	public function errorAction() {
		$this->loadLayout();
		$this->getLayout()->getBlock('head')->setTitle($this->__('Veritrans Payment Gateway'));
		$this->renderLayout();
	}
	
	/**
	 * Unauthorized Action
	 *
	 * <veritrans_payment_unauthorized>
	 *
	 * @return 	void;
	 * @see 	<package>/<themes>/layout/veritrans.xml
	 */
	public function unauthorizedAction() {
		$message = $this->__("Whoaa there!");
		Mage::getSingleton('core/session')->addError($message);
		
		$this->loadLayout();
		$this->getLayout()->getBlock('head')->setTitle($this->__('Veritrans Unauthorized'));
		$this->renderLayout();
	}
	
	/**
	 * Notification Action - Handle Incoming data from veritrans
	 *
	 * @return 	void;
	 * @see 	<package>/<themes>/layout/veritrans.xml
	 */
	public function notificationAction() {
		$checkout = Mage::getSingleton('checkout/session');
		$postdata = Mage::app()->getRequest()->getPost();
		
		$veritrans = Mage::getModel('veritrans/veritrans')->load($postdata['orderId'], 'order_id');
		$id = $veritrans->getData('veritrans_id');
		
		$order = Mage::getSingleton('sales/order')->loadByIncrementId($checkout->getLastRealOrderId());
		
		if ($veritrans->getData('token_merchant') != $postdata['TOKEN_MERCHANT']) {
			$veritrans->addData(array('status' => 'failed'));
			
			$order->setStatus(Mage_Sales_Model_Order::STATE_CLOSED);
		} else {		
			$this->_createInvoice($checkout->getLastRealOrderId());
			$order->setStatus(Mage_Sales_Model_Order::STATE_PROCESSING);
			$order->sendNewOrderEmail();
			$order->setEmailSent(true);
			
			$veritrans->addData(array('status' => 'success'));
		}
		
		$veritrans->setId($id)->save();
		$order->save();
		
		$this->_clearVeritransSession();
	}
	
	/**
	 * Create Invoice for successfull veritrans Payment
	 * 
	 * @param string $orderIncrementId
	 */
	protected function _createInvoice($orderIncrementId){
		$order = Mage::getModel('sales/order')->loadByIncrementId($orderIncrementId);
		$itemsQty = count($order->getAllItems());
		
		$invoice = $order->prepareInvoice($itemsQty);
		$invoice->register();
		$invoice->setOrder($order);
		$invoice->setEmailSent(true);
		$invoice->getOrder()->setIsInProcess(true);
		$invoice->pay();
		$invoice->save();
		$order->save();
	
		return $invoice->getIncrementId();
	}
	
	/**
	 * Clear Veritrans Session
	 * 
	 * @return void
	 */
	protected function _clearVeritransSession() {
		$session = Mage::getSingleton('core/session');
		
		$session->unsTokenBrowser();
		$session->unsVeritransQuoteId();
	}
}