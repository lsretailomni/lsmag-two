<?php

namespace Ls\Omni\Test\Unit\Client\Ecommerce\Operation;

use \Ls\Omni\Client\Ecommerce\Entity\ArrayOfReplImageLink;
use \Ls\Omni\Client\Ecommerce\Entity\ReplEcommImageLinks;
use \Ls\Omni\Client\Ecommerce\Entity\ReplImageLinkResponse;

class ReplEcommImageLinksTest extends ReplicationTest
{
    public function testReplEcommImageLinks()
    {
        $request = new ReplEcommImageLinks();
        $request->setReplRequest($this->params);
        $response = $this->executeMethod("ReplEcommImageLinks", $request);
        $result = $response ? $response->getResult() : null;
        $this->assertInstanceOf(ReplImageLinkResponse::class, $result);
        $this->assertNotNull($result->getImageLinks());
        $this->assertInstanceOf(ArrayOfReplImageLink::class, $result->getImageLinks());
    }
}
