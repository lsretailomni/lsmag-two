<?php

namespace Ls\Omni\Test\Unit\Client\Ecommerce\Operation;

use \Ls\Omni\Client\Ecommerce\Entity\ArrayOfReplAttributeOptionValue;
use \Ls\Omni\Client\Ecommerce\Entity\ReplAttributeOptionValueResponse;
use \Ls\Omni\Client\Ecommerce\Entity\ReplEcommAttributeOptionValue;

class ReplEcommAttributeOptionValueTest extends ReplicationTest
{
    public function testReplEcommAttributeOptionValue()
    {
        $request = new ReplEcommAttributeOptionValue();
        $request->setReplRequest($this->params);
        $response = $this->client->ReplEcommAttributeOptionValue($request);
        $result   = $response->getResult();
        $this->assertInstanceOf(ReplAttributeOptionValueResponse::class, $result);
        $this->assertNotNull($result->getOptionValues());
        $this->assertInstanceOf(ArrayOfReplAttributeOptionValue::class, $result->getOptionValues());
    }
}