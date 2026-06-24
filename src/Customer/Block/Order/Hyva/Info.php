<?php
declare(strict_types=1);

namespace Ls\Customer\Block\Order\Hyva;

use \Ls\Core\Model\LSR;
use \Ls\Omni\Helper\Data as DataHelper;
use \Ls\Omni\Helper\LoyaltyHelper;
use \Ls\Omni\Helper\OrderHelper;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Directory\Model\CountryFactory;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\View\Element\Template\Context;
use Magento\Framework\Pricing\Helper\Data as PriceHelper;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Sales\Model\OrderRepository;
use Magento\Framework\App\Http\Context as HttpContext;

/**
 * This class is overriding in hospitality module
 *
 * Block being used for various sections on order detail
 */
class Info extends \Ls\Customer\Block\Order\Info
{
    /**
     * @var string
     */
    // @codingStandardsIgnoreStart
    protected $_template = 'Ls_Customer::order/info-hyva.phtml';
    // @codingStandardsIgnoreEnd
}
