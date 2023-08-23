<?php

namespace Ls\Omni\Test\Unit\Client\Ecommerce\Operation;

use \Ls\Omni\Client\Ecommerce\Entity\Store;

class StoreGetByIdTest extends OmniClientSetupTest
{
    public function testExecute()
    {
        $this->assertNotNull($this->client);
        $param    = [
            'storeId' => $this->getEnvironmentVariableValueGivenName('STORE_ID')
        ];
        $response = $this->client->StoreGetById($param);
        $result   = $response->getResult();
        $this->assertInstanceOf(Store::class, $result);
        $this->assertNotNull($result->getLatitude());
        $this->assertNotNull($result->getLongitude());
        $this->assertNotNull($result->getPhone());
        $this->assertNotNull($result->getStoreHours());
        $this->assertNotNull($result->getAddress());
        $this->assertEquals($this->getEnvironmentVariableValueGivenName('STORE_ID'), $result->getId());
    }
}
