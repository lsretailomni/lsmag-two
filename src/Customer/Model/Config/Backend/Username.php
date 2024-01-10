<?php

namespace Ls\Customer\Model\Config\Backend;

use Magento\Framework\App\Config\Value;
use Magento\Framework\Exception\ValidatorException;

/**
 * Class Username
 * Backend model for new customer username
 * username
 */
class Username extends Value
{
    /**
     * @return Username|void
     * @throws ValidatorException
     */
    public function beforeSave()
    {
        $label = $this->getData('field_config/label');
        if (!preg_match("/^[a-zA-Z0-9-_@.]*$/", $this->getValue())) {
            throw new ValidatorException(
                __('Enter a valid %1. Valid characters are A-Z a-z 0-9 . _ - @', $label)
            );
        } elseif (strlen($this->getValue()) < 3 || strlen($this->getValue()) > 5) {
            throw new ValidatorException(
                __('Enter a valid %1. It can only have a min of length 3 and a max of length 5', $label)
            );
        }
        $this->setValue($this->getValue());
        parent::beforeSave();
    }
}
