<?php

namespace Ls\Omni\Test\Integration\Controller\Ajax;

use \Ls\Core\Model\LSR;
use \Ls\Omni\Test\Integration\AbstractIntegrationTest;
use \Ls\Omni\Controller\Ajax\CheckGiftCardBalance;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\TestFramework\Fixture\AppArea;
use Magento\TestFramework\Fixture\Config;
use Magento\Framework\App\Request\Http as HttpRequest;
use Magento\Store\Model\Store;

use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\TestCase\AbstractController;

class CheckGiftCardBalanceTest extends AbstractController
{

    /**
     * @var \Magento\Framework\ObjectManagerInterface
     */
    public $objectManager;

    /**
     * @var Store
     */
    public $store;

    /**
     * @var CheckGiftCardBalance
     */
    public $checkGiftCardBalance;

    /**
     * @var SerializerInterface
     */
    public $json;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->objectManager        = Bootstrap::getObjectManager();
        $this->store                = $this->objectManager->get(Store::class);
        $this->checkGiftCardBalance = $this->objectManager->get(CheckGiftCardBalance::class);
        $this->json                 = $this->objectManager->get(SerializerInterface::class);
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
        Config(LSR::LS_INDUSTRY_VALUE, AbstractIntegrationTest::RETAIL_INDUSTRY, 'store', 'default')
    ]
    public function testExecute()
    {
        $giftCardData = ['gift_card_code' => '10000011', 'gift_card_pin' => '8118'];
        $content      = json_encode($giftCardData);
        $this->getRequest()->setContent($content);
        $this->getRequest()->setMethod(HttpRequest::METHOD_POST);
        $this->getRequest()->getHeaders()
            ->addHeaderLine('X_REQUESTED_WITH', 'XMLHttpRequest');
        $this->dispatch('omni/ajax/checkGiftCardBalance');
        $content = json_decode($this->getResponse()->getBody());
        $this->assertEquals('true', $content->success);
        $this->assertNotNull($content->data);
    }
}
