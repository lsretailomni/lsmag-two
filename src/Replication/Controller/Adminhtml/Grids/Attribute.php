<?php
declare(strict_types=1);

namespace Ls\Replication\Controller\Adminhtml\Grids;

use Magento\Framework\Phrase;

class Attribute extends AbstractGrid
{
    /**
     * Get title
     *
     * @return Phrase
     */
    public function getTitle()
    {
        return __('Attribute Replication');
    }
}
