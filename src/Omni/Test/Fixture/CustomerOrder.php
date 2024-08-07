<?php
declare(strict_types=1);

namespace Ls\Omni\Test\Fixture;

use Ls\Core\Model\LSR;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Checkout\Model\Type\Onepage;
use Magento\Customer\Api\AddressRepositoryInterface;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\App\Area;
use Magento\Framework\App\State;
use Magento\Framework\DataObject;
use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\Exception\AlreadyExistsException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Quote\Api\CartManagementInterface;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Api\Data\AddressInterfaceFactory;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\TestFramework\Fixture\DataFixtureInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;

class CustomerOrder implements DataFixtureInterface
{
    private const DEFAULT_DATA = [];
    public $customerSession;
    public $checkoutSession;
    public $eventManager;
    public $cartManagement;
    public $cartRepository;
    public $addressRespositoryInterface;
    public $addressInterfaceFactory;

    public $customerRepository;
    public $orderRepository;
    public $state;

    /**
     * @param CustomerSession $customerSession
     * @param CheckoutSession $checkoutSession
     * @param ManagerInterface $eventManager
     * @param CartManagementInterface $cartManagement
     * @param CartRepositoryInterface $cartRepository
     * @param AddressRepositoryInterface $addressRepositoryInterface
     * @param AddressInterfaceFactory $addressInterfaceFactory
     * @param CustomerRepositoryInterface $customerRepository
     * @param OrderRepositoryInterface $orderRepository
     * @param State $state
     */
    public function __construct(
        CustomerSession $customerSession,
        CheckoutSession $checkoutSession,
        ManagerInterface $eventManager,
        CartManagementInterface $cartManagement,
        CartRepositoryInterface $cartRepository,
        AddressRepositoryInterface $addressRepositoryInterface,
        AddressInterfaceFactory $addressInterfaceFactory,
        CustomerRepositoryInterface $customerRepository,
        OrderRepositoryInterface $orderRepository,
        State $state
    ) {
        $this->customerSession             = $customerSession;
        $this->checkoutSession             = $checkoutSession;
        $this->eventManager                = $eventManager;
        $this->cartManagement              = $cartManagement;
        $this->cartRepository              = $cartRepository;
        $this->addressRespositoryInterface = $addressRepositoryInterface;
        $this->addressInterfaceFactory     = $addressInterfaceFactory;
        $this->customerRepository          = $customerRepository;
        $this->orderRepository             = $orderRepository;
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
        $customer = $data['customer'];
        $quote    = $data['cart1'];
        $address  = $data['address'];
        $payment  = $data['payment'];
        $shipment = (isset($data['shipment'])) ? $data['shipment'] : 'flatrate_flatrate';
        $this->customerSession->setData('customer_id', $customer->getId());
        $this->customerSession->setData(LSR::SESSION_CUSTOMER_CARDID, $customer->getLsrCardid());
        $this->checkoutSession->setQuoteId($quote->getId());

        $this->eventManager->dispatch(
            'checkout_cart_save_after',
            ['items' => $quote->getAllVisibleItems()]
        );

        $quoteShippingAddress = $this->addressInterfaceFactory->create();
        $quoteShippingAddress->importCustomerAddressData(
            $this->addressRespositoryInterface->getById($address->getId())
        );
        $customer = $this->customerRepository->getById($customer->getId());
        $quote->setStoreId(1)
            ->setIsActive(true)
            ->setIsMultiShipping(0)
            ->assignCustomerWithAddressChange($customer)
            ->setShippingAddress($quoteShippingAddress)
            ->setBillingAddress($quoteShippingAddress)
            ->setCheckoutMethod(Onepage::METHOD_CUSTOMER)
            ->setReservedOrderId('55555555')
            ->setEmail($customer->getEmail());
        $quote->getShippingAddress()->setShippingMethod($shipment);
        $quote->getShippingAddress()->setCollectShippingRates(true);
        $quote->getShippingAddress()->collectShippingRates();
        $quote->getPayment()->setMethod($payment);
        $this->cartRepository->save($quote);

        $quote = $this->cartRepository->getActiveForCustomer($customer->getId());

        $orderId = $this->cartManagement->placeOrder($quote->getId());

        $order = $this->orderRepository->get($orderId);
        $this->eventManager->dispatch(
            'checkout_onepage_controller_success_action',
            ['order' => $order]
        );

        return $order;
    }
}
