<?php
declare(strict_types=1);

namespace Ls\Omni\Block\Product\View\VendorSourcing;

use \Ls\Omni\Helper\VendorSourcingHelper;
use Magento\Framework\Phrase;
use Magento\Framework\View\Element\Template;

/**
 * Presents the vendor sourcing options in the popup
 */
class Options extends Template
{
    /**
     * Get the option rows set by the controller
     *
     * @return array
     */
    public function getOptions(): array
    {
        $options = $this->getData('options');

        return is_array($options) ? $options : [];
    }

    /**
     * Get the customer facing text of an option
     *
     * @param array $option
     * @return Phrase
     */
    public function getOptionText(array $option): Phrase
    {
        $days = (int)$option['days'];

        if ($option['type'] === VendorSourcingHelper::TYPE_COLLECT) {
            $store = (string)$option['store_name'];

            if ($days === 0) {
                return __('Collect from %1: ready today', $store);
            }

            return $days === 1
                ? __('Collect from %1: ready in %2 day', $store, $days)
                : __('Collect from %1: ready in %2 days', $store, $days);
        }

        if ($days === 0) {
            return __('Home delivery: ready to ship today');
        }

        return $days === 1
            ? __('Home delivery: ready to ship in %1 day', $days)
            : __('Home delivery: ready to ship in %1 days', $days);
    }
}
