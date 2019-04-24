<?php

namespace Ls\Customer\Plugin\Customer\Block\Widget;

class Name
{
    public function beforeToHtml(\Magento\Customer\Block\Widget\Name $subject)
    {
        $subject->setTemplate('Ls_Customer::widget/name.phtml');
    }
}