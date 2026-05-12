<?php
declare(strict_types=1);

namespace Ls\Omni\Block\Html;

use Magento\Framework\Exception\NoSuchEntityException;
use \Ls\Core\Model\LSR;
use Magento\Framework\View\Element\Template\Context;

class Link extends \Magento\Framework\View\Element\Html\Link
{
    /**
     * @var LSR
     */
    public $lsr;

    /**
     * @param Context $context
     * @param LSR $lsr
     * @param array $data
     */
    public function __construct(
        Context $context,
        LSR $lsr,
        array $data = []
    ) {
        $this->lsr = $lsr;
        parent::__construct($context, $data);
    }

    /**
     * Check if we need to render block or not
     *
     * @return string
     * @throws NoSuchEntityException
     */
    public function _toHtml()
    {
        if ($this->lsr->isLSR($this->lsr->getCurrentStoreId())) {
            return parent::_toHtml();
        }

        return '';
    }
}
