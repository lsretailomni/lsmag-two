<?php

namespace Ls\Omni\Test\Unit\Client\Ecommerce\Operation;

use \Ls\Omni\Client\Ecommerce\Entity\ArrayOfReplItemVariantRegistration;
use \Ls\Omni\Client\Ecommerce\Entity\ReplEcommItemVariantRegistrations;
use \Ls\Omni\Client\Ecommerce\Entity\ReplItemVariantRegistrationResponse;

class ReplEcommItemVariantRegistrationsTest extends ReplicationTest
{
    public function testReplEcommAttribute()
    {
        $request = new ReplEcommItemVariantRegistrations();
        $request->setReplRequest($this->params);
        $response = $this->executeMethod("ReplEcommItemVariantRegistrations", $request);
        $result = $response ? $response->getResult() : null;
        $this->assertInstanceOf(ReplItemVariantRegistrationResponse::class, $result);
        $this->assertNotNull($result->getItemVariantRegistrations());
        $this->assertInstanceOf(ArrayOfReplItemVariantRegistration::class, $result->getItemVariantRegistrations());
    }
}
