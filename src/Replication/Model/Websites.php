<?php

namespace Ls\Replication\Model;

use Magento\Framework\Data\OptionSourceInterface;
use Magento\Store\Model\System\Store as StoreManager;

class Websites implements OptionSourceInterface
{
    /**
     * @var StoreManager
     */
    public $storeManager;

    /**
     * @param StoreManager $storeManager
     */
    public function __construct(StoreManager $storeManager)
    {
        $this->storeManager = $storeManager;
    }

    /**
     * @return array
     */
    public function toOptionArray()
    {
        $options = [];

        foreach ($this->storeManager->getWebsiteCollection() as $website) {
            $options[] = ['label' => $website->getName(), 'value' => $website->getId()];
        }

        return $options;
    }
}
