<?php

declare(strict_types=1);

namespace Ls\Omni\Test\Fixture;

use Ls\Omni\Controller\Cart\RedeemPoints;
use Ls\Omni\Test\Integration\AbstractIntegrationTest;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Framework\App\Area;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\State;
use Magento\Framework\DataObject;
use Laminas\Stdlib\Parameters;
use Magento\Paypal\Model\CartFactory;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\TestFramework\Fixture\DataFixtureInterface;
use Magento\Framework\App\Request\Http as HttpRequest;

class ApplyLoyaltyPointsInCartFixture implements DataFixtureInterface
{
    private const DEFAULT_DATA = [
        'product'     => '1',
        'qty'         => '1',
        'customer_id' => '1',
        'store_id'    => '1',
    ];
    /**
     * @var CartFactory
     */
    public $cartFactory;

    /**
     * @var RedeemPoints
     */
    public $redeemPoints;

    /**
     * @var CartRepositoryInterface
     */
    public $quoteRepository;

    /**
     * @var State
     */
    public $state;

    /**
     * @var CheckoutSession
     */
    public $checkoutSession;

    /**
     * @param CartFactory $cartFactory
     * @param RedeemPoints $redeemPoints
     * @param State $state
     * @param CheckoutSession $checkoutSession
     * @param CartRepositoryInterface $quoteRepository
     */
    public function __construct(
        CartFactory $cartFactory,
        RedeemPoints $redeemPoints,
        State $state,
        CheckoutSession $checkoutSession,
        CartRepositoryInterface $quoteRepository
    ) {
        $this->cartFactory     = $cartFactory;
        $this->redeemPoints    = $redeemPoints;
        $this->quoteRepository = $quoteRepository;
        $this->state           = $state;
        $this->checkoutSession = $checkoutSession;
    }

    public function apply(array $data = []): ?DataObject
    {

        $this->state->setAreaCode(Area::AREA_FRONTEND);
        $data = array_merge(self::DEFAULT_DATA, $data);

        $cart = $data['cart'];

        $cart->getShippingAddress()->setCollectShippingRates(true);
        $cart->setLsPointsSpent(AbstractIntegrationTest::LSR_LOY_POINTS)->collectTotals();
        $this->quoteRepository->save($cart);

        $this->checkoutSession->getQuote()->setLsPointsSpent(AbstractIntegrationTest::LSR_LOY_POINTS)->save();

        return $cart;
    }
}
