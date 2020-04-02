<?php
namespace Ls\Replication\Model\System\Source;

use Magento\Framework\Option\ArrayInterface;

/**
 * Class MagStore
 * @package Ls\Replication\Model\System\Source
 */
class MagStore implements ArrayInterface
{

    /** @var \Magento\Store\Model\StoreManagerInterface */
    protected $storeManager;


    public function __construct(
        \Magento\Store\Model\StoreManagerInterface $storeManager
    ) {
        $this->storeManager=$storeManager;
    }

    /**
     * @return array
     */
    public function toOptionArray()
    {

        foreach ($this->getStores() as $mag_store) {
            $option_array[] = [ 'value' => $mag_store->getId(), 'label' => $mag_store->getName() ];
        }

        return $option_array;
    }

    /**
     * @return mixed
     */
    public function toArray()
    {
        foreach ($this->getStores() as $mag_store) {
            $option_array[ $mag_store->getId() ] = $mag_store->getName();
        }

        return $option_array;
    }

    /**
     * @return \Magento\Store\Api\Data\StoreInterface[]
     */
    protected function getStores()
    {
        $websites = $this->storeManager->getStores();
        return $websites;
    }
}
