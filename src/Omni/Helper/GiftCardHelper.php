<?php

namespace Ls\Omni\Helper;

use \Ls\Omni\Client\Ecommerce\Entity;
use \Ls\Omni\Client\Ecommerce\Operation;
use \Ls\Core\Model\LSR;
use Magento\Framework\App\Filesystem\DirectoryList;

/**
 * Class GiftCardHelper
 * @package Ls\Omni\Helper
 */
class GiftCardHelper extends \Magento\Framework\App\Helper\AbstractHelper
{

    const SERVICE_TYPE = 'ecommerce';

    /** @var \Magento\Framework\Api\FilterBuilder */
    public $filterBuilder;

    /** @var \Magento\Framework\Api\SearchCriteriaBuilder */
    public $searchCriteriaBuilder;

    /** @var \Magento\Store\Model\StoreManagerInterface */
    public $storeManager;

    /** @var \Magento\Customer\Api\CustomerRepositoryInterface */
    public $customerRepository;

    /** @var \Magento\Customer\Model\CustomerFactory */
    public $customerFactory;

    /**
     * @var \Magento\Customer\Model\Session\Proxy
     */
    public $customerSession;

    /** @var null */
    public $ns = null;

    /** @var \Magento\Framework\Filesystem */
    public $filesystem;

    /**
     * @var $checkoutSession
     */
    public $checkoutSession;

    /** @var  LSR $lsr */
    public $lsr;

    /**
     * LoyaltyHelper constructor.
     * @param \Magento\Framework\App\Helper\Context $context
     * @param \Magento\Framework\Api\FilterBuilder $filterBuilder
     * @param \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder
     * @param \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Customer\Model\CustomerFactory $customerFactory
     * @param \Magento\Customer\Model\Session\Proxy $customerSession
     * @param \Magento\Checkout\Model\Session\Proxy $checkoutSession
     * @param \Magento\Framework\Filesystem $Filesystem
     */

    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Framework\Api\FilterBuilder $filterBuilder,
        \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder,
        \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Customer\Model\CustomerFactory $customerFactory,
        \Magento\Customer\Model\Session\Proxy $customerSession,
        \Magento\Checkout\Model\Session\Proxy $checkoutSession,
        \Magento\Framework\Filesystem $Filesystem,
        LSR $Lsr
    )
    {
        $this->filterBuilder = $filterBuilder;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->storeManager = $storeManager;
        $this->customerRepository = $customerRepository;
        $this->customerFactory = $customerFactory;
        $this->customerSession = $customerSession;
        $this->checkoutSession = $checkoutSession;
        $this->filesystem = $Filesystem;
        $this->lsr = $Lsr;

        parent::__construct(
            $context
        );
    }

    /**
     * @param $giftCardNo
     * @return float|Entity\GiftCard|null
     */
    public function getGiftCardBalance($giftCardNo)
    {
        $response = null;
        // @codingStandardsIgnoreStart
        $request = new Operation\GiftCardGetBalance();
        $entity = new Entity\GiftCardGetBalance();
        $entity->setCardNo($giftCardNo);
        // @codingStandardsIgnoreEnd
        try {
            $responseData = $request->execute($entity);
            $response = $responseData ? $responseData->getResult() : $response;
        } catch (\Exception $e) {
            $this->_logger->error($e->getMessage());
        }
        $this->checkoutSession->setGiftCard($response);
        return $response;
    }

    /**
     * @param $grandTotal
     * @param $giftCardAmount
     * @param $giftCardBalanceAmount
     * @return bool
     */
    public function isGiftCardAmountValid($grandTotal, $giftCardAmount, $giftCardBalanceAmount)
    {
        if ($giftCardAmount <= $grandTotal && $giftCardAmount <= $giftCardBalanceAmount) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * @return string
     */

    public function isGiftCardEnable()
    {
        return $this->lsr->getStoreConfig(\Ls\Core\Model\LSR::LS_GIFTCARD_ACTIVE);
    }

    /**
     * @return string
     */

    public function isGiftCardEnableOnCartPage()
    {
        return $this->lsr->getStoreConfig(\Ls\Core\Model\LSR::LS_GIFTCARD_SHOW_ON_CART);
    }

    /**
     * @return string
     */
    public function isGiftCardEnableOnCheckOut()
    {
        return $this->lsr->getStoreConfig(\Ls\Core\Model\LSR::LS_GIFTCARD_SHOW_ON_CHECKOUT);
    }

}
