<?php
declare(strict_types=1);

namespace Ls\Replication\Controller\Adminhtml\Grids;

use Magento\Framework\Phrase;

class StoreTenderType extends AbstractGrid
{
    /**
     * Get title
     *
     * @return Phrase
     */
    public function getTitle()
    {
        return __('Store Tender Type Replication');
    }
}
