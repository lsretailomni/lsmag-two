<?php

namespace Ls\Omni\Test\Unit\Client\Ecommerce\Operation;

use \Ls\Omni\Client\Ecommerce\Entity\ArrayOfReplBarcode;
use \Ls\Omni\Client\Ecommerce\Entity\ReplBarcodeResponse;
use \Ls\Omni\Client\Ecommerce\Entity\ReplEcommBarcodes;

class ReplEcommBarcodesTest extends ReplicationTest
{
    public function testReplEcommBarcodes()
    {
        $request = new ReplEcommBarcodes();
        $request->setReplRequest($this->params);
        $response = $this->executeMethod("ReplEcommBarcodes", $request);
        $result = $response ? $response->getResult() : null;
        $this->assertInstanceOf(ReplBarcodeResponse::class, $result);
        $this->assertNotNull($result->getBarcodes());
        $this->assertInstanceOf(ArrayOfReplBarcode::class, $result->getBarcodes());
    }
}
