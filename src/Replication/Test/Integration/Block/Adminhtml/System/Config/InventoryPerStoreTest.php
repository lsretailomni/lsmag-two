<?php
declare(strict_types=1);

namespace Ls\Replication\Test\Integration\Block\Adminhtml\System\Config;

use \Ls\Replication\Block\Adminhtml\System\Config\InventoryPerStore;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

/**
 * @magentoAppArea adminhtml
 * @magentoDbIsolation enabled
 */
class InventoryPerStoreTest extends TestCase
{
    /**
     * @magentoAppIsolation enabled
     */
    public function testToOptionArray(): void
    {
        /** @var $model InventoryPerStore */
        $model = Bootstrap::getObjectManager()->create(
            InventoryPerStore::class
        );
        $result = $model->toOptionArray();
        $this->assertCount(2, $result);
    }
}
