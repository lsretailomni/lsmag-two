<?php

namespace Ls\Core\Block;

use Magento\Framework\View\Element\Template\Context;
use Ls\Core\Model\LSR;

class InvalidNotice extends \Magento\Framework\View\Element\Template
{
    /**
     * @var LSR
     */
    protected $lsr;

    /**
     * @param Context $context
     * @param array $data
     */
    public function __construct(Context $context, LSR $lsr, array $data = [])
    {
        $this->lsr = $lsr;
        parent::__construct($context, $data);
    }

    /**
     * @return \Magento\Framework\Phrase
     */
    public function displayNotice()
    {
        if (!$this->lsr->isLSR()) {

            return $this->lsr->getInvalidMessageContainer();
        }
    }
}
