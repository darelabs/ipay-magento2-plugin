<?php
/**
 * Copyright © 2013-2017 Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Ccc\IPay\Model;

use Magento\Framework\Locale\Bundle\DataBundle;
use Magento\Payment\Model\Method\AbstractMethod;
use Magento\Store\Model\ScopeInterface;

/**
 * Payment configuration model
 *
 * Used for retrieving configuration data by payment models
 */
class Config
{
	/**
	 * Cross-models public exchange keys
	 *
	 * @var string
	 */
	const MERCHANT_ID          = 'MerchantId';
	const STATUS_ID            = 'StatusId';
	const TXN_TIMESTAMP        = 'Timestamp';

	/**
	 * All payment information map
	 *
	 * @var array
	 */
	protected $_paymentMap = array(
		self::MERCHANT_ID          => 'MerchantId',
		self::STATUS_ID            => 'StatusId',
		self::TXN_TIMESTAMP        => 'Timestamp',
	);

	/**
	 * iPay payment status possible values
	 *
	 * @var string
	 */
	const PAYMENT_STATUS_NONE         = 'none';
	const PAYMENT_STATUS_PAID         = 'paid';
	const PAYMENT_STATUS_CANCELLED    = 'cancelled';
	const PAYMENT_STATUS_EXPIRED      = 'expired';
	const PAYMENT_STATUS_REVIEWED     = 'reviewed';
	const PAYMENT_STATUS_AWAITING     = 'awaiting_payment';

	
    
    /**
     * @var array
     */
    protected $_methods;

    /**
     * Core store config
     *
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $_scopeConfig;

    /**
     * @var \Magento\Framework\Config\DataInterface
     */
    protected $_dataStorage;

    /**
     * Locale model
     *
     * @var \Magento\Framework\Locale\ResolverInterface
     */
    protected $localeResolver;

    /**
     * Payment method factory
     *
     * @var \Magento\Payment\Model\Method\Factory
     */
    protected $_paymentMethodFactory;

    /**
     * DateTime
     *
     * @var \Magento\Framework\Stdlib\DateTime\DateTime
     */
    protected $_date;

    /**
     * Construct
     *
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Payment\Model\Method\Factory $paymentMethodFactory
     * @param \Magento\Framework\Locale\ResolverInterface $localeResolver
     * @param \Magento\Framework\Config\DataInterface $dataStorage
     * @param \Magento\Framework\Stdlib\DateTime\DateTime $date
     */
    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Payment\Model\Method\Factory $paymentMethodFactory,
        \Magento\Framework\Locale\ResolverInterface $localeResolver,
        \Magento\Framework\Config\DataInterface $dataStorage,
        \Magento\Framework\Stdlib\DateTime\DateTime $date
    ) {
        $this->_scopeConfig = $scopeConfig;
        $this->_dataStorage = $dataStorage;
        $this->_paymentMethodFactory = $paymentMethodFactory;
        $this->localeResolver = $localeResolver;
        $this->_date = $date;
    }

    
    /**
     * Retrieve array of payment methods information
     *
     * @return array
     * @api
     */
    public function getMethodsInfo()
    {
        return $this->_dataStorage->get('methods');
    }

    /**
     * Get payment groups
     *
     * @return array
     * @api
     */
    public function getGroups()
    {
        return $this->_dataStorage->get('groups');
    }
    
    /**
	 * Explain pending payment reason code
	 *
	 * @param string $code
	 * @return string
	 */
	public static function explainPendingReason($code)
	{
		switch ($code) {
			case 'address':
				return __('Customer did not include a confirmed address.');
			case 'authorization':
			case 'order':
				return __('The payment is authorized but not settled.');
			case 'echeck':
				return __('The payment eCheck is not yet cleared.');
			case 'intl':
				return __('Merchant holds a non-U.S. account and does not have a withdrawal mechanism.');
			case 'multi-currency': // break is intentionally omitted
			case 'multi_currency': // break is intentionally omitted
			case 'multicurrency':
				return __('The payment curency does not match any of the merchant\'s balances currency.');
			case 'paymentreview':
				return __('The payment is pending while it is being reviewed by iPay for risk.');
			case 'unilateral':
				return __('The payment is pending because it was made to an email address that is not yet registered or confirmed.');
			case 'verify':
				return __('The merchant account is not yet verified.');
			case 'upgrade':
				return __('The payment was made via credit card. In order to receive funds merchant must upgrade account to Business or Premier status.');
			case 'none': // break is intentionally omitted
			case 'other': // break is intentionally omitted
			default:
				return __('Unknown reason. Please contact iPay customer service.');
		}
	}

	/**
	 * Explain the refund or chargeback reason code
	 *
	 * @param $code
	 * @return string
	 */
	public static function explainReasonCode($code)
	{
		switch ($code) {
			case 'chargeback':
				return __('Chargeback by customer.');
			case 'guarantee':
				return __('Customer triggered a money-back guarantee.');
			case 'buyer-complaint':
				return __('Customer complaint.');
			case 'refund':
				return __('Refund issued by merchant.');
			case 'adjustment_reversal':
				return __('Reversal of an adjustment.');
			case 'chargeback_reimbursement':
				return __('Reimbursement for a chargeback.');
			case 'chargeback_settlement':
				return __('Settlement of a chargeback.');
			case 'none': // break is intentionally omitted
			case 'other':
			default:
				return __('Unknown reason. Please contact iPay customer service.');
		}
	}

	/**
	 * Whether a reversal/refund can be disputed with iPay
	 *
	 * @param string $code
	 * @return bool;
	 */
	public static function isReversalDisputable($code)
	{
		switch ($code) {
			case 'none':
			case 'other':
			case 'chargeback':
			case 'buyer-complaint':
			case 'adjustment_reversal':
				return true;
			case 'guarantee':
			case 'refund':
			case 'chargeback_reimbursement':
			case 'chargeback_settlement':
			default:
				return false;
		}
	}
}
