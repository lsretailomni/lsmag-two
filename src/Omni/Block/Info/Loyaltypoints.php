<?php

namespace Ls\Omni\Block\Info;

use Magento\Payment\Block\Info;

/**
 * Class Loyaltypoints
 * @package Ls\Omni\Block\Info
 */
class Loyaltypoints extends Info
{

    /**
     * @var string
     */
    public $payableTo;

    /**
     * @var string
     */
    public $mailingAddress;

    /**
     * @var string
     */
    // @codingStandardsIgnoreLine
    public $_template = 'Ls_Omni::info/loyaltypoints.phtml';

    /**
     * @return string
     */
    public function toPdf()
    {
        $this->setTemplate('Ls_Omni::info/pdf/loyaltypoints.phtml');
        return $this->toHtml();
    }
}
