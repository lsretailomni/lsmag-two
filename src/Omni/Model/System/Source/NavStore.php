<?php
namespace Ls\Omni\Model\System\Source;

use Ls\Omni\Client\Ecommerce\Entity\Store;
use Ls\Omni\Client\Ecommerce\Operation\StoresGetAll;
use Magento\Framework\Option\ArrayInterface;

class NavStore implements ArrayInterface
{
    /**
     * @return array
     */
    public function toOptionArray()
    {
        $option_array = [ [ 'value' => '', 'label' => __('Select One') ] ];

        foreach ($this->getNavStores() as $nav_store) {
            $option_array[] = [ 'value' => $nav_store->getId(), 'label' => $nav_store->getDescription() ];
        }

        return $option_array;
    }

    /**
     * @return array
     */
    public function toArray()
    {
        $option_array = [
            '' => __('Select One')
        ];
        foreach ($this->getNavStores() as $nav_store) {
            $option_array[ $nav_store->getId() ] = $nav_store->getDescription();
        }

        return $option_array;
    }

    /**
     * @return Store[]
     */
    protected function getNavStores()
    {

        $get_nav_stores = new StoresGetAll();
        $result = $get_nav_stores->execute()
                                ->getResult();
        if (is_null($result)) {
            return [ ];
        } else {
            return $result->getIterator();
        }
    }
}
