<?php

namespace Ls\Omni\ViewModel;

use \Ls\Omni\Helper\Data;
use Magento\Framework\View\Element\Block\ArgumentInterface;

/**
 * Class CouponsViewModel
 * @package Ls\Omni\ViewModel
 */
class CouponsViewModel implements ArgumentInterface
{
    /**
     * @var Data
     */
    public $data;

    /**
     * CouponsViewModel constructor.
     * @param Data $data
     */
    public function __construct(Data $data)
    {
        $this->data = $data;
    }

    /**
     * @return string
     */
    public function isCouponsEnabled()
    {
        return $this->data->isCouponsEnabled("cart");
    }
}