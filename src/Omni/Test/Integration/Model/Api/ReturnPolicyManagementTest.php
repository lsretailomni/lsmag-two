<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Ls\Omni\Test\Integration\Model\Api;

use \Ls\Core\Model\LSR;
use \Ls\Customer\Test\Fixture\CustomerFixture;
use \Ls\Omni\Test\Fixture\CreateSimpleProductFixture;
use \Ls\Omni\Test\Integration\AbstractIntegrationTest;
use \Ls\Omni\Model\Api\ReturnPolicyManagement;
use Magento\TestFramework\Fixture\Config;
use Magento\TestFramework\Fixture\DataFixture;
use Magento\TestFramework\Fixture\DataFixtureStorageManager;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\Fixture\AppArea;
use PHPUnit\Framework\TestCase;

class ReturnPolicyManagementTest extends TestCase
{
    /**
     * @var \Magento\Framework\ObjectManagerInterface
     */
    public $objectManager;

    /**
     * @var DataFixtureStorageManager
     */
    public $fixtures;

    /**
     * @var ReturnPolicyManagement
     */
    public $returnPolicyManagement;

    /**
     * @return void
     */
    public function setUp(): void
    {
        $this->objectManager          = Bootstrap::getObjectManager();
        $this->fixtures               = $this->objectManager->get(DataFixtureStorageManager::class)->getStorage();
        $this->returnPolicyManagement = $this->objectManager->get(ReturnPolicyManagement::class);
    }

    /**
     * @magentoAppIsolation enabled
     */
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
        ),
    ]
    public function testGetReturnPolicy()
    {
        $prod = $this->fixtures->get('p1');

        $result = $this->returnPolicyManagement->getReturnPolicy($prod->getSku(), '', '');
        $this->assertNotNull($result);
    }
}
