<?php

namespace Ls\Omni\Test\Unit\Client\Ecommerce\Operation;

use \Ls\Omni\Client\Ecommerce\Entity\ArrayOfReplAttribute;
use \Ls\Omni\Client\Ecommerce\Entity\ReplAttributeResponse;
use \Ls\Omni\Client\Ecommerce\Entity\ReplEcommAttribute;

class ReplEcommAttributeTest extends ReplicationTest
{
    public function testReplEcommAttribute()
    {
        $request = new ReplEcommAttribute();
        $request->setReplRequest($this->params);
        $response = $this->executeMethod("ReplEcommAttribute", $request);
        $result = $response ? $response->getResult() : null;
        $this->assertInstanceOf(ReplAttributeResponse::class, $result);
        $this->assertNotNull($result->getAttributes());
        $this->assertInstanceOf(ArrayOfReplAttribute::class, $result->getAttributes());
    }
}
