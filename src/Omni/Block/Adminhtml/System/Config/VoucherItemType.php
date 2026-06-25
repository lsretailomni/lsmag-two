<?php
declare(strict_types=1);

namespace Ls\Omni\Block\Adminhtml\System\Config;

use Magento\Framework\View\Element\Html\Select;

class VoucherItemType extends Select
{
    public function setInputName($value)
    {
        return $this->setName($value);
    }

    public function setInputId($value)
    {
        return $this->setId($value);
    }

    public function _toHtml(): string
    {
        if (!$this->getOptions()) {
            $this->setOptions([
                ['label' => __('Item'), 'value' => 'item'],
                ['label' => __('Income/Account'), 'value' => 'income_account'],
            ]);
        }
        return parent::_toHtml();
    }
}

