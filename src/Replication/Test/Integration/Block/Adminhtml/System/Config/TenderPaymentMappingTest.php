<?php
declare(strict_types=1);

namespace Ls\Replication\Test\Integration\Block\Adminhtml\System\Config;

use \Ls\Core\Model\LSR;
use \Ls\Replication\Block\Adminhtml\System\Config\Tax;
use \Ls\Replication\Block\Adminhtml\System\Config\TenderPaymentMapping;
use \Ls\Replication\Cron\ReplEcommStoreTenderTypesTask;
use \Ls\Replication\Test\Fixture\FlatDataReplication;
use \Ls\Replication\Test\Integration\AbstractIntegrationTest;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Data\Form\Element\Text;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\TestFramework\Fixture\AppArea;
use Magento\TestFramework\Fixture\Config;
use Magento\TestFramework\Fixture\DataFixture;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\Helper\Xpath;
use PHPUnit\Framework\TestCase;

/**
 * @magentoAppArea adminhtml
 * @magentoDbIsolation enabled
 */
class TenderPaymentMappingTest extends TestCase
{
    public $objectManager;
    public $request;
    public $storeManager;
    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->objectManager = Bootstrap::getObjectManager();
        $this->request       = $this->objectManager->get(
            RequestInterface::class
        );
        $this->storeManager  = $this->objectManager->get(StoreManagerInterface::class);
    }

    /**
     * @magentoAppIsolation enabled
     */
    #[
        AppArea('adminhtml'),
        Config(LSR::SC_SERVICE_ENABLE, AbstractIntegrationTest::ENABLED, 'store', 'default'),
        Config(LSR::SC_SERVICE_BASE_URL, AbstractIntegrationTest::CS_URL, 'store', 'default'),
        Config(LSR::SC_SERVICE_STORE, AbstractIntegrationTest::CS_STORE, 'store', 'default'),
        Config(LSR::SC_SERVICE_VERSION, AbstractIntegrationTest::CS_VERSION, 'store', 'default'),
        Config(LSR::SC_SERVICE_BASE_URL, AbstractIntegrationTest::CS_URL, 'website'),
        Config(LSR::SC_SERVICE_ENABLE, AbstractIntegrationTest::ENABLED, 'website'),
        Config(LSR::SC_SERVICE_STORE, AbstractIntegrationTest::CS_STORE, 'website'),
        Config(LSR::SC_SERVICE_VERSION, AbstractIntegrationTest::CS_VERSION, 'website'),
        Config(LSR::LS_INDUSTRY_VALUE, LSR::LS_INDUSTRY_VALUE_RETAIL, 'store', 'default'),
        Config(LSR::SC_REPLICATION_DEFAULT_BATCHSIZE, AbstractIntegrationTest::DEFAULT_BATCH_SIZE),
        DataFixture(
            FlatDataReplication::class,
            [
                'job_url' => ReplEcommStoreTenderTypesTask::class,
                'scope' => ScopeInterface::SCOPE_WEBSITE
            ]
        ),
    ]
    public function testRender(): void
    {
        $this->request->setParams([
            'website' => $this->storeManager->getWebsite()->getId()
        ]);
        /** @var $model Tax */
        $block = $this->objectManager->create(
            TenderPaymentMapping::class
        );
        $form = $this->objectManager->create(\Magento\Framework\Data\Form::class);
        /** @var Text $element */
        $element = $this->objectManager->create(Text::class);
        $data = [
            'name' => 'tender_type_mapping',
            'label' => 'Tender Type Mapping',
            'class' => '',
            'note' => '',
            'value' => null,
            'type' => 'text',
            'ext_type' => 'textfield',
            'container_id' => '',
            'html_id' => 'options_fieldset67a77e971a7c331b6eaefcaf2f596097_condition',
        ];
        $element->setData($data);
        $element->setForm($form);

        $output = $block->render($element);

        $elementPaths1 = [
            "//table[contains(@class, 'admin__control-table')]",
            "//thead",
            "//tr",
            sprintf("//th[contains(text(), '%s')]", __('Payment Methods'))
        ];

        $this->validatePaths($output, $elementPaths1, sprintf('Can\'t validate tender type mapping: %s', $output));

        $elementPaths2 = [
            "//table[contains(@class, 'admin__control-table')]",
            "//thead",
            "//tr",
            sprintf("//th[contains(text(), '%s')]", __('Tender Types'))
        ];

        $this->validatePaths($output, $elementPaths2, sprintf('Can\'t validate tender type mapping: %s', $output));
    }
    public function validatePaths($output, $ele, $msg, $expected = 1, $condition = 1)
    {
        $eleCount = implode('', $ele);

        if ($condition == 1) {
            $this->assertEquals(
                $expected,
                Xpath::getElementsCountForXpath($eleCount, $output),
                $msg
            );
        } else {
            $this->assertGreaterThanOrEqual(
                $expected,
                Xpath::getElementsCountForXpath($eleCount, $output),
                $msg
            );
        }
    }

}
