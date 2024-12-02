<?php

namespace Ls\Omni\Test\Unit\Client\Ecommerce\Operation;

use \Ls\Omni\Client\Ecommerce\Entity\ArrayOfReplAttributeValue;
use \Ls\Omni\Client\Ecommerce\Entity\ReplAttributeValueResponse;
use \Ls\Omni\Client\Ecommerce\Entity\ReplEcommAttributeValue;

class ReplEcommAttributeValueTest extends ReplicationTest
{
    public function testReplEcommAttributeValue()
    {
        $request = new ReplEcommAttributeValue();
        $request->setReplRequest($this->params);
        $response = $this->executeMethod("ReplEcommAttributeValue", $request);
        $result = $response ? $response->getResult() : null;
        $this->assertInstanceOf(ReplAttributeValueResponse::class, $result);
        $this->assertNotNull($result->getValues());
        $this->assertInstanceOf(ArrayOfReplAttributeValue::class, $result->getValues());
    }
}
