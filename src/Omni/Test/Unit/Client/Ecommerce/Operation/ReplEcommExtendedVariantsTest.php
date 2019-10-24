<?php

namespace Ls\Omni\Test\Unit\Client\Ecommerce\Operation;

use Ls\Omni\Client\Ecommerce\Entity\ReplEcommExtendedVariants;
use Ls\Omni\Client\Ecommerce\Entity\ReplExtendedVariantValuesResponse;
use Ls\Omni\Client\Ecommerce\Entity\ArrayOfReplExtendedVariantValue;

class ReplEcommExtendedVariantsTest extends ReplicationTest
{
    public function testReplEcommAttribute()
    {
        $request = new ReplEcommExtendedVariants();
        $request->setReplRequest($this->params);
        $response = $this->client->ReplEcommExtendedVariants($request);
        $result = $response->getResult();
        $this->assertInstanceOf(ReplExtendedVariantValuesResponse::class, $result);
        $this->assertNotNull($result->getExtendedVariantValue());
        $this->assertInstanceOf(ArrayOfReplExtendedVariantValue::class, $result->getExtendedVariantValue());
    }
}