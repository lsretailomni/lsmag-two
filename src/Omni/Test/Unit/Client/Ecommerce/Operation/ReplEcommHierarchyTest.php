<?php

namespace Ls\Omni\Test\Unit\Client\Ecommerce\Operation;

use \Ls\Omni\Client\Ecommerce\Entity\ReplEcommHierarchy;
use \Ls\Omni\Client\Ecommerce\Entity\ReplHierarchyResponse;
use \Ls\Omni\Client\Ecommerce\Entity\ArrayOfReplHierarchy;

class ReplEcommHierarchyTest extends ReplicationTest
{
    public function testReplEcommHierarchy()
    {
        $request = new ReplEcommHierarchy();
        $request->setReplRequest($this->params);
        $response = $this->client->ReplEcommHierarchy($request);
        $result = $response->getResult();
        $this->assertInstanceOf(ReplHierarchyResponse::class, $result);
        $this->assertNotNull($result->getHierarchies());
        $this->assertInstanceOf(ArrayOfReplHierarchy::class, $result->getHierarchies());
    }
}