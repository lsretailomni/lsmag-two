<?php
declare(strict_types=1);

namespace Ls\Webhooks\Model\Config\Source;

use \Ls\Core\Model\LSR;
use Magento\Framework\Data\OptionSourceInterface;

class NotificationType implements OptionSourceInterface
{
    /**
     * Get all notification types
     *
     * @return array
     */
    public function toOptionArray()
    {
        return [
            ['value' => '', 'label' => __('-- Please Select --')],
            ['value' => LSR::LS_NOTIFICATION_EMAIL, 'label' => __('EMAIL')],
            ['value' => LSR::LS_NOTIFICATION_SMS, 'label' => __('SMS')]
        ];
    }
}
