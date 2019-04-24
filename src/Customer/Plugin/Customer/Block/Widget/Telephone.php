<?php

namespace Ls\Customer\Plugin\Customer\Block\Widget;

class Telephone
{
    public function beforeToHtml(\Magento\Customer\Block\Widget\Telephone $subject)
    {
        $subject->setTemplate('Ls_Customer::widget/telephone.phtml');
    }
}