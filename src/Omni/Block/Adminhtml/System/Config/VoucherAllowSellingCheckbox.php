<?php
declare(strict_types=1);

namespace Ls\Omni\Block\Adminhtml\System\Config;

use Magento\Framework\View\Element\AbstractBlock;

/**
 * Checkbox renderer for Allow Selling column in VoucherGiftCardConfig dynamic rows
 */
class VoucherAllowSellingCheckbox extends AbstractBlock
{
    /**
     * @var string
     */
    protected $inputName;

    /**
     * @var string
     */
    protected $inputId;

    /**
     * @var string
     */
    protected $value;

    /**
     * @param string $value
     * @return $this
     */
    public function setInputName(string $value): self
    {
        $this->inputName = $value;
        return $this;
    }

    /**
     * @param string $value
     * @return $this
     */
    public function setInputId(string $value): self
    {
        $this->inputId = $value;
        return $this;
    }

    /**
     * @param string $value
     * @return $this
     */
    public function setValue(string $value): self
    {
        $this->value = $value;
        return $this;
    }

    /**
     * @return string
     */
    public function getValue(): string
    {
        return (string)$this->value;
    }

    /**
     * Required by AbstractFieldArray for option hash comparison (no-op for checkbox)
     *
     * @param mixed $optionValue
     * @return string
     */
    public function calcOptionHash($optionValue): string
    {
        return hash('sha256', $this->inputName . $this->inputId . $optionValue);
    }

    /**
     * @return string
     */
    public function _toHtml(): string
    {
        $checked = ($this->value == '1') ? 'checked="checked"' : '';
        $name    = htmlspecialchars((string)$this->inputName);
        $id      = htmlspecialchars((string)$this->inputId);

        // Hidden field ensures "0" is submitted when unchecked
        return sprintf(
            '<input type="hidden" name="%1$s" value="0" />'
            . '<input type="checkbox" id="%2$s" name="%1$s" value="1" %3$s'
            . ' class="admin__control-checkbox" style="margin: 0 auto; display: block;" />',
            $name,
            $id,
            $checked
        );
    }
}

