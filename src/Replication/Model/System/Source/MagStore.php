<?php

namespace Ls\Replication\Model\System\Source;

use Magento\Framework\Data\OptionSourceInterface;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Class MagStore
 * @package Ls\Replication\Model\System\Source
 */
class MagStore implements OptionSourceInterface
{

    /** @var StoreManagerInterface */
    protected $storeManager;


    public function __construct(
        StoreManagerInterface $storeManager
    ) {
        $this->storeManager = $storeManager;
    }

    /**
     * @return array
     */
    public function toOptionArray()
    {
        $optionArray[] = '';
        foreach ($this->getStores() as $magStore) {
            $optionArray[] = ['value' => $magStore->getId(), 'label' => $magStore->getName()];
        }

        return $optionArray;
    }

    /**
     * @return mixed
     */
    public function toArray()
    {
        $optionArray[] = '';
        foreach ($this->getStores() as $magStore) {
            $optionArray[$magStore->getId()] = $magStore->getName();
        }

        return $optionArray;
    }

    /**
     * @return \Magento\Store\Api\Data\StoreInterface[]
     */
    protected function getStores()
    {
        return $this->storeManager->getStores();
    }
}
