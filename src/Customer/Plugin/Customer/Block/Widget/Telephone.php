<?php

namespace Ls\Customer\Plugin\Customer\Block\Widget;

use Ls\Customer\Model\HyvaThemeResolver;

class Telephone
{
    /**
     * @param HyvaThemeResolver $hyvaThemeResolver
     */
    public function __construct(
        private HyvaThemeResolver $hyvaThemeResolver
    )
    {}

    public function beforeToHtml(\Magento\Customer\Block\Widget\Telephone $subject)
    {
        $template = $this->hyvaThemeResolver->isHyva()
            ? 'Ls_Customer::widget/hyva/telephone.phtml'
            : 'Ls_Customer::widget/telephone.phtml';

        $subject->setTemplate($template);
    }
}
