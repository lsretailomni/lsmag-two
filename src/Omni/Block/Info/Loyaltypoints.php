<?php
declare(strict_types=1);

namespace Ls\Omni\Block\Info;

use Magento\Payment\Block\Info;

class Loyaltypoints extends Info
{
    /**
     * @var string
     */
    // @codingStandardsIgnoreLine
    public $_template = 'Ls_Omni::info/loyaltypoints.phtml';

    /**
     * Get html for pdf
     *
     * @return string
     */
    public function toPdf()
    {
        $this->setTemplate('Ls_Omni::info/pdf/loyaltypoints.phtml');
        return $this->toHtml();
    }
}
