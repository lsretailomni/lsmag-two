<?php
declare(strict_types=1);

namespace Ls\Customer\Test\Integration\Plugin\Customer\Block\Widget;

use Magento\Customer\Api\Data\CustomerInterfaceFactory;
use Magento\Customer\Block\Widget\Name;
use Magento\Framework\View\LayoutInterface;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\Helper\Xpath;
use PHPUnit\Framework\TestCase;

/**
 * @magentoAppArea frontend
 * @magentoDbIsolation enabled
 * @magentoAppIsolation enabled
 */
class NameTest extends TestCase
{
    public $block;
    public $fixtures;
    public $objectManager;
    public $registry;
    public $customerFactory;

    protected function setUp(): void
    {
        parent::setUp();
        $this->objectManager   = Bootstrap::getObjectManager();
        $this->block           = $this->objectManager->get(
            LayoutInterface::class
        )->createBlock(
            Name::class
        );
        $this->customerFactory = $this->objectManager->get(CustomerInterfaceFactory::class);
    }

    public function testBeforeToHtml()
    {
        $customerDataObject = $this->customerFactory->create();
        $customerDataObject->setFirstname('Jane');
        $customerDataObject->setLastname('Doe');
        $this->block->setObject($customerDataObject);
        $output = $this->block->toHtml();
        $this->assertEquals($this->block->getTemplate(), 'Ls_Customer::widget/name.phtml');
        $msg = sprintf('Can\'t validate html: %s', $output);
        $ele1 = [
            "//div[contains(@class, 'field-name-firstname')]",
            "//input[contains(@maxlength, '24') and contains(@data-validate, '{\"required\":true,\"maxlength\":30}')]",
        ];
        $ele2 = [
            "//div[contains(@class, 'field-name-lastname')]",
            "//input[contains(@maxlength, '24') and contains(@data-validate, '{\"required\":true,\"maxlength\":30}')]",
        ];
        $this->validateCountForXpath($ele1, 1, $output, $msg);
        $this->validateCountForXpath($ele2, 1, $output, $msg);
    }

    public function validateCountForXpath($ele, $expected, $output, $msg)
    {
        $eleCount = implode('', $ele);
        $this->assertEquals(
            $expected,
            Xpath::getElementsCountForXpath($eleCount, $output),
            $msg
        );
    }
}
