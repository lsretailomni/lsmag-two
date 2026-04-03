<?php
declare(strict_types=1);

namespace Ls\Omni\Test\Integration\Observer;

use \Ls\Core\Model\LSR;
use \Ls\Omni\Helper\BasketHelper;
use \Ls\Omni\Helper\ContactHelper;
use \Ls\Omni\Test\Fixture\CreateSimpleProductFixture;
use \Ls\Omni\Test\Fixture\WishlistWithItemFixture;
use \Ls\Customer\Test\Fixture\CustomerFixture;
use \Ls\Omni\Test\Integration\AbstractIntegrationTest;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\App\FrontController;
use Magento\Framework\App\Request\Http as HttpRequest;
use Magento\Framework\Data\Form\FormKey;
use Magento\TestFramework\Fixture\AppArea;
use Magento\TestFramework\Fixture\Config;
use Magento\TestFramework\Fixture\DataFixture;
use Magento\TestFramework\Fixture\DataFixtureStorageManager;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\Request as TestHttpRequest;
use Magento\Framework\Registry;
use Magento\Framework\App\ResourceConnection;

/**
 * Test for sync wish list to CS.
 *
 * @magentoAppArea frontend
 * @magentoAppIsolation enabled
 * @magentoDbIsolation disabled
 */
class WishlistObserverTest extends AbstractIntegrationTest
{
    public $objectManager;
    public $fixtures;
    public $customerSession;
    public $contactHelper;
    public $basketHelper;
    public $registry;
    public $formKey;
    public $customerId;
    public $oneLists;
    private $frontController;
    private $requestInterface;

    protected function setUp(): void
    {
        $this->objectManager   = Bootstrap::getObjectManager();
        $this->fixtures        = $this->objectManager->get(DataFixtureStorageManager::class)->getStorage();
        $this->customerSession = $this->objectManager->get(CustomerSession::class);
        $this->contactHelper   = $this->objectManager->get(ContactHelper::class);
        $this->basketHelper    = $this->objectManager->get(BasketHelper::class);
        $this->registry        = $this->objectManager->get(Registry::class);
        $this->formKey         = $this->objectManager->get(FormKey::class);
        $this->frontController = $this->objectManager->get(FrontController::class);
        $this->requestInterface = $this->objectManager->get(TestHttpRequest::class);
    }

    #[
        AppArea('frontend'),
        Config(LSR::SC_SERVICE_ENABLE, AbstractIntegrationTest::LS_MAG_ENABLE, 'store', 'default'),
        Config(LSR::SC_SERVICE_BASE_URL, AbstractIntegrationTest::CS_URL, 'store', 'default'),
        Config(LSR::SC_SERVICE_STORE, AbstractIntegrationTest::CS_STORE, 'store', 'default'),
        Config(LSR::SC_SERVICE_VERSION, AbstractIntegrationTest::CS_VERSION, 'store', 'default'),
        Config(LSR::LS_INDUSTRY_VALUE, AbstractIntegrationTest::RETAIL_INDUSTRY, 'store', 'default'),
        Config(LSR::SC_SERVICE_DEBUG, AbstractIntegrationTest::LS_MAG_ENABLE, 'website'),
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
            [LSR::LS_ITEM_ID_ATTRIBUTE_CODE => '40180'],
            as: 'p1'
        )
    ]
    /**
     * Test adding a wishlist item syncs to CS.
     */
    public function testAddWishlistItemInOmni(): void
    {
        $customer = $this->fixtures->get('customer');
        $product = $this->fixtures->get('p1');
        $this->customerId = $customer->getId();

        $this->customerSession->setData('customer_id', $customer->getId());
        $this->customerSession->setData(LSR::SESSION_CUSTOMER_CARDID, $customer->getLsrCardid());

        $loginResult = $this->contactHelper->login(
            AbstractIntegrationTest::USERNAME,
            AbstractIntegrationTest::PASSWORD
        );
        $this->registry->register(LSR::REGISTRY_LOYALTY_LOGINRESULT, $loginResult);

        $this->requestInterface->setMethod(HttpRequest::METHOD_GET);
        $this->requestInterface->setParams([
            'form_key' => $this->formKey->getFormKey(),
            'product' => $product->getId(),
        ]);
        $this->requestInterface->setRequestUri('wishlist/index/add/');
        $this->frontController->dispatch($this->requestInterface);

        $response = $this->contactHelper->getOneListGetByCardId($customer->getLsrCardid());
        $result = $response ? $response->getResult() : null;
        $this->oneLists = $result->getOneList();
        $this->registry->unregister(LSR::REGISTRY_LOYALTY_LOGINRESULT);

        $oneListItems = $result->getOneList()[0]->getItems()->getOneListItem();
        $found = false;

        foreach ((array)$oneListItems as $oneListItem) {
            if ($oneListItem->getItemId() == $product->getSku()) {
                $found = true;
                break;
            }
        }

        $this->assertTrue($found, 'Added item should exist in CS wishlist');
    }

    #[
        AppArea('frontend'),
        Config(LSR::SC_SERVICE_ENABLE, AbstractIntegrationTest::LS_MAG_ENABLE, 'store', 'default'),
        Config(LSR::SC_SERVICE_BASE_URL, AbstractIntegrationTest::CS_URL, 'store', 'default'),
        Config(LSR::SC_SERVICE_STORE, AbstractIntegrationTest::CS_STORE, 'store', 'default'),
        Config(LSR::SC_SERVICE_VERSION, AbstractIntegrationTest::CS_VERSION, 'store', 'default'),
        Config(LSR::LS_INDUSTRY_VALUE, AbstractIntegrationTest::RETAIL_INDUSTRY, 'store', 'default'),
        Config(LSR::SC_SERVICE_DEBUG, AbstractIntegrationTest::LS_MAG_ENABLE, 'website'),
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
            [LSR::LS_ITEM_ID_ATTRIBUTE_CODE => '40180'],
            as: 'p1'
        ),
        DataFixture(
            WishlistWithItemFixture::class,
            [
                'customer' => '$customer$',
                'product_id'  => '$p1.id$',
            ],
            as: 'wishlist'
        )
    ]
    /**
     * Test removing a wishlist item syncs removal to CS.
     */
    public function testRemoveWishlistItemInOmni(): void
    {
        $customer = $this->fixtures->get('customer');
        $product = $this->fixtures->get('p1');
        $wishlist = $this->fixtures->get('wishlist');
        $this->customerId = $customer->getId();
        $this->customerSession->setData('customer_id', $customer->getId());
        $this->customerSession->setData(LSR::SESSION_CUSTOMER_CARDID, $customer->getLsrCardid());

        $loginResult = $this->contactHelper->login(
            AbstractIntegrationTest::USERNAME,
            AbstractIntegrationTest::PASSWORD
        );
        $this->registry->register(LSR::REGISTRY_LOYALTY_LOGINRESULT, $loginResult);
        $oneListWish = $this->contactHelper->getOneListTypeObject(
            $loginResult->getOneLists()->getOneList(),
            \Ls\Omni\Client\Ecommerce\Entity\Enum\ListType::WISH
        );
        $this->basketHelper->setWishListInCustomerSession($oneListWish);

        // Fetch the wishlist item created by fixture
        $wishlistItems = $wishlist->getItemCollection()->getItems();
        $wishlistItem = reset($wishlistItems);

        $this->assertNotEmpty($wishlistItem, 'Wishlist item should exist before removal');

        $this->requestInterface->setMethod(HttpRequest::METHOD_POST);
        $this->requestInterface->setParams([
            'form_key' => $this->formKey->getFormKey(),
            'item' => $wishlistItem->getId(),
        ]);
        $this->requestInterface->setRequestUri('wishlist/index/remove/');
        $this->frontController->dispatch($this->requestInterface);

        $response = $this->contactHelper->getOneListGetByCardId($customer->getLsrCardid());
        $result = $response ? $response->getResult() : null;

        $this->registry->unregister(LSR::REGISTRY_LOYALTY_LOGINRESULT);
        $this->oneLists = $result->getOneList();
        $oneListItems = $result->getOneList()[0]->getItems()->getOneListItem();
        $found = false;

        foreach ((array)$oneListItems as $oneListItem) {
            if ($oneListItem->getItemId() == $product->getSku()) {
                $found = true;
                break;
            }
        }

        $this->assertFalse($found, 'Removed item should not exist in CS wishlist');
    }

//    #[
//        AppArea('frontend'),
//        Config(LSR::SC_SERVICE_ENABLE, AbstractIntegrationTest::LS_MAG_ENABLE, 'store', 'default'),
//        Config(LSR::SC_SERVICE_BASE_URL, AbstractIntegrationTest::CS_URL, 'store', 'default'),
//        Config(LSR::SC_SERVICE_STORE, AbstractIntegrationTest::CS_STORE, 'store', 'default'),
//        Config(LSR::SC_SERVICE_VERSION, AbstractIntegrationTest::CS_VERSION, 'store', 'default'),
//        Config(LSR::LS_INDUSTRY_VALUE, AbstractIntegrationTest::RETAIL_INDUSTRY, 'store', 'default'),
//        Config(LSR::SC_SERVICE_DEBUG, AbstractIntegrationTest::LS_MAG_ENABLE, 'website'),
//        DataFixture(
//            CustomerFixture::class,
//            [
//                'lsr_username' => AbstractIntegrationTest::USERNAME,
//                'lsr_id'       => AbstractIntegrationTest::LSR_ID,
//                'lsr_cardid'   => AbstractIntegrationTest::LSR_CARD_ID,
//                'lsr_token'    => AbstractIntegrationTest::CUSTOMER_ID
//            ],
//            as: 'customer'
//        ),
//        DataFixture(
//            CreateSimpleProductFixture::class,
//            [LSR::LS_ITEM_ID_ATTRIBUTE_CODE => '40180'],
//            as: 'p1'
//        ),
//        DataFixture(
//            WishlistWithItemFixture::class,
//            [
//                'customer' => '$customer$',
//                'product_id'  => '$p1.id$',
//            ],
//            as: 'wishlist'
//        )
//    ]
//    /**
//     * Test updating wishlist item quantity syncs to CS.
//     */
//    public function testUpdateWishlistItemQtyInOmni(): void
//    {
//        $customer         = $this->fixtures->get('customer');
//        $product          = $this->fixtures->get('p1');
//        $this->customerId = $customer->getId();
//
//        $this->customerSession->setData('customer_id', $customer->getId());
//        $this->customerSession->setData(LSR::SESSION_CUSTOMER_CARDID, $customer->getLsrCardid());
//
//        $loginResult = $this->contactHelper->login(
//            AbstractIntegrationTest::USERNAME,
//            AbstractIntegrationTest::PASSWORD
//        );
//        $this->registry->register(LSR::REGISTRY_LOYALTY_LOGINRESULT, $loginResult);
//
//        // Fetch the wishlist item created by fixture
//        $wishlist      = $this->objectManager->get(\Magento\Wishlist\Model\Wishlist::class);
//        $wishlistItems = $wishlist->loadByCustomerId($customer->getId())->getItemCollection()->getItems();
//        $wishlistItem  = reset($wishlistItems);
//
//        $this->assertNotEmpty($wishlistItem, 'Wishlist item should exist before update');
//
//        $this->requestInterface->setMethod(HttpRequest::METHOD_POST);
//        $this->requestInterface->setParams([
//            'form_key' => $this->formKey->getFormKey(),
//            'qty'      => [$wishlistItem->getId() => 2],
//        ]);
//        $this->requestInterface->setRequestUri('wishlist/index/update/');
//        $this->frontController->dispatch($this->requestInterface);
//
//        $response = $this->contactHelper->getOneListGetByCardId($customer->getLsrCardid());
//        $result   = $response ? $response->getResult() : null;
//
//        $this->registry->unregister(LSR::REGISTRY_LOYALTY_LOGINRESULT);
//
//        $oneListItems = $result->getOneList()[0]->getItems()->getOneListItem();
//        $this->assertNotEmpty($oneListItems, 'CS wishlist should have items after update');
//
//        $updatedItem = null;
//        foreach ((array)$oneListItems as $oneListItem) {
//            if ($oneListItem->getItemId() == $product->getSku()) {
//                $updatedItem = $oneListItem;
//                break;
//            }
//        }
//
//        $this->assertNotNull($updatedItem, 'Updated item should exist in CS wishlist');
//        $this->assertEquals(2, (int)$updatedItem->getQuantity(), 'Item quantity should be updated to 2 in CS');
//    }

    public function tearDown(): void
    {
        $resource   = $this->objectManager->get(ResourceConnection::class);
        $connection = $resource->getConnection();

        if (!empty($this->customerId)) {
            $connection->delete(
                $resource->getTableName('customer_entity'),
                ['entity_id IN (?)' => $this->customerId]
            );

            $connection->delete(
                $resource->getTableName('customer_address_entity'),
                ['parent_id IN (?)' => $this->customerId]
            );
        }

        if (!empty($this->oneLists)) {
            foreach ($this->oneLists as $oneList) {
                $this->basketHelper->delete($oneList);
            }
        }

        parent::tearDown();
    }
}
