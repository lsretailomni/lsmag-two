<?php
declare(strict_types=1);

namespace Ls\Customer\Test\Fixture;

use \Ls\Core\Model\LSR;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\App\Area;
use Magento\Framework\App\State;
use Magento\Framework\DataObject;
use Magento\Framework\Exception\AlreadyExistsException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\TestFramework\Fixture\DataFixtureInterface;
use Magento\Checkout\Model\Cart;

class BasketCalculateFixture implements DataFixtureInterface
{
    private const DEFAULT_DATA = [];
    public $customerSession;
    public $checkoutSession;
    public $eventManager;
    public $state;
    private Cart $cart;

    /**
     * @param CustomerSession $customerSession
     * @param CheckoutSession $checkoutSession
     * @param State $state
     */
    public function __construct(
        CustomerSession $customerSession,
        CheckoutSession $checkoutSession,
        State $state,
        Cart $cart,
    ) {
        $this->customerSession             = $customerSession;
        $this->checkoutSession             = $checkoutSession;
        $this->state                       = $state;
        $this->cart            = $cart;
    }

    /**
     * Apply fixture data
     *
     * @param array $data
     * @return DataObject|null
     * @throws AlreadyExistsException
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function apply(array $data = []): ?DataObject
    {
        echo "Method: " . __METHOD__;
        echo "Line: " . __LINE__;
        try {
            $this->state->setAreaCode(Area::AREA_FRONTEND);
        } catch (\Exception $e) {
            echo 'Unable to set area:' . $e->getMessage();
        }
        $data     = array_merge(self::DEFAULT_DATA, $data);

        $quote    = $data['cart1'];

        if (isset($data['customer'])) {
            $customer = $data['customer'];
            $this->customerSession->setData('customer_id', $customer->getId());
            $this->customerSession->setData(LSR::SESSION_CUSTOMER_CARDID, $customer->getLsrCardid());
        }

        echo "Line: " . __LINE__;
        $this->checkoutSession->setQuoteId($quote->getId());
        $quote = $data['cart1'];

        $this->cart->setQuote($quote);
        echo "Line: " . __LINE__;
        try {
            $this->cart->save();
        } catch (\Exception $e) {
            echo 'Unable to set quote:' . $e->getMessage();
        }

        echo "Line: " . __LINE__;
//        $this->eventManager->dispatch(
//            'checkout_cart_save_after',
//            ['items' => $quote->getAllVisibleItems()]
//        );

        return new DataObject();
    }
}
