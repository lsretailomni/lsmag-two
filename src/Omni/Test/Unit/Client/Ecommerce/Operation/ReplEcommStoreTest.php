<?php

namespace Ls\Omni\Test\Unit\Client\Ecommerce\Operation;

use \Ls\Omni\Client\Ecommerce\Entity\ArrayOfReplStore;
use \Ls\Omni\Client\Ecommerce\Entity\ReplEcommStores;
use \Ls\Omni\Client\Ecommerce\Entity\ReplStoreResponse;

class ReplEcommStoreTest extends ReplicationTest
{
    public function testReplEcommAttribute()
    {
        $request = new ReplEcommStores();
        $request->setReplRequest($this->params);
        $response = $this->executeMethod("ReplEcommStores", $request);
        $result = $response ? $response->getResult() : null;
        $this->assertInstanceOf(ReplStoreResponse::class, $result);
        $this->assertNotNull($result->getStores());
        $this->assertInstanceOf(ArrayOfReplStore::class, $result->getStores());
    }
}
