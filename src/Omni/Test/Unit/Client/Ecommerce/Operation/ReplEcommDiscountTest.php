<?php

namespace Ls\Omni\Test\Unit\Client\Ecommerce\Operation;

use \Ls\Omni\Client\Ecommerce\Entity\ArrayOfReplDiscount;
use \Ls\Omni\Client\Ecommerce\Entity\ReplDiscountResponse;
use \Ls\Omni\Client\Ecommerce\Entity\ReplEcommDiscounts;

class ReplEcommDiscountTest extends ReplicationTest
{
    public function testReplEcommAttribute()
    {
        $request = new ReplEcommDiscounts();
        $request->setReplRequest($this->params);
        $response = $this->executeMethod("ReplEcommDiscounts", $request);
        $result = $response ? $response->getResult() : null;
        $this->assertInstanceOf(ReplDiscountResponse::class, $result);
        $this->assertNotNull($result->getDiscounts());
        $this->assertInstanceOf(ArrayOfReplDiscount::class, $result->getDiscounts());
    }
}
