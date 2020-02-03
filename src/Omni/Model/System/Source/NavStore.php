<?php

namespace Ls\Omni\Model\System\Source;

use Ls\Core\Model\LSR;
use Ls\Omni\Client\Ecommerce\Entity\Store;
use Ls\Omni\Client\Ecommerce\Operation\StoresGetAll;
use Magento\Framework\Data\OptionSourceInterface;

/**
 * Class NavStore
 * @package Ls\Omni\Model\System\Source
 */
class NavStore implements OptionSourceInterface
{
    /**
     * @var LSR
     */
    public $lsr;

    /**
     * NavStore constructor.
     * @param LSR $lsr
     */
    public function __construct(LSR $lsr)
    {
        $this->lsr = $lsr;
    }

    public function toOptionArray()
    {
        $option_array = [['value' => '', 'label' => __('Please select your web store')]];
        if (!empty($this->getNavStores())) {
            foreach ($this->getNavStores() as $nav_store) {
                $option_array[] = ['value' => $nav_store->getId(), 'label' => $nav_store->getDescription()];
            }
        }
        return $option_array;
    }

    /**
     * @return Store[]
     */
    public function getNavStores()
    {
        if ($this->lsr->validateBaseUrl()) {
            $baseUrl = $this->lsr->getStoreConfig(LSR::SC_SERVICE_BASE_URL);
            if (!empty($baseUrl)) {
                // @codingStandardsIgnoreLine
                $get_nav_stores = new StoresGetAll();
                $result         = $get_nav_stores->execute();

                if ($result != null) {
                    $result = $result->getResult();
                }
                if (!is_array($result)) {
                    return $resultArray[] = $result;
                } else {
                    return $result;
                }
            }
        }
        return [];
    }
}
