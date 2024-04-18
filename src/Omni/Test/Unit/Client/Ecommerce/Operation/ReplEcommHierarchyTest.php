<?php

namespace Ls\Omni\Test\Unit\Client\Ecommerce\Operation;

use \Ls\Omni\Client\Ecommerce\Entity\ArrayOfReplHierarchy;
use \Ls\Omni\Client\Ecommerce\Entity\ReplEcommHierarchy;
use \Ls\Omni\Client\Ecommerce\Entity\ReplHierarchyResponse;

class ReplEcommHierarchyTest extends ReplicationTest
{
    public function testReplEcommHierarchy()
    {
        $request = new ReplEcommHierarchy();
        $request->setReplRequest($this->params);
        $response = $this->executeMethod("ReplEcommHierarchy", $request);
        $result = $response ? $response->getResult() : null;
        $this->assertInstanceOf(ReplHierarchyResponse::class, $result);
        $this->assertNotNull($result->getHierarchies());
        $this->assertInstanceOf(ArrayOfReplHierarchy::class, $result->getHierarchies());
    }
}
