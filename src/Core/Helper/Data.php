<?php

namespace Ls\Core\Helper;

use Ls\Core\Model\LSR;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\ObjectManagerInterface;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Class Data
 * @package Ls\Core\Helper
 */
class Data extends AbstractHelper
{
    private $object_manager;
    private $store_manager;

    /**
     * Data constructor.
     * @param Context $context
     * @param ObjectManagerInterface $object_manager
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        Context $context,
        ObjectManagerInterface $object_manager,
        StoreManagerInterface $storeManager
    ) {
        $this->object_manager = $object_manager;
        $this->store_manager = $storeManager;
        parent::__construct($context);
    }

    /**
     * @return bool
     */
    public function enabled()
    {
        $enabled = $this->scopeConfig->getValue(LSR::SC_SERVICE_ENABLE);
        return $enabled === '1' or $enabled === 1;
    }
}
