<?php
declare(strict_types=1);

namespace Ls\Replication\Model;

use Magento\Framework\Data\OptionSourceInterface;
use Magento\Store\Model\System\Store as StoreManager;

class Websites implements OptionSourceInterface
{
    /**
     * @param StoreManager $storeManager
     */
    public function __construct(public StoreManager $storeManager)
    {
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
