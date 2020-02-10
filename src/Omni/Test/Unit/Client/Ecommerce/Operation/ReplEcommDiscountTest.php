<?php

namespace Ls\Omni\Test\Unit\Client\Ecommerce\Operation;

use \Ls\Omni\Client\Ecommerce\Entity\ReplEcommDiscounts;
use \Ls\Omni\Client\Ecommerce\Entity\ReplDiscountResponse;
use \Ls\Omni\Client\Ecommerce\Entity\ArrayOfReplDiscount;

class ReplEcommDiscountTest extends ReplicationTest
{
    public function testReplEcommAttribute()
    {
        $request = new ReplEcommDiscounts();
        $request->setReplRequest($this->params);
        $response = $this->client->ReplEcommDiscounts($request);
        $result = $response->getResult();
        $this->assertInstanceOf(ReplDiscountResponse::class, $result);
        $this->assertNotNull($result->getDiscounts());
        $this->assertInstanceOf(ArrayOfReplDiscount::class, $result->getDiscounts());
    }
}