<?php

namespace Ls\Omni\Test\Unit\Client\Ecommerce\Operation;

use Ls\Omni\Client\Ecommerce\ClassMap;
use Ls\Omni\Client\Ecommerce\Entity\ReplEcommHierarchyLeaf;
use Ls\Omni\Client\Ecommerce\Entity\ReplHierarchyLeafResponse;
use Ls\Omni\Client\Ecommerce\Entity\ArrayOfReplHierarchyLeaf;
use Ls\Omni\Service\ServiceType;
use Ls\Omni\Service\Soap\Client as OmniClient;
use Zend\Uri\UriFactory;

class ReplEcommHierarchyLeafTest extends ReplicationTest
{

    public function testReplEcommHierarchyLeaf()
    {
        $request = new ReplEcommHierarchyLeaf();
        $request->setReplRequest($this->params);
        $response = $this->client->ReplEcommHierarchyLeaf($request);
        $result = $response->getResult();
        $this->assertInstanceOf(ReplHierarchyLeafResponse::class, $result);
        $this->assertNotNull($result->getLeafs());
        $this->assertInstanceOf(ArrayOfReplHierarchyLeaf::class, $result->getLeafs());
    }
}