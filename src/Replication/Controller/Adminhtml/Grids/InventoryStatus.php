<?php
declare(strict_types=1);

namespace Ls\Replication\Controller\Adminhtml\Grids;

use Magento\Framework\Phrase;

class InventoryStatus extends AbstractGrid
{
    /**
     * Get title
     *
     * @return Phrase
     */
    public function getTitle()
    {
        return __('Inventory Status Replication');
    }
}
