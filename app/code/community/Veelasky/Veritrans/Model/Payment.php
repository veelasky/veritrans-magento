<?php
/**
 * Veritrans Payment
 * 	
 * @package		Magento
 * @author		veelasky <riefky.alhuraibi@gmail.com>
 * @version		0.1.0
 * 
 * @see			https://github.com/veritrans/
 */

class Veelasky_Veritrans_Model_Payment extends Mage_Payment_Model_Method_Abstract {	
	const REQUEST_KEY_URL = 'https://payments.veritrans.co.id/web1/commodityRegist.action';
	const PAYMENT_REDIRECT_URL = 'https://payments.veritrans.co.id/web1/paymentStart.action';
	
	const PAYMENT_TYPE_AUTH = 'AUTHORIZATION';
	const PAYMENT_TYPE_SALE = 'SALE';
	
	/**
	 * Payment Code
	 * 
	 * @var string;
	 */
	
	protected $_code 						= 'veritrans';
	protected $_formBlockType				= 'veritrans/form';
	protected $_canAuthorize                = true;
	protected $_canCapture                  = true;
	protected $_canUseInternal              = false;
	protected $_canUseForMultishipping      = false;
	protected $_allowCurrencyCode			= array('IDR');
	
	private $settlement_type 				= '01';
	
	public function assignData($data) {
		if (! ($data instanceof Varien_Object)) {
			$data = new Varien_Object($data);
		}
		
		$info = $this->getInfoInstance();
		$info->setCcType($data->getAmex());
		
		return $this;
	}
	
	public function createFormBlock($name) {
		$block = $this->getLayout()->createBlock('veritrans/form', $name)
					  ->setMethod('veritrans')
					  ->setTemplate('veritrans/form.phtml');
		
		return block;
	}
	
	public function validate() {
		parent::validate();
		$currency_code = Mage::getSingleton('checkout/session')->getQuote()->getBaseCurrencyCode();
		
		if (!in_array($currency_code, $this->_allowCurrencyCode)) {
			Mage::throwException(Mage::helper('veritrans')->__("Selected currency code($currency_code) is not accepted for Veritrans Payment"));
		}
		
		return $this;
	}
	
	public function onOrderValidate(Mage_Sales_Model_Order_Payment $payment) {
		return $this;
	}
	
	public function onInvoiceCreate(Mage_Sales_Model_Invoice_Payment $payment) {
		
	}
	
	public function getOrderPlaceRedirectUrl() {
		return Mage::getUrl('veritrans/payment/process', array('_secure' => true));
	}
	
	public function initialize($paymentAction, $stateObject) {
		$state = Mage_Sales_Model_Order::STATE_PENDING_PAYMENT;
		$stateObject->setState($state);
		$stateObject->setIsNotified(false);
	}
	
	public function authorize(Varien_Object $payment, $amount) {
		$checkout	= Mage::getSingleton('checkout/session');
		$session	= Mage::getSingleton('core/session');
		$shipping 	= $checkout->getQuote()->getShippingAddress();
		$totals		= $checkout->getQuote()->getTotals();
		
		//Mage::throwException('debug:' . $checkout->getQuoteId());
		
		$commodity = array();
		
		foreach ($checkout->getQuote()->getAllVisibleItems() as $item) {
			$name = (strlen($item->getName()) > 26) ? substr($item->getName(), 0, 26) : $item->getName();
			$commodity[] = array(
					'COMMODITY_ID'		=> $item->getProductId(),
					'COMMODITY_PRICE'	=> $item->getBaseCalculationPrice(),
					'COMMODITY_QTY'		=> $item->getQty(),
					'COMMODITY_NAME1'	=> $name,
					'COMMODITY_NAME2'	=> $name,
			);
		}
		
		$shippingAndTax = $totals['grand_total']->getValue() - $totals['subtotal']->getValue();
		
		if ($shippingAndTax > 0) {
			$commodity[] = array(
					'COMMODITY_ID'		=> 'tax-shipping',
					'COMMODITY_PRICE'	=> $shippingAndTax,
					'COMMODITY_QTY'		=> 1,
					'COMMODITY_NAME1'	=> 'Tax And Shipping',
					'COMMODITY_NAME2'	=> 'Tax And Shipping'
			);
		}
		
		/*
		$commodity[] = array(
				'COMMODITY_ID'		=> 'tax',
				'COMMODITY_PRICE'	=> $shipping['tax_amount'],
				'COMMODITY_QTY'		=> 1,
				'COMMODITY_NAME1'	=> 'Tax',
				'COMMODITY_NAME2'	=> 'Tax'
		);
		
		*/
		
		$parameter = array(
				'SETTLEMENT_TYPE'				=> $this->getSettlementType(),
				'MERCHANT_ID'					=> $this->getMerchantId(),
				'ORDER_ID'						=> $checkout->getQuoteId(),
				'SESSION_ID'					=> $session->getEncryptedSessionId(),
				'GROSS_AMOUNT'					=> $totals['grand_total']->getValue(),
				'PREVIOUS_CUSTOMER_FLAG'		=> null,
				'CUSTOMER_STATUS'				=> 'retail',
				'MERCHANTHASH'					=> $this->getHash($checkout->getQuoteId(), $totals['grand_total']->getValue()),
					
				'CUSTOMER_SPECIFICATION_FLAG'	=> 1,
				'EMAIL'							=> null,
				'FIRST_NAME'					=> null,
				'LAST_NAME'						=> null,
				'POSTAL_CODE'					=> null,
				'ADDRESS1'						=> null,
				'ADDRESS2'						=> null,
				'CITY'							=> null,
				'COUNTRY_CODE'					=> null,
				'PHONE'							=> null,
		
				'SHIPPING_FLAG'					=> 0,
				'SHIPPING_FIRST_NAME'			=> null,
				'SHIPPING_LAST_NAME'			=> null,
				'SHIPPING_ADDRESS1'				=> null,
				'SHIPPING_ADDRESS2'				=> null,
				'SHIPPING_CITY'					=> null,
				'SHIPPING_COUNTRY_CODE'			=> null,
				'SHIPPING_POSTAL_CODE'			=> null,
				'SHIPPING_PHONE'				=> null,
				'SHIPPING_METHOD'				=> null,
					
				'CARD_NO'						=> null,
				'CARD_EXP_DATE'					=> null,
		
				'FINISH_PAYMENT_RETURN_URL'		=> Mage::getUrl('veritrans/payment/success', array('_secure' => true)),
				'UNFINISH_PAYMENT_RETURN_URL'	=> Mage::getUrl('veritrans/payment/cancel', array('_secure' => true)),
				'ERROR_PAYMENT_RETURN_URL'		=> Mage::getUrl('veritrans/payment/error', array('_secure' => true)),
		
				'LANG_ENABLE_FLAG'				=> 1,
				'LANG'							=> 'en',
		);
		
		$token = $this->getKeys($commodity, $parameter);
		
		if (! array_key_exists('token_browser', $token) OR ! array_key_exists('token_merchant', $token)) {
			Mage::throwException('[Failed] Veritrans: '. $token['error_message']);
		} else {
			$session->setTokenBrowser($token['token_browser']);
			$session->setVeritransQuoteId($checkout->getQuoteId());
		}
		
		$veritrans = Mage::getModel('veritrans/veritrans')->setData(array(
			'session_id'		=> $session->getEncryptedSessionId(),
			'order_id'			=> $checkout->getQuoteId(),
			'token_merchant'	=> $token['token_merchant'],
			'amount'			=> $totals['grand_total']->getValue(),
			'status'			=> 'processing',
			'start_time'		=> date('Y-m-d H:i:s'),
		));
		
		try {
			$veritrans_id = $veritrans->save()->getId();
		} catch (Exception $e) {
			Mage::throwException($e->getMessage());
		}
	}
	
	/**
	 * Retrieve Merchant ID From Config
	 *
	 * @return string;
	 */
	public function getMerchantId() {
		return Mage::getStoreConfig('payment/veritrans/merchant_id');
	}
	
	/**
	 * Retrieve Merchant Hash From Config
	 *
	 * @return string;
	 */
	public function getMerchantHash() {
		return Mage::getStoreConfig('payment/veritrans/merchant_hash');
	}
	
	/**
	 * Retrieve Settlement Type
	 *
	 * @return	string;
	 */
	
	public function getSettlementType() {
		return $this->settlement_type;
	}
	
	/**
	 * Retrieve Veritrans Gateway URL
	 *
	 * @return	string;
	 */
	public function getPaymentUrl() {
		return self::PAYMENT_REDIRECT_URL;
	}
	
	/**
	 * Generate Hash with Veritrans Compliance
	 *
	 * @param 	integer $orderID
	 * @param 	decimal $amount
	 * @return	string;
	 */
	public function getHash($orderID, $amount) {
		$crypt = hash_init('sha512');
	
		$str = $this->getMerchantHash() .
		"," . $this->getMerchantId() .
		"," . $this->settlement_type .
		"," . $orderID .
		","	. $amount;
		hash_update($crypt, $str);
		$hash = hash_final($crypt, true);
		
		Mage::log($str);
		
		return bin2hex($hash);
	}
	
	/**
	 * Get Token From Veritrans Server
	 *
	 * @param 	array $commodity
	 * @param 	array $data
	 * @return 	string;
	 */
	
	public function getKeys($commodity = array(), $data = array()) {
		$query_string = http_build_query($data);
		$commodity_string = $this->buildCommodityString($commodity);
	
		$query_string = "$query_string&$commodity_string";
		
		Mage::log($query_string);
		
		$client = new Pest_Rest(self::REQUEST_KEY_URL);
		$result = $client->post('', $query_string);
	
		$key = $this->extractKeys($result);
		
		Mage::log($key);
		
		return $key;
	}
	
	/**
	 * Convert Commodity to string
	 *
	 * @param 	array $commodity
	 * @return	string;
	 */
	private function buildCommodityString($commodity) {
		$line = 0;
		$query_string = "";
		foreach ($commodity as $row) {
			$row = $this->replaceParamsWithLegacy($row);
	
			$q = http_build_query($row);
			if(!($query_string==""))
				$query_string = $query_string . "&";
			$query_string = $query_string . $q;
			$line = $line + 1;
		};
		$query_string = $query_string . "&REPEAT_LINE=" . $line;
			
		return $query_string;
	}
	
	/**
	 * Extract key to readable veritrans format
	 *
	 * @param 	string $body
	 * @return	string
	 */
	private function extractKeys($body) {
		$key = array();
	
		$body_lines = explode("\n", $body);
		foreach($body_lines as $line) {
			if(preg_match('/^TOKEN_MERCHANT=(.+)/', $line, $match)) {
				$key['token_merchant'] = str_replace("\r", "", $match[1]);
			} elseif(preg_match('/^TOKEN_BROWSER=(.+)/', $line, $match)) {
				$key['token_browser'] = str_replace("\r", "", $match[1]);
			} elseif(preg_match('/^ERROR_MESSAGE=(.+)/', $line, $match)) {
				$key['error_message'] = str_replace("\r", "", $match[1]);
			}
		}
		return $key;
	}
	
	/**
	 * Replace Parameter with Legacy Parameter
	 *
	 * @param 	string $commodity
	 * @return	string
	 */
	private function replaceParamsWithLegacy($commodity) {
		if(array_key_exists("COMMODITY_QTY", $commodity) && $commodity["COMMODITY_QTY"] != '' ) {
			$commodity["COMMODITY_NUM"] = $commodity["COMMODITY_QTY"];
			unset($commodity["COMMODITY_QTY"]);
		}
	
		if(array_key_exists("COMMODITY_PRICE", $commodity) && $commodity["COMMODITY_PRICE"] != '') {
			$commodity["COMMODITY_UNIT"] = $commodity["COMMODITY_PRICE"];
			unset($commodity["COMMODITY_PRICE"]);
		}
			
		return $commodity;
	}
}