<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Ls\Omni\Test\Integration\Observer;

use \Ls\Omni\Test\Fixture\CreateCustomerWishlistFixture;
use \Ls\Core\Model\LSR;
use \Ls\Omni\Helper\BasketHelper;
use \Ls\Omni\Helper\ContactHelper;
use \Ls\Omni\Observer\WishlistObserver;
use \Ls\Omni\Test\Fixture\CreateSimpleProductFixture;
use \Ls\Customer\Test\Fixture\CustomerFixture;
use \Ls\Omni\Test\Integration\AbstractIntegrationTest;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Request\Http as HttpRequest;
use Magento\Framework\Event;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ManagerInterface;
use Magento\TestFramework\Fixture\Config;
use Magento\TestFramework\Fixture\DataFixture;
use Magento\TestFramework\Fixture\DataFixtureStorageManager;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\Fixture\AppArea;
use Magento\Framework\Registry;
use Magento\Wishlist\Model\Wishlist;

class WishlistObserverTest extends AbstractIntegrationTest
{
    /**
     * @var \Magento\Framework\ObjectManagerInterface
     */
    public $objectManager;

    /**
     * @var mixed
     */
    public $request;

    /**
     * @var DataFixtureStorageManager
     */
    public $fixtures;

    /**
     * @var mixed
     */
    public $registry;

    /**
     * @var mixed
     */
    public $customerSession;

    /**
     * @var mixed
     */
    public $checkoutSession;

    /**
     * @var mixed
     */
    public $controllerAction;

    /**
     * @var mixed
     */
    public $contactHelper;

    /**
     * @var mixed
     */
    public $basketHelper;

    /**
     * @var mixed
     */
    public $event;

    /**
     * @var mixed
     */
    public $eventManager;

    public $wishlist;
    /**
     * @var WishlistObserver
     */
    public $wishlistObserver;

    public const PASSWORD = 'Nmswer123@';
    public const EMAIL = 'pipeline_retail@lsretail.com';
    public const USERNAME = 'mc_57745';
    public const INVALID_EMAIL = 'pipeline_retail_pipeline_retail_pipeline_retail_pipeline_retail@lsretail.com';
    public const CUSTOMER_ID = '1';
    public const CS_URL = 'http://20.6.33.78/commerceservice';
    public const CS_VERSION = '2024.4.1';
    public const CS_STORE = 'S0013';
    public const LS_MAG_ENABLE = '1';
    public const INVALID_COUPON_CODE = 'COUPON_CODE';
    public const VALID_COUPON_CODE = 'COUP0119';
    public const RETAIL_INDUSTRY = 'retail';

    /**
     * @return void
     */
    protected function setUp(): void
    {
        $this->objectManager    = Bootstrap::getObjectManager();
        $this->request          = $this->objectManager->get(HttpRequest::class);
        $this->fixtures         = $this->objectManager->get(DataFixtureStorageManager::class)->getStorage();
        $this->customerSession  = $this->objectManager->get(CustomerSession::class);
        $this->controllerAction = $this->objectManager->get(Action::class);
        $this->contactHelper    = $this->objectManager->get(ContactHelper::class);
        $this->basketHelper     = $this->objectManager->get(BasketHelper::class);
        $this->eventManager     = $this->objectManager->create(ManagerInterface::class);
        $this->event            = $this->objectManager->get(Event::class);
        $this->wishlistObserver = $this->objectManager->get(WishlistObserver::class);
        $this->registry         = $this->objectManager->get(Registry::class);
        $this->wishlist         = $this->objectManager->get(Wishlist::class);
    }

    /**
     * @magentoAppIsolation enabled
     */
    #[
        AppArea('frontend'),
        Config(LSR::SC_SERVICE_ENABLE, self::LS_MAG_ENABLE, 'store', 'default'),
        Config(LSR::SC_SERVICE_BASE_URL, self::CS_URL, 'store', 'default'),
        Config(LSR::SC_SERVICE_STORE, self::CS_STORE, 'store', 'default'),
        Config(LSR::SC_SERVICE_VERSION, self::CS_VERSION, 'store', 'default'),
        Config(LSR::LS_INDUSTRY_VALUE, self::RETAIL_INDUSTRY, 'store', 'default'),
        DataFixture(
            CustomerFixture::class,
            [
                'lsr_username' => AbstractIntegrationTest::USERNAME,
                'lsr_id'       => AbstractIntegrationTest::LSR_ID,
                'lsr_cardid'   => AbstractIntegrationTest::LSR_CARD_ID,
                'lsr_token'    => AbstractIntegrationTest::CUSTOMER_ID
            ],
            as: 'customer'
        ),
        DataFixture(
            CreateSimpleProductFixture::class,
            [
                LSR::LS_ITEM_ID_ATTRIBUTE_CODE => '40180',
            ],
            as: 'p1'
        ),
        DataFixture(
            CreateCustomerWishlistFixture::class,
            [
                'customer' => '$customer$',
                'product'  => '$p1.id$',
                'qty'      => 1
            ],
            as: 'w1'
        )
    ]
    /**
     * Show payment methods enabled for click and collect shipping method from admin
     */
    public function testUpdateWishlistInOmni()
    {
        $customer       = $this->fixtures->get('customer');
        $this->wishlist = $this->fixtures->get('w1');
        $wid            = $this->wishlist->getId();
        $this->customerSession->setData('customer_id', $customer->getId());
        $this->customerSession->setData(LSR::SESSION_CUSTOMER_CARDID, $customer->getLsrCardid());

        $result = $this->contactHelper->login(self::USERNAME, self::PASSWORD);
        $this->registry->register(LSR::REGISTRY_LOYALTY_LOGINRESULT, $result);

        //$wishlist = $this->wishlist->loadByCustomerId($customer->getId())->getItemCollection();

        // Execute the observer method
        $this->wishlistObserver->execute(new Observer(
            [
                'wishlist'          => $this->wishlist,
                'request'           => $this->request,
                'controller_action' => $this->controllerAction
            ]
        ));

        $this->registry->unregister(LSR::REGISTRY_LOYALTY_LOGINRESULT);

//        $this->assertTrue($this->event->getResult()->getData('is_available'));
    }
}
