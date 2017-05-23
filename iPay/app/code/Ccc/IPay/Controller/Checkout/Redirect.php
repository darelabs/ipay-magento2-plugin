<?php
/**
 * Copyright © 2013-2017 Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Ccc\IPay\Controller\Checkout;

/**
 * Class Start
 */
class Redirect extends \Magento\Framework\App\Action\Action 
{
	/**
     * @var \Magento\Checkout\Model\Session
     */
    protected $_checkoutSession;

    /**
     * @var \Magento\Sales\Model\OrderFactory
     */
    protected $_orderFactory;
    
    
    /**
     * @var \Magento\Paypal\Helper\Checkout
     */
    protected $_checkoutHelper;
    
	/**
     * @var \Psr\Log\LoggerInterface
     */
    protected $_logger;

    /**
     * @var \Ccc\IPay\Model\Ipay
     */
    protected $_iPayFactory;
    
    /**
    *  
    */
     
	public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Sales\Model\OrderFactory $orderFactory,
        \Ccc\IPay\Model\Ipay $iPayFactory,
        \Magento\Paypal\Helper\Checkout $checkoutHelper, 
        \Psr\Log\LoggerInterface $logger
    ) {
    	$this->_checkoutSession = $checkoutSession;
        $this->_orderFactory = $orderFactory;
        $this->_logger = $logger;
        $this->_iPayFactory = $iPayFactory;
        $this->_checkoutHelper = $checkoutHelper;
        parent::__construct($context);
    }
	/**
     * Submit transaction to Payflow getaway into iframe
     *
     * @return void
     */
    public function execute()
    {
    	if (!$this->_objectManager->get('Magento\Checkout\Model\Session\SuccessValidator')->isValid()) {
            return $this->resultRedirectFactory->create()->setPath('checkout/cart');
        }
        if($this->_checkoutSession->getLastRealOrderId()) {
    		$order = $this->_orderFactory->create()->loadByIncrementId($this->_checkoutSession->getLastRealOrderId()); 
    		if ($order->getIncrementId()) {
				$message = __("Customer was redirected to iPay Payment Gateway.");
				$order->addStatusHistoryComment($message, $order->getStatus())->setIsCustomerNotified(false)->save();
			}
    	}
    	$this->_view->loadLayout(false)->renderLayout();
    }
    
    /**
     * Get frontend checkout session object
     *
     * @return \Magento\Checkout\Model\Session
     */
    protected function _getCheckout()
    {
        return $this->_checkoutSession;
    }
}
