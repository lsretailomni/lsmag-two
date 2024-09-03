<?php

namespace Ls\Omni\Test\Unit\Client\Ecommerce\Operation;

use \Ls\Omni\Client\Ecommerce\Entity\ArrayOfReplExtendedVariantValue;
use \Ls\Omni\Client\Ecommerce\Entity\ReplEcommExtendedVariants;
use \Ls\Omni\Client\Ecommerce\Entity\ReplExtendedVariantValuesResponse;

class ReplEcommExtendedVariantsTest extends ReplicationTest
{
    public function testReplEcommAttribute()
    {
        $request = new ReplEcommExtendedVariants();
        $request->setReplRequest($this->params);
        $response = $this->executeMethod("ReplEcommExtendedVariants", $request);
        $result = $response ? $response->getResult() : null;
        $this->assertInstanceOf(ReplExtendedVariantValuesResponse::class, $result);
        $this->assertNotNull($result->getExtendedVariantValue());
        $this->assertInstanceOf(ArrayOfReplExtendedVariantValue::class, $result->getExtendedVariantValue());
    }
}
