<?php

namespace Ls\Omni\Helper;

use Exception;
use \Ls\Core\Model\LSR;
use \Ls\Omni\Client\Ecommerce\Entity;
use \Ls\Omni\Client\Ecommerce\Operation;
use Magento\Checkout\Model\Session\Proxy;
use Magento\Customer\Model\Session\Proxy as CustomerProxy;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Model\CustomerFactory;
use Magento\Framework\Api\FilterBuilder;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\Filesystem;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Class GiftCardHelper
 * @package Ls\Omni\Helper
 */
class GiftCardHelper extends AbstractHelper
{

    const SERVICE_TYPE = 'ecommerce';

    /** @var FilterBuilder */
    public $filterBuilder;

    /** @var SearchCriteriaBuilder */
    public $searchCriteriaBuilder;

    /** @var StoreManagerInterface */
    public $storeManager;

    /** @var CustomerRepositoryInterface */
    public $customerRepository;

    /** @var CustomerFactory */
    public $customerFactory;

    /**
     * @var \Magento\Customer\Model\Session\Proxy
     */
    public $customerSession;

    /** @var null */
    public $ns = null;

    /** @var Filesystem */
    public $filesystem;

    /**
     * @var $checkoutSession
     */
    public $checkoutSession;

    /** @var  LSR $lsr */
    public $lsr;

    /**
     * GiftCardHelper constructor.
     * @param Context $context
     * @param FilterBuilder $filterBuilder
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param CustomerRepositoryInterface $customerRepository
     * @param StoreManagerInterface $storeManager
     * @param CustomerFactory $customerFactory
     * @param CustomerProxy $customerSession
     * @param Proxy $checkoutSession
     * @param Filesystem $Filesystem
     * @param LSR $Lsr
     */
    public function __construct(
        Context $context,
        FilterBuilder $filterBuilder,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        CustomerRepositoryInterface $customerRepository,
        StoreManagerInterface $storeManager,
        CustomerFactory $customerFactory,
        CustomerProxy $customerSession,
        Proxy $checkoutSession,
        Filesystem $Filesystem,
        LSR $Lsr
    ) {
        $this->filterBuilder         = $filterBuilder;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->storeManager          = $storeManager;
        $this->customerRepository    = $customerRepository;
        $this->customerFactory       = $customerFactory;
        $this->customerSession       = $customerSession;
        $this->checkoutSession       = $checkoutSession;
        $this->filesystem            = $Filesystem;
        $this->lsr                   = $Lsr;

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
        $entity  = new Entity\GiftCardGetBalance();
        $entity->setCardNo($giftCardNo);
        // @codingStandardsIgnoreEnd
        try {
            $responseData = $request->execute($entity);
            $response     = $responseData ? $responseData->getResult() : $response;
        } catch (Exception $e) {
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
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function isGiftCardEnableOnCartPage()
    {
        return $this->lsr->getStoreConfig(
            LSR::LS_GIFTCARD_SHOW_ON_CART,
            $this->lsr->getCurrentStoreId()
        );
    }

    /**
     * @return string
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function isGiftCardEnableOnCheckOut()
    {
        return $this->lsr->getStoreConfig(
            LSR::LS_GIFTCARD_SHOW_ON_CHECKOUT,
            $this->lsr->getCurrentStoreId()
        );
    }
}
