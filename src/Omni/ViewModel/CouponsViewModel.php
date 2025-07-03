<?php
declare(strict_types=1);

namespace Ls\Omni\ViewModel;

use \Ls\Omni\Helper\Data;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\View\Element\Block\ArgumentInterface;

class CouponsViewModel implements ArgumentInterface
{
    /**
     * @param Data $data
     */
    public function __construct(public Data $data)
    {
    }

    /**
     * Is Coupons Enabled
     *
     * @return bool
     * @throws NoSuchEntityException
     */
    public function isCouponsEnabled()
    {
        return $this->data->isCouponsEnabled("cart");
    }

    /**
     * Is Module Enabled
     *
     * @return array|string
     * @throws NoSuchEntityException
     */
    public function isModuleEnabled()
    {
        return $this->data->lsr->isEnabled();
    }
}
