<?php
declare(strict_types=1);

namespace Ls\Replication\Test\Integration\Block\Adminhtml\System\Config;

use \Ls\Replication\Block\Adminhtml\System\Config\OrderCreationMethod;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

/**
 * @magentoAppArea adminhtml
 * @magentoDbIsolation enabled
 */
class OrderCreationMethodTest extends TestCase
{
    /**
     * @magentoAppIsolation enabled
     */
    public function testToOptionArray(): void
    {
        /** @var $model OrderCreationMethod */
        $model = Bootstrap::getObjectManager()->create(
            OrderCreationMethod::class
        );
        $result = $model->toOptionArray();
        $this->assertCount(2, $result);
    }
}
