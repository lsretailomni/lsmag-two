<?php

namespace Ls\Omni\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Store\Model\StoreManagerInterface;
use Ls\Replication\Api\ReplStoreRepositoryInterface;

class Data extends AbstractHelper
{
    /** @var StoreManagerInterface  */
    protected $_storeManager;

    /** @var \Magento\Framework\App\Config\ScopeConfigInterface  */
    protected $_config;

    /** @var ReplStoreRepositoryInterface  */
    protected $storeRepository;

    /**
     * Data constructor.
     * @param Context $context
     * @param StoreManagerInterface $store_manager
     * @param ReplStoreRepositoryInterface $storeRepository
     */

    public function __construct(Context $context,
                                StoreManagerInterface $store_manager,
                                ReplStoreRepositoryInterface $storeRepository
    )
    {
        $this->_storeManager = $store_manager;
        $this->storeRepository = $storeRepository;
        $this->_config = $context->getScopeConfig();
        parent::__construct($context);
    }


    /**
     * @param $storeId
     * @return mixed
     */
    public function getStoreNameById($storeId)
    {
        $store = $this->storeRepository->getById($storeId);
        if ($store->getId()) {
            return $store->getName();
        }
    }
}
