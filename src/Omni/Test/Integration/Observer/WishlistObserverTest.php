<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Ls\Omni\Test\Integration\Observer;

use \Ls\Core\Model\LSR;
use \Ls\Omni\Helper\BasketHelper;
use \Ls\Omni\Helper\ContactHelper;
use \Ls\Omni\Test\Fixture\CreateSimpleProductFixture;
use \Ls\Customer\Test\Fixture\CustomerFixture;
use \Ls\Omni\Test\Integration\AbstractIntegrationTest;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\App\FrontController;
use Magento\Framework\App\Request\Http as HttpRequest;
use Magento\Framework\Data\Form\FormKey;
use Magento\TestFramework\Fixture\Config;
use Magento\TestFramework\Fixture\DataFixture;
use Magento\TestFramework\Fixture\DataFixtureStorageManager;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\Fixture\AppArea;
use Magento\Framework\Registry;
use Magento\TestFramework\Request as TestHttpRequest;
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
    public $contactHelper;

    /**
     * @var mixed
     */
    public $basketHelper;

    /**
     * @var FrontController
     */
    private $frontController;

    /**
     * @var FormKey
     */
    public $formKey;

    /**
     * @var TestHttpRequest
     */
    public $requestInterface;

    /**
     * @var mixed
     */
    public $customerId;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        $this->objectManager    = Bootstrap::getObjectManager();
        $this->request          = $this->objectManager->get(HttpRequest::class);
        $this->requestInterface = $this->objectManager->get(TestHttpRequest::class);
        $this->fixtures         = $this->objectManager->get(DataFixtureStorageManager::class)->getStorage();
        $this->customerSession  = $this->objectManager->get(CustomerSession::class);
        $this->contactHelper    = $this->objectManager->get(ContactHelper::class);
        $this->basketHelper     = $this->objectManager->get(BasketHelper::class);
        $this->registry         = $this->objectManager->get(Registry::class);

        $this->frontController = $this->objectManager->get(
            FrontController::class
        );
        $this->formKey         = $this->objectManager->get(FormKey::class);
    }

    #[
        AppArea('frontend'),
        Config(LSR::SC_SERVICE_ENABLE, AbstractIntegrationTest::LS_MAG_ENABLE, 'store', 'default'),
        Config(LSR::SC_SERVICE_BASE_URL, AbstractIntegrationTest::CS_URL, 'store', 'default'),
        Config(LSR::SC_SERVICE_STORE, AbstractIntegrationTest::CS_STORE, 'store', 'default'),
        Config(LSR::SC_SERVICE_VERSION, AbstractIntegrationTest::CS_VERSION, 'store', 'default'),
        Config(LSR::LS_INDUSTRY_VALUE, AbstractIntegrationTest::RETAIL_INDUSTRY, 'store', 'default'),
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
        )
    ]
    /**
     * Test wishlist sync to CS.
     */
    public function testUpdateWishlistInOmni()
    {
        $customer         = $this->fixtures->get('customer');
        $product          = $this->fixtures->get('p1');
        $this->customerId = $customer->getId();

        $this->customerSession->setData('customer_id', $customer->getId());
        $this->customerSession->setData(LSR::SESSION_CUSTOMER_CARDID, $customer->getLsrCardid());

        $result = $this->contactHelper->login(AbstractIntegrationTest::USERNAME, AbstractIntegrationTest::PASSWORD);
        $this->registry->register(LSR::REGISTRY_LOYALTY_LOGINRESULT, $result);

        $this->requestInterface->setMethod(HttpRequest::METHOD_GET);
        $this->requestInterface->setParams([
            'form_key' => $this->formKey->getFormKey(),
            'product'  => $product->getId()
        ]);
        $this->requestInterface->setRequestUri('wishlist/index/add/');
        $this->frontController->dispatch($this->requestInterface);

        $response = $this->contactHelper->getOneListGetByCardId($customer->getLsrCardid());
        $result   = $response ? $response->getResult() : null;

        $this->registry->unregister(LSR::REGISTRY_LOYALTY_LOGINRESULT);
        $this->assertGreaterThan(0, count($result->getOneList()[0]->getItems()->getOneListItem()));
        $this->assertEquals($product->getSku(), $result->getOneList()[0]->getItems()->getOneListItem()[0]->getItemId());
    }

    /**
     * @return void
     */
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

        parent::tearDown();
    }
}
