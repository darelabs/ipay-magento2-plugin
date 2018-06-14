<?php
/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Ccc\IPay\Model;

/**
 * Pay In Store payment method model
 */
class Ipay extends \Magento\Payment\Model\Method\AbstractMethod
{

	/**
	 * iPay Payment
	 */
	const METHOD_IPAY = 'ipay';


	/**
	 * Payment code
	 *
	 * @var string
	 */
	protected $_code = self::METHOD_IPAY;

	/**
	 * Availability option
	 *
	 * @var bool
	 */
	protected $_isOffline = false;
	protected $_isGateway                   = true;
	protected $_canCapture                  = true;
	protected $_canCapturePartial           = true;
	protected $_canRefund                   = true;
	protected $_canRefundInvoicePartial     = true;
	protected $_isInitializeNeeded 			= true;

	protected $_order;

	protected $_urlBuilder;

	protected $_orderFactory;

	/**
	 * @var \Magento\Framework\Encryption\EncryptorInterface
	 */
	protected $_encryptor;

	public function __construct(
		\Magento\Framework\Model\Context $context,
		\Magento\Framework\Registry $registry,
		\Magento\Framework\Api\ExtensionAttributesFactory $extensionFactory,
		\Magento\Framework\Api\AttributeValueFactory $customAttributeFactory,
		\Magento\Payment\Helper\Data $paymentData,
		\Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
		\Magento\Payment\Model\Method\Logger $logger,
		\Magento\Framework\Module\ModuleListInterface $moduleList,
		\Magento\Framework\Stdlib\DateTime\TimezoneInterface $localeDate,
		\Magento\Sales\Model\OrderFactory $orderFactory,
		\Magento\Framework\Encryption\EncryptorInterface $encryptor,
		\Magento\Framework\UrlInterface $urlBuilder,
		\Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
		\Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
		array $data = []){
		$this->_orderFactory = $orderFactory;
		$this->_encryptor = $encryptor;
		$this->_urlBuilder = $urlBuilder;
		parent::__construct($context,
			$registry,
			$extensionFactory,
			$customAttributeFactory,
			$paymentData,
			$scopeConfig,
			$logger,
			$resource,
			$resourceCollection,
			$data);
	}

	/**
	 *
	 */
	public function getFormFields($order)
	{
		$fieldsArr = array();
		$shippingAddress			= $order->getShippingAddress();
		$billingAddress				= $order->getBillingAddress();
		$orderId					= $order->getRealOrderId();
		$orderAmount				= (double)$order->getBaseGrandTotal();

		$fieldsArr = array(
			'merchant_key'			=> $this->getMerchantKey(),
			'success_url'			=> $this->getCallbackUrl(),
			'cancelled_url'			=> $this->getCallbackUrl(),
			'deferred_url'			=> $this->getCallbackUrl(),
			'extra_src_currency'	=> $order->getBaseCurrencyCode(),
			'invoice_id'			=> $orderId,
			'total'					=> $orderAmount,
			'description'			=> 'Payment for Order #'.$orderId,
			'extra_email'			=> $billingAddress->getEmail(),
			'extra_name'			=> $billingAddress->getFirstname().' '.$billingAddress->getLastname(),
			'extra_mobile'			=> $billingAddress->getTelephone()
		);

		$debugData = array(
			'request' => $fieldsArr
		);

		return $fieldsArr;
	}

	/**
	 * Get callback url
	 *
	 * @param string $actionName
	 * @return string
	 */
	public function getCallbackUrl()
	{
		return $this->_urlBuilder->getUrl('ipay/checkout/response', ['_secure' => true]);
	}


	public function IPNResponse($incrementId)
	{
		$url = 'https://community.ipaygh.com/v1/gateway/status_chk?invoice_id='.$incrementId.'&merchant_key='.$this->getMerchantKey();
		$ch = curl_init($url);
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, null);
		curl_setopt($ch, CURLOPT_HEADER, false);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		/*if($this->isTestMode()) {
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		}
		else {
			curl_setopt($ch, CURLOPT_SSL_CIPHER_LIST,'TLSv1');
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
			curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
		}*/
		$response = curl_exec($ch);
		curl_close($ch);
		$response = explode("~",$response);
		$result['trans_id']  = (isset($response[0]) && $response[0]) ? $response[0] : '';
		$result['status'] 	 = (isset($response[1]) && $response[1]) ? $response[1] : '';
		$result['timestamp'] = (isset($response[2]) && $response[2]) ? $response[2] : '';

		return $result;
	}

	public function getMerchantKey()
	{
		return $this->_encryptor->decrypt($this->getConfigData('merchant_key'));
	}

	/**
	 * Getter for URL to perform iPay requests, based on test mode by default
	 *
	 * @param bool|null $testMode Ability to specify test mode using
	 * @return string
	 */
	public function getTransactionUrl($testMode = null)
	{
		$testMode = $testMode === null ? $this->getConfigData("test") : (bool)$testMode;
		if ($testMode) {
			return "https://community.ipaygh.com/gateway";
		}
		return "https://community.ipaygh.com/gateway";
	}

	/**
	 * Get initialized flag status
	 * @return true
	 */
	public function isInitializeNeeded()
	{
		return true;
	}

	/**
	 * @param string $paymentAction
	 * @param object $stateObject
	 */
	public function initialize($paymentAction, $stateObject)
	{
		$payment = $this->getInfoInstance();
		$order = $payment->getOrder();
		$order->setCanSendNewEmailFlag(false);
		$order->setIsNotified(false);
		$order->setCustomerNoteNotify(false);
		$stateObject->setState(\Magento\Sales\Model\Order::STATE_PENDING_PAYMENT);
		$stateObject->setStatus(\Magento\Sales\Model\Order::STATE_PENDING_PAYMENT);
		$stateObject->setIsNotified(false);
		$stateObject->setCustomerNoteNotify(false);
	}

	/**
	 * Get config action to process initialization
	 *
	 * @return string
	 */
	public function getConfigPaymentAction()
	{
		$paymentAction = $this->getConfigData('payment_action');
		return empty($paymentAction) ? true : $paymentAction;
	}
}
