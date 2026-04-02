<?php
declare(strict_types=1);

namespace Ls\Customer\Test\Fixture;

use \Ls\Core\Model\LSR;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\App\Area;
use Magento\Framework\App\State;
use Magento\Framework\DataObject;
use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\Exception\AlreadyExistsException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\TestFramework\Fixture\DataFixtureInterface;

class BasketCalculateFixture implements DataFixtureInterface
{
    private const DEFAULT_DATA = [];
    public $customerSession;
    public $checkoutSession;
    public $eventManager;
    public $state;

    /**
     * @param CustomerSession $customerSession
     * @param CheckoutSession $checkoutSession
     * @param ManagerInterface $eventManager
     * @param State $state
     */
    public function __construct(
        CustomerSession $customerSession,
        CheckoutSession $checkoutSession,
        ManagerInterface $eventManager,
        State $state
    ) {
        $this->customerSession             = $customerSession;
        $this->checkoutSession             = $checkoutSession;
        $this->eventManager                = $eventManager;
        $this->state                       = $state;
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
        $this->state->setAreaCode(Area::AREA_FRONTEND);
        $data     = array_merge(self::DEFAULT_DATA, $data);

        $quote    = $data['cart1'];

        if (isset($data['customer'])) {
            $customer = $data['customer'];
            $this->customerSession->setData('customer_id', $customer->getId());
            $this->customerSession->setData(LSR::SESSION_CUSTOMER_CARDID, $customer->getLsrCardid());
        }

        $this->checkoutSession->setQuoteId($quote->getId());

        $this->eventManager->dispatch(
            'checkout_cart_save_after',
            ['items' => $quote->getAllVisibleItems()]
        );

        return new DataObject();
    }
}
