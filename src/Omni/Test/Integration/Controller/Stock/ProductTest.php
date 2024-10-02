<?php

namespace Ls\Omni\Test\Integration\Controller\Stock;

use \Ls\Core\Model\LSR;
use \Ls\Customer\Test\Fixture\CustomerFixture;
use \Ls\Omni\Test\Fixture\CreateSimpleProductFixture;
use \Ls\Omni\Test\Integration\AbstractIntegrationTest;
use Magento\Framework\App\Request\Http as HttpRequest;
use Magento\TestFramework\Fixture\AppArea;
use Magento\TestFramework\Fixture\Config;
use Magento\TestFramework\Fixture\DataFixture;
use Magento\TestFramework\TestCase\AbstractController;

class ProductTest extends AbstractController
{
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
        )
    ]
    public function testExecute()
    {
        $params = [
            'sku' => '40180',
            'id'  => ''
        ];
        $this->getRequest()->setParams($params);
        $this->getRequest()->setMethod(HttpRequest::METHOD_POST);
        $this->getRequest()->getHeaders()
            ->addHeaderLine('X_REQUESTED_WITH', 'XMLHttpRequest');

        $this->dispatch('omni/stock/Product');
        $content = json_decode($this->getResponse()->getBody());
        $this->assertNotNull($content->content);
        $this->assertNotNull($content->stocks);
    }
}
