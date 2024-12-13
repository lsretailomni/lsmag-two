<?php

namespace Ls\Omni\Test\Unit\Client\Ecommerce\Operation;

use \Ls\Omni\Client\Ecommerce\Entity\ArrayOfReplInvStatus;
use \Ls\Omni\Client\Ecommerce\Entity\ReplEcommInventoryStatus;
use \Ls\Omni\Client\Ecommerce\Entity\ReplInvStatusResponse;

class ReplEcommInventoryStatusTest extends ReplicationTest
{
    public function testReplEcommInventoryStatus()
    {
        $request = new ReplEcommInventoryStatus();
        $request->setReplRequest($this->params);
        $response = $this->executeMethod("ReplEcommInventoryStatus", $request);
        $result = $response ? $response->getResult() : null;
        $this->assertInstanceOf(ReplInvStatusResponse::class, $result);
        $this->assertNotNull($result->getItems());
        $this->assertInstanceOf(ArrayOfReplInvStatus::class, $result->getItems());
    }
}
