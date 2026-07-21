<?php

namespace Ls\Customer\Plugin\Customer\Block\Widget;

use Ls\Customer\Model\HyvaThemeResolver;

class Name
{
    /**
     * @param HyvaThemeResolver $hyvaThemeResolver
     */
    public function __construct(
        private HyvaThemeResolver $hyvaThemeResolver
    )
    {}

    public function beforeToHtml(\Magento\Customer\Block\Widget\Name $subject)
    {
        $template = $this->hyvaThemeResolver->isHyva()
            ? 'Ls_Customer::widget/hyva/name.phtml'
            : 'Ls_Customer::widget/name.phtml';

        $subject->setTemplate($template);
    }
}
