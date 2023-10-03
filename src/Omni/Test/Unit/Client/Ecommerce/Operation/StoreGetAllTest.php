<?php

namespace Ls\Omni\Test\Unit\Client\Ecommerce\Operation;

use \Ls\Omni\Client\Ecommerce\Entity\ArrayOfStore;
use PHPUnit\Framework\TestCase;

class StoresGetAllTest extends OmniClientSetupTest
{
    public function testExecute()
    {
        $this->assertNotNull($this->client);
        $response = $this->client->StoresGetAll();
        $result   = $response->getResult();
        $this->assertInstanceOf(ArrayOfStore::class, $result);
        $stores = $result->getStore();
        foreach ($stores as $store) {
            if (!empty($store)) {
                $this->assertNotNull($store->getLatitude());
                $this->assertNotNull($store->getLatitude());
                $this->assertNotNull($store->getStoreHours());
                $this->assertNotNull($store->getAddress());
            }
        }
    }
}
