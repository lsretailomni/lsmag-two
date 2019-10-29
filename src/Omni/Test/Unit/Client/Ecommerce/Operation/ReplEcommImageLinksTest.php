<?php

namespace Ls\Omni\Test\Unit\Client\Ecommerce\Operation;

use Ls\Omni\Client\Ecommerce\Entity\ReplEcommImageLinks;
use Ls\Omni\Client\Ecommerce\Entity\ReplImageLinkResponse;
use Ls\Omni\Client\Ecommerce\Entity\ArrayOfReplImageLink;

class ReplEcommImageLinksTest extends ReplicationTest
{
    public function testReplEcommImageLinks()
    {
        $request = new ReplEcommImageLinks();
        $request->setReplRequest($this->params);
        $response = $this->client->ReplEcommImageLinks($request);
        $result = $response->getResult();
        $this->assertInstanceOf(ReplImageLinkResponse::class, $result);
        $this->assertNotNull($result->getImageLinks());
        $this->assertInstanceOf(ArrayOfReplImageLink::class, $result->getImageLinks());
    }
}