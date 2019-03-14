<?php

namespace Ls\Omni\Model\System\Source;

use \Ls\Core\Model\LSR;
use \Ls\Omni\Client\Ecommerce\Entity\Store;
use \Ls\Omni\Client\Ecommerce\Operation\StoresGetAll;
use Magento\Framework\Option\ArrayInterface;


/**
 * Class NavStore
 * @package Ls\Omni\Model\System\Source
 */
class NavStore implements ArrayInterface
{
    /**
     * @return array
     */
    /**
     * @var Ls\Core\Model\LSR
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
        $option_array = [['value' => '', 'label' => __('Select One')]];
        if (!empty($this->getNavStores())) {
            foreach ($this->getNavStores() as $nav_store) {
                $option_array[] = ['value' => $nav_store->getId(), 'label' => $nav_store->getDescription()];
            }
        }

        return $option_array;
    }

    /**
     * @return array
     */
    public function toArray()
    {
        $option_array = [
            '' => __('Select One'),
        ];
        if (!empty($this->getNavStores())) {
            foreach ($this->getNavStores() as $nav_store) {
                $option_array[$nav_store->getId()] = $nav_store->getDescription();
            }
        }

        return $option_array;
    }

    /**
     * @return Store[]
     */
    public function getNavStores()
    {
        $baseUrl = $this->lsr->getStoreConfig(LSR::SC_SERVICE_BASE_URL);
        if (!empty($baseUrl)) {
            // @codingStandardsIgnoreLine
            $get_nav_stores = new StoresGetAll();
            $result = $get_nav_stores->execute();

            if ($result != null) {
                $result = $result->getResult();
            }

            if ($result == null) {
                return [];
            } else {
                return $result->getIterator();
            }
        } else {
            return null;
        }
    }
}
