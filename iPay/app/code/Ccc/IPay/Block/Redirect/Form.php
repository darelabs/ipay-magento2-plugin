<?php
/**
 * Copyright © 2013-2017 Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Ccc\IPay\Block\Redirect;

use Magento\Customer\Helper\Session\CurrentCustomer;
use Magento\Framework\Locale\ResolverInterface;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Magento\Payment\Helper\Data;
use Magento\Sales\Model\OrderFactory;
use Magento\Checkout\Model\Session;
use Magento\Paypal\Model\Config;
use Magento\Paypal\Model\ConfigFactory;
use Magento\Paypal\Model\Express\Checkout;

class Form extends \Magento\Payment\Block\Form
{
	
    /**
     * Order object
     *
     * @var \Magento\Sales\Model\Order
     */
    protected $_order;
    
    /**
     * Paypal data
     *
     * @var Data
     */
    protected $_paymentData;

    /**
     * @var OrderFactory
     */
    protected $_orderFactory;

    /**
     * @var ResolverInterface
     */
    protected $_localeResolver;

    /**
     * @var null
     */
    protected $_config;

    /**
     * @var bool
     */
    protected $_isScopePrivate;

    /**
     * @var CurrentCustomer
     */
    protected $currentCustomer;
    
    /**
     * @var \Magento\Checkout\Model\Session
     */
     
     protected $_checkoutSession;

    /**
     * @param Context $context
     * @param ConfigFactory $paypalConfigFactory
     * @param ResolverInterface $localeResolver
     * @param Data $paymentData
     * @param CurrentCustomer $currentCustomer
     * @param array $data
     */
    public function __construct(
        Context $context,
        OrderFactory $orderFactory,
        ResolverInterface $localeResolver,
        Data $paymentData,
        Session $checkoutSession,
        CurrentCustomer $currentCustomer,
        array $data = []
    ) {
        $this->_paymentData = $paymentData;
        $this->_orderFactory = $orderFactory;
        $this->_checkoutSession = $checkoutSession;
        $this->_localeResolver = $localeResolver;
        $this->_config = null;
        $this->_isScopePrivate = true;
        $this->currentCustomer = $currentCustomer;
        parent::__construct($context, $data);
    }
    
        /**
     * Set payment method code
     *
     * @return void
     */
    protected function _construct()
    {
        parent::_construct();
        $this->_paymentMethodCode = \Ccc\IPay\Model\Ipay::METHOD_IPAY;
    }
    
    public function requestFields()
    {
    	$this->_order = $this-> _getOrder();
    	return $this->_paymentData->getMethodInstance($this->_paymentMethodCode)->getFormFields($this->_order);
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

    /**
     * Get order object
     *
     * @return \Magento\Sales\Model\Order
     */
    protected function _getOrder()
    {
        if (!$this->_order) {
            $incrementId = $this->_getCheckout()->getLastRealOrderId();
            $this->_order = $this->_orderFactory->create()->loadByIncrementId($incrementId);
        }
        return $this->_order;
    }
    
    /**
     * Get frame action URL
     *
     * @return string
     */
    public function getFrameActionUrl()
    {
        return $this->getUrl('paypal/payflow/form', ['_secure' => true]);
    }
    
    /**
     * Get payflow transaction URL
     *
     * @return string
     */
    public function getTransactionUrl()
    {
    	return $this->_paymentData->getMethodInstance($this->_paymentMethodCode)->getTransactionUrl();
    }
}