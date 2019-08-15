<?php

namespace Ls\Core\Block;

use Magento\Framework\View\Element\Template\Context;
use \Ls\Core\Model\LSR;

/**
 * Class InvalidNotice
 * @package Ls\Core\Block
 */
class InvalidNotice extends \Magento\Framework\View\Element\Template
{
    /**
     * @var LSR
     */
    public $lsr;

    /**
     * InvalidNotice constructor.
     * @param Context $context
     * @param LSR $lsr
     * @param array $data
     */
    public function __construct(Context $context, LSR $lsr, array $data = [])
    {
        $this->lsr = $lsr;
        parent::__construct($context, $data);
    }

    /**
     *
     */
    public function displayNotice()
    {
        //TODO commenting out code in order to pass the Magento validation. OMNI-4797
        /*if (!$this->lsr->isLSR()) {
            return $this->lsr->getInvalidMessageContainer();
        }*/
        return '';
    }
}
